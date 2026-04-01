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
    $amount_paid = isset($amountPaid) ? (float)$amountPaid : (isset($booking->amount_paid) ? (float)$booking->amount_paid : (float)($booking->payments ? $booking->payments->sum('amount') : 0));
    $balance_due  = $total_amount - $amount_paid;

    // Payment Type Logic
    $paymentType = $booking->payment_type ?? 'cash_eft';
    $isCard = ($paymentType === 'Card Holder' || $paymentType === 'credit_card');

    $baseAmount = $total_amount;
    $surcharge  = 0.0;

    if ($isCard && $total_amount > 0) {
        $baseAmount = $total_amount / 1.029;
        $surcharge  = $total_amount - $baseAmount;
    }

    // Invoice Number Logic
    $invNo = !empty($booking->invoice_number) ? $booking->invoice_number : str_pad($booking->id, 6, '0', STR_PAD_LEFT);

    // Event date
    $eventDate = !empty($booking->event_date) ? date('d/m/Y', strtotime($booking->event_date)) : '-';
    $eventMidnight = \Carbon\Carbon::parse($booking->event_date)->startOfDay();
    $todayMidnight = now()->startOfDay();
    $daysPast = $eventMidnight->isPast() ? (int) $todayMidnight->diffInDays($eventMidnight) : 0;

    // Time Range
    $start = !empty($booking->start_time) ? date('h:i A', strtotime($booking->start_time)) : '-';
    $end   = (!empty($booking->end_time) && $booking->end_time !== '00:00:00') ? date('h:i A', strtotime($booking->end_time)) : '';
    $timeRange = $end ? ($start . ' - ' . $end) : $start;

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
        $total_amount -= $attraction_subtotal;
        $balance_due -= $attraction_subtotal;
        // Recalculate base/surcharge if card
        if ($isCard && $total_amount > 0) {
            $baseAmount = $total_amount / 1.029;
            $surcharge  = $total_amount - $baseAmount;
        }
    }
 @endphp

<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 40px;
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
            color: #d9534f;
        }

        .invoice-title h1 {
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
        }

        /* Watermark Stamp */
        .overdue-stamp {
            position: absolute;
            top: 350px;
            right: 80px;
            border: 4px solid #d9534f;
            color: #d9534f;
            font-size: 45px;
            font-weight: bold;
            padding: 10px 30px;
            text-transform: uppercase;
            transform: rotate(-15deg);
            opacity: 0.12;
            z-index: -1;
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

        .reminder-box {
            background-color: #fdf2f2;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .reminder-box h3 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 14px;
        }

        /* Items Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #eee;
            border-bottom: 2px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
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
            background-color: #ffeaea;
            font-weight: bold;
            color: #d9534f;
            border-top: 1px solid #000 !important;
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
            height: 60px;
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            color: #555;
        }
    </style>
</head>

<body>

    <div class="overdue-stamp">OVERDUE</div>

    <div class="header">
        <div style="float:left;">
            @if($logoData)
                <img src="{{ $logoData }}" class="logo-img">
            @else
                <h1 style="color:#d6df22; text-shadow: 1px 1px #000; margin:0;">BigFun</h1>
            @endif
        </div>

        <div class="invoice-title">
            <h1>Debt Reminder</h1>
            <span style="font-size: 12px; color: #555;">Invoice #: {{ $invNo }}</span><br>
            <span style="font-size: 12px; color: #555;">Date: {{ date('F d, Y') }}</span>
        </div>
        <div class="clear"></div>
    </div>

    <div class="row">
        <div class="col-left">
            <span style="font-size: 10px; color: #777; text-transform: uppercase; font-weight: bold;">Billed To:</span><br>
            <strong>{{ $booking->customer_organization ?? '' }}</strong><br>
            {{ trim(($booking->customer_first_name ?? 'Valued') . ' ' . ($booking->customer_last_name ?? 'Customer')) }}<br>
            {{ !empty($booking->customer_phone) ? 'Ph: ' . $booking->customer_phone : '' }}
        </div>

        <div class="col-right">
            <span style="font-size: 10px; color: #777; text-transform: uppercase; font-weight: bold;">Event Details:</span><br>
            Booking #{{ $booking->id ?? '' }}<br>
            Event Date: {{ $eventDate }}<br>
            Time: {{ $timeRange }}
        </div>
        <div class="clear"></div>
    </div>

    @if($balance_due > 0)
    <div class="reminder-box">
        <h3>Friendly Reminder: Outstanding Account</h3>
        @if($daysPast > 0)
        <p style="margin: 0; line-height: 1.4;">
            Our records indicate that there is an outstanding balance of <strong>{{ money($balance_due) }}</strong> for your event on {{ $eventDate }}.<br>This invoice is currently <strong>{{ $daysPast }} day(s) past due</strong>.
        </p>
        @else
        <p style="margin: 0; line-height: 1.4;">
            Our records indicate that there is an outstanding balance of <strong>{{ money($balance_due) }}</strong> for your recent event.<br>Please arrange payment as soon as possible.
        </p>
        @endif
        <p style="margin-top: 10px; margin-bottom: 0; font-size: 11px;">
            If you have already made a payment, please disregard this notice or contact us to confirm receipt.
        </p>
    </div>
    @endif

    <div style="margin-bottom: 10px;">
        <strong>Statement of Account:</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th width="{{ $include_attraction_cost ? '40%' : '50%' }}">Item / Service</th>
                <th width="{{ $include_attraction_cost ? '30%' : '40%' }}">Specifications</th>
                <th width="10%">Qty</th>
                @if($include_attraction_cost)
                <th width="20%" style="text-align:right;">Price</th>
                @else
                <th width="0%"></th>
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
                            <span class="bold uppercase">{!! $name !!}</span>
                            @if(!empty($item->is_custom))
                                <br><span class='mini' style='color:#666;'>(Additional Service)</span>
                            @endif
                        </td>
                        <td>
                            @if($item->specification)
                                <div style="font-size: 10px; color: #666; line-height: 1.1;">
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
                        <td>{{ $qty }}</td>
                        @if($include_attraction_cost)
                        <td style="text-align:right;">
                            @if(!$item->is_custom && $item->unit_price > 0)
                                {{ money($item->unit_price * $qty) }}
                            @else
                                -
                            @endif
                        </td>
                        @endif
                    </tr>
                @endforeach
            @else
                <tr><td colspan="{{ $include_attraction_cost ? 4 : 3 }}" style="text-align:center; color:#777;">No item lines found.</td></tr>
            @endif
        </tbody>
    </table>


    <table class="totals-table">
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
            <td>Amount Outstanding:</td>
            <td style="text-align:right;">{{ money($balance_due) }}</td>
        </tr>
    </table>

    <div class="clear"></div>

    <div style="margin-top: 40px; font-size: 11px; line-height: 1.5;">
        <strong>Payment Instructions:</strong><br>
        All payments should be made via Electronic Funds Transfer (EFT) to Big Fun.<br>
        Please quote Invoice No <strong>{{ $invNo }}</strong> as the payment reference.<br>
        If you have any questions or to discuss this account, please phone our office on 1800 244 386.
    </div>

    <div class="footer">
        <strong>Big Fun Queensland</strong> | ABN: 20 956 190 125 <br>
        145 Ferguson Rd Seven Hills, 4170 QLD | 1800 244 386<br>
        This is an automatically generated document.
    </div>

</body>
</html>
