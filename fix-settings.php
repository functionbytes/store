<?php
// Script directo para corregir configuraciones
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CORRIGIENDO CONFIGURACIONES ===\n\n";

// Configuraciones que necesitamos
$settings = [
    'ecommerce_load_countries_states_cities_from_location_plugin' => '1',
    'ecommerce_use_city_field_as_field_text' => '0',
    'ecommerce_default_country_at_checkout_page' => 'CO',
    'ecommerce_filter_cities_by_state' => '1',
    'ecommerce_default_state_for_city_filter' => '28',
    'ecommerce_selected_cities_for_checkout' => '[]' // Inicialmente vacío
];

foreach ($settings as $key => $value) {
    try {
        // Verificar si existe
        $existing = DB::table('settings')->where('key', $key)->first();
        
        if ($existing) {
            // Actualizar
            DB::table('settings')
                ->where('key', $key)
                ->update([
                    'value' => $value,
                    'updated_at' => now()
                ]);
            echo "✓ Actualizado: {$key} = {$value}\n";
        } else {
            // Crear
            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "✓ Creado: {$key} = {$value}\n";
        }
    } catch (Exception $e) {
        echo "❌ Error con {$key}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== VERIFICANDO RESULTADOS ===\n";

foreach ($settings as $key => $expectedValue) {
    $actualValue = DB::table('settings')->where('key', $key)->value('value');
    $status = ($actualValue == $expectedValue) ? '✅' : '❌';
    echo "{$status} {$key}: esperado='{$expectedValue}', actual='{$actualValue}'\n";
}

echo "\n=== LIMPIANDO CACHE ===\n";
try {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "✓ Cache limpiado\n";
    
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    echo "✓ Config limpiado\n";
} catch (Exception $e) {
    echo "❌ Error limpiando cache: " . $e->getMessage() . "\n";
}

echo "\n🎉 ¡Proceso completado!\n";
echo "Ahora ve a https://mercosan.test/admin/ecommerce/settings/checkout\n";
echo "y selecciona algunas ciudades, luego guarda la configuración.\n";
echo "Después prueba en https://mercosan.test/orden/\n";