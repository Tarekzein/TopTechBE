<?php

namespace Modules\Store\App\Repositories;

use Modules\Store\App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Modules\Store\App\Repositories\Interfaces\CurrencyRepositoryInterface;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    /**
     * @var Currency
     */
    protected $model;

    /**
     * CurrencyRepository constructor.
     *
     * @param Currency $model
     */
    public function __construct(Currency $model)
    {
        $this->model = $model;
    }

    /**
     * Get all active currencies
     *
     * @return Collection
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get the default currency
     *
     * @return Currency|null
     */
    public function getDefault(): ?Currency
    {
        return $this->model->where('is_default', true)->first();
    }

    /**
     * Set a currency as default
     *
     * @param Currency $currency
     * @return bool
     */
    public function setDefault(Currency $currency): bool
    {
        // Start transaction
        return \DB::transaction(function () use ($currency) {
            // Remove default from all currencies
            $this->model->where('is_default', true)->update(['is_default' => false]);
            
            // Set the new default
            return $currency->update(['is_default' => true]);
        });
    }

    /**
     * Update exchange rates
     *
     * @param array $rates Array of currency codes and their rates
     * @return bool
     */
    public function updateExchangeRates(array $rates): bool
    {
        $defaultCurrency = $this->getDefault();
        if (!$defaultCurrency) {
            return false;
        }

        $defaultRate = $rates[$defaultCurrency->code] ?? 1.0;
        
        foreach ($rates as $code => $rate) {
            if ($code === $defaultCurrency->code) {
                continue;
            }

            $currency = $this->findByCode($code);
            if ($currency) {
                // Convert rate relative to default currency
                $relativeRate = $rate / $defaultRate;
                $currency->update(['exchange_rate' => $relativeRate]);
            }
        }

        return true;
    }

    /**
     * Find a currency by its code
     *
     * @param string $code
     * @return Currency|null
     */
    public function findByCode(string $code): ?Currency
    {
        return $this->model->where('code', $code)->first();
    }
} 