<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTimeSlot extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'days_of_week' => 'array',
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i', // simpler casting or string
        'end_time' => 'datetime:H:i',
    ];
}
