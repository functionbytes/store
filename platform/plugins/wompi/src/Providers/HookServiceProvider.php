<?php

namespace Functionbytes\Wompi\Providers;

use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Functionbytes\Wompi\Services\WompiPaymentService;
use Functionbytes\Wompi\Services\WompiService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Agregar configuraciones de Wompi a la página de configuración de pagos
        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $settings) {
            return $settings . view('plugins/wompi::index')->render();
        }, 999);

        // Registrar Wompi como método de pago en el enum
        add_filter(BASE_FILTER_ENUM_ARRAY, function (array $values, string $class): array {
            if ($class === PaymentMethodEnum::class) {
                $values['WOMPI'] = WompiServiceProvider::MODULE_NAME;
            }
            return $values;
        }, 999, 2);

        // Definir la etiqueta para mostrar en el admin
        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class): string {
            if ($class === PaymentMethodEnum::class && $value === WompiServiceProvider::MODULE_NAME) {
                $value = 'Wompi';
            }
            return $value;
        }, 999, 2);

        // Agregar el método de pago a la lista de métodos disponibles
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data): ?string {
            if (get_payment_setting('status', WompiServiceProvider::MODULE_NAME)) {
                // Debug log para método de pago
                file_put_contents(storage_path('logs/wompi-method-registration.log'), "[" . date('Y-m-d H:i:s') . "] Payment method being registered\n", FILE_APPEND);

                PaymentMethods::method(WompiServiceProvider::MODULE_NAME, [
                    'html' => view(
                        'plugins/wompi::method',
                        array_merge($data, [
                            'moduleName' => WompiServiceProvider::MODULE_NAME,
                            'selecting_method' => PaymentMethods::getSelectingMethod()
                        ])
                    )->render(),
                ]);
            }
            return $html;
        }, 999, 2);

        // Registrar el servicio de pago de Wompi
        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function (?string $data, string $value): ?string {
            // Debug log forzado - TODAS las llamadas
            file_put_contents(storage_path('logs/wompi-service-registration.log'), "[" . date('Y-m-d H:i:s') . "] Service filter called for: '{$value}', current data: '{$data}', wompi module: '" . WompiServiceProvider::MODULE_NAME . "'\n", FILE_APPEND);

            if ($value === WompiServiceProvider::MODULE_NAME) {
                $data = WompiPaymentService::class;
                file_put_contents(storage_path('logs/wompi-service-registration.log'), "[" . date('Y-m-d H:i:s') . "] MATCH! Returning: {$data}\n", FILE_APPEND);
            }
            return $data;
        }, 20, 2);

        // Debug hook para interceptar checkout data
        add_filter(PAYMENT_FILTER_PAYMENT_DATA, function (array $data, $request): array {
            file_put_contents(storage_path('logs/wompi-checkout-data.log'), "[" . date('Y-m-d H:i:s') . "] Payment data filter called: " . json_encode($data) . "\n", FILE_APPEND);
            return $data;
        }, 999, 2);

        // Implementar checkout con Wompi (como PayPal)
        add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithWompi'], 2, 2);
    }

    /**
     * Handle Wompi checkout (similar to PayPal)
     */
    public function checkoutWithWompi(array $data, Request $request): array
    {
        if ($data['type'] !== WompiServiceProvider::MODULE_NAME) {
            return $data;
        }

        file_put_contents(storage_path('logs/wompi-checkout-handler.log'), "[" . date('Y-m-d H:i:s') . "] checkoutWithWompi called\n", FILE_APPEND);

        try {
            $wompiService = $this->app->make(WompiPaymentService::class);

            file_put_contents(storage_path('logs/wompi-checkout-handler.log'), "[" . date('Y-m-d H:i:s') . "] WompiPaymentService created, calling execute()\n", FILE_APPEND);

            $result = $wompiService->execute($request);

            file_put_contents(storage_path('logs/wompi-checkout-handler.log'), "[" . date('Y-m-d H:i:s') . "] execute() completed, result: " . ($result ?: 'NULL') . "\n", FILE_APPEND);

        } catch (\Exception $e) {
            file_put_contents(storage_path('logs/wompi-checkout-handler.log'), "[" . date('Y-m-d H:i:s') . "] Exception: " . $e->getMessage() . "\n", FILE_APPEND);

            $data['error'] = true;
            $data['message'] = $e->getMessage();
        }

        return $data;
    }

    /**
     * Calculate tax based on Colombian tax rules
     */
    private function calculateTax(float $amount): float
    {
        // Colombian IVA is typically 19%
        // Adjust this calculation based on your business needs
        return $amount * 0.19;
    }
}
