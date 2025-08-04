<?php

namespace Functionbytes\Wompi\Services;

use Functionbytes\Wompi\Providers\WompiServiceProvider;

class WompiService
{
    protected string $eventSecret;
    protected string $publicKey;
    protected string $privateKey;
    protected string $integritySecret;
    protected bool $isSandbox;
    protected array $data = [];
    protected string $baseUrl;

    public function __construct()
    {
        // Priorizar configuración del panel administrativo, fallback a .env
        $this->publicKey = $this->getConfigValue('public_key', 'WOMPI_PUBLIC_KEY');
        $this->privateKey = $this->getConfigValue('private_key', 'WOMPI_PRIVATE_KEY');
        $this->integritySecret = $this->getConfigValue('integrity_secret', 'WOMPI_INTEGRITY_SECRET');
        $this->eventSecret = $this->getConfigValue('event_secret', 'WOMPI_EVENT_SECRET');
        $this->isSandbox = $this->getConfigValue('mode', 'WOMPI_MODE') === 'sandbox';

        // Log para debug de credenciales
        \Log::info('Wompi Service Initialization', [
            'public_key_length' => strlen($this->publicKey),
            'public_key_preview' => substr($this->publicKey, 0, 20) . '...',
            'private_key_set' => !empty($this->privateKey),
            'integrity_secret_set' => !empty($this->integritySecret),
            'event_secret_set' => !empty($this->eventSecret),
            'is_sandbox' => $this->isSandbox,
            'config_source' => [
                'public_key' => $this->getConfigSource('public_key', 'WOMPI_PUBLIC_KEY'),
                'private_key' => $this->getConfigSource('private_key', 'WOMPI_PRIVATE_KEY'),
                'integrity_secret' => $this->getConfigSource('integrity_secret', 'WOMPI_INTEGRITY_SECRET'),
                'event_secret' => $this->getConfigSource('event_secret', 'WOMPI_EVENT_SECRET'),
                'mode' => $this->getConfigSource('mode', 'WOMPI_MODE')
            ]
        ]);

        // Validar credenciales esenciales
        if (empty($this->publicKey)) {
            throw new \Exception('Wompi public key is not configured. Configure it in Admin Panel > Payment Methods > Wompi or set WOMPI_PUBLIC_KEY in .env file');
        }

        if (empty($this->integritySecret)) {
            throw new \Exception('Wompi integrity secret is not configured. Configure it in Admin Panel > Payment Methods > Wompi or set WOMPI_INTEGRITY_SECRET in .env file');
        }

        // Event secret es opcional para pagos básicos pero requerido para webhooks
        if (empty($this->eventSecret)) {
            \Log::warning('Wompi Event Secret not configured. Webhook validation will not work.');
        }

        // Validar formato de public key
        if (!$this->validatePublicKeyFormat($this->publicKey)) {
            throw new \Exception('Wompi public key format is invalid. Should start with pub_test_ or pub_prod_');
        }

        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';

    }

    private function getConfigValue(string $settingKey, string $envKey): string
    {
        // Prioridad 1: Configuración del panel administrativo
        $adminValue = get_payment_setting($settingKey, WompiServiceProvider::MODULE_NAME);

        if (!empty($adminValue)) {
            return $adminValue;
        }

        // Prioridad 2: Variable de entorno
        return env($envKey, '');
    }


