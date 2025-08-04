<?php

namespace Botble\Newsletter\Http\Requests\Settings;

use Botble\Base\Rules\OnOffRule;
use Botble\Support\Http\Requests\Request;

class NewsletterSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'enable_newsletter_contacts_list_api' => [new OnOffRule()],
            'newsletter_mailjet_api_key' => ['nullable', 'string', 'min:32', 'max:40'],
            'newsletter_mailjet_api_secret' => ['nullable', 'string', 'min:32', 'max:40'],
            'newsletter_mailjet_list_id' => ['nullable', 'string', 'size:8'],
            'newsletter_popup_enable' => ['nullable', 'boolean'],
            'newsletter_popup_title' => ['nullable', 'string', 'max:120'],
            'newsletter_popup_subtitle' => ['nullable', 'string', 'max:150'],
            'newsletter_popup_description' => ['nullable', 'string', 'max:300'],
            'newsletter_popup_delay' => ['nullable', 'integer', 'min:0', 'max:60'],
        ];
    }
}
