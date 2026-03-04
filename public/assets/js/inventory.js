// public/assets/js/inventory.js

// Store original states for tracking limit changes
let originalCatLimit = null;
let originalProdLimit = null;
let originalProdCounts = null;

if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}

document.addEventListener("DOMContentLoaded", function () {
    // Scroll Restoration
    const scrollPos = sessionStorage.getItem("scrollPos");
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        sessionStorage.removeItem("scrollPos");
    }

    // Trigger Toast if PHP session has a message
    if (typeof PHP_SESSION_TOAST !== 'undefined' && PHP_SESSION_TOAST) {
        showToast(PHP_SESSION_TOAST.message, PHP_SESSION_TOAST.type);
    }

    // Load active tab
    if (typeof PHP_ACTIVE_TAB !== 'undefined') {
        switchTab(PHP_ACTIVE_TAB);
    } else {
        switchTab('categories');
    }

    // Initialize Addon Rows if empty
    if (document.getElementById('dropdownRows') && document.getElementById('dropdownRows').children.length === 0) addDropdownRow();
    if (document.getElementById('addonRows') && document.getElementById('addonRows').children.length === 0) addAddonRow();
});

window.addEventListener("beforeunload", function () {
    sessionStorage.setItem("scrollPos", window.scrollY);
});

// --- Tab Logic ---
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById('view-' + tabId);
    if (target) target.classList.remove('hidden');

    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    const btn = document.getElementById('tab-' + tabId);
    if (btn) btn.classList.add('active');
}

// --- Dynamic Form Rows ---
function addAddonRow() {
    const div = document.createElement('div');
    div.className = 'space-y-4 border-b border-dashed border-slate-200 pb-4 mt-4';
    div.innerHTML = `<div><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Item Name</label><input type="text" name="addon_label[]" class="input-field addon-label" placeholder="e.g. Generator" required></div><div><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Price (Leave empty for Free)</label><input type="number" step="0.01" name="addon_price[]" class="input-field addon-price" placeholder="Free"></div>`;
    document.getElementById('addonRows').appendChild(div);
}

function addDropdownRow() {
    const div = document.createElement('div');
    div.className = 'space-y-4 border-b border-dashed border-slate-200 pb-4 mt-4';
    div.innerHTML = `<div><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Option Name</label><input type="text" name="option_label[]" class="input-field dd-opt-label" placeholder="e.g. Standard Hire" required></div><div><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Price (Leave empty for Free)</label><input type="number" step="0.01" name="option_price[]" class="input-field dd-opt-price" placeholder="Free"></div>`;
    document.getElementById('dropdownRows').appendChild(div);
}

// --- Filter Search ---
function filterProducts() {
    const term = document.getElementById('productSearch').value.toLowerCase();
    document.querySelectorAll('.product-row').forEach(row => {
        const name = row.querySelector('.product-name').innerText.toLowerCase();
        row.style.display = name.includes(term) ? '' : 'none';
    });
}

// --- Submit Handlers with Global Confirmation Modals ---
function confirmDelete(form) {
    showConfirm('Delete Item?', 'Are you sure you want to delete this? This action cannot be undone.', function () {
        form.submit();
    });
    return false; // Prevent form from submitting normally
}

function handleCatSubmit(e) {
    e.preventDefault();
    const currentLimit = document.getElementById('cat_limit').value;
    if (originalCatLimit !== null && originalCatLimit != currentLimit) {
        showConfirm("Confirm Limit Change", "Changing the category daily limit will affect inventory calculations for all linked products. Proceed?", () => {
            document.getElementById('catForm').submit();
        });
    } else {
        document.getElementById('catForm').submit();
    }
}

function handleProductSubmit(e) {
    e.preventDefault();
    const currentLimit = document.getElementById('prod_limit').value;
    const currentCounts = document.getElementById('prod_counts').value;
    let warnings = [];

    if (originalProdLimit !== null && originalProdLimit != currentLimit) warnings.push("daily limit");
    if (originalProdCounts !== null && originalProdCounts !== currentCounts) warnings.push("target limit category");

    if (warnings.length > 0) {
        showConfirm("Confirm Changes", `You are changing the ${warnings.join(' and ')} for this product. This may affect future availability calculations. Proceed?`, () => {
            document.getElementById('productForm').submit();
        });
    } else {
        document.getElementById('productForm').submit();
    }
}

// --- Edit Form Populators ---
function editCategory(data) {
    originalCatLimit = data.daily_limit;
    document.getElementById('cat_id').value = data.id;
    document.getElementById('cat_name').value = data.category_name;
    document.getElementById('cat_limit').value = data.daily_limit;
    document.getElementById('cat-cancel-btn').classList.remove('hidden');
    document.getElementById('cat-form-title').innerText = 'Edit Category';
    document.getElementById('cat_name').focus();
}

