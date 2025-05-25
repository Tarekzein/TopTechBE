<?php

namespace  Modules\Store\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Modules\Store\App\Models\Currency;

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
