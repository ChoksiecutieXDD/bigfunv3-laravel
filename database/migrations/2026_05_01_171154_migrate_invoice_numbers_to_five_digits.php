<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $bookings = DB::table('bookings')->get();

        foreach ($bookings as $b) {
            $newInv = null;
            $current = $b->invoice_number;

            // Pattern 1: INV-20240501-0001 (Old format with date)
            if (preg_match('/INV-[0-9]{8}-([0-9]+)/i', $current, $m)) {
                $newInv = 'INV-' . str_pad($m[1], 5, '0', STR_PAD_LEFT);
            }
            // Pattern 2: Inv no. 0001 (Temporary transition format)
            elseif (preg_match('/Inv no\.\s*([0-9]+)/i', $current, $m)) {
                $newInv = 'INV-' . str_pad($m[1], 5, '0', STR_PAD_LEFT);
            }
            // Pattern 3: INV-0001 (Standard 4-digit format)
            elseif (preg_match('/^INV-([0-9]{4})$/i', $current, $m)) {
                $newInv = 'INV-' . str_pad($m[1], 5, '0', STR_PAD_LEFT);
            }

            if ($newInv && $newInv !== $current) {
                DB::table('bookings')->where('id', $b->id)->update(['invoice_number' => $newInv]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
