<?php

namespace Botble\Ecommerce\Http\Requests\Settings;

use Botble\Support\Http\Requests\Request;

class AbandonedCartSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'abandoned_cart_enabled' => 'nullable|boolean',
            'abandoned_cart_delay_hours' => 'required|integer|min:1|max:72',
            'abandoned_cart_max_hours' => 'required|integer|min:24|max:720',
            'abandoned_cart_email_limit' => 'required|integer|min:1|max:500',
            'abandoned_cart_email_template' => 'required|string|in:abandoned_cart,order_recover',
            'abandoned_cart_email_subject' => 'required|string|max:255',
            'abandoned_cart_max_emails' => 'required|integer|min:1|max:10',
            'abandoned_cart_email_interval_hours' => 'required|integer|min:1|max:168',
            'abandoned_cart_offer_free_shipping' => 'nullable|boolean',
            'abandoned_cart_exclude_categories' => 'nullable|string|max:1000',
            'abandoned_cart_test_email' => 'nullable|email',
            'cart_destroy_on_logout' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'abandoned_cart_enabled' => trans('plugins/ecommerce::setting.abandoned_cart.enable'),
            'abandoned_cart_delay_hours' => trans('plugins/ecommerce::setting.abandoned_cart.delay_hours'),
            'abandoned_cart_max_hours' => trans('plugins/ecommerce::setting.abandoned_cart.max_hours'),
            'abandoned_cart_email_limit' => trans('plugins/ecommerce::setting.abandoned_cart.email_limit'),
            'abandoned_cart_email_template' => trans('plugins/ecommerce::setting.abandoned_cart.email_template'),
            'abandoned_cart_email_subject' => trans('plugins/ecommerce::setting.abandoned_cart.email_subject'),
            'abandoned_cart_max_emails' => trans('plugins/ecommerce::setting.abandoned_cart.max_emails'),
            'abandoned_cart_email_interval_hours' => trans('plugins/ecommerce::setting.abandoned_cart.email_interval'),
            'abandoned_cart_offer_free_shipping' => trans('plugins/ecommerce::setting.abandoned_cart.offer_free_shipping'),
            'abandoned_cart_exclude_categories' => trans('plugins/ecommerce::setting.abandoned_cart.exclude_categories'),
            'abandoned_cart_test_email' => trans('plugins/ecommerce::setting.abandoned_cart.test_email'),
            'cart_destroy_on_logout' => 'Destruir carrito al cerrar sesión',
        ];
    }

    public function messages(): array
    {
        return [
            'abandoned_cart_delay_hours.min' => trans('plugins/ecommerce::setting.abandoned_cart.validation.delay_hours_min'),
            'abandoned_cart_delay_hours.max' => trans('plugins/ecommerce::setting.abandoned_cart.validation.delay_hours_max'),
            'abandoned_cart_max_hours.min' => trans('plugins/ecommerce::setting.abandoned_cart.validation.max_hours_min'),
            'abandoned_cart_max_hours.max' => trans('plugins/ecommerce::setting.abandoned_cart.validation.max_hours_max'),
            'abandoned_cart_email_limit.min' => trans('plugins/ecommerce::setting.abandoned_cart.validation.email_limit_min'),
            'abandoned_cart_email_limit.max' => trans('plugins/ecommerce::setting.abandoned_cart.validation.email_limit_max'),
            'abandoned_cart_email_template.in' => trans('plugins/ecommerce::setting.abandoned_cart.validation.template_invalid'),
            'abandoned_cart_max_emails.min' => trans('plugins/ecommerce::setting.abandoned_cart.validation.max_emails_min'),
            'abandoned_cart_max_emails.max' => trans('plugins/ecommerce::setting.abandoned_cart.validation.max_emails_max'),
            'abandoned_cart_email_interval_hours.min' => trans('plugins/ecommerce::setting.abandoned_cart.validation.interval_min'),
            'abandoned_cart_email_interval_hours.max' => trans('plugins/ecommerce::setting.abandoned_cart.validation.interval_max'),
        ];
    }
}