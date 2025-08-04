<x-core::form.on-off.checkbox
    :label="trans('plugins/newsletter::newsletter.settings.enable_newsletter_contacts_list_api')"
    name="enable_newsletter_contacts_list_api"
    :checked="setting('enable_newsletter_contacts_list_api', false)"
    data-bb-toggle="collapse"
    data-bb-target="#newsletter-settings"
    class="mb-0"
    :wrapper="false"
/>

<x-core::form.fieldset
    data-bb-value="1"
    class="mt-3"
    id="newsletter-settings"
    @style(['display: none;' => !setting('enable_newsletter_contacts_list_api', false)])
>
    <x-core::form.text-input
        name="newsletter_mailjet_api_key"
        data-counter="120"
        :label="trans('plugins/newsletter::newsletter.settings.mailjet_api_key')"
        :value="setting('newsletter_mailjet_api_key')"
        :placeholder="trans('plugins/newsletter::newsletter.settings.mailjet_api_key')"
    />

    <x-core::form.text-input
        name="newsletter_mailjet_api_secret"
        data-counter="120"
        :label="trans('plugins/newsletter::newsletter.settings.mailjet_api_secret')"
        :value="setting('newsletter_mailjet_api_secret')"
        :placeholder="trans('plugins/newsletter::newsletter.settings.mailjet_api_secret')"
    />


        <x-core::form.text-input
            name="newsletter_mailjet_list_id"
            data-counter="120"
            :label="trans('plugins/newsletter::newsletter.settings.mailjet_list_id')"
            :value="setting('newsletter_mailjet_list_id')"
            :placeholder="trans('plugins/newsletter::newsletter.settings.mailjet_list_id')"
        />


</x-core::form.fieldset>
