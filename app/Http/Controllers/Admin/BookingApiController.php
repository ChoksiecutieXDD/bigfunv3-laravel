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
use App\Models\Booking;
use App\Models\BookingPayment;

class BookingApiController extends Controller
{
    // --- 1. REAL-TIME AVAILABILITY CHECK ---
    public function checkAvailability(Request $request)
    {
        try {
            $date = $request->query('date');
            $invoice = $request->query('invoice');

            if (empty($date)) {
                return response()->json(['status' => 'success', 'products' => []]);
            }

            $categories = [];
            $resCat = DB::table('product_categories')->select('category_name', 'daily_limit')->get();
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

            $resProd = DB::table('products')->where('is_active', 1)->select('name', 'total_quantity', 'daily_limit', 'counts_against', 'category')->get();
            foreach ($resProd as $row) {
                $cleanName = strtolower(trim($row->name));
                $stock = (int)$row->total_quantity;
                $item_limit = (int)$row->daily_limit;

                $master_inventory[$cleanName] = [
                    'total' => $stock,
                    'limit' => $item_limit,
                    'booked' => 0,
                    'left' => $stock
                ];

                if ($item_limit > 0) {
                    $master_inventory[$cleanName]['left'] = min($stock, $item_limit);
                }

                $target = !empty($row->counts_against) ? $row->counts_against : $row->category;
                $product_targets[$cleanName] = strtolower(trim($target));
            }

            $res = DB::table('booking_items as bi')
                ->join('bookings as b', 'bi.booking_id', '=', 'b.id')
                ->where('b.event_date', $date)
                ->when($invoice, function ($q) use ($invoice) {
                    return $q->where('b.invoice_number', '!=', $invoice);
                })
                ->when($request->query('booking_id'), function ($q) use ($request) {
                    return $q->where('b.id', '!=', $request->query('booking_id'));
                })
                ->where(function ($q) {
                    $q->whereNotIn('b.status', ['Cancelled', 'Draft'])
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

            // --- EXTRAS COUNTING ---
            $addon_cat_map = [];
            foreach (DB::table('category_addons')->get() as $a) {
                $addon_cat_map[$a->id] = strtolower(trim($a->counts_against ?? $a->category_target ?? ''));
            }
            $dropdown_cat_map = [];
            foreach (DB::table('product_dropdowns')->get() as $d) {
                $dropdown_cat_map[$d->id] = strtolower(trim($d->counts_against ?? $d->category_target ?? ''));
            }
            $question_cat_map = [];
            foreach (DB::table('product_extras')->get() as $q) {
                $question_cat_map[$q->id] = strtolower(trim($q->counts_against ?? $q->category_target ?? ''));
            }

            $other_bookings_json = DB::table('bookings')
                ->where('event_date', $date)
                ->when($invoice, function ($q) use ($invoice) {
                    return $q->where('invoice_number', '!=', $invoice);
                })
                ->when($request->query('booking_id'), function ($q) use ($request) {
                    return $q->where('id', '!=', $request->query('booking_id'));
                })
                ->where(function ($q) {
                    $q->whereNotIn('status', ['Cancelled', 'Draft'])
                        ->orWhere(function ($q2) {
                            $q2->where('status', 'Draft')
                                ->where('created_at', '>=', Carbon::now()->subMinutes(20));
                        });
                })
                ->pluck('extras_json');

            foreach ($other_bookings_json as $json) {
                if (!$json) continue;
                $extras = json_decode($json, true);
                if (!$extras) continue;
                foreach ($extras as $key => $val) {
                    $targetCat = null;
                    if (strncmp($key, 'add_', 4) === 0) {
                        if ((string)$val === '1' || $val === true) {
                            $id = str_replace('add_', '', $key);
                            $targetCat = $addon_cat_map[$id] ?? null;
                        }
                    } elseif (strncmp($key, 'dd_', 3) === 0 && !empty($val) && $val !== '0') {
                        $id = str_replace('dd_', '', $key);
                        $targetCat = $dropdown_cat_map[$id] ?? null;
                    } elseif (strncmp($key, 'q_', 2) === 0 && !empty($val) && $val !== '0') {
                        // Check if it's a "no" answer (e.g. q_123|no)
                        if (is_string($val) && str_ends_with($val, '|no')) continue;

                        $id = str_replace('q_', '', $key);
                        $targetCat = $question_cat_map[$id] ?? null;
                    }

                    if ($targetCat && isset($categories[$targetCat])) {
                        $categories[$targetCat]['booked']++;
                    }
                }
            }

            // Recalculate 'left' for categories after counting extras
            foreach ($categories as $cat => &$data) {
                $data['left'] = ($data['limit'] > 0) ? max(0, $data['limit'] - $data['booked']) : 9999;
            }
            unset($data);

            // 5. Finalize Availability (Compare Stock vs Daily Item Limit vs Category Limit)
            foreach ($master_inventory as $prodName => &$prodData) {
                $booked = $prodData['booked'];
                $stock = $prodData['total'];
                $iLimit = $prodData['limit'];

                // a) Start with remaining physical stock
                $left = max(0, $stock - $booked);

                // b) Apply Item daily limit if set
                if ($iLimit > 0) {
                    $limit_left = max(0, $iLimit - $booked);
                    if ($limit_left < $left) $left = $limit_left;
                }

                // c) Apply Category limit if applicable
                if (isset($product_targets[$prodName])) {
                    $targetCat = $product_targets[$prodName];
                    if (isset($categories[$targetCat]) && $categories[$targetCat]['limit'] > 0) {
                        $catLeft = $categories[$targetCat]['left'];
                        if ($catLeft < $left) $left = $catLeft;
                    }
                }

                $prodData['left'] = $left;
                // Add the actual daily_limit to the response for visual logic on frontend
                $prodData['daily_limit'] = $iLimit;
            }
            unset($prodData);

            return response()->json([
                'status' => 'success',
                'products' => $master_inventory,
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            Log::error("Availability Check API Error: " . $e->getMessage(), [
                'date' => $request->query('date'),
                'exception' => $e
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // --- 2. MAIN POST HANDLER ---
    public function handler(Request $request)
    {
        $action = $request->input('action');
        try {
            // Fetch Dynamic Daily Limit (Baseline of 5, or max of category limits)
            $DAILY_TOTAL_LIMIT = max(7, DB::table('product_categories')->max('daily_limit') ?: 0);
            
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

                    $booking_id = $request->input('booking_id');
                    $invoice_number = $request->input('invoice');
                    $form_token = $request->input('token');

                    // 1. Aggressive Cleanup: Remove ALL expired selections from the entire database
                    DB::table('booking_selections')->where('expires_at', '<', now())->delete();

                    // 2. Fetch existing booking counts (Confirmed/Draft)
                    $res = DB::table('bookings')
                        ->select('event_date', DB::raw('COUNT(*) as cnt'))
                        ->whereBetween('event_date', [$start, $end])
                        ->where(function ($q) use ($booking_id) {
                            $q->whereNotIn('status', ['Cancelled'])
                                ->where(function ($q2) use ($booking_id) {
                                    $q2->where('status', '!=', 'Draft')
                                        ->orWhere('created_at', '>=', now()->subMinutes(20))
                                        ->orWhere('id', '=', $booking_id);
                                });
                        })
                        ->groupBy('event_date')
                        ->get();

                    $map = [];
                    foreach ($res as $row) {
                        $map[$row->event_date] = (int)$row->cnt;
                    }

                        // 3. Fetch Live Selections (Locks)
                        $selections = DB::table('booking_selections')
                            ->whereBetween('event_date', [$start, $end])
                            ->where('expires_at', '>=', now())
                            ->get();

                        // Pre-fetch invoices that are already counted in $map to avoid double counting
                        $countedInvoices = DB::table('bookings')
                            ->whereBetween('event_date', [$start, $end])
                            ->where(function ($q) use ($booking_id) {
                                $q->whereNotIn('status', ['Cancelled'])
                                    ->where(function ($q2) use ($booking_id) {
                                        $q2->where('status', '!=', 'Draft')
                                            ->orWhere('created_at', '>=', now()->subMinutes(20))
                                            ->orWhere('id', '=', $booking_id);
                                    });
                            })
                            ->pluck('invoice_number')
                            ->toArray();

                        $live_badges = [];
                        $seenSelections = []; // Track to avoid double counting same session
                        foreach ($selections as $sel) {
                            $d = $sel->event_date;
                            
                            $isMe = false;
                            if ($form_token && $sel->form_token == $form_token) {
                                $isMe = true;
                            } elseif (!$form_token && Auth::check() && Auth::id() == $sel->user_id) {
                                $isMe = true;
                            }
                            
                            if ($invoice_number && $sel->invoice_number == $invoice_number && $sel->invoice_number != '') {
                                $isMe = true;
                            }

                            // Count EVERY unique selection towards the slot decrease
                            // We use a combination of date, token and invoice to ensure accuracy per day
                            $selKey = $d . '_' . ($sel->form_token ?: ($sel->user_id . '_' . $sel->invoice_number));
                            if (!in_array($sel->invoice_number, $countedInvoices) && !isset($seenSelections[$selKey])) {
                                if (!isset($map[$d])) $map[$d] = 0;
                                $map[$d]++;
                                $seenSelections[$selKey] = true;
                            }

                            if (!isset($live_badges[$d])) $live_badges[$d] = [];
                            $live_badges[$d][] = [
                                'name' => $sel->user_name,
                                'role' => $sel->user_role,
                                'is_me' => $isMe
                            ];
                        }

                    return response()->json([
                        'success' => true, 
                        'status' => 'success', 
                        'daily_limit' => $DAILY_TOTAL_LIMIT, 
                        'counts' => $map,
                        'live_badges' => $live_badges
                    ]);

                case 'select_calendar_date':
                    $date = $request->input('date');
                    $invoice = $request->input('invoice');
                    $token = $request->input('token');

                    if (Auth::check()) {
                        $user = Auth::user();
                        
                        // Strict Session Cleanup: Only remove the selection that belongs to THIS specific tab (token).
                        // This ensures that an Admin and a Supervisor (even if sharing an account)
                        // can both have their own unique badges visible at the same time.
                        if ($token) {
                            DB::table('booking_selections')->where('form_token', $token)->delete();
                        } else {
                            // Fallback for non-tokenized requests (if any)
                            DB::table('booking_selections')
                                ->where('user_id', $user->id)
                                ->where('invoice_number', $invoice)
                                ->delete();
                        }
                        
                        $firstName = explode(' ', $user->first_name)[0];
                        
                        // Insert new selection only if date is provided
                        if (!empty($date)) {
                            DB::table('booking_selections')->insert([
                                'user_id' => $user->id,
                                'user_name' => $firstName, 
                                'user_role' => $user->role,
                                'event_date' => $date,
                                'invoice_number' => $invoice,
                                'form_token' => $token,
                                'expires_at' => now()->addMinutes(2),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        // NEW: If booking_id is provided (usually from sendBeacon cleanup), delete the draft too
                        $bid = $request->input('booking_id');
                        if ($bid) {
                            DB::table('bookings')->where('id', $bid)->where('status', 'Draft')->delete();
                        }
                    }
                    return response()->json(['success' => true]);

                case 'check_duplicates':
                    $date = $request->input('date');
                    $firstName = trim($request->input('first_name', ''));
                    $lastName = trim($request->input('last_name', ''));
                    $currentInvoice = $request->input('current_invoice', '');
                    $warnings = [];

                    if ($date && $firstName && $lastName) {
                        $cnt = DB::table('bookings')
                            ->where('event_date', $date)
                            ->where('customer_first_name', $firstName)
                            ->where('customer_last_name', $lastName)
                            ->where('invoice_number', '!=', $currentInvoice)
                            ->where(function ($q) {
                                $q->whereNotIn('status', ['Cancelled', 'Draft'])
                                    ->orWhere(function ($q2) {
                                        $q2->where('status', 'Draft')->where('created_at', '>=', now()->subMinutes(20));
                                    });
                            })->count();
                        if ($cnt > 0) {
                            $warnings[] = "Customer <strong>$firstName $lastName</strong> already has a booking/draft scheduled for <strong>$date</strong>.";
                        }
                    }
                    return response()->json(['success' => true, 'status' => 'success', 'warnings' => $warnings]);

                case 'save_full_booking':
                    DB::beginTransaction();
                    Log::info("Finalizing Booking save", ['invoice' => $request->input('invoice_number'), 'all_inputs' => $request->except(['card_number', 'card_cvv'])]);

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
                        $suffix = ($i === 1) ? '' : "_$i";
                        $inputName = "delivery_attachment$suffix";
                        
                        if ($request->hasFile($inputName)) {
                            $totalSize += $request->file($inputName)->getSize();
                        } elseif (!empty($existing_files[$i-1])) {
                            // Existing file being kept
                            $fileName = $existing_files[$i-1];
                            $path1 = public_path('uploads/' . $fileName);
                            $path2 = storage_path('app/public/uploads/' . $fileName);
                            
                            if (File::exists($path1)) {
                                $totalSize += File::size($path1);
                            } elseif (File::exists($path2)) {
                                $totalSize += File::size($path2);
                            }
                        }
                    }

                    if ($totalSize > 5 * 1024 * 1024) {
                        return response()->json(['success' => false, 'status' => 'error', 'message' => 'Total size of all attachments must not exceed 5MB.']);
                    }

                    // Handle File Uploads
                    for ($i = 1; $i <= 5; $i++) {
                        $suffix = ($i === 1) ? '' : "_$i";
                        $inputName = "delivery_attachment$suffix"; 
                        
                        if ($request->hasFile($inputName)) {
                            $file = $request->file($inputName);
                            $destinationPath = storage_path('app/public/uploads');
                            if (!File::exists($destinationPath)) {
                                File::makeDirectory($destinationPath, 0755, true);
                            }
                            $fileName = $file->hashName();
                            $file->move($destinationPath, $fileName);
                            $existing_files[$i-1] = $fileName;
                            Log::info("File $i Uploaded", ['slot' => $i, 'name' => $fileName]);
                        }
                    }

                    $payment_status = $request->input('payment_status', 'Pending');
                    $db_status = 'Confirmed'; 

                    $data = [
                        'event_date' => $request->input('event_date'),
                        'start_time' => !empty($request->input('start_time')) ? $request->input('start_time') : '00:00:00',
                        'end_time'   => !empty($request->input('end_time'))   ? $request->input('end_time')   : '23:59:59',
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

                    // Save JSON Extras (Raw and Formatted)
                    $raw_extras = [];
                    $general_extra = [];
                    $specific_extra = [];

                    $allAddons = DB::table('category_addons')->get()->keyBy('id');
                    $allDropdowns = DB::table('product_dropdowns')->get()->keyBy('id');
                    $allOptions = DB::table('dropdown_options')->get()->keyBy('id');
                    $allQuestions = DB::table('product_extras')->get()->keyBy('id');

                    foreach ($request->all() as $key => $val) {
                        if (str_starts_with($key, 'dd_') || str_starts_with($key, 'add_') || str_starts_with($key, 'q_')) {
                            // Raw storage
                            if (str_starts_with($key, 'add_')) {
                                if ((string)$val === '1') $raw_extras[$key] = $val;
                            } elseif ($val !== '') {
                                $raw_extras[$key] = $val;
                            }

                            // Formatted for Overview/Sync
                            if (str_starts_with($key, 'add_') && (string)$val === '1') {
                                $id = str_replace('add_', '', $key);
                                if ($addon = $allAddons->get($id)) {
                                    if ($addon->category_target === 'General Logistics') {
                                        $general_extra[$addon->addon_label] = (float)$addon->addon_price;
                                    } else {
                                        $specific_extra[$addon->category_target . ': ' . $addon->addon_label] = (float)$addon->addon_price;
                                    }
                                }
                            }
                            if (str_starts_with($key, 'dd_') && $val !== '') {
                                $ddId = str_replace('dd_', '', $key);
                                if (($dd = $allDropdowns->get($ddId)) && ($opt = $allOptions->get($val))) {
                                    $label = $dd->label . ' - ' . $opt->option_label;
                                    if ($dd->category_target === 'General Logistics') {
                                        $general_extra[$label] = (float)$opt->option_price;
                                    } else {
                                        $specific_extra[$dd->category_target . ': ' . $label] = (float)$opt->option_price;
                                    }
                                }
                            }
                            if (str_starts_with($key, 'q_') && $val !== '') {
                                $qId = str_replace('q_', '', $key);
                                if ($q = $allQuestions->get($qId)) {
                                    $parts = explode('|', $val);
                                    $price = (float)($parts[0] ?? 0);
                                    $answer = $parts[1] ?? 'yes';
                                    $label = $q->question_text . ' (' . ucfirst($answer) . ')';
                                    if ($q->category_target === 'General Logistics') {
                                        $general_extra[$label] = $price;
                                    } else {
                                        $specific_extra[$q->category_target . ': ' . $label] = $price;
                                    }
                                }
                            }
                        }
                    }

                    DB::table('bookings')->where('id', $booking_id)->update([
                        'extras_json' => json_encode($raw_extras),
                        'general_extra' => json_encode($general_extra),
                        'specific_extra' => json_encode($specific_extra)
                    ]);

                    // --- 6. AUTO-CREATE PAYMENT FOR "DEPOSIT PAID" STATUS ---
                    if ($payment_status === 'Deposit Paid') {
                        $deposit_amount = (float)$request->input('deposit_amount', 0);
                        
                        // Check current payment total to prevent duplicates on manual update
                        $existing_paid = DB::table('booking_payments')
                            ->where('booking_id', $booking_id)
                            ->sum('amount');

                        if ($existing_paid < $deposit_amount) {
                            $remainder = $deposit_amount - $existing_paid;
                            
                            DB::table('booking_payments')->insert([
                                'booking_id' => $booking_id,
                                'amount' => $remainder,
                                'payment_method' => $request->input('payment_method') ?: $request->input('payment_type', 'EFT'),
                                'payment_type' => 'Deposit Capture',
                                'payment_date' => now()->format('Y-m-d'),
                                'reference' => $request->input('payment_reference'),
                                'notes' => $request->input('payment_notes') ?: 'Auto-recorded during booking creation/update as Deposit Paid.',
                                'card_number' => $request->input('payment_type') === 'Card Holder' ? $request->input('card_number') : null,
                                'card_expiry' => $request->input('payment_type') === 'Card Holder' ? $request->input('card_expiry') : null,
                                'card_cvv' => $request->input('payment_type') === 'Card Holder' ? $request->input('card_cvv') : null,
                                'card_network' => $request->input('payment_type') === 'Card Holder' ? $request->input('card_network') : null,
                            ]);
                        }
                    }

                    // --- 7. UPDATE CACHED TOTALS ---
                    DB::commit();

                    // Refresh financials (Includes Payment Status logic) using the Model
                    $model = \App\Models\Booking::find($booking_id);
                    if ($model) {
                        $model->syncFinancials();
                    }

                    // Trigger Google Sheet Sync
                    $this->syncToGoogleSheet($booking_id, !$is_update);

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
    protected function syncToGoogleSheet($bookingId, $isNew = false)
    {
        app(\App\Services\GoogleSheetService::class)->sync($bookingId, $isNew);
    }
}
