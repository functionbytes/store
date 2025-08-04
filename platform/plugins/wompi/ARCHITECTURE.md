listo# Wompi Plugin - Arquitectura y Documentación Técnica

## 📊 **Resumen del Plugin**

El plugin **Wompi** es una integración de gateway de pagos para Colombia que extiende el sistema base de pagos de Botble CMS. Proporciona integración completa con la API de Wompi, incluyendo widget de checkout, webhooks, y manejo de transacciones.

---

## 🏗️ **Arquitectura de Dependencias**

### **Plugin Payment (Base) - `botble/payment`**
- **Namespace**: `Botble\Payment\`
- **Función**: Framework base de pagos para Botble CMS
- **Responsabilidades**:
  - Gestión de transacciones y logs
  - Sistema de registro de métodos de pago
  - Interface común (`PaymentAbstract`)
  - Dashboard administrativo
  - Helpers y utilities compartidos

### **Plugin Wompi (Extensión) - `functionbytes/wompi`**
- **Namespace**: `Functionbytes\Wompi\`
- **Dependencia**: `"require": ["botble/payment"]`
- **Versión**: 1.1.1
- **Responsabilidades**:
  - Integración específica con API de Wompi
  - Widget de checkout embebido
  - Webhooks y callbacks
  - Conversión de monedas a COP
  - Validación de firmas de seguridad

---

## 🔗 **Sistema de Hooks e Integración**

### **1. Registro del Método de Pago**
```php
// src/Providers/HookServiceProvider.php
add_filter(BASE_FILTER_ENUM_ARRAY, function (array $values, string $class): array {
    if ($class === PaymentMethodEnum::class) {
        $values['WOMPI'] = WompiServiceProvider::MODULE_NAME;
    }
    return $values;
}, 999, 2);
```

### **2. Integración con UI de Checkout**
```php
add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data): ?string {
    if (get_payment_setting('status', WompiServiceProvider::MODULE_NAME)) {
        PaymentMethods::method(WompiServiceProvider::MODULE_NAME, [
            'html' => view('plugins/wompi::method', ...)->render(),
        ]);
    }
    return $html;
}, 999, 2);
```

### **3. Procesamiento de Pagos**
```php
add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, function (array $data, Request $request): array {
    if ($data['type'] !== WompiServiceProvider::MODULE_NAME) {
        return $data;
    }
    // Lógica específica de procesamiento Wompi
}, 999, 2);
```

### **4. Configuración Administrativa**
```php
add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $settings) {
    return $settings . view('plugins/wompi::index')->render();
}, 999);
```

---

## ⚙️ **Componentes Técnicos Principales**

### **WompiService (Core)**
**Ubicación**: `src/Services/WompiService.php`

**Responsabilidades**:
- Configuración multi-fuente (Admin panel + .env)
- Validación de credenciales y formato
- Gestión de entornos (sandbox/production)
- Generación de firmas de integridad (SHA256)
- Widget de checkout embebido
- Conversión automática de monedas
- Comunicación con API de Wompi

**Métodos principales**:
```php
public function __construct()                           // Inicialización y validación
public function withData(array $data): self            // Configurar datos de pago
public function redirectToCheckoutPage(): void         // Redirigir a widget
public function generateIntegritySignature(): string   // Generar firma de seguridad
public function verifyWebhookSignature(): bool         // Verificar webhooks
public function validateConfiguration(): array         // Validar configuración
```

### **WompiPaymentService (Abstracción)**
**Ubicación**: `src/Services/WompiPaymentService.php`

**Hereda de**: `Botble\Payment\Services\Abstracts\PaymentAbstract`

**Métodos implementados**:
```php
public function makePayment(Request $request): string
public function afterMakePayment(Request $request)
public function getServiceProvider(): string
public function isSupportRefundOnline(): bool
public function refund(string $chargeId, float $amount, array $options = []): array
```

### **Controllers**

#### **WompiController**
**Ubicación**: `src/Http/Controllers/WompiController.php`
- Manejo de callbacks de usuario
- Procesamiento de webhooks
- Verificación de transacciones
- Simulación de webhooks (desarrollo)

#### **CallbackController**
**Ubicación**: `src/Http/Controllers/CallbackController.php`
- Procesamiento de retornos de Wompi
- Actualización de estado de órdenes
- Logging de callbacks

---

## 🛡️ **Seguridad y Validación**

### **Verificación de Webhooks**
```php
public function verifyWebhookSignature(array $payload, string $signature): bool
{
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
```

### **Validación de Credenciales**
- **Public Key**: Formato `pub_test_*` (sandbox) o `pub_prod_*` (production)
- **Private Key**: Opcional para pagos básicos, requerido para API
- **Integrity Secret**: Obligatorio para firmas
- **Event Secret**: Requerido para validación de webhooks

### **Validación de Configuración**
```php
public function validateConfiguration(): array
{
    $errors = [];
    $warnings = [];
    
    // Validaciones de formato y consistencia
    // Verificación de modo sandbox/production
    // Comprobación de credenciales
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}
```

---

## 💰 **Características Específicas de Wompi**

### **Conversión de Monedas**
- **Moneda base**: Solo COP (Pesos Colombianos)
- **Conversión automática**:
  - USD → COP (tasa: 4000)
  - EUR → COP (tasa: 4300)
- **Manejo de impuestos**: IVA 19% para Colombia

### **Límites de Sandbox**
- **Máximo**: 500,000 COP (50,000,000 centavos)
- **Reducción proporcional**: Mantiene ratio impuesto/monto
- **Logging**: Advertencias cuando se reducen montos

### **Formato de Datos del Cliente**
```php
// Teléfono con código de país
'phone_number' => '3001234567',
'phone_number_prefix' => '+57',

// Dirección de envío completa
'shipping_address' => [
    'address_line_1' => 'Calle 123 #45-67',
    'city' => 'Bogotá',
    'region' => 'Cundinamarca',
    'country' => 'CO',
    'phone_number' => '3001234567',
    'phone_number_prefix' => '+57'
]
```

---

## 🔄 **Flujo de Pago Completo**

### **1. Inicialización**
```
Usuario selecciona Wompi → PaymentMethods::method() → Vista method.blade.php
```

### **2. Procesamiento**
```
Checkout POST → PAYMENT_FILTER_AFTER_POST_CHECKOUT → WompiPaymentService::makePayment()
```

### **3. Widget**
```
WompiService::withData() → generateIntegritySignature() → redirectToCheckoutPage()
```

### **4. Finalización**
```
Wompi Widget → Callback (/payment/wompi/callback) → Order status update
                ↓
           Webhook (/payment/wompi/webhook) → Verification → Final status
```

---

## 📝 **Configuración**

### **Variables de Entorno (.env)**
```bash
# Wompi Configuration
WOMPI_PUBLIC_KEY=pub_test_xxxxxxxxxx
WOMPI_PRIVATE_KEY=prv_test_xxxxxxxxxx
WOMPI_INTEGRITY_SECRET=test_integrity_xxxxxxxxxx
WOMPI_EVENT_SECRET=test_events_xxxxxxxxxx
WOMPI_MODE=sandbox
```

### **Configuración Administrativa**
Disponible en: `Admin → Payment Methods → Wompi`

**Campos**:
- Status (habilitado/deshabilitado)
- Public Key
- Private Key
- Integrity Secret
- Event Secret
- Mode (sandbox/production)
- Logo
- Descripción

---

## 🚀 **Rutas y Endpoints**

### **Rutas Públicas**
```php
// routes/web.php
Route::middleware(['web', 'core'])
    ->prefix('payment/wompi')
    ->name('payment.wompi.')
    ->group(function () {
        
        // Callback para usuarios
        Route::get('callback', [WompiController::class, 'callback'])
            ->name('callback');
        
        // Webhook para notificaciones
        Route::post('webhook', [WompiController::class, 'webhook'])
            ->name('webhook');
        
        // Verificación de transacciones
        Route::get('transaction/{transactionId}', [WompiController::class, 'checkTransaction'])
            ->name('transaction.check');
        
        // Simulador para desarrollo
        if (app()->environment('local')) {
            Route::get('simulate-webhook/{transactionId}', [WompiController::class, 'simulateWebhook'])
                ->name('simulate.webhook');
        }
    });
```

---

## 🐛 **Debugging y Logging**

### **Logs Principales**
- **Inicialización**: Configuración y credenciales
- **Widget**: Datos enviados al checkout
- **Conversiones**: Monedas y montos
- **Callbacks**: Respuestas de Wompi
- **Webhooks**: Verificaciones de firma
- **Errores**: Excepciones y validaciones

### **Logging de Ejemplo**
```php
\Log::info('Wompi Payment: Order token details', [
    'order_token' => $orderToken,
    'payment_data_token' => $paymentData['token'] ?? 'not_set',
    'order_id' => $paymentData['order_id'] ?? 'not_set',
    'amount' => $paymentData['amount'],
    'currency' => $paymentData['currency'] ?? 'COP',
    'customer_email' => $paymentData['address']['email'] ?? 'N/A'
]);
```

---

## 🔧 **Desarrollo y Mantenimiento**

### **Entorno de Desarrollo**
- **Sandbox**: Usar credenciales `pub_test_*`
- **Límites**: Máximo 500,000 COP
- **Simulación**: Endpoint para simular webhooks
- **Debug**: Información adicional en entorno local

### **Testing**
- **Webhooks**: Usar simulador local
- **Transacciones**: Verificar con montos de prueba
- **Configuración**: Validar credenciales antes de producción

### **Deployment**
1. Configurar credenciales de producción
2. Cambiar modo a `production`
3. Verificar URLs de callback/webhook
4. Probar transacciones de prueba
5. Monitorear logs iniciales

---

## 📋 **Troubleshooting Común**

### **Errores de Configuración**
- **Public key inválida**: Verificar formato `pub_test_*` o `pub_prod_*`
- **Integrity secret faltante**: Configurar en admin panel o .env
- **Modo inconsistente**: Verificar que credenciales coincidan con modo

### **Errores de Widget**
- **Monto excesivo**: Reducir para sandbox (<500,000 COP)
- **Datos faltantes**: Verificar email, referencia, firma
- **Currency mismatch**: Solo acepta COP

### **Errores de Firma (CRÍTICO)**
- **Signature Error**: ❌ **NO incluir `expiration_time` en la firma**
- **Orden correcta**: `Reference + AmountInCents + Currency + IntegritySecret`
- **Formato widget**: Usar `data-signature:integrity` (con dos puntos)
- **URLs correctas**: Sandbox: `checkout.sandbox.wompi.co`, Producción: `checkout.wompi.co`

### **Errores de Webhook**
- **Firma inválida**: Verificar Event Secret
- **Orden no encontrada**: Verificar token de referencia
- **Timeout**: Verificar conectividad de servidor

---

## 📚 **Referencias**

- **Documentación Wompi**: https://docs.wompi.co/
- **Botble CMS Payment**: https://botble.com/docs/payment
- **Plugin Repository**: Platform/plugins/wompi/
- **Support**: functionbytes.com
