<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\CustomerRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\User;

class CustomerService
{
    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get all customers (admin only).
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllCustomers(array $filters = []): LengthAwarePaginator
    {
        return $this->customerRepository->getAllCustomers($filters);
    }

    /**
     * Get customers for the authenticated vendor.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     * @throws \Exception
     */
    public function getVendorCustomers(array $filters = []): LengthAwarePaginator
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if (!$user->vendor) {
            throw new \Exception('User is not associated with any vendor account.');
        }

        return $this->customerRepository->getVendorCustomers($user->vendor->id, $filters);
    }

    /**
     * Get customers for a specific vendor (admin only).
     *
     * @param int $vendorId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getVendorCustomersForAdmin(int $vendorId, array $filters = []): LengthAwarePaginator
    {
        return $this->customerRepository->getVendorCustomers($vendorId, $filters);
    }

    /**
     * Get customer details (admin only).
     *
     * @param int $customerId
     * @return User|null
     */
    public function getCustomerDetails(int $customerId): ?User
    {
        return $this->customerRepository->getCustomerDetails($customerId);
    }

    /**
     * Get customer details for the authenticated vendor.
     *
     * @param int $customerId
     * @return User|null
     * @throws \Exception
     */
    public function getVendorCustomerDetails(int $customerId): ?User
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if (!$user->vendor) {
            throw new \Exception('User is not associated with any vendor account.');
        }

        return $this->customerRepository->getVendorCustomerDetails($customerId, $user->vendor->id);
    }

    /**
     * Get customer details for a specific vendor (admin only).
     *
     * @param int $customerId
     * @param int $vendorId
     * @return User|null
     */
    public function getVendorCustomerDetailsForAdmin(int $customerId, int $vendorId): ?User
    {
        return $this->customerRepository->getVendorCustomerDetails($customerId, $vendorId);
    }

    /**
     * Get customers analytics (admin only).
     *
     * @param array $filters
     * @return array
     */
    public function getCustomersAnalytics(array $filters = []): array
    {
        return $this->customerRepository->getCustomersAnalytics($filters);
    }

    /**
     * Get customers analytics for the authenticated vendor.
     *
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function getVendorCustomersAnalytics(array $filters = []): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if (!$user->vendor) {
            throw new \Exception('User is not associated with any vendor account.');
        }

        return $this->customerRepository->getVendorCustomersAnalytics($user->vendor->id, $filters);
    }

    /**
     * Get customers analytics for a specific vendor (admin only).
     *
     * @param int $vendorId
     * @param array $filters
     * @return array
     */
    public function getVendorCustomersAnalyticsForAdmin(int $vendorId, array $filters = []): array
    {
        return $this->customerRepository->getVendorCustomersAnalytics($vendorId, $filters);
    }

    /**
     * Export customers data (admin only).
     *
     * @param array $filters
     * @return array
     */
    public function exportCustomersData(array $filters = []): array
    {
        // Remove pagination for export
        $filters['per_page'] = 10000; // Large number to get all records
        $customers = $this->customerRepository->getAllCustomers($filters);

        $exportData = [];
        foreach ($customers as $customer) {
            $exportData[] = [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'total_orders' => $customer->orders_count,
                'completed_orders' => $customer->completed_orders_count,
                'total_spent' => $customer->orders_sum_total ?? 0,
                'avg_order_value' => $customer->orders_avg_total ?? 0,
                'first_order' => $customer->orders->first()?->created_at?->format('Y-m-d'),
                'last_order' => $customer->orders->last()?->created_at?->format('Y-m-d'),
                'registered_at' => $customer->created_at->format('Y-m-d'),
            ];
        }

        return $exportData;
    }

    /**
     * Export vendor customers data.
     *
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function exportVendorCustomersData(array $filters = []): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if (!$user->vendor) {
            throw new \Exception('User is not associated with any vendor account.');
        }

        // Remove pagination for export
        $filters['per_page'] = 10000; // Large number to get all records
        $customers = $this->customerRepository->getVendorCustomers($user->vendor->id, $filters);

        $exportData = [];
        foreach ($customers as $customer) {
            $exportData[] = [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'total_orders' => $customer->vendor_orders_count,
                'completed_orders' => $customer->vendor_completed_orders_count,
                'total_spent' => $customer->vendor_total_spent ?? 0,
                'first_order' => $customer->orders->first()?->created_at?->format('Y-m-d'),
                'last_order' => $customer->orders->last()?->created_at?->format('Y-m-d'),
                'registered_at' => $customer->created_at->format('Y-m-d'),
            ];
        }

        return $exportData;
    }

    /**
     * Get customer summary statistics.
     *
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function getCustomersSummary(array $filters = []): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if ($user->vendor) {
            return $this->getVendorCustomersAnalytics($filters);
        } else {
            return $this->getCustomersAnalytics($filters);
        }
    }
}
