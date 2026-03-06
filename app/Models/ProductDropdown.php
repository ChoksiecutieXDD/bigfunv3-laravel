<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDropdown extends Model
{
    // Make sure your fillables are set too!
    protected $fillable = ['category_target', 'label'];

    /**
     * Get the options for the dropdown.
     */
    public function options()
    {
        // Parameter 1: The related model
        // Parameter 2: The foreign key on the dropdown_options table
        // Parameter 3: The local key on this table (defaults to 'id')
        return $this->hasMany(DropdownOption::class, 'dropdown_id', 'id');
    }
}
