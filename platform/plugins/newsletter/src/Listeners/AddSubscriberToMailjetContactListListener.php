<?php

namespace Botble\Newsletter\Listeners;

use Botble\Newsletter\Events\SubscribeNewsletterEvent;
use Botble\Newsletter\Facades\Newsletter;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddSubscriberToMailjetContactListListener implements ShouldQueue
{
    public function handle(SubscribeNewsletterEvent $event): void
    {
        if (! setting('enable_newsletter_contacts_list_api')) {
            return;
        }

        $mailjetApiKey = setting('newsletter_mailjet_api_key');
        $mailjetListId = setting('newsletter_mailjet_list_id');
        $mailjetApiSecret = setting('newsletter_mailjet_api_secret');

        if (! $mailjetApiKey || ! $mailjetListId || ! $mailjetApiSecret) {
            return;
        }

        Newsletter::driver('mailjet')->subscribe($event->newsletter->email);
    }
}
