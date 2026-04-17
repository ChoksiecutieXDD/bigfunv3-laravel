<div x-data="bookingApp"
    x-init="
        showCustomDelivery = @entangle('form.delivery_area').live === 'custom' || (@entangle('form.delivery_area').live !== '' && !@js($deliveryOptions->pluck('zone_name')->contains($form['delivery_area'] ?? '')));
        showCustomDuration = @entangle('form.duration').live === 'custom';
        $watch('modals.history', val => { if(val) loadPreviousCustomers(); });

        window.addEventListener('cost-increased', e => {
            modals.costIncrease = true;
            modals.costDelta = e.detail.delta;
        });
        window.addEventListener('cost-decreased', e => {
            modals.costDecrease = true;
            modals.costDelta = e.detail.delta;
        });
        window.addEventListener('negative-balance-alert', e => {
            modals.negativeBalance = true;
        });
    "
    class="w-full relative pb-8">
    <!-- Premium Toast Notifications (system-settings style) -->
    <div class="fixed top-6 right-6 z-[999999] flex flex-col gap-3 pointer-events-none" style="width:380px;">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                x-transition:enter="transition ease-out duration-400"
                x-transition:enter-start="opacity-0 translate-x-8 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 translate-x-8 scale-95"
                class="pointer-events-auto w-full bg-slate-900/95 backdrop-blur-xl border rounded-2xl shadow-2xl p-4 flex items-start gap-3"
                :class="{
                    'border-emerald-500/40': toast.type === 'success',
                    'border-red-500/40': toast.type === 'error',
                    'border-amber-500/40': toast.type === 'warning',
                    'border-[#9E6B73]/40': toast.type === 'primary'
                }">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                    :class="{
                        'bg-emerald-500/15 text-emerald-400': toast.type === 'success',
                        'bg-red-500/15 text-red-400': toast.type === 'error',
                        'bg-amber-500/15 text-amber-400': toast.type === 'warning',
                        'bg-[#9E6B73]/15 text-[#9E6B73]': toast.type === 'primary'
                    }">
                    <span class="material-symbols-rounded text-xl" x-text="toast.icon"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-bold text-sm text-white" x-text="toast.title"></h4>
                    <p class="text-xs text-slate-400 mt-0.5 leading-relaxed" x-text="toast.message"></p>
                </div>
                <button @click="toast.visible = false" class="text-slate-600 hover:text-slate-300 transition shrink-0 p-1 rounded-lg hover:bg-white/10">
                    <span class="material-symbols-rounded text-base">close</span>
                </button>
            </div>
        </template>
    </div>

    <div class="flex w-full relative overflow-hidden">
        <main class="flex-1 pt-4 pb-16 px-0 max-w-[1440px] mx-auto w-full">
            <form id="combinedBookingForm" onsubmit="return false;" class="form-layout-wrapper">
                <input type="hidden" name="booking_id" id="booking_id" value="{{ $booking->id }}">
                <input type="hidden" name="invoice_number" id="invoice_number" value="{{ $booking->invoice_number }}">
                <input type="hidden" id="duration_cost" value="{{ $durationCost }}">
                <input type="hidden" id="delivery_cost" value="{{ $deliveryCost }}">

                <div class="flex flex-col gap-6 mb-8">
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
                            $backRoute = $isSupervisor ? 'supervisor.bookings.overview' : 'booking.overview';
                            @endphp
                            <a href="{{ route($backRoute, $booking->id) }}" wire:navigate class="bg-white hover:bg-gray-50 text-slate-600 p-2.5 rounded-xl border border-gray-200 transition shadow-sm flex items-center justify-center">
                                <span class="material-symbols-rounded text-2xl">arrow_back</span>
                            </a>
                            <div>
                                <h1 class="text-3xl font-extrabold text-[#1E293B]">Edit Booking</h1>
                                <p class="text-sm text-slate-500 font-medium mt-1 uppercase tracking-wide text-[10px]">Invoice: <span class="font-bold text-[#9D686E]">{{ $booking->invoice_number ?? $booking->id }}</span></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <button @click="modals.saveConfirm = true" type="button" class="btn-action bg-[#9E6B73] text-white hover:bg-[#86545C] flex-1 sm:flex-none justify-center shadow-md shadow-[#9E6B73]/20">
                                <span class="material-symbols-rounded text-lg mr-2">save</span> SAVE CHANGES
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
                        <div class="flex gap-10">
                            <div class="text-right">
                                <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Outstanding Balance</p>
                                <p class="text-4xl font-extrabold tracking-tighter {{ $balanceDue > 0.01 ? 'text-rose-400' : 'text-emerald-400' }}" id="disp_balance">${{ number_format($balanceDue, 2) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Total Amount</p>
                                <p class="text-4xl font-extrabold tracking-tighter text-white/50" id="disp_total">${{ number_format($totalAmount, 2) }}</p>
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
                                <span class="font-bold">-${{ number_format($totalPaid, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-base font-black text-white mt-1.5 bg-slate-800/50 p-3 rounded-xl border border-slate-700">
                                <span class="flex items-center gap-2 font-bold uppercase tracking-wider text-[11px]">Outstanding Balance</span>
                                <span class="text-xl {{ $balanceDue > 0.01 ? 'text-rose-400' : 'text-emerald-400' }}">${{ number_format($balanceDue, 2) }}</span>
                            </div>

                            <div class="bg-slate-800/50 rounded-xl p-4 mt-6 border border-slate-700">
                                <label class="text-[10px] text-slate-400 uppercase font-bold mb-2 block">Override Final Total (Optional)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 font-bold">$</span>
                                    <input type="number" id="override_total" wire:model.live.debounce.500ms="totalAmount" placeholder="Leave empty to use calculated sum" class="input-dark input-with-icon !py-3">
                                </div>
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
                                            <option value="Card Holder">Credit/Debit Card</option>
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
                    <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                        <span class="material-symbols-rounded text-[#9E6B73] text-2xl">calendar_month</span>
                        <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Live Availability & Duration</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 pt-6 border-t border-gray-100 mt-4">
                        <div class="input-group">
                            <label class="input-label">Event Date</label>
                            <div class="flex gap-2">
                                <input type="date" id="event_date" name="event_date" wire:model.live="form.event_date" value="{{ $form['event_date'] }}" class="input-field" @change="dateChanged()">
                                <button wire:click="openCalendarModal" type="button" class="bg-[#9E6B73] text-white px-4 rounded-xl flex items-center justify-center hover:bg-[#855359] transition shadow-md shadow-[#9E6B73]/20">
                                    <span class="material-symbols-rounded">calendar_month</span>
                                </button>
                            </div>
                        </div>
                        <div class="input-group lg:col-span-1">
                            <label class="input-label">Operational Hours</label>
                            <input type="text" wire:model="form.operational_hours" placeholder="e.g. 9am to 5pm or TBC" class="input-field">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Start Time</label>
                            <input type="time" wire:model="form.start_time" class="input-field">
                        </div>
                        <div class="input-group">
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
                                <input type="text" wire:model.live="form.custom_duration_text" placeholder="e.g. 2 Days, Full Weekend" class="input-field bg-white">
                            </div>
                            <div class="input-group">
                                <label class="input-label text-[#9E6B73]">Manual Duration Cost</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                    <input type="number" wire:model.live="form.duration_cost" step="0.01" class="input-field bg-white pl-8" placeholder="0.00">
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

                                <div x-show="showCustomDelivery" x-collapse class="mt-4">
                                    <div class="input-group">
                                        <label class="input-label text-[#9E6B73]">Manual Delivery Cost</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                            <input type="number" wire:model.live="form.delivery_cost" step="0.01" class="input-field input-with-icon" placeholder="0.00">
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
                            <input type="text" wire:model="form.lead_operator" class="input-field" placeholder="Select Staff...">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Lead Deliverer</label>
                            <input type="text" wire:model="form.lead_deliverer" class="input-field" placeholder="Select Staff...">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100 mt-6">
                        <label class="input-label mb-2 flex items-center justify-between">
                            <span class="flex items-center gap-2">Delivery Attachments <span class="font-black text-slate-500">(Up to 5)</span></span>
                            <span class="flex items-center gap-1.5 text-[10px] bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider"><span class="material-symbols-rounded text-xs">folder_limited</span>Max 5MB Total</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach([
                            1 => 'delivery_attachment',
                            2 => 'delivery_attachment_2',
                            3 => 'delivery_attachment_3',
                            4 => 'delivery_attachment_4',
                            5 => 'delivery_attachment_5'
                            ] as $i => $field)
                            @php
                            $hasFile = !empty($form[$field]) && !in_array($field, $deletedAttachments);
                            @endphp
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col justify-center border-dashed">
                                @if($hasFile)
                                <div class="flex items-center justify-between">
                                    <a href="/uploads/{{ $form[$field] }}" target="_blank" class="text-xs font-bold text-[#9E6B73] hover:underline flex items-center gap-1 truncate"><span class="material-symbols-rounded text-sm">open_in_new</span> View Slot {{ $i }}</a>
                                    <button type="button" wire:click="markAttachmentDeleted('{{ $field }}')" class="text-red-400 hover:text-red-600 transition"><span class="material-symbols-rounded text-sm">delete</span></button>
                                </div>
                                @else
                                <input type="file" accept="image/png,image/jpeg,application/pdf"
                                    wire:model="newAttachments.{{ $field }}"
                                    @change="checkTotalAttachmentSize($el)"
                                    class="text-[10px] text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-[#9E6B73]/10 file:text-[#9E6B73] hover:file:bg-[#9E6B73]/20 cursor-pointer">
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="section-card" wire:ignore>
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
                                $qty = $isSelected ? $selectedItems[$cleanName] : 1;
                                $availInfo = $availability[$cleanName] ?? ['left' => 99, 'sold_out' => false];
                                $cardClass = $isSelected ? 'border-[#9E6B73] bg-[#FFF5F7] ring-2 ring-[#9E6B73]/20' : 'border-slate-200 hover:border-slate-300';
                                if (!$isSelected && $availInfo['sold_out']) $cardClass = 'opacity-60 bg-slate-50 border-slate-200';
                                @endphp
                                <div class="product-card group {{ $isSelected ? 'selected' : '' }} cursor-pointer"
                                    data-name="{{ $p['name'] }}"
                                    data-category="{{ $p['category'] }}"
                                    data-counts-against="{{ $p['counts_against'] ?: $p['category'] }}"
                                    data-daily-limit="{{ (int)($p['daily_limit'] ?? 0) }}"
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
                                            <div class="mt-2"><span class="status-badge {{ $availInfo['sold_out'] ? 'status-full bg-rose-100 text-rose-700' : 'status-available bg-emerald-100 text-emerald-700' }}">{{ $availInfo['sold_out'] ? 'SOLD OUT' : $availInfo['left'] . ' AVAILABLE' }}</span></div>
                                        </div>
                                        <div class="custom-checkbox flex-shrink-0"></div>
                                        <input type="checkbox" class="ride-checkbox hidden" {{ $isSelected ? 'checked' : '' }}>
                                    </div>
                                    <div class="flex items-center justify-between mt-auto pt-2" @click.stop>
                                        <span class="text-[10px] text-slate-400 font-medium action-text">{{ $isSelected ? 'Booked' : 'Click to select' }}</span>

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
                            @click="saveCurrentExtrasState(); $wire.set('dynamicExtras', window.bookingAppData.savedExtras); $wire.saveBooking();"
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
        <!-- CALENDAR MODAL -->
        <div x-show="modals.calendar" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.calendar"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="modals.calendar = false"></div>

            <div x-show="modals.calendar"
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
                    <button @click="modals.calendar = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded text-2xl font-bold">close</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-8 bg-white">
                    <div class="bg-slate-50 p-5 rounded-[24px] mb-8 border border-slate-100">
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

                    <div class="grid grid-cols-7 text-[11px] font-black text-slate-300 mb-4 uppercase tracking-widest text-center px-1">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
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
                            $isOriginal=$d['date']===($booking->event_date ?? $form['event_date']);

                            $ring = $isSelected ? 'border-[#9D686E] bg-pink-50 ring-4 ring-[#9D686E]/10 shadow-md z-10' : '' ;
                            $originStyle = $isOriginal && !$isSelected ? 'border-2 border-dashed border-[#9D686E] shadow-inner' : '';
                            @endphp
                            @php
                            $dayAtts = $dailyAttractions[$d['date']] ?? [];
                            $hasConflict = false;
                            foreach($bookedAttractions as $myAtt) {
                                if(in_array($myAtt, $dayAtts)) { $hasConflict = true; break; }
                            }
                            @endphp
                            <button wire:click="$set('tempSelectedDate', '{{ $d['date'] }}')"
                                class="h-20 rounded-2xl border {{ $bg }} {{ $border }} {{ $text }} {{ $ring }} {{ $originStyle }} {{ $hasConflict ? 'ring-2 ring-red-500 ring-offset-2' : '' }} flex flex-col items-center justify-center cursor-pointer transition-all relative hover:-translate-y-1 shadow-sm group hover:border-[#9D686E]">
                                @if($isOriginal)
                                <div class="absolute -top-1.5 -right-1.5 bg-[#9D686E] text-white text-[7px] px-2 py-0.5 rounded-full font-black uppercase tracking-tighter shadow-sm z-20">Current</div>
                                @endif
                                
                                @if($hasConflict)
                                <div class="absolute top-1 right-1 flex items-center justify-center w-5 h-5 bg-red-500 text-white rounded-full shadow-sm animate-pulse">
                                    <span class="material-symbols-rounded text-sm font-bold">warning</span>
                                </div>
                                @endif

                                @if($d['breach'] ?? false)
                                <div class="absolute top-1 left-1 flex items-center justify-center w-5 h-5 bg-amber-500 text-white rounded-full shadow-sm">
                                    <span class="material-symbols-rounded text-sm font-bold">inventory_2</span>
                                </div>
                                @endif

                                <span class="font-black text-lg">{{ $d['day'] }}</span>
                                <span class="text-[9px] uppercase font-extrabold tracking-tighter group-hover:text-[#9D686E]">{{ $d['left'] }} Left</span>
                            </button>
                            @endif
                            @endforeach
                    </div>

                    <div class="mt-10 flex items-center gap-6 text-[9px] text-slate-400 font-extrabold justify-center border-t border-slate-50 pt-8 uppercase tracking-widest">
                        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm"></span>AVAILABLE</span>
                        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-sm"></span>BUSY</span>
                        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-sm"></span>FULL</span>
                        <span class="inline-flex items-center gap-2 p-1.5 bg-red-50 text-red-600 rounded-lg"><span class="material-symbols-rounded text-xs">warning</span> CONFLICT</span>
                        <span class="inline-flex items-center gap-2 p-1.5 bg-amber-50 text-amber-600 rounded-lg"><span class="material-symbols-rounded text-xs">inventory_2</span> AT CAPACITY</span>
                    </div>

                    @if($tempSelectedDate)
                    <div class="mt-8 animate-[fadeIn_0.3s_ease-out]">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <span class="material-symbols-rounded text-sm">event_note</span>
                            Schedule for {{ \Carbon\Carbon::parse($tempSelectedDate)->format('d M Y') }}
                        </h4>
                        <div class="bg-slate-50 rounded-2xl border border-slate-100 overflow-hidden">
                            @php
                            $dayBookings = DB::table('bookings')
                                ->where('event_date', $tempSelectedDate)
                                ->where('id', '!=', $booking->id)
                                ->whereNotIn('status', ['Cancelled'])
                                ->get();
                            @endphp
                            
                            @if($dayBookings->isEmpty())
                                <div class="p-6 text-center text-xs text-slate-400 font-medium">No other bookings found for this date.</div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="bg-slate-100/50">
                                            <tr class="text-[9px] text-slate-500 uppercase tracking-widest">
                                                <th class="px-4 py-3 font-black">Invoice</th>
                                                <th class="px-4 py-3 font-black">Customer</th>
                                                <th class="px-4 py-3 font-black">Booked Items</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($dayBookings as $db)
                                            <tr class="hover:bg-white transition-colors">
                                                <td class="px-4 py-3 text-[10px] font-black text-[#9D686E]">{{ $db->invoice_number }}</td>
                                                <td class="px-4 py-3 text-[11px] font-bold text-slate-700">{{ $db->customer_first_name }} {{ $db->customer_last_name }}</td>
                                                <td class="px-4 py-3">
                                                    @php
                                                    $dbItems = DB::table('booking_items')->where('booking_id', $db->id)->pluck('item_name')->toArray();
                                                    @endphp
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($dbItems as $dbi)
                                                        @php 
                                                            $isConflict = in_array(strtolower(trim($dbi)), $bookedAttractions);
                                                        @endphp
                                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold {{ $isConflict ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-600' }}">
                                                            {{ $dbi }}
                                                        </span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                @if($tempSelectedDate)
                    @if(!empty($modalConflicts) || !empty($modalCapacityBreaches) || !empty($modalNameConflicts))
                    <div class="px-8 pb-4">
                        <div class="bg-red-50/50 border border-red-100 rounded-2xl p-4 flex flex-col gap-3">
                            @foreach($modalConflicts as $ca)
                            <div class="flex items-center gap-3 text-red-700 text-[11px] font-black uppercase tracking-tight">
                                <span class="material-symbols-rounded text-base">warning</span>
                                Conflict: '{{ $ca }}' is already booked on this day.
                            </div>
                            @endforeach

                            @foreach($modalCapacityBreaches as $cat => $data)
                            <div class="flex items-center gap-3 text-amber-700 text-[11px] font-black uppercase tracking-tight">
                                <span class="material-symbols-rounded text-base">inventory_2</span>
                                Capacity: Category {{ $cat }} ({{ $data['current'] }} + {{ $data['added'] }} > {{ $data['limit'] }})
                            </div>
                            @endforeach

                            @if(!empty($modalNameConflicts))
                            <div class="pt-2 border-t border-red-100/50 mt-1">
                                <p class="text-[10px] font-black text-amber-800 uppercase tracking-tight mb-2 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm">person_alert</span>
                                    Potential Duplicate Detected
                                </p>
                                <div class="space-y-1">
                                    @foreach($modalNameConflicts as $nc)
                                    <div class="flex justify-between items-center bg-white/60 p-1.5 px-3 rounded-lg border border-amber-200/30">
                                        <span class="text-[9px] font-black text-amber-900 uppercase">#{{ $nc['invoice_number'] ?? $nc['id'] }}</span>
                                        <span class="text-[9px] font-bold text-slate-500 italic">{{ strtoupper($nc['status']) }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                <p class="text-[9px] font-bold text-amber-600/80 mt-2 italic uppercase tracking-tighter">Existing bookings found for this customer on this date.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                @endif

                <div class="p-8 border-t border-slate-50 bg-white">
                    <button wire:click="applySelectedDate" 
                        {{ ($tempSelectedDate && (!empty($modalConflicts) || !empty($modalCapacityBreaches))) ? 'disabled' : '' }}
                        class="w-full py-5 rounded-2xl bg-[#9D686E] text-white font-black shadow-xl shadow-[#9D686E]/20 hover:bg-[#855359] transition-all transform active:scale-95 uppercase tracking-widest text-xs flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:grayscale">
                        <span class="material-symbols-rounded text-base">event_available</span>
                        Apply Selection
                    </button>
                </div>
            </div>
        </div>
    </template>


    <!-- Modal Event Listeners -->
    <div x-on:close-modal.window="if ($event.detail === 'calendarModal' || (Array.isArray($event.detail) && $event.detail[0] === 'calendarModal')) modals.calendar = false; if ($event.detail === 'saveConfirm' || (Array.isArray($event.detail) && $event.detail[0] === 'saveConfirm')) modals.saveConfirm = false;"
        x-on:open-modal.window="if ($event.detail === 'calendarModal' || (Array.isArray($event.detail) && $event.detail[0] === 'calendarModal')) modals.calendar = true; if ($event.detail === 'saveConfirm' || (Array.isArray($event.detail) && $event.detail[0] === 'saveConfirm')) modals.saveConfirm = true;"></div>

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
        <div x-show="modals.fileSizeAlert" class="fixed inset-0 z-[20001] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.fileSizeAlert"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="modals.fileSizeAlert = false"></div>

            <div x-show="modals.fileSizeAlert"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-10 text-center z-10">

                <div class="w-20 h-20 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6 text-rose-500 ring-8 ring-rose-50/50">
                    <span class="material-symbols-rounded text-4xl font-bold">file_upload_off</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">File Too Large</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-4 leading-relaxed px-2">
                    This file exceeds the <span class="font-black text-rose-600">5MB</span> size limit per attachment.
                </p>
                <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 mb-8 text-left">
                    <p class="text-xs font-bold text-rose-700 flex items-start gap-2">
                        <span class="material-symbols-rounded text-sm shrink-0 mt-0.5">tips_and_updates</span>
                        Please compress or resize your image before uploading. Tools like <span class="underline font-extrabold">TinyPNG</span> or <span class="underline font-extrabold">Squoosh</span> can reduce file sizes quickly.
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
        <!-- COST INCREASE MODAL -->
        <div x-show="modals.costIncrease" class="fixed inset-0 z-[10005] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.costIncrease" x-transition.opacity.duration.300ms class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="modals.costIncrease = false"></div>
            <div x-show="modals.costIncrease" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-8 text-center z-10 border-t-8 border-rose-500">
                <div class="w-16 h-16 bg-rose-50 rounded-2xl flex items-center justify-center mx-auto mb-5 text-rose-500">
                    <span class="material-symbols-rounded text-3xl font-bold">trending_up</span>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2 uppercase tracking-tight">Cost Increase</h3>
                <p class="text-[13px] font-medium text-slate-500 mb-6 leading-relaxed">The total amount will increase by <span class="font-bold text-rose-600">$<span x-text="parseFloat(modals.costDelta).toFixed(2)"></span></span>. Please ensure this is authorized.</p>
                <button @click="modals.costIncrease = false" class="w-full py-4 bg-rose-500 text-white rounded-xl font-bold hover:bg-rose-600 transition shadow-lg shadow-rose-100 uppercase tracking-widest text-[10px]">Acknowledge</button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- COST DECREASE MODAL -->
        <div x-show="modals.costDecrease" class="fixed inset-0 z-[10005] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.costDecrease" x-transition.opacity.duration.300ms class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="modals.costDecrease = false"></div>
            <div x-show="modals.costDecrease" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-8 text-center z-10 border-t-8 border-emerald-500">
                <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto mb-5 text-emerald-500">
                    <span class="material-symbols-rounded text-3xl font-bold">trending_down</span>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2 uppercase tracking-tight">Cost Decrease</h3>
                <p class="text-[13px] font-medium text-slate-500 mb-6 leading-relaxed">The total amount will decrease by <span class="font-bold text-emerald-600">$<span x-text="parseFloat(modals.costDelta).toFixed(2)"></span></span>.</p>
                <button @click="modals.costDecrease = false" class="w-full py-4 bg-emerald-500 text-white rounded-xl font-bold hover:bg-emerald-600 transition shadow-lg shadow-emerald-100 uppercase tracking-widest text-[10px]">Acknowledge</button>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- NEGATIVE BALANCE MODAL -->
        <div x-show="modals.negativeBalance" class="fixed inset-0 z-[10006] flex items-center justify-center p-4" x-cloak>
            <div x-show="modals.negativeBalance" x-transition.opacity.duration.300ms class="absolute inset-0 bg-red-900/40 backdrop-blur-md" @click="modals.negativeBalance = false"></div>
            <div x-show="modals.negativeBalance" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-10 text-center z-10 border-t-8 border-amber-500">
                <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-6 text-amber-500 shadow-inner">
                    <span class="material-symbols-rounded text-4xl font-bold">account_balance_wallet</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight uppercase">Negative Balance</h3>
                <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed px-4">
                    Your total amount will decrease below what was already paid. The customer will have a <span class="font-black text-amber-600 underline underline-offset-4 decoration-2">credit balance</span>.
                </p>
                <button type="button" @click="modals.negativeBalance = false" class="w-full py-4 bg-amber-500 text-white rounded-2xl font-black hover:bg-amber-600 transition shadow-xl shadow-amber-100 uppercase tracking-widest text-[11px]">
                    Understood
                </button>
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
        data-id="{{ $this->booking->id }}"
        data-invoice="{{ $this->booking->invoice_number }}">
</div>
</div>

@vite(['resources/js/availability-sync.js', 'resources/js/new-booking.js'])

@script
<script>
    document.addEventListener('livewire:navigated', () => {
         // 0. Refresh bridge data on navigation entry
         if (typeof window.initBookingAppData === 'function') {
             window.initBookingAppData();
         }

         // 1. Initial check after load/navigation
         setTimeout(() => {
            if (typeof checkRealTimeAvailability === 'function') {
                checkRealTimeAvailability(true);
            }
            if (typeof window.triggerRecalculate === 'function') {
                window.triggerRecalculate();
            }
         }, 500);

         // 2. Force a direct bridge between Vanilla JS and Livewire
         window.lwBookingComponent = $wire;

         // 3. Override the Extra saving to guarantee Livewire gets the data
         const originalSaveExtras = window.saveCurrentExtrasState;
         window.saveCurrentExtrasState = function(ignoreSync = false) {
             if (typeof originalSaveExtras === 'function') {
                originalSaveExtras(true); // Run original UI updates but block old sync
             }
             if (!ignoreSync && window.lwBookingComponent) {
                 window.lwBookingComponent.syncExtras(window.bookingAppData.savedExtras);
             }
         };

         // 4. Override Item toggling to guarantee Livewire sync on UI interactions
         const originalToggleItemUI = window.toggleItemUI;
         window.toggleItemUI = function(checkbox, card) {
             if (typeof originalToggleItemUI === 'function') {
                originalToggleItemUI(checkbox, card);
             }
             if (window.lwBookingComponent) {
                 window.lwBookingComponent.toggleItem(card.dataset.name, checkbox.checked);
             }
         };
    });

    // For first load if navigated didn't fire
    document.addEventListener('livewire:initialized', () => {
         setTimeout(() => {
            if (typeof checkRealTimeAvailability === 'function') {
                checkRealTimeAvailability(true);
            }
            if (typeof window.triggerRecalculate === 'function') {
                window.triggerRecalculate();
            }
         }, 300);
         window.lwBookingComponent = $wire;
    });
</script>
@endscript
</div>