@php
    function money($n) { return '$' . number_format((float)$n, 2); }

    $logoPath = public_path('assets/icon/bgfile.png');
    $logoData = "";
    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    // --- FINANCIALS ---
    $total_amount = (float)($booking->total_amount ?? 0);
    // Handle payments which might be a relationship or missing
    $amount_paid = isset($amount_paid) ? (float)$amount_paid : (isset($booking->amount_paid) ? (float)$booking->amount_paid : (float)($booking->payments ? $booking->payments->sum('amount') : 0));
    $balance_due  = $total_amount - $amount_paid;

    // Payment Type Logic
    $paymentType = strtolower($booking->payment_type ?? 'eft');
    $isCard = in_array($paymentType, ['card holder', 'credit_card', 'card_holder']);
    
    $paymentMethodLabel = 'EFT';
    if ($paymentType === 'cash') {
        $paymentMethodLabel = 'Cash';
    } elseif ($isCard) {
        $paymentMethodLabel = 'Credit Card';
    }

    $baseAmount = $total_amount;
    $surcharge  = 0.0;

    if ($isCard && $total_amount > 0) {
        $baseAmount = $total_amount / 1.029;
        $surcharge  = $total_amount - $baseAmount;
    }

    // PAID stamp check (If balance is roughly 0)
    $isFullyPaid = ($balance_due <= 0.01);

    // --- Attraction Costing Logic ---
    $include_attraction_cost = $booking->include_attraction_cost ?? true;
    
    // Fetch Items with Specs and Prices
    $items_with_details = \App\Models\BookingItem::where('booking_id', $booking->id)
            ->leftJoin('products', function($join) {
                $join->on('booking_items.item_name', '=', 'products.name')
                     ->where('booking_items.is_custom', '=', 0);
            })
            ->selectRaw('booking_items.item_name, booking_items.is_custom, booking_items.qty, products.specification, products.price as unit_price')
            ->get();

    $attraction_subtotal = 0;
    foreach($items_with_details as $det) {
        if (!$det->is_custom) {
            $attraction_subtotal += ($det->qty * ($det->unit_price ?? 0));
        }
    }

    if (!$include_attraction_cost) {
        // We no longer subtract from the total to ensure accuracy
        // But we handle the display subtotal in the table instead
        $isFullyPaid = ($balance_due <= 0.01);
    }


    // Receipt Number Logic (Invoice No + -REC suffix)
    $invNo = !empty($booking->invoice_number) ? $booking->invoice_number : str_pad($booking->id, 6, '0', STR_PAD_LEFT);
    $receiptNo = $invNo . '-REC';

    // Extract General and Specific Extras
    $general_extras = !empty($booking->general_extra) ? (is_string($booking->general_extra) ? json_decode($booking->general_extra, true) : $booking->general_extra) : [];
    $specific_extras = !empty($booking->specific_extra) ? (is_string($booking->specific_extra) ? json_decode($booking->specific_extra, true) : $booking->specific_extra) : [];

    // Event date
    $eventDate = !empty($booking->event_date) ? date('d/m/Y', strtotime($booking->event_date)) : '-';

    // FIXED: TIME RANGE (Replaced special dash to fix "???")
    $start = !empty($booking->start_time) ? date('h:i A', strtotime($booking->start_time)) : '-';
    $end   = (!empty($booking->end_time) && $booking->end_time !== '00:00:00') ? date('h:i A', strtotime($booking->end_time)) : '';
    $timeRange = $end ? ($start . ' - ' . $end) : $start;
    $col_span = $include_attraction_cost ? 4 : 3;
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 60px 40px 80px 40px;
        }

        body {
            font-family: Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .logo-img {
            max-height: 60px;
            width: auto;
        }

        .invoice-title {
            float: right;
            text-align: right;
        }

        .invoice-title h1 {
            margin: 0;
            font-size: 22px;
            color: #000;
            text-transform: uppercase;
        }

        /* Watermark Stamp styles */
        .stamp {
            position: absolute;
            top: 5px;
            right: 20px;
            border: 4px solid #28a745;
            color: #28a745;
            font-size: 40px;
            font-weight: bold;
            padding: 5px 15px;
            text-transform: uppercase;
            transform: rotate(-10deg);
            opacity: 0.3;
            z-index: 9999;
        }
        .stamp-partial {
            border-color: #ffc107;
            color: #ffc107;
        }
        .stamp-debt {
            border-color: #d9534f;
            color: #d9534f;
        }

        .row {
            width: 100%;
            clear: both;
            margin-bottom: 20px;
        }

        .col-left {
            float: left;
            width: 50%;
        }

        .col-right {
            float: right;
            width: 45%;
            text-align: right;
        }

        /* Items Table matching Invoice */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #eee;
            border-bottom: 1px solid #000;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px 5px;
            vertical-align: top;
            border-bottom: 1px dashed #ccc;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }

        /* Totals Table */
        .totals-table {
            width: 55%;
            float: right;
            border: 1px solid #000;
        }

        .totals-table td {
            border: none;
            padding: 6px 10px;
        }

        .total-row {
            background-color: #eee;
            font-weight: bold;
            border-top: 1px solid #000 !important;
            /* Force border on total row */
        }

        .note {
            font-size: 10px;
            color: #666;
            margin-top: 6px;
            line-height: 1.25;
        }

        .mini {
            font-size: 10px;
            color: #999;
        }

        .bold {
            font-weight: bold;
        }

        .clear {
            clear: both;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            color: #555;
        }
    </style>
</head>

<body>


    <div class="header">
        <div style="float:left;">
            @if($logoData)
                <img src="{{ $logoData }}" class="logo-img">
            @else
                <h1 style="color:#d6df22; text-shadow: 1px 1px #000; margin:0;">BigFun</h1>
            @endif
        </div>

        <div class="invoice-title">
            <h1>Official Receipt</h1>
            <span style="font-size: 12px; color: #555;">Receipt #: {{ $receiptNo }}</span><br>
            <span style="font-size: 12px; color: #555;">Date: {{ date('F d, Y') }}</span>
        </div>
        <div class="clear"></div>
    </div>

    <div class="row">
        <div class="col-left">
            <span style="font-size: 10px; color: #777; text-transform: uppercase; font-weight: bold;">Received From:</span><br>
            <strong>{{ $booking->customer_organization ?? '' }}</strong><br>
            {{ trim(($booking->customer_first_name ?? 'Valued') . ' ' . ($booking->customer_last_name ?? 'Customer')) }}<br>
            {{ $booking->customer_phone ?? '' }}
        </div>

        <div class="col-right">
            <span style="font-size: 10px; color: #777; text-transform: uppercase; font-weight: bold;">Payment For:</span><br>
            Booking #{{ $booking->id ?? '' }}<br>
            Event Date: {{ $eventDate }}<br>

            <div class="note" style="text-align:right;">
                <strong>Method:</strong> {{ $paymentMethodLabel }}
            </div>
        </div>

        <div class="clear"></div>
    </div>

    <div style="margin-top: 20px; margin-bottom: 20px;">
        <h3>Payment Summary</h3>
        <p>This document confirms that we have received payment for the following items:</p>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="{{ $include_attraction_cost ? '40%' : '50%' }}">Ride / Equipment:</th>
                <th width="{{ $include_attraction_cost ? '30%' : '40%' }}">Specifications:</th>
                <th width="10%" class="text-center">Qty:</th>
                @if($include_attraction_cost)
                <th width="20%" class="text-right">Price/Cost:</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @if(count($items_with_details) > 0)
                @foreach($items_with_details as $item)
                    @php
                        $qty = (int)($item->qty ?? 1);
                        $name = $item->item_name ?? '';
                    @endphp
                    <tr>
                        <td>
                            <span class="bold">{!! $name !!}</span>
                            @if(!empty($item->is_custom))
                                <br><span class='mini' style='color:#666;'>(Additional Service)</span>
                            @endif
                        </td>
                        <td>
                            @if($item->specification)
                                <div class="mini" style="color: #666; line-height: 1.2;">
                                    @foreach(explode("\n", str_replace(["\r\n", "\r"], "\n", $item->specification)) as $line)
                                        @if(trim($line))
                                            &bull; {{ trim($line) }}<br>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">{{ $qty }}</td>
                        @if($include_attraction_cost)
                        <td class="text-right">
                            @if(!$item->is_custom && $item->unit_price > 0)
                                {{ money($item->unit_price * $qty) }}
                            @else
                                -
                            @endif
                        </td>
                        @endif
                    </tr>
                @endforeach

                @if(!$include_attraction_cost && $attraction_subtotal > 0)
                    <tr style="background-color: #fafafa;">
                        <td colspan="{{ $col_span }}" style="padding: 10px 5px; border-bottom: 2px solid #000;">
                            <span class="bold">Attraction Package Service Fee:</span>
                        </td>
                    </tr>
                @endif


                @php
                    $extra_cost_fallback = (float)($booking->extra_logistics_cost ?? 0);
                    $has_json_extras = !empty($general_extras) || !empty($specific_extras);
                    $show_extras_section = $has_json_extras || !empty($booking->logistics_surfaces) || (!$has_json_extras && $extra_cost_fallback > 0);
                @endphp

                @if($show_extras_section)
                    @if(!empty($general_extras) || !empty($booking->logistics_surfaces) || (!$has_json_extras && $extra_cost_fallback > 0))
                        <tr>
                            <td colspan="{{ $col_span }}" style="padding: 10px 5px 5px 5px; border-bottom: 1px solid #000;">
                                <span class="bold" style="font-size: 11px;">General Logistic Extras:</span>
                            </td>
                        </tr>
                    @if(!empty($booking->logistics_surfaces))
                        <tr style="border-bottom: 1px dashed #eee;">
                            <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                &bull; Logistics Surface: {{ $booking->logistics_surfaces }}
                            </td>
                            <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                            @if($include_attraction_cost)
                            <td class="text-right" style="padding: 5px 5px 5px 15px; font-size: 10px;">-</td>
                            @endif
                        </tr>
                    @endif
                    @if(is_array($general_extras))
                        @foreach($general_extras as $label => $cost)
                            <tr style="border-bottom: 1px dashed #eee;">
                                <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                    &bull; {{ $label }}
                                </td>
                                <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                                @if($include_attraction_cost)
                                <td class="text-right" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                    {{ ($include_attraction_cost && (float)$cost > 0) ? money($cost) : '-' }}
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                    @if(!$has_json_extras && $extra_cost_fallback > 0)
                        <tr style="border-bottom: 1px dashed #eee;">
                            <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                &bull; General Logistics Charge
                            </td>
                            <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                            @if($include_attraction_cost)
                            <td class="text-right" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                {{ $include_attraction_cost ? money($extra_cost_fallback) : '-' }}
                            </td>
                            @endif
                        </tr>
                    @endif
                    @endif

                    @if(!empty($specific_extras))
                        <tr>
                            <td colspan="{{ $col_span }}" style="padding: 10px 5px 5px 5px; border-bottom: 1px solid #000;">
                                <span class="bold" style="font-size: 11px;">Specific Extras:</span>
                            </td>
                        </tr>
                        @foreach($specific_extras as $label => $cost)
                            <tr style="border-bottom: 1px dashed #eee;">
                                <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                    &bull; {{ $label }}
                                </td>
                                <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                                @if($include_attraction_cost)
                                <td class="text-right" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                    {{ ($include_attraction_cost && (float)$cost > 0) ? money($cost) : '-' }}
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                @endif
            @else
                <tr><td colspan="{{ $include_attraction_cost ? 4 : 3 }}" style="text-align:center; color:#777;">No item lines found.</td></tr>
            @endif
        </tbody>
    </table>


    <div style="position: relative; float: right; width: 55%; margin-top: 10px;">
        <table class="totals-table" style="width: 100%; float: none;">
            @if($isCard && $surcharge > 0)
                <tr>
                    <td>Base Amount:</td>
                    <td style="text-align:right;">{{ money($baseAmount) }}</td>
                </tr>
                <tr>
                    <td>Card Fee (+2.9%):</td>
                    <td style="text-align:right;">{{ money($surcharge) }}</td>
                </tr>
                <tr>
                    <td><strong>Total Invoice Amount:</strong></td>
                    <td style="text-align:right;"><strong>{{ money($total_amount) }}</strong></td>
                </tr>
                <tr>
                    <td colspan="2" class="note" style="border-top:1px solid #ddd;">
                        Total includes card surcharge.
                    </td>
                </tr>
            @else
                <tr>
                    <td>Total Invoice Amount:</td>
                    <td style="text-align:right;">{{ money($total_amount) }}</td>
                </tr>
            @endif
    
            <tr>
                <td>Total Paid to Date:</td>
                <td style="text-align:right;">{{ money($amount_paid) }}</td>
            </tr>
    
            <tr class="total-row">
                <td>Remaining Balance:</td>
                <td style="text-align:right;">{{ money($balance_due) }}</td>
            </tr>
        </table>
        
        @if($balance_due <= 0.01)
            <div class="stamp">PAID</div>
        @elseif($amount_paid > 0)
            <div class="stamp stamp-partial">PARTIAL</div>
        @else
            <div class="stamp stamp-debt">DEBT</div>
        @endif
    </div>

    <div class="clear"></div>

    <div style="margin-top: 40px; text-align: center; font-style: italic; color: #777;">
        Thank you for your business!
    </div>

    <div class="footer">
        <strong>Big Fun Queensland</strong> | ABN: 20 956 190 125 <br>
        145 Ferguson Rd Seven Hills, 4170 QLD<br>
        This is a computer-generated receipt and requires no signature.
    </div>

</body>
</html>
