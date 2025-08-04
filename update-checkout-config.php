<?php

// Script para actualizar la configuración de checkout
require_once 'bootstrap/app.php';

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Actualizando configuración de checkout...\n";

// Actualizar configuraciones necesarias
$settings = [
    'load_countries_states_cities_from_location_plugin' => 1,
    'use_city_field_as_field_text' => 0,
    'default_country_at_checkout_page' => 'CO',
    'filter_cities_by_state' => 1,
    'default_state_for_city_filter' => '28'
];

foreach ($settings as $key => $value) {
    $prefixedKey = 'ecommerce_' . $key;
    
    // Buscar si existe la configuración
    $existing = DB::table('settings')->where('key', $prefixedKey)->first();
    
    if ($existing) {
        DB::table('settings')
            ->where('key', $prefixedKey)
            ->update(['value' => $value]);
        echo "✓ Actualizado: $prefixedKey = $value\n";
    } else {
        DB::table('settings')->insert([
            'key' => $prefixedKey,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✓ Creado: $prefixedKey = $value\n";
    }
}

// Limpiar cache de configuración
\Illuminate\Support\Facades\Artisan::call('cache:clear');
\Illuminate\Support\Facades\Artisan::call('config:clear');

echo "\n🎉 Configuración actualizada correctamente!\n";
echo "Ahora ve a https://mercosan.test/orden/ y deberías ver el select de ciudades.\n";