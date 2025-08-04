# Configuración de Tarifas de Envío por Ciudades

## Requisitos Previos

Para que aparezca el botón "Add City Rate" en el sistema de shipping, necesitas seguir estos pasos:

### 1. Verificar Plugin Location
El plugin `location` debe estar activo. Puedes verificarlo en:
- Admin Panel → Plugins → Location (debe estar activado)

### 2. Configurar Checkout Settings
Ve a **Admin Panel → Ecommerce → Settings → Checkout** y:

1. **Habilitar Location Plugin Integration:**
   - Busca la opción: "Load countries states cities from location plugin"
   - Selecciona: **"Yes"**
   - Esto habilitará las opciones de ubicación

2. **Configurar Países Disponibles:**
   - Selecciona los países donde quieres ofrecer envío
   - Asegúrate de tener al menos un país seleccionado

3. **Configurar Estado y Ciudades (para Colombia):**
   - Habilita: "Filter cities by state"
   - Selecciona: "Default state for city filter" → **Santander** (o el estado que necesites)
   - Selecciona las ciudades específicas en "Selected cities for checkout"

### 3. Configurar Zip Code (Opcional)
Si quieres usar tarifas basadas en código postal:
- Habilita: "Zip code enabled"

## Crear Tarifas de Envío por Ciudad

### Paso 1: Crear Región de Envío
1. Ve a **Admin Panel → Ecommerce → Settings → Shipping**
2. Haz clic en **"Select country"**
3. Selecciona el país (ej: Colombia)
4. Haz clic en **"Save"**

### Paso 2: Crear Regla de Envío
1. En la región creada, haz clic en **"Add shipping rule"**
2. Completa los datos:
   - **Name:** Ej: "Envío por Ubicación"
   - **Type:** Selecciona **"Based on location"** (esto es clave)
   - **Shipping fee:** Precio base (ej: 10000)
3. Haz clic en **"Save"**

### Paso 3: Agregar Tarifas Específicas por Ciudad
1. **Expande el accordion** de la regla creada (haz clic en el nombre de la regla)
2. Ahora deberías ver la sección **"City-Specific Shipping Rates"**
3. Haz clic en **"Add City Rate"**
4. Configura:
   - **Shipping rule:** (se selecciona automáticamente)
   - **State:** Selecciona el estado
   - **City:** Selecciona la ciudad específica
   - **Adjustment price:** 
     - Precio adicional: `5000` (suma 5000 al precio base)
     - Descuento: `-2000` (resta 2000 del precio base)
     - Sin cambio: `0` (usa el precio base)
   - **Is enabled:** Sí

## Ejemplos de Configuración

### Ejemplo 1: Tarifas Diferentes por Ciudad
- **Precio base:** 10000 COP
- **Bucaramanga:** +0 COP = 10000 COP total
- **Bogotá:** +5000 COP = 15000 COP total  
- **Medellín:** -2000 COP = 8000 COP total

### Ejemplo 2: Solo Ciudades Específicas
- **Precio base:** 15000 COP
- **Solo habilitar ciudades principales:** Bucaramanga, Bogotá, Medellín
- **Otras ciudades:** No aparecerán como opción en el checkout

## Troubleshooting

### El botón "Add City Rate" no aparece:
1. ✅ Verifica que el plugin Location esté activo
2. ✅ Verifica que "Load countries states cities from location plugin" esté en "Yes"
3. ✅ Verifica que la regla de envío sea tipo "Based on location" o "Based on zipcode"
4. ✅ Verifica que tengas permisos de "ecommerce.settings.shipping"
5. ✅ Asegúrate de expandir el accordion de la regla de envío

### No aparecen ciudades en el checkout:
1. ✅ Verifica que hayas importado los datos de ubicación (países, estados, ciudades)
2. ✅ Ve a Tools → Import/Export Data y importa la data de ubicación si es necesario
3. ✅ Verifica la configuración en Settings → Checkout

### Las tarifas no se calculan correctamente:
1. ✅ Verifica que las reglas estén habilitadas (`Is enabled: Yes`)
2. ✅ Verifica que los precios de ajuste sean correctos
3. ✅ Limpia la cache si es necesario

## Comandos Útiles

Para limpiar la cache de shipping:
```bash
php artisan cache:clear
php artisan config:clear
```

Para verificar la configuración:
```bash
php artisan tinker
>>> get_ecommerce_setting('load_countries_states_cities_from_location_plugin')
>>> \Botble\Ecommerce\Facades\EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()
```