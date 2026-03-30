<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingApiController extends Controller
{
    // --- 1. REAL-TIME AVAILABILITY CHECK ---
    public function checkAvailability(Request $request)
    {
        $date = $request->query('date');
        $invoice = $request->query('invoice');

        if (empty($date)) {
            return response()->json(['status' => 'success', 'products' => []]);
        }

        $categories = [];
        $resCat = DB::table('product_categories')->get();
        foreach ($resCat as $row) {
            $catName = strtolower(trim($row->category_name));
            $categories[$catName] = [
                'limit' => (int)$row->daily_limit,
                'booked' => 0,
                'left' => (int)$row->daily_limit
            ];
        }

        $master_inventory = [];
        $product_targets = [];

        $resProd = DB::table('products')->where('is_active', 1)->get();
        foreach ($resProd as $row) {
            $cleanName = strtolower(trim($row->name));
            $stock = (int)$row->total_quantity;
            $limit = (int)$row->daily_limit;

            $effectiveTotal = ($limit > 0) ? $limit : (($stock > 0) ? $stock : 9999);

            $master_inventory[$cleanName] = [
                'total' => $effectiveTotal,
                'booked' => 0,
                'left' => $effectiveTotal
            ];

            $target = !empty($row->counts_against) ? $row->counts_against : $row->category;
            $product_targets[$cleanName] = strtolower(trim($target));
        }

        $res = DB::table('booking_items as bi')
            ->join('bookings as b', 'bi.booking_id', '=', 'b.id')
            ->where('b.event_date', $date)
            ->where('b.invoice_number', '!=', $invoice)
            ->where(function ($q) {
                $q->whereIn('b.status', ['Pending', 'Confirmed', 'Paid'])
                    ->orWhere(function ($q2) {
                        $q2->where('b.status', 'Draft')
                            ->where('b.created_at', '>=', Carbon::now()->subMinutes(20));
                    });
            })
            ->select('bi.item_name', DB::raw('COUNT(bi.id) as cnt'))
            ->groupBy('bi.item_name')
            ->get();

        foreach ($res as $row) {
            $cleanName = strtolower(trim($row->item_name));
            $count = (int)$row->cnt;

            if (isset($master_inventory[$cleanName])) {
                $master_inventory[$cleanName]['booked'] += $count;
            } else {
                $master_inventory[$cleanName] = ['total' => $count, 'booked' => $count, 'left' => 0];
            }

            if (isset($product_targets[$cleanName])) {
                $targetCat = $product_targets[$cleanName];
                if (isset($categories[$targetCat])) {
                    $categories[$targetCat]['booked'] += $count;
                }
            }
        }

        foreach ($categories as $cat => &$data) {
            $data['left'] = ($data['limit'] > 0) ? max(0, $data['limit'] - $data['booked']) : 9999;
        }
        unset($data);

        foreach ($master_inventory as $prodName => &$prodData) {
            $prodData['left'] = max(0, $prodData['total'] - $prodData['booked']);

            if (isset($product_targets[$prodName])) {
                $targetCat = $product_targets[$prodName];
                if (isset($categories[$targetCat]) && $categories[$targetCat]['limit'] > 0) {
                    $catLeft = $categories[$targetCat]['left'];
                    $prodData['left'] = min($prodData['left'], $catLeft);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'products' => $master_inventory,
        ]);
    }

    // --- 2. MAIN POST HANDLER ---
    public function handler(Request $request)
    {
        $action = $request->input('action', '');
        $DAILY_TOTAL_LIMIT = 7;

        try {
            switch ($action) {
                case 'delete_draft':
                    $del_id = (int)$request->input('booking_id', 0);
                    if ($del_id > 0) {
                        DB::table('bookings')->where('id', $del_id)->where('status', 'Draft')->delete();
                    }
                    return response()->json(['success' => true, 'status' => 'success', 'message' => 'Draft deleted']);

                case 'get_calendar_slots':
                    $start = $request->input('start');
                    $end = $request->input('end');
                    if (!$start || !$end) return response()->json(['success' => false, 'status' => 'error', 'message' => 'Missing dates']);

                    $res = DB::table('bookings')
                        ->select('event_date', DB::raw('COUNT(*) as cnt'))
                        ->whereBetween('event_date', [$start, $end])
                        ->where(function ($q) {
                            $q->whereIn('status', ['Pending', 'Confirmed', 'Paid'])
                                ->orWhere(function ($q2) {
                                    $q2->where('status', 'Draft')->where('created_at', '>=', now()->subMinutes(20));
                                });
                        })
                        ->groupBy('event_date')
                        ->get();

                    $map = [];
                    foreach ($res as $row) {
                        $map[$row->event_date] = (int)$row->cnt;
                    }
                    return response()->json(['success' => true, 'status' => 'success', 'daily_limit' => $DAILY_TOTAL_LIMIT, 'counts' => $map]);

                case 'check_duplicates':
                    $date = $request->input('date');
                    $firstName = trim($request->input('first_name', ''));
                    $lastName = trim($request->input('last_name', ''));
                    $email = trim($request->input('email', ''));
                    $currentInvoice = $request->input('current_invoice', '');
                    $warnings = [];

                    if ($date && $firstName && $lastName) {
                        $cnt = DB::table('bookings')
                            ->where('event_date', $date)
                            ->where('customer_first_name', $firstName)
                            ->where('customer_last_name', $lastName)
                            ->where('invoice_number', '!=', $currentInvoice)
                            ->where(function ($q) {
                                $q->whereIn('status', ['Pending', 'Confirmed', 'Paid'])
                                    ->orWhere(function ($q2) {
                                        $q2->where('status', 'Draft')->where('created_at', '>=', now()->subMinutes(20));
                                    });
                            })->count();
                        if ($cnt > 0) {
                            $warnings[] = "Customer <strong>$firstName $lastName</strong> already has a booking on <strong>$date</strong>.";
                        }
                    }

                    if ($date && $email) {
                        $cnt = DB::table('bookings')
                            ->where('event_date', $date)
                            ->where('customer_email', $email)
                            ->where('invoice_number', '!=', $currentInvoice)
                            ->where(function ($q) {
                                $q->whereIn('status', ['Pending', 'Confirmed', 'Paid'])
                                    ->orWhere(function ($q2) {
                                        $q2->where('status', 'Draft')->where('created_at', '>=', now()->subMinutes(20));
                                    });
                            })->count();
                        if ($cnt > 0) {
                            $warnings[] = "The email <strong>$email</strong> is already used for a booking on <strong>$date</strong>.";
                        }
                    }
                    return response()->json(['success' => true, 'status' => 'success', 'warnings' => $warnings]);

                case 'save_full_booking':
                    DB::beginTransaction();

                    $invoice_number = $request->input('invoice_number');
                    $is_update = false;
                    $booking_id = (int)$request->input('booking_id');

                    $existing = DB::table('bookings')->where('invoice_number', $invoice_number)->first();
                    $existing_files = [null, null, null, null, null];

                    if ($existing) {
                        $is_update = true;
                        $booking_id = $existing->id;
                        $existing_files = [
                            $existing->delivery_attachment,
                            $existing->delivery_attachment_2,
                            $existing->delivery_attachment_3,
                            $existing->delivery_attachment_4,
                            $existing->delivery_attachment_5
                        ];
                    }

                    // Combined File Size Check (Max 5MB)
                    $totalSize = 0;
                    for ($i = 1; $i <= 5; $i++) {
                        $inputName = ($i === 1) ? 'delivery_attachment' : 'delivery_attachment_' . $i;
                        if ($request->hasFile($inputName)) {
                            $totalSize += $request->file($inputName)->getSize();
                        }
                    }

                    if ($totalSize > 5 * 1024 * 1024) {
                        return response()->json(['success' => false, 'status' => 'error', 'message' => 'Total size of attachments must not exceed 5MB.']);
                    }

                    // Handle File Uploads
                    for ($i = 1; $i <= 5; $i++) {
                        $suffix = ($i === 1) ? '' : "_$i";
                        $inputName = "delivery_attachment$suffix";
                        
                        if ($request->hasFile($inputName)) {
                            $file = $request->file($inputName);
                            $destinationPath = public_path('uploads');
                            if (!File::exists($destinationPath)) {
                                File::makeDirectory($destinationPath, 0755, true);
                            }
                            $fileName = $file->hashName();
                            $file->move($destinationPath, $fileName);
                            $existing_files[$i-1] = $fileName;
                        }
                    }

                    $payment_status = $request->input('payment_status', 'Pending');
                    $db_status = 'Confirmed'; 

                    $data = [
                        'event_date' => $request->input('event_date'),
                        'start_time' => $request->input('start_time') ?? '00:00:00',
                        'end_time'   => $request->input('end_time')   ?? '23:59:59',
                        'event_type' => $request->input('event_type', 'Private'),
                        'hire_type'  => $request->input('hire_type')   ?? 'Standard',
                        'is_null_booking' => $request->has('is_null_booking') ? 1 : 0,
                        'expected_people' => (int)$request->input('expected_people', 0),
                        'customer_first_name' => $request->input('customer_first_name'),
                        'customer_last_name' => $request->input('customer_last_name'),
                        'customer_email' => $request->input('customer_email_address'),
                        'customer_phone' => $request->input('customer_phone_mobile'),
                        'customer_organization' => $request->input('customer_organization'),
                        'customer_abn' => $request->input('customer_abn'),
                        'employer_name' => $request->input('employer_name'),
                        'customer_business_phone' => $request->input('customer_business_phone'),
                        'lead_operator' => $request->input('lead_operator', 'Team'),
                        'lead_deliverer' => $request->input('lead_deliverer', 'Team'),
                        'address_line_1' => $request->input('address_line_1'),
                        'business_address' => $request->input('business_address'),
                        'suburb' => $request->input('suburb'),
                        'state' => $request->input('state'),
                        'postcode' => $request->input('postcode'),
                        'delivery_area' => $request->input('delivery_area'),
                        'delivery_cost' => (float)$request->input('delivery_cost', 0),
                        'duration' => ($request->input('duration') === 'custom') ? $request->input('custom_duration_text') : $request->input('duration'),
                        'duration_cost' => (float)$request->input('duration_cost', 0),
                        'notes_customer' => $request->input('notes_customer'),
                        'notes_delivery' => $request->input('notes_delivery'),
                        'operational_hours' => $request->input('operational_hours'),
                        'delivery_attachment' => $existing_files[0],
                        'delivery_attachment_2' => $existing_files[1],
                        'delivery_attachment_3' => $existing_files[2],
                        'delivery_attachment_4' => $existing_files[3],
                        'delivery_attachment_5' => $existing_files[4],
                        'payment_type' => $request->input('payment_type', 'EFT'),
                        'payment_status' => $payment_status,
                        'payment_reference' => $request->input('payment_reference'),
                        'total_amount' => (float)$request->input('final_total', 0),
                        'surcharge_amount' => (float)$request->input('surcharge_amount', 0),
                        'deposit_required' => (float)$request->input('deposit_amount', 0),
                        'card_network' => $request->input('card_network'),
                        'card_last4' => substr(str_replace(' ', '', $request->input('card_number', '')), -4),
                        'card_number' => $request->input('card_number'),
                        'card_expiry' => $request->input('card_expiry'),
                        'card_cvv' => $request->input('card_cvv'),
                        'status' => $db_status
                    ];

                    if ($is_update) {
                        DB::table('bookings')->where('id', $booking_id)->update($data);
                    } else {
                        $data['invoice_number'] = $invoice_number;
                        $data['created_at'] = now();
                        
                        // Add Creator Attribution (Name and Role)
                        if (Auth::check()) {
                            $user = Auth::user();
                            $data['created_by_user_id'] = $user->user_id;
                            $data['booked_by'] = $user->first_name . ', ' . $user->role;
                        }
                        
                        $booking_id = DB::table('bookings')->insertGetId($data);
                    }

                    // Save Items
                    DB::table('booking_items')->where('booking_id', $booking_id)->delete();
                    $products = $request->input('products', []);
                    if (is_array($products) && count($products) > 0) {
                        $insertItems = [];
                        foreach ($products as $p) {
                            if (trim($p)) {
                                $insertItems[] = [
                                    'booking_id' => $booking_id,
                                    'item_name' => trim($p),
                                    'item_price' => 0.00,
                                    'is_custom' => 0,
                                    'qty' => 1
                                ];
                            }
                        }
                        if (!empty($insertItems)) DB::table('booking_items')->insert($insertItems);
                    }

                    // Save JSON Extras
                    $raw_extras = [];
                    foreach ($request->all() as $key => $val) {
                        if ((str_starts_with($key, 'dd_') || str_starts_with($key, 'add_') || str_starts_with($key, 'q_')) && $val !== '') {
                            $raw_extras[$key] = $val;
                        }
                    }

                    DB::table('bookings')->where('id', $booking_id)->update([
                        'extras_json' => json_encode($raw_extras)
                    ]);

                    DB::commit();

                    // Trigger Google Sheet Sync
                    $this->syncToGoogleSheet($booking_id);

                    return response()->json(['success' => true, 'status' => 'success', 'message' => 'Booking successfully finalized']);

                default:
                    return response()->json(['success' => false, 'status' => 'error', 'message' => 'Unknown action']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Syncs booking data to the Google Spreadsheet via Web App Webhook.
     */
    protected function syncToGoogleSheet($bookingId)
    {
        try {
            $webhookUrl = config('services.google.sheet_webhook');
            if (empty($webhookUrl)) return;

            $booking = DB::table('bookings')->where('id', $bookingId)->first();
            if (!$booking) return;

            $items = DB::table('booking_items')
                ->where('booking_id', $bookingId)
                ->pluck('item_name')
                ->toArray();

            // Parse Extras JSON into a readable string (Translating IDs to Names)
            $extras = json_decode($booking->extras_json, true) ?: [];
            $extraString = "";
            foreach ($extras as $key => $val) {
                if (str_starts_with($key, 'dd_')) {
                    $ddId = str_replace('dd_', '', $key);
                    $optionId = $val;
                    
                    $ddLabel = DB::table('product_dropdowns')->where('id', $ddId)->value('label') ?? "Extra";
                    $optLabel = DB::table('dropdown_options')->where('id', $optionId)->value('option_label') ?? $val;
                    
                    $extraString .= "$ddLabel: $optLabel | ";
                } elseif (str_starts_with($key, 'add_')) {
                    $addonId = str_replace('add_', '', $key);
                    $addonLabel = DB::table('category_addons')->where('id', $addonId)->value('addon_label') ?? "Addon";
                    
                    $extraString .= "$addonLabel: $val | ";
                } else {
                    $cleanKey = ucwords(str_replace(['q_', '_'], ['', ' '], $key));
                    $extraString .= "$cleanKey: $val | ";
                }
            }
            $extraString = rtrim($extraString, " | ");

            $payload = [
                'invoice_number'        => $booking->invoice_number,
                'status'                => $booking->status,
                'payment_status'        => $booking->payment_status,
                'booked_by'             => $booking->booked_by ?? 'System',
                'event_date'            => $booking->event_date,
                'start_time'            => $booking->start_time,
                'end_time'              => $booking->end_time,
                'duration'              => $booking->duration,
                'operational_hours'     => $booking->operational_hours,
                'items'                 => implode(', ', $items),
                'customer_name'         => trim($booking->customer_first_name . ' ' . $booking->customer_last_name),
                'customer_email'        => $booking->customer_email,
                'customer_phone'        => $booking->customer_phone,
                'customer_organization' => $booking->customer_organization,
                'customer_abn'          => $booking->customer_abn,
                'employer_name'         => $booking->employer_name,
                'business_phone'        => $booking->customer_business_phone,
                'business_address'      => $booking->business_address,
                'event_type'            => $booking->event_type,
                'address'               => $booking->address_line_1,
                'suburb'                => $booking->suburb,
                'state_postcode'        => $booking->state . ' ' . $booking->postcode,
                'delivery_area'         => $booking->delivery_area,
                'lead_deliverer'        => $booking->lead_deliverer,
                'lead_operator'         => $booking->lead_operator,
                'expected_people'       => $booking->expected_people,
                'total_amount'          => number_format($booking->total_amount, 2),
                'surcharge_amount'      => number_format($booking->surcharge_amount, 2),
                'deposit_required'      => number_format($booking->deposit_required, 2),
                'payment_type'          => $booking->payment_type,
                'payment_reference'     => $booking->payment_reference,
                'card_network'          => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') ? $booking->card_network : 'N/A',
                'card_masked'           => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') 
                                            ? '**** **** ' . substr(str_replace(' ', '', $booking->card_number ?? ''), -8, 4) . ' ' . substr(str_replace(' ', '', $booking->card_number ?? ''), -4)
                                            : 'N/A',
                'card_cvv_expiry'       => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') 
                                            ? 'Exp: **/** | CVV: *** (Secure)' 
                                            : 'N/A',
                'manual_delivery_cost'  => number_format($booking->delivery_cost, 2),
                'manual_duration_cost'  => number_format($booking->duration_cost, 2),
                'notes_delivery'        => $booking->notes_delivery,
                'notes_customer'        => $booking->notes_customer,
                'extra_configs'         => $extraString,
                'synced_at'             => now()->toDateTimeString(),
            ];

            // Fire and forget (or log result)
            $response = Http::withoutVerifying()->post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::error("Google Sheet Sync Failed for Invoice: {$booking->invoice_number}", [
                    'error' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Google Sheet Sync Exception: " . $e->getMessage());
        }
    }
}