function editProduct(data) {
    originalProdLimit = data.daily_limit;
    originalProdCounts = data.counts_against ? data.counts_against : data.category;
    document.getElementById('prod_id').value = data.id;
    document.getElementById('prod_name').value = data.name;
    document.getElementById('prod_cat').value = data.category;
    document.getElementById('prod_price').value = data.price;
    document.getElementById('prod_limit').value = data.daily_limit;
    document.getElementById('prod_active').checked = (data.is_active == 1);
    document.getElementById('prod_counts').value = originalProdCounts;
    document.getElementById('prod-cancel-btn').classList.remove('hidden');
    document.getElementById('prod-form-title').innerText = 'Edit Product';
    document.getElementById('prod_name').focus();
}

function editAddon(data) {
    document.getElementById('addonRows').innerHTML = '';
    const div = document.createElement('div');
    div.className = 'space-y-4 border-b border-dashed border-slate-200 pb-4 mt-4';
    div.innerHTML = `<div><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Item Name</label><input type="text" name="addon_label[]" class="input-field addon-label" value="${data.addon_label}" required></div><div><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Price (Leave empty for Free)</label><input type="number" step="0.01" name="addon_price[]" class="input-field addon-price" value="${parseFloat(data.addon_price) === 0 ? '' : data.addon_price}" placeholder="Free"></div>`;
    document.getElementById('addonRows').appendChild(div);
    document.getElementById('addon_id').value = data.id;
    document.getElementById('addon_cat').value = data.category_target;
    document.getElementById('addon-cancel-btn').classList.remove('hidden');
    document.getElementById('addon-form-title').innerText = 'Edit Add-on';
    document.querySelector('#addonForm .addon-label').focus();
}

function editDropdown(data) {
    document.getElementById('dropdownRows').innerHTML = '';
    data.options.forEach(opt => {
        const div = document.createElement('div');
        div.className = 'space-y-4 border-b border-dashed border-slate-200 pb-4 mt-4';
        div.innerHTML = `<div><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Option Name</label><input type="text" name="option_label[]" class="input-field dd-opt-label" value="${opt.option_label}" required></div><div><label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Price (Leave empty for Free)</label><input type="number" step="0.01" name="option_price[]" class="input-field dd-opt-price" value="${parseFloat(opt.option_price) === 0 ? '' : opt.option_price}" placeholder="Free"></div>`;
        document.getElementById('dropdownRows').appendChild(div);
    });
    document.getElementById('dd_id').value = data.id;
    document.getElementById('dd_label').value = data.label;
    document.getElementById('dd_cat').value = data.category_target;
    document.getElementById('dropdown-cancel-btn').classList.remove('hidden');
    document.getElementById('dd_label').focus();
}

function editDelivery(data) {
    document.getElementById('del_id').value = data.id;
    document.getElementById('del_name').value = data.zone_name;
    document.getElementById('del_price').value = data.price;
    document.getElementById('del-cancel-btn').classList.remove('hidden');
    document.getElementById('del_name').focus();
}

function editDuration(data) {
    document.getElementById('dur_id').value = data.id;
    document.getElementById('dur_label').value = data.label;
    document.getElementById('dur_hours').value = data.hours;
    document.getElementById('dur_price').value = data.price;
    document.getElementById('dur-cancel-btn').classList.remove('hidden');
    document.getElementById('dur_label').focus();
}

function resetForm(formId) {
    document.getElementById(formId).reset();
    document.querySelectorAll(`#${formId} input[type="hidden"]`).forEach(h => {
        if (h.name.includes('_id') && !h.name.includes('action')) h.value = '';
    });

    if (formId === 'catForm') {
        originalCatLimit = null;
        document.getElementById('cat-cancel-btn').classList.add('hidden');
        document.getElementById('cat-form-title').innerText = 'Add Category';
    }
    if (formId === 'productForm') {
        originalProdLimit = null;
        originalProdCounts = null;
        document.getElementById('prod-cancel-btn').classList.add('hidden');
        document.getElementById('prod-form-title').innerText = 'Add New Product';
    }
    if (formId === 'addonForm') {
        document.getElementById('addon-cancel-btn').classList.add('hidden');
        document.getElementById('addonRows').innerHTML = '';
        addAddonRow();
        document.getElementById('addon-form-title').innerText = '1. Add Specific Items';
    }
    if (formId === 'dropdownForm') {
        document.getElementById('dropdown-cancel-btn').classList.add('hidden');
        document.getElementById('dropdownRows').innerHTML = '';
        addDropdownRow();
    }
    if (formId === 'deliveryForm') document.getElementById('del-cancel-btn').classList.add('hidden');
    if (formId === 'durationForm') document.getElementById('dur-cancel-btn').classList.add('hidden');
}