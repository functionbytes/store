<?php

namespace Botble\Ecommerce\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Services\HandleCheckoutOrderData;
use Botble\Ecommerce\Services\HandleTaxService;
use Botble\Ecommerce\Services\DynamicShippingValidationService;
use Botble\Ecommerce\Services\FreeShippingAutoHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class PublicUpdateCheckoutController extends BaseController
{
    public function __invoke(
        Request $request, 
        HandleCheckoutOrderData $handleCheckoutOrderData,
        DynamicShippingValidationService $dynamicShippingValidation,
        FreeShippingAutoHandler $freeShippingHandler
    )
    {
        $sessionCheckoutData = OrderHelper::getOrderSessionData(
            $token = OrderHelper::getOrderSessionToken()
        );

        /**
         * @var Collection $products
         */
        $products = Cart::instance('cart')->products();

        $checkoutOrderData = $handleCheckoutOrderData->execute(
            $request,
            $products,
            $token,
            $sessionCheckoutData
        );

        app(HandleTaxService::class)->execute($products, $sessionCheckoutData);
        
        // Validate and enhance shipping methods based on order total and location
        $shippingData = [
            'order_total' => $checkoutOrderData->orderAmount,
            'city' => data_get($sessionCheckoutData, 'city'),
            'state' => data_get($sessionCheckoutData, 'state'),
            'country' => data_get($sessionCheckoutData, 'country'),
            'weight' => $products->sum('weight'),
        ];
        
        // Check if free shipping should be auto-applied
        $shouldAutoApplyFreeShipping = $freeShippingHandler->shouldAutoApplyFreeShipping($shippingData);
        
        if ($shouldAutoApplyFreeShipping) {
            // Auto-apply free shipping and skip selection process
            $validatedShipping = $freeShippingHandler->createAutoFreeShippingMethod($shippingData);
            $skipShippingSelection = true;
        } else {
            // Normal shipping validation
            $validatedShipping = $dynamicShippingValidation->validateShippingMethods($shippingData);
            $skipShippingSelection = false;
        }
        
        $shippingSummary = $dynamicShippingValidation->getShippingMethodsSummary(
            $checkoutOrderData->orderAmount,
            data_get($sessionCheckoutData, 'city')
        );
        
        // Override summary if free shipping is auto-applied
        if ($shouldAutoApplyFreeShipping) {
            $shippingSummary['skip_delivery_selection'] = true;
            $shippingSummary['auto_applied_free_shipping'] = true;
        }

        add_filter('payment_order_total_amount', function () use ($checkoutOrderData) {
            return $checkoutOrderData->orderAmount - $checkoutOrderData->paymentFee;
        }, 120);

        return $this
            ->httpResponse()
            ->setData([
                'amount' => view('plugins/ecommerce::orders.partials.amount', [
                    'products' => $products,
                    'rawTotal' => $checkoutOrderData->rawTotal,
                    'orderAmount' => $checkoutOrderData->orderAmount,
                    'shipping' => $checkoutOrderData->shipping,
                    'sessionCheckoutData' => $sessionCheckoutData,
                    'shippingAmount' => $checkoutOrderData->shippingAmount,
                    'promotionDiscountAmount' => $checkoutOrderData->promotionDiscountAmount,
                    'couponDiscountAmount' => $checkoutOrderData->couponDiscountAmount,
                    'paymentFee' => $checkoutOrderData->paymentFee,
                ])->render(),
                'payment_methods' => view('plugins/ecommerce::orders.partials.payment-methods', [
                    'orderAmount' => $checkoutOrderData->orderAmount,
                ])->render(),
                'shipping_methods' => view('plugins/ecommerce::orders.partials.shipping-methods', [
                    'shipping' => $validatedShipping ?: $checkoutOrderData->shipping,
                    'defaultShippingOption' => $shouldAutoApplyFreeShipping ? 'free_shipping_auto' : $checkoutOrderData->defaultShippingOption,
                    'defaultShippingMethod' => $shouldAutoApplyFreeShipping ? 'default' : $checkoutOrderData->defaultShippingMethod,
                    'shippingSummary' => $shippingSummary,
                    'orderTotal' => $checkoutOrderData->orderAmount,
                    'skipShippingSelection' => $skipShippingSelection ?? false,
                ])->render(),
            ]);
    }
}
