<?php

namespace Modules\Store\Services;

use Modules\Store\Models\BillingAddress;
use Modules\Store\Models\ShippingAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class AddressService
{
    /**
     * Get all billing addresses for the authenticated user.
     *
     * @return Collection
     */
    public function getBillingAddresses(): Collection
    {
        return BillingAddress::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all shipping addresses for the authenticated user.
     *
     * @return Collection
     */
    public function getShippingAddresses(): Collection
    {
        return ShippingAddress::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new billing address.
     *
     * @param array $data
     * @return BillingAddress
     */
    public function createBillingAddress(array $data): BillingAddress
    {
        if ($data['is_default'] ?? false) {
            BillingAddress::where('user_id', Auth::id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return BillingAddress::create([
            'user_id' => Auth::id(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'],
            'country' => $data['country'],
            'is_default' => $data['is_default'] ?? false,
            'label' => $data['label'] ?? null,
        ]);
    }

    /**
     * Create a new shipping address.
     *
     * @param array $data
     * @return ShippingAddress
     */
    public function createShippingAddress(array $data): ShippingAddress
    {
        if ($data['is_default'] ?? false) {
            ShippingAddress::where('user_id', Auth::id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return ShippingAddress::create([
            'user_id' => Auth::id(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'],
            'country' => $data['country'],
            'is_default' => $data['is_default'] ?? false,
            'label' => $data['label'] ?? null,
        ]);
    }

    /**
     * Update a billing address.
     *
     * @param BillingAddress $address
     * @param array $data
     * @return BillingAddress
     */
    public function updateBillingAddress(BillingAddress $address, array $data): BillingAddress
    {
        if (($data['is_default'] ?? false) && !$address->is_default) {
            BillingAddress::where('user_id', Auth::id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $address->update([
            'first_name' => $data['first_name'] ?? $address->first_name,
            'last_name' => $data['last_name'] ?? $address->last_name,
            'email' => $data['email'] ?? $address->email,
            'phone' => $data['phone'] ?? $address->phone,
            'address' => $data['address'] ?? $address->address,
            'city' => $data['city'] ?? $address->city,
            'state' => $data['state'] ?? $address->state,
            'postcode' => $data['postcode'] ?? $address->postcode,
            'country' => $data['country'] ?? $address->country,
            'is_default' => $data['is_default'] ?? $address->is_default,
            'label' => $data['label'] ?? $address->label,
        ]);

        return $address;
    }

    /**
     * Update a shipping address.
     *
     * @param ShippingAddress $address
     * @param array $data
     * @return ShippingAddress
     */
    public function updateShippingAddress(ShippingAddress $address, array $data): ShippingAddress
    {
        if (($data['is_default'] ?? false) && !$address->is_default) {
            ShippingAddress::where('user_id', Auth::id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $address->update([
            'first_name' => $data['first_name'] ?? $address->first_name,
            'last_name' => $data['last_name'] ?? $address->last_name,
            'email' => $data['email'] ?? $address->email,
            'phone' => $data['phone'] ?? $address->phone,
            'address' => $data['address'] ?? $address->address,
            'city' => $data['city'] ?? $address->city,
            'state' => $data['state'] ?? $address->state,
            'postcode' => $data['postcode'] ?? $address->postcode,
            'country' => $data['country'] ?? $address->country,
            'is_default' => $data['is_default'] ?? $address->is_default,
            'label' => $data['label'] ?? $address->label,
        ]);

        return $address;
    }

    /**
     * Delete a billing address.
     *
     * @param BillingAddress $address
     * @return bool
     */
    public function deleteBillingAddress(BillingAddress $address): bool
    {
        return $address->delete();
    }

    /**
     * Delete a shipping address.
     *
     * @param ShippingAddress $address
     * @return bool
     */
    public function deleteShippingAddress(ShippingAddress $address): bool
    {
        return $address->delete();
    }

    /**
     * Get the default billing address for the authenticated user.
     *
     * @return BillingAddress|null
     */
    public function getDefaultBillingAddress(): ?BillingAddress
    {
        return BillingAddress::where('user_id', Auth::id())
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get the default shipping address for the authenticated user.
     *
     * @return ShippingAddress|null
     */
    public function getDefaultShippingAddress(): ?ShippingAddress
    {
        return ShippingAddress::where('user_id', Auth::id())
            ->where('is_default', true)
            ->first();
    }
} 