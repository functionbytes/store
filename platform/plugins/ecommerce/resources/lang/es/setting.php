<?php

return [
    'save_settings' => 'Guardar configuración',
    'general' => [
        'name' => 'General',
        'description' => 'Ver y actualizar la configuración general',
    ],
    'abandoned_cart' => [
        'name' => 'Recuperación de Carritos Abandonados',
        'description' => 'Recupera automáticamente las ventas perdidas enviando correos electrónicos dirigidos a clientes que abandonaron sus carritos de compras',
        
        // Section titles
        'general_settings' => 'Configuración General',
        'timing_settings' => 'Tiempo y Programación',
        'email_settings' => 'Configuración de Correo',
        'advanced_settings' => 'Opciones Avanzadas',
        'testing_tools' => 'Herramientas de Prueba',
        
        // General settings
        'enable' => 'Habilitar Correos de Carrito Abandonado',
        'enable_help' => 'Activar correos automáticos de recuperación de carritos abandonados. Cuando está habilitado, el sistema detectará automáticamente los carritos abandonados y enviará correos de recuperación.',
        
        // Timing settings
        'delay_hours' => 'Retraso Inicial (horas)',
        'delay_hours_help' => 'Esperar esta cantidad de horas después del abandono del carrito antes de enviar el primer correo de recuperación. Recomendado: 1-2 horas.',
        'max_hours' => 'Edad Máxima del Carrito (horas)',
        'max_hours_help' => 'No enviar correos para carritos más antiguos que esto. Después de este tiempo, los carritos se consideran demasiado antiguos para recuperar. Por defecto: 168 horas (7 días).',
        'email_limit' => 'Límite de Correos por Lote',
        'email_limit_help' => 'Número máximo de correos a enviar en cada ejecución automatizada. Ayuda a controlar la carga del servidor y los límites de envío de correos.',
        
        // Email configuration
        'email_template' => 'Plantilla de Correo',
        'email_template_help' => 'Elige el diseño y estilo para tus correos de carrito abandonado. La plantilla moderna incluye incentivos y mejores elementos de conversión.',
        'template_modern' => 'Plantilla Moderna (Recomendada)',
        'template_classic' => 'Plantilla Clásica',
        'email_subject' => 'Línea de Asunto del Correo',
        'email_subject_help' => 'La línea de asunto que aparece en las bandejas de entrada de los clientes. Usa lenguaje atractivo para fomentar la apertura.',
        'max_emails' => 'Máx. Correos por Carrito',
        'max_emails_help' => 'Número máximo de correos de seguimiento a enviar por un solo carrito abandonado. Previene el spam y la fatiga del correo.',
        'email_interval' => 'Intervalo de Correos (horas)',
        'email_interval_help' => 'Tiempo de espera entre el envío de correos de seguimiento para el mismo carrito. Recomendado: 24-72 horas.',
        
        // Advanced settings
        'offer_free_shipping' => 'Incluir Oferta de Envío Gratis',
        'offer_free_shipping_help' => 'Mostrar un incentivo de envío gratis en la plantilla de correo para fomentar la finalización. Esta es solo una opción de visualización.',
        'exclude_categories' => 'Excluir Categorías de Productos',
        'exclude_categories_help' => 'Lista separada por comas de slugs de categorías a excluir de los correos de carrito abandonado (ej., descargas-digitales, tarjetas-regalo).',
        
        // Testing tools
        'test_email' => 'Dirección de Correo de Prueba',
        'test_email_help' => 'Ingresa una dirección de correo para enviar un correo de prueba de carrito abandonado y previsualizar cómo se ve.',
        
        // Messages
        'no_sample_order' => 'No se encontró una muestra de carrito abandonado para enviar correo de prueba',
        'template_not_found' => 'Plantilla de correo ":template" no encontrada o no habilitada',
        'test_email_sent' => '✅ Correo de prueba enviado exitosamente a :email',
        'test_email_failed' => '❌ Error al enviar correo de prueba: :error',
        'dry_run_result' => 'Vista previa: Se encontraron :count carrito(s) abandonado(s) que recibirían correos',
        'emails_queued' => '📧 Se encolaron exitosamente :count correo(s) de carrito abandonado para envío',
        'bulk_send_failed' => '❌ Error al encolar correos de carrito abandonado: :error',
        
        // Validation messages
        'validation' => [
            'delay_hours_min' => 'El retraso debe ser de al menos 1 hora',
            'delay_hours_max' => 'El retraso no puede exceder 72 horas',
            'max_hours_min' => 'La edad máxima debe ser de al menos 24 horas',
            'max_hours_max' => 'La edad máxima no puede exceder 30 días',
            'email_limit_min' => 'El límite de correos debe ser de al menos 1',
            'email_limit_max' => 'El límite de correos no puede exceder 500',
            'template_invalid' => 'Plantilla de correo seleccionada inválida',
            'max_emails_min' => 'El máximo de correos debe ser de al menos 1',
            'max_emails_max' => 'El máximo de correos no puede exceder 10',
            'interval_min' => 'El intervalo de correos debe ser de al menos 1 hora',
            'interval_max' => 'El intervalo de correos no puede exceder 7 días',
        ],
    ],
    'currency' => [
        'name' => 'Monedas',
        'description' => 'Ver y actualizar la configuración de monedas',
        'currency_setting_description' => 'Ver y actualizar las monedas utilizadas en el sitio web',
        'form' => [
            'enable_auto_detect_visitor_currency' => 'Habilitar detección automática de la moneda del visitante',
            'add_space_between_price_and_currency' => 'Agregar un espacio entre el precio y la moneda',
            'thousands_separator' => 'Separador de miles',
            'decimal_separator' => 'Separador decimal',
            'separator_period' => 'Punto (.)',
            'separator_comma' => 'Coma (,)',
            'separator_space' => 'Espacio ( )',
            'api_key' => 'Clave API de tasas de cambio',
            'api_key_helper' => 'Obtener clave API de tasas de cambio en :link',
            'update_currency_rates' => 'Actualizar tasas de moneda',
            'use_exchange_rate_from_api' => 'Usar tasa de cambio de API',
            'clear_cache_rates' => 'Limpiar tasas de caché',
            'auto_detect_visitor_currency_description' => 'Detecta la moneda del visitante basada en el idioma del navegador. Anulará la selección de moneda predeterminada.',
            'exchange_rate' => [
                'api_provider' => 'Proveedor de API',
                'select' => '-- Seleccionar --',
                'none' => 'Ninguno',
                'provider' => [
                    'api_layer' => 'API Layer',
                    'open_exchange_rate' => 'Open Exchange Rates',
                ],
                'open_exchange_app_id' => 'ID de App de Open Exchange Rates',
            ],
            'default_currency_warning' => 'Para la moneda predeterminada, la tasa de cambio debe ser 1.',
        ],
    ],
    'product' => [
        'name' => 'Productos',
        'description' => 'Ver y actualizar la configuración de productos',
        'product_settings' => 'Configuración de productos',
        'product_settings_description' => 'Configurar reglas para productos',
        'form' => [
            'show_number_of_products' => 'Mostrar número de productos en la página individual del producto',
            'show_number_of_products_helper' => 'Mostrar el número total de productos en la página de detalles del producto.',
            'show_out_of_stock_products' => 'Mostrar productos agotados',
            'show_out_of_stock_products_helper' => 'Si está habilitado, los productos agotados se mostrarán en la página de listado de productos.',
            'how_to_display_product_variation_images' => 'Cómo mostrar imágenes de variación de productos',
            'how_to_display_product_variation_images_helper' => 'Elegir si mostrar solo imágenes específicas de variación o incluir tanto imágenes de variación como del producto principal.',
            'only_variation_images' => 'Solo imágenes de variación',
            'variation_images_and_main_product_images' => 'Imágenes de variación e imágenes del producto principal',
            'enable_product_options' => 'Habilitar opciones de producto',
            'enable_product_options_helper' => 'Permitir que los productos tengan opciones personalizables como tamaño, color, etc.',
            'is_enabled_cross_sale_products' => 'Habilitar productos de venta cruzada',
            'is_enabled_cross_sale_products_helper' => 'Mostrar sugerencias de productos de venta cruzada para fomentar compras adicionales.',
            'is_enabled_related_products' => 'Habilitar productos relacionados',
            'is_enabled_related_products_helper' => 'Mostrar productos relacionados basados en categoría, o seleccionados por el administrador en el formulario del producto.',
            'auto_generate_product_sku' => 'Generar automáticamente SKU al crear producto',
            'auto_generate_product_sku_helper' => 'Generar automáticamente SKUs únicos para nuevos productos basados en el formato siguiente.',
            'product_sku_format' => 'Formato de SKU',
            'product_sku_format_helper' => 'Puedes usar %s (1 carácter de cadena) o %d (1 dígito) en el formato para generar cadena aleatoria. Ej: SKU-%s%s%s-HN-%d%d%d',
            'enable_product_specification' => 'Habilitar especificación de producto',
            'enable_product_specification_help' => 'Si está habilitado, la tabla de especificaciones del producto se mostrará en la página de detalles del producto.',
            'make_product_barcode_required' => 'Hacer obligatorio el código de barras del producto',
            'make_product_barcode_required_helper' => 'Si está habilitado, el código de barras del producto será obligatorio al crear un producto.',
        ],
    ],
];