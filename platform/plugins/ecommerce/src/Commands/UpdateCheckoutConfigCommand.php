<?php

namespace Botble\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('ecommerce:update-checkout-config', 'Update checkout configuration for location plugin')]
class UpdateCheckoutConfigCommand extends Command
{
    protected $signature = 'ecommerce:update-checkout-config';

    protected $description = 'Update checkout configuration to enable location plugin and city select dropdown';

    public function handle(): int
    {
        $this->info('Updating checkout configuration...');

        // Configuraciones necesarias
        $settings = [
            'load_countries_states_cities_from_location_plugin' => 1,
            'use_city_field_as_field_text' => 0,
            'default_country_at_checkout_page' => 'CO',
            'filter_cities_by_state' => 1,
            'default_state_for_city_filter' => '28',
            'selected_cities_for_checkout' => '[]'
        ];

        foreach ($settings as $key => $value) {
            $prefixedKey = 'ecommerce_' . $key;
            
            // Buscar si existe la configuración
            $existing = DB::table('settings')->where('key', $prefixedKey)->first();
            
            if ($existing) {
                DB::table('settings')
                    ->where('key', $prefixedKey)
                    ->update(['value' => $value]);
                $this->line("✓ Updated: $prefixedKey = $value");
            } else {
                DB::table('settings')->insert([
                    'key' => $prefixedKey,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $this->line("✓ Created: $prefixedKey = $value");
            }
        }

        // Limpiar cache
        $this->call('cache:clear');
        $this->call('config:clear');

        $this->newLine();
        $this->info('🎉 Checkout configuration updated successfully!');
        $this->info('Now the city field should appear as a select dropdown instead of text input.');
        $this->info('Visit https://mercosan.test/orden/ to verify the changes.');

        return self::SUCCESS;
    }
}