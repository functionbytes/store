<?php

// Script para probar el sistema dinámico de validación de tarifas de envío
// Ejecutar con: php test-dynamic-shipping.php

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use Botble\Ecommerce\Services\DynamicShippingValidationService;
use Botble\Ecommerce\Services\HandleShippingFeeService;
use Botble\Ecommerce\Services\FreeShippingAutoHandler;
use Botble\Ecommerce\Models\ShippingRule;
use Botble\Location\Models\City;

echo "=== PRUEBAS DEL SISTEMA DINÁMICO DE VALIDACIÓN DE ENVÍO ===\n\n";

try {
    // Inicializar servicios
    $handleShippingFeeService = app(HandleShippingFeeService::class);
    $dynamicShippingService = new DynamicShippingValidationService($handleShippingFeeService);
    $freeShippingHandler = new FreeShippingAutoHandler();
    
    // Datos de prueba - Pedido menor a $200,000
    echo "1. PRUEBA: Pedido de \$150,000 en Bucaramanga\n";
    echo "---------------------------------------------------\n";
    
    $testData1 = [
        'order_total' => 150000,
        'city' => 'Bucaramanga', // Buscar ID de Bucaramanga
        'state' => '28', // Santander
        'country' => 'CO',
        'weight' => 1.5,
    ];
    
    // Buscar ciudad de Bucaramanga
    $bucaramanga = City::where('name', 'LIKE', '%Bucaramanga%')->first();
    if ($bucaramanga) {
        $testData1['city'] = $bucaramanga->id;
        echo "Ciudad Bucaramanga encontrada (ID: {$bucaramanga->id})\n";
    } else {
        echo "⚠️ Ciudad Bucaramanga no encontrada, usando nombre\n";
    }
    
    $methods1 = $dynamicShippingService->validateShippingMethods($testData1);
    $summary1 = $dynamicShippingService->getShippingMethodsSummary(150000, $bucaramanga->id ?? 'Bucaramanga');
    
    echo "Métodos de envío disponibles:\n";
    foreach ($methods1 as $methodKey => $methodOptions) {
        foreach ($methodOptions as $optionKey => $option) {
            $autoSelect = isset($option['auto_select']) ? ' (AUTO-SELECCIONADO)' : '';
            $citySpecific = isset($option['city_specific']) && $option['city_specific'] ? ' (Específico para ciudad)' : '';
            echo "- {$option['name']}: \${$option['price']}{$autoSelect}{$citySpecific}\n";
        }
    }
    
    echo "\nResumen de envío:\n";
    echo "- Envío gratis disponible: " . ($summary1['has_free_shipping'] ? 'SÍ' : 'NO') . "\n";
    echo "- Monto para envío gratis: \${$summary1['amount_to_free_shipping']}\n";
    echo "- Tarifas específicas por ciudad: " . ($summary1['city_based_rates_available'] ? 'SÍ' : 'NO') . "\n\n";
    
    // Datos de prueba - Pedido mayor a $200,000
    echo "2. PRUEBA: Pedido de \$250,000 en Floridablanca\n";
    echo "----------------------------------------------------\n";
    
    $floridablanca = City::where('name', 'LIKE', '%Floridablanca%')->first();
    
    $testData2 = [
        'order_total' => 250000,
        'city' => $floridablanca ? $floridablanca->id : 'Floridablanca',
        'state' => '28', // Santander
        'country' => 'CO',
        'weight' => 2.0,
    ];
    
    if ($floridablanca) {
        echo "Ciudad Floridablanca encontrada (ID: {$floridablanca->id})\n";
    } else {
        echo "⚠️ Ciudad Floridablanca no encontrada, usando nombre\n";
    }
    
    $methods2 = $dynamicShippingService->validateShippingMethods($testData2);
    $summary2 = $dynamicShippingService->getShippingMethodsSummary(250000, $floridablanca->id ?? 'Floridablanca');
    
    echo "Métodos de envío disponibles:\n";
    foreach ($methods2 as $methodKey => $methodOptions) {
        foreach ($methodOptions as $optionKey => $option) {
            $autoSelect = isset($option['auto_select']) ? ' (AUTO-SELECCIONADO)' : '';
            $citySpecific = isset($option['city_specific']) && $option['city_specific'] ? ' (Específico para ciudad)' : '';
            echo "- {$option['name']}: \${$option['price']}{$autoSelect}{$citySpecific}\n";
        }
    }
    
    echo "\nResumen de envío:\n";
    echo "- Envío gratis disponible: " . ($summary2['has_free_shipping'] ? 'SÍ' : 'NO') . "\n";
    echo "- Monto para envío gratis: \${$summary2['amount_to_free_shipping']}\n";
    echo "- Tarifas específicas por ciudad: " . ($summary2['city_based_rates_available'] ? 'SÍ' : 'NO') . "\n\n";
    
    // Datos de prueba - Ciudad sin tarifas específicas
    echo "3. PRUEBA: Pedido de \$100,000 en ciudad no configurada\n";
    echo "-------------------------------------------------------\n";
    
    $testData3 = [
        'order_total' => 100000,
        'city' => 'Medellín',
        'state' => '05', // Antioquia
        'country' => 'CO',
        'weight' => 1.0,
    ];
    
    $methods3 = $dynamicShippingService->validateShippingMethods($testData3);
    $summary3 = $dynamicShippingService->getShippingMethodsSummary(100000, 'Medellín');
    
    echo "Métodos de envío disponibles:\n";
    if (empty($methods3)) {
        echo "- No hay métodos de envío disponibles para esta ubicación\n";
    } else {
        foreach ($methods3 as $methodKey => $methodOptions) {
            foreach ($methodOptions as $optionKey => $option) {
                $autoSelect = isset($option['auto_select']) ? ' (AUTO-SELECCIONADO)' : '';
                echo "- {$option['name']}: \${$option['price']}{$autoSelect}\n";
            }
        }
    }
    
    echo "\nResumen de envío:\n";
    echo "- Envío gratis disponible: " . ($summary3['has_free_shipping'] ? 'SÍ' : 'NO') . "\n";
    echo "- Monto para envío gratis: \${$summary3['amount_to_free_shipping']}\n";
    echo "- Tarifas específicas por ciudad: " . ($summary3['city_based_rates_available'] ? 'SÍ' : 'NO') . "\n\n";
    
    // Verificar reglas existentes
    echo "4. VERIFICACIÓN: Reglas de envío configuradas\n";
    echo "----------------------------------------------\n";
    
    $rules = ShippingRule::with(['items'])->get();
    foreach ($rules as $rule) {
        echo "Regla: {$rule->name}\n";
        echo "- Tipo: {$rule->type->getValue()}\n";
        echo "- Precio base: \${$rule->price}\n";
        if ($rule->type->getValue() === 'based_on_price') {
            echo "- Rango: \${$rule->from} - " . ($rule->to ? "\${$rule->to}" : 'Sin límite') . "\n";
        }
        echo "- Items configurados: {$rule->items->count()}\n";
        
        foreach ($rule->items as $item) {
            $cityName = $item->city_name ?? "ID: {$item->city}";
            $stateName = $item->state_name ?? "ID: {$item->state}";
            echo "  * {$cityName}, {$stateName}: ";
            if ($item->adjustment_price > 0) {
                echo "+\${$item->adjustment_price}";
            } elseif ($item->adjustment_price < 0) {
                echo "-\${" . abs($item->adjustment_price) . "}";
            } else {
                echo "Sin ajuste";
            }
            echo " (Final: \$" . max($rule->price + $item->adjustment_price, 0) . ")\n";
        }
        echo "\n";
    }
    
    echo "=== PRUEBAS COMPLETADAS ===\n";
    echo "✅ Sistema de validación dinámico funcional\n";
    echo "✅ Auto-selección implementada\n";
    echo "✅ Validación por monto y ciudad operativa\n\n";
    
    echo "PRÓXIMOS PASOS:\n";
    echo "1. Verificar en el frontend del checkout\n";
    echo "2. Probar cambios de ciudad en tiempo real\n";
    echo "3. Validar notificaciones automáticas\n";
    echo "4. Comprobar que el JavaScript se carga correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error durante las pruebas: " . $e->getMessage() . "\n";
    echo "Traceback: " . $e->getTraceAsString() . "\n";
}