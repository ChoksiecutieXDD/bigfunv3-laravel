<div class="max-w-[1440px] mx-auto w-full pb-12">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-6">
        <div>
            <h1 class="text-3xl font-extrabold text-white drop-shadow-sm">Lead Assignments</h1>
            <p class="text-white/90 font-medium mt-1">Confirmed jobs where you are the Lead Operator.</p>
            <p class="text-[10px] text-white/50 mt-1">Logged in as: {{ $fullName }}</p>
        </div>

        <div class="flex items-center gap-8 text-sm font-medium w-full md:w-auto border-b md:border-none border-white/20 pb-1 md:pb-0 overflow-x-auto no-scrollbar">
            <button wire:click="switchTab('upcoming')" class="pb-2 px-2 transition-all duration-200 {{ $activeTab == 'upcoming' ? 'border-b-4 border-white text-white font-bold' : 'text-white/70 hover:text-white border-b-4 border-transparent' }}">
                Upcoming
            </button>
            <button wire:click="switchTab('past')" class="pb-2 px-2 transition-all duration-200 {{ $activeTab == 'past' ? 'border-b-4 border-white text-white font-bold' : 'text-white/70 hover:text-white border-b-4 border-transparent' }}">
                History
            </button>
        </div>
    </div>

    <!-- Search Section -->
    <div class="bg-white/95 backdrop-blur-md rounded-2xl p-6 mb-8 border border-white/50 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-rounded text-[#9E6B73]">search</span>
            <h2 class="text-lg font-bold text-gray-800">Find Assignments</h2>
        </div>
        <div class="flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
                <span class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search Booking ID, Name, or Location..."
                    class="pl-10 pr-4 py-3 rounded-xl text-sm border border-gray-100 focus:border-[#9E6B73] focus:ring-4 focus:ring-[#9E6B73]/10 w-full shadow-sm bg-white/50 text-gray-800 transition-all">
            </div>
            @if(!empty($search))
                <button wire:click="$set('search', '')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl text-sm font-bold transition-all shadow-sm">Clear</button>
            @endif
        </div>
    </div>

    <!-- Upcoming View -->
    <div class="{{ $activeTab == 'upcoming' ? '' : 'hidden' }}">
        @if($upcomingAssignments->isEmpty())
            <div class="bg-white/90 backdrop-blur rounded-[2rem] p-16 text-center border border-white/50 shadow-sm">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300">
                    <span class="material-symbols-rounded text-5xl">event_available</span>
                </div>
                <h3 class="text-2xl font-black text-gray-800">No Confirmed Assignments</h3>
                <p class="text-gray-500 mt-2 max-w-sm mx-auto">No upcoming <span class="font-bold text-[#9E6B73]">Confirmed</span> lead jobs found matching your criteria.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach ($upcomingAssignments as $job)
                    <div class="bg-white rounded-[1.5rem] p-6 shadow-sm border border-gray-50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col h-full relative overflow-hidden">
                        <!-- Decorative element -->
                        <div class="absolute top-0 right-0 w-24 h-24 bg-[#FDF2F4] rounded-bl-[4rem] -mr-12 -mt-12 transition-all group-hover:bg-[#FDF2F4]/60"></div>

                        <div class="relative z-10 flex justify-between items-start mb-5">
                            <div class="bg-[#FDF2F4] text-[#9E6B73] font-bold text-[10px] px-3 py-1.5 rounded-lg border border-[#9E6B73]/10 uppercase tracking-widest truncate max-w-[150px]">
                                {{ $job->event_type }}
                            </div>
                            <span class="bg-pink-100 text-pink-700 text-[10px] font-black px-3 py-1 rounded-full border border-pink-200 uppercase">Lead Op</span>
                        </div>

                        <div class="relative z-10 mb-6">
                            <h3 class="text-2xl font-black text-gray-800 leading-tight">
                                {{ \Carbon\Carbon::parse($job->event_date)->format('D, M d') }}
                            </h3>
                            <div class="flex items-center gap-2 text-[#9E6B73] font-bold mt-1 text-sm">
                                <span class="material-symbols-rounded text-lg">schedule</span>
                                {{ \Carbon\Carbon::parse($job->start_time)->format('g:i A') }}
                            </div>
                        </div>

                        <div class="w-full h-px bg-gray-50 mb-6 relative z-10"></div>

                        <div class="relative z-10 space-y-4 mb-8 flex-1">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-blue-400 text-lg">person</span>
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Customer</p>
                                    <p class="text-sm font-bold text-gray-700 truncate">{{ $job->customer_first_name }} {{ $job->customer_last_name }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-orange-400 text-lg">location_on</span>
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Location</p>
                                    <p class="text-sm font-medium text-gray-600 leading-snug line-clamp-2" title="{{ $job->address_line_1 }}">
                                        {{ $job->address_line_1 }}, {{ $job->suburb }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('booking.overview', $job->id) }}" class="relative z-10 w-full py-4 rounded-xl bg-gray-900 text-white font-bold text-sm flex items-center justify-center gap-2 hover:bg-[#9E6B73] hover:shadow-lg hover:shadow-gray-200 transition-all">
                            View Details <span class="material-symbols-rounded text-base">arrow_forward_ios</span>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $upcomingAssignments->links() }}
            </div>
        @endif
    </div>

    <!-- History View -->
    <div class="{{ $activeTab == 'past' ? '' : 'hidden' }}">
        @if($pastAssignments->isEmpty())
            <div class="bg-white/90 backdrop-blur rounded-[2rem] p-16 text-center border border-white/50 shadow-sm">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300">
                    <span class="material-symbols-rounded text-5xl">history</span>
                </div>
                <h3 class="text-2xl font-black text-gray-800">No History Found</h3>
                <p class="text-gray-500 mt-2 max-w-sm mx-auto">Completed or cancelled lead jobs will appear here.</p>
            </div>
        @else
            <div class="bg-white rounded-[1.5rem] shadow-sm overflow-hidden border border-gray-100 mb-8">
                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] border-b border-gray-100">
                                <th class="p-5">Date</th>
                                <th class="p-5">Job ID</th>
                                <th class="p-5">Customer</th>
                                <th class="p-5">Location</th>
                                <th class="p-5">Status</th>
                                <th class="p-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($pastAssignments as $job)
                                <tr class="hover:bg-[#FDF2F4]/40 transition-colors group">
                                    <td class="p-5">
                                        <p class="font-black text-gray-800">{{ \Carbon\Carbon::parse($job->event_date)->format('M d, Y') }}</p>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mt-0.5">{{ \Carbon\Carbon::parse($job->start_time)->format('g:i A') }}</p>
                                    </td>
                                    <td class="p-5">
                                        <span class="px-3 py-1 rounded-lg bg-gray-100 text-gray-500 font-bold text-[11px]">#{{ $job->id }}</span>
                                    </td>
                                    <td class="p-5">
                                        <p class="font-bold text-gray-700 whitespace-nowrap">{{ $job->customer_first_name }} {{ $job->customer_last_name }}</p>
                                    </td>
                                    <td class="p-5">
                                        <p class="text-xs font-medium text-gray-500 truncate max-w-xs" title="{{ $job->address_line_1 }}">{{ $job->address_line_1 }}</p>
                                    </td>
                                    <td class="p-5">
                                        @php
                                            $stClass = 'bg-gray-100 text-gray-500 border-gray-200';
                                            if ($job->status == 'Completed') $stClass = 'bg-green-50 text-green-700 border-green-200';
                                            if ($job->status == 'Cancelled') $stClass = 'bg-red-50 text-red-600 border-red-200';
                                            if ($job->status == 'Confirmed') $stClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                        @endphp
                                        <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase border {{ $stClass }}">
                                            {{ $job->status }}
                                        </span>
                                    </td>
                                    <td class="p-5 text-right">
                                        <a href="{{ route('booking.overview', $job->id) }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-[#9E6B73] hover:text-white transition-all shadow-sm hover:shadow-md">
                                            <span class="material-symbols-rounded">visibility</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8">
                {{ $pastAssignments->links() }}
            </div>
        @endif
    </div>
</div>
