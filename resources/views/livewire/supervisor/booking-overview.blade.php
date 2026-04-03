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
        sentSuccessModal: false
    }"
    class="max-w-[1440px] mx-auto space-y-6">

    <!-- Header & Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('supervisor.calendar') }}" wire:navigate class="bg-white hover:bg-gray-50 text-slate-600 p-2.5 rounded-xl border border-gray-200 transition shadow-sm flex items-center justify-center">
                <span class="material-symbols-rounded text-2xl">arrow_back</span>
            </a>
            <div>
                <h1 class="text-3xl font-extrabold text-[#1E293B]">Booking #{{ $booking->id }}</h1>
                <p class="text-slate-500 font-medium mt-1 uppercase tracking-wide text-[10px]">Current Status: <span class="font-black underline">{{ $booking->status }}</span></p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            @if($balanceDue > 0 && $booking->status === 'Completed')
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

    <!-- Booking Origin & Timeline -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center gap-2 mb-2 pb-2 border-b border-gray-100 text-[#9D686E]">
            <span class="material-symbols-rounded">history_edu</span>
            <span class="text-sm font-bold uppercase tracking-wide">Booking Origin & Timeline</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 text-xs">
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
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 pb-4">
                <span class="text-[10px] font-extrabold text-gray-300 uppercase tracking-widest mr-1 w-full sm:w-auto">Actions:</span>
                <div class="flex flex-wrap gap-2">
                    <button wire:click="openEmailModal('receipt')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-regular fa-envelope"></i> Email Receipt</button>
                    <button wire:click="openEmailModal('invoice')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-regular fa-envelope"></i> Email Invoice</button>
                    <button wire:click="openEmailModal('po')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-regular fa-file-lines"></i> Email PO</button>
                    @if($balanceDue > 0 && $booking->status === 'Completed')
                    <button wire:click="openEmailModal('debt')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-red-600 border border-red-600 text-white shadow-sm hover:bg-white hover:text-red-600"><i class="fa-solid fa-file-invoice-dollar"></i> Email Debt</button>
                    @endif
                    <button @click="historyModal = true" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-clock-rotate-left"></i> History</button>
                </div>
                <div class="h-6 w-px bg-gray-200 mx-2 hidden lg:block"></div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('pdf.invoice', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-print"></i> Invoice</a>
                    <a href="{{ route('pdf.envelope', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-envelope-open-text"></i> Envelope</a>
                    <a href="{{ route('pdf.po', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-file-invoice"></i> PO/Quote</a>
                    @if($balanceDue > 0 && $booking->status === 'Completed')
                    <a href="{{ route('pdf.debt', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-red-600 border border-red-600 text-white shadow-sm hover:bg-white hover:text-red-600"><i class="fa-solid fa-file-invoice-dollar"></i> Debt Reminder</a>
                    @endif
                </div>
            </div>

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <span class="text-[10px] font-extrabold text-gray-300 uppercase tracking-widest mr-1">Manage:</span>
                    <!-- UPDATED LINKS BELOW -->
                    <a href="{{ route('supervisor.customer.profile', $booking->id) }}" wire:navigate class="flex items-center gap-1 text-xs font-bold text-slate-600 hover:text-[#9D686E] hover:underline transition"><i class="fa-regular fa-eye"></i> View Customer</a>
                    <span class="text-gray-200 hidden sm:inline">|</span>
                    <a href="{{ route('supervisor.bookings.edit', $booking->id) }}" wire:navigate class="flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition"><i class="fa-solid fa-pen-to-square"></i> Edit Booking</a>
                    <span class="text-gray-200 hidden sm:inline">|</span>
                    <div class="flex items-center gap-2">
                        <label for="attraction_cost_toggle" class="text-[10px] font-bold text-slate-500 uppercase cursor-pointer">PDF Price:</label>
                        <button wire:click="toggleAttractionCost" id="attraction_cost_toggle" class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $booking->include_attraction_cost ? 'bg-[#9D686E]' : 'bg-gray-200' }}">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $booking->include_attraction_cost ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                        <span class="text-[10px] font-black {{ $booking->include_attraction_cost ? 'text-[#9D686E]' : 'text-gray-400' }} uppercase">{{ $booking->include_attraction_cost ? 'Included' : 'Excluded' }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <div class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-lg border border-gray-100 flex-1 lg:flex-none">
                        <label class="text-[10px] font-bold text-gray-400 uppercase ml-2 whitespace-nowrap">Move To:</label>
                        <div class="relative flex items-center">
                            <input type="date" wire:model="newDate" class="text-xs border border-gray-200 rounded-l p-1 text-gray-700 focus:outline-none focus:border-[#9D686E] bg-white w-32">
                            <button wire:click="openCalendarModal" class="bg-[#9D686E] text-white p-1 rounded-r border border-[#9D686E] hover:bg-[#855359] transition h-full flex items-center justify-center">
                                <span class="material-symbols-rounded text-sm">calendar_month</span>
                            </button>
                        </div>
                        <button wire:click="moveDate" class="bg-white text-gray-600 border border-gray-200 text-[10px] uppercase font-bold px-3 py-1.5 rounded shadow-sm hover:bg-gray-100 transition ml-1">
                            <span wire:loading.remove wire:target="moveDate">Move</span>
                            <span wire:loading wire:target="moveDate">Moving...</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-lg border border-gray-100 flex-1 lg:flex-none">
                        <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Status:</label>
                        <select wire:model="newStatus" class="text-xs border-none bg-transparent font-bold text-gray-700 focus:ring-0 cursor-pointer py-1 w-full lg:w-auto">
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Hold">Hold</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Draft">Draft</option>
                        </select>
                        <button wire:click="updateStatus" class="bg-white text-[#9D686E] border border-gray-200 text-[10px] uppercase font-bold px-3 py-1.5 rounded shadow-sm hover:bg-gray-50 transition">
                            <span wire:loading.remove wire:target="updateStatus">Update</span>
                            <span wire:loading wire:target="updateStatus">Updating...</span>
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
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Side: Schedule -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 text-xs h-full">
                <div class="grid grid-cols-2 gap-4">
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
                            <div class="group relative flex items-center gap-3 p-2 rounded-lg bg-white border border-gray-100 hover:border-[#9D686E]/30 transiton shadow-sm">
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
                                        <button @click="selectedPayment = {{ json_encode($pay) }}; paymentDetailsModal = true" class="text-[#9D686E] hover:underline font-bold uppercase tracking-tighter">View Details</button>
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
            <div class="bg-[#9D686E]/10 rounded-xl p-5 space-y-3 border border-[#9D686E]/20 flex flex-col justify-center">
                <div class="space-y-2 max-h-[200px] overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($items as $item)
                        @if($item->unit_price > 0)
                        <div class="flex justify-between items-start text-[11px] border-b border-[#9D686E]/5 pb-1 last:border-0 mb-1">
                            <span class="font-medium text-slate-500 flex-1">{{ $item->item_name }} ({{ $item->total_qty }})</span>
                            <span class="font-bold text-slate-700 ml-4">${{ number_format($item->unit_price * $item->total_qty, 2) }}</span>
                        </div>
                        @endif
                    @endforeach

                    @if(($booking->duration_cost ?? 0) > 0)
                    <div class="flex justify-between items-center text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                        <span class="font-medium text-slate-500">Shift Duration Cost:</span>
                        <span class="font-bold text-slate-700">${{ number_format($booking->duration_cost, 2) }}</span>
                    </div>
                    @endif

                    @if($deliveryCost > 0)
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
                            @php $isSelected = isset($selectedExtras['add_'.$addon->id]); @endphp
                            @if($isSelected && $addon->addon_price > 0)
                                <div class="flex justify-between items-start text-[11px] border-b border-[#9D686E]/5 pb-1 mb-1">
                                    <span class="font-medium text-slate-500 flex-1">{{ $addon->addon_label }}</span>
                                    <span class="font-bold text-slate-700 ml-4">${{ number_format($addon->addon_price, 2) }}</span>
                                </div>
                            @endif
                        @endforeach

                        @foreach($catQuestions as $q)
                            @php 
                                $val = $selectedExtras['extra_'.$q->id] ?? $selectedExtras['q_'.$q->id] ?? null; 
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
                                    <span class="font-medium text-slate-500 flex-1">{{ $q->question_text }}</span>
                                    <span class="font-bold text-slate-700 ml-4">${{ number_format($price, 2) }}</span>
                                </div>
                            @endif
                        @endforeach
                    @endforeach

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
                <button wire:click="toggleTerms" class="px-1.5 py-0.5 rounded text-[10px] font-bold transition {{ $booking->terms_agreed ? 'bg-green-50 text-green-600 hover:bg-green-100' : 'bg-red-50 text-red-600 hover:bg-red-100' }}">
                    {{ $booking->terms_agreed ? 'Yes' : 'No' }}
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
                <div class="space-y-2">
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Type</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->event_type }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Contact</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Event Date</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ \Carbon\Carbon::parse($booking->event_date)->format('l d/m/Y') }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Time</span><span class="text-[0.75rem] font-bold text-[#9D686E] w-3/5 text-left">{{ $timeString }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Duration</span><span class="text-[0.75rem] font-bold text-gray-800 w-3/5 text-left">{{ $booking->duration ?: '-' }} @if($booking->duration_cost > 0) <span class="text-[#9D686E] ml-1">(${{ number_format($booking->duration_cost, 2) }})</span> @endif</span></div>
                    @if($booking->operational_hours)
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Op. Hours</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->operational_hours }}</span></div>
                    @endif
                    @if($booking->hire_type)
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Hire Type</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->hire_type }}</span></div>
                    @endif
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Delivery</span><span class="text-[0.75rem] font-bold text-gray-800 w-3/5 text-left">{{ $booking->delivery_area ?: 'Not Set' }} <span class="text-[#9D686E] ml-1">(${{ number_format($deliveryCost, 2) }})</span></span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Pax</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->expected_people }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Booked By</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->booked_by ?? 'System' }}</span></div>
                </div>

                {{-- EXTRA CONFIGURATIONS SECTION --}}
                <div class="mt-8 pt-8 border-t border-gray-100">
                    <div class="flex items-center gap-2 mb-6 text-[#9D686E]">
                        <span class="material-symbols-rounded">tune</span>
                        <span class="text-sm font-bold uppercase tracking-wide">Extra Configurations</span>
                    </div>

                    <div class="space-y-8">
                        @foreach($activeCategories as $cat)
                            @php
                                $catAddons = $config['addons'][$cat] ?? [];
                                $catQuestions = $config['questions'][$cat] ?? [];
                                $catDropdowns = $config['dropdowns'][$cat] ?? [];

                                // Check if anything is actually selected in this category
                                $hasSelectedAddon = collect($catAddons)->contains(fn($a) => isset($selectedExtras['add_'.$a->id]));
                                $hasSelectedQuestion = collect($catQuestions)->contains(function($q) use ($selectedExtras) {
                                    $val = $selectedExtras['extra_'.$q->id] ?? $selectedExtras['q_'.$q->id] ?? null;
                                    if (!$val) return false;
                                    $parts = explode('|', $val);
                                    return ($parts[1] ?? 'yes') === 'yes';
                                });
                                $hasSelectedDropdown = collect($catDropdowns)->contains(function($dd) use ($selectedExtras) {
                                    $val = $selectedExtras['dd_'.$dd['id']] ?? null;
                                    return $val && $val !== 'Not Selected' && $val !== 'None';
                                });
                                $hasLogistics = ($cat === 'General Logistics' && ($booking->hire_type || $booking->logistics_surfaces));

                                $shouldShowCategory = $hasSelectedAddon || $hasSelectedQuestion || $hasSelectedDropdown || $hasLogistics;
                            @endphp

                            @if($shouldShowCategory)
                            <div class="bg-gray-50/50 rounded-3xl border border-gray-100 overflow-hidden">
                                <div class="bg-gray-100/50 px-6 py-3 border-b border-gray-200 flex justify-between items-center">
                                    <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest">{{ $cat }}</span>
                                    <span class="text-[9px] font-bold text-[#9D686E] bg-[#9D686E]/5 px-2 py-0.5 rounded-full border border-[#9D686E]/10">Active Configuration</span>
                                </div>
                                <div class="p-6 space-y-6">
                                    {{-- DROPDOWNS --}}
                                    @if(!empty($catDropdowns))
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($catDropdowns as $dd)
                                            @php $val = $selectedExtras['dd_'.$dd['id']] ?? null; @endphp
                                            @if($val && $val !== 'Not Selected')
                                                <div>
                                                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-2 block tracking-tight">{{ $dd['label'] }}</label>
                                                    <div class="text-xs font-bold text-[#9D686E] border border-[#9D686E]/30 bg-[#9D686E]/5 rounded-xl px-4 py-2.5 shadow-sm transition-all">
                                                        {{ $val }}
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    @endif

                                    {{-- ADDONS & QUESTIONS --}}
                                    @if(!empty($catAddons) || !empty($catQuestions) || ($cat === 'General Logistics' && $booking->logistics_surfaces))
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        {{-- Surface special case --}}
                                        @if($cat === 'General Logistics' && $booking->logistics_surfaces)
                                        <div class="flex items-center justify-between bg-white p-3.5 rounded-2xl border border-emerald-100 shadow-sm ring-1 ring-emerald-50">
                                            <div class="flex items-center gap-3">
                                                <div class="w-6 h-6 rounded-lg bg-emerald-500 text-white flex items-center justify-center shadow-sm">
                                                    <span class="material-symbols-rounded text-sm">check</span>
                                                </div>
                                                <span class="text-xs font-bold text-slate-700">Surface: {{ $booking->logistics_surfaces }}</span>
                                            </div>
                                        </div>
                                        @endif

                                        @foreach($catAddons as $addon)
                                            @php $isSelected = isset($selectedExtras['add_'.$addon->id]); @endphp
                                            @if($isSelected)
                                                <div class="flex items-center justify-between p-3.5 rounded-2xl border bg-white border-emerald-100 shadow-sm ring-1 ring-emerald-50 transition-all">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-6 h-6 rounded-lg bg-emerald-500 text-white shadow-emerald-100 flex items-center justify-center shadow-sm">
                                                            <span class="material-symbols-rounded text-sm">check</span>
                                                        </div>
                                                        <span class="text-xs font-bold text-slate-700">{{ $addon->addon_label }}</span>
                                                    </div>
                                                    @if($addon->addon_price > 0)
                                                        <span class="text-[10px] font-black text-[#9D686E]">+${{ number_format($addon->addon_price, 2) }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach

                                        @foreach($catQuestions as $q)
                                            @php 
                                                $val = $selectedExtras['extra_'.$q->id] ?? null; 
                                                if (!$val) $val = $selectedExtras['q_'.$q->id] ?? null;
                                                
                                                $price = $q->yes_price;
                                                $isYes = false;
                                                if ($val) {
                                                    $parts = explode('|', $val);
                                                    $price = (float)($parts[0] ?? 0);
                                                    $answer = $parts[1] ?? 'yes';
                                                    $isYes = ($answer === 'yes');
                                                }
                                            @endphp
                                            @if($isYes)
                                                <div class="flex items-center justify-between p-3.5 rounded-2xl border bg-white border-emerald-100 shadow-sm ring-1 ring-emerald-50 transition-all">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-6 h-6 rounded-lg bg-emerald-500 text-white shadow-emerald-100 flex items-center justify-center shadow-sm">
                                                            <span class="material-symbols-rounded text-sm">check</span>
                                                        </div>
                                                        <span class="text-xs font-bold text-slate-700">{{ $q->question_text }}</span>
                                                    </div>
                                                    @if($price > 0)
                                                        <span class="text-[10px] font-black text-[#9D686E]">+${{ number_format($price, 2) }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
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
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Name</span><span class="text-[0.75rem] font-bold text-slate-800 w-3/5 text-left">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Org / Company</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->customer_organization ?: '-' }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">ABN No.</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->customer_abn ?: '-' }}</span></div>
                    
                    <div class="grid grid-cols-2 gap-4 border-b border-dotted border-gray-200 pb-1">
                        <div class="flex flex-col"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Mobile</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->customer_phone }}</span></div>
                        <div class="flex flex-col"><span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Business Phone</span><span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->customer_business_phone ?: '-' }}</span></div>
                    </div>

                    <div class="flex flex-col mb-1 pb-1 border-b border-dotted border-gray-200">
                        <span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Business Address</span>
                        <span class="text-[0.75rem] font-medium text-slate-800">{{ $booking->business_address ?: 'Not provided' }}</span>
                    </div>

                    <div class="mt-2 pt-2 border-t border-gray-50">
                        <span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide">Email</span>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-sm text-blue-600 truncate mr-2">{{ $booking->customer_email }}</span>
                            <a href="mailto:{{ $booking->customer_email }}" class="text-[10px] text-amber-600 font-bold uppercase hover:underline shrink-0 bg-amber-50 px-2 py-1 rounded">Email</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rides Booked -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">attractions</span><span class="text-sm font-bold uppercase tracking-wide">Rides Booked</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[300px]">
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
                                    @php $isSelected = isset($selectedExtras['add_'.$addon->id]); @endphp
                                    @if($isSelected && $addon->addon_price > 0)
                                        <tr class="hover:bg-gray-50 transition border-b border-gray-50 last:border-0 bg-slate-50/30">
                                            <td class="p-2 font-bold text-slate-600 py-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="material-symbols-rounded text-sm text-[#9D686E]">add_circle</span>
                                                    <span class="text-xs uppercase tracking-tight">{{ $addon->addon_label }}</span>
                                                </div>
                                            </td>
                                            <td class="p-2 py-4 italic text-[10px] text-slate-400">Extra / Logistics</td>
                                            <td class="p-2 text-center font-bold text-slate-500 py-4">1</td>
                                            <td class="p-2 text-right font-black text-[#9D686E] py-4">${{ number_format($addon->addon_price, 2) }}</td>
                                        </tr>
                                    @endif
                                @endforeach

                                @foreach($catQuestions as $q)
                                    @php 
                                        $val = $selectedExtras['extra_'.$q->id] ?? $selectedExtras['q_'.$q->id] ?? null; 
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
                                                    <span class="text-xs uppercase tracking-tight">{{ $q->question_text }}</span>
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

    <!-- DELETE MODAL -->
    <div x-show="deleteModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="deleteModal = false"></div>
            <div x-show="deleteModal" x-transition class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md z-10 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-red-600"><span class="material-symbols-rounded text-3xl">delete_forever</span></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Delete Booking?</h3>
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to permanently delete Booking #{{ $booking->id }}? This cannot be undone.</p>
                <div class="flex justify-center gap-3">
                    <button @click="deleteModal = false" class="px-5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-bold">Cancel</button>
                    <button wire:click="deleteBooking" class="px-5 py-2.5 rounded-xl bg-red-500 text-white hover:bg-red-600 text-sm font-bold shadow-lg shadow-red-200">Yes, Delete It</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DRAFT MODAL -->
    <div x-show="draftModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="draftModal = false"></div>
            <div x-show="draftModal" x-transition class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md z-10 text-center">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 text-orange-600">
                    <span class="material-symbols-rounded text-3xl">warning</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Publish Draft?</h3>
                <p class="text-sm text-gray-500 mb-6">
                    This booking is currently a <strong>Draft</strong>. Are you sure you want to change its status to <strong class="text-[#9D686E]">{{ $newStatus }}</strong>? <br><br> Make sure you have edited and confirmed all necessary details first!
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="draftModal = false" class="px-5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-bold transition">Cancel</button>
                    <button wire:click="executeStatusUpdate" class="px-5 py-2.5 rounded-xl bg-orange-500 text-white hover:bg-orange-600 text-sm font-bold shadow-lg shadow-orange-200 transition">Yes, Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <!-- STATUS CONFIRM MODAL -->
    <div x-show="statusConfirmModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="statusConfirmModal = false"></div>
            <div x-show="statusConfirmModal" x-transition class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md z-10 text-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600">
                    <span class="material-symbols-rounded text-3xl">info</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Confirm Status Change</h3>
                <p class="text-sm text-gray-500 mb-6">
                    You are changing the status from <strong class="text-gray-700">{{ $booking->status }}</strong> to <strong class="text-[#9D686E]">{{ $newStatus }}</strong>. Are you sure you want to proceed?
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="statusConfirmModal = false" class="px-5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-bold transition">Cancel</button>
                    <button wire:click="executeStatusUpdate" class="px-5 py-2.5 rounded-xl bg-blue-500 text-white hover:bg-blue-600 text-sm font-bold shadow-lg shadow-blue-200 transition">Confirm Change</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PAYMENT MODAL (RESTORED WITH ALL DROPDOWNS) -->
    <div x-show="paymentModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="paymentModal = false"></div>
            <div x-show="paymentModal" x-transition class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm z-10 flex flex-col">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-gray-800">Record Payment</h3>
                    <button @click="paymentModal = false" class="text-gray-400 hover:text-gray-600 transition"><span class="material-symbols-rounded">close</span></button>
                </div>

                <form wire:submit="savePayment" class="space-y-4">

                    <div class="flex justify-between text-xs text-gray-500 border-b border-gray-100 pb-2">
                        <div>
                            <div class="font-bold text-gray-700 truncate max-w-[180px]">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</div>
                            <span class="text-[10px] text-gray-400 font-mono mt-0.5 inline-block">INV {{ $booking->invoice_number ?? $booking->id }}</span>
                        </div>
                        <div class="text-right">
                            <span>Due: <span class="font-bold text-[#9D686E]">${{ number_format($balanceDue, 2) }}</span></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Payment For</label>
                        <select wire:model.live="payType" class="w-full p-2.5 bg-gray-50 rounded-lg border border-transparent focus:border-[#9D686E] outline-none cursor-pointer text-sm">
                            <option value="Deposit">Deposit</option>
                            <option value="Remaining Balance">Remaining Balance</option>
                            <option value="Full Amount">Full Amount</option>
                            <option value="Partial Payment">Partial Payment</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Amount ($)</label>
                        <input type="number" wire:model="payAmount" step="0.01" required class="w-full p-2.5 bg-gray-50 rounded-lg border border-transparent focus:bg-white focus:border-[#9D686E] outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Method</label>
                        <select wire:model.live="payMethod" class="w-full p-2.5 bg-gray-50 rounded-lg border border-transparent focus:border-[#9D686E] outline-none cursor-pointer text-sm">
                            <option value="EFT">EFT / Bank Transfer</option>
                            <option value="Card Holder">Credit/Debit Card</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>

                    {{-- EFT Specifics --}}
                    <div x-show="payMethod === 'EFT'" x-transition>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Specific Method</label>
                        <select wire:model="eftMethod" class="w-full p-2.5 bg-gray-50 rounded-lg border border-transparent focus:border-[#9D686E] outline-none cursor-pointer text-sm">
                            <option value="Direct Deposit">Direct Deposit</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Osko">Osko</option>
                            <option value="PayID">PayID</option>
                        </select>
                    </div>

                    {{-- Card Specifics --}}
                    <div x-show="payMethod === 'Card Holder'" x-transition class="space-y-3 bg-gray-50/50 p-3 rounded-lg border border-dashed border-gray-200">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-1 uppercase tracking-wider">Card Number</label>
                            <input type="text" wire:model="cardNum" 
                                x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim()"
                                maxlength="19" 
                                class="w-full p-2 bg-white rounded border border-gray-100 text-xs text-gray-600 outline-none focus:border-[#9D686E]" 
                                placeholder="1234 5678 1234 5678">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1 uppercase tracking-wider">Expiry (MM/YY)</label>
                                <input type="text" wire:model="cardExpiry" 
                                    x-on:input="
                                        let v = $el.value.replace(/\D/g, '');
                                        if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2,4);
                                        $el.value = v;
                                    "
                                    maxlength="5" 
                                    class="w-full p-2 bg-white rounded border border-gray-100 text-xs text-gray-600 outline-none focus:border-[#9D686E]" 
                                    placeholder="MM/YY">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1 uppercase tracking-wider">CVV</label>
                                <input type="text" wire:model="cardCvv" 
                                    maxlength="4" 
                                    class="w-full p-2 bg-white rounded border border-gray-100 text-xs text-gray-600 outline-none focus:border-[#9D686E]" 
                                    placeholder="123">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-1 uppercase tracking-wider">Network</label>
                            <select wire:model="cardNetwork" class="w-full p-2 bg-white rounded border border-gray-100 text-xs text-gray-600 outline-none focus:border-[#9D686E] cursor-pointer">
                                <option value="Visa">Visa</option>
                                <option value="Mastercard">Mastercard</option>
                                <option value="American Express">American Express</option>
                                <option value="Discover">Discover</option>
                                <option value="Bankcard">Bankcard</option>
                                <option value="Bartercard">Bartercard</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Date</label>
                        <input type="date" wire:model="payDate" class="w-full p-2.5 bg-gray-50 rounded-lg border border-transparent focus:bg-white focus:border-[#9D686E] outline-none text-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Reference No.</label>
                        <input type="text" wire:model="payRef" placeholder="e.g. INV-1234" class="w-full p-2.5 bg-gray-50 border-transparent focus:border-[#9D686E] rounded-lg outline-none text-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Notes</label>
                        <textarea wire:model="payNotes" rows="2" class="w-full p-2.5 bg-gray-50 border-transparent focus:border-[#9D686E] rounded-lg outline-none resize-none text-sm"></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-[#9D686E] hover:bg-[#855359] text-white font-bold shadow-lg shadow-[#9D686E]/20 transition transform active:scale-95 text-sm mt-2">
                        <span wire:loading.remove wire:target="savePayment">Save Payment</span>
                        <span wire:loading wire:target="savePayment">Processing...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- EMAIL MODAL -->
    <div x-show="emailModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="emailModal = false"></div>
            <div x-show="emailModal" x-transition class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl z-10 flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white rounded-t-xl">
                    <div class="flex items-center gap-2 text-[#9D686E]"><span class="material-symbols-rounded text-xl">mail</span>
                        <h3 class="font-bold text-lg">Send Email</h3>
                    </div>
                    <button @click="emailModal = false" class="text-gray-400 hover:text-gray-600 transition"><span class="material-symbols-rounded text-2xl">close</span></button>
                </div>
                <div class="p-6">
                    <form wire:submit="sendEmail">
                        <div class="flex items-start mb-2">
                            <label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3 pt-2">From:</label>
                            <div class="flex-grow space-y-2">
                                <div class="flex items-center gap-2"><span class="text-[10px] font-bold text-gray-400 w-12 text-right">Address:</span><input type="text" value="bigfun.qld.au@gmail.com" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-100 text-slate-500 cursor-not-allowed" readonly></div>
                                <div class="flex items-center gap-2"><span class="text-[10px] font-bold text-gray-400 w-12 text-right">Name:</span><input type="text" value="BigFun" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-100 text-slate-500 cursor-not-allowed" readonly></div>
                            </div>
                        </div>
                        <div class="flex items-center mb-2"><label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">To:</label><input type="text" wire:model="emailTo" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9D686E]"></div>
                        <div class="flex items-center mb-2"><label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Cc:</label><input type="text" wire:model="emailCc" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9D686E]"></div>
                        <div class="flex items-center mb-2"><label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Bcc:</label><input type="text" wire:model="emailBcc" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9D686E]"></div>
                        <div class="flex items-center mb-2"><label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Subject:</label><input type="text" wire:model="emailSubject" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9D686E] font-medium text-slate-800"></div>
                        <div class="flex items-center mb-4"><label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Attachment:</label>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" checked class="w-4 h-4 text-[#9D686E] rounded border-gray-300">
                                <span class="text-xs font-medium text-blue-600 underline">{{ $emailAttachment }}</span>
                            </div>
                        </div>
                        <div class="flex items-start mb-4"><label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Body:</label><textarea wire:model="emailBody" rows="8" class="flex-grow text-xs p-2 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9D686E] font-mono leading-relaxed resize-none"></textarea></div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="button" @click="emailModal = false" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 text-xs font-bold hover:bg-gray-50 transition">Cancel</button>
                            <button type="submit" class="px-6 py-2 rounded-lg bg-[#9D686E] text-white text-xs font-bold shadow-md shadow-[#9D686E]/20 hover:bg-[#855359] transition flex items-center gap-2">
                                <span wire:loading.remove wire:target="sendEmail"><i class="fa-solid fa-paper-plane mr-1"></i> Send Email</span>
                                <span wire:loading wire:target="sendEmail">Sending...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- HISTORY MODAL -->
    <div x-show="historyModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="historyModal = false"></div>
            <div x-show="historyModal" x-transition class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm z-10 flex flex-col">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-800">Email History</h3>
                    <button @click="historyModal = false" class="text-gray-400 hover:text-gray-600 transition"><span class="material-symbols-rounded">close</span></button>
                </div>
                <div class="space-y-3 max-h-[60vh] overflow-y-auto custom-scrollbar pr-2">
                    @if($booking->invoice_emailed)
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-100 mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-green-200 text-green-700 flex items-center justify-center"><i class="fa-solid fa-check"></i></div>
                            <div>
                                <p class="text-xs font-bold text-gray-700">Invoice Marked as Sent</p>
                                <p class="text-[10px] text-gray-500">Legacy record</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-green-600">SENT</span>
                    </div>
                    @endif

                    @forelse($emailLogs as $log)
                    @php
                    $icon = 'fa-envelope'; $color = 'text-gray-500 bg-gray-100'; $title = 'Email Sent';
                    if (str_contains($log->type, 'invoice')) { $icon = 'fa-file-invoice-dollar'; $color = 'text-green-600 bg-green-50 border-green-200'; $title = 'Invoice Sent'; }
                    elseif (str_contains($log->type, 'receipt')) { $icon = 'fa-receipt'; $color = 'text-blue-600 bg-blue-50 border-blue-200'; $title = 'Receipt Sent'; }
                    elseif (str_contains($log->type, 'po')) { $icon = 'fa-file-contract'; $color = 'text-purple-600 bg-purple-50 border-purple-200'; $title = 'PO Sent'; }
                    @endphp
                    <div class="flex items-start gap-3 p-3 rounded-lg border {{ $color }}">
                        <div class="mt-1 w-8 h-8 rounded-full bg-white/80 flex items-center justify-center shrink-0 shadow-sm"><i class="fa-solid {{ $icon }}"></i></div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-bold uppercase tracking-wide opacity-90">{{ $title }}</span>
                                <span class="text-[10px] font-medium opacity-70">{{ \Carbon\Carbon::parse($log->sent_at)->format('d/m/y H:i') }}</span>
                            </div>
                            <div class="text-xs font-medium mt-0.5 truncate max-w-[200px]">To: {{ $log->sent_to }}</div>
                        </div>
                    </div>
                    @empty
                    @if(!$booking->invoice_emailed)
                    <div class="text-center text-xs text-gray-400 italic py-4">No email history found.</div>
                    @endif
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- CALENDAR MODAL -->
    <div x-show="calendarModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="calendarModal = false"></div>
            <div x-show="calendarModal" x-transition class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg z-10">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-gray-800 text-xl">Check Availability</h3>
                    <button @click="calendarModal = false" class="text-gray-400 hover:text-gray-600"><span class="material-symbols-rounded">close</span></button>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Limit: 7/day</p>
                    <div class="flex items-center gap-3">
                        <button wire:click="calPrev" class="text-slate-400 hover:text-slate-600"><span class="material-symbols-rounded text-lg">chevron_left</span></button>
                        <p class="text-lg font-bold text-slate-800 w-36 text-center">{{ \Carbon\Carbon::create($calYear, $calMonth, 1)->format('F Y') }}</p>
                        <button wire:click="calNext" class="text-slate-400 hover:text-slate-600"><span class="material-symbols-rounded text-lg">chevron_right</span></button>
                    </div>
                </div>

                <div class="grid grid-cols-7 text-xs font-extrabold text-slate-400 mb-2 uppercase tracking-wide">
                    <div class="text-center">Sun</div>
                    <div class="text-center">Mon</div>
                    <div class="text-center">Tue</div>
                    <div class="text-center">Wed</div>
                    <div class="text-center">Thu</div>
                    <div class="text-center">Fri</div>
                    <div class="text-center">Sat</div>
                </div>

                <div class="grid grid-cols-7 gap-2">
                    @foreach($calDays as $d)
                    @if($d === null)
                    <div></div>
                    @else
                    @php
                    $bg = 'bg-emerald-50'; $text = 'text-emerald-700'; $border = 'border-emerald-200'; $dot = 'bg-emerald-500';
                    if ($d['left'] == 0) { $bg = 'bg-red-50'; $text = 'text-red-700'; $border = 'border-red-200'; $dot = 'bg-red-500'; }
                    elseif ($d['left'] <= 2) { $bg='bg-amber-50' ; $text='text-amber-700' ; $border='border-amber-200' ; $dot='bg-amber-500' ; }
                        $isSelected=$d['date']===$tempSelectedDate;
                        $ring=$isSelected ? 'border-[#9D686E] bg-pink-50 ring-2 ring-[#9D686E] shadow-md z-10' : '' ;
                        $opacity=($d['left']==0 && !$isSelected) ? 'opacity-50' : '' ;
                        @endphp
                        <button wire:click="$set('tempSelectedDate', '{{ $d['date'] }}')" class="h-14 rounded-xl border {{ $bg }} {{ $border }} {{ $text }} {{ $ring }} {{ $opacity }} flex flex-col items-center justify-center cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md hover:border-[#9D686E]">
                        <span class="font-bold text-sm">{{ $d['day'] }}</span>
                        <span class="text-[9px] uppercase tracking-wide font-medium mt-0.5">{{ $d['left'] }} Left</span>
                        <span class="w-1.5 h-1.5 rounded-full mt-1 {{ $dot }}"></span>
                        </button>
                        @endif
                        @endforeach
                </div>

                <div class="mt-6 flex items-center gap-4 text-xs text-slate-500 font-bold justify-center bg-gray-50 p-2 rounded-lg">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Open</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>Busy</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Full</span>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 mt-4">
                    <button wire:click="applySelectedDate" class="px-6 py-2.5 rounded-xl bg-[#9D686E] text-white font-bold shadow-lg shadow-[#9D686E]/20 hover:bg-[#855359] transition transform active:scale-95">Select Date</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PAYMENT DETAILS MODAL -->
    <div x-show="paymentDetailsModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="paymentDetailsModal = false"></div>
            <div x-show="paymentDetailsModal" x-transition class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm z-10">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-800">Transaction Details</h3>
                    <button @click="paymentDetailsModal = false" class="text-gray-400 hover:text-gray-600 transition"><span class="material-symbols-rounded">close</span></button>
                </div>
                <div class="space-y-3 text-sm text-gray-700" x-text="!selectedPayment ? 'Loading...' : ''">
                    <template x-if="selectedPayment">
                        <div>
                            <div class="flex justify-between mb-2"><span class="font-bold text-gray-500">Amount:</span> <span class="text-green-600 font-bold">$<span x-text="parseFloat(selectedPayment.amount).toFixed(2)"></span></span></div>
                            <div class="flex justify-between mb-2"><span class="font-bold text-gray-500">Date:</span> <span class="font-medium" x-text="new Date(selectedPayment.payment_date).toLocaleDateString('en-GB')"></span></div>
                            <div class="flex justify-between mb-2"><span class="font-bold text-gray-500">Method:</span> <span class="font-medium" x-text="selectedPayment.payment_method === 'Card Holder' ? 'Credit/Debit Card' : selectedPayment.payment_method"></span></div>
                            <template x-if="selectedPayment.payment_method === 'Card Holder' && selectedPayment.card_number">
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 mt-2 space-y-1">
                                    <div class="flex justify-between text-[11px]"><span class="text-gray-400">Card:</span> <span class="font-mono font-bold" x-text="'**** **** ' + selectedPayment.card_number.replace(/\s/g, '').slice(-8, -4) + ' ' + selectedPayment.card_number.replace(/\s/g, '').slice(-4)"></span></div>
                                    <div class="flex justify-between text-[11px]"><span class="text-gray-400">Expiry:</span> <span class="font-mono font-bold">**/**</span></div>
                                    <div class="flex justify-between text-[11px]"><span class="text-gray-400">CVV:</span> <span class="font-mono font-bold">***</span></div>
                                    <div class="flex justify-between text-[11px]"><span class="text-gray-400">Network:</span> <span class="font-bold text-blue-600" x-text="selectedPayment.card_network"></span></div>
                                </div>
                            </template>
                            <template x-if="selectedPayment.reference">
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <span class="font-bold text-gray-500 block mb-1">Reference / Notes:</span>
                                    <div class="bg-gray-50 p-2 rounded text-xs italic text-gray-600" x-text="selectedPayment.reference + (selectedPayment.notes ? ' - ' + selectedPayment.notes : '')"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <button @click="paymentDetailsModal = false" class="w-full mt-6 py-2.5 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold transition">Close</button>
            </div>
        </div>
    </div>

    <!-- SENT SUCCESS MODAL -->
    <div x-show="sentSuccessModal" class="fixed inset-0 z-[10001] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="sentSuccessModal = false; $wire.resetEmailState()"></div>
            <div x-show="sentSuccessModal" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative bg-white rounded-[2rem] shadow-2xl p-8 w-full max-w-sm z-10 text-center">
                
                <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 ring-8 ring-green-50/50">
                    <span class="material-symbols-rounded text-4xl">check_circle</span>
                </div>

                <h3 class="text-2xl font-bold text-gray-800 mb-2">Email Sent!</h3>
                <p class="text-gray-500 text-sm mb-8">
                    The <span class="font-bold text-[#9D686E]">{{ $emailType === 'po' ? 'Purchase Order' : ($emailType === 'receipt' ? 'Receipt' : 'Invoice') }}</span> has been successfully sent to <span class="font-bold">{{ $emailTo }}</span>.
                </p>

                <button @click="sentSuccessModal = false; $wire.resetEmailState()" class="w-full py-4 bg-[#9D686E] text-white rounded-2xl font-bold hover:bg-[#855359] shadow-lg shadow-[#9D686E]/20 transition-all active:scale-[0.98]">
                    Great, thanks!
                </button>
            </div>
        </div>
    </div>


    <div
        x-on:close-modal.window="paymentModal = false; emailModal = false; deleteModal = false; calendarModal = false; paymentDetailsModal = false; draftModal = false; statusConfirmModal = false;"
        x-on:open-modal.window="
            let modalToOpen = typeof $event.detail === 'string' ? $event.detail : $event.detail[0];
            if (modalToOpen === 'paymentModal') paymentModal = true;
            if (modalToOpen === 'emailModal') emailModal = true;
            if (modalToOpen === 'calendarModal') calendarModal = true;
            if (modalToOpen === 'draftModal') draftModal = true;
            if (modalToOpen === 'statusConfirmModal') statusConfirmModal = true;
            if (modalToOpen === 'sentSuccessModal') sentSuccessModal = true;
        "></div>
</div>