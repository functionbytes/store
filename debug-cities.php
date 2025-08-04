<?php
// Simple debug script to check cities
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\Location\Models\City;
use Botble\Ecommerce\Facades\EcommerceHelper;

echo "=== Debug City Loading ===\n";

// Check settings
echo "Settings:\n";
echo "- load_countries_states_cities_from_location_plugin: " . get_ecommerce_setting('load_countries_states_cities_from_location_plugin', 'not set') . "\n";
echo "- use_city_field_as_field_text: " . get_ecommerce_setting('use_city_field_as_field_text', 'not set') . "\n";
echo "- filter_cities_by_state: " . get_ecommerce_setting('filter_cities_by_state', 'not set') . "\n";
echo "- default_state_for_city_filter: " . get_ecommerce_setting('default_state_for_city_filter', 'not set') . "\n";

echo "\n";

// Check EcommerceHelper methods
echo "EcommerceHelper methods:\n";
if (method_exists(EcommerceHelper::class, 'isFilterCitiesByStateEnabled')) {
    echo "- isFilterCitiesByStateEnabled(): " . (EcommerceHelper::isFilterCitiesByStateEnabled() ? 'true' : 'false') . "\n";
} else {
    echo "- isFilterCitiesByStateEnabled(): METHOD NOT EXISTS\n";
}

if (method_exists(EcommerceHelper::class, 'getDefaultStateForCityFilter')) {
    echo "- getDefaultStateForCityFilter(): " . EcommerceHelper::getDefaultStateForCityFilter() . "\n";
} else {
    echo "- getDefaultStateForCityFilter(): METHOD NOT EXISTS\n";
}

echo "\n";

// Check cities in database
echo "Cities in database:\n";
$totalCities = City::count();
echo "- Total cities: $totalCities\n";

$publishedCities = City::wherePublished()->count();
echo "- Published cities: $publishedCities\n";

$santanderCities = City::where('state_id', 28)->wherePublished()->count();
echo "- Published cities in Santander (state_id=28): $santanderCities\n";

if ($santanderCities > 0) {
    echo "\nFirst 10 cities in Santander:\n";
    $cities = City::where('state_id', 28)->wherePublished()->orderBy('name')->take(10)->get(['id', 'name']);
    foreach ($cities as $city) {
        echo "- ID: {$city->id}, Name: {$city->name}\n";
    }
}

echo "\n=== End Debug ===\n";