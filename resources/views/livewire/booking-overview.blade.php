<style>
    .card-rounded {
        border-radius: 1.5rem;
    }

    .btn-rounded {
        border-radius: 1rem;
    }

    .box-rounded {
        border-radius: 1.25rem;
    }

    .status-badge {
        @apply px-4 py-1.5 rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1.5;
    }

    /* Dynamic Status Colors */
    .status-Pending {
        background-color: #fff7ed !important;
        color: #c2410c !important;
        border: 1px solid #ffedd5 !important;
    }

    .status-Confirmed {
        background-color: #f0fdf4 !important;
        color: #15803d !important;
        border: 1px solid #dcfce7 !important;
    }

    .status-Completed {
        background-color: #f0f9ff !important;
        color: #0369a1 !important;
        border: 1px solid #e0f2fe !important;
    }

    .status-Cancelled {
        background-color: #fef2f2 !important;
        color: #b91c1c !important;
        border: 1px solid #fee2e2 !important;
    }

    .status-Deposit-Paid {
        background-color: #f5f3ff !important;
        color: #6d28d9 !important;
        border: 1px solid #ede9fe !important;
    }

    .status-Finished {
        background-color: #f8fafc !important;
        color: #475569 !important;
        border: 1px solid #f1f5f9 !important;
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #ccc;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        main {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .bg-white {
            background: white !important;
        }

        .shadow-xl,
        .shadow-lg,
        .shadow-md,
        .shadow-sm {
            box-shadow: none !important;
        }

        .border {
            border: 1px solid #eee !important;
        }
    }
</style>

