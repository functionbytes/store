<?php

namespace Functionbytes\Wompi\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Supports\PaymentHelper;
use Functionbytes\Wompi\Providers\WompiServiceProvider;
use Functionbytes\Wompi\Services\WompiService;
use Functionbytes\Wompi\Traits\PaymentsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Botble\Ecommerce\Models\Order;
use Exception;

class WompiController extends BaseController
{
    use PaymentsTrait;

    /**
     * Handle user redirection from Wompi Web Checkout
     */
    public function callback(Request $request, BaseHttpResponse $response)
    {
        $transactionId = $request->input('id');
        $reference = $request->input('reference');
        $checkoutToken = $request->input('checkout_token');

        Log::info('Wompi Callback received', [
            'transaction_id' => $transactionId,
            'reference' => $reference,
            'checkout_token' => $checkoutToken,
            'all_params' => $request->all()
        ]);

        try {
            $wompiService = new WompiService();

            if (!$transactionId) {
                Log::error('Wompi Callback: Missing transaction ID and reference');
                return $response
                    ->setError()
                    ->setNextUrl(PaymentHelper::getCancelURL($checkoutToken))
                    ->setMessage(__('Invalid payment data'));
            }

            // Get transaction details from Wompi API
            $transaction = $wompiService->getTransaction($transactionId);
            $transactionData = $transaction['data'] ?? [];

            $status = $transactionData['status'] ?? 'PENDING';
            $reference = $transactionData['reference'] ?? $reference;
            $amount = ($transactionData['amount_in_cents'] ?? 0) / 100;

            Log::info('Wompi Transaction details', [
                'transaction_id' => $transactionId,
                'reference' => $reference,
                'status' => $status,
                'amount' => $amount,
                'currency' => $transactionData['currency'] ?? 'COP',
                'payment_method' => $transactionData['payment_method_type'] ?? 'unknown'
            ]);

            // Find order by token (reference) or by code
            $order = null;
            if ($reference) {
                $order = Order::where('token', $reference)->with('payment')->first();
                if (!$order) {
                    $order = Order::where('code', $reference)->with('payment')->first();
                }
            }
            
            // If still no order and we have checkout_token, try that
            if (!$order && $checkoutToken) {
                $order = Order::where('token', $checkoutToken)->with('payment')->first();
            }

            if (!$order) {
                Log::error('Wompi Callback: Order not found', ['reference' => $reference]);
                return $response
                    ->setError()
                    ->setNextUrl(PaymentHelper::getCancelURL($checkoutToken))
                    ->setMessage(__('Order not found'));
            }

            // Check if payment was successful
            if ($status === 'APPROVED') {
                // Check if payment already processed
                if ($order->payment && $order->payment->status == PaymentStatusEnum::COMPLETED) {
                    Log::info('Wompi Callback: Payment already processed', ['reference' => $reference]);

                    $successUrl = url("/orden/{$order->token}/success");
                    return $response
                        ->setNextUrl($successUrl)
                        ->setMessage(__('Payment already completed'));
                }

                // Check if there's a pending payment to update
                $existingPayment = $order->payment;

                if ($existingPayment && $existingPayment->status == PaymentStatusEnum::PENDING) {
                    // Update existing pending payment with Wompi transaction ID
                    $this->updatePaymentChargeId($existingPayment->charge_id, $transactionId, [
                        'wompi_transaction_data' => $transactionData,
                        'payment_method' => $transactionData['payment_method_type'] ?? 'unknown'
                    ]);

                    // Update status to completed
                    $existingPayment->status = PaymentStatusEnum::COMPLETED;
                    $existingPayment->save();

                    $chargeId = $transactionId;

                    Log::info('Wompi Callback: Updated existing pending payment', [
                        'old_charge_id' => $existingPayment->charge_id,
                        'new_charge_id' => $chargeId,
                        'reference' => $reference
                    ]);
                } else {
                    // Create new payment record
                    $chargeId = $this->storeLocalPayment([
                        'amount' => $amount,
                        'currency' => $transactionData['currency'] ?? 'COP',
                        'charge_id' => $transactionId,
                        'payment_channel' => WompiServiceProvider::MODULE_NAME,
                        'status' => PaymentStatusEnum::COMPLETED,
                        'order_id' => $order->id,
                        'customer_id' => $order->user_id,
                        'customer_type' => $order->user_type ?? 'Botble\\ACL\\Models\\User',
                        'payment_type' => 'direct'
                    ]);

                    Log::info('Wompi Callback: Created new payment record', [
                        'charge_id' => $chargeId,
                        'reference' => $reference
                    ]);
                }

                // Trigger post-payment processing
                $this->afterPaymentCompleted($chargeId);

                Log::info('Wompi Callback: Payment processed successfully', [
                    'reference' => $reference,
                    'charge_id' => $chargeId
                ]);

                // Redirect to order success page
                $successUrl = url("/orden/{$order->token}/success");
                Log::info('Wompi Callback: Redirecting to success URL', [
                    'success_url' => $successUrl,
                    'order_token' => $order->token
                ]);

                return $response
                    ->setNextUrl($successUrl)
                    ->setMessage(__('Payment completed successfully'));
            } else {
                Log::warning('Wompi Callback: Transaction not approved', [
                    'status' => $status,
                    'transaction_id' => $transactionId,
                    'reference' => $reference
                ]);

                return $response
                    ->setError()
                    ->setNextUrl(PaymentHelper::getCancelURL($checkoutToken))
                    ->setMessage(__('Payment was not successful'));
            }

        } catch (Exception $e) {
            Log::error('Wompi Callback Error: ' . $e->getMessage(), [
                'transaction_id' => $transactionId,
                'reference' => $reference,
                'trace' => $e->getTraceAsString()
            ]);

            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL($checkoutToken))
                ->setMessage(__('Payment processing failed'));
        }
    }

    /**
     * Handle webhook from Wompi (server-to-server notification)
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature');

        Log::info('Wompi Webhook received', [
            'event' => $payload['event'] ?? 'unknown',
            'timestamp' => $payload['timestamp'] ?? now()->timestamp,
            'has_signature' => !empty($signature)
        ]);

        if (!$signature) {
            Log::error('Wompi Webhook: Missing signature header');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        try {
            $wompiService = new WompiService();

            // Verify webhook signature
            if (!$wompiService->verifySignature($payload, $signature)) {
                Log::error('Wompi Webhook: Invalid signature verification');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $transaction = $payload['data']['transaction'] ?? null;

            if (!$transaction) {
                Log::error('Wompi Webhook: No transaction data in payload');
                return response()->json(['error' => 'No transaction data'], 400);
            }

            // Process the webhook transaction
            $result = $this->processWebhookTransaction($transaction);

            if ($result['success']) {
                return response()->json(['status' => 'ok'], 200);
            } else {
                return response()->json(['error' => $result['message']], $result['code'] ?? 400);
            }

        } catch (Exception $e) {
            Log::error('Wompi Webhook Processing Error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle transaction inquiry (optional endpoint for checking status)
     */
    public function checkTransaction(Request $request, string $transactionId)
    {
        try {
            $wompiService = new WompiService();
            $transaction = $wompiService->getTransaction($transactionId);

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Simulate webhook for local development
     * URL: /payment/wompi/simulate-webhook/{transactionId}
     */
    public function simulateWebhook(Request $request, string $transactionId)
    {
        if (!app()->environment('local')) {
            return response('Not available in production', 403);
        }

        try {
            $wompiService = new WompiService();
            $transaction = $wompiService->getTransaction($transactionId);

            // Simular payload de webhook
            $simulatedPayload = [
                'event' => 'transaction.updated',
                'data' => [
                    'transaction' => $transaction['data']
                ],
                'signature' => [
                    'checksum' => 'simulated_checksum_for_local_dev'
                ],
                'timestamp' => now()->timestamp,
                'sent_at' => now()->toISOString()
            ];

            Log::info('Simulating Wompi webhook for local development', $simulatedPayload);

            // Procesar como si fuera un webhook real (saltando verificación de firma)
            $result = $this->processWebhookTransaction($simulatedPayload['data']['transaction']);

            return response()->json([
                'success' => true,
                'message' => 'Webhook simulated successfully',
                'transaction' => $transaction['data']
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Process webhook transaction (extracted for reuse)
     */
    private function processWebhookTransaction(array $transaction): array
    {
        $reference = $transaction['reference'];
        $transactionId = $transaction['id'];
        $status = $transaction['status'];
        $amountInCents = $transaction['amount_in_cents'];

        Log::info('Processing webhook transaction', [
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'status' => $status,
            'amount_cents' => $amountInCents
        ]);

        // Find the order
        $order = Order::where('code', $reference)->with('payment')->first();

        if (!$order) {
            Log::error("Wompi Webhook: Order with reference {$reference} not found");
            return ['success' => false, 'message' => 'Order not found', 'code' => 404];
        }

        // Check if already processed
        if ($order->payment && $order->payment->status == PaymentStatusEnum::COMPLETED) {
            Log::info("Wompi Webhook: Order {$reference} already processed");
            return ['success' => true, 'message' => 'Already processed'];
        }

        // Check if there's a pending payment to update
        $existingPayment = $order->payment;

        // Verify amount (convert cents to currency)
        $receivedAmount = $amountInCents / 100;
        $expectedAmount = round($order->amount, 2);

        if (abs($receivedAmount - $expectedAmount) > 0.01) {
            Log::error("Wompi Webhook: Amount mismatch for order {$reference}", [
                'expected' => $expectedAmount,
                'received' => $receivedAmount,
                'difference' => abs($receivedAmount - $expectedAmount)
            ]);
            return ['success' => false, 'message' => 'Amount mismatch', 'code' => 400];
        }

        // Determine payment status based on Wompi status
        $paymentStatus = match($status) {
            'APPROVED' => PaymentStatusEnum::COMPLETED,
            'DECLINED', 'ERROR', 'VOIDED' => PaymentStatusEnum::FAILED,
            'PENDING' => PaymentStatusEnum::PENDING,
            default => PaymentStatusEnum::PENDING
        };

        // Handle payment record creation or update
        if ($existingPayment && $existingPayment->status == PaymentStatusEnum::PENDING) {
            // Update existing pending payment
            $this->updatePaymentChargeId($existingPayment->charge_id, $transactionId, [
                'wompi_transaction_data' => $transaction,
                'payment_method' => $transaction['payment_method_type'] ?? 'unknown'
            ]);

            $existingPayment->status = $paymentStatus;
            $existingPayment->save();

            $chargeId = $transactionId;

            Log::info("Wompi Webhook: Updated existing pending payment for order {$reference}", [
                'old_charge_id' => $existingPayment->charge_id,
                'new_charge_id' => $chargeId,
                'status' => $paymentStatus->value ?? $paymentStatus
            ]);
        } else {
            // Create new payment record
            $chargeId = $this->storeLocalPayment([
                'amount' => $receivedAmount,
                'currency' => $transaction['currency'] ?? 'COP',
                'charge_id' => $transactionId,
                'payment_channel' => WompiServiceProvider::MODULE_NAME,
                'status' => $paymentStatus,
                'order_id' => $order->id,
                'customer_id' => $order->user_id,
                'customer_type' => $order->user_type ?? 'Botble\\ACL\\Models\\User',
                'payment_type' => 'webhook'
            ]);

            Log::info("Wompi Webhook: Created new payment record for order {$reference}", [
                'charge_id' => $chargeId,
                'status' => $paymentStatus->value ?? $paymentStatus
            ]);
        }

        // If payment completed, trigger post-payment processing
        if ($paymentStatus == PaymentStatusEnum::COMPLETED) {
            $this->afterPaymentCompleted($chargeId);

            Log::info("Wompi Webhook: Payment completed successfully for order {$reference}", [
                'charge_id' => $chargeId,
                'amount' => $receivedAmount
            ]);
        } elseif ($paymentStatus == PaymentStatusEnum::FAILED) {
            Log::warning("Wompi Webhook: Payment failed for order {$reference}", [
                'charge_id' => $chargeId,
                'status' => $status
            ]);
        }

        return ['success' => true, 'message' => 'Payment processed successfully'];
    }

    /**
     * Debug configuration - only available in local environment
     */
    public function debugConfig()
    {
        if (!app()->environment(['local', 'testing']) && !config('app.debug')) {
            return response('Not available in production', 403);
        }

        $moduleName = WompiServiceProvider::MODULE_NAME;
        
        $debugInfo = [
            'module_name' => $moduleName,
            'payment_status' => get_payment_setting('status', $moduleName),
            'payment_name' => get_payment_setting('name', $moduleName),
            'payment_description' => get_payment_setting('description', $moduleName),
            'payment_mode' => get_payment_setting('mode', $moduleName),
            'has_public_key' => !empty(get_payment_setting('public_key', $moduleName)),
            'has_private_key' => !empty(get_payment_setting('private_key', $moduleName)),
            'has_integrity_secret' => !empty(get_payment_setting('integrity_secret', $moduleName)),
            'has_event_secret' => !empty(get_payment_setting('event_secret', $moduleName)),
            'service_class_registered' => apply_filters(PAYMENT_FILTER_GET_SERVICE_CLASS, null, $moduleName),
            'plugin_active' => is_plugin_active('payment'),
            'wompi_service_provider_loaded' => class_exists(WompiServiceProvider::class),
        ];

        return response()->json($debugInfo, 200, [], JSON_PRETTY_PRINT);
    }
}
