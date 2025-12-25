<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'rules' => 'array',
        'value' => 'decimal:2',
        'min_cart_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }
    
    // Scope for availability
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                     ->where(function($q) {
                         $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                     })
                     ->where(function($q) {
                         $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                     });
    }
}
