<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$affected = DB::table('products')
    ->where('total_quantity', 1)
    ->update(['total_quantity' => 99]);

echo "Updated $affected products to have total_quantity = 99.\n";
