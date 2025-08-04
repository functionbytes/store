<?php
// Debug script para verificar configuración de checkout
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\Location\Models\City;
use Botble\Ecommerce\Facades\EcommerceHelper;

echo "=== DEBUG CHECKOUT CONFIGURATION ===\n\n";

// 1. Check settings in database
echo "1. CONFIGURACIONES EN BASE DE DATOS:\n";
$settings = [
    'load_countries_states_cities_from_location_plugin',
    'use_city_field_as_field_text',
    'filter_cities_by_state',
    'default_state_for_city_filter',
    'selected_cities_for_checkout'
];

foreach ($settings as $setting) {
    $value = get_ecommerce_setting($setting, 'NOT_SET');
    echo "   - ecommerce_{$setting}: {$value}\n";
}

echo "\n2. MÉTODOS HELPER:\n";
echo "   - loadCountriesStatesCitiesFromPluginLocation(): " . (EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation() ? 'true' : 'false') . "\n";
echo "   - useCityFieldAsTextField(): " . (EcommerceHelper::useCityFieldAsTextField() ? 'true' : 'false') . "\n";
echo "   - isFilterCitiesByStateEnabled(): " . (EcommerceHelper::isFilterCitiesByStateEnabled() ? 'true' : 'false') . "\n";
echo "   - getDefaultStateForCityFilter(): " . EcommerceHelper::getDefaultStateForCityFilter() . "\n";

echo "\n3. CIUDADES SELECCIONADAS:\n";
$selectedCitiesJson = get_ecommerce_setting('selected_cities_for_checkout', '[]');
$selectedCities = json_decode($selectedCitiesJson, true) ?: [];
echo "   - JSON crudo: {$selectedCitiesJson}\n";
echo "   - Decodificado: " . print_r($selectedCities, true) . "\n";
echo "   - Cantidad: " . count($selectedCities) . "\n";

echo "\n4. PRUEBA DEL MÉTODO getAvailableCitiesByState():\n";
$availableCities = EcommerceHelper::getAvailableCitiesByState(28);
echo "   - Resultado: " . count($availableCities) . " ciudades\n";
if (count($availableCities) > 0) {
    echo "   - Primeras 5 ciudades:\n";
    $count = 0;
    foreach ($availableCities as $id => $name) {
        if ($count >= 5) break;
        echo "     * ID: {$id}, Nombre: {$name}\n";
        $count++;
    }
} else {
    echo "   - ❌ NO HAY CIUDADES - Este es el problema!\n";
}

echo "\n5. PRUEBA DIRECTA EN BASE DE DATOS:\n";
if (!empty($selectedCities)) {
    $dbCities = City::whereIn('id', $selectedCities)->wherePublished()->count();
    echo "   - Ciudades seleccionadas en DB: {$dbCities}\n";
} else {
    $allSantanderCities = City::where('state_id', 28)->wherePublished()->count();
    echo "   - Todas las ciudades de Santander en DB: {$allSantanderCities}\n";
}

echo "\n=== FIN DEBUG ===\n";