@php
    function money($n) { return '$' . number_format((float)$n, 2); }

    $logoPath = public_path('assets/icon/bgfile.png');
    $logoData = "";
    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    $total_amount = (float)($booking->total_amount ?? 0);
    $amount_paid = isset($amountPaid) ? (float)$amountPaid : (isset($booking->amount_paid) ? (float)$booking->amount_paid : (float)($booking->payments ? $booking->payments->sum('amount') : 0));
    $balance_due  = $total_amount - $amount_paid;
    $quote_no = $booking->id;

    // Dates & Time
    $eventDate = !empty($booking->event_date) ? date('l d F Y', strtotime($booking->event_date)) : '';
    $start = !empty($booking->start_time) ? date('h:i A', strtotime($booking->start_time)) : '-';
    $end   = (!empty($booking->end_time) && $booking->end_time !== '00:00:00') ? date('h:i A', strtotime($booking->end_time)) : '';
    $timeRange = $end ? ($start . ' - ' . $end) : $start;

    // Addresses
    $address = $booking->address_line_1 ?? '';
    if (!empty($booking->suburb)) $address .= ', ' . $booking->suburb;
    if (!empty($booking->postcode)) $address .= ' ' . $booking->postcode;

    // Instructions
    $notes = !empty($booking->notes_delivery) ? $booking->notes_delivery : ($booking->note_delivery ?? '-');

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
    }

    // Extract General and Specific Extras
    $general_extras = !empty($booking->general_extra) ? (is_string($booking->general_extra) ? json_decode($booking->general_extra, true) : $booking->general_extra) : [];
    $specific_extras = !empty($booking->specific_extra) ? (is_string($booking->specific_extra) ? json_decode($booking->specific_extra, true) : $booking->specific_extra) : [];
    $col_span = $include_attraction_cost ? 4 : 3;
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
            color: #000;
            line-height: 1.3;
        }

        .bold {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .uppercase {
            text-transform: uppercase;
        }

        /* Standard Layout Matches Invoice */
        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-img {
            max-height: 70px;
            width: auto;
        }

        .function-box {
            width: 100%;
            border: 2px solid #000;
            margin-bottom: 10px;
        }

        .function-title {
            background-color: #fff;
            font-size: 14px;
            font-weight: bold;
            padding: 5px;
            border-bottom: 1px solid #000;
        }

        .box-table {
            width: 100%;
            border-collapse: collapse;
        }

        .box-table td {
            vertical-align: top;
            padding: 4px;
        }

        .box-left {
            width: 55%;
            border-right: 1px solid #000;
        }

        .box-right {
            width: 45%;
        }

        .finance-row {
            width: 100%;
            margin-bottom: 4px;
            clear: both;
        }

        .fin-label {
            float: left;
            font-weight: bold;
        }

        .fin-val {
            float: right;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-top: 10px;
        }

        .items-table th {
            text-align: left;
            padding: 5px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            background-color: #eee;
        }

        .items-table td {
            padding: 8px 5px;
            vertical-align: top;
            border-bottom: 1px dashed #ccc;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }

        .signature-section {
            margin-top: 30px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        .footer-info {
            margin-top: 15px;
            font-size: 11px;
        }

        /* Print button removed */
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td width="35%">
                {{ date('F d, Y') }}<br><br>
                <strong>Employer:</strong><br>
                @if(!empty($booking->employer_name))
                    {{ $booking->employer_name }}<br>
                @endif
                @if(!empty($booking->customer_organization))
                    {{ $booking->customer_organization }}<br>
                @endif
                @if(!empty($booking->customer_business_phone))
                    {{ $booking->customer_business_phone }}<br>
                @endif
                @if(!empty($booking->customer_abn))
                    ABN: {{ $booking->customer_abn }}<br>
                @endif
            </td>

            <td width="30%" class="text-center">
                @if($logoData)
                    <img src="{{ $logoData }}" class="logo-img">
                @else
                    <h1 style="color: #000;">BigFun</h1>
                @endif
            </td>

            <td width="35%" class="text-right">
                <br>
                <h2 style="margin:0; font-size: 18px;">PURCHASE ORDER</h2>
            </td>
        </tr>
    </table>

    <div class="function-box">
        <div class="function-title">FUNCTION DETAILS</div>
        <table class="box-table">
            <tr>
                <td class="box-left">
                    <table width="100%">
                        <tr>
                            <td width="60" class="bold">Date:</td>
                            <td>{{ $eventDate }}</td>
                        </tr>
                        <tr>
                            <td class="bold">Time:</td>
                            <td>{{ $timeRange }}</td>
                        </tr>
                        <tr>
                            <td class="bold">Venue:</td>
                            <td>{{ $address }}</td>
                        </tr>
                        <tr>
                            <td class="bold">Contact:</td>
                            <td>{{ trim(($booking->customer_first_name ?? '') . ' ' . ($booking->customer_phone ?? '')) }}</td>
                        </tr>
                    </table>
                </td>
                <td class="box-right">
                    <div style="padding: 0 10px;">
                        <div class="finance-row">
                            <span class="fin-label">Total Proposed:</span>
                            <span class="fin-val bold">{{ money($total_amount) }}</span>
                        </div>
                        @if(!empty($booking->deposit_required) && $booking->deposit_required > 0 && $balance_due > 0)
                            <div class="finance-row" style="color: #666; font-size: 10px; margin-top: 5px;">
                                <span class="fin-label">Deposit (if accepted):</span>
                                <span class="fin-val">{{ money($booking->deposit_required) }}</span>
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($notes !== '-')
        <div style="background-color: #f9f9f9; font-size: 10px; padding: 10px; border: 2px solid #000; margin-bottom: 10px;">
            <strong>Notes/Instructions:</strong><br>
            {!! nl2br(e($notes)) !!}
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th width="{{ $include_attraction_cost ? '40%' : '50%' }}">Description / Item</th>
                <th width="{{ $include_attraction_cost ? '30%' : '40%' }}">Specifications</th>
                <th width="10%" class="text-center">Qty</th>
                @if($include_attraction_cost)
                <th width="20%" class="text-right">Price</th>
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
                                <br><span class='mini' style='color:#666;'>(Custom Service)</span>
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

                @php
                    $extra_cost_fallback = (float)($booking->extra_logistics_cost ?? 0);
                    $has_json_extras = !empty($general_extras) || !empty($specific_extras);
                    $show_extras_section = $has_json_extras || !empty($booking->logistics_surfaces) || (!$has_json_extras && $extra_cost_fallback > 0);
                @endphp

                @if($show_extras_section)
                    {{-- General Logistic Extras --}}
                    @if(!empty($general_extras) || !empty($booking->logistics_surfaces) || (!$has_json_extras && $extra_cost_fallback > 0))
                        <tr>
                            <td colspan="{{ $col_span }}" style="padding: 10px 8px 5px 8px; border-bottom: 1px solid #000; background-color: #f9f9f9;">
                                <span class="bold" style="font-size: 11px;">General Logistic Extras:</span>
                            </td>
                        </tr>
                        @if(!empty($booking->logistics_surfaces))
                            <tr style="border-bottom: 1px dashed #eee;">
                                <td @if($include_attraction_cost) colspan="2" @endif style="padding: 5px 8px 5px 15px; font-size: 10px;">
                                    &bull; Logistics Surface: {{ $booking->logistics_surfaces }}
                                </td>
                                <td @if(!$include_attraction_cost) colspan="2" @endif class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                                @if($include_attraction_cost)
                                <td class="text-right" style="font-size: 10px;">-</td>
                                @endif
                            </tr>
                        @endif
                        @if(is_array($general_extras))
                            @foreach($general_extras as $label => $cost)
                                <tr style="border-bottom: 1px dashed #eee;">
                                    <td @if($include_attraction_cost) colspan="2" @endif style="padding: 5px 8px 5px 15px; font-size: 10px;">
                                        &bull; {{ $label }}
                                    </td>
                                    <td @if(!$include_attraction_cost) colspan="2" @endif class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                                    @if($include_attraction_cost)
                                    <td class="text-right" style="font-size: 10px;">
                                        {{ ($include_attraction_cost && (float)$cost > 0) ? money($cost) : '-' }}
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    @endif

                    {{-- Specific Extras --}}
                    @if(!empty($specific_extras))
                        <tr>
                            <td colspan="{{ $col_span }}" style="padding: 10px 8px 5px 8px; border-bottom: 1px solid #000; background-color: #f9f9f9;">
                                <span class="bold" style="font-size: 11px;">Specific Extras:</span>
                            </td>
                        </tr>
                        @foreach($specific_extras as $label => $cost)
                            <tr style="border-bottom: 1px dashed #eee;">
                                <td @if($include_attraction_cost) colspan="2" @endif style="padding: 5px 8px 5px 15px; font-size: 10px;">
                                    &bull; {{ $label }}
                                </td>
                                <td @if(!$include_attraction_cost) colspan="2" @endif class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                                @if($include_attraction_cost)
                                <td class="text-right" style="font-size: 10px;">
                                    {{ ($include_attraction_cost && (float)$cost > 0) ? money($cost) : '-' }}
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                @endif
            @else
                <tr><td colspan="{{ $col_span }}" class="text-center" style="padding: 20px;">No items listed.</td></tr>
            @endif
        </tbody>
    </table>


    <div style="margin-top: 30px; font-size: 11px;">
        To accept this quotation/proposal, please sign below and return this document, or provide your official company Purchase Order number.
    </div>

    <div class="signature-section">
        <div style="float:left; width: 50%;">
            Signed __________________________________
            <div style="font-size: 9px; margin-top: 2px;">(for & on behalf of employer/organisation)</div>
        </div>
        <div style="float:right; width: 30%;">
            Date ________________________
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer-info">
        <div style="float:left; width: 50%;">
            <span class="bold">Big Fun Queensland</span><br>
            ABN: 20 956 190 125<br>
            145 Ferguson Rd Seven Hills, 4170 QLD<br>
            Email: bigfun.qld.au@gmail.com
        </div>
        <div style="float:right; width: 45%;">
            <span class="bold">Payment Details (For Reference):</span><br>
            Bank: Big Fun<br>
            BSB: 067872 | Acc: 19935785<br>
            Ref: {!! $booking->customer_organization ?? 'Payment' !!}
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>
