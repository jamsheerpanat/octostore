<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $guarded = ['id']; // Note: 'database_name' etc. should be guarded if you use mass assignment carefully

    protected $hidden = [
        'database_password',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'feature_flags' => 'array',
        'subscription_ends_at' => 'datetime'
    ];
    
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