<main class="max-w-[1660px] mx-auto px-4 md:px-6">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 no-print">
        <div class="flex items-center gap-3 md:gap-4 w-full md:w-auto">
            @if($from_url)
                <a href="{{ $from_url }}" wire:navigate class="bg-white/20 hover:bg-white/30 text-white p-2 md:p-3 btn-rounded backdrop-blur-sm transition shadow-sm shrink-0">
                    <span class="material-symbols-rounded text-xl md:text-2xl">arrow_back</span>
                </a>
            @else
                <a href="javascript:history.back()" class="bg-white/20 hover:bg-white/30 text-white p-2 md:p-3 btn-rounded backdrop-blur-sm transition shadow-sm shrink-0">
                    <span class="material-symbols-rounded text-xl md:text-2xl">arrow_back</span>
                </a>
            @endif
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 md:gap-4">
                    <h1 class="text-2xl md:text-4xl font-extrabold text-white drop-shadow-sm">Booking #{{ $booking['id'] }}</h1>
                    <span class="status-badge status-{{ str_replace(' ', '-', $booking['status']) }} shadow-md">
                        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-50"></span>
                        {{ $booking['status'] }}
                    </span>
                </div>
                <p class="text-white/90 font-medium text-xs md:text-sm mt-1 ml-1">Created on {{ date('F d, Y', strtotime($booking['created_at'])) }}</p>
            </div>
        </div>

        <div class="flex gap-3 w-full md:w-auto">
            <button onclick="window.print()" class="w-full md:w-auto justify-center bg-white text-plum px-5 py-2.5 btn-rounded font-bold shadow-lg hover:bg-gray-50 transition flex items-center gap-2">
                <span class="material-symbols-rounded">print</span> Print
            </button>
        </div>
    </div>

    <!-- Rest of the layout remains the same but with improved max-width context -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 xl:gap-8">
        <!-- Content from original file starting from line 28 -->
        <div class="xl:col-span-2 space-y-6 md:space-y-8">

            <div class="bg-white card-rounded shadow-xl p-5 md:p-8 border border-gray-100 relative overflow-hidden">
                <div class="hidden md:block absolute top-0 right-0 p-6 opacity-5 pointer-events-none">
                    <span class="material-symbols-rounded text-[8rem] lg:text-[10rem] text-plum">event</span>
                </div>

                <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-6 md:mb-8 flex items-center gap-3 border-b border-gray-100 pb-4">
                    <span class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-plum-light flex items-center justify-center text-plum shrink-0">
                        <span class="material-symbols-rounded text-lg md:text-xl">person</span>
                    </span>
                    Customer & Event Information
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-8 gap-x-8 lg:gap-x-16">
                    <div class="space-y-6">
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Customer Name</label>
                            <p class="text-lg md:text-xl font-bold text-gray-800">{{ $booking['customer_first_name'] }} {{ $booking['customer_last_name'] }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Company / Org</label>
                                <p class="font-medium text-gray-700 text-sm md:text-base break-all md:break-words">{{ $booking['customer_organization'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">ABN</label>
                                <p class="font-medium text-gray-700 text-sm md:text-base">{{ $booking['customer_abn'] ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Email</label>
                                <p class="font-medium text-gray-700 text-sm md:text-base break-all md:break-words">{{ $booking['customer_email'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Phone</label>
                                <p class="font-medium text-gray-700 text-sm md:text-base">{{ $booking['customer_phone'] ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Event Address</label>
                            <div class="flex items-start gap-2">
                                <span class="material-symbols-rounded text-plum mt-0.5 text-lg shrink-0">location_on</span>
                                <p class="font-medium text-gray-700 text-sm md:text-base">
                                    {{ $address_line_1 }}
                                    @if (!empty($address_line_2))
                                    <br><span class="text-gray-500">{{ $address_line_2 }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Event Date</label>
                            <p class="text-lg md:text-xl font-bold text-plum">{{ date('F d, Y', strtotime($booking['event_date'])) }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Start Time</label>
                                <p class="font-medium text-gray-700 text-base md:text-lg">{{ date('g:i A', strtotime($booking['start_time'])) }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">End Time</label>
                                <p class="font-medium text-gray-700 text-base md:text-lg">
                                    {{ (!empty($booking['end_time']) && $booking['end_time'] != '00:00:00') ? date('g:i A', strtotime($booking['end_time'])) : 'TBD' }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Event Type</label>
                            <span class="px-3 py-1 bg-gray-100 rounded-lg text-xs md:text-sm font-bold text-gray-600 inline-block border border-gray-200">
                                {{ $booking['event_type'] }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <div class="flex justify-between items-end mb-4">
                        <h4 class="text-sm font-bold text-plum uppercase tracking-wider flex items-center gap-2">
                            <span class="material-symbols-rounded text-base">local_shipping</span> Extras & Logistics Details
                        </h4>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">Total Extra Cost</span>
                            <p class="font-bold text-plum text-base">${{ number_format($calculated_extras_total, 2) }}</p>
                        </div>
                    </div>

                    @if (!empty($grouped_extras))
                    <div class="space-y-4">
                        @foreach ($grouped_extras as $cat_name => $items_in_cat)
                        <div class="bg-gray-50/50 p-4 box-rounded border border-gray-100">
                            <h5 class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="material-symbols-rounded text-[14px] text-plum">category</span>
                                {{ strtoupper($cat_name) }}
                            </h5>
                            <div class="space-y-2 text-sm">
                                @foreach ($items_in_cat as $item)
                                <div class="flex justify-between items-center p-3 bg-white rounded-lg border border-gray-100 shadow-sm">
                                    <span class="font-medium text-gray-700">{{ $item['name'] }}</span>
                                    <span class="font-bold text-plum">
                                        {{ $item['cost'] > 0 ? '+$' . number_format($item['cost'], 2) : '$0.00' }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="bg-gray-50/50 p-6 box-rounded border border-gray-100 text-center">
                        <p class="text-gray-400 text-sm italic">No extras or logistics recorded for this booking.</p>
                    </div>
                    @endif
                </div>

            </div>

            <div class="bg-white card-rounded shadow-xl p-5 md:p-8 border border-gray-100">
                <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-yellow-50 flex items-center justify-center text-yellow-600 border border-yellow-100 shrink-0">
                        <span class="material-symbols-rounded text-lg md:text-xl">description</span>
                    </span>
                    Notes & Attachments
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <div class="bg-yellow-50/30 p-5 box-rounded border border-yellow-100 h-full">
                        <label class="text-xs font-bold text-yellow-700 uppercase tracking-wider block mb-2 flex items-center gap-1">
                            <span class="material-symbols-rounded text-sm">person</span> Customer Notes
                        </label>
                        <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">
                            @if (!empty($booking['notes_customer']))
                            {{ $booking['notes_customer'] }}
                            @else
                            <span class="text-gray-400 italic">None provided.</span>
                            @endif
                        </p>
                    </div>

                    <div class="bg-blue-50/30 p-5 box-rounded border border-blue-100 h-full">
                        <label class="text-xs font-bold text-blue-700 uppercase tracking-wider block mb-2 flex items-center gap-1">
                            <span class="material-symbols-rounded text-sm">local_shipping</span> Delivery Notes
                        </label>
                        <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">
                            @if (!empty($booking['notes_delivery']))
                            {{ $booking['notes_delivery'] }}
                            @else
                            <span class="text-gray-400 italic">None provided.</span>
                            @endif
                        </p>
                    </div>

                    <div class="md:col-span-2 lg:col-span-1 bg-white p-5 box-rounded border border-gray-200 h-full flex flex-col">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-3 flex items-center gap-1">
                            <span class="material-symbols-rounded text-sm">attachment</span> Attachments
                        </label>

                        @if (empty($gallery_files))
                        <div class="flex-1 flex flex-col items-center justify-center text-center opacity-50 py-4">
                            <span class="material-symbols-rounded text-3xl text-gray-300 mb-1">folder_off</span>
                            <p class="text-xs text-gray-400 font-medium">No files attached</p>
                        </div>
                        @else
                        <div class="flex flex-col space-y-2 overflow-y-auto custom-scrollbar max-h-[150px] pr-1">
                            @foreach ($gallery_files as $file)
                            @php
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $icon = $isImage ? 'image' : 'description';
                            @endphp
                            <a href="{{ asset('uploads/' . $file) }}" target="_blank" class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-100 bg-gray-50 hover:bg-gray-100 transition group">
                                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm text-plum shrink-0 group-hover:text-plum-dark transition">
                                    <span class="material-symbols-rounded text-lg">{{ $icon }}</span>
                                </div>
                                <span class="text-xs font-bold text-blue-600 underline truncate flex-1 text-left" title="{{ $file }}">
                                    {{ $file }}
                                </span>
                                <span class="material-symbols-rounded text-gray-300 text-sm group-hover:text-plum transition">open_in_new</span>
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-1 space-y-6 md:space-y-8">

            <div class="bg-white card-rounded shadow-xl overflow-hidden border border-gray-100">
                <div class="p-5 md:p-6 border-b border-gray-50 bg-gray-50/30">
                    <h3 class="text-lg md:text-xl font-bold text-gray-800 flex items-center gap-3">
                        <span class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-plum-light flex items-center justify-center text-plum shrink-0">
                            <span class="material-symbols-rounded text-lg md:text-xl">inventory_2</span>
                        </span>
                        Services & Items
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[300px]">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 md:px-8 md:py-4">Item Name</th>
                                <th class="px-4 py-3 md:px-8 md:py-4">Category</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            @if (count($items) > 0)
                            @foreach ($items as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 md:px-8 md:py-5 font-medium text-gray-800">{{ $item['item_name'] }}</td>
                                <td class="px-4 py-3 md:px-8 md:py-5 text-gray-500">{{ $item['category'] ?? 'General' }}</td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan="2" class="px-8 py-10 text-center text-gray-400 italic">No items found for this booking.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white card-rounded shadow-xl p-5 md:p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                        <span class="material-symbols-rounded text-lg">badge</span>
                    </span>
                    Staff Assignment
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-3 bg-gray-50 box-rounded border border-gray-100">
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-plum shadow-sm shrink-0">
                            <span class="material-symbols-rounded">engineering</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Lead Operator</p>
                            <p class="font-bold text-gray-800 text-sm">{{ $booking['lead_operator'] ?? 'Not Assigned' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-3 bg-gray-50 box-rounded border border-gray-100">
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-blue-500 shadow-sm shrink-0">
                            <span class="material-symbols-rounded">local_shipping</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Lead Deliverer</p>
                            <p class="font-bold text-gray-800 text-sm">{{ $booking['lead_deliverer'] ?? 'Not Assigned' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white card-rounded shadow-xl p-5 md:p-8 border border-gray-100 relative">
                <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-green-50 flex items-center justify-center text-green-600 shrink-0">
                        <span class="material-symbols-rounded text-lg">payments</span>
                    </span>
                    Payment Summary
                </h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-50">
                        <span class="text-gray-500 font-medium text-sm md:text-base">Total Amount</span>
                        <span class="text-lg md:text-xl font-bold text-gray-800">${{ number_format($booking['total_amount'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-4 border-b border-gray-50">
                        <span class="text-gray-500 font-medium text-sm md:text-base">Total Paid</span>
                        <span class="text-base md:text-lg font-bold text-green-600">${{ number_format($total_paid, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2">
                        <span class="text-gray-600 font-bold text-sm md:text-base">Balance Due</span>
                        <span class="text-xl md:text-2xl font-extrabold {{ $balance > 0 ? 'text-red-500' : 'text-green-500' }}">
                            ${{ number_format($balance, 2) }}
                        </span>
                    </div>
                </div>

                <div class="mt-8 bg-gray-50 box-rounded p-4 border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Payment Method</p>
                    <div class="flex items-center gap-2 font-bold text-gray-700 text-sm md:text-base">
                        @php
                        $method = $booking['payment_type'] ?? 'Cash / EFT';
                        $icon = ($method === 'credit_card' || $method === 'Card Holder') ? 'credit_card' : 'account_balance_wallet';
                        @endphp
                        <span class="material-symbols-rounded text-plum">{{ $icon }}</span>
                        {{ ucfirst($method) }}
                    </div>
                </div>
            </div>

            <div class="bg-white card-rounded shadow-xl overflow-hidden border border-gray-100">
                <div class="p-5 md:p-6 border-b border-gray-50 bg-gray-50/30">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-3">
                        <span class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 shrink-0">
                            <span class="material-symbols-rounded text-lg">history</span>
                        </span>
                        Transactions
                    </h3>
                </div>

                <div class="max-h-[300px] overflow-y-auto custom-scrollbar">
                    @if (count($payments) > 0)
                    <div class="divide-y divide-gray-50">
                        @foreach ($payments as $pay)
                        <div class="p-4 md:p-5 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-bold text-gray-700 text-sm">{{ $pay['payment_method'] ?? 'Payment' }}</span>
                                <span class="font-bold text-green-600 text-sm">${{ number_format($pay['amount'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-gray-400">
                                <span>{{ date('F d, Y', strtotime($pay['payment_date'])) }}</span>
                                <span class="bg-gray-100 px-2 py-0.5 rounded text-[10px] uppercase tracking-wide">Ref: {{ $pay['transaction_ref'] ?? '-' }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="p-8 text-center text-gray-400 text-sm italic">
                        No payment records found.
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</main>