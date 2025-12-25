<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'areas' => 'array',
        'coordinates' => 'array',
        'base_fee' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'free_shipping_amount' => 'decimal:2',
        'cod_surcharge' => 'decimal:2',
        'cod_allowed' => 'boolean',
        'is_active' => 'boolean',
    ];
}
