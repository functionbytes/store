<?php

namespace Botble\Newsletter\Providers;

use Botble\Newsletter\Events\SubscribeNewsletterEvent;
use Botble\Newsletter\Events\UnsubscribeNewsletterEvent;
use Botble\Newsletter\Listeners\AddSubscriberToMailjetContactListListener;
use Botble\Newsletter\Listeners\RemoveSubscriberTomailjetContactListListener;
use Botble\Newsletter\Listeners\SendEmailNotificationAboutNewSubscriberListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SubscribeNewsletterEvent::class => [
            SendEmailNotificationAboutNewSubscriberListener::class,
            AddSubscriberToMailjetContactListListener::class,
        ],
        UnsubscribeNewsletterEvent::class => [
            RemoveSubscriberTomailjetContactListListener::class,
        ],
    ];
}
