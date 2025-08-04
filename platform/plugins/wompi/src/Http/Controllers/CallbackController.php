<?php

namespace Functionbytes\Wompi\Http\Controllers;

use Botble\Ecommerce\Models\Order;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Supports\PaymentHelper;
use Functionbytes\Wompi\Providers\WompiServiceProvider;
use Functionbytes\Wompi\Services\WompiService;
use Functionbytes\Wompi\Traits\PaymentsTrait;
use Illuminate\Http\Request;
use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Log;
use Exception;

class CallbackController extends BaseController
{
    use PaymentsTrait;
    /**
     * Handle payment callback from Wompi checkout
     * This is called when user returns to the website after payment
     */
    public function callback(Request $request)
    {
        $transactionId = $request->input('id');
        $reference = $request->input('reference');
        $checkoutToken = $request->input('checkout_token');
        $status = $request->input('status');

        Log::info('Wompi Callback received', [
            'transaction_id' => $transactionId,
            'reference' => $reference,
            'status' => $status,
            'checkout_token' => $checkoutToken
        ]);
        try {
            // Validate required parameters
            if (!$transactionId) {
                Log::error('Wompi Callback: Missing transaction ID and reference');
                return redirect()->to(PaymentHelper::getCancelURL($checkoutToken))
                    ->with('error_msg', __('Invalid payment data'));
            }

            // If we have transaction ID, get details from Wompi API
            if ($transactionId) {
                $wompiService = new WompiService();
                $transaction = $wompiService->getTransaction($transactionId);
                $transactionData = $transaction['data'] ?? [];

                $status = $transactionData['status'] ?? $status;
                $reference = $transactionData['reference'] ?? $reference;
                $amount = ($transactionData['amount_in_cents'] ?? 0) / 100;

                Log::info('Wompi API Transaction details', $transactionData);
            }

            // Find order by reference or token
            $order = null;
            if ($reference) {
                $order = Order::where('code', $reference)->with('payment')->first();
            }
            if (!$order && $checkoutToken) {
                $order = Order::where('token', $checkoutToken)->with('payment')->first();
            }

            if (!$order) {
                Log::error('Wompi Callback: Order not found', [
                    'reference' => $reference,
                    'checkout_token' => $checkoutToken
                ]);
                return redirect()->to(PaymentHelper::getCancelURL($checkoutToken))
                    ->with('error_msg', __('Order not found'));
            }

            Log::info('Wompi Callback: Order found', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'order_token' => $order->token,
                'payment_status' => $status
            ]);

            // Handle payment based on status
            if ($status === 'APPROVED') {
                // Check if payment already processed

                if ($order->payment && $order->payment->status == PaymentStatusEnum::COMPLETED) {
                    Log::info('Wompi Callback: Payment already processed', [
                        'order_id' => $order->id
                    ]);
                    return redirect()->to(PaymentHelper::getRedirectURL($checkoutToken))
                        ->with('success_msg', __('Payment already completed'));
                }


                // For callback, we rely on webhook for actual payment processing
                // Just redirect user to success page
                Log::info('Wompi Callback: Approved transaction, redirecting to success', [
                    'order_id' => $order->id,
                    'transaction_id' => $transactionId
                ]);

                return redirect()->to(PaymentHelper::getRedirectURL($checkoutToken))
                    ->with('success_msg', __('Payment completed successfully'));
            } else {
                // Payment failed or pending
                Log::warning('Wompi Callback: Payment not approved', [
                    'status' => $status,
                    'order_id' => $order->id,
                    'transaction_id' => $transactionId
                ]);

                return redirect()->to(PaymentHelper::getCancelURL($checkoutToken))
                    ->with('error_msg', __('Payment was not successful'));
            }

        } catch (Exception $e) {
            Log::error('Wompi Callback Error: ' . $e->getMessage(), [
                'transaction_id' => $transactionId,
                'reference' => $reference,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->to(PaymentHelper::getCancelURL($checkoutToken))
                ->with('error_msg', __('Payment processing failed'));
        }
    }
}
