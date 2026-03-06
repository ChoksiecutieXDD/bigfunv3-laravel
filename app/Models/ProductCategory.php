<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    // Tell Laravel this table doesn't use created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'category_name',
        'daily_limit',
        'sort_order'
    ];
}
