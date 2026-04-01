// Initialize Bridge Data from element
(function() {
    const bridge = document.getElementById('booking-data-bridge');
    if (bridge) {
        window.bookingAppData = {
            config: bridge.dataset.config ? JSON.parse(bridge.dataset.config) : {},
            categories: bridge.dataset.categories ? JSON.parse(bridge.dataset.categories) : {},
            savedExtras: bridge.dataset.extras ? JSON.parse(bridge.dataset.extras) : [],
            csrfToken: bridge.dataset.csrf,
            bookingId: bridge.dataset.id,
            invoiceNumber: bridge.dataset.invoice,
            pastCustomers: bridge.dataset.customers ? JSON.parse(bridge.dataset.customers) : [],
        };
    }
})();

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
            limitExceeded: false
        },
        limitExceededCategory: '',
        limitExceededLimit: 0,
        previousCustomers: [],
        filteredCustomers: [],
        searchHistory: '',
        cardNetwork: 'Visa',
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
                // If it's edit mode, we might want to pre-load some things
                setTimeout(() => {
                    if (typeof checkRealTimeAvailability === 'function') checkRealTimeAvailability();
                    if (typeof calLoad === 'function') calLoad();
                }, 100);
            }
        },

        filterCustomers() {
            const term = this.searchHistory.toLowerCase().trim();
            if (!term) {
                this.filteredCustomers = this.previousCustomers;
                return;
            }
            this.filteredCustomers = this.previousCustomers.filter(c => {
                const name = (c.customer_first_name + ' ' + (c.customer_last_name || '')).toLowerCase();
                const email = (c.customer_email || '').toLowerCase();
                return name.includes(term) || email.includes(term);
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
            checkDuplicates();
        },

        togglePaymentStatus() {
            this.paymentStatus = (this.paymentStatus === 'Pending') ? 'Deposit Paid' : 'Pending';
            this.$nextTick(() => {
                if(typeof triggerRecalculate === 'function') triggerRecalculate();
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
            if (this.toasts.length >= 3) this.toasts.shift();
            this.toasts.push({
                id,
                title,
                message,
                type,
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
});

// Bridge Global Vanilla JS to Alpine Toast
window.showToast = function(title, message, type = 'primary') {
    const el = document.querySelector('[x-data="bookingApp"]');
    if (el && el._x_dataStack) {
        // Alpine v3/v4 style
        el._x_dataStack[0].addToast(title, message, type);
    }
};

// --- GLOBAL VARIABLES & API ---
let globalCategoryBooked = {};
let isProceeding = false;
let duplicateCheckTimer;
let calCursor = new Date(); // Initialized below

window.apiPost = async function(action, payload = null) {
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

window.checkDuplicates = function() {
    clearTimeout(duplicateCheckTimer);
    duplicateCheckTimer = setTimeout(async () => {
        const dateEl = document.getElementById('event_date');
        const fNameEl = document.getElementById('cust_first_name');
        const lNameEl = document.getElementById('cust_last_name');
        const emailEl = document.getElementById('customer_email_address');
        const invoiceEl = document.getElementById('invoice_number');

        if (!dateEl) return;
        
        const date = dateEl.value;
        const fName = fNameEl ? fNameEl.value.trim() : '';
        const lName = lNameEl ? lNameEl.value.trim() : '';
        const email = emailEl ? emailEl.value.trim() : '';
        const invoice = invoiceEl ? invoiceEl.value : '';

        if (!date || (!email && (!fName || !lName))) return;

        try {
            const data = await apiPost('check_duplicates', {
                date: date,
                first_name: fName,
                last_name: lName,
                email: email,
                current_invoice: invoice
            });
            if (data.warnings && data.warnings.length > 0) {
                const bannerBody = document.getElementById('duplicateBannerBody');
                if (bannerBody) bannerBody.innerHTML = data.warnings.map(w => `<p>• ${w}</p>`).join('');
                const banner = document.getElementById('duplicateBanner');
                if (banner) banner.classList.remove('hidden');
            } else {
                const banner = document.getElementById('duplicateBanner');
                if (banner) banner.classList.add('hidden');
            }
        } catch (e) {
            console.error("Duplicate check failed", e);
        }
    }, 500);
};

function fmtDate(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

window.calLoad = async function() {
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
            end: fmtDate(e)
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
            let dStr = `${calCursor.getFullYear()}-${String(calCursor.getMonth()+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            let used = (res.counts && res.counts[dStr]) ? parseInt(res.counts[dStr]) : 0;
            let left = Math.max(0, dailyLimit - used);

            let bg = left === 0 ? 'bg-red-50 border-red-200 text-red-500 opacity-60' : (left <= 2 ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700');
            let ring = currentVal === dStr ? 'ring-2 ring-[#9E6B73] ring-offset-2 border-[#9E6B73]' : '';
            let todayBadge = todayKey === dStr ? '<span class="absolute top-2 right-2 w-3 h-3 rounded-full bg-[#9E6B73]"></span>' : '';

            grid.innerHTML += `<div class="cal-day ${bg} ${ring} p-4 sm:p-6 min-h-[80px] sm:min-h-[100px] border rounded-2xl cursor-pointer flex flex-col items-center justify-center relative hover:shadow-md transition" onclick="selectDate('${dStr}', ${left})">
                ${todayBadge}
                <span class="font-extrabold text-xl sm:text-3xl leading-none mb-2">${d}</span>
                <span class="text-[10px] sm:text-xs font-bold bg-white/80 px-2 py-1 rounded-full leading-none shadow-sm">${left===0?'Full':left + ' Left'}</span>
            </div>`;
        }
    } catch (e) {
        console.error("Calendar Load Error:", e);
        const summaryEl = document.getElementById('calSummary');
        if (summaryEl) summaryEl.innerText = "Failed to load slots.";
    }
};

window.calPrev = function() {
    calCursor.setMonth(calCursor.getMonth() - 1);
    calLoad();
};

window.calNext = function() {
    calCursor.setMonth(calCursor.getMonth() + 1);
    calLoad();
};

window.selectDate = function(dateStr, left) {
    if (left <= 0) {
        showToast("Sold Out", "This date is fully booked.", "error");
        return;
    }
    const dateInput = document.getElementById('event_date');
    if (dateInput) dateInput.value = dateStr;
    calLoad();
    dateChanged();
    showToast("Date Selected", "Date updated to " + dateStr, "success");
};

window.dateChanged = function() {
    checkRealTimeAvailability();
    checkDuplicates();
};

window.checkRealTimeAvailability = async function() {
    const dateEl = document.getElementById('event_date');
    const invoiceEl = document.getElementById('invoice_number');
    if (!dateEl) return;
    
    const date = dateEl.value;
    const invoice = invoiceEl ? invoiceEl.value : '';
    if (!date) return;

    try {
        const response = await fetch(`/api/bookings/check-availability?date=${date}&invoice=${invoice}`);
        if (!response.ok) throw new Error("API call failed");
        const data = await response.json();

        if (data.status === 'success' && data.products) {
            globalCategoryBooked = {};

            document.querySelectorAll('.product-card').forEach(card => {
                const rawName = card.dataset.name.trim();
                const cleanName = rawName.toLowerCase();
                const checkbox = card.querySelector('.ride-checkbox');
                const badge = card.querySelector('.status-badge');
                const actionText = card.querySelector('.action-text');
                const targetCat = (card.dataset.countsAgainst || '').trim();
                const itemLimit = parseInt(card.dataset.dailyLimit) || 0;

                if (data.products[cleanName]) {
                    const left = data.products[cleanName].left;

                    if (itemLimit > 0) {
                        const bookedAmount = itemLimit - left;
                        if (bookedAmount > 0) globalCategoryBooked[targetCat] = (globalCategoryBooked[targetCat] || 0) + bookedAmount;
                    } else if (left === 0) {
                        globalCategoryBooked[targetCat] = (globalCategoryBooked[targetCat] || 0) + 1;
                    }

                    if (left <= 0) {
                        card.dataset.productSoldOut = 'true';
                        card.classList.add('disabled-card');
                        card.classList.remove('selected');
                        checkbox.checked = false;
                        checkbox.disabled = true;
                        badge.innerText = 'SOLD OUT';
                        badge.className = 'status-badge status-soldout';
                        if (actionText) actionText.innerText = 'Not Available';
                    } else {
                        card.dataset.productSoldOut = 'false';
                        card.classList.remove('disabled-card');
                        checkbox.disabled = false;
                        if (left <= 2) {
                            badge.innerText = `ONLY ${left} LEFT`;
                            badge.className = 'status-badge status-limited';
                        } else {
                            badge.innerText = `${left} AVAILABLE`;
                            badge.className = 'status-badge status-avail';
                        }
                        if (actionText) actionText.innerText = 'Click to select';
                    }
                } else {
                    card.dataset.productSoldOut = 'false';
                    card.classList.remove('disabled-card');
                    checkbox.disabled = false;
                    badge.innerText = itemLimit > 0 ? `${itemLimit} AVAIL` : 'AVAILABLE';
                    badge.className = 'status-badge status-avail';
                    if (actionText) actionText.innerText = 'Click to select';
                }
            });

            updateDynamicExtras();
            updateCategoryLimitsUI();
        }
    } catch (error) {
        console.error("Availability Check Failed", error);
    }
};

window.updateCategoryLimitsUI = function() {
    let usage = {};
    const categories = window.bookingAppData.categories;
    for (let cat in categories) usage[cat.trim()] = globalCategoryBooked[cat.trim()] || 0;

    document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
        const limitCategory = (cb.closest('.product-card').dataset.countsAgainst || '').trim();
        usage[limitCategory] = (usage[limitCategory] || 0) + 1;
    });

    // Also count Extras (Addons & Dropdowns) that count against category limits
    document.querySelectorAll('.ext-price[data-counts-against]').forEach(el => {
        const countsAgainst = (el.dataset.countsAgainst || '').trim();
        if (!countsAgainst) return;

        if (el.type === 'checkbox') {
            if (el.checked) usage[countsAgainst] = (usage[countsAgainst] || 0) + 1;
        } else if (el.tagName === 'SELECT') {
            if (el.value !== '' && el.value !== '0' && !el.value.includes('|no')) {
                usage[countsAgainst] = (usage[countsAgainst] || 0) + 1;
            }
        }
    });

    document.querySelectorAll('.category-section').forEach(section => {
        const catNameTrimmed = (section.dataset.category || '').trim();
        const catData = categories[section.dataset.category];

        if (catData && catData.limit > 0) {
            const used = usage[catNameTrimmed] || 0;
            const remaining = Math.max(0, catData.limit - used);
            const badge = section.querySelector('.cat-limit-badge');
            if (badge) {
                badge.innerText = `Limit: ${catData.limit} (Left: ${remaining})`;
                if (remaining <= 0) {
                    badge.className = 'cat-limit-badge text-[10px] bg-red-500 text-white px-3 py-1 rounded-lg font-bold uppercase tracking-wide border border-red-600';
                } else {
                    badge.className = 'cat-limit-badge text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold uppercase tracking-wide border border-amber-200';
                }
            }
        }
    });

    // Disable/Enable main Attraction checkboxes
    document.querySelectorAll('.ride-checkbox').forEach(cb => {
        const card = cb.closest('.product-card');
        const targetCat = (card.dataset.countsAgainst || '').trim();
        if (card.dataset.productSoldOut === 'true') return;

        let targetData = null;
        for (let key in categories) {
            if (key.trim() === targetCat) {
                targetData = categories[key];
                break;
            }
        }

        if (targetData && targetData.limit > 0) {
            const used = usage[targetCat] || 0;
            if (!cb.checked && used >= targetData.limit) {
                cb.disabled = true;
                card.classList.add('disabled-card');
            } else {
                cb.disabled = false;
                card.classList.remove('disabled-card');
            }
        }
    });

    // Disable/Enable Extra (Addons/Dropdowns) elements
    document.querySelectorAll('.ext-price[data-counts-against]').forEach(el => {
        const targetCat = (el.dataset.countsAgainst || '').trim();
        if (!targetCat) return;

        let targetData = null;
        for (let key in categories) {
            if (key.trim() === targetCat) {
                targetData = categories[key];
                break;
            }
        }

        if (targetData && targetData.limit > 0) {
            const used = usage[targetCat] || 0;
            const isSelected = (el.type === 'checkbox' ? el.checked : (el.value !== '' && el.value !== '0' && !el.value.includes('|no')));
            
            if (!isSelected && used >= targetData.limit) {
                el.disabled = true;
                if (el.type === 'checkbox') el.closest('label').classList.add('opacity-50', 'pointer-events-none');
            } else {
                el.disabled = false;
                if (el.type === 'checkbox') el.closest('label').classList.remove('opacity-50', 'pointer-events-none');
            }
        }
    });
};

window.handleSelection = function(checkbox) {
    const card = checkbox.closest('.product-card');
    const limitCategory = (card.dataset.countsAgainst || '').trim();
    const categories = window.bookingAppData.categories;

    if (checkbox.checked) {
        let catLimit = 0;
        for (let key in categories) {
            if (key.trim() === limitCategory) {
                catLimit = categories[key].limit;
                break;
            }
        }

        if (limitCategory && catLimit > 0) {
            // Calculate usage manually to see if this addition is okay
            let currentUsage = (globalCategoryBooked[limitCategory] || 0);
            
            document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
                if (cb !== checkbox && (cb.closest('.product-card').dataset.countsAgainst || '').trim() === limitCategory) {
                    currentUsage++;
                }
            });

            document.querySelectorAll('.ext-price[data-counts-against]').forEach(el => {
                if ((el.dataset.countsAgainst || '').trim() === limitCategory) {
                    if (el.type === 'checkbox' && el.checked) currentUsage++;
                    else if (el.tagName === 'SELECT' && el.value !== '' && el.value !== '0' && !el.value.includes('|no')) currentUsage++;
                }
            });

            if (currentUsage + 1 > catLimit) {
                const alpine = document.querySelector('[x-data="bookingApp"]').__x.$data;
                if (alpine) {
                    alpine.limitExceededCategory = limitCategory;
                    alpine.limitExceededLimit = catLimit;
                    alpine.modals.limitExceeded = true;
                } else {
                    showToast("Limit Reached", `Max ${catLimit} items for ${limitCategory}.`, "warning");
                }
                checkbox.checked = false;
                return;
            }
        }
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }

    triggerRecalculate();
    saveCurrentExtrasState();
    updateDynamicExtras();
    updateCategoryLimitsUI();
};

window.handleExtraSelection = function(element) {
    const limitCategory = (element.dataset.countsAgainst || '').trim();
    const categories = window.bookingAppData.categories;

    const isSelected = (element.type === 'checkbox' ? element.checked : (element.value !== '' && element.value !== '0' && !element.value.includes('|no')));

    if (isSelected) {
        let catLimit = 0;
        for (let key in categories) {
            if (key.trim() === limitCategory) {
                catLimit = categories[key].limit;
                break;
            }
        }

        if (limitCategory && catLimit > 0) {
            let currentUsage = (globalCategoryBooked[limitCategory] || 0);
            
            document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
                if ((cb.closest('.product-card').dataset.countsAgainst || '').trim() === limitCategory) {
                    currentUsage++;
                }
            });

            document.querySelectorAll('.ext-price[data-counts-against]').forEach(el => {
                if (el !== element && (el.dataset.countsAgainst || '').trim() === limitCategory) {
                    if (el.type === 'checkbox' && el.checked) currentUsage++;
                    else if (el.tagName === 'SELECT' && el.value !== '' && el.value !== '0' && !el.value.includes('|no')) currentUsage++;
                }
            });

            if (currentUsage + 1 > catLimit) {
                const alpine = document.querySelector('[x-data="bookingApp"]').__x.$data;
                if (alpine) {
                    alpine.limitExceededCategory = limitCategory;
                    alpine.limitExceededLimit = catLimit;
                    alpine.modals.limitExceeded = true;
                } else {
                    showToast("Limit Reached", `Max ${catLimit} items for ${limitCategory}.`, "warning");
                }
                
                // Revert
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

window.finalizeBooking = async function() {
    if (isProceeding) return;
    isProceeding = true;
    const btn = document.getElementById('btnSaveFinal');
    const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-rounded animate-spin">sync</span> Saving...';
    btn.disabled = true;

    const form = document.getElementById('combinedBookingForm');
    const fd = new FormData(form);
    fd.append('action', 'save_full_booking');

    // Ensure we have a final total even if override is empty
    const override = document.getElementById('override_total').value;
    if (!override) {
        const totalDisp = document.getElementById('disp_total').innerText.replace('$', '');
        fd.set('final_total', totalDisp);
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

window.filterRides = function() {
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

window.saveCurrentExtrasState = function() {
    if (!window.bookingAppData) return;
    const container = document.getElementById('dynamicExtrasContainer');
    if (!container) return;

    container.querySelectorAll('.ext-price').forEach(el => {
        if (el.type === 'checkbox') {
            window.bookingAppData.savedExtras[el.name] = el.checked ? "1" : "0";
        } else if (el.tagName === 'SELECT') {
            window.bookingAppData.savedExtras[el.name] = el.value;
        }
    });
};

window.updateDynamicExtras = function() {
    saveCurrentExtrasState();

    const activeCategories = new Set();
    document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
        activeCategories.add(cb.closest('.product-card').dataset.category);
    });

    const container = document.getElementById('dynamicExtrasContainer');
    if (!container) return;
    
    container.innerHTML = '';

    let hasBlocks = false;
    let glHtml = renderCategoryBlockHTML('General Logistics');
    let catHtmlCombined = '';

    activeCategories.forEach(catName => {
        if (catName !== 'General Logistics') {
            let cHtml = renderCategoryBlockHTML(catName);
            if (cHtml) {
                catHtmlCombined += cHtml;
            }
        }
    });

    if (glHtml) {
        container.innerHTML += `
            <div class="flex flex-col gap-4">
                <h4 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider pl-1">General Logistics</h4>
                ${glHtml}
            </div>
        `;
        hasBlocks = true;
    }

    if (catHtmlCombined) {
        container.innerHTML += `
            <div class="flex flex-col gap-4 ${glHtml ? 'mt-6 pt-6 border-t border-slate-200' : ''}">
                <h4 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider pl-1">Attraction Specific Extras</h4>
                ${catHtmlCombined}
            </div>
        `;
        hasBlocks = true;
    }

    if (!hasBlocks) {
        container.innerHTML = '<p class="text-xs text-slate-500 italic py-4 col-span-full">Select attractions to view related extras.</p>';
    }
    triggerRecalculate();
};

function renderCategoryBlockHTML(catName) {
    let catHtml = '';
    const config = window.bookingAppData.config;
    const savedExtras = window.bookingAppData.savedExtras;

    if (config.dropdowns[catName]) {
        config.dropdowns[catName].forEach(dd => {
            let key = `dd_${dd.id}`;
            let val = savedExtras[key] || '';
            let countsAgainst = (dd.counts_against || '').trim();
            let placeholder = `<option value="" data-price="0" ${val == '' ? 'selected' : ''}>-- Select Option --</option>`;
            let opts = placeholder + dd.options.map(o => `<option value="${o.id}" data-price="${o.option_price}" ${val == o.id ? 'selected' : ''}>${o.option_label} (+$${o.option_price})</option>`).join('');
            catHtml += `<div class="mt-3"><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1 ml-1">${dd.label}</label><select name="${key}" data-counts-against="${countsAgainst}" class="input-field !py-2 text-sm ext-price bg-white cursor-pointer" onchange="handleExtraSelection(this)">${opts}</select></div>`;
        });
    }

    if (config.questions[catName]) {
        config.questions[catName].forEach(q => {
            let key = `q_${q.id}`;
            let val = savedExtras[key] || '';
            let yesSel = (val == (q.yes_price + '|yes')) ? 'selected' : '';
            let noSel = (val == (q.no_price + '|no')) ? 'selected' : '';
            let placeholderSel = (!yesSel && !noSel) ? 'selected' : '';
            catHtml += `<div class="mt-3"><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1 ml-1">${q.question_text}</label><select name="${key}" class="input-field !py-2 text-sm ext-price bg-white cursor-pointer" onchange="handleExtraSelection(this)"><option value="" data-price="0" ${placeholderSel}>-- Select Choice --</option><option value="${q.yes_price}|yes" data-price="${q.yes_price}" ${yesSel}>${q.yes_label} (+$${q.yes_price})</option><option value="${q.no_price}|no" data-price="${q.no_price}" ${noSel}>${q.no_label} (+$${q.no_price})</option></select></div>`;
        });
    }

    if (config.addons[catName]) {
        config.addons[catName].forEach(addon => {
            let key = `add_${addon.id}`;
            let isChecked = (savedExtras[key] == '1');
            let countsAgainst = (addon.counts_against || '').trim();
            catHtml += `<label class="flex items-center gap-3 mt-3 p-3 bg-white border border-slate-200 rounded-xl hover:border-[#9E6B73] cursor-pointer transition shadow-sm h-[42px]"><input type="checkbox" name="${key}" value="1" class="ext-price w-4 h-4 text-[#9E6B73] focus:ring-[#9E6B73]" data-price="${addon.addon_price}" data-counts-against="${countsAgainst}" ${isChecked ? 'checked' : ''} onchange="handleExtraSelection(this)"><span class="text-sm font-bold text-slate-700 flex-1">${addon.addon_label}</span><span class="text-xs font-bold text-[#9E6B73] bg-[#9E6B73]/10 px-2 py-1 rounded-lg">+$${addon.addon_price}</span></label>`;
        });
    }

    if (catHtml) {
        return `
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-2">
                    <span class="material-symbols-rounded text-[#9E6B73]">category</span>
                    <h4 class="font-extrabold text-slate-800 text-sm uppercase tracking-wide">${catName}</h4>
                </div>
                ${catHtml}
            </div>
        `;
    }
    return '';
}

window.calcDuration = function() {
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

window.selectDurationCard = function(labelEl) {
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

window.updateDurationCost = function(radio) {
    if (radio.checked) {
        const durCostInput = document.getElementById('duration_cost');
        if (durCostInput) durCostInput.value = radio.dataset.price;
        selectDurationCard(radio.closest('.duration-card'));
    }
};

window.updateDeliveryCost = function(sel) {
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

window.triggerRecalculate = function() {
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
            attractionsCost += parseFloat(card.dataset.price || 0);
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

window.calculateFinalTotals = function() {
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
    if (surLabel) surLabel.innerText = `Processing Fee (${(rate*100).toFixed(1)}%)`;
    
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

window.openReviewModal = function() {
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

    alpine.modals.review = true;
};

function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

window.finalizeBooking = async function() {
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
            if (window.location.pathname.includes('/supervisor/')) {
                window.location.href = '/supervisor/calendar';
            } else if (window.location.pathname.includes('/admin/')) {
                window.location.href = '/admin/calendar';
            } else {
                // Fallback for prefix-less or other cases
                window.location.href = '/admin/calendar';
            }
        }, 1000);
    } catch (e) {
        showToast("Error", e.message, "error");
        btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> Confirm & Save Booking';
        btn.disabled = false;
        isProceeding = false;
    }
};
 
window.deleteAndExit = function() {
    isProceeding = true;
    const bookingIdEl = document.getElementById('booking_id');
    if (bookingIdEl && bookingIdEl.value) {
        let fd = new FormData();
        fd.append('action', 'delete_draft');
        fd.append('booking_id', bookingIdEl.value);
        fd.append('_token', window.bookingAppData.csrfToken);
        navigator.sendBeacon('/api/bookings/handler', fd);
    }
    
    // Check if we're in supervisor mode or admin
    if (window.location.pathname.includes('/supervisor/')) {
        window.location.href = '/supervisor/calendar';
    } else if (window.location.pathname.includes('/admin/')) {
        window.location.href = '/admin/calendar';
    } else {
        // Fallback for prefix-less or other cases
        window.location.href = '/admin/calendar';
    }
};

// --- DOM READY ---
document.addEventListener('DOMContentLoaded', () => {
    // Initialize calCursor from bridge or now
    const dateInput = document.getElementById('event_date');
    calCursor = new Date(dateInput && dateInput.value ? dateInput.value : Date.now());
    
    // Initial triggers
    if (typeof calLoad === 'function') calLoad();
    if (typeof checkRealTimeAvailability === 'function') checkRealTimeAvailability();
    if (typeof triggerRecalculate === 'function') triggerRecalculate();
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
