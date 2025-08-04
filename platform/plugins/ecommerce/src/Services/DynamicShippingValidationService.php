<?php

namespace Botble\Ecommerce\Services;

use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Shipping;
use Botble\Ecommerce\Models\ShippingRule;
use Botble\Ecommerce\Enums\ShippingRuleTypeEnum;
use Illuminate\Support\Arr;

class DynamicShippingValidationService
{
    protected HandleShippingFeeService $shippingFeeService;
    
    public function __construct(HandleShippingFeeService $shippingFeeService)
    {
        $this->shippingFeeService = $shippingFeeService;
    }
    
    /**
     * Validate and filter shipping methods based on order total and selected city
     */
    public function validateShippingMethods(array $data): array
    {
        $orderTotal = Arr::get($data, 'order_total', 0);
        $city = Arr::get($data, 'city');
        $state = Arr::get($data, 'state');
        $country = Arr::get($data, 'country');
        
        // Get all available shipping methods
        $shippingMethods = $this->shippingFeeService->execute($data);
        
        // Filter methods based on order total and location
        $validMethods = $this->filterMethodsByOrderTotal($shippingMethods, $orderTotal, $city, $state, $country);
        
        // Apply auto-selection logic
        $validMethods = $this->applyAutoSelectionLogic($validMethods);
        
        // Add priority sorting for free shipping
        $validMethods = $this->prioritizeFreeShipping($validMethods);
        
        return $validMethods;
    }
    
