<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Services\AddressService;
use Modules\Store\Models\BillingAddress;
use Modules\Store\Models\ShippingAddress;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AddressController extends Controller
{
    protected AddressService $addressService;

    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    /**
     * Get all billing addresses for the authenticated user.
     *
     * @return ResourceCollection
     */
    public function getBillingAddresses(): ResourceCollection
    {
        $addresses = $this->addressService->getBillingAddresses();
        return JsonResource::collection($addresses);
    }

    /**
     * Get all shipping addresses for the authenticated user.
     *
     * @return ResourceCollection
     */
    public function getShippingAddresses(): ResourceCollection
    {
        $addresses = $this->addressService->getShippingAddresses();
        return JsonResource::collection($addresses);
    }

    /**
     * Create a new billing address.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createBillingAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'is_default' => 'boolean',
            'label' => 'nullable|string|max:50',
        ]);

        $address = $this->addressService->createBillingAddress($validated);
        return response()->json(new JsonResource($address), 201);
    }

    /**
     * Create a new shipping address.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createShippingAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'is_default' => 'boolean',
            'label' => 'nullable|string|max:50',
        ]);

        $address = $this->addressService->createShippingAddress($validated);
        return response()->json(new JsonResource($address), 201);
    }

    /**
     * Update a billing address.
     *
     * @param Request $request
     * @param BillingAddress $address
     * @return JsonResponse
     */
    public function updateBillingAddress(Request $request, BillingAddress $address): JsonResponse
    {
        $this->authorize('update', $address);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|max:2',
            'is_default' => 'boolean',
            'label' => 'nullable|string|max:50',
        ]);

        $address = $this->addressService->updateBillingAddress($address, $validated);
        return response()->json(new JsonResource($address));
    }

    /**
     * Update a shipping address.
     *
     * @param Request $request
     * @param ShippingAddress $address
     * @return JsonResponse
     */
    public function updateShippingAddress(Request $request, ShippingAddress $address): JsonResponse
    {
        $this->authorize('update', $address);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|max:2',
            'is_default' => 'boolean',
            'label' => 'nullable|string|max:50',
        ]);

        $address = $this->addressService->updateShippingAddress($address, $validated);
        return response()->json(new JsonResource($address));
    }

    /**
     * Delete a billing address.
     *
     * @param BillingAddress $address
     * @return JsonResponse
     */
    public function deleteBillingAddress(BillingAddress $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $this->addressService->deleteBillingAddress($address);
        return response()->json(null, 204);
    }

    /**
     * Delete a shipping address.
     *
     * @param ShippingAddress $address
     * @return JsonResponse
     */
    public function deleteShippingAddress(ShippingAddress $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $this->addressService->deleteShippingAddress($address);
        return response()->json(null, 204);
    }

    /**
     * Get the default billing address.
     *
     * @return JsonResponse
     */
    public function getDefaultBillingAddress(): JsonResponse
    {
        $address = $this->addressService->getDefaultBillingAddress();
        return response()->json(new JsonResource($address));
    }

    /**
     * Get the default shipping address.
     *
     * @return JsonResponse
     */
    public function getDefaultShippingAddress(): JsonResponse
    {
        $address = $this->addressService->getDefaultShippingAddress();
        return response()->json(new JsonResource($address));
    }
} 