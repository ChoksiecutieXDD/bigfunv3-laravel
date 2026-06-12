<div class="max-w-360 mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8"
    x-data="{ 
         showPaymentModal: false, 
         showCardModal: false, 
         showEftModal: false, 
         showEmailModal: false, 
         showPaymentDetailsModal: false,
         showSuccessModal: false,
         showPaymentSuccessModal: false,
         sentSuccessModal: false,
         showConfirmModal: false,
         confirmEmailModal: false,
         confirmMessage: '',
         confirmAction: null,
         quotaWarningModal: false,
         quotaLimitModal: false
     }"
    @open-modal.window="
        let modal = typeof $event.detail === 'string' ? $event.detail : ($event.detail.name || $event.detail[0]);
        if(modal === 'paymentModal') showPaymentModal = true;
        if(modal === 'cardModal') showCardModal = true;
        if(modal === 'eftModal') showEftModal = true;
        if(modal === 'emailModal') showEmailModal = true;
        if(modal === 'paymentDetailsModal') showPaymentDetailsModal = true;
        if(modal === 'successModal') showSuccessModal = true;
        if(modal === 'paymentSuccessModal') showPaymentSuccessModal = true;
        if(modal === 'sentSuccessModal') sentSuccessModal = true;
        if(modal === 'confirmEmailModal') confirmEmailModal = true;
        if(modal === 'quotaWarningModal') quotaWarningModal = true;
        if(modal === 'quotaLimitModal') quotaLimitModal = true;
     "
    @close-modal.window="
        let modal = typeof $event.detail === 'string' ? $event.detail : ($event.detail.name || $event.detail[0]);
        if(modal === 'paymentModal') showPaymentModal = false;
        if(modal === 'cardModal') showCardModal = false;
        if(modal === 'eftModal') showEftModal = false;
        if(modal === 'emailModal') showEmailModal = false;
        if(modal === 'paymentDetailsModal') showPaymentDetailsModal = false;
        if(modal === 'successModal') showSuccessModal = false;
        if(modal === 'paymentSuccessModal') showPaymentSuccessModal = false;
        if(modal === 'sentSuccessModal') sentSuccessModal = false;
        if(modal === 'confirmEmailModal') confirmEmailModal = false;
        if(modal === 'quotaWarningModal') quotaWarningModal = false;
        if(modal === 'quotaLimitModal') quotaLimitModal = false;
     ">

    <style>
        .modal-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            color: #4a5568;
            margin-bottom: 5px;
        }

        .modal-input:focus {
            border-color: #9E6B73;
            box-shadow: 0 0 0 2px rgba(158, 107, 115, 0.1);
        }

        .email-label {
            width: 85px;
            text-align: right;
            font-size: 12px;
            font-weight: 800;
            color: #718096;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .email-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .email-row:has(textarea),
        .email-row:has(.items-start) {
            align-items: flex-start;
            padding-top: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(158, 107, 115, 0.5);
            border-radius: 4px;
        }

        .custom-scrollbar:hover::-webkit-scrollbar-thumb {
            background: #86545C;
        }
    </style>

    <div class="flex flex-col gap-1 px-1 sm:px-0">
        <h2 class="text-2xl sm:text-3xl font-bold text-white drop-shadow-md">Logistics Inbox</h2>
        <p class="text-white/80 text-xs sm:text-sm font-medium leading-relaxed">Manage daily dispatching, payments, and invoicing tasks.</p>
    </div>

    @if ($enquiriesCount > 0)
    <div class="bg-yellow-50 border border-yellow-200 p-4 sm:p-5 rounded-2xl sm:rounded-4xl shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 text-yellow-600 rounded-xl sm:rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-symbols-rounded text-xl sm:text-2xl">notifications_active</span>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-base sm:text-lg">Attention Needed</h3>
                <p class="text-xs sm:text-sm text-gray-600">You have <span class="font-bold text-yellow-700">{{ $enquiriesCount }}</span> enquiry pending follow-up.</p>
            </div>
        </div>
        <a href="{{ route('supervisor.enquiries') }}" class="w-full md:w-auto text-center px-6 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-xl text-sm shadow-lg shadow-yellow-500/30 transition transform active:scale-95 whitespace-nowrap">View Enquiries</a>
    </div>
    @endif

    <section class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-linear-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3 w-full lg:w-auto">
                <div class="p-2 bg-amber-50 text-amber-600 rounded-xl"><span class="material-symbols-rounded">pending_actions</span></div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-gray-800 text-base sm:text-lg">Pending Payments</h3>
                        <button wire:click="toggleSort('pay')" class="p-1 hover:bg-amber-100 rounded-lg transition-colors group" title="Toggle Sort">
                            <span class="material-symbols-rounded text-sm text-amber-600 transition-transform {{ $sort_pay === 'desc' ? 'rotate-180' : '' }}">sort</span>
                        </button>
                    </div>
                    <p class="text-[10px] sm:text-xs text-gray-400">Total Outstanding: {{ $pendingPayments->total() }} ({{ $sort_pay === 'asc' ? 'Oldest First' : 'Newest First' }})</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center w-full lg:w-auto gap-3 sm:gap-2">
                <div class="relative w-full sm:w-56">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search_pay" placeholder="Search Pending..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-plum/20 outline-none">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <div class="min-w-275">
                <table class="w-full text-left text-sm border-collapse relative">
                    <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                        <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                            <th class="p-4 font-bold text-left">Customer / Date</th>
                            <th class="p-4 font-bold text-left">Financials / Plan</th>
                            <th class="p-4 font-bold text-left">Details</th>
                            <th class="p-4 font-bold">Payment History</th>
                            <th class="p-4 font-bold w-48 text-center">Payment Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse ($pendingPayments as $row)
                        @php
                        $total = (float)$row->total_amount;
                        $paid = (float)$row->total_paid;
                        $outstanding = max(0, $total - $paid);
                        $isCard = stripos($row->payment_type, 'Card') !== false;
                        $isCash = $row->payment_type === 'Cash';
                        $cleanNum = str_replace(' ', '', $row->card_number ?? '');
                        $cardNum = !empty($cleanNum) ? '**** **** ' . substr($cleanNum, -8, 4) . ' ' . substr($cleanNum, -4) : 'N/A';
                        $rawNetwork = $this->getCardNetwork($row->card_number, $row->card_type);
                        $cardStyle = $this->getCardStyle($rawNetwork);
                        @endphp
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="p-4 text-left align-top">
                                <div class="flex flex-col items-start gap-1 justify-start h-full">
                                    <div class="font-bold text-gray-800 text-sm">{{ $row->customer_first_name }} {{ $row->customer_last_name }}</div>
                                    <div class="text-xs text-gray-500 font-medium">{{ $row->customer_organization }}</div>
                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('supervisor.bookings.overview', ['id' => $row->id, 'back' => route('supervisor.logistics')]) }}"
                                            class="bg-gray-100 text-gray-600 hover:bg-plum hover:text-white transition text-[10px] font-bold px-2 py-0.5 rounded border border-gray-200 no-underline whitespace-nowrap">{{ $row->invoice_number ?: 'ID: #' . $row->id }}</a>
                                        <span class="text-[10px] font-black uppercase bg-plum/10 text-plum px-2 py-0.5 rounded border border-plum/20 shadow-sm whitespace-nowrap">{{ \Carbon\Carbon::parse($row->event_date)->format('F j, Y') }}</span>
                                        @php
                                        $isPassed = \Carbon\Carbon::parse($row->event_date)->startOfDay()->isBefore(now()->startOfDay());
                                        $hasBalance = $outstanding > 0;
                                        $statusClass = $row->status === 'Confirmed' ? 'bg-green-50 text-green-600 border-green-100' : 'bg-orange-50 text-orange-600 border-orange-100';
                                        @endphp
                                        
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black border uppercase {{ $statusClass }} whitespace-nowrap">{{ $row->status ?: 'Pending' }}</span>

                                        @if($hasBalance)
                                            @if($isPassed)
                                                <span class="bg-red-50 text-red-600 px-2 py-0.5 rounded text-[9px] font-black border border-red-100 uppercase tracking-tighter shadow-sm whitespace-nowrap">Debt</span>
                                            @else
                                                <span class="bg-amber-50 text-amber-600 px-2 py-0.5 rounded text-[9px] font-black border border-amber-100 uppercase tracking-tighter shadow-sm whitespace-nowrap">Owe</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-left align-top">
                                <div class="flex justify-start items-start h-full">
                                    <div class="flex flex-col gap-1 text-xs w-40">
                                        <div class="flex justify-between"><span class="text-gray-400">Total:</span> <span class="font-bold text-gray-700">${{ number_format($total, 2) }}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-400">Paid:</span> <span class="font-bold text-green-600">${{ number_format($paid, 2) }}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-400">Deposit Req:</span> <span class="font-bold text-orange-400">${{ number_format($row->deposit_required, 2) }}</span></div>
                                        <div class="border-t border-gray-100 my-0.5"></div>
                                        <div class="flex justify-between items-center"><span class="text-gray-500 font-bold">Owing:</span> <span class="font-bold text-red-500">${{ number_format($outstanding, 2) }}</span></div>
                                        @if($isCard)
                                        <div class="mt-1 flex items-center gap-1.5 text-[9px] font-black text-plum/80 uppercase tracking-tighter bg-plum/5 px-2 py-0.5 rounded border border-plum/10">
                                            <span class="material-symbols-rounded text-xs">info</span>
                                            Processing Fee (2.9%) Inc.
                                        </div>
                                        @endif

                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-left align-top">
                                <div class="flex justify-start items-start h-full">
                                    @if ($isCard)
                                    <div class="flex items-start gap-3 w-fit">
                                        <div class="flex items-center gap-3">
                                            <div class="{{ $cardStyle['bg'] }} {{ $cardStyle['color'] }} p-1.5 rounded-lg border border-gray-100/50 shadow-sm flex items-center justify-center">
                                                <span class="material-symbols-rounded text-lg">{{ $cardStyle['icon'] }}</span>
                                            </div>
                                            <div class="text-left">
                                                <div class="text-[10px] font-extrabold {{ $cardStyle['color'] }} uppercase tracking-wider">{{ $cardStyle['label'] }}</div>
                                                <div class="text-xs font-mono font-bold text-gray-700">{{ $cardNum }}</div>
                                                <div class="text-[10px] text-gray-400">Exp: **/** | CVV: ***</div>
                                            </div>
                                        </div>
                                        <button wire:click="openCardModal({{ $row->id }})" class="text-gray-300 hover:text-plum transition p-1"><span class="material-symbols-rounded text-sm">edit_square</span></button>
                                    </div>
                                    @elseif ($isCash)
                                    <div class="flex items-start gap-3 w-fit mt-1">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-emerald-50 text-emerald-600 p-1.5 rounded-lg border border-emerald-100/50 shadow-sm flex items-center justify-center">
                                                <span class="material-symbols-rounded text-lg">payments</span>
                                            </div>
                                            <div class="text-left">
                                                <div class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-wider">Cash Payment</div>
                                                <div class="text-[10px] font-bold text-gray-700">Physical Collection</div>
                                            </div>
                                        </div>
                                    </div>
                                    @else
                                    <div class="flex items-start gap-3 w-fit mt-1">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-blue-50 text-blue-600 p-1.5 rounded-lg border border-blue-100/50 shadow-sm flex items-center justify-center">
                                                <span class="material-symbols-rounded text-lg">account_balance</span>
                                            </div>
                                            <div class="text-left">
                                                <div class="text-[10px] font-extrabold text-blue-600 uppercase tracking-wider">EFT / Bank</div>
                                                <div class="text-[10px] font-bold text-gray-700">Via: {{ $row->eft_method ?? 'Not Set' }}</div>
                                            </div>
                                        </div>
                                        <button wire:click="openEftModal({{ $row->id }})" class="text-gray-300 hover:text-plum transition p-1"><span class="material-symbols-rounded text-sm">edit_square</span></button>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 align-top text-xs w-48">
                                @forelse ($row->payments as $index => $hist)
                                <div class="border-b border-gray-100 last:border-0 pb-1.5 last:pb-0 mb-1.5">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <span class="font-bold text-plum">Payment {{ $index + 1 }}</span>
                                        <span class="font-bold text-green-600">${{ number_format($hist->amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400 text-[10px]">{{ \Carbon\Carbon::parse($hist->payment_date)->format('d M Y') }}</span>
                                        <button wire:click="viewPaymentDetails({{ $hist->id }})" class="text-[10px] text-blue-500 hover:text-blue-700 hover:underline">Details</button>
                                    </div>
                                </div>
                                @empty
                                <span class="text-gray-400 italic">No payments yet</span>
                                @endforelse
                            </td>
                            <td class="p-4 align-middle text-center w-48">
                                <div class="flex flex-col gap-2 w-full">
                                    <div class="flex items-stretch w-full border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white focus-within:border-plum focus-within:ring-1 transition-all"
                                        x-data="{ method: '{{ $row->payment_type }}' }">
                                        <select x-model="method" class="flex-1 py-1.5 px-2 text-xs border-none bg-transparent text-gray-700 font-semibold outline-none cursor-pointer">
                                            <option value="Card Holder">Card Holder</option>
                                            <option value="EFT">EFT / Direct</option>
                                            <option value="Cash">Cash</option>
                                        </select>
                                        <button type="button" @click="confirmMessage = 'Are you sure you want to change the payment method? The balance due will be affected by this change (e.g. 2.9% surcharge for Card Holder).'; confirmAction = () => $wire.savePaymentType({{ $row->id }}, method); showConfirmModal = true;" class="bg-gray-50 hover:bg-plum/10 text-gray-500 hover:text-plum transition-colors border-l border-gray-200 px-2.5 flex items-center justify-center cursor-pointer">
                                            <span wire:loading.remove wire:target="savePaymentType({{ $row->id }}, 'Card Holder'), savePaymentType({{ $row->id }}, 'EFT'), savePaymentType({{ $row->id }}, 'Cash')" class="material-symbols-rounded text-[16px]">save</span>
                                            <span wire:loading wire:target="savePaymentType({{ $row->id }}, 'Card Holder'), savePaymentType({{ $row->id }}, 'EFT'), savePaymentType({{ $row->id }}, 'Cash')" class="material-symbols-rounded text-[16px] animate-spin">sync</span>
                                        </button>
                                    </div>
                                    <button wire:click="openPaymentModal({{ $row->id }})" class="w-full bg-plum hover:bg-plum-dark text-white py-1.5 rounded-lg shadow-sm transition transform active:scale-95 text-xs font-bold flex items-center justify-center gap-1">
                                        Process
                                    </button>
                                    <button wire:click="prepareEmail({{ $row->id }}, 'receipt')" class="w-full bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 py-1.5 rounded-lg shadow-sm transition transform active:scale-95 text-xs font-bold flex items-center justify-center gap-1">
                                        <span class="material-symbols-rounded text-sm">receipt</span> Send Receipt
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-gray-400 italic">No pending payments found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($pendingPayments->hasPages())
            <div class="p-4 border-t border-gray-100 bg-plum/5 flex justify-between items-center">
                <span class="text-[10px] font-black text-plum uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-plum/10 shadow-sm">
                    Showing {{ $pendingPayments->firstItem() }}-{{ $pendingPayments->lastItem() }} of {{ $pendingPayments->total() }}
                </span>
                <div class="flex gap-2">
                    <button wire:click="previousPage('page_pay')" @disabled($pendingPayments->onFirstPage()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                        <span class="material-symbols-rounded block group-hover:-translate-x-0.5 transition-transform">chevron_left</span>
                    </button>
                    <button wire:click="nextPage('page_pay')" @disabled(!$pendingPayments->hasMorePages()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                        <span class="material-symbols-rounded block group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </button>
                </div>
            </div>
            @endif
    </section>

    <!-- FULLY PAID BOOKINGS SECTION -->
    <section class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-linear-to-r from-emerald-50 to-white">
            <div class="flex items-center gap-3 w-full lg:w-auto">
                <div class="p-2 bg-emerald-100 text-emerald-600 rounded-xl"><span class="material-symbols-rounded">verified</span></div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-gray-800 text-base sm:text-lg">Fully Paid Bookings</h3>
                        <button wire:click="toggleSort('full')" class="p-1 hover:bg-emerald-100 rounded-lg transition-colors group" title="Toggle Sort">
                            <span class="material-symbols-rounded text-sm text-emerald-600 transition-transform {{ $sort_full === 'desc' ? 'rotate-180' : '' }}">sort</span>
                        </button>
                    </div>
                    <p class="text-[10px] sm:text-xs text-gray-400">Total Settled: {{ $fullyPaidBookings->total() }} ({{ $sort_full === 'asc' ? 'Oldest First' : 'Newest First' }})</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center w-full lg:w-auto gap-3 sm:gap-2">
                <div class="relative w-full sm:w-56">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search_full" placeholder="Search Settled..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500/20 outline-none">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <div class="min-w-275">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                        <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                            <th class="p-4 font-bold text-left">Customer / Date</th>
                            <th class="p-4 font-bold text-left">Financials</th>
                            <th class="p-4 font-bold text-left w-64">Payment History</th>
                            <th class="p-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse ($fullyPaidBookings as $row)
                        @php
                        $total = (float)$row->total_amount;
                        $paid = (float)$row->total_paid;
                        $lastPay = $row->payments->sortByDesc('id')->first();
                        @endphp
                        <tr class="group hover:bg-emerald-50/30 transition-colors">
                            <td class="p-4 text-left align-middle">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="font-bold text-gray-800 text-sm">{{ $row->customer_first_name }} {{ $row->customer_last_name }}</div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 tracking-tight">{{ $row->invoice_number ?: 'ID: #' . $row->id }}</span>
                                        <span class="text-[10px] font-black uppercase bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded border border-emerald-100">{{ \Carbon\Carbon::parse($row->event_date)->format('d M Y') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-left align-middle">
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Total Settled</span>
                                    <span class="font-black text-emerald-600 text-sm">${{ number_format($total, 2) }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-top text-xs w-64">
                                @forelse ($row->payments as $index => $hist)
                                <div class="border-b border-gray-100 last:border-0 pb-1.5 last:pb-0 mb-1.5">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <span class="font-bold text-plum">Payment {{ $index + 1 }}</span>
                                        <span class="font-bold text-green-600">${{ number_format($hist->amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400 text-[10px]">{{ \Carbon\Carbon::parse($hist->payment_date)->format('d M Y') }}</span>
                                        <button wire:click="viewPaymentDetails({{ $hist->id }})" class="text-[10px] text-blue-500 hover:text-blue-700 hover:underline">Details</button>
                                    </div>
                                </div>
                                @empty
                                <span class="text-gray-400 italic">No record</span>
                                @endforelse
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div class="flex gap-2 justify-end">
                                    <button wire:click="prepareEmail({{ $row->id }}, 'receipt')" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 px-4 py-1.5 rounded-lg text-xs font-black border border-gray-200 transition shadow-sm transform active:scale-95 cursor-pointer">
                                        <span class="material-symbols-rounded text-sm">receipt</span> RECEIPT
                                    </button>
                                    <a href="{{ route('supervisor.bookings.overview', ['id' => $row->id, 'back' => route('supervisor.logistics')]) }}" class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 px-4 py-1.5 rounded-lg text-xs font-black hover:bg-emerald-100 transition shadow-sm border border-emerald-100 no-underline">
                                        <span class="material-symbols-rounded text-sm">visibility</span> VIEW
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-400 italic">No fully paid bookings in this view.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($fullyPaidBookings->hasPages())
            <div class="p-4 border-t border-gray-100 bg-emerald-50/50 flex justify-between items-center">
                <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-emerald-100 shadow-sm">
                    Showing {{ $fullyPaidBookings->firstItem() }}-{{ $fullyPaidBookings->lastItem() }} of {{ $fullyPaidBookings->total() }}
                </span>
                <div class="flex gap-2">
                    <button wire:click="previousPage('page_full')" @disabled($fullyPaidBookings->onFirstPage()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-emerald-600 hover:border-emerald-300 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                        <span class="material-symbols-rounded block group-hover:-translate-x-0.5 transition-transform">chevron_left</span>
                    </button>
                    <button wire:click="nextPage('page_full')" @disabled(!$fullyPaidBookings->hasMorePages()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-emerald-600 hover:border-emerald-300 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                        <span class="material-symbols-rounded block group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </button>
                </div>
            </div>
            @endif
    </section>

    <section class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-linear-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3 w-full lg:w-auto">
                <div class="p-2 bg-plum/10 text-plum rounded-xl"><span class="material-symbols-rounded">description</span></div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-gray-800 text-base sm:text-lg">Invoices to Send</h3>
                        <button wire:click="toggleSort('inv')" class="p-1 hover:bg-plum/10 rounded-lg transition-colors group" title="Toggle Sort">
                            <span class="material-symbols-rounded text-sm text-plum transition-transform {{ $sort_inv === 'desc' ? 'rotate-180' : '' }}">sort</span>
                        </button>
                    </div>
                    <p class="text-[10px] sm:text-xs text-gray-400">Total Pending: {{ $invoices->total() }} ({{ $sort_inv === 'asc' ? 'Oldest First' : 'Newest First' }})</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4 w-full lg:w-auto">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-white/50 rounded-xl border border-gray-200/50 shadow-sm transition hover:bg-white w-full sm:w-auto justify-center sm:justify-start">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest cursor-pointer whitespace-nowrap">PDF Prices</label>
                    <button wire:click="$set('invoice_pdf_prices', {{ !$invoice_pdf_prices ? 'true' : 'false' }})" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $invoice_pdf_prices ? 'bg-plum' : 'bg-gray-200' }}">
                        <span class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $invoice_pdf_prices ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                </div>
                <div class="relative w-full sm:w-56">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search_inv" placeholder="Search Invoices..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-plum outline-none">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <div class="min-w-250 px-1">
                <table class="w-full text-left text-sm border-collapse relative">
                    <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                        <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                            <th class="p-4 font-bold text-left">Invoice #</th>
                            <th class="p-4 font-bold text-left">Customer</th>
                            <th class="p-4 font-bold text-left">Address</th>
                            <th class="p-4 font-bold text-right">Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse ($invoices as $inv)
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="p-4 align-top text-left">
                                <a href="{{ route('supervisor.bookings.overview', ['id' => $inv->id, 'back' => route('supervisor.logistics')]) }}" class="font-mono font-bold text-plum bg-plum/5 px-2 py-1 rounded text-xs border border-plum hover:bg-plum hover:text-white transition no-underline">
                                    #{{ $inv->invoice_number ?? $inv->id }}
                                </a>
                            </td>
                            <td class="p-4 align-top text-left">
                                <div class="flex items-center gap-2">
                                    <div class="font-bold text-gray-800 text-sm">{{ $inv->customer_first_name }} {{ $inv->customer_last_name }}</div>
                                    @php
                                        $invStatus = $inv->status ?: 'Pending';
                                        $invBadgeClass = 'bg-orange-50 text-orange-600 border-orange-100';
                                        if ($invStatus === 'Confirmed') $invBadgeClass = 'bg-green-50 text-green-600 border-green-100';
                                        if ($invStatus === 'Completed') $invBadgeClass = 'bg-blue-50 text-blue-600 border-blue-100';
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black border uppercase {{ $invBadgeClass }}">{{ $invStatus }}</span>
                                </div>
                                <div class="text-xs text-gray-500 font-medium">{{ $inv->customer_organization }}</div>
                            </td>
                            <td class="p-4 align-top text-left w-72">
                                <div class="flex items-start gap-1">
                                    <span class="material-symbols-rounded text-gray-300 text-sm mt-0.5">location_on</span>
                                    <p class="text-xs text-gray-600 leading-relaxed">{{ $inv->address_line_1 }}, {{ $inv->suburb }}</p>
                                </div>
                                <div class="text-[10px] font-black uppercase bg-plum/5 text-plum px-2 py-0.5 rounded border border-plum/10 w-fit mt-1">Due: {{ \Carbon\Carbon::parse($inv->event_date)->format('F j, Y') }}</div>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div class="flex gap-2 justify-end flex-wrap">
                                    <button wire:click="prepareEmail({{ $inv->id }}, 'invoice')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold border transition transform active:scale-95 bg-plum text-white hover:bg-plum-dark">
                                        Send Invoice
                                    </button>
                                    <button wire:click="prepareEmail({{ $inv->id }}, 'receipt')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition transform active:scale-95">
                                        Receipt
                                    </button>

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-400 italic">No invoices found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
            @if ($invoices->hasPages())
            <div class="p-4 border-t border-gray-100 bg-plum/5 flex justify-between items-center">
                <span class="text-[10px] font-black text-plum uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-plum/10 shadow-sm">
                    Showing {{ $invoices->firstItem() }}-{{ $invoices->lastItem() }} of {{ $invoices->total() }}
                </span>
                <div class="flex gap-2">
                    <button wire:click="previousPage('page_inv')" @disabled($invoices->onFirstPage()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                        <span class="material-symbols-rounded block group-hover:-translate-x-0.5 transition-transform">chevron_left</span>
                    </button>
                    <button wire:click="nextPage('page_inv')" @disabled(!$invoices->hasMorePages()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                        <span class="material-symbols-rounded block group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </button>
                </div>
            </div>
            @endif
    </section>

    <section class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-linear-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3 w-full lg:w-auto">
                <div class="p-2 bg-plum/10 text-plum rounded-xl"><span class="material-symbols-rounded">inventory_2</span></div>
                <div>
                    <h3 class="font-bold text-gray-800 text-base sm:text-lg">Upcoming Orders</h3>
                    <p class="text-[10px] sm:text-xs text-gray-400">Total: {{ $orders->total() }}</p>
                </div>
            </div>
            <div class="relative w-full lg:w-56">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                <input type="text" wire:model.live.debounce.300ms="search_ord" placeholder="Search Orders..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-plum outline-none">
            </div>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <div class="min-w-250 px-1">
                <table class="w-full text-left text-sm border-collapse relative">
                    <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                        <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                            <th class="p-4 font-bold text-left">Event Date</th>
                            <th class="p-4 font-bold text-left">Customer</th>
                            <th class="p-4 font-bold text-left">Lead Operator</th>
                            <th class="p-4 font-bold text-left">Lead Deliverer</th>
                            <th class="p-4 font-bold text-left">Booked By</th>
                            <th class="p-4 font-bold text-left">Status</th>
                            <th class="p-4 font-bold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse ($orders as $ord)
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="p-4 align-top text-left">
                                <span class="text-[10px] font-black uppercase bg-plum/10 text-plum px-2 py-1 rounded border border-plum/20 shadow-sm">{{ \Carbon\Carbon::parse($ord->event_date)->format('M d, Y') }}</span>
                            </td>
                            <td class="p-4 text-gray-700 font-bold align-top text-left">{{ $ord->customer_first_name }} {{ $ord->customer_last_name }}</td>
                            <td class="p-4 text-xs align-top text-left">
                                <span class="{{ !$ord->lead_operator ? 'text-gray-400 italic' : 'text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded' }}">{{ $ord->lead_operator ?: 'Unassigned' }}</span>
                            </td>
                            <td class="p-4 text-xs align-top text-left">
                                <span class="{{ !$ord->lead_deliverer ? 'text-gray-400 italic' : 'text-orange-600 font-bold bg-orange-50 px-2 py-0.5 rounded' }}">{{ $ord->lead_deliverer ?: 'Unassigned' }}</span>
                            </td>
                            <td class="p-4 text-xs align-top text-left">
                                <span class="text-gray-600 font-bold">{{ $ord->booked_by ?: 'N/A' }}</span>
                            </td>
                            <td class="p-4 align-top text-left"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Confirmed</span></td>
                            <td class="p-4 text-right align-top">
                                <a href="{{ route('supervisor.bookings.overview', ['id' => $ord->id, 'back' => route('supervisor.logistics')]) }}" class="inline-flex items-center gap-1 bg-plum text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-md hover:bg-plum-dark transition transform active:scale-95 no-underline">
                                    <span class="material-symbols-rounded text-sm text-white">visibility</span> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-400 italic">No orders found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($orders->hasPages())
        <div class="p-4 border-t border-gray-100 bg-plum/5 flex justify-between items-center text-xs">
            <span class="text-[10px] font-black text-plum uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-plum/10 shadow-sm">
                Showing {{ $orders->firstItem() }}-{{ $orders->lastItem() }} of {{ $orders->total() }}
            </span>
            <div class="flex gap-2">
                <button wire:click="previousPage('page_ord')" @disabled($orders->onFirstPage()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                    <span class="material-symbols-rounded block group-hover:-translate-x-0.5 transition-transform">chevron_left</span>
                </button>
                <button wire:click="nextPage('page_ord')" @disabled(!$orders->hasMorePages()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                    <span class="material-symbols-rounded block group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                </button>
            </div>
        </div>
        @endif
    </section>

    <section class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-linear-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3 w-full lg:w-auto">
                <div class="p-2 bg-plum/10 text-plum rounded-xl"><span class="material-symbols-rounded">money_off</span></div>
                <div>
                    <h3 class="font-bold text-gray-800 text-base sm:text-lg">Debtors</h3>
                    <p class="text-[10px] sm:text-xs text-gray-400">Total: {{ $debtors->total() }}</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4 w-full lg:w-auto">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-white/50 rounded-xl border border-gray-200/50 shadow-sm transition hover:bg-white w-full sm:w-auto justify-center sm:justify-start">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest cursor-pointer whitespace-nowrap">PDF Prices</label>
                    <button wire:click="$set('debtor_pdf_prices', {{ !$debtor_pdf_prices ? 'true' : 'false' }})" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $debtor_pdf_prices ? 'bg-plum' : 'bg-gray-200' }}">
                        <span class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $debtor_pdf_prices ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                </div>
                <div class="relative w-full sm:w-56">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search_deb" placeholder="Search Debtors..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-plum outline-none">
                </div>
            </div>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <div class="min-w-250 px-1">
                <table class="w-full text-left text-sm border-collapse relative min-w-200">
                    <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                        <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                            <th class="p-4 font-bold text-left">Customer</th>
                            <th class="p-4 font-bold text-left">Financials</th>
                            <th class="p-4 font-bold text-left">Method</th>
                            <th class="p-4 font-bold text-left">Contact</th>
                            <th class="p-4 font-bold text-left w-48">Payment History</th>
                            <th class="p-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse ($debtors as $deb)
                        @php
                        $debTotal = (float)$deb->total_amount;
                        $debPaid = (float)$deb->total_paid;
                        $amountDue = max(0, $debTotal - $debPaid);
                        @endphp
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="p-4 font-bold text-gray-700 align-top text-left">
                                {{ $deb->customer_first_name }} {{ $deb->customer_last_name }}
                                <div class="text-xs text-gray-400 font-medium">{{ $deb->customer_organization }}</div>
                            </td>
                            <td class="p-4 align-top text-left">
                                <div class="text-xs text-gray-500">Total: <span class="font-bold text-gray-700">${{ number_format($debTotal, 2) }}</span></div>
                                <div class="text-xs text-red-500 font-bold mt-1">Due: ${{ number_format($amountDue, 2) }}</div>
                            </td>
                            <td class="p-4 align-top text-left">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide border border-gray-200">{{ $deb->payment_type ?? 'EFT' }}</span>
                            </td>
                            <td class="p-4 text-gray-600 text-xs align-top text-left">
                                <div>{{ $deb->customer_phone }}</div>
                                <div class="mt-1 text-[10px] font-black uppercase bg-red-50 text-red-600 px-2 py-0.5 rounded border border-red-100 w-fit">{{ \Carbon\Carbon::parse($deb->event_date)->format('F j, Y') }}</div>
                            </td>
                            <td class="p-4 align-top text-xs w-48">
                                @forelse ($deb->payments as $index => $hist)
                                <div class="border-b border-gray-100 last:border-0 pb-1.5 last:pb-0 mb-1.5">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <span class="font-bold text-plum">Payment {{ $index + 1 }}</span>
                                        <span class="font-bold text-green-600">${{ number_format($hist->amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400 text-[10px]">{{ \Carbon\Carbon::parse($hist->payment_date)->format('d M Y') }}</span>
                                        <button wire:click="viewPaymentDetails({{ $hist->id }})" class="text-[10px] text-blue-500 hover:text-blue-700 hover:underline">Details</button>
                                    </div>
                                </div>
                                @empty
                                <span class="text-gray-400 italic">No payments yet</span>
                                @endforelse
                            </td>
                            <td class="p-4 align-top text-right">
                                <div class="flex gap-2 justify-end flex-wrap">
                                    <button wire:click="prepareEmail({{ $deb->id }}, 'debt')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition transform active:scale-95 inline-flex items-center justify-center gap-1">
                                        Send Debt
                                    </button>
                                    <button wire:click="openPaymentModal({{ $deb->id }})" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 text-white hover:bg-amber-600 transition shadow-sm transform active:scale-95">
                                        Add Payment
                                    </button>
                                    <a href="{{ route('supervisor.bookings.overview', ['id' => $deb->id, 'back' => route('supervisor.logistics')]) }}" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition transform active:scale-95 inline-flex items-center justify-center gap-1 no-underline">
                                        <span class="material-symbols-rounded text-sm text-gray-500">visibility</span> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-400 italic">No debtors found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($debtors->hasPages())
        <div class="p-4 border-t border-gray-100 bg-plum/5 flex justify-between items-center text-xs">
            <span class="text-[10px] font-black text-plum uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-plum/10 shadow-sm">
                Showing {{ $debtors->firstItem() }}-{{ $debtors->lastItem() }} of {{ $debtors->total() }}
            </span>
            <div class="flex gap-2">
                <button wire:click="previousPage('page_deb')" @disabled($debtors->onFirstPage()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                    <span class="material-symbols-rounded block group-hover:-translate-x-0.5 transition-transform">chevron_left</span>
                </button>
                <button wire:click="nextPage('page_deb')" @disabled(!$debtors->hasMorePages()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                    <span class="material-symbols-rounded block group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                </button>
            </div>
        </div>
        @endif
    </section>

    <!-- CANCELLED BOOKINGS SECTION -->
    <section class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-linear-to-r from-red-50 to-white">
            <div class="flex items-center gap-3 w-full lg:w-auto">
                <div class="p-2 bg-red-100 text-red-600 rounded-xl"><span class="material-symbols-rounded">cancel</span></div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-gray-800 text-base sm:text-lg">Cancelled Bookings</h3>
                        <button wire:click="toggleSort('can')" class="p-1 hover:bg-red-100 rounded-lg transition-colors group" title="Toggle Sort">
                            <span class="material-symbols-rounded text-sm text-red-600 transition-transform {{ $sort_can === 'desc' ? 'rotate-180' : '' }}">sort</span>
                        </button>
                    </div>
                    <p class="text-[10px] sm:text-xs text-gray-400">Total Cancelled: {{ $cancelledBookings->total() }} ({{ $sort_can === 'asc' ? 'Oldest First' : 'Newest First' }})</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center w-full lg:w-auto gap-3 sm:gap-2">
                <div class="relative w-full sm:w-56">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search_can" placeholder="Search Cancelled..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-red-500/20 outline-none">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <div class="min-w-275">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                        <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                            <th class="p-4 font-bold text-left">Customer / Date</th>
                            <th class="p-4 font-bold text-left">Financials</th>
                            <th class="p-4 font-bold text-left">Method / Org</th>
                            <th class="p-4 font-bold text-left">Contact</th>
                            <th class="p-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse ($cancelledBookings as $row)
                        @php
                        $total = (float)$row->total_amount;
                        $paid = (float)$row->total_paid;
                        @endphp
                        <tr class="group hover:bg-red-50/20 transition-colors">
                            <td class="p-4 text-left align-middle">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="font-bold text-gray-800 text-sm">{{ $row->customer_first_name }} {{ $row->customer_last_name }}</div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 tracking-tight">{{ $row->invoice_number ?: 'ID: #' . $row->id }}</span>
                                        <span class="text-[10px] font-black uppercase bg-red-50 text-red-600 px-2 py-0.5 rounded border border-red-100">{{ \Carbon\Carbon::parse($row->event_date)->format('d M Y') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-left align-middle">
                                <div class="flex flex-col">
                                    <div class="text-xs text-gray-500">Originally: <span class="font-bold text-gray-700">${{ number_format($total, 2) }}</span></div>
                                    <div class="text-xs text-green-600 font-bold mt-1">Paid: ${{ number_format($paid, 2) }}</div>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-left">
                                <div class="flex flex-col gap-1">
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide border border-gray-200 w-fit">{{ $row->payment_type ?? 'None' }}</span>
                                    @if($row->customer_organization)
                                        <span class="text-xs text-gray-500 font-medium">{{ $row->customer_organization }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 text-gray-600 text-xs align-middle text-left">
                                <div>{{ $row->customer_phone }}</div>
                                <div class="text-gray-400 mt-0.5">{{ $row->customer_email }}</div>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <a href="{{ route('supervisor.bookings.overview', ['id' => $row->id, 'back' => route('supervisor.logistics')]) }}" class="inline-flex items-center gap-2 bg-red-50 text-red-700 px-4 py-1.5 rounded-lg text-xs font-black hover:bg-red-100 transition shadow-sm border border-red-100 no-underline">
                                    <span class="material-symbols-rounded text-sm">visibility</span> VIEW
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-400 italic">No cancelled bookings found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($cancelledBookings->hasPages())
            <div class="p-4 border-t border-gray-100 bg-red-50/50 flex justify-between items-center">
                <span class="text-[10px] font-black text-red-700 uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-red-100 shadow-sm">
                    Showing {{ $cancelledBookings->firstItem() }}-{{ $cancelledBookings->lastItem() }} of {{ $cancelledBookings->total() }}
                </span>
                <div class="flex gap-2">
                    <button wire:click="previousPage('page_can')" @disabled($cancelledBookings->onFirstPage()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-red-600 hover:border-red-300 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                        <span class="material-symbols-rounded block group-hover:-translate-x-0.5 transition-transform">chevron_left</span>
                    </button>
                    <button wire:click="nextPage('page_can')" @disabled(!$cancelledBookings->hasMorePages()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-red-600 hover:border-red-300 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                        <span class="material-symbols-rounded block group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </button>
                </div>
            </div>
            @endif
    </section>

    <section class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-linear-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-plum/10 text-plum rounded-xl"><span class="material-symbols-rounded">badge</span></div>
                <div>
                    <h3 class="font-bold text-gray-800 text-base sm:text-lg">Operators</h3>
                    <p class="text-[10px] sm:text-xs text-gray-400">Total: {{ $operators->total() }}</p>
                </div>
            </div>
            <div class="relative w-full md:w-56">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                <input type="text" wire:model.live.debounce.300ms="search_op" placeholder="Search Staff..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-plum outline-none">
            </div>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <div class="min-w-200 px-1">
                <table class="w-full text-left text-sm border-collapse relative">
                    <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                        <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                            <th class="p-4 font-bold text-left">Name</th>
                            <th class="p-4 font-bold text-left">Role</th>
                            <th class="p-4 font-bold text-left">Email</th>
                            <th class="p-4 font-bold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse ($operators as $op)
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="p-4 font-bold text-gray-700 text-left">{{ $op->first_name }} {{ $op->last_name }}</td>
                            <td class="p-4 text-gray-600 text-xs uppercase tracking-wide text-left">{{ $op->role }}</td>
                            <td class="p-4 text-gray-500 text-xs text-left">{{ $op->email }}</td>
                            <td class="p-4 text-right"><span class="inline-block w-2 h-2 rounded-full bg-green-500"></span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-400 italic">No staff found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($operators->hasPages())
        <div class="p-4 border-t border-gray-100 bg-plum/5 flex justify-between items-center text-xs">
            <span class="text-[10px] font-black text-plum uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-plum/10 shadow-sm">
                Showing {{ $operators->firstItem() }}-{{ $operators->lastItem() }} of {{ $operators->total() }}
            </span>
            <div class="flex gap-2">
                <button wire:click="previousPage('page_op')" @disabled($operators->onFirstPage()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                    <span class="material-symbols-rounded block group-hover:-translate-x-0.5 transition-transform">chevron_left</span>
                </button>
                <button wire:click="nextPage('page_op')" @disabled(!$operators->hasMorePages()) class="p-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-plum hover:border-plum/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm group">
                    <span class="material-symbols-rounded block group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                </button>
            </div>
        </div>
        @endif
    </section>

    <template x-teleport="body">
        <!-- GLOBAL CONFIRMATION MODAL -->
        <div x-show="showConfirmModal" class="fixed inset-0 z-10000 flex items-center justify-center p-4" x-cloak>
            <div x-show="showConfirmModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showConfirmModal = false"></div>

            <div x-show="showConfirmModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-3xl shadow-2xl p-10 max-w-sm w-full text-center z-10 overflow-hidden">

                <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-6 text-amber-500 ring-8 ring-amber-50/50">
                    <span class="material-symbols-rounded text-4xl font-bold">help_outline</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight uppercase">Confirm Action</h3>
                <p class="text-[14px] font-bold text-slate-500 mb-10 leading-relaxed px-2" x-text="confirmMessage"></p>

                <div class="flex gap-4">
                    <button type="button" @click="showConfirmModal = false" class="flex-1 py-4 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-2xl transition-all uppercase tracking-widest border border-slate-100">Cancel</button>
                    <button type="button" @click="if(confirmAction) confirmAction(); showConfirmModal = false;" class="flex-1 py-4 bg-plum text-white hover:bg-plum-dark rounded-2xl font-black text-[11px] shadow-xl shadow-plum/20 transition-all active:scale-95 uppercase tracking-widest">Confirm</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- RECORD TRANSACTION MODAL -->
        <div x-show="showPaymentModal" class="fixed inset-0 z-10000 flex items-center justify-center p-4" x-cloak>
            <div x-show="showPaymentModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showPaymentModal = false"></div>

            <div x-show="showPaymentModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md flex flex-col max-h-[90vh] overflow-hidden z-20">

                <div class="px-8 py-8 border-b border-slate-50 flex justify-between items-center bg-white shrink-0">
                    <div class="flex items-center gap-4 text-plum">
                        <div class="w-12 h-12 rounded-xl bg-plum/10 flex items-center justify-center">
                            <span class="material-symbols-rounded text-2xl font-bold">payments</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase">Add Payment</h3>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5" x-text="'INVOICE #' + ($wire.pay_context['invoice_num'] || 'PENDING')"></p>
                        </div>
                    </div>
                    <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded text-2xl font-bold">close</span>
                    </button>
                </div>

                <div class="p-8 overflow-y-auto custom-scrollbar grow bg-white">
                    <form wire:submit.prevent="processPayment" class="space-y-8">
                        <div class="bg-slate-900 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl shadow-slate-900/20">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-plum/20 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl pointer-events-none"></div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3" x-text="$wire.pay_context['customer_name'] || 'System Account'"></p>
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Amount Outstanding</span>
                                <span class="text-4xl font-black tracking-tighter" x-text="'$' + Number($wire.pay_context['owing'] || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-8">
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Payment Amount ($)</label>
                                <input type="number" wire:model="pay_amount" step="0.01" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[18px] font-black text-slate-800 transition-all">
                            </div>

                            <div class="grid grid-cols-2 gap-6">
                                <div class="input-group">
                                    <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Payment Type</label>
                                    <div class="relative">
                                        <select wire:model.live="pay_type" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[13px] font-bold text-slate-700 cursor-pointer appearance-none transition-all">
                                            <option value="Deposit Capture">Deposit Capture</option>
                                            <option value="Final Settlement">Final Settlement</option>
                                            <option value="Total Liquidation">Total Liquidation</option>
                                            <option value="Partial Allocation">Partial Allocation</option>
                                        </select>
                                        <span class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-rounded">expand_more</span>
                                    </div>
                                </div>

                                <div class="input-group">
                                    <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Payment Method</label>
                                    <div class="relative">
                                        <select wire:model.live="pay_method" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[13px] font-bold text-slate-700 cursor-pointer appearance-none transition-all">
                                            <option value="EFT">EFT</option>
                                            <option value="Card Holder">Card Holder</option>
                                            <option value="Cash">Cash Payment</option>
                                        </select>
                                        <span class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-rounded">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            @if ($pay_method === 'EFT')
                            <div class="input-group animate-[fadeIn_0.3s_ease-out]">
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">EFT Method</label>
                                <div class="relative">
                                    <select wire:model="eft_specific_method" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[13px] font-bold text-slate-700 cursor-pointer appearance-none transition-all">
                                        <option value="Direct Deposit">Direct Deposit</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Osko">Osko Realtime</option>
                                        <option value="PayID">PayID</option>
                                    </select>
                                    <span class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-rounded">expand_more</span>
                                </div>
                            </div>
                            @elseif ($pay_method === 'Card Holder')
                            <div class="p-8 bg-slate-50 rounded-3xl border border-dashed border-slate-200 gap-6 grid grid-cols-1 animate-[fadeIn_0.3s_ease-out]">
                                <div class="input-group">
                                    <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Card Type</label>
                                    <div class="relative">
                                        <select wire:model="modal_card_network" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none text-xs font-bold text-slate-700 cursor-pointer appearance-none transition-all">
                                            <option value="Visa">Visa</option>
                                            <option value="Mastercard">Mastercard</option>
                                            <option value="American Express">AMEX</option>
                                            <option value="Discover">Discover</option>
                                            <option value="Bankcard">Bankcard</option>
                                            <option value="Bartercard">Bartercard</option>
                                        </select>
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-rounded">expand_more</span>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Card Holder Name</label>
                                    <input type="text" wire:model="pay_card_holder" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none text-sm font-black text-slate-800" placeholder="Full Name on Card">
                                </div>
                                <div class="input-group">
                                    <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Card Number</label>
                                    <input type="text" wire:model="pay_card_number" x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim()" maxlength="19" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none text-sm font-mono font-black text-slate-800" placeholder="0000 0000 0000 0000">
                                </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="input-group">
                                        <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Expiry</label>
                                        <input type="text" wire:model="pay_card_expiry" x-on:input="let v = $el.value.replace(/\D/g, ''); if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2,4); $el.value = v;" maxlength="5" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none text-sm text-center font-mono font-black text-slate-800" placeholder="MM/YY">
                                    </div>
                                    <div class="input-group">
                                        <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">CVV</label>
                                        <input type="text" wire:model="pay_card_cvv" maxlength="4" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none text-sm text-center font-mono font-black text-slate-800" placeholder="***">
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="grid grid-cols-2 gap-6">
                                <div class="input-group">
                                    <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Payment Date</label>
                                    <input type="date" wire:model="pay_date" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[13px] font-bold text-slate-700 transition-all">
                                </div>
                                <div class="input-group">
                                    <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Reference Number</label>
                                    <input type="text" wire:model="pay_ref" placeholder="Enter Reference" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[13px] font-bold text-slate-700 font-mono transition-all">
                                </div>
                            </div>

                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Internal Notes</label>
                                <textarea wire:model="pay_notes" rows="3" class="w-full px-6 py-5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:border-plum/30 outline-none text-[13px] font-medium text-slate-700 resize-none transition-all placeholder:text-slate-300" placeholder="Add payment notes..."></textarea>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-slate-50 flex gap-4">
                            <button type="button" @click="showPaymentModal = false" class="px-8 py-5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-2xl transition-all uppercase tracking-[0.2em] border border-slate-100">Cancel</button>
                            <button type="submit" class="grow py-5 bg-plum text-white rounded-2xl font-black hover:bg-plum-dark shadow-xl shadow-plum/20 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-[11px] flex items-center justify-center gap-3">
                                <span wire:loading.remove wire:target="processPayment" class="flex items-center gap-3">
                                    Authorise Transaction
                                </span>
                                <span wire:loading wire:target="processPayment" class="flex items-center gap-3">
                                    <span class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- CARD MANAGEMENT MODAL -->
        <div x-show="showCardModal" class="fixed inset-0 z-10000 flex items-center justify-center p-4" x-cloak>
            <div x-show="showCardModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showCardModal = false"></div>

            <div x-show="showCardModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm flex flex-col max-h-[90vh] overflow-hidden z-10 transition-all">

                <div class="px-8 py-8 border-b border-slate-50 flex justify-between items-center bg-white shrink-0">
                    <div class="flex items-center gap-4 text-plum">
                        <div class="w-12 h-12 rounded-xl bg-plum/10 flex items-center justify-center">
                            <span class="material-symbols-rounded text-2xl font-bold">credit_score</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase">Card Details</h3>
                        </div>
                    </div>
                    <button type="button" @click="showCardModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded text-2xl font-bold">close</span>
                    </button>
                </div>

                <div class="p-8 overflow-y-auto custom-scrollbar grow bg-white">
                    <form wire:submit.prevent="saveCardDetails" class="space-y-8">
                        <div class="grid grid-cols-1 gap-8">
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Card Holder Name</label>
                                <input type="text" wire:model="edit_card_holder" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[15px] font-black text-slate-800 transition-all" placeholder="Enter Cardholder Name">
                            </div>
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Card Type</label>
                                <div class="relative">
                                    <select wire:model="edit_card_type" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[13px] font-bold text-slate-700 appearance-none cursor-pointer transition-all">
                                        <option value="Visa">Visa</option>
                                        <option value="Mastercard">Mastercard</option>
                                        <option value="American Express">AMEX</option>
                                        <option value="Discover">Discover</option>
                                        <option value="Bankcard">Bankcard</option>
                                        <option value="Bartercard">Bartercard</option>
                                    </select>
                                    <span class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-rounded">expand_more</span>
                                </div>
                            </div>

                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Card Number</label>
                                <input type="text" wire:model="edit_card_number" x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim()" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[15px] font-black text-slate-800 font-mono transition-all" placeholder="0000 0000 0000 0000" maxlength="19">
                            </div>

                            <div class="grid grid-cols-2 gap-6">
                                <div class="input-group">
                                    <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Expiry Date</label>
                                    <input type="text" wire:model="edit_card_expiry" x-on:input="let v = $el.value.replace(/\D/g, ''); if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2,4); $el.value = v;" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[14px] font-black text-slate-800 text-center font-mono transition-all" placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="input-group">
                                    <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Security CVV</label>
                                    <input type="text" wire:model="edit_card_cvv" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[14px] font-black text-slate-800 text-center font-mono transition-all" placeholder="***" maxlength="4">
                                </div>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-slate-50 flex gap-4">
                            <button type="button" @click="showCardModal = false" class="px-8 py-5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-2xl transition-all uppercase tracking-[0.2em] border border-slate-100">Cancel</button>
                            <button type="submit" class="grow py-5 bg-plum text-white rounded-2xl font-black hover:bg-plum-dark shadow-xl shadow-plum/20 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-[11px] flex items-center justify-center gap-3">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- EFT CONFIGURATION MODAL -->
        <div x-show="showEftModal" class="fixed inset-0 z-10000 flex items-center justify-center p-6 sm:p-12 lg:p-20" x-cloak>
            <div x-show="showEftModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showEftModal = false"></div>

            <div x-show="showEftModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-[40px] shadow-2xl w-full max-w-sm flex flex-col max-h-[85vh] overflow-hidden z-10 transition-all border border-white/20">

                <div class="px-8 py-8 border-b border-slate-50 flex justify-between items-center bg-white shrink-0">
                    <div class="flex items-center gap-4 text-plum">
                        <div class="w-12 h-12 rounded-xl bg-plum/10 flex items-center justify-center">
                            <span class="material-symbols-rounded text-2xl font-bold">account_balance</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase">EFT Configuration</h3>
                        </div>
                    </div>
                    <button type="button" @click="showEftModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded text-2xl font-bold">close</span>
                    </button>
                </div>

                <div class="p-8 overflow-y-auto custom-scrollbar grow bg-white">
                    <form wire:submit.prevent="saveEftDetails" class="space-y-8">
                        <div class="input-group">
                            <label class="block text-[11px] font-black text-slate-400 mb-4 uppercase tracking-widest px-1">Payment Method Details</label>
                            <div class="relative">
                                <select wire:model="edit_eft_method" class="w-full px-6 py-5 bg-slate-50 border border-slate-100 rounded-3xl focus:bg-white focus:border-plum/30 outline-none text-[15px] font-black text-slate-800 appearance-none cursor-pointer transition-all shadow-sm">
                                    <option value="Direct Deposit">Direct Deposit</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Osko">Osko</option>
                                    <option value="PayID">PayID</option>
                                </select>
                                <span class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-rounded font-bold">expand_more</span>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-slate-50 flex gap-4">
                            <button type="button" @click="showEftModal = false" class="px-8 py-5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-2xl transition-all uppercase tracking-[0.2em] border border-slate-100">Cancel</button>
                            <button type="submit" class="grow py-5 bg-plum text-white rounded-2xl font-black hover:bg-plum-dark shadow-xl shadow-plum/20 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-[11px] flex items-center justify-center gap-3">
                                Update EFT Method
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- EMAIL COMMUNICATION MODAL -->
        <div x-show="showEmailModal" class="fixed inset-0 z-10000 flex items-center justify-center p-6 sm:p-12 lg:p-20" x-cloak>
            <div x-show="showEmailModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showEmailModal = false"></div>

            <div x-show="showEmailModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-[40px] shadow-2xl w-full max-w-2xl flex flex-col max-h-[85vh] overflow-hidden z-10 transition-all border border-white/20">

                <div class="px-8 py-8 border-b border-slate-50 flex justify-between items-center bg-white shrink-0">
                    <div class="flex items-center gap-4 text-plum">
                        <div class="w-12 h-12 rounded-xl bg-plum/10 flex items-center justify-center">
                            <span class="material-symbols-rounded text-2xl font-bold">mail</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase">Email Details</h3>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5" x-text="'SEND TO: ' + ($wire.email_to || 'Recipient Pending...')"></p>
                        </div>
                    </div>
                    <button type="button" @click="showEmailModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded text-2xl font-bold">close</span>
                    </button>
                </div>

                <div class="p-8 overflow-y-auto custom-scrollbar grow bg-white">
                    <form wire:submit.prevent="sendEmail" class="space-y-8">
                        <div class="space-y-4 bg-slate-50 p-8 rounded-3xl border border-slate-100 relative group overflow-hidden shadow-inner">
                            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                                <span class="material-symbols-rounded text-4xl font-bold">forward_to_inbox</span>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest sm:w-24 text-left sm:text-right shrink-0">From:</label>
                                <div class="grow flex items-center gap-2">
                                    <span class="text-[11px] font-black text-slate-500 bg-white border border-slate-200 px-4 py-2 rounded-xl grow shadow-sm">bigfun.qld.au@gmail.com</span>
                                    <span class="text-[11px] font-black text-slate-500 bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm">BigFun</span>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest sm:w-24 text-left sm:text-right shrink-0">Recipient:</label>
                                <input type="text" wire:model="email_to" class="grow text-[14px] font-black text-slate-800 px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none transition-all shadow-sm">
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest sm:w-24 text-left sm:text-right shrink-0">Cc / Bcc:</label>
                                <div class="grow grid grid-cols-2 gap-4">
                                    <input type="text" wire:model="email_cc" placeholder="Cc Recipient" class="text-[14px] font-black text-slate-800 px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none transition-all shadow-sm">
                                    <input type="text" wire:model="email_bcc" placeholder="Bcc Recipient" class="text-[14px] font-black text-slate-800 px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none transition-all shadow-sm">
                                </div>
                            </div>
                            <div class="h-px bg-slate-200/50 my-2"></div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest sm:w-24 text-left sm:text-right shrink-0">Subject:</label>
                                <input type="text" wire:model="email_subject" class="grow text-[14px] font-black text-slate-800 px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-plum/20 outline-none transition-all shadow-sm">
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="block text-[11px] font-black text-slate-400 mb-4 uppercase tracking-widest px-2">Email Body Content</label>
                            <textarea wire:model="email_body" rows="12" class="w-full p-8 bg-slate-50 border border-slate-100 rounded-3xl focus:bg-white focus:border-plum/30 outline-none text-[14px] font-medium leading-relaxed resize-none transition-all custom-scrollbar" placeholder="Enter email content..."></textarea>
                        </div>

                        @if ($email_attachment)
                        @php
                            $pricingToggle = ($email_type === 'debt') ? $debtor_pdf_prices : $invoice_pdf_prices;
                            $previewRoute = match($email_type) {
                                'invoice' => route('pdf.invoice', ['id' => $email_booking_id, 'prices' => $pricingToggle ? 1 : 0]),
                                'receipt' => route('pdf.receipt', ['id' => $email_booking_id, 'prices' => $pricingToggle ? 1 : 0]),
                                'debt' => route('pdf.debt', ['id' => $email_booking_id, 'prices' => $pricingToggle ? 1 : 0]),
                                'po','purchase_order' => route('pdf.po', ['id' => $email_booking_id, 'prices' => $pricingToggle ? 1 : 0]),
                                'envelope' => \Illuminate\Support\Facades\Route::has('pdf.delivery_receipt') ? route('pdf.delivery_receipt', ['id' => $email_booking_id, 'prices' => $pricingToggle ? 1 : 0]) : '#',
                                default => '#'
                            };
                        @endphp
                        <a href="{{ $previewRoute }}" target="_blank" class="flex items-center gap-4 bg-blue-50/50 p-6 rounded-3xl border border-blue-100 shadow-sm group hover:border-blue-300 hover:bg-blue-100/50 transition-all no-underline cursor-pointer">
                            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center text-blue-500 shadow-sm group-hover:scale-110 transition-transform">
                                <span class="material-symbols-rounded text-2xl font-bold">attach_file</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] mb-1">Attached Document (Click to Preview)</p>
                                <p class="text-[13px] font-black text-blue-700 truncate" x-text="$wire.email_attachment"></p>
                            </div>
                            <div class="text-blue-300 group-hover:text-blue-500 transition-colors">
                                <span class="material-symbols-rounded">open_in_new</span>
                            </div>
                        </a>
                        @endif

                        <div class="pt-8 border-t border-slate-50 flex gap-4">
                            <button type="button" @click="showEmailModal = false" class="px-8 py-5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-2xl transition-all uppercase tracking-[0.2em] border border-slate-100">Cancel</button>
                            <button type="submit" class="grow py-5 bg-plum text-white rounded-2xl font-black hover:bg-plum-dark shadow-xl shadow-plum/20 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-[11px] flex items-center justify-center gap-3">
                                <span wire:loading.remove wire:target="sendEmail" class="flex items-center gap-3">
                                    Send Email
                                </span>
                                <span wire:loading wire:target="sendEmail" class="flex items-center gap-3">
                                    <span class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- RECEIPT DETAIL MODAL -->
        <div x-show="showPaymentDetailsModal" class="fixed inset-0 z-10000 flex items-center justify-center p-6 sm:p-12 lg:p-20" x-cloak>
            <div x-show="showPaymentDetailsModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showPaymentDetailsModal = false"></div>

            <div x-show="showPaymentDetailsModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-[40px] shadow-2xl max-w-sm w-full z-10 flex flex-col max-h-[85vh] overflow-hidden transition-all border border-white/20">

                <!-- Header -->
                <div class="flex justify-between items-center p-8 border-b border-slate-50 shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 shadow-sm">
                            <span class="material-symbols-rounded text-2xl font-bold">receipt</span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase">Receipt Details</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (!$is_editing_payment)
                        <button type="button" wire:click="editPaymentDetails" class="text-blue-500 hover:text-blue-600 transition p-2 hover:bg-blue-50 rounded-xl">
                            <span class="material-symbols-rounded text-2xl font-bold">edit_square</span>
                        </button>
                        @endif
                        <button type="button" @click="showPaymentDetailsModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                            <span class="material-symbols-rounded text-2xl font-bold">close</span>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Content -->
                <div class="p-8 overflow-y-auto custom-scrollbar grow bg-white">
                    <div class="space-y-8">
                        @if ($view_payment_details)
                        @if ($is_editing_payment)
                        <!-- Edit Mode -->
                        <div class="space-y-5 px-2">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Payment Amount ($)</label>
                                <input type="number" step="0.01" wire:model="edit_payment_amount" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-plum/20 outline-none transition-all">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Date</label>
                                    <input type="date" wire:model="edit_payment_date" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold focus:ring-2 focus:ring-plum/20 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Method</label>
                                    <select wire:model="edit_payment_method" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold focus:ring-2 focus:ring-plum/20 outline-none transition-all appearance-none">
                                        <option value="EFT">EFT</option>
                                        <option value="Card Holder">Card Holder</option>
                                        <option value="Cash">Cash</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Reference Number</label>
                                <input type="text" wire:model="edit_payment_ref" placeholder="e.g. bf-1234" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-plum/20 outline-none transition-all">
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Internal Notes</label>
                                <textarea wire:model="edit_payment_notes" rows="3" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-plum/20 outline-none transition-all resize-none"></textarea>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" wire:click="updatePaymentDetails" class="flex-1 py-4 bg-plum text-white rounded-2xl font-black text-[11px] shadow-xl shadow-plum/20 hover:bg-plum-dark transition-all active:scale-95 uppercase tracking-widest flex items-center justify-center gap-2">
                                    <span wire:loading wire:target="updatePaymentDetails" class="material-symbols-rounded animate-spin text-sm">sync</span>
                                    Update Record
                                </button>
                                <button type="button" wire:click="cancelPaymentEdit" class="px-6 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-[11px] hover:bg-slate-200 transition-all uppercase tracking-widest">Cancel</button>
                            </div>
                        </div>
                        @else
                        <!-- View Mode -->
                        <div class="bg-slate-900 rounded-3xl p-8 text-white text-center shadow-xl shadow-slate-900/20 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-24 h-24 bg-green-500/10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-2xl"></div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Total Transaction</p>
                            <p class="text-4xl font-black tracking-tighter text-green-400">${{ number_format($view_payment_details->amount, 2) }}</p>
                        </div>

                        <div class="space-y-4 px-2">
                            <div class="flex justify-between items-center py-4 border-b border-slate-50">
                                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Captured Date</span>
                                <span class="text-[14px] font-black text-slate-700">{{ \Carbon\Carbon::parse($view_payment_details->payment_date)->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between items-center py-4 border-b border-slate-50">
                                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Payment ID</span>
                                <span class="text-[14px] font-black text-slate-800 bg-slate-50 px-3 py-1 rounded-lg">#{{ $view_payment_details->id }}</span>
                            </div>
                            <div class="flex justify-between items-center py-4 border-b border-slate-50">
                                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Method</span>
                                <span class="text-[13px] font-black text-plum uppercase tracking-wider">{{ $view_payment_details->payment_method === 'Card Holder' ? 'Card Holder' : $view_payment_details->payment_method }}</span>
                            </div>
                            <div class="flex justify-between items-center py-4 border-b border-slate-50">
                                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Reference ID</span>
                                <span class="text-[13px] font-black text-slate-700 break-all">{{ $view_payment_details->reference ?: 'GEN-' . strtoupper(substr(md5($view_payment_details->id), 0, 8)) }}</span>
                            </div>

                            @if ($view_payment_details->payment_method === 'Card Holder')
                            <div class="mt-6 p-6 bg-slate-50 rounded-3xl border border-slate-100 space-y-4 shadow-inner">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Card Holder</span>
                                    <span class="text-[12px] font-black text-slate-700 uppercase tracking-tight">{{ $view_payment_details->card_holder ?? ($view_payment_details->booking->card_holder ?? 'Unknown') }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-200/50 pt-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Card Network</span>
                                    <span class="text-[12px] font-black text-slate-700 uppercase">{{ $view_payment_details->card_network ?? ($view_payment_details->booking->card_type ?? 'Generic Card') }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-200/50 pt-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Card Number</span>
                                    <span class="text-[13px] font-mono font-black text-slate-800 tracking-tighter">**** **** {{ !empty($view_payment_details->card_number ?? $view_payment_details->booking->card_number) ? substr(str_replace(' ', '', ($view_payment_details->card_number ?? $view_payment_details->booking->card_number)), -4) : 'NULL' }}</span>
                                </div>
                            </div>
                            @elseif ($view_payment_details->payment_method === 'EFT')
                            <div class="flex justify-between items-center py-4 border-b border-slate-50">
                                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">EFT Method</span>
                                <span class="text-[14px] font-black text-slate-700">{{ $view_payment_details->booking->eft_method ?? 'Direct Deposit' }}</span>
                            </div>
                            @endif

                            @if ($view_payment_details->notes)
                            <div class="mt-6">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block px-1">Internal Notes</span>
                                <div class="bg-indigo-50/50 p-5 rounded-[18px] text-[13px] font-bold text-slate-600 italic leading-relaxed border border-indigo-100/50 shadow-sm">{{ $view_payment_details->notes }}</div>
                            </div>
                            @endif
                        </div>
                        @endif
                        @endif
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-8 pt-0 shrink-0">
                    <button type="button" @click="showPaymentDetailsModal = false" class="w-full py-5 bg-slate-900 text-white rounded-3xl font-black hover:bg-slate-800 transition shadow-xl shadow-slate-900/20 uppercase tracking-[0.2em] text-[11px] active:scale-[0.98]">Close Details</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- GENERAL SUCCESS MODAL -->
        <div x-show="showSuccessModal" class="fixed inset-0 z-10001 flex items-center justify-center p-4" x-cloak>
            <div x-show="showSuccessModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showSuccessModal = false"></div>

            <div x-show="showSuccessModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-4xl shadow-2xl p-10 max-w-sm w-full text-center z-10 overflow-hidden border border-slate-50 transition-all">

                <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-8 text-green-500 ring-8 ring-green-50/50 shadow-inner">
                    <span class="material-symbols-rounded text-5xl font-bold">mark_email_read</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight uppercase">Receipt Sent</h3>
                <p class="text-[14px] font-bold text-slate-400 mb-10 px-4 leading-relaxed tracking-tight">The receipt has been successfully delivered to the customer.</p>

                <button type="button" @click="showSuccessModal = false" class="w-full py-5 bg-slate-900 text-white rounded-3xl font-black hover:bg-slate-800 transition shadow-xl shadow-slate-900/20 uppercase tracking-[0.2em] text-[11px] active:scale-[0.98]">Continue</button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- PAYMENT SUCCESS MODAL -->
        <div x-show="showPaymentSuccessModal" class="fixed inset-0 z-10001 flex items-center justify-center p-6 sm:p-12 lg:p-20" x-cloak>
            <div x-show="showPaymentSuccessModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showPaymentSuccessModal = false"></div>

            <div x-show="showPaymentSuccessModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-[40px] shadow-2xl p-12 max-w-sm w-full text-center z-10 overflow-hidden border border-slate-50 transition-all">

                <div class="w-24 h-24 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-8 text-emerald-500 ring-8 ring-emerald-50/50 shadow-inner">
                    <span class="material-symbols-rounded text-5xl font-bold">verified</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-2 tracking-tight uppercase">Payment Recorded</h3>
                <p class="text-[14px] font-bold text-slate-400 mb-10 px-4 leading-relaxed tracking-tight">The payment has been validated and recorded in the system.</p>

                <button type="button" @click="showPaymentSuccessModal = false" class="w-full py-5 bg-slate-900 text-white rounded-3xl font-black hover:bg-slate-800 transition shadow-xl shadow-slate-900/20 uppercase tracking-[0.2em] text-[11px] active:scale-[0.98]">Great, Thanks!</button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- COMMUNICATION SENT MODAL -->
        <div x-show="sentSuccessModal" class="fixed inset-0 z-10001 flex items-center justify-center p-6 sm:p-12 lg:p-20" x-cloak>
            <div x-show="sentSuccessModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="sentSuccessModal = false; $wire.resetEmailState()"></div>

            <div x-show="sentSuccessModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-[40px] shadow-2xl p-12 max-w-sm w-full text-center z-10 overflow-hidden border border-slate-50 transition-all">

                <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-8 text-green-600 ring-8 ring-green-50/50 shadow-inner">
                    <span class="material-symbols-rounded text-5xl font-bold">mail_lock</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-2 tracking-tight uppercase">Process Complete</h3>
                <p class="text-[14px] font-bold text-slate-400 mb-10 px-6 leading-relaxed tracking-tight">The customer documentation has been successfully dispatched.</p>

                <button @click="sentSuccessModal = false; $wire.resetEmailState()" class="w-full py-5 bg-plum text-white rounded-3xl font-black hover:bg-plum-dark shadow-xl shadow-plum/20 transition-all active:scale-95 uppercase tracking-[0.2em] text-[11px]">Dismiss</button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="confirmEmailModal" class="fixed inset-0 z-10000 flex items-center justify-center p-4" x-cloak>
            <div x-show="confirmEmailModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="confirmEmailModal = false"></div>

            <div x-show="confirmEmailModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-4xl shadow-2xl p-12 max-w-md w-full text-center z-10 overflow-hidden border border-slate-50 transition-all">

                <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-8 text-amber-600 ring-8 ring-amber-50/50 shadow-inner">
                    <span class="material-symbols-rounded text-4xl font-bold">notification_important</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-4 tracking-tight uppercase" x-text="$wire.confirmEmailTitle"></h3>
                <div class="text-[14px] font-bold text-slate-500 mb-10 bg-slate-50 p-8 rounded-3xl border border-slate-100 leading-relaxed text-left shadow-inner tracking-tight" x-html="$wire.confirmEmailMessage"></div>

                <div class="flex gap-4">
                    <button @click="confirmEmailModal = false" class="flex-1 py-5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-2xl transition-all uppercase tracking-[0.2em] border border-slate-100">Decline</button>
                    <button wire:click="proceedWithEmail" class="flex-1 py-5 bg-plum text-white hover:bg-plum-dark rounded-2xl font-black text-[11px] shadow-xl shadow-plum/20 transition-all active:scale-95 uppercase tracking-[0.2em]">Authorise</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="quotaWarningModal" class="fixed inset-0 z-10001 flex items-center justify-center p-4" x-cloak>
            <div x-show="quotaWarningModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="quotaWarningModal = false"></div>

            <div x-show="quotaWarningModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-4xl shadow-2xl p-10 max-w-md w-full text-center z-10 overflow-hidden border border-slate-50 transition-all">

                <div class="w-20 h-20 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-8 text-rose-500 ring-8 ring-rose-50/50 shadow-inner">
                    <span class="material-symbols-rounded text-4xl font-bold">warning</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-4 tracking-tight uppercase" x-text="$wire.quotaWarningTitle"></h3>
                <p class="text-[14px] font-bold text-slate-500 mb-10 leading-relaxed px-4 tracking-tight" x-text="$wire.quotaWarningMessage"></p>

                <div class="flex gap-4">
                    <button @click="quotaWarningModal = false" class="flex-1 py-5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-2xl transition-all uppercase tracking-[0.2em] border border-slate-100">Cancel</button>
                    <button wire:click="continueEmailAfterQuotaWarning" class="flex-1 py-5 bg-rose-500 text-white hover:bg-rose-600 rounded-2xl font-black text-[11px] shadow-xl shadow-rose-500/20 transition-all active:scale-95 uppercase tracking-[0.2em]">Bypass Quota</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="quotaLimitModal" class="fixed inset-0 z-10001 flex items-center justify-center p-4" x-cloak>
            <div x-show="quotaLimitModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="quotaLimitModal = false"></div>

            <div x-show="quotaLimitModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white rounded-4xl shadow-2xl p-12 max-w-md w-full text-center z-10 overflow-hidden border border-slate-50 transition-all">

                <div class="w-24 h-24 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-8 text-rose-500 ring-8 ring-rose-50/50 shadow-inner">
                    <span class="material-symbols-rounded text-5xl font-bold">block_flipped</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-4 tracking-tight uppercase" x-text="$wire.quotaLimitTitle"></h3>
                <p class="text-[14px] font-bold text-slate-400 mb-12 leading-relaxed px-4 tracking-tight" x-text="$wire.quotaLimitMessage"></p>

                <button @click="quotaLimitModal = false" class="w-full py-5 bg-slate-900 text-white rounded-3xl font-black hover:bg-slate-800 transition shadow-xl shadow-slate-900/20 uppercase tracking-[0.2em] text-[11px] active:scale-[0.98]">Close</button>
            </div>
        </div>
    </template>



</div>