// availability-sync.js - Decoupled Availability & Dynamic UI Synchronization Module
// This module handles real-time slot checking and category limit calculations.

(function () {
    // --- AVAILABILITY LOGIC GLOBALS ---
    window.globalCategoryBooked = {};
    window.availabilityAbortController = null;
    window.lastCheckedDate = null;
    window.availabilityCheckTimer = null;

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
        const bookingId = window.bookingAppData ? window.bookingAppData.bookingId : '';

        if (!date) {
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.innerText = 'SELECT DATE';
                badge.className = 'status-badge status-checking';
            });
            return;
        }

        window.lastCheckedDate = date;

        // Visual Sync Progress: Pulse the badges to show it is LIVE and not static
        document.querySelectorAll('.status-badge').forEach(badge => {
            badge.classList.add('opacity-50', 'animate-pulse');
        });

        // Use current AbortController system to prevent hangs
        if (window.availabilityAbortController) window.availabilityAbortController.abort();
        window.availabilityAbortController = new AbortController();

        try {
            console.log(`Syncing availability for date: ${date} (Silent: ${silent})`);
            const response = await fetch(`/api/bookings/check-availability?date=${date}&invoice=${invoice}&booking_id=${bookingId}`, {
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
                        window.globalCategoryBooked[cat.trim().toLowerCase()] = data.categories[cat].booked || 0;
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

                        const actionText = card.querySelector('.action-text');
                        const targetCat = (card.dataset.countsAgainst || '').trim().toLowerCase();
                        const itemLimit = parseInt(card.dataset.dailyLimit) || 0;
                        const initialStock = parseInt(card.dataset.stock) || 999;
                        const UNLIMITED_THRESHOLD = 99;

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
        for (let cat in categories) usage[cat.trim().toLowerCase()] = window.globalCategoryBooked[cat.trim().toLowerCase()] || 0;

        document.querySelectorAll('.ride-checkbox:checked').forEach(cb => {
            const limitCategory = (cb.closest('.product-card').dataset.countsAgainst || '').trim().toLowerCase();
            if (limitCategory) usage[limitCategory] = (usage[limitCategory] || 0) + 1;
        });

        // Also count selected Extras (Addons, Dropdowns, Questions)
        // CRITICAL: Avoid double-counting items that are also selected as rides (synced items)
        const selectedRideNames = new Set(Object.keys(window.bookingAppData.selectedItems || {}).map(s => s.toLowerCase().trim()));

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
                // If it's a dropdown, also check the selected option label
                if (isSelected && el.options[el.selectedIndex]) {
                    const optLabel = el.options[el.selectedIndex].text.split('(')[0].toLowerCase().trim();
                    if (selectedRideNames.has(optLabel)) {
                        // This specific option matches a ride, skip counting
                        return;
                    }
                }
            }

            // If the addon label matches a selected ride, don't double count
            if (isSelected && selectedRideNames.has(label)) return;

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
                    badge.innerText = `Limit: ${catData.limit} (Left: ${remaining})`;
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
        const selectedRideNames = new Set(Object.keys(window.bookingAppData.selectedItems || {}).map(s => s.toLowerCase().trim()));

        // --- Just-in-Time Parity Sync ---
        // CRITICAL: Run this BEFORE saving state so we don't accidentally wipe selections on first render
        if (window.bookingAppData && window.bookingAppData.config) {
            
            // 1. Sync Addons
            if (window.bookingAppData.config.addons) {
                for (let cat in window.bookingAppData.config.addons) {
                    window.bookingAppData.config.addons[cat].forEach(addon => {
                        const addonName = (addon.addon_label || '').toLowerCase().trim();
                        if (addonName && selectedRideNames.has(addonName)) {
                            window.bookingAppData.savedExtras[`add_${addon.id}`] = "1";
                        }
                    });
                }
            }

            // 2. Sync Questions
            if (window.bookingAppData.config.questions) {
                for (let cat in window.bookingAppData.config.questions) {
                    window.bookingAppData.config.questions[cat].forEach(q => {
                        const qName = (q.question_text || '').toLowerCase().trim();
                        if (qName && selectedRideNames.has(qName)) {
                            window.bookingAppData.savedExtras[`q_${q.id}`] = `${q.yes_price}|yes`;
                        }
                    });
                }
            }

            // 3. Sync Dropdowns (Match Option Labels)
            if (window.bookingAppData.config.dropdowns) {
                for (let cat in window.bookingAppData.config.dropdowns) {
                    window.bookingAppData.config.dropdowns[cat].forEach(dd => {
                        if (dd.options) {
                            dd.options.forEach(opt => {
                                const optName = (opt.option_label || '').toLowerCase().trim();
                                if (optName && selectedRideNames.has(optName)) {
                                    window.bookingAppData.savedExtras[`dd_${dd.id}`] = opt.id;
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
        if (typeof window.triggerRecalculate === 'function') window.triggerRecalculate();
    };

    /**
     * Renders HTML for questions, addons, and dropdowns for a specific category.
     */
    window.renderCategoryBlockHTML = function (catName) {
        let catHtml = '';
        if (!window.bookingAppData) return '';
        const config = window.bookingAppData.config;
        const savedExtras = window.bookingAppData.savedExtras;
        const selectedRideNames = new Set(Object.keys(window.bookingAppData.selectedItems || {}).map(s => s.toLowerCase().trim()));

        if (config.dropdowns[catName]) {
            config.dropdowns[catName].forEach(dd => {
                let key = `dd_${dd.id}`;
                let val = savedExtras[key] || '';
                let countsAgainst = (dd.counts_against || catName).trim();
                let placeholder = `<option value="" data-price="0" ${val == '' ? 'selected' : ''}>-- Select Option --</option>`;
                let opts = placeholder + dd.options.map(o => `<option value="${o.id}" data-price="${o.option_price}" ${val == o.id ? 'selected' : ''}>${o.option_label} (+$${o.option_price})</option>`).join('');
                catHtml += `<div class="mt-3"><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1 ml-1">${dd.label}</label><select name="${key}" data-counts-against="${countsAgainst}" data-original-value="${val}" class="input-field !py-2 text-sm ext-price bg-white cursor-pointer" onchange="window.handleExtraSelection(this)">${opts}</select></div>`;
            });
        }

        if (config.questions[catName]) {
            config.questions[catName].forEach(q => {
                let key = `q_${q.id}`;
                let val = savedExtras[key] || '';
                let countsAgainst = (q.counts_against || catName).trim();
                let yesSel = (val == (q.yes_price + '|yes')) ? 'selected' : '';
                let noSel = (val == (q.no_price + '|no')) ? 'selected' : '';
                let placeholderSel = (!yesSel && !noSel) ? 'selected' : '';
                catHtml += `<div class="mt-3"><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1 ml-1">${q.question_text}</label><select name="${key}" data-counts-against="${countsAgainst}" data-original-value="${val}" class="input-field !py-2 text-sm ext-price bg-white cursor-pointer" onchange="window.handleExtraSelection(this)"><option value="" data-price="0" ${placeholderSel}>-- Select Choice --</option><option value="${q.yes_price}|yes" data-price="${q.yes_price}" ${yesSel}>${q.yes_label} (+$${q.yes_price})</option><option value="${q.no_price}|no" data-price="${q.no_price}" ${noSel}>${q.no_label} (+$${q.no_price})</option></select></div>`;
            });
        }

        if (config.addons[catName]) {
            config.addons[catName].forEach(addon => {
                let key = `add_${addon.id}`;
                let addonLabel = (addon.addon_label || '').toLowerCase().trim();
                // FORCE CHECK if it was in the savedExtras OR if it matches a selected ride item
                let isChecked = (savedExtras[key] == '1' || selectedRideNames.has(addonLabel));
                let countsAgainst = (addon.counts_against || catName).trim();
                catHtml += `<label class="flex items-center gap-3 mt-3 p-3 bg-white border border-slate-200 rounded-xl hover:border-[#9E6B73] cursor-pointer transition shadow-sm h-[42px]"><input type="checkbox" name="${key}" value="1" class="ext-price w-4 h-4 text-[#9E6B73] focus:ring-[#9E6B73]" data-price="${addon.addon_price}" data-counts-against="${countsAgainst}" data-original-checked="${isChecked}" ${isChecked ? 'checked' : ''} onchange="window.handleExtraSelection(this)"><span class="text-sm font-bold text-slate-700 flex-1">${addon.addon_label}</span><span class="text-xs font-bold text-[#9E6B73] bg-[#9E6B73]/10 px-2 py-1 rounded-lg">+$${addon.addon_price}</span></label>`;
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

        container.querySelectorAll('.ext-price').forEach(el => {
            if (el.type === 'checkbox') {
                window.bookingAppData.savedExtras[el.name] = el.checked ? "1" : "0";
            } else if (el.tagName === 'SELECT') {
                window.bookingAppData.savedExtras[el.name] = el.value;
            }
        });

        if (ignoreSync) return;

        const bridge = document.getElementById('booking-data-bridge');
        if (bridge && bridge.dataset.id) {
            const lwEl = document.querySelector('[wire\\:id]');
            if (lwEl && window.Livewire) {
                const lwComp = window.Livewire.find(lwEl.getAttribute('wire:id'));
                if (lwComp) {
                    lwComp.syncExtras(window.bookingAppData.savedExtras);
                }
            }
        }
    };
})();
