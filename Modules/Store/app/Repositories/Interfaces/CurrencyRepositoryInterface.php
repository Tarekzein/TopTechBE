<?php

namespace Modules\Store\App\Repositories\Interfaces;

use Modules\Store\App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

interface CurrencyRepositoryInterface
{
    /**
     * Get all active currencies
     *
     * @return Collection
     */
    public function getActive(): Collection;

    /**
     * Get the default currency
     *
     * @return Currency|null
     */
    public function getDefault(): ?Currency;

    /**
     * Set a currency as default
     *
     * @param Currency $currency
     * @return bool
     */
    public function setDefault(Currency $currency): bool;

    /**
     * Update exchange rates
     *
     * @param array $rates Array of currency codes and their rates
     * @return bool
     */
    public function updateExchangeRates(array $rates): bool;

    /**
     * Find a currency by its code
     *
     * @param string $code
     * @return Currency|null
     */
    public function findByCode(string $code): ?Currency;
} 