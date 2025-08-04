<?php

namespace Botble\Ecommerce\Http\Controllers\Settings;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Forms\Settings\AbandonedCartSettingForm;
use Botble\Ecommerce\Http\Requests\Settings\AbandonedCartSettingRequest;
use Botble\Ecommerce\Jobs\SendAbandonedCartEmailJob;
use Botble\Ecommerce\Models\Order;
use Botble\Setting\Http\Controllers\Concerns\InteractsWithSettings;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbandonedCartSettingController extends BaseController
{
    use InteractsWithSettings;

    public function edit()
    {
        $this->pageTitle(trans('plugins/ecommerce::setting.abandoned_cart.name'));

        Assets::addStylesDirectly('vendor/core/plugins/ecommerce/css/abandoned-cart-settings.css')
            ->addScriptsDirectly('vendor/core/plugins/ecommerce/js/abandoned-cart-settings.js');

        return AbandonedCartSettingForm::create()->renderForm();
    }

    public function update(AbandonedCartSettingRequest $request)
    {
        $validated = $request->validated();
        
        // Manual save to ensure settings are stored correctly
        foreach ($validated as $key => $value) {
            $settingKey = 'ecommerce_' . $key;
            setting()->set($settingKey, $value);
        }
        setting()->save();
        
        return $this->httpResponse()->withUpdatedSuccessMessage();
    }

    public function sendTestEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'template' => 'required|string'
        ]);

        try {
            // Find a sample abandoned cart order or create a mock one
            $sampleOrder = Order::query()
                ->with(['user', 'address', 'products'])
                ->where('is_finished', 0)
                ->whereHas('products')
                ->first();

            if (!$sampleOrder) {
                return response()->json([
                    'error' => true,
                    'message' => trans('plugins/ecommerce::setting.abandoned_cart.no_sample_order'),
                ], 422);
            }

            $template = $request->input('template', 'abandoned_cart');
            $testEmail = $request->input('email');

            // Check if template exists
            $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);
            if (!$mailer->templateEnabled($template)) {
                return response()->json([
                    'error' => true,
                    'message' => trans('plugins/ecommerce::setting.abandoned_cart.template_not_found', ['template' => $template]),
                ], 422);
            }

            // Prepare order for email
            $sampleOrder->dont_show_order_info_in_product_list = true;

            // Set email variables
            $mailer = OrderHelper::setEmailVariables($sampleOrder, $mailer);

            // Send test email
            $mailer->sendUsingTemplate($template, $testEmail);

            return response()->json([
                'error' => false,
                'message' => trans('plugins/ecommerce::setting.abandoned_cart.test_email_sent', ['email' => $testEmail]),
            ]);

        } catch (Exception $exception) {
            return response()->json([
                'error' => true,
                'message' => trans('plugins/ecommerce::setting.abandoned_cart.test_email_failed', [
                    'error' => $exception->getMessage()
                ]),
            ], 500);
        }
    }

    public function sendBulkAbandonedCartEmails(Request $request): JsonResponse
    {
        $request->validate([
            'hours' => 'integer|min:1|max:72',
            'max_hours' => 'integer|min:24|max:720',
            'limit' => 'integer|min:1|max:500',
            'template' => 'string',
            'dry_run' => 'boolean'
        ]);

        try {
            $minHours = $request->input('hours', get_ecommerce_setting('abandoned_cart_delay_hours', 1));
            $maxHours = $request->input('max_hours', get_ecommerce_setting('abandoned_cart_max_hours', 168));
            $limit = $request->input('limit', get_ecommerce_setting('abandoned_cart_email_limit', 50));
            $template = $request->input('template', get_ecommerce_setting('abandoned_cart_email_template', 'abandoned_cart'));
            $dryRun = $request->boolean('dry_run', false);

            // Calculate time boundaries
            $minTime = now()->subHours($maxHours);
            $maxTime = now()->subHours($minHours);

            // Find abandoned orders
            $query = Order::query()
                ->with(['user', 'address', 'products'])
                ->where('is_finished', 0)
                ->whereBetween('created_at', [$minTime, $maxTime])
                ->whereHas('products')
                ->where(function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->whereNotNull('email');
                    })->orWhereHas('address', function ($q) {
                        $q->whereNotNull('email');
                    });
                });

            // Exclude orders that already received abandoned cart emails recently
            $query->whereDoesntHave('histories', function ($q) use ($minTime) {
                $q->where('action', 'abandoned_cart_email_sent')
                  ->where('created_at', '>=', $minTime);
            });

            $orders = $query->limit($limit)->get();

            if ($dryRun) {
                return response()->json([
                    'error' => false,
                    'message' => trans('plugins/ecommerce::setting.abandoned_cart.dry_run_result', [
                        'count' => $orders->count()
                    ]),
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->code,
                            'customer' => $order->user->name ?: $order->address->name,
                            'email' => $order->user->email ?: $order->address->email,
                            'items' => $order->products->count(),
                            'amount' => format_price($order->amount),
                            'created' => $order->created_at->format('Y-m-d H:i'),
                        ];
                    })
                ]);
            }

            // Queue emails for processing
            $emailsQueued = 0;
            foreach ($orders as $order) {
                SendAbandonedCartEmailJob::dispatch($order, $template);
                $emailsQueued++;
            }

            return response()->json([
                'error' => false,
                'message' => trans('plugins/ecommerce::setting.abandoned_cart.emails_queued', [
                    'count' => $emailsQueued
                ]),
            ]);

        } catch (Exception $exception) {
            return response()->json([
                'error' => true,
                'message' => trans('plugins/ecommerce::setting.abandoned_cart.bulk_send_failed', [
                    'error' => $exception->getMessage()
                ]),
            ], 500);
        }
    }
}