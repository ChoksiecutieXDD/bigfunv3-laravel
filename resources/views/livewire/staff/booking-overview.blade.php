<div x-data="{ 
        historyModal: false
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
                <p class="text-white/90 font-medium mt-1 uppercase tracking-wide text-[10px]">Staff View &bull; <span class="font-black underline">{{ $booking->status }}</span></p>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Created On</span>
                <span class="font-semibold text-gray-700 text-sm">{{ \Carbon\Carbon::parse($booking->created_at)->format('d M Y, h:i A') }}</span>
            </div>
            <div>
                <span class="block font-bold text-amber-600 uppercase text-[10px]">Event Date</span>
                <span class="font-bold text-gray-800 text-sm italic">{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</span>
            </div>
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Current Status</span>
                <span class="font-bold {{ $booking->status == 'Confirmed' ? 'text-green-600' : 'text-gray-700' }}">{{ strtoupper($booking->status) }}</span>
            </div>
            <div>
                <span class="block font-bold text-gray-400 uppercase text-[10px]">Reference No</span>
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
                <span class="mx-2 opacity-50">&#8594;</span>
                Current Date: <span class="font-mono font-bold">{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</span>
            </p>
        </div>
    </div>
    @endif

    <!-- Document Generation Toolbar (Limited for Staff) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100 text-[#9D686E]">
            <span class="material-symbols-rounded text-xl">print</span>
            <span class="text-sm font-bold uppercase tracking-wide">Resources</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pdf.delivery_receipt', $booking->id) }}" target="_blank" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-[11px] font-bold tracking-tight transition whitespace-nowrap bg-[#9D686E] border border-[#9D686E] text-white shadow-sm hover:bg-white hover:text-[#9D686E] no-underline">
                <i class="fa-solid fa-truck-ramp-box"></i> DELIVERY RECEIPT
            </a>
        </div>
    </div>

    <!-- Bento Details Grid -->
    <div class="space-y-6">

        <!-- Row 1: Work Order Details + Items -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">

            <!-- Work Order Details -->
            <div class="lg:col-span-12">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                        <span class="material-symbols-rounded">event_note</span><span class="text-sm font-bold uppercase tracking-wide">Work Order Details</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Event Type</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->event_type ?: 'no note' }}</span></div>
                            <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Lead Contact</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                            <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Service Date</span><span class="text-[0.8rem] font-bold text-slate-800">{{ \Carbon\Carbon::parse($booking->event_date)->format('l, d M Y') }}</span></div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Operations Window</span><span class="text-[0.8rem] font-black text-[#9D686E]">{{ $timeString }}</span></div>
                            <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Shift Duration</span><span class="text-[0.8rem] font-bold text-gray-800">{{ $booking->duration ?: 'no note' }}</span></div>
                            <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Operational Hour</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->operational_hours ?: '-' }}</span></div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between mb-1 pb-1 border-b border-dotted border-gray-100"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Delivery</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->delivery_area ?: 'Not Set' }}</span></div>
                            <div class="flex justify-between mb-1 pb-1"><span class="text-[0.7rem] font-bold text-slate-400 uppercase">Expected Pax</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->expected_people }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Client Profile + Service Assets -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">

            <!-- Client Profile -->
            <div class="lg:col-span-4">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                            <span class="material-symbols-rounded">person_pin</span><span class="text-sm font-bold uppercase tracking-wide">Client Profile</span>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-baseline border-b border-dotted border-gray-200 pb-2"><span class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest">Client Name</span><span class="text-[0.8rem] font-black text-slate-800">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span></div>
                            <div class="flex justify-between items-baseline border-b border-dotted border-gray-200 pb-2"><span class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest">Organization</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->customer_organization ?: '-' }}</span></div>
                            <div class="flex justify-between items-baseline border-b border-dotted border-gray-200 pb-2"><span class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest">Employer Name</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->employer_name ?: '-' }}</span></div>
                            <div class="grid grid-cols-2 gap-4 border-b border-dotted border-gray-200 pb-2">
                                <div class="flex flex-col"><span class="text-[0.65rem] font-bold text-slate-400 uppercase">Primary Mobile</span><span class="text-[0.8rem] font-black text-slate-800">{{ $booking->customer_phone }}</span></div>
                                <div class="flex flex-col"><span class="text-[0.65rem] font-bold text-slate-400 uppercase">Corp Landline</span><span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->customer_business_phone ?: '-' }}</span></div>
                            </div>
                            <div class="flex flex-col border-b border-dotted border-gray-200 pb-2">
                                <span class="text-[0.65rem] font-bold text-slate-400 uppercase">Business Address</span>
                                <span class="text-[0.8rem] font-bold text-slate-800">{{ $booking->business_address ?: 'Not provided' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100 flex items-center gap-3 mt-4">
                        <span class="material-symbols-rounded text-gray-400 text-sm shrink-0">mail</span>
                        <span class="text-xs font-bold text-blue-600 truncate">{{ $booking->customer_email }}</span>
                    </div>
                </div>
            </div>

            <!-- Service Assets -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                        <span class="material-symbols-rounded">attractions</span><span class="text-sm font-bold uppercase tracking-wide">Service Assets</span>
                    </div>
                    <!-- Header Row -->
                    <div class="grid grid-cols-12 gap-2 px-3 pb-2 mb-1">
                        <div class="col-span-7 text-[9px] font-black text-gray-400 uppercase tracking-widest">Identified Item</div>
                        <div class="col-span-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Specification</div>
                        <div class="col-span-1 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Qty</div>
                    </div>
                    <div class="space-y-2">
                        @forelse ($items as $s)
                        <div class="grid grid-cols-12 gap-2 items-start bg-gray-50/50 rounded-xl px-3 py-3 border border-gray-100 hover:bg-white hover:shadow-sm transition-all duration-200">
                            <div class="col-span-7 flex flex-col">
                                <span class="text-[0.7rem] font-black text-[#9D686E] uppercase tracking-tight leading-tight">{{ $s->item_name }}</span>
                                @if($s->is_custom)<span class="text-[8px] font-bold text-amber-500 uppercase mt-0.5">Custom</span>@endif
                            </div>
                            <div class="col-span-4">
                                @if($s->specification)
                                <div class="text-[9px] text-gray-500 space-y-0.5 leading-relaxed">
                                    @foreach(explode("\n", str_replace(["\r\n", "\r"], "\n", $s->specification)) as $line)
                                    @if(trim($line))<div class="flex items-start gap-1"><span class="mt-1.5 w-1 h-1 rounded-full bg-[#9D686E]/50 shrink-0"></span><span>{{ trim($line) }}</span></div>@endif
                                    @endforeach
                                </div>
                                @else
                                <span class="text-[9px] text-gray-300 italic">No specs</span>
                                @endif
                            </div>
                            <div class="col-span-1 flex justify-center">
                                <span class="px-2 py-0.5 bg-white rounded-lg font-black text-gray-700 border border-gray-200 text-[10px] shadow-sm">{{ $s->total_qty }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-gray-300 text-xs font-bold uppercase tracking-widest italic">No assets catalogued</div>
                        @endforelse

                        {{-- Consolidated Extras --}}
                        @foreach($activeCategories as $cat)
                        @php
                        $catAddons = $config['addons'][$cat] ?? [];
                        $catQuestions = $config['questions'][$cat] ?? [];
                        $catDropdowns = $config['dropdowns'][$cat] ?? [];
                        @endphp

                        @if($cat === 'General Logistics' && $booking->logistics_surfaces)
                        <div class="grid grid-cols-12 gap-2 items-center bg-slate-50/50 rounded-xl px-3 py-2.5 border border-slate-100 hover:bg-white hover:shadow-sm transition-all duration-200">
                            <div class="col-span-7 flex items-center gap-2">
                                <span class="material-symbols-rounded text-xs text-[#9D686E]">local_shipping</span>
                                <span class="text-[0.7rem] font-bold text-slate-600 uppercase tracking-tight">Surface: {{ $booking->logistics_surfaces }}</span>
                            </div>
                            <div class="col-span-4 text-[9px] italic text-slate-400">Logistics Configuration</div>
                            <div class="col-span-1 flex justify-center"><span class="px-2 py-0.5 bg-white rounded-lg font-bold text-slate-400 border border-gray-100 text-[10px]">1</span></div>
                        </div>
                        @endif

                        @foreach($catAddons as $addon)
                        @php $isSelected = isset($selectedExtras['add_'.$addon['id']]); @endphp
                        @if($isSelected)
                        <div class="grid grid-cols-12 gap-2 items-center bg-slate-50/50 rounded-xl px-3 py-2.5 border border-slate-100 hover:bg-white hover:shadow-sm transition-all duration-200">
                            <div class="col-span-7 flex items-center gap-2">
                                <span class="material-symbols-rounded text-xs text-[#9D686E]">add_circle</span>
                                <span class="text-[0.7rem] font-bold text-slate-600 uppercase tracking-tight">{{ $addon['addon_label'] }}</span>
                            </div>
                            <div class="col-span-4 text-[9px] italic text-slate-400">Extra / Configuration</div>
                            <div class="col-span-1 flex justify-center"><span class="px-2 py-0.5 bg-white rounded-lg font-bold text-slate-400 border border-gray-100 text-[10px]">1</span></div>
                        </div>
                        @endif
                        @endforeach

                        @foreach($catQuestions as $q)
                        @php
                        $val = $selectedExtras['extra_'.$q['id']] ?? $selectedExtras['q_'.$q['id']] ?? null;
                        $isYes = false;
                        if ($val) { $parts = explode('|', $val); $answer = $parts[1] ?? 'yes'; $isYes = ($answer === 'yes'); }
                        @endphp
                        @if($isYes)
                        <div class="grid grid-cols-12 gap-2 items-center bg-slate-50/50 rounded-xl px-3 py-2.5 border border-slate-100 hover:bg-white hover:shadow-sm transition-all duration-200">
                            <div class="col-span-7 flex items-center gap-2">
                                <span class="material-symbols-rounded text-xs text-[#9D686E]">help_center</span>
                                <span class="text-[0.7rem] font-bold text-slate-600 uppercase tracking-tight">{{ $q['question_text'] }}</span>
                            </div>
                            <div class="col-span-4 text-[9px] italic text-slate-400">Extra / Configuration</div>
                            <div class="col-span-1 flex justify-center"><span class="px-2 py-0.5 bg-white rounded-lg font-bold text-slate-400 border border-gray-100 text-[10px]">1</span></div>
                        </div>
                        @endif
                        @endforeach

                        @foreach($catDropdowns as $dd)
                        @php
                        $val = $selectedExtras['dd_'.$dd['id']] ?? null;
                        $selectedOpt = null;
                        if ($val) {
                        foreach($dd['options'] as $opt) {
                        if($opt['id'] == $val) { $selectedOpt = $opt; break; }
                        }
                        }
                        @endphp
                        @if($selectedOpt)
                        <div class="grid grid-cols-12 gap-2 items-center bg-slate-50/50 rounded-xl px-3 py-2.5 border border-slate-100 hover:bg-white hover:shadow-sm transition-all duration-200">
                            <div class="col-span-7 flex items-center gap-2">
                                <span class="material-symbols-rounded text-xs text-[#9D686E]">settings_input_component</span>
                                <span class="text-[0.7rem] font-bold text-slate-600 uppercase tracking-tight">{{ $dd['label'] }}: {{ $selectedOpt['option_label'] }}</span>
                            </div>
                            <div class="col-span-4 text-[9px] italic text-slate-400">Extra / Configuration</div>
                            <div class="col-span-1 flex justify-center"><span class="px-2 py-0.5 bg-white rounded-lg font-bold text-slate-400 border border-gray-100 text-[10px]">1</span></div>
                        </div>
                        @endif
                        @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Operational Notes -->
        <div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">chat_bubble</span><span class="text-sm font-bold uppercase tracking-wide">Operational Notes</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-yellow-50/50 p-5 rounded-2xl border border-yellow-100">
                        <span class="text-[10px] font-black text-yellow-700 uppercase tracking-widest block mb-2">Customer Briefing</span>
                        <div class="bg-white/50 p-4 rounded-xl text-[12px] text-gray-700 italic leading-relaxed shadow-sm min-h-[60px]">{{ $booking->notes_customer ?: 'no special notes provided' }}</div>
                    </div>
                    <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-100">
                        <span class="text-[10px] font-black text-blue-700 uppercase tracking-widest block mb-2">Logistics Instructions</span>
                        <div class="bg-white/50 p-4 rounded-xl text-[12px] text-gray-700 italic leading-relaxed shadow-sm min-h-[60px]">{{ $booking->note_delivery ?? $booking->notes_delivery ?: 'no logistics instructions recorded' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 4: Deployment Team + Work Site Location -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Deployment Team -->
            <div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full">
                    <div class="flex items-center gap-2 mb-5 pb-2 border-b border-gray-100 text-[#9D686E]">
                        <span class="material-symbols-rounded">engineering</span><span class="text-sm font-bold uppercase tracking-wide">Deployment Team</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 flex flex-col items-center text-center group">
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3">Project Lead</span>
                            <div class="w-12 h-12 rounded-2xl bg-[#9D686E] text-white flex items-center justify-center text-sm font-black shadow-lg shadow-[#9D686E]/20 capitalize group-hover:scale-110 transition-transform">{{ substr($booking->lead_operator ?? '?', 0, 1) }}</div>
                            <p class="text-xs font-black text-gray-800 mt-3 tracking-tighter uppercase line-clamp-2 leading-tight">{{ $booking->lead_operator ?? 'Unassigned' }}</p>
                        </div>
                        <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 flex flex-col items-center text-center group">
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3">Logistics Lead</span>
                            <div class="w-12 h-12 rounded-2xl bg-blue-500 text-white flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-transform"><span class="material-symbols-rounded text-lg">local_shipping</span></div>
                            <p class="text-xs font-black text-gray-800 mt-3 tracking-tighter uppercase line-clamp-2 leading-tight">{{ $booking->lead_deliverer ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Site Location -->
            <div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 h-full flex flex-col justify-center">
                    <div class="flex items-center gap-2 mb-5 pb-2 border-b border-gray-100 text-[#9D686E]">
                        <span class="material-symbols-rounded">location_on</span><span class="text-sm font-bold uppercase tracking-wide">Work Site Location</span>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-[#9D686E] border border-gray-100 shrink-0 shadow-sm">
                            <span class="material-symbols-rounded text-3xl">map</span>
                        </div>
                        <div>
                            <p class="font-black text-lg text-gray-800 leading-tight">{{ $booking->address_line_1 }}</p>
                            <p class="text-xs font-bold text-gray-400 mt-1 uppercase tracking-widest">{{ $booking->suburb }}, {{ $booking->state }} {{ $booking->postcode }}</p>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($booking->address_line_1 . ' ' . $booking->suburb) }}" target="_blank" class="inline-flex items-center gap-1.5 text-[10px] font-black text-blue-500 hover:text-blue-700 mt-3 uppercase tracking-widest group">
                                GO TO MAP <span class="material-symbols-rounded text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 5: Photographic Assets -->
        <div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100 text-[#9D686E]">
                    <span class="material-symbols-rounded">panorama_horizontal</span><span class="text-sm font-bold uppercase tracking-wide">Photographic Assets</span>
                </div>
                @if(empty($galleryFiles))
                <div class="text-center py-10 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200/50">
                    <span class="material-symbols-rounded text-gray-200 text-5xl block mb-3">file_upload_off</span>
                    <span class="text-[9px] text-gray-300 font-black uppercase tracking-[0.2em]">Vault is empty</span>
                </div>
                @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($galleryFiles as $file)
                    @php
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    $filePath = asset('storage/uploads/' . $file);
                    @endphp
                    <div class="group relative bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300">
                        @if($isImage)
                        <div class="aspect-square w-full">
                            <img src="{{ $filePath }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        @else
                        <div class="aspect-square w-full flex flex-col items-center justify-center bg-slate-100">
                            <span class="material-symbols-rounded text-3xl text-slate-400">description</span>
                            <span class="text-[10px] font-black uppercase text-slate-400 mt-1">{{ $ext }}</span>
                        </div>
                        @endif

                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <a href="{{ $filePath }}" target="_blank" class="w-10 h-10 rounded-full bg-white text-[#9D686E] flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                                <span class="material-symbols-rounded">visibility</span>
                            </a>
                            <a href="{{ $filePath }}" download class="w-10 h-10 rounded-full bg-white text-[#9D686E] flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                                <span class="material-symbols-rounded">download</span>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>
</div>