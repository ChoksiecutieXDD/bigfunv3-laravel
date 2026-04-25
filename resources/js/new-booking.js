window.initBookingAppData = function () {
    const bridge = document.getElementById('booking-data-bridge');
    if (bridge) {
        window.bookingAppData = {
            config: bridge.dataset.config ? JSON.parse(bridge.dataset.config) : {},
            categories: bridge.dataset.categories ? JSON.parse(bridge.dataset.categories) : {},
            savedExtras: (bridge.dataset.extras && bridge.dataset.extras !== '[]') ? JSON.parse(bridge.dataset.extras) : {},
            csrfToken: bridge.dataset.csrf,
            bookingId: bridge.dataset.id,
            invoiceNumber: bridge.dataset.invoice,
            pastCustomers: bridge.dataset.customers ? JSON.parse(bridge.dataset.customers) : [],
            selectedItems: (bridge.dataset.selected && bridge.dataset.selected !== '[]' && bridge.dataset.selected !== 'null') ? JSON.parse(bridge.dataset.selected) : {},
        };
    }
};

// Initial run
window.initBookingAppData();

// --- GLOBAL STATE ---
let isProceeding = false;
let duplicateCheckTimer;
let calCursor = new Date();
let lastCalRenderMonth = -1;
let lastCalRenderYear = -1;
window.hasBookingDuplicates = false;

document.addEventListener('alpine:init', () => {
    Alpine.data('bookingApp', () => ({
        paymentType: 'EFT',
        paymentMethods: ['Direct Deposit', 'Bank Transfer', 'Osko', 'PayID'],
        paymentMethod: 'Direct Deposit',
        paymentStatus: 'Pending',
        deliveryZone: '',
        modals: {
            review: false,
            history: false,
            exit: false,
            reset: false,
            limitExceeded: false,
            saveConfirm: false,
            calendar: false,
            fullCapacityWarning: false,
            changeExtrasConfirm: false,
            fileSizeAlert: false,
            costIncrease: false,
            costDecrease: false,
            negativeBalance: false,
            costDelta: 0,
            saveDuplicateConfirm: false,
            removeConfirm: false,
            imagePreview: '',
            imagePreviewVisible: false,
        },
        itemToRemove: '',
        limitExceededCategory: '',
        limitExceededLimit: 0,
        previousCustomers: [],
        filteredCustomers: [],
        searchHistory: '',
        customerPage: 1,
        customerPageSize: 5,
        get paginatedCustomers() {
            let start = (this.customerPage - 1) * this.customerPageSize;
            let end = start + this.customerPageSize;
            return this.filteredCustomers.slice(start, end);
        },
        get totalCustomerPages() {
            return Math.ceil(this.filteredCustomers.length / this.customerPageSize) || 1;
        },
        cardNetwork: 'Visa',
        isDurationCustom: false,
        productDetails: {
            visible: false,
            name: '',
            spec: '',
            price: 0
        },

        openProductDetails(card) {
            this.productDetails.name = card.dataset.name;
            this.productDetails.spec = card.dataset.specification || '';
            this.productDetails.price = parseFloat(card.dataset.price || 0);
            this.productDetails.visible = true;
        },

        // Gmail State
        gmailModalVisible: false,
        previewSidebarVisible: false,
        isFetchingEmails: false,
        gmailSearchQuery: '',
        emailList: [],
        selectedEmailData: null,
        selectedEmailBody: '',
        previewZoomLevel: 1.0,

        updatePaymentMethods() {
            if (this.paymentType === 'EFT') {
                this.paymentMethods = ['Direct Deposit', 'Bank Transfer', 'Osko', 'PayID'];
                this.paymentMethod = 'Direct Deposit';
            } else if (this.paymentType === 'Cash') {
                this.paymentMethods = ['Cash Payment'];
                this.paymentMethod = 'Cash Payment';
            } else {
                // If Card Holder, we don't need sub-methods
                this.paymentMethods = ['Card Holder'];
                this.paymentMethod = 'Card Holder';
            }
            this.triggerRecalculate();
        },

        triggerRecalculate() {
            this.$nextTick(() => {
                if (typeof triggerRecalculate === 'function') triggerRecalculate();
            });
        },

        init() {
            // Initialize from bridge data if available
            if (window.bookingAppData) {
                this.previousCustomers = window.bookingAppData.pastCustomers || [];
                // If it's edit mode, we might want to pre-load some things
                setTimeout(() => {
                    if (typeof checkRealTimeAvailability === 'function') checkRealTimeAvailability();
                    if (typeof calLoad === 'function') calLoad();
                }, 100);
            }
        },

        filterCustomers() {
            const term = this.searchHistory.toLowerCase().trim();
            this.customerPage = 1; // Reset to first page on search
            if (!term) {
                this.filteredCustomers = this.previousCustomers;
                return;
            }
            this.filteredCustomers = this.previousCustomers.filter(c => {
                const name = (c.customer_first_name + ' ' + (c.customer_last_name || '')).toLowerCase();
                const email = (c.customer_email || '').toLowerCase();
                const phone = (c.customer_phone || '').toLowerCase();
                return name.includes(term) || email.includes(term) || phone.includes(term);
            });
        },

        fillCustomerDetails(c) {
            document.getElementById('cust_first_name').value = c.customer_first_name || '';
            document.getElementById('cust_last_name').value = c.customer_last_name || '';
            document.getElementById('customer_email_address').value = c.customer_email || '';
            document.getElementById('customer_phone_mobile').value = c.customer_phone || '';
            document.getElementById('customer_organization').value = c.customer_organization || '';
            document.getElementById('customer_abn').value = c.customer_abn || '';
            document.getElementById('employer_name').value = c.employer_name || '';
            document.getElementById('customer_business_phone').value = c.customer_business_phone || '';
            document.getElementById('addr_line_1').value = c.address_line_1 || '';
            document.getElementById('business_address').value = c.business_address || '';
            document.getElementById('addr_suburb').value = c.suburb || '';
            document.getElementById('addr_state').value = c.state || 'QLD';
            document.getElementById('addr_postcode').value = c.postcode || '';

            this.modals.history = false;
            showToast("Details Filled", "Customer information has been populated.", "success");

            // Trigger duplicate check asynchronously and toast if found
            setTimeout(async () => {
                const found = await checkDuplicates();
                if (found) {
                    showToast("Duplicate Detected", "This customer already has a booking/draft scheduled for the selected date.", "warning");
                }
            }, 100);
        },

        togglePaymentStatus() {
            this.paymentStatus = (this.paymentStatus === 'Pending') ? 'Deposit Paid' : 'Pending';
            this.$nextTick(() => {
                if (typeof triggerRecalculate === 'function') triggerRecalculate();
            });
        },

        // Gmail Functions
        async fetchEmails() {
            this.isFetchingEmails = true;
            this.emailList = [];
            this.selectedEmailData = null;
            this.selectedEmailBody = '';

            try {
                const url = '/google/fetch-emails' + (this.gmailSearchQuery ? '?q=' + encodeURIComponent(this.gmailSearchQuery) : '');
                const res = await fetch(url);
                const data = await res.json();

                if (data.error) {
                    if (typeof showToast !== 'undefined') showToast('Gmail Error', data.error, 'error');
                } else {
                    this.emailList = data;
                }
            } catch (err) {
                if (typeof showToast !== 'undefined') showToast('Network Error', 'Failed to fetch emails.', 'error');
            } finally {
                this.isFetchingEmails = false;
            }
        },

        selectEmail(email) {
            this.selectedEmailData = email;
            this.selectedEmailBody = email.body;
            this.previewSidebarVisible = true;
            this.gmailModalVisible = false;
        },

        extractEmailData() {
            if (!this.selectedEmailData) return;

            const email = this.selectedEmailData;

            if (email.name) {
                const parts = email.name.split(' ');
                const elFirst = document.getElementById('cust_first_name');
                const elLast = document.getElementById('cust_last_name');
                if (elFirst) elFirst.value = parts[0] || '';
                if (elLast) elLast.value = parts.slice(1).join(' ') || '';
            }

            const elEmail = document.getElementById('customer_email_address');
            if (elEmail) elEmail.value = email.email || '';

            const phoneMatch = email.body.match(/(?:\+?61|0)[2-478](?:[ \-]?[0-9]){8}/);
            if (phoneMatch) {
                const elPhone = document.getElementById('customer_phone_mobile');
                if (elPhone) elPhone.value = phoneMatch[0];
            }

            if (typeof showToast !== 'undefined') showToast("Data Extracted", "Customer details populated from quote.", "success");
            this.previewSidebarVisible = false;
        },

        performReset() {
            const form = document.getElementById('combinedBookingForm');
            if (form) form.reset();

            document.querySelectorAll('.ride-checkbox').forEach(cb => {
                cb.checked = false;
                const card = cb.closest('.product-card');
                if (card) card.classList.remove('selected');
            });

            const banner = document.getElementById('duplicateBanner');
            if (banner) banner.classList.add('hidden');

            if (typeof updateDynamicExtras === 'function') updateDynamicExtras();
            if (typeof calLoad === 'function') calLoad();

            this.paymentStatus = 'Pending';
            this.paymentType = 'EFT';
            this.updatePaymentMethods();
            this.modals.reset = false;
            showToast("Reset Complete", "Form reset successfully.", "success");
        },

    }));
});

