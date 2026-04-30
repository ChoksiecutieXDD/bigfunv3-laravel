window.initBookingAppData = function () {
    const bridge = document.getElementById('booking-data-bridge');
    if (bridge) {
        // Ensure the object exists so we don't break references
        if (!window.bookingAppData) window.bookingAppData = {};

        // Merge the data gracefully. 
        // IMPORTANT: If we already have entangled objects, we MUST NOT overwrite them with bridge data
        // because that breaks the Livewire <-> Alpine proxy connection.
        const newData = {
            config: bridge.dataset.config ? JSON.parse(bridge.dataset.config) : {},
            categories: bridge.dataset.categories ? JSON.parse(bridge.dataset.categories) : {},
            csrfToken: bridge.dataset.csrf,
            bookingId: bridge.dataset.id,
            invoiceNumber: bridge.dataset.invoice,
            pastCustomers: bridge.dataset.customers ? JSON.parse(bridge.dataset.customers) : [],
        };

        // Update these from bridge data
        newData.savedExtras = (bridge.dataset.extras && bridge.dataset.extras !== '[]') ? JSON.parse(bridge.dataset.extras) : (window.bookingAppData.savedExtras || {});
        newData.selectedItems = (bridge.dataset.selected && bridge.dataset.selected !== '[]' && bridge.dataset.selected !== 'null') ? JSON.parse(bridge.dataset.selected) : (window.bookingAppData.selectedItems || {});
        newData.extraPrices = (bridge.dataset.extraPrices && bridge.dataset.extraPrices !== '[]' && bridge.dataset.extraPrices !== 'null')
            ? JSON.parse(bridge.dataset.extraPrices)
            : (bridge.dataset.extra_prices ? JSON.parse(bridge.dataset.extra_prices) : (window.bookingAppData.extraPrices || {}));
        newData.activeOverrides = (bridge.dataset.activeOverrides && bridge.dataset.activeOverrides !== '[]' && bridge.dataset.activeOverrides !== 'null')
            ? JSON.parse(bridge.dataset.activeOverrides)
            : (bridge.dataset.active_overrides ? JSON.parse(bridge.dataset.active_overrides) : (window.bookingAppData.activeOverrides || {}));
        newData.lockedOverrides = (bridge.dataset.lockedOverrides && bridge.dataset.lockedOverrides !== '[]' && bridge.dataset.lockedOverrides !== 'null')
            ? JSON.parse(bridge.dataset.lockedOverrides)
            : (window.bookingAppData.lockedOverrides || {});
        newData.manualPrices = (bridge.dataset.manualPrices && bridge.dataset.manualPrices !== '[]' && bridge.dataset.manualPrices !== 'null')
            ? JSON.parse(bridge.dataset.manualPrices)
            : (bridge.dataset.manual_prices ? JSON.parse(bridge.dataset.manual_prices) : (window.bookingAppData.manualPrices || {}));

        Object.assign(window.bookingAppData, newData);
        window.isInitialLoading = true;
        setTimeout(() => { window.isInitialLoading = false; }, 2000);

        // Capture Amount Paid for dynamic balance calculation
        if (window.bookingAppData.bookingId) {
            if (bridge.dataset.totalPaid !== undefined) {
                window.bookingAppData.totalPaid = parseFloat(bridge.dataset.totalPaid) || 0;
            } else if (window.bookingAppData.totalPaid === undefined) {
                const dispTotal = document.getElementById('disp_total');
                const dispBalance = document.getElementById('disp_balance');
                if (dispBalance && dispTotal) {
                    const balance = parseFloat(dispBalance.innerText.replace(/[^0-9.-]+/g, "")) || 0;
                    const total = parseFloat(dispTotal.innerText.replace(/[^0-9.-]+/g, "")) || 0;
                    window.bookingAppData.totalPaid = total - balance;
                }
            }

            // Set lastPriceToastTotal to whatever is currently in the DOM to avoid "Cost Updated" on first run
            if (window.lastPriceToastTotal === undefined) {
                const dispTotal = document.getElementById('disp_total');
                if (dispTotal) {
                    window.lastPriceToastTotal = parseFloat(dispTotal.innerText.replace(/[^0-9.-]+/g, "")) || 0;
                }
            }
        }

        const lwEl = document.querySelector('[wire\\:id]');
        if (lwEl && window.Livewire) {
            window.lwBookingComponent = window.Livewire.find(lwEl.getAttribute('wire:id'));
        }

        window.showToast = function (title, message, type = 'success') {
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { title, message, type }
            }));
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
window.isInitialLoading = true;
setTimeout(() => { window.isInitialLoading = false; }, 3000);