    /**
     * Verify webhook signature using event secret
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        if (empty($this->eventSecret)) {
            \Log::warning('Cannot verify webhook signature: Event Secret not configured');
            return false;
        }

        if (!isset($payload['data']['transaction'])) {
            return false;
        }

        $transaction = $payload['data']['transaction'];

        $concatenatedString = implode('', [
            $transaction['id'] ?? '',
            $transaction['status'] ?? '',
            $transaction['amount_in_cents'] ?? '',
            $transaction['currency'] ?? '',
            $payload['signature']['checksum'] ?? '',
        ]);

        $expectedSignature = hash_hmac('sha256', $concatenatedString, $this->eventSecret);

        return hash_equals($expectedSignature, $signature);
    }



    private function getConfigSource(string $settingKey, string $envKey): string
    {
        $adminValue = get_payment_setting($settingKey, WompiServiceProvider::MODULE_NAME);

        if (!empty($adminValue)) {
            return 'admin_panel';
        }

        if (!empty(env($envKey))) {
            return 'env_file';
        }

        return 'not_set';
    }

    /**
     * Validar que toda la configuración esté correcta
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        $warnings = [];

        // Validar public key
        if (empty($this->publicKey)) {
            $errors[] = 'Public Key no configurada';
        } elseif (!$this->validatePublicKeyFormat($this->publicKey)) {
            $errors[] = 'Public Key tiene formato inválido';
        }

        // Validar private key
        if (empty($this->privateKey)) {
            $errors[] = 'Private Key no configurada';
        }

        // Validar integrity secret
        if (empty($this->integritySecret)) {
            $errors[] = 'Integrity Secret no configurado';
        }

        // Verificar consistencia de modo (sandbox/production)
        $publicKeyMode = str_contains($this->publicKey, 'test') ? 'sandbox' : 'production';
        $configMode = $this->isSandbox ? 'sandbox' : 'production';

        if ($publicKeyMode !== $configMode) {
            $warnings[] = "Public Key es de $publicKeyMode pero el modo está configurado como $configMode";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'config_sources' => [
                'public_key' => $this->getConfigSource('public_key', 'WOMPI_PUBLIC_KEY'),
                'private_key' => $this->getConfigSource('private_key', 'WOMPI_PRIVATE_KEY'),
                'integrity_secret' => $this->getConfigSource('integrity_secret', 'WOMPI_INTEGRITY_SECRET'),
                'mode' => $this->getConfigSource('mode', 'WOMPI_MODE')
            ]
        ];
    }


    /**
     * Validar el formato de la public key
     */
    private function validatePublicKeyFormat(string $publicKey): bool
    {
        // Las public keys de Wompi tienen el formato:
        // Sandbox: pub_test_xxxxxx o pub_sandbox_xxxxxx
        // Producción: pub_prod_xxxxxx o pub_live_xxxxxx

        $validPrefixes = [
            'pub_test_',
            'pub_sandbox_',
            'pub_prod_',
            'pub_live_'
        ];

        foreach ($validPrefixes as $prefix) {
            if (str_starts_with($publicKey, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function withData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Generate integrity signature for Web Checkout
     * According to Wompi docs: Reference + AmountInCents + Currency + IntegritySecret
     */
    public function generateIntegritySignature(int $amountInCents, string $currency = 'COP'): string
    {
        $reference = $this->data['reference'];

        // Concatenate in the exact order required by Wompi:
        // <Reference><AmountInCents><Currency><IntegritySecret>
        // NOTE: expiration_time is NOT included in signature generation
        $concatenatedString = $reference . $amountInCents . $currency . $this->integritySecret;

        $signature = hash('sha256', $concatenatedString);

        \Log::info('Wompi Signature Generation (CORRECTED)', [
            'reference' => $reference,
            'amount_in_cents' => $amountInCents,
            'currency' => $currency,
            'concatenated_string' => $concatenatedString,
            'signature' => $signature,
            'integrity_secret_length' => strlen($this->integritySecret),
            'expiration_time_included' => 'NO - Per Wompi docs, expiration_time should NOT be included in signature'
        ]);

        return $signature;
    }

    /**
     * Get Web Checkout URL for form submission
     */
    public function getWebCheckoutUrl(): string
    {
        // CORREGIDO: Solo existe checkout.wompi.co para ambos entornos
        // La diferenciación se hace por credenciales, no por subdominio
        return 'https://checkout.wompi.co/p/';
    }

    /**
     * Redirect to Web Checkout using form submission
     */
    public function redirectToCheckoutPage(): void
    {
        try {
            // Preparar datos para el Widget
            $widgetData = $this->prepareWidgetData();

            \Log::info('Wompi Widget Data Full Debug', [
                'widget_data' => $widgetData,
                'public_key_valid' => !empty($widgetData['public_key']),
                'signature_generated' => !empty($widgetData['signature_integrity'])
            ]);

            // Renderizar página con Widget
            $this->renderWidgetPage($widgetData);

        } catch (\Exception $e) {
            \Log::error('Error in Wompi Widget', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
                'public_key_set' => !empty($this->publicKey),
                'integrity_secret_set' => !empty($this->integritySecret)
            ]);

            echo view('plugins/wompi::error', [
                'message' => $e->getMessage()
            ]);
            exit();
        }
    }

    /**
     * Renderizar página con Widget
     */
    private function renderWidgetPage(array $widgetData): void
    {
        // LOG CRÍTICO: Verificar qué llega exactamente al widget
        \Log::critical('Wompi Widget Page Render - DATOS COMPLETOS', [
            'widget_data_keys' => array_keys($widgetData),
            'public_key_exists' => isset($widgetData['public_key']),
            'public_key_value' => $widgetData['public_key'] ?? 'NOT_SET',
            'public_key_length' => isset($widgetData['public_key']) ? strlen($widgetData['public_key']) : 0,
            'signature_exists' => isset($widgetData['signature_integrity']),
            'signature_value' => $widgetData['signature_integrity'] ?? 'NOT_SET',
            'reference_value' => $widgetData['reference'] ?? 'NOT_SET',
            'full_widget_data' => $widgetData
        ]);

        // Verificar que los datos críticos estén presentes
        $requiredFields = ['public_key', 'amount_in_cents', 'reference', 'signature_integrity'];

        foreach ($requiredFields as $field) {
            if (empty($widgetData[$field])) {
                \Log::error("Wompi Widget: Missing required field: {$field}", [
                    'field' => $field,
                    'value' => $widgetData[$field] ?? 'NOT_SET',
                    'all_fields' => array_keys($widgetData)
                ]);
                throw new \Exception("Missing required field for widget: {$field}");
            }
        }

        echo view('plugins/wompi::widget', [
            'widgetData' => $widgetData,
            'originalAmount' => $this->data['amount'],
            'originalCurrency' => $this->data['currency'] ?? 'USD'
        ])->render();

        exit();
    }

    /**
     * Prepare widget page but return content instead of exit
     */
    public function getWidgetPageContent(): string
    {
        try {
            // Preparar datos para el Widget
            $widgetData = $this->prepareWidgetData();

            \Log::info('Wompi Widget Data Full Debug', [
                'widget_data' => $widgetData,
                'public_key_valid' => !empty($widgetData['public_key']),
                'signature_generated' => !empty($widgetData['signature_integrity'])
            ]);

            // Renderizar página con Widget y retornar contenido
            return view('plugins/wompi::widget', [
                'widgetData' => $widgetData,
                'originalAmount' => $this->data['amount'],
                'originalCurrency' => $this->data['currency'] ?? 'USD'
            ])->render();

        } catch (\Exception $e) {
            \Log::error('Error in Wompi Widget', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
                'public_key_set' => !empty($this->publicKey),
                'integrity_secret_set' => !empty($this->integritySecret)
            ]);

            return view('plugins/wompi::error', [
                'message' => $e->getMessage()
            ])->render();
        }
    }

    /**
     * Get transaction details from Wompi (using private key)
     */
    public function getTransaction(string $transactionId): array
    {
        return $this->makeApiRequest('GET', "/transactions/{$transactionId}");
    }

    /**
     * Verify transaction signature from webhook
     */
    public function verifySignature(array $payload, string $signature): bool
    {
        if (!isset($payload['data']['transaction'])) {
            return false;
        }

        $transaction = $payload['data']['transaction'];

        $concatenatedString = implode('', [
            $transaction['id'] ?? '',
            $transaction['status'] ?? '',
            $transaction['amount_in_cents'] ?? '',
            $transaction['currency'] ?? '',
            $payload['signature']['checksum'] ?? '',
        ]);

        $expectedSignature = hash_hmac('sha256', $concatenatedString, $this->integritySecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Make API request to Wompi
     */
    protected function makeApiRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->privateKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new \Exception('Error connecting to Wompi API: ' . $error);
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = 'Unknown error';

            if (isset($decodedResponse['error'])) {
                if (is_array($decodedResponse['error'])) {
                    $errorMessage = $decodedResponse['error']['reason'] ??
                        $decodedResponse['error']['type'] ??
                        json_encode($decodedResponse['error']);
                } else {
                    $errorMessage = $decodedResponse['error'];
                }
            }

            throw new \Exception("Wompi API Error (HTTP {$httpCode}): " . $errorMessage);
        }

        return $decodedResponse;
    }

    private function prepareWidgetData(): array
    {
        // Validar configuración antes de proceder
        $validation = $this->validateConfiguration();

        if (!$validation['valid']) {
            \Log::error('Wompi Configuration Error', $validation);
            throw new \Exception('Configuración de Wompi inválida: ' . implode(', ', $validation['errors']));
        }

        if (!empty($validation['warnings'])) {
            \Log::warning('Wompi Configuration Warnings', $validation['warnings']);
        }


        $originalAmount = $this->data['amount'];
        $originalCurrency = $this->data['currency'] ?? 'USD';

        // Convertir a COP (Wompi solo acepta COP)
        $currency = 'COP';
        if ($originalCurrency !== 'COP') {
            \Log::info('Wompi: Converting currency', [
                'from' => $originalCurrency,
                'to' => 'COP',
                'original_amount' => $originalAmount
            ]);

            $exchangeRate = match($originalCurrency) {
                'USD' => 4000,   // 1 USD = 4000 COP
                'EUR' => 4300,   // 1 EUR = 4300 COP
                default => 4000
            };

            $convertedAmount = $originalAmount * $exchangeRate;
            $convertedTax = ($this->data['tax'] ?? 0) * $exchangeRate;
        } else {
            $convertedAmount = $originalAmount;
            $convertedTax = $this->data['tax'] ?? 0;
        }

        // Convertir a centavos
        $amountInCents = (int)($convertedAmount * 100);
        $taxInCents = (int)($convertedTax * 100);

        // Limitar para sandbox - MANTENER LA PROPORCIÓN DEL TAX
        if ($this->isSandbox) {
            $maxAmount = 50000000; // 500,000 COP máximo para sandbox

            if ($amountInCents > $maxAmount) {
                // Calcular la proporción para mantener la relación tax/amount
                $originalTaxRatio = $convertedTax / $convertedAmount;
                $reducedAmountInPesos = $maxAmount / 100; // Convertir centavos a pesos
                $reducedTax = $reducedAmountInPesos * $originalTaxRatio;

                $amountInCents = $maxAmount;
                $taxInCents = (int)($reducedTax * 100);

                \Log::warning('Wompi: Amount and tax reduced proportionally for sandbox limits', [
                    'original_amount_cents' => (int)($convertedAmount * 100),
                    'original_tax_cents' => (int)($convertedTax * 100),
                    'reduced_amount_cents' => $amountInCents,
                    'reduced_tax_cents' => $taxInCents,
                    'original_tax_ratio' => $originalTaxRatio
                ]);
            }
        }

        // Preparar datos del cliente con teléfono formateado
        $customerData = $this->prepareCustomerData();

        // VERIFICAR CREDENCIALES ANTES DE CREAR WIDGET DATA
        if (empty($this->publicKey)) {
            throw new \Exception('Public key está vacía');
        }

        if (empty($this->integritySecret)) {
            throw new \Exception('Integrity Secret está vacío');
        }

        if (empty($this->data['reference'])) {
            throw new \Exception('Reference está vacía');
        }

        $expirationTime = $this->generateExpirationTime();

        $signature = $this->generateIntegritySignature($amountInCents, $currency);


        if (empty($signature)) {
            throw new \Exception('No se pudo generar la firma de integridad');
        }

        $widgetData = [
            'public_key' => $this->publicKey,
            'currency' => $currency,
            'amount_in_cents' => $amountInCents,
            'reference' => $this->data['reference'],
            'signature_integrity' => $signature,
            'expiration_time' => $expirationTime, // Agregar fecha de expiración
            'redirect_url' => $this->data['redirect_url'],
            'customer_data' => $customerData,
            'customer_email' => $this->data['customer_email'],
            'customer_name' => $this->data['customer_name'] ?? '',
            'customer_phone' => $this->data['customer_phone'] ?? '',
            'is_sandbox' => $this->isSandbox,
            'widget_url' => $this->getWidgetUrl()
        ];

        // Solo agregar impuestos si son válidos (mayor a 0)
        if ($taxInCents > 0) {
            $widgetData['tax_in_cents'] = [
                'vat' => $taxInCents,
                'consumption' => 0
            ];

            \Log::info('Wompi: Including tax data', [
                'original_tax' => $this->data['tax'] ?? 0,
                'converted_tax' => $convertedTax,
                'tax_in_cents' => $taxInCents
            ]);
        }

        // Validar y agregar dirección de envío solo si TODOS los campos requeridos están presentes
        $shippingData = $this->prepareShippingAddress();
        if ($shippingData) {
            $widgetData['shipping_address'] = $shippingData;
            \Log::info('Wompi: Including complete shipping address', $shippingData);
        } else {
            \Log::info('Wompi: Skipping shipping address (incomplete data)');
        }

        return $widgetData;
    }

    public function getWidgetUrl(): string
    {
        // CORREGIDO: Solo existe checkout.wompi.co para ambos entornos
        // La diferenciación se hace por credenciales, no por subdominio
        return 'https://checkout.wompi.co/widget.js';
    }


    /**
     * Preparar datos del cliente con formato de teléfono requerido por Wompi
     */
    private function prepareCustomerData(): array
    {
        $customerData = [
            'email' => $this->data['customer_email'],
            'full_name' => $this->data['customer_name'] ?? '',
        ];

        // Procesar número de teléfono
        $phoneData = $this->parsePhoneNumber($this->data['customer_phone'] ?? '');
        if ($phoneData) {
            $customerData['phone_number'] = $phoneData['number'];
            $customerData['phone_number_prefix'] = $phoneData['prefix'];

            \Log::info('Wompi: Phone number parsed', [
                'original' => $this->data['customer_phone'] ?? '',
                'prefix' => $phoneData['prefix'],
                'number' => $phoneData['number']
            ]);
        } else {
            // Si no se puede parsear, usar valores por defecto para Colombia
            $customerData['phone_number'] = '3001234567';
            $customerData['phone_number_prefix'] = '+57';

            \Log::warning('Wompi: Using default phone number (parsing failed)', [
                'original' => $this->data['customer_phone'] ?? 'empty'
            ]);
        }

        return $customerData;
    }

    private function generateExpirationTime(int $hoursFromNow = 24): string
    {
        // Formato ISO 8601 exacto que espera Wompi
        // Ejemplo: 2021-12-30T09:30:00.000Z
        return now()->addHours($hoursFromNow)->format('Y-m-d\TH:i:s.000\Z');
    }


    /**
     * Parsear número de teléfono para separar código de país y número
     */
    private function parsePhoneNumber(string $phone): ?array
    {
        if (empty($phone)) {
            return null;
        }

        // Limpiar el número
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);

        if (empty($cleanPhone)) {
            return null;
        }

        // Patrones comunes de números de teléfono
        $patterns = [
            // +57 3001234567 (formato internacional completo)
            '/^\+57(\d{10})$/' => ['+57', '$1'],
            // +573001234567 (formato internacional sin espacio)
            '/^\+57(\d{10})$/' => ['+57', '$1'],
            // 573001234567 (sin +)
            '/^57(\d{10})$/' => ['+57', '$1'],
            // 3001234567 (solo número local colombiano)
            '/^(\d{10})$/' => ['+57', '$1'],
            // +1 formato USA
            '/^\+1(\d{10})$/' => ['+1', '$1'],
            // Otros códigos internacionales comunes
            '/^\+(\d{1,4})(\d{6,10})$/' => ['+$1', '$2'],
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $cleanPhone)) {
                $prefix = str_replace(['$1', '$2'], [
                    preg_replace($pattern, '$1', $cleanPhone),
                    preg_replace($pattern, '$2', $cleanPhone)
                ], $replacement[0]);

                $number = str_replace(['$1', '$2'], [
                    preg_replace($pattern, '$1', $cleanPhone),
                    preg_replace($pattern, '$2', $cleanPhone)
                ], $replacement[1]);

                return [
                    'prefix' => $prefix,
                    'number' => $number
                ];
            }
        }

