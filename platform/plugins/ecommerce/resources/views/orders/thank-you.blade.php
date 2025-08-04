@extends('plugins/ecommerce::orders.master')

@section('title', __('Order successfully. Order number :id', ['id' => $order->code]))

@section('content')
    <div class="row">
        <div class="col-lg-7 col-md-6 col-12">

            <div class="thank-you">
                <x-core::icon name="ti ti-circle-check-filled" />

                <div class="d-inline-block">
                    <h3 class="thank-you-sentence">
                        {{ __('Your order is successfully placed') }}
                    </h3>
                    <p>{{ __('Thank you for purchasing our products!') }}</p>
                </div>
            </div>

            @php
                $orders = $order;

                if ($orders instanceof \Illuminate\Support\Collection) {
                    $order = $orders->where('is_finished', true)->first();

                    if (! $order) {
                        $order = $orders->first();
                    }
                }

                $userInfo = $order->address->id ? $order->address : $order->user;
            @endphp

            <div class="order-customer-info">
                <h3> {{ __('Customer information') }}</h3>
                @if ($userInfo->id)
                    @if ($userInfo->name)
                        <p>
                            <span class="d-inline-block">{{ __('Full name') }}:</span>
                            <span class="order-customer-info-meta">{{ $userInfo->name }}</span>
                        </p>
                    @endif

                    @if ($userInfo->phone)
                        <p>
                            <span class="d-inline-block">{{ __('Phone') }}:</span>
                            <span class="order-customer-info-meta">{{ $userInfo->phone }}</span>
                        </p>
                    @endif

                    @if ($userInfo->email)
                        <p>
                            <span class="d-inline-block">{{ __('Email') }}:</span>
                            <span class="order-customer-info-meta">{{ $userInfo->email }}</span>
                        </p>
                    @endif

                    @if ($order->full_address && in_array('address', EcommerceHelper::getHiddenFieldsAtCheckout()) && ! empty($isShowShipping))
                        <p>
                            <span class="d-inline-block">{{ __('Address') }}:</span>
                            <span class="order-customer-info-meta">{{ $order->full_address }}</span>
                        </p>
                    @endif
                @endif

                @if (!empty($isShowShipping))
                    <p>
                        <span class="d-inline-block">{{ __('Shipping method') }}:</span>
                        <span class="order-customer-info-meta">{{ $order->shipping_method_name }} -
                {{ format_price($order->shipping_amount) }}</span>
                    </p>
                @endif

                @if (is_plugin_active('payment') && $order->payment->id)
                    <p>
                        <span class="d-inline-block">{{ __('Payment method') }}:</span>
                        <span class="order-customer-info-meta">{{ $order->payment->payment_channel->label() }}</span>
                    </p>
                    <p>
                        <span class="d-inline-block">{{ __('Payment status') }}:</span>
                        <span
                            class="order-customer-info-meta"
                            style="text-transform: uppercase"
                            data-bb-target="ecommerce-order-payment-status"
                        >{!! BaseHelper::clean($order->payment->status->toHtml()) !!}</span>
                    </p>

                    @if (setting('payment_bank_transfer_display_bank_info_at_the_checkout_success_page', false) &&
                            ($bankInfo = OrderHelper::getOrderBankInfo($orders)))
                        {!! $bankInfo !!}
                    @endif
                @endif

                {!! apply_filters('ecommerce_thank_you_customer_info', null, $order) !!}
            </div>

            @if ($tax = $order->taxInformation)
                <div class="order-customer-info">
                    <h3> {{ __('Tax information') }}</h3>
                    <p>
                        <span class="d-inline-block">{{ __('Company name') }}:</span>
                        <span class="order-customer-info-meta">{{ $tax->company_name }}</span>
                    </p>

                    <p>
                        <span class="d-inline-block">{{ __('Company tax code') }}:</span>
                        <span class="order-customer-info-meta">{{ $tax->company_tax_code }}</span>
                    </p>

                    <p>
                        <span class="d-inline-block">{{ __('Company email') }}:</span>
                        <span class="order-customer-info-meta">{{ $tax->company_email }}</span>
                    </p>

                    <p>
                        <span class="d-inline-block">{{ __('Company address') }}:</span>
                        <span class="order-customer-info-meta">{{ $tax->company_address }}</span>
                    </p>
                </div>
            @endif


            <a class="btn payment-checkout-btn" href="{{ BaseHelper::getHomepageUrl() }}">
                {{ __('Continue shopping') }}
            </a>
        </div>
        <div class="col-lg-5 col-md-6 d-none d-md-block mt-5 mt-md-0 mb-5">
            <div class="my-3 bg-light p-3">
                <div class="pt-3 mb-5 order-item-info">
                    <div class="align-items-center">
                        <h6 class="d-inline-block">{{ __('Order number') }}: {{ $order->code }}</h6>
                    </div>

                    <div class="checkout-success-products">
                        <div id="{{ 'cart-item-' . $order->id }}">
                            @foreach ($order->products as $orderProduct)
                                <div class="row cart-item">
                                    <div class="col-lg-3 col-md-3">
                                        <div class="checkout-product-img-wrapper d-inline-block">
                                            <img
                                                class="item-thumb img-thumbnail img-rounded mb-2 mb-md-0"
                                                src="{{ RvMedia::getImageUrl($orderProduct->product_image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                                                alt="{{ $orderProduct->product_name }}"
                                            >
                                            <span class="checkout-quantity">{{ $orderProduct->qty }}</span>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 col-md-5">
                                        <p class="mb-2 mb-md-0">{!! BaseHelper::clean($orderProduct->product_name) !!}</p>
                                        <p class="mb-2 mb-md-0">
                                            <small>{{ Arr::get($orderProduct->options, 'attributes', '') }}</small>
                                        </p>
                                        @if (!empty($orderProduct->product_options) && is_array($orderProduct->product_options))
                                            {!! render_product_options_html($orderProduct->product_options, $orderProduct->price) !!}
                                        @endif

                                        @include(EcommerceHelper::viewPath('includes.cart-item-options-extras'), [
                                            'options' => $orderProduct->options,
                                        ])
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-4 float-md-end text-md-end">
                                        <p>{{ format_price($orderProduct->price) }}</p>
                                    </div>
                                </div>
                            @endforeach

                            @if (!empty($isShowTotalInfo))
                                @include('plugins/ecommerce::orders.thank-you.total-info', compact('order'))
                            @endif
                        </div>
                    </div>
                </div>

                @if (
    $order->sub_total != $order->amount
    || $order->shipping_method->getValue()
    || (EcommerceHelper::isTaxEnabled() && (float) $order->tax_amount)
    || (float) $order->discount_amount
)
                    <hr class="border-dark-subtle" />
                @endif

                @if ($order->sub_total != $order->amount)
                    @include('plugins/ecommerce::orders.thank-you.total-row', [
                        'label' => __('Subtotal'),
                        'value' => format_price($order->sub_total),
                    ])
                @endif

                @if ($order->shipping_method->getValue())
                    @include('plugins/ecommerce::orders.thank-you.total-row', [
                        'label' =>
                            __('Shipping fee') .
                            ($order->is_free_shipping
                                ? ' <small>(' . __('Using coupon code') . ': <strong>' . $order->coupon_code . '</strong>)</small>'
                                : ''),
                        'value' => $order->shipping_method_name . ((float) $order->shipping_amount ? ' - ' . format_price($order->shipping_amount) : ' - ' . __('Free')),
                    ])
                @endif

                @if (EcommerceHelper::isTaxEnabled() && (float) $order->tax_amount)
                    @include('plugins/ecommerce::orders.thank-you.total-row', [
                        'label' => __('Tax'),
                        'value' => format_price($order->tax_amount),
                    ])
                @endif

                @if ((float) $order->discount_amount)
                    @include('plugins/ecommerce::orders.thank-you.total-row', [
                        'label' => __('Discount'),
                        'value' =>
                            format_price($order->discount_amount) .
                            ($order->coupon_code
                                ? ' <small>(' . __('Using coupon code') . ': <strong>' . $order->coupon_code . '</strong>)</small>'
                                : ''),
                    ])
                @endif

                @if ((float) $order->payment_fee)
                    @include('plugins/ecommerce::orders.thank-you.total-row', [
                        'label' => __('plugins/payment::payment.payment_fee'),
                        'value' => format_price($order->payment_fee),
                    ])
                @endif

                {!! apply_filters('ecommerce_thank_you_total_info', null, $order) !!}

                <hr class="border-dark-subtle" />

                <div class="row">
                    <div class="col-6">
                        <p>{{ __('Total') }}:</p>
                    </div>
                    <div class="col-6 float-end">
                        <p class="total-text raw-total-text"> {{ format_price($order->amount) }} </p>
                    </div>
                </div>

                 </div>
        </div>
    </div>
@stop
