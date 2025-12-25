<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'cost' => 'decimal:2',
        'rules' => 'array',
        'is_active' => 'boolean',
    ];
}
