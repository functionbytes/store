<?php

namespace Functionbytes\Wompi\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;

class WompiServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public const MODULE_NAME = 'wompi';

    public function boot(): void
    {

        if (! is_plugin_active('payment')) {
            return;
        }

        $this->setNamespace('plugins/wompi')
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->publishAssets()
            ->loadRoutes();

        $this->app->booted(function () {
            $this->app->register(HookServiceProvider::class);
        });
    }

}
