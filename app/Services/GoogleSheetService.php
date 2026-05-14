<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class GoogleSheetService
{
    /**
     * Strips newlines and collapses whitespace in a string so spreadsheet cells stay clean.
     */
    private static function clean(?string $value): string
    {
        if ($value === null || $value === '') return '';
        // Replace all newline/carriage return variants with a space, then collapse multiple spaces
        return trim(preg_replace('/\s+/', ' ', str_replace(["\r\n", "\r", "\n", "\t"], ' ', $value)));
    }

    /**
     * Syncs booking data to the Google Spreadsheet via Web App Webhook.
     */
    public static function sync(int|string $bookingId, bool $isNew = false)
    {
        try {
            // Check if sync is enabled in settings
            if (!config('services.google.sync_enabled', true)) {
                Log::info("Google Sheet Sync is disabled in system settings.");
                return;
            }

            $webhookUrl = config('services.google.sheet_webhook');
            if (empty($webhookUrl)) return;

            $booking = DB::table('bookings')->where('id', $bookingId)->first();
            if (!$booking) return;

            $items = DB::table('booking_items')
                ->where('booking_id', $bookingId)
                ->pluck('item_name')
                ->toArray();

            // Calculate Payment Data
            $amountPaid = DB::table('booking_payments')
                ->where('booking_id', $bookingId)
                ->sum('amount') ?: 0;
            
            $totalAmount = (float) $booking->total_amount;
            $balanceDue = max(0, $totalAmount - $amountPaid);

            // Calculate Attraction Costing (Sum from booking_items directly)
            $attractionCost = DB::table('booking_items')
                ->where('booking_id', $bookingId)
                ->sum(DB::raw('qty * item_price')) ?? 0;

            // Fetch All Payments for Reference String
            $payments = DB::table('booking_payments')
                ->where('booking_id', $bookingId)
                ->orderBy('id', 'asc')
                ->get();
            
            $paymentRefString = "";
            $payment1Amount = 0;
            foreach ($payments as $index => $payment) {
                if ($index === 0) {
                    $payment1Amount = $payment->amount;
                }
                $ref = $payment->reference ?: "Gen-" . str_pad($payment->id, 4, '0', STR_PAD_LEFT);
                $amt = number_format((float)$payment->amount, 2);
                $num = $index + 1;
                $paymentRefString .= "Payment {$num}: {$ref} (\${$amt}) | ";
            }
            $paymentRefString = rtrim($paymentRefString, " | ");
            if (empty($paymentRefString)) {
                $paymentRefString = $booking->payment_reference ?? '';
            }


            // Parse Extras JSON into a readable string
            $extras = json_decode($booking->extras_json, true) ?: [];
            $extraString = "";
            
            // --- RECOVERY LOGIC FOR SPREADSHEET ---
            // If extras_json is missing known logistics items from booking_items (like Generator or Dropbox), add them
            $lowercaseItems = array_map(fn($it) => strtolower(trim($it)), $items);

            // 1. Sync Addons
            $addonsLookup = DB::table('category_addons')->get();
            foreach ($addonsLookup as $a) {
                $addonKey = 'add_' . $a->id;
                if (!isset($extras[$addonKey]) && in_array(strtolower(trim($a->addon_label)), $lowercaseItems)) {
                    $extras[$addonKey] = "1";
                }
            }

            // 2. Sync Questions
            $questionsLookup = DB::table('product_extras')->get();
            foreach ($questionsLookup as $q) {
                $qKey = 'q_' . $q->id;
                if (!isset($extras[$qKey]) && in_array(strtolower(trim($q->question_text)), $lowercaseItems)) {
                    $extras[$qKey] = $q->yes_price . '|yes';
                }
            }

            // 3. Sync Dropdowns
            $dropdownOptions = DB::table('dropdown_options')->get();
            foreach ($dropdownOptions as $opt) {
                $ddKey = 'dd_' . $opt->dropdown_id;
                if (!isset($extras[$ddKey]) && in_array(strtolower(trim($opt->option_label)), $lowercaseItems)) {
                    $extras[$ddKey] = $opt->id;
                }
            }

            // Fallback to legacy fields if extraString still feels light
            if (empty($extras)) {
                $genExt = json_decode($booking->general_extra ?? '[]', true) ?: [];
                $specExt = json_decode($booking->specific_extra ?? '[]', true) ?: [];
                $allLegacy = array_merge($genExt, $specExt);
                foreach ($allLegacy as $lab => $price) {
                    $extraString .= "$lab: $" . number_format($price, 2) . " | ";
                }
            }

            foreach ($extras as $key => $val) {
                if ($val === '' || $val === null) continue;

                if (str_starts_with($key, 'dd_')) {
                    $ddId = str_replace('dd_', '', $key);
                    $ddLabel = DB::table('product_dropdowns')->where('id', $ddId)->value('label');
                    $optLabel = DB::table('dropdown_options')->where('id', $val)->value('option_label') ?? $val;
                    
                    if ($ddLabel) {
                        $extraString .= "$ddLabel: $optLabel | ";
                    }
                } elseif (str_starts_with($key, 'add_')) {
                    if ((string)$val === '1') {
                        $addonId = str_replace('add_', '', $key);
                        $addonLabel = DB::table('category_addons')->where('id', $addonId)->value('addon_label') ?? "Addon";
                        $extraString .= "$addonLabel: Yes | ";
                    }
                } elseif (str_starts_with($key, 'q_')) {
                    $qId = str_replace('q_', '', $key);
                    $qLabel = DB::table('product_extras')->where('id', $qId)->value('question_text');
                    $parts = explode('|', (string)$val);
                    $answer = end($parts); // 'yes' or 'no'
                    
                    if ($qLabel) {
                        $extraString .= "$qLabel: " . ucwords($answer) . " | ";
                    }
                } else {
                    $cleanKey = ucwords(str_replace(['q_', '_'], ['', ' '], $key));
                    $extraString .= "$cleanKey: $val | ";
                }
            }
            $extraString = rtrim($extraString, " | ");

            $user = Auth::user();
            $updatedBy = $user ? ($user->first_name . ' (' . $user->role . ')') : 'System';

            $payload = [
                'invoice_number'        => self::clean($booking->invoice_number),
                'status'                => self::clean($booking->status),
                'payment_status'        => self::clean($booking->payment_status),
                'booked_by'             => self::clean($booking->booked_by ?? 'System'),
                'updated_by'            => self::clean($updatedBy),
                'event_date'            => \Carbon\Carbon::parse($booking->event_date)->format('Y-m-d'),
                'original_event_date'   => \Carbon\Carbon::parse($booking->original_event_date ?? $booking->event_date)->format('Y-m-d'),
                'start_time'            => self::clean($booking->start_time),
                'end_time'              => self::clean($booking->end_time),
                'duration'              => self::clean($booking->duration),
                'operational_hours'     => self::clean($booking->operational_hours),
                'items'                 => self::clean(implode(', ', $items)),
                'customer_name'         => self::clean(trim($booking->customer_first_name . ' ' . $booking->customer_last_name)),
                'customer_email'        => self::clean($booking->customer_email),
                'customer_phone'        => self::clean($booking->customer_phone),
                'customer_organization' => self::clean($booking->customer_organization),
                'customer_abn'          => self::clean($booking->customer_abn),
                'employer_name'         => self::clean($booking->employer_name),
                'business_phone'        => self::clean($booking->customer_business_phone),
                'business_address'      => self::clean($booking->business_address),
                'event_type'            => self::clean($booking->event_type),
                'address'               => self::clean($booking->address_line_1),
                'suburb'                => self::clean($booking->suburb),
                'state_postcode'        => self::clean($booking->state . ' ' . $booking->postcode),
                'delivery_area'         => self::clean($booking->delivery_area),
                'lead_deliverer'        => self::clean($booking->lead_deliverer),
                'lead_operator'         => self::clean($booking->lead_operator),
                'expected_people'       => self::clean($booking->expected_people),
                'total_amount'          => number_format($totalAmount, 2, '.', ''),
                'amount_paid'           => number_format($amountPaid, 2, '.', ''),
                'balance_due'           => number_format($balanceDue, 2, '.', ''),
                'is_debtor'             => ($balanceDue > 0 && \Carbon\Carbon::parse($booking->event_date)->startOfDay()->isBefore(\Carbon\Carbon::today())) ? 'YES' : 'NO',
                'surcharge_amount'      => number_format($booking->surcharge_amount, 2, '.', ''),
                'deposit_required'      => number_format($booking->deposit_required, 2, '.', ''),
                'payment_type'          => self::clean($booking->payment_type),
                'payment_1'             => number_format($payment1Amount, 2, '.', ''),
                'payment_reference'     => self::clean($paymentRefString),
                'card_network'          => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') ? self::clean($booking->card_network) : 'N/A',
                'card_masked'           => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') 
                                            ? '**** **** ' . substr(str_replace(' ', '', $booking->card_number ?? ''), -8, 4) . ' ' . substr(str_replace(' ', '', $booking->card_number ?? ''), -4)
                                            : 'N/A',
                'card_cvv_expiry'       => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') 
                                            ? 'Exp: **/** | CVV: *** (Secure)' 
                                            : 'N/A',
                'manual_delivery_cost'  => number_format($booking->delivery_cost, 2, '.', ''),
                'manual_duration_cost'  => number_format($booking->duration_cost, 2, '.', ''),
                'notes_delivery'        => self::clean($booking->notes_delivery),
                'notes_customer'        => self::clean($booking->notes_customer),
                'extra_configs'         => self::clean($extraString),
                'attraction_cost'       => number_format($attractionCost, 2, '.', ''),
                'is_deleted'            => ($booking->status === 'Cancelled' || $booking->status === 'Deleted') ? 'YES' : 'NO',
                'is_new_booking'        => $isNew ? 'YES' : 'NO',
                'synced_at'             => now()->toDateTimeString(),
            ];

            // Log Payload for debugging
            Log::info("Google Sheet Sync Payload for Invoice: {$booking->invoice_number}", $payload);

            // Add a strict timeout to prevent hanging the main request
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::error("Google Sheet Sync Failed for Invoice: {$booking->invoice_number}", [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Google Sheet Sync Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }
}
