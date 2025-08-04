<?php

// Script para corregir la tarifa de envío de Piedecuesta
// Ejecutar con: php fix-piedecuesta-shipping.php

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use Botble\Ecommerce\Models\ShippingRule;
use Botble\Ecommerce\Models\ShippingRuleItem;
use Botble\Location\Models\City;
use Botble\Location\Models\State;

echo "=== CORRECCIÓN DE TARIFA DE PIEDECUESTA ===\n\n";

try {
    // 1. Buscar Piedecuesta
    echo "1. BUSCANDO CIUDAD PIEDECUESTA\n";
    echo "--------------------------------\n";
    
    $piedecuesta = City::where('name', 'LIKE', '%Piedecuesta%')->first();
    if (!$piedecuesta) {
        echo "❌ Piedecuesta no encontrada\n";
        exit(1);
    }
    
    echo "✅ Piedecuesta encontrada:\n";
    echo "   - ID: {$piedecuesta->id}\n";
    echo "   - Nombre: {$piedecuesta->name}\n";
    echo "   - Estado ID: {$piedecuesta->state_id}\n";
    
    // 2. Buscar regla de envío para pedidos < $200,000
    echo "\n2. BUSCANDO REGLA DE ENVÍO PAGADO\n";
    echo "--------------------------------\n";
    
    $rule = ShippingRule::where('name', 'LIKE', '%Área Metropolitana%')
                       ->where('from', '<=', 199999)
                       ->where('price', 6000)
                       ->first();
    
    if (!$rule) {
        // Buscar regla alternativa
        $rule = ShippingRule::where('type', 'based_on_price')
                           ->where('from', 0)
                           ->where('to', '<=', 200000)
                           ->where('price', 6000)
                           ->first();
    }
    
    if (!$rule) {
        echo "❌ No se encontró la regla de envío pagado\n";
        echo "Creando nueva regla...\n";
        
        // Buscar shipping de Colombia
        $shipping = \Botble\Ecommerce\Models\Shipping::where('country', 'CO')->first();
        if (!$shipping) {
            echo "❌ No se encontró shipping de Colombia\n";
            exit(1);
        }
        
        $rule = ShippingRule::create([
            'shipping_id' => $shipping->id,
            'name' => 'Envío Área Metropolitana - Pagado',
            'type' => \Botble\Ecommerce\Enums\ShippingRuleTypeEnum::BASED_ON_PRICE,
            'from' => 0,
            'to' => 199999,
            'price' => 6000,
        ]);
        
        echo "✅ Regla creada con ID: {$rule->id}\n";
    } else {
        echo "✅ Regla encontrada:\n";
        echo "   - ID: {$rule->id}\n";
        echo "   - Nombre: {$rule->name}\n";
        echo "   - Precio base: \${$rule->price}\n";
        echo "   - Rango: \${$rule->from} - \${$rule->to}\n";
    }
    
    // 3. Buscar o crear item para Piedecuesta
    echo "\n3. VERIFICANDO ITEM PARA PIEDECUESTA\n";
    echo "-----------------------------------\n";
    
    $item = ShippingRuleItem::where('shipping_rule_id', $rule->id)
                            ->where('city', $piedecuesta->id)
                            ->first();
    
    if ($item) {
        echo "✅ Item existente encontrado:\n";
        echo "   - ID: {$item->id}\n";
        echo "   - Precio ajuste actual: \${$item->adjustment_price}\n";
        echo "   - Precio final actual: \$" . ($rule->price + $item->adjustment_price) . "\n";
        echo "   - Habilitado: " . ($item->is_enabled ? 'SÍ' : 'NO') . "\n";
        
        // Verificar si el precio es correcto
        $precioEsperado = 15000;
        $ajusteEsperado = $precioEsperado - $rule->price; // 15000 - 6000 = 9000
        
        if ($item->adjustment_price != $ajusteEsperado || !$item->is_enabled) {
            echo "\n⚠️ CORRIGIENDO PRECIO DE PIEDECUESTA\n";
            echo "   - Precio esperado: \${$precioEsperado}\n";
            echo "   - Ajuste esperado: \${$ajusteEsperado}\n";
            
            $item->update([
                'adjustment_price' => $ajusteEsperado,
                'is_enabled' => true,
            ]);
            
            echo "✅ Precio corregido a \${$precioEsperado}\n";
        } else {
            echo "✅ El precio ya está correcto\n";
        }
    } else {
        echo "❌ Item no encontrado, creando...\n";
        
        $item = ShippingRuleItem::create([
            'shipping_rule_id' => $rule->id,
            'country' => 'CO',
            'state' => $piedecuesta->state_id,
            'city' => $piedecuesta->id,
            'adjustment_price' => 9000, // 15000 - 6000
            'is_enabled' => true,
        ]);
        
        echo "✅ Item creado para Piedecuesta con precio final de \$15,000\n";
    }
    
    // 4. Verificar configuración final
    echo "\n4. VERIFICACIÓN FINAL\n";
    echo "--------------------\n";
    
    $finalItem = ShippingRuleItem::where('shipping_rule_id', $rule->id)
                                 ->where('city', $piedecuesta->id)
                                 ->first();
    
    if ($finalItem) {
        $precioFinal = $rule->price + $finalItem->adjustment_price;
        echo "✅ CONFIGURACIÓN FINAL:\n";
        echo "   - Ciudad: {$piedecuesta->name}\n";
        echo "   - Precio base: \${$rule->price}\n";
        echo "   - Ajuste: \${$finalItem->adjustment_price}\n";
        echo "   - PRECIO FINAL: \${$precioFinal}\n";
        echo "   - Habilitado: " . ($finalItem->is_enabled ? 'SÍ' : 'NO') . "\n";
        
        if ($precioFinal == 15000 && $finalItem->is_enabled) {
            echo "\n🎉 ¡CORRECCIÓN EXITOSA!\n";
            echo "Piedecuesta ahora debería mostrar \$15,000 en el checkout\n";
        } else {
            echo "\n⚠️ Algo no está bien, verifica manualmente\n";
        }
    }
    
    // 5. Limpiar caché si es necesario
    echo "\n5. LIMPIANDO CACHÉ\n";
    echo "-----------------\n";
    try {
        \Illuminate\Support\Facades\Cache::flush();
        echo "✅ Caché limpiado\n";
    } catch (Exception $e) {
        echo "⚠️ No se pudo limpiar caché: {$e->getMessage()}\n";
    }
    
    echo "\n=== CORRECCIÓN COMPLETADA ===\n";
    echo "Prueba ahora en el checkout seleccionando Piedecuesta\n";
    echo "Debería mostrar \$15,000 en lugar de \$6,000\n\n";
    
} catch (Exception $e) {
    echo "❌ Error durante la corrección: " . $e->getMessage() . "\n";
    echo "Traceback: " . $e->getTraceAsString() . "\n";
}