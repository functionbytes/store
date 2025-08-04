{{--
    Plantilla de correo de pedido rediseñada para coincidir con la identidad corporativa de Mercosan.
    Se ha ajustado la paleta de colores, añadido un espacio para el logo y el icono principal.
--}}

<style>
    /* Estilos para la legibilidad en modo oscuro y responsividad */
    body {
        margin: 0;
        padding: 0;
        width: 100% !important;
        background-color: #f8f9fa; /* Color de fondo claro */
    }
    .container {
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
    }
    .card {
        background-color: #ffffff;
        border: 1px solid #dee2e6; /* Borde sutil */
        border-radius: 0.375rem; /* Bordes redondeados */
        margin: 20px 0;
    }
    .card-body {
        padding: 2rem;
    }
    .btn {
        display: inline-block;
        font-weight: 600;
        color: #ffffff;
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        user-select: none;
        background-color: #d32f2f; /* Rojo corporativo */
        border: 1px solid #d32f2f;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        border-radius: 0.25rem;
        text-decoration: none;
    }
    .text-muted {
        color: #6c757d; /* Color de texto silenciado */
    }
    .product-row {
        padding: 1rem 0;
        border-bottom: 1px solid #dee2e6;
    }
    .product-row:last-child {
        border-bottom: none;
    }
    .totals-row td {
        padding: 0.5rem 0;
    }
    .font-weight-bold {
        font-weight: 700;
    }
    @media (max-width: 600px) {
        .card-body {
            padding: 1.5rem;
        }
    }
</style>

<table width="100%" border="0" cellpadding="0" cellspacing="0" >
    <tr>
        <td align="center">
            <table class="container" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <table class="card" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr style="background-color: #fff;border: 1px solid #8888881a;box-shadow: 0 0 10px #8888881a; border-radius: 10px; margin-bottom: 10px;">
                                <td class="card-body" align="center" style="padding: 1rem;">
                                    @if (!$order->dont_show_order_info_in_product_list)
                                        <h1 style="font-size: 1.5rem; font-weight: 600; margin: 0 0 1rem;">¡Gracias por tu pedido!</h1>
                                        <p style="font-size: 1rem; color: #343a40; margin: 0 0 1.5rem;">Aquí tienes un resumen de tu compra. Puedes ver los detalles completos en el siguiente botón:</p>
                                        <p style="margin: 0 0 1.5rem;">
                                            <a href="{{ route('public.orders.tracking', ['order_id' => $order->code, 'email' => $order->user->email ?: $order->address->email]) }}" class="btn">
                                                Ver mi Pedido
                                            </a>
                                        </p>
                                        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 2rem 0;">
                                    @endif

                                    @foreach($products ?? $order->products as $orderProduct)
                                        <table class="product-row" width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="74" style="padding-right: 10px;">
                                                    <img src="{{ RvMedia::getImageUrl($orderProduct->product_image, 'thumb') }}" width="64" height="64" alt="{{ $orderProduct->product_name }}" style="border-radius: 0.25rem;">
                                                </td>
                                                <td style="vertical-align: top; text-align: left;">
                                                    <p style="font-weight:500;text-transform: uppercase;margin: 0rem;">{{ $orderProduct->product_name }}</p>
                                                    @if ($attributes = Arr::get($orderProduct->options, 'attributes'))
                                                        <p class="text-muted" style="font-size: 14px;margin: 0px;">{{ $attributes }}</p>
                                                    @endif
                                                    @if ($orderProduct->product_options_implode)
                                                        <p class="text-muted" style="font-size: 14px;margin: 0px;">{{ $orderProduct->product_options_implode }}</p>
                                                    @endif
                                                    <p style="font-size: 14px;margin: 0px;">{{ trans('plugins/ecommerce::products.form.quantity') }}: {{ $orderProduct->qty }}</p>
                                                </td>
                                                <td style="text-align: right;font-weight: 600;vertical-align: center;font-size: 17px;">
                                                    {{ format_price($orderProduct->price) }}
                                                </td>
                                            </tr>
                                        </table>
                                    @endforeach

                                    <!-- Totales -->
                                    @if (!$order->dont_show_order_info_in_product_list)
                                        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 2rem 0 1rem;">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align: left;">
                                            @if ($order->sub_total != $order->amount)
                                                <tr class="totals-row">
                                                    <td>{{ trans('plugins/ecommerce::products.form.sub_total') }}</td>
                                                    <td align="right">{{ format_price($order->sub_total) }}</td>
                                                </tr>
                                            @endif
                                            @if ((float)$order->shipping_amount)
                                                <tr class="totals-row">
                                                    <td>{{ trans('plugins/ecommerce::products.form.shipping_fee') }}</td>
                                                    <td align="right">{{ format_price($order->shipping_amount) }}</td>
                                                </tr>
                                            @endif
                                            @if ((float)$order->tax_amount)
                                                <tr class="totals-row">
                                                    <td>{{ trans('plugins/ecommerce::products.form.tax') }}</td>
                                                    <td align="right">{{ format_price($order->tax_amount) }}</td>
                                                </tr>
                                            @endif
                                            @if ((float)$order->discount_amount)
                                                <tr class="totals-row">
                                                    <td>{{ trans('plugins/ecommerce::products.form.discount') }}</td>
                                                    <td align="right" style="color: #198754;">-{{ format_price($order->discount_amount) }}</td>
                                                </tr>
                                            @endif
                                            @if ((float)$order->payment_fee)
                                                <tr class="totals-row">
                                                    <td>{{ trans('plugins/payment::payment.payment_fee') }}</td>
                                                    <td align="right">{{ format_price($order->payment_fee) }}</td>
                                                </tr>
                                            @endif
                                            <tr class="totals-row">
                                                <td colspan="2"><hr style="border: none; border-top: 1px solid #dee2e6; margin: 0.5rem 0;"></td>
                                            </tr>
                                            <tr class="totals-row">
                                                <td class="font-weight-bold" style="font-size: 1.25rem;">{{ trans('plugins/ecommerce::products.form.total') }}</td>
                                                <td align="right" class="font-weight-bold" style="font-size: 1.25rem;">{{ format_price($order->amount) }}</td>
                                            </tr>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
