<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    /**
     * Syncs booking data to the Google Spreadsheet via Web App Webhook.
     */
    public function sync($bookingId, $isNew = false)
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

            // Calculate Payment Data
            $amountPaid = DB::table('booking_payments')
                ->where('booking_id', $bookingId)
                ->sum('amount') ?: 0;
            
            $totalAmount = (float) $booking->total_amount;
            $balanceDue = max(0, $totalAmount - $amountPaid);

            // Calculate Attraction Costing
            $attractionCost = DB::table('booking_items')
                ->leftJoin('products', 'booking_items.item_name', '=', 'products.name')
                ->where('booking_id', $bookingId)
                ->where('booking_items.is_custom', 0)
                ->selectRaw('SUM(booking_items.qty * IFNULL(products.price, 0)) as total')
                ->value('total') ?? 0;


            // Parse Extras JSON into a readable string
            $extras = json_decode($booking->extras_json, true) ?: [];
            $extraString = "";
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

            $user = auth()->user();
            $updatedBy = $user ? ($user->first_name . ' (' . $user->role . ')') : 'System';

            $payload = [
                'invoice_number'        => $booking->invoice_number,
                'status'                => $booking->status,
                'payment_status'        => $booking->payment_status,
                'booked_by'             => $booking->booked_by ?? 'System',
                'updated_by'            => $updatedBy,
                'event_date'            => \Carbon\Carbon::parse($booking->event_date)->format('Y-m-d'),
                'original_event_date'   => \Carbon\Carbon::parse($booking->original_event_date ?? $booking->event_date)->format('Y-m-d'),
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
                'total_amount'          => number_format($totalAmount, 2, '.', ''),
                'amount_paid'           => number_format($amountPaid, 2, '.', ''),
                'balance_due'           => number_format($balanceDue, 2, '.', ''),
                'is_debtor'             => $balanceDue > 0 ? 'YES' : 'NO',
                'surcharge_amount'      => number_format($booking->surcharge_amount, 2, '.', ''),
                'deposit_required'      => number_format($booking->deposit_required, 2, '.', ''),
                'payment_type'          => $booking->payment_type,
                'payment_reference'     => $booking->payment_reference,
                'card_network'          => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') ? $booking->card_network : 'N/A',
                'card_masked'           => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') 
                                            ? '**** **** ' . substr(str_replace(' ', '', $booking->card_number ?? ''), -8, 4) . ' ' . substr(str_replace(' ', '', $booking->card_number ?? ''), -4)
                                            : 'N/A',
                'card_cvv_expiry'       => ($booking->payment_type === 'Card Holder' || $booking->payment_type === 'Card' || $booking->payment_type === 'credit_card') 
                                            ? 'Exp: **/** | CVV: *** (Secure)' 
                                            : 'N/A',
                'manual_delivery_cost'  => number_format($booking->delivery_cost, 2, '.', ''),
                'manual_duration_cost'  => number_format($booking->duration_cost, 2, '.', ''),
                'notes_delivery'        => $booking->notes_delivery,
                'notes_customer'        => $booking->notes_customer,
                'extra_configs'         => $extraString,
                'attraction_cost'       => number_format($attractionCost, 2, '.', ''),
                'is_deleted'            => ($booking->status === 'Cancelled' || $booking->status === 'Deleted') ? 'YES' : 'NO',
                'is_new_booking'        => $isNew ? 'YES' : 'NO',
                'synced_at'             => now()->toDateTimeString(),
            ];

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
