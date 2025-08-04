@echo off
echo Actualizando configuracion de checkout...

php artisan tinker --execute="
DB::table('settings')->updateOrInsert(['key' => 'ecommerce_load_countries_states_cities_from_location_plugin'], ['value' => '1', 'updated_at' => now()]);
DB::table('settings')->updateOrInsert(['key' => 'ecommerce_use_city_field_as_field_text'], ['value' => '0', 'updated_at' => now()]);
DB::table('settings')->updateOrInsert(['key' => 'ecommerce_default_country_at_checkout_page'], ['value' => 'CO', 'updated_at' => now()]);
DB::table('settings')->updateOrInsert(['key' => 'ecommerce_filter_cities_by_state'], ['value' => '1', 'updated_at' => now()]);
DB::table('settings')->updateOrInsert(['key' => 'ecommerce_default_state_for_city_filter'], ['value' => '28', 'updated_at' => now()]);
echo 'Configuracion actualizada exitosamente!';
"

echo Limpiando cache...
php artisan cache:clear
php artisan config:clear

echo.
echo ✅ Configuracion actualizada!
echo Ahora ve a https://mercosan.test/orden/ y deberia aparecer el select de ciudades.
pause