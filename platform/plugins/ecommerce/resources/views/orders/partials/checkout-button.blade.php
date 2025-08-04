@if (EcommerceHelper::isValidToProcessCheckout())
    <button
        class="btn payment-checkout-btn payment-checkout-btn-step float-end w-100"
        data-processing-text="{{ __('Processing. Please wait...') }}"
        data-error-header="{{ __('Error') }}"
        type="submit"
    >
        {{ __('Checkout') }}
    </button>
@else
    <span class="btn payment-checkout-btn-step float-end disabled w-100">
        {{ __('Checkout') }}
    </span>
@endif
