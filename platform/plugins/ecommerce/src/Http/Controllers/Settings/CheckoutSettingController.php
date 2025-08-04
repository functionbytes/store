<?php

namespace Botble\Ecommerce\Http\Controllers\Settings;

use Botble\Ecommerce\Forms\Settings\CheckoutSettingForm;
use Botble\Ecommerce\Http\Requests\Settings\CheckoutSettingRequest;

class CheckoutSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle(trans('plugins/ecommerce::setting.checkout.name'));

        return CheckoutSettingForm::create()->renderForm();
    }

    public function update(CheckoutSettingRequest $request)
    {
        return $this->performUpdate([
            ...$request->validated(),
            'mandatory_form_fields_at_checkout' => $request->input('mandatory_form_fields_at_checkout', []),
            'hide_form_fields_at_checkout' => $request->input('hide_form_fields_at_checkout', []),
            'available_countries' => $request->boolean('available_countries_all') ? [] : $request->input('available_countries', []),
            'filter_cities_by_state' => $request->input('filter_cities_by_state', false),
            'default_state_for_city_filter' => $request->input('default_state_for_city_filter'),
            'selected_cities_for_checkout' => json_encode($request->input('selected_cities_for_checkout', [])),
        ]);
    }
}
