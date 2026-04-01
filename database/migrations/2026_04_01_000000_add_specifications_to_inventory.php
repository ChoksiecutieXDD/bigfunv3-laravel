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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'specification')) {
                $table->text('specification')->nullable()->after('name');
            }
        });

        Schema::table('category_addons', function (Blueprint $table) {
            if (!Schema::hasColumn('category_addons', 'counts_against')) {
                $table->string('counts_against')->nullable()->after('category_target');
            }
        });

        Schema::table('product_dropdowns', function (Blueprint $table) {
            if (!Schema::hasColumn('product_dropdowns', 'counts_against')) {
                $table->string('counts_against')->nullable()->after('category_target');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('specification');
        });

        Schema::table('category_addons', function (Blueprint $table) {
            $table->dropColumn('counts_against');
        });

        Schema::table('product_dropdowns', function (Blueprint $table) {
            $table->dropColumn('counts_against');
        });
    }
};
