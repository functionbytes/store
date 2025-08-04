# Instrucciones para Configurar el Checkout

## Problema Identificado

El campo de ciudad aparece como un input de texto en lugar de un select dropdown porque las siguientes configuraciones no están correctamente establecidas en la base de datos:

1. `ecommerce_load_countries_states_cities_from_location_plugin` debe ser `1`
2. `ecommerce_use_city_field_as_field_text` debe ser `0`
3. `ecommerce_filter_cities_by_state` debe ser `1`
4. `ecommerce_default_state_for_city_filter` debe ser `28` (Santander)

## Solución 1: Ejecutar Script SQL

Ejecuta el archivo `update-config.sql` en tu base de datos MySQL:

```bash
mysql -u root -p6cWRY1PUmiwYciQxJXkg -D mercosan < update-config.sql
```

## Solución 2: Ejecutar Comando Artisan

Si tienes acceso a la línea de comandos de Laravel:

```bash
php artisan ecommerce:update-checkout-config
```

## Solución 3: Actualización Manual en Base de Datos

Si ninguna de las anteriores funciona, ejecuta estos comandos SQL manualmente:

```sql
REPLACE INTO settings (key, value, created_at, updated_at) VALUES 
('ecommerce_load_countries_states_cities_from_location_plugin', '1', NOW(), NOW());

REPLACE INTO settings (key, value, created_at, updated_at) VALUES 
('ecommerce_use_city_field_as_field_text', '0', NOW(), NOW());

REPLACE INTO settings (key, value, created_at, updated_at) VALUES 
('ecommerce_filter_cities_by_state', '1', NOW(), NOW());

REPLACE INTO settings (key, value, created_at, updated_at) VALUES 
('ecommerce_default_state_for_city_filter', '28', NOW(), NOW());
```

## Verificar Resultado

Después de ejecutar cualquiera de las soluciones:

1. Limpia el cache de Laravel:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

2. Ve a `https://mercosan.test/orden/`

3. Deberías ver:
   - Un select dropdown para "Ciudad" en lugar de un input de texto
   - El dropdown debe cargarse automáticamente con ciudades de Santander
   - Al seleccionar una ciudad, se deben aplicar las reglas de envío correspondientes

## Archivos Modificados

Los siguientes archivos han sido modificados para soportar esta funcionalidad:

- `platform/plugins/ecommerce/src/Supports/EcommerceHelper.php` - Métodos para filtrado de ciudades
- `platform/plugins/ecommerce/src/Forms/Settings/CheckoutSettingForm.php` - Formulario de configuración
- `platform/plugins/ecommerce/resources/views/orders/partials/address-form.blade.php` - Template de checkout
- `platform/plugins/location/src/Http/Controllers/CityController.php` - Endpoint AJAX para ciudades

## Troubleshooting

Si el select sigue apareciendo vacío:
1. Verifica que existan ciudades activas para Santander (state_id = 28) en la tabla `location_cities`
2. Verifica que el endpoint `https://mercosan.test/ajax/cities-by-state` responda correctamente
3. Revisa la consola del navegador por errores JavaScript