function registerBookingApp() {
    if (window.bookingAppRegistered) return;
    window.bookingAppRegistered = true;

    Alpine.data('bookingApp', () => ({
        paymentType: window.bookingAppData?.form?.payment_type || 'EFT',
        paymentMethods: ['Direct Deposit', 'Bank Transfer', 'Osko', 'PayID'],
        paymentMethod: 'Direct Deposit',
        paymentStatus: 'Pending',
        isInitialLoading: true,
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
            calendarModal: false,
            info: false,
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
        productDetails: {
            visible: false,
            name: '',
            spec: '',
            price: 0
        },
        totalSizeMB: '0.00',
        calculateTotalSize() {
            let total = 0;
            const slots = document.querySelectorAll('.attachment-slot');
            slots.forEach(slot => {
                const input = slot.querySelector('input[type=\'file\']');
                const slotName = slot.dataset.slotName;
                const isDeleted = slot.dataset.isDeleted === 'true';
                let existingSize = parseInt(slot.dataset.existingSize || '0', 10);
                
                // 1. Check if user just selected a file in the input
                if (input && input.files && input.files[0]) {
                    total += input.files[0].size;
                } 
                // 2. Check if there's a pending temporary upload on the server for this slot
                else if (!isDeleted && window.bookingAppData && window.bookingAppData.config && window.bookingAppData.config.tempFileSizes && window.bookingAppData.config.tempFileSizes[slotName] > 0) {
                    total += window.bookingAppData.config.tempFileSizes[slotName];
                }
                // 3. Check for existing file in DB
                else if (!isDeleted && existingSize > 0) {
                    total += existingSize;
                }
            });
            this.totalSizeMB = (total / (1024 * 1024)).toFixed(2);
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
                // If Card Holder (Credit/Debit Card), we don't need sub-methods
                this.paymentMethods = ['Credit/Debit Card'];
                this.paymentMethod = 'Credit/Debit Card';
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
                
                // CRITICAL: Sync payment type immediately from bridge to prevent JS overwrite with default 'EFT'
                if (window.bookingAppData.form && window.bookingAppData.form.payment_type) {
                    this.paymentType = window.bookingAppData.form.payment_type;
                    this.updatePaymentMethods();
                }

                // If it's edit mode, we might want to pre-load some things
                setTimeout(() => {
                    if (typeof checkRealTimeAvailability === 'function') checkRealTimeAvailability();
                    this.calculateTotalSize();
                    this.triggerRecalculate();
                    this.isInitialLoading = false;
                    window.isInitialLoading = false; // Sync with global for toasts
                }, 1500);
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

        toasts: [],
        addToast(title, message, type = 'primary') {
            const id = Date.now();

            // Map types to premium icons
            const iconMap = {
                'success': 'check_circle',
                'error': 'error',
                'warning': 'warning',
                'primary': 'info'
            };

            if (this.toasts.length >= 3) this.toasts.shift();
            this.toasts.push({
                id,
                title,
                message,
                type,
                icon: iconMap[type] || 'notifications',
                visible: true
            });
            setTimeout(() => {
                const t = this.toasts.find(toast => toast.id === id);
                if (t) t.visible = false;
            }, 3700);
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 4000);
        }
    }));
}

