<div x-data="{ 
        calendarModal: false,
        saveConfirmModal: false,
        showCustomDelivery: @entangle('form.delivery_area').live === 'custom' || (@entangle('form.delivery_area').live !== '' && !@js($deliveryOptions->pluck('zone_name')->contains($form['delivery_area'] ?? ''))),
        showCustomDuration: @entangle('form.duration').live === 'custom'
    }"
    class="min-h-screen flex flex-col bg-[#F8FAFC] text-[#1E293B] font-[Poppins]">
    <!-- Top Navigation -->
    <nav class="fixed top-0 left-0 w-full h-16 bg-white z-50 shadow-sm border-b border-gray-100 px-6 flex items-center justify-between">
        <div class="flex items-center gap-3"><img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="BigFun" class="h-8"></div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider hidden sm:block">Invoice #{{ $booking->invoice_number ?? $booking->id }}</span>
            <a href="{{ route('booking.overview', $booking->id) }}" class="flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition text-xs uppercase tracking-wide">
                <span class="material-symbols-rounded text-lg">arrow_back</span> <span class="hidden sm:inline">Cancel</span>
            </a>
        </div>
    </nav>

    <!-- Content Container: Reduced pt-24 to pt-20 to fix dead space above title -->
    <!-- Increased max-w-6xl to max-w-[1400px] for better edge-to-edge usage -->
    <main class="w-full max-w-[1400px] mx-auto px-4 lg:px-10 pb-20 transition-all duration-300">

        <!-- Header -->
        <header class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Edit Booking</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">Modifying booking ID #{{ $booking->id }} • {{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</p>
            </div>
            <button @click="saveConfirmModal = true" type="button" class="flex-1 sm:flex-none bg-[#9D686E] hover:bg-[#855359] shadow-lg shadow-[#9D686E]/30 text-white px-8 py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 transition transform active:scale-95 whitespace-nowrap">
                <span class="material-symbols-rounded text-lg">save</span> Save All Changes
            </button>
        </header>

        <div class="space-y-6">
            <!-- Financial Overview (Dark Box) -->
            <div class="bg-[#0F172A] text-white rounded-3xl p-6 md:p-10 shadow-xl relative overflow-hidden border border-slate-800">
                <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-[#9D686E]/10 rounded-full blur-[80px] -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-8 border-b border-slate-700 pb-4">
                        <span class="material-symbols-rounded text-[#9D686E] text-2xl">account_balance_wallet</span>
                        <h2 class="text-lg font-bold text-white uppercase tracking-wide">Financial Overview</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <!-- Left Side: Payment Config -->
                        <div class="space-y-6">
                            <div>
                                <label class="text-[10px] text-slate-400 uppercase font-bold mb-1.5 ml-1 block">Payment Type</label>
                                <div class="relative">
                                    <select wire:model.live="form.payment_type" class="w-full pt-5 pb-2 px-4 bg-[#1e293b] border border-slate-700 rounded-xl text-sm font-medium text-white appearance-none outline-none focus:border-[#9D686E] focus:ring-1 focus:ring-[#9D686E]">
                                        <option value="EFT">EFT / Bank Transfer</option>
                                        <option value="Card Holder">Card Holder</option>
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></div>
                                </div>
                            </div>

                            @if(($form['payment_type'] ?? '') === 'EFT')
                            <div class="bg-slate-800/80 rounded-2xl p-5 border border-slate-700 shadow-inner animate-[fadeIn_0.2s_ease-in]">
                                <h3 class="text-xs font-bold text-[#9D686E] uppercase mb-4 flex justify-between items-center">
                                    <span>EFT Details</span>
                                    <span class="material-symbols-rounded text-slate-500 text-sm">account_balance</span>
                                </h3>
                                <div>
                                    <label class="text-[10px] text-slate-500 uppercase font-bold mb-1 block">Specific Method</label>
                                    <select wire:model="form.eft_method" class="w-full py-2 px-3 bg-[#1e293b] border border-slate-700 rounded-xl text-sm text-white outline-none">
                                        <option value="Direct Deposit">Direct Deposit</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Osko">Osko</option>
                                        <option value="PayID">PayID</option>
                                    </select>
                                </div>
                            </div>
                            @endif

                            @if(in_array(($form['payment_type'] ?? ''), ['Card Holder', 'credit_card']))
                            <div class="bg-slate-800/80 rounded-2xl p-6 border border-slate-700 shadow-inner animate-[fadeIn_0.2s_ease-in]">
                                <h3 class="text-xs font-bold text-[#9D686E] uppercase mb-5 flex justify-between items-center">
                                    <span>Card Holder Details</span>
                                    <span class="material-symbols-rounded text-slate-500 text-sm">lock</span>
                                </h3>

                                <div class="grid grid-cols-2 gap-4 mb-5">
                                    <div>
                                        <label class="text-[10px] text-slate-500 uppercase font-bold mb-1 block">Network</label>
                                        <select wire:model="form.card_network" class="w-full py-2 px-3 bg-[#1e293b] border border-slate-700 rounded-xl text-xs md:text-sm text-white outline-none">
                                            <option value="Visa">Visa</option>
                                            <option value="Mastercard">Mastercard</option>
                                            <option value="AMEX">American Express</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-slate-500 uppercase font-bold mb-1 block">Category</label>
                                        <select wire:model="form.card_category" class="w-full py-2 px-3 bg-[#1e293b] border border-slate-700 rounded-xl text-xs md:text-sm text-white outline-none">
                                            <option value="Credit">Credit</option>
                                            <option value="Debit">Debit</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="relative">
                                    <input type="text" wire:model="form.card_number" placeholder="**** **** **** {{ $form['card_last4'] ?? '1234' }}" class="w-full pt-5 pb-2 px-4 bg-[#1e293b] border border-slate-700 rounded-xl font-mono tracking-widest text-lg text-white outline-none focus:border-[#9D686E]">
                                    <label class="absolute top-2 left-4 text-[0.65rem] font-bold text-[#9D686E]">Card Number</label>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Right Side: Calculations -->
                        <div class="space-y-4">
                            <div class="bg-[#1E293B] p-8 rounded-2xl border border-slate-700 space-y-4">
                                <div class="flex justify-between items-center border-b border-slate-700 pb-4">
                                    <span class="text-slate-300 font-medium text-sm">Subtotal (Pre-Tax/Fee)</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-slate-500 text-sm">$</span>
                                        <input type="number" wire:model.live.debounce.500ms="subtotal" step="0.01" class="w-28 bg-transparent text-right text-xl font-semibold text-white focus:outline-none">
                                    </div>
                                </div>

                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-400">Processing Fee ({{ in_array(($form['payment_type'] ?? ''), ['Card Holder', 'credit_card']) ? '2.9%' : '0%' }})</span>
                                    <span class="font-medium text-slate-300">${{ number_format($surchargeAmount, 2) }}</span>
                                </div>

                                <div class="bg-slate-950/50 rounded-xl p-5 mt-6 border border-slate-800 flex justify-between items-end">
                                    <div>
                                        <span class="block text-[#9D686E] text-[10px] font-bold uppercase mb-1">Grand Total</span>
                                        <input type="number" wire:model.live.debounce.500ms="totalAmount" placeholder="Override" class="bg-transparent border border-transparent hover:border-slate-700 rounded px-1 text-[10px] text-slate-500 focus:border-[#9D686E] outline-none w-20">
                                    </div>
                                    <span class="block text-4xl font-extrabold tracking-tighter text-white">${{ number_format($totalAmount, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between px-2 pt-2">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold">Recommended Deposit (50%)</span>
                                    <span class="text-white font-bold text-lg">${{ number_format($depositRequired, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Details -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-200">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <span class="material-symbols-rounded text-[#9D686E] text-2xl">event</span>
                    <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Event Details</h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Event Date</label>
                            <div class="flex gap-2">
                                <input type="date" wire:model.live="form.event_date" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:border-[#9D686E]">
                                <button wire:click="loadCalendar(); $dispatch('open-modal', 'calendarModal')" type="button" class="bg-[#9D686E] text-white px-4 rounded-xl flex items-center justify-center hover:bg-[#855359] transition">
                                    <span class="material-symbols-rounded">calendar_month</span>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Start Time</label><input type="time" wire:model="form.start_time" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">End Time</label><input type="time" wire:model="form.end_time" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Operational Hours</label>
                            <input type="text" wire:model="form.operational_hours" placeholder="e.g. 9am to 5pm" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]">
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Expected People (Pax)</label><input type="number" wire:model="form.expected_people" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Delivery Zone</label>
                            <select wire:model.live="form.delivery_area" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]">
                                <option value="">-- Select Zone --</option>
                                @foreach($deliveryOptions as $del)
                                <option value="{{ $del->zone_name }}">{{ $del->zone_name }} (+${{ number_format($del->price, 2) }})</option>
                                @endforeach
                                <option value="custom">Custom / Manual Quote</option>
                            </select>

                            <div x-show="showCustomDelivery" x-transition class="mt-3">
                                <label class="block text-xs font-bold text-[#9D686E] uppercase mb-1">Manual Delivery Cost</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                    <input type="number" wire:model.live="form.delivery_cost" step="0.01" class="w-full p-3 pl-8 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Lead Operator</label><input type="text" wire:model="form.lead_operator" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Lead Deliverer</label><input type="text" wire:model="form.lead_deliverer" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-3">Duration Selection</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($durationOptions as $dur)
                        <label class="border {{ ($form['duration'] ?? '') === $dur->label ? 'border-[#9D686E] bg-pink-50' : 'border-slate-200 hover:bg-slate-50' }} rounded-xl p-3 cursor-pointer transition flex items-center gap-2">
                            <input type="radio" wire:model.live="form.duration" value="{{ $dur->label }}" class="w-4 h-4 text-[#9D686E] focus:ring-[#9D686E]">
                            <span class="text-sm font-bold text-slate-600 flex-1">{{ $dur->label }}</span>
                            <span class="text-xs font-bold text-[#9D686E]">${{ number_format($dur->price, 2) }}</span>
                        </label>
                        @endforeach

                        <label class="border {{ ($form['duration'] ?? '') === 'custom' ? 'border-[#9D686E] bg-pink-50' : 'border-slate-200 hover:bg-slate-50' }} rounded-xl p-3 cursor-pointer transition flex items-center gap-2">
                            <input type="radio" wire:model.live="form.duration" value="custom" class="w-4 h-4 text-[#9D686E] focus:ring-[#9D686E]">
                            <span class="text-sm font-bold text-slate-600 flex-1">Custom / Manual</span>
                        </label>
                    </div>

                    @if(($form['duration'] ?? '') === 'custom')
                    <div class="mt-4 p-5 bg-slate-50 rounded-2xl border border-slate-200 animate-[fadeIn_0.2s_ease-in]">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Custom Duration Label</label>
                                <input type="text" wire:model.live="form.custom_duration_text" placeholder="e.g. 2 Days, Full Weekend" class="w-full p-3 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-[#9D686E] uppercase mb-1.5">Manual Duration Cost</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                    <input type="number" wire:model.live="form.duration_cost" step="0.01" class="w-full p-3 pl-8 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Rides & Attractions -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-200">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <span class="material-symbols-rounded text-[#9D686E] text-2xl">attractions</span>
                    <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Rides & Attractions</h2>
                </div>

                <div class="space-y-10">
                    @php $catIndex = 0; @endphp
                    @foreach($categories as $catName => $catData)
                    @if(empty($catData['products'])) @continue @endif
                    @php $catIndex++; @endphp

                    <div>
                        <div class="flex items-center gap-3 border-b border-gray-100 pb-3 mb-4">
                            <span class="w-6 h-6 rounded bg-[#9D686E]/10 text-[#9D686E] flex items-center justify-center font-bold text-[10px]">{{ $catIndex }}</span>
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest">{{ $catName }}</h3>
                            @if(($catData['limit'] ?? 0) > 0)
                            <span class="text-[9px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded border border-amber-200 font-bold uppercase tracking-wide">
                                Limit: {{ $catData['limit'] }}
                            </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                            @foreach($catData['products'] as $p)
                            @php
                            $cleanName = strtolower(trim($p->name));
                            $isSelected = isset($selectedItems[$cleanName]);
                            $qty = $isSelected ? $selectedItems[$cleanName] : 1;
                            $availInfo = $availability[$cleanName] ?? ['left' => 99, 'sold_out' => false];

                            $cardClass = $isSelected ? 'border-[#9D686E] bg-[#FFF5F7] ring-2 ring-[#9D686E]/20' : 'border-slate-200 hover:border-slate-300';
                            if (!$isSelected && $availInfo['sold_out']) $cardClass = 'opacity-60 bg-slate-50 border-slate-200';
                            @endphp

                            <div class="bg-white rounded-2xl p-4 border {{ $cardClass }} flex flex-col justify-between min-h-[130px] transition cursor-pointer" wire:click="toggleItem('{{ $p->name }}')">
                                <div class="flex justify-between items-start gap-2 mb-2 w-full">
                                    <div class="pr-2 w-full">
                                        <h4 class="font-bold text-slate-800 text-sm leading-snug line-clamp-2 min-h-[40px]">{{ $p->name }}</h4>
                                        <div class="mt-2">
                                            @if($availInfo['sold_out'] && !$isSelected)
                                            <span class="text-[0.65rem] font-bold text-red-700 bg-red-100 px-2 py-0.5 rounded-full border border-red-200 uppercase tracking-wider">Sold Out</span>
                                            @elseif($isSelected)
                                            <span class="text-[0.65rem] font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full border border-green-200 uppercase tracking-wider">Booked</span>
                                            @else
                                            <span class="text-[0.65rem] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200 uppercase tracking-wider">{{ $availInfo['left'] }} Available</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition {{ $isSelected ? 'bg-[#9D686E] border-[#9D686E]' : 'border-slate-300 bg-white' }}">
                                        @if($isSelected) <span class="text-white text-xs font-bold">✓</span> @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-between mt-auto pt-2" @click.stop>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ $isSelected ? 'Selected' : 'Click to select' }}</span>

                                    @if($isSelected)
                                    <div class="flex items-center bg-white border border-[#9D686E] rounded-lg overflow-hidden">
                                        <button wire:click.stop="updateItemQty('{{ $p->name }}', -1)" class="w-7 h-7 flex items-center justify-center bg-[#FFF5F7] text-[#9D686E] font-bold hover:bg-[#9D686E] hover:text-white transition">-</button>
                                        <input type="text" readonly value="{{ $qty }}" class="w-8 text-center border-none text-xs font-bold text-slate-700 bg-transparent pointer-events-none">
                                        <button wire:click.stop="updateItemQty('{{ $p->name }}', 1)" class="w-7 h-7 flex items-center justify-center bg-[#FFF5F7] text-[#9D686E] font-bold hover:bg-[#9D686E] hover:text-white transition">+</button>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Extras & Logistics Section -->
            @php
            $hasAnyExtras = false;
            foreach($activeCategories as $cat) {
            if(($addons[$cat] ?? collect([]))->isNotEmpty() || isset($dropdowns[$cat]) || ($questions[$cat] ?? collect([]))->isNotEmpty()) {
            $hasAnyExtras = true; break;
            }
            }
            @endphp

            @if($hasAnyExtras)
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-200 animate-[fadeIn_0.3s_ease-out]">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <span class="material-symbols-rounded text-[#9D686E] text-2xl">format_list_bulleted_add</span>
                    <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Extras & Logistics</h2>
                </div>

                <div class="space-y-8">
                    @foreach(['General Logistics', ...array_diff($activeCategories, ['General Logistics'])] as $catName)
                    @php
                    $catAddons = $addons[$catName] ?? collect([]);
                    $catQuestions = $questions[$catName] ?? collect([]);
                    $catDropdowns = $dropdowns[$catName] ?? [];
                    @endphp

                    @if($catAddons->isNotEmpty() || count($catDropdowns) > 0 || $catQuestions->isNotEmpty())
                    <div class="bg-slate-50 rounded-3xl border border-gray-200 p-6 md:p-8">
                        <div class="flex items-center gap-2 mb-6 border-b border-gray-200 pb-3">
                            <span class="material-symbols-rounded text-[#9D686E] text-sm">add_circle</span>
                            <h3 class="font-bold text-slate-800 text-sm tracking-wide">{{ $catName }} Extras</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Questions --}}
                            @foreach($catQuestions as $q)
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $q->question_text }}</label>
                                <select wire:model.live="dynamicExtras.q_{{ $q->id }}" class="w-full p-3 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E] cursor-pointer shadow-sm">
                                    <option value="">-- Select --</option>
                                    <option value="{{ $q->yes_price }}|yes">{{ $q->yes_label }} (+${{ number_format($q->yes_price, 2) }})</option>
                                    <option value="{{ $q->no_price }}|no">{{ $q->no_label }} (+${{ number_format($q->no_price, 2) }})</option>
                                </select>
                            </div>
                            @endforeach

                            {{-- Dropdowns --}}
                            @foreach($catDropdowns as $dd)
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $dd->label }}</label>
                                <select wire:model.live="dynamicExtras.dd_{{ $dd->id }}" class="w-full p-3 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E] cursor-pointer shadow-sm">
                                    <option value="">-- Select --</option>
                                    @foreach($dd->options as $opt)
                                    <option value="{{ $opt->id }}">{{ $opt->option_label }} (+${{ number_format($opt->option_price, 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @endforeach
                        </div>

                        {{-- Addons --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                            @foreach($catAddons as $addon)
                            <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-100 cursor-pointer bg-white transition shadow-sm">
                                <input type="checkbox" wire:model.live="dynamicExtras.add_{{ $addon->id }}" class="w-4 h-4 text-[#9D686E] rounded focus:ring-[#9D686E]">
                                <span class="text-sm font-bold text-slate-700 flex-1">{{ $addon->addon_label }}</span>
                                <span class="text-xs font-bold text-[#9D686E]">+${{ number_format($addon->addon_price, 2) }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Customer Details -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-200">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <span class="material-symbols-rounded text-[#9D686E] text-2xl">person</span>
                    <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Customer Details</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-6">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50 pb-2">Personal Info</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">First Name</label><input type="text" wire:model="form.customer_first_name" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Last Name</label><input type="text" wire:model="form.customer_last_name" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Primary Email</label><input type="email" wire:model="form.customer_email" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Primary Phone</label><input type="tel" wire:model="form.customer_phone" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                    </div>

                    <div class="space-y-6">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50 pb-2">Business Info</h3>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Employer Name / Point of Contact</label>
                            <input type="text" wire:model="form.employer_name" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]">
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Company Name</label><input type="text" wire:model="form.customer_organization" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">ABN</label><input type="text" wire:model="form.customer_abn" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Business Phone</label><input type="tel" wire:model="form.customer_business_phone" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Business Address</label><input type="text" wire:model="form.business_address" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E]"></div>
                    </div>
                </div>
            </div>

            <!-- Notes & Attachments -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-200">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <span class="material-symbols-rounded text-[#9D686E] text-2xl">description</span>
                    <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Final Details</h2>
                </div>

                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Customer Specific Notes</label><textarea wire:model="form.notes_customer" rows="4" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E] resize-none"></textarea></div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Delivery & Logistics Notes</label><textarea wire:model="form.notes_delivery" rows="4" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-[#9D686E] resize-none"></textarea></div>
                    </div>

                    <div class="pt-6 border-t border-gray-100">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-4">Attachments (Uploaded Documents/Images)</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach([
                            1 => 'delivery_attachment',
                            2 => 'delivery_attachment_1',
                            3 => 'delivery_attachment_2',
                            4 => 'delivery_attachment_3',
                            5 => 'delivery_attachment_4'
                            ] as $i => $field)
                            @php
                            $hasFile = !empty($form[$field]) && !in_array($field, $deletedAttachments);
                            @endphp
                            <div class="flex items-center gap-3 bg-slate-50 p-3 rounded-xl border {{ $hasFile ? 'border-slate-200' : 'border-dashed border-slate-300' }}">
                                @if($hasFile)
                                <a href="/uploads/{{ $form[$field] }}" target="_blank" class="text-xs font-bold text-[#9D686E] hover:underline flex items-center gap-1.5 flex-1 truncate">
                                    <span class="material-symbols-rounded text-sm">visibility</span> {{ $form[$field] }}
                                </a>
                                <button type="button" wire:click="markAttachmentDeleted('{{ $field }}')" class="text-red-400 hover:text-red-600 transition p-1 hover:bg-red-50 rounded-lg">
                                    <span class="material-symbols-rounded text-xl">delete</span>
                                </button>
                                @else
                                <div class="flex flex-col w-full">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase mb-1">Slot {{ $i }}</span>
                                    <input type="file" wire:model="newAttachments.{{ $field }}" class="text-[10px] w-full text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-[#9D686E]/10 file:text-[#9D686E] hover:file:bg-[#9D686E]/20 cursor-pointer">
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- SAVE CONFIRM MODAL -->
    <div x-show="saveConfirmModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="saveConfirmModal = false"></div>
            <div x-show="saveConfirmModal" x-transition class="relative bg-white rounded-3xl shadow-2xl p-8 w-full max-w-md z-10 text-center">
                <div class="w-16 h-16 bg-[#9D686E]/10 rounded-full flex items-center justify-center mx-auto mb-5 text-[#9D686E]">
                    <span class="material-symbols-rounded text-4xl">save</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Save All Changes?</h3>
                <p class="text-sm text-gray-500 mb-8 leading-relaxed">Are you sure you want to finalize and save all modifications made to this booking? This action will update the invoice and calendar records.</p>
                <div class="flex justify-center gap-3">
                    <button @click="saveConfirmModal = false" class="flex-1 px-5 py-3 rounded-2xl text-gray-600 hover:bg-gray-100 text-sm font-bold transition">Cancel</button>
                    <button wire:click="saveBooking" @click="saveConfirmModal = false" class="flex-1 px-5 py-3 rounded-2xl bg-[#9D686E] text-white hover:bg-[#855359] text-sm font-bold shadow-lg shadow-[#9D686E]/30 transition transform active:scale-95">Yes, Save Now</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CALENDAR MODAL -->
    <div x-show="calendarModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="calendarModal = false"></div>
            <div x-show="calendarModal" x-transition class="relative bg-white rounded-3xl shadow-2xl p-8 w-full max-w-lg z-10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="font-bold text-gray-800 text-xl">Check Date Availability</h3>
                    <button @click="calendarModal = false" class="text-gray-400 hover:text-gray-600 transition p-1 hover:bg-slate-50 rounded-lg"><span class="material-symbols-rounded">close</span></button>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Limit: 7 / Day</p>
                    <div class="flex items-center gap-4">
                        <button wire:click="calPrev" class="w-8 h-8 flex items-center justify-center bg-slate-100 rounded-xl text-slate-500 hover:bg-slate-200 transition"><span class="material-symbols-rounded text-sm">chevron_left</span></button>
                        <p class="text-md font-bold text-slate-800 w-32 text-center">{{ \Carbon\Carbon::create($calYear, $calMonth, 1)->format('F Y') }}</p>
                        <button wire:click="calNext" class="w-8 h-8 flex items-center justify-center bg-slate-100 rounded-xl text-slate-500 hover:bg-slate-200 transition"><span class="material-symbols-rounded text-sm">chevron_right</span></button>
                    </div>
                </div>

                <div class="grid grid-cols-7 text-[10px] font-bold text-slate-400 mb-3 uppercase tracking-widest text-center">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>

                <div class="grid grid-cols-7 gap-2.5">
                    @foreach($calDays as $d)
                    @if($d === null)
                    <div></div>
                    @else
                    @php
                    $bg = 'bg-emerald-50'; $text = 'text-emerald-700'; $border = 'border-emerald-200';
                    if ($d['left'] == 0) { $bg = 'bg-red-50'; $text = 'text-red-700'; $border = 'border-red-200'; }
                    elseif ($d['left'] <= 2) { $bg='bg-amber-50' ; $text='text-amber-700' ; $border='border-amber-200' ; }
                        $isSelected=$d['date']===$tempSelectedDate;
                        $ring=$isSelected ? 'border-[#9D686E] bg-pink-50 ring-2 ring-[#9D686E] shadow-md z-10' : '' ;
                        @endphp
                        <button wire:click="$set('tempSelectedDate', '{{ $d['date'] }}')" class="h-14 rounded-2xl border {{ $bg }} {{ $border }} {{ $text }} {{ $ring }} flex flex-col items-center justify-center cursor-pointer transition hover:-translate-y-0.5 shadow-sm">
                        <span class="font-bold text-sm">{{ $d['day'] }}</span>
                        <span class="text-[8px] uppercase font-bold tracking-tight">{{ $d['left'] }} Left</span>
                        </button>
                        @endif
                        @endforeach
                </div>

                <div class="flex justify-end pt-6 border-t border-gray-100 mt-8">
                    <button wire:click="applySelectedDate" class="px-8 py-3 rounded-2xl bg-[#9D686E] text-white font-bold shadow-lg shadow-[#9D686E]/20 hover:bg-[#855359] transition transform active:scale-95">Apply Selected Date</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Event Listeners -->
    <div x-on:close-modal.window="calendarModal = false; saveConfirmModal = false;" x-on:open-modal.window="if ($event.detail === 'calendarModal' || $event.detail[0] === 'calendarModal') calendarModal = true;"></div>
</div>