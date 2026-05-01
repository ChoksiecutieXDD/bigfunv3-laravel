// availability-sync.js - Decoupled Availability & Dynamic UI Synchronization Module
// This module handles real-time slot checking and category limit calculations.

(function () {
    // --- AVAILABILITY LOGIC GLOBALS ---
    window.globalCategoryBooked = {};
    window.availabilityAbortController = null;
    window.lastCheckedDate = null;
    window.availabilityCheckTimer = null;
    window.lastActiveCategoriesStr = "";

    /**
     * Main API entry point to sync server-side availability with the frontend.
     * Implements visual pulsing feedback to confirm live activity.
     */
    window.checkRealTimeAvailability = async function (silent = false) {
        const dateEl = document.getElementById('event_date');
        const invoiceEl = document.getElementById('invoice_number');
        if (!dateEl) return;

        const date = dateEl.value;
        const invoice = invoiceEl ? invoiceEl.value : '';
        const token = window.bookingAppData ? window.bookingAppData.formToken : '';
        const bookingId = window.bookingAppData ? window.bookingAppData.bookingId : '';

        if (!date) {
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.innerText = 'SELECT DATE';
                badge.className = 'status-badge status-checking';
            });
            return;
        }

        window.lastCheckedDate = date;

        // Visual Sync Progress: Removed flickering effect
        if (!silent) {
            document.querySelectorAll('.status-badge').forEach(badge => {
                // badge.classList.add('opacity-50', 'animate-pulse'); // Removed
            });
        }

        // Use current AbortController system to prevent hangs
        if (window.availabilityAbortController) window.availabilityAbortController.abort();
        window.availabilityAbortController = new AbortController();

        try {
            console.log(`Syncing availability for date: ${date} (Silent: ${silent})`);
            const response = await fetch(`/api/bookings/check-availability?date=${date}&invoice=${invoice}&booking_id=${bookingId}&token=${token}`, {
                signal: window.availabilityAbortController.signal
            });
            if (!response.ok) {
                const text = await response.text();
                console.error("Availability API Error:", text);
                throw new Error("API call failed: " + response.status);
            }
            const data = await response.json();

            if (data.status === 'success' && data.products) {
                window.globalCategoryBooked = {};

                // 1. ADD THIS: Normalize the API response keys so they match the frontend
                const normalizedApiProducts = {};

                // Handles both Object-based and Array-based API responses
                if (Array.isArray(data.products)) {
                    data.products.forEach(p => {
                        if (p.name) normalizedApiProducts[p.name.toLowerCase().replace(/\s+/g, ' ').trim()] = p;
                    });
                } else {
                    for (let key in data.products) {
                        normalizedApiProducts[key.toLowerCase().replace(/\s+/g, ' ').trim()] = data.products[key];
                    }
                }

                // Use the API's categorical counts as the base if provided
            if (data.categories) {
                for (let cat in data.categories) {
                    const catKey = cat.trim().toLowerCase();
                    // The server now EXCLUDES current user's selections from 'booked',
                    // so we can use this as the base for 'globalCategoryBooked'
                    if (data.categories[cat]) {
                        window.globalCategoryBooked[catKey] = data.categories[cat].booked || 0;
                        // Also sync the limit in case it changed on server
                        if (window.bookingAppData && window.bookingAppData.categories && window.bookingAppData.categories[cat]) {
                            window.bookingAppData.categories[cat].limit = data.categories[cat].limit;
                        }
                    }
                }
            }

                document.querySelectorAll('.product-card').forEach(card => {
                    try {
                        const badge = card.querySelector('.status-badge');
                        // DEEP FIX: Prioritize cleanup at the very top of the processed loop
                        if (badge) badge.classList.remove('opacity-50', 'animate-pulse');

                        const rawName = card.dataset.name ? card.dataset.name.trim() : null;
                        if (!rawName) return;

                        // Sanitize cleanName to ensure exact match with backend (lowercase, trimmed, single-spaced)
                        const cleanName = rawName.toLowerCase().replace(/\s+/g, ' ').trim();
                        const checkbox = card.querySelector('.ride-checkbox');

                        const statusWrapper = card.querySelector('.status-wrapper');
                        const actionText = card.querySelector('.action-text');
                        const targetCat = (card.dataset.countsAgainst || '').trim().toLowerCase();
                        const itemLimit = parseInt(card.dataset.dailyLimit) || 0;
                        const initialStock = (card.dataset.stock !== undefined && card.dataset.stock !== '') ? parseInt(card.dataset.stock) : 999;
                        const UNLIMITED_THRESHOLD = 99;

                        // LIVE SELECTION BADGES
                        if (statusWrapper) {
                            statusWrapper.querySelectorAll('.live-item-badge').forEach(b => b.remove());
                            if (data.live_selections && data.live_selections[cleanName]) {
                                data.live_selections[cleanName].forEach(user => {
                                    const lBadge = document.createElement('span');
                                    lBadge.className = 'live-item-badge text-[8px] bg-slate-800 text-white px-1.5 py-0.5 rounded ml-1 font-bold uppercase whitespace-nowrap border border-slate-600';
                                    lBadge.innerText = `${user.name} | ${user.role}`;
                                    statusWrapper.appendChild(lBadge);
                                });
                            }
                        }

                        // 2. CHANGE THIS: Check against our new normalizedApiProducts object
                        if (normalizedApiProducts[cleanName]) {
                            const left = normalizedApiProducts[cleanName].left;

                            if (left <= 0) {
                                card.dataset.productSoldOut = 'true';
                                card.classList.add('disabled-card');
                                card.classList.remove('selected');
                                if (checkbox) {
                                    checkbox.checked = false;
                                    checkbox.disabled = true;
                                }
                                if (badge) {
                                    badge.innerText = 'SOLD OUT';
                                    badge.className = 'status-badge status-soldout';
                                }
                                if (actionText) actionText.innerText = 'Not Available';
                            } else {
                                card.dataset.productSoldOut = 'false';
                                card.classList.remove('disabled-card');
                                if (checkbox) checkbox.disabled = false;
                                if (badge) {
                                    if (left >= UNLIMITED_THRESHOLD) {
                                        badge.innerText = 'UNLIMITED';
                                        badge.className = 'status-badge status-avail';
                                    } else {
                                        badge.innerText = `${left} AVAILABLE`;
                                        badge.className = (left <= 2) ? 'status-badge status-limited' : 'status-badge status-avail';
                                    }
                                }
                                if (actionText) actionText.innerText = card.classList.contains('selected') ? 'Selected' : 'Click to select';
                            }
                        } else {
                            card.dataset.productSoldOut = 'false';
                            card.classList.remove('disabled-card');
                            if (checkbox) checkbox.disabled = false;
                            if (badge) {
                                if (initialStock >= UNLIMITED_THRESHOLD) {
                                    badge.innerText = 'UNLIMITED';
                                    badge.className = 'status-badge status-avail';
                                } else if (initialStock > 0) {
                                    badge.innerText = `${initialStock} AVAILABLE`;
                                    badge.className = (initialStock <= 2) ? 'status-badge status-limited' : 'status-badge status-avail';
                                } else {
                                    badge.innerText = 'SOLD OUT';
                                    badge.className = 'status-badge status-soldout';
                                }
                            }
                            if (actionText) actionText.innerText = card.classList.contains('selected') ? 'Selected' : 'Click to select';
                        }
                        
                        // IMPORTANT: Always clear any temporary "Syncing..." or "pulse" state
                        if (badge) {
                            badge.classList.remove('opacity-50', 'animate-pulse');
                            badge.style.opacity = '1';
                        }
                   } catch (cardErr) {
                        console.error("Error processing product card:", cardErr, card);
                    }
                });

                window.updateDynamicExtras();
                window.updateCategoryLimitsUI();
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                console.log("Availability check aborted (superseded by new request).");
            } else {
                console.error("Availability Check Critical Error:", error);
            }
        } finally {
            // BULLETPROOF: Global sweep of ALL status badges on the page
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.classList.remove('opacity-50', 'animate-pulse');
                badge.style.opacity = '1';

                const badgeText = (badge.innerText || '').trim().toUpperCase();
                // Match case-insensitively and detect common variants/styling
                if (badgeText.includes('SYNCING') || badgeText.includes('CHECKING')) {
                    const card = badge.closest('.product-card');
                    if (card) {
                        const itemLimit = parseInt(card.dataset.dailyLimit) || 0;
                        if (itemLimit === 0) {
                            badge.innerText = 'UNLIMITED';
                            badge.className = 'status-badge status-avail';
                        } else {
                            badge.innerText = `${itemLimit} AVAILABLE`;
                            badge.className = (itemLimit <= 2) ? 'status-badge status-limited' : 'status-badge status-avail';
                        }
                    }
                }
            });
            window.availabilityAbortController = null;
        }
    };

    /**
     * Recalculate remaining capacity for every category section card and badge.
     */
    window.updateCategoryLimitsUI = function () {
        let usage = {};
        if (!window.bookingAppData) return;
        const categories = window.bookingAppData.categories;
        
        // Start with server's count of OTHERS' bookings
        for (let cat in categories) {
            const catKey = cat.trim().toLowerCase();
            usage[catKey] = window.globalCategoryBooked[catKey] || 0;
        }

        const rideNamesCounted = new Set();
        document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
            const card = cb.closest('.product-card');
            const limitCategory = (card.dataset.countsAgainst || '').trim().toLowerCase();
            const rideName = (card.dataset.name || '').trim().toLowerCase().replace(/\s+/g, ' ');
            if (limitCategory) usage[limitCategory] = (usage[limitCategory] || 0) + 1;
            if (rideName) rideNamesCounted.add(rideName);
        });

        // Also count selected Extras (Addons, Dropdowns, Questions)
        // CRITICAL: Avoid double-counting items that are also selected as rides (synced items)
        document.querySelectorAll('.ext-price').forEach(el => {
            if (!el.dataset.countsAgainst) return;
            const catKey = el.dataset.countsAgainst.trim().toLowerCase();
            let isSelected = false;
            let label = "";

            if (el.type === 'checkbox') {
                isSelected = el.checked;
                label = (el.closest('label').querySelector('span')?.innerText || "").toLowerCase().trim();
            } else if (el.tagName === 'SELECT') {
                isSelected = el.value !== '' && el.value !== '0' && !el.value.includes('|no');
                // For selects/questions/dropdowns, identify the label
                const labelEl = el.closest('div').querySelector('label');
                label = (labelEl?.innerText || "").toLowerCase().trim();
                
                // If it's a dropdown, check if the selected OPTION matches a counted ride
                if (isSelected && el.options[el.selectedIndex]) {
                    const optLabel = el.options[el.selectedIndex].text.split('(')[0].toLowerCase().trim();
                    if (rideNamesCounted.has(optLabel)) return;
                }
            }

            // If the addon label matches a ride ALREADY COUNTED above, don't double count
            if (isSelected && rideNamesCounted.has(label)) return;

            if (isSelected) usage[catKey] = (usage[catKey] || 0) + 1;
        });

        document.querySelectorAll('.category-section').forEach(section => {
            const catName = (section.dataset.category || '').trim().toLowerCase();
            let catData = null;
            for (let key in categories) {
                if (key.trim().toLowerCase() === catName) {
                    catData = categories[key];
                    break;
                }
            }

            if (catData && catData.limit > 0) {
                const used = usage[catName] || 0;
                const remaining = Math.max(0, catData.limit - used);
                const badge = section.querySelector('.cat-limit-badge');
                if (badge) {
                    badge.innerText = `LIMIT: ${catData.limit} (LEFT: ${remaining})`;
                    if (remaining <= 0) {
                        badge.className = 'cat-limit-badge text-[10px] bg-red-100 text-red-700 px-3 py-1 rounded-lg font-bold uppercase tracking-wide border border-red-200';
                    } else {
                        badge.className = 'cat-limit-badge text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold uppercase tracking-wide border border-amber-200';
                    }
                }
            }
        });

        // Loop through all product cards and disable checkboxes if group limit is reached
        document.querySelectorAll('.ride-checkbox').forEach(cb => {
            const card = cb.closest('.product-card');
            const targetCat = (card.dataset.countsAgainst || '').trim().toLowerCase();
            if (card.dataset.productSoldOut === 'true') return;

            let targetData = null;
            for (let key in categories) {
                if (key.trim().toLowerCase() === targetCat) {
                    targetData = categories[key];
                    break;
                }
            }

            if (targetData && targetData.limit > 0) {
                const used = usage[targetCat] || 0;
                // Only disable if NOT checked and limit is reached
                if (!cb.checked && used >= targetData.limit) {
                    cb.disabled = true;
                    card.classList.add('disabled-card');
                } else {
                    cb.disabled = false;
                    card.classList.remove('disabled-card');
                }
            }
        });

        // Loop through all Extras and disable them if group limit is reached
        document.querySelectorAll('.ext-price').forEach(el => {
            if (!el.dataset.countsAgainst) return;
            const targetCat = el.dataset.countsAgainst.trim().toLowerCase();
            
            let targetData = null;
            for (let key in categories) {
                if (key.trim().toLowerCase() === targetCat) {
                    targetData = categories[key];
                    break;
                }
            }

            if (targetData && targetData.limit > 0) {
                const used = usage[targetCat] || 0;
                let isSelected = false;
                if (el.type === 'checkbox') isSelected = el.checked;
                else if (el.tagName === 'SELECT') isSelected = el.value !== '' && el.value !== '0' && !el.value.includes('|no');

                // Only disable if NOT selected and limit is reached/exceeded
                if (!isSelected && used >= targetData.limit) {
                    el.disabled = true;
                    if (el.type === 'checkbox') {
                        el.closest('label').classList.add('opacity-40', 'pointer-events-none');
                    } else {
                        el.classList.add('opacity-40', 'pointer-events-none');
                    }
                } else {
                    el.disabled = false;
                    if (el.type === 'checkbox') {
                        el.closest('label').classList.remove('opacity-40', 'pointer-events-none');
                    } else {
                        el.classList.remove('opacity-40', 'pointer-events-none');
                    }
                }
            }
        });
    };

    /**
     * Refresh the dynamic extras sidebar based on selected attractions and category dependencies.
     */
    window.updateDynamicExtras = function () {
        const items = window.bookingAppData.selectedItems || {};
        const selectedRideNames = new Set(
            (Array.isArray(items) ? items : Object.keys(items))
            .map(s => s.toLowerCase().trim())
        );

        // --- Just-in-Time Parity Sync ---
        // CRITICAL: Run this BEFORE saving state so we don't accidentally wipe selections on first render
        if (window.bookingAppData && window.bookingAppData.config) {
            
            // 1. Sync Addons - Only if not explicitly '0'
            if (window.bookingAppData.config.addons) {
                for (let cat in window.bookingAppData.config.addons) {
                    window.bookingAppData.config.addons[cat].forEach(addon => {
                        const addonName = (addon.addon_label || '').toLowerCase().trim();
                        const key = `add_${addon.id}`;
                        if (addonName && selectedRideNames.has(addonName)) {
                            if (!window.bookingAppData.savedExtras[key] || window.bookingAppData.savedExtras[key] !== "0") {
                                window.bookingAppData.savedExtras[key] = "1";
                            }
                        }
                    });
                }
            }

            // 2. Sync Questions - Only if not explicitly 'no' or '0'
            if (window.bookingAppData.config.questions) {
                for (let cat in window.bookingAppData.config.questions) {
                    window.bookingAppData.config.questions[cat].forEach(q => {
                        const qName = (q.question_text || '').toLowerCase().trim();
                        const key = `q_${q.id}`;
                        if (qName && selectedRideNames.has(qName)) {
                            const val = window.bookingAppData.savedExtras[key];
                            if (!val || (!val.endsWith('|no') && val !== "0")) {
                                window.bookingAppData.savedExtras[key] = `${q.yes_price}|yes`;
                            }
                        }
                    });
                }
            }

            // 3. Sync Dropdowns - Only if not explicitly '0'
            if (window.bookingAppData.config.dropdowns) {
                for (let cat in window.bookingAppData.config.dropdowns) {
                    window.bookingAppData.config.dropdowns[cat].forEach(dd => {
                        if (dd.options) {
                            dd.options.forEach(opt => {
                                const optName = (opt.option_label || '').toLowerCase().trim();
                                const key = `dd_${dd.id}`;
                                if (optName && selectedRideNames.has(optName)) {
                                    if (!window.bookingAppData.savedExtras[key] || window.bookingAppData.savedExtras[key] !== "0") {
                                        window.bookingAppData.savedExtras[key] = opt.id;
                                    }
                                }
                            });
                        }
                    });
                }
            }
        }

        // Now save the current state from any existing DOM elements
        window.saveCurrentExtrasState(true);

        const activeCategories = new Set();
        document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
            activeCategories.add(cb.closest('.product-card').dataset.category);
        });

        const container = document.getElementById('dynamicExtrasContainer');
        if (!container) return;

        const activeCategoriesArray = Array.from(activeCategories).sort();
        const activeCategoriesStr = activeCategoriesArray.join('|');
        const activeOverridesStr = JSON.stringify(window.bookingAppData.activeOverrides || {});
        
        // --- PERFORMANCE/UX FIX: Only re-render if categories changed ---
        // We avoid re-rendering just for override state changes or if user is typing
        const focusedEl = document.activeElement;
        const isTypingInExtras = focusedEl && focusedEl.closest('#dynamicExtrasContainer') && (focusedEl.tagName === 'INPUT' || focusedEl.tagName === 'SELECT');

        // CATEGORY CHANGE is the main reason to re-render the whole container
        if (window.lastActiveCategoriesStr === activeCategoriesStr) {
            // Even if activeOverridesStr changed, we don't want to re-render the WHOLE container 
            // if it's just a price update. Individual visibility logic for wraps is handled in updateExtraPrice.
            if (!isTypingInExtras) {
                window.updateCategoryLimitsUI();
                if (typeof window.triggerRecalculate === 'function') window.triggerRecalculate();
            }
            return;
        }

        window.lastActiveCategoriesStr = activeCategoriesStr;
        window.lastActiveOverridesStr = activeOverridesStr;
        if (focusedEl && focusedEl.closest('#dynamicExtrasContainer') && focusedEl.tagName === 'SELECT') {
            console.log("Skipping extras re-render: User is interacting with a dropdown.");
            return;
        }
        container.innerHTML = '';
        const configCategories = window.bookingAppData.categories;

        function createBlockWithBadge(catName, html) {
            if (!html) return '';
            let badgeHtml = '';
            const catKey = catName.trim().toLowerCase();
            let catData = null;
            for (let key in configCategories) {
                if (key.trim().toLowerCase() === catKey) {
                    catData = configCategories[key];
                    break;
                }
            }
            if (!catData) catData = configCategories[catName];

            if (catData && catData.limit > 0) {
                badgeHtml = `<span class="cat-limit-badge text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold uppercase tracking-wide border border-amber-200 ml-auto">Limit: ${catData.limit}</span>`;
            }
            return `
                <div class="category-section" data-category="${catName}">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-2 mb-3">
                        <span class="material-symbols-rounded text-[#9E6B73] text-sm">tune</span>
                        <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">${catName}</h4>
                        ${badgeHtml}
                    </div>
                    ${html}
                </div>
            `;
        }

        let hasBlocks = false;
        let glHtml = window.renderCategoryBlockHTML('General Logistics');
        if (glHtml) {
            container.innerHTML += createBlockWithBadge('General Logistics', glHtml);
            hasBlocks = true;
        }

        activeCategories.forEach(catName => {
            if (catName !== 'General Logistics') {
                let cHtml = window.renderCategoryBlockHTML(catName);
                if (cHtml) {
                    container.innerHTML += (hasBlocks ? '<div class="mt-8 pt-6 border-t border-slate-200"></div>' : '') + createBlockWithBadge(catName, cHtml);
                    hasBlocks = true;
                }
            }
        });

        if (!hasBlocks) {
            container.innerHTML = '<p class="text-xs text-slate-500 italic py-4 col-span-full">Select attractions to view related extras.</p>';
        }

        window.updateCategoryLimitsUI();
    };

    /**
     * Renders HTML for questions, addons, and dropdowns for a specific category.
     */
    window.renderCategoryBlockHTML = function (catName) {
        let catHtml = '';
        if (!window.bookingAppData) return '';
        const config = window.bookingAppData.config || { questions: {}, addons: {}, dropdowns: {} };
        let savedExtras = window.bookingAppData.savedExtras || {};
        if (Array.isArray(savedExtras)) {
            // Convert empty array to object if needed
            savedExtras = {};
        }
        
        const items = window.bookingAppData.selectedItems || {};
        const selectedRideNames = new Set(
            (Array.isArray(items) ? items : Object.keys(items))
            .map(s => s.toLowerCase().trim())
        );

        if (config.dropdowns[catName]) {
            config.dropdowns[catName].forEach(dd => {
                let key = `dd_${dd.id}`;
                let val = savedExtras[key] || '';
                let countsAgainst = (dd.counts_against || catName).trim();
                let ddLabel = (dd.label || '').toLowerCase().trim();

                let placeholder = `<option value="" data-price="0" ${val == '' ? 'selected' : ''}>-- Select Option --</option>`;
                let opts = placeholder + dd.options.map(o => {
                    const optLabel = (o.option_label || '').toLowerCase().trim();
                    const fullMatch = `${ddLabel}: ${optLabel}`;
                    const dashMatch = `${ddLabel} - ${optLabel}`;
                    const isSelectedByRide = selectedRideNames.has(optLabel) || selectedRideNames.has(fullMatch) || selectedRideNames.has(dashMatch);
                    
                    return `<option value="${o.id}" data-price="${o.option_price}" ${(val == o.id || isSelectedByRide) ? 'selected' : ''}>${o.option_label} (+$${o.option_price})</option>`;
                }).join('');
                
                let selectedId = val;
                if (!selectedId) {
                    const matchedOpt = dd.options.find(o => {
                        const optLabel = (o.option_label || '').toLowerCase().trim();
                        const fullMatch = `${ddLabel}: ${optLabel}`;
                        const dashMatch = `${ddLabel} - ${optLabel}`;
                        return selectedRideNames.has(optLabel) || selectedRideNames.has(fullMatch) || selectedRideNames.has(dashMatch);
                    });
                    if (matchedOpt) selectedId = matchedOpt.id;
                }
                let selectedOpt = dd.options.find(o => o.id == selectedId);
                let defaultPrice = selectedOpt ? selectedOpt.option_price : 0;
                let currentPrice = (window.bookingAppData.extraPrices && window.bookingAppData.extraPrices[key] !== undefined) ? window.bookingAppData.extraPrices[key] : defaultPrice;
                let isSelected = selectedId !== '' && selectedId !== '0';
                
                const isOverrideActive = window.bookingAppData.activeOverrides && window.bookingAppData.activeOverrides[key];

                catHtml += `
                    <div class="mt-4 first:mt-0">
                        <label class="text-[10px] font-black text-slate-400 uppercase block mb-1.5 ml-1 tracking-widest">${dd.label}</label>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="flex-1">
                                <select name="${key}" data-counts-against="${countsAgainst}" data-original-value="${val}" class="input-field !py-3 text-sm ext-price bg-white cursor-pointer w-full border-slate-200 focus:border-[#9E6B73] transition shadow-sm" onchange="window.handleExtraSelection(this)">${opts}</select>
                            </div>
                            
                            <div id="ov_wrap_${key}" class="w-full sm:w-32 shrink-0 ${ isSelected ? '' : 'hidden' }">
                                <div class="text-[8px] font-bold text-slate-400 mb-1 uppercase tracking-tighter">Price Override</div>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3.5 text-slate-400 text-[11px] font-black group-focus-within:text-[#9E6B73] transition-colors">$</span>
                                    <input type="number" step="0.01" class="w-full bg-white border border-slate-200 rounded-xl py-3 pl-8 pr-3 text-[11px] font-black text-slate-700 focus:border-[#9E6B73] transition shadow-sm outline-none" 
                                            value="${currentPrice}" oninput="window.updateExtraPrice('${key}', this.value)" placeholder="${defaultPrice}">
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
        }

        if (config.questions[catName]) {
            config.questions[catName].forEach(q => {
                let key = `q_${q.id}`;
                let val = savedExtras[key] || '';
                let countsAgainst = (q.counts_against || catName).trim();
                let qText = (q.question_text || '').toLowerCase().trim();
                
                // Match if the question text is in selected items (usually "Question Text (Yes)")
                let matchesRide = [...selectedRideNames].some(name => name.includes(qText));

                let yesSel = (val == (q.yes_price + '|yes') || matchesRide) ? 'selected' : '';
                let noSel = (val == (q.no_price + '|no')) ? 'selected' : '';
                let placeholderSel = (!yesSel && !noSel) ? 'selected' : '';
                let isSelected = yesSel || noSel;
                
                let currentPrice = (window.bookingAppData.extraPrices && window.bookingAppData.extraPrices[key] !== undefined) ? window.bookingAppData.extraPrices[key] : (isSelected ? (yesSel ? q.yes_price : q.no_price) : 0);
                let defaultPrice = (yesSel ? q.yes_price : (noSel ? q.no_price : 0));

                const isOverrideActive = window.bookingAppData.activeOverrides && window.bookingAppData.activeOverrides[key];

                catHtml += `
                    <div class="mt-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase block mb-1.5 ml-1 tracking-widest">${q.question_text}</label>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="flex-1">
                                <select name="${key}" data-counts-against="${countsAgainst}" data-original-value="${val}" class="input-field !py-3 text-sm ext-price bg-white cursor-pointer w-full border-slate-200 focus:border-[#9E6B73] transition shadow-sm" onchange="window.handleExtraSelection(this)">
                                    <option value="" data-price="0" ${placeholderSel}>-- Select Choice --</option>
                                    <option value="${q.yes_price}|yes" data-price="${q.yes_price}" ${yesSel}>${q.yes_label} (+$${q.yes_price})</option>
                                    <option value="${q.no_price}|no" data-price="${q.no_price}" ${noSel}>${q.no_label} (+$${q.no_price})</option>
                                </select>
                            </div>
                            
                             <div id="ov_wrap_${key}" class="w-full sm:w-32 shrink-0 ${ isSelected ? '' : 'hidden' }">
                                <div class="text-[8px] font-bold text-slate-400 mb-1 uppercase tracking-tighter">Price Override</div>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3.5 text-slate-400 text-[11px] font-black group-focus-within:text-[#9E6B73] transition-colors">$</span>
                                    <input type="number" step="0.01" class="w-full bg-white border border-slate-200 rounded-xl py-3 pl-8 pr-3 text-[11px] font-black text-slate-700 focus:border-[#9E6B73] transition shadow-sm outline-none" 
                                           value="${currentPrice}" oninput="window.updateExtraPrice('${key}', this.value)" placeholder="${defaultPrice}">
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
        }

        if (config.addons[catName]) {
            config.addons[catName].forEach(addon => {
                let key = `add_${addon.id}`;
                let addonLabel = (addon.addon_label || '').toLowerCase().trim();
                let catTarget = (addon.category_target || '').toLowerCase().trim();
                
                // Aggressive matching: exact, category-prefixed, or substring (most robust)
                const itemsList = (Array.isArray(items) ? items : Object.keys(items)).map(s => s.toLowerCase().trim());
                const isExplicitlyUnselected = savedExtras[key] === '0';
                let isChecked = (savedExtras[key] == '1' || 
                                 (!isExplicitlyUnselected && itemsList.some(name => name === addonLabel || 
                                                        name === `${catTarget}: ${addonLabel}` || 
                                                        name.includes(addonLabel))));
                
                let currentPrice = (window.bookingAppData.extraPrices && window.bookingAppData.extraPrices[key] !== undefined) ? window.bookingAppData.extraPrices[key] : addon.addon_price;
                let countsAgainst = (addon.counts_against || catName).trim();

                catHtml += `
                    <div class="mt-4">
                        <div class="flex flex-col gap-2">
                            <label class="flex items-center gap-3 p-4 bg-white border border-slate-200 rounded-2xl hover:border-[#9E6B73] cursor-pointer transition shadow-sm min-h-[56px] relative group overflow-hidden">
                                <input type="checkbox" name="${key}" value="1" class="ext-price w-5 h-5 text-[#9E6B73] border-slate-300 rounded focus:ring-[#9E6B73] transition-all" data-price="${addon.addon_price}" data-counts-against="${countsAgainst}" data-original-checked="${isChecked}" ${isChecked ? 'checked' : ''} onchange="window.handleExtraSelection(this)">
                                <span class="text-sm font-extrabold text-slate-700 flex-1">${addon.addon_label}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black text-[#9E6B73] bg-[#9E6B73]/10 px-3 py-1.5 rounded-xl border border-[#9E6B73]/20 shadow-sm transition-all group-hover:scale-105">+$${Number(addon.addon_price).toFixed(2)}</span>
                                </div>
                            </label>
                            
                             <div id="ov_wrap_${key}" class="ml-10 bg-slate-50/50 border border-slate-200/60 rounded-2xl p-4 flex items-center gap-4 ${isChecked ? '' : 'hidden'}">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[9px] font-black text-[#9E6B73] uppercase tracking-[0.2em] whitespace-nowrap">Price Override</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter">Enter manual cost</span>
                                </div>
                                <div class="relative flex-1 flex items-center group">
                                    <span class="absolute left-3.5 text-slate-400 text-[11px] font-black group-focus-within:text-[#9E6B73] transition-colors">$</span>
                                    <input type="number" step="0.01" class="w-full bg-white border border-slate-200 rounded-xl py-3 pl-8 pr-3 text-[11px] font-black text-slate-700 focus:border-[#9E6B73] transition shadow-sm outline-none" 
                                           value="${currentPrice}" oninput="window.updateExtraPrice('${key}', this.value)" placeholder="${addon.addon_price}">
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
        }

        if (catHtml) {
            return `
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-2">
                        <span class="material-symbols-rounded text-[#9E6B73]">category</span>
                        <h4 class="font-extrabold text-slate-800 text-sm uppercase tracking-wide">${catName}</h4>
                    </div>
                    <div class="category-block-items">
                        ${catHtml}
                    </div>
                </div>`;
        }
        return '';
    };

    /**
     * Preserve the state of all extra inputs in the global application state.
     */
    window.saveCurrentExtrasState = function (ignoreSync = false) {
        if (!window.bookingAppData) return;
        const container = document.getElementById('dynamicExtrasContainer');
        if (!container) return;
        if (!window.bookingAppData.savedExtras) window.bookingAppData.savedExtras = {};

        container.querySelectorAll('.ext-price').forEach(el => {
            if (el.type === 'checkbox') {
                window.bookingAppData.savedExtras[el.name] = el.checked ? "1" : "0";
            } else if (el.tagName === 'SELECT') {
                window.bookingAppData.savedExtras[el.name] = el.value;
            }
        });

        if (ignoreSync) return window.bookingAppData.savedExtras;

        const bridge = document.getElementById('booking-data-bridge');
        if (bridge) {
            const lwEl = document.querySelector('[wire\\:id]');
            if (lwEl && window.Livewire) {
                const lwComp = window.Livewire.find(lwEl.getAttribute('wire:id'));
                if (lwComp) {
                    lwComp.syncExtras(window.bookingAppData.savedExtras);
                }
            }
        }
        return window.bookingAppData.savedExtras;
    };

    window.toggleOverrideState = function (key, isActive) {
        if (!window.bookingAppData) return;
        window.bookingAppData.activeOverrides = window.bookingAppData.activeOverrides || {};
        window.bookingAppData.activeOverrides[key] = isActive;
        
        // INSTANT TOGGLE: Directly manipulation DOM for speed
        const wrap = document.getElementById(`ov_wrap_${key}`);
        if (wrap) {
            // Only show if the extra is actually selected
            const el = document.querySelector(`[name="${key}"]`);
            const isSelected = el ? (el.type === 'checkbox' ? el.checked : (el.value !== '' && el.value !== '0' && !el.value.includes('|no'))) : false;
            
            if (isActive && isSelected) wrap.classList.remove('hidden');
            else wrap.classList.add('hidden');
        }
        
        if (window.lwBookingComponent) {
            window.lwBookingComponent.updateOverrideState(key, isActive);
        }
    };

    let extraPriceDebounceTimer;
    window.updateExtraPrice = function (key, price) {
        if (!window.bookingAppData) return;
        
        // Ensure objects exist
        window.bookingAppData.extraPrices = window.bookingAppData.extraPrices || {};
        window.bookingAppData.manualPrices = window.bookingAppData.manualPrices || {};
        window.bookingAppData.activeOverrides = window.bookingAppData.activeOverrides || {};
        window.bookingAppData.lockedOverrides = window.bookingAppData.lockedOverrides || {};

        const p = parseFloat(price) || 0;
        
        // 1. INSTANT LOCAL UPDATE (for breakdown calculation)
        window.bookingAppData.extraPrices[key] = p;
        window.bookingAppData.manualPrices[key] = p;
        window.bookingAppData.activeOverrides[key] = true;
        window.bookingAppData.lockedOverrides[key] = true;

        // 2. INSTANT BREAKDOWN UPDATE (No heavy DOM scan, just the total)
        if (typeof window.triggerRecalculate === 'function') {
            window.triggerRecalculate(true);
        }

    // 3. DEBOUNCED SERVER SYNC
        clearTimeout(extraPriceDebounceTimer);
        extraPriceDebounceTimer = setTimeout(() => {
            if (window.lwBookingComponent) {
                window.lwBookingComponent.updateExtraPrice(key, p);
            }
            // Also sync the live selection to server to keep badges fresh
            if (typeof window.syncLiveSelectionsToServer === 'function') {
                window.syncLiveSelectionsToServer();
            }
        }, 500);
    };

    /**
     * SPA Watchdog: Ensure sync runs on every Livewire navigation
     */
    document.addEventListener('livewire:navigated', () => {
        console.log("SPA Watchdog: Navigated detected. Initializing sync...");
        if (typeof window.checkRealTimeAvailability === 'function') {
            // Delay slightly to ensure bridge data is ready
            setTimeout(() => window.checkRealTimeAvailability(true), 500);
        }
    });

    // Master Synchronization Hooks
    document.addEventListener('date-selected', (e) => {
        console.log("Master Sync: date-selected caught. Refreshing badges...");
        if (typeof window.checkRealTimeAvailability === 'function') {
            window.checkRealTimeAvailability(true);
        }
    });

    // Listen for Alpine-based date changes that might not trigger @change
    window.addEventListener('date-changed-manual', () => {
        if (typeof window.checkRealTimeAvailability === 'function') {
            window.checkRealTimeAvailability(false);
        }
    });

    // Hook into Livewire's lifecycle to ensure badges are refreshed after a morph
    document.addEventListener('livewire:init', () => {
        Livewire.hook('morph.updated', ({ el, component }) => {
            // Only re-check if we are in a booking component and the date is set
            const dateInput = document.getElementById('event_date');
            if (dateInput && dateInput.value && !window.isInitialLoading) {
                // Use a small delay to ensure DOM is fully ready
                if (window.checkRealTimeAvailability) {
                    // Debounce it slightly to avoid hammer
                    clearTimeout(window.badgeRefreshTimer);
                    window.badgeRefreshTimer = setTimeout(() => {
                        window.checkRealTimeAvailability(true);
                    }, 100);
                }
            }
        });
    });
})();