    /**
     * Filter shipping methods based on order total
     */
    protected function filterMethodsByOrderTotal(array $methods, float $orderTotal, ?string $city = null, ?string $state = null, ?string $country = null): array
    {
        $filtered = [];
        
        foreach ($methods as $methodKey => $methodOptions) {
            foreach ($methodOptions as $optionKey => $option) {
                $ruleType = Arr::get($option, 'rule_type');
                $isValid = true;
                
                // For price-based rules, validate the order total is within range
                if ($ruleType === ShippingRuleTypeEnum::BASED_ON_PRICE) {
                    $rule = ShippingRule::find($optionKey);
                    if ($rule) {
                        $isValid = $this->validatePriceRange($rule, $orderTotal);
                    }
                }
                
                // For location-based rules, validate the city/state
                if ($ruleType === ShippingRuleTypeEnum::BASED_ON_LOCATION && $city) {
                    $rule = ShippingRule::find($optionKey);
                    if ($rule) {
                        $isValid = $this->validateLocationRule($rule, $city, $state);
                    }
                }
                
                if ($isValid) {
                    if (!isset($filtered[$methodKey])) {
                        $filtered[$methodKey] = [];
                    }
                    $filtered[$methodKey][$optionKey] = $option;
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * Validate if order total is within the price range of the rule
     */
    protected function validatePriceRange(ShippingRule $rule, float $orderTotal): bool
    {
        if ($rule->type !== ShippingRuleTypeEnum::BASED_ON_PRICE) {
            return true;
        }
        
        $withinMinimum = $orderTotal >= $rule->from;
        $withinMaximum = is_null($rule->to) || $orderTotal <= $rule->to;
        
        return $withinMinimum && $withinMaximum;
    }
    
    /**
     * Validate if the location rule applies to the selected city/state
     */
    protected function validateLocationRule(ShippingRule $rule, string $city, ?string $state = null): bool
    {
        if ($rule->type !== ShippingRuleTypeEnum::BASED_ON_LOCATION) {
            return true;
        }
        
        // Check if rule has specific items for this location
        $hasLocationItem = $rule->items()
            ->where('is_enabled', true)
            ->where(function ($query) use ($city, $state) {
                $query->where('city', $city);
                if ($state) {
                    $query->where('state', $state);
                }
            })
            ->exists();
            
        // If no specific location item, check if rule applies to all locations in this state
        if (!$hasLocationItem && $state) {
            $hasStateItem = $rule->items()
                ->where('is_enabled', true)
                ->where('state', $state)
                ->whereIn('city', ['', null, 0])
                ->exists();
                
            return $hasStateItem;
        }
        
        return $hasLocationItem;
    }
    
    /**
     * Apply auto-selection logic when only one method is available
     */
    protected function applyAutoSelectionLogic(array $methods): array
    {
        $totalMethods = 0;
        $hasFreeShipping = false;
        
        // Count methods and check for free shipping
        foreach ($methods as $methodOptions) {
            $totalMethods += count($methodOptions);
            foreach ($methodOptions as $option) {
                if ((float) $option['price'] === 0.0) {
                    $hasFreeShipping = true;
                }
            }
        }
        
        // If free shipping is available, mark it for auto-selection and skip delivery process
        if ($hasFreeShipping) {
            foreach ($methods as $methodKey => $methodOptions) {
                foreach ($methodOptions as $optionKey => $option) {
                    if ((float) $option['price'] === 0.0) {
                        $methods[$methodKey][$optionKey]['auto_select'] = true;
                        $methods[$methodKey][$optionKey]['skip_delivery_process'] = true;
                        $methods[$methodKey][$optionKey]['description'] = $this->getAutoSelectedMethodDescription($option);
                    }
                }
            }
        }
        // If only one method available (and not free), mark it for auto-selection
        elseif ($totalMethods === 1) {
            foreach ($methods as $methodKey => $methodOptions) {
                foreach ($methodOptions as $optionKey => $option) {
                    $methods[$methodKey][$optionKey]['auto_select'] = true;
                    $methods[$methodKey][$optionKey]['description'] = $this->getAutoSelectedMethodDescription($option);
                }
            }
        }
        
        return $methods;
    }
    
    /**
     * Prioritize free shipping methods
     */
    protected function prioritizeFreeShipping(array $methods): array
    {
        $freeShippingMethods = [];
        $paidShippingMethods = [];
        
        foreach ($methods as $methodKey => $methodOptions) {
            foreach ($methodOptions as $optionKey => $option) {
                if ((float) $option['price'] === 0.0) {
                    $freeShippingMethods[$methodKey][$optionKey] = $option;
                    $freeShippingMethods[$methodKey][$optionKey]['is_free'] = true;
                } else {
                    $paidShippingMethods[$methodKey][$optionKey] = $option;
                }
            }
        }
        
        // Return free shipping first, then paid options
        return array_merge($freeShippingMethods, $paidShippingMethods);
    }
    
    /**
     * Get description for auto-selected method
     */
    protected function getAutoSelectedMethodDescription(array $option): string
    {
        $price = (float) $option['price'];
        
        if ($price === 0.0) {
            return __('Free shipping automatically applied - No delivery selection needed!');
        }
        
        $citySpecific = Arr::get($option, 'city_specific', false);
        if ($citySpecific && Arr::get($option, 'city_name')) {
            return __('Automatically selected - Special rate for :city', ['city' => $option['city_name']]);
        }
        
        return __('Automatically selected - Only shipping method available');
    }
    
    /**
     * Get shipping methods summary for order total
     */
    public function getShippingMethodsSummary(float $orderTotal, ?string $city = null): array
    {
        $freeShippingThreshold = get_ecommerce_setting('free_shipping_threshold', 200000);
        $hasFreeShipping = $orderTotal >= $freeShippingThreshold;
        
        $summary = [
            'has_free_shipping' => $hasFreeShipping,
            'free_shipping_threshold' => $freeShippingThreshold,
            'amount_to_free_shipping' => max(0, $freeShippingThreshold - $orderTotal),
            'city_based_rates_available' => $this->hasCityBasedRates($city),
            'skip_delivery_selection' => $hasFreeShipping, // Skip delivery process when free
        ];
        
        return $summary;
    }
    
    /**
     * Check if city has specific shipping rates
     */
    protected function hasCityBasedRates(?string $city = null): bool
    {
        if (!$city || !EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()) {
            return false;
        }
        
        return ShippingRule::whereHas('items', function ($query) use ($city) {
            $query->where('city', $city)->where('is_enabled', true);
        })->exists();
    }
}