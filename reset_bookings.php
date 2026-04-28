<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    DB::beginTransaction();
    
    DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
    
    $tables = ['booking_payments', 'booking_items', 'bookings', 'booking_selections'];
    
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
            echo "Truncated $table\n";
        } else {
            echo "Table $table not found, skipping\n";
        }
    }
    
    DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    
    DB::commit();
    echo "\nAll bookings removed and IDs reset to 1 successfully.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
