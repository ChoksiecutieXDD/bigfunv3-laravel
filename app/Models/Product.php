<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'category',
        'counts_against',
        'price',
        'daily_limit',
        'sort_order',
        'is_active',
        'specification'
    ];

    // Cast is_active to boolean so the checkbox works perfectly with Livewire
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
