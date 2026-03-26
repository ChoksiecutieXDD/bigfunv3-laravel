<div x-data="{ 
        deleteModal: false, 
        paymentModal: false, 
        emailModal: false, 
        historyModal: false,
        calendarModal: false,
        draftModal: false,
        payMethod: @entangle('payMethod'),
        selectedPayment: null,
        paymentDetailsModal: false
    }"
    class="max-w-[1600px] mx-auto space-y-6">

    <!-- Header & Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Booking #{{ $booking->id }}</h1>
            <span class="{{ $statusColor }} px-3 py-1 rounded-full text-xs md:text-sm font-bold uppercase tracking-wider border w-fit">
                {{ $booking->status }}
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <button @click="deleteModal = true" class="flex items-center gap-1 text-xs font-bold text-red-500 bg-red-50 px-3 py-1.5 rounded-lg border border-red-100 hover:bg-red-100 transition shadow-sm">
                <span class="material-symbols-rounded text-base">delete</span> Delete
            </button>
        </div>
    </div>

    <!-- Booking Origin & Timeline -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center gap-2 mb-2 pb-2 border-b border-gray-100 text-[#9D686E]">
            <span class="material-symbols-rounded">history_edu</span>
            <span class="text-sm font-bold uppercase tracking-wide">Booking Origin & Timeline</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Created On</span>
                <span class="font-semibold text-gray-700 text-sm">{{ \Carbon\Carbon::parse($booking->created_at)->format('d M Y, h:i A') }}</span>
            </div>
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Booked By</span>
                <span class="font-semibold text-gray-700 text-sm">{{ $booking->booked_by ?? 'System' }}</span>
            </div>
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Current Status</span>
                <span class="font-bold {{ $booking->status == 'Confirmed' ? 'text-green-600' : 'text-gray-700' }}">{{ strtoupper($booking->status) }}</span>
            </div>
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Invoice No</span>
                <span class="font-mono text-gray-600">{{ $booking->invoice_number ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Moved Alert -->
    @if(\Carbon\Carbon::parse($booking->created_at)->format('Y-m-d') !== \Carbon\Carbon::parse($booking->event_date)->format('Y-m-d'))
    <div class="bg-amber-50 rounded-2xl shadow-sm border border-amber-100 p-4 flex items-center gap-4 animate-[fadeIn_0.3s_ease-out_forwards]">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 shrink-0">
            <span class="material-symbols-rounded">edit_calendar</span>
        </div>
        <div>
            <h4 class="text-sm font-bold text-amber-800">Booking Moved</h4>
            <p class="text-xs text-amber-700 mt-0.5">
                Original Date: <span class="font-mono font-bold">{{ \Carbon\Carbon::parse($booking->created_at)->format('d M Y') }}</span>
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
                    <button @click="historyModal = true" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-clock-rotate-left"></i> History</button>
                </div>
                <div class="h-6 w-px bg-gray-200 mx-2 hidden lg:block"></div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('pdf.invoice', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-print"></i> Invoice</a>
                    <a href="{{ route('pdf.envelope', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-envelope-open-text"></i> Envelope</a>
                    <a href="{{ route('pdf.po', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E]"><i class="fa-solid fa-file-invoice"></i> PO/Quote</a>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <span class="text-[10px] font-extrabold text-gray-300 uppercase tracking-widest mr-1">Manage:</span>
                    <!-- UPDATED LINKS BELOW -->
                    <a href="/customers/{{ $booking->id }}" class="flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition"><i class="fa-regular fa-eye"></i> View Customer</a>
                    <span class="text-gray-200 hidden sm:inline">|</span>
                    <a href="/bookings/{{ $booking->id }}/edit" class="flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition"><i class="fa-solid fa-pen-to-square"></i> Edit Booking</a>
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
                        <button wire:click="moveDate" class="bg-white text-gray-600 border border-gray-200 text-[10px] uppercase font-bold px-3 py-1.5 rounded shadow-sm hover:bg-gray-100 transition ml-1">Move</button>
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
                        <button wire:click="updateStatus" class="bg-white text-[#9D686E] border border-gray-200 text-[10px] uppercase font-bold px-3 py-1.5 rounded shadow-sm hover:bg-gray-50 transition">Update</button>
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
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Payment Type</span>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-bold text-gray-700">{{ $booking->payment_type ?: 'Not Set' }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Payment Schedule</span>
                        <div class="space-y-1">
                            @forelse($payments as $index => $pay)
                            <div class="text-[10px] text-gray-600 flex justify-between items-center group">
                                <div>
                                    <span class="font-bold text-[#9D686E]">Payment {{ $index + 1 }}:</span>
                                    {{ \Carbon\Carbon::parse($pay->payment_date)->format('d/m/Y') }} -
                                    <span class="font-bold">${{ number_format($pay->amount, 2) }}</span>
                                </div>
                                <button @click="selectedPayment = {{ json_encode($pay) }}; paymentDetailsModal = true" class="text-[9px] text-blue-500 hover:underline">Details</button>
                            </div>
                            @empty
                            <span class="text-xs text-gray-400 italic">No payments recorded.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Totals -->
            <div class="bg-[#9D686E]/5 rounded-xl p-4 space-y-2 border border-[#9D686E]/10 flex flex-col justify-center">
                <div class="flex justify-between items-center text-xs">
                    <span class="font-medium text-gray-500">Rides / Duration Cost:</span>
                    <span class="font-medium text-gray-700">${{ number_format($ridesCost, 2) }}</span>
                </div>
                @if($deliveryCost > 0)
                <div class="flex justify-between items-center text-xs">
                    <span class="font-medium text-gray-500">Delivery Cost:</span>
                    <span class="font-medium text-gray-700">${{ number_format($deliveryCost, 2) }}</span>
                </div>
                @endif
                @if($calculatedExtrasTotal > 0)
                <div class="flex justify-between items-center text-xs">
                    <span class="font-medium text-gray-500">Extra Logistics Cost:</span>
                    <span class="font-medium text-gray-700">${{ number_format($calculatedExtrasTotal, 2) }}</span>
                </div>
                @endif

                <div class="flex justify-between items-center mt-2 pt-2 border-t border-[#9D686E]/10">
                    <span class="text-xs font-bold text-[#9D686E] uppercase">Total Amount</span>
                    <span class="text-lg font-extrabold text-[#9D686E]">${{ number_format($totalAmount, 2) }}</span>
                </div>

                <div class="flex justify-between items-center text-xs mt-1 pt-1 border-t border-[#9D686E]/10">
                    <span class="font-bold text-gray-700 text-sm">Balance Due:</span>
                    <span class="font-extrabold text-sm {{ $balanceDue > 0 ? 'text-red-500' : 'text-green-500' }}">${{ number_format($balanceDue, 2) }}</span>
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
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Duration</span><span class="text-[0.75rem] font-bold text-gray-800 w-3/5 text-left">{{ $booking->duration ?: '-' }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Pax</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->expected_people }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Booked By</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->booked_by ?? 'System' }}</span></div>
                </div>

                @if($calculatedExtrasTotal > 0)
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <span class="text-[0.7rem] font-bold text-[#9D686E] w-full uppercase tracking-wide">Extras & Logistics</span>
                    <div class="w-full text-xs text-gray-600 mt-2 space-y-1 bg-gray-50 p-3 rounded-lg">
                        @php
                        $gen = json_decode($booking->general_extra ?? '[]', true) ?? [];
                        $spec = json_decode($booking->specific_extra ?? '[]', true) ?? [];
                        $mergedExtras = array_merge($gen, $spec);
                        @endphp

                        @forelse($mergedExtras as $name => $cost)
                        <div class="flex justify-between border-b border-dotted border-gray-200 pb-1">
                            <span>&bull; {{ $name }}</span>
                            <span class="font-bold text-gray-700">${{ number_format($cost, 2) }}</span>
                        </div>
                        @empty
                        <div class="italic text-gray-400">Logistics Surface: {{ $booking->logistics_surfaces ?? 'None' }}</div>
                        @endforelse
                    </div>
                </div>
                @endif
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
                <div class="space-y-2">
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Name</span><span class="text-[0.75rem] font-bold text-slate-800 w-3/5 text-left">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Company</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->customer_organization ?: '-' }}</span></div>
                    <div class="flex justify-between items-baseline mb-1 pb-1 border-b border-dotted border-gray-200"><span class="text-[0.7rem] font-bold text-slate-500 w-2/5 uppercase tracking-wide">Mobile</span><span class="text-[0.75rem] font-medium text-slate-800 w-3/5 text-left">{{ $booking->customer_phone }}</span></div>
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
                                <th class="p-2">Type</th>
                                <th class="p-2 rounded-r-lg text-center">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs divide-y divide-gray-50">
                            @forelse ($items as $s)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-2 font-medium text-gray-700">{{ $s->item_name }}</td>
                                <td class="p-2 text-gray-500">{{ $s->is_custom ? 'Custom' : 'Standard' }}</td>
                                <td class="p-2 text-center font-bold text-[#9D686E]">{{ $s->total_qty }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="p-3 text-center italic text-gray-400">No specific items listed.</td>
                            </tr>
                            @endforelse
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
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="deleteModal = false"></div>
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
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="draftModal = false"></div>
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

    <!-- PAYMENT MODAL (RESTORED WITH ALL DROPDOWNS) -->
    <div x-show="paymentModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="paymentModal = false"></div>
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
                            <option value="Card Holder">Card Holder</option>
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
                    <div x-show="payMethod === 'Card Holder'" x-transition class="space-y-2 bg-gray-50/50 p-3 rounded-lg border border-dashed border-gray-200">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-1">Card Number (Last 4)</label>
                            <input type="text" wire:model="cardNum" class="w-full p-2 bg-white rounded border border-gray-100 text-xs text-gray-600 outline-none focus:border-[#9D686E]" placeholder="**** **** **** 1234">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1">Category</label>
                                <select wire:model="cardCategory" class="w-full p-2 bg-white rounded border border-gray-100 text-xs text-gray-600 outline-none focus:border-[#9D686E] cursor-pointer">
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="Credit Card">Credit Card</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1">Network</label>
                                <select wire:model="cardNetwork" class="w-full p-2 bg-white rounded border border-gray-100 text-xs text-gray-600 outline-none focus:border-[#9D686E] cursor-pointer">
                                    <option value="Visa">Visa</option>
                                    <option value="MasterCard">MasterCard</option>
                                    <option value="Amex">Amex</option>
                                    <option value="Bartercard">Bartercard</option>
                                    <option value="Bankcard">Bankcard</option>
                                </select>
                            </div>
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
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="emailModal = false"></div>
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
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="historyModal = false"></div>
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
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="calendarModal = false"></div>
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
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="paymentDetailsModal = false"></div>
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
                            <div class="flex justify-between mb-2"><span class="font-bold text-gray-500">Method:</span> <span class="font-medium" x-text="selectedPayment.payment_method"></span></div>
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

    <!-- Modal Listeners (Listens to Livewire dispatches) -->
    <div
        x-on:close-modal.window="paymentModal = false; emailModal = false; deleteModal = false; calendarModal = false; paymentDetailsModal = false; draftModal = false;"
        x-on:open-modal.window="
            let modalToOpen = typeof $event.detail === 'string' ? $event.detail : $event.detail[0];
            if (modalToOpen === 'paymentModal') paymentModal = true;
            if (modalToOpen === 'emailModal') emailModal = true;
            if (modalToOpen === 'calendarModal') calendarModal = true;
            if (modalToOpen === 'draftModal') draftModal = true;
        "></div>
</div>