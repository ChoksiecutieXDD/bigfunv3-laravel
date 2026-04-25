<div x-data="{ remindersOpen: false }" class="w-full max-w-[1440px] mx-auto space-y-8 pb-12">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-white drop-shadow-sm">Calendar View</h2>
            <p class="text-white/80 mt-1 text-sm font-medium">Overview of appointments and financials.</p>
        </div>

        <a href="/supervisor/bookings/create" class="hidden md:flex bg-plum hover:bg-plum-dark text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-pink-900/20 items-center gap-2 transition transform hover:scale-105 active:scale-95 text-sm">
            <span class="material-symbols-rounded text-lg">add</span> New Booking
        </a>
    </div>

    <div class="bg-white rounded-[2rem] shadow-xl p-8">
        <h3 class="text-xl font-bold text-[#2D3748] mb-6 flex items-center justify-between">
            Monthly Financials
            {{-- FIXED: Added null, $currentMonth, 1 to prevent day-31 rollover bugs --}}
            <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full">{{ \Carbon\Carbon::create(null, $currentMonth, 1)->format('F') }} Stats</span>
        </h3>

        <div class="flex flex-col xl:flex-row gap-10">
            <div class="flex-1">
                <div class="grid grid-cols-3 pb-3 border-b border-gray-100 font-bold text-sm text-[#2D3748]">
                    <div class="text-left">Metric</div>
                    <div class="text-right">Bookings</div>
                    <div class="text-right">Revenue</div>
                </div>

                <div class="grid grid-cols-3 py-4 border-b border-gray-50 text-sm items-center">
                    <div class="text-left font-medium text-gray-600">This Month:</div>
                    <div class="text-right font-bold text-gray-800">{{ $stats['monthBookings'] ?? 0 }}</div>
                    <div class="text-right font-bold text-gray-800">${{ number_format($stats['monthRevenue'] ?? 0, 2) }}</div>
                </div>

                <div class="grid grid-cols-3 py-4 border-b border-gray-50 text-sm items-center">
                    <div class="text-left font-bold text-gray-800">Saturdays ({{ $stats['saturdayCount'] ?? 0 }}):</div>
                    <div class="text-right font-bold text-plum">{{ $stats['saturdayBookings'] ?? 0 }}</div>
                    <div class="text-right font-bold text-plum">${{ number_format($stats['saturdayRevenue'] ?? 0, 2) }}</div>
                </div>

                <div class="grid grid-cols-3 py-4 text-sm items-center">
                    <div class="text-left font-bold text-plum">Year To Date ({{ $currentYear }})</div>
                    <div class="text-right font-bold text-[#2D3748]">{{ $stats['ytdBookings'] ?? 0 }}</div>
                    <div class="text-right font-bold text-[#2D3748]">${{ number_format($stats['ytdRevenue'] ?? 0, 2) }}</div>
                </div>
            </div>

            <div class="xl:w-1/3 flex flex-col justify-center space-y-6 xl:border-l xl:pl-10 border-gray-100">
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-gray-600">Total Collected (This View):</span>
                    <span class="font-bold text-green-600">${{ number_format($stats['monthCollected'] ?? 0, 2) }}</span>
                </div>

                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-gray-600">Outstanding Balance (This View):</span>
                    <span class="font-bold text-red-500">${{ number_format($stats['monthBalance'] ?? 0, 2) }}</span>
                </div>

                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 mt-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="font-bold text-gray-700">Total Unpaid (All Time):</span>
                        <span class="font-extrabold text-red-600 text-base">
                            ${{ number_format($globalOutstandingBalance ?? 0, 2) }}
                        </span>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1 italic">Total booking value minus total payments collected across all years.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-xl flex flex-col h-[800px] overflow-hidden mt-8">

        <div class="calendar-header-bar p-5 flex flex-col justify-between shrink-0 z-30 relative gap-4">
            <div class="flex flex-col xl:flex-row justify-between items-center w-full gap-4">

                <div class="flex items-center gap-4 w-full md:w-auto justify-center md:justify-start">
                    <button wire:click="previousMonth" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl font-medium transition flex items-center justify-center backdrop-blur-sm shadow-sm">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>

                    <button wire:click="goToToday" class="bg-white/20 hover:bg-white/30 text-white px-3 h-10 rounded-xl font-bold text-xs uppercase tracking-wider transition flex items-center justify-center backdrop-blur-sm shadow-sm">
                        Today
                    </button>

                    <h3 class="text-2xl font-bold tracking-tight text-center min-w-[200px] text-white">
                        @if($showWholeYear)
                            Year {{ $currentYear }} Overview
                        @else
                            {{ \Carbon\Carbon::create(null, $currentMonth, 1)->format('F') }} {{ $currentYear }}
                        @endif
                    </h3>

                    <button wire:click="nextMonth" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl font-medium transition flex items-center justify-center backdrop-blur-sm shadow-sm">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto justify-center flex-wrap">

                    <!-- TOGGLE BUTTONS -->
                    <button wire:click="$toggle('showOnlyBooked')" class="bg-white text-plum font-bold text-xs rounded-xl h-10 px-4 focus:outline-none hover:bg-gray-50 transition border border-white/20 shadow-sm flex items-center gap-2">
                        <span class="material-symbols-rounded text-lg">{{ $showOnlyBooked ? 'visibility' : 'visibility_off' }}</span>
                        {{ $showOnlyBooked ? 'Booked Only' : 'All Days' }}
                    </button>

                    <button wire:click="$toggle('showWholeYear')" class="{{ $showWholeYear ? 'bg-plum text-white border-white/40' : 'bg-white text-plum' }} font-bold text-xs rounded-xl h-10 px-4 focus:outline-none transition border shadow-sm flex items-center gap-2">
                        <span class="material-symbols-rounded text-lg">calendar_month</span>
                        {{ $showWholeYear ? 'Switch to Month' : 'Whole Year' }}
                    </button>

                    <select wire:model.live="currentMonth" class="filter-select bg-white text-gray-600 font-bold text-xs rounded-xl h-10 focus:outline-none cursor-pointer hover:bg-gray-50 transition border border-white/20 shadow-sm w-36">
                        @foreach(range(1, 12) as $m)
                        {{-- Added @selected to force the correct option on load --}}
                        <option value="{{ $m }}" @selected($m==$currentMonth)>
                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                        </option>
                        @endforeach
                    </select>

                    <select wire:model.live="currentYear" class="filter-select bg-white text-gray-600 font-bold text-xs rounded-xl h-10 focus:outline-none cursor-pointer hover:bg-gray-50 transition border border-white/20 shadow-sm w-28">
                        @foreach($yearRange as $yr)
                        {{-- Added @selected to force the correct option on load --}}
                        <option value="{{ $yr }}" @selected($yr==$currentYear)>
                            {{ $yr }}
                        </option>
                        @endforeach
                    </select>

                    <div class="h-6 w-px bg-white/30 mx-1 hidden sm:block"></div>

                    <select wire:model.live="statusFilter" class="filter-select bg-white text-plum font-bold text-xs rounded-xl h-10 focus:outline-none cursor-pointer hover:bg-gray-50 transition border border-white/20 shadow-sm w-36">
                        <option value="All">All Status</option>
                        <option value="Booked">Booked (Confirmed)</option>
                        <option value="Pending">Pending</option>
                        <option value="Hold">Hold</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Draft">Drafts</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 text-[11px] font-bold text-white bg-black/10 p-2.5 rounded-xl justify-center w-full">
                <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-red"></span>No Deposit</div>
                <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-orange"></span>Deposit Paid</div>
                <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-blue"></span>Partial (50%+)</div>
                <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-purple"></span>Finalizing (80%+)</div>
                <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-green"></span>Fully Paid</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar bg-white relative p-4 space-y-4">

            <div wire:loading class="absolute inset-0 bg-white/70 backdrop-blur-sm z-20 flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-4 border-gray-200 border-t-plum"></div>
            </div>

            @forelse($calendarDays as $date => $bookings)
            <div>
                <div class="day-header flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100 mb-3">
                    <span class="font-bold text-gray-700">{{ \Carbon\Carbon::parse($date)->format('l, F jS') }}</span>
                    <span class="text-xs bg-plum text-white px-2.5 py-1 rounded-full">{{ count($bookings) }}</span>
                </div>

                @if(count($bookings) > 0)
                <div class="space-y-3">
                    @foreach($bookings as $booking)
                    <a href="{{ route('supervisor.bookings.overview', ['id' => $booking->id, 'back' => route('supervisor.calendar')]) }}" class="booking-card card-{{ $booking->color_code }}">
                        <div>
                            <span class="booking-label">Time & Duration</span>
                            <span class="booking-value block">
                                @if($booking->duration === 'custom' && $booking->custom_duration_text)
                                    {{ $booking->custom_duration_text }}
                                @else
                                    {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ $booking->end_time ? \Carbon\Carbon::parse($booking->end_time)->format('g:i A') : 'TBD' }}
                                @endif
                            </span>
                            <span class="text-xs font-bold mt-1 text-{{ $booking->color_code }}-600 flex items-center gap-1">
                                <span class="legend-dot dot-{{ $booking->color_code }}"></span> {{ $booking->status_label }}
                            </span>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @php
                                    $statusClasses = match($booking->status) {
                                        'Confirmed', 'Booked' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                        'Pending', 'Draft' => 'bg-amber-50 text-amber-600 border-amber-100',
                                        'Completed' => 'bg-blue-50 text-blue-600 border-blue-100',
                                        'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                                        default => 'bg-gray-50 text-gray-500 border-gray-100'
                                    };
                                @endphp
                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-tight border {{ $statusClasses }}">
                                    {{ $booking->status }}
                                </span>
                                <span class="pill pill-install text-[9px] bg-plum/10 text-plum border-plum/20">OP: {{ $booking->lead_operator ?: 'TBD' }}</span>
                                <span class="pill pill-install text-[9px] bg-blue-50 text-blue-600 border-blue-100">DEL: {{ $booking->lead_deliverer ?: 'TBD' }}</span>
                            </div>
                            @if($booking->booked_by)
                                <div class="mt-2 text-[10px] font-bold text-gray-400 flex items-center gap-1">
                                    <span class="material-symbols-rounded text-xs">person_add</span>
                                    By: {{ $booking->booked_by }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <span class="booking-label">Customer</span>
                            <span class="booking-value block">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</span>
                            <span class="text-xs text-gray-500 truncate block">{{ $booking->suburb }}</span>
                        </div>

                        <div>
                            <span class="booking-label">Services</span>
                            <span class="booking-value block text-sm">{{ $booking->services_booked ?? 'N/A' }}</span>
                        </div>

                        <div>
                            <span class="booking-label">Balance</span>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Total:</span>
                                <span class="font-bold">${{ number_format($booking->total_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Paid:</span>
                                <span class="font-bold text-green-600">${{ number_format($booking->real_paid, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm border-t border-gray-100 mt-1 pt-1">
                                <span class="text-gray-500 text-xs">Due:</span>
                                <span class="font-bold text-red-500">${{ number_format($booking->total_amount - $booking->real_paid, 2) }}</span>
                            </div>
                        </div>

                        <div>
                            <span class="booking-label">Terms & Type</span>
                            <div class="flex flex-col gap-1 mt-1">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-tighter {{ $booking->terms_agreed ? 'bg-green-50 text-green-600 border border-green-100' : 'bg-red-50 text-red-600 border border-red-100' }}">
                                    {{ $booking->terms_agreed ? 'Agreed' : 'No Terms' }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-tighter bg-blue-50 text-blue-600 border border-blue-100">
                                    {{ $booking->payment_type ?: 'EFT' }}
                                </span>
                            </div>
                        </div>


                    </a>
                    @endforeach
                </div>
                @else
                <!-- Empty state placeholder for dates without bookings -->
                <div class="py-4 text-center text-gray-400 border-2 border-dashed border-gray-100 rounded-xl text-sm italic">
                    No bookings scheduled.
                </div>
                @endif
            </div>
            @empty
            <div class="p-10 text-center text-gray-400 flex flex-col items-center">
                <span class="material-symbols-rounded text-4xl mb-2 opacity-50">event_busy</span>
                No days found for this period.
            </div>
            @endforelse
        </div>
    </div>

    <button @click="remindersOpen = true" class="fab-reminders z-30 bg-white hover:bg-gray-50 text-plum w-12 h-12 rounded-full border border-gray-200 flex items-center justify-center transition shadow-lg group">
        <span class="material-symbols-rounded text-2xl group-hover:scale-110 transition">notifications</span>

        @if(($stats['urgentAlertsCount'] ?? 0) > 0)
        <span class="fab-badge flex">{{ $stats['urgentAlertsCount'] }}</span>
        @endif
    </button>

    <a href="/supervisor/bookings/create" class="lg:hidden fixed bottom-6 right-6 w-14 h-14 bg-plum text-white rounded-full shadow-2xl flex items-center justify-center hover:bg-plum-dark transition transform active:scale-90 border-4 border-white z-30">
        <span class="material-symbols-rounded text-2xl">add</span>
    </a>

    <div class="reminder-panel" :class="{ 'open': remindersOpen }">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                <span class="material-symbols-rounded text-plum">notifications_active</span> Reminders
            </h3>
            <button @click="remindersOpen = false" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-6">
            <div>
                <h4 class="text-xs font-bold text-red-500 uppercase mb-3 tracking-wider flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">payments</span> Outstanding Debt (Past Events)
                </h4>
                <div class="space-y-2">
                    @forelse($debtAlerts ?? [] as $alert)
                    <a href="{{ route('supervisor.bookings.overview', $alert->id) }}" class="block p-3 bg-red-50 hover:bg-red-100 border border-red-100 rounded-lg text-sm transition shadow-sm group">
                        <div class="flex justify-between items-start mb-1">
                            <p class="font-bold text-red-700 group-hover:text-red-800 transition">{{ $alert->customer_first_name }} {{ $alert->customer_last_name }}</p>
                            <span class="text-[10px] font-bold text-red-500 bg-red-100 px-1.5 py-0.5 rounded">{{ \Carbon\Carbon::parse($alert->event_date)->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                             <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-tight border bg-blue-50 text-blue-600 border-blue-100">
                                {{ $alert->status }}
                            </span>
                            <p class="text-[10px] text-red-600/80 font-medium italic">INV-{{ $alert->invoice_number ?? $alert->id }}</p>
                        </div>
                        <p class="text-red-600 font-bold mt-1.5 flex items-center justify-between border-t border-red-200/30 pt-1.5">
                            <span class="text-[10px] uppercase tracking-widest font-black">Debt Amount:</span>
                            <span class="text-base">${{ number_format($alert->balance, 2) }}</span>
                        </p>
                    </a>
                    @empty
                    <div class="text-xs text-gray-400 italic">No past outstanding debt.</div>
                    @endforelse
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-orange-600 uppercase mb-3 tracking-wider flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">priority_high</span> Upcoming (3 Days)
                </h4>
                <div class="space-y-2">
                    @forelse($upcomingEvents3Days ?? [] as $event)
                    @php
                        $statusClasses = match($event->status) {
                            'Confirmed', 'Booked' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                            'Pending', 'Draft' => 'bg-amber-50 text-amber-600 border-amber-100',
                            'Completed' => 'bg-blue-50 text-blue-600 border-blue-100',
                            'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                            default => 'bg-gray-50 text-gray-500 border-gray-100'
                        };
                    @endphp
                    <a href="{{ route('supervisor.bookings.overview', $event->id) }}" class="block p-3 bg-orange-50 hover:bg-orange-100 border-orange-200 border rounded-lg text-sm transition shadow-sm group">
                        <div class="flex justify-between items-start mb-1">
                            <p class="font-bold text-orange-700 group-hover:text-orange-800 transition">{{ \Carbon\Carbon::parse($event->event_date)->format('l, M d') }}</p>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded text-orange-600 bg-orange-200/50">{{ \Carbon\Carbon::parse($event->start_time)->format('g:i A') }}</span>
                        </div>
                        <div class="flex items-center gap-2 mb-1">
                             <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-tight border {{ $statusClasses }}">
                                {{ $event->status }}
                            </span>
                            <p class="font-bold text-orange-900">{{ $event->customer_first_name }} {{ $event->customer_last_name }}</p>
                        </div>
                        <div class="flex justify-between items-center mt-1 pt-1 border-t border-orange-200/50">
                            <p class="text-[10px] text-orange-600/80 truncate">{{ $event->suburb ?? 'No Address' }}</p>
                        </div>
                    </a>
                    @empty
                    <div class="text-xs text-gray-400 italic">No events in the next 3 days.</div>
                    @endforelse
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-wider flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">event_upcoming</span> Upcoming (4-7 Days)
                </h4>
                <div class="space-y-2">
                    @forelse($upcomingEvents7Days ?? [] as $event)
                    @php
                        $statusClasses = match($event->status) {
                            'Confirmed', 'Booked' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                            'Pending', 'Draft' => 'bg-amber-50 text-amber-600 border-amber-100',
                            'Completed' => 'bg-blue-50 text-blue-600 border-blue-100',
                            'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                            default => 'bg-gray-50 text-gray-500 border-gray-100'
                        };
                    @endphp
                    <a href="{{ route('supervisor.bookings.overview', $event->id) }}" class="block p-3 border rounded-lg text-sm transition shadow-sm group bg-gray-50 hover:bg-white border-gray-100 hover:border-[#9D686E]/40">
                        <div class="flex justify-between items-start mb-1">
                            <p class="font-bold transition text-gray-700 group-hover:text-[#9D686E]">{{ \Carbon\Carbon::parse($event->event_date)->format('l, M d') }}</p>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded text-gray-500 bg-gray-200/50">{{ \Carbon\Carbon::parse($event->start_time)->format('g:i A') }}</span>
                        </div>
                        <div class="flex items-center gap-2 mb-1">
                             <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-tight border {{ $statusClasses }}">
                                {{ $event->status }}
                            </span>
                            <p class="font-bold text-gray-800">{{ $event->customer_first_name }} {{ $event->customer_last_name }}</p>
                        </div>
                        <div class="flex justify-between items-center mt-1 pt-1 border-t border-gray-200/50">
                            <p class="text-[10px] text-gray-500 truncate">{{ $event->suburb ?? 'No Address' }}</p>
                        </div>
                    </a>
                    @empty
                    <div class="text-xs text-gray-400 italic">No events in the 4-7 day window.</div>
                    @endforelse
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-orange-500 uppercase mb-3 tracking-wider flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">history</span> Pending Status Updates
                </h4>
                <div class="space-y-2">
                    @forelse($pendingCompletionAlerts ?? [] as $alert)
                    <a href="{{ route('supervisor.bookings.overview', $alert->id) }}" class="block p-3 bg-red-50 hover:bg-red-100 border border-red-100 rounded-lg text-sm transition shadow-sm group">
                        <div class="flex justify-between items-start mb-1">
                            <p class="font-bold text-red-700 group-hover:text-red-800 transition">{{ $alert->customer_first_name }} {{ $alert->customer_last_name }}</p>
                            <span class="text-[10px] font-bold text-red-500 bg-red-100 px-1.5 py-0.5 rounded">{{ \Carbon\Carbon::parse($alert->event_date)->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-center items-center py-1">
                            <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-tight border bg-amber-50 text-amber-600 border-amber-100">
                                Still marked as: {{ $alert->status }}
                            </span>
                        </div>
                        <p class="text-[10px] text-red-500/70 font-bold mt-1.5 italic text-center">Event date passed - update status to Complete/Cancel</p>
                    </a>
                    @empty
                    <div class="text-xs text-gray-400 italic">All statuses up to date.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>


</div>