        // Si no coincide con ningún patrón, asumir que es colombiano si tiene 10 dígitos
        if (strlen($cleanPhone) === 10) {
            return [
                'prefix' => '+57',
                'number' => $cleanPhone
            ];
        }

        return null;
    }

    /**
     * Preparar dirección de envío con formato de teléfono correcto
     */
    private function prepareShippingAddress(): ?array
    {
        $addressLine1 = trim($this->data['shipping_address'] ?? '');
        $city = trim($this->data['shipping_city'] ?? '');
        $region = trim($this->data['shipping_region'] ?? '');
        $phone = trim($this->data['shipping_phone'] ?? '');

        // Verificar campos básicos
        if (empty($addressLine1) || empty($city) || empty($region)) {
            return null;
        }

        // Parsear teléfono de envío
        $phoneData = $this->parsePhoneNumber($phone);

        $shippingAddress = [
            'address_line_1' => $addressLine1,
            'city' => $city,
            'region' => $region,
            'country' => 'CO'
        ];

        // Solo agregar teléfono si se pudo parsear correctamente
        if ($phoneData) {
            $shippingAddress['phone_number'] = $phoneData['number'];
            $shippingAddress['phone_number_prefix'] = $phoneData['prefix'];
        } else {
            // Usar teléfono por defecto si no se puede parsear
            $shippingAddress['phone_number'] = '3001234567';
            $shippingAddress['phone_number_prefix'] = '+57';
        }

        return $shippingAddress;
    }

}
