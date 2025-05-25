<?php

namespace Modules\Store\Policies;

use App\Models\User;
use Modules\Store\Models\ShippingAddress;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingAddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any shipping addresses.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the shipping address.
     *
     * @param User $user
     * @param ShippingAddress $address
     * @return bool
     */
    public function view(User $user, ShippingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can create shipping addresses.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the shipping address.
     *
     * @param User $user
     * @param ShippingAddress $address
     * @return bool
     */
    public function update(User $user, ShippingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can delete the shipping address.
     *
     * @param User $user
     * @param ShippingAddress $address
     * @return bool
     */
    public function delete(User $user, ShippingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can restore the shipping address.
     *
     * @param User $user
     * @param ShippingAddress $address
     * @return bool
     */
    public function restore(User $user, ShippingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can permanently delete the shipping address.
     *
     * @param User $user
     * @param ShippingAddress $address
     * @return bool
     */
    public function forceDelete(User $user, ShippingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }
} 