<?php

// Script de diagnóstico para tarifas de envío por ciudad
// Ejecutar con: php diagnostico-shipping.php

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Location\Models\City;

echo "=== DIAGNÓSTICO DE CONFIGURACIÓN DE TARIFAS DE ENVÍO ===\n\n";

// 1. Verificar si el plugin location está activo
echo "1. Plugin Location: ";
if (is_plugin_active('location')) {
    echo "✅ ACTIVO\n";
} else {
    echo "❌ INACTIVO - Necesitas activar el plugin Location\n";
}

// 2. Verificar configuración de location plugin
echo "2. Load countries from location plugin: ";
$loadFromLocation = get_ecommerce_setting('load_countries_states_cities_from_location_plugin', 1);
if ($loadFromLocation) {
    echo "✅ HABILITADO\n";
} else {
    echo "❌ DESHABILITADO - Ve a Settings → Checkout y habilita esta opción\n";
}

// 3. Verificar función helper
echo "3. EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation(): ";
if (EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()) {
    echo "✅ TRUE\n";
} else {
    echo "❌ FALSE\n";
}

// 4. Verificar datos en base de datos
echo "4. Datos de ubicación en base de datos:\n";
if (is_plugin_active('location')) {
    try {
        $countries = Country::count();
        $states = State::count();
        $cities = City::count();
        
        echo "   - Países: {$countries}\n";
        echo "   - Estados: {$states}\n";
        echo "   - Ciudades: {$cities}\n";
        
        if ($countries > 0 && $states > 0) {
            echo "   ✅ Datos de ubicación disponibles\n";
        } else {
            echo "   ❌ Faltan datos de ubicación - Importa los datos en Tools → Import/Export Data\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error al verificar datos: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Plugin Location no activo\n";
}

// 5. Verificar zip code enabled
echo "5. Zip Code habilitado: ";
if (EcommerceHelper::isZipCodeEnabled()) {
    echo "✅ SÍ\n";
} else {
    echo "ℹ️ NO (opcional para tarifas por ciudad)\n";
}

// 6. Verificar configuraciones de checkout
echo "6. Configuraciones de checkout:\n";
echo "   - Filter cities by state: " . (get_ecommerce_setting('filter_cities_by_state', false) ? "✅ SÍ" : "ℹ️ NO") . "\n";
echo "   - Default state for city filter: " . (get_ecommerce_setting('default_state_for_city_filter', '') ?: 'No configurado') . "\n";
echo "   - Default country: " . (get_ecommerce_setting('default_country_at_checkout_page', 'CO')) . "\n";

echo "\n=== INSTRUCCIONES ===\n";
echo "Para que aparezca el botón 'Add City Rate':\n";
echo "1. Asegúrate de que todos los puntos arriba estén en ✅\n";
echo "2. Ve a Admin → Ecommerce → Settings → Shipping\n";
echo "3. Selecciona un país (Select country)\n";
echo "4. Crea una regla con Type = 'Based on location'\n";
echo "5. Expande el accordion de la regla\n";
echo "6. Deberías ver el botón 'Add City Rate'\n\n";

if (!EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()) {
    echo "⚠️ PROBLEMA PRINCIPAL: loadCountriesStatesCitiesFromPluginLocation() devuelve FALSE\n";
    echo "Soluciones:\n";
    echo "- Ve a Settings → Checkout\n";
    echo "- Habilita 'Load countries states cities from location plugin' = YES\n";
    echo "- Verifica que el plugin Location esté activo\n";
    echo "- Importa datos de ubicación si es necesario\n";
}

// 7. Verificación específica para área metropolitana de Bucaramanga
echo "\n7. Verificación específica para área metropolitana de Bucaramanga:\n";
if (is_plugin_active('location')) {
    try {
        // Buscar Santander
        $santander = \Botble\Location\Models\State::where('name', 'LIKE', '%Santander%')->first();
        if ($santander) {
            echo "   ✅ Estado Santander encontrado (ID: {$santander->id})\n";
            
            // Buscar ciudades específicas
            $ciudades_requeridas = ['Bucaramanga', 'Floridablanca', 'Girón', 'Piedecuesta'];
            $ciudades_encontradas = 0;
            
            foreach ($ciudades_requeridas as $ciudad_nombre) {
                $ciudad = \Botble\Location\Models\City::where('state_id', $santander->id)
                                                      ->where('name', 'LIKE', "%{$ciudad_nombre}%")
                                                      ->first();
                if ($ciudad) {
                    echo "   ✅ {$ciudad_nombre} encontrada (ID: {$ciudad->id})\n";
                    $ciudades_encontradas++;
                } else {
                    echo "   ❌ {$ciudad_nombre} NO encontrada\n";
                }
            }
            
            if ($ciudades_encontradas == 4) {
                echo "   ✅ Todas las ciudades del área metropolitana están disponibles\n";
                echo "\n🚀 PUEDES EJECUTAR: php crear-reglas-bucaramanga.php\n";
                echo "   Este script creará automáticamente las reglas configuradas\n";
            } else {
                echo "   ⚠️ Faltan ciudades - verifica los datos de ubicación\n";
            }
        } else {
            echo "   ❌ Estado Santander no encontrado\n";
        }
        
        // Verificar si ya existen reglas configuradas
        $reglas_existentes = \Botble\Ecommerce\Models\ShippingRule::where('name', 'LIKE', '%Área Metropolitana%')->count();
        if ($reglas_existentes > 0) {
            echo "   ℹ️ Ya tienes {$reglas_existentes} regla(s) del área metropolitana configurada(s)\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error verificando área metropolitana: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Plugin Location no activo\n";
}

echo "\n=== PRÓXIMOS PASOS PARA TU CONFIGURACIÓN ===\n";
echo "1. Ejecuta: php crear-reglas-bucaramanga.php (crea las reglas automáticamente)\n";
echo "2. O configura manualmente siguiendo: CONFIGURACION_EJEMPLO_BUCARAMANGA.md\n";
echo "3. Verifica en Admin Panel → Settings → Shipping\n";
echo "4. Prueba con pedidos de diferentes montos\n";