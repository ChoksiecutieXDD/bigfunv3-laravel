<div x-data="bookingApp" 
    x-init="
        showCustomDelivery = @entangle('form.delivery_area').live === 'custom' || (@entangle('form.delivery_area').live !== '' && !@js($deliveryOptions->pluck('zone_name')->contains($form['delivery_area'] ?? '')));
        showCustomDuration = @entangle('form.duration').live === 'custom';
        $watch('modals.history', val => { if(val) loadPreviousCustomers(); });
    "
    class="w-full relative pb-8">

    <div class="flex w-full relative overflow-hidden">
        <main class="flex-1 pt-4 pb-16 px-0 max-w-[1440px] mx-auto w-full">
            <form id="combinedBookingForm" onsubmit="return false;" class="form-layout-wrapper">
                <input type="hidden" name="booking_id" id="booking_id" value="{{ $booking->id }}">
                <input type="hidden" name="invoice_number" id="invoice_number" value="{{ $booking->invoice_number }}">

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
                        <div class="text-right">
                            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Total Amount</p>
                            <p class="text-4xl font-extrabold tracking-tighter text-white" id="disp_total">${{ number_format($totalAmount, 2) }}</p>
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
                                <button wire:click="loadCalendar(); $dispatch('open-modal', 'calendarModal')" type="button" class="bg-[#9E6B73] text-white px-4 rounded-xl flex items-center justify-center hover:bg-[#855359] transition">
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
                            <span>Delivery Attachments (Up to 5)</span>
                            <span class="text-[10px] bg-[#9E6B73]/10 text-[#9E6B73] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Up to 5 slots</span>
                        </label>
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
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col justify-center border-dashed">
                                @if($hasFile)
                                <div class="flex items-center justify-between">
                                    <a href="/uploads/{{ $form[$field] }}" target="_blank" class="text-xs font-bold text-[#9E6B73] hover:underline flex items-center gap-1 truncate"><span class="material-symbols-rounded text-sm">open_in_new</span> View Slot {{ $i }}</a>
                                    <button type="button" wire:click="markAttachmentDeleted('{{ $field }}')" class="text-red-400 hover:text-red-600 transition"><span class="material-symbols-rounded text-sm">delete</span></button>
                                </div>
                                @else
                                <input type="file" wire:model="newAttachments.{{ $field }}" class="text-[10px] text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-[#9E6B73]/10 file:text-[#9E6B73] hover:file:bg-[#9E6B73]/20 cursor-pointer">
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="section-card">
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
                                     data-daily-limit="{{ (int)($catData['limit'] ?? 0) }}"
                                     data-specification="{{ $p['specification'] ?? '' }}"
                                     data-price="{{ $p['price'] }}"
                                     data-product-sold-out="false"
                                     @click="const cb = $el.querySelector('.ride-checkbox'); if(cb && !cb.disabled) { cb.checked = !cb.checked; handleSelection(cb); $wire.toggleItem('{{ $p['name'] }}', cb.checked); }">
                                    <div class="flex justify-between items-start gap-2 mb-2 w-full relative">
                                        <div class="pr-2 w-full">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h4 class="font-bold text-slate-800 text-sm leading-snug group-hover:text-[#9E6B73] line-clamp-2 min-h-[40px]">{{ $p['name'] }}</h4>
                                                <button type="button" @click.stop="openProductDetails($el.closest('.product-card'))" class="text-slate-300 hover:text-[#9E6B73] transition-colors p-1 rounded-full hover:bg-slate-100 flex items-center justify-center shrink-0">
                                                    <span class="material-symbols-rounded text-lg">info</span>
                                                </button>
                                            </div>
                                            <div class="mt-2 status-wrapper"><span class="status-badge status-checking">Checking...</span></div>
                                        </div>
                                        <div class="custom-checkbox flex-shrink-0"></div>
                                        <input type="checkbox" class="ride-checkbox hidden" {{ $isSelected ? 'checked' : '' }}>
                                    </div>
                                    <div class="flex items-center justify-between mt-auto pt-2" @click.stop>
                                        <span class="text-[10px] text-slate-400 font-medium action-text">{{ $isSelected ? 'Booked' : 'Click to select' }}</span>
                                        @if($isSelected)
                                        <div class="flex items-center bg-white border border-[#9E6B73] rounded-lg overflow-hidden">
                                            <button wire:click.stop="updateItemQty('{{ $p['name'] }}', -1)" @click="setTimeout(() => triggerRecalculate(), 100)" class="w-7 h-7 flex items-center justify-center bg-[#FFF5F7] text-[#9E6B73] font-bold hover:bg-[#9E6B73] hover:text-white transition">-</button>
                                            <input type="text" readonly value="{{ $qty }}" class="w-8 text-center border-none text-xs font-bold text-slate-700 bg-transparent pointer-events-none">
                                            <button wire:click.stop="updateItemQty('{{ $p['name'] }}', 1)" @click="setTimeout(() => triggerRecalculate(), 100)" class="w-7 h-7 flex items-center justify-center bg-[#FFF5F7] text-[#9E6B73] font-bold hover:bg-[#9E6B73] hover:text-white transition">+</button>
                                        </div>
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

    <!-- SAVE CONFIRM MODAL -->
    <div x-show="modals.saveConfirm" class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[9999]" x-cloak>
        <div x-show="modals.saveConfirm" x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.saveConfirm = false"></div>
        <div x-show="modals.saveConfirm" x-transition class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center z-10">
            <div class="w-14 h-14 bg-[#9E6B73]/10 rounded-full flex items-center justify-center mx-auto mb-4 text-[#9E6B73]">
                <span class="material-symbols-rounded text-3xl">save</span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Save All Changes?</h3>
            <p class="text-sm text-slate-600 mb-6 leading-relaxed">Are you sure you want to finalize and save all modifications made to this booking? This action will update the invoice and calendar records.</p>
            <div class="flex gap-3">
                <button @click="modals.saveConfirm = false" class="flex-1 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition">Cancel</button>
                <button @click="saveCurrentExtrasState(); $wire.set('dynamicExtras', window.bookingAppData.savedExtras); $wire.saveBooking(); modals.saveConfirm = false;" class="flex-1 py-3 bg-[#9E6B73] text-white rounded-xl font-bold hover:bg-[#86545C] transition shadow-lg shadow-[#9E6B73]/20 flex items-center justify-center gap-2 text-lg">
                    <span class="material-symbols-rounded">check_circle</span> Yes, Save
                </button>
            </div>
        </div>
    </div>

    <!-- CHANGE EXTRAS CONFIRM MODAL -->
    <div x-show="modals.changeExtrasConfirm" class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[10001]" x-cloak>
        <div x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.changeExtrasConfirm = false"></div>
        <div x-transition class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center z-10">
            <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4 text-amber-600">
                <span class="material-symbols-rounded text-3xl">edit_attributes</span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Change Extras?</h3>
            <p class="text-sm text-slate-600 mb-6">You are about to modify the selected extras for this attraction. This may affect the total price and setup requirements.</p>
            <div class="flex gap-3">
                <button @click="modals.changeExtrasConfirm = false" class="flex-1 py-2 bg-slate-100 text-slate-700 rounded-xl font-bold">Cancel</button>
                <button id="btnConfirmExtraChange" class="flex-1 py-2 bg-[#9E6B73] text-white rounded-xl font-bold">Confirm Change</button>
            </div>
        </div>
    </div>

    <!-- EDIT RIDES CONFIRM MODAL -->
    <div x-show="modals.editRidesConfirm" class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[10001]" x-cloak>
        <div x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.editRidesConfirm = false"></div>
        <div x-transition class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center z-10">
            <div class="w-14 h-14 bg-[#9E6B73]/10 rounded-full flex items-center justify-center mx-auto mb-4 text-[#9E6B73]">
                <span class="material-symbols-rounded text-3xl">info</span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Modify Attractions?</h3>
            <p class="text-sm text-slate-600 mb-6">Are you sure you want to change the selected rides for this booking? Availability will be re-checked for the new selection.</p>
            <div class="flex gap-3">
                <button @click="modals.editRidesConfirm = false" class="flex-1 py-2 bg-slate-100 text-slate-700 rounded-xl font-bold">Cancel</button>
                <button id="btnConfirmRideChange" class="flex-1 py-2 bg-[#9E6B73] text-white rounded-xl font-bold">Confirm Edit</button>
            </div>
        </div>
    </div>

    <!-- FULL CAPACITY / 0 LIMIT MODAL -->
    <div x-show="modals.fullCapacityWarning" class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[10003]" x-cloak>
        <div x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.fullCapacityWarning = false"></div>
        <div x-transition class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6 text-red-600">
                <span class="material-symbols-rounded text-4xl">error</span>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-3 uppercase tracking-tight">Full Capacity</h3>
            <p class="text-sm text-slate-600 mb-8 leading-relaxed">
                This item has reached its <span class="font-bold text-red-600">daily limit</span> or is <span class="font-bold text-red-600">out of stock</span> for the selected date. 
                Please choose a different date or another attraction.
            </p>
            <button type="button" @click="modals.fullCapacityWarning = false" class="w-full py-4 bg-slate-800 text-white rounded-xl font-bold hover:bg-slate-700 transition uppercase tracking-widest text-xs">
                I Understand
            </button>
        </div>
    </div>

    <!-- CALENDAR MODAL -->
    <div x-show="modals.calendar" class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[9999]" x-cloak>
        <div x-show="modals.calendar" x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.calendar = false"></div>
        <div x-show="modals.calendar" x-transition class="relative bg-white rounded-3xl shadow-2xl p-8 w-full max-w-lg z-10">
            <div class="flex justify-between items-center mb-8">
                <h3 class="font-bold text-gray-800 text-xl">Check Date Availability</h3>
                <button @click="modals.calendar = false" class="text-gray-400 hover:text-gray-600 transition p-1 hover:bg-slate-50 rounded-lg"><span class="material-symbols-rounded">close</span></button>
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
                    $ring=$isSelected ? 'border-[#9E6B73] bg-pink-50 ring-2 ring-[#9E6B73] shadow-md z-10' : '' ;
                    @endphp
                    <button wire:click="$set('tempSelectedDate', '{{ $d['date'] }}')" class="h-14 rounded-2xl border {{ $bg }} {{ $border }} {{ $text }} {{ $ring }} flex flex-col items-center justify-center cursor-pointer transition hover:-translate-y-0.5 shadow-sm">
                    <span class="font-bold text-sm">{{ $d['day'] }}</span>
                    <span class="text-[8px] uppercase font-bold tracking-tight">{{ $d['left'] }} Left</span>
                    </button>
                    @endif
                    @endforeach
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-100 mt-8">
                <button wire:click="applySelectedDate" class="px-8 py-3 rounded-2xl bg-[#9E6B73] text-white font-bold shadow-lg shadow-[#9E6B73]/20 hover:bg-[#86545C] transition transform active:scale-95">Apply Selected Date</button>
            </div>
        </div>
    </div>

    <!-- Modal Event Listeners -->
    <div x-on:close-modal.window="if ($event.detail === 'calendarModal' || (Array.isArray($event.detail) && $event.detail[0] === 'calendarModal')) modals.calendar = false; if ($event.detail === 'saveConfirm' || (Array.isArray($event.detail) && $event.detail[0] === 'saveConfirm')) modals.saveConfirm = false;" 
         x-on:open-modal.window="if ($event.detail === 'calendarModal' || (Array.isArray($event.detail) && $event.detail[0] === 'calendarModal')) modals.calendar = true; if ($event.detail === 'saveConfirm' || (Array.isArray($event.detail) && $event.detail[0] === 'saveConfirm')) modals.saveConfirm = true;"></div>

    <!-- Category Limit Modal -->
    <div id="categoryLimitModal" x-show="modals.limitExceeded" x-cloak class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[10002]">
        <div x-show="modals.limitExceeded" x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.limitExceeded = false"></div>
        <div x-show="modals.limitExceeded" x-transition class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8 text-center">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6 text-amber-600">
                <span class="material-symbols-rounded text-4xl">warning</span>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-3 uppercase tracking-tight">Category Limit Reached</h3>
            <p class="text-sm text-slate-600 mb-8 leading-relaxed">
                You have reached the maximum limit of <span class="font-bold text-slate-800" x-text="limitExceededLimit"></span> items for the 
                <span class="font-extrabold text-[#9E6B73] underline decoration-2 underline-offset-4" x-text="limitExceededCategory"></span> category. 
                Please deselect an item before adding a new one.
            </p>
            <button type="button" @click="modals.limitExceeded = false" class="w-full py-4 bg-slate-800 text-white rounded-xl font-bold hover:bg-slate-700 transition shadow-lg shadow-slate-200 uppercase tracking-widest text-xs">
                I Understand
            </button>
        </div>
    </div>

    <div id="booking-data-bridge"
        class="hidden"
        data-config='@json($this->config)'
        data-categories='@json($this->categories)'
        data-extras='@json($this->saved_extras)'
        data-selected='@json($selectedItemsClean ?? [])'
        data-customers='[]'
        data-csrf="{{ csrf_token() }}"
        data-id="{{ $this->booking->id }}"
        data-invoice="{{ $this->booking->invoice_number }}">
    </div>

    <!-- Product Details Modal -->
    <div x-show="productDetails.visible" x-cloak class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[20000]">
        <div x-show="productDetails.visible" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="productDetails.visible = false"></div>
        <div x-show="productDetails.visible" x-transition class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
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

    @vite(['resources/js/new-booking.js'])
</div>