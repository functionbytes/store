<li class="list-group-item payment-method-item">
    <input class="magic-radio js_payment_method"
           type="radio"
           name="payment_method"
           id="payment_{{ $moduleName }}"
           value="{{ $moduleName }}"
           data-bs-toggle="collapse"
           data-bs-target=".payment_{{ $moduleName }}_wrap"
           @if (($selecting_method ?? '') == $moduleName) checked @endif>

    <label for="payment_{{ $moduleName }}" class="text-start">
        {{ trans('Pagar con Wompi') }}
    </label>
    <div class="payment-method-logo">
        <img src="{{ asset('vendor/core/plugins/wompi/images/wompi.png') }}" alt="Wompi" >
    </div>


    <div class="payment_{{ $moduleName }}_wrap payment_collapse_wrap collapse @if (($selecting_method ?? '') == $moduleName) show @endif" style="padding: 15px 0;">
        <p>{!! BaseHelper::clean(get_payment_setting('description', $moduleName, __('Pago seguro con tarjeta de crédito o débito a través de Wompi.'))) !!}</p>
    </div>
</li>
