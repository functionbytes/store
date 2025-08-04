<?php

namespace Botble\Ecommerce\Forms\Settings;

use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Ecommerce\Http\Requests\Settings\AbandonedCartSettingRequest;
use Botble\Setting\Forms\SettingForm;

class AbandonedCartSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/ecommerce::setting.abandoned_cart.name'))
            ->setSectionDescription(trans('plugins/ecommerce::setting.abandoned_cart.description'))
            ->setValidatorClass(AbandonedCartSettingRequest::class)
            
            // GENERAL SETTINGS
            ->add('abandoned_cart_enabled', OnOffCheckboxField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.enable'),
                'value' => get_ecommerce_setting('abandoned_cart_enabled', true),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.enable_help'),
                ],
            ])

            // EMAIL TEMPLATE CONFIGURATION - This is the key field for template selection
            ->add('abandoned_cart_email_template', SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/ecommerce::setting.abandoned_cart.email_template'))
                    ->helperText(trans('plugins/ecommerce::setting.abandoned_cart.email_template_help'))
                    ->choices([
                        'abandoned_cart' => trans('plugins/ecommerce::setting.abandoned_cart.template_modern'),
                        'order_recover' => trans('plugins/ecommerce::setting.abandoned_cart.template_classic'),
                    ])
                    ->selected(get_ecommerce_setting('abandoned_cart_email_template', 'abandoned_cart'))
            )

            ->add('abandoned_cart_email_subject', TextField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.email_subject'),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.email_subject_help'),
                ],
                'value' => get_ecommerce_setting('abandoned_cart_email_subject', 'Complete your purchase - Your cart is waiting!'),
            ])

            ->add('abandoned_cart_delay_hours', NumberField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.delay_hours'),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.delay_hours_help'),
                ],
                'value' => get_ecommerce_setting('abandoned_cart_delay_hours', 1),
                'attr' => [
                    'min' => 1,
                    'max' => 72,
                ],
            ])

            ->add('abandoned_cart_max_hours', NumberField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.max_hours'),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.max_hours_help'),
                ],
                'value' => get_ecommerce_setting('abandoned_cart_max_hours', 168),
                'attr' => [
                    'min' => 24,
                    'max' => 720,
                ],
            ])

            ->add('abandoned_cart_email_limit', NumberField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.email_limit'),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.email_limit_help'),
                ],
                'value' => get_ecommerce_setting('abandoned_cart_email_limit', 50),
                'attr' => [
                    'min' => 1,
                    'max' => 500,
                ],
            ])

            ->add('abandoned_cart_max_emails', NumberField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.max_emails'),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.max_emails_help'),
                ],
                'value' => get_ecommerce_setting('abandoned_cart_max_emails', 3),
                'attr' => [
                    'min' => 1,
                    'max' => 10,
                ],
            ])

            ->add('abandoned_cart_email_interval_hours', NumberField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.email_interval'),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.email_interval_help'),
                ],
                'value' => get_ecommerce_setting('abandoned_cart_email_interval_hours', 24),
                'attr' => [
                    'min' => 1,
                    'max' => 168,
                ],
            ])

            ->add('abandoned_cart_offer_free_shipping', OnOffCheckboxField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.offer_free_shipping'),
                'value' => get_ecommerce_setting('abandoned_cart_offer_free_shipping', true),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.offer_free_shipping_help'),
                ],
            ])

            ->add('abandoned_cart_exclude_categories', TextareaField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.exclude_categories'),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.exclude_categories_help'),
                ],
                'value' => get_ecommerce_setting('abandoned_cart_exclude_categories', ''),
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'electronics,books,digital-downloads',
                ],
            ])

            ->add('abandoned_cart_test_email', TextField::class, [
                'label' => trans('plugins/ecommerce::setting.abandoned_cart.test_email'),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.abandoned_cart.test_email_help'),
                ],
                'attr' => [
                    'placeholder' => 'your-email@example.com'
                ],
            ])

            ->add('send_test_email_button', HtmlField::class, [
                'html' => '<div class="mb-3">
                    <button type="button" class="btn btn-info btn-send-test-email">
                        <i class="fas fa-paper-plane me-2"></i>
                        <span class="btn-text">Enviar Email de Prueba</span>
                    </button>
                    <small class="form-hint d-block mt-2">
                        Se enviará un email de prueba usando la plantilla seleccionada arriba a la dirección especificada.
                    </small>
                </div>',
            ])

            ->add('cart_destroy_on_logout', OnOffCheckboxField::class, [
                'label' => 'Destruir carrito al cerrar sesión',
                'value' => get_ecommerce_setting('cart_destroy_on_logout', false),
                'help_block' => [
                    'text' => 'El carrito se destruirá cuando el cliente cierre la sesión.',
                ],
            ]);
    }
}