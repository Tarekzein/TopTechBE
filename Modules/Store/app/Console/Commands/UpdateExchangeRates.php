<?php

namespace Modules\Store\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Store\Interfaces\CurrencyServiceInterface;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:update-exchange-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update currency exchange rates from external service';

    /**
     * @var CurrencyServiceInterface
     */
    protected $currencyService;

    /**
     * Create a new command instance.
     *
     * @param CurrencyServiceInterface $currencyService
     * @return void
     */
    public function __construct(CurrencyServiceInterface $currencyService)
    {
        parent::__construct();
        $this->currencyService = $currencyService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Updating exchange rates...');

        try {
            $result = $this->currencyService->updateExchangeRates();

            if ($result) {
                $this->info('Exchange rates updated successfully.');
                return 0;
            }

            $this->error('Failed to update exchange rates.');
            return 1;

        } catch (\Exception $e) {
            $this->error('Error updating exchange rates: ' . $e->getMessage());
            return 1;
        }
    }
}
