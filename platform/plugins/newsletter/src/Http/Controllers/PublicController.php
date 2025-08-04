<?php

namespace Botble\Newsletter\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\EmailFieldOption;
use Botble\Base\Forms\Fields\CheckboxField;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Newsletter\Enums\NewsletterStatusEnum;
use Botble\Newsletter\Events\SubscribeNewsletterEvent;
use Botble\Newsletter\Events\UnsubscribeNewsletterEvent;
use Botble\Newsletter\Forms\Fronts\NewsletterForm;
use Botble\Newsletter\Http\Requests\NewsletterRequest;
use Botble\Newsletter\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicController extends BaseController
{
    public function postSubscribe(NewsletterRequest $request)
    {
        do_action('form_extra_fields_validate', $request, NewsletterForm::class);

        $newsletterForm = NewsletterForm::create();
        $newsletterForm->setRequest($request);

        $newsletterForm->onlyValidatedData()->saving(function (NewsletterForm $form): void {
            /**
             * @var NewsletterRequest $request
             */
            $request = $form->getRequest();

            /**
             * @var Newsletter $newsletter
             */
            $newsletter = $form->getModel()->newQuery()->firstOrNew([
                'email' => $request->input('email'),
            ], [
                ...$form->getRequestData(),
                'status' => NewsletterStatusEnum::SUBSCRIBED,
            ]);

            $newsletter->save();

            // Almacenar el email en la sesión para futuras verificaciones
            $request->session()->put('user_email', $newsletter->email);

            SubscribeNewsletterEvent::dispatch($newsletter);
        });

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/newsletter::newsletter.popup.subscribe_success'));
    }

    public function getUnsubscribe(int|string $id, Request $request)
    {
        abort_unless(URL::hasValidSignature($request), 404);

        /**
         * @var Newsletter $newsletter
         */
        $newsletter = Newsletter::query()
            ->where([
                'id' => $id,
                'status' => NewsletterStatusEnum::SUBSCRIBED,
            ])
            ->first();

        if ($newsletter) {
            $newsletter->update(['status' => NewsletterStatusEnum::UNSUBSCRIBED]);

            UnsubscribeNewsletterEvent::dispatch($newsletter);

            return $this
                ->httpResponse()
                ->setNextUrl(BaseHelper::getHomepageUrl())
                ->setMessage(__('Unsubscribe to newsletter successfully'));
        }

        return $this
            ->httpResponse()
            ->setError()
            ->setNextUrl(BaseHelper::getHomepageUrl())
            ->setMessage(__('Your email does not exist in the system or you have unsubscribed already!'));
    }

    public function ajaxLoadPopup(Request $request)
    {
        // Verificar si el usuario ya está suscrito por IP o email en sesión
        $userEmail = $request->session()->get('user_email');
        $userIp = $request->ip();
        
        // Si hay un email en sesión, verificar si ya está suscrito
        if ($userEmail) {
            $existingSubscription = Newsletter::query()
                ->where('email', $userEmail)
                ->where('status', NewsletterStatusEnum::SUBSCRIBED)
                ->exists();
                
            if ($existingSubscription) {
                return $this
                    ->httpResponse()
                    ->setData([
                        'html' => '',
                        'show_popup' => false,
                        'message' => trans('plugins/newsletter::newsletter.popup.already_subscribed')
                    ]);
            }
        }

        $newsletterForm = NewsletterForm::create()
            ->remove(['wrapper_before', 'wrapper_after', 'email'])
            ->addBefore(
                'submit',
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/newsletter::newsletter.popup.email_label'))
                    ->maxLength(-1)
                    ->placeholder(trans('plugins/newsletter::newsletter.popup.email_placeholder'))
                    ->required()
            )
            ->addAfter(
                'submit',
                'dont_show_again',
                CheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/newsletter::newsletter.popup.dont_show_again'))
                    ->value(false)
            );

        return $this
            ->httpResponse()
            ->setData([
                'html' => view('plugins/newsletter::partials.popup', compact('newsletterForm'))->render(),
                'show_popup' => true
            ]);
    }

    public function checkSubscriptionStatus(Request $request)
    {
        $email = $request->input('email');
        
        if (!$email) {
            return $this
                ->httpResponse()
                ->setData(['subscribed' => false]);
        }

        $isSubscribed = Newsletter::query()
            ->where('email', $email)
            ->where('status', NewsletterStatusEnum::SUBSCRIBED)
            ->exists();

        return $this
            ->httpResponse()
            ->setData(['subscribed' => $isSubscribed]);
    }
}
