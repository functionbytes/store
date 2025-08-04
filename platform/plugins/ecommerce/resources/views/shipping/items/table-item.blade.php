<tr class="shipping-rule-item-{{ $item->id }} city-rate-item">
    <th scope="row">{{ $item->id }}</th>
    @if($item->state_name)
        <td>
            <span class="fw-semibold">{{ $item->state_name }}</span>
            @if($item->country_name && $item->country_name !== $item->shippingRule->shipping->country_name)
                <br><small class="text-muted">{{ $item->country_name }}</small>
            @endif
        </td>
    @else
        <td><span class="text-muted">&mdash;</span></td>
    @endif
    @if($item->city_name)
        <td>
            <span class="city-name">{{ $item->city_name }}</span>
            @if($item->state_name)
                <br><small class="state-name">{{ $item->state_name }}</small>
            @endif
        </td>
    @else
        <td><span class="text-muted">&mdash;</span></td>
    @endif
    @if($item->zip_code)
        <td>{{ $item->zip_code }}</td>
    @else
        <td>&mdash;</td>
    @endif
    <td>
        <div class="price-info">
            <span class="adjustment-price {{ $item->adjustment_price < 0 ? 'negative' : ($item->adjustment_price > 0 ? 'positive' : 'neutral') }}">
                {{ $item->adjustment_price != 0 ? ($item->adjustment_price < 0 ? '' : '+') . format_price($item->adjustment_price) : '&mdash;' }}
            </span>
            <br><small class="final-price">
                {{ trans('plugins/ecommerce::shipping.final_price') }}: {{ format_price(max($item->adjustment_price + $item->shippingRule->price, 0)) }}
            </small>
        </div>
    </td>
    <td>
        @if ($item->is_enabled)
            {!! Html::tag('span', trans('core/base::base.yes'), ['class' => 'text-primary']) !!}
        @else
            {!! Html::tag('span', trans('core/base::base.no'), ['class' => 'text-secondary']) !!}
        @endif
    </td>
    <td>{{ BaseHelper::formatDate($item->created_at) }}</td>
    @if ($hasOperations)
        <td class="text-center">
            @if (Auth::user()->hasPermission('ecommerce.shipping-rule-items.edit'))
                <button
                    class="btn btn-icon btn-sm btn-primary px-2 py-1 btn-shipping-rule-item-trigger"
                    data-url="{{ route('ecommerce.shipping-rule-items.edit', $item->id) }}"
                    type="button"
                >
                    <x-core::icon name="ti ti-pencil" />
                </button>
            @endif

            @if (Auth::user()->hasPermission('ecommerce.shipping-rule-items.destroy'))
                <button
                    class="btn btn-icon btn-sm btn-danger px-2 py-1 btn-confirm-delete-rule-item-modal-trigger"
                    data-section="{{ route('ecommerce.shipping-rule-items.destroy', $item->id) }}"
                    data-name="{{ $item->name_item }}"
                    type="button"
                >
                    <x-core::icon name="ti ti-trash" />
                </button>
            @endif
        </td>
    @endif
</tr>
