<?php

namespace Modules\Store\Policies;

use App\Models\User;
use Modules\Store\Models\BillingAddress;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingAddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any billing addresses.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the billing address.
     *
     * @param User $user
     * @param BillingAddress $address
     * @return bool
     */
    public function view(User $user, BillingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can create billing addresses.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the billing address.
     *
     * @param User $user
     * @param BillingAddress $address
     * @return bool
     */
    public function update(User $user, BillingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can delete the billing address.
     *
     * @param User $user
     * @param BillingAddress $address
     * @return bool
     */
    public function delete(User $user, BillingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can restore the billing address.
     *
     * @param User $user
     * @param BillingAddress $address
     * @return bool
     */
    public function restore(User $user, BillingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can permanently delete the billing address.
     *
     * @param User $user
     * @param BillingAddress $address
     * @return bool
     */
    public function forceDelete(User $user, BillingAddress $address): bool
    {
        return $user->id === $address->user_id;
    }
} 