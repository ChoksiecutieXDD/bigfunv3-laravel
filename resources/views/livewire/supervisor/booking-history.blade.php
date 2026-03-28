<div class="max-w-[1440px] mx-auto space-y-6">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-white drop-shadow-sm">Booking History</h2>
            <p class="text-white/80 mt-1 text-sm font-medium">Archive of completed bookings and records.</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-3xl shadow-lg border border-gray-100 flex flex-col md:flex-row gap-4 items-stretch md:items-center">
        <div class="relative w-full md:w-64">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <span class="material-symbols-rounded">search</span>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Full name or ID..." class="w-full pl-10 pr-4 py-3 bg-gray-50 rounded-xl border border-transparent focus:bg-white focus:border-[#9E6B73]/50 outline-none text-sm transition-all shadow-sm">
        </div>

        <div class="flex gap-4 w-full md:w-auto">
            <input type="date" wire:model.live="date_filter" class="w-full md:w-auto py-3 px-3 bg-gray-50 rounded-xl border border-transparent focus:bg-white focus:border-[#9E6B73]/50 outline-none text-sm font-medium text-gray-600 shadow-sm">
        </div>

        @if (!empty($search) || !empty($date_filter))
        <button wire:click="clearFilters" type="button" class="text-gray-400 hover:text-red-500 text-sm font-medium px-4 py-3 flex items-center transition-colors">
            Clear
        </button>
        @endif

        <div class="ml-auto text-xs text-gray-400 font-medium hidden md:block self-center">
            Found: {{ $total_rows }} records
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-xl shadow-black-200/50 border border-gray-100 overflow-hidden flex flex-col">

        <div class="hidden lg:block overflow-x-auto custom-scrollbar flex-1">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 sticky top-0 z-10 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="p-5 border-b border-gray-100">Event Date</th>
                        <th class="p-5 border-b border-gray-100">Customer Name</th>
                        <th class="p-5 border-b border-gray-100">Items / Service</th>
                        <th class="p-5 border-b border-gray-100">Total</th>
                        <th class="p-5 border-b border-gray-100">Booked By</th>
                        <th class="p-5 border-b border-gray-100">Status</th>
                        <th class="p-5 border-b border-gray-100 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-50">
                    @forelse ($history as $row)
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <td class="p-5 text-gray-600 font-medium whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($row->event_date)->format('M d, Y') }}
                        </td>
                        <td class="p-5 font-bold text-gray-800">
                            {{ $row->customer_first_name }} {{ $row->customer_last_name }}
                        </td>
                        <td class="p-5 text-gray-600 max-w-xs truncate" title="{{ $row->items->pluck('item_name')->implode(', ') }}">
                            {{ $row->items->count() > 0 ? $row->items->pluck('item_name')->implode(', ') : 'No items listed' }}
                        </td>
                        <td class="p-5 font-bold text-[#9E6B73]">
                            ${{ number_format($row->total_amount, 2) }}
                        </td>
                        <td class="p-5 text-[11px] font-bold text-gray-500 italic">
                            {{ $row->booked_by ?: 'System' }}
                        </td>
                        <td class="p-5">
                            @php
                            $status = $row->status ?: 'Pending';
                            $badgeClass = 'bg-orange-100 text-orange-700 border-orange-200';
                            if ($status === 'Completed') $badgeClass = 'bg-green-100 text-green-700 border-green-200';
                            if ($status === 'Cancelled') $badgeClass = 'bg-red-100 text-red-700 border-red-200';
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg {{ $badgeClass }} text-xs font-bold border">{{ $status }}</span>
                        </td>
                        <td class="p-5 text-right">
                            <a href="{{ route('supervisor.bookings.overview', $row->id) }}" class="inline-flex items-center justify-center text-gray-400 hover:text-[#9E6B73] p-1 transition rounded-full hover:bg-gray-100">
                                <span class="material-symbols-rounded">visibility</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center text-gray-400">
                            <div class="flex flex-col items-center">
                                <span class="material-symbols-rounded text-4xl mb-2">search_off</span>
                                <span>No records found matching your search.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="lg:hidden p-4 space-y-3 bg-gray-50/30 flex-1">
            @forelse ($history as $row)
            @php
            $status = $row->status ?: 'Pending';
            $statusColor = 'text-orange-600 bg-orange-50 border-orange-100';
            if ($status === 'Completed') $statusColor = 'text-green-600 bg-green-50 border-green-100';
            if ($status === 'Cancelled') $statusColor = 'text-red-500 bg-red-50 border-red-100';
            @endphp
            <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden active:scale-[0.99] transition-transform">
                <a href="{{ route('booking.overview', $row->id) }}" class="absolute inset-0 z-10"></a>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-bold text-gray-800 text-sm truncate">{{ $row->customer_first_name }} {{ $row->customer_last_name }}</h4>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $statusColor }} whitespace-nowrap">{{ $status }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span>{{ \Carbon\Carbon::parse($row->event_date)->format('M d, Y') }}</span>
                            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                            <span class="font-bold text-[#9E6B73]">${{ number_format($row->total_amount, 2) }}</span>
                            @if($row->booked_by)
                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                <span class="italic text-[10px]">{{ $row->booked_by }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="material-symbols-rounded text-gray-300">chevron_right</span>
                </div>
            </div>
            @empty
            <div class="text-center p-8 text-gray-400 italic">No bookings found.</div>
            @endforelse
        </div>

        @if ($history->hasPages())
        <div class="border-t border-gray-100 p-4 bg-white flex items-center justify-between">
            <span class="text-xs text-gray-400 font-medium">
                Page {{ $history->currentPage() }} of {{ $history->lastPage() }}
            </span>
            <div class="flex gap-2">
                @if ($history->onFirstPage())
                <span class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-300 bg-gray-50 border border-gray-100 rounded-lg cursor-not-allowed">
                    <span class="material-symbols-rounded text-base">arrow_back</span> Prev
                </span>
                @else
                <button wire:click="previousPage" class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition">
                    <span class="material-symbols-rounded text-base">arrow_back</span> Prev
                </button>
                @endif

                @if ($history->hasMorePages())
                <button wire:click="nextPage" class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-white bg-[#9E6B73] border border-transparent rounded-lg hover:bg-[#86545C] transition shadow-md shadow-[#9E6B73]/30">
                    Next <span class="material-symbols-rounded text-base">arrow_forward</span>
                </button>
                @else
                <span class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-300 bg-gray-50 border border-gray-100 rounded-lg cursor-not-allowed">
                    Next <span class="material-symbols-rounded text-base">arrow_forward</span>
                </span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>