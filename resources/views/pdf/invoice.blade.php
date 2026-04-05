@php
    function money($n) { return '$' . number_format((float)$n, 2); }

    $logoPath = public_path('assets/icon/bgfile.png');
    $logoData = "";
    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    $total_amount = (float)$booking->total_amount;
    // Handle payments which might be a relationship or missing
    $amount_paid = isset($amount_paid) ? (float)$amount_paid : (isset($booking->amount_paid) ? (float)$booking->amount_paid : (float)($booking->payments ? $booking->payments->sum('amount') : 0));
    $deposit_required = (float)$booking->deposit_required;

    $balance_due = $total_amount - $amount_paid;

    // Fix Negative Balance
    $is_overpaid = $balance_due < 0;
    $display_balance = $is_overpaid ? 0.00 : $balance_due;

    // Check if fully paid (to trigger the Method row visibility)
    $is_fully_paid = ($amount_paid > 0 && $balance_due <= 0);

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
        $display_balance = $balance_due < 0 ? 0.00 : $balance_due;
        $is_fully_paid = ($amount_paid > 0 && $balance_due <= 0);
    }


    // Dynamic Warning Logic
    $header_warning = "";
    $box_warning = "";
    $warning_color = "red";

    if ($amount_paid < $deposit_required) {
        $header_warning = "DEPOSIT NOW DUE";
        $box_warning = "YOUR BOOKING HAS BEEN<br>ACCEPTED - PLEASE GUARANTEE<br>YOUR BOOKING BY PAYING THE DEPOSIT";
    } elseif ($balance_due > 0) {
        $header_warning = "BALANCE DUE";
        $box_warning = "PLEASE PAY THE REMAINING BALANCE PRIOR TO YOUR EVENT";
    } else {
        $header_warning = "PAID IN FULL";
        $box_warning = "THANK YOU FOR YOUR PAYMENT";
        $warning_color = "green"; // Turn green if paid
    }

    // Check Payment Type logic
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
        $baseAmount = $total_amount / 1.029;       // reverse out 2.9%
        $surcharge  = $total_amount - $baseAmount; // fee portion
    }

    // Installment info
    $installment_plan = trim((string)($booking->installation_plan ?? ''));
    $manual_terms     = trim((string)($booking->manual_install_terms ?? ''));

    // Time safety
    $start = !empty($booking->start_time) ? date('h:i A', strtotime($booking->start_time)) : '-';
    $end   = (!empty($booking->end_time) && $booking->end_time !== '00:00:00') ? date('h:i A', strtotime($booking->end_time)) : '';
    $timeRange = $end ? ($start . ' - ' . $end) : $start;

    // Special instructions
    $specialInstructions = '';
    if (!empty($booking->notes_delivery)) {
        $specialInstructions = $booking->notes_delivery;
    } elseif (!empty($booking->note_delivery)) {
        $specialInstructions = $booking->note_delivery;
    } else {
        $specialInstructions = '-';
    }

    // Invoice Number Logic
    $displayInvoiceNo = !empty($booking->invoice_number) ? $booking->invoice_number : $booking->id;

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

        .red {
            color: #FF0000;
        }

        .green {
            color: #10b981;
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

        .page-break {
            page-break-before: always;
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

        .balance-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            padding-bottom: 2px;
            margin-top: 4px;
        }

        .small-note {
            font-size: 8px;
            color: #333;
            margin-top: 6px;
            line-height: 1.25;
        }

        .mini {
            font-size: 8px;
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

        .terms-section {
            font-size: 9px;
            margin-top: 15px;
        }

        .terms-section ol {
            padding-left: 20px;
            margin-top: 5px;
        }

        .terms-section li {
            margin-bottom: 2px;
        }

        .cols-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cols-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
            font-size: 8px;
            text-align: justify;
        }

        .term-block {
            margin-bottom: 8px;
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
                @if(!empty($booking->customer_business_phone))
                    {{ $booking->customer_business_phone }}<br>
                @endif
                @if(!empty($booking->customer_abn))
                    ABN: {{ $booking->customer_abn }}<br>
                @endif
                {{ trim(($booking->customer_first_name ?? '') . ' ' . ($booking->customer_last_name ?? '')) }}<br>
                {{ $booking->customer_phone ?? '' }}
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
                <strong>Tax Invoice {{ $displayInvoiceNo }}</strong><br><br>
                <h2 class="{{ $warning_color }} uppercase" style="margin:0;">{{ $header_warning }}</h2>
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
                            <td>{{ !empty($booking->event_date) ? date('l d F Y', strtotime($booking->event_date)) : '' }}</td>
                        </tr>
                        <tr>
                            <td class="bold">Time:</td>
                            <td>{{ $timeRange }}</td>
                        </tr>
                        <tr>
                            <td class="bold">Venue:</td>
                            <td>
                                {{ $booking->address_line_1 ?? '' }},
                                {{ $booking->suburb ?? '' }}
                                {{ $booking->postcode ?? '' }}
                            </td>
                        </tr>
                        @if(!empty($booking->region))
                            <tr>
                                <td class="bold">Region:</td>
                                <td>{{ $booking->region }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="bold">Contact:</td>
                            <td>
                                {{ $booking->customer_first_name ?? '' }},
                                {{ $booking->customer_phone ?? '' }}
                            </td>
                        </tr>
                        @if(!empty($installment_plan) && strcasecmp($installment_plan, 'Not Set') !== 0)
                            <tr>
                                <td class="bold">Installment:</td>
                                <td>
                                    {{ $installment_plan }}
                                    @if(strcasecmp($installment_plan, 'Manual') === 0 && $manual_terms !== '')
                                        <div class="mini" style="margin-top:4px;">
                                            <span class="bold">Terms:</span> {{ $manual_terms }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        {{-- Method removed as per request --}}
                    </table>
                </td>

                <td class="box-right">
                    <div style="padding: 0 10px;">

                        @if($isCard && $surcharge > 0)
                            <div class="finance-row">
                                <span class="fin-label">Base Amount</span>
                                <span class="fin-val">{{ money($baseAmount) }}</span>
                            </div>
                            <div class="finance-row">
                                <span class="fin-label">Card Fee (+2.9%)</span>
                                <span class="fin-val">{{ money($surcharge) }}</span>
                            </div>
                            <div class="finance-row">
                                <span class="fin-label">Invoice Total*</span>
                                <span class="fin-val bold">{{ money($total_amount) }}</span>
                            </div>
                            <div class="small-note">
                                *Invoice Total includes card surcharge.
                            </div>
                        @else
                            <div class="finance-row">
                                <span class="fin-label">Invoice Total*</span>
                                <span class="fin-val">{{ money($total_amount) }}</span>
                            </div>
                        @endif

                        <div class="finance-row">
                            <span class="fin-label">Paid/Deposited</span>
                            <span class="fin-val">{{ money($amount_paid) }}</span>
                        </div>

                        <div class="finance-row balance-line">
                            <span class="fin-label">Balance Due</span>
                            <span class="fin-val bold">
                                {{ money($display_balance) }}
                            </span>
                        </div>

                        @if($deposit_required > 0)
                            <div class="finance-row">
                                <span class="fin-label">Deposit Required</span>
                                <span class="fin-val">{{ money($deposit_required) }}</span>
                            </div>
                        @endif
                        @if($is_overpaid)
                            <div class="finance-row text-right" style="font-size: 9px; color: green; font-weight: bold;">
                                (Overpaid / Credit: {{ money(abs($balance_due)) }})
                            </div>
                        @endif

                        <div class="text-center {{ $warning_color }} bold" style="font-size: 9px; margin-top: 10px; clear: both; width: 100%;">
                            {!! $box_warning !!}
                        </div>

                    </div>
                </td>
            </tr>
        </table>
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

                {{-- Attraction Package Row for hidden prices --}}
                @if(!$include_attraction_cost && $attraction_subtotal > 0)
                    <tr style="background-color: #fafafa;">
                        <td colspan="{{ $col_span }}" style="padding: 10px 5px; border-bottom: 2px solid #000;">
                            <span class="bold">Attraction Package Service Fee:</span>
                        </td>
                    </tr>
                @endif
            @endif


            @php
                $extra_cost_fallback = (float)($booking->extra_logistics_cost ?? 0);
                $has_json_extras = !empty($general_extras) || !empty($specific_extras);
                $show_extras_section = $has_json_extras || !empty($booking->logistics_surfaces) || (!$has_json_extras && $extra_cost_fallback > 0);
            @endphp

            @if($show_extras_section)
                @if(!empty($general_extras) || !empty($booking->logistics_surfaces) || (!$has_json_extras && $extra_cost_fallback > 0))
                    @php
                        $genHeaderStyle = 'style="padding: 10px 5px 5px 5px; border-bottom: 1px solid #000;"';
                    @endphp
                    <tr>
                        <td colspan="{{ $col_span }}" {!! $genHeaderStyle !!}>
                            <span class="bold" style="font-size: 11px;">General Logistic Extras:</span>
                        </td>
                    </tr>
                    @if(!empty($booking->logistics_surfaces))
                        <tr style="border-bottom: 1px dashed #ddd;">
                            <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                &bull; Logistics Surface: {{ $booking->logistics_surfaces }}
                            </td>
                            <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                            @if($include_attraction_cost)
                            <td class="text-right" style="padding: 5px; font-size: 10px;">-</td>
                            @endif
                        </tr>
                    @endif
                    @if(is_array($general_extras))
                        @foreach($general_extras as $label => $cost)
                            <tr style="border-bottom: 1px dashed #ddd;">
                                <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                    &bull; {{ $label }}
                                </td>
                                <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                                @if($include_attraction_cost)
                                <td class="text-right" style="padding: 5px; font-size: 10px;">
                                    {{ ($include_attraction_cost && (float)$cost > 0) ? money($cost) : '-' }}
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                    @if(!$has_json_extras && $extra_cost_fallback > 0)
                        <tr style="border-bottom: 1px dashed #ddd;">
                            <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                &bull; General Logistics Charge
                            </td>
                            <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                            <td class="text-right" style="padding: 5px; font-size: 10px;">
                                {{ $include_attraction_cost ? money($extra_cost_fallback) : '-' }}
                            </td>
                        </tr>
                    @endif
                @endif

                @if(!empty($specific_extras))
                    @php
                        $specStyleStr = "padding: 10px 5px 5px 5px; border-bottom: 1px solid #000;";
                        if (!empty($general_extras) || !empty($booking->logistics_surfaces) || (!$has_json_extras && $extra_cost_fallback > 0)) {
                            $specStyleStr .= " margin-top: 5px;";
                        }
                        $specHeaderStyle = 'style="' . $specStyleStr . '"';
                    @endphp
                    <tr>
                        <td colspan="{{ $col_span }}" {!! $specHeaderStyle !!}>
                            <span class="bold" style="font-size: 11px;">Specific Extras:</span>
                        </td>
                    </tr>

                    @foreach($specific_extras as $label => $cost)
                        <tr style="border-bottom: 1px dashed #ddd;">
                            <td colspan="2" style="padding: 5px 5px 5px 15px; font-size: 10px;">
                                &bull; {{ $label }}
                            </td>
                            <td class="text-center" style="padding: 5px; font-size: 10px;">1</td>
                            @if($include_attraction_cost)
                            <td class="text-right" style="padding: 5px; font-size: 10px;">
                                {{ ($include_attraction_cost && (float)$cost > 0) ? money($cost) : '-' }}
                            </td>
                            @endif
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

    <div class="text-center bold" style="margin-top: 10px; font-size: 10px;">
        Thank you for booking with us - please carefully check the booking details and conditions of this booking, sign and send (mail or fax) back to us.
    </div>

    <div class="terms-section">
        <span class="bold">Booking Conditions:</span>
        <ol>
            <li>Your booking is now confirmed and guaranteed. Your deposit (at least 50%) is now due now. See below for payment options.</li>
            <li>Bookings made within 7 days of the event date must be deposited within 24 hours.</li>
            <li>Full payment is due no later than the day preceding the event.</li>
            <li>Our inflatable rides (& Sumo suits) may not be suitable for children 12 years and younger. If you need to cater for this age group please contact us.</li>
            <li>The employer shall accept full responsibility for providing a safe environment and for the safety of the performer, equipment and the audience during the performance.</li>
            <li>An area sufficient to house the rides is required. If power is not available within 20 meters you may need to book a generator (power not required for some items). Big Fun accepts no responsibility if ample electricity has not been provided on the day of the event.</li>
            <li>The employer agrees to provide an adequate number of attendants to maintain crowd control.</li>
            <li>The employer shall indemnify the performer for any loss, damage or injury, actual or consequential, of whatever kind during the subsistence of this agreement. Please be aware self-supervised rides will not be covered by our insurance policy. Water rides are not covered.</li>
            <li>The Employer may terminate this agreement by delivery of written notice to Big Fun not later than 21 days before the date of the performance. If such notice is not given full payment is due. All deposits are non-refundable.</li>
        </ol>
        <div style="font-style: italic;">I have read, understood and accept the booking and performance conditions above and overleaf.</div>
    </div>

    <div style="margin-top: 15px; border-bottom: 1px solid #000; padding-bottom: 10px;">
        <div style="float:left; width: 50%;">
            Signed __________________________________
            <div style="font-size: 9px; margin-top: 2px;">(for & on behalf of employer/organisation/venue/promoter)</div>
        </div>
        <div style="float:right; width: 30%;">
            Date ________________________
        </div>
        <div style="clear: both;"></div>
    </div>

    <div style="margin-top: 15px;">
        <div style="float:left; width: 50%;">
            <span class="bold" style="font-size: 11px;">Big Fun Queensland</span><br>
            ABN: 20 956 190 125<br>
            145 Ferguson Rd Seven Hills, 4170 QLD<br>
            Email: bigfun.qld.au@gmail.com
        </div>
        <div style="float:right; width: 45%;">
            <span class="bold">Payment Details:</span><br>
            <span class="bold">Big Fun</span><br>
            BSB 067872 Account Number 19935785<br>
            Please include your invoice number or your full name as reference.<br>
            Please pay 50% for the deposit and 50% on the week of the event.
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="page-break"></div>

    <div class="text-center bold uppercase" style="margin-bottom: 15px; font-size: 12px;">Performance Conditions</div>

    <table class="cols-table">
        <tr>
            <td>
                <div class="term-block">
                    <span class="bold">1. DEFINITIONS</span><br>
                    "Employer" means the person, firm or corporation conducting the event and any officer employed or agent of the Employer who signs this or any other document on the employer's behalf may be taken by the Owner as having full authority to sign on the Owners behalf.
                    "Performer" means the entertainer for the event more accurately described overleaf including all tools, equipment and accessories supplied therewith.
                    "Owner" means Nabco P/L and/or it franchises agents or managers.
                </div>

                <div class="term-block">
                    <span class="bold">2. PERFORMANCE CHARGES</span><br>
                    (a) The performance charge is the rate agreed between the Employer and the Owner more specifically defined on invoice
                    (b) The Owner reserves the right at any time and without notice to revise the charges quoted, invoiced or advertised.
                    (c) A deposit for the performance must be received within 1 week of a booking or, if booked within 7 days of the event 24 hours after booking.
                    (d) If a security bond is paid, it is only partly or fully refunded if the equipment is in the same condition as delivered. If the inflatable is dirty it will incur a $95 cleaning fee automatically.
                </div>

                <div class="term-block">
                    <span class="bold">3. CANCELLATIONS</span><br>
                    (a) In the event that the Employer cancels the performance prior to the function, no refund will be made.
                    (b) In the event of unfavorable weather conditions, the employer should make alternative undercover arrangements.
                </div>

                <div class="term-block">
                    <span class="bold">4. BREAKDOWNS</span><br>
                    Where the Employer notifies the Owner of an equipment failure associated with the performance that renders the performance inoperative or part thereof, charges shall not be payable during such times, or at the owners discretion extra time in lieu will be made, provided that the unusable condition of the Equipment is not attributable wholly or partly to the employer's negligence, misuse or breach of contract.
                </div>

                <div class="term-block">
                    <span class="bold">5. CONDITIONS OF THE PERFORMANCE AND INSPECTION PRIVILEGE</span><br>
                    (a) The Employer accepts and employs the Entertainer and their equipment on an "as is" basis. The employer acknowledges and declares that he has examined the performers Equipment together with all devices and materials used to connect the Equipment to the employer's power supply, if any, and the Employer declares that it is in a secure and operative condition.
                    (b) The owner reserves the right to refuse the performance on the day, if an unfavorable situation could cause damage or negligence to the performer.
                </div>

                <div class="term-block">
                    <span class="bold">6. OPERATOR</span><br>
                    (a) The performer shall be under the direction and control of the Employer and shall for those purposes be deemed to be the servant or agent of the Employer who alone shall be responsible for all claims whatsoever arising in connection with the performance by the operator or any third party. If no operator is provided the employer will nominate a responsible adult to enforce the rules and guidelines of the inflatable.
                </div>

                <div class="term-block">
                    <span class="bold">7. EMPLOYER'S OBLIGATIONS</span><br>
                    The Employer shall;<br>
                    (a) check all details of the booking contained overleaf, including date and time of booking, and notify the owner immediately if details are incorrect. No refunds will be provided in the event that details are incorrect as the owner does not confirm bookings and the employer accepts full responsibility for any inaccuracies of the booking if they have failed to advise the owner.<br>
                    (b) pay all performance charges the day proceeding the performance, and where applicable, all delivery and collection charges. Cheques will only be accepted with prior arrangements.<br>
                    (c) accept full responsibility and liability for the safety of the performer and their Equipment if any and indemnify the Owner or performer for all loss, theft or damage to the Equipment, including loss of sales and future business, however caused and without limiting the generality of the forgoing, whether or not such loss, theft or damage is attributable to any abandonment of any Equipment, negligence, failure or omission of the Employer.<br>
                    (d) in the case where a ride is to be used for minors (under 18 years) it is the employer's obligation to gain approval from the parents or guardian of that child to use the ride. Neither Nabco P/L, nor any of its employees, agents or contractors are responsible for gaining this permission as our "risk waiver" is for persons over the age of 18 years.<br>
                    (e) have in place the correct insurance to cover the performance, if any is indeed applicable, given that the Owner has $10 million public liability cover.<br>
                    (f) indemnify and hold the Owner and/or Performer harmless against all claims, actions, suits, demands, costs, and expenses, including all legal costs and expenses in any way arising out of the performance.<br>
                    (g) provide an adequate number of attendants to ensure acceptable crowd control and to prevent the entry of undesirables for the safety of the performer and the venue.<br>
                    (h) provide a 10 amp power supply where necessary.<br>
                    (i) To display and advise all rules and safety guidelines to patrons.
                </div>
            </td>

            <td>
                <div class="term-block">
                    <span class="bold">8. LATE DELIVERY</span><br>
                    The Owner hearby undertakes that it will use its best endeavors to start the performance by the specified time but in any event the Owner shall not by liable to the Employer for late delivery, non-delivery or any loss or damage occasioned to the Employer as a result of such late or non-delivery.
                </div>

                <div class="term-block">
                    <span class="bold">9. DAMAGES</span><br>
                    The Owner, or his agents, accept no responsibility for damages caused to furnishings; secured or unsecured, electrical; including fusion, burnout or surges and the like including water or gas pipes, Telstra, or recirculation systems whilst setting up or operating the performance, whether known or unknown.
                </div>

                <div class="term-block">
                    <span class="bold">10. EXCLUSIONS</span><br>
                    To the extent that the commonwealth, state and territorial laws permit:
                    (a) all conditions, terms and warranties, which are not expressly contained in this agreement, are hereby excluded.
                    (b) any warranty, condition, description or representation, whether express or implied, as to the description, state, quality, merchantability of the Equipment for the purpose for which it is hired is hereby excluded; and
                    (c) the Owner shall not be responsible or liable to the Employer, whether on grounds of breach of contract, contractual duty or negligence, for any loss or damage that the Employer may directly or indirectly sustain or suffer arising from defects in or miscalculations, breakdown or failure of performance of the Equipment, and the Employer hereby exonerates and releases the Owner from all claims and demands in respect thereof.
                </div>

                <div class="term-block">
                    <span class="bold">11. TITLE</span><br>
                    Title to the performance is and shall remain with the Owner or Performer.
                </div>

                <div class="term-block">
                    <span class="bold">12. CONSTRUCTION</span><br>
                    The paragraph headings used herein are for convenience only and are not to be used in constructing the hearing or intent of any of the terms or provisions of the rental contract.
                </div>

                <div class="term-block">
                    <span class="bold">13. SEVERANCE</span><br>
                    In the event of any part of the conditions of hire becoming void or unenforceable whether due to the provision of any statue or otherwise, then that part shall be severed from these conditions of hire, to the intent that all parts that shall not be or become void or enforceable shall remain in full force and effect and be unaffected by any such severance.
                </div>

                <div class="term-block">
                    <span class="bold">14. ENUREMENT OF CERTAIN OBLIGATIONS</span><br>
                    The expiration or determination of these conditions of hire howsoever arising, shall not affect such provisions hereof as are expressed or implied to operate or have effect thereafter and shall be without prejudice to any right or action already accorded to either the Employer or Owner in respect of any breach of these conditions of hire by the other party.
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