// Bridge Global Vanilla JS to Alpine Toast
window.showToast = function (title, message, type = 'success') {
    window.dispatchEvent(new CustomEvent('notify', {
        detail: { title, message, type }
    }));
};

// --- API & HANDLERS ---


window.apiPost = async function (action, payload = null) {
    let fd = new FormData();
    if (payload) {
        for (let key in payload) fd.append(key, payload[key]);
    }
    fd.set('action', action);

    const res = await fetch('/api/bookings/handler', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.bookingAppData.csrfToken
        },
        body: fd
    });
    const text = await res.text();
    try {
        const data = JSON.parse(text);
        if (data.status === 'error') throw new Error(data.message || 'Server error');
        return data;
    } catch (err) {
        console.error("Raw response:", text);
        throw new Error("Server communication failed.");
    }
};

window.checkDuplicates = async function () {
    // We optionally use a timer for blur events, but for fillCustomerDetails we call it directly.
    const dateEl = document.getElementById('event_date');
    const fNameEl = document.getElementById('cust_first_name');
    const lNameEl = document.getElementById('cust_last_name');
    const invoiceEl = document.getElementById('invoice_number');

    const date = dateEl ? dateEl.value : '';
    const firstName = fNameEl ? fNameEl.value.trim() : '';
    const lastName = lNameEl ? lNameEl.value.trim() : '';
    const invoice = invoiceEl ? invoiceEl.value : '';

    if (date && firstName && lastName) {
        try {
            const data = await apiPost('check_duplicates', {
                date: date,
                first_name: firstName,
                last_name: lastName,
                current_invoice: invoice
            });

            const hasDupes = data.warnings && data.warnings.length > 0;
            window.hasBookingDuplicates = hasDupes;

            if (hasDupes) {
                const warningHtml = data.warnings.map(w => `<p>• ${w}</p>`).join('');

                // Main Form Banner
                const bannerBody = document.getElementById('duplicateBannerBody');
                if (bannerBody) bannerBody.innerHTML = warningHtml;
                const banner = document.getElementById('duplicateBanner');
                if (banner) banner.classList.remove('hidden');

                // Review Modal Banner
                const revBannerBody = document.getElementById('rev_duplicate_list');
                if (revBannerBody) revBannerBody.innerHTML = warningHtml;
                const revBanner = document.getElementById('rev_duplicate_warning');
                if (revBanner) revBanner.classList.remove('hidden');
            } else {
                const banner = document.getElementById('duplicateBanner');
                if (banner) banner.classList.add('hidden');

                const revBanner = document.getElementById('rev_duplicate_warning');
                if (revBanner) revBanner.classList.add('hidden');
            }
            return hasDupes;
        } catch (e) {
            console.error("Duplicate check failed", e);
        }
    } else {
        window.hasBookingDuplicates = false;
        const banner = document.getElementById('duplicateBanner');
        if (banner) banner.classList.add('hidden');
        const revBanner = document.getElementById('rev_duplicate_warning');
        if (revBanner) revBanner.classList.add('hidden');
    }
    return false;
};

