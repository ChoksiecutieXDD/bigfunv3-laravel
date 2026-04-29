<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$b = DB::table('bookings')->orderBy('id', 'desc')->first();
if ($b) {
    echo "Invoice: {$b->invoice_number}\n";
    echo "Type: {$b->payment_type}\n";
    echo "Total Amount (DB): {$b->total_amount}\n";
    echo "Surcharge (DB): {$b->surcharge_amount}\n";
    echo "Owing (DB): {$b->owing_amount}\n";
    echo "Paid (DB): {$b->amount_paid}\n";
} else {
    echo "No booking found.\n";
}
