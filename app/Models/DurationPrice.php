<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DurationPrice extends Model
{
    protected $fillable = [
        'label',
        'hours',
        'price'
    ];
}
