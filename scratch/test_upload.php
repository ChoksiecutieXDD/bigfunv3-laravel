<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\Admin\BookingApiController;
use Illuminate\Http\Request;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create a mock file
$file = UploadedFile::fake()->image('test_image.png');

$request = new Request([], [
    'action' => 'save_full_booking',
    'invoice_number' => 'TEST-' . time(),
    'event_date' => date('Y-m-d'),
    'customer_first_name' => 'Test',
    'customer_last_name' => 'User',
    'customer_email_address' => 'test@example.com',
], [], [], ['delivery_attachment' => $file]);

$controller = new BookingApiController();
$response = $controller->handler($request);

echo "Response: " . $response->getContent() . "\n";
