<div>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 999px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        .status-badge {
            padding: 2px 10px;
            border-radius: 999px;
            font-size: .70rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            border: 1px solid rgba(0, 0, 0, .05);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .st-Pending {
            background: #fff7ed;
            color: #c2410c;
            border-color: #ffedd5;
        }

        .st-Confirmed {
            background: #f0fdf4;
            color: #15803d;
            border-color: #dcfce7;
        }

        .st-Cancelled {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fee2e2;
        }

        .st-Completed {
            background: #eff6ff;
            color: #1e40af;
            border-color: #dbeafe;
        }

        .st-Draft {
            background: #f3f4f6;
            color: #4b5563;
            border-color: #e5e7eb;
        }
    </style>

    <div class="max-w-[1600px] mx-auto w-full space-y-6 pb-12">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white drop-shadow-sm">Book Manage</h1>
                <p class="text-white/90 font-medium mt-1">Track schedule and review requests.</p>
            </div>
        </div>

    <section class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden mb-10">
        <div class="p-5 sm:p-6 border-b border-gray-50 flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
            <h3 class="text-base sm:text-lg font-extrabold text-gray-800 flex items-center gap-2">
                <span class="material-symbols-rounded text-[#9E6B73]">calendar_month</span>
                Upcoming Schedule
            </h3>

            <div class="relative w-full lg:w-auto">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <span class="material-symbols-rounded text-lg">search</span>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search_up" placeholder="Search upcoming..."
                    class="w-full lg:w-72 pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#9E6B73] focus:ring-1 focus:ring-[#9E6B73] transition">
            </div>
        </div>

        <div class="lg:hidden p-4 sm:p-6">
            @if ($upcoming_bookings->isEmpty())
            <div class="text-center py-10 text-gray-400 italic bg-gray-50 rounded-2xl">No upcoming bookings found.</div>
            @else
            <div class="space-y-3">
                @foreach ($upcoming_bookings as $row)
                <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-extrabold text-gray-400">#{{ $row->id }}</span>
                                <span class="status-badge st-{{ $row->status }}">{{ $row->status }}</span>
                            </div>
                            <p class="mt-1 font-extrabold text-gray-800 truncate">
                                {{ trim($row->customer_first_name . ' ' . $row->customer_last_name) }}
                            </p>
                            <p class="text-xs text-gray-500 truncate">{{ $row->event_type ?? 'Event' }}</p>
                        </div>
                        <a href="{{ route('booking.overview', $row->id) }}" wire:navigate class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white border border-gray-200 text-gray-500 hover:text-[#9E6B73] hover:border-[#9E6B73] transition" title="View Details">
                            <span class="material-symbols-rounded">visibility</span>
                        </a>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-white border border-gray-200 p-3">
                            <p class="text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">Date</p>
                            <p class="text-sm font-extrabold text-gray-800 mt-1">{{ \Carbon\Carbon::parse($row->event_date)->format('M d, Y') }}</p>
                        </div>
                        <div class="rounded-2xl bg-white border border-gray-200 p-3">
                            <p class="text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">Amount</p>
                            <p class="text-sm font-extrabold text-[#9E6B73] mt-1">${{ number_format($row->total_amount, 2) }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if ($upcoming_bookings->lastPage() > 1)
            <div class="mt-4 flex items-center justify-between">
                <span class="text-xs text-gray-500 font-medium">Page {{ $upcoming_bookings->currentPage() }} of {{ $upcoming_bookings->lastPage() }}</span>
                <div class="flex gap-2">
                    <button wire:click="previousPage('page_up')" @if($upcoming_bookings->onFirstPage()) disabled class="px-3 py-2 rounded-xl bg-gray-100 border text-gray-300 text-xs font-extrabold cursor-not-allowed" @else class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-xs font-extrabold text-gray-600 hover:bg-gray-100" @endif>Prev</button>
                    <button wire:click="nextPage('page_up')" @if(!$upcoming_bookings->hasMorePages()) disabled class="px-3 py-2 rounded-xl bg-gray-100 border text-gray-300 text-xs font-extrabold cursor-not-allowed" @else class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-xs font-extrabold text-gray-600 hover:bg-gray-100" @endif>Next</button>
                </div>
            </div>
            @endif
            @endif
        </div>

        <div class="hidden lg:block overflow-x-auto custom-scrollbar w-full">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-gray-50/50 text-xs font-extrabold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm relative">
                    <div wire:loading wire:target="search_up, previousPage, nextPage" class="absolute inset-0 bg-white/50 backdrop-blur-[2px] z-10"></div>

                    @if ($upcoming_bookings->isEmpty())
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic">No upcoming bookings found.</td>
                    </tr>
                    @else
                    @foreach ($upcoming_bookings as $row)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-extrabold text-gray-400">#{{ $row->id }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-700">{{ \Carbon\Carbon::parse($row->event_date)->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="font-extrabold text-gray-800 truncate max-w-xs">
                                {{ trim($row->customer_first_name . ' ' . $row->customer_last_name) }}
                            </div>
                            <div class="text-xs text-gray-400 truncate max-w-xs">{{ $row->event_type ?? 'Event' }}</div>
                        </td>
                        <td class="px-6 py-4 font-extrabold text-[#9E6B73]">${{ number_format($row->total_amount, 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="status-badge st-{{ $row->status }}">{{ $row->status }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('booking.overview', $row->id) }}" wire:navigate class="inline-flex items-center justify-center text-gray-400 hover:text-[#9E6B73] p-2 hover:bg-gray-100 rounded-2xl transition" title="View Details">
                                <span class="material-symbols-rounded">visibility</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        @if ($upcoming_bookings->lastPage() > 1)
        <div class="hidden lg:flex p-4 border-t border-gray-100 justify-between items-center bg-gray-50/50">
            <span class="text-xs text-gray-500 font-medium">Page {{ $upcoming_bookings->currentPage() }} of {{ $upcoming_bookings->lastPage() }}</span>
            <div class="flex gap-2">
                <button wire:click="previousPage('page_up')" @if($upcoming_bookings->onFirstPage()) disabled class="px-3 py-2 bg-gray-100 border text-gray-300 rounded-xl text-xs font-extrabold cursor-not-allowed" @else class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-extrabold text-gray-600 hover:bg-gray-100" @endif>Prev</button>
                <button wire:click="nextPage('page_up')" @if(!$upcoming_bookings->hasMorePages()) disabled class="px-3 py-2 bg-gray-100 border text-gray-300 rounded-xl text-xs font-extrabold cursor-not-allowed" @else class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-extrabold text-gray-600 hover:bg-gray-100" @endif>Next</button>
            </div>
        </div>
        @endif
    </section>

    <section class="bg-white rounded-[2rem] shadow-xl border border-gray-100 p-5 sm:p-6 relative overflow-hidden mb-10">
        <div class="absolute top-0 left-0 w-1.5 h-full bg-orange-400"></div>

        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6 pl-2">
            <div class="flex items-center gap-3">
                <h3 class="text-base sm:text-lg font-extrabold text-gray-800 flex items-center gap-2">
                    <span class="material-symbols-rounded text-orange-500 bg-orange-100 p-1 rounded-full text-lg">hourglass_top</span>
                    Pending Confirmation
                </h3>
                <span class="text-xs font-extrabold bg-orange-100 text-orange-700 px-2 py-1 rounded-xl shrink-0">{{ $total_pen }} New</span>
            </div>

            <div class="relative w-full lg:w-auto">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <span class="material-symbols-rounded text-lg">search</span>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search_pen" placeholder="Search pending..."
                    class="w-full lg:w-72 pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-orange-400 focus:ring-1 focus:ring-orange-400 transition">
            </div>
        </div>

        <div class="relative">
            <div wire:loading wire:target="search_pen, previousPage, nextPage" class="absolute inset-0 bg-white/50 backdrop-blur-[2px] z-10 rounded-2xl"></div>

            @if ($pending_bookings->isEmpty())
            <div class="text-center py-10 text-gray-400 italic bg-gray-50 rounded-2xl">No pending bookings found.</div>
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($pending_bookings as $b)
                <div class="border border-gray-200 rounded-2xl p-4 hover:shadow-md transition bg-gray-50/50 flex flex-col h-full">
                    <div class="flex justify-between items-start mb-2 gap-2">
                        <span class="text-xs font-extrabold text-gray-400">#{{ $b->id }}</span>
                        <span class="text-xs font-extrabold text-[#9E6B73] bg-[#FDF2F4] px-2 py-1 rounded-xl whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($b->event_date)->format('M d, Y') }}
                        </span>
                    </div>

                    <h4 class="font-extrabold text-gray-800 text-sm mb-1 truncate" title="{{ trim($b->customer_first_name . ' ' . $b->customer_last_name) }}">
                        {{ trim($b->customer_first_name . ' ' . $b->customer_last_name) }}
                    </h4>

                    <p class="text-xs text-gray-500 mb-4 truncate">{{ $b->event_type ?? 'Event' }}</p>

                    <div class="mt-auto">
                        <a href="{{ route('booking.overview', $b->id) }}" wire:navigate class="w-full py-2.5 bg-white border border-gray-200 text-gray-700 hover:text-[#9E6B73] hover:border-[#9E6B73] hover:bg-gray-50 rounded-2xl flex items-center justify-center gap-2 transition text-xs font-extrabold shadow-sm">
                            <span class="material-symbols-rounded text-base">visibility</span> View Details
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            @if ($pending_bookings->lastPage() > 1)
            <div class="mt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <span class="text-xs text-gray-500 font-medium">Page {{ $pending_bookings->currentPage() }} of {{ $pending_bookings->lastPage() }}</span>
                <div class="flex gap-2">
                    <button wire:click="previousPage('page_pen')" @if($pending_bookings->onFirstPage()) disabled class="px-3 py-2 bg-gray-100 border text-gray-300 rounded-xl text-xs font-extrabold cursor-not-allowed" @else class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-extrabold text-gray-600 hover:bg-gray-100" @endif>Prev</button>
                    <button wire:click="nextPage('page_pen')" @if(!$pending_bookings->hasMorePages()) disabled class="px-3 py-2 bg-gray-100 border text-gray-300 rounded-xl text-xs font-extrabold cursor-not-allowed" @else class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-extrabold text-gray-600 hover:bg-gray-100" @endif>Next</button>
                </div>
            </div>
            @endif
            @endif
        </div>
    </section>
    </div>
</div>