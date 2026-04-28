<div>
    <style>
        .status-pill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
        }

        .status-confirmed {
            background: #ecfccb;
            color: #3f6212;
            border: 1px solid #d9f99d;
        }

        .status-completed {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .role-pill {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .role-driver {
            background: #e0f2fe;
            color: #0369a1;
        }

        .role-lead {
            background: #fce7f3;
            color: #9d174d;
        }

        .role-team {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>

    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Top Overview & Stats -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 mb-8 mt-4">
            <div class="w-full lg:w-auto">
                <h1 class="text-2xl md:text-4xl font-extrabold text-white drop-shadow-md">Hello, {{ $firstNameOnly }}! 👋</h1>
                <p class="text-white/90 font-medium mt-1 text-sm md:text-base">Here is your operational overview.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <div class="bg-white/90 backdrop-blur px-5 py-4 rounded-2xl shadow-sm border border-white/50 flex items-center gap-4 flex-1 lg:min-w-[180px]">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 shrink-0 shadow-inner">
                        <span class="material-symbols-rounded text-2xl">calendar_month</span>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-0.5">Upcoming</div>
                        <div class="text-xl md:text-2xl font-black text-gray-800 leading-none">{{ $upcoming_count }}</div>
                    </div>
                </div>
                <div class="bg-white/90 backdrop-blur px-5 py-4 rounded-2xl shadow-sm border border-white/50 flex items-center gap-4 flex-1 lg:min-w-[180px]">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0 shadow-inner">
                        <span class="material-symbols-rounded text-2xl">check_circle</span>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-0.5">Completed</div>
                        <div class="text-xl md:text-2xl font-black text-gray-800 leading-none">{{ $completed_count }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Livewire Reactive Search Bar -->
        <div class="bg-white/95 backdrop-blur-md rounded-2xl p-5 sm:p-6 mb-10 border border-white/50 shadow-xl shadow-black/10">
            <div class="flex items-center gap-3 mb-5 border-b border-gray-100 pb-3">
                <div class="w-8 h-8 rounded-lg bg-[#FDF2F4] flex items-center justify-center text-[#9E6B73]">
                    <span class="material-symbols-rounded text-xl">search</span>
                </div>
                <h2 class="text-lg font-bold text-gray-800">Find Booking</h2>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full">
                <div class="relative flex-grow">
                    <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by Job ID or Customer Name..."
                        class="pl-12 pr-4 py-4 rounded-xl text-sm border-2 border-gray-50 focus:border-[#9E6B73] focus:ring-4 focus:ring-[#9E6B73]/5 w-full shadow-inner bg-gray-50/50 text-gray-800 transition-all font-medium">
                </div>
                @if (!empty($search))
                <button wire:click="clearSearch" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center sm:w-auto shadow-sm">
                    <span class="material-symbols-rounded mr-2">close</span> Clear
                </button>
                @endif
            </div>
        </div>

    <!-- 1. UPCOMING SCHEDULE -->
    <div class="mb-10">
        <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="material-symbols-rounded">schedule</span> Upcoming Schedule
            </h2>

            <!-- Custom Livewire Pagination for Upcoming Cards -->
            @if ($upcoming_tasks->lastPage() > 1)
            <div class="flex gap-1">
                <button wire:click="previousPage('up_page')" @if($upcoming_tasks->onFirstPage()) disabled class="w-8 h-8 flex items-center justify-center bg-white/5 text-white/30 rounded-full cursor-not-allowed" @else class="w-8 h-8 flex items-center justify-center bg-white/20 hover:bg-white/40 text-white rounded-full transition" @endif>
                    <span class="material-symbols-rounded text-lg">chevron_left</span>
                </button>
                <span class="px-2 flex items-center text-white text-xs font-bold">{{ $upcoming_tasks->currentPage() }} / {{ $upcoming_tasks->lastPage() }}</span>
                <button wire:click="nextPage('up_page')" @if(!$upcoming_tasks->hasMorePages()) disabled class="w-8 h-8 flex items-center justify-center bg-white/5 text-white/30 rounded-full cursor-not-allowed" @else class="w-8 h-8 flex items-center justify-center bg-white/20 hover:bg-white/40 text-white rounded-full transition" @endif>
                    <span class="material-symbols-rounded text-lg">chevron_right</span>
                </button>
            </div>
            @endif
        </div>

        @if ($upcoming_tasks->isEmpty())
        <div class="bg-white/90 backdrop-blur rounded-2xl p-6 text-center border border-dashed border-white/50">
            <p class="text-gray-500 font-medium">No upcoming tasks found.</p>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach ($upcoming_tasks as $job)
            @php
            // Dynamic Role Check
            $role = 'Team';
            if (!empty($job->lead_operator) && stripos($job->lead_operator, $fullName) !== false) $role = 'Lead Op';
            elseif (!empty($job->lead_deliverer) && stripos($job->lead_deliverer, $fullName) !== false) $role = 'Driver';

            $roleClass = ($role === 'Driver') ? 'role-driver' : (($role === 'Lead Op') ? 'role-lead' : 'role-team');
            @endphp
            <div class="bg-white rounded-2xl p-5 shadow-lg shadow-black/5 hover:shadow-xl transition group relative overflow-hidden flex flex-col h-full">
                <div class="absolute top-0 right-0 w-16 h-16 bg-[#FDF2F4] rounded-bl-full -mr-8 -mt-8 z-0"></div>
                <div class="relative z-10 flex-1 flex flex-col">
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-[#191D2C] font-extrabold text-sm tracking-tight">#{{ $job->id }}</span>
                            <div class="bg-gray-100 text-gray-500 font-bold text-[10px] px-2 py-1 rounded uppercase tracking-wide truncate max-w-[80px] sm:max-w-none">{{ $job->event_type }}</div>
                        </div>
                        <span class="role-pill {{ $roleClass }} shrink-0">{{ $role }}</span>
                    </div>
                    <div class="mb-3">
                        <h3 class="text-2xl font-black text-gray-800 leading-tight">
                            {{ \Carbon\Carbon::parse($job->event_date)->format('d') }}
                            <span class="text-lg font-medium text-gray-400">{{ \Carbon\Carbon::parse($job->event_date)->format('M') }}</span>
                        </h3>
                        <p class="text-[#9E6B73] font-bold text-sm flex items-center gap-1 mt-1">
                            <span class="material-symbols-rounded text-sm">schedule</span>
                            @if($job->custom_duration_text)
                                {{ $job->custom_duration_text }} @if(($job->duration_cost ?? 0) > 0) (${{ number_format($job->duration_cost, 2) }}) @endif
                            @else
                                {{ \Carbon\Carbon::parse($job->start_time)->format('g:i A') }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-start gap-2 mb-4">
                        <span class="material-symbols-rounded text-gray-300 text-lg shrink-0">location_on</span>
                        <p class="text-sm text-gray-600 leading-snug line-clamp-2" title="{{ $job->address_line_1 }}, {{ $job->suburb }}">
                            {{ $job->address_line_1 }}, {{ $job->suburb }}
                        </p>
                    </div>
                    <div class="mt-auto pt-3 mb-4 border-t border-gray-100 grid grid-cols-2 gap-2 text-xs">
                        <div class="overflow-hidden">
                            <span class="block text-gray-400 font-bold uppercase text-[10px] mb-0.5">Operator</span>
                            @if(!empty($job->lead_operator))
                            <span class="font-bold text-gray-700 truncate block" title="{{ $job->lead_operator }}">{{ $job->lead_operator }}</span>
                            @else
                            <span class="text-gray-400 italic">Unassigned</span>
                            @endif
                        </div>
                        <div class="overflow-hidden">
                            <span class="block text-gray-400 font-bold uppercase text-[10px] mb-0.5">Driver</span>
                            @if(!empty($job->lead_deliverer))
                            <span class="font-bold text-gray-700 truncate block" title="{{ $job->lead_deliverer }}">{{ $job->lead_deliverer }}</span>
                            @else
                            <span class="text-gray-400 italic">Unassigned</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('staff.bookings.overview', $job->id) }}" class="block w-full py-2.5 rounded-xl bg-gray-900 hover:bg-gray-800 text-white font-bold text-sm text-center transition shadow-md">View Job</a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- 2. CURRENT OPERATIONS (CONFIRMED) -->
    <div class="mb-10">
        <h2 class="text-xl font-bold text-white flex items-center gap-2 mb-4">
            <span class="material-symbols-rounded">check_circle</span> Current Operations (Confirmed)
        </h2>
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-white/50">
            <div class="w-full overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="p-5">Date</th>
                            <th class="p-5">Job ID</th>
                            <th class="p-5">Role</th>
                            <th class="p-5">Type</th>
                            <th class="p-5">Operator</th>
                            <th class="p-5">Driver</th>
                            <th class="p-5">Location</th>
                            <th class="p-5">Status</th>
                            <th class="p-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm">
                        @forelse ($curr_tasks as $job)
                        @php
                        $role = 'Team';
                        if (!empty($job->lead_operator) && stripos($job->lead_operator, $fullName) !== false) $role = 'Lead Op';
                        elseif (!empty($job->lead_deliverer) && stripos($job->lead_deliverer, $fullName) !== false) $role = 'Driver';
                        $roleClass = ($role === 'Driver') ? 'role-driver' : (($role === 'Lead Op') ? 'role-lead' : 'role-team');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-5 font-bold text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($job->event_date)->format('M d, Y') }}</td>
                            <td class="p-5 text-gray-500">#{{ $job->id }}</td>
                            <td class="p-5"><span class="role-pill {{ $roleClass }}">{{ $role }}</span></td>
                            <td class="p-5 font-medium text-gray-700">{{ $job->event_type }}</td>
                            <td class="p-5 text-gray-700 truncate max-w-[120px]" title="{{ $job->lead_operator }}">
                                {!! !empty($job->lead_operator) ? $job->lead_operator : '<span class="text-gray-400">-</span>' !!}
                            </td>
                            <td class="p-5 text-gray-700 truncate max-w-[120px]" title="{{ $job->lead_deliverer }}">
                                {!! !empty($job->lead_deliverer) ? $job->lead_deliverer : '<span class="text-gray-400">-</span>' !!}
                            </td>
                            <td class="p-5 text-gray-500 truncate max-w-xs">{{ $job->address_line_1 }}</td>
                            <td class="p-5"><span class="status-pill status-confirmed">Confirmed</span></td>
                            <td class="p-5 text-right"><a href="{{ route('staff.bookings.overview', $job->id) }}" class="text-gray-400 hover:text-[#9E6B73] transition"><span class="material-symbols-rounded">visibility</span></a></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-500 italic">No future confirmed operations.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Livewire Pagination for Current Operations -->
            @if ($curr_tasks->lastPage() > 1)
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50">
                <span class="text-xs text-gray-500 font-medium">Page {{ $curr_tasks->currentPage() }} of {{ $curr_tasks->lastPage() }}</span>
                <div class="flex gap-2">
                    <button wire:click="previousPage('curr_page')" @if($curr_tasks->onFirstPage()) disabled class="px-4 py-2 bg-gray-100 border text-gray-300 rounded-lg text-xs font-bold cursor-not-allowed" @else class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition" @endif>Previous</button>
                    <button wire:click="nextPage('curr_page')" @if(!$curr_tasks->hasMorePages()) disabled class="px-4 py-2 bg-gray-100 border text-gray-300 rounded-lg text-xs font-bold cursor-not-allowed" @else class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition" @endif>Next</button>
                </div>
            </div>
            @endif
        </div>
    </div>

    </div>
</div>