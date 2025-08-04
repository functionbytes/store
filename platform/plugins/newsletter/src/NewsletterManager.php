<?php

namespace Botble\Newsletter;

use Botble\Base\Facades\AdminHelper;
use Botble\Media\Facades\RvMedia;
use Botble\Newsletter\Contracts\Factory;
use Botble\Newsletter\Drivers\Mailjet;
use Botble\Newsletter\Drivers\SendGrid;
use Botble\Theme\Events\RenderingThemeOptionSettings;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Facades\ThemeOption;
use Botble\Theme\ThemeOption\Fields\MediaImageField;
use Botble\Theme\ThemeOption\Fields\MultiCheckListField;
use Botble\Theme\ThemeOption\Fields\NumberField;
use Botble\Theme\ThemeOption\Fields\TextareaField;
use Botble\Theme\ThemeOption\Fields\TextField;
use Botble\Theme\ThemeOption\Fields\ToggleField;
use Botble\Theme\ThemeOption\ThemeOptionSection;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class NewsletterManager extends Manager implements Factory
{


    protected function createmailjetDriver(): Mailjet
    {
        return new Mailjet(
            setting('newsletter_mailjet_api_key'),
            setting('newsletter_mailjet_list_id')
        );
    }

    public function getDefaultDriver(): string
    {
        throw new InvalidArgumentException('No email marketing provider was specified.');
    }

    public function registerNewsletterPopup(bool $keepHtmlDomOnClose = false): void
    {
        app('events')->listen(RenderingThemeOptionSettings::class, function (): void {
            ThemeOption::setSection(
                ThemeOptionSection::make('opt-text-subsection-newsletter-popup')
                    ->title(__('Newsletter Popup'))
                    ->icon('ti ti-mail-opened')
                    ->fields([
                        ToggleField::make()
                            ->name('newsletter_popup_enable')
                            ->label(trans('plugins/newsletter::newsletter.settings.enable_popup'))
                            ->defaultValue(true),
                        MediaImageField::make()
                            ->name('newsletter_popup_image')
                            ->label(__('Popup Image')),
                        TextField::make()
                            ->name('newsletter_popup_title')
                            ->label(__('Popup Title'))
                            ->defaultValue('¡Suscríbete a nuestro Newsletter!'), // Título por defecto
                        TextField::make()
                            ->name('newsletter_popup_subtitle')
                            ->label(__('Popup Subtitle'))
                            ->defaultValue('Recibe las últimas noticias y ofertas especiales'), // Subtítulo por defecto
                        TextareaField::make()
                            ->name('newsletter_popup_description')
                            ->label(__('Popup Description'))
                            ->defaultValue('Mantente informado con nuestras últimas actualizaciones, promociones exclusivas y contenido de valor directamente en tu bandeja de entrada.'), // Descripción por defecto
                        NumberField::make()
                            ->name('newsletter_popup_delay')
                            ->label(__('Popup Delay (seconds)'))
                            ->defaultValue(0)
                            ->helperText(
                                __(
                                    'Set the delay time to show the popup after the page is loaded. Set 0 to show the popup immediately.'
                                )
                            )
                            ->attributes([
                                'min' => 0,
                            ]),
                        MultiCheckListField::make()
                            ->name('newsletter_popup_display_pages')
                            ->label(__('Display on pages'))
                            ->inline()
                            ->defaultValue(['public.index', 'all']) // Mostrar en homepage y todas las páginas
                            ->options(
                                apply_filters('newsletter_popup_display_pages', [
                                    'public.index' => __('Homepage'),
                                    'all' => __('All Pages'),
                                ])
                            ),
                    ])
            );
        });

        app('events')->listen(RouteMatched::class, function () use ($keepHtmlDomOnClose): void {
            if (! $this->isNewsletterPopupEnabled($keepHtmlDomOnClose)) {
                return;
            }

            Theme::asset()
                ->container('footer')
                ->add(
                    'newsletter',
                    asset('vendor/core/plugins/newsletter/js/newsletter.js'),
                    ['jquery'],
                    version: '1.2.8'
                );

            add_filter('theme_front_meta', function (?string $html): string {
                $image = theme_option('newsletter_popup_image');

                if (! $image) {
                    return $html;
                }
                // Quitar este dd()
                return $html . '<link rel="preload" as="image" href="' . RvMedia::getImageUrl($image) . '" />';
            });

            add_filter(THEME_FRONT_BODY, function (?string $html): string {
                return $html . view('plugins/newsletter::partials.newsletter-popup');
            });
        });
    }

        protected function isNewsletterPopupEnabled(bool $keepHtmlDomOnClose = false): bool
    {

        $pluginActive = is_plugin_active('newsletter');
        $popupEnabled = setting('newsletter_popup_enable', true);
        $noCookie = !isset($_COOKIE['newsletter_popup']);
        $notAdmin = !AdminHelper::isInAdmin();

        $isEnabled = $pluginActive && $popupEnabled && ($keepHtmlDomOnClose || $noCookie) && $notAdmin;

        if (! $isEnabled) {
            return false;
        }

        $displayPages = theme_option('newsletter_popup_display_pages');

        if ($displayPages) {
            $displayPages = json_decode($displayPages, true);
        } else {
            $displayPages = ['public.index'];
        }

        if (
            ! in_array('all', $displayPages)
            && ! in_array(Route::currentRouteName(), $displayPages)
        ) {
            return false;
        }

        $ignoredBots = [
            'googlebot', // Googlebot
            'bingbot', // Microsoft Bingbot
            'slurp', // Yahoo! Slurp
            'ia_archiver', // Alexa
            'Chrome-Lighthouse', // Google Lighthouse
        ];

        if (in_array(strtolower(request()->userAgent()), $ignoredBots)) {
            return false;
        }

        return true;
    }

    public function setDefaultNewsletterPopupSettings(): void
    {
        $defaults = [
            'newsletter_popup_enable' => true,
            'newsletter_popup_title' => '¡Suscríbete a nuestro Newsletter!',
            'newsletter_popup_subtitle' => 'Recibe las últimas noticias y ofertas especiales',
            'newsletter_popup_description' => 'Mantente informado con nuestras últimas actualizaciones, promociones exclusivas y contenido de valor directamente en tu bandeja de entrada.',
            'newsletter_popup_delay' => 0,
            'newsletter_popup_display_pages' => json_encode(['public.index', 'all'])
        ];

        foreach ($defaults as $key => $value) {
            if (!theme_option($key)) {
                theme_option()->set($key, $value);
            }
        }
    }
}
