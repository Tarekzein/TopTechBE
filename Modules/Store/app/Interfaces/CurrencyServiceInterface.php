<?php

namespace  Modules\Store\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Modules\Store\App\Models\Currency;

interface CurrencyServiceInterface
{
    /**
     * Get all active currencies
     *
     * @return Collection
     */
    public function getActiveCurrencies(): Collection;

    /**
     * Get the default currency
     *
     * @return Currency|null
     */
    public function getDefaultCurrency(): ?Currency;

    /**
     * Set a currency as default
     *
     * @param string $code
     * @return bool
     */
    public function setDefaultCurrency(string $code): bool;

    /**
     * Convert amount from one currency to another
     *
     * @param float $amount
     * @param string $fromCode
     * @param string $toCode
     * @return float|null
     */
    public function convertAmount(float $amount, string $fromCode, string $toCode): ?float;

    /**
     * Format amount in a specific currency
     *
     * @param float $amount
     * @param string $code
     * @return string|null
     */
    public function formatAmount(float $amount, string $code): ?string;

    /**
     * Update exchange rates from an external service
     *
     * @return bool
     */
    public function updateExchangeRates(): bool;

    /**
     * Create a new currency
     *
     * @param array $data
     * @return Currency|null
     */
    public function createCurrency(array $data): ?Currency;

    /**
     * Update a currency
     *
     * @param string $code
     * @param array $data
     * @return bool
     */
    public function updateCurrency(string $code, array $data): bool;

    /**
     * Delete a currency
     *
     * @param string $code
     * @return bool
     */
    public function deleteCurrency(string $code): bool;
}
