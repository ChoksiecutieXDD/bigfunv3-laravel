@vite(['resources/js/availability-sync.js', 'resources/js/edit-booking.js'])
<div x-data="bookingApp"
    x-init="
        window.dayjs = window.dayjs || function() { return { format: () => '' } };
        window.lwBookingComponent = @this;
        window.bookingAppData = {
            savedExtras: @entangle('saved_extras'),
            selectedItems: @js($selectedItemsClean),
            bookingId: @js($booking->id),
            categories: @js($categories),
            config: @js($config),
            extraPrices: @entangle('extraPrices'),
            activeOverrides: @entangle('activeOverrides'),
            manualPrices: @entangle('manualPrices'),
            lockedOverrides: @entangle('lockedOverrides'),
            csrfToken: '{{ csrf_token() }}'
        };

        // Removal Modal State
        modals.removeConfirm = false;
        itemToRemove = '';

        // Product Details State
        productDetails = {
            visible: false,
            name: '',
            spec: '',
            price: 0
        };
        pendingRemovalCheckbox = null;
        pendingRemovalCard = null;

        window.confirmRemoval = () => {
            if (pendingRemovalCheckbox && pendingRemovalCard) {
                const itemName = pendingRemovalCard.dataset.name;
                pendingRemovalCheckbox.checked = false;

                if (typeof processSelection === 'function') {
                    processSelection(pendingRemovalCheckbox, pendingRemovalCard);
                }
                
                if (window.lwBookingComponent) {
                    // Force the deep object sync into Livewire
                    window.lwBookingComponent.extraPrices = JSON.parse(JSON.stringify(window.bookingAppData.extraPrices || {}));
                    window.lwBookingComponent.activeOverrides = JSON.parse(JSON.stringify(window.bookingAppData.activeOverrides || {}));
                    window.lwBookingComponent.saved_extras = JSON.parse(JSON.stringify(window.bookingAppData.savedExtras || {}));
                    
                    window.lwBookingComponent.toggleItem(itemName, false);
                }
            }
            modals.removeConfirm = false;
        };

        $watch('modals.history', val => { if(val) loadPreviousCustomers(); });

        window.addEventListener('recalculate-requested', () => {
            if (typeof window.initBookingAppData === 'function') window.initBookingAppData();
            if (typeof window.triggerRecalculate === 'function') {
                window.triggerRecalculate();
            }
        });
    "
    @notify.window="addToast($event.detail.title, $event.detail.message, $event.detail.type || 'success')"
    @show-toast.window="addToast($event.detail.title, $event.detail.message, $event.detail.type || 'success')"
    @booking-updated.window="modals.saveConfirm = false"
    class="w-full relative pb-8">

    <div class="flex w-full relative overflow-hidden">
        <main class="flex-1 pt-4 pb-16 px-0 max-w-[1440px] mx-auto w-full">
            <form id="combinedBookingForm" onsubmit="return false;" class="form-layout-wrapper">
                <input type="hidden" name="booking_id" id="booking_id" value="{{ $booking->id }}">
                <input type="hidden" name="invoice_number" id="invoice_number" value="{{ $booking->invoice_number }}">
                <input type="hidden" id="duration_cost" value="{{ $durationCost }}">
                <input type="hidden" id="delivery_cost" value="{{ $deliveryCost }}">

                <div class="flex flex-col gap-6 mb-8">
                    <!-- Conflict Banner -->
                    @if(!empty($activeConflicts) || !empty($activeCapacityBreaches))
                    <div class="bg-red-50 border border-red-200 border-l-4 border-l-red-500 p-6 rounded-2xl shadow-sm">
                        <div class="flex items-start gap-4">
                            <span class="material-symbols-rounded text-red-500 text-3xl">error</span>
                            <div class="flex-1">
                                <h3 class="text-base font-bold text-red-800 mb-1 leading-none uppercase tracking-wide">Validation Blocked</h3>
                                <div class="text-sm text-red-700 space-y-1 mt-2">
                                    @foreach($activeConflicts as $conf)
                                    <p class="flex items-center gap-2 font-medium">• <span class="font-bold">{{ $conf }}</span> is already booked on this date.</p>
                                    @endforeach
                                    @foreach($activeCapacityBreaches as $cat => $data)
                                    <p class="flex items-center gap-2 font-medium">• <span class="font-bold">{{ $cat }}</span> capacity exceeded ({{ $data['current'] + $data['added'] }} / {{ $data['limit'] }}).</p>
                                    @endforeach
                                </div>
                                <p class="text-[11px] font-black text-red-600 mt-3 uppercase tracking-widest">Please change the date or remove items to enable saving</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div id="duplicateBanner" class="hidden bg-amber-50 border border-amber-200 border-l-4 border-l-amber-500 p-4 rounded-xl shadow-sm">
                        <div class="flex items-start gap-4">
                            <span class="material-symbols-rounded text-amber-500 text-3xl">warning</span>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-amber-800 mb-1">Potential Schedule Conflict Detected</h3>
                                <div id="duplicateBannerBody" class="text-xs text-amber-700 space-y-1 mb-2"></div>
                                <p class="text-xs font-bold text-amber-600">You may continue, but please double-check before saving.</p>
                            </div>
                        </div>
                    </div>
                    <header class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            @php
                            $backRoute = $isSupervisor ? 'supervisor.bookings.overview' : 'admin.bookings.overview';
                            $overviewUrl = route($backRoute, ['id' => $booking->id, 'back' => $backUrl]);
                            @endphp
                            <a href="{{ $overviewUrl }}" wire:navigate class="bg-white hover:bg-gray-50 text-slate-600 p-2.5 rounded-xl border border-gray-200 transition shadow-sm flex items-center justify-center">
                                <span class="material-symbols-rounded text-2xl">arrow_back</span>
                            </a>
                            <div>
                                <h1 class="text-3xl font-extrabold text-[#1E293B]">Edit Booking</h1>
                                <p class="text-sm text-slate-500 font-medium mt-1 uppercase tracking-wide text-[10px]">Invoice: <span class="font-bold text-[#9D686E]">{{ $booking->invoice_number ?? $booking->id }}</span></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            @php
                            $hasConflicts = !empty($activeConflicts) || !empty($activeCapacityBreaches);
                            $saveBtnClass = $hasConflicts
                            ? 'bg-red-100 text-red-400 cursor-not-allowed border-red-200 opacity-60'
                            : 'bg-[#9E6B73] text-white hover:bg-[#86545C] shadow-md shadow-[#9E6B73]/20';
                            @endphp
                            <button type="button" @click="modals.history = true; filteredCustomers = previousCustomers; searchHistory = ''" class="btn-action bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 flex-1 sm:flex-none justify-center">
                                <span class="material-symbols-rounded mr-2 text-lg">history</span> Past Customer
                            </button>
                            <button
                                @if(!$hasConflicts) @click="modals.saveConfirm = true" @endif
                                type="button"
                                class="btn-action flex-1 sm:flex-none justify-center transition-all duration-300 {{ $saveBtnClass }}"
                                @if($hasConflicts) disabled @endif>
                                <span class="material-symbols-rounded text-lg mr-2">{{ $hasConflicts ? 'block' : 'save' }}</span>
                                {{ $hasConflicts ? 'BLOCKED: FIX CONFLICTS' : 'SAVE CHANGES' }}
                            </button>
                        </div>
                    </header>
                </div>

                <div class="financial-panel">
                    <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-[#9E6B73]/20 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>

                    <div class="flex items-center justify-between border-b border-slate-700 pb-4 relative z-10">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-[#9E6B73] text-3xl">account_balance_wallet</span>
                            <h2 class="text-xl font-bold text-white uppercase tracking-wide">Financials & Payment</h2>
                        </div>
                        <div class="flex gap-4">
                            <div class="bg-slate-800/50 p-4 rounded-2xl border border-slate-700/50 shadow-inner flex flex-col items-end min-w-[200px]">
                                <p class="text-[10px] text-slate-400 uppercase font-black tracking-[0.15em] mb-1">Outstanding Balance</p>
                                <div class="flex items-center gap-2">
                                    <span id="icon_balance" class="material-symbols-rounded text-xl {{ $balanceDue > 0.01 ? 'text-rose-500' : 'text-emerald-500' }}">{{ $balanceDue > 0.01 ? 'pending' : 'check_circle' }}</span>
                                    <p class="text-4xl font-extrabold tracking-tighter {{ $balanceDue > 0.01 ? 'text-rose-400' : 'text-emerald-400' }}" id="disp_balance">${{ number_format($balanceDue, 2) }}</p>
                                </div>
                            </div>
                            <div class="bg-slate-800/30 p-4 rounded-2xl border border-slate-700/30 shadow-inner flex flex-col items-end min-w-[200px]">
                                <p class="text-[10px] text-slate-500 uppercase font-black tracking-[0.15em] mb-1">Total Amount</p>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-xl text-white/20">payments</span>
                                    <p class="text-4xl font-extrabold tracking-tighter text-white/50" id="disp_total">${{ number_format($totalAmount, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
                        <div class="space-y-4">
                            <h3 class="text-sm font-bold text-[#9E6B73] uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Cost Breakdown</h3>

                            <div class="flex justify-between items-center text-sm text-slate-300">
                                <span>Duration Cost</span>
                                <span class="font-bold" id="breakdown_dur">${{ number_format($durationCost, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-slate-300">
                                <span>Delivery Zone Cost</span>
                                <span class="font-bold" id="breakdown_del">${{ number_format($deliveryCost, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-slate-300">
                                <span>Attractions Cost</span>
                                <span class="font-bold" id="breakdown_attractions">${{ number_format($attractionsCost, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-slate-300">
                                <span>Extras Cost</span>
                                <span class="font-bold" id="breakdown_ext">${{ number_format($extrasCost, 2) }}</span>
                            </div>

                            <div class="h-px bg-slate-700 my-3"></div>

                            <div class="flex justify-between items-center text-sm font-bold text-white">
                                <span>Subtotal</span>
                                <div class="flex items-center gap-1 text-lg">
                                    $ <input type="number" id="calc_subtotal" wire:model.live.debounce.500ms="subtotal" readonly class="bg-transparent text-right w-24 outline-none border-none pointer-events-none text-white font-bold">
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-sm mt-2">
                                <span class="text-slate-400">Processing Fee ({{ in_array(($form['payment_type'] ?? ''), ['Card Holder', 'credit_card']) ? '2.9%' : '0%' }})</span>
                                <span class="font-medium text-slate-300">${{ number_format($surchargeAmount, 2) }}</span>
                            </div>

                            <div class="h-px bg-slate-700/50 my-4"></div>

                            <div class="flex justify-between items-center text-sm font-bold text-emerald-400/90">
                                <span class="flex items-center gap-2"><span class="material-symbols-rounded text-xs">payments</span> Total Paid (Track Record)</span>
                                <span class="font-bold" id="disp_total_paid">-${{ number_format($totalPaid, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-base font-black text-white mt-1.5 bg-slate-800/50 p-3 rounded-xl border border-slate-700">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-[#9E6B73] text-lg">account_balance</span>
                                    <span class="font-bold uppercase tracking-wider text-[11px]">Outstanding Balance</span>
                                </div>
                                <span class="text-xl font-black {{ $balanceDue > 0.01 ? 'text-rose-400' : 'text-emerald-400' }}" id="disp_balance_footer">${{ number_format($balanceDue, 2) }}</span>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <h3 class="text-sm font-bold text-[#9E6B73] uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Payment Configuration</h3>

                            <div class="flex flex-col gap-4">
                                <div class="input-group">
                                    <label class="input-label text-slate-400 !ml-1">Payment Type</label>
                                    <div class="relative">
                                        <select wire:model.live="form.payment_type" class="input-dark appearance-none cursor-pointer">
                                            <option value="EFT">EFT / Bank Transfer</option>
                                            <option value="Card Holder">Card Holder</option>
                                            <option value="Cash">Cash</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>

                                @if(($form['payment_type'] ?? '') === 'EFT')
                                <div class="input-group animate-[fadeIn_0.2s_ease-in]">
                                    <label class="input-label text-slate-400 !ml-1">Specific Method</label>
                                    <div class="relative">
                                        <select wire:model="form.eft_method" class="input-dark appearance-none cursor-pointer">
                                            <option value="Direct Deposit">Direct Deposit</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Osko">Osko</option>
                                            <option value="PayID">PayID</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>
                                @endif
                            </div>

                            @if(in_array(($form['payment_type'] ?? ''), ['Card Holder', 'credit_card']))
                            <div class="bg-slate-800/80 rounded-2xl p-5 border border-slate-700 mt-4 shadow-inner flex flex-col gap-4 animate-[fadeIn_0.2s_ease-in]">
                                <h4 class="text-xs font-bold text-[#9E6B73] uppercase flex justify-between items-center"><span>Card Details</span><span class="material-symbols-rounded text-sm">lock</span></h4>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="relative">
                                        <select wire:model="form.card_network" class="input-dark appearance-none !py-3 text-sm cursor-pointer">
                                            <option value="Visa">Visa</option>
                                            <option value="Mastercard">Mastercard</option>
                                            <option value="American Express">American Express</option>
                                            <option value="Discover">Discover</option>
                                            <option value="Bankcard">Bankcard</option>
                                            <option value="Bartercard">Bartercard</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded text-sm">expand_more</span></span>
                                    </div>
                                </div>

                                <div class="relative">
                                    <input type="text" wire:model="form.card_number" placeholder=" " maxlength="19"
                                        x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim()"
                                        class="input-dark font-mono text-lg tracking-widest">
                                    <label class="input-floating-label">Card Number</label>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="relative">
                                        <input type="text" wire:model="form.card_expiry" placeholder=" " maxlength="5"
                                            x-on:input="
                                                let v = $el.value.replace(/\D/g, '');
                                                if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2,4);
                                                $el.value = v;
                                            "
                                            class="input-dark text-center font-mono">
                                        <label class="input-floating-label">MM/YY</label>
                                    </div>
                                    <div class="relative">
                                        <input type="text" wire:model="form.card_cvv" placeholder=" " maxlength="4" class="input-dark text-center font-mono">
                                        <label class="input-floating-label">CVV</label>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="flex items-center justify-between bg-[#9E6B73]/20 rounded-xl p-4 border border-[#9E6B73]/30 mt-4">
                                <span class="text-slate-300 text-xs uppercase font-bold">Req. Deposit (50%)</span>
                                <span class="text-white font-bold text-xl">${{ number_format($depositRequired, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-6 px-8 pt-8">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-[#9E6B73] text-2xl">calendar_month</span>
                            <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Live Availability & Movement</h2>
                        </div>
                        <div class="flex items-center gap-3 bg-slate-50 p-2 rounded-[20px] border border-slate-100">
                            <button type="button" wire:click="calPrev" class="w-10 h-10 flex items-center justify-center bg-white rounded-2xl text-slate-400 hover:text-[#9D686E] shadow-sm border border-slate-100 transition-all hover:scale-105 active:scale-95">
                                <span class="material-symbols-rounded text-xl font-bold">chevron_left</span>
                            </button>
                            <p class="text-[13px] font-black text-slate-700 w-40 text-center uppercase tracking-widest">
                                {{ \Carbon\Carbon::create($calYear, $calMonth, 1)->format('F Y') }}
                            </p>
                            <button type="button" wire:click="calNext" class="w-10 h-10 flex items-center justify-center bg-white rounded-2xl text-slate-400 hover:text-[#9D686E] shadow-sm border border-slate-100 transition-all hover:scale-105 active:scale-95">
                                <span class="material-symbols-rounded text-xl font-bold">chevron_right</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 text-[10px] font-black text-slate-300 mb-4 uppercase tracking-[0.2em] text-center px-1">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-8">
                        @foreach($calDays as $d)
                        @if($d === null)
                        <div class="hidden lg:block h-24 rounded-2xl bg-slate-50/30"></div>
                        @else
                        @php
                        $bg = 'bg-emerald-50'; $text = 'text-emerald-700'; $border = 'border-emerald-100';
                        if ($d['left'] == 0) { $bg = 'bg-red-50'; $text = 'text-red-700'; $border = 'border-red-100'; }
                        elseif ($d['left'] <= 2) { $bg='bg-amber-50' ; $text='text-amber-700' ; $border='border-amber-100' ; }

                            $isSelected=$d['date']===$tempSelectedDate;
                            $isOriginal=$d['date']===($booking->event_date);

                            $ring = $isSelected ? 'border-[#9D686E] bg-pink-50 ring-4 ring-[#9D686E]/10 shadow-md z-10' : '';
                            $originStyle = $isOriginal && !$isSelected ? 'border-2 border-dashed border-[#9D686E] shadow-inner' : '';
                            @endphp
                            <button type="button" wire:click="selectDate('{{ $d['date'] }}')"
                                class="h-24 rounded-2xl border transition-all relative group {{ $bg }} {{ $border }} {{ $text }} {{ $ring }} {{ $originStyle }} hover:-translate-y-1 hover:shadow-lg hover:border-[#9D686E]">

                                @if($isOriginal)
                                <div class="absolute -top-1.5 -right-1.5 bg-[#9D686E] text-white text-[7px] px-2 py-0.5 rounded-full font-black uppercase tracking-tighter shadow-sm z-20">Current</div>
                                @endif

                                @if($d['conflict'] ?? false)
                                <div class="absolute top-2 right-2 flex items-center justify-center w-5 h-5 bg-red-500 text-white rounded-full shadow-sm animate-pulse z-20">
                                    <span class="material-symbols-rounded text-sm font-bold">warning</span>
                                </div>
                                @endif

                                @if($d['breach'] ?? false)
                                <div class="absolute top-2 left-2 flex items-center justify-center w-5 h-5 bg-amber-500 text-white rounded-full shadow-sm z-20">
                                    <span class="material-symbols-rounded text-sm font-bold">inventory_2</span>
                                </div>
                                @endif

                                @if($d['duplicate'] ?? false)
                                <div class="absolute bottom-2 right-2 flex items-center justify-center w-5 h-5 bg-amber-600 text-white rounded-full shadow-sm z-20">
                                    <span class="material-symbols-rounded text-sm font-bold">person_alert</span>
                                </div>
                                @endif

                                <span class="font-black text-2xl mb-1">{{ $d['day'] }}</span>
                                <span class="text-[9px] uppercase font-black tracking-tighter opacity-70 group-hover:opacity-100">{{ $d['left'] }} Left</span>
                            </button>
                            @endif
                            @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-6 text-[9px] text-slate-400 font-extrabold justify-center border-t border-slate-50 pt-8 uppercase tracking-[0.15em] mb-10">
                        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm"></span>AVAILABLE</span>
                        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-sm"></span>BUSY</span>
                        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-sm"></span>FULL</span>
                        <span class="inline-flex items-center gap-2 p-1.5 bg-red-50 text-red-600 rounded-lg border border-red-100"><span class="material-symbols-rounded text-xs">warning</span> CONFLICT</span>
                        <span class="inline-flex items-center gap-2 p-1.5 bg-amber-50 text-amber-600 rounded-lg border border-amber-100"><span class="material-symbols-rounded text-xs">inventory_2</span> AT CAPACITY</span>
                        <span class="inline-flex items-center gap-2 p-1.5 bg-amber-100/50 text-amber-700 rounded-lg border border-amber-200"><span class="material-symbols-rounded text-xs">person_alert</span> DUPLICATE</span>
                    </div>

                    @if($tempSelectedDate)
                    <div class="p-6 bg-slate-50 border border-slate-200 rounded-[28px] animate-[fadeIn_0.3s_ease-out]">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm border border-slate-100">
                                    <span class="material-symbols-rounded text-slate-400">calendar_today</span>
                                </div>
                                <div>
                                    <h4 class="text-[12px] font-black text-slate-700 uppercase tracking-widest">{{ \Carbon\Carbon::parse($tempSelectedDate)->format('d M Y') }}</h4>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase">Analysis Results</p>
                                </div>
                            </div>
                            @if(count($modalConflicts) > 0 || count($modalCapacityBreaches) > 0)
                            <span class="bg-red-50 text-red-600 text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest border border-red-100 flex items-center gap-2 shadow-sm">
                                <span class="material-symbols-rounded text-sm">block</span> Move Blocked
                            </span>
                            @else
                            <span class="bg-emerald-50 text-emerald-600 text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest border border-emerald-100 flex items-center gap-2 shadow-sm">
                                <span class="material-symbols-rounded text-sm">check_circle</span> Optimized Path
                            </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-3 tracking-widest">Booked on this day:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @php $dayItems = $dailyAttractions[$tempSelectedDate] ?? []; @endphp
                                        @forelse($dayItems as $itemName)
                                        @php $isConflict = in_array($itemName, $bookedAttractions); @endphp
                                        <span class="px-3 py-1.5 rounded-xl text-[10px] font-bold border transition-all {{ $isConflict ? 'bg-red-50 text-red-600 border-red-200 shadow-sm shadow-red-500/10' : 'bg-white text-slate-600 border-slate-200' }}">
                                            {{ strtoupper($itemName ?? '') }}
                                        </span>
                                        @empty
                                        <p class="text-[10px] font-bold text-slate-300 italic">No attractions reserved yet.</p>
                                        @endforelse
                                    </div>
                                </div>

                                @if(!empty($modalNameConflicts))
                                <div class="bg-[#9D686E]/5 border border-[#9D686E]/10 rounded-[24px] p-5">
                                    <p class="text-[10px] font-black text-[#9D686E] uppercase tracking-tight mb-4 flex items-center gap-2">
                                        <span class="material-symbols-rounded text-sm">person_alert</span>
                                        Duplicate Contact Found
                                    </p>
                                    <div class="space-y-2">
                                        @foreach($modalNameConflicts as $nc)
                                        <div class="flex justify-between items-center bg-white/80 p-3 rounded-2xl border border-[#9D686E]/10 shadow-sm">
                                            <div class="flex items-center gap-3">
                                                <span class="text-[10px] font-black text-[#9D686E]">#{{ $nc['invoice_number'] ?? $nc['id'] }}</span>
                                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">{{ $nc['item_names_summary'] ?? 'Customer Record' }}</span>
                                            </div>
                                            <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 uppercase">{{ $nc['status'] }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>

                            <div class="p-3 bg-white rounded-2xl border border-slate-100 flex items-center justify-center min-h-[50px]">
                                @if(empty($modalConflicts) && empty($modalCapacityBreaches))
                                <div class="flex items-center gap-2 text-emerald-600 font-bold px-4 py-2 bg-emerald-50 rounded-xl border border-emerald-100 w-full justify-center">
                                    <span class="material-symbols-rounded text-xl">check_circle</span>
                                    <span class="text-sm uppercase tracking-wider">Optimized Selection Path</span>
                                </div>
                                @else
                                <div class="flex items-center gap-2 text-red-600 font-bold px-4 py-2 bg-red-50 rounded-xl border border-red-100 w-full justify-center">
                                    <span class="material-symbols-rounded text-xl">block</span>
                                    <span class="text-sm uppercase tracking-wider">Analysis Blocked</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 pt-10 mt-10 border-t border-slate-100">
                        <div class="input-group">
                            <label class="input-label">Event Date</label>
                            <input type="date" id="event_date" name="event_date" wire:model.live="form.event_date" value="{{ $form['event_date'] }}" class="input-field" @change="dateChanged()">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Operational Hours</label>
                            <input type="text" wire:model="form.operational_hours" placeholder="e.g. 9am to 5pm or TBC" class="input-field">
                        </div>
                        <div class="input-group" x-show="$wire.form.duration !== 'custom'">
                            <label class="input-label">Start Time</label>
                            <input type="time" wire:model="form.start_time" class="input-field">
                        </div>
                        <div class="input-group" x-show="$wire.form.duration !== 'custom'">
                            <label class="input-label">End Time</label>
                            <input type="time" wire:model="form.end_time" class="input-field">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100 mt-6">
                        <label class="input-label mb-3">Duration Pricing</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            @foreach($durationOptions as $dur)
                            @php
                            $isSelected = (($form['duration'] ?? '') === $dur->label);
                            $activeClass = $isSelected ? 'duration-active border-[#9E6B73] bg-pink-50' : 'border-slate-200 hover:bg-slate-50';
                            @endphp
                            <label class="duration-card flex flex-col items-center justify-center p-3 border rounded-xl cursor-pointer transition text-center {{ $activeClass }}">
                                <input type="radio" wire:model.live="form.duration" value="{{ $dur->label }}" class="hidden">
                                <span class="font-bold text-slate-700 text-xs">{{ $dur->label }}</span>
                                <span class="text-[#9E6B73] text-sm font-extrabold mt-1">${{ number_format($dur->price, 2) }}</span>
                            </label>
                            @endforeach

                            <label class="duration-card flex flex-col items-center justify-center p-3 border {{ ($form['duration'] ?? '') === 'custom' ? 'border-[#9E6B73] bg-pink-50 duration-active' : 'border-slate-200 hover:bg-slate-50' }} rounded-xl cursor-pointer transition text-center">
                                <input type="radio" wire:model.live="form.duration" value="custom" class="hidden">
                                <span class="font-bold text-slate-700 text-xs uppercase tracking-wide">Custom</span>
                                <span class="text-[#9E6B73] text-[10px] font-extrabold mt-1">Manual Quote</span>
                            </label>
                        </div>

                        @if(($form['duration'] ?? '') === 'custom')
                        <div class="mt-4 p-5 bg-slate-50 rounded-2xl border border-slate-200 animate-[fadeIn_0.2s_ease-in] grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="input-group">
                                <label class="input-label">Custom Duration Label</label>
                                <input type="text" wire:model.live.debounce.500ms="form.custom_duration_text" placeholder="e.g. 2 Days, Full Weekend" class="input-field bg-white">
                            </div>
                            <div class="input-group">
                                <label class="input-label text-[#9E6B73]">Manual Duration Cost</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                    <input type="number" wire:model.live.debounce.500ms="form.duration_cost" oninput="if(window.triggerRecalculate) window.triggerRecalculate(true)" step="0.01" class="input-field bg-white pl-8" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="section-card">
                    <div class="flex items-center gap-3 border-b border-gray-100 pb-6">
                        <span class="material-symbols-rounded text-[#9E6B73] text-2xl">person_pin</span>
                        <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Customer & Venue</h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 pt-6">
                        <!-- Left Column: CONTACT INFO -->
                        <div class="space-y-6">
                            <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4">Contact Info</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="input-group">
                                    <label class="input-label">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" id="cust_first_name" wire:model="form.customer_first_name" class="input-field">
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Last Name</label>
                                    <input type="text" id="cust_last_name" wire:model="form.customer_last_name" class="input-field">
                                </div>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Business / Org Name</label>
                                <input type="text" id="customer_organization" wire:model="form.customer_organization" class="input-field">
                            </div>

                            <div class="input-group">
                                <label class="input-label">ABN Number</label>
                                <input type="text" id="customer_abn" wire:model="form.customer_abn" class="input-field" placeholder="Optional">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Employer Name</label>
                                <input type="text" id="employer_name" wire:model="form.employer_name" class="input-field">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Business Contact Number</label>
                                <input type="tel" id="customer_business_phone" wire:model="form.customer_business_phone" class="input-field">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Mobile Phone <span class="text-red-500">*</span></label>
                                <input type="tel" id="customer_phone_mobile" wire:model="form.customer_phone" class="input-field">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" id="customer_email_address" wire:model="form.customer_email" class="input-field">
                            </div>
                        </div>

                        <!-- Right Column: VENUE LOCATION -->
                        <div class="space-y-6">
                            <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4">Venue Location</h3>

                            <div class="input-group">
                                <label class="input-label">Event Address Line 1 <span class="text-red-500">*</span></label>
                                <input type="text" id="addr_line_1" wire:model="form.address_line_1" class="input-field" placeholder="Street Address">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Business Address (Optional)</label>
                                <input type="text" id="business_address" wire:model="form.business_address" class="input-field" placeholder="e.g. Suite 123">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="input-group">
                                    <label class="input-label">Suburb</label>
                                    <input type="text" id="addr_suburb" wire:model="form.suburb" class="input-field">
                                </div>
                                <div class="input-group">
                                    <label class="input-label">State</label>
                                    <div class="relative">
                                        <select id="addr_state" wire:model="form.state" class="input-field appearance-none cursor-pointer">
                                            <option value="QLD">QLD</option>
                                            <option value="NSW">NSW</option>
                                            <option value="VIC">VIC</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Postcode</label>
                                    <input type="text" id="addr_postcode" wire:model="form.postcode" class="input-field">
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="input-group">
                                        <label class="input-label">Event Type</label>
                                        <div class="relative">
                                            <select wire:model="form.event_type" class="input-field appearance-none cursor-pointer">
                                                <option value="Private">Private Party</option>
                                                <option value="Corporate">Corporate Event</option>
                                                <option value="Community">Community / School</option>
                                            </select>
                                            <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Expected People</label>
                                        <input type="number" wire:model="form.expected_people" placeholder="e.g. 50" class="input-field">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="input-group">
                                    <label class="input-label">Delivery Zone</label>
                                    <div class="relative">
                                        <select wire:model.live="form.delivery_area" class="input-field appearance-none cursor-pointer">
                                            <option value="">-- Select Zone --</option>
                                            @foreach($deliveryOptions as $del)
                                            <option value="{{ $del->zone_name }}">{{ $del->zone_name }} (+${{ number_format($del->price, 2) }})</option>
                                            @endforeach
                                            <option value="custom">Custom / Manual Quote</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>

                                <div x-show="$wire.form.is_custom_duration || $wire.form.delivery_area === 'custom' || ($wire.form.delivery_area !== '' && !@js($deliveryOptions->pluck('zone_name')->contains($form['delivery_area'] ?? '')))" x-collapse class="mt-4">
                                    <div class="input-group">
                                        <label class="input-label text-[#9E6B73]">Manual Delivery Cost</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                            <input type="number" wire:model.live.debounce.500ms="form.delivery_cost" oninput="if(window.triggerRecalculate) window.triggerRecalculate(true)" step="0.01" class="input-field input-with-icon" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-10 border-t border-gray-100 mt-6">
                        <div class="input-group">
                            <label class="input-label">Delivery Notes</label>
                            <textarea wire:model="form.notes_delivery" rows="2" class="input-field resize-none text-xs" placeholder="Access details..."></textarea>
                        </div>
                        <div class="input-group">
                            <label class="input-label">Customer Notes</label>
                            <textarea wire:model="form.notes_customer" rows="2" class="input-field resize-none text-xs" placeholder="Special requests..."></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-100">
                        <div class="input-group">
                            <label class="input-label">Lead Operator</label>
                            <input list="staff_list" wire:model="form.lead_operator" class="input-field" placeholder="Select or type staff name...">
                            <datalist id="staff_list">
                                @foreach($staffList as $staffName)
                                <option value="{{ $staffName }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="input-group">
                            <label class="input-label">Lead Deliverer</label>
                            <input list="staff_list" wire:model="form.lead_deliverer" class="input-field" placeholder="Select or type staff name...">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100 mt-6"
                        x-init="calculateTotalSize()"
                        @recalc-size="calculateTotalSize()">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div class="flex flex-col gap-1">
                                <label class="input-label mb-0 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#9E6B73]">attachment</span>
                                    <span>Delivery Attachments <span class="font-black text-slate-400">({{ $this->booking->id ? "Manage" : "Up to 5" }})</span></span>
                                </label>
                                <p class="text-[10px] text-slate-500 font-medium italic">Upload proof of delivery or site photos (JPG/PNG only)</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Detailed Storage Status Card -->
                                <div class="bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 flex items-center gap-4 shadow-sm transition-all duration-300"
                                    :class="{'bg-rose-50 border-rose-200 shadow-rose-100/50': parseFloat(totalSizeMB) >= 4.5, 'bg-amber-50 border-amber-200': parseFloat(totalSizeMB) >= 3.5 && parseFloat(totalSizeMB) < 4.5}">

                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="material-symbols-rounded text-[16px]" :class="parseFloat(totalSizeMB) >= 5 ? 'text-rose-500 animate-pulse' : 'text-slate-400'">database</span>
                                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Storage Usage</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                <div class="h-full transition-all duration-500 ease-out rounded-full"
                                                    :class="parseFloat(totalSizeMB) >= 5 ? 'bg-rose-500' : (parseFloat(totalSizeMB) >= 3.5 ? 'bg-amber-500' : 'bg-[#9E6B73]')"
                                                    :style="'width: ' + Math.min(100, (parseFloat(totalSizeMB) / 5) * 100) + '%'"></div>
                                            </div>
                                            <span class="text-[11px] font-black text-slate-700" x-text="totalSizeMB + 'MB / 5MB'"></span>
                                        </div>
                                    </div>

                                    <div class="h-8 w-px bg-slate-200 mx-1"></div>

                                    <div class="flex flex-col items-end">
                                        <span class="text-[9px] font-bold uppercase tracking-tighter text-slate-400">Remaining</span>
                                        <span class="text-[11px] font-black" :class="parseFloat(totalSizeMB) >= 5 ? 'text-rose-600' : 'text-emerald-600'"
                                            x-text="(Math.max(0, 5 - parseFloat(totalSizeMB))).toFixed(2) + ' MB'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            @foreach([
                            1 => 'delivery_attachment',
                            2 => 'delivery_attachment_2',
                            3 => 'delivery_attachment_3',
                            4 => 'delivery_attachment_4',
                            5 => 'delivery_attachment_5'
                            ] as $i => $dbCol)
                            @php
                            $existingFile = $form[$dbCol] ?? null;
                            $existingExt = $existingFile ? strtolower(pathinfo($existingFile, PATHINFO_EXTENSION)) : '';
                            $existingSize = 0;
                            if ($existingFile) {
                            $p1 = public_path('uploads/' . $existingFile);
                            $p2 = storage_path('app/public/uploads/' . $existingFile);
                            if (file_exists($p1)) $existingSize = filesize($p1);
                            elseif (file_exists($p2)) $existingSize = filesize($p2);
                            }
                            @endphp

                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col relative group transition-all hover:bg-white hover:shadow-md attachment-slot"
                                data-slot-name="{{ $dbCol }}"
                                data-existing-size="{{ $existingSize }}"
                                data-is-deleted="false"
                                x-data="{ 
                                        fileName: '{{ $existingFile }}', 
                                        fileExt: '{{ $existingExt }}',
                                        isImage: {{ in_array($existingExt, ['jpg', 'jpeg', 'png']) ? 'true' : 'false' }},
                                        previewUrl: '{{ $existingFile ? asset("storage/uploads/$existingFile") : "" }}',
                                        handleFile(el) {
                                            const file = el.files[0];
                                            if (!file) return;

                                            const ext = file.name.split('.').pop().toLowerCase();
                                            if (!['jpg', 'jpeg', 'png'].includes(ext)) {
                                                el.value = '';
                                                window.dispatchEvent(new CustomEvent('notify', { detail: { title: 'Invalid File Type', type: 'error', icon: 'error', message: 'Only JPG and PNG formats are allowed.' } }));
                                                return;
                                            }

                                            if (!checkTotalAttachmentSize(el)) {
                                                el.value = '';
                                                return;
                                            }

                                            this.fileName = file.name;
                                            this.fileExt = ext;
                                            this.isImage = true;
                                            
                                            // Reset deletion state if it was marked as deleted
                                            const slotEl = el.closest('.attachment-slot');
                                            if (slotEl) slotEl.dataset.isDeleted = 'false';

                                            if (this.previewUrl.startsWith('blob:')) {
                                                URL.revokeObjectURL(this.previewUrl);
                                            }
                                            
                                            this.previewUrl = URL.createObjectURL(file);
                                            this.$dispatch('recalc-size');
                                        },
                                        openFile() {
                                            if (!this.previewUrl) return;
                                            const a = document.createElement('a');
                                            a.href = this.previewUrl;
                                            a.target = '_blank';
                                            document.body.appendChild(a);
                                            a.click();
                                            document.body.removeChild(a);
                                        }
                                     }">

                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-[#9E6B73]/10 flex items-center justify-center text-[#9E6B73]">
                                            <span class="material-symbols-rounded text-lg" x-text="isImage ? 'image' : (fileName ? 'description' : 'upload_file')"></span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Slot {{ $i }}</span>
                                            <template x-if="fileExt">
                                                <span class="text-[9px] font-bold bg-[#9E6B73] text-white px-1.5 py-0.5 rounded-md uppercase w-fit" x-text="fileExt"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <template x-if="fileName">
                                        <button type="button"
                                            @click="$wire.removeAttachment('{{ $dbCol }}'); fileName = ''; fileExt = ''; previewUrl = ''; isImage = false; $el.closest('.group').querySelector('input[type=file]').value = ''; $el.closest('.attachment-slot').dataset.isDeleted = 'true'; if(!window.bookingAppData.deletedAttachments) window.bookingAppData.deletedAttachments = []; window.bookingAppData.deletedAttachments.push('{{ $dbCol }}'); $dispatch('recalc-size');"
                                            class="w-7 h-7 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm z-20">
                                            <span class="material-symbols-rounded text-sm">close</span>
                                        </button>
                                    </template>
                                </div>

                                <div class="relative flex-1">
                                    <input type="file" name="delivery_attachment{{ $i > 1 ? "_$i" : "" }}"
                                        wire:model="temp_attachment_{{ $i }}"
                                        accept="image/png, image/jpeg"
                                        @change="handleFile($el)"
                                        class="absolute inset-0 opacity-0 cursor-pointer z-10">

                                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-2 h-24 flex items-center justify-center bg-white overflow-hidden group-hover:border-[#9E6B73]/30 transition-colors">
                                        <template x-if="!fileName">
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-[10px] font-bold text-slate-400">Click to Upload</span>
                                                <span class="text-[9px] text-slate-300">JPG, PNG Only</span>
                                            </div>
                                        </template>

                                        <template x-if="fileName">
                                            <div class="w-full h-full flex items-center justify-center relative">
                                                <template x-if="isImage">
                                                    <img :src="previewUrl" class="h-full w-full object-cover rounded-lg cursor-zoom-in z-20" @click.stop="openFile()">
                                                </template>
                                                <template x-if="!isImage">
                                                    <div class="flex flex-col items-center justify-center gap-1 p-2 text-center cursor-pointer z-20 w-full h-full" @click.stop="openFile()">
                                                        <span class="material-symbols-rounded text-[#9E6B73] text-2xl">description</span>
                                                        <span class="text-[9px] font-bold text-slate-600 truncate max-w-[120px]" x-text="fileName"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="section-card" wire:key="attractions-section">
                    <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-[#9E6B73] text-2xl">celebration</span>
                            <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Attractions & Extras</h2>
                        </div>
                        <div class="relative w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#9E6B73]"><span class="material-symbols-rounded text-lg">search</span></span>
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search attractions..." class="input-field input-with-icon py-2 text-sm">
                        </div>
                    </div>

                    <div class="space-y-8">
                        @php $catIndex = 0; @endphp
                        @foreach($this->categories as $catName => $catData)
                        @if(empty($catData['products'])) @continue @endif
                        @php $catIndex++; @endphp
                        <div class="category-section" data-category="{{ $catName }}">
                            <div class="flex items-center gap-3 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <span class="w-8 h-8 rounded-lg bg-white text-[#9E6B73] flex items-center justify-center font-bold text-xs shadow-sm">{{ $catIndex }}</span>
                                <h3 class="text-md font-bold text-slate-700 flex-1">{{ $catName }}</h3>
                                @if(($catData['limit'] ?? 0) > 0)
                                <span class="cat-limit-badge text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold uppercase tracking-wide">Category Limit: {{ $catData['limit'] }}</span>
                                @endif
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 px-2">
                                @foreach($catData['products'] as $p)
                                @php
                                $cleanName = strtolower(trim($p['name']));
                                $isSelected = isset($selectedItems[$cleanName]);
                                $itemData = $isSelected ? $selectedItems[$cleanName] : ['qty' => 1, 'price' => $p['price']];
                                $availInfo = $availability[$cleanName] ?? ['left' => 99, 'sold_out' => false];
                                $cardClass = $isSelected ? 'border-[#9E6B73] bg-[#FFF5F7] ring-2 ring-[#9E6B73]/20' : 'border-slate-200 hover:border-slate-300';
                                if (!$isSelected && $availInfo['sold_out']) $cardClass = 'opacity-60 bg-slate-50 border-slate-200';
                                @endphp
                                <div class="product-card group relative h-full flex flex-col rounded-[22px] border pb-4 overflow-hidden cursor-pointer transition-all duration-300 hover:shadow-2xl hover:-translate-y-1.5 {{ $isSelected ? 'selected ring-2 ring-[#9D686E] bg-white shadow-xl translate-y--1' : 'bg-white/40 border-slate-100 hover:border-[#9E6B73]/30 bg-white shadow-sm' }}"
                                    wire:loading.class="opacity-60 pointer-events-none grayscale-[0.5]"
                                    wire:key="product-{{ $p['name'] }}"
                                    data-name="{{ $p['name'] }}"
                                    data-category="{{ $p['category'] }}"
                                    data-counts-against="{{ $p['counts_against'] ?: $p['category'] }}"
                                    data-daily-limit="{{ (int)($p['daily_limit'] ?? 0) }}"
                                    data-stock="{{ (int)($p['total_quantity'] ?? 0) }}"
                                    data-category-limit="{{ (int)($catData['limit'] ?? 0) }}"
                                    data-specification="{{ $p['specification'] ?? '' }}"
                                    data-price="{{ $p['price'] }}"
                                    data-product-sold-out="false"
                                    @click="
                                        const cb = $el.querySelector('.ride-checkbox');
                                        if(cb && !cb.disabled) {
                                            cb.checked = !cb.checked;
                                            handleSelection(cb);
                                        }
                                      ">
                                    <div class="flex justify-between items-start gap-2 mb-2 w-full relative">
                                        <div class="pr-2 w-full">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-slate-800 text-sm leading-snug group-hover:text-[#9E6B73]">{{ $p['name'] }}</h4>
                                                <button type="button" @click.stop="openProductDetails($event.currentTarget.closest('.product-card'))" class="text-slate-300 hover:text-[#9E6B73] transition-colors p-1 rounded-full hover:bg-slate-100 flex items-center justify-center shrink-0">
                                                    <span class="material-symbols-rounded text-lg">info</span>
                                                </button>
                                            </div>
                                            <div class="mt-2 status-wrapper flex items-center gap-2">
                                                @php
                                                $left = $availInfo['left'] ?? 0;
                                                $isSoldOut = $availInfo['sold_out'] ?? false;
                                                $badgeClass = 'status-available bg-emerald-100 text-emerald-700';
                                                if ($isSoldOut) {
                                                $badgeClass = 'status-full bg-rose-100 text-rose-700';
                                                } elseif ($left <= 2) {
                                                    $badgeClass='status-limited bg-amber-100 text-amber-700 border border-amber-200' ;
                                                    }
                                                    @endphp
                                                    <span class="status-badge {{ $badgeClass }}">{{ $isSoldOut ? 'SOLD OUT' : $left . ' AVAILABLE' }}</span>
                                                    <span class="text-[10px] font-black text-slate-400 bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100 uppercase tracking-tighter">
                                                        ${{ number_format($p['price'], 2) }}
                                                    </span>
                                            </div>
                                        </div>
                                        <div class="custom-checkbox flex-shrink-0"></div>
                                        <input type="checkbox" class="ride-checkbox hidden" {{ $isSelected ? 'checked' : '' }}>
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-slate-100 ride-override-panel {{ $isSelected ? '' : 'hidden' }}" @click.stop x-cloak>
                                        <label class="text-[9px] font-black text-[#9E6B73] uppercase tracking-widest block mb-1.5 flex items-center gap-1.5">
                                            <span class="material-symbols-rounded text-xs">edit_note</span>
                                            Price Override
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-400 text-[10px] font-bold">$</span>
                                            <input type="number"
                                                wire:model.live.debounce.500ms="selectedItems.{{ $cleanName }}.price"
                                                oninput="if(window.triggerRecalculate) window.triggerRecalculate(true)"
                                                step="0.01"
                                                class="manual-ride-price w-full bg-white border border-slate-200 rounded-xl py-1.5 pl-5 pr-2 text-[11px] font-black text-slate-700 focus:ring-2 focus:ring-[#9E6B73]/20 focus:border-[#9E6B73] transition-all"
                                                placeholder="{{ number_format($p['price'], 2) }}">
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-medium action-text mt-auto">
                                        @if($isSelected)
                                        Selected
                                        @else
                                        Click to select
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach

                        <div class="bg-slate-50 rounded-3xl p-6 border border-slate-200 mt-8">
                            <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2 mb-6 border-b border-slate-200 pb-3"><span class="material-symbols-rounded text-[#9E6B73]">tune</span> Extra Configurations</h3>

                            <div id="dynamicExtrasContainer" class="grid grid-cols-1 gap-6" wire:ignore>
                                <p class="text-xs text-slate-500 italic py-4 col-span-full">Select attractions to view related extras.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <template x-teleport="body">
        <!-- SAVE CONFIRM MODAL -->
        <div x-show="modals.saveConfirm" class="fixed inset-0 z-[9999] flex items-center justify-center p-6" x-cloak>
            <div x-show="modals.saveConfirm"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/80 backdrop-blur-md"
                @click="modals.saveConfirm = false"></div>

            <div x-show="modals.saveConfirm"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-6"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-6"
                class="relative w-full max-w-md bg-white rounded-[28px] shadow-2xl overflow-hidden z-10">

                <!-- Header Band -->
                <div class="bg-gradient-to-br from-[#9D686E] to-[#7C4E54] px-8 pt-10 pb-8 text-center">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-inner backdrop-blur-sm">
                        <span class="material-symbols-rounded text-white text-4xl">save_as</span>
                    </div>
                    <h3 class="text-2xl font-black text-white tracking-tight mb-1">Save Changes?</h3>
                    <p class="text-[13px] font-medium text-white/70 leading-relaxed">Review your modifications before confirming.</p>
                </div>

                <!-- Body -->
                <div class="px-8 py-7">
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 mb-7">
                        <span class="material-symbols-rounded text-amber-500 text-xl shrink-0 mt-0.5">info</span>
                        <p class="text-[13px] font-medium text-amber-800 leading-relaxed">
                            This will <span class="font-extrabold">update the invoice</span> and <span class="font-extrabold">production schedule</span>. Make sure all attractions, extras, and financials are correct.
                        </p>
                    </div>

                    <!-- Buttons stacked -->
                    <div class="flex flex-col gap-3">
                        <button
                            wire:click="saveBooking"
                            @click="if(typeof saveCurrentExtrasState === 'function') saveCurrentExtrasState(false); if(typeof triggerRecalculate === 'function') triggerRecalculate();"
                            wire:loading.attr="disabled"
                            class="w-full py-4 bg-[#9D686E] text-white hover:bg-[#7C4E54] font-black text-[13px] rounded-2xl transition-all active:scale-95 uppercase tracking-widest flex items-center justify-center gap-2.5 disabled:opacity-75 disabled:cursor-wait shadow-none">
                            <span wire:loading.remove wire:target="saveBooking" class="flex items-center gap-2.5">
                                <span class="material-symbols-rounded text-xl leading-none">check_circle</span>
                                Confirm & Save Booking
                            </span>
                            <span wire:loading wire:target="saveBooking" class="flex items-center gap-2.5">
                                <span class="material-symbols-rounded animate-spin text-xl leading-none flex items-center justify-center">sync</span>
                                Syncing to Cloud...
                            </span>
                        </button>
                        <button
                            @click="modals.saveConfirm = false"
                            class="w-full py-4 text-slate-500 hover:text-slate-700 font-bold text-[12px] hover:bg-slate-50 rounded-2xl transition-colors uppercase tracking-widest flex items-center justify-center gap-2 border border-slate-200 hover:border-slate-300">
                            <span class="material-symbols-rounded text-base">close</span>
                            Cancel, Go Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- CHANGE EXTRAS CONFIRM MODAL -->
        <div x-show="modals.changeExtrasConfirm" class="fixed inset-0 z-[10001] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.changeExtrasConfirm"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="modals.changeExtrasConfirm = false"></div>

            <div x-show="modals.changeExtrasConfirm"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-10 text-center z-10">
                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6 text-amber-600 ring-8 ring-amber-50">
                    <span class="material-symbols-rounded text-4xl font-bold">edit_attributes</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Update Setup?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-10 leading-relaxed px-4">Modifying these extras may impact the total quote and setup requirements for this attraction.</p>
                <div class="flex gap-4">
                    <button @click="modals.changeExtrasConfirm = false" class="flex-1 py-4 text-slate-600 font-black text-[13px] hover:bg-slate-50 rounded-2xl transition-colors uppercase tracking-widest">Go Back</button>
                    <button id="btnConfirmExtraChange" class="flex-1 py-4 bg-[#9D686E] text-white hover:bg-[#855359] font-black text-[13px] rounded-2xl shadow-xl shadow-[#9D686E]/20 transition-all active:scale-95 uppercase tracking-widest">Confirm</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- FULL CAPACITY / 0 LIMIT MODAL -->
        <div x-show="modals.fullCapacityWarning" class="fixed inset-0 z-[10003] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.fullCapacityWarning"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="modals.fullCapacityWarning = false"></div>

            <div x-show="modals.fullCapacityWarning"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-10 text-center z-10">
                <div class="w-20 h-20 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6 text-rose-500 shadow-inner">
                    <span class="material-symbols-rounded text-4xl font-bold">inventory_2</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Full Capacity</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-10 leading-relaxed px-4">
                    This item has reached its <span class="font-black text-rose-600 underline underline-offset-4 decoration-2">daily booking limit</span> or is currently <span class="font-black text-rose-600">out of stock</span> for the chosen date.
                </p>
                <button type="button" @click="modals.fullCapacityWarning = false" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black hover:bg-slate-800 transition shadow-xl shadow-slate-200 uppercase tracking-widest text-[11px]">
                    Acknowledged
                </button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- Category Limit Modal -->
        <div x-show="modals.limitExceeded" class="fixed inset-0 z-[10002] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.limitExceeded"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="modals.limitExceeded = false"></div>

            <div x-show="modals.limitExceeded"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-10 text-center z-10">
                <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-6 text-amber-500 shadow-inner">
                    <span class="material-symbols-rounded text-4xl font-bold">warning</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight uppercase">Limit Reached</h3>
                <p class="text-[14px] font-medium text-slate-600 mb-8 leading-relaxed px-4">
                    You have reached the maximum cap of <span class="font-black text-slate-800" x-text="limitExceededLimit"></span> items for the
                    <span class="font-black text-[#9D686E] underline decoration-2 underline-offset-4" x-text="limitExceededCategory"></span> category.
                </p>
                <button type="button" @click="modals.limitExceeded = false" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black hover:bg-slate-800 transition shadow-xl uppercase tracking-widest text-[11px] flex items-center justify-center gap-2">
                    <span class="material-symbols-rounded text-base">thumb_up</span>
                    I Understand
                </button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- FILE SIZE ALERT MODAL -->
        <div x-show="modals.fileSizeAlert" class="fixed inset-0 z-[100000] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.fileSizeAlert"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="modals.fileSizeAlert = false"></div>

            <div x-show="modals.fileSizeAlert"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-lg bg-white rounded-[24px] shadow-2xl overflow-hidden z-10 border-t-8 border-rose-500 p-8 text-center">

                <div class="w-20 h-20 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6 text-rose-500 ring-8 ring-rose-50/50">
                    <span class="material-symbols-rounded text-4xl font-bold">file_upload_off</span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Storage Limit Exceeded</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-4 leading-relaxed px-2">
                    This file would exceed the <span class="font-black text-rose-600">5MB</span> combined size limit for all attachments.
                </p>

                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-6 text-left">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Current Storage Details</span>
                        <span class="text-[10px] font-black text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-100 uppercase" x-text="totalSizeMB + 'MB / 5.00MB'"></span>
                    </div>

                    <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-rose-500 transition-all duration-500" :style="'width: ' + Math.min(100, (parseFloat(totalSizeMB) / 5) * 100) + '%'"></div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-xl">warning</span>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-800 mb-0.5">Capacity Overload</h4>
                            <p class="text-[11px] text-slate-500 leading-tight">Please remove existing files or compress the new image to fit within the limit.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 mb-8 text-left">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-rounded text-sm text-amber-500 shrink-0">tips_and_updates</span>
                        <span class="text-xs font-black text-amber-700 uppercase tracking-wide">Optimization Tip</span>
                    </div>
                    <p class="text-xs font-medium text-amber-700 leading-relaxed">
                        Try using <span class="underline font-extrabold">TinyPNG</span> or <span class="underline font-extrabold">Squoosh</span>
                        to reduce image size by up to 80% without losing quality.
                    </p>
                </div>

                <button type="button" @click="modals.fileSizeAlert = false"
                    class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black hover:bg-slate-800 transition shadow-xl uppercase tracking-widest text-[11px] flex items-center justify-center gap-2">
                    <span class="material-symbols-rounded text-base">check_circle</span>
                    Got It, Try Again
                </button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- Product Details Modal -->
        <div x-show="productDetails.visible" x-cloak class="fixed inset-0 z-[20000] flex items-center justify-center p-4">
            <div x-show="productDetails.visible" x-transition.opacity.duration.300ms class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="productDetails.visible = false"></div>
            <div x-show="productDetails.visible" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-90 translate-y-4" class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden z-10">
                <div class="p-6 bg-slate-800 text-white flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-rounded text-[#9E6B73] text-2xl">info</span>
                        <h3 class="text-xl font-bold" x-text="productDetails.name">Product Specification</h3>
                    </div>
                    <button type="button" @click="productDetails.visible = false" class="text-slate-400 hover:text-white transition">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
                <div class="p-8 space-y-6">
                    <div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Costing Overview</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-slate-800">$</span>
                            <span class="text-4xl font-black text-slate-800 tracking-tighter" x-text="Number(productDetails.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})">0.00</span>
                            <span class="text-sm font-bold text-slate-400 ml-1">per session</span>
                        </div>
                    </div>
                    <div class="h-px bg-slate-100"></div>
                    <div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-3">Key Specifications</span>
                        <div class="bg-slate-50/50 rounded-xl p-5 border border-slate-100 min-h-[100px]">
                            <template x-if="productDetails.spec">
                                <ul class="space-y-3">
                                    <template x-for="line in productDetails.spec.split('\n').filter(l => l.trim())">
                                        <li class="flex items-start gap-3">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#9E6B73] mt-1.5 shrink-0"></span>
                                            <span class="text-sm text-slate-600 font-medium leading-relaxed" x-text="line"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                            <template x-if="!productDetails.spec">
                                <p class="text-sm text-slate-400 italic">No specific instructions or features listed for this product.</p>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="p-4 bg-slate-50 border-t border-gray-100 flex justify-end">
                    <button type="button" @click="productDetails.visible = false" class="px-6 py-2.5 bg-slate-800 text-white font-bold rounded-xl hover:bg-slate-700 transition shadow-lg shadow-slate-200">Got it, close</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- MOVE CONFIRM MODAL -->
        <div x-show="modals.moveConfirm" class="fixed inset-0 z-[10010] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.moveConfirm" x-transition.opacity.duration.300ms class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="modals.moveConfirm = false"></div>
            <div x-show="modals.moveConfirm" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-10 text-center z-10 border-t-8 border-[#9D686E]">
                <div class="w-20 h-20 bg-[#9D686E]/10 rounded-full flex items-center justify-center mx-auto mb-6 text-[#9D686E] ring-8 ring-[#9D686E]/5">
                    <span class="material-symbols-rounded text-4xl font-bold">event_repeat</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Confirm Move?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-10 leading-relaxed px-4">
                    Are you sure you want to move this booking to <span class="font-black text-slate-800 uppercase" x-text="window.dayjs($wire.tempSelectedDate).format('DD MMM YYYY')"></span>?
                </p>
                <div class="flex gap-4">
                    <button @click="modals.moveConfirm = false" class="flex-1 py-4 text-slate-500 font-black text-[11px] hover:bg-slate-50 rounded-xl transition uppercase tracking-widest border border-slate-200">Go Back</button>
                    <button @click="modals.moveConfirm = false; $wire.executeMove()" class="flex-1 py-4 bg-[#9D686E] text-white rounded-xl font-black hover:bg-[#855359] transition shadow-lg shadow-[#9D686E]/20 uppercase tracking-widest text-[11px]">Confirm Move</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- ITEM REMOVAL CONFIRM MODAL -->
        <div x-show="modals.removeConfirm" class="fixed inset-0 z-[10010] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.removeConfirm" x-transition.opacity.duration.300ms class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="modals.removeConfirm = false"></div>
            <div x-show="modals.removeConfirm" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-10 text-center z-10 border-t-8 border-[#9D686E]">
                <div class="w-20 h-20 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6 text-[#9D686E] ring-8 ring-rose-50/50">
                    <span class="material-symbols-rounded text-4xl font-bold">delete_sweep</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Remove Item?</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-10 leading-relaxed px-4">
                    Deleting <span class="font-black text-slate-800 italic" x-text="itemToRemove"></span> will update the totals and may affect other related logistics.
                </p>
                <div class="flex gap-4">
                    <button @click="modals.removeConfirm = false" class="flex-1 py-4 text-slate-500 font-black text-[11px] hover:bg-slate-50 rounded-xl transition uppercase tracking-widest border border-slate-200">Cancel</button>
                    <button @click="confirmRemoval()" class="flex-1 py-4 bg-[#9D686E] text-white rounded-xl font-black hover:bg-[#855359] transition shadow-lg shadow-[#9D686E]/20 uppercase tracking-widest text-[11px]">Confirm</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- CAPACITY CHECK MODAL -->
        <div x-show="modals.calendarModal" class="fixed inset-0 z-[10005] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.calendarModal"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="modals.calendarModal = false"></div>

            <div x-show="modals.calendarModal"
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
                    <button @click="modals.calendarModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
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
                                <span class="text-[11px] font-extrabold text-[#9D686E] uppercase tracking-widest">Global Soft Limit: 7 MISSIONS / DAY</span>
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
                        $bg = 'bg-emerald-50'; $text = 'text-emerald-700'; $border = 'border-emerald-100';
                        if ($d['left'] == 0) { $bg = 'bg-red-50'; $text = 'text-red-700'; $border = 'border-red-100'; }
                        elseif ($d['left'] <= 2) { $bg='bg-amber-50' ; $text='text-amber-700' ; $border='border-amber-100' ; }

                            $isSelected=$d['date']===$tempSelectedDate;
                            $isOriginal=$d['date']===$booking->event_date;

                            $ring = $isSelected ? 'border-[#9D686E] bg-pink-50 ring-4 ring-[#9D686E]/10 shadow-md z-10' : '' ;
                            $originStyle = $isOriginal && !$isSelected ? 'border-2 border-dashed border-[#9D686E] shadow-inner' : '';
                            $opacity = ($d['left'] == 0 && !$isSelected && !$isOriginal) ? 'opacity-40 grayscale-[0.5]' : '' ;
                            @endphp
                            <button wire:click="$set('tempSelectedDate', '{{ $d['date'] }}')"
                                class="h-20 rounded-2xl border {{ $bg }} {{ $border }} {{ $text }} {{ $ring }} {{ $originStyle }} {{ $opacity }} flex flex-col items-center justify-center cursor-pointer transition-all relative hover:-translate-y-1 hover:shadow-lg group">
                                @if($isOriginal)
                                <div class="absolute -top-1.5 -right-1.5 bg-[#9D686E] text-white text-[8px] px-2 py-0.5 rounded-full font-black uppercase tracking-tighter shadow-sm z-20">Current</div>
                                @endif
                                @if($d['conflict'] ?? false)
                                <div class="absolute -top-1.5 -left-1.5 bg-red-600 text-white p-1 rounded-lg shadow-sm animate-pulse z-20">
                                    <span class="material-symbols-rounded text-[10px] font-black">warning</span>
                                </div>
                                @endif
                                @if($d['breach'] ?? false)
                                <div class="absolute -top-1.5 -right-1.5 bg-amber-600 text-white p-1 rounded-lg shadow-sm animate-pulse z-20">
                                    <span class="material-symbols-rounded text-[10px] font-black">inventory_2</span>
                                </div>
                                @endif
                                @if($d['duplicate'] ?? false)
                                <div class="absolute -bottom-1 -right-1 bg-amber-600 text-white p-1 rounded-lg shadow-sm z-20">
                                    <span class="material-symbols-rounded text-[10px] font-black">person_alert</span>
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
                                <div class="flex-1">
                                    <p class="text-[11px] font-black text-red-800 uppercase tracking-tight">Scheduling Prohibition</p>
                                    <p class="text-[10px] font-bold text-red-700/80 leading-relaxed mt-0.5">
                                        Movement to this date is blocked. Items already committed:
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
                                    <div class="mt-2 space-y-2">
                                        @foreach($modalCapacityBreaches as $cat => $data)
                                        <div class="bg-white/60 p-2.5 rounded-xl border border-amber-200/50">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-[11px] font-black text-amber-900 uppercase">{{ $cat }}</span>
                                                <span class="text-[11px] font-black text-amber-600 tracking-tighter">{{ $data['current'] + $data['added'] }} / {{ $data['limit'] }}</span>
                                            </div>
                                            <div class="w-full bg-amber-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-amber-500 h-full" style="width: 100%"></div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
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
                                            <span class="text-[9px] font-bold text-slate-500 italic">{{ strtoupper($nc['status'] ?? '') }}</span>
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

                    <div class="mt-10 flex flex-wrap items-center gap-6 text-[10px] text-slate-400 font-extrabold justify-center border-t border-slate-50 pt-8 uppercase tracking-widest">
                        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>AVAILABLE</span>
                        <span class="inline-flex items-center gap-2 text-red-500"><span class="material-symbols-rounded text-sm">warning</span> CONFLICT</span>
                        <span class="inline-flex items-center gap-2 text-amber-500"><span class="material-symbols-rounded text-sm">inventory_2</span> CAPACITY</span>
                        <span class="inline-flex items-center gap-2 text-amber-600 font-black"><span class="material-symbols-rounded text-sm">person_alert</span> DUPLICATE</span>
                    </div>
                </div>

                <div class="p-8 border-t border-slate-50 bg-white">
                    <button wire:click="applySelectedDate()"
                        @if(count($modalConflicts)> 0 || count($modalCapacityBreaches) > 0) disabled @endif
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
        <div x-show="modals.history" x-cloak class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[10000]">
            <div x-show="modals.history" x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.history = false"></div>
            <div x-show="modals.history" x-transition class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[85vh]">
                <div class="p-6 border-b border-gray-100 flex flex-col gap-4 bg-green-600 text-white rounded-t-2xl">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold">Existing Customers</h3>
                        <button type="button" @click="modals.history = false" class="text-white/70 hover:text-white p-2 rounded-full hover:bg-white/20 transition"><span class="material-symbols-rounded">close</span></button>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-200"><span class="material-symbols-rounded text-lg">search</span></span>
                        <input type="text" x-model="searchHistory" @input="filterCustomers()" placeholder="Search name or email..." class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/20 text-white placeholder-white/70 focus:bg-white focus:text-slate-800 outline-none transition border border-transparent focus:border-white">
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-2 bg-slate-50 min-h-[400px]">
                    <template x-for="c in paginatedCustomers">
                        <div class="p-3 bg-white border border-gray-100 rounded-xl hover:border-green-300 hover:bg-green-50 cursor-pointer flex justify-between items-center transition group" @click="fillCustomerDetails(c)">
                            <div>
                                <p class="font-bold text-slate-800" x-text="(c.customer_first_name + ' ' + (c.customer_last_name || '')).trim()"></p>
                                <p class="text-[10px] font-black text-[#9E6B73] uppercase tracking-tighter" x-show="c.suburb" x-text="c.suburb + (c.state ? ', ' + c.state : '')"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="(c.customer_organization || 'Private') + ' • ' + (c.customer_email || c.customer_phone || '')"></p>
                            </div>
                            <span class="text-xs font-bold text-green-600 opacity-0 group-hover:opacity-100 transition">Select</span>
                        </div>
                    </template>
                    <div x-show="filteredCustomers.length === 0" class="text-center p-4 text-gray-400">No customers found.</div>
                </div>

                <!-- Pagination Controls -->
                <div class="p-4 border-t border-gray-100 bg-white flex items-center justify-between rounded-b-2xl" x-show="filteredCustomers.length > 0">
                    <span class="text-xs text-gray-500 font-bold">Page <span x-text="customerPage"></span> of <span x-text="totalCustomerPages"></span></span>
                    <div class="flex gap-2">
                        <button type="button" @click="if(customerPage > 1) customerPage--" :disabled="customerPage === 1" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition">Previous</button>
                        <button type="button" @click="if(customerPage < totalCustomerPages) customerPage++" :disabled="customerPage === totalCustomerPages" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <div id="booking-data-bridge"
        class="hidden"
        data-config='@json($this->config)'
        data-categories='@json($this->categories)'
        data-extras='@json($this->saved_extras)'
        data-selected='@json($selectedItemsClean ?? [])'
        data-duration-cost="{{ $durationCost }}"
        data-delivery-cost="{{ $deliveryCost }}"
        data-customers='[]'
        data-csrf="{{ csrf_token() }}"
        data-extra-prices='@json($extraPrices)'
        data-active-overrides='@json($activeOverrides)'
        data-locked-overrides='@json($lockedOverrides)'
        data-manual-prices='@json($manualPrices)'
        data-id="{{ $this->booking->id }}"
        data-total-paid="{{ $totalPaid }}"
        data-invoice="{{ $this->booking->invoice_number }}">
    </div>
</div>

@script
<script>
    // CLEANED UP BRIDGE LOGIC
    window.initJSBridge = function() {
        if (window.jsBridgeInitialized) return;
        
        console.log("Initializing Booking JS Bridge...");
        
        if (typeof window.initBookingAppData === 'function') {
            window.initBookingAppData();
        }

        setTimeout(() => {
            if (typeof checkRealTimeAvailability === 'function') checkRealTimeAvailability(true);
            if (typeof window.triggerRecalculate === 'function') window.triggerRecalculate();
        }, 500);

        window.lwBookingComponent = $wire;

        // One-time wrapper for saveCurrentExtrasState
        if (typeof window.saveCurrentExtrasState === 'function' && !window.saveCurrentExtrasState._isWrapped) {
            const originalSaveExtras = window.saveCurrentExtrasState;
            window.saveCurrentExtrasState = function(ignoreSync = false) {
                if (typeof originalSaveExtras === 'function') {
                    originalSaveExtras(true); // Always run original UI updates
                }
                // Only sync back to server if explicitly requested
                if (!ignoreSync && window.lwBookingComponent) {
                    window.lwBookingComponent.syncExtras(window.bookingAppData && window.bookingAppData.savedExtras ? window.bookingAppData.savedExtras : {});
                }
            };
            window.saveCurrentExtrasState._isWrapped = true;
        }

        if (typeof window.toggleItemUI === 'function' && !window.toggleItemUI._isWrapped) {
            const originalToggleItemUI = window.toggleItemUI;
            window.toggleItemUI = function(checkbox, card) {
                if (typeof originalToggleItemUI === 'function') {
                    originalToggleItemUI(checkbox, card);
                }
                if (window.lwBookingComponent) {
                    window.lwBookingComponent.toggleItem(card.dataset.name, checkbox.checked);
                }
            };
            window.toggleItemUI._isWrapped = true;
        }

        window.jsBridgeInitialized = true;
    };

    document.addEventListener('livewire:navigated', window.initJSBridge);
    document.addEventListener('livewire:initialized', window.initJSBridge);

    document.addEventListener('livewire:update', () => {
        if (typeof window.initBookingAppData === 'function') {
            window.initBookingAppData();
        }
        if (typeof updateDynamicExtras === 'function') {
            updateDynamicExtras();
        }
        if (typeof window.triggerRecalculate === 'function') {
            window.triggerRecalculate();
        }
    });

    // Run once on load
    window.initJSBridge();
</script>
@endscript
</div>