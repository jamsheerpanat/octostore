<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductQuestion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    public function answers()
    {
        return $this->hasMany(ProductAnswer::class, 'question_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
