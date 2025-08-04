# Configuración de Ciudades para Checkout

## Funcionalidad Implementada

Se ha implementado un sistema de configuración de ciudades para el checkout que permite:

1. **Filtrar ciudades por estado**: Activar/desactivar el filtro de ciudades por estado
2. **Seleccionar estado por defecto**: Elegir el estado del cual se mostrarán ciudades (ej: Santander - ID 28)
3. **Seleccionar ciudades específicas**: Cuando se selecciona un estado, aparece la opción de elegir ciudades específicas de ese estado
4. **Mostrar solo ciudades seleccionadas**: En el checkout solo aparecerán las ciudades que se hayan seleccionado

## Cómo Usar la Configuración

### Paso 1: Acceder a la Configuración
1. Ve a `/admin/ecommerce/settings/checkout`
2. Busca la sección "Load countries, states, cities from plugin location"
3. Asegúrate de que esté configurado en "Yes"

### Paso 2: Activar Filtro de Ciudades
1. Activa "Filter cities by state"
2. Esto habilitará las opciones adicionales

### Paso 3: Seleccionar Estado
1. En "Default state for city filtering", selecciona el estado deseado (ej: Santander)
2. Al seleccionar un estado, aparecerá automáticamente la sección "Select specific cities for checkout"

### Paso 4: Seleccionar Ciudades Específicas
1. Marca las ciudades que quieres que aparezcan en el checkout
2. Si no seleccionas ninguna ciudad, se mostrarán todas las ciudades activas del estado
3. Si seleccionas ciudades específicas, solo esas aparecerán en el checkout

### Paso 5: Guardar Configuración
1. Haz clic en "Save settings"
2. La configuración se aplicará inmediatamente

## Archivos Modificados

### Backend (PHP)
- `platform/plugins/ecommerce/src/Forms/Settings/CheckoutSettingForm.php` - Formulario de configuración
- `platform/plugins/ecommerce/src/Http/Controllers/Settings/CheckoutSettingController.php` - Controlador
- `platform/plugins/ecommerce/src/Http/Requests/Settings/CheckoutSettingRequest.php` - Validación
- `platform/plugins/ecommerce/src/Supports/EcommerceHelper.php` - Lógica de ciudades
- `platform/plugins/ecommerce/resources/lang/en/setting.php` - Traducciones

### Frontend (JavaScript)
- `platform/plugins/ecommerce/resources/js/checkout-city-selector.js` - Interactividad del formulario

### Base de Datos
- Nueva configuración `ecommerce_selected_cities_for_checkout` que almacena las ciudades seleccionadas

## Configuraciones de Base de Datos

Las siguientes configuraciones se guardan en la tabla `settings`:

```sql
-- Habilitar carga desde plugin de ubicación
ecommerce_load_countries_states_cities_from_location_plugin = '1'

-- No usar campo de ciudad como texto libre
ecommerce_use_city_field_as_field_text = '0'

-- País por defecto
ecommerce_default_country_at_checkout_page = 'CO'

-- Habilitar filtro de ciudades por estado
ecommerce_filter_cities_by_state = '1'

-- Estado por defecto (Santander)
ecommerce_default_state_for_city_filter = '28'

-- Ciudades seleccionadas (array JSON)
ecommerce_selected_cities_for_checkout = '[1,2,3,4,5]' -- IDs de las ciudades
```

## Flujo de Funcionamiento

1. **Usuario visita checkout**: `/orden/`
2. **Sistema verifica configuración**:
   - Si `filter_cities_by_state` está activo
   - Si hay `selected_cities_for_checkout` configuradas
3. **Carga ciudades**:
   - Si hay ciudades específicas seleccionadas → muestra solo esas
   - Si no hay ciudades específicas → muestra todas las activas del estado configurado
4. **Usuario selecciona ciudad** del dropdown
5. **Sistema aplica reglas de envío** basadas en la ciudad seleccionada

## Comandos de Actualización

Para aplicar la configuración automáticamente:

```bash
# Opción 1: Comando Laravel
php artisan ecommerce:update-checkout-config

# Opción 2: Script SQL
mysql -u root -p6cWRY1PUmiwYciQxJXkg -D mercosan < update-config.sql
```

## Troubleshooting

### Problema: Las ciudades no aparecen en el dropdown
**Solución**: Verificar que:
1. `ecommerce_load_countries_states_cities_from_location_plugin = '1'`
2. `ecommerce_use_city_field_as_field_text = '0'`
3. Existan ciudades activas en la base de datos para el estado seleccionado

### Problema: Aparece input de texto en lugar de select
**Solución**: Ejecutar el comando de actualización o script SQL

### Problema: El JavaScript no funciona
**Solución**: Verificar que el archivo `checkout-city-selector.js` esté en `public/vendor/core/plugins/ecommerce/js/`

## Extensibilidad

El sistema está diseñado para ser extensible:

- **Múltiples estados**: Se puede modificar para manejar múltiples estados
- **Reglas complejas**: Se pueden agregar reglas adicionales de filtrado
- **Integración con envíos**: Las ciudades seleccionadas se integran automáticamente con las reglas de envío existentes