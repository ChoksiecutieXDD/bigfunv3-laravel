<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAddon extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'category_target',
        'addon_label',
        'addon_price'
    ];
}
