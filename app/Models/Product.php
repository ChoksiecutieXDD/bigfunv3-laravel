<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'counts_against',
        'price',
        'daily_limit',
        'is_active'
    ];

    // Cast is_active to boolean so the checkbox works perfectly with Livewire
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
