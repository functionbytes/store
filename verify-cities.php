<?php
// Verification script to check city configuration
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\Location\Models\City;
use Botble\Ecommerce\Facades\EcommerceHelper;

echo "=== VERIFICACIÓN DE CONFIGURACIÓN DE CIUDADES ===\n\n";

// 1. Check critical settings
echo "1. CONFIGURACIONES CRÍTICAS:\n";
$loadFromPlugin = get_ecommerce_setting('load_countries_states_cities_from_location_plugin', 'NOT_SET');
$useTextField = get_ecommerce_setting('use_city_field_as_field_text', 'NOT_SET');
$filterCities = get_ecommerce_setting('filter_cities_by_state', 'NOT_SET');
$defaultState = get_ecommerce_setting('default_state_for_city_filter', 'NOT_SET');

echo "   - load_countries_states_cities_from_location_plugin: {$loadFromPlugin} " . ($loadFromPlugin == '1' ? '✅' : '❌') . "\n";
echo "   - use_city_field_as_field_text: {$useTextField} " . ($useTextField == '0' ? '✅' : '❌') . "\n";
echo "   - filter_cities_by_state: {$filterCities} " . ($filterCities == '1' ? '✅' : '❌') . "\n";
echo "   - default_state_for_city_filter: {$defaultState} " . ($defaultState == '28' ? '✅' : '❌') . "\n\n";

// 2. Check helper methods
echo "2. MÉTODOS DE HELPER:\n";
$loadCountries = EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation();
$useCityText = EcommerceHelper::useCityFieldAsTextField();
$filterEnabled = EcommerceHelper::isFilterCitiesByStateEnabled();
$defaultStateFilter = EcommerceHelper::getDefaultStateForCityFilter();

echo "   - loadCountriesStatesCitiesFromPluginLocation(): " . ($loadCountries ? 'true' : 'false') . " " . ($loadCountries ? '✅' : '❌') . "\n";
echo "   - useCityFieldAsTextField(): " . ($useCityText ? 'true' : 'false') . " " . ($useCityText ? '❌' : '✅') . "\n";
echo "   - isFilterCitiesByStateEnabled(): " . ($filterEnabled ? 'true' : 'false') . " " . ($filterEnabled ? '✅' : '❌') . "\n";
echo "   - getDefaultStateForCityFilter(): {$defaultStateFilter} " . ($defaultStateFilter == '28' ? '✅' : '❌') . "\n\n";

// 3. Check cities in database
echo "3. CIUDADES EN BASE DE DATOS:\n";
$totalCities = City::count();
$publishedCities = City::wherePublished()->count();
$santanderCities = City::where('state_id', 28)->wherePublished()->count();

echo "   - Total ciudades: {$totalCities}\n";
echo "   - Ciudades publicadas: {$publishedCities}\n";
echo "   - Ciudades publicadas en Santander (ID 28): {$santanderCities} " . ($santanderCities > 0 ? '✅' : '❌') . "\n\n";

// 4. Show sample cities
if ($santanderCities > 0) {
    echo "4. CIUDADES DE EJEMPLO EN SANTANDER:\n";
    $cities = City::where('state_id', 28)->wherePublished()->orderBy('name')->take(5)->get(['id', 'name']);
    foreach ($cities as $city) {
        echo "   - ID: {$city->id}, Nombre: {$city->name}\n";
    }
    echo "\n";
}

// 5. Test getAvailableCitiesByState method
echo "5. PRUEBA DEL MÉTODO getAvailableCitiesByState():\n";
$availableCities = EcommerceHelper::getAvailableCitiesByState(null, null);
echo "   - Ciudades disponibles: " . count($availableCities) . " " . (count($availableCities) > 0 ? '✅' : '❌') . "\n";

if (count($availableCities) > 0) {
    echo "   - Primeras 3 ciudades:\n";
    $count = 0;
    foreach ($availableCities as $id => $name) {
        if ($count >= 3) break;
        echo "     * ID: {$id}, Nombre: {$name}\n";
        $count++;
    }
}

echo "\n";

// 6. Final assessment
echo "6. EVALUACIÓN FINAL:\n";
$allGood = ($loadFromPlugin == '1' && $useTextField == '0' && $filterCities == '1' && 
           $defaultState == '28' && !$useCityText && $santanderCities > 0 && count($availableCities) > 0);

if ($allGood) {
    echo "   🎉 ¡TODO CONFIGURADO CORRECTAMENTE!\n";
    echo "   El campo de ciudad debería aparecer como select dropdown con ciudades de Santander.\n";
} else {
    echo "   ⚠️  CONFIGURACIÓN INCOMPLETA\n";
    echo "   Ejecuta el script update-config.sql o el comando artisan para corregir.\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";