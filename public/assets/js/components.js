// public/assets/js/components.js

document.addEventListener('DOMContentLoaded', () => {
    initComponentContainers();
});

/**
 * Ensures all necessary containers exist in the DOM automatically!
 */
function initComponentContainers() {
    // 1. Toast Container
    if (!document.getElementById('toast-container')) {
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'fixed top-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none';
        document.body.appendChild(toastContainer);
    }

    // 2. Global Confirm Modal Container
    if (!document.getElementById('global-confirm-modal')) {
        const confirmHTML = `
            <div id="global-confirm-modal" class="modal-backdrop items-center justify-center">
                <div class="modal-content bg-slate-800 border border-slate-700 p-6 rounded-3xl shadow-2xl max-w-sm w-full mx-4 relative">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center text-red-400">
                            <span class="material-symbols-rounded">warning</span>
                        </div>
                        <h3 id="confirm-title" class="text-xl font-bold text-white">Confirm Action</h3>
                    </div>
                    <p id="confirm-message" class="text-sm text-slate-400 mb-6">Are you sure you want to proceed?</p>
                    <div class="flex justify-end gap-3">
                        <button onclick="closeConfirm()" class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold transition-colors">Cancel</button>
                        <button id="confirm-btn-yes" class="px-4 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold transition-colors shadow-lg shadow-red-500/20">Yes, Proceed</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', confirmHTML);
    }
}

/* ==========================================
   1. ALERT BANNER SYSTEM
   ========================================== */
let alertTimeout;

function showAlert(message, type = 'success') {
    const banner = document.getElementById('custom-alert');
    if (!banner) return alert(message); // Ultimate Fallback

    const msgEl = document.getElementById('alert-message');
    const iconEl = document.getElementById('alert-icon');

    // Reset styles safely
    banner.className = 'fixed top-6 z-[9999] flex items-center gap-3 px-6 py-4 rounded-2xl text-white shadow-2xl transition-all duration-300 border';

    // Apply foolproof CSS classes instead of Tailwind utilities
    if (type === 'success') {
        banner.classList.add('alert-success');
        iconEl.innerText = 'check_circle';
    } else if (type === 'error') {
        banner.classList.add('alert-error');
        iconEl.innerText = 'error';
    } else {
        banner.classList.add('alert-info');
        iconEl.innerText = 'info';
    }

    msgEl.innerText = message;

    // Center and Animate In
    banner.style.display = 'flex';
    banner.style.left = '50%';
    banner.style.transform = 'translate(-50%, -20px)';
    banner.style.opacity = '0';

    setTimeout(() => {
        banner.style.transform = 'translate(-50%, 0)';
        banner.style.opacity = '1';
    }, 10);

    // Clear any existing timeout (prevents bugs if buttons are clicked rapidly)
    clearTimeout(alertTimeout);

    // Set the 4-second auto-close timeout
    alertTimeout = setTimeout(() => {
        closeAlert();
    }, 4000);
}

// Ensure this function exists right below showAlert!
function closeAlert() {
    const banner = document.getElementById('custom-alert');
    if (!banner) return;

    // Animate out (slide up and fade out)
    banner.style.transform = 'translate(-50%, -20px)';
    banner.style.opacity = '0';

    // Wait for the CSS transition to finish before actually hiding the element
    setTimeout(() => {
        banner.style.display = 'none';
    }, 300);
}

/* ==========================================
   2. TOAST SYSTEM
   ========================================== */
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    // Limit to 3 toasts
    if (container.children.length >= 3) {
        container.firstElementChild.remove();
    }

    const toast = document.createElement('div');

    let styleClasses = type === 'success' ? 'bg-emerald-500 border-emerald-400' :
        type === 'error' ? 'bg-red-500 border-red-400' :
            'bg-slate-700 border-slate-600';

    let icon = type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info';

    toast.className = `flex items-center gap-3 px-5 py-4 rounded-2xl text-white shadow-xl shadow-black/20 border pointer-events-auto toast-enter ${styleClasses}`;
    toast.innerHTML = `
        <span class="material-symbols-rounded text-2xl">${icon}</span>
        <span class="text-sm font-semibold tracking-wide">${message}</span>
        <button onclick="this.parentElement.classList.add('toast-exit'); setTimeout(() => this.parentElement.remove(), 300);" class="ml-4 hover:text-white/70 transition-colors focus:outline-none">
            <span class="material-symbols-rounded text-xl">close</span>
        </button>
    `;

    container.appendChild(toast);

    // Auto remove after 4 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-exit');
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 300);
        }
    }, 4000);
}

/* ==========================================
   3. CUSTOM MODAL SYSTEM
   ========================================== */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

/* ==========================================
   4. CONFIRMATION LOG / DIALOG SYSTEM
   ========================================== */
let currentConfirmCallback = null;

function showConfirm(title, message, onConfirm) {
    const modal = document.getElementById('global-confirm-modal');
    if (!modal) return;

    document.getElementById('confirm-title').innerText = title;
    document.getElementById('confirm-message').innerText = message;

    currentConfirmCallback = onConfirm;

    openModal('global-confirm-modal');
}

function closeConfirm() {
    closeModal('global-confirm-modal');
    currentConfirmCallback = null;
}

document.addEventListener('click', (e) => {
    if (e.target.id === 'confirm-btn-yes') {
        if (currentConfirmCallback) currentConfirmCallback();
        closeConfirm();
    }
});