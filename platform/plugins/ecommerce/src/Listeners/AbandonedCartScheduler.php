<?php

namespace Botble\Ecommerce\Listeners;

use Botble\Ecommerce\Commands\SendAbandonedCartsEmailCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;

class AbandonedCartScheduler
{
    public function handle(Schedule $schedule): void
    {
        // Only schedule if abandoned cart emails are enabled
        if (!get_ecommerce_setting('abandoned_cart_enabled', true)) {
            return;
        }

        $delayHours = get_ecommerce_setting('abandoned_cart_delay_hours', 1);
        $maxHours = get_ecommerce_setting('abandoned_cart_max_hours', 168);
        $limit = get_ecommerce_setting('abandoned_cart_email_limit', 50);
        $template = get_ecommerce_setting('abandoned_cart_email_template', 'abandoned_cart');

        // Schedule abandoned cart emails to run every hour
        $schedule->command(SendAbandonedCartsEmailCommand::class, [
            '--hours' => $delayHours,
            '--max-hours' => $maxHours,
            '--limit' => $limit,
            '--template' => $template
        ])
        ->hourly()
        ->name('abandoned-cart-emails')
        ->description('Send abandoned cart recovery emails')
        ->withoutOverlapping(60) // Prevent overlapping for up to 60 minutes
        ->onOneServer() // Only run on one server in multi-server setup
        ->runInBackground();

        // Also schedule a daily summary report (optional)
        $schedule->call(function () {
            // This could be expanded to send daily reports about abandoned cart recovery
            \Log::info('Abandoned cart email scheduler ran successfully');
        })
        ->daily()
        ->at('09:00')
        ->name('abandoned-cart-daily-report')
        ->description('Log daily abandoned cart email activity');
    }

    public function subscribe(Dispatcher $events): void
    {
        // Subscribe to Laravel's scheduling events if needed
        $events->listen(
            'Illuminate\Console\Events\ScheduledTaskStarting',
            function ($event) {
                if ($event->task->description === 'Send abandoned cart recovery emails') {
                    \Log::info('Starting abandoned cart email job');
                }
            }
        );

        $events->listen(
            'Illuminate\Console\Events\ScheduledTaskFinished',
            function ($event) {
                if ($event->task->description === 'Send abandoned cart recovery emails') {
                    \Log::info('Finished abandoned cart email job', [
                        'runtime' => $event->runtime
                    ]);
                }
            }
        );
    }
}