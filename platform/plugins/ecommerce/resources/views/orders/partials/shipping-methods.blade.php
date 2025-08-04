<div @style(['display: none' => (bool) get_ecommerce_setting('disable_shipping_options', false)])>
    {{-- Shipping summary information --}}
    @if (isset($shippingSummary) && isset($orderTotal))
        <div class="mb-3">
            @if ($shippingSummary['has_free_shipping'])
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-gift me-2"></i>
                    <strong>{{ __('Free shipping applied!') }}</strong>
                    {{ __('Your order qualifies for free shipping. No delivery selection needed.') }}
                </div>
                
                {{-- Hidden input for automatic free shipping selection --}}
                <input type="hidden" name="shipping_method" value="default">
                <input type="hidden" name="shipping_option" value="free_shipping_auto">
                
                {{-- Skip the rest of shipping selection --}}
                @php $skipShippingSelection = true; @endphp
            @elseif ($shippingSummary['amount_to_free_shipping'] > 0)
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ __('Add :amount more to get free shipping!', ['amount' => format_price($shippingSummary['amount_to_free_shipping'])]) }}
                </div>
            @endif
        </div>
    @endif
    
    @if (! empty($shipping) && !isset($skipShippingSelection))
        <div class="payment-checkout-form">
            <input
                name="shipping_option"
                type="hidden"
                value="{{ BaseHelper::stringify(old('shipping_option', $defaultShippingOption)) }}"
            >

            <ul class="list-group list_payment_method">
                
                @foreach ($shipping as $shippingKey => $shippingItems)
                    @foreach ($shippingItems as $shippingOption => $shippingItem)
                        @include(
                            'plugins/ecommerce::orders.partials.shipping-option',
                            [
                                'shippingItem' => $shippingItem,
                                'attributes' => [
                                    'id' => "shipping-method-$shippingKey-$shippingOption",
                                    'name' => 'shipping_method',
                                    'class' => 'magic-radio shipping_method_input',
                                    'checked' => old('shipping_method', $defaultShippingMethod) == $shippingKey && old('shipping_option', $defaultShippingOption) == $shippingOption,
                                    'disabled' => Arr::get($shippingItem, 'disabled'),
                                    'data-option' => $shippingOption,
                                ],
                                'shippingKey' => $shippingKey,
                                'shippingOption' => $shippingOption,
                            ]
                        )
                    @endforeach
                @endforeach
            </ul>
        </div>
    @elseif (!isset($skipShippingSelection))
        @php
            $sessionCheckoutData = $sessionCheckoutData ?? OrderHelper::getOrderSessionData();
        @endphp

        @if ($sessionCheckoutData && Arr::get($sessionCheckoutData, 'country'))
            <p class="text-muted">{{ __('No shipping methods were found with your provided shipping information!') }}</p>
        @else
            <p class="text-muted">{{ __('Please fill out all shipping information to view available shipping methods!') }}</p>
        @endif
    @endif
    
    {{-- Show simplified message when shipping selection is skipped --}}
    @if (isset($skipShippingSelection))
        <div class="text-center py-3">
            <i class="fas fa-truck text-success" style="font-size: 2rem;"></i>
            <p class="mt-2 mb-0 text-success fw-bold">{{ __('Free shipping automatically applied to your order') }}</p>
            <small class="text-muted">{{ __('We\'ll process your order for free delivery') }}</small>
        </div>
    @endif
</div>
