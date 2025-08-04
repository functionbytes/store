@if (Auth::user()->hasAnyPermission([
        'ecommerce.shipping-rule-items.create',
        'ecommerce.shipping-rule-items.bulk-import',
    ]))
    <div class="mt-3 shipping-city-rates">
        <div class="city-rate-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">{{ trans('plugins/ecommerce::shipping.city_specific_rates') }}</h6>
                <small>{{ trans('plugins/ecommerce::shipping.city_specific_rates_description') }}</small>
            </div>
            <div class="btn-group" role="group">
                @if (Auth::user()->hasPermission('ecommerce.shipping-rule-items.create'))
                    <button
                        class="btn btn-primary btn-shipping-rule-item-trigger btn-sm"
                        data-url="{{ route('ecommerce.shipping-rule-items.create', ['shipping_rule_id' => $rule->id]) }}"
                        type="button"
                    >
                        <x-core::icon name="ti ti-plus" />
                        <span>{{ trans('plugins/ecommerce::shipping.add_city_rate') }}</span>
                    </button>
                @endif
                @if (Auth::user()->hasPermission('ecommerce.shipping-rule-items.bulk-import'))
                    <a
                        class="btn btn-outline-primary btn-sm"
                        href="{{ route('ecommerce.shipping-rule-items.bulk-import.index') }}"
                    >
                        <x-core::icon name="ti ti-file-import" />
                        <span>{{ trans('plugins/ecommerce::bulk-import.tables.import') }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif

@include('plugins/ecommerce::shipping.items.table')
