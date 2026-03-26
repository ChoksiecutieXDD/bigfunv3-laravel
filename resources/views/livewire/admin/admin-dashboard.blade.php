<div class="max-w-[1600px] mx-auto w-full space-y-6 pb-12">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white drop-shadow-sm">Dashboard Overview</h1>
            <p class="text-white/90 font-medium mt-1">Welcome back, {{ auth()->user()->first_name }}! Here's what's happening today.</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 rounded-3xl shadow-lg shadow-black/5 border border-white/50 transition-transform hover:-translate-y-1 flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Bookings (This Month)</p>
                <h3 class="text-3xl font-black text-gray-800">{{ $total_bookings }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-500">
                <span class="material-symbols-rounded text-2xl">calendar_month</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-lg shadow-black/5 border border-white/50 transition-transform hover:-translate-y-1 flex items-center justify-between relative overflow-hidden">
            @if ($pending_bookings > 0)
            <div class="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full mr-4 mt-4 animate-ping"></div>
            @endif
            <div>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Pending Approval</p>
                <h3 class="text-3xl font-black text-gray-800">{{ $pending_bookings }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-500">
                <span class="material-symbols-rounded text-2xl">pending_actions</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-lg shadow-black/5 border border-white/50 transition-transform hover:-translate-y-1 flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Revenue (Today)</p>
                <h3 class="text-3xl font-black text-gray-800">${{ number_format($todays_revenue, 2) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center text-green-500">
                <span class="material-symbols-rounded text-2xl">payments</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-lg shadow-black/5 border border-white/50 transition-transform hover:-translate-y-1 flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Active Staff</p>
                <h3 class="text-3xl font-black text-gray-800">{{ $active_staff }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-500">
                <span class="material-symbols-rounded text-2xl">group</span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

        <!-- Line Chart -->
        <div class="xl:col-span-2 bg-white rounded-3xl p-6 shadow-lg shadow-black/5 border border-white/50 flex flex-col transition-all hover:shadow-xl hover:shadow-black/10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="material-symbols-rounded text-[#9E6B73]">monitoring</span> 
                    Booking Analytics
                </h3>
                <select wire:model.live="chartPeriod" class="bg-gray-50 border-none text-xs font-bold text-gray-500 py-2 px-4 rounded-xl cursor-pointer hover:bg-gray-100 outline-none focus:ring-2 focus:ring-[#9E6B73]/20">
                    <option value="This Week">This Week</option>
                    <option value="This Month">This Month</option>
                    <option value="This Year">This Year</option>
                </select>
            </div>

            <!-- Inline Alpine for Line Chart -->
            <div class="relative flex-1 min-h-[300px] w-full"
                x-data="{
                    chartInstance: null,
                    init() {
                        const canvas = document.getElementById('bookingChart');
                        if (!canvas) return;

                        // BUG FIX: Destroy existing chart so Livewire can redraw cleanly
                        if (Chart.getChart(canvas)) Chart.getChart(canvas).destroy();

                        const ctx = canvas.getContext('2d');
                        
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(158, 107, 115, 0.25)');
                        gradient.addColorStop(1, 'rgba(158, 107, 115, 0.0)');

                        this.chartInstance = new Chart(ctx, {
                            type: 'line',
                            data: { 
                                labels: @js($chartLabels), 
                                datasets: [{
                                    label: 'Bookings',
                                    data: @js($chartData),
                                    backgroundColor: gradient,
                                    borderColor: '#9E6B73',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#9E6B73',
                                    pointRadius: 4,
                                    pointHoverRadius: 6
                                }] 
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { 
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: '#1e293b',
                                        titleFont: { size: 13, weight: 'bold' },
                                        bodyFont: { size: 12 },
                                        padding: 12,
                                        cornerRadius: 10,
                                        displayColors: false
                                    }
                                },
                                scales: {
                                    y: { 
                                        beginAtZero: true, 
                                        grid: { borderDash: [4, 4], color: '#f1f5f9' }, 
                                        ticks: { stepSize: 1, color: '#94a3b8', font: { size: 11 } },
                                        border: { display: false }
                                    },
                                    x: { 
                                        grid: { display: false },
                                        ticks: { color: '#94a3b8', font: { size: 11 } }
                                    }
                                }
                            }
                        });
                    }
                }"
                @update-booking-chart.window="
                    const canvas = document.getElementById('bookingChart');
                    const chart = Chart.getChart(canvas);
                    if (chart) {
                        chart.data.labels = $event.detail.labels;
                        chart.data.datasets[0].data = $event.detail.data;
                        chart.update();
                    }
                ">
                <canvas id="bookingChart"></canvas>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="bg-white rounded-3xl p-6 shadow-lg shadow-black/5 border border-white/50 flex flex-col transition-all hover:shadow-xl hover:shadow-black/10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="material-symbols-rounded text-[#9E6B73]">pie_chart</span>
                    Payment Status
                </h3>
            </div>

            <!-- Inline Alpine for Pie Chart -->
            <div class="relative flex-1 min-h-[220px] flex items-center justify-center"
                wire:ignore
                x-data="{
                    chartInstance: null,
                    init() {
                        const canvas = document.getElementById('paymentChart');
                        if (!canvas) return;

                        if (Chart.getChart(canvas)) Chart.getChart(canvas).destroy();

                        const ctx = canvas.getContext('2d');
                        this.chartInstance = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Fully Paid', 'Partial', 'Unpaid'],
                                datasets: [{
                                    data: [{{ $payment_breakdown['Paid'] }}, {{ $payment_breakdown['Partial'] }}, {{ $payment_breakdown['Unpaid'] }}],
                                    backgroundColor: ['#22c55e', '#3b82f6', '#ef4444'],
                                    borderWidth: 0,
                                    hoverOffset: 10
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { 
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: '#1e293b',
                                        padding: 10,
                                        cornerRadius: 8
                                    }
                                },
                                cutout: '75%'
                            }
                        });
                    }
                 }"
                 @update-payment-chart.window="
                    if (chartInstance) {
                        chartInstance.data.datasets[0].data = [$event.detail.paid, $event.detail.partial, $event.detail.unpaid];
                        chartInstance.update();
                    }
                 ">
                <canvas id="paymentChart"></canvas>
            </div>

            <div class="mt-8 space-y-4">
                <div class="flex justify-between items-center text-sm p-3 bg-green-50/50 rounded-2xl">
                    <span class="flex items-center gap-3 font-semibold text-gray-600">
                        <span class="w-3 h-3 rounded-full bg-green-500 shadow-sm shadow-green-200"></span> 
                        Fully Paid
                    </span>
                    <span class="font-black text-gray-800">{{ $payment_breakdown['Paid'] }}</span>
                </div>
                <div class="flex justify-between items-center text-sm p-3 bg-blue-50/50 rounded-2xl">
                    <span class="flex items-center gap-3 font-semibold text-gray-600">
                        <span class="w-3 h-3 rounded-full bg-blue-500 shadow-sm shadow-blue-200"></span> 
                        Partial
                    </span>
                    <span class="font-black text-gray-800">{{ $payment_breakdown['Partial'] }}</span>
                </div>
                <div class="flex justify-between items-center text-sm p-3 bg-red-50/50 rounded-2xl">
                    <span class="flex items-center gap-3 font-semibold text-gray-600">
                        <span class="w-3 h-3 rounded-full bg-red-500 shadow-sm shadow-red-200"></span> 
                        Unpaid
                    </span>
                    <span class="font-black text-gray-800">{{ $payment_breakdown['Unpaid'] }}</span>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="xl:col-span-3 bg-white rounded-3xl p-6 shadow-lg shadow-black/5 border border-white/50">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800">Recent Bookings</h3>
                <a href="{{ route('admin.manages') }}" wire:navigate class="text-[#9E6B73] text-xs font-bold hover:underline">View All</a>
            </div>
            <div class="space-y-4">
                @forelse ($recent_activities as $act)
                @php
                $stClass = $act->status == 'Confirmed' ? 'bg-green-100 text-green-600' : ($act->status == 'Pending' ? 'bg-orange-100 text-orange-600' : 'bg-red-100 text-red-600');
                $icon = $act->status == 'Confirmed' ? 'check' : ($act->status == 'Pending' ? 'hourglass_empty' : 'close');
                @endphp
                <div class="flex items-center gap-4 p-3 hover:bg-gray-50 rounded-2xl transition-colors cursor-pointer group">
                    <div class="w-10 h-10 rounded-full {{ $stClass }} flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-lg">{{ $icon }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800 truncate">{{ $act->customer_first_name }} {{ $act->customer_last_name }}</p>
                        <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($act->event_date)->format('M d, Y') }} • #{{ $act->id }}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-lg {{ $stClass }}">{{ $act->status }}</span>
                </div>
                @empty
                <p class="text-gray-400 text-sm text-center py-4">No recent activity.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>