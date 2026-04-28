@php
    function money($n) { return '$' . number_format((float)$n, 2); }

    $logoPath = public_path('assets/icon/bgfile.png');
    $logoData = "";
    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    // --- Attraction Logic ---
    $items_with_details = \App\Models\BookingItem::where('booking_id', $booking->id)
            ->leftJoin('products', function($join) {
                $join->on('booking_items.item_name', '=', 'products.name')
                     ->where('booking_items.is_custom', '=', 0);
            })
            ->selectRaw('booking_items.item_name, booking_items.is_custom, booking_items.qty, products.specification')
            ->get();

    // Time safety
    $start = !empty($booking->start_time) ? date('h:i A', strtotime($booking->start_time)) : '-';
    $end   = (!empty($booking->end_time) && $booking->end_time !== '00:00:00') ? date('h:i A', strtotime($booking->end_time)) : '';
    $stdDurations = ['1 Hour','2 Hours','3 Hours','4 Hours','5 Hours','6 Hours','7 Hours','8 Hours','9 Hours','10 Hours','11 Hours','12 Hours','Overnight'];
    $isCustomDuration = false;
    $durationPrice = 0;
    if (!empty($booking->duration) && !in_array($booking->duration, $stdDurations)) {
        $timeRange = $booking->duration;
        $isCustomDuration = true;
        
        // Fetch price if exists
        $durObj = DB::table('duration_prices')->where('label', $booking->duration)->first();
        if ($durObj && (float)$durObj->price > 0) {
            $durationPrice = (float)$durObj->price;
        }
    }

    // Special instructions
    $specialInstructions = '';
    if (!empty($booking->notes_delivery)) {
        $specialInstructions = $booking->notes_delivery;
    } elseif (!empty($booking->note_delivery)) {
        $specialInstructions = $booking->note_delivery;
    } else {
        $specialInstructions = '-';
    }

    // Extract General and Specific Extras
    $general_extras = !empty($booking->general_extra) ? (is_string($booking->general_extra) ? json_decode($booking->general_extra, true) : $booking->general_extra) : [];
    $specific_extras = !empty($booking->specific_extra) ? (is_string($booking->specific_extra) ? json_decode($booking->specific_extra, true) : $booking->specific_extra) : [];

    $col_span = 3;
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

        .header-table {
            width: 100%;
            margin-bottom: 10px;
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
            width: 100%; /* Expanded for delivery receipt */
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-top: -2px;
        }

        .items-table th {
            text-align: left;
            padding: 5px;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        .items-table td {
            padding: 8px 5px;
            vertical-align: top;
            border-bottom: 1px dashed #ccc;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .mini {
            font-size: 8px;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td width="30%">
                {{ date('F d, Y') }}<br><br>
                <strong>Employer:</strong><br>
                @if(!empty($booking->employer_name))
                    {{ $booking->employer_name }}<br>
                @endif
                @if(!empty($booking->customer_organization))
                    {{ $booking->customer_organization }}<br>
                @endif
                @if(!empty($booking->customer_abn))
                    ABN: {{ $booking->customer_abn }}<br>
                @endif
                @if(!empty($booking->customer_business_phone))
                    {{ $booking->customer_business_phone }}<br>
                @endif
            </td>

            <td width="40%" class="text-center">
                @if($logoData)
                    <img src="{{ $logoData }}" class="logo-img">
                @else
                    <h1 style="color: #d6df22; text-shadow: 1px 1px #000;">BigFun</h1>
                @endif
            </td>

            <td width="30%" class="text-right">
                <br>
                <h2 class="uppercase" style="margin:0; color: #333;">DELIVERY RECEIPT</h2>
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
                            <td width="80" class="bold">Date:</td>
                            <td>{{ !empty($booking->event_date) ? date('l d F Y', strtotime($booking->event_date)) : '' }}</td>
                        </tr>
                        <tr>
                            <td class="bold">{{ $isCustomDuration ? 'Duration:' : 'Time:' }}</td>
                            <td>
                                {{ $timeRange }}
                                @if($isCustomDuration && $durationPrice > 0)
                                    ({{ money($durationPrice) }})
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="bold">Venue:</td>
                            <td>
                                {{ $booking->address_line_1 ?? '' }},
                                {{ $booking->suburb ?? '' }}
                                {{ $booking->postcode ?? '' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="bold">Contact:</td>
                            <td>
                                {{ $booking->customer_first_name ?? '' }},
                                {{ $booking->customer_phone ?? '' }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="60%">Ride / Equipment:</th>
                <th width="30%">Specifications:</th>
                <th width="10%" class="text-center">Qty:</th>
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
                    </tr>
                @endforeach
            @endif

            @php
                $has_json_extras = !empty($general_extras) || !empty($specific_extras);
                $show_extras_section = $has_json_extras || !empty($booking->logistics_surfaces);
            @endphp

            @if($show_extras_section)
                @if(!empty($general_extras) || !empty($booking->logistics_surfaces))
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
                        </tr>
                    @endif
                    @if(is_array($general_extras))
                        @foreach($general_extras as $label => $cost)
                            <tr style="border-bottom: 1px dashed #eee;">
                                <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                    &bull; {{ $label }}
                                </td>
                                <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                            </tr>
                        @endforeach
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
                        </tr>
                    @endforeach
                @endif
            @endif

            <tr>
                <td colspan="{{ $col_span }}" style="border-top: 2px solid #000; padding: 5px;">
                    <span class="bold">Special Instructions:</span><br>
                    {!! nl2br(e($specialInstructions)) !!}
                </td>
            </tr>
        </tbody>
    </table>

    <div style="position: fixed; bottom: 0; width: 100%; border-top: 1px solid #000; padding-top: 10px;">
        <div style="float:left; width: 100%;">
            <span class="bold" style="font-size: 11px;">Big Fun Queensland</span><br>
            <span style="font-size: 9px; line-height: 1.4;">
                ABN: 20 956 190 125 | 145 Ferguson Rd Seven Hills, 4170 QLD<br>
                Email: bigfun.qld.au@gmail.com
            </span>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>