function fmtDate(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

window.calLoad = async function () {
    const s = new Date(calCursor.getFullYear(), calCursor.getMonth(), 1);
    const e = new Date(calCursor.getFullYear(), calCursor.getMonth() + 1, 0);
    const labelEl = document.getElementById('calLabel');
    if (labelEl) labelEl.innerText = s.toLocaleString('default', {
        month: 'long',
        year: 'numeric'
    });

    try {
        const res = await apiPost('get_calendar_slots', {
            start: fmtDate(s),
            end: fmtDate(e),
            booking_id: window.bookingAppData ? window.bookingAppData.bookingId : ''
        });
        const grid = document.getElementById('calGrid');
        if (!grid) return;

        grid.innerHTML = '';
        for (let i = 0; i < s.getDay(); i++) grid.innerHTML += '<div></div>';

        const todayKey = fmtDate(new Date());
        const dateInput = document.getElementById('event_date');
        const currentVal = dateInput ? dateInput.value : '';
        const dailyLimit = res.daily_limit || 7;

        const summaryEl = document.getElementById('calSummary');
        if (summaryEl) summaryEl.innerText = `Daily limit: ${dailyLimit} bookings`;

        for (let d = 1; d <= e.getDate(); d++) {
            let dStr = `${calCursor.getFullYear()}-${String(calCursor.getMonth() + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            let used = (res.counts && res.counts[dStr]) ? parseInt(res.counts[dStr]) : 0;
            let left = Math.max(0, dailyLimit - used);

            let bg = left === 0 ? 'bg-red-50 border-red-200 text-red-500 opacity-60' : (left <= 2 ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700');
            let todayBadge = todayKey === dStr ? '<span class="absolute top-2 right-2 w-3 h-3 rounded-full bg-[#9E6B73]/40"></span>' : '';

            grid.innerHTML += `<div class="cal-day ${bg} p-4 sm:p-6 min-h-[80px] sm:min-h-[100px] border rounded-2xl cursor-pointer flex flex-col items-center justify-center relative hover:shadow-md transition-all duration-300" 
                data-date="${dStr}"
                onclick="selectDate('${dStr}', ${left})">
                ${todayBadge}
                <span class="font-extrabold text-xl sm:text-3xl leading-none mb-2">${d}</span>
                <span class="text-[10px] sm:text-xs font-bold bg-white/80 px-2 py-1 rounded-full leading-none shadow-sm">${left === 0 ? 'Full' : left + ' Left'}</span>
            </div>`;
        }
    } catch (e) {
        console.error("Calendar Load Error:", e);
        const summaryEl = document.getElementById('calSummary');
        if (summaryEl) summaryEl.innerText = "Failed to load slots.";
    }

    // Always sync selection after grid is built
    window.updateCalSelection();
};

window.updateCalSelection = function () {
    const dateInput = document.getElementById('event_date');
    const currentVal = dateInput ? dateInput.value : '';

    document.querySelectorAll('.cal-day').forEach(day => {
        // Clear ALL possible ring/border/scale classes
        day.classList.remove(
            'ring-2', 'ring-4', 'ring-[#9E6B73]', 'ring-[#9D686E]',
            'ring-offset-2', 'ring-offset-4',
            'border-[#9E6B73]', 'border-[#9D686E]',
            'scale-110', 'shadow-xl', 'z-10'
        );

        if (day.dataset.date === currentVal) {
            day.classList.add('ring-4', 'ring-[#9D686E]', 'ring-offset-4', 'border-[#9D686E]', 'scale-110', 'shadow-xl', 'z-10');
        }
    });
};

window.calPrev = function () {
    calCursor.setMonth(calCursor.getMonth() - 1);
    calLoad();
};

window.calNext = function () {
    calCursor.setMonth(calCursor.getMonth() + 1);
    calLoad();
};

window.selectDate = function (dateStr, left) {
    if (left <= 0) {
        showToast("Sold Out", "This date is fully booked.", "error");
        return;
    }
    const dateInput = document.getElementById('event_date');
    if (dateInput) dateInput.value = dateStr;
    updateCalSelection();
    dateChanged();
    showToast("Date Selected", "Date updated to " + dateStr, "success");
};

window.dateChanged = function () {
    checkRealTimeAvailability();
    checkDuplicates(); // Re-verify duplicates whenever the date changes
};





window.handleSelection = function (checkbox) {
    const card = checkbox.closest('.product-card');
    const dateVal = document.getElementById('event_date')?.value;

    if (!dateVal) {
        checkbox.checked = false;
        showToast("Select Date First", "Please select a booking date before choosing an attraction.", "warning");
        return;
    }

    const isSoldOut = card.dataset.productSoldOut === 'true';
    const appEl = document.querySelector('[x-data="bookingApp"]');

    // Check if Alpine instance is ready
    let alpine = null;
    if (appEl && appEl.__x) {
        alpine = appEl.__x.$data;
    } else if (appEl && appEl._x_dataStack) {
        alpine = appEl._x_dataStack[0];
    }

    if (checkbox.checked && isSoldOut) {
        checkbox.checked = false;
        if (alpine) alpine.modals.fullCapacityWarning = true;
        return;
    }

    // IN NEW BOOKING: REMOVE IMMEDIATELY WITHOUT MODAL
    if (!checkbox.checked) {
        processSelection(checkbox, card);
        return;
    }

    processSelection(checkbox, card);
};

function processSelection(checkbox, card) {
    const limitCategory = (card.dataset.countsAgainst || '').trim().toLowerCase();
    const categories = window.bookingAppData.categories;
    const actionText = card.querySelector('.action-text');
    const name = (card.dataset.name || '').toLowerCase().trim();

    if (checkbox.checked) {
        let catLimit = 0;
        for (let key in categories) {
            if (key.trim().toLowerCase() === limitCategory) {
                catLimit = categories[key].limit;
                break;
            }
        }

        if (limitCategory && catLimit > 0) {
            let count = (window.globalCategoryBooked ? (window.globalCategoryBooked[limitCategory] || 0) : 0);
            document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
                if ((cb.closest('.product-card').dataset.countsAgainst || '').trim().toLowerCase() === limitCategory) count++;
            });
            // Also count Extras
            document.querySelectorAll('.ext-price').forEach(el => {
                const elCat = (el.dataset.countsAgainst || '').trim().toLowerCase();
                if (elCat === limitCategory) {
                    const isSelected = (el.type === 'checkbox' ? el.checked : (el.value !== '' && el.value !== '0' && !el.value.includes('|no')));
                    if (isSelected) count++;
                }
            });

            if (count > catLimit) {
                showToast("Limit Reached", `Max ${catLimit} items for ${limitCategory}.`, "warning");
                checkbox.checked = false;
                card.classList.remove('selected');
                if (actionText) actionText.innerText = 'Click to select';
                return;
            }
        }
        card.classList.add('selected');
        if (actionText) actionText.innerText = 'Selected';
        // Add to selectedItems for sync
        if (window.bookingAppData) window.bookingAppData.selectedItems[name] = 1;
    } else {
        card.classList.remove('selected');
        if (actionText) actionText.innerText = 'Click to select';
        // Remove from selectedItems for sync
        if (window.bookingAppData) delete window.bookingAppData.selectedItems[name];
    }

    try {
        if (typeof updateCategoryLimitsUI === 'function') updateCategoryLimitsUI();
        if (typeof updateDynamicExtras === 'function') updateDynamicExtras();
        if (typeof saveCurrentExtrasState === 'function') saveCurrentExtrasState(true);
        if (typeof triggerRecalculate === 'function') triggerRecalculate();

        // Communicate to Livewire
        if (window.lwBookingComponent) {
            window.lwBookingComponent.toggleItem(card.dataset.name, checkbox.checked);
        } else {
            const lwEl = document.querySelector('[wire\\:id]');
            if (lwEl && window.Livewire) {
                const lwComp = window.Livewire.find(lwEl.getAttribute('wire:id'));
                if (lwComp) {
                    lwComp.toggleItem(card.dataset.name, checkbox.checked);
                }
            }
        }
    } catch (error) {
        console.error("Selection sync error:", error);
        showToast("Sync Issue", "Could not fully update selection. Please refresh.", "error");
    }
};


window.updateItemQty = function (itemName, change) {
    const lwEl = document.querySelector('[wire\\:id]');
    if (lwEl && window.Livewire) {
        const lwComp = window.Livewire.find(lwEl.getAttribute('wire:id'));
        if (lwComp) {
            lwComp.updateItemQty(itemName, change);
        }
    }
    // Need a tiny delay for backend calculation before UI update
    setTimeout(() => triggerRecalculate(), 150);
};

window.handleExtraSelection = function (element) {
    const dateVal = document.getElementById('event_date')?.value;
    const isSelected = (element.type === 'checkbox' ? element.checked : (element.value !== '' && element.value !== '0' && !element.value.includes('|no')));

    if (isSelected && !dateVal) {
        if (element.type === 'checkbox') element.checked = false;
        else element.value = (element.tagName === 'SELECT') ? '' : '0';
        showToast("Select Date First", "Please select a booking date before choosing this extra.", "warning");
        return;
    }

    const limitCategory = (element.dataset.countsAgainst || '').trim().toLowerCase();
    const categories = window.bookingAppData.categories;

    if (isSelected) {
        let catLimit = 0;
        for (let key in categories) {
            if (key.trim().toLowerCase() === limitCategory) {
                catLimit = categories[key].limit;
                break;
            }
        }

        if (limitCategory && catLimit > 0) {
            let currentUsage = (globalCategoryBooked[limitCategory] || 0);

            document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
                const cbCat = (cb.closest('.product-card').dataset.countsAgainst || '').trim().toLowerCase();
                if (cbCat === limitCategory) {
                    currentUsage++;
                }
            });

            document.querySelectorAll('.ext-price[data-counts-against]').forEach(el => {
                const elCat = (el.dataset.countsAgainst || '').trim().toLowerCase();
                if (el !== element && elCat === limitCategory) {
                    if (el.type === 'checkbox' && el.checked) currentUsage++;
                    else if (el.tagName === 'SELECT' && el.value !== '' && el.value !== '0' && !el.value.includes('|no')) currentUsage++;
                }
            });

            if (currentUsage + 1 > catLimit) {
                const appEl = document.querySelector('[x-data="bookingApp"]');
                const alpine = appEl ? (appEl._x_dataStack ? appEl._x_dataStack[0] : (appEl.__x ? appEl.__x.$data : null)) : null;
                if (alpine) {
                    alpine.limitExceededCategory = limitCategory;
                    alpine.limitExceededLimit = catLimit;
                    alpine.modals.limitExceeded = true;
                } else {
                    showToast("Limit Reached", `Max ${catLimit} items for ${limitCategory}.`, "warning");
                }

                if (element.type === 'checkbox') {
                    element.checked = false;
                } else {
                    element.value = '';
                }
                return;
            }
        }
    }

    triggerRecalculate();
    saveCurrentExtrasState();
    updateCategoryLimitsUI();
};

window.finalizeBooking = async function () {
    if (isProceeding) return;
    isProceeding = true;
    const btn = document.getElementById('btnSaveFinal');
    const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-rounded animate-spin">sync</span> Saving...';
    btn.disabled = true;

    const form = document.getElementById('combinedBookingForm');
    const fd = new FormData(form);

    // --- FILE SIZE VALIDATION (Max 5MB Total) ---
    let totalSize = 0;
    let fileCount = 0;
    for (let [key, value] of fd.entries()) {
        if (value instanceof File && value.size > 0 && key.startsWith('delivery_attachment')) {
            totalSize += value.size;
            fileCount++;
        }
    }

    if (totalSize > 5 * 1024 * 1024) {
        showToast("Storage Limit", "Total size of all attachments must not exceed 5MB. Current: " + (totalSize / (1024 * 1024)).toFixed(2) + "MB", "error");
        isProceeding = false;
        return;
    }

    fd.append('action', 'save_full_booking');

    // For Edit Mode, we rely on the backend sync
    if (window.bookingAppData && window.bookingAppData.bookingId) {
        // Just proceed, backend has the totals
    } else {
        // Ensure we have a final total even if override is empty (New Booking mode)
        const override = document.getElementById('override_total').value;
        if (!override) {
            const totalDisp = document.getElementById('disp_total').innerText.replace('$', '');
            fd.set('final_total', totalDisp);
        }
    }

    try {
        const res = await fetch('/api/bookings/handler', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.bookingAppData.csrfToken },
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            showToast("Success", "Booking has been saved successfully!", "success");
            setTimeout(() => window.location.href = '/enquiries', 1500);
        } else {
            throw new Error(data.message || "Failed to save.");
        }
    } catch (e) {
        showToast("Error", e.message, "error");
        btn.innerHTML = oldText;
        btn.disabled = false;
        isProceeding = false;
    }
};

window.checkTotalAttachmentSize = function (currentInput) {
    const inputs = document.querySelectorAll('input[type="file"]');
    let total = 0;
    inputs.forEach(input => {
        // Look for any input that looks like a delivery attachment
        if (input.name.includes('delivery_attachment') || input.getAttribute('wire:model')?.includes('newAttachments')) {
            if (input.files && input.files[0]) {
                total += input.files[0].size;
            }
        }
    });

    if (total > 5 * 1024 * 1024) {
        showToast("Storage Limit", "Total size of all new attachments must not exceed 5MB. Current: " + (total / (1024 * 1024)).toFixed(2) + "MB", "error");
        if (currentInput) currentInput.value = ""; // Clear the input that caused the overflow

        // If we have access to Alpine modals (Edit mode)
        if (window.bookingApp && typeof window.bookingApp.modals !== 'undefined') {
            // We could trigger the internal modal if we wanted, but toast is enough
        }

        return false;
    }
    return true;
};

window.filterRides = function () {
    const searchEl = document.getElementById('rideSearch');
    if (!searchEl) return;
    const term = searchEl.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        if (card.dataset.name.toLowerCase().includes(term)) {
            card.parentElement.style.display = 'block';
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
};


window.calcDuration = function () {
    const sEl = document.getElementById('start_time');
    const eEl = document.getElementById('end_time');
    if (!sEl || !eEl) return;

    const s = sEl.value;
    const e = eEl.value;
    if (!s || !e) return;

    const d1 = new Date(`1970-01-01T${s}`);
    const d2 = new Date(`1970-01-01T${e}`);

    let diff = (d2 - d1) / 36e5;
    if (diff < 0) diff += 24;

    if (diff > 0) {
        const radios = document.querySelectorAll('input[name="duration"]');
        let best = null;
        let minDiff = Infinity;

        radios.forEach(r => {
            const h = parseFloat(r.getAttribute('data-hours'));
            if (!isNaN(h)) {
                const delta = Math.abs(diff - h);
                if (delta < minDiff) {
                    minDiff = delta;
                    best = r;
                }
            }
        });

        const customCard = document.getElementById('dur_card_custom');
        // If "Custom/Manual" is already active, don't let auto-calculation override it
        if (customCard && customCard.classList.contains('duration-active')) return;

        if (best && minDiff < 1.5) {
            best.checked = true;
            selectDurationCard(best.closest('.duration-card'));
        }
    }
};

window.selectDurationCard = function (labelEl) {
    if (!labelEl) return;
    document.querySelectorAll('.duration-card').forEach(r => r.classList.remove('duration-active'));
    labelEl.classList.add('duration-active');

    const input = labelEl.querySelector('input');
    if (!input) return;
    input.checked = true;

    const wrapper = document.getElementById('customDurationWrapper');
    const durCostInput = document.getElementById('duration_cost');
    const manualCost = document.getElementById('manual_duration_cost');

    const el = document.querySelector('[x-data="bookingApp"]');
    const app = el && el._x_dataStack ? el._x_dataStack[0] : null;

    if (input.value === 'custom') {
        if (wrapper) wrapper.classList.remove('hidden');
        if (app) app.isDurationCustom = true;
        if (durCostInput && manualCost && manualCost.value !== "") {
            durCostInput.value = manualCost.value;
        }
    } else {
        if (wrapper) wrapper.classList.add('hidden');
        if (app) app.isDurationCustom = false;
        if (durCostInput) durCostInput.value = input.dataset.price || 0;
    }
    triggerRecalculate();
};

window.updateDurationCost = function (radio) {
    if (radio.checked) {
        const durCostInput = document.getElementById('duration_cost');
        if (durCostInput) durCostInput.value = radio.dataset.price;
        selectDurationCard(radio.closest('.duration-card'));
    }
};

window.updateDeliveryCost = function (sel) {
    const manual = document.getElementById('delivery_area_manual');
    const delCostInput = document.getElementById('delivery_cost');
    if (!delCostInput) return;

    if (sel.value === 'custom') {
        // If manual input has a value, use it, otherwise keep current OR fallback to 0
        if (manual && manual.value !== "") {
            delCostInput.value = manual.value;
        } else {
            // Only set to 0 if it was truly empty
            if (delCostInput.value === "") delCostInput.value = 0;
        }
    } else {
        if (sel.selectedIndex >= 0 && sel.options[sel.selectedIndex]) {
            const price = sel.options[sel.selectedIndex].getAttribute('data-price');
            if (price !== null) delCostInput.value = price;
        } else {
            // Don't reset to 0 if we already have a value and the selection is just "loading"
            if (sel.value === "" && delCostInput.value == 0) delCostInput.value = 0;
        }
    }
    triggerRecalculate();
};

window.triggerRecalculate = function () {
    // Force calculation even in edit mode to satisfy wire:ignore breakdown panels

    const durCostInput = document.getElementById('duration_cost');
    let durCost = durCostInput ? (parseFloat(durCostInput.value) || 0) : 0;
    const breakDur = document.getElementById('breakdown_dur');
    if (breakDur) breakDur.innerText = '$' + durCost.toFixed(2);

    const delCostInput = document.getElementById('delivery_cost');
    let delCost = delCostInput ? (parseFloat(delCostInput.value) || 0) : 0;
    const breakDel = document.getElementById('breakdown_del');
    if (breakDel) breakDel.innerText = '$' + delCost.toFixed(2);

    let attractionsCost = 0;
    document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
        const card = cb.closest('.product-card');
        if (card) {
            const qtyInput = card.querySelector('input[readonly].w-8'); // Match the quantity input in Edit mode
            const q = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;
            attractionsCost += (parseFloat(card.dataset.price || 0) * q);
        }
    });
    const breakAttractions = document.getElementById('breakdown_attractions');
    if (breakAttractions) breakAttractions.innerText = '$' + attractionsCost.toFixed(2);

    let extCost = 0;
    document.querySelectorAll('.ext-price').forEach(el => {
        if (el.tagName === 'SELECT') {
            let val = el.value;
            if (val.includes('|')) {
                extCost += parseFloat(val.split('|')[0] || 0);
            } else {
                extCost += parseFloat(el.options[el.selectedIndex].dataset.price || 0);
            }
        }
        if (el.tagName === 'INPUT' && el.checked) {
            extCost += parseFloat(el.dataset.price || 0);
        }
    });
    const breakExt = document.getElementById('breakdown_ext');
    if (breakExt) breakExt.innerText = '$' + extCost.toFixed(2);

    let sub = durCost + delCost + extCost + attractionsCost;
    const calcSub = document.getElementById('calc_subtotal');
    if (calcSub) calcSub.value = sub.toFixed(2);

    calculateFinalTotals();
    updateCategoryLimitsUI();
};

window.calculateFinalTotals = function () {
    // Force calculation even in edit mode to satisfy wire:ignore breakdown panels

    const calcSubInput = document.getElementById('calc_subtotal');
    let sub = calcSubInput ? (parseFloat(calcSubInput.value) || 0) : 0;

    // Extract from Alpine
    const el = document.querySelector('[x-data="bookingApp"]');
    let type = 'EFT';
    if (el && el._x_dataStack) {
        type = el._x_dataStack[0].paymentType;
    }

    let rate = (type === 'Card Holder') ? 0.029 : 0;
    let sur = sub * rate;
    let tot = sub + sur;

    const ovInput = document.getElementById('override_total');
    let ov = ovInput ? ovInput.value : "";
    if (ov !== "") tot = parseFloat(ov);

    const surLabel = document.getElementById('surcharge_label');
    if (surLabel) surLabel.innerText = `Processing Fee (${(rate * 100).toFixed(1)}%)`;

    const dispSur = document.getElementById('disp_surcharge');
    if (dispSur) dispSur.innerText = '$' + sur.toFixed(2);

    const dispTot = document.getElementById('disp_total');
    if (dispTot) dispTot.innerText = '$' + tot.toFixed(2);

    const dispDep = document.getElementById('disp_deposit');
    if (dispDep) dispDep.innerText = '$' + (tot / 2).toFixed(2);

    const surAmount = document.getElementById('surcharge_amount');
    if (surAmount) surAmount.value = sur.toFixed(2);

    const depAmount = document.getElementById('deposit_amount');
    if (depAmount) depAmount.value = (tot / 2).toFixed(2);
};

window.openReviewModal = async function () {
    // Re-verify duplicates before opening modal to ensure state is fresh
    await checkDuplicates();

    const dateEl = document.getElementById('event_date');
    const startEl = document.getElementById('start_time');
    const endEl = document.getElementById('end_time');
    const fNameEl = document.getElementById('cust_first_name');
    const emailEl = document.getElementById('customer_email_address');
    const phoneEl = document.getElementById('customer_phone_mobile');
    const addrEl = document.getElementById('addr_line_1');

    const date = dateEl ? dateEl.value : '';
    const startTime = startEl ? startEl.value : '';
    const endTime = endEl ? endEl.value : '';
    const fName = fNameEl ? fNameEl.value : '';
    const email = emailEl ? emailEl.value : '';
    const phone = phoneEl ? phoneEl.value : '';
    const addr = addrEl ? addrEl.value : '';

    const el = document.querySelector('[x-data="bookingApp"]');
    const alpine = el && el._x_dataStack ? el._x_dataStack[0] : { deliveryZone: '', modals: { review: false } };
    const delZone = alpine.deliveryZone;

    let missing = [];
    const appEl = document.querySelector('[x-data="bookingApp"]');
    const app = appEl && appEl._x_dataStack ? appEl._x_dataStack[0] : null;
    const isCustom = app ? app.isDurationCustom : false;

    if (!date) missing.push("Event Date");
    if (!isCustom) {
        if (!startTime) missing.push("Start Time");
        if (!endTime) missing.push("End Time");
    }
    if (!fName) missing.push("First Name");
    if (!email) missing.push("Email");
    if (!phone) missing.push("Mobile");
    if (!addr) missing.push("Address");
    if (delZone === "") missing.push("Delivery Zone");

    if (missing.length > 0) {
        const warningBox = document.getElementById('rev_missing_warning');
        const listEl = document.getElementById('rev_missing_list');
        if (warningBox && listEl) {
            listEl.innerHTML = missing.map(m => `<li>${m}</li>`).join('');
            warningBox.classList.remove('hidden');
        }
    } else {
        const warningBox = document.getElementById('rev_missing_warning');
        if (warningBox) warningBox.classList.add('hidden');
    }

    const checkedRides = document.querySelectorAll('.ride-checkbox:checked');
    if (checkedRides.length === 0) {
        showToast("No Attractions", "Please select at least one attraction to proceed.", "error");
        return;
    }

    const lNameEl = document.getElementById('cust_last_name');
    const lName = lNameEl ? lNameEl.value : '';

    document.getElementById('rev_name').innerText = (fName + " " + lName).trim() || "Not Provided";
    document.getElementById('rev_email').innerText = email || "Not Provided";
    document.getElementById('rev_phone').innerText = phone || "Not Provided";
    document.getElementById('rev_org').innerText = (document.getElementById('customer_organization') || {}).value || "N/A";
    document.getElementById('rev_abn').innerText = (document.getElementById('customer_abn') || {}).value || "N/A";
    document.getElementById('rev_employer').innerText = (document.getElementById('employer_name') || {}).value || "N/A";
    document.getElementById('rev_biz_phone').innerText = (document.getElementById('customer_business_phone') || {}).value || "N/A";

    document.getElementById('rev_date').innerText = date || "TBD / Not Provided";
    document.getElementById('rev_op_hours').innerText = (document.getElementById('operational_hours') || {}).value || "N/A";


    document.getElementById('rev_event_type').innerText = (document.getElementById('event_type') || {}).value || "N/A";
    document.getElementById('rev_people').innerText = (document.getElementById('expected_people') || {}).value || "N/A";

    const subEl = document.getElementById('addr_suburb');
    const stEl = document.getElementById('addr_state');
    const pcEl = document.getElementById('addr_postcode');
    document.getElementById('rev_address').innerText = addr ? (addr + "\n" + (subEl ? subEl.value : '') + " " + (stEl ? stEl.value : '') + " " + (pcEl ? pcEl.value : '')) : "Not Provided";

    document.getElementById('rev_biz_address').innerText = (document.getElementById('business_address') || {}).value || "N/A";
    document.getElementById('rev_del_notes').innerText = (document.getElementById('note_delivery') || {}).value || "None provided";
    document.getElementById('rev_cust_notes').innerText = (document.getElementById('notes_customer') || {}).value || "None provided";

    document.getElementById('rev_operator').innerText = (document.getElementById('lead_operator') || {}).value || "Team (Default)";
    document.getElementById('rev_deliverer').innerText = (document.getElementById('lead_deliverer') || {}).value || "Team (Default)";
    
    // Duration Label & Cost
    let durPrice = document.getElementById('breakdown_dur').innerText;
    let durLabel = "";
    const activeDurCard = document.querySelector('.duration-card.duration-active');
    if (activeDurCard) {
        const rad = activeDurCard.querySelector('input[name="duration"]');
        if (rad) {
            if (rad.value === 'custom') {
                durLabel = (document.getElementById('custom_duration_text') || {}).value || "Custom Duration";
            } else {
                durLabel = rad.value;
            }
        }
    }
    document.getElementById('rev_dur_cost').innerText = durLabel ? (durLabel + " (" + durPrice + ")") : durPrice;

    // Time string adjustment for custom
    let timeStr = "TBD - TBD";
    if (isCustom && durLabel) {
        timeStr = durLabel;
        if (startTime || endTime) {
            timeStr += " [" + (startTime || "TBD") + " - " + (endTime || "TBD") + "]";
        }
    } else if (startTime || endTime) {
        timeStr = (startTime || "TBD") + " - " + (endTime || "TBD");
    }
    document.getElementById('rev_time').innerText = timeStr;

    let displayDelZone = document.getElementById('breakdown_del').innerText;
    if (delZone === "custom") {
        displayDelZone += " (Custom Quote)";
    } else if (delZone !== "") {
        displayDelZone += " (" + delZone + ")";
    } else {
        displayDelZone += " (TBD / Not Selected)";
    }
    document.getElementById('rev_del_cost').innerText = displayDelZone;

    document.getElementById('rev_ext_cost').innerText = document.getElementById('breakdown_ext').innerText;
    document.getElementById('rev_attractions_cost').innerText = document.getElementById('breakdown_attractions').innerText;
    document.getElementById('rev_sur_cost').innerText = document.getElementById('disp_surcharge').innerText;

    const totalRaw = document.getElementById('disp_total').innerText;
    document.getElementById('rev_total').innerText = totalRaw;

    // Handle Card Masking in Review
    const cardInput = document.getElementById('card_number');
    const cardMaskedEl = document.getElementById('rev_card_masked');
    if (cardMaskedEl) {
        if (alpine.paymentType === 'Card Holder' && cardInput && cardInput.value.trim() !== "") {
            const raw = cardInput.value.replace(/\s/g, '');
            const last4 = raw.slice(-4);
            const mid4 = raw.slice(-8, -4);
            cardMaskedEl.innerText = "**** **** " + (mid4 || "****") + " " + (last4 || "****");
        } else {
            cardMaskedEl.innerText = "N/A";
        }
    }

    const payStatus = alpine.paymentStatus;
    const totalNum = parseFloat(totalRaw.replace(/[^0-9.-]+/g, "")) || 0;
    let depositNum = 0;

    if (payStatus === 'Deposit Paid') {
        depositNum = totalNum * 0.5; // Assuming 50% deposit as per UI label
    }

    const balanceDue = totalNum - depositNum;
    document.getElementById('rev_deposit_paid').innerText = "$" + depositNum.toFixed(2);
    document.getElementById('rev_balance_due').innerText = "$" + balanceDue.toFixed(2);

    // Handle Receipt ID
    const receiptWrapper = document.getElementById('rev_receipt_wrapper');
    const receiptInput = document.querySelector('input[name="payment_reference"]');
    if (payStatus === 'Deposit Paid' && receiptInput && receiptInput.value.trim() !== "") {
        document.getElementById('rev_receipt_id').innerText = receiptInput.value.trim();
        if (receiptWrapper) receiptWrapper.classList.remove('hidden');
    } else {
        if (receiptWrapper) receiptWrapper.classList.add('hidden');
    }

    const statusEl = document.getElementById('rev_status');
    statusEl.innerText = payStatus;
    statusEl.className = payStatus === 'Pending' ? 'font-bold uppercase px-2 py-1 rounded bg-amber-500/20 text-amber-400' : 'font-bold uppercase px-2 py-1 rounded bg-green-500/20 text-green-400';

    const attractionList = document.getElementById('rev_attractions');
    attractionList.innerHTML = '';

    // 1. Add Rides
    checkedRides.forEach(cb => {
        attractionList.innerHTML += `<li><span class="material-symbols-rounded text-[#9E6B73] text-sm align-middle mr-1">check_circle</span>${cb.value}</li>`;
    });

    // 2. Add Extras (Grouped with Attractions)
    document.querySelectorAll('.ext-price').forEach(el => {
        if (el.tagName === 'SELECT') {
            const opt = el.options[el.selectedIndex];
            const price = parseFloat(opt.dataset.price || 0);
            if (price > 0 || el.value.includes('|yes')) {
                const label = opt.textContent;
                attractionList.innerHTML += `<li><span class="material-symbols-rounded text-green-500 text-sm align-middle mr-1">add_circle</span>${escapeHTML(label)}</li>`;
            }
        } else if (el.tagName === 'INPUT' && el.checked) {
            const label = el.nextElementSibling.textContent;
            const price = parseFloat(el.dataset.price || 0);
            attractionList.innerHTML += `<li><span class="material-symbols-rounded text-green-500 text-sm align-middle mr-1">add_circle</span>${escapeHTML(label)} (+$${price.toFixed(2)})</li>`;
        }
    });

    const revAttachments = document.getElementById('rev_attachments');
    revAttachments.innerHTML = '';
    let hasFiles = false;
    for (let i = 1; i <= 5; i++) {
        const suffix = i > 1 ? `_${i}` : '';
        const input = document.querySelector(`input[name="delivery_attachment${suffix}"]`);

        if (input && input.files && input.files.length > 0) {
            revAttachments.innerHTML += `<li><span class="text-[#9E6B73] font-bold">New:</span> ${escapeHTML(input.files[0].name)}</li>`;
            hasFiles = true;
        } else if (input) {
            // Robust search for existing files in the new Bento UI
            const slot = input.closest('.group');
            const fname = slot ? slot.getAttribute('data-filename') : null;

            if (fname) {
                revAttachments.innerHTML += `<li><span class="text-slate-500 font-bold">Saved:</span> ${escapeHTML(fname)}</li>`;
                hasFiles = true;
            }
        }
    }
    if (!hasFiles) {
        revAttachments.innerHTML = '<li class="text-slate-400 italic">No attachments added.</li>';
    }

    const warningBox = document.getElementById('rev_missing_warning');
    const warningList = document.getElementById('rev_missing_list');

    if (missing.length > 0) {
        if (warningList) warningList.innerHTML = missing.map(m => `<li>${m}</li>`).join('');
        if (warningBox) warningBox.classList.remove('hidden');
    } else {
        if (warningBox) warningBox.classList.add('hidden');
    }

    // Sync Duplicate Banner for Review Modal
    const revDupBanner = document.getElementById('rev_duplicate_warning');
    if (revDupBanner) {
        if (window.hasBookingDuplicates) {
            revDupBanner.classList.remove('hidden');
        } else {
            revDupBanner.classList.add('hidden');
        }
    }

    alpine.modals.review = true;
};

function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

window.finalizeBooking = async function () {
    const btn = document.getElementById('btnSaveFinal');
    if (!btn) return;
    btn.innerHTML = '<span class="material-symbols-rounded animate-spin">refresh</span> Processing...';
    btn.disabled = true;

    try {
        const form = document.getElementById('combinedBookingForm');

        // --- 5MB Combined File Size Check ---
        let totalSize = 0;
        const fileInputs = form.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            if (input.files && input.files[0]) {
                totalSize += input.files[0].size;
            }
        });

        if (totalSize > 5 * 1024 * 1024) {
            showToast("Error", "Total attachment size exceeds 5MB limit.", "error");
            btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> Confirm & Save Booking';
            btn.disabled = false;
            return;
        }

        const fd = new FormData(form);
        const overrideTotal = document.getElementById('override_total');
        const dispTotal = document.getElementById('disp_total');

        let finalVal = "0";
        if (overrideTotal && overrideTotal.value.trim() !== "") {
            finalVal = overrideTotal.value.trim();
        } else if (dispTotal) {
            finalVal = dispTotal.innerText.replace(/[^0-9.-]+/g, "");
        }
        fd.set('final_total', finalVal);

        // Similarly handle surcharge_amount
        const surch = document.getElementById('disp_surcharge');
        fd.set('surcharge_amount', surch ? surch.innerText.replace(/[^0-9.-]+/g, "") : "0");

        fd.append('action', 'save_full_booking');

        isProceeding = true;
        const res = await fetch('/api/bookings/handler', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.bookingAppData.csrfToken
            },
            body: fd
        });
        const json = await res.json();

        if (!json.success) throw new Error(json.message || "Failed to save booking");

        showToast("Success", "Booking Confirmed & Saved!", "success");
        setTimeout(() => {
            // Check if we're in supervisor mode or admin
            const isEdit = !!window.bookingAppData.bookingId;
            if (isEdit) {
                const backRoute = window.location.pathname.includes('/supervisor/') ? `/supervisor/bookings/${window.bookingAppData.bookingId}` : `/admin/bookings/${window.bookingAppData.bookingId}`;
                window.location.href = backRoute;
            } else {
                const calRoute = window.location.pathname.includes('/supervisor/') ? '/supervisor/calendar' : '/admin/calendar';
                window.location.href = calRoute;
            }
        }, 1000);
    } catch (e) {
        showToast("Error", e.message, "error");
        btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> Confirm & Save Booking';
        btn.disabled = false;
        isProceeding = false;
    }
};

window.deleteAndExit = function () {
    isProceeding = true;
    const isEdit = !!window.bookingAppData.bookingId;

    if (!isEdit && window.bookingAppData.bookingId) { // Only delete if it was a fresh draft (simplified check)
        const bookingIdEl = document.getElementById('booking_id');
        if (bookingIdEl && bookingIdEl.value) {
            let fd = new FormData();
            fd.append('action', 'delete_draft');
            fd.append('booking_id', bookingIdEl.value);
            fd.append('_token', window.bookingAppData.csrfToken);
            navigator.sendBeacon('/api/bookings/handler', fd);
        }
    }

    if (isEdit) {
        window.location.href = window.location.pathname.includes('/supervisor/') ? `/supervisor/bookings/${window.bookingAppData.bookingId}` : `/admin/bookings/${window.bookingAppData.bookingId}`;
    } else {
        window.location.href = window.location.pathname.includes('/supervisor/') ? '/supervisor/calendar' : '/admin/calendar';
    }
};

window.isInitialLoading = true;
document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize data from bridge
    const bridge = document.getElementById('booking-data-bridge');
    if (bridge) {
        window.bookingAppData = {
            config: JSON.parse(bridge.dataset.config || '{}'),
            categories: JSON.parse(bridge.dataset.categories || '{}'),
            savedExtras: JSON.parse(bridge.dataset.extras || '{}'),
            selectedItems: JSON.parse(bridge.dataset.selected || '{}'),
            csrfToken: bridge.dataset.csrf,
            bookingId: bridge.dataset.id,
            invoiceNumber: bridge.dataset.invoice
        };

        // Pre-check rides from bridge
        for (let name in window.bookingAppData.selectedItems) {
            const clean = name.toLowerCase().trim();
            const card = document.querySelector(`.product-card[data-name]`); // Need to find by name carefully
            // In a better loop below
        }
    }

    const dateInput = document.getElementById('event_date');
    calCursor = new Date(dateInput && dateInput.value ? dateInput.value : Date.now());

    // Initial triggers
    setTimeout(async () => {
        if (typeof calLoad === 'function') calLoad();

        // Populate initial selection state
        if (window.bookingAppData.selectedItems) {
            document.querySelectorAll('.product-card').forEach(card => {
                const name = card.dataset.name.toLowerCase().trim();
                if (window.bookingAppData.selectedItems[name]) {
                    const cb = card.querySelector('.ride-checkbox');
                    if (cb) {
                        cb.checked = true;
                        card.classList.add('selected');
                        const actionText = card.querySelector('.action-text');
                        if (actionText) actionText.innerText = 'Booked';
                    }
                }
            });
        }

        if (typeof checkRealTimeAvailability === 'function') await checkRealTimeAvailability(false); // First load is NOT silent
        const delSelect = document.getElementById('delivery_area_select');
        if (delSelect && typeof updateDeliveryCost === 'function') updateDeliveryCost(delSelect);

        // Sync initial duration custom state
        const checkedDur = document.querySelector('input[name="duration"]:checked');
        if (checkedDur && checkedDur.value === 'custom') {
            const appEl = document.querySelector('[x-data="bookingApp"]');
            const app = appEl && appEl._x_dataStack ? appEl._x_dataStack[0] : null;
            if (app) app.isDurationCustom = true;
        }

        if (typeof triggerRecalculate === 'function') triggerRecalculate();

        window.isInitialLoading = false;
    }, 100);
});

window.addEventListener('beforeunload', () => {
    const bookingIdEl = document.getElementById('booking_id');
    if (!isProceeding && bookingIdEl && bookingIdEl.value) {
        let fd = new FormData();
        fd.append('action', 'delete_draft');
        fd.append('booking_id', bookingIdEl.value);
        fd.append('_token', window.bookingAppData.csrfToken);
        navigator.sendBeacon('/api/bookings/handler', fd);
    }
});
