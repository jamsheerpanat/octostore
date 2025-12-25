<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * Validate and calculate coupon discount for a cart.
     * Does NOT save to cart, just returns calculation result.
     * 
     * @param Cart $cart
     * @param string $code
     * @param int|null $userId
     * @return array ['valid' => bool, 'discount' => float, 'coupon' => Coupon|null, 'error' => string|null]
     */
    public function validateAndCalculate(Cart $cart, string $code, ?int $userId = null): array
    {
        $coupon = Coupon::where('code', $code)->available()->first();

        if (!$coupon) {
            return ['valid' => false, 'discount' => 0, 'error' => 'Coupon not found or expired'];
        }

        // 1. Min Cart Amount check
        if ($coupon->min_cart_amount && $cart->items_total < $coupon->min_cart_amount) {
            return ['valid' => false, 'discount' => 0, 'error' => "Minimum cart amount of {$coupon->min_cart_amount} required"];
        }

        // 2. Global Usage Limit
        if ($coupon->usage_limit_per_coupon) {
             $used = $coupon->usages()->count();
             if ($used >= $coupon->usage_limit_per_coupon) {
                 return ['valid' => false, 'discount' => 0, 'error' => 'Coupon usage limit reached'];
             }
        }

        // 3. Per User Usage Limit
        if ($coupon->usage_limit_per_user && $userId) {
            $userUsed = $coupon->usages()->where('user_id', $userId)->count();
            if ($userUsed >= $coupon->usage_limit_per_user) {
                return ['valid' => false, 'discount' => 0, 'error' => 'You have already used this coupon'];
            }
        }
        
        // 4. Product/Category Rules (Simplified)
        // rules: { "allowed_products": [1, 2], "allowed_categories": [10] }
        // If rules exist, verify at least one item matches, or apply discount only to matching items.
        // For this implementation: "Whole Cart" discount if conditions met, OR "Affected Items" calculation.
        // Let's go with "Whole Cart" logic but only counting eligible items towards expected calculation if strict.
        // Or simpler: Flat discount on total, verifying no "exclusions".
        
        // Simple Logic:
        $discount = 0;
        
        if ($coupon->type === 'fixed') {
            $discount = $coupon->value;
        } elseif ($coupon->type === 'percent') {
            $discount = ($cart->items_total * $coupon->value) / 100;
            if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
                $discount = $coupon->max_discount_amount;
            }
        }
        
        // Ensure discount doesn't exceed total
        if ($discount > $cart->items_total) {
            $discount = $cart->items_total;
        }

        return [
            'valid' => true,
            'discount' => $discount,
            'coupon' => $coupon,
            'error' => null
        ];
    }
}
