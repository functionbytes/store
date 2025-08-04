<?php

// Script para crear automáticamente las reglas de envío para Bucaramanga
// Ejecutar con: php crear-reglas-bucaramanga.php

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use Botble\Ecommerce\Models\Shipping;
use Botble\Ecommerce\Models\ShippingRule;
use Botble\Ecommerce\Models\ShippingRuleItem;
use Botble\Ecommerce\Enums\ShippingRuleTypeEnum;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Location\Models\City;

echo "=== CONFIGURACIÓN AUTOMÁTICA DE REGLAS DE ENVÍO BUCARAMANGA ===\n\n";

try {
    // 1. Buscar o crear shipping para Colombia
    $colombia = Country::where('code', 'CO')->first();
    if (!$colombia) {
        echo "❌ Error: No se encontró el país Colombia (CO)\n";
        exit(1);
    }

    $shipping = Shipping::where('country', 'CO')->first();
    if (!$shipping) {
        $shipping = Shipping::create([
            'title' => 'Colombia',
            'country' => 'CO',
        ]);
        echo "✅ Región de envío Colombia creada\n";
    } else {
        echo "✅ Región de envío Colombia ya existe\n";
    }

    // 2. Buscar estado Santander
    $santander = State::where('country_id', $colombia->id)
                     ->where('name', 'LIKE', '%Santander%')
                     ->first();
    
    if (!$santander) {
        echo "❌ Error: No se encontró el estado Santander\n";
        exit(1);
    }
    echo "✅ Estado Santander encontrado (ID: {$santander->id})\n";

    // 3. Buscar las ciudades
    $ciudades = [
        'Bucaramanga' => ['precio_ajuste' => 0, 'precio_final' => 6000],
        'Floridablanca' => ['precio_ajuste' => 9000, 'precio_final' => 15000],
        'Girón' => ['precio_ajuste' => 9000, 'precio_final' => 15000],
        'Piedecuesta' => ['precio_ajuste' => 9000, 'precio_final' => 15000],
    ];

    $ciudades_ids = [];
    foreach ($ciudades as $nombre_ciudad => $config) {
        $ciudad = City::where('state_id', $santander->id)
                     ->where('name', 'LIKE', "%{$nombre_ciudad}%")
                     ->first();
        
        if ($ciudad) {
            $ciudades_ids[$nombre_ciudad] = [
                'id' => $ciudad->id,
                'precio_ajuste' => $config['precio_ajuste'],
                'precio_final' => $config['precio_final']
            ];
            echo "✅ Ciudad {$nombre_ciudad} encontrada (ID: {$ciudad->id})\n";
        } else {
            echo "⚠️ Ciudad {$nombre_ciudad} no encontrada - se omitirá\n";
        }
    }

    if (empty($ciudades_ids)) {
        echo "❌ Error: No se encontraron ciudades para configurar\n";
        exit(1);
    }

    // 4. Crear regla para pedidos < $200,000
    $regla_pagada = ShippingRule::where('shipping_id', $shipping->id)
                               ->where('name', 'Envío Área Metropolitana - Pagado')
                               ->first();

    if (!$regla_pagada) {
        $regla_pagada = ShippingRule::create([
            'shipping_id' => $shipping->id,
            'name' => 'Envío Área Metropolitana - Pagado',
            'type' => ShippingRuleTypeEnum::BASED_ON_PRICE,
            'from' => 0,
            'to' => 199999,
            'price' => 6000, // precio base
        ]);
        echo "✅ Regla para pedidos < \$200,000 creada\n";
    } else {
        echo "✅ Regla para pedidos < \$200,000 ya existe\n";
    }

    // 5. Crear items de regla para cada ciudad
    foreach ($ciudades_ids as $nombre_ciudad => $config) {
        $item_existente = ShippingRuleItem::where('shipping_rule_id', $regla_pagada->id)
                                         ->where('city', $config['id'])
                                         ->first();

        if (!$item_existente) {
            ShippingRuleItem::create([
                'shipping_rule_id' => $regla_pagada->id,
                'country' => 'CO',
                'state' => $santander->id,
                'city' => $config['id'],
                'adjustment_price' => $config['precio_ajuste'],
                'is_enabled' => true,
            ]);
            echo "✅ Tarifa para {$nombre_ciudad}: \${$config['precio_final']} creada\n";
        } else {
            echo "✅ Tarifa para {$nombre_ciudad} ya existe\n";
        }
    }

    // 6. Crear regla para pedidos ≥ $200,000 (envío gratis)
    $regla_gratis = ShippingRule::where('shipping_id', $shipping->id)
                               ->where('name', 'Envío Gratis - Área Metropolitana')
                               ->first();

    if (!$regla_gratis) {
        $regla_gratis = ShippingRule::create([
            'shipping_id' => $shipping->id,
            'name' => 'Envío Gratis - Área Metropolitana',
            'type' => ShippingRuleTypeEnum::BASED_ON_PRICE,
            'from' => 200000,
            'to' => null, // sin límite superior
            'price' => 0, // gratis
        ]);
        echo "✅ Regla para pedidos ≥ \$200,000 (gratis) creada\n";
    } else {
        echo "✅ Regla para pedidos ≥ \$200,000 ya existe\n";
    }

    echo "\n=== CONFIGURACIÓN COMPLETADA ===\n";
    echo "✅ Reglas de envío configuradas correctamente\n\n";

    echo "RESUMEN:\n";
    echo "• Pedidos < \$200,000:\n";
    foreach ($ciudades_ids as $nombre_ciudad => $config) {
        echo "  - {$nombre_ciudad}: \${$config['precio_final']}\n";
    }
    echo "• Pedidos ≥ \$200,000: GRATIS\n\n";

    echo "Puedes verificar la configuración en:\n";
    echo "Admin Panel → Ecommerce → Settings → Shipping\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Ejecuta primero: php diagnostico-shipping.php\n";
}