<div x-data="{ 
        deleteModal: false, 
        paymentModal: false, 
        emailModal: false, 
        historyModal: false,
        calendarModal: false,
        draftModal: false,
        statusConfirmModal: false,
        payMethod: @entangle('payMethod'),
        selectedPayment: null,
        paymentDetailsModal: false,
        sentSuccessModal: false,
        confirmEmailModal: false,
        historyClearModal: false,
        deleteSingleLogModal: false,
        selectedLogToDelete: null,
        deleteLegacyModal: false,
        quotaWarningModal: false,
        quotaLimitModal: false,
        termsConfirmModal: false
    }"
    class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-1 sm:px-0">
        <div class="flex items-center gap-3 sm:gap-4 w-full md:w-auto">
            <a href="{{ route('supervisor.calendar') }}" wire:navigate class="bg-white hover:bg-gray-50 text-slate-600 p-2 sm:p-2.5 rounded-xl border border-gray-200 transition shadow-sm flex items-center justify-center shrink-0">
                <span class="material-symbols-rounded text-xl sm:text-2xl">arrow_back</span>
            </a>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#1E293B] truncate">Booking #{{ $booking->id }}</h1>
                <p class="text-slate-500 font-medium mt-1 uppercase tracking-wide text-[9px] sm:text-[10px]">Current Status: <span class="font-black underline">{{ $booking->status }}</span></p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            @if($isDebt)
            <span class="bg-red-50 text-red-600 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider border border-red-200 shadow-sm flex items-center gap-1">
                <span class="material-symbols-rounded text-sm">warning</span> Debt: ${{ number_format($balanceDue, 2) }}
            </span>
            @endif
            <span class="{{ $statusColor }} px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider border shadow-md bg-white">
                {{ $booking->status }}
            </span>
            <button @click="deleteModal = true" class="flex items-center gap-1.5 text-xs font-black text-red-500 bg-white px-4 py-2 rounded-xl border border-red-100 hover:bg-red-50 transition shadow-lg">
                <span class="material-symbols-rounded text-lg">delete</span> DELETE
            </button>
        </div>
    </div>



    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100 text-[#9D686E]">
            <span class="material-symbols-rounded text-xl">history_edu</span>
            <span class="text-xs sm:text-sm font-bold uppercase tracking-wide">Booking Origin & Timeline</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 text-xs font-medium">
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Created On</span>
                <span class="font-semibold text-gray-700 text-sm">{{ \Carbon\Carbon::parse($booking->created_at)->format('d M Y, h:i A') }}</span>
            </div>
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Booked By</span>
                <span class="font-semibold text-gray-700 text-sm italic">{{ $booking->booked_by ?: 'System' }}</span>
            </div>
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Current Status</span>
                <span class="font-bold {{ $booking->status == 'Confirmed' ? 'text-green-600' : 'text-gray-700' }}">{{ strtoupper($booking->status) }}</span>
            </div>
            <div>
                <span class="block font-bold text-amber-600 uppercase text-[10px]">Event Date</span>
                <span class="font-bold text-gray-800 text-sm italic">{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</span>
            </div>
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Invoice No</span>
                <span class="font-mono text-gray-600">{{ $booking->invoice_number ?? 'N/A' }}</span>
            </div>
        </div>
    </div>


    <!-- Moved Alert -->
    @if($booking->original_event_date && $booking->original_event_date !== $booking->event_date)
    <div class="bg-amber-50 rounded-2xl shadow-sm border border-amber-100 p-4 flex items-center gap-4 animate-[fadeIn_0.3s_ease-out_forwards]">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 shrink-0">
            <span class="material-symbols-rounded">edit_calendar</span>
        </div>
        <div>
            <h4 class="text-sm font-bold text-amber-800">Booking Moved</h4>
            <p class="text-xs text-amber-700 mt-0.5">
                Original Date: <span class="font-mono font-bold">{{ \Carbon\Carbon::parse($booking->original_event_date)->format('d M Y') }}</span>
                <i class="fa-solid fa-arrow-right mx-1 opacity-50"></i>
                Current Date: <span class="font-mono font-bold">{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</span>
            </p>
        </div>
    </div>
    @endif

    <!-- Actions Toolbar -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-2">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100 pb-4">
                <span class="text-[10px] font-extrabold text-gray-300 uppercase tracking-widest mr-1 sm:w-16">Actions:</span>
                <div class="flex flex-wrap gap-2 flex-grow">
                    <button wire:click="openEmailModal('receipt')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E] active:scale-95">
                        <i class="fa-regular fa-envelope"></i> Email Receipt
                    </button>
                    <button wire:click="openEmailModal('invoice')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E] active:scale-95">
                        <i class="fa-regular fa-envelope"></i> Email Invoice
                    </button>
                    <button wire:click="openEmailModal('po')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E] active:scale-95">
                        <i class="fa-regular fa-file-lines"></i> Email PO
                    </button>
                    @if($isDebt)
                    <button wire:click="openEmailModal('debt')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-red-600 border border-red-600 text-white shadow-sm hover:bg-white hover:text-red-600 active:scale-95">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Email Debt
                    </button>
                    @endif
                    <button @click="historyModal = true" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-clock-rotate-left"></i> History</button>
                </div>
                <div class="h-6 w-px bg-gray-200 mx-2 hidden lg:block shrink-0"></div>
                <div class="flex flex-wrap gap-2 flex-grow">
                    <a href="{{ route('pdf.invoice', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E] no-underline"><i class="fa-solid fa-print"></i> Invoice</a>
                    <a href="{{ route('pdf.envelope', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E] no-underline"><i class="fa-solid fa-envelope-open-text"></i> Envelope</a>
                    <a href="{{ route('pdf.po', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E] no-underline"><i class="fa-solid fa-file-invoice"></i> PO/Quote</a>
                    @if($isDebt)
                    <a href="{{ route('pdf.debt', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] sm:text-[11px] font-semibold transition whitespace-nowrap bg-red-600 border border-red-600 text-white shadow-sm hover:bg-white hover:text-red-600 no-underline"><i class="fa-solid fa-file-invoice-dollar"></i> Debt</a>
                    @endif
                </div>
            </div>

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <span class="text-[10px] font-extrabold text-gray-300 uppercase tracking-widest mr-1">Manage:</span>
                    <a href="{{ route('supervisor.customer.profile', $booking->id) }}" wire:navigate class="flex items-center gap-1 text-[11px] sm:text-xs font-bold text-slate-600 hover:text-[#9D686E] hover:underline transition"><i class="fa-regular fa-eye"></i> View Customer</a>
                    <span class="text-gray-200 hidden sm:inline">|</span>
                    <a href="{{ route('supervisor.bookings.edit', $booking->id) }}" wire:navigate class="flex items-center gap-1 text-[11px] sm:text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition"><i class="fa-solid fa-pen-to-square"></i> Edit Booking</a>
                    <span class="text-gray-200 hidden sm:inline">|</span>
                    <div class="flex items-center gap-2">
                        <label for="attraction_cost_toggle" class="text-[10px] font-bold text-slate-500 uppercase cursor-pointer">PDF Price:</label>
                        <button wire:click="toggleAttractionCost" id="attraction_cost_toggle" class="relative inline-flex h-4 sm:h-5 w-8 sm:w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $booking->include_attraction_cost ? 'bg-[#9D686E]' : 'bg-gray-200' }}">
                            <span class="pointer-events-none inline-block h-3 sm:h-4 w-3 sm:w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $booking->include_attraction_cost ? 'translate-x-4 sm:translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                    <div class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-lg border border-gray-100 flex-grow">
                        <label class="text-[10px] font-bold text-gray-400 uppercase ml-2 whitespace-nowrap">Move:</label>
                        <div class="relative flex items-center flex-grow">
                            <input type="date" wire:model="newDate" class="text-xs border border-gray-200 rounded-l p-2 text-gray-700 focus:outline-none focus:border-[#9D686E] bg-white w-full sm:w-32">
                            <button wire:click="openCalendarModal" class="bg-[#9D686E] text-white px-2 py-1.5 rounded-r border border-[#9D686E] hover:bg-[#855359] transition h-full flex items-center justify-center shrink-0">
                                <span class="material-symbols-rounded text-base">calendar_month</span>
                            </button>
                        </div>
                        <button wire:click="moveDate" class="bg-white text-gray-600 border border-gray-200 text-[10px] uppercase font-bold px-3 py-2 rounded shadow-sm hover:bg-gray-100 transition whitespace-nowrap">
                            <span wire:loading.remove wire:target="moveDate">Go</span>
                            <span wire:loading wire:target="moveDate" class="flex items-center gap-1.5">
                                <span class="material-symbols-rounded animate-spin text-xs">sync</span>
                                Syncing...
                            </span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-lg border border-gray-100 flex-grow">
                        <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Status:</label>
                        <select wire:model="newStatus" class="text-xs border-none bg-transparent font-bold text-gray-700 focus:ring-0 cursor-pointer py-1 flex-grow">
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Hold">Hold</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Draft">Draft</option>
                        </select>
                        <button wire:click="updateStatus" class="bg-white text-[#9D686E] border border-gray-200 text-[10px] uppercase font-bold px-3 py-2 rounded shadow-sm hover:bg-gray-50 transition whitespace-nowrap">
                            <span wire:loading.remove wire:target="updateStatus">Apply</span>
                            <span wire:loading wire:target="updateStatus" class="flex items-center gap-1">
                                <span class="material-symbols-rounded animate-spin text-[10px]">sync</span>
                                Sync...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Details (Full Width Container) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 relative group">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
            <div class="flex items-center gap-2"><span class="material-symbols-rounded">payments</span><span class="text-sm font-bold uppercase tracking-wide">Financial Details</span></div>
            <button wire:click="manualSync" 
                    wire:loading.attr="disabled"
                    class="group flex items-center gap-2 px-3 py-1 rounded-full bg-slate-50 border border-slate-200 text-[10px] font-black text-slate-400 hover:text-[#9D686E] hover:border-[#9D686E]/50 transition-all shadow-sm disabled:opacity-50">
                <span wire:loading.remove wire:target="manualSync" class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-sm">cloud_sync</span> 
                    SYNC TO CLOUD
                </span>
                <span wire:loading wire:target="manualSync" class="flex items-center gap-2">
                    <span class="material-symbols-rounded animate-spin text-sm">sync</span> 
                    SYNCING...
                </span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Side: Schedule & Financials -->
            <div class="bg-gray-50 rounded-xl p-4 sm:p-5 border border-gray-100 text-xs h-full">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 sm:gap-4">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Payment Method</span>
                        <div class="flex items-center gap-2 mb-2">
                            @php
                            $displayType = $booking->payment_type === 'Card Holder' ? 'Credit/Debit Card' : ($booking->payment_type ?: 'Not Set');
                            $network = strtolower($booking->card_network ?? '');
                            $networkIcon = '';
                            if (str_contains($network, 'visa')) $networkIcon = '<i class="fa-brands fa-cc-visa text-blue-600"></i>';
                            elseif (str_contains($network, 'mastercard')) $networkIcon = '<i class="fa-brands fa-cc-mastercard text-orange-500"></i>';
                            elseif (str_contains($network, 'amex') || str_contains($network, 'american express')) $networkIcon = '<i class="fa-brands fa-cc-amex text-blue-400"></i>';
                            elseif (str_contains($network, 'discover')) $networkIcon = '<i class="fa-brands fa-cc-discover text-orange-400"></i>';
                            elseif (str_contains($network, 'diners')) $networkIcon = '<i class="fa-brands fa-cc-diners-club text-blue-800"></i>';
                            else $networkIcon = '<i class="fa-solid fa-credit-card text-gray-400"></i>';
                            @endphp
                            <span class="font-bold text-gray-700 flex items-center gap-2">
                                {!! $booking->payment_type === 'Card Holder' ? $networkIcon : '' !!}
                                {{ $displayType }}
                            </span>
                        </div>
                        @if($booking->payment_type === 'Card Holder' && $booking->card_number)
                        <div class="text-[10px] text-gray-500 font-mono space-y-0.5">
                            <div>Card: **** **** {{ substr(str_replace(' ', '', $booking->card_number), -8, 4) }} {{ substr(str_replace(' ', '', $booking->card_number), -4) }}</div>
                            <div>Exp: **/** | CVV: ***</div>
                        </div>
                        @endif
                    </div>

                    <div class="flex flex-col h-full">
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-2 tracking-widest">Financial Track Record</span>
                        <div class="space-y-2 flex-grow overflow-y-auto max-h-[120px] custom-scrollbar pr-2">
                            @forelse($payments as $index => $pay)
                            @php
                                $methodIcon = 'account_balance';
                                $methodColor = 'text-blue-500 bg-blue-50';
                                if ($pay->payment_method === 'Card Holder') {
                                    $methodIcon = 'credit_card';
                                    $methodColor = 'text-purple-500 bg-purple-50';
                                } elseif ($pay->payment_method === 'Cash') {
                                    $methodIcon = 'payments';
                                    $methodColor = 'text-emerald-500 bg-emerald-50';
                                }
                            @endphp
                            <div class="group relative flex items-center gap-3 p-2 rounded-lg bg-white border border-gray-100 hover:border-[#9D686E]/30 transition shadow-sm">
                                <div class="w-8 h-8 rounded-lg {{ $methodColor }} flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-lg">{{ $methodIcon }}</span>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <span class="font-bold text-gray-700 truncate">#{{ $index + 1 }} - {{ $pay->payment_type }}</span>
                                        <span class="font-black text-[#9D686E] whitespace-nowrap">${{ number_format($pay->amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[9px] text-gray-400 font-medium">
                                        <span>{{ \Carbon\Carbon::parse($pay->payment_date)->format('M d, Y') }}</span>
                                        <button wire:click="selectPayment({{ $pay->id }})" class="text-[#9D686E] font-bold uppercase tracking-tighter hover:underline">View Details</button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="flex flex-col items-center justify-center py-4 bg-white/50 rounded-lg border border-dashed border-gray-200">
                                <span class="material-symbols-rounded text-gray-300 mb-1">payments</span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase">No payments recorded.</span>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Totals -->
            <div class="bg-[#9D686E]/10 rounded-xl p-4 sm:p-5 space-y-3 border border-[#9D686E]/20 flex flex-col justify-center">
                <div class="space-y-2 max-h-[200px] overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($items as $item)
                    @if($item->unit_price > 0)
                    <div class="flex justify-between items-start text-[11px] border-b border-[#9D686E]/5 pb-1 last:border-0 mb-1">
                        <span class="font-medium text-slate-500 flex-1">{{ $item->item_name }} ({{ $item->total_qty }})</span>
                        <span class="font-bold text-slate-700 ml-4">${{ number_format($item->unit_price * $item->total_qty, 2) }}</span>
                    </div>
                    @endif
                    @endforeach

                    @if(($booking->duration_cost ?? 0) >= 0)
                    <div class="flex justify-between items-center text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                        <span class="font-medium text-slate-500">Duration Cost:</span>
                        <span class="font-bold text-slate-700">${{ number_format($booking->duration_cost, 2) }}</span>
                    </div>
                    @endif

                    @if(($deliveryCost ?? 0) >= 0)
                    <div class="flex justify-between items-center text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                        <span class="font-medium text-slate-500">Delivery Fee:</span>
                        <span class="font-bold text-slate-700">${{ number_format($deliveryCost, 2) }}</span>
                    </div>
                    @endif

                    {{-- Dynamically Derived Extras with Costing --}}
                    @foreach($activeCategories as $cat)
                    @php
                    $catAddons = $config['addons'][$cat] ?? [];
                    $catQuestions = $config['questions'][$cat] ?? [];
                    @endphp

                    @foreach($catAddons as $addon)
                    @php $isSelected = isset($selectedExtras['add_'.$addon['id']]); @endphp
                    @if($isSelected && $addon['addon_price'] > 0)
                    <div class="flex justify-between items-start text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                        <span class="font-medium text-slate-500 flex-1">{{ $addon['addon_label'] }}</span>
                        <span class="font-bold text-slate-700 ml-4">${{ number_format($addon['addon_price'], 2) }}</span>
                    </div>
                    @endif
                    @endforeach

                    @foreach($catQuestions as $q)
                    @php
                    $val = $selectedExtras['extra_'.$q['id']] ?? $selectedExtras['q_'.$q['id']] ?? null;
                    $price = 0;
                    $isYes = false;
                    if ($val) {
                    $parts = explode('|', $val);
                    $price = (float)($parts[0] ?? 0);
                    $answer = $parts[1] ?? 'yes';
                    $isYes = ($answer === 'yes');
                    }
                    @endphp
                    @if($isYes && $price > 0)
                    <div class="flex justify-between items-start text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                        <span class="font-medium text-slate-500 flex-1">{{ $q['question_text'] }}</span>
                        <span class="font-bold text-slate-700 ml-4">${{ number_format($price, 2) }}</span>
                    </div>
                    @endif
                    @endforeach
                    @endforeach

                    @foreach($items as $item)
                    @if($item->unit_price > 0)
                    <div class="flex justify-between items-start text-[11px] border-b border-[#9D686E]/5 pb-1 last:border-0 mb-1">
                        <span class="font-medium text-slate-500 flex-1">{{ $item->item_name }} ({{ $item->total_qty }})</span>
                        <span class="font-bold text-slate-700 ml-4">${{ number_format($item->unit_price * $item->total_qty, 2) }}</span>
                    </div>
                    @endif
                    @endforeach

                    @if(($booking->duration_cost ?? 0) >= 0)
                    <div class="flex justify-between items-center text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                        <span class="font-medium text-slate-500">Duration Cost:</span>
                        <span class="font-bold text-slate-700">${{ number_format($booking->duration_cost, 2) }}</span>
                    </div>
                    @endif

                    @if(($deliveryCost ?? 0) >= 0)
                    <div class="flex justify-between items-center text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                        <span class="font-medium text-slate-500">Delivery Fee:</span>
                        <span class="font-bold text-slate-700">${{ number_format($deliveryCost, 2) }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between items-center text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                         <span class="font-medium text-slate-500">Operational Hour:</span>
                         <span class="font-bold text-slate-700">{{ $booking->operational_hours ?: '-' }}</span>
                    </div>

                    @if($isCard && $surcharge > 0)
                    <div class="flex justify-between items-center text-[11px] border-t border-dotted border-[#9D686E]/20 pt-1 mt-1 text-purple-600">
                        <span class="font-bold italic">Card Surcharge (2.9%):</span>
                        <span class="font-black">${{ number_format($surcharge, 2) }}</span>
                    </div>
                    @endif
                </div>

                <div class="flex justify-between items-center mt-3 pt-3 border-t-2 border-[#9D686E]/20">
                    <span class="text-xs font-black text-[#9D686E] uppercase tracking-wider">Total Amount</span>
                    <span class="text-xl font-black text-[#9D686E] tracking-tighter">${{ number_format($totalAmount, 2) }}</span>
                </div>

                <div class="flex justify-between items-center bg-white/50 p-2 rounded-lg mt-1 border border-[#9D686E]/10">
                    <span class="text-[10px] font-black text-slate-600 uppercase">Balance Due:</span>
                    <span class="text-lg font-black {{ $balanceDue > 0 ? 'text-red-500' : 'text-green-500' }} tracking-tighter">${{ number_format($balanceDue, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Bottom Actions of Financial Details -->
        <div class="mt-4 border-t border-gray-100 pt-4 flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center gap-2 text-xs">
                <span class="text-gray-500">Terms Agreed?</span>
                <button @click="termsConfirmModal = true" 
                        wire:loading.attr="disabled"
                        class="px-2 py-1 rounded text-[10px] font-bold transition flex items-center gap-1.5 {{ $booking->terms_agreed ? 'bg-green-50 text-green-600 hover:bg-green-100' : 'bg-red-50 text-red-600 hover:bg-red-100' }}">
                    <span wire:loading.remove wire:target="toggleTerms">
                        {{ $booking->terms_agreed ? 'Agreed' : 'No' }}
                    </span>
                    <span wire:loading wire:target="toggleTerms" class="flex items-center gap-1">
                        <span class="w-3 h-3 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                        Syncing...
                    </span>
                </button>
            </div>

            @if($balanceDue > 0)
            <button wire:click="openPaymentModal" class="text-[10px] font-bold text-[#9D686E] bg-[#9D686E]/10 px-3 py-2 rounded hover:bg-[#9D686E]/20 transition shadow-sm">+ Record Payment</button>
            @else
            <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded border border-green-200"><i class="fa-solid fa-check mr-1"></i> Fully Paid</span>
            @endif
        </div>
    </div>

    <!-- Details Grid (2 Columns) -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">

        <!-- Left Column -->
        <div class="space-y-6">

            <!-- Booking Details -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">event_note</span><span class="text-sm font-bold uppercase tracking-wide">Booking Details</span>
                </div>
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Type</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->event_type }}</span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Contact</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Event Date</span><span class="text-[0.75rem] font-medium text-slate-800">{{ \Carbon\Carbon::parse($booking->event_date)->format('l d/m/Y') }}</span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Time</span><span class="text-[0.75rem] font-bold text-[#9D686E]">{{ $timeString }}</span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Duration</span><span class="text-[0.75rem] font-bold text-gray-800">{{ $booking->duration ?: '-' }} @if($booking->duration_cost > 0) <span class="text-[#9D686E] ml-1">(${{ number_format($booking->duration_cost, 2) }})</span> @endif</span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200">
                        <span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Operational Hour</span>
                        <span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->operational_hours ?: '-' }}</span>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Delivery</span><span class="text-[0.75rem] font-bold text-gray-800">{{ $booking->delivery_area ?: 'Not Set' }} <span class="text-[#9D686E] ml-1">(${{ number_format($deliveryCost, 2) }})</span></span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Pax</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->expected_people }}</span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Booked By</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->booked_by ?? 'System' }}</span></div>
                </div>
            </div>

            <!-- Task Reminders / Notes -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">task</span><span class="text-sm font-bold uppercase tracking-wide">Task Reminders / Notes</span>
                </div>
                <div class="grid gap-3">
                    @if(!empty($booking->notes_customer))
                    <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-100">
                        <span class="text-[10px] font-bold text-yellow-700 uppercase block mb-1">Customer Notes</span>
                        <p class="text-xs text-gray-600 italic mt-1 whitespace-pre-wrap">{{ $booking->notes_customer }}</p>
                    </div>
                    @else
                    <p class="text-xs text-gray-400 italic">No customer notes.</p>
                    @endif

                    @if(!empty($booking->note_delivery) || !empty($booking->notes_delivery))
                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                        <span class="text-[10px] font-bold text-blue-700 uppercase block mb-1">Delivery Notes</span>
                        <p class="text-xs text-gray-600 italic mt-1 whitespace-pre-wrap">{{ $booking->note_delivery ?? $booking->notes_delivery }}</p>
                    </div>
                    @else
                    <p class="text-xs text-gray-400 italic">No delivery notes.</p>
                    @endif
                </div>
            </div>

            <!-- Event Address -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">location_on</span><span class="text-sm font-bold uppercase tracking-wide">Event Address</span>
                </div>
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-[#9D686E] shrink-0"><span class="material-symbols-rounded">map</span></div>
                    <div>
                        <p class="font-bold text-sm text-gray-800 whitespace-pre-line">{{ $booking->address_line_1 }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $booking->suburb }}, {{ $booking->state }} {{ $booking->postcode }}</p>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($booking->address_line_1 . ' ' . $booking->suburb) }}" target="_blank" class="text-[10px] font-bold text-blue-500 hover:underline mt-1 block">View on Google Maps</a>
                    </div>
                </div>
            </div>

            <!-- Staff & Operations -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">engineering</span><span class="text-sm font-bold uppercase tracking-wide">Staff & Operations</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">Lead Operator</span>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="w-6 h-6 rounded-full bg-[#9D686E] text-white flex items-center justify-center text-xs font-bold">{{ substr($booking->lead_operator ?? '?', 0, 1) }}</div>
                            <p class="text-sm font-bold text-gray-700">{{ $booking->lead_operator ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">Lead Deliverer</span>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold"><span class="material-symbols-rounded text-[14px]">local_shipping</span></div>
                            <p class="text-sm font-bold text-gray-700">{{ $booking->lead_deliverer ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="space-y-6">

            <!-- Customer Details -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">person</span><span class="text-sm font-bold uppercase tracking-wide">Customer Details</span>
                </div>
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Name</span><span class="text-[0.75rem] font-bold text-slate-800">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Org / Company</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->customer_organization ?: '-' }}</span></div>
                    <div class="flex flex-col sm:flex-row justify-between items-baseline gap-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">ABN No.</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->customer_abn ?: '-' }}</span></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-b border-dotted border-gray-200 pb-1">
                        <div class="flex flex-col"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Mobile</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->customer_phone }}</span></div>
                        <div class="flex flex-col"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Business Phone</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->customer_business_phone ?: '-' }}</span></div>
                    </div>

                    <div class="flex flex-col mb-1 pb-1 border-b border-dotted border-gray-200">
                        <span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Business Address</span>
                        <span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->business_address ?: 'Not provided' }}</span>
                    </div>

                    <div class="mt-2 pt-2 border-t border-gray-50 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                        <div class="min-w-0">
                            <span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide block">Email</span>
                            <span class="text-sm text-blue-600 truncate block">{{ $booking->customer_email }}</span>
                        </div>
                        <a href="mailto:{{ $booking->customer_email }}" class="text-[10px] text-amber-600 font-bold uppercase hover:underline shrink-0 bg-amber-50 px-2 py-1.5 rounded inline-block text-center">Email</a>
                    </div>
                </div>
            </div>

            <!-- Rides Booked -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">attractions</span><span class="text-sm font-bold uppercase tracking-wide">Rides Booked</span>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left min-w-[500px]">
                        <thead class="bg-gray-50 text-[10px] text-gray-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="p-2 rounded-l-lg">Ride / Item</th>
                                <th class="p-2">Specification</th>
                                <th class="p-2 text-center">Qty</th>
                                <th class="p-2 rounded-r-lg text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs divide-y divide-gray-50">
                            @forelse ($items as $s)
                            <tr class="hover:bg-gray-50 transition border-b border-gray-50 last:border-0">
                                <td class="p-2 font-black text-[#9D686E] py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm uppercase tracking-tight">{{ $s->item_name }}</span>
                                        @if($s->is_custom)
                                        <span class="text-[9px] font-bold text-amber-500 uppercase">Custom Item</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-2 py-4">
                                    @if($s->specification)
                                    <div class="text-[10px] text-gray-500 space-y-0.5">
                                        @foreach(explode("\n", str_replace(["\r\n", "\r"], "\n", $s->specification)) as $line)
                                        @if(trim($line))
                                        <div class="flex items-start gap-1">
                                            <span class="mt-1 w-1 h-1 rounded-full bg-[#9D686E] shrink-0"></span>
                                            <span>{{ trim($line) }}</span>
                                        </div>
                                        @endif
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="text-[10px] text-gray-400 italic">No specs</span>
                                    @endif
                                </td>
                                <td class="p-2 text-center font-black text-gray-800 py-4">{{ $s->total_qty }}</td>
                                <td class="p-2 text-right font-black text-[#9D686E] py-4">
                                    @if($s->unit_price > 0)
                                    ${{ number_format($s->unit_price * $s->total_qty, 2) }}
                                    @else
                                    -
                                    @endif
                                </td>
                            </tr>
                            @empty
                            {{-- Check if any extras exist if items are empty --}}
                            @endforelse

                            {{-- ADD EXTRAS TO TABLE DYNAMICALLY --}}
                            @foreach($activeCategories as $cat)
                            @php
                            $catAddons = $config['addons'][$cat] ?? [];
                            $catQuestions = $config['questions'][$cat] ?? [];
                            @endphp

                            @foreach($catAddons as $addon)
                            @php $isSelected = isset($selectedExtras['add_'.$addon['id']]); @endphp
                            @if($isSelected && $addon['addon_price'] > 0)
                            <tr class="hover:bg-gray-50 transition border-b border-gray-50 last:border-0 bg-slate-50/30">
                                <td class="p-2 font-bold text-slate-600 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-rounded text-sm text-[#9D686E]">add_circle</span>
                                        <span class="text-xs uppercase tracking-tight">{{ $addon['addon_label'] }}</span>
                                    </div>
                                </td>
                                <td class="p-2 py-4 italic text-[10px] text-slate-400">Extra / Logistics</td>
                                <td class="p-2 text-center font-bold text-slate-500 py-4">1</td>
                                <td class="p-2 text-right font-black text-[#9D686E] py-4">${{ number_format($addon['addon_price'], 2) }}</td>
                            </tr>
                            @endif
                            @endforeach

                            @foreach($catQuestions as $q)
                            @php
                            $val = $selectedExtras['extra_'.$q['id']] ?? $selectedExtras['q_'.$q['id']] ?? null;
                            $price = 0;
                            $isYes = false;
                            if ($val) {
                            $parts = explode('|', $val);
                            $price = (float)($parts[0] ?? 0);
                            $answer = $parts[1] ?? 'yes';
                            $isYes = ($answer === 'yes');
                            }
                            @endphp
                            @if($isYes && $price > 0)
                            <tr class="hover:bg-gray-50 transition border-b border-gray-50 last:border-0 bg-slate-50/30">
                                <td class="p-2 font-bold text-slate-600 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-rounded text-sm text-[#9D686E]">add_circle</span>
                                        <span class="text-xs uppercase tracking-tight">{{ $q['question_text'] }}</span>
                                    </div>
                                </td>
                                <td class="p-2 py-4 italic text-[10px] text-slate-400">Extra / Logistics</td>
                                <td class="p-2 text-center font-bold text-slate-500 py-4">1</td>
                                <td class="p-2 text-right font-black text-[#9D686E] py-4">${{ number_format($price, 2) }}</td>
                            </tr>
                            @endif
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gallery & Attachments -->
            <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">perm_media</span><span class="text-sm font-bold uppercase tracking-wide">Gallery & Attachments</span>
                </div>
                @if(empty($galleryFiles))
                <div class="text-center py-6 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                    <span class="material-symbols-rounded text-gray-300 text-4xl block mb-2">no_photography</span>
                    <span class="text-xs text-gray-400 font-bold">No files attached</span>
                </div>
                @else
                <div class="flex flex-col space-y-2">
                    @foreach($galleryFiles as $file)
                    @php
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    $icon = $isImage ? 'image' : 'description';
                    @endphp
                    <a href="/uploads/{{ $file }}" target="_blank" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-100 transition group">
                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm text-[#9D686E] group-hover:text-[#855359] transition">
                            <span class="material-symbols-rounded text-lg">{{ $icon }}</span>
                        </div>
                        <span class="text-xs font-bold text-blue-600 underline truncate">{{ $file }}</span>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

        </div>
    </div>



    <!-- ================== ALPINE MODALS ================== -->

    <template x-teleport="body">
        <!-- QUOTA LOW WARNING MODAL -->
        <div x-show="quotaWarningModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="quotaWarningModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="quotaWarningModal = false"></div>

            <div x-show="quotaWarningModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-md bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-5 text-amber-600">
                    <span class="material-symbols-rounded text-3xl">warning</span>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">{{ $quotaWarningTitle }}</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">{{ $quotaWarningMessage }}</p>
                <div class="flex justify-center gap-3">
                    <button @click="quotaWarningModal = false" class="flex-1 py-3.5 text-slate-600 font-bold text-[15px] hover:bg-slate-50 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="continueEmailAfterQuotaWarning" class="flex-1 py-3.5 bg-amber-500 text-white hover:bg-amber-600 font-bold text-[15px] rounded-xl shadow-md shadow-amber-500/20 transition-all active:scale-95">Continue</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- QUOTA LIMIT MODAL -->
        <div x-show="quotaLimitModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="quotaLimitModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="quotaLimitModal = false"></div>

            <div x-show="quotaLimitModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-md bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5 text-red-600">
                    <span class="material-symbols-rounded text-3xl">block</span>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">{{ $quotaLimitTitle }}</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">{{ $quotaLimitMessage }}</p>
                <button @click="quotaLimitModal = false" class="w-full py-3.5 bg-red-500 text-white hover:bg-red-600 font-bold text-[15px] rounded-xl shadow-md shadow-red-500/20 transition-all active:scale-95">Understood</button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- DELETE MODAL -->
        <div x-show="deleteModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="deleteModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="deleteModal = false"></div>

            <div x-show="deleteModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-md bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5 text-red-600">
                    <span class="material-symbols-rounded text-3xl">delete_forever</span>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Delete Booking?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">Are you sure you want to permanently delete Booking #{{ $booking->id }}? This cannot be undone.</p>
                <div class="flex justify-center gap-3">
                    <button @click="deleteModal = false" class="flex-1 py-3.5 text-slate-600 font-bold text-[15px] hover:bg-slate-50 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="deleteBooking" class="flex-1 py-3.5 bg-red-500 text-white hover:bg-red-600 font-bold text-[15px] rounded-xl shadow-md shadow-red-500/20 transition-all active:scale-95">Yes, Delete It</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- DRAFT MODAL -->
        <div x-show="draftModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="draftModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="draftModal = false"></div>

            <div x-show="draftModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-md bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-5 text-orange-600">
                    <span class="material-symbols-rounded text-3xl">warning</span>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Publish Draft?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">
                    This booking is currently a <strong>Draft</strong>. Are you sure you want to change its status to <strong class="text-[#9D686E]">{{ $newStatus }}</strong>? <br><br> Make sure you have edited and confirmed all necessary details first!
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="draftModal = false" class="flex-1 py-3.5 text-slate-600 font-bold text-[15px] hover:bg-slate-50 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="executeStatusUpdate" class="flex-1 py-3.5 bg-orange-500 text-white hover:bg-orange-600 font-bold text-[15px] rounded-xl shadow-md shadow-orange-500/20 transition-all active:scale-95">Yes, Update Status</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- STATUS CONFIRM MODAL -->
        <div x-show="statusConfirmModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="statusConfirmModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="statusConfirmModal = false"></div>

            <div x-show="statusConfirmModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-[#9D686E]/10 rounded-full flex items-center justify-center mx-auto mb-5 text-[#9D686E]">
                    <span class="material-symbols-rounded text-3xl">sync_alt</span>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Update Status?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">
                    Are you sure you want to change the booking status to <strong class="text-[#9D686E]">{{ $newStatus }}</strong>?
                </p>
                <div class="flex justify-center gap-4">
                    <button @click="statusConfirmModal = false" class="flex-1 py-3.5 text-slate-600 font-bold text-[15px] hover:bg-slate-50 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="executeStatusUpdate" wire:loading.attr="disabled" class="flex-1 py-3.5 bg-[#9D686E] text-white hover:bg-[#855359] font-bold text-[15px] rounded-xl shadow-md shadow-[#9D686E]/20 transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-75">
                        <span wire:loading.remove wire:target="executeStatusUpdate">Yes, Update</span>
                        <span wire:loading wire:target="executeStatusUpdate" class="flex items-center gap-2">
                            <span class="material-symbols-rounded animate-spin text-lg">sync</span>
                            Syncing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </template>


    <template x-teleport="body">
        <!-- PAYMENT MODAL -->
        <div x-show="paymentModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="paymentModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="paymentModal = false"></div>

            <div x-show="paymentModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl overflow-hidden z-10 flex flex-col max-h-[90vh]">
                
                <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center shrink-0 bg-white">
                    <div class="flex items-center gap-3 text-[#9D686E]">
                        <span class="material-symbols-rounded text-2xl font-bold">account_balance_wallet</span>
                        <h3 class="font-black text-lg text-slate-800 uppercase tracking-tight">Add Payment</h3>
                    </div>
                    <button @click="paymentModal = false" class="text-slate-400 hover:text-slate-600 transition p-1.5 hover:bg-slate-50 rounded-lg">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                <form wire:submit.prevent="savePayment" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-white">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">ALLOCATION MATRIX</label>
                                <div class="relative group">
                                    <select wire:model.live="payType" class="w-full px-5 py-4 bg-slate-50 rounded-2xl border border-slate-100 text-sm font-bold text-slate-700 outline-none focus:border-[#9D686E]/30 focus:bg-white focus:ring-4 focus:ring-[#9D686E]/5 cursor-pointer shadow-sm appearance-none transition-all">
                                        <option value="Deposit Capture">Deposit Capture</option>
                                        <option value="Final Settlement">Final Settlement</option>
                                        <option value="Total Liquidation">Total Liquidation</option>
                                        <option value="Partial Allocation">Partial Allocation</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-5 flex items-center pointer-events-none text-[#9D686E] transition-colors">
                                        <span class="material-symbols-rounded text-2xl">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount Section -->
                            <div class="p-8 bg-slate-50/50 rounded-[32px] border border-slate-100/80 shadow-inner group transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/20">
                                <label class="block text-[11px] font-black text-slate-400 mb-6 uppercase tracking-[0.2em]">ENTRY AMOUNT RETRIEVAL ($)</label>
                                <div class="relative flex items-center">
                                    <span class="absolute left-0 text-4xl font-black text-slate-300 pointer-events-none">$</span>
                                    <input type="number" step="0.01" wire:model.live="payAmount" class="w-full pl-10 bg-transparent border-none text-[56px] font-black text-slate-800 outline-none p-0 focus:ring-0 placeholder:text-slate-200" placeholder="0.00">
                                </div>
                            </div>

                            <!-- Payment Method Selection -->
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Transaction Method</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <label class="relative flex flex-col items-center gap-2 p-4 border rounded-2xl cursor-pointer transition-all {{ $payMethod === 'EFT' ? 'border-[#9D686E] bg-pink-50 ring-2 ring-[#9D686E]/10' : 'border-slate-100 hover:bg-slate-50' }}">
                                            <input type="radio" wire:model.live="payMethod" value="EFT" class="hidden">
                                            <span class="material-symbols-rounded {{ $payMethod === 'EFT' ? 'text-[#9D686E]' : 'text-slate-400' }}">account_balance</span>
                                            <span class="text-[11px] font-black {{ $payMethod === 'EFT' ? 'text-[#9D686E]' : 'text-slate-600' }} uppercase tracking-wide">EFT</span>
                                        </label>
                                        <label class="relative flex flex-col items-center gap-2 p-4 border rounded-2xl cursor-pointer transition-all {{ $payMethod === 'Card Holder' ? 'border-[#9D686E] bg-pink-50 ring-2 ring-[#9D686E]/10' : 'border-slate-100 hover:bg-slate-50' }}">
                                            <input type="radio" wire:model.live="payMethod" value="Card Holder" class="hidden">
                                            <span class="material-symbols-rounded {{ $payMethod === 'Card Holder' ? 'text-[#9D686E]' : 'text-slate-400' }}">credit_card</span>
                                            <span class="text-[11px] font-black {{ $payMethod === 'Card Holder' ? 'text-[#9D686E]' : 'text-slate-600' }} uppercase tracking-wide">Card</span>
                                        </label>
                                        <label class="relative flex flex-col items-center gap-2 p-4 border rounded-2xl cursor-pointer transition-all {{ $payMethod === 'Cash' ? 'border-[#9D686E] bg-pink-50 ring-2 ring-[#9D686E]/10' : 'border-slate-100 hover:bg-slate-50' }}">
                                            <input type="radio" wire:model.live="payMethod" value="Cash" class="hidden">
                                            <span class="material-symbols-rounded {{ $payMethod === 'Cash' ? 'text-[#9D686E]' : 'text-slate-400' }}">payments</span>
                                            <span class="text-[11px] font-black {{ $payMethod === 'Cash' ? 'text-[#9D686E]' : 'text-slate-600' }} uppercase tracking-wide">Cash</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- EFT Details (Sub-Methods) -->
                            <div x-show="$wire.payMethod === 'EFT'" x-collapse class="space-y-4 pt-2">
                                <div class="p-5 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                    <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Specific Routing Protocol</label>
                                    <div class="relative group">
                                        <select wire:model="eftMethod" class="w-full px-4 py-3.5 bg-white rounded-xl border border-slate-100 text-sm font-bold text-slate-700 outline-none focus:border-[#9D686E]/30 cursor-pointer shadow-sm appearance-none">
                                            <option value="Direct Deposit">Direct Deposit</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Osko Realtime">Osko Realtime</option>
                                            <option value="PayID Matrix">PayID Matrix</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400 group-hover:text-[#9D686E] transition-colors">
                                            <span class="material-symbols-rounded">expand_more</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Details Area (Dynamic) -->
                            <div x-show="$wire.payMethod === 'Card Holder'" x-collapse class="space-y-4 pt-2">
                                <div class="p-5 bg-slate-50 rounded-2xl border border-dashed border-slate-200 space-y-4">
                                    <div class="relative">
                                        <label class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Card Number</label>
                                        <input type="text" wire:model="cardNumber" maxlength="19" x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim()" class="w-full px-4 py-3 bg-white rounded-xl border border-slate-100 text-sm font-mono tracking-wider focus:border-[#9D686E]/30 outline-none shadow-sm" placeholder="0000 0000 0000 0000">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Expiry</label>
                                            <input type="text" wire:model="cardExpiry" maxlength="5" placeholder="MM/YY" x-on:input="let v = $el.value.replace(/\D/g, ''); if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2,4); $el.value = v;" class="w-full px-4 py-3 bg-white rounded-xl border border-slate-100 text-sm font-mono text-center focus:border-[#9D686E]/30 outline-none shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">CVV</label>
                                            <input type="text" wire:model="cardCvv" maxlength="4" placeholder="123" class="w-full px-4 py-3 bg-white rounded-xl border border-slate-100 text-sm font-mono text-center focus:border-[#9D686E]/30 outline-none shadow-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Network Provider</label>
                                        <select wire:model="cardNetwork" class="w-full px-4 py-3 bg-white rounded-xl border border-slate-100 text-sm font-bold text-slate-600 outline-none focus:border-[#9D686E]/30 cursor-pointer shadow-sm">
                                            <option value="Visa">Visa</option>
                                            <option value="Mastercard">Mastercard</option>
                                            <option value="American Express">American Express</option>
                                            <option value="Discover">Discover</option>
                                            <option value="Bankcard">Bankcard</option>
                                            <option value="Bartercard">Bartercard</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Payment Date</label>
                                    <input type="date" wire:model="payDate" class="w-full px-4 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-sm font-bold text-slate-700">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Reference No.</label>
                                    <input type="text" wire:model="payRef" placeholder="e.g. INV-1234" class="w-full px-4 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-sm font-bold text-slate-700">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Additional Notes</label>
                                <textarea wire:model="payNotes" rows="2" class="w-full px-4 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none resize-none text-sm font-medium text-slate-600 leading-relaxed"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 border-t border-slate-50 shrink-0 bg-white">
                        <button type="submit" wire:loading.attr="disabled" class="w-full py-4 rounded-xl bg-[#9D686E] hover:bg-[#855359] text-white font-black shadow-xl shadow-[#9D686E]/20 transition-all transform active:scale-[0.98] flex items-center justify-center gap-3 uppercase tracking-widest text-xs disabled:opacity-75">
                            <span wire:loading.remove wire:target="savePayment" class="flex items-center gap-3"><span class="material-symbols-rounded">check_circle</span> Save Transaction</span>
                            <span wire:loading wire:target="savePayment" class="flex items-center gap-2">
                                <span class="material-symbols-rounded animate-spin text-lg">sync</span> 
                                Syncing to Cloud...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>


    <template x-teleport="body">
        <!-- EMAIL MODAL -->
        <div x-show="emailModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="emailModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="emailModal = false"></div>

            <div x-show="emailModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-2xl bg-white rounded-[24px] shadow-2xl overflow-hidden z-10 flex flex-col max-h-[90vh]">
                
                <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center shrink-0 bg-white">
                    <div class="flex items-center gap-3 text-[#9D686E]">
                        <span class="material-symbols-rounded text-2xl font-bold">mail</span>
                        <h3 class="font-black text-xl text-slate-800 uppercase tracking-tight">Compose Email</h3>
                    </div>
                    <button @click="emailModal = false" class="text-slate-400 hover:text-slate-600 transition p-1.5 hover:bg-slate-50 rounded-lg">
                        <span class="material-symbols-rounded text-2xl font-bold">close</span>
                    </button>
                </div>

                <form wire:submit.prevent="sendEmail" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-slate-50/30">
                        <div class="space-y-6">
                            <div class="space-y-4 bg-white p-8 rounded-[24px] border border-slate-100 shadow-sm">
                                <div class="grid grid-cols-1 md:grid-cols-[100px_1fr] items-center gap-2">
                                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest md:text-right md:pr-4">From:</label>
                                    <input type="text" wire:model="emailFrom" class="w-full px-4 py-2.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 text-xs font-bold text-slate-700">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-[100px_1fr] items-center gap-2">
                                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest md:text-right md:pr-4">Recipient:</label>
                                    <input type="text" wire:model="emailTo" class="w-full px-4 py-2.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 text-xs font-bold text-slate-700">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-[100px_1fr] items-center gap-2">
                                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest md:text-right md:pr-4">Cc:</label>
                                    <input type="text" wire:model="emailCc" class="w-full px-4 py-2.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 text-xs font-medium text-slate-500" placeholder="Optional CC recipients">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-[100px_1fr] items-center gap-2">
                                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest md:text-right md:pr-4">Subject:</label>
                                    <input type="text" wire:model="emailSubject" class="w-full px-4 py-2.5 bg-white border border-slate-100 rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 text-xs font-black text-slate-800">
                                </div>
                            </div>

                            <div class="bg-white p-6 rounded-[24px] border border-slate-100 shadow-sm flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-500">
                                        <span class="material-symbols-rounded text-2xl font-bold">attachment</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Selected Attachment</p>
                                        <p class="text-xs font-bold text-blue-600 truncate max-w-[300px]">{{ $emailAttachment }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" checked class="w-5 h-5 text-[#9D686E] rounded-lg border-slate-200 focus:ring-offset-0">
                                    <span class="text-[11px] font-bold text-slate-500">Include</span>
                                </div>
                            </div>

                            <div class="bg-white p-8 rounded-[24px] border border-slate-100 shadow-sm overflow-hidden">
                                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4">Message Body</label>
                                <textarea wire:model="emailBody" rows="10" class="w-full p-6 bg-slate-50/50 border-none rounded-2xl focus:ring-0 text-xs font-mono leading-relaxed resize-none text-slate-600 min-h-[300px] scrollbar-hide"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 border-t border-slate-100 shrink-0 bg-white flex justify-end gap-4">
                        <button type="button" @click="emailModal = false" class="px-8 py-4 rounded-xl border border-slate-200 text-slate-500 text-[11px] font-black hover:bg-slate-50 hover:text-slate-700 transition-all uppercase tracking-widest">Cancel</button>
                        <button type="submit" class="px-8 py-4 rounded-xl bg-[#9D686E] text-white text-[11px] font-black shadow-xl shadow-[#9D686E]/20 hover:bg-[#855359] hover:-translate-y-0.5 transition-all flex items-center gap-3 uppercase tracking-widest">
                            <span wire:loading.remove wire:target="sendEmail" class="flex items-center gap-3"><span class="material-symbols-rounded text-lg font-bold">send</span> Dispatch Email</span>
                            <span wire:loading wire:target="sendEmail" class="flex items-center gap-2"><span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> Sending...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>


    <template x-teleport="body">
        <!-- HISTORY MODAL -->
        <div x-show="historyModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="historyModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="historyModal = false"></div>

            <div x-show="historyModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl overflow-hidden z-10 flex flex-col max-h-[85vh]">
                
                <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center shrink-0 bg-white">
                    <div class="flex items-center gap-3 text-slate-800">
                        <span class="material-symbols-rounded text-[#9D686E] text-2xl font-bold">history</span>
                        <h3 class="font-black text-lg uppercase tracking-tight">Transmission Log</h3>
                    </div>
                    <button @click="historyModal = false" class="text-slate-400 hover:text-slate-600 transition p-1.5 hover:bg-slate-50 rounded-lg">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar bg-slate-50/20 p-8 space-y-4">
                    @if($booking->invoice_emailed || $emailLogs->count() > 0)
                    <div class="flex justify-end mb-4">
                        <button @click="historyClearModal = true" class="text-[10px] font-black text-red-500 hover:text-red-600 uppercase tracking-widest bg-red-50 px-4 py-2 rounded-xl border border-red-100/50 transition-all flex items-center gap-2 shadow-sm">
                            <span class="material-symbols-rounded text-sm">delete_sweep</span> Purge History
                        </button>
                    </div>
                    @endif

                    @if($booking->invoice_emailed)
                    <div class="flex items-center justify-between p-5 bg-emerald-50 rounded-[20px] border border-emerald-100 group relative shadow-sm transition-all hover:scale-[1.01]">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-white text-emerald-500 flex items-center justify-center shadow-sm border border-emerald-50">
                                <span class="material-symbols-rounded text-2xl">mark_email_read</span>
                            </div>
                            <div>
                                <p class="text-[11px] font-black text-slate-800 uppercase tracking-tighter">Invoice Emailed</p>
                                <p class="text-[10px] text-slate-500 font-bold">Standard dispatch successful</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-black bg-emerald-500 text-white px-2.5 py-1 rounded-lg uppercase tracking-widest shadow-sm">SENT</span>
                            <button @click="deleteLegacyModal = true" class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-red-500 transition-all p-2 hover:bg-white rounded-xl">
                                <span class="material-symbols-rounded text-lg font-bold">delete</span>
                            </button>
                        </div>
                    </div>
                    @endif

                    @forelse($emailLogs as $log)
                    @php
                    $icon = 'mail'; $color = 'text-slate-500 bg-slate-100 border-slate-200'; $accent = 'bg-slate-500';
                    if (str_contains($log->type, 'invoice')) { $icon = 'description'; $color = 'text-emerald-500 bg-white border-emerald-100'; $accent = 'bg-emerald-500'; }
                    elseif (str_contains($log->type, 'receipt')) { $icon = 'receipt_long'; $color = 'text-blue-500 bg-white border-blue-100'; $accent = 'bg-blue-500'; }
                    elseif (str_contains($log->type, 'po')) { $icon = 'text_snippet'; $color = 'text-purple-500 bg-white border-purple-100'; $accent = 'bg-purple-500'; }
                    elseif (str_contains($log->type, 'debt')) { $icon = 'payments'; $color = 'text-rose-500 bg-white border-rose-100'; $accent = 'bg-rose-500'; }
                    @endphp
                    <div class="flex items-start gap-4 p-5 rounded-[20px] border {{ explode(' ', $color)[1] === 'bg-white' ? 'bg-white border-slate-100 shadow-sm' : 'bg-slate-50 border-slate-100' }} group relative transition-all hover:translate-x-1">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center shrink-0 shadow-inner border border-white">
                            <span class="material-symbols-rounded {{ explode(' ', $color)[0] }} text-2xl font-bold">{{ $icon }}</span>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ str_replace('_', ' ', $log->type) }}</span>
                                <span class="text-[9px] font-bold text-slate-400">{{ \Carbon\Carbon::parse($log->sent_at)->format('d M, H:i') }}</span>
                            </div>
                            <p class="text-xs font-black text-slate-700 truncate pr-8" title="{{ $log->sent_to }}">To: {{ $log->sent_to }}</p>
                        </div>
                        <button @click="$wire.set('selectedLogToDelete', {{ $log->id }}); deleteSingleLogModal = true" class="absolute top-1/2 -translate-y-1/2 right-2 opacity-0 group-hover:opacity-100 text-slate-300 hover:text-red-500 transition-all p-3 hover:bg-red-50 rounded-2xl">
                            <span class="material-symbols-rounded text-xl font-bold">delete</span>
                        </button>
                    </div>
                    @empty
                    @if(!$booking->invoice_emailed)
                    <div class="text-center py-16 px-6 bg-white rounded-[32px] border border-dashed border-slate-200">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                            <span class="material-symbols-rounded text-4xl font-bold">history_toggle_off</span>
                        </div>
                        <h4 class="text-slate-800 font-black text-sm uppercase tracking-tight">No History Found</h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Ready for primary dispatch</p>
                    </div>
                    @endif
                    @endforelse
                </div>

                <div class="p-8 border-t border-slate-50 bg-white">
                    <button @click="historyModal = false" class="w-full py-4 rounded-xl bg-slate-100 text-slate-500 font-black hover:bg-slate-200 transition-all uppercase tracking-widest text-[10px]">Close History</button>
                </div>
            </div>
        </div>
    </template>


    <template x-teleport="body">
        <!-- CALENDAR MODAL -->
        <div x-show="calendarModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="calendarModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="calendarModal = false"></div>

            <div x-show="calendarModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-lg bg-white rounded-[24px] shadow-2xl overflow-hidden z-10 flex flex-col max-h-[90vh]">
                
                <div class="px-8 py-8 border-b border-slate-50 flex justify-between items-center shrink-0 bg-white">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#9D686E]/10 text-[#9D686E] flex items-center justify-center">
                            <span class="material-symbols-rounded text-2xl font-bold">calendar_month</span>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-xl uppercase tracking-tight">Capacity Check</h3>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-0.5">Global Schedule Review</p>
                        </div>
                    </div>
                    <button @click="calendarModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded text-2xl font-bold">close</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-8 bg-white">
                    <div class="bg-slate-50 p-6 rounded-[24px] mb-8 border border-slate-100">
                        <div class="flex items-center justify-center mb-4">
                            <div class="flex items-center gap-4">
                                <button wire:click="calPrev" class="w-10 h-10 flex items-center justify-center bg-white rounded-2xl text-slate-400 hover:text-[#9D686E] shadow-sm border border-slate-100 transition-all hover:scale-105 active:scale-95"><span class="material-symbols-rounded text-xl font-bold">chevron_left</span></button>
                                <p class="text-lg font-black text-slate-800 w-48 text-center truncate tracking-widest">{{ \Carbon\Carbon::create($calYear, $calMonth, 1)->format('F Y') }}</p>
                                <button wire:click="calNext" class="w-10 h-10 flex items-center justify-center bg-white rounded-2xl text-slate-400 hover:text-[#9D686E] shadow-sm border border-slate-100 transition-all hover:scale-105 active:scale-95"><span class="material-symbols-rounded text-xl font-bold">chevron_right</span></button>
                            </div>
                        </div>
                        <div class="flex items-center justify-center">
                            <div class="inline-flex items-center gap-2 bg-[#9D686E]/10 border border-[#9D686E]/20 rounded-full px-4 py-2">
                                <span class="material-symbols-rounded text-[#9D686E] text-sm">shield</span>
                                <span class="text-[11px] font-extrabold text-[#9D686E] uppercase tracking-widest">Global Soft Limit: 7 Missions / Day</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 text-[11px] font-black text-slate-300 mb-4 uppercase tracking-widest px-1">
                        <div class="text-center">Sun</div>
                        <div class="text-center">Mon</div>
                        <div class="text-center">Tue</div>
                        <div class="text-center">Wed</div>
                        <div class="text-center">Thu</div>
                        <div class="text-center">Fri</div>
                        <div class="text-center">Sat</div>
                    </div>

                    <div class="grid grid-cols-7 gap-3">
                        @foreach($calDays as $d)
                        @if($d === null)
                        <div></div>
                        @else
                        @php
                            $bg = 'bg-emerald-50'; $text = 'text-emerald-700'; $border = 'border-emerald-100'; $dot = 'bg-emerald-500';
                            if ($d['left'] == 0) { $bg = 'bg-red-50'; $text = 'text-red-700'; $border = 'border-red-100'; $dot = 'bg-red-500'; }
                            elseif ($d['left'] <= 2) { $bg='bg-amber-50' ; $text='text-amber-700' ; $border='border-amber-100' ; $dot='bg-amber-500' ; }
                            
                            $isSelected = $d['date'] === $tempSelectedDate;
                            $isOriginal = $d['date'] === $booking->event_date;
                            
                            $dayConflicts = array_intersect($bookedAttractions, $d['items'] ?? []);
                            $hasConflict = count($dayConflicts) > 0;
                            
                            $ring = $isSelected ? 'border-[#9D686E] bg-pink-50 ring-4 ring-[#9D686E]/10 shadow-md z-10' : '' ;
                            if($hasConflict) $ring .= ' ring-2 ring-red-500/50';
                            
                            $originStyle = $isOriginal && !$isSelected ? 'border-2 border-dashed border-[#9D686E] shadow-inner' : '';
                            $opacity = ($d['left'] == 0 && !$isSelected && !$isOriginal) ? 'opacity-40 grayscale-[0.5]' : '' ;
                        @endphp
                        <button wire:click="$set('tempSelectedDate', '{{ $d['date'] }}')" 
                                class="h-20 rounded-2xl border {{ $bg }} {{ $border }} {{ $text }} {{ $ring }} {{ $originStyle }} {{ $opacity }} flex flex-col items-center justify-center cursor-pointer transition-all relative hover:-translate-y-1 hover:shadow-lg group">
                            @if($isOriginal)
                                <div class="absolute -top-2 -right-1.5 bg-[#9D686E] text-white text-[8px] px-2 py-0.5 rounded-full font-black uppercase tracking-tighter shadow-sm z-20">Current</div>
                            @endif
                            @if($hasConflict)
                                <div class="absolute -top-1.5 -left-1.5 bg-red-600 text-white p-1 rounded-lg shadow-sm animate-pulse z-20">
                                    <span class="material-symbols-rounded text-[10px] font-black">warning</span>
                                </div>
                            @endif
                            @if($d['breach'] ?? false)
                                <div class="absolute -top-1.5 -right-1.5 bg-amber-600 text-white p-1 rounded-lg shadow-sm animate-pulse z-20">
                                    <span class="material-symbols-rounded text-[10px] font-black">inventory_2</span>
                                </div>
                            @endif
                            <span class="font-black text-lg">{{ $d['day'] }}</span>
                            <span class="text-[9px] uppercase tracking-tighter font-extrabold mt-0.5 opacity-60 group-hover:opacity-100">{{ $d['left'] }} Left</span>
                        </button>
                        @endif
                        @endforeach
                    </div>

                    @if($tempSelectedDate)
                    <div class="mt-8 p-6 bg-slate-50 border border-slate-100 rounded-[24px]">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Selected Date: {{ \Carbon\Carbon::parse($tempSelectedDate)->format('d M Y') }}</h4>
                            @if(count($modalConflicts) > 0 || count($modalCapacityBreaches) > 0)
                            <span class="bg-red-50 text-red-600 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest border border-red-100 flex items-center gap-1.5">
                                <span class="material-symbols-rounded text-sm">block</span> Move Blocked
                            </span>
                            @else
                            <span class="bg-emerald-50 text-emerald-600 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest border border-emerald-100 flex items-center gap-1.5">
                                <span class="material-symbols-rounded text-sm">check_circle</span> Optimized Path
                            </span>
                            @endif
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase mb-2">Booked on this day:</p>
                                <div class="flex flex-wrap gap-2">
                                    @php $dayItems = $dailyAttractions[$tempSelectedDate] ?? []; @endphp
                                    @forelse($dayItems as $itemName)
                                        @php $isConflict = in_array($itemName, $bookedAttractions); @endphp
                                        <span class="px-3 py-1.5 rounded-xl text-[10px] font-bold border transition-all {{ $isConflict ? 'bg-red-50 text-red-600 border-red-200 shadow-sm shadow-red-500/10' : 'bg-white text-slate-600 border-slate-200' }}">
                                            {{ $itemName }}
                                        </span>
                                    @empty
                                        <p class="text-[10px] font-bold text-slate-400 italic">No attractions reserved for this day.</p>
                                    @endforelse
                                </div>
                            </div>

                            @if(count($modalConflicts) > 0)
                            <div class="p-4 bg-red-100/50 border border-red-200 rounded-2xl flex items-start gap-4">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-red-600 shrink-0 shadow-sm">
                                    <span class="material-symbols-rounded text-2xl font-bold">report_problem</span>
                                </div>
                                <div>
                                    <p class="text-[11px] font-black text-red-800 uppercase tracking-tight">Scheduling Prohibition</p>
                                    <p class="text-[10px] font-bold text-red-700/80 leading-relaxed mt-0.5">
                                        Movement to this date is blocked. The following items are already committed to other bookings:
                                        <span class="font-black underline">{{ implode(', ', $modalConflicts) }}</span>
                                    </p>
                                </div>
                            </div>
                            @endif

                            @if(count($modalCapacityBreaches) > 0)
                            <div class="p-4 bg-amber-100/50 border border-amber-200 rounded-2xl flex items-start gap-4">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-600 shrink-0 shadow-sm">
                                    <span class="material-symbols-rounded text-2xl font-bold">inventory_2</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[11px] font-black text-amber-800 uppercase tracking-tight">Category Capacity Breach</p>
                                    <div class="mt-2 space-y-1">
                                        @foreach($modalCapacityBreaches as $cat => $data)
                                        <div class="flex justify-between items-center bg-white/50 p-1.5 px-2.5 rounded-lg border border-amber-200/50">
                                            <span class="text-[10px] font-bold text-amber-900">{{ $cat }}</span>
                                            <div class="flex items-center gap-3">
                                                <span class="text-[9px] font-black text-slate-500">{{ $data['current'] }} + {{ $data['added'] }}</span>
                                                <span class="material-symbols-rounded text-xs text-amber-600">arrow_forward</span>
                                                <span class="text-[10px] font-black text-red-600">{{ $data['current'] + $data['added'] }} / {{ $data['limit'] }}</span>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    <p class="text-[10px] font-bold text-amber-700/80 leading-relaxed mt-2">
                                        Select another date. This date has reached its maximum daily equipment allocation for the categories shown above.
                                    </p>
                                </div>
                            </div>
                            @endif
                            @if(!empty($modalNameConflicts))
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-4 animate-[fadeIn_0.3s_ease-out]">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-600 shrink-0 shadow-sm border border-amber-100">
                                    <span class="material-symbols-rounded text-2xl font-bold">person_alert</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[11px] font-black text-amber-800 uppercase tracking-tight">Potential Duplicate Detected</p>
                                    <p class="text-[10px] font-bold text-amber-700/80 leading-relaxed mt-0.5">
                                        The customer <span class="font-black underline">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span> already has existing bookings on this date:
                                    </p>
                                    <div class="mt-2 space-y-1">
                                        @foreach($modalNameConflicts as $nc)
                                        <div class="flex justify-between items-center bg-white/50 p-1.5 px-3 rounded-lg border border-amber-200/50">
                                            <span class="text-[9px] font-black text-amber-900">#{{ $nc['invoice_number'] ?? $nc['id'] }}</span>
                                            <span class="text-[9px] font-bold text-slate-500 italic">{{ strtoupper($nc['status']) }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    <p class="text-[10px] font-bold text-amber-600/80 mt-2 uppercase tracking-tighter italic">Warning only - you can still move if authorized.</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="mt-10 flex items-center gap-8 text-[10px] text-slate-400 font-extrabold justify-center border-t border-slate-50 pt-8">
                        <span class="inline-flex items-center gap-2.5"><span class="w-3 h-3 rounded-full bg-emerald-500 shadow-sm"></span>AVAILABLE</span>
                        <span class="inline-flex items-center gap-2.5 text-red-500"><span class="material-symbols-rounded text-sm">warning</span> CONFLICT</span>
                        <span class="inline-flex items-center gap-2.5 text-amber-500"><span class="material-symbols-rounded text-sm">inventory_2</span> CAPACITY</span>
                        <span class="inline-flex items-center gap-2.5 text-amber-600 font-black"><span class="material-symbols-rounded text-sm">person_alert</span> DUPLICATE</span>
                    </div>
                </div>

                <div class="p-8 border-t border-slate-50 bg-white">
                    <button wire:click="applySelectedDate()" 
                            @if(count($modalConflicts) > 0 || count($modalCapacityBreaches) > 0) disabled @endif
                            class="w-full py-5 rounded-2xl font-black transition-all transform active:scale-95 uppercase tracking-widest text-xs {{ (count($modalConflicts) > 0 || count($modalCapacityBreaches) > 0) ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-[#9D686E] text-white shadow-xl shadow-[#9D686E]/20 hover:bg-[#855359]' }}">
                        @if(count($modalConflicts) > 0)
                            <span class="flex items-center justify-center gap-2">
                                <span class="material-symbols-rounded text-sm">block</span>
                                Attraction Conflict
                            </span>
                        @elseif(count($modalCapacityBreaches) > 0)
                            <span class="flex items-center justify-center gap-2">
                                <span class="material-symbols-rounded text-sm">inventory_2</span>
                                Capacity Breach
                            </span>
                        @else
                            Apply Selection
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </template>


    <template x-teleport="body">
        <!-- PAYMENT DETAILS MODAL -->
        <div x-show="paymentDetailsModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="paymentDetailsModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="paymentDetailsModal = false"></div>

            <div x-show="paymentDetailsModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl overflow-hidden z-10 flex flex-col max-h-[80vh]">
                
                <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center shrink-0 bg-white">
                    <div class="flex items-center gap-3 text-slate-800">
                        <span class="material-symbols-rounded text-[#9D686E] text-2xl font-bold">receipt</span>
                        <h3 class="font-black text-lg uppercase tracking-tight">Receipt Details</h3>
                    </div>
                    <button @click="paymentDetailsModal = false" class="text-slate-400 hover:text-slate-600 transition p-1.5 hover:bg-slate-50 rounded-lg">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-8 bg-white">
                    @if($selectedPayment)
                    <div class="space-y-6">
                        <div class="flex items-center justify-between p-5 bg-slate-50 rounded-2xl border border-slate-100 shadow-inner">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Transaction</p>
                                <p class="text-2xl font-black text-slate-800">${{ number_format($selectedPayment->amount, 2) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="bg-[#9D686E] text-white text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest shadow-sm shadow-[#9D686E]/20">{{ $selectedPayment->type }}</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-white border border-slate-100 rounded-2xl">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Captured Date</p>
                                    <p class="text-[11px] font-bold text-slate-700">{{ \Carbon\Carbon::parse($selectedPayment->payment_date)->format('d M, Y') }}</p>
                                </div>
                                <div class="p-4 bg-white border border-slate-100 rounded-2xl">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Method</p>
                                    <p class="text-[11px] font-bold text-slate-700">{{ $selectedPayment->payment_method }}</p>
                                </div>
                            </div>
                            <div class="p-4 bg-white border border-slate-100 rounded-2xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Reference ID</p>
                                <p class="text-[11px] font-bold text-slate-700 break-all">{{ $selectedPayment->reference_no ?: 'GEN-' . strtoupper(substr(md5($selectedPayment->id), 0, 8)) }}</p>
                            </div>

                            @if($selectedPayment->notes)
                            <div class="p-5 bg-amber-50/50 border border-amber-100 rounded-2xl">
                                <div class="flex items-center gap-2 mb-2 text-amber-600">
                                    <span class="material-symbols-rounded text-base font-bold">sticky_note</span>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Internal Notes</p>
                                </div>
                                <p class="text-[11px] font-medium text-amber-900/70 leading-relaxed italic">"{{ $selectedPayment->notes }}"</p>
                            </div>
                            @endif

                            @if($selectedPayment->card_network)
                            <div class="p-5 bg-slate-900 text-white rounded-[24px] shadow-xl relative overflow-hidden group">
                                <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-rounded text-6xl font-bold">credit_card</span>
                                </div>
                                <div class="relative z-10">
                                    <div class="flex justify-between items-start mb-6">
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-50">Card Processor</p>
                                        <span class="font-black italic text-sm text-amber-400">{{ $selectedPayment->card_network }}</span>
                                    </div>
                                    <p class="text-lg font-mono tracking-[4px] mb-6">**** **** **** {{ substr($selectedPayment->card_number, -4) }}</p>
                                    <div class="flex gap-4">
                                        <div>
                                            <p class="text-[8px] font-black uppercase opacity-50 mb-1">Expiry</p>
                                            <p class="text-[10px] font-bold font-mono">{{ $selectedPayment->card_expiry }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <div class="p-8 border-t border-slate-50 bg-white">
                    <button @click="paymentDetailsModal = false" class="w-full py-4 rounded-xl bg-slate-100 text-slate-500 font-black hover:bg-slate-200 transition-all uppercase tracking-widest text-[10px]">Close Details</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- SENT SUCCESS MODAL -->
        <div x-show="sentSuccessModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4" x-cloak>
            <div x-show="sentSuccessModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="sentSuccessModal = false"></div>

            <div x-show="sentSuccessModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-10 z-10 text-center">
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6 text-emerald-600">
                    <span class="material-symbols-rounded text-4xl font-bold">check_circle</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3">Dispatch Success</h3>
                <p class="text-sm font-medium text-slate-500 mb-8 px-4 leading-relaxed tracking-tight">The communication has been successfully routed and transmitted to the recipient.</p>
                <button @click="sentSuccessModal = false" class="w-full py-4 bg-emerald-500 text-white hover:bg-emerald-600 font-black text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition-all active:scale-95 uppercase tracking-[0.2em]">Perfect</button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- CONFIRM EMAIL MODAL -->
        <div x-show="confirmEmailModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4" x-cloak>
            <div x-show="confirmEmailModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="confirmEmailModal = false"></div>

            <div x-show="confirmEmailModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-5 text-blue-600">
                    <span class="material-symbols-rounded text-3xl font-bold">mail</span>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2">Primary Dispatch?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">Are you sure you want to send the initial invoice to <strong class="text-slate-700">{{ $booking->customer_email }}</strong>?</p>
                <div class="flex justify-center gap-3">
                    <button @click="confirmEmailModal = false" class="flex-1 py-3.5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-xl transition-colors uppercase tracking-widest">Cancel</button>
                    <button wire:click="sendInvoiceEmail" class="flex-1 py-3.5 bg-blue-500 text-white hover:bg-blue-600 font-black text-[11px] rounded-xl shadow-md shadow-blue-500/20 transition-all active:scale-95 uppercase tracking-widest text-xs">Execute Send</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- DELETE SINGLE LOG MODAL -->
        <div x-show="deleteSingleLogModal" class="fixed inset-0 z-[10001] flex items-center justify-center p-4" x-cloak>
            <div x-show="deleteSingleLogModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="deleteSingleLogModal = false"></div>

            <div x-show="deleteSingleLogModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5 text-red-600">
                    <span class="material-symbols-rounded text-3xl font-bold">delete_forever</span>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2">Delete Log Entry?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">Permanently remove this transmission record? This action is irreversible.</p>
                <div class="flex justify-center gap-3">
                    <button @click="deleteSingleLogModal = false" class="flex-1 py-3.5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-xl transition-colors uppercase tracking-widest">Cancel</button>
                    <button wire:click="deleteEmailLog(selectedLogToDelete)" class="flex-1 py-3.5 bg-red-500 text-white hover:bg-red-600 font-black text-[11px] rounded-xl shadow-md shadow-red-500/20 transition-all active:scale-95 uppercase tracking-widest">Confirm</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- HISTORY CLEAR MODAL -->
        <div x-show="historyClearModal" class="fixed inset-0 z-[10010] flex items-center justify-center p-4" x-cloak>
            <div x-show="historyClearModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="historyClearModal = false"></div>

            <div x-show="historyClearModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center mx-auto mb-5 text-white">
                    <span class="material-symbols-rounded text-3xl font-bold">delete_sweep</span>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2">Purge Entire Log?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">This will wipe ALL communication records for this booking. Continue with deletion?</p>
                <div class="flex justify-center gap-3">
                    <button @click="historyClearModal = false" class="flex-1 py-3.5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-xl transition-colors uppercase tracking-widest">Cancel</button>
                    <button wire:click="clearHistory" class="flex-1 py-3.5 bg-red-600 text-white hover:bg-red-700 font-black text-[11px] rounded-xl shadow-md shadow-red-600/20 transition-all active:scale-95 uppercase tracking-widest">Purge All</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- DELETE LEGACY MODAL -->
        <div x-show="deleteLegacyModal" class="fixed inset-0 z-[10010] flex items-center justify-center p-4" x-cloak>
            <div x-show="deleteLegacyModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="deleteLegacyModal = false"></div>

            <div x-show="deleteLegacyModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-8 z-10 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5 text-red-600">
                    <span class="material-symbols-rounded text-3xl font-bold">history_toggle_off</span>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2">Reset Initial Flag?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed">Clear the primary "Invoice Sent" status flag? This allows a fresh primary dispatch.</p>
                <div class="flex justify-center gap-3">
                    <button @click="deleteLegacyModal = false" class="flex-1 py-3.5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-xl transition-colors uppercase tracking-widest">Cancel</button>
                    <button wire:click="deleteLegacyLog" class="flex-1 py-3.5 bg-red-500 text-white hover:bg-red-600 font-black text-[11px] rounded-xl shadow-md shadow-red-500/20 transition-all active:scale-95 uppercase tracking-widest">Reset Flag</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- TERMS CONFIRM MODAL -->
        <div x-show="termsConfirmModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="termsConfirmModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="termsConfirmModal = false"></div>

            <div x-show="termsConfirmModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-md bg-white rounded-[32px] shadow-2xl p-10 z-10 text-center overflow-hidden border border-gray-100">
                
                <div class="absolute top-0 right-0 w-32 h-32 bg-[#9D686E]/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                
                <div class="w-20 h-20 {{ $booking->terms_agreed ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-500' }} rounded-full flex items-center justify-center mx-auto mb-6 transition-colors shadow-inner">
                    <span class="material-symbols-rounded text-4xl">{{ $booking->terms_agreed ? 'history_edu' : 'assignment_turned_in' }}</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">
                    {{ $booking->terms_agreed ? 'Withdraw Agreement?' : 'Acknowledge Terms?' }}
                </h3>
                
                <p class="text-[14px] font-bold text-slate-500 mb-10 leading-relaxed px-2">
                    @if($booking->terms_agreed)
                        You are about to mark these terms as <strong>NOT</strong> agreed. The booking status will remain <strong>{{ $booking->status }}</strong>.
                    @else
                        By confirming, you acknowledge that the customer has formally agreed to the Big Fun terms and conditions. 
                        @if(in_array($booking->status, ['Pending', 'Draft']))
                            <br><br><span class="text-green-600 font-black">NOTE: This will automatically move the booking to CONFIRMED status.</span>
                        @endif
                    @endif
                </p>

                <div class="flex gap-4">
                    <button @click="termsConfirmModal = false" class="flex-1 py-4 text-slate-600 font-extrabold text-[12px] hover:bg-slate-50 rounded-2xl transition-all border border-slate-100 uppercase tracking-widest">Cancel</button>
                    <button wire:click="toggleTerms" @click="termsConfirmModal = false" class="flex-1 py-4 bg-[#9D686E] text-white hover:bg-[#855359] rounded-2xl font-black text-[12px] shadow-xl shadow-[#9D686E]/20 transition-all active:scale-95 uppercase tracking-widest">
                        Yes, Confirm
                    </button>
                </div>
            </div>
        </div>
    </template>

    <div
        x-on:close-modal.window="paymentModal = false; emailModal = false; deleteModal = false; calendarModal = false; paymentDetailsModal = false; draftModal = false; statusConfirmModal = false; confirmEmailModal = false; historyClearModal = false; deleteSingleLogModal = false; deleteLegacyModal = false; quotaWarningModal = false; quotaLimitModal = false; termsConfirmModal = false;"
        x-on:open-modal.window="
            let modalToOpen = typeof $event.detail === 'string' ? $event.detail : $event.detail[0];
            if (modalToOpen === 'paymentModal') paymentModal = true;
            if (modalToOpen === 'emailModal') emailModal = true;
            if (modalToOpen === 'calendarModal') calendarModal = true;
            if (modalToOpen === 'paymentDetailsModal') paymentDetailsModal = true;
            if (modalToOpen === 'draftModal') draftModal = true;
            if (modalToOpen === 'statusConfirmModal') statusConfirmModal = true;
            if (modalToOpen === 'sentSuccessModal') sentSuccessModal = true;
            if (modalToOpen === 'confirmEmailModal') confirmEmailModal = true;
            if (modalToOpen === 'historyClearModal') historyClearModal = true;
            if (modalToOpen === 'deleteSingleLogModal') deleteSingleLogModal = true;
            if (modalToOpen === 'deleteLegacyModal') deleteLegacyModal = true;
            if (modalToOpen === 'quotaWarningModal') quotaWarningModal = true;
            if (modalToOpen === 'quotaLimitModal') quotaLimitModal = true;
        "></div>
</div>