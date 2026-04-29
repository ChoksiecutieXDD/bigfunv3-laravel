<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$bookings = DB::table('bookings')
    ->where('specific_extra', 'like', '%Anchor Weights%')
    ->get(['id', 'invoice_number', 'specific_extra']);

foreach ($bookings as $b) {
    echo "ID: {$b->id} | Invoice: {$b->invoice_number} | Extra: {$b->specific_extra}\n";
}
