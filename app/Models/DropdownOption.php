<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DropdownOption extends Model
{
    protected $fillable = ['dropdown_id', 'option_label', 'option_price'];

    // Optional: The inverse relationship
    public function dropdown()
    {
        return $this->belongsTo(ProductDropdown::class, 'dropdown_id');
    }
}
