<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public $timestamps = false; // Custom created_at only

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime'
    ];

    public function subject()
    {
        return $this->morphTo();
    }
}
