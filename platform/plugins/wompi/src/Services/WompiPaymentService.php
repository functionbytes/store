<?php

namespace Functionbytes\Wompi\Services;

use Functionbytes\Wompi\Services\Abstracts\PaymentAbstract;
use Functionbytes\Wompi\Providers\WompiServiceProvider;
use Functionbytes\Wompi\Traits\PaymentsTrait;
use Botble\Ecommerce\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class WompiPaymentService extends PaymentAbstract
{
    use PaymentsTrait;
    public function makePayment(Request $request): string
    {
        // FORZAR LOG PARA DEBUG
        file_put_contents(storage_path('logs/wompi-debug.log'), "[" . date('Y-m-d H:i:s') . "] WompiPaymentService::makePayment called\n", FILE_APPEND);
        
        \Log::info('WompiPaymentService::makePayment called', [
            'request_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->url()
        ]);

        try {
            $paymentData = $this->preparePaymentData($request);
            \Log::info('WompiPaymentService: Payment data prepared', $paymentData);
        } catch (\Exception $e) {
            \Log::error('WompiPaymentService: Failed to prepare payment data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        try {
            // Verificar configuración antes de proceder
            $wompiStatus = get_payment_setting('status', WompiServiceProvider::MODULE_NAME);
            \Log::info('WompiPaymentService: Payment status check', [
                'wompi_status' => $wompiStatus,
                'is_enabled' => (bool)$wompiStatus
            ]);
            
            if (!$wompiStatus) {
                throw new \Exception('Wompi payment method is not enabled in configuration');
            }
            
            $wompiService = new WompiService();
            \Log::info('WompiPaymentService: WompiService instantiated successfully');
            $orderToken = $this->getOrderToken($paymentData);


            // Debug payment data before creating pending payment
            \Log::info('Wompi Payment: Payment data debug', [
                'payment_data' => $paymentData,
                'order_token' => $orderToken,
                'has_order_id' => !empty($paymentData['order_id']),
                'order_id_value' => $paymentData['order_id'] ?? 'NULL'
            ]);

            // Create pending payment record in database
            try {
                $pendingChargeId = $this->createPendingPayment([
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'COP',
                'payment_channel' => WompiServiceProvider::MODULE_NAME,
                'order_id' => $paymentData['order_id'],
                'customer_id' => $paymentData['customer_id'] ?? null,
                'customer_type' => $paymentData['customer_type'] ?? 'Botble\\ACL\\Models\\User',
                'payment_type' => 'wompi_checkout'
                ]);

                \Log::info('Wompi Payment: Pending payment created successfully', [
                    'pending_charge_id' => $pendingChargeId
                ]);
            } catch (\Exception $e) {
                \Log::error('Wompi Payment: Failed to create pending payment', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'payment_data' => $paymentData
                ]);
                throw $e;
            }

            \Log::info('Wompi Payment: Created pending payment record', [
                'pending_charge_id' => $pendingChargeId,
                'order_token' => $orderToken,
                'amount' => $paymentData['amount'],
                'order_id' => $paymentData['order_id']
            ]);


            // Preparar datos para Wompi Web Checkout
            $wompiService->withData([
                'reference' => $orderToken, // Usar el token único de la orden
                'amount'           => $paymentData['amount'],
                'currency'         => $paymentData['currency'] ?? 'COP',
                'customer_email'   => $paymentData['address']['email'],
                'customer_name'    => trim(($paymentData['address']['first_name'] ?? '') . ' ' . ($paymentData['address']['last_name'] ?? '')),
                'customer_phone'   => $paymentData['address']['phone'] ?? '',
                'redirect_url'     => route('payment.wompi.callback') . '?checkout_token=' . $orderToken . '&reference=' . $orderToken, // URL de retorno
                'tax'              => $this->calculateTax($paymentData['amount']),
                // Datos completos de dirección de envío
                'shipping_address' => $paymentData['address']['address'] ?? '',
                'shipping_city'    => $paymentData['address']['city'] ?? '',
                'shipping_region'  => $paymentData['address']['state'] ?? $paymentData['address']['region'] ?? '',
                'shipping_phone'   => $paymentData['address']['phone'] ?? '',
                'shipping_country' => $paymentData['address']['country'] ?? 'CO', // Colombia por defecto
            ]);

            // En lugar de hacer exit(), vamos a obtener el contenido de la página
            $widgetContent = $wompiService->getWidgetPageContent();
            
            // Mostrar el contenido del widget y hacer exit()
            echo $widgetContent;
            exit();

            // Este return nunca se ejecutará pero es necesario para el tipo de retorno
            return $pendingChargeId;

        } catch (Throwable $exception) {
            \Log::error('WompiPaymentService::makePayment - Critical Error', [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'payment_data' => $paymentData ?? 'not_available'
            ]);
            
            $this->setErrorMessageAndLogging($exception, true);
            return '';
        }
    }

    private function getOrderToken(array $paymentData): string
    {
        // 1. Try checkout_token first (most common case)
        if (!empty($paymentData['checkout_token'])) {
            \Log::info('Wompi: Using checkout_token from paymentData', ['token' => $paymentData['checkout_token']]);
            return $paymentData['checkout_token'];
        }

        // 2. Try token field
        if (!empty($paymentData['token'])) {
            \Log::info('Wompi: Using token from paymentData', ['token' => $paymentData['token']]);
            return $paymentData['token'];
        }

        // 3. Intentar obtener usando order_id (handle array case)
        if (!empty($paymentData['order_id'])) {
            $orderIds = is_array($paymentData['order_id']) ? $paymentData['order_id'] : [$paymentData['order_id']];
            $orderId = $orderIds[0]; // Get first order ID
            $order = Order::find($orderId);
            if ($order && !empty($order->token)) {
                \Log::info('Wompi: Using token from Order model', [
                    'order_id' => $orderId,
                    'token' => $order->token
                ]);
                return $order->token;
            }
        }

        // 3. Buscar por código de orden si existe
        if (!empty($paymentData['order_code'])) {
            $order = Order::where('code', $paymentData['order_code'])->first();
            if ($order && !empty($order->token)) {
                \Log::info('Wompi: Using token from Order by code', [
                    'order_code' => $paymentData['order_code'],
                    'token' => $order->token
                ]);
                return $order->token;
            }
        }

        // 4. Como último recurso, generar un token temporal
        $fallbackToken = 'OR' . time() . '_' . Str::random(8);

        \Log::warning('Wompi: No order token found, using fallback', [
            'fallback_token' => $fallbackToken,
            'payment_data_keys' => array_keys($paymentData),
            'order_id' => $paymentData['order_id'] ?? 'not_set',
            'order_code' => $paymentData['order_code'] ?? 'not_set'
        ]);

        return $fallbackToken;
    }


    public function afterMakePayment(Request $request)
    {
        // No se necesita implementación ya que la redirección se maneja en makePayment
        // y el procesamiento del pago se hace vía webhook
    }

    public function getServiceProvider(): string
    {
        return WompiServiceProvider::MODULE_NAME;
    }

    public function isSupportRefundOnline(): bool
    {
        return false; // Wompi soporta reembolsos pero requiere implementación adicional
    }

    public function refund(string $chargeId, float $amount, array $options = []): array
    {
        // TODO: Implementar reembolsos usando la API de Wompi
        // Por ahora retornamos error
        return [
            'error' => true,
            'message' => 'Refunds are not implemented yet for Wompi.',
        ];
    }

    /**
     * Calculate tax based on Colombian tax rules
     * This is a simple example - adjust according to your business needs
     */
    private function calculateTax(float $amount): float
    {
        // Colombian IVA is typically 19%
        // Adjust this calculation based on your tax requirements
        return $amount * 0.19;
    }
}
