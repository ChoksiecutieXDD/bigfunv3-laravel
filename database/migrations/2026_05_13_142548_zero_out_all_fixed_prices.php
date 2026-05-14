<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Zero out all fixed prices to enforce manual entry only
        \Illuminate\Support\Facades\DB::table('products')->update(['price' => 0]);
        \Illuminate\Support\Facades\DB::table('category_addons')->update(['addon_price' => 0]);
        \Illuminate\Support\Facades\DB::table('product_extras')->update(['yes_price' => 0, 'no_price' => 0]);
        \Illuminate\Support\Facades\DB::table('dropdown_options')->update(['option_price' => 0]);
        \Illuminate\Support\Facades\DB::table('delivery_zones')->update(['price' => 0]);
        \Illuminate\Support\Facades\DB::table('duration_prices')->update(['price' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive operation on pricing data, no easy way to reverse
        // without a backup of the original prices.
    }
};
