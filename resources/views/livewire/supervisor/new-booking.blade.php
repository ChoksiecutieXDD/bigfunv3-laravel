<div x-data="bookingApp" 
    x-init="
        window.lwBookingComponent = @this;
        window.bookingAppData = {
            savedExtras: @entangle('saved_extras'),
            selectedItems: @entangle('selected_products'),
            extraPrices: @entangle('extraPrices'),
            activeOverrides: @entangle('activeOverrides'),
            csrfToken: '{{ csrf_token() }}',
            formToken: '{{ $form_token }}'
        };
    "
    class="w-full relative pb-8">


    <div class="flex w-full relative overflow-hidden">
        <main class="flex-1 pt-4 pb-16 px-0 max-w-[1440px] mx-auto w-full">

            <form id="combinedBookingForm" onsubmit="return false;" class="form-layout-wrapper">
                <input type="hidden" name="booking_id" id="booking_id" value="{{ $booking_id }}">
                <input type="hidden" name="invoice_number" id="invoice_number" value="{{ $invoice_number }}">

                <div class="flex flex-col gap-6">
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
                            <a href="{{ route('supervisor.calendar') }}" wire:navigate class="bg-white hover:bg-gray-50 text-slate-600 p-2.5 rounded-xl border border-gray-200 transition shadow-sm flex items-center justify-center">
                                <span class="material-symbols-rounded text-2xl">arrow_back</span>
                            </a>
                            <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-3xl font-extrabold text-[#1E293B]">{{ $is_edit_mode ? 'Edit Booking' : 'Create Booking' }}</h1>
                                <button type="button" @click="modals.systemInfo = true" class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 hover:bg-[#9E6B73]/10 hover:text-[#9E6B73] transition-colors flex items-center justify-center">
                                    <span class="material-symbols-rounded text-base">info</span>
                                </button>
                            </div>
                            <p class="text-sm text-slate-500 font-medium mt-1 uppercase tracking-wide text-[10px]">Invoice: <span class="font-bold text-[#9D686E]">{{ $invoice_number }}</span></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <button type="button" @click="modals.history = true; filteredCustomers = previousCustomers; searchHistory = ''" class="btn-action bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 flex-1 sm:flex-none justify-center">
                                <span class="material-symbols-rounded mr-2 text-lg">history</span> Past Customer
                            </button>
                            <button type="button" @click="gmailModalVisible = true" class="btn-action bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 flex-1 sm:flex-none justify-center">
                                <span class="material-symbols-rounded mr-2 text-lg">mail</span> Import from Gmail
                            </button>
                            <button type="button" @click="modals.reset = true" class="btn-action bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 flex-1 sm:flex-none justify-center">
                                <span class="material-symbols-rounded mr-2 text-lg">restart_alt</span> Reset Form
                            </button>
                            <button type="button" @click="openReviewModal()" class="btn-action bg-[#9E6B73] text-white hover:bg-[#86545C] flex-1 sm:flex-none justify-center shadow-md shadow-[#9E6B73]/20">
                                <span class="material-symbols-rounded mr-2 text-lg">playlist_add_check</span> Review Details
                            </button>
                        </div>
                    </header>
                </div>

                <div class="financial-panel" wire:ignore>
                    <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-[#9E6B73]/20 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>

                    <div class="flex items-center justify-between border-b border-slate-700 pb-4 relative z-10">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-[#9E6B73] text-3xl">account_balance_wallet</span>
                            <h2 class="text-xl font-bold text-white uppercase tracking-wide">Financials & Payment</h2>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Total Amount</p>
                            <p class="text-4xl font-extrabold tracking-tighter text-white" id="disp_total">$0.00</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
                        <div class="space-y-4">
                            <h3 class="text-sm font-bold text-[#9E6B73] uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Cost Breakdown</h3>

                            <div class="flex justify-between items-center text-sm text-slate-300">
                                <span>Duration Cost</span>
                                <span class="font-bold" id="breakdown_dur">$0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-slate-300">
                                <span>Delivery Zone Cost</span>
                                <span class="font-bold" id="breakdown_del">$0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-slate-300">
                                <span>Attractions Cost</span>
                                <span class="font-bold" id="breakdown_attractions">$0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-slate-300">
                                <span>Extras Cost</span>
                                <span class="font-bold" id="breakdown_ext">$0.00</span>
                            </div>

                            <div class="h-px bg-slate-700 my-3"></div>

                            <div class="flex justify-between items-center text-sm font-bold text-white">
                                <span>Subtotal</span>
                                <div class="flex items-center gap-1 text-lg">
                                    $ <input type="number" id="calc_subtotal" readonly class="bg-transparent text-right w-24 outline-none border-none pointer-events-none text-white font-bold">
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-sm mt-2">
                                <span class="text-slate-400" id="surcharge_label">Processing Fee (0%)</span>
                                <span class="font-medium text-slate-300" id="disp_surcharge">$0.00</span>
                            </div>

                            <div class="bg-slate-800/50 rounded-xl p-4 mt-6 border border-slate-700">
                                <label class="text-[10px] text-slate-400 uppercase font-bold mb-2 block">Override Final Total (Optional)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 font-bold">$</span>
                                    <input type="number" name="final_total" id="override_total" placeholder="Leave empty to use calculated sum" @input="calculateFinalTotals()" class="input-dark input-with-icon !py-3">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <h3 class="text-sm font-bold text-[#9E6B73] uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Payment Configuration</h3>

                            <div class="flex flex-col gap-4">
                                <div class="input-group">
                                    <label class="input-label text-slate-400 !ml-1">Payment Type</label>
                                    <div class="relative">
                                        <select name="payment_type" x-model="paymentType" @change="updatePaymentMethods()" class="input-dark appearance-none cursor-pointer">
                                            <option value="EFT">EFT</option>
                                            <option value="Card Holder">Card Holder</option>
                                            <option value="Cash">Cash</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>

                                <div class="input-group" x-show="paymentType === 'EFT'" x-transition>
                                    <label class="input-label text-slate-400 !ml-1">Payment Method</label>
                                    <div class="relative">
                                        <select name="payment_method" x-model="paymentMethod" @change="triggerRecalculate()" class="input-dark appearance-none cursor-pointer">
                                            <template x-for="opt in paymentMethods" :key="opt">
                                                <option :value="opt" x-text="opt"></option>
                                            </template>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>
                            </div>

                            <div x-show="paymentType === 'Card Holder'" x-collapse class="bg-slate-800/80 rounded-2xl p-5 border border-slate-700 mt-4 shadow-inner flex flex-col gap-4">
                                <h4 class="text-xs font-bold text-[#9E6B73] uppercase flex justify-between items-center"><span>Card Details</span><span class="material-symbols-rounded text-sm">lock</span></h4>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="relative">
                                        <select name="card_network" id="card_network" x-model="cardNetwork" class="input-dark appearance-none !py-3 text-sm cursor-pointer">
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
                                    <input type="text" name="card_number" id="card_number" placeholder=" " maxlength="19"
                                        x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim()"
                                        class="input-dark font-mono text-lg tracking-widest">
                                    <label class="input-floating-label">Card Number</label>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="relative">
                                        <input type="text" name="card_expiry" id="card_expiry" placeholder=" " maxlength="5"
                                            x-on:input="
                                                let v = $el.value.replace(/\D/g, '');
                                                if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2,4);
                                                $el.value = v;
                                            "
                                            class="input-dark text-center font-mono">
                                        <label class="input-floating-label">MM/YY</label>
                                    </div>
                                    <div class="relative">
                                        <input type="text" name="card_cvv" id="card_cvv" placeholder=" " :maxlength="['American Express', 'AMEX'].includes(cardNetwork) ? 4 : 3" class="input-dark text-center font-mono">
                                        <label class="input-floating-label">CVV</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="text-[10px] text-slate-400 uppercase font-bold mb-2 ml-1 block">Deposit Status</label>
                                <input type="hidden" name="payment_status" :value="paymentStatus">
                                <div class="payment-status-toggle" :class="paymentStatus === 'Pending' ? 'toggle-state-pending' : 'toggle-state-paid'" @click="togglePaymentStatus()">
                                    <div class="payment-status-option" :class="{'active': paymentStatus === 'Pending'}">Pay Later (Pending)</div>
                                    <div class="payment-status-option" :class="{'active': paymentStatus === 'Deposit Paid'}">Deposit Paid</div>
                                    <div class="payment-status-indicator"></div>
                                </div>
                            </div>

                            <div x-show="paymentStatus === 'Deposit Paid'" x-collapse class="mt-4 space-y-4">
                                <div class="relative">
                                    <input type="text" name="payment_reference" placeholder=" " class="input-dark">
                                    <label class="input-floating-label">Reference Number</label>
                                </div>
                                <div class="relative">
                                    <textarea name="payment_notes" placeholder=" " class="input-dark !h-24 resize-none" style="min-height: 96px;"></textarea>
                                    <label class="input-floating-label">Additional Payment Notes</label>
                                </div>
                            </div>

                            <div class="flex items-center justify-between bg-[#9E6B73]/20 rounded-xl p-4 border border-[#9E6B73]/30 mt-4">
                                <span class="text-slate-300 text-xs uppercase font-bold">Req. Deposit (50%)</span>
                                <span class="text-white font-bold text-xl" id="disp_deposit">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="surcharge_amount" id="surcharge_amount" value="0">
                    <input type="hidden" name="deposit_amount" id="deposit_amount" value="0">
                </div>

                <div class="section-card" wire:ignore>
                    <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                        <span class="material-symbols-rounded text-[#9E6B73] text-2xl">calendar_month</span>
                        <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Live Availability & Duration</h2>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="font-extrabold text-slate-800 leading-tight">Select Date</p>
                                <p class="text-[11px] text-slate-500" id="calSummary">Loading...</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="zoom-btn" @click="calPrev()"><span class="material-symbols-rounded text-sm">chevron_left</span></button>
                                <p class="text-sm font-bold text-slate-700 w-32 text-center" id="calLabel">—</p>
                                <button type="button" class="zoom-btn" @click="calNext()"><span class="material-symbols-rounded text-sm">chevron_right</span></button>
                            </div>
                        </div>

                        <div class="flex justify-center items-center gap-4 text-[11px] text-slate-600 font-bold mb-4">
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Avail</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>Limited</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Sold</span>
                        </div>

                        <div class="grid grid-cols-7 text-sm sm:text-base font-extrabold text-slate-500 mb-3 text-center">
                            <div>S</div>
                            <div>M</div>
                            <div>T</div>
                            <div>W</div>
                            <div>T</div>
                            <div>F</div>
                            <div>S</div>
                        </div>
                        <div id="calGrid" class="grid grid-cols-7 gap-2 sm:gap-4 mb-6"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 pt-6 border-t border-gray-100">
                        <div class="input-group">
                            <label class="input-label">Selected Date</label>
                            <input type="date" name="event_date" id="event_date" class="input-field" value="{{ $default_event_date }}" @change="dateChanged()">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Operational Hours</label>
                            <input type="text" name="operational_hours" id="operational_hours" class="input-field" placeholder="e.g. 9am to 5pm" value="{{ $operational_hours }}">
                        </div>
                        <div class="input-group" x-show="!isDurationCustom">
                            <label class="input-label">Start Time</label>
                            <input type="time" name="start_time" id="start_time" 
                                class="input-field" :class="{'opacity-50 pointer-events-none bg-slate-100': isDurationCustom}"
                                :readonly="isDurationCustom"
                                value="{{ substr($this->getVal('start_time'), 0, 5) }}" @change="calcDuration()">
                        </div>
                        <div class="input-group" x-show="!isDurationCustom">
                            <label class="input-label">End Time</label>
                            <input type="time" name="end_time" id="end_time" 
                                class="input-field" :class="{'opacity-50 pointer-events-none bg-slate-100': isDurationCustom}"
                                :readonly="isDurationCustom"
                                value="{{ substr($this->getVal('end_time'), 0, 5) }}" @change="calcDuration()">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100">
                        <label class="input-label mb-3">Duration Pricing</label>
                        <input type="hidden" name="duration_cost" id="duration_cost" value="{{ $this->getVal('duration_cost', 0) }}">
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            @php $current_dur = $this->getVal('duration'); @endphp
                            @foreach ($duration_options as $dur)
                            @php
                            $isSelected = ($current_dur == $dur->label);
                            $activeClass = $isSelected ? 'duration-active' : '';
                            @endphp
                            <label class="duration-card flex flex-col items-center justify-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 hover:border-[#9E6B73]/50 transition text-center {{ $activeClass }}" @click="selectDurationCard($event.currentTarget)">
                                <input type="radio" name="duration" value="{{ $dur->label }}" data-price="{{ $dur->price }}" data-hours="{{ $dur->hours }}" {{ $isSelected ? 'checked' : '' }} class="hidden" @change="updateDurationCost($event.target)">
                                <span class="font-bold text-slate-700 text-xs">{{ $dur->label }}</span>
                                <span class="text-[#9E6B73] text-sm font-extrabold mt-1">${{ number_format($dur->price, 2) }}</span>
                            </label>
                            @endforeach

                            <label class="duration-card flex flex-col items-center justify-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 hover:border-[#9E6B73]/50 transition text-center" id="dur_card_custom" @click="selectDurationCard($event.currentTarget)">
                                <input type="radio" name="duration" value="custom" data-price="0" data-hours="0" class="hidden">
                                <span class="font-bold text-slate-700 text-xs uppercase tracking-wide">Custom</span>
                                <span class="text-[#9E6B73] text-[10px] font-extrabold mt-1">Manual Quote</span>
                            </label>
                        </div>

                        <div id="customDurationWrapper" class="hidden mt-4 p-5 bg-slate-50 rounded-2xl border border-slate-200 animate-[fadeIn_0.2s_ease-in] grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="input-group">
                                <label class="input-label">Custom Duration Label</label>
                                <input type="text" name="custom_duration_text" id="custom_duration_text" placeholder="e.g. 2 Days, Full Weekend" class="input-field bg-white" @input="triggerRecalculate()">
                            </div>
                            <div class="input-group">
                                <label class="input-label text-[#9E6B73]">Manual Duration Cost</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                    <input type="number" id="manual_duration_cost" step="0.01" class="input-field bg-white pl-8" placeholder="0.00" @input="document.getElementById('duration_cost').value = $el.value; triggerRecalculate();">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card" wire:ignore>
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
                                    <input type="text" id="cust_first_name" name="customer_first_name" required class="input-field" value="{{ $this->getVal('customer_first_name') }}" @blur="checkDuplicates()">
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Last Name</label>
                                    <input type="text" id="cust_last_name" name="customer_last_name" class="input-field" value="{{ $this->getVal('customer_last_name') }}" @blur="checkDuplicates()">
                                </div>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Business / Org Name</label>
                                <input type="text" id="customer_organization" name="customer_organization" class="input-field" value="{{ $this->getVal('customer_organization') }}">
                            </div>

                            <div class="input-group">
                                <label class="input-label">ABN Number</label>
                                <input type="text" name="customer_abn" id="customer_abn" placeholder="Optional" class="input-field" value="{{ $this->getVal('customer_abn') }}">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Employer Name</label>
                                <input type="text" name="employer_name" id="employer_name" class="input-field" value="{{ $this->getVal('employer_name') }}">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Business Contact Number</label>
                                <input type="tel" name="customer_business_phone" id="customer_business_phone" class="input-field" value="{{ $this->getVal('customer_business_phone') }}">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Mobile Phone <span class="text-red-500">*</span></label>
                                <input type="tel" id="customer_phone_mobile" name="customer_phone_mobile" required class="input-field" value="{{ $this->getVal('customer_phone') }}">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" id="customer_email_address" name="customer_email_address" required class="input-field" value="{{ $this->getVal('customer_email') }}" @blur="checkDuplicates()">
                            </div>
                        </div>

                        <!-- Right Column: VENUE LOCATION -->
                        <div class="space-y-6">
                            <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4">Venue Location</h3>

                            <div class="input-group">
                                <label class="input-label">Event Address Line 1 <span class="text-red-500">*</span></label>
                                <input type="text" name="address_line_1" id="addr_line_1" required class="input-field" value="{{ $this->getVal('address_line_1') }}">
                            </div>

                            <div class="input-group">
                                <label class="input-label">Business Address (Optional)</label>
                                <input type="text" name="business_address" id="business_address" class="input-field" value="{{ $this->getVal('business_address') }}">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="input-group">
                                    <label class="input-label">Suburb</label>
                                    <input type="text" name="suburb" id="addr_suburb" class="input-field" value="{{ $this->getVal('suburb') }}">
                                </div>
                                <div class="input-group">
                                    <label class="input-label">State</label>
                                    <div class="relative">
                                        <select name="state" id="addr_state" class="input-field appearance-none cursor-pointer">
                                            <option value="QLD" {{ $this->getVal('state') == 'QLD' ? 'selected' : '' }}>QLD</option>
                                            <option value="NSW" {{ $this->getVal('state') == 'NSW' ? 'selected' : '' }}>NSW</option>
                                            <option value="VIC" {{ $this->getVal('state') == 'VIC' ? 'selected' : '' }}>VIC</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Postcode</label>
                                    <input type="text" name="postcode" id="addr_postcode" class="input-field" value="{{ $this->getVal('postcode') }}">
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="input-group">
                                        <label class="input-label">Event Type</label>
                                        <div class="relative">
                                            @php $et = $this->getVal('event_type', 'Private'); @endphp
                                            <select name="event_type" id="event_type" class="input-field appearance-none cursor-pointer">
                                                <option value="Private" {{ $et == 'Private' ? 'selected' : '' }}>Private Party</option>
                                                <option value="Corporate" {{ $et == 'Corporate' ? 'selected' : '' }}>Corporate Event</option>
                                                <option value="Community" {{ $et == 'Community' ? 'selected' : '' }}>Community / School</option>
                                            </select>
                                            <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Expected People</label>
                                        <input type="number" name="expected_people" id="expected_people" placeholder="e.g. 50" class="input-field" value="{{ $this->getVal('expected_people') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="input-group">
                                    <label class="input-label">Delivery Zone</label>
                                    <input type="hidden" name="delivery_cost" id="delivery_cost" value="{{ $this->getVal('delivery_cost', 0) }}">
                                    <div class="relative">
                                        <select name="delivery_area" id="delivery_area_select" x-model="deliveryZone" @change="updateDeliveryCost($el)" class="input-field appearance-none cursor-pointer">
                                            <option value="" data-price="0">-- Select Zone --</option>
                                            @foreach ($delivery_options as $del)
                                            <option value="{{ $del->zone_name }}" data-price="{{ $del->price }}">
                                                {{ $del->zone_name }} (+${{ number_format($del->price, 2) }})
                                            </option>
                                            @endforeach
                                            <option value="custom" data-price="0">Custom / Manual Quote</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>

                                <div x-show="deliveryZone === 'custom'" x-collapse class="mt-4">
                                    <div class="input-group">
                                        <label class="input-label text-[#9E6B73]">Manual Delivery Cost</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                            <input type="number" id="delivery_area_manual" step="0.01" class="input-field input-with-icon" placeholder="0.00" value="{{ $this->getVal('delivery_cost') }}" @input="document.getElementById('delivery_cost').value = $el.value; triggerRecalculate();">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-10 border-t border-gray-100 mt-6">
                        <div class="input-group">
                            <label class="input-label">Delivery Notes</label>
                            <textarea name="notes_delivery" id="note_delivery" rows="2" class="input-field resize-none text-xs" placeholder="Access details...">{{ $this->getVal('notes_delivery') }}</textarea>
                        </div>
                        <div class="input-group">
                            <label class="input-label">Customer Notes</label>
                            <textarea name="notes_customer" id="notes_customer" rows="2" class="input-field resize-none text-xs" placeholder="Special requests...">{{ $this->getVal('notes_customer') }}</textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-100">
                        <div class="input-group">
                            <label class="input-label">Lead Operator</label>
                            <input list="staff_list" name="lead_operator" id="lead_operator" class="input-field" placeholder="Select Staff..." value="{{ $this->getVal('lead_operator', 'Team') }}">
                            <datalist id="staff_list">
                                @foreach ($operators_list as $op)
                                <option value="{{ $op }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="input-group">
                            <label class="input-label">Lead Deliverer</label>
                            <input list="staff_list" name="lead_deliverer" id="lead_deliverer" class="input-field" placeholder="Select Staff..." value="{{ $this->getVal('lead_deliverer', 'Team') }}">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100">
                        <label class="input-label mb-2 flex items-center justify-between">
                            <span>Delivery Attachments (Up to 5)</span>
                            <span class="text-[10px] bg-[#9E6B73]/10 text-[#9E6B73] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Max 5MB Total</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @for ($i = 1; $i <= 5; $i++)
                                @php
                                $dbCol=($i===1) ? 'delivery_attachment' : 'delivery_attachment_' . $i;
                                $existingFile=$this->getVal($dbCol);
                                $existingExt = $existingFile ? strtolower(pathinfo($existingFile, PATHINFO_EXTENSION)) : '';
                                @endphp
                                 <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col relative group transition-all hover:bg-white hover:shadow-md" 
                                     :data-filename="fileName"
                                     x-data="{ 
                                        fileName: '{{ $existingFile }}', 
                                        fileExt: '{{ $existingExt }}',
                                        isImage: {{ in_array($existingExt, ['jpg', 'jpeg', 'png']) ? 'true' : 'false' }},
                                        previewUrl: '{{ $existingFile ? "/storage/uploads/$existingFile" : "" }}',
                                        handleFile(el) {
                                            const file = el.files[0];
                                            if (!file) return;

                                            const ext = file.name.split('.').pop().toLowerCase();
                                            if (!['jpg', 'jpeg', 'png'].includes(ext)) {
                                                el.value = '';
                                                window.dispatchEvent(new CustomEvent('notify', { detail: { title: 'Invalid File Type', type: 'error', icon: 'error', message: 'Only JPG and PNG formats are allowed.' } }));
                                                return;
                                            }

                                            // Check size BEFORE updating state
                                            if (!checkTotalAttachmentSize(el)) {
                                                el.value = ''; // Reset input
                                                return;
                                            }

                                            this.fileName = file.name;
                                            this.fileExt = ext;
                                            this.isImage = true;
                                            
                                            // Clean up old object URLs to prevent memory leaks
                                            if (this.previewUrl.startsWith('blob:')) {
                                                URL.revokeObjectURL(this.previewUrl);
                                            }
                                            
                                            this.previewUrl = URL.createObjectURL(file);
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
                                                    @click="$wire.removeAttachment('{{ $dbCol }}'); fileName = ''; fileExt = ''; previewUrl = ''; isImage = false; $el.closest('.group').querySelector('input[type=file]').value = '';"
                                                    class="w-7 h-7 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm z-20">
                                                <span class="material-symbols-rounded text-sm">close</span>
                                            </button>
                                        </template>
                                    </div>

                                    <div class="relative flex-1">
                                        <input type="file" name="delivery_attachment{{ $i > 1 ? "_$i" : "" }}" 
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
                                @endfor
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
                            <input type="text" id="rideSearch" @keyup="filterRides()" placeholder="Search attractions..." class="input-field input-with-icon py-2 text-sm">
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div class="space-y-6">
                            @php $catIndex = 0; @endphp
                            @foreach ($categories as $catName => $catData)
                            @if (empty($catData['products'])) @continue @endif
                            @php $catIndex++; @endphp
                            <div class="category-section" data-category="{{ $catName }}">
                                <div class="flex items-center gap-3 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="w-8 h-8 rounded-lg bg-white text-[#9E6B73] flex items-center justify-center font-bold text-xs shadow-sm">{{ $catIndex }}</span>
                                    <h3 class="text-md font-bold text-slate-700 flex-1">{{ $catName }}</h3>
                                    @if ($catData['limit'] > 0)
                                    <span class="cat-limit-badge text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold uppercase tracking-wide border border-amber-200">Syncing Limits</span>
                                    @endif
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 px-2">
                                    @foreach ($catData['products'] as $product)
                                    @php
                                    $pName = $product['name'];
                                    $countsAgainst = !empty($product['counts_against']) ? $product['counts_against'] : $catName;
                                    $isChecked = in_array($pName, $selected_products);
                                    @endphp
                                    <div class="product-card group cursor-pointer relative"
                                         x-data="{ isChecked: {{ $isChecked ? 'true' : 'false' }} }"
                                         :class="{ 'selected': isChecked }"
                                         @click="
                                            const dateVal = document.getElementById('event_date')?.value;
                                            if (!dateVal) {
                                                showToast('Select Date First', 'Please select a booking date before choosing an attraction.', 'warning');
                                                return;
                                            }
                                            let cb = $el.querySelector('.ride-checkbox'); 
                                            if(cb && !cb.disabled) { 
                                                cb.checked = !cb.checked; 
                                                isChecked = cb.checked; 
                                                handleSelection(cb); 
                                            }
                                         "
                                         data-name="{{ $pName }}"
                                         data-category="{{ $catName }}"
                                         data-counts-against="{{ $countsAgainst }}"
                                         data-daily-limit="{{ (int)$product['daily_limit'] }}"
                                         data-stock="{{ (int)$product['total_quantity'] }}"
                                         data-specification="{{ $product['specification'] ?? '' }}"
                                         data-price="{{ $product['price'] ?? 0 }}"
                                         data-product-sold-out="false">
                                        <div class="flex justify-between items-start gap-2 mb-2 w-full">
                                            <div class="pr-2 w-full">
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-bold text-slate-800 text-sm leading-snug group-hover:text-[#9E6B73]">{{ $pName }}</h4>
                                                    <button type="button" @click.stop="openProductDetails($event.currentTarget.closest('.product-card'))" class="text-slate-300 hover:text-[#9E6B73] transition-colors p-1 rounded-full hover:bg-slate-100 flex items-center justify-center">
                                                        <span class="material-symbols-rounded text-lg">info</span>
                                                    </button>
                                                </div>
                                                <div class="mt-2 status-wrapper flex items-center gap-2">
                                                    <span class="status-badge status-checking">Available</span>
                                                    <span class="text-[10px] font-black text-slate-400 bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100 uppercase tracking-tighter">
                                                        ${{ number_format($product['price'] ?? 100, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="custom-checkbox"></div>
                                            <input type="checkbox" name="products[]" value="{{ $pName }}" class="ride-checkbox hidden" @change="handleSelection($event.target); isChecked = $event.target.checked" {{ $isChecked ? 'checked' : '' }} @click.stop>
                                        </div>
                                        
                                        <div x-show="isChecked" class="mt-2 pt-3 border-t border-slate-100 w-full" @click.stop x-cloak>
                                            <label class="text-[9px] font-black text-[#9E6B73] uppercase tracking-widest block mb-1.5 flex items-center gap-1.5">
                                                <span class="material-symbols-rounded text-xs">edit_note</span>
                                                Price Override
                                            </label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-400 text-[10px] font-bold">$</span>
                                                <input type="number" 
                                                       name="manual_prices[{{ $pName }}]"
                                                       step="0.01" 
                                                       class="manual-ride-price w-full bg-white border border-slate-200 rounded-xl py-1.5 pl-5 pr-2 text-[11px] font-black text-slate-700 focus:ring-2 focus:ring-[#9E6B73]/20 focus:border-[#9E6B73] transition-all" 
                                                       placeholder="{{ number_format($product['price'] ?? 100, 2) }}"
                                                       value="{{ $isChecked ? ($this->selected_manual_prices[$pName] ?? '') : '' }}"
                                                       @input.debounce.500ms="$wire.updateManualPrice('{{ $pName }}', $event.target.value); triggerRecalculate()">
                                            </div>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium action-text mt-auto">Click to select</div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="bg-slate-50 rounded-3xl p-6 border border-slate-200 mt-8">
                            <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2 mb-6 border-b border-slate-200 pb-3"><span class="material-symbols-rounded text-[#9E6B73]">tune</span> Extra Configurations</h3>

                            <div id="dynamicExtrasContainer" class="grid grid-cols-1 gap-6">
                                <p class="text-xs text-slate-500 italic py-4 col-span-full">Select attractions to view related extras.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <div x-show="modals.review" x-cloak class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[9999]">
        <div x-show="modals.review" x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.review = false"></div>
        <div x-show="modals.review" x-transition class="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-gray-100 flex items-center gap-3 bg-slate-800 text-white rounded-t-3xl">
                <span class="material-symbols-rounded text-3xl text-[#9E6B73]">fact_check</span>
                <div>
                    <h3 class="text-xl font-bold">Review Booking Details</h3>
                    <p class="text-xs text-slate-300">Confirm everything looks correct before saving.</p>
                </div>
                <button type="button" @click="modals.review = false" class="ml-auto text-slate-300 hover:text-white transition"><span class="material-symbols-rounded">close</span></button>
            </div>

            <div class="p-6 flex-1 overflow-y-auto custom-scrollbar space-y-6 bg-slate-50">
                <div id="rev_missing_warning" class="hidden mb-2 bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2 text-amber-700">
                        <span class="material-symbols-rounded">warning</span>
                        <h4 class="font-bold text-sm">Missing Information</h4>
                    </div>
                    <p class="text-xs text-amber-800 mb-2">You are proceeding without the following details:</p>
                    <ul id="rev_missing_list" class="text-xs text-amber-700 list-disc list-inside font-medium"></ul>
                </div>

                <!-- Duplicate Alert Banner in Review Modal -->
                <div id="rev_duplicate_warning" class="hidden mb-2 bg-red-50 border border-red-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2 text-red-700">
                        <span class="material-symbols-rounded">warning</span>
                        <h4 class="font-bold text-sm text-red-800">Potential Duplicate Detected</h4>
                    </div>
                    <div id="rev_duplicate_list" class="text-xs text-red-700 space-y-1 mb-2"></div>
                    <p class="text-[10px] font-bold text-red-600">Please verify if this is a double booking before proceeding.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <h4 class="text-xs font-bold text-[#9E6B73] uppercase mb-3 border-b pb-2 flex items-center gap-2"><span class="material-symbols-rounded text-sm">person</span> Customer Info</h4>
                        <div class="space-y-2 text-sm text-slate-600">
                            <p class="flex justify-between"><span class="font-bold">Name:</span> <span id="rev_name"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Email:</span> <span id="rev_email"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Phone:</span> <span id="rev_phone"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Company:</span> <span id="rev_org"></span></p>
                            <p class="flex justify-between"><span class="font-bold">ABN:</span> <span id="rev_abn"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Employer:</span> <span id="rev_employer"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Biz Phone:</span> <span id="rev_biz_phone"></span></p>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <h4 class="text-xs font-bold text-[#9E6B73] uppercase mb-3 border-b pb-2 flex items-center gap-2"><span class="material-symbols-rounded text-sm">event</span> Event & Venue Info</h4>
                        <div class="space-y-2 text-sm text-slate-600">
                            <p class="flex justify-between"><span class="font-bold">Date:</span> <span id="rev_date" class="text-[#9E6B73] font-bold"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Op. Hours:</span> <span id="rev_op_hours"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Time:</span> <span id="rev_time"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Event Type:</span> <span id="rev_event_type"></span></p>
                            <p class="flex justify-between"><span class="font-bold">Expected People:</span> <span id="rev_people"></span></p>
                            <div class="border-t border-slate-100 my-2 pt-2"></div>
                            <p class="flex flex-col"><span class="font-bold mb-1">Event Address:</span> <span id="rev_address" class="text-right leading-snug"></span></p>
                            <p class="flex flex-col mt-2"><span class="font-bold mb-1">Business Address:</span> <span id="rev_biz_address" class="text-right leading-snug"></span></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm h-full">
                        <h4 class="text-xs font-bold text-[#9E6B73] uppercase mb-3 border-b pb-2 flex items-center gap-2"><span class="material-symbols-rounded text-sm">celebration</span> Selected Attractions & Staff</h4>
                        <ul id="rev_attractions" class="space-y-2 text-sm font-bold text-slate-700 mb-4"></ul>

                        <div class="border-t border-slate-100 pt-3">
                            <p class="flex justify-between text-sm text-slate-600"><span class="font-bold">Lead Operator:</span> <span id="rev_operator"></span></p>
                            <p class="flex justify-between text-sm text-slate-600 mt-1"><span class="font-bold">Lead Deliverer:</span> <span id="rev_deliverer"></span></p>
                        </div>

                        <div class="border-t border-slate-100 pt-3 mt-3">
                            <h5 class="text-[10px] font-bold text-slate-400 uppercase mb-2">Attachments</h5>
                            <ul id="rev_attachments" class="text-xs text-slate-600 list-disc pl-4 space-y-1"></ul>
                        </div>
                    </div>

                    <div class="bg-slate-800 text-white p-5 rounded-2xl border border-slate-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-[#9E6B73] uppercase mb-3 border-b border-slate-600 pb-2 flex items-center gap-2"><span class="material-symbols-rounded text-sm">payments</span> Financials</h4>
                            <div class="space-y-2 text-sm text-slate-300">
                                <p class="flex justify-between"><span>Duration:</span> <span id="rev_dur_cost" class="font-bold text-white"></span></p>
                                <p class="flex justify-between"><span>Delivery:</span> <span id="rev_del_cost" class="font-bold text-white"></span></p>
                                <p class="flex justify-between"><span>Attractions:</span> <span id="rev_attractions_cost" class="font-bold text-white"></span></p>
                                <p class="flex justify-between"><span>Extras:</span> <span id="rev_ext_cost" class="font-bold text-white"></span></p>
                                <p class="flex justify-between"><span>Processing Fee:</span> <span id="rev_sur_cost" class="font-bold text-white"></span></p>
                                <div class="border-t border-slate-600 pt-2 mt-2"></div>
                                <p class="flex justify-between text-xs text-slate-400"><span>Grand Total:</span> <span id="rev_total" class="font-bold text-white text-lg"></span></p>
                                <div x-show="paymentType === 'Card Holder'" class="mt-2 p-3 bg-slate-900/50 rounded-xl border border-slate-700 space-y-1">
                                    <div class="flex justify-between text-[10px]"><span class="text-slate-500 uppercase">Card:</span> <span class="font-mono text-white" id="rev_card_masked">**** **** **** ****</span></div>
                                    <div class="flex justify-between text-[10px]"><span class="text-slate-500 uppercase">Exp/CVV:</span> <span class="font-mono text-white">** / ** | ***</span></div>
                                </div>
                                <p class="flex justify-between text-xs text-emerald-400"><span>Deposit Paid:</span> <span id="rev_deposit_paid" class="font-bold"></span></p>
                                <p class="flex justify-between font-bold items-center border-t border-slate-600 pt-3 mt-2"><span class="text-[#9E6B73] uppercase text-xs tracking-wider">Balance Due:</span> <span id="rev_balance_due" class="text-2xl text-white"></span></p>

                                <div id="rev_receipt_wrapper" class="hidden bg-slate-900 rounded-lg p-3 mt-3 border border-slate-700">
                                    <p class="flex justify-between text-[10px] items-center text-slate-400 uppercase font-bold tracking-tight"><span>Receipt Ref:</span> <span id="rev_receipt_id" class="text-white"></span></p>
                                </div>

                                <div class="bg-slate-900 rounded-lg p-3 mt-1 border border-slate-700">
                                    <p class="flex justify-between text-xs items-center"><span>Deposit Status:</span> <span id="rev_status" class="font-bold uppercase px-2 py-1 rounded"></span></p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <h4 class="text-xs font-bold text-[#9E6B73] uppercase mb-3 border-b pb-2 flex items-center gap-2"><span class="material-symbols-rounded text-sm">notes</span> Notes</h4>
                    <p class="flex flex-col text-sm text-slate-600"><span class="font-bold mb-1">Delivery Notes:</span> <span id="rev_del_notes" class="leading-relaxed bg-slate-50 p-2 rounded-lg text-xs"></span></p>
                    <p class="flex flex-col text-sm text-slate-600 mt-3"><span class="font-bold mb-1">Customer Notes:</span> <span id="rev_cust_notes" class="leading-relaxed bg-slate-50 p-2 rounded-lg text-xs"></span></p>
                </div>
            </div>

            <div class="p-5 border-t border-gray-100 flex gap-4 bg-white rounded-b-3xl">
                <button type="button" @click="modals.review = false" class="flex-1 py-4 text-slate-600 font-bold hover:bg-slate-100 rounded-xl transition">Go Back & Edit</button>
                <button type="button" @click="finalizeBooking()" id="btnSaveFinal" class="flex-1 py-4 bg-[#9E6B73] text-white font-bold rounded-xl hover:bg-[#86545C] shadow-lg shadow-[#9E6B73]/20 transition flex items-center justify-center gap-2 text-lg">
                    <span class="material-symbols-rounded">check_circle</span> Confirm & Save Booking
                </button>
            </div>
        </div>
    </div>

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

    <div x-show="modals.exit" x-cloak class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[10001]">
        <div x-show="modals.exit" x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.exit = false"></div>
        <div x-show="modals.exit" x-transition class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500">
                <span class="material-symbols-rounded text-3xl">logout</span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Cancel Booking?</h3>
            <p class="text-sm text-slate-600 mb-6">Any unsaved data will be lost.</p>
            <div class="flex gap-3">
                <button @click="modals.exit = false" class="flex-1 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition">Stay</button>
                <button @click="deleteAndExit()" class="flex-1 py-3 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 transition">Exit</button>
            </div>
        </div>
    </div>

    <div x-show="modals.reset" x-cloak class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[10001]">
        <div x-show="modals.reset" x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="modals.reset = false"></div>
        <div x-show="modals.reset" x-transition class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500">
                <span class="material-symbols-rounded text-3xl">restart_alt</span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Reset Entire Form?</h3>
            <p class="text-sm text-slate-600 mb-6">This will clear all details, attractions, and financials.</p>
            <div class="flex gap-3">
                <button @click="modals.reset = false" class="flex-1 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition">Cancel</button>
                <button @click="performReset()" class="flex-1 py-3 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 transition">Yes, Reset</button>
            </div>
        </div>
    </div>

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

    <!-- SYSTEM INFO MODAL -->
    <div x-show="modals.systemInfo" x-cloak class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[10005]">
        <div x-show="modals.systemInfo" x-transition.opacity.duration.300ms class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="modals.systemInfo = false"></div>
        <div x-show="modals.systemInfo" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="relative w-full max-w-lg bg-white rounded-[24px] shadow-2xl overflow-hidden z-10 border-t-8 border-[#9E6B73]">
            
            <div class="p-8 pb-4 text-left">
                <div class="w-16 h-16 bg-[#9E6B73]/10 rounded-2xl flex items-center justify-center mb-6 text-[#9E6B73]">
                    <span class="material-symbols-rounded text-3xl font-bold">help_center</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-2 tracking-tight">System Concurrency & Guidelines</h3>
                <p class="text-[13px] font-medium text-slate-500 mb-8 leading-relaxed">To ensure data integrity and prevent double-bookings, the system implements the following real-time guards:</p>

                <div class="space-y-6">
                    <div class="flex gap-4 text-left">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-xl">sync_alt</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 mb-1 uppercase tracking-wider text-[11px]">Real-Time Sync</h4>
                            <p class="text-[12px] text-slate-500 leading-relaxed font-medium">When another staff member selects an attraction, a badge with their name appears instantly. Available slots decrease in real-time to prevent overbooking.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 text-left">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-xl">timer</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 mb-1 uppercase tracking-wider text-[11px]">Draft Expiration</h4>
                            <p class="text-[12px] text-slate-500 leading-relaxed font-medium">New bookings are held as "Drafts" for 20 minutes. If not saved within this time, held slots are automatically released for other staff members.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 text-left">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-xl">lock</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 mb-1 uppercase tracking-wider text-[11px]">Category Limits</h4>
                            <p class="text-[12px] text-slate-500 leading-relaxed font-medium">Certain categories (like Mechanical Rides) have shared limits. Selecting any item in these categories counts against the limit shown in header badges.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-8 pt-4">
                <button @click="modals.systemInfo = false" class="w-full py-4 bg-slate-900 text-white rounded-xl font-bold hover:bg-slate-800 transition shadow-lg uppercase tracking-widest text-[10px]">I Understand</button>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div x-show="modals.imagePreviewVisible" x-cloak class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[999999]">
        <div x-show="modals.imagePreviewVisible" x-transition.opacity class="absolute inset-0 bg-slate-900/90 backdrop-blur-md" @click="modals.imagePreviewVisible = false"></div>
        <div x-show="modals.imagePreviewVisible" x-transition class="relative max-w-5xl w-full max-h-[90vh] flex flex-col items-center justify-center pointer-events-none">
            <button @click="modals.imagePreviewVisible = false" class="absolute -top-12 right-0 text-white hover:text-[#9E6B73] transition pointer-events-auto p-2 bg-white/10 rounded-full">
                <span class="material-symbols-rounded text-3xl">close</span>
            </button>
            <img :src="modals.imagePreview" class="max-w-full max-h-full object-contain rounded-2xl shadow-2xl pointer-events-auto overflow-hidden">
        </div>
    </div>

    <button type="button" @click="previewSidebarVisible = !previewSidebarVisible" class="fixed bottom-6 right-6 w-14 h-14 bg-[#9E6B73] text-white rounded-full shadow-lg shadow-[#9E6B73]/30 flex items-center justify-center hover:bg-[#855359] hover:-translate-y-1 transition-all z-40 group" title="Toggle Email Preview">
        <span class="material-symbols-rounded text-2xl group-hover:scale-110 transition-transform">visibility</span>
    </button>

    <!-- EMAIL PREVIEW SIDEBAR -->
    <aside :class="previewSidebarVisible ? 'translate-x-0' : 'translate-x-full'" class="fixed top-0 right-0 h-full w-full md:w-[500px] bg-white shadow-[-10px_0_30px_rgba(0,0,0,0.1)] border-l border-gray-200 z-50 flex flex-col transition-transform duration-300 ease-in-out" x-cloak>
        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <span class="material-symbols-rounded text-[#9E6B73]">visibility</span> Email Preview
                </h3>
                <p class="text-[10px] text-slate-500 uppercase tracking-wide">Reference while editing</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="previewZoomLevel = Math.max(0.5, previewZoomLevel - 0.1)" class="w-8 h-8 rounded hover:bg-white border border-transparent hover:border-gray-200 flex items-center justify-center text-slate-500 transition shadow-sm"><span class="material-symbols-rounded text-sm">remove</span></button>
                <button type="button" @click="previewZoomLevel = 1.0" class="w-8 h-8 rounded hover:bg-white border border-transparent hover:border-gray-200 flex items-center justify-center text-slate-500 transition shadow-sm"><span class="material-symbols-rounded text-sm">restart_alt</span></button>
                <button type="button" @click="previewZoomLevel = Math.min(2.0, previewZoomLevel + 0.1)" class="w-8 h-8 rounded hover:bg-white border border-transparent hover:border-gray-200 flex items-center justify-center text-slate-500 transition shadow-sm"><span class="material-symbols-rounded text-sm">add</span></button>
                <div class="w-px h-6 bg-gray-200 mx-1"></div>
                <button type="button" @click="previewSidebarVisible = false" class="text-slate-400 hover:text-slate-600 p-1 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-gray-200 transition"><span class="material-symbols-rounded text-lg">close</span></button>
            </div>
        </div>
        <div class="flex-1 overflow-auto custom-scrollbar p-6 bg-white text-slate-800 relative">
            <template x-if="selectedEmailBody">
                <div class="origin-top-left transition-transform duration-200 ease-out h-full" :style="'transform: scale(' + previewZoomLevel + ')'" x-html="selectedEmailBody"></div>
            </template>
            <template x-if="!selectedEmailBody">
                <div class="flex flex-col items-center justify-center h-full text-slate-300 mt-20">
                    <span class="material-symbols-rounded text-4xl mb-2">mark_email_unread</span>
                    <p class="text-sm">No email selected</p>
                </div>
            </template>
        </div>
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            <button type="button" @click="extractEmailData()" class="w-full py-3 bg-[#9E6B73] text-white font-bold rounded-xl hover:bg-[#855359] shadow-lg shadow-[#9E6B73]/20 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="!selectedEmailData">
                <span class="material-symbols-rounded">integration_instructions</span> Extract Data
            </button>
        </div>
    </aside>

    <!-- GMAIL SEARCH MODAL -->
    <div x-show="gmailModalVisible" class="fixed inset-0 modal-wrapper flex items-center justify-center p-4 z-[9999]" x-cloak>
        <div x-show="gmailModalVisible" x-transition.opacity class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" @click="gmailModalVisible = false"></div>
        <div x-show="gmailModalVisible" x-transition class="relative w-full max-w-3xl bg-white rounded-3xl shadow-2xl flex flex-col max-h-[85vh] z-10 overflow-hidden border border-slate-200">
            <div class="p-6 border-b border-gray-100 flex flex-col gap-4 bg-[#9E6B73] text-white">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold flex items-center gap-2"><span class="material-symbols-rounded">mail</span> Import from Gmail</h3>
                    <button type="button" @click="gmailModalVisible = false" class="text-white/70 hover:text-white p-2 rounded-full hover:bg-white/20 transition"><span class="material-symbols-rounded text-lg">close</span></button>
                </div>
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400"><span class="material-symbols-rounded text-lg">search</span></span>
                        <input type="text" x-model="gmailSearchQuery" @keyup.enter="fetchEmails()" placeholder="Search subject (e.g. Quote #123)..." class="w-full pl-12 pr-4 py-3.5 rounded-xl bg-white/95 text-gray-800 text-sm focus:bg-white outline-none transition border-none shadow-inner placeholder:text-gray-400">
                    </div>
                    <button type="button" @click="fetchEmails()" class="bg-white text-[#9E6B73] px-6 py-3.5 rounded-xl font-bold hover:bg-gray-50 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" :disabled="isFetchingEmails">
                        <span x-text="isFetchingEmails ? 'Searching...' : 'Search'"></span>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-3 bg-slate-50">
                <template x-if="emailList.length === 0 && !isFetchingEmails">
                    <div class="flex flex-col items-center justify-center h-48">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-3"><span class="material-symbols-rounded text-3xl">inbox</span></div>
                        <p class="text-sm font-medium text-gray-500">Enter a search term to find quotes.</p>
                    </div>
                </template>
                <template x-for="(email, index) in emailList" :key="email.id">
                    <div @click="selectEmail(email)" class="p-4 bg-white border border-gray-100 rounded-2xl hover:border-[#9E6B73]/50 hover:bg-[#FFF5F7]/30 cursor-pointer flex justify-between items-start transition group shadow-sm">
                        <div class="flex-1 min-w-0 pr-4">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-rounded text-slate-400 text-[16px]">person</span>
                                <span class="font-extrabold text-sm text-slate-800 truncate" x-text="email.name"></span>
                                <span class="text-xs text-slate-400 truncate" x-text="'<' + email.email + '>'"></span>
                            </div>
                            <h4 class="font-bold text-[#9E6B73] text-sm mb-1.5 truncate" x-text="email.subject || '(No Subject)'"></h4>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed" x-text="email.snippet"></p>
                        </div>
                        <div class="flex flex-col items-end gap-3 min-w-[80px]">
                            <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase bg-slate-100 px-2 py-1 rounded-md" x-text="email.date"></span>
                            <div class="bg-[#9E6B73]/10 text-[#9E6B73] text-[10px] font-bold px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                                Select Email
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div id="booking-data-bridge"
        class="hidden"
        data-config='@json($this->config)'
        data-categories='@json($this->categories)'
        data-extras='@json($saved_extras ?? (object)[])'
        data-selected='@json($selected_products ?? [])'
        data-customers='@json($past_customers ?? [])'
        data-csrf="{{ csrf_token() }}"
        data-id="{{ $booking_id }}"
        data-invoice="{{ $invoice_number }}"
        data-token="{{ $form_token }}"
        data-extra-prices='@json($extraPrices)'>
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
        </div>
    </div>

    @vite(['resources/js/availability-sync.js', 'resources/js/new-booking.js'])

    @script
    <script>
        document.addEventListener('livewire:navigated', () => {
             if (typeof window.initBookingAppData === 'function') {
                 window.initBookingAppData();
             }
             setTimeout(() => {
                if (typeof checkRealTimeAvailability === 'function') {
                    checkRealTimeAvailability(true);
                }
                if (typeof window.triggerRecalculate === 'function') {
                    window.triggerRecalculate();
                }
                if (typeof window.startLivePolling === 'function') {
                    window.startLivePolling();
                }
             }, 500);
             window.lwBookingComponent = $wire;
             
             // Bridge UI actions to Livewire
             const originalToggleItemUI = window.toggleItemUI;
             window.toggleItemUI = function(checkbox, card) {
                 if (typeof originalToggleItemUI === 'function') {
                    originalToggleItemUI(checkbox, card);
                 }
                 if (window.lwBookingComponent) {
                     window.lwBookingComponent.toggleItem(card.dataset.name, checkbox.checked);
                 }
             };

             const originalSaveExtras = window.saveCurrentExtrasState;
             window.saveCurrentExtrasState = function(ignoreSync = false) {
                 if (typeof originalSaveExtras === 'function') {
                    originalSaveExtras(true);
                 }
                 if (!ignoreSync && window.lwBookingComponent) {
                     window.lwBookingComponent.syncExtras(window.bookingAppData.savedExtras);
                 }
             };
        });

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