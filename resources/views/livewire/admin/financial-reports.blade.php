<div class="max-w-[1440px] mx-auto w-full space-y-6 pb-12">

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white drop-shadow-sm">Financial Overview</h1>
            <p class="text-white/90 font-medium mt-1">Analytics for: <span class="underline decoration-white">{{ $periodLabel }}</span></p>
        </div>

        <div class="flex flex-wrap md:flex-nowrap gap-2 bg-gray-100 p-1 rounded-xl items-center">

            <div class="gap-2 items-center mr-2 transition-all duration-300 {{ $period === 'custom' ? 'flex' : 'hidden' }}">
                <input type="date" wire:model="customStart" class="bg-white text-gray-700 text-sm px-2 py-2 rounded-lg border-none focus:ring-2 focus:ring-[#9E6B73] h-10 shadow-sm">
                <span class="text-gray-400 font-bold">-</span>
                <input type="date" wire:model="customEnd" class="bg-white text-gray-700 text-sm px-2 py-2 rounded-lg border-none focus:ring-2 focus:ring-[#9E6B73] h-10 shadow-sm">
                <button wire:click="applyCustomDate" class="bg-[#9E6B73] hover:bg-[#86545C] text-white h-10 w-10 flex items-center justify-center rounded-lg shadow-sm transition">
                    <span class="material-symbols-rounded text-lg">arrow_forward</span>
                </button>
            </div>

            <select wire:model.live="period" class="bg-white text-gray-700 text-sm font-bold px-4 py-2 rounded-lg border-none focus:ring-2 focus:ring-[#9E6B73] cursor-pointer hover:bg-white transition h-10 shadow-sm">
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="last_3_months">Last 3 Months</option>
                <option value="this_year">This Year (YTD)</option>
                <option value="custom">Custom Date...</option>
            </select>

            <button wire:click="exportCsv" class="bg-[#9E6B73] hover:bg-[#86545C] text-white px-4 py-2 rounded-lg font-bold shadow-sm transition flex items-center gap-2 text-sm h-10">
                <span class="material-symbols-rounded text-base">download</span> Export
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-[#F0FFF4] p-6 rounded-[2rem] shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 rounded-bl-full bg-[#dcfce7] opacity-50 pointer-events-none"></div>
            <div class="absolute right-6 top-6 w-10 h-10 bg-green-50 text-green-500 rounded-xl flex items-center justify-center z-10"><span class="material-symbols-rounded text-2xl">payments</span></div>
            <div class="relative z-10 pt-1">
                <p class="text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">Gross Revenue</p>
                <h3 class="text-2xl font-extrabold text-[#2D3748] mt-2">${{ number_format($summary['revenue'], 2) }}</h3>
                <p class="text-xs font-bold {{ $summary['growth'] >= 0 ? 'text-green-500' : 'text-red-500' }} mt-2 flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">{{ $summary['growth'] >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    {{ $summary['growth'] >= 0 ? '+' : '' }}{{ $summary['growth'] }}%
                </p>
            </div>
        </div>

        <div class="bg-[#FFFAF0] p-6 rounded-[2rem] shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 rounded-bl-full bg-[#ffedd5] opacity-50 pointer-events-none"></div>
            <div class="absolute right-6 top-6 w-10 h-10 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center z-10"><span class="material-symbols-rounded text-2xl">shopping_cart</span></div>
            <div class="relative z-10 pt-1">
                <p class="text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">Est. Expenses</p>
                <h3 class="text-2xl font-extrabold text-[#2D3748] mt-2">${{ number_format($summary['expenses'], 2) }}</h3>
                <p class="text-xs font-bold text-gray-400 mt-2">Approx. 30%</p>
            </div>
        </div>

        <div class="bg-[#FFF5F7] p-6 rounded-[2rem] shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 rounded-bl-full bg-[#fae8eb] opacity-50 pointer-events-none"></div>
            <div class="absolute right-6 top-6 w-10 h-10 bg-[#FDF2F4] text-[#9E6B73] rounded-xl flex items-center justify-center z-10"><span class="material-symbols-rounded text-2xl">savings</span></div>
            <div class="relative z-10 pt-1">
                <p class="text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">Net Profit</p>
                <h3 class="text-2xl font-extrabold text-[#9E6B73] mt-2">${{ number_format($summary['profit'], 2) }}</h3>
                <p class="text-xs font-bold text-[#9E6B73]/70 mt-2">Healthy Margin</p>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
        x-data="{
             init() {
                 const revCanvas = document.getElementById('revenueChart');
                 const catCanvas = document.getElementById('categoryChart');

                 if (Chart.getChart(revCanvas)) Chart.getChart(revCanvas).destroy();
                 if (Chart.getChart(catCanvas)) Chart.getChart(catCanvas).destroy();

                 const ctxRev = revCanvas.getContext('2d');
                 const gradientRev = ctxRev.createLinearGradient(0, 0, 0, 400);
                 gradientRev.addColorStop(0, 'rgba(158, 107, 115, 0.5)');
                 gradientRev.addColorStop(1, 'rgba(158, 107, 115, 0.0)');

                 new Chart(ctxRev, {
                     type: 'line',
                     data: {
                         labels: @js($chartLabels),
                         datasets: [{
                             data: @js($chartData),
                             borderColor: '#9E6B73', backgroundColor: gradientRev,
                             borderWidth: 3, fill: true, tension: 0.4,
                             pointBackgroundColor: '#fff', pointBorderColor: '#9E6B73', pointRadius: 4
                         }]
                     },
                     options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { border: { display: false }, beginAtZero: true } } }
                 });

                 const ctxCat = catCanvas.getContext('2d');
                 new Chart(ctxCat, {
                     type: 'doughnut',
                     data: {
                         labels: @js($catLabels),
                         datasets: [{
                             data: @js($catData),
                             backgroundColor: ['#9E6B73', '#F6AD55', '#68D391', '#63B3ED', '#FC8181'],
                             borderWidth: 0
                         }]
                     },
                     options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
                 });
             }
         }">

        <div class="bg-white p-6 rounded-[2rem] shadow-lg border border-gray-100 lg:col-span-2 flex flex-col">
            <h3 class="font-bold text-[#2D3748] text-lg flex items-center gap-2 mb-6">
                <span class="material-symbols-rounded text-[#9E6B73]">monitoring</span> Revenue Distribution
            </h3>
            <div class="relative flex-1 min-h-[380px] pb-4">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-lg border border-gray-100 flex flex-col">
            <h3 class="font-bold text-[#2D3748] text-lg flex items-center gap-2 mb-6">
                <span class="material-symbols-rounded text-[#9E6B73]">pie_chart</span> Top Categories
            </h3>
            <div class="relative flex-1 min-h-[380px] pb-4 flex items-center justify-center">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="mt-4 text-center text-xs text-gray-400 font-medium">Based on bookings count</div>
        </div>
    </div>



    <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden"
        x-data="{ page: 1, perPage: 5, items: @js($paidList) }"
        x-effect="items = @js($paidList); page = 1">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-green-50/50">
            <h3 class="font-bold text-green-600 flex items-center gap-2"><span class="material-symbols-rounded">check_circle</span> Fully Paid Bookings</h3>
            <div class="flex gap-2">
                <button @click="page > 1 ? page-- : null" :disabled="page === 1" class="px-3 py-1 text-xs rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 disabled:opacity-50">Previous</button>
                <button @click="page < Math.ceil(items.length / perPage) ? page++ : null" :disabled="page >= Math.ceil(items.length / perPage) || items.length === 0" class="px-3 py-1 text-xs rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 disabled:opacity-50">Next</button>
            </div>
        </div>
        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-left">
                <thead class="text-xs font-bold text-gray-400 uppercase bg-gray-50/30 sticky top-0">
                    <tr>
                        <th class="px-6 py-4">Event Date</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Method</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4 text-right">Total Paid</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-50">
                    <template x-for="p in items.slice((page - 1) * perPage, page * perPage)" :key="p.id">
                        <tr class="hover:bg-green-50/30 transition">
                            <td class="px-6 py-4 text-gray-500" x-text="p.event_date"></td>
                            <td class="px-6 py-4 font-bold text-gray-700" x-text="p.name"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <template x-if="p.payment_type === 'Card Holder'">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-brands fa-cc-visa text-blue-600" x-show="p.card_network?.toLowerCase().includes('visa')"></i>
                                            <i class="fa-brands fa-cc-mastercard text-orange-500" x-show="p.card_network?.toLowerCase().includes('mastercard')"></i>
                                            <i class="fa-brands fa-cc-amex text-blue-400" x-show="p.card_network?.toLowerCase().includes('amex') || p.card_network?.toLowerCase().includes('american express')"></i>
                                            <i class="fa-brands fa-cc-discover text-orange-400" x-show="p.card_network?.toLowerCase().includes('discover')"></i>
                                            <i class="fa-solid fa-credit-card text-gray-400" x-show="!p.card_network?.toLowerCase().includes('visa') && !p.card_network?.toLowerCase().includes('mastercard') && !p.card_network?.toLowerCase().includes('amex') && !p.card_network?.toLowerCase().includes('american express') && !p.card_network?.toLowerCase().includes('discover')"></i>
                                            <span class="text-[10px] font-bold text-gray-600 uppercase">Card</span>
                                        </div>
                                    </template>
                                    <template x-if="p.payment_type !== 'Card Holder'">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase" x-text="p.payment_type || 'N/A'"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4"><span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs" x-text="p.event_type"></span></td>
                            <td class="px-6 py-4 text-right font-bold text-green-600" x-text="'$' + p.total_amount.toFixed(2)"></td>
                            <td class="px-6 py-4 text-center"><a :href="'/admin/bookings/overview/' + p.id" class="text-xs font-bold text-blue-500 hover:underline">View</a></td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">No paid bookings found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden"
        x-data="{ page: 1, perPage: 5, items: @js($transactionList) }"
        x-effect="items = @js($transactionList); page = 1">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#2D3748] flex items-center gap-2"><span class="material-symbols-rounded text-gray-400">receipt_long</span> All Transactions</h3>
            <div class="flex gap-2">
                <button @click="page > 1 ? page-- : null" :disabled="page === 1" class="px-3 py-1 text-xs rounded-md bg-gray-200 text-gray-600 hover:bg-gray-300 disabled:opacity-50">Previous</button>
                <button @click="page < Math.ceil(items.length / perPage) ? page++ : null" :disabled="page >= Math.ceil(items.length / perPage) || items.length === 0" class="px-3 py-1 text-xs rounded-md bg-gray-200 text-gray-600 hover:bg-gray-300 disabled:opacity-50">Next</button>
            </div>
        </div>
        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-left">
                <thead class="text-xs font-bold text-gray-400 uppercase bg-gray-50/30 sticky top-0">
                    <tr>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Method</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-50">
                    <template x-for="t in items.slice((page - 1) * perPage, page * perPage)" :key="t.id">
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-gray-500" x-text="t.event_date"></td>
                            <td class="px-6 py-4 font-bold text-gray-700" x-text="t.name"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <template x-if="t.payment_type === 'Card Holder'">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-brands fa-cc-visa text-blue-600" x-show="t.card_network?.toLowerCase().includes('visa')"></i>
                                            <i class="fa-brands fa-cc-mastercard text-orange-500" x-show="t.card_network?.toLowerCase().includes('mastercard')"></i>
                                            <i class="fa-brands fa-cc-amex text-blue-400" x-show="t.card_network?.toLowerCase().includes('amex') || t.card_network?.toLowerCase().includes('american express')"></i>
                                            <i class="fa-brands fa-cc-discover text-orange-400" x-show="t.card_network?.toLowerCase().includes('discover')"></i>
                                            <i class="fa-solid fa-credit-card text-gray-400" x-show="!t.card_network?.toLowerCase().includes('visa') && !t.card_network?.toLowerCase().includes('mastercard') && !t.card_network?.toLowerCase().includes('amex') && !t.card_network?.toLowerCase().includes('american express') && !t.card_network?.toLowerCase().includes('discover')"></i>
                                            <span class="text-[10px] font-bold text-gray-600 uppercase">Card</span>
                                        </div>
                                    </template>
                                    <template x-if="t.payment_type !== 'Card Holder'">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase" x-text="t.payment_type || 'N/A'"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4"><span class="px-2 py-1 bg-gray-50 text-gray-600 rounded text-xs font-bold" x-text="t.event_type"></span></td>
                            <td class="px-6 py-4 text-right font-bold text-green-600" x-text="'+$' + t.total_amount.toFixed(2)"></td>
                            <td class="px-6 py-4 text-center"><a :href="'/admin/bookings/overview/' + t.id" class="text-xs font-bold text-blue-500 hover:underline">View</a></td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                </tbody>
            </table>
        </div>
    </div>

</div>
