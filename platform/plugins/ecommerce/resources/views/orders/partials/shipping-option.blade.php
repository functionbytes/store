@php
    $isAutoSelected = $isAutoSelected ?? false;
    $isFreeShipping = (float) $shippingItem['price'] === 0.0;
    $citySpecific = Arr::get($shippingItem, 'city_specific', false);
    $cityName = Arr::get($shippingItem, 'city_name');
    $adjustmentPrice = Arr::get($shippingItem, 'adjustment_price', 0);
    $basePrice = Arr::get($shippingItem, 'base_price', $shippingItem['price']);
@endphp

<li class="list-group-item {{ $isAutoSelected ? 'border-primary' : '' }}">
    {!! Form::radio(Arr::get($attributes, 'name'), $shippingKey, Arr::get($attributes, 'checked'), $attributes) !!}
    <label for="{{ Arr::get($attributes, 'id') }}" class="{{ $isAutoSelected ? 'text-primary' : '' }}">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                @if ($image = Arr::get($shippingItem, 'image'))
                    <img
                        src="{{ $image }}"
                        alt="{{ $shippingItem['name'] }}"
                        style="max-height: 40px; max-width: 55px"
                        class="me-2"
                    >
                @endif
                
                <div class="d-inline-block">
                    <strong>{{ $shippingItem['name'] }}</strong>
                    
                    @if ($isAutoSelected)
                        <span class="badge bg-primary ms-2">{{ __('Auto-selected') }}</span>
                    @endif
                    
                    @if ($citySpecific && $cityName)
                        <br><small class="text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ __('Special rate for :city', ['city' => $cityName]) }}
                        </small>
                    @endif
                </div>
            </div>
            
            <div class="text-end">
                @if ($isFreeShipping)
                    <strong class="text-success fs-5">{{ __('Free shipping') }}</strong>
                    @if ($isAutoSelected)
                        <br><small class="text-success"><i class="fas fa-gift me-1"></i>{{ __('Congratulations!') }}</small>
                    @endif
                @else
                    <strong class="fs-5">{{ format_price($shippingItem['price']) }}</strong>
                    @if ($citySpecific && $adjustmentPrice != 0)
                        <br><small class="text-muted">
                            {{ __('Base') }}: {{ format_price($basePrice) }}
                            @if ($adjustmentPrice > 0)
                                <span class="text-warning">+ {{ format_price($adjustmentPrice) }}</span>
                            @else
                                <span class="text-success">- {{ format_price(abs($adjustmentPrice)) }}</span>
                            @endif
                        </small>
                    @endif
                @endif
            </div>
        </div>
        
        <div class="mt-2">
            @if ($description = Arr::get($shippingItem, 'description'))
                <small class="text-secondary">{!! BaseHelper::clean($description) !!}</small>
            @endif
            
            @if ($isAutoSelected && !$description)
                <small class="text-primary">
                    <i class="fas fa-info-circle me-1"></i>
                    @if ($isFreeShipping)
                        {{ __('Free shipping automatically applied to your order!') }}
                    @elseif ($citySpecific)
                        {{ __('Best rate selected for your location') }}
                    @else
                        {{ __('Only available shipping method') }}
                    @endif
                </small>
            @endif
            
            @if ($errorMessage = Arr::get($shippingItem, 'error_message'))
                <small class="text-danger">{!! BaseHelper::clean($errorMessage) !!}</small>
            @endif
        </div>
    </label>
</li>
