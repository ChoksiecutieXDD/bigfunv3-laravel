@php
    $logoPath = public_path('assets/icon/bgfile.png');
    $logoData = "";
    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* Set page size to DL Envelope */
        @page {
            size: 220mm 110mm;
            margin: 0;
        }

        body {
            font-family: Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }

        .envelope-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        /* SENDER ADDRESS (Top Left) */
        .sender-box {
            position: absolute;
            top: 15mm;
            left: 15mm;
            width: 80mm;
            font-size: 10px;
            color: #555;
            line-height: 1.3;
        }

        .logo-img {
            max-height: 40px;
            margin-bottom: 5px;
        }

        /* RECIPIENT ADDRESS (Middle Right) */
        .recipient-box {
            position: absolute;
            top: 50mm;
            left: 100mm;
            /* Push to the right half */
            width: 100mm;
            font-size: 14px;
            font-weight: bold;
            line-height: 1.5;
        }

        /* STAMP PLACEHOLDER (Top Right) */
        .stamp-box {
            position: absolute;
            top: 10mm;
            right: 15mm;
            width: 20mm;
            height: 25mm;
            border: 1px dashed #ccc;
            background-color: #f9f9f9;
            text-align: center;
            font-size: 9px;
            color: #aaa;
            display: table;
            /* To vertical align text */
        }

        .stamp-text {
            display: table-cell;
            vertical-align: middle;
        }

        .return-text {
            font-size: 8px;
            text-transform: uppercase;
            color: #777;
            margin-top: 5px;
            border-top: 1px solid #ccc;
            padding-top: 2px;
            display: inline-block;
        }
    </style>
</head>

<body>

    <div class="envelope-container">

        <div class="sender-box">
            @if ($logoData)
                <img src="{{ $logoData }}" class="logo-img"><br>
            @else
                <h2 style="margin:0 0 5px 0;">BigFun</h2>
            @endif

            <div class="return-text">If undeliverable, return to:</div>
            <strong>Big Fun Queensland</strong><br>
            145 Ferguson Rd<br>
            Seven Hills, 4170 QLD
        </div>

        <div class="stamp-box">
            <div class="stamp-text">Place<br>Stamp<br>Here</div>
        </div>

        <div class="recipient-box">
            @if (!empty($booking->customer_organization))
                {{ strtoupper($booking->customer_organization) }}<br>
            @endif

            ATTN: {{ $booking->customer_first_name . ' ' . $booking->customer_last_name }}<br>

            {{ $booking->address_line_1 }}<br>

            {{ $booking->suburb . ' ' . $booking->state . ' ' . $booking->postcode }}
        </div>

    </div>

</body>
</html>
