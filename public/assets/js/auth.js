// public/assets/js/auth.js

// ==========================================
// 1. SLIDER LOGIC
// ==========================================
let currentSlide = 0;
const totalSlides = 3;
const autoPlayInterval = 5000;
let slideTimer;

function goToSlide(index) {
    clearInterval(slideTimer);
    updateSlideUI(index);
    startAutoPlay();
}

function updateSlideUI(index) {
    document.querySelectorAll('.slide').forEach(el => {
        el.classList.remove('active');
        el.classList.add('inactive');
    });
    document.querySelectorAll('.dot').forEach(el => {
        el.classList.remove('active');
        el.classList.add('inactive');
    });

    const activeSlide = document.getElementById(`slide-${index}`);
    const activeDot = document.querySelectorAll('.dot')[index];

    if (activeSlide) {
        activeSlide.classList.remove('inactive');
        activeSlide.classList.add('active');
    }
    if (activeDot) {
        activeDot.classList.remove('inactive');
        activeDot.classList.add('active');
    }
    currentSlide = index;
}

function nextSlide() {
    const next = (currentSlide + 1) % totalSlides;
    updateSlideUI(next);
}

function startAutoPlay() {
    slideTimer = setInterval(nextSlide, autoPlayInterval);
}

// Initialize slider only if elements exist on the current page
if (document.getElementById('slide-0')) {
    startAutoPlay();
}

// ==========================================
// 2. PASSWORD TOGGLE LOGIC
// ==========================================
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if (togglePassword && passwordInput) {
    const iconSpan = togglePassword.querySelector('span');
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        iconSpan.textContent = type === 'password' ? 'visibility' : 'visibility_off';
    });
}

// ==========================================
// 3. GLOBAL MODAL LOGIC
// ==========================================
function openErrorModal(message) {
    const modal = document.getElementById('errorModal');
    const msgEl = document.getElementById('errorModalMessage');
    if (msgEl) msgEl.textContent = message || "Invalid credentials. Please try again.";
    if (modal) modal.classList.add('active');
}

function closeErrorModal() {
    const modal = document.getElementById('errorModal');
    if (modal) modal.classList.remove('active');
}

const errorModal = document.getElementById('errorModal');
if (errorModal) {
    errorModal.addEventListener('click', (e) => {
        if (e.target.id === 'errorModal') closeErrorModal();
    });
}

// ==========================================
// 4. LOGIN FORM SUBMISSION (Handles Standard & Supervisor)
// ==========================================
const loginForm = document.getElementById('loginForm');

if (loginForm) {
    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const role = document.getElementById('role') ? document.getElementById('role').value : null;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const rememberMe = document.getElementById('rememberMe') ? document.getElementById('rememberMe').checked : false;
        const submitBtn = document.querySelector('button[type="submit"]');

        if (!role) {
            openErrorModal("Please select a role.");
            return;
        }

        const originalBtnHTML = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="material-symbols-rounded animate-spin text-base">progress_activity</span> Processing...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    password: password,
                    role_check: role,
                    remember_me: rememberMe
                })
            });

            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Server did not return JSON. Check PHP error logs.");
            }

            const data = await response.json();

            if (response.ok && data.success) {
                const modal = document.getElementById('successModal');
                if (modal) modal.classList.add('active');

                // Redirect based on the authenticated role
                setTimeout(() => {
                    if (data.role === 'Administrator') {
                        window.location.href = '/admin_dashboard';
                    } else if (data.role === 'Supervisor') {
                        window.location.href = '/calendar';
                    } else if (data.role === 'Staff' || data.role === 'Operator' || data.role === 'Deliverer') {
                        window.location.href = '/staff_dashboard';
                    } else {
                        window.location.href = '/';
                    }
                }, 1500);
            } else {
                openErrorModal(data.message || "Invalid credentials. Please check your role, email, and password.");
                submitBtn.innerHTML = originalBtnHTML;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error("Login Error:", error);
            openErrorModal("Connection failed. Please try again.");
            submitBtn.innerHTML = originalBtnHTML;
            submitBtn.disabled = false;
        }
    });
}

// ==========================================
// 5. FORGOT PASSWORD FORM SUBMISSION
// ==========================================
const forgotForm = document.getElementById('forgotForm');

if (forgotForm) {
    forgotForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalBtnHTML = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="material-symbols-rounded animate-spin text-base">progress_activity</span> Sending...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('/api/auth/forgot-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: email })
            });

            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Server response was not JSON. Check PHP error logs.");
            }

            const data = await response.json();

            if (response.ok && data.success) {
                const modal = document.getElementById('successModal');
                if (modal) modal.classList.add('active');
            } else {
                openErrorModal(data.message || "Email address not found.");
                submitBtn.innerHTML = originalBtnHTML;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error("Forgot Password Error:", error);
            openErrorModal("Connection failed. Please try again later.");
            submitBtn.innerHTML = originalBtnHTML;
            submitBtn.disabled = false;
        }
    });
}
// ==========================================
// 6. RESET PASSWORD FORM SUBMISSION
// ==========================================
const resetForm = document.getElementById('resetForm');

if (resetForm) {
    resetForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const p1 = document.getElementById('password').value;
        const p2 = document.getElementById('confirm_password').value;
        const token = document.getElementById('token').value;
        const msg = document.getElementById('msgArea');
        const btn = document.getElementById('submitBtn');
        const originalBtnText = btn.innerHTML;

        // Reset message
        msg.textContent = "";
        msg.className = "";

        if (p1 !== p2) {
            msg.textContent = "Passwords do not match!";
            msg.className = "text-sm text-center font-bold text-red-500";
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded animate-spin text-base align-middle mr-2">progress_activity</span> Updating...';

        try {
            // Pointing to your new API route in index.php
            const res = await fetch('/api/auth/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: token,
                    password: p1
                })
            });

            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Server response was not JSON. Check PHP error logs.");
            }

            const data = await res.json();

            if (data.success) {
                // Replace content with Success Message directly inside the card
                const resetCard = document.getElementById('resetCard');
                resetCard.innerHTML = `
                    <div class="text-center py-6 animate-enter">
                        <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 shadow-inner">
                            <span class="material-symbols-rounded text-4xl">check_circle</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Success!</h3>
                        <p class="text-gray-500 mb-8">Your password has been updated securely.</p>
                        <a href="/login" class="block w-full py-4 bg-[#9E6B73] text-white font-bold rounded-2xl hover:bg-[#86545C] shadow-lg transition-all">Login Now</a>
                    </div>
                `;
            } else {
                throw new Error(data.message || "Failed to update password.");
            }
        } catch (err) {
            msg.textContent = err.message || "Error updating password.";
            msg.className = "text-sm text-center font-bold text-red-500";
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
        }
    });
}