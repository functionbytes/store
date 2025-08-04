# Configuración de Tarifas de Envío para Bucaramanga y Área Metropolitana

## Objetivo
- **Pedidos < $200,000**: Tarifas específicas por ciudad
  - Bucaramanga: $6,000
  - Floridablanca: $15,000
  - Girón: $15,000
  - Piedecuesta: $15,000
- **Pedidos ≥ $200,000**: Envío gratis

## Configuración Paso a Paso

### 1. Configurar Checkout (Requisito Previo)
Ve a **Settings → Checkout** y verifica:
- ✅ "Load countries states cities from location plugin" = **Yes**
- ✅ "Filter cities by state" = **Yes**
- ✅ "Default state for city filter" = **Santander**
- ✅ En "Selected cities for checkout" selecciona:
  - Bucaramanga
  - Floridablanca
  - Girón
  - Piedecuesta

### 2. Crear Región de Envío
1. Ve a **Settings → Shipping**
2. Clic en **"Select country"**
3. Selecciona **Colombia**
4. Clic en **"Save"**

### 3. Crear Primera Regla: Pedidos < $200,000

#### 3.1 Crear la regla base
1. En la región Colombia, clic en **"Add shipping rule"**
2. Configura:
   - **Name:** "Envío Área Metropolitana - Pagado"
   - **Type:** "Based on price" 
   - **From:** 0
   - **To:** 199999 (importante: un peso menos de 200,000)
   - **Shipping fee:** 6000 (precio base para Bucaramanga)
3. Clic en **"Save"**

#### 3.2 Agregar tarifas específicas por ciudad
1. **Expande el accordion** de la regla "Envío Área Metropolitana - Pagado"
2. En la sección **"City-Specific Shipping Rates"**, clic en **"Add City Rate"**

**Para Bucaramanga:**
- Shipping rule: (se selecciona automáticamente)
- State: Santander
- City: Bucaramanga
- Adjustment price: 0 (usa el precio base de $6,000)
- Is enabled: Yes

**Para Floridablanca:**
- Clic en **"Add City Rate"** nuevamente
- State: Santander
- City: Floridablanca
- Adjustment price: 9000 (6000 base + 9000 = 15000 total)
- Is enabled: Yes

**Para Girón:**
- Clic en **"Add City Rate"** nuevamente
- State: Santander
- City: Girón
- Adjustment price: 9000 (6000 base + 9000 = 15000 total)
- Is enabled: Yes

**Para Piedecuesta:**
- Clic en **"Add City Rate"** nuevamente
- State: Santander
- City: Piedecuesta
- Adjustment price: 9000 (6000 base + 9000 = 15000 total)
- Is enabled: Yes

### 4. Crear Segunda Regla: Pedidos ≥ $200,000 (Envío Gratis)

1. En la región Colombia, clic en **"Add shipping rule"** nuevamente
2. Configura:
   - **Name:** "Envío Gratis - Área Metropolitana"
   - **Type:** "Based on price"
   - **From:** 200000
   - **To:** (dejar vacío para "sin límite superior")
   - **Shipping fee:** 0 (envío gratis)
3. Clic en **"Save"**

**Nota:** Para esta regla NO necesitas agregar city rates porque es gratis para todas las ciudades del área metropolitana.

## Resultado Final

El sistema funcionará así:

### Pedido de $150,000 (< $200,000):
- Bucaramanga: $6,000 de envío
- Floridablanca: $15,000 de envío
- Girón: $15,000 de envío
- Piedecuesta: $15,000 de envío

### Pedido de $250,000 (≥ $200,000):
- Todas las ciudades: $0 de envío (GRATIS)

## Configuración Alternativa (Más Limpia)

Si prefieres una configuración más organizada, puedes crear dos reglas separadas con base en ubicación:

### Opción B: Usando "Based on location"

#### Regla 1: Envío Pagado por Ubicación
- **Name:** "Envío Área Metropolitana"
- **Type:** "Based on location"
- **Shipping fee:** 6000 (precio base)
- **City rates:**
  - Bucaramanga: +0 = $6,000
  - Floridablanca: +9000 = $15,000
  - Girón: +9000 = $15,000
  - Piedecuesta: +9000 = $15,000

#### Regla 2: Envío Gratis por Monto
- **Name:** "Envío Gratis +$200K"
- **Type:** "Based on price"
- **From:** 200000
- **To:** (vacío)
- **Shipping fee:** 0

## Verificación

Para verificar que funciona correctamente:

1. **Simula un pedido de $150,000**:
   - Selecciona Bucaramanga → debe mostrar $6,000
   - Selecciona Floridablanca → debe mostrar $15,000

2. **Simula un pedido de $250,000**:
   - Cualquier ciudad → debe mostrar $0 (gratis)

3. **Si no funciona**:
   - Ejecuta: `php diagnostico-shipping.php`
   - Verifica que las reglas estén habilitadas
   - Limpia cache: `php artisan cache:clear`

## Notas Importantes

- ⚠️ **Orden de reglas**: Las reglas se evalúan en orden, la primera que coincida se aplica
- ⚠️ **Rangos de precio**: Asegúrate de que no haya solapamiento (0-199999 y 200000+)
- ⚠️ **Ciudades**: Solo funcionará para las ciudades que hayas configurado en Settings → Checkout
- ⚠️ **Moneda**: Los montos deben estar en pesos colombianos sin decimales