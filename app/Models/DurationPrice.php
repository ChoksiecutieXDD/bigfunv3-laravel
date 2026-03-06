<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DurationPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'label',
        'hours',
        'price'
    ];
}
