<?php

// Script para depurar el problema de tarifas por ciudad
// Ejecutar con: php debug-city-shipping.php

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use Botble\Ecommerce\Models\ShippingRule;
use Botble\Ecommerce\Models\ShippingRuleItem;
use Botble\Location\Models\City;
use Botble\Location\Models\State;
use Botble\Ecommerce\Services\HandleShippingFeeService;

echo "=== DEPURACIÓN DE TARIFAS DE ENVÍO POR CIUDAD ===\n\n";

try {
    // 1. Buscar Piedecuesta
    echo "1. BUSCANDO CIUDAD PIEDECUESTA\n";
    echo "--------------------------------\n";
    
    $piedecuesta = City::where('name', 'LIKE', '%Piedecuesta%')->first();
    if ($piedecuesta) {
        echo "✅ Piedecuesta encontrada:\n";
        echo "   - ID: {$piedecuesta->id}\n";
        echo "   - Nombre: {$piedecuesta->name}\n";
        echo "   - Estado ID: {$piedecuesta->state_id}\n";
        
        $state = State::find($piedecuesta->state_id);
        if ($state) {
            echo "   - Estado: {$state->name}\n";
        }
    } else {
        echo "❌ Piedecuesta NO encontrada\n";
        
        // Buscar ciudades similares
        $similarCities = City::where('name', 'LIKE', '%Piede%')->get();
        echo "Ciudades similares encontradas:\n";
        foreach ($similarCities as $city) {
            echo "   - ID: {$city->id}, Nombre: {$city->name}\n";
        }
        
        if ($similarCities->isEmpty()) {
            echo "   No hay ciudades similares\n";
        }
    }
    
    // 2. Verificar reglas de envío existentes
    echo "\n2. REGLAS DE ENVÍO CONFIGURADAS\n";
    echo "--------------------------------\n";
    
    $rules = ShippingRule::with(['items', 'shipping'])->get();
    foreach ($rules as $rule) {
        echo "Regla: {$rule->name}\n";
        echo "   - ID: {$rule->id}\n";
        echo "   - Tipo: {$rule->type->getValue()}\n";
        echo "   - Precio base: \${$rule->price}\n";
        
        if ($rule->type->getValue() === 'based_on_price') {
            echo "   - Rango: \${$rule->from}" . ($rule->to ? " - \${$rule->to}" : " - Sin límite") . "\n";
        }
        
        echo "   - Items configurados: {$rule->items->count()}\n";
        
        // Buscar item específico para Piedecuesta
        if ($piedecuesta) {
            $piedecuestaItem = $rule->items->where('city', $piedecuesta->id)->first();
            if ($piedecuestaItem) {
                echo "   ✅ CONFIGURADO PARA PIEDECUESTA:\n";
                echo "      - Precio ajuste: \${$piedecuestaItem->adjustment_price}\n";
                echo "      - Precio final: \$" . ($rule->price + $piedecuestaItem->adjustment_price) . "\n";
                echo "      - Habilitado: " . ($piedecuestaItem->is_enabled ? 'SÍ' : 'NO') . "\n";
            } else {
                echo "   ❌ NO configurado para Piedecuesta\n";
            }
        }
        
        echo "\n";
    }
    
    // 3. Verificar items de reglas específicos
    echo "3. ITEMS DE REGLAS DE ENVÍO\n";
    echo "----------------------------\n";
    
    $items = ShippingRuleItem::with(['shippingRule'])->get();
    foreach ($items as $item) {
        if ($piedecuesta && $item->city == $piedecuesta->id) {
            echo "✅ ITEM PARA PIEDECUESTA:\n";
            echo "   - Regla: {$item->shippingRule->name}\n";
            echo "   - Ciudad ID: {$item->city}\n";
            echo "   - Estado ID: {$item->state}\n";
            echo "   - Precio ajuste: \${$item->adjustment_price}\n";
            echo "   - Precio base regla: \${$item->shippingRule->price}\n";
            echo "   - Precio final: \$" . max($item->shippingRule->price + $item->adjustment_price, 0) . "\n";
            echo "   - Habilitado: " . ($item->is_enabled ? 'SÍ' : 'NO') . "\n\n";
        }
    }
    
    // 4. Probar el servicio de shipping fee
    echo "4. PRUEBA DEL SERVICIO DE SHIPPING FEE\n";
    echo "--------------------------------------\n";
    
    if ($piedecuesta) {
        $shippingService = app(HandleShippingFeeService::class);
        
        $testData = [
            'order_total' => 150000,
            'city' => $piedecuesta->id,
            'state' => $piedecuesta->state_id,
            'country' => 'CO',
            'weight' => 1.5,
        ];
        
        echo "Datos de prueba:\n";
        echo "   - Total pedido: \${$testData['order_total']}\n";
        echo "   - Ciudad ID: {$testData['city']}\n";
        echo "   - Estado ID: {$testData['state']}\n";
        
        $shippingMethods = $shippingService->execute($testData);
        
        echo "\nMétodos de envío calculados:\n";
        foreach ($shippingMethods as $methodKey => $methodOptions) {
            echo "Método: {$methodKey}\n";
            foreach ($methodOptions as $optionKey => $option) {
                echo "   - Opción {$optionKey}: {$option['name']} - \${$option['price']}\n";
            }
        }
        
        if (empty($shippingMethods)) {
            echo "❌ NO se encontraron métodos de envío\n";
        }
    }
    
    // 5. Verificar configuración de checkout
    echo "\n5. CONFIGURACIÓN DE CHECKOUT\n";
    echo "-----------------------------\n";
    
    echo "Load countries from location plugin: " . (get_ecommerce_setting('load_countries_states_cities_from_location_plugin', 1) ? 'SÍ' : 'NO') . "\n";
    echo "Filter cities by state: " . (get_ecommerce_setting('filter_cities_by_state', false) ? 'SÍ' : 'NO') . "\n";
    echo "Default state for city filter: " . (get_ecommerce_setting('default_state_for_city_filter', '') ?: 'No configurado') . "\n";
    
    $selectedCities = json_decode(get_ecommerce_setting('selected_cities_for_checkout', '[]'), true) ?: [];
    echo "Ciudades seleccionadas para checkout: " . count($selectedCities) . " ciudades\n";
    
    if ($piedecuesta && in_array($piedecuesta->id, $selectedCities)) {
        echo "✅ Piedecuesta está en las ciudades seleccionadas\n";
    } elseif ($piedecuesta) {
        echo "❌ Piedecuesta NO está en las ciudades seleccionadas para checkout\n";
        echo "Esto puede causar que no funcione correctamente\n";
    }
    
    echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";
    
} catch (Exception $e) {
    echo "❌ Error durante el diagnóstico: " . $e->getMessage() . "\n";
    echo "Traceback: " . $e->getTraceAsString() . "\n";
}