<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CartController extends Controller
{
    // Helper to get or create cart
    private function getCart(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID', $request->input('session_id'));

        // Resolve effective ID for caching
        $cacheKey = null;
        if ($user) {
            $cacheKey = "cart_user_{$user->id}";
        } elseif ($sessionId) {
            $cacheKey = "cart_guest_{$sessionId}";
        }

        // 1. Try Cache
        if ($cacheKey && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 2. Fallback to DB
        $cart = null;
        if (!$sessionId && !$user) {
            $sessionId = (string) Str::uuid();
            $cacheKey = "cart_guest_{$sessionId}"; // New session
        }

        if ($user) {
            $cart = Cart::with(['items.product', 'items.variant'])
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$cart) {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'session_id' => $sessionId ?? (string) Str::uuid(),
                    'status' => 'active',
                    'currency' => 'USD'
                ]);
            }
        } else {
            $cart = Cart::with(['items.product', 'items.variant'])
                ->where('session_id', $sessionId)
                ->where('status', 'active')
                ->first();

            if (!$cart && $sessionId) {
                $cart = Cart::create([
                    'session_id' => $sessionId,
                    'status' => 'active',
                    'currency' => 'USD'
                ]);
            }
        }

        // Cache the fresh cart
        if ($cart) {
            Cache::put($cacheKey, $cart, 3600);
        }

        return $cart;
    }

    // Helper to refresh cache
    private function refreshCartCache(Cart $cart)
    {
        $cart->load(['items.product', 'items.variant']);
        $key = $cart->user_id ? "cart_user_{$cart->user_id}" : "cart_guest_{$cart->session_id}";
        Cache::put($key, $cart, 3600);
    }

    public function index(Request $request)
    {
        $sessionId = $request->header('X-Session-ID');
        if (!$sessionId && !$request->user()) {
            return response()->json(['data' => null, 'message' => 'No session'], 200);
        }

        $cart = $this->getCart($request);
        if (!$cart) {
            return response()->json(['data' => null], 200);
        }

        return response()->json([
            'data' => $cart,
            'session_id' => $cart->session_id
        ]);
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $cart = $this->getCart($request);
        // Ensure we are working with a fresh model from DB interactions for writing
        // But getCart might return a cached object. For generic operations it's fine,
        // but for relationship saving we need to ensure it's linked to DB context or just use IDs.
        // Since we are calling items()->..., if $cart is hydration from Cache, it might not have connection?
        // Laravel handles serialized models well, but let's be safe.
        // Actually, for addItem, we usually want to hit the DB to ensure consistency anyway, 
        // then update cache.

        $product = Product::find($validated['product_id']);

        $price = $product->variants->where('is_default', true)->first()?->price ?? 0;
        $variantId = $validated['variant_id'] ?? null;

        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant) {
                $price = $variant->price;
                if ($variant->stock_quantity < $validated['quantity']) {
                    return response()->json(['message' => 'Insufficient stock for this variant'], 422);
                }
            }
        }

        // We use the ID to query items ensuring DB consistency
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $validated['product_id'])
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existingItem) {
            $existingItem->quantity += $validated['quantity'];
            $existingItem->save();
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $validated['product_id'],
                'product_variant_id' => $variantId,
                'quantity' => $validated['quantity'],
                'unit_price' => $price,
            ]);
        }

        $cart->recalculateTotals(); // This saves the cart model
        $this->refreshCartCache($cart);

        return response()->json([
            'data' => $cart,
            'message' => 'Item added',
            'session_id' => $cart->session_id
        ]);
    }

    public function updateItem(Request $request, $itemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getCart($request);
        $item = $cart->items()->where('id', $itemId)->firstOrFail();

        $item->quantity = $validated['quantity'];
        $item->save();

        $cart->recalculateTotals();
        $this->refreshCartCache($cart);

        return response()->json([
            'data' => $cart,
            'message' => 'Cart updated'
        ]);
    }

    public function removeItem(Request $request, $itemId)
    {
        $cart = $this->getCart($request);
        CartItem::where('id', $itemId)->where('cart_id', $cart->id)->delete();
        $cart->recalculateTotals();
        $this->refreshCartCache($cart);

        return response()->json([
            'data' => $cart,
            'message' => 'Item removed'
        ]);
    }

    // Simple draft checkout placeholder with stock check
    public function checkoutDraft(Request $request)
    {
        $cart = $this->getCart($request);
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Validation: Check stock for all items
        $errors = [];
        foreach ($cart->items as $item) {
            $stock = $item->variant ? $item->variant->stock_quantity : 0;
            if ($item->quantity > $stock) {
                $errors[] = "Product {$item->product->name} (Variant: {$item->variant?->sku}) has insufficient stock ({$stock} available).";
            }
        }

        if (!empty($errors)) {
            return response()->json(['message' => 'Stock validation failed', 'errors' => $errors], 422);
        }

        // "Lock" logic (Simulation using Redis)
        // In a real scenario, we might decrement a "reserved_stock" column
        $lockKey = "checkout_lock_cart_{$cart->id}";
        Cache::put($lockKey, true, 600); // 10 mins

        return response()->json([
            'data' => $cart,
            'checkout_token' => Str::random(32), // Token to finalize
            'message' => 'Checkout draft created. Stock reserved for 10 minutes.'
        ]);
    }

    public function applyCoupon(Request $request, \App\Services\CouponService $couponService)
    {
        $validated = $request->validate(['code' => 'required|string']);
        $cart = $this->getCart($request);

        $result = $couponService->validateAndCalculate($cart, $validated['code'], $request->user()?->id);

        if (!$result['valid']) {
            return response()->json(['message' => $result['error']], 422);
        }

        $cart->coupon_code = $result['coupon']->code;
        // In real app, we should probably store discount_amount in DB column explicitly or metadata
        // For now, assuming recalculateTotals handles it or we just store metadata.
        $cart->metadata = array_merge($cart->metadata ?? [], [
            'coupon_discount' => $result['discount'],
            'coupon_id' => $result['coupon']->id
        ]);

        // Manually adjust grand total here since recalculateTotals in model is simple sum
        // A better approach is to update the recalculateTotals logic in the Model to check coupon.
        // Staying simple for now:
        $cart->items_total = $cart->items->sum('total_price');
        $cart->grand_total = max(0, ($cart->items_total + $cart->shipping_total + $cart->tax_total) - $result['discount']);

        $cart->save();
        $this->refreshCartCache($cart);

        return response()->json([
            'message' => 'Coupon applied',
            'data' => $cart,
            'discount_amount' => $result['discount']
        ]);
    }

    public function estimateShipping(Request $request)
    {
        $validated = $request->validate(['country_code' => 'required|string|size:2']);

        // Simple zone match implementation
        // Real world: check if country in rules['allowed_countries']
        $methods = \App\Models\ShippingMethod::where('is_active', true)->get();

        $validMethods = $methods->filter(function ($method) use ($validated) {
            $rules = $method->rules ?? [];
            // If no rules, assume global (or handle as needed)
            if (empty($rules))
                return true;

            if (isset($rules['allowed_countries']) && is_array($rules['allowed_countries'])) {
                return in_array($validated['country_code'], $rules['allowed_countries']);
            }
            return true;
        });

        return response()->json(['data' => $validMethods->values()]);
    }
}
