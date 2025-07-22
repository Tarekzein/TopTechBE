<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Models\PromoCode;
use App\Http\Controllers\Controller;

class PromoCodeController extends Controller
{


    /**
     * Validate a promocode and return discount info
     */
    public function validateCode(Request $request): JsonResponse
    {
        $code = $request->query('code');
        $total = (float) $request->query('total', 0);
        $userId = $request->query('user_id');

        if (!$code) {
            return response()->json(['status' => 'error', 'message' => 'Promo code is required.'], 400);
        }

        $promo = PromoCode::where('code', $code)->first();
        if (!$promo) {
            return response()->json(['status' => 'error', 'message' => 'Promo code not found.'], 404);
        }
        if (!$promo->isActive()) {
            return response()->json(['status' => 'error', 'message' => 'Promo code is not active or expired.'], 400);
        }
        if ($promo->min_order_total && $total < $promo->min_order_total) {
            return response()->json(['status' => 'error', 'message' => 'Order total is less than minimum required for this promo code.'], 400);
        }
        if ($userId && !$promo->canBeUsedBy($userId)) {
            return response()->json(['status' => 'error', 'message' => 'Promo code usage limit reached for this user.'], 400);
        }
        $discount = $promo->calculateDiscount($total);
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $promo->id,
                'code' => $promo->code,
                'type' => $promo->type,
                'amount' => $promo->amount,
                'discount' => $discount,
                'max_discount' => $promo->max_discount,
                'min_order_total' => $promo->min_order_total,
                'usage_limit' => $promo->usage_limit,
                'usage_limit_per_user' => $promo->usage_limit_per_user,
                'used' => $promo->used,
                'expires_at' => $promo->expires_at,
            ]
        ]);
    }
}
