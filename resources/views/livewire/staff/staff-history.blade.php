<div class="max-w-360 mx-auto w-full pb-12 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end mb-8 gap-6 mt-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-white drop-shadow-sm">Lead Operator Log</h1>
            <p class="text-white/90 font-medium mt-1">Full record of all jobs assigned to you as Lead Operator.</p>
            <p class="text-[10px] text-white/40 mt-1 uppercase tracking-widest font-black leading-none">Logged in as: {{ $fullName }}</p>
        </div>

        <div class="bg-white/10 backdrop-blur-md text-white px-5 py-3 rounded-2xl border border-white/20 text-xs font-black uppercase tracking-widest shadow-sm w-full lg:w-auto text-center lg:text-left">
            Records Found: <span class="text-white drop-shadow-sm font-black">{{ $historyData->total() }}</span>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white/95 backdrop-blur-md rounded-2xl p-5 sm:p-6 mb-10 border border-white/50 shadow-xl shadow-black/10">
        <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-3">
            <div class="w-8 h-8 rounded-lg bg-plum-light flex items-center justify-center text-plum">
                <span class="material-symbols-rounded text-xl">filter_list</span>
            </div>
            <h2 class="text-lg font-bold text-gray-800">Filter History</h2>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-5 items-end">
            <!-- Search -->
            <div class="lg:col-span-5">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Search Details</label>
                <div class="relative">
                    <span class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Job ID, Name, Location..."
                        class="pl-10 pr-4 py-3 rounded-xl text-sm border-2 border-gray-50 focus:border-plum focus:ring-4 focus:ring-plum/5 shadow-inner bg-gray-50/50 text-gray-800 transition-all font-medium">
                </div>
            </div>

            <!-- Date -->
            <div class="lg:col-span-3">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Specific Date</label>
                <input wire:model.live="specificDate" type="date"
                    class="w-full px-4 py-3 rounded-xl text-sm border-2 border-gray-50 focus:border-plum focus:ring-4 focus:ring-plum/5 shadow-inner bg-gray-50/50 text-gray-800 transition-all font-bold">
            </div>

            <!-- Status -->
            <div class="lg:col-span-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Status</label>
                <select wire:model.live="statusFilter" class="w-full px-4 py-3 rounded-xl text-sm border-2 border-gray-50 focus:border-plum focus:ring-4 focus:ring-plum/5 shadow-inner bg-gray-50/50 text-gray-800 transition-all font-bold">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Confirmed">Confirmed</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Reset -->
            <div class="lg:col-span-2 flex gap-2">
                <button wire:click="clearFilters" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-3.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-sm flex items-center justify-center gap-2">
                    <span class="material-symbols-rounded text-base">close</span> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-4xl shadow-sm overflow-hidden border border-gray-100 mb-8 transition-all hover:shadow-xl">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left border-collapse min-w-225">
                <thead>
                    <tr class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] border-b border-gray-100">
                        <th class="p-5">Date</th>
                        <th class="p-5">Job ID</th>
                        <th class="p-5">Assignment</th>
                        <th class="p-5">Customer</th>
                        <th class="p-5">Location</th>
                        <th class="p-5">Status</th>
                        <th class="p-5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($historyData as $job)
                        @php
                            $stClass = 'bg-gray-100 text-gray-500 border-gray-200';
                            if ($job->status == 'Confirmed') $stClass = 'bg-green-50 text-green-700 border-green-200';
                            if ($job->status == 'Pending') $stClass = 'bg-orange-50 text-orange-600 border-orange-100';
                            if ($job->status == 'Completed') $stClass = 'bg-blue-50 text-blue-700 border-blue-200';
                            if ($job->status == 'Cancelled') $stClass = 'bg-red-50 text-red-600 border-red-200';
                        @endphp
                        <tr class="hover:bg-plum-light/40 transition-colors group">
                            <td class="p-5">
                                <p class="font-black text-gray-800">{{ \Carbon\Carbon::parse($job->event_date)->format('M d, Y') }}</p>
                                <p class="text-[10px] font-bold text-gray-400 uppercase mt-1">{{ \Carbon\Carbon::parse($job->start_time)->format('g:i A') }}</p>
                            </td>
                            <td class="p-5">
                                <span class="px-3 py-1 rounded-lg bg-gray-100 text-gray-500 font-bold text-[11px]">#{{ $job->id }}</span>
                            </td>
                            <td class="p-5">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] uppercase font-black bg-pink-100 text-pink-700 border border-pink-200 shadow-sm">
                                    Lead Op
                                </span>
                            </td>
                            <td class="p-5">
                                <p class="font-bold text-gray-700 whitespace-nowrap">{{ $job->customer_first_name }} {{ $job->customer_last_name }}</p>
                            </td>
                            <td class="p-5">
                                <p class="text-xs font-medium text-gray-500 truncate max-w-xs leading-relaxed" title="{{ $job->address_line_1 }}">{{ $job->address_line_1 }}</p>
                            </td>
                            <td class="p-5">
                                <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase border {{ $stClass }}">
                                    {{ $job->status }}
                                </span>
                            </td>
                            <td class="p-5 text-right">
                                <a href="{{ route('staff.bookings.overview', $job->id) }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-plum hover:text-white transition-all shadow-sm hover:shadow-md">
                                    <span class="material-symbols-rounded">visibility</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-20 text-center">
                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                    <span class="material-symbols-rounded text-4xl">folder_off</span>
                                </div>
                                <p class="text-gray-400 font-bold italic tracking-wide">No records found matching your filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $historyData->links() }}
    </div>
</div>
