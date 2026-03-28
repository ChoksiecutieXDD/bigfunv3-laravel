<div x-data="{ 
        emailModal: false, 
        historyModal: false,
        selectedPayment: null,
        paymentDetailsModal: false
    }"
    class="max-w-[1440px] mx-auto space-y-6">

    <!-- Header & Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ $from_url ?: 'javascript:history.back()' }}" class="bg-white hover:bg-gray-50 text-slate-600 p-2.5 rounded-xl border border-gray-200 transition shadow-sm flex items-center justify-center">
                <span class="material-symbols-rounded text-2xl">arrow_back</span>
            </a>
            <div>
                <h1 class="text-3xl font-extrabold text-white">Booking #{{ $booking->id }}</h1>
                <p class="text-white/90 font-medium mt-1 uppercase tracking-wide text-[10px]">Read-Only View • <span class="font-black underline">{{ $booking->status }}</span></p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <span class="{{ $statusColor }} px-6 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider border shadow-md bg-white">
                {{ $booking->status }}
            </span>
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
                <span class="mx-2 opacity-50">→</span>
                Current Date: <span class="font-mono font-bold">{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</span>
            </p>
        </div>
    </div>
    @endif

    <!-- Removed Reference Tools Toolbar as requested -->

    <!-- Financial Details -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 relative group">
        <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-100 text-[#9D686E]">
            <div class="flex items-center gap-2"><span class="material-symbols-rounded">payments</span><span class="text-sm font-bold uppercase tracking-wide">Financial Briefing</span></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Payment Method</span>
                <p class="text-lg font-black text-slate-800 capitalize">{{ $booking->payment_type ?: 'Not Defined' }}</p>
            </div>
            <div class="bg-[#9D686E]/5 p-4 rounded-xl border border-[#9D686E]/10">
                <span class="text-[10px] font-black text-[#9D686E] uppercase tracking-widest block mb-1">Grand Total</span>
                <p class="text-3xl font-black text-[#9D686E] tracking-tighter leading-none">${{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Outstanding Balance</span>
                <p class="text-3xl font-black {{ $balanceDue > 0 ? 'text-red-500' : 'text-green-500' }} tracking-tighter leading-none">${{ number_format($balanceDue, 2) }}</p>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-50 flex justify-between items-center">
            <div class="flex items-center gap-2 text-xs">
                <span class="text-gray-400 font-bold uppercase text-[9px] tracking-widest">Contract Terms:</span>
                <span class="px-3 py-1 rounded text-[9px] font-black {{ $booking->terms_agreed ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200' }} border uppercase">
                    @if($booking->terms_agreed) AGREED @else PENDING @endif
                </span>
            </div>
            @if($balanceDue <= 0)
                <span class="text-[9px] font-black text-green-600 bg-green-50 px-4 py-1 rounded-full border border-green-200 uppercase tracking-widest">Verified Fully Paid</span>
            @endif
        </div>
    </div>

    <!-- Bento Details Grid (Refined) -->
    <div class="grid grid-cols-1 md:grid-cols-6 lg:grid-cols-12 gap-6 items-stretch">

        <!-- Work Order Details (Bento Focal) -->
        <div class="lg:col-span-8 flex flex-col">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">event_note</span><span class="text-sm font-bold uppercase tracking-wide">Work Order Details</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Event Type</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->event_type ?: 'no note' }}</span></div>
                    <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Lead Contact</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                    <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Service Date</span><span class="text-[0.8rem] font-bold text-slate-800">{{ \Carbon\Carbon::parse($booking->event_date)->format('l, d M Y') }}</span></div>
                    <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Operations Window</span><span class="text-[0.8rem] font-black text-[#9D686E]">{{ $timeString }}</span></div>
                    <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Shift Duration</span><span class="text-[0.8rem] font-bold text-gray-800">{{ $booking->duration ?: 'no note' }}</span></div>
                    <div class="flex justify-between mb-1 pb-1"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Expected Pax</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->expected_people }}</span></div>
                </div>

                @if($calculatedExtrasTotal > 0)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <span class="text-[0.7rem] font-bold text-[#9D686E] uppercase tracking-[0.2em] block mb-3">Service Add-ons</span>
                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100 space-y-2">
                        @php
                        $gen = json_decode($booking->general_extra ?? '[]', true) ?? [];
                        $spec = json_decode($booking->specific_extra ?? '[]', true) ?? [];
                        $mergedExtras = array_merge($gen, $spec);
                        @endphp
                        @foreach($mergedExtras as $name => $cost)
                        <div class="flex justify-between text-[11px] border-b border-white border-dashed pb-1">
                            <span class="font-bold text-gray-600 tracking-tight">&bull; {{ $name }}</span>
                            <span class="font-black text-gray-900">${{ number_format($cost, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Client Profile (2-1 Alignment) -->
        <div class="lg:col-span-4 flex flex-col">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                        <span class="material-symbols-rounded">person_pin</span><span class="text-sm font-bold uppercase tracking-wide">Client Profile</span>
                    </div>
                    <!-- Profile Content -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-baseline border-b border-dotted border-gray-200 pb-1"><span class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest">Client Name</span><span class="text-[0.8rem] font-black text-slate-800">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                        <div class="flex justify-between items-baseline border-b border-dotted border-gray-200 pb-1"><span class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest">Organization</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->customer_organization ?: 'no note' }}</span></div>
                        
                        <div class="grid grid-cols-2 gap-4 border-b border-dotted border-gray-200 pb-1">
                            <div class="flex flex-col"><span class="text-[0.65rem] font-bold text-slate-400 uppercase">Primary Mobile</span><span class="text-[0.8rem] font-black text-slate-800">{{ $booking->customer_phone }}</span></div>
                            <div class="flex flex-col"><span class="text-[0.65rem] font-bold text-slate-400 uppercase">Corp Landline</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->customer_business_phone ?: '-' }}</span></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex justify-between items-center group mt-4">
                    <span class="text-xs font-bold text-blue-600 truncate mr-3">{{ $booking->customer_email }}</span>
                </div>
            </div>
        </div>

        <!-- Operational Notes (Full Width Single Row) -->
        <div class="col-span-full">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">chat_bubble</span><span class="text-sm font-bold uppercase tracking-wide">Operational Notes</span>
                </div>
                <!-- side-by-side notes to avoid overhaul -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-yellow-50/50 p-5 rounded-2xl border border-yellow-100 h-full">
                        <span class="text-[10px] font-black text-yellow-700 uppercase tracking-widest block mb-2 px-1">Customer Briefing</span>
                        <div class="bg-white/50 p-4 rounded-xl text-[12px] text-gray-700 italic leading-relaxed shadow-sm min-h-[60px]">
                            {{ $booking->notes_customer ?: 'no special notes provided' }}
                        </div>
                    </div>
                    <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-100 h-full">
                        <span class="text-[10px] font-black text-blue-700 uppercase tracking-widest block mb-2 px-1">Logistics Instructions</span>
                        <div class="bg-white/50 p-4 rounded-xl text-[12px] text-gray-700 italic leading-relaxed shadow-sm min-h-[60px]">
                            {{ $booking->note_delivery ?? $booking->notes_delivery ?: 'no logistics instructions recorded' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Assets (aligned) -->
        <div class="md:col-span-3 lg:col-span-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">attractions</span><span class="text-sm font-bold uppercase tracking-wide">Service Assets</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[150px]">
                        <thead class="bg-gray-50/50 text-[9px] text-gray-400 font-black uppercase tracking-widest border-b border-gray-100">
                            <tr>
                                <th class="p-4 rounded-tl-xl text-[10px]">Identified Item</th>
                                <th class="p-4 rounded-tr-xl text-center text-[10px]">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs divide-y divide-gray-100">
                            @forelse ($items as $s)
                            <tr class="hover:bg-gray-50/50 transition border-b border-dotted border-gray-50 last:border-0">
                                <td class="p-4 font-black text-gray-800 tracking-tight leading-tight">{{ $s->item_name }}</td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 bg-gray-100 rounded-lg font-black text-gray-900 border border-gray-200 text-[10px]">{{ $s->total_qty }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="p-10 text-center italic text-gray-300 font-bold uppercase tracking-widest">no assets catalogued</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Deployment Team (aligned) -->
        <div class="md:col-span-3 lg:col-span-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full flex flex-col">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">engineering</span><span class="text-sm font-bold uppercase tracking-wide">Deployment Team</span>
                </div>
                <div class="grid grid-cols-2 gap-4 flex-1 items-center">
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex flex-col items-center text-center group">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3">Project Lead</span>
                        <div class="w-10 h-10 rounded-2xl bg-[#9D686E] text-white flex items-center justify-center text-xs font-black shadow-lg shadow-[#9D686E]/20 capitalize group-hover:scale-110 transition-transform">{{ substr($booking->lead_operator ?? '?', 0, 1) }}</div>
                        <p class="text-xs font-black text-gray-800 mt-3 tracking-tighter uppercase line-clamp-1">{{ $booking->lead_operator ?? 'unassigned' }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex flex-col items-center text-center group">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3">Logistics Lead</span>
                        <div class="w-10 h-10 rounded-2xl bg-blue-500 text-white flex items-center justify-center text-xs font-black shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-transform"><span class="material-symbols-rounded text-lg">local_shipping</span></div>
                        <p class="text-xs font-black text-gray-800 mt-3 tracking-tighter uppercase line-clamp-1">{{ $booking->lead_deliverer ?? 'unassigned' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Work Site Location (aligned) -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full flex flex-col justify-center">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">location_on</span><span class="text-sm font-bold uppercase tracking-wide">Work Site Location</span>
                </div>
                <div class="flex gap-5 items-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-[#9D686E] border border-gray-100 shrink-0"><span class="material-symbols-rounded text-2xl">map</span></div>
                    <div>
                        <p class="font-black text-base text-gray-800 leading-tight">{{ $booking->address_line_1 }}</p>
                        <p class="text-[11px] font-bold text-gray-400 mt-1 uppercase tracking-widest">{{ $booking->suburb }}, {{ $booking->state }} {{ $booking->postcode }}</p>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($booking->address_line_1 . ' ' . $booking->suburb) }}" target="_blank" class="inline-flex items-center gap-1.5 text-[10px] font-black text-blue-500 hover:text-blue-700 mt-3 uppercase tracking-widest group">GO TO MAP <span class="material-symbols-rounded text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photographic Assets (Footer Full Width) -->
        <div class="col-span-full">
            <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">panorama_horizontal</span><span class="text-sm font-bold uppercase tracking-wide">Photographic Assets</span>
                </div>
                @if(empty($galleryFiles))
                <div class="text-center py-10 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200/50">
                    <span class="material-symbols-rounded text-gray-200 text-5xl block mb-3">file_upload_off</span>
                    <span class="text-[9px] text-gray-300 font-black uppercase tracking-[0.2em]">Vault is empty</span>
                </div>
                @else
                <div class="grid grid-cols-1 gap-3">
                    @foreach($galleryFiles as $file)
                    @php
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    $icon = $isImage ? 'image' : 'description';
                    @endphp
                    <a href="/uploads/{{ $file }}" target="_blank" class="flex items-center gap-4 p-4 rounded-2xl border border-gray-100 bg-gray-50/30 hover:bg-white hover:shadow-xl transition-all duration-300 group">
                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-lg text-[#9D686E] group-hover:bg-[#9D686E] group-hover:text-white transition-all transform group-hover:rotate-12">
                            <span class="material-symbols-rounded text-xl">{{ $icon }}</span>
                        </div>
                        <div class="flex-grow min-w-0">
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Internal Reference</span>
                            <span class="text-xs font-bold text-blue-600 underline truncate block">{{ $file }}</span>
                        </div>
                        <span class="material-symbols-rounded text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">open_in_new</span>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>


    <!-- ================== MODALS ================== -->

    <!-- Email Modal remains for transmission -->
    <div x-show="emailModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity" @click="emailModal = false"></div>
            <div x-show="emailModal" x-transition class="relative bg-white rounded-3xl shadow-3xl w-full max-w-2xl z-10 flex flex-col overflow-hidden border border-white/20">
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div class="flex items-center gap-3 text-[#9D686E]"><span class="material-symbols-rounded text-2xl">mail_lock</span>
                        <h3 class="font-black text-xl tracking-tight">Secure Email Relay</h3>
                    </div>
                    <button @click="emailModal = false" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 hover:text-red-500 shadow-sm transition-all"><span class="material-symbols-rounded">close</span></button>
                </div>
                <div class="p-8">
                    <form wire:submit="sendEmail" class="space-y-4">
                        <div class="flex flex-col gap-1.5"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">To Recipient</label><input type="text" wire:model="emailTo" class="w-full text-xs p-3 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white outline-none focus:border-[#9D686E] font-bold"></div>
                        <div class="flex flex-col gap-1.5"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Subject Header</label><input type="text" wire:model="emailSubject" class="w-full text-xs p-3 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white outline-none focus:border-[#9D686E] font-black text-slate-800"></div>
                        <div class="flex flex-col gap-1.5"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Message Body</label><textarea wire:model="emailBody" rows="8" class="w-full text-xs p-4 border border-slate-200 rounded-2xl bg-slate-50 focus:bg-white outline-none focus:border-[#9D686E] font-mono leading-relaxed resize-none"></textarea></div>

                        <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                            <button type="button" @click="emailModal = false" class="px-6 py-3 rounded-xl border border-gray-200 text-gray-500 text-[10px] font-black uppercase tracking-widest hover:bg-gray-50 transition">Cancel</button>
                            <button type="submit" class="px-10 py-3 rounded-xl bg-[#9D686E] text-white text-[10px] font-black tracking-widest shadow-xl shadow-[#9D686E]/20 hover:bg-[#855359] transition flex items-center gap-2 group">
                                <span wire:loading.remove wire:target="sendEmail">TRANSMIT REPORT</span>
                                <span wire:loading wire:target="sendEmail">SENDING...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Listeners -->
    <div
        x-on:close-modal.window="emailModal = false;"
        x-on:open-modal.window="
            let modalToOpen = typeof $event.detail === 'string' ? $event.detail : $event.detail[0];
            if (modalToOpen === 'emailModal') emailModal = true;
        "></div>
</div>