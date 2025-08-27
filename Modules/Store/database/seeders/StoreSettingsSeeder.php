<?php

namespace Modules\Store\Database\Seeders;

use Modules\Store\Interfaces\CurrencyServiceInterface;
use Modules\Store\Interfaces\SettingServiceInterface;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    /**
     * @var SettingServiceInterface
     */
    protected $settingService;

    /**
     * @var CurrencyServiceInterface
     */
    protected $currencyService;

    /**
     * StoreSettingsSeeder constructor.
     *
     * @param SettingServiceInterface $settingService
     * @param CurrencyServiceInterface $currencyService
     */
    public function __construct(
        SettingServiceInterface $settingService,
        CurrencyServiceInterface $currencyService
    ) {
        $this->settingService = $settingService;
        $this->currencyService = $currencyService;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedCurrencies();
        $this->seedSettings();
    }

    /**
     * Seed initial currencies
     *
     * @return void
     */
    protected function seedCurrencies()
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'position' => 'before',
                'decimal_places' => 2,
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'is_active' => true,
                'is_default' => true,
                'exchange_rate' => 1.000000,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'position' => 'after',
                'decimal_places' => 2,
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'is_active' => true,
                'is_default' => false,
                'exchange_rate' => 0.920000,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'position' => 'before',
                'decimal_places' => 2,
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'is_active' => true,
                'is_default' => false,
                'exchange_rate' => 0.790000,
            ],
            [
                'code' => 'EGP',
                'name' => 'Egyptian Pound',
                'symbol' => 'EGP',
                'position' => 'before',
                'decimal_places' => 2,
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'is_active' => true,
                'is_default' => false,
                'exchange_rate' => 1.000000,
            ]
        ];

        foreach ($currencies as $currency) {
            $this->currencyService->createCurrency($currency);
        }
    }

    /**
     * Seed initial settings
     *
     * @return void
     */
    protected function seedSettings()
    {
        $settings = [
            // General Settings
            [
                'key' => 'store_name',
                'name' => 'Store Name',
                'description' => 'The name of your store',
                'type' => 'text',
                'group' => 'general',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'string', 'max:255'],
                'display_order' => 1,
            ],
            [
                'key' => 'store_description',
                'name' => 'Store Description',
                'description' => 'A brief description of your store',
                'type' => 'textarea',
                'group' => 'general',
                'is_public' => true,
                'is_required' => false,
                'validation_rules' => ['nullable', 'string', 'max:1000'],
                'display_order' => 2,
            ],
            [
                'key' => 'store_email',
                'name' => 'Store Email',
                'description' => 'The main contact email for your store',
                'type' => 'text',
                'group' => 'general',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'email', 'max:255'],
                'display_order' => 3,
            ],
            [
                'key' => 'store_phone',
                'name' => 'Store Phone',
                'description' => 'The main contact phone number for your store',
                'type' => 'text',
                'group' => 'general',
                'is_public' => true,
                'is_required' => false,
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'display_order' => 4,
            ],

            // Currency Settings
            [
                'key' => 'currency_code',
                'name' => 'Default Currency',
                'description' => 'The default currency for your store',
                'type' => 'select',
                'group' => 'currency',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'string', 'size:3'],
                'options' => [
                    'USD' => 'US Dollar',
                    'EUR' => 'Euro',
                    'GBP' => 'British Pound',
                    'EGP' => 'Egyptian Pound',
                ],
                'display_order' => 1,
            ],
            [
                'key' => 'currency_position',
                'name' => 'Currency Position',
                'description' => 'The position of the currency symbol',
                'type' => 'select',
                'group' => 'currency',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'string', 'in:before,after'],
                'options' => [
                    'before' => 'Before Amount',
                    'after' => 'After Amount',
                ],
                'display_order' => 2,
            ],
            [
                'key' => 'currency_decimal_places',
                'name' => 'Decimal Places',
                'description' => 'Number of decimal places to display',
                'type' => 'number',
                'group' => 'currency',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'integer', 'min:0', 'max:4'],
                'display_order' => 3,
            ],
            [
                'key' => 'currency_decimal_separator',
                'name' => 'Decimal Separator',
                'description' => 'Character used as decimal separator',
                'type' => 'text',
                'group' => 'currency',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'string', 'size:1'],
                'display_order' => 4,
            ],
            [
                'key' => 'currency_thousands_separator',
                'name' => 'Thousands Separator',
                'description' => 'Character used as thousands separator',
                'type' => 'text',
                'group' => 'currency',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'string', 'size:1'],
                'display_order' => 5,
            ],

            // Store Settings
            [
                'key' => 'store_status',
                'name' => 'Store Status',
                'description' => 'Whether the store is open or closed',
                'type' => 'boolean',
                'group' => 'store',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'boolean'],
                'display_order' => 1,
            ],
            [
                'key' => 'store_maintenance_message',
                'name' => 'Maintenance Message',
                'description' => 'Message to display when store is in maintenance mode',
                'type' => 'textarea',
                'group' => 'store',
                'is_public' => true,
                'is_required' => false,
                'validation_rules' => ['nullable', 'string', 'max:1000'],
                'display_order' => 2,
            ],
            [
                'key' => 'store_address',
                'name' => 'Store Address',
                'description' => 'The physical address of your store',
                'type' => 'textarea',
                'group' => 'store',
                'is_public' => true,
                'is_required' => false,
                'validation_rules' => ['nullable', 'string', 'max:1000'],
                'display_order' => 3,
            ],
            [
                'key' => 'store_tax_rate',
                'name' => 'Default Tax Rate',
                'description' => 'The default tax rate for products (percentage)',
                'type' => 'number',
                'group' => 'store',
                'is_public' => true,
                'is_required' => true,
                'validation_rules' => ['required', 'numeric', 'min:0', 'max:100'],
                'display_order' => 4,
            ],
        ];

        foreach ($settings as $setting) {
            $this->settingService->createSetting($setting);
        }

        // Set some default values
        $this->settingService->setSettingValue('store_name', 'My Store');
        $this->settingService->setSettingValue('store_description', 'Welcome to our online store!');
        $this->settingService->setSettingValue('store_email', 'store@example.com');
        $this->settingService->setSettingValue('currency_code', 'EGP');
        $this->settingService->setSettingValue('currency_position', 'before');
        $this->settingService->setSettingValue('currency_decimal_places', 2);
        $this->settingService->setSettingValue('currency_decimal_separator', '.');
        $this->settingService->setSettingValue('currency_thousands_separator', ',');
        $this->settingService->setSettingValue('store_status', true);
        $this->settingService->setSettingValue('store_tax_rate', 0);
    }
}
