# Comandos de Diagnóstico para Tarifas de Envío

## Ejecutar Diagnóstico Completo
```bash
php diagnostico-shipping.php
```

## Comandos Artisan Útiles

### Verificar configuración actual
```bash
php artisan tinker
```
Luego ejecuta estos comandos dentro de tinker:
```php
// Verificar si la integración con location está habilitada
get_ecommerce_setting('load_countries_states_cities_from_location_plugin')

// Verificar función helper
\Botble\Ecommerce\Facades\EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()

// Verificar datos de ubicación
\Botble\Location\Models\Country::count()
\Botble\Location\Models\State::count()
\Botble\Location\Models\City::count()

// Verificar configuraciones relacionadas
get_ecommerce_setting('filter_cities_by_state')
get_ecommerce_setting('default_state_for_city_filter')
get_ecommerce_setting('zip_code_enabled')

// Salir de tinker
exit
```

### Limpiar cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Verificar plugins activos
```bash
php artisan tinker
```
```php
// Verificar si el plugin location está activo
is_plugin_active('location')

// Ver todos los plugins activos
get_active_plugins()
```

### Verificar tablas de ubicación
```bash
php artisan tinker
```
```php
// Ver algunos países
\Botble\Location\Models\Country::take(5)->get(['id', 'name', 'code'])

// Ver algunos estados/departamentos
\Botble\Location\Models\State::take(5)->get(['id', 'name', 'country_id'])

// Ver algunas ciudades
\Botble\Location\Models\City::take(5)->get(['id', 'name', 'state_id'])

// Ver estados de Colombia (si país Colombia tiene ID 48)
\Botble\Location\Models\State::where('country_id', 48)->get(['id', 'name'])

// Ver ciudades de Santander (si estado tiene ID 28)
\Botble\Location\Models\City::where('state_id', 28)->take(10)->get(['id', 'name'])
```

## Solución Rápida

Si el diagnóstico muestra problemas, ejecuta estos pasos:

### 1. Activar plugin Location (si no está activo)
Ve al admin panel → Plugins → Location → Activate

### 2. Habilitar integración en Checkout
Ve a Settings → Checkout → Busca "Load countries states cities from location plugin" → Selecciona "Yes"

### 3. Importar datos de ubicación (si faltan)
Ve a Tools → Import/Export Data → Import Location Data

### 4. Configurar estado y ciudades por defecto
Ve a Settings → Checkout → Configura:
- Filter cities by state: Yes
- Default state for city filter: Santander (o el que necesites)
- Selected cities for checkout: Selecciona las ciudades

### 5. Crear regla de envío correcta
Ve a Settings → Shipping → Select country → Add shipping rule → Type: "Based on location"

## Verificación Final

Después de configurar todo, verifica:
1. El diagnóstico debe mostrar todo en ✅
2. En Settings → Shipping debe aparecer el botón "Add City Rate" cuando expandas una regla "Based on location"
3. En el checkout deben aparecer las ciudades seleccionadas