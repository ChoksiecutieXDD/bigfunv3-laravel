<div x-data="bookingApp" class="min-h-screen flex flex-col relative pb-8 bg-[#F8FAFC]">
    <div class="fixed top-8 right-8 z-[999999] flex flex-col gap-3 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-12 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 translate-x-12 scale-95"
                class="pointer-events-auto min-w-[320px] max-w-[420px] bg-white border border-gray-100 rounded-2xl shadow-2xl p-4 flex items-start gap-3 border-l-4"
                :class="{'border-l-green-500': toast.type === 'success', 'border-l-red-500': toast.type === 'error', 'border-l-amber-500': toast.type === 'warning', 'border-l-[#9E6B73]': toast.type === 'primary'}">
                <span class="material-symbols-rounded text-xl mt-0.5"
                    :class="{'text-green-500': toast.type === 'success', 'text-red-500': toast.type === 'error', 'text-amber-500': toast.type === 'warning', 'text-[#9E6B73]': toast.type === 'primary'}"
                    x-text="toast.icon"></span>
                <div class="flex-1">
                    <h4 class="font-bold text-sm text-slate-800" x-text="toast.title"></h4>
                    <p class="text-xs text-slate-500 mt-1" x-text="toast.message"></p>
                </div>
                <button @click="toast.visible = false" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-rounded text-sm">close</span>
                </button>
            </div>
        </template>
    </div>

    <div class="flex w-full relative overflow-hidden">
        <main class="flex-1 pt-4 pb-16 px-0 max-w-[1600px] mx-auto w-full">

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
                            <a href="{{ route('admin.calendar') }}" wire:navigate class="bg-white hover:bg-gray-50 text-slate-600 p-2.5 rounded-xl border border-gray-200 transition shadow-sm flex items-center justify-center">
                                <span class="material-symbols-rounded text-2xl">arrow_back</span>
                            </a>
                            <div>
                                <h1 class="text-3xl font-extrabold text-[#1E293B]">New Booking</h1>
                                <p class="text-sm text-slate-500 font-medium mt-1 uppercase tracking-wide text-[10px]">Invoice: <span class="font-bold text-[#9D686E]">{{ $invoice_number }}</span></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <button type="button" @click="modals.history = true; filteredCustomers = previousCustomers; searchHistory = ''" class="btn-action bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 flex-1 sm:flex-none justify-center">
                                <span class="material-symbols-rounded mr-2 text-lg">history</span> Past Customer
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

                <div class="financial-panel">
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
                                    <label class="input-label text-slate-400 !ml-1">Payment Method</label>
                                    <div class="relative">
                                        <select id="main_payment_type" name="payment_type" x-model="paymentMethod" @change="triggerRecalculate()" class="input-dark appearance-none cursor-pointer">
                                            <option value="EFT">EFT / Bank Transfer</option>
                                            <option value="Card">Card (2.9% Fee)</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                    </div>
                                </div>
                            </div>

                            <div x-show="paymentMethod === 'Card'" x-collapse class="bg-slate-800/80 rounded-2xl p-5 border border-slate-700 mt-4 shadow-inner flex flex-col gap-4">
                                <h4 class="text-xs font-bold text-[#9E6B73] uppercase flex justify-between items-center"><span>Card Details</span><span class="material-symbols-rounded text-sm">lock</span></h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="relative">
                                        <select name="card_type" class="input-dark appearance-none !py-3 text-sm cursor-pointer">
                                            <option value="Credit Card">Credit Card</option>
                                            <option value="Debit Card">Debit Card</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded text-sm">expand_more</span></span>
                                    </div>
                                    <div class="relative">
                                        <select name="card_network" id="card_network" x-model="cardNetwork" class="input-dark appearance-none !py-3 text-sm cursor-pointer">
                                            <option value="Visa">Visa</option>
                                            <option value="Mastercard">Mastercard</option>
                                            <option value="AMEX">AMEX</option>
                                        </select>
                                        <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded text-sm">expand_more</span></span>
                                    </div>
                                </div>

                                <div class="relative">
                                    <input type="text" name="card_number" id="card_number" placeholder=" " maxlength="19" class="input-dark font-mono text-lg tracking-widest">
                                    <label class="input-floating-label">Card Number</label>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="relative">
                                        <input type="text" name="card_expiry" id="card_expiry" placeholder=" " maxlength="5" class="input-dark text-center font-mono">
                                        <label class="input-floating-label">MM/YY</label>
                                    </div>
                                    <div class="relative">
                                        <input type="text" name="card_cvv" placeholder=" " :maxlength="cardNetwork === 'AMEX' ? 4 : 3" class="input-dark text-center font-mono">
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

                            <div x-show="paymentStatus === 'Deposit Paid'" x-collapse class="mt-4">
                                <div class="relative">
                                    <input type="text" name="payment_reference" placeholder=" " class="input-dark">
                                    <label class="input-floating-label">Receipt / Transaction Ref ID</label>
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

                <div class="section-card">
                    <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                        <span class="material-symbols-rounded text-[#9E6B73] text-2xl">calendar_month</span>
                        <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Live Availability & Duration</h2>
                    </div>

                    <div class="w-full max-w-5xl mx-auto">
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
                        <div class="input-group">
                            <label class="input-label">Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="input-field" value="{{ substr($this->getVal('start_time'), 0, 5) }}" @change="calcDuration()">
                        </div>
                        <div class="input-group">
                            <label class="input-label">End Time</label>
                            <input type="time" name="end_time" id="end_time" class="input-field" value="{{ substr($this->getVal('end_time'), 0, 5) }}" @change="calcDuration()">
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

                <div class="section-card">
                    <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                        <span class="material-symbols-rounded text-[#9E6B73] text-2xl">person_pin</span>
                        <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Customer Info</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="input-group">
                            <label class="input-label">First Name <span class="text-red-500">*</span></label>
                            <input type="text" id="cust_first_name" name="customer_first_name" required class="input-field" value="{{ $this->getVal('customer_first_name') }}" @blur="checkDuplicates()">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Last Name</label>
                            <input type="text" id="cust_last_name" name="customer_last_name" class="input-field" value="{{ $this->getVal('customer_last_name') }}" @blur="checkDuplicates()">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="input-group">
                            <label class="input-label">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="customer_email_address" name="customer_email_address" required class="input-field" value="{{ $this->getVal('customer_email') }}" @blur="checkDuplicates()">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Mobile <span class="text-red-500">*</span></label>
                            <input type="tel" id="customer_phone_mobile" name="customer_phone_mobile" required class="input-field" value="{{ $this->getVal('customer_phone') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-100">
                        <div class="input-group">
                            <label class="input-label">Business / Org</label>
                            <input type="text" id="customer_organization" name="customer_organization" class="input-field" value="{{ $this->getVal('customer_organization') }}">
                        </div>
                        <div class="input-group">
                            <label class="input-label">ABN</label>
                            <input type="text" name="customer_abn" id="customer_abn" class="input-field" value="{{ $this->getVal('customer_abn') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="input-group">
                            <label class="input-label">Employer Name</label>
                            <input type="text" name="employer_name" id="employer_name" class="input-field" value="{{ $this->getVal('employer_name') }}">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Business Phone</label>
                            <input type="tel" name="customer_business_phone" id="customer_business_phone" class="input-field" value="{{ $this->getVal('customer_business_phone') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-100">
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

                <div class="section-card">
                    <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                        <span class="material-symbols-rounded text-[#9E6B73] text-2xl">local_shipping</span>
                        <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Logistics & Venue</h2>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Event Address <span class="text-red-500">*</span></label>
                        <input type="text" name="address_line_1" id="addr_line_1" required class="input-field" value="{{ $this->getVal('address_line_1') }}">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Business Address (Optional)</label>
                        <input type="text" name="business_address" id="business_address" class="input-field" value="{{ $this->getVal('business_address') }}">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-100">
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

                        <div x-show="deliveryZone === 'custom'" x-collapse>
                            <div class="input-group">
                                <label class="input-label text-[#9E6B73]">Manual Delivery Cost</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">$</span>
                                    <input type="number" id="delivery_area_manual" step="0.01" class="input-field input-with-icon" placeholder="0.00" value="{{ $this->getVal('delivery_cost') }}" @input="document.getElementById('delivery_cost').value = $el.value; triggerRecalculate();">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-100">
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
                        <label class="input-label mb-2">Delivery Attachments (Up to 5)</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @for ($i = 1; $i <= 5; $i++)
                                @php
                                $dbCol=($i===1) ? 'delivery_attachment' : 'delivery_attachment_' . $i;
                                $existingFile=$this->getVal($dbCol);
                                @endphp
                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col justify-center">
                                    <input type="file" name="delivery_attachment{{ $i > 1 ? "_$i" : "" }}" accept="image/png, image/jpeg, application/pdf" class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#9E6B73]/10 file:text-[#9E6B73] hover:file:bg-[#9E6B73]/20 cursor-pointer">
                                    @if ($existingFile)
                                    <a href="/storage/uploads/{{ $existingFile }}" target="_blank" class="text-xs font-bold text-[#9E6B73] hover:underline mt-2 flex items-center gap-1 view-attachment-link"><span class="material-symbols-rounded text-sm">open_in_new</span> View {{ $existingFile }}</a>
                                    @endif
                                </div>
                                @endfor
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
                                    <span class="cat-limit-badge text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold uppercase tracking-wide">Category Limit: {{ $catData['limit'] }}</span>
                                    @endif
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 px-2">
                                    @foreach ($catData['products'] as $product)
                                    @php
                                    $pName = $product['name'];
                                    $countsAgainst = !empty($product['counts_against']) ? $product['counts_against'] : $catName;
                                    $isChecked = in_array($pName, $selected_products);
                                    @endphp
                                    <label class="product-card group {{ $isChecked ? 'selected' : '' }}" data-name="{{ $pName }}" data-category="{{ $catName }}" data-counts-against="{{ $countsAgainst }}" data-daily-limit="{{ (int)$product['daily_limit'] }}" data-product-sold-out="false">
                                        <div class="flex justify-between items-start gap-2 mb-2 w-full">
                                            <div class="pr-2 w-full">
                                                <h4 class="font-bold text-slate-800 text-sm leading-snug group-hover:text-[#9E6B73]">{{ $pName }}</h4>
                                                <div class="mt-2 status-wrapper"><span class="status-badge status-checking">Checking...</span></div>
                                            </div>
                                            <div class="custom-checkbox"></div>
                                            <input type="checkbox" name="products[]" value="{{ $pName }}" class="ride-checkbox hidden" @change="handleSelection($event.target)" {{ $isChecked ? 'checked' : '' }}>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium action-text mt-auto">Click to select</div>
                                    </label>
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
                                <p class="flex justify-between"><span>Extras:</span> <span id="rev_ext_cost" class="font-bold text-white"></span></p>
                                <p class="flex justify-between"><span>Processing Fee:</span> <span id="rev_sur_cost" class="font-bold text-white"></span></p>
                                <div class="border-t border-slate-600 pt-3 mt-3"></div>
                                <p class="flex justify-between font-bold items-center"><span class="text-[#9E6B73] uppercase text-xs tracking-wider">Grand Total:</span> <span id="rev_total" class="text-2xl text-white"></span></p>
                                <div class="bg-slate-900 rounded-lg p-3 mt-3 border border-slate-700">
                                    <p class="flex justify-between text-xs items-center"><span>Deposit Status:</span> <span id="rev_status" class="font-bold uppercase px-2 py-1 rounded"></span></p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-600 pt-3 mt-3">
                            <h5 class="text-[10px] font-bold text-[#9E6B73] uppercase mb-2">Selected Extras</h5>
                            <ul id="rev_extras" class="text-xs text-slate-300 list-disc pl-4 space-y-1"></ul>
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
            <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-2 bg-slate-50">
                <template x-for="c in filteredCustomers">
                    <div class="p-3 bg-white border border-gray-100 rounded-xl hover:border-green-300 hover:bg-green-50 cursor-pointer flex justify-between items-center transition group" @click="fillCustomerDetails(c)">
                        <div>
                            <p class="font-bold text-slate-800" x-text="(c.customer_first_name + ' ' + (c.customer_last_name || '')).trim()"></p>
                            <p class="text-xs text-gray-500" x-text="(c.customer_organization || 'Private') + ' • ' + (c.customer_email || '')"></p>
                        </div>
                        <span class="text-xs font-bold text-green-600 opacity-0 group-hover:opacity-100 transition">Select</span>
                    </div>
                </template>
                <div x-show="filteredCustomers.length === 0" class="text-center p-4 text-gray-400">No customers found.</div>
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
    <div id="booking-data-bridge"
        class="hidden"
        data-config='@json($this->config)'
        data-categories='@json($this->categories)'
        data-extras='@json($this->saved_extras)'
        data-csrf="{{ csrf_token() }}"
        data-id="{{ $this->booking_id }}"
        data-invoice="{{ $this->invoice_number }}">
    </div>
</div>