if (window.Alpine) {
    registerBookingApp();
} else {
    document.addEventListener('alpine:init', registerBookingApp);
}

// Bridge Global Vanilla JS to Alpine Toast
window.showToast = function (title, message, type = 'success') {
    window.dispatchEvent(new CustomEvent('show-toast', {
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

window.dateChanged = function () {
    const dateInput = document.getElementById('event_date');
    const val = dateInput ? dateInput.value : '';

    // Communicate to Livewire
    if (window.lwBookingComponent) {
        window.lwBookingComponent.set('form.event_date', val);
        // Wait for Livewire to finish its update before checking real-time availability
        setTimeout(() => {
            if (typeof checkRealTimeAvailability === 'function') {
                checkRealTimeAvailability(true);
            }
        }, 600);
    } else {
        if (typeof checkRealTimeAvailability === 'function') {
            checkRealTimeAvailability(true);
        }
    }

    if (typeof checkDuplicates === 'function') checkDuplicates();
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

    // Toggling in Edit mode is now direct
    const isChecked = checkbox.checked;

    // Pulse the badge to show syncing
    const badge = card.querySelector('.status-badge');
    if (badge) {
        badge.innerText = 'Syncing...';
        badge.classList.add('opacity-50', 'animate-pulse');
        // ULTIMATE SAFETY: If for some reason the sync module misses this, clear it after 10s
        setTimeout(() => {
            const currentText = (badge.innerText || '').trim().toUpperCase();
            if (currentText.includes('SYNCING') && typeof checkRealTimeAvailability === 'function') {
                checkRealTimeAvailability(true);
            }
        }, 2000);
    }

    if (window.toggleItemUI) {
        window.toggleItemUI(checkbox, card);
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
    }
    const panel = card.querySelector('.ride-override-panel');

    if (checkbox.checked) {
        if (panel) panel.classList.remove('hidden');
        card.classList.add('selected');
        if (actionText) actionText.innerText = 'Selected';
        // Add to selectedItems for sync
        if (window.bookingAppData) window.bookingAppData.selectedItems[name] = 1;
    } else {
        if (panel) panel.classList.add('hidden');
        card.classList.remove('selected');
        if (actionText) actionText.innerText = 'Click to select';
        // Remove from selectedItems for sync
        if (window.bookingAppData) delete window.bookingAppData.selectedItems[name];
    }

    try {
        // 1. Scrape current manual inputs BEFORE destroying/rebuilding the DOM
        if (typeof saveCurrentExtrasState === 'function') saveCurrentExtrasState(true);

        if (typeof updateCategoryLimitsUI === 'function') updateCategoryLimitsUI();
        if (typeof updateDynamicExtras === 'function') updateDynamicExtras();

        // 2. Resave to lock internal arrays
        if (typeof saveCurrentExtrasState === 'function') saveCurrentExtrasState(true);

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
}


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

    // --- Price Override Force Sync ---
    const key = element.name;
    if (isSelected) {
        let price = 0;
        if (element.type === 'checkbox') {
            price = parseFloat(element.dataset.price || 0);
        } else if (element.tagName === 'SELECT') {
            const opt = element.options[element.selectedIndex];
            if (opt && opt.value.includes('|')) {
                price = parseFloat(opt.value.split('|')[0] || 0);
            } else if (opt) {
                price = parseFloat(opt.dataset.price || 0);
            }
        }

        // Only update extraPrices if it's currently missing or explicitly set to 0 (unselected state). 
        // We preserve existing non-zero overrides even when selections change.
        if (window.bookingAppData.extraPrices[key] === undefined || window.bookingAppData.extraPrices[key] === 0 || window.bookingAppData.extraPrices[key] === "0") {
            if (window.updateExtraPrice) window.updateExtraPrice(key, price);
        }
        // Force override state to active for "instant check"
        if (window.toggleOverrideState) window.toggleOverrideState(key, true);
    } else {
        if (window.updateExtraPrice) window.updateExtraPrice(key, 0);
        if (window.toggleOverrideState) window.toggleOverrideState(key, false);
    }

    triggerRecalculate();
    saveCurrentExtrasState();
    updateCategoryLimitsUI();
};

window.checkTotalAttachmentSize = function (currentInput) {
    const MAX_TOTAL = 5 * 1024 * 1024;   // 5MB total limit for all files

    // --- Total size check ---
    const slots = document.querySelectorAll('.attachment-slot');
    let total = 0;

    slots.forEach(slot => {
        const input = slot.querySelector('input[type="file"]');
        const slotName = slot.dataset.slotName;
        const isDeleted = slot.dataset.isDeleted === 'true';
        let existingSize = parseInt(slot.dataset.existingSize || "0", 10);

        if (input && input.files && input.files[0]) {
            // Priority 1: New file selected for this slot overrides existing
            total += input.files[0].size;
        } else if (!isDeleted && window.bookingAppData && window.bookingAppData.config && window.bookingAppData.config.tempFileSizes && window.bookingAppData.config.tempFileSizes[slotName] > 0) {
            // Priority 2: Pending temp file on server
            total += window.bookingAppData.config.tempFileSizes[slotName];
        } else if (!isDeleted && existingSize > 0) {
            // Priority 3: Existing file in this slot that wasn't deleted
            total += existingSize;
        }
    });

    if (total > MAX_TOTAL) {
        const currentMB = (total / (1024 * 1024)).toFixed(2);

        // ONLY trigger the modal alert if this was called from a file input change (currentInput exists)
        // AND we are not in the initial loading phase.
        if (currentInput && !window.isInitialLoading) {
            if (typeof showToast !== 'undefined') {
                showToast("Storage Limit", "Total size of all attachments must not exceed 5MB. Current: " + currentMB + "MB", "error");
            }
            window.dispatchEvent(new CustomEvent('notify', { detail: { title: 'Storage Limit Exceeded', type: 'error', icon: 'error', message: `Total size of all attachments must not exceed 5MB. Current total is ${currentMB}MB.` } }));

            const appEl = document.querySelector('[x-data="bookingApp"]');
            const alpine = appEl && appEl._x_dataStack ? appEl._x_dataStack[0] : null;
            if (alpine && alpine.modals) alpine.modals.fileSizeAlert = true;

            currentInput.value = "";
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

    if (input.value === 'custom') {
        if (wrapper) wrapper.classList.remove('hidden');
        const manualCost = document.getElementById('manual_duration_cost');
        if (durCostInput) durCostInput.value = manualCost ? (manualCost.value || 0) : 0;
    } else {
        if (wrapper) wrapper.classList.add('hidden');
        if (durCostInput) durCostInput.value = input.dataset.price;
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
        delCostInput.value = manual ? (manual.value || 0) : 0;
    } else {
        if (sel.selectedIndex >= 0 && sel.options[sel.selectedIndex]) {
            delCostInput.value = sel.options[sel.selectedIndex].getAttribute('data-price') || 0;
        } else {
            delCostInput.value = 0;
        }
    }
    triggerRecalculate();
};

window.triggerRecalculate = function () {
    // Determine source of costs (Livewire inputs or hidden fields)
    const manualDurInput = document.querySelector('input[wire\\:model\\.live="form.duration_cost"]');
    const durCostHidden = document.getElementById('duration_cost');
    let durCost = manualDurInput ? (parseFloat(manualDurInput.value) || 0) : (durCostHidden ? (parseFloat(durCostHidden.value) || 0) : 0);

    const breakDur = document.getElementById('breakdown_dur');
    if (breakDur) breakDur.innerText = '$' + durCost.toFixed(2);

    const manualDelInput = document.querySelector('input[wire\\:model\\.live="form.delivery_cost"]');
    const delCostHidden = document.getElementById('delivery_cost');
    let delCost = manualDelInput ? (parseFloat(manualDelInput.value) || 0) : (delCostHidden ? (parseFloat(delCostHidden.value) || 0) : 0);

    const breakDel = document.getElementById('breakdown_del');
    if (breakDel) breakDel.innerText = '$' + delCost.toFixed(2);

    let attractionsCost = 0;
    document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
        const card = cb.closest('.product-card');
        if (card) {
            // In Edit Mode, we might have a quantity input that is NOT readonly or different selector
            const qtyInput = card.querySelector('input[readonly].w-8') || card.querySelector('.ride-qty-input');
            const q = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;

            // Prioritize override price if available in the card UI
            const manualPriceInput = card.querySelector('.manual-ride-price');
            let p = parseFloat(card.dataset.price || 0);
            if (manualPriceInput && manualPriceInput.value.trim() !== "") {
                p = parseFloat(manualPriceInput.value);
            }

            attractionsCost += (p * q);
        }
    });
    const breakAttractions = document.getElementById('breakdown_attractions');
    if (breakAttractions) breakAttractions.innerText = '$' + attractionsCost.toFixed(2);

    let extCost = 0;
    const extPriceElements = document.querySelectorAll('.ext-price');
    const extraLabels = (window.bookingAppData && window.bookingAppData.config && window.bookingAppData.config.extraLabels) ? window.bookingAppData.config.extraLabels : {};
    const extraCategories = (window.bookingAppData && window.bookingAppData.config && window.bookingAppData.config.extraCategories) ? window.bookingAppData.config.extraCategories : {};
    // Ensure activeCategories is always an array
    let activeCategoriesRaw = (window.bookingAppData && window.bookingAppData.config && window.bookingAppData.config.activeCategories) ? window.bookingAppData.config.activeCategories : ['General Logistics'];
    const activeCategories = Array.isArray(activeCategoriesRaw) ? activeCategoriesRaw : Object.values(activeCategoriesRaw);
    const selectedItems = (window.bookingAppData && window.bookingAppData.selectedItems) ? window.bookingAppData.selectedItems : {};
    
    if (extPriceElements.length > 0) {
        extPriceElements.forEach(el => {
            const key = el.name;
            
            // 1. Skip if the category for this extra is not active
            const cat = extraCategories[key];
            if (cat && !activeCategories.includes(cat)) return;

            // 2. Skip if this extra is already counted in attractionsCost (rides)
            const label = extraLabels[key];
            if (label && selectedItems[label]) return;

            const isSelected = (el.tagName === 'SELECT' ? (el.value !== '' && el.value !== '0' && !el.value.includes('|no')) : el.checked);

            if (isSelected) {
                // Priority 1: Check for manual override in bookingAppData
                if (window.bookingAppData && window.bookingAppData.extraPrices && window.bookingAppData.extraPrices[key] !== undefined) {
                    extCost += parseFloat(window.bookingAppData.extraPrices[key] || 0);
                } else {
                    // Priority 2: Fallback to DOM values if no override recorded yet
                    if (el.tagName === 'SELECT') {
                        let val = el.value;
                        if (val.includes('|')) {
                            extCost += parseFloat(val.split('|')[0] || 0);
                        } else {
                            extCost += parseFloat(el.options[el.selectedIndex].dataset.price || 0);
                        }
                    } else if (el.tagName === 'INPUT' && el.checked) {
                        extCost += parseFloat(el.dataset.price || 0);
                    }
                }
            }
        });
    } else if (window.bookingAppData && window.bookingAppData.savedExtras) {
        // Source of Truth Fallback: If DOM isn't rendered yet (initial load), use bridge data
        for (let key in window.bookingAppData.savedExtras) {
            let val = window.bookingAppData.savedExtras[key];
            
            // 1. Skip if the category for this extra is not active
            const cat = extraCategories[key];
            if (cat && !activeCategories.includes(cat)) continue;

            // 2. Skip if this extra is already counted in attractionsCost (rides)
            const label = extraLabels[key];
            if (label && selectedItems[label]) continue;

            // Check if extra is selected (not "0" and not ending in "|no")
            if (val && val !== "0" && !String(val).endsWith('|no')) {
                if (window.bookingAppData.extraPrices && window.bookingAppData.extraPrices[key] !== undefined) {
                    extCost += parseFloat(window.bookingAppData.extraPrices[key] || 0);
                }
            }
        }
    }
    const breakExt = document.getElementById('breakdown_ext');
    if (breakExt) breakExt.innerText = '$' + extCost.toFixed(2);

    let sub = durCost + delCost + extCost + attractionsCost;
    const calcSub = document.getElementById('calc_subtotal');
    if (calcSub) calcSub.value = sub.toFixed(2);

    // Call total calculation regardless of Edit Mode for dynamic feedback
    calculateFinalTotals();

    updateCategoryLimitsUI();
};

window.calculateFinalTotals = function () {
    const calcSubInput = document.getElementById('calc_subtotal');
    let sub = calcSubInput ? (parseFloat(calcSubInput.value) || 0) : 0;

    // Extract from Alpine or direct DOM if Alpine state is stale/default
    const el = document.querySelector('[x-data="bookingApp"]');
    let alpine = el && el._x_dataStack ? el._x_dataStack[0] : null;
    
    // Check DOM directly as source of truth for payment type if alpine is at default
    let type = (alpine && alpine.paymentType !== 'EFT') 
        ? alpine.paymentType 
        : (document.querySelector('select[wire\\:model\\.live="form.payment_type"], select[wire\\:model="form.payment_type"]')?.value || 'EFT');

    let rate = (type === 'Card Holder' || type === 'credit_card') ? 0.029 : 0;
    let sur = sub * rate;
    let tot = sub + sur;

    const ovInput = document.getElementById('override_total');
    let ov = (ovInput && ovInput.value.trim() !== "") ? ovInput.value.trim() : "";
    if (ov !== "") tot = parseFloat(ov);

    const surLabel = document.getElementById('surcharge_label');
    if (surLabel) surLabel.innerText = `Processing Fee (${(rate * 100).toFixed(1)}%)`;

    const dispSur = document.getElementById('disp_surcharge');
    if (dispSur) dispSur.innerText = '$' + sur.toFixed(2);

    const dispTot = document.getElementById('disp_total');
    if (dispTot) dispTot.innerText = '$' + tot.toFixed(2);

    const dispDep = document.getElementById('disp_deposit');
    if (dispDep) dispDep.innerText = '$' + (tot / 2).toFixed(2);

    // Update Balance Due Displays
    const balanceElements = [
        document.getElementById('disp_balance'),
        document.getElementById('disp_balance_footer')
    ];

    if (window.bookingAppData && window.bookingAppData.totalPaid !== undefined) {
        const balance = tot - window.bookingAppData.totalPaid;
        const balanceStr = '$' + balance.toFixed(2);

        balanceElements.forEach(el => {
            if (el) {
                el.innerText = balanceStr;
                // Dynamic coloring
                if (balance > 0.01) {
                    el.classList.remove('text-emerald-400');
                    el.classList.add('text-rose-400');
                } else {
                    el.classList.remove('text-rose-400');
                    el.classList.add('text-emerald-400');
                }
            }
        });

        // Update Icon in summary
        const iconBal = document.getElementById('icon_balance');
        if (iconBal) {
            if (balance > 0.01) {
                iconBal.innerText = 'pending';
                iconBal.classList.remove('text-emerald-500');
                iconBal.classList.add('text-rose-500');
            } else {
                iconBal.innerText = 'check_circle';
                iconBal.classList.remove('text-rose-500');
                iconBal.classList.add('text-emerald-500');
            }
        }

        // Update Total Paid display just in case
        const dispPaid = document.getElementById('disp_total_paid');
        if (dispPaid) dispPaid.innerText = '-$' + window.bookingAppData.totalPaid.toFixed(2);
    }

    const surAmount = document.getElementById('surcharge_amount');
    if (surAmount) surAmount.value = sur.toFixed(2);

    const depAmount = document.getElementById('deposit_amount');
    if (depAmount) depAmount.value = (tot / 2).toFixed(2);

    // Debounced Toast for Price Changes
    if (window.lastPriceToastTotal !== tot) {
        clearTimeout(window.priceToastTimer);
        window.priceToastTimer = setTimeout(() => {
            if (!window.isInitialLoading && window.lastPriceToastTotal !== undefined && typeof showToast === 'function') {
                const diff = tot - window.lastPriceToastTotal;
                if (Math.abs(diff) > 0.01) {
                    const type = diff > 0 ? "increase" : "decrease";
                    showToast("Cost Updated", `Total amount ${type}d to $${tot.toFixed(2)}`, "info");
                }
            }
            window.lastPriceToastTotal = tot;
        }, 1500);
    }
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
    if (!date) missing.push("Event Date");
    if (!startTime) missing.push("Start Time");
    if (!endTime) missing.push("End Time");
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

    let timeStr = "TBD - TBD";
    if (startTime || endTime) {
        timeStr = (startTime || "TBD") + " - " + (endTime || "TBD");
    }
    document.getElementById('rev_time').innerText = timeStr;

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

    document.getElementById('rev_dur_cost').innerText = document.getElementById('breakdown_dur').innerText;

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
        if (input && input.files.length > 0) {
            revAttachments.innerHTML += `<li><span class="text-[#9E6B73] font-bold">New:</span> ${escapeHTML(input.files[0].name)}</li>`;
            hasFiles = true;
        } else if (input) {
            const link = input.nextElementSibling;
            if (link && !link.classList.contains('hidden') && link.classList.contains('view-attachment-link')) {
                const fname = link.textContent.trim().replace(/^View\s+/i, '');
                revAttachments.innerHTML += `<li><span class="text-slate-500 font-bold">Saved:</span> ${escapeHTML(fname)}</li>`;
                hasFiles = true;
            }
        }
    }
    if (!hasFiles) revAttachments.innerHTML = '<li class="text-slate-400 italic">No attachments added.</li>';

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
    const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-rounded animate-spin">refresh</span> Processing...';
    btn.disabled = true;

    try {
        const form = document.getElementById('combinedBookingForm');

        // --- 5MB Combined File Size Check ---
        let totalSize = 0;
        const slots = document.querySelectorAll('.attachment-slot');
        slots.forEach(slot => {
            const input = slot.querySelector('input[type="file"]');
            const isDeleted = slot.dataset.isDeleted === 'true';
            let existingSize = parseInt(slot.dataset.existingSize || '0', 10);

            if (input && input.files && input.files[0]) {
                totalSize += input.files[0].size;
            } else if (!isDeleted && existingSize > 0) {
                totalSize += existingSize;
            }
        });

        if (totalSize > 5 * 1024 * 1024) {
            showToast("Error", "Total attachment size exceeds 5MB limit. Current: " + (totalSize / (1024 * 1024)).toFixed(2) + "MB", "error");
            btn.innerHTML = oldText;
            btn.disabled = false;
            return;
        }

        const fd = new FormData(form);

        // --- Capture Deleted Attachments ---
        document.querySelectorAll('.attachment-slot[data-is-deleted="true"]').forEach(slot => {
            fd.append('deleted_attachments[]', slot.dataset.slotName);
        });

        // --- Capture Extras (explicitly ensures they are synced even if not in DOM) ---
        // Actually, the DOM elements are better for the backend loop, but if they are hidden, we might need this:
        // fd.append('dynamic_extras', JSON.stringify(window.bookingAppData.savedExtras));

        const overrideTotal = document.getElementById('override_total');
        const dispTotal = document.getElementById('disp_total');

        let finalVal = "0";
        if (overrideTotal && overrideTotal.value.trim() !== "") {
            finalVal = overrideTotal.value.trim();
        } else if (dispTotal) {
            finalVal = dispTotal.innerText.replace(/[^0-9.-]+/g, "");
        }
        fd.set('final_total', finalVal);

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
    const bridge = document.getElementById('booking-data-bridge');
    if (bridge) {
        window.initBookingAppData();
    }

    const dateInput = document.getElementById('event_date');
    calCursor = new Date(dateInput && dateInput.value ? dateInput.value : Date.now());

    // Listen for backend recalculations to keep Alpine/UI in sync
    window.addEventListener('booking-preview-updated', (e) => {
        const appEl = document.querySelector('[x-data="bookingApp"]');
        if (!appEl) return;
        
        let app = null;
        if (appEl.__x) app = appEl.__x.$data;
        else if (appEl._x_dataStack) app = appEl._x_dataStack[0];
        
        if (!app) return;

        const detail = e.detail || {};
        const totals = detail.totals || {};

        // 1. Sync Payment Type if it changed on backend
        if (detail.paymentType && app.paymentType !== detail.paymentType) {
            app.paymentType = detail.paymentType;
        }

        // 2. Update breakdown elements directly for instant feedback
        if (document.getElementById('breakdown_dur')) document.getElementById('breakdown_dur').textContent = '$' + Number(totals.duration || 0).toFixed(2);
        if (document.getElementById('breakdown_del')) document.getElementById('breakdown_del').textContent = '$' + Number(totals.delivery || 0).toFixed(2);
        if (document.getElementById('breakdown_attractions')) document.getElementById('breakdown_attractions').textContent = '$' + Number(totals.attractions || 0).toFixed(2);
        if (document.getElementById('breakdown_ext')) document.getElementById('breakdown_ext').textContent = '$' + Number(totals.extras || 0).toFixed(2);
        if (document.getElementById('calc_subtotal')) document.getElementById('calc_subtotal').value = Number(totals.subtotal || 0).toFixed(2);
        if (document.getElementById('disp_surcharge')) document.getElementById('disp_surcharge').textContent = '$' + Number(totals.surcharge || 0).toFixed(2);
        
        // 3. Update main totals
        const totalStr = '$' + Number(totals.total || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (document.getElementById('disp_total')) document.getElementById('disp_total').textContent = totalStr;
        
        const balanceStr = '$' + Number(totals.balance || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (document.getElementById('disp_balance')) document.getElementById('disp_balance').textContent = balanceStr;
        if (document.getElementById('disp_balance_footer')) document.getElementById('disp_balance_footer').textContent = balanceStr;
        if (document.getElementById('disp_total_paid')) document.getElementById('disp_total_paid').textContent = '-$' + Number(totals.paid || 0).toFixed(2);
        if (document.getElementById('disp_deposit')) document.getElementById('disp_deposit').textContent = '$' + (Number(totals.total || 0) / 2).toFixed(2);

        // 4. Update balance icon and color
        const balance = Number(totals.balance || 0);
        const iconBal = document.getElementById('icon_balance');
        const dispBal = document.getElementById('disp_balance');
        const dispBalFooter = document.getElementById('disp_balance_footer');

        [dispBal, dispBalFooter].forEach(el => {
            if (el) {
                if (balance > 0.01) {
                    el.classList.remove('text-emerald-400', 'text-emerald-500');
                    el.classList.add('text-rose-400');
                } else {
                    el.classList.remove('text-rose-400');
                    el.classList.add('text-emerald-400');
                }
            }
        });

        if (iconBal) {
            if (balance > 0.01) {
                iconBal.innerText = 'pending';
                iconBal.classList.remove('text-emerald-500');
                iconBal.classList.add('text-rose-500');
            } else {
                iconBal.innerText = 'check_circle';
                iconBal.classList.remove('text-rose-500');
                iconBal.classList.add('text-emerald-500');
            }
        }
    });

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