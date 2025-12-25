<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashDeal extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'flash_deal_products')
                    ->withPivot(['discount_type', 'discount_value']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
             ->where('starts_at', '<=', now())
             ->where('ends_at', '>=', now());
    }
}
