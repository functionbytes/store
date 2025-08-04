<?php

namespace Botble\Newsletter\Forms;

use Botble\Base\Facades\BaseHelper;
use Botble\Newsletter\Facades\Newsletter as NewsletterFacade;
use Botble\Newsletter\Http\Requests\Settings\NewsletterSettingRequest;
use Botble\Setting\Forms\SettingForm;
use Exception;
use Illuminate\Support\Arr;

class NewsletterSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $mailjetContactList = [];

        if (setting('newsletter_mailjet_api_key')) {
            try {
                $contacts = collect(NewsletterFacade::driver('mailjet')->contacts());

                if (! setting('newsletter_mailjet_list_id')) {
                    setting()->set(['newsletter_mailjet_list_id' => Arr::get($contacts, 'id')])->save();
                }

                $mailjetContactList = $contacts->pluck('name', 'id')->all();
            } catch (Exception $exception) {
                BaseHelper::logError($exception);
            }
        }

        $this
            ->setSectionTitle(trans('plugins/newsletter::newsletter.settings.title'))
            ->setSectionDescription(trans('plugins/newsletter::newsletter.settings.description'))
            ->setValidatorClass(NewsletterSettingRequest::class)
            ->add('newsletter_popup_enable', 'html', [
                'html' => '<div class="form-group mb-3">
                    <label class="form-check">
                        <input type="checkbox" 
                               name="newsletter_popup_enable" 
                               class="form-check-input mb-0" 
                               data-bb-toggle="collapse" 
                               data-bb-target=".newsletter-popup-settings" 
                               value="1" 
                               ' . (setting('newsletter_popup_enable', true) ? 'checked=""' : '') . '>
                        <span class="form-check-label">
                            ' . trans('plugins/newsletter::newsletter.settings.enable_popup') . '
                        </span>
                    </label>
                </div>',
            ])
            ->add('open_popup_settings_wrapper', 'html', [
                'html' => '<div class="newsletter-popup-settings  mb-4 border-bottom collapse' . (setting('newsletter_popup_enable', true) ? ' show' : '') . '">',
            ])
            ->add('newsletter_popup_title', 'text', [
                'label' => trans('plugins/newsletter::newsletter.settings.popup_title'),
                'value' => setting('newsletter_popup_title', trans('plugins/newsletter::newsletter.popup.title')),
                'attr' => [
                    'data-counter' => 120,
                ],
            ])
            ->add('newsletter_popup_subtitle', 'text', [
                'label' => trans('plugins/newsletter::newsletter.settings.popup_subtitle'),
                'value' => setting('newsletter_popup_subtitle', trans('plugins/newsletter::newsletter.popup.subtitle')),
                'attr' => [
                    'data-counter' => 150,
                ],
            ])
            ->add('newsletter_popup_description', 'textarea', [
                'label' => trans('plugins/newsletter::newsletter.settings.popup_description'),
                'value' => setting('newsletter_popup_description', trans('plugins/newsletter::newsletter.popup.description')),
                'attr' => [
                    'rows' => 3,
                    'data-counter' => 300,
                ],
            ])
            ->add('newsletter_popup_delay', 'number', [
                'label' => trans('plugins/newsletter::newsletter.settings.popup_delay'),
                'value' => setting('newsletter_popup_delay', 3),
                'attr' => [
                    'min' => 0,
                    'max' => 60,
                ],
                'help_block' => [
                    'text' => trans('plugins/newsletter::newsletter.settings.popup_delay_help'),
                ],
            ])
            ->add('close_popup_settings_wrapper', 'html', [
                'html' => '</div>',
            ])
            ->add('newsletter_contacts_list_api_fields', 'html', [
                'html' => view('plugins/newsletter::partials.newsletter-contacts-list-api-fields', compact('mailjetContactList')),
                'wrapper' => [
                    'class' => 'mb-0',
                ],
            ]);
    }
}
