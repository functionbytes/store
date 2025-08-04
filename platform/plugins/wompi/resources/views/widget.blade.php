<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Procesando Pago') }} - {{ theme_option('site_title') }}</title>

    <!-- Favicon para evitar 404 -->
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #fbfbfb;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .payment-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .payment-header {
            background: #fb0000;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .payment-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        .payment-body {
            padding: 2rem;
        }
        .payment-summary {
            background: #fbfbfb;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .amount-display {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .wompi-form {
            text-align: center;
            padding: 2rem 0;
            min-height: 100px;
        }
        .back-button, .fallback-button {
            background: #000000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0px;
            height: 40px;
            line-height: 40px;
            width: 100%;
            margin-top: 10px;
        }
        .back-button:hover, .fallback-button:hover {
            background: #fb0000;
            transform: translateY(-1px);
        }
        .fallback-button {
            background: #fb0000;
        }
        .fallback-button:hover {
            background: #fb0000;
        }
        .payment-instructions {
            background: #fb000045;
            border: 1px solid #fb000045;
            border-radius: 4px;
            padding: 1rem;
            color: #fb0000;
        }
        .security-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            color: #000;
            font-size: 0.9rem;
        }
        .security-badges {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .loading-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            color: #000;
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #fb0000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-message {
            display: none;
            background: #f8d7da;
            color: #fb0000;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1rem 0;
            border-left: 4px solid #dc3545;
        }
        .text-success {
            --bs-text-opacity: 1;
            color: #fb0000;
        }

        .widget-status {
            text-align: center;
            margin: 1rem 0;
            font-size: 0.9rem;
            color: #fb0000;
            display: none !important;
        }
        .waybox-button {
            background-color: #fb0000 !important;
            width: 100% !important;
        }

        .security-info i{
            color: #fb0000 !important;
        }
    </style>
</head>
<body>
<div class="payment-container">
    <div class="payment-card">

        <div class="payment-header">
            <div class="payment-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <h2 class="mb-0">{{ __('Pago seguro con wompi') }}</h2>
            <p class="mb-0 opacity-75">{{ __('Procesa tu pago de forma segura') }}</p>
        </div>

        <div class="payment-body">
            <div class="payment-summary">
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="mb-2">{{ __('Referencia de Pedido') }}</h6>
                        <p class="mb-0 text-muted">{{ $widgetData['reference'] }}</p>
                        <small class="text-muted">{{ __('Guarda esta referencia para futuras consultas') }}</small>
                    </div>
                    <div class="col-md-12 mt-4">
                        <h6 class="mb-2">{{ __('Total a Pagar') }}</h6>
                        <div class="amount-display">
                            @if($originalCurrency !== 'COP')
                                {{ number_format($originalAmount, 2) }} {{ $originalCurrency }}
                                <small class="text-muted d-block" style="font-size: 0.8rem;">
                                    ≈ {{ number_format($widgetData['amount_in_cents'] / 100, 0) }} COP
                                </small>
                            @else
                                ${{ number_format($widgetData['amount_in_cents'] / 100, 0) }} COP
                            @endif
                        </div>
                        @if(isset($widgetData['tax_in_cents']) && $widgetData['tax_in_cents']['vat'] > 0)
                            <small class="text-muted">
                                {{ __('Incluye IVA') }}: ${{ number_format($widgetData['tax_in_cents']['vat'] / 100, 0) }} COP
                            </small>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Instrucciones de pago -->
            <div class="payment-instructions">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>{{ __('Instrucciones de Pago') }}</strong>
                </div>
                <p class="mb-2">{{ __('Al hacer clic en "Pagar con Wompi" serás redirigido a la plataforma segura de pago.') }}</p>
                <small>
                    {{ __('Podrás pagar con tarjeta de crédito, débito, PSE o Nequi.') }}
                </small>
            </div>

            <!-- Loading indicator -->
            <div id="loading-indicator" class="loading-indicator">
                <div class="loading-spinner"></div>
                <span>{{ __('Cargando formulario de pago...') }}</span>
            </div>

            <!-- Widget status -->
            <div id="widget-status" class="widget-status" style="display: none;"></div>

            <!-- Error message -->
            <div id="error-message" class="error-message">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>{{ __('Error al cargar el widget de pago') }}</strong>
                </div>
                <p id="error-details" class="mb-3"></p>
                <div class="text-center">
                    <button onclick="location.reload()" class="fallback-button">
                        <i class="fas fa-redo me-2"></i>
                        {{ __('Recargar Página') }}
                    </button>
                    <button onclick="redirectToDirectCheckout()" class="fallback-button">
                        <i class="fas fa-external-link-alt me-2"></i>
                        {{ __('Pago Directo') }}
                    </button>
                </div>
            </div>



            <!-- Widget de Wompi -->
            <div id="wompi-form-container" class="wompi-form" >
                <form id="formWompi">
                    {{-- Script del widget con configuración correcta según documentación oficial --}}
                    <script
                        id="wompi-widget-script"
                        src="https://checkout.wompi.co/widget.js"
                        data-render="button"
                        data-public-key="{{ $widgetData['public_key'] }}"
                        data-currency="{{ $widgetData['currency'] }}"
                        data-amount-in-cents="{{ $widgetData['amount_in_cents'] }}"
                        data-reference="{{ $widgetData['reference'] }}"
                        data-redirect-url="{{ $widgetData['redirect_url'] }}"
                        @if(isset($widgetData['signature_integrity']))
                            data-signature:integrity="{{ $widgetData['signature_integrity'] }}"
                        @endif
                        {{-- Temporarily removing expiration_time to test signature issue --}}
                        {{-- @if(isset($widgetData['expiration_time']))
                            data-expiration-time="{{ $widgetData['expiration_time'] }}"
                        @endif --}}
                        @if(isset($widgetData['customer_data']['email']))
                            data-customer-data:email="{{ $widgetData['customer_data']['email'] }}"
                        @endif
                        @if(isset($widgetData['customer_data']['full_name']) && $widgetData['customer_data']['full_name'])
                            data-customer-data:full-name="{{ $widgetData['customer_data']['full_name'] }}"
                        @endif
                        @if(isset($widgetData['customer_data']['phone_number']) && isset($widgetData['customer_data']['phone_number_prefix']))
                            data-customer-data:phone-number="{{ $widgetData['customer_data']['phone_number'] }}"
                            data-customer-data:phone-number-prefix="{{ $widgetData['customer_data']['phone_number_prefix'] }}"
                        @endif
                        @if(isset($widgetData['tax_in_cents']['vat']) && $widgetData['tax_in_cents']['vat'] > 0)
                            data-tax-in-cents:vat="{{ $widgetData['tax_in_cents']['vat'] }}"
                            @if(isset($widgetData['tax_in_cents']['consumption']) && $widgetData['tax_in_cents']['consumption'] > 0)
                                data-tax-in-cents:consumption="{{ $widgetData['tax_in_cents']['consumption'] }}"
                            @endif
                        @endif
                        @if(isset($widgetData['shipping_address']) && isset($widgetData['shipping_address']['address_line_1']))
                            data-shipping-address:address-line-1="{{ $widgetData['shipping_address']['address_line_1'] }}"
                            data-shipping-address:city="{{ $widgetData['shipping_address']['city'] }}"
                            data-shipping-address:region="{{ $widgetData['shipping_address']['region'] }}"
                            data-shipping-address:country="{{ $widgetData['shipping_address']['country'] }}"
                            @if(isset($widgetData['shipping_address']['phone_number']) && isset($widgetData['shipping_address']['phone_number_prefix']))
                                data-shipping-address:phone-number="{{ $widgetData['shipping_address']['phone_number'] }}"
                                data-shipping-address:phone-number-prefix="{{ $widgetData['shipping_address']['phone_number_prefix'] }}"
                            @endif
                        @endif>
                    </script>
                </form>

                <button onclick="window.history.back()" class="back-button">
                    <i class="fas fa-arrow-left me-2"></i>
                    {{ __('Volver al Carrito') }}
                </button>
            </div>

            <!-- Información de seguridad -->
            <div class="security-info">
                <div class="security-badges">
                    <i class="fas fa-shield-alt text-success"></i>
                    <span>{{ __('Pago 100% Seguro') }}</span>
                </div>
                <div class="security-badges">
                    <i class="fas fa-lock text-primary"></i>
                    <span>{{ __('Conexión Encriptada') }}</span>
                </div>
                <div class="security-badges">
                    <i class="fas fa-certificate text-warning"></i>
                    <span>{{ __('Certificado SSL') }}</span>
                </div>
            </div>

            <div id="error-message" class="error-message">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>{{ __('Error al cargar el widget de pago') }}</strong>
                </div>
                <p id="error-details" class="mb-3"></p>
                <div class="text-center">
                    <button onclick="location.reload()" class="fallback-button">
                        <i class="fas fa-redo me-2"></i>
                        {{ __('Recargar Página') }}
                    </button>
                    <button onclick="redirectToDirectCheckout()" class="fallback-button">
                        <i class="fas fa-external-link-alt me-2"></i>
                        {{ __('Pago Directo') }}
                    </button>
                    <button onclick="runDiagnostics()" class="fallback-button" style="background: #17a2b8;">
                        <i class="fas fa-stethoscope me-2"></i>
                        {{ __('Diagnóstico') }}
                    </button>
                </div>
            </div>


            @if(app()->environment('local'))
                <!-- Debug info (solo en desarrollo) -->
                <div class="mt-4 p-3" style="background: #fbfbfb; border-radius: 10px; font-size: 0.85rem;">
                    <strong>{{ __('Debug Info') }} (Solo en desarrollo):</strong><br>
                    <strong>Entorno:</strong> {{ $widgetData['is_sandbox'] ? 'Sandbox' : 'Producción' }}<br>
                    <strong>Public Key:</strong> {{ substr($widgetData['public_key'], 0, 20) }}...<br>
                    <strong>Referencia:</strong> {{ $widgetData['reference'] }}<br>
                    <strong>Monto (centavos):</strong> {{ $widgetData['amount_in_cents'] }}<br>
                    <strong>Moneda:</strong> {{ $widgetData['currency'] }}<br>
                    <strong>Email:</strong> {{ $widgetData['customer_data']['email'] ?? 'N/A' }}<br>
                    @if(isset($widgetData['customer_data']['phone_number']) && isset($widgetData['customer_data']['phone_number_prefix']))
                        <strong>Teléfono:</strong> {{ $widgetData['customer_data']['phone_number_prefix'] }} {{ $widgetData['customer_data']['phone_number'] }}<br>
                    @else
                        <strong>Teléfono:</strong> No disponible<br>
                    @endif
                    @if(isset($widgetData['tax_in_cents']))
                        <strong>IVA (centavos):</strong> {{ $widgetData['tax_in_cents']['vat'] ?? 'N/A' }}<br>
                    @else
                        <strong>IVA:</strong> No incluido<br>
                    @endif
                    <strong>URL Redirect:</strong> {{ $widgetData['redirect_url'] }}
                </div>
            @endif

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Configuración
    const widgetConfig = @json($widgetData);
    let widgetCheckInterval;
    let widgetDetected = false;
    let scriptLoadAttempts = 0;
    const maxScriptAttempts = 3;

    console.log('Wompi Widget initialized with config:', widgetConfig);
    console.log('🔍 Widget Debug Info:', {
        public_key_exists: !!widgetConfig.public_key,
        public_key_value: widgetConfig.public_key,
        public_key_length: widgetConfig.public_key?.length,
        signature_exists: !!widgetConfig.signature_integrity,
        signature_value: widgetConfig.signature_integrity,
        reference_exists: !!widgetConfig.reference,
        full_config: widgetConfig
    });

    // Verificar si el problema está en la configuración del script
    const scriptElement = document.getElementById('wompi-widget-script');
    if (scriptElement) {
        console.log('🔍 Script Element Debug:', {
            script_src: scriptElement.src,
            data_public_key: scriptElement.getAttribute('data-public-key'),
            data_attributes: Array.from(scriptElement.attributes).map(attr => ({
                name: attr.name,
                value: attr.value
            }))
        });
    } else {
        console.error('❌ Wompi script element not found!');
    }

    function showLoading() {
        document.getElementById('loading-indicator').style.display = 'flex';
        document.getElementById('error-message').style.display = 'none';
        document.getElementById('wompi-form-container').style.display = 'none';
        document.getElementById('widget-status').style.display = 'none';
    }

    function showError(message, details = '') {
        document.getElementById('loading-indicator').style.display = 'none';
        document.getElementById('error-message').style.display = 'block';
        document.getElementById('wompi-form-container').style.display = 'none';
        document.getElementById('widget-status').style.display = 'none';
        document.getElementById('error-details').innerHTML = details || message;
    }

    function showWidget() {
        document.getElementById('loading-indicator').style.display = 'none';
        document.getElementById('error-message').style.display = 'none';
        document.getElementById('wompi-form-container').style.display = 'block';
        document.getElementById('widget-status').style.display = 'block';
        document.getElementById('widget-status').innerHTML = '<i class="fas fa-check-circle text-success"></i> Widget cargado correctamente';
    }

    function showStatus(message, type = 'info') {
        const statusEl = document.getElementById('widget-status');
        statusEl.style.display = 'block';
        const iconClass = type === 'success' ? 'fas fa-check-circle text-success' :
            type === 'error' ? 'fas fa-times-circle text-danger' :
                'fas fa-info-circle text-info';
        statusEl.innerHTML = `<i class="${iconClass}"></i> ${message}`;
    }

    // NUEVO: Función para detectar si el script se cargó correctamente
    function checkScriptLoaded() {
        const script = document.getElementById('wompi-widget-script');
        if (!script) {
            console.error('Wompi script element not found');
            return false;
        }

        // Verificar si el script se cargó sin errores
        if (script.readyState && script.readyState !== 'loaded' && script.readyState !== 'complete') {
            console.warn('Wompi script not ready:', script.readyState);
            return false;
        }

        return true;
    }

    // MEJORADO: Mejor detección del widget
    function checkWidgetLoaded() {
        // Primero verificar que el script se haya cargado
        if (!checkScriptLoaded()) {
            return false;
        }

        // Buscar indicadores de que el widget se cargó
        const indicators = [
            document.querySelector('button[data-wompi-id]'),
            document.querySelector('.wompi-button'),
            document.querySelector('[class*="wompi"]'),
            document.querySelector('iframe[src*="wompi"]'),
            document.querySelector('[data-wompi-id]'),
            document.querySelector('form button[type="submit"]'),
            // Buscar elementos específicos que crea el widget
            document.querySelector('button[onclick*="wompi"]'),
            document.querySelector('.checkout-button')
        ];

        const widgetFound = indicators.some(indicator => indicator !== null);

        if (widgetFound && !widgetDetected) {
            console.log('Wompi widget detected successfully');
            widgetDetected = true;
            showWidget();
            clearInterval(widgetCheckInterval);
            return true;
        }

        return false;
    }

    // MEJORADO: Detección con reintentos
    function startWidgetDetection() {
        showStatus('Detectando widget de Wompi...', 'info');

        let checkCount = 0;
        const maxChecks = 30; // 15 segundos máximo (500ms * 30)

        widgetCheckInterval = setInterval(() => {
            checkCount++;

            if (checkWidgetLoaded()) {
                return; // Widget encontrado, interval limpio
            }

            // Mostrar progreso cada 5 intentos
            if (checkCount % 5 === 0) {
                showStatus(`Detectando widget... (${checkCount}/${maxChecks})`, 'info');
            }

            if (checkCount >= maxChecks) {
                console.warn('Widget detection timeout after 15 seconds');
                clearInterval(widgetCheckInterval);

                // Si el script no se cargó, ofrecer reintento
                if (!checkScriptLoaded() && scriptLoadAttempts < maxScriptAttempts) {
                    retryScriptLoad();
                } else {
                    showError(
                        'El widget de Wompi no se detectó correctamente',
                        'Puede haber un problema de conectividad. Puedes recargar la página o usar el pago directo.'
                    );
                }
            }
        }, 500);
    }

    // NUEVO: Función para reintentar la carga del script
    function retryScriptLoad() {
        scriptLoadAttempts++;
        console.log(`Retrying script load (attempt ${scriptLoadAttempts}/${maxScriptAttempts})`);

        showStatus(`Reintentando conexión... (${scriptLoadAttempts}/${maxScriptAttempts})`, 'info');

        // Remover script anterior
        const oldScript = document.getElementById('wompi-widget-script');
        if (oldScript && oldScript.parentNode) {
            oldScript.parentNode.removeChild(oldScript);
        }

        // Crear nuevo script con diferentes URLs como fallback
        const scriptUrls = [
            'https://checkout.wompi.co/widget.js', // URL única para sandbox y producción
            'https://checkout.wompi.co/widget.js', // Mismo fallback
        ];

        const currentUrl = scriptUrls[scriptLoadAttempts - 1] || scriptUrls[0];

        // Crear nuevo elemento script
        const newScript = document.createElement('script');
        newScript.id = 'wompi-widget-script';
        newScript.src = currentUrl;
        newScript.setAttribute('data-render', 'button');
        newScript.setAttribute('data-public-key', widgetConfig.public_key);
        newScript.setAttribute('data-currency', widgetConfig.currency);
        newScript.setAttribute('data-amount-in-cents', widgetConfig.amount_in_cents);
        newScript.setAttribute('data-reference', widgetConfig.reference);
        newScript.setAttribute('data-redirect-url', widgetConfig.redirect_url);

        if (widgetConfig.signature_integrity) {
            newScript.setAttribute('data-signature:integrity', widgetConfig.signature_integrity);
        }

        if (widgetConfig.customer_data && widgetConfig.customer_data.email) {
            newScript.setAttribute('data-customer-data:email', widgetConfig.customer_data.email);
        }

        // Event listeners para el nuevo script
        newScript.onload = function() {
            console.log('Wompi script loaded successfully on retry');
            setTimeout(() => {
                startWidgetDetection();
            }, 1000);
        };

        newScript.onerror = function(error) {
            console.error('Wompi script failed to load on retry:', error);
            if (scriptLoadAttempts >= maxScriptAttempts) {
                showError(
                    'No se pudo cargar el widget de Wompi',
                    'Problema de conectividad persistente. Usa el pago directo para continuar.'
                );
            } else {
                setTimeout(() => retryScriptLoad(), 2000); // Esperar 2 segundos antes del siguiente intento
            }
        };

        // Insertar el nuevo script
        const form = document.getElementById('formWompi');
        form.appendChild(newScript);
    }

    function redirectToDirectCheckout() {
        console.log('Redirecting to direct checkout...');

        // Crear formulario para POST directo a Wompi
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'https://checkout.wompi.co/p/';

        const formData = {
            'public-key': widgetConfig.public_key,
            'currency': widgetConfig.currency,
            'amount-in-cents': widgetConfig.amount_in_cents,
            'reference': widgetConfig.reference,
            'signature:integrity': widgetConfig.signature_integrity,
            'redirect-url': widgetConfig.redirect_url,
            'customer-data:email': widgetConfig.customer_data.email
        };

        // Agregar datos del cliente con nombres correctos
        if (widgetConfig.customer_data.full_name) {
            formData['customer-data:full-name'] = widgetConfig.customer_data.full_name;
        }

        if (widgetConfig.customer_data.phone_number && widgetConfig.customer_data.phone_number_prefix) {
            formData['customer-data:phone-number'] = widgetConfig.customer_data.phone_number;
            formData['customer-data:phone-number-prefix'] = widgetConfig.customer_data.phone_number_prefix;
        }

        // Solo agregar impuestos si existen y son válidos
        if (widgetConfig.tax_in_cents && widgetConfig.tax_in_cents.vat > 0) {
            formData['tax-in-cents:vat'] = widgetConfig.tax_in_cents.vat;
            if (widgetConfig.tax_in_cents.consumption > 0) {
                formData['tax-in-cents:consumption'] = widgetConfig.tax_in_cents.consumption;
            }
        }

        // Solo agregar datos que existan
        for (const [key, value] of Object.entries(formData)) {
            if (value !== null && value !== undefined && value !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
        }

        console.log('Form data for direct checkout:', formData);
        document.body.appendChild(form);
        form.submit();
    }

    // NUEVA: Función de diagnóstico de conectividad
    function runDiagnostics() {
        console.log('=== WOMPI DIAGNOSTICS ===');
        console.log('Online status:', navigator.onLine);
        console.log('User agent:', navigator.userAgent);
        console.log('Widget config:', widgetConfig);

        const scriptEl = document.getElementById('wompi-widget-script');
        console.log('Script element:', scriptEl);
        console.log('Script src:', scriptEl?.src);
        console.log('Script ready state:', scriptEl?.readyState);

        // Test de conectividad a Wompi (usar widget.js en lugar de health)
        const testUrl = 'https://checkout.wompi.co';

        fetch(testUrl + '/widget.js', { mode: 'no-cors' })
            .then(() => console.log('✅ Wompi widget service: OK'))
            .catch(err => console.log('⚠️ Wompi widget endpoint test failed (this is normal):', err.message));

        // Test DNS resolution
        fetch('https://www.google.com/favicon.ico', { mode: 'no-cors' })
            .then(() => console.log('✅ DNS resolution: OK'))
            .catch(err => console.log('❌ DNS resolution:', err.message));
    }

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, starting widget detection...');
        console.log('Environment:', widgetConfig.is_sandbox ? 'SANDBOX' : 'PRODUCTION');
        console.log('Widget URL:', document.getElementById('wompi-widget-script')?.src);

        showLoading();

        // Verificar conectividad básica
        if (!navigator.onLine) {
            showError(
                'Sin conexión a internet',
                'Verifica tu conexión y recarga la página.'
            );
            return;
        }

        // Ejecutar diagnósticos automáticos
        setTimeout(runDiagnostics, 1000);

        // Esperar un poco para que el script de Wompi se ejecute
        setTimeout(() => {
            startWidgetDetection();
        }, 2000);

        // Fallback después de 20 segundos
        setTimeout(() => {
            if (!widgetDetected && document.getElementById('loading-indicator').style.display !== 'none') {
                console.log('Auto-fallback to error state after 20 seconds');
                showError(
                    'El widget de pago tardó demasiado en cargar',
                    'Puedes recargar la página o usar el pago directo para continuar con tu compra.'
                );
            }
        }, 20000);
    });

    // MEJORADO: Manejar errores del script con más detalle
    window.addEventListener('error', function(e) {
        console.error('Global error caught:', e);

        if (e.message && (e.message.includes('wompi') || e.message.includes('checkout'))) {
            console.error('Wompi script error:', e);
            clearInterval(widgetCheckInterval);

            if (e.message.includes('ERR_NAME_NOT_RESOLVED') || e.message.includes('net::')) {
                showError(
                    'Error de conectividad con Wompi',
                    'No se pudo conectar con el servidor de pagos. Verifica tu conexión a internet o usa el pago directo.'
                );
            } else {
                showError(
                    'Error en el script de Wompi',
                    'Hubo un problema técnico. Puedes recargar la página o usar el pago directo.'
                );
            }
        }
    });

    // NUEVO: Detectar cambios en la conectividad
    window.addEventListener('online', function() {
        console.log('Connection restored');
        if (!widgetDetected) {
            location.reload(); // Recargar automáticamente cuando se recupere la conexión
        }
    });

    window.addEventListener('offline', function() {
        console.log('Connection lost');
        showError(
            'Conexión perdida',
            'Se perdió la conexión a internet. El widget se recargará automáticamente cuando se recupere la conexión.'
        );
    });

    // Auto-redirect después de 1 día por seguridad
    setTimeout(() => {
        if (confirm('La sesión de pago ha expirado. ¿Deseas volver al carrito?')) {
            window.history.back();
        }
    }, 86400000); // 24 horas (1 día)

    // Debug: Mostrar información en consola
    setTimeout(() => {
        console.log('Widget check summary:', {
            detected: widgetDetected,
            scriptLoadAttempts: scriptLoadAttempts,
            formElements: document.querySelectorAll('#formWompi *').length,
            buttonsFound: document.querySelectorAll('button').length,
            wompiElements: document.querySelectorAll('[data-wompi-id], [class*="wompi"], button[data-wompi-id]').length,
            taxIncluded: widgetConfig.tax_in_cents ? widgetConfig.tax_in_cents.vat : 'No tax',
            scriptExists: !!document.getElementById('wompi-widget-script'),
            onlineStatus: navigator.onLine
        });
    }, 5000);
</script>

</body>
</html>
