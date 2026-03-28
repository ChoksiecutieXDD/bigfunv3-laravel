<div class="max-w-[1440px] mx-auto space-y-8"
    x-data="{ 
         showPaymentModal: false, 
         showCardModal: false, 
         showEftModal: false, 
         showEmailModal: false, 
         showPaymentDetailsModal: false,
         showSuccessModal: false,
         showPaymentSuccessModal: false,
         showConfirmModal: false,
         confirmMessage: '',
         confirmAction: null
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

    <div class="flex flex-col gap-1">
        <h2 class="text-3xl font-bold text-white drop-shadow-md">Logistics Inbox</h2>
        <p class="text-white/80 text-sm font-medium">Manage daily dispatching, payments, and invoicing tasks.</p>
    </div>

    @if ($enquiriesCount > 0)
    <div class="bg-yellow-50 border border-yellow-200 p-5 rounded-[2rem] shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-symbols-rounded text-2xl">notifications_active</span>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-lg">Attention Needed</h3>
                <p class="text-sm text-gray-600">You have <span class="font-bold text-yellow-700">{{ $enquiriesCount }}</span> enquiry pending follow-up.</p>
            </div>
        </div>
        <a href="{{ route('supervisor.enquiries') }}" class="px-6 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-xl text-sm shadow-lg shadow-yellow-500/30 transition transform active:scale-95 whitespace-nowrap">View Enquiries</a>
    </div>
    @endif

    <section class="bg-white rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="p-2 bg-[#9E6B73]/10 text-[#9E6B73] rounded-xl"><span class="material-symbols-rounded">credit_card</span></div>
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Manage Payment Methods</h3>
                    <p class="text-xs text-gray-400">Total Pending: {{ $pendingPayments->total() }}</p>
                </div>
            </div>
            <div class="flex items-center w-full md:w-auto gap-2">
                <div class="relative w-full md:w-56">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search_pay" placeholder="Search Payments..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#9E6B73]/20 outline-none">
                </div>
            </div>
        </div>

        <div class="overflow-y-auto custom-scrollbar" style="max-height: 500px;">
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
                    $paid = (float)$row->amount_paid;
                    $outstanding = max(0, $total - $paid);
                    $isCard = stripos($row->payment_type, 'Card') !== false;
                    $cardNum = $row->card_number ? '•••• ' . substr($row->card_number, -4) : 'N/A';
                    $rawNetwork = $this->getCardNetwork($row->card_number, $row->card_type);
                    $cardStyle = $this->getCardStyle($rawNetwork);
                    @endphp
                    <tr class="group hover:bg-gray-50 transition-colors">
                        <td class="p-4 text-left align-top">
                            <div class="flex flex-col items-start gap-1 justify-start h-full">
                                <div class="font-bold text-gray-800 text-sm">{{ $row->customer_first_name }} {{ $row->customer_last_name }}</div>
                                <div class="text-xs text-gray-500 font-medium">{{ $row->customer_organization }}</div>
                                <div class="mt-2 flex items-center gap-2">
                                    <a href="{{ route('supervisor.bookings.overview', ['id' => $row->id, 'back' => route('supervisor.logistics')]) }}"
 class="bg-gray-100 text-gray-600 hover:bg-[#9E6B73] hover:text-white transition text-[10px] font-bold px-2 py-0.5 rounded border border-gray-200 no-underline">ID: #{{ $row->id }}</a>
                                    <span class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($row->event_date)->format('d/m/y') }}</span>
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
                                    <div class="flex justify-between"><span class="text-gray-500 font-bold">Owing:</span> <span class="font-bold text-red-500">${{ number_format($outstanding, 2) }}</span></div>
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
                                            <div class="text-[10px] text-gray-400">Exp: {{ $row->card_expiry ?: '--/--' }}</div>
                                        </div>
                                    </div>
                                    <button wire:click="openCardModal({{ $row->id }})" class="text-gray-300 hover:text-[#9E6B73] transition p-1"><span class="material-symbols-rounded text-sm">edit_square</span></button>
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
                                    <button wire:click="openEftModal({{ $row->id }})" class="text-gray-300 hover:text-[#9E6B73] transition p-1"><span class="material-symbols-rounded text-sm">edit_square</span></button>
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="p-4 align-top text-xs w-48">
                            @forelse ($row->payments as $index => $hist)
                            <div class="border-b border-gray-100 last:border-0 pb-1.5 last:pb-0 mb-1.5">
                                <div class="flex justify-between items-center mb-0.5">
                                    <span class="font-bold text-[#9E6B73]">Payment {{ $index + 1 }}</span>
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
                                <div class="flex items-stretch w-full border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white focus-within:border-[#9E6B73] focus-within:ring-1 transition-all"
                                    x-data="{ method: '{{ $row->payment_type }}' }">
                                    <select x-model="method" class="flex-1 py-1.5 px-2 text-xs border-none bg-transparent text-gray-700 font-semibold outline-none cursor-pointer">
                                        <option value="Card Holder">Card Holder</option>
                                        <option value="EFT">EFT / Direct</option>
                                    </select>
                                    <button type="button" @click="confirmMessage = 'Are you sure you want to change the payment method for this booking?'; confirmAction = () => $wire.savePaymentType({{ $row->id }}, method); showConfirmModal = true;" class="bg-gray-50 hover:bg-[#9E6B73]/10 text-gray-500 hover:text-[#9E6B73] transition-colors border-l border-gray-200 px-2.5 flex items-center justify-center cursor-pointer">
                                        <span class="material-symbols-rounded text-[16px]">save</span>
                                    </button>
                                </div>
                                @if ($outstanding > 0)
                                <button wire:click="openPaymentModal({{ $row->id }})" class="w-full bg-[#9E6B73] hover:bg-[#86545C] text-white py-1.5 rounded-lg shadow-sm transition transform active:scale-95 text-xs font-bold flex items-center justify-center gap-1">
                                    <span class="material-symbols-rounded text-[16px] text-white">payments</span> Process
                                </button>
                                @else
                                <div class="w-full bg-green-50 text-green-700 border border-green-200 py-1.5 rounded-lg shadow-sm text-xs font-bold flex items-center justify-center gap-1">
                                    <span class="material-symbols-rounded text-[16px]">check_circle</span> Fully Paid
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-gray-400 italic">No payments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pendingPayments->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">{{ $pendingPayments->links() }}</div>
        @endif
    </section>

    <section class="bg-white rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-[#9E6B73]/10 text-[#9E6B73] rounded-xl"><span class="material-symbols-rounded">description</span></div>
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Invoices to Send</h3>
                    <p class="text-xs text-gray-400">Total Pending: {{ $invoices->total() }}</p>
                </div>
            </div>
            <div class="relative w-full md:w-56">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                <input type="text" wire:model.live.debounce.300ms="search_inv" placeholder="Search Invoices..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#9E6B73] outline-none">
            </div>
        </div>

        <div class="overflow-y-auto custom-scrollbar" style="max-height: 500px;">
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
                        <td class="p-4 align-top text-left"><span class="font-mono font-bold text-[#9E6B73] bg-[#9E6B73]/5 px-2 py-1 rounded text-xs border border-[#9E6B73]">#{{ $inv->invoice_number ?? $inv->id }}</span></td>
                        <td class="p-4 align-top text-left">
                            <div class="font-bold text-gray-800 text-sm">{{ $inv->customer_first_name }} {{ $inv->customer_last_name }}</div>
                            <div class="text-xs text-gray-500 font-medium">{{ $inv->customer_organization }}</div>
                        </td>
                        <td class="p-4 align-top text-left w-72">
                            <div class="flex items-start gap-1">
                                <span class="material-symbols-rounded text-gray-300 text-sm mt-0.5">location_on</span>
                                <p class="text-xs text-gray-600 leading-relaxed">{{ $inv->address_line_1 }}, {{ $inv->suburb }}</p>
                            </div>
                            <div class="text-[10px] text-red-400 font-bold mt-1 ml-5">Due: {{ \Carbon\Carbon::parse($inv->event_date)->format('M d') }}</div>
                        </td>
                        <td class="p-4 align-middle text-right">
                            <div class="flex gap-2 justify-end flex-wrap">
                                <button wire:click="prepareEmail({{ $inv->id }}, 'invoice')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold border transition transform active:scale-95 bg-[#9E6B73] text-white hover:bg-[#86545C]">
                                    <span class="material-symbols-rounded text-xs">receipt_long</span> Send Invoice
                                </button>
                                <button wire:click="prepareEmail({{ $inv->id }}, 'receipt')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition transform active:scale-95">
                                    <span class="material-symbols-rounded text-xs">payments</span> Receipt
                                </button>
                                <button wire:click="prepareEmail({{ $inv->id }}, 'envelope')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition transform active:scale-95">
                                    <span class="material-symbols-rounded text-xs">mail</span> Envelope
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
        @if ($invoices->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">{{ $invoices->links() }}</div>
        @endif
    </section>

    <section class="bg-white rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-[#9E6B73]/10 text-[#9E6B73] rounded-xl"><span class="material-symbols-rounded">inventory_2</span></div>
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Upcoming Orders</h3>
                    <p class="text-xs text-gray-400">Total: {{ $orders->total() }}</p>
                </div>
            </div>
            <div class="relative w-full md:w-56">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                <input type="text" wire:model.live.debounce.300ms="search_ord" placeholder="Search Orders..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#9E6B73] outline-none">
            </div>
        </div>
        <div class="overflow-x-auto custom-scrollbar" style="max-height: 500px;">
            <table class="w-full text-left text-sm border-collapse relative">
                <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                    <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                        <th class="p-4 font-bold text-left">Event Date</th>
                        <th class="p-4 font-bold text-left">Customer</th>
                        <th class="p-4 font-bold text-left">Lead Operator</th>
                        <th class="p-4 font-bold text-left">Lead Deliverer</th>
                        <th class="p-4 font-bold text-left">Status</th>
                        <th class="p-4 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    @forelse ($orders as $ord)
                    <tr class="group hover:bg-gray-50 transition-colors">
                        <td class="p-4 font-bold text-gray-700 align-top text-left">{{ \Carbon\Carbon::parse($ord->event_date)->format('M d, Y') }}</td>
                        <td class="p-4 text-gray-700 font-bold align-top text-left">{{ $ord->customer_first_name }} {{ $ord->customer_last_name }}</td>
                        <td class="p-4 text-xs align-top text-left">
                            <span class="{{ !$ord->lead_operator ? 'text-gray-400 italic' : 'text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded' }}">{{ $ord->lead_operator ?: 'Unassigned' }}</span>
                        </td>
                        <td class="p-4 text-xs align-top text-left">
                            <span class="{{ !$ord->lead_deliverer ? 'text-gray-400 italic' : 'text-orange-600 font-bold bg-orange-50 px-2 py-0.5 rounded' }}">{{ $ord->lead_deliverer ?: 'Unassigned' }}</span>
                        </td>
                        <td class="p-4 align-top text-left"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Confirmed</span></td>
                        <td class="p-4 text-right align-top">
                            <a href="{{ route('supervisor.bookings.overview', ['id' => $ord->id, 'back' => route('supervisor.logistics')]) }}" class="inline-flex items-center gap-1 bg-[#9E6B73] text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-md hover:bg-[#86545C] transition transform active:scale-95 no-underline">
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
        @if ($orders->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">{{ $orders->links() }}</div>
        @endif
    </section>

    <section class="bg-white rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-[#9E6B73]/10 text-[#9E6B73] rounded-xl"><span class="material-symbols-rounded">money_off</span></div>
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Debtors</h3>
                    <p class="text-xs text-gray-400">Total: {{ $debtors->total() }}</p>
                </div>
            </div>
            <div class="relative w-full md:w-56">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                <input type="text" wire:model.live.debounce.300ms="search_deb" placeholder="Search Debtors..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#9E6B73] outline-none">
            </div>
        </div>
        <div class="overflow-x-auto custom-scrollbar" style="max-height: 500px;">
            <table class="w-full text-left text-sm border-collapse relative">
                <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-sm z-10 shadow-sm">
                    <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                        <th class="p-4 font-bold text-left">Customer</th>
                        <th class="p-4 font-bold text-left">Financials</th>
                        <th class="p-4 font-bold text-left">Method</th>
                        <th class="p-4 font-bold text-left">Contact</th>
                        <th class="p-4 font-bold text-right w-40">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    @forelse ($debtors as $deb)
                    @php
                    $debTotal = (float)$deb->total_amount;
                    $debPaid = (float)$deb->amount_paid;
                    $amountDue = $debTotal - $debPaid;
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
                            <div class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($deb->event_date)->format('d/m/y') }}</div>
                        </td>
                        <td class="p-4 align-middle text-right">
                            <a href="{{ route('supervisor.bookings.overview', ['id' => $deb->id, 'back' => route('supervisor.logistics')]) }}" class="bg-[#9E6B73] hover:bg-[#86545C] text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-md transition transform active:scale-95 inline-flex items-center gap-1 no-underline">
                                <span class="material-symbols-rounded text-sm text-white">visibility</span> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-400 italic">No debtors found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($debtors->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">{{ $debtors->links() }}</div>
        @endif
    </section>

    <section class="bg-white rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-[#9E6B73]/10 text-[#9E6B73] rounded-xl"><span class="material-symbols-rounded">badge</span></div>
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Operators</h3>
                    <p class="text-xs text-gray-400">Total: {{ $operators->total() }}</p>
                </div>
            </div>
            <div class="relative w-full md:w-56">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-rounded text-sm">search</span>
                <input type="text" wire:model.live.debounce.300ms="search_op" placeholder="Search Staff..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#9E6B73] outline-none">
            </div>
        </div>
        <div class="overflow-x-auto custom-scrollbar" style="max-height: 500px;">
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
        @if ($operators->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">{{ $operators->links() }}</div>
        @endif
    </section>

    <template x-teleport="body">
        <div style="z-index: 10000; position: relative;">
            <div x-show="showConfirmModal" x-transition.opacity style="display: none;" class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showConfirmModal = false"></div>
            <div x-show="showConfirmModal" x-transition.scale style="display: none;" class="fixed inset-0 flex items-center justify-center" @click.self="showConfirmModal = false">
                <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm text-center">
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4 text-amber-600"><span class="material-symbols-rounded text-3xl">help</span></div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Confirm Action</h3>
                    <p class="text-sm text-gray-600 mb-6" x-text="confirmMessage"></p>
                    <div class="flex justify-center gap-3">
                        <button type="button" @click="showConfirmModal = false" class="px-5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-bold">Cancel</button>
                        <button type="button" @click="if(confirmAction) confirmAction(); showConfirmModal = false;" class="px-5 py-2.5 rounded-xl bg-[#9E6B73] hover:bg-[#86545C] text-white font-bold shadow-md">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div style="z-index: 9999; position: relative;">
            <div x-show="showPaymentModal" x-transition.opacity style="display: none;" class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPaymentModal = false"></div>
            <div x-show="showPaymentModal" x-transition.scale style="display: none;" class="fixed inset-0 flex items-center justify-center" @click.self="showPaymentModal = false">
                <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm flex flex-col">
                    <div class="flex justify-between items-center mb-5">
                        <h3 class="text-xl font-extrabold text-gray-800">Record Payment</h3>
                        <button type="button" @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600 transition"><span class="material-symbols-rounded">close</span></button>
                    </div>
                    <form wire:submit.prevent="processPayment" class="space-y-4">
                        <div class="flex justify-between items-center text-xs text-gray-500 border-b border-gray-100 pb-2">
                            <div>
                                <div class="font-bold text-gray-700 truncate max-w-[180px]">{{ $pay_context['customer_name'] ?? '' }}</div>
                                <span class="text-[10px] text-gray-400 font-mono mt-0.5 inline-block">INV {{ $pay_context['invoice_num'] ?? '' }}</span>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-[1px]">Amount Due</div>
                                <div class="text-sm font-black text-[#9E6B73]">${{ number_format($pay_context['owing'] ?? 0, 2) }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Amount ($)</label>
                            <input type="number" wire:model="pay_amount" step="0.01" required class="modal-input font-bold text-gray-800 focus:ring-2 focus:ring-[#9E6B73]/20">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Payment For</label>
                            <select wire:model.live="pay_type" class="modal-input bg-white cursor-pointer focus:ring-2 focus:ring-[#9E6B73]/20">
                                <option value="Deposit">Deposit</option>
                                <option value="Remaining Balance">Remaining Balance</option>
                                <option value="Full Amount">Full Amount</option>
                                <option value="Partial Payment">Partial Payment</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Payment Method</label>
                            <select wire:model.live="pay_method" class="modal-input bg-white cursor-pointer focus:ring-2 focus:ring-[#9E6B73]/20">
                                <option value="EFT">EFT / Bank Transfer</option>
                                <option value="Card Holder">Card Holder</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>

                        @if ($pay_method === 'EFT')
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Specific Method</label>
                            <select wire:model="eft_specific_method" class="modal-input bg-white cursor-pointer">
                                <option value="Direct Deposit">Direct Deposit</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Osko">Osko</option>
                                <option value="PayID">PayID</option>
                            </select>
                        </div>
                        @elseif ($pay_method === 'Card Holder')
                        <div class="grid grid-cols-2 gap-3 bg-gray-50/50 p-3 rounded-lg border border-dashed border-gray-200">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1 uppercase tracking-wide">Category</label>
                                <select wire:model="modal_card_category" class="modal-input bg-white text-xs text-gray-600 focus:ring-1 cursor-pointer">
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="Credit Card">Credit Card</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1 uppercase tracking-wide">Network</label>
                                <select wire:model="modal_card_network" class="modal-input bg-white text-xs text-gray-600 focus:ring-1 cursor-pointer">
                                    <option value="Visa">Visa</option>
                                    <option value="MasterCard">MasterCard</option>
                                    <option value="Amex">Amex</option>
                                </select>
                            </div>
                        </div>
                        @endif

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Date</label>
                            <input type="date" wire:model="pay_date" class="modal-input">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Reference No.</label>
                            <input type="text" wire:model="pay_ref" placeholder="e.g. INV-1234" class="modal-input">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Notes (Optional)</label>
                            <textarea wire:model="pay_notes" rows="2" class="modal-input resize-none"></textarea>
                        </div>
                        <button type="submit" class="w-full py-3 rounded-xl bg-[#9E6B73] hover:bg-[#86545C] text-white font-bold shadow-lg shadow-plum/20 transition transform active:scale-95 text-sm mt-2">
                            <span wire:loading.remove wire:target="processPayment">Save Payment</span>
                            <span wire:loading wire:target="processPayment">Processing...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div style="z-index: 9999; position: relative;">
            <div x-show="showCardModal" x-transition.opacity style="display: none;" class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showCardModal = false"></div>
            <div x-show="showCardModal" x-transition.scale style="display: none;" class="fixed inset-0 flex items-center justify-center" @click.self="showCardModal = false">
                <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm transform scale-100 transition-all">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Edit Card Details</h3>
                        <button type="button" @click="showCardModal = false" class="text-gray-400 hover:text-gray-600"><span class="material-symbols-rounded">close</span></button>
                    </div>
                    <form wire:submit.prevent="saveCardDetails" class="space-y-3">
                        <div>
                            <label class="text-xs font-bold text-gray-500">Category</label>
                            <select wire:model="edit_card_category" class="modal-input bg-white cursor-pointer">
                                <option value="Debit Card">Debit Card</option>
                                <option value="Credit Card">Credit Card</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500">Card Network</label>
                            <select wire:model="edit_card_type" class="modal-input bg-white cursor-pointer">
                                <option value="Visa">Visa</option>
                                <option value="MasterCard">MasterCard</option>
                                <option value="Amex">Amex</option>
                                <option value="Discover">Discover</option>
                                <option value="Bartercard">Bartercard</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500">Number</label>
                            <input type="text" wire:model="edit_card_number" class="modal-input" placeholder="0000 0000 0000 0000" maxlength="19">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-bold text-gray-500">Expiry</label>
                                <input type="text" wire:model="edit_card_expiry" class="modal-input text-center" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500">CVV</label>
                                <input type="text" wire:model="edit_card_cvv" class="modal-input text-center" placeholder="123" maxlength="4">
                            </div>
                        </div>
                        <button type="submit" class="w-full mt-5 py-2 rounded-xl bg-[#9E6B73] hover:bg-[#86545C] text-white font-bold shadow-lg">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div style="z-index: 9999; position: relative;">
            <div x-show="showEftModal" x-transition.opacity style="display: none;" class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showEftModal = false"></div>
            <div x-show="showEftModal" x-transition.scale style="display: none;" class="fixed inset-0 flex items-center justify-center" @click.self="showEftModal = false">
                <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Edit EFT Details</h3>
                        <button type="button" @click="showEftModal = false" class="text-gray-400 hover:text-gray-600"><span class="material-symbols-rounded">close</span></button>
                    </div>
                    <form wire:submit.prevent="saveEftDetails" class="space-y-3">
                        <div>
                            <label class="text-xs font-bold text-gray-500">Specific Method</label>
                            <select wire:model="edit_eft_method" class="modal-input bg-white cursor-pointer">
                                <option value="Direct Deposit">Direct Deposit</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Osko">Osko</option>
                                <option value="PayID">PayID</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full mt-5 py-2 rounded-xl bg-[#9E6B73] hover:bg-[#86545C] text-white font-bold shadow-lg">Save Details</button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div style="z-index: 9999; position: relative;">
            <div x-show="showEmailModal" x-transition.opacity style="display: none;" class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showEmailModal = false"></div>
            <div x-show="showEmailModal" x-transition.scale style="display: none;" class="fixed inset-0 flex items-center justify-center" @click.self="showEmailModal = false">
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[90vh]">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white rounded-t-xl">
                        <div class="flex items-center gap-2 text-[#9E6B73]">
                            <span class="material-symbols-rounded text-xl">mail</span>
                            <h3 class="font-bold text-lg text-[#9E6B73]">Send Email</h3>
                        </div>
                        <button type="button" @click="showEmailModal = false" class="text-gray-400 hover:text-gray-600 transition"><span class="material-symbols-rounded text-2xl">close</span></button>
                    </div>
                    <div class="p-6 overflow-y-auto custom-scrollbar">
                        <form wire:submit.prevent="sendEmail">
                            <div class="flex items-start mb-2">
                                <label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3 pt-2">From:</label>
                                <div class="flex-grow space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 w-12 text-right">Address:</span>
                                        <input type="text" value="bigfun.qld.au@gmail.com" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-100 text-slate-500 cursor-not-allowed" readonly>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 w-12 text-right">Name:</span>
                                        <input type="text" value="BigFun" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-100 text-slate-500 cursor-not-allowed" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center mb-2">
                                <label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">To:</label>
                                <input type="text" wire:model="email_to" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9E6B73]">
                            </div>
                            <div class="flex items-center mb-2">
                                <label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Cc:</label>
                                <input type="text" wire:model="email_cc" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9E6B73]">
                            </div>
                            <div class="flex items-center mb-2">
                                <label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Bcc:</label>
                                <input type="text" wire:model="email_bcc" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9E6B73]">
                            </div>
                            <div class="flex items-center mb-2">
                                <label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Subject:</label>
                                <input type="text" wire:model="email_subject" class="flex-grow text-xs p-1.5 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9E6B73] font-medium text-slate-800">
                            </div>
                            <div class="flex items-center mb-4">
                                <label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Attachment:</label>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" checked class="w-4 h-4 text-[#9E6B73] rounded border-gray-300">
                                    <span class="text-[#3182ce] underline font-semibold text-[13px] cursor-pointer">{{ $email_attachment }}</span>
                                </div>
                            </div>
                            <div class="flex items-start mb-4">
                                <label class="text-[11px] font-bold text-slate-500 w-24 text-right pr-3">Body:</label>
                                <textarea wire:model="email_body" rows="8" class="flex-grow text-xs p-2 border border-slate-200 rounded bg-slate-50 focus:bg-white outline-none focus:border-[#9E6B73] font-mono leading-relaxed resize-none"></textarea>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <button type="button" @click="showEmailModal = false" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 text-xs font-bold hover:bg-gray-50 transition">Cancel</button>
                                <button type="submit" class="px-6 py-2 rounded-lg bg-[#9E6B73] text-white text-xs font-bold shadow-md shadow-[#9E6B73]/20 hover:bg-[#86545C] transition flex items-center gap-2">
                                    <span wire:loading.remove wire:target="sendEmail"><i class="fa-solid fa-paper-plane mr-1"></i> Send Email</span>
                                    <span wire:loading wire:target="sendEmail">Sending...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div style="z-index: 10000; position: relative;">
            <div x-show="showPaymentDetailsModal" x-transition.opacity style="display: none;" class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showPaymentDetailsModal = false"></div>
            <div x-show="showPaymentDetailsModal" x-transition.scale style="display: none;" class="fixed inset-0 flex items-center justify-center" @click.self="showPaymentDetailsModal = false">
                <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                        <h3 class="text-lg font-bold text-gray-800">Transaction Details</h3>
                        <button type="button" @click="showPaymentDetailsModal = false" class="text-gray-400 hover:text-gray-600 transition"><span class="material-symbols-rounded">close</span></button>
                    </div>
                    <div class="space-y-3 text-sm text-gray-700">
                        @if ($view_payment_details)
                        <div class="flex justify-between"><span class="font-bold text-gray-500">Amount:</span> <span class="text-green-600 font-bold">${{ number_format($view_payment_details->amount, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-bold text-gray-500">Date:</span> <span class="font-medium">{{ \Carbon\Carbon::parse($view_payment_details->payment_date)->format('d/m/Y') }}</span></div>
                        <div class="flex justify-between"><span class="font-bold text-gray-500">Method:</span> <span class="font-medium">{{ $view_payment_details->payment_method }}</span></div>

                        @if ($view_payment_details->payment_method === 'Card Holder')
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-500">Card:</span>
                            <span class="font-mono bg-gray-50 px-1.5 py-0.5 rounded border text-xs text-gray-600">
                                {{ $view_payment_details->booking->card_type ?? 'Card' }} *{{ substr($view_payment_details->booking->card_number ?? '0000', -4) }}
                            </span>
                        </div>
                        @elseif ($view_payment_details->payment_method === 'EFT')
                        <div class="flex justify-between"><span class="font-bold text-gray-500">Via:</span> <span class="font-medium">{{ $view_payment_details->booking->eft_method ?? 'Direct Deposit' }}</span></div>
                        @endif

                        @if ($view_payment_details->notes)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <span class="font-bold text-gray-500 block mb-1">Reference / Notes:</span>
                            <div class="bg-gray-50 p-2 rounded text-xs italic text-gray-600">{{ $view_payment_details->notes }}</div>
                        </div>
                        @endif
                        @endif
                    </div>
                    <button type="button" @click="showPaymentDetailsModal = false" class="w-full mt-6 py-2.5 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold transition">Close</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div style="z-index: 10001; position: relative;">
            <div x-show="showSuccessModal" x-transition.opacity style="display: none;" class="fixed inset-0 bg-black/30 backdrop-blur-sm" @click="showSuccessModal = false"></div>
            <div x-show="showSuccessModal" x-transition.scale style="display: none;" class="fixed inset-0 flex items-center justify-center" @click.self="showSuccessModal = false">
                <div class="relative bg-white rounded-2xl shadow-xl p-8 max-w-sm w-full text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4"><span class="material-symbols-rounded text-3xl text-green-600">check_circle</span></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Email Sent!</h3>
                    <p class="text-gray-600 mb-6">The email has been successfully sent to the customer.</p>
                    <button type="button" @click="showSuccessModal = false" class="bg-[#9E6B73] hover:bg-[#86545C] text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md transition w-full">OK, Got it</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div style="z-index: 10001; position: relative;">
            <div x-show="showPaymentSuccessModal" x-transition.opacity style="display: none;" class="fixed inset-0 bg-black/30 backdrop-blur-sm" @click="showPaymentSuccessModal = false"></div>
            <div x-show="showPaymentSuccessModal" x-transition.scale style="display: none;" class="fixed inset-0 flex items-center justify-center" @click.self="showPaymentSuccessModal = false">
                <div class="relative bg-white rounded-2xl shadow-xl p-8 max-w-sm w-full text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4"><span class="material-symbols-rounded text-3xl text-green-600">payments</span></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Payment Saved!</h3>
                    <p class="text-gray-600 mb-6">The payment record has been successfully updated.</p>
                    <button type="button" @click="showPaymentSuccessModal = false" class="bg-[#9E6B73] hover:bg-[#86545C] text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md transition w-full">OK, Got it</button>
                </div>
            </div>
        </div>
    </template>

</div>