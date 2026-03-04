// public/assets/js/calendar.js

let currentDate = new Date();

document.addEventListener('DOMContentLoaded', () => {
    initCalendar();
    if (typeof dbBookings !== 'undefined') {
        checkReminders();
    }

    // Dark mode check
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }
});

window.addEventListener('pageshow', function (event) {
    if (event.persisted) window.location.reload();
});

// --- UI TOGGLE FUNCTIONS ---

function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebar) {
        sidebar.classList.toggle('-translate-x-full');
    }
    if (overlay) {
        overlay.classList.toggle('hidden');
        setTimeout(() => {
            overlay.classList.toggle('opacity-0');
        }, 10);
    }
}

function toggleRemindersPanel() {
    const panel = document.getElementById('reminderPanel');
    const overlay = document.getElementById('reminderOverlay');

    if (panel.classList.contains('translate-x-full')) {
        panel.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.remove('opacity-0'), 10);
    } else {
        panel.classList.add('translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }
}

function toggleCustomerDetails() {
    const panel = document.getElementById('customerDetailPanel');
    const overlay = document.getElementById('customerDetailOverlay');

    if (panel.classList.contains('translate-x-full')) {
        panel.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.remove('opacity-0'), 10);
    } else {
        panel.classList.add('translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }
}

// --- CUSTOMER DETAILS POPULATOR ---

function openCustomerDetails(id) {
    if (typeof dbBookings === 'undefined') return;

    const booking = dbBookings.find(b => b.id === id);
    if (!booking) return;

    const content = document.getElementById('customerDetailContent');

    // Build the dynamic HTML for the customer details panel
    content.innerHTML = `
        <div class="space-y-4">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Customer</h4>
                <div class="font-bold text-lg text-gray-800">${booking.full_name}</div>
                <div class="text-sm text-gray-600 flex items-center gap-2 mt-2"><span class="material-symbols-rounded text-sm text-plum">mail</span> ${booking.email || 'No email provided'}</div>
                <div class="text-sm text-gray-600 flex items-start gap-2 mt-2"><span class="material-symbols-rounded text-sm text-plum mt-0.5">location_on</span> <span>${booking.address}</span></div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Event Details</h4>
                <div class="grid grid-cols-2 gap-y-4 gap-x-2">
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase tracking-wide">Date</span>
                        <div class="font-bold text-sm text-gray-700">${booking.full_date}</div>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase tracking-wide">Time</span>
                        <div class="font-bold text-sm text-gray-700">${booking.start_time} - ${booking.end_time}</div>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase tracking-wide">Event Type</span>
                        <div class="font-bold text-sm text-gray-700">${booking.type_event || 'N/A'}</div>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase tracking-wide">Status</span>
                        <div class="font-bold text-sm ${booking.status === 'Completed' ? 'text-green-600' : 'text-gray-700'}">${booking.status}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Services & Logistics</h4>
                <div class="text-sm font-medium text-gray-800 mb-3">${booking.services_booked}</div>
                <div class="flex gap-2 mb-4">
                    <span class="text-xs bg-purple-50 text-purple-700 font-bold px-2 py-1 rounded border border-purple-100">Plan: ${booking.install_plan}</span>
                </div>
                <div class="flex justify-between items-center text-xs border-t border-gray-50 pt-3">
                    <span class="text-gray-500 flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">engineering</span> Op: <b class="text-gray-800">${booking.lead_operator}</b></span>
                    <span class="text-gray-500 flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">local_shipping</span> Del: <b class="text-gray-800">${booking.lead_deliverer}</b></span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Financial Overview</h4>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600">Total Amount</span>
                    <span class="font-bold text-gray-800">$${booking.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600">Amount Paid</span>
                    <span class="font-bold text-green-600">$${booking.real_paid.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                </div>
                <div class="flex justify-between items-center border-t border-gray-100 pt-3 mt-1">
                    <span class="text-sm font-bold text-gray-800">Balance Due</span>
                    <span class="font-bold text-lg ${booking.balance_due > 0 ? 'text-red-500' : 'text-green-500'}">$${booking.balance_due.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                </div>
                <div class="text-[10px] text-gray-400 mt-2 flex justify-end items-center gap-1">
                    <span class="material-symbols-rounded text-[12px]">${booking.payment_type_icon}</span> ${booking.payment_type_label}
                </div>
            </div>

            <div class="pt-2 flex gap-3 pb-8">
                <a href="book_details.php?id=${booking.id}" class="flex-1 bg-white border-2 border-plum text-plum font-bold py-3 rounded-xl text-center hover:bg-plum/5 transition active:scale-95">Full View</a>
                <a href="new_booking.php?edit_id=${booking.id}" class="flex-1 bg-plum text-white font-bold py-3 rounded-xl text-center hover:bg-plum-dark transition shadow-lg shadow-pink-900/20 active:scale-95">Edit Booking</a>
            </div>
        </div>
    `;

    toggleCustomerDetails();
}

// --- CALENDAR LOGIC ---

function initCalendar() {
    const savedMonth = sessionStorage.getItem('calendarMonth');
    const savedYear = sessionStorage.getItem('calendarYear');
    if (savedMonth !== null && savedYear !== null) {
        currentDate.setDate(1);
        currentDate.setMonth(parseInt(savedMonth));
        currentDate.setFullYear(parseInt(savedYear));
    }
    renderMonth(currentDate.getMonth(), currentDate.getFullYear());

    document.getElementById('prevMonthBtn').onclick = () => {
        currentDate.setDate(1);
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderMonth(currentDate.getMonth(), currentDate.getFullYear());
    };
    document.getElementById('nextMonthBtn').onclick = () => {
        currentDate.setDate(1);
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderMonth(currentDate.getMonth(), currentDate.getFullYear());
    };
}

function goToToday() {
    sessionStorage.removeItem('calendarMonth');
    sessionStorage.removeItem('calendarYear');
    currentDate = new Date();
    renderMonth(currentDate.getMonth(), currentDate.getFullYear());
}

function jumpToDate() {
    const month = parseInt(document.getElementById('monthSelect').value);
    const year = parseInt(document.getElementById('yearSelect').value);
    currentDate.setDate(1);
    currentDate.setMonth(month);
    currentDate.setFullYear(year);
    renderMonth(month, year);
}

function renderMonth(month, year) {
    sessionStorage.setItem('calendarMonth', month);
    sessionStorage.setItem('calendarYear', year);
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    document.getElementById('monthSelect').value = month;
    document.getElementById('yearSelect').value = year;
    document.getElementById('calendarTitle').textContent = `${monthNames[month]} ${year}`;
    document.getElementById('stats-month-label').textContent = monthNames[month];
    document.getElementById('year-label').textContent = year;

    const container = document.getElementById('calendar-list-content');
    container.innerHTML = '';
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const statusFilter = document.getElementById('statusFilter').value;

    let mCount = 0, mRev = 0, mPaid = 0, mBal = 0, satCount = 0, satBookings = 0, satRev = 0, ytdCount = 0, ytdRev = 0;

    if (typeof dbBookings === 'undefined') return;

    dbBookings.forEach(b => {
        if (b.year === year && b.status !== 'Draft') {
            ytdCount++;
            ytdRev += b.total_amount;
        }
    });

    for (let day = 1; day <= daysInMonth; day++) {
        const dateObj = new Date(year, month, day);
        const dayOfWeek = dateObj.getDay();
        if (dayOfWeek === 6) satCount++;
        const dayStr = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        const dayBookings = dbBookings.filter(b => b.day === day && b.month === month && b.year === year && (statusFilter === 'All' || b.status === statusFilter));

        dayBookings.forEach(b => {
            if (b.status !== 'Cancelled' && b.status !== 'Draft') {
                mCount++;
                mRev += b.total_amount;
                mPaid += b.real_paid;
                mBal += b.balance_due;
                if (dayOfWeek === 6) {
                    satBookings++;
                    satRev += b.total_amount;
                }
            }
        });

        const dayGroup = document.createElement('div');
        dayGroup.className = 'mb-8';
        const dayHeader = document.createElement('div');
        dayHeader.className = 'day-header rounded-xl mb-4 flex justify-between items-center shadow-sm border border-slate-200';
        dayHeader.innerHTML = `<span class="text-lg">${dayStr}</span> <span class="text-xs bg-plum/10 text-plum px-3 py-1 rounded-full font-bold">${dayBookings.length} Events</span>`;
        dayGroup.appendChild(dayHeader);

        if (dayBookings.length > 0) {
            dayBookings.forEach(b => {
                // CHANGED: Instead of an 'a' tag redirecting, it's a div that triggers the side panel
                const card = document.createElement('div');
                card.onclick = () => openCustomerDetails(b.id);
                card.className = `booking-card rounded-xl card-${b.color_code} mb-3 cursor-pointer hover:shadow-md transition-shadow`;

                let statusBadge = `<span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500 font-bold">${b.status}</span>`;
                if (b.status === 'Completed') statusBadge = `<span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-600 font-bold">Completed</span>`;
                if (b.status === 'Cancelled') statusBadge = `<span class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-500 font-bold">Cancelled</span>`;

                const paidText = b.real_paid > 0 ? `<span class="text-green-600 font-bold">Paid: $${b.real_paid.toLocaleString()}</span>` : `<span class="text-gray-400">Paid: $0</span>`;
                const balanceText = b.balance_due > 0 ? `<span class="text-red-500 font-bold">Bal: $${b.balance_due.toLocaleString()}</span>` : `<span class="text-green-500 font-bold">Fully Paid</span>`;
                const termsBadge = b.terms_agreed == 1 ? `<span class="ml-2 text-[10px] font-bold text-green-600 flex items-center gap-1 border border-green-200 px-1.5 py-0.5 rounded bg-white"><span class="material-symbols-rounded text-sm">check_circle</span> Terms Signed</span>` : `<span class="ml-2 text-[10px] font-bold text-gray-400 flex items-center gap-1 border border-gray-200 px-1.5 py-0.5 rounded bg-white"><span class="material-symbols-rounded text-sm">pending</span> Terms Pending</span>`;
                const opName = b.lead_operator ? b.lead_operator.split(' ')[0] : 'Team';
                const delName = b.lead_deliverer ? b.lead_deliverer.split(' ')[0] : 'Team';

                card.innerHTML = `
                    <div><span class="booking-label">Time & Location</span><div class="booking-value">${b.start_time} - ${b.end_time}</div><div class="text-xs text-gray-500 truncate mt-0.5" title="${b.address}">${b.address}</div></div>
                    <div><span class="booking-label">Customer</span><div class="booking-value truncate" title="${b.full_name}">${b.full_name}</div><div class="mt-1 flex items-center flex-wrap">${statusBadge} ${termsBadge}</div></div>
                    <div><span class="booking-label">Staff & Del</span><div class="flex flex-col gap-1 mt-0.5"><div class="flex items-center gap-1 text-[11px] text-gray-700" title="Operator"><span class="material-symbols-rounded text-[14px] text-plum">engineering</span> ${opName}</div><div class="flex items-center gap-1 text-[11px] text-gray-700" title="Deliverer"><span class="material-symbols-rounded text-[14px] text-blue-500">local_shipping</span> ${delName}</div></div></div>
                    <div><span class="booking-label">Rides & Install</span><div class="booking-value truncate text-xs mb-1" title="${b.services_booked}">${b.services_booked}</div><span class="pill pill-install">${b.install_plan}</span></div>
                    <div class="text-left lg:text-right"><span class="booking-label">${b.status_label}</span><div class="booking-value text-plum text-sm">$${b.total_amount.toLocaleString()}</div><div class="text-[10px] flex flex-col mt-0.5 items-start lg:items-end">${paidText}${balanceText}</div><div class="text-[10px] text-gray-400 mt-1 flex items-center justify-start lg:justify-end gap-1"><span class="material-symbols-rounded text-[12px]">${b.payment_type_icon}</span> ${b.payment_type_label}</div></div>
                `;
                dayGroup.appendChild(card);
            });
        } else {
            dayGroup.innerHTML += `<div class="p-3 text-center text-gray-300 italic bg-white border border-gray-100 rounded-b-xl text-sm">No bookings.</div>`;
        }
        container.appendChild(dayGroup);
    }

    const fmt = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
    document.getElementById('fin-bookings-month').textContent = mCount;
    document.getElementById('fin-amount-month').textContent = fmt.format(mRev);
    document.getElementById('saturday-count').textContent = satCount;
    document.getElementById('fin-saturday-bookings').textContent = satBookings;
    document.getElementById('fin-saturday-amount').textContent = fmt.format(satRev);
    document.getElementById('fin-ytd-bookings').textContent = ytdCount;
    document.getElementById('fin-ytd-amount').textContent = fmt.format(ytdRev);
    document.getElementById('fin-deposit-month').textContent = fmt.format(mPaid);
    document.getElementById('fin-balance-month').textContent = fmt.format(mBal);
}

// --- ALERTS & REMINDERS LOGIC ---

function checkReminders() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const draftsContainer = document.getElementById('drafts-list');
    const draftsSection = document.getElementById('drafts-container');
    const urgentContainer = document.getElementById('urgent-alerts-list');
    const urgentSection = document.getElementById('urgent-alerts-container');
    const unpaidContainer = document.getElementById('unpaid-list');
    const upcomingContainer = document.getElementById('upcoming-list');
    const badge = document.getElementById('fab-badge');

    let draftsHTML = '', urgentHTML = '', unpaidHTML = '', upcomingHTML = '', alertCount = 0;

    if (typeof dbBookings === 'undefined') return;

    dbBookings.forEach(b => {
        const eventDate = new Date(b.full_date);
        eventDate.setHours(0, 0, 0, 0);
        const diffTime = eventDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (b.status === 'Draft') {
            draftsHTML += `
                <div class="p-3 rounded-lg bg-orange-50 border border-orange-100 group hover:shadow-md transition">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="text-xs font-bold text-orange-800 flex items-center gap-1">
                                <span class="material-symbols-rounded text-[14px]">edit</span> Draft #${b.id}
                            </span>
                            <span class="text-[10px] text-gray-500 block mt-0.5">${b.full_date}</span>
                        </div>
                    </div>
                    <div class="text-xs text-gray-700 font-medium truncate mb-3">${b.full_name || 'No Name Provided'}</div>
                    <div class="flex gap-2">
                        <a href="book_details.php?id=${b.id}" class="flex-1 bg-white border border-gray-200 text-gray-600 text-[10px] font-bold py-2 rounded-lg text-center hover:bg-gray-50 transition shadow-sm">View</a>
                        <a href="new_booking.php?edit_id=${b.id}" class="flex-1 bg-plum text-white text-[10px] font-bold py-2 rounded-lg text-center hover:bg-plum-dark transition shadow-sm shadow-pink-900/10">Edit</a>
                    </div>
                </div>`;
            alertCount++;
        }
        else if (b.status !== 'Cancelled') {
            if (b.balance_due > 0) {
                if (diffDays === 5 || diffDays === 2) {
                    urgentHTML += `
                        <div class="p-3 rounded-lg bg-red-50 border border-red-200">
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="text-xs font-bold text-red-800 flex items-center gap-1"><span class="material-symbols-rounded text-sm">warning</span> Booking #${b.id}</div>
                                        <div class="text-[10px] text-red-600">${b.full_name}</div>
                                        <div class="text-[10px] font-bold text-red-500 mt-0.5">Due in ${diffDays} Days</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] font-bold text-gray-400 block">${b.full_date}</span>
                                        <span class="text-xs font-bold text-red-600 block mt-1">$${b.balance_due.toLocaleString()}</span>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="book_details.php?id=${b.id}" class="block w-full bg-white border border-gray-300 text-gray-600 text-[10px] font-bold py-1.5 rounded text-center hover:bg-gray-50">View Details</a>
                                </div>
                            </div>
                        </div>`;
                    alertCount++;
                } else {
                    unpaidHTML += `
                        <a href="book_details.php?id=${b.id}" class="block p-3 rounded-lg bg-gray-50 border border-gray-200 hover:bg-gray-100 transition group">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-bold text-gray-700">Booking #${b.id}</span>
                                <span class="text-[10px] text-gray-400">${b.full_date}</span>
                            </div>
                            <div class="text-xs text-gray-600 truncate">${b.full_name}</div>
                            <div class="text-xs text-red-500 font-bold mt-1">Due: $${b.balance_due.toLocaleString()}</div>
                        </a>`;
                    alertCount++;
                }
            }

            if (diffDays >= 0 && diffDays <= 7) {
                upcomingHTML += `
                    <a href="book_details.php?id=${b.id}" class="block p-3 rounded-lg bg-blue-50 border border-blue-100 hover:bg-blue-100 transition">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-blue-600">${b.full_name}</span>
                            <span class="text-[10px] bg-white px-2 py-0.5 rounded text-blue-500 font-bold">${diffDays === 0 ? 'TODAY' : diffDays + ' days'}</span>
                        </div>
                        <div class="text-xs text-gray-600 truncate">${b.services_booked}</div>
                    </a>`;
            }
        }
    });

    if (draftsHTML) { draftsContainer.innerHTML = draftsHTML; draftsSection.classList.remove('hidden'); } else { draftsSection.classList.add('hidden'); }
    if (urgentHTML) { urgentContainer.innerHTML = urgentHTML; urgentSection.classList.remove('hidden'); } else { urgentSection.classList.add('hidden'); }
    if (unpaidHTML) unpaidContainer.innerHTML = unpaidHTML;
    if (upcomingHTML) upcomingContainer.innerHTML = upcomingHTML;

    if (alertCount > 0) {
        badge.classList.remove('hidden');
        badge.textContent = alertCount > 9 ? '9+' : alertCount;
    } else {
        badge.classList.add('hidden');
    }
}