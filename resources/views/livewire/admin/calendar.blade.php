<div class="max-w-[1440px] mx-auto w-full">
    <style>
        .calendar-header-bar {
            background-color: #9E6B73;
            color: white;
            border-top-left-radius: 1.5rem;
            border-top-right-radius: 1.5rem;
        }

        .booking-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 1rem;
            background: white;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
            cursor: pointer;
            border-left: 4px solid transparent;
            text-decoration: none;
        }

        @media (min-width: 1024px) {
            .booking-card {
                display: grid;
                grid-template-columns: 1.2fr 1.2fr 1fr 1.6fr 1fr;
                gap: 1rem;
                align-items: center;
                flex-direction: row;
            }
        }

        .booking-card:hover {
            background-color: #fff5f7;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            z-index: 10;
        }

        .card-red {
            border-left-color: #ef4444;
            background-color: #fef2f2;
        }

        .card-orange {
            border-left-color: #f97316;
            background-color: #fff7ed;
        }

        .card-blue {
            border-left-color: #3b82f6;
            background-color: #eff6ff;
        }

        .card-purple {
            border-left-color: #a855f7;
            background-color: #faf5ff;
        }

        .card-green {
            border-left-color: #22c55e;
            background-color: #f0fdf4;
        }

        .card-gray {
            border-left-color: #9ca3af;
            background-color: #f3f4f6;
            opacity: 0.7;
        }

        .booking-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            color: #9ca3af;
            font-weight: 700;
            display: block;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .booking-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: #2D3748;
            line-height: 1.3;
        }

        .day-header {
            background-color: #f8fafc;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            color: #9E6B73;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .dot-red {
            background-color: #ef4444;
        }

        .dot-orange {
            background-color: #f97316;
        }

        .dot-blue {
            background-color: #3b82f6;
        }

        .dot-purple {
            background-color: #a855f7;
        }

        .dot-green {
            background-color: #22c55e;
        }

        .filter-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239E6B73' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.2em 1.2em;
            padding-right: 2rem;
            padding-left: 1rem;
            -webkit-appearance: none;
            appearance: none;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .pill-install {
            background-color: #e0f2fe;
            color: #1e40af;
        }
    </style>

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 lg:mb-8">
        <div>
            <h2 class="text-2xl lg:text-3xl font-bold text-white">Calendar View</h2>
            <p class="text-white/80 mt-1 text-sm font-medium">Overview of appointments and financials.</p>
        </div>

        <!-- ✅ New Booking Href Attached Here -->
        <a href="{{ route('admin.bookings.create') }}" wire:navigate class="hidden md:flex bg-[#9E6B73] hover:bg-[#86545C] text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-pink-900/20 items-center gap-2 transition transform hover:scale-105 active:scale-95 text-sm">
            <span class="material-symbols-rounded text-lg">add</span> New Booking
        </a>
    </div>

    <!-- Financial Stats Card -->
    <div class="bg-white rounded-[2rem] shadow-xl p-6 lg:p-8 mb-6 lg:mb-8">
        <h3 class="text-lg lg:text-xl font-bold text-[#2D3748] mb-6 flex items-center justify-between">
            Monthly Financials
            <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full">{{ $months[$currentMonth] }}</span>
        </h3>

        <div class="flex flex-col xl:flex-row gap-8 lg:gap-10">
            <div class="flex-1">
                <div class="hidden sm:grid grid-cols-3 pb-3 border-b border-gray-100 font-bold text-sm text-[#2D3748]">
                    <div class="text-left">Metric</div>
                    <div class="text-right">Bookings</div>
                    <div class="text-right">Revenue</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-0 py-4 border-b border-gray-50 text-sm items-center">
                    <div class="text-left font-medium text-gray-600 flex justify-between sm:block">
                        <span>This Month:</span>
                        <span class="sm:hidden font-bold text-gray-800">{{ $calendarData['stats']['mCount'] }}</span>
                    </div>
                    <div class="hidden sm:block text-right font-bold text-gray-800">{{ $calendarData['stats']['mCount'] }}</div>
                    <div class="text-right font-bold text-gray-800">${{ number_format($calendarData['stats']['mRev'], 2) }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-0 py-4 border-b border-gray-50 text-sm items-center">
                    <div class="text-left font-bold text-gray-800 flex justify-between sm:block">
                        <span>Saturdays ({{ $calendarData['stats']['satCount'] }}):</span>
                        <span class="sm:hidden font-bold text-[#9E6B73]">{{ $calendarData['stats']['satBookings'] }}</span>
                    </div>
                    <div class="hidden sm:block text-right font-bold text-[#9E6B73]">{{ $calendarData['stats']['satBookings'] }}</div>
                    <div class="text-right font-bold text-[#9E6B73]">${{ number_format($calendarData['stats']['satRev'], 2) }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-0 py-4 text-sm items-center">
                    <div class="text-left font-bold text-[#9E6B73] flex justify-between sm:block">
                        <span>YTD ({{ $currentYear }}):</span>
                        <span class="sm:hidden font-bold text-[#2D3748]">{{ $calendarData['stats']['ytdCount'] }}</span>
                    </div>
                    <div class="hidden sm:block text-right font-bold text-[#2D3748]">{{ $calendarData['stats']['ytdCount'] }}</div>
                    <div class="text-right font-bold text-[#2D3748]">${{ number_format($calendarData['stats']['ytdRev'], 2) }}</div>
                </div>
            </div>

            <div class="xl:w-1/3 flex flex-col justify-center space-y-4 lg:space-y-6 xl:border-l xl:pl-10 border-gray-100">
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-gray-600">Total Collected (This View):</span>
                    <span class="font-bold text-green-600">${{ number_format($calendarData['stats']['mPaid'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-gray-600">Outstanding (This View):</span>
                    <span class="font-bold text-red-500">${{ number_format($calendarData['stats']['mBal'], 2) }}</span>
                </div>

                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 mt-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="font-bold text-gray-700">Total Unpaid (All Time):</span>
                        <span class="font-extrabold text-red-600 text-base">
                            ${{ number_format($global_outstanding_balance, 2) }}
                        </span>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1 italic">Total booking value minus total payments collected across all years.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar View Container -->
    <div class="bg-white rounded-[2rem] shadow-xl flex flex-col overflow-hidden relative">

        <!-- Loading Overlay during wire actions -->
        <div wire:loading class="absolute inset-0 z-50 bg-white/60 backdrop-blur-sm flex items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-gray-200 border-t-[#9E6B73]"></div>
        </div>

        <div class="calendar-header-bar p-4 lg:p-5 flex flex-col justify-between shrink-0 z-30 relative gap-4">
            <div class="flex flex-col xl:flex-row justify-between items-center w-full gap-4">

                <!-- Month Navigation -->
                <div class="flex items-center gap-2 lg:gap-4 w-full md:w-auto justify-between md:justify-start order-1 xl:order-1">
                    <button wire:click="previousMonth" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl font-medium transition flex items-center justify-center backdrop-blur-sm shadow-sm"><span class="material-symbols-rounded">chevron_left</span></button>
                    <h3 class="text-xl lg:text-2xl font-bold tracking-tight text-center min-w-[140px]">{{ $months[$currentMonth] }} {{ $currentYear }}</h3>
                    <button wire:click="nextMonth" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl font-medium transition flex items-center justify-center backdrop-blur-sm shadow-sm"><span class="material-symbols-rounded">chevron_right</span></button>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap md:flex-nowrap items-center gap-2 lg:gap-4 w-full xl:w-auto justify-center order-2 xl:order-2">
                    <button wire:click="goToToday" class="bg-white/20 hover:bg-white/30 text-white px-3 h-10 rounded-xl font-bold text-xs uppercase tracking-wider transition flex items-center justify-center backdrop-blur-sm shadow-sm grow md:grow-0">
                        Today
                    </button>

                    <select wire:model.live="currentMonth" class="filter-select bg-white text-gray-600 font-bold text-xs rounded-xl h-10 focus:outline-none cursor-pointer hover:bg-gray-50 transition border border-white/20 shadow-sm w-1/2 md:w-36 grow md:grow-0">
                        @foreach($months as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="currentYear" class="filter-select bg-white text-gray-600 font-bold text-xs rounded-xl h-10 focus:outline-none cursor-pointer hover:bg-gray-50 transition border border-white/20 shadow-sm w-1/3 md:w-28 grow md:grow-0">
                        @foreach ($yearRange as $yr)
                        <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>

                    <div class="h-6 w-px bg-white/30 mx-1 hidden xl:block"></div>

                    <select wire:model.live="statusFilter" class="filter-select bg-white text-[#9E6B73] font-bold text-xs rounded-xl h-10 focus:outline-none cursor-pointer hover:bg-gray-50 transition border border-white/20 shadow-sm w-full md:w-36 grow md:grow-0">
                        <option value="All">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Draft">Drafts</option>
                    </select>
                </div>
            </div>

            <!-- Legend -->
            <div class="overflow-x-auto pb-1 -mb-1 w-full">
                <div class="flex gap-2 text-[10px] lg:text-[11px] font-bold text-white bg-black/10 p-2.5 rounded-xl min-w-max xl:w-full xl:justify-center">
                    <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-red"></span>No Deposit</div>
                    <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-orange"></span>Deposit Paid</div>
                    <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-blue"></span>Partial (50%+)</div>
                    <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-purple"></span>Finalizing (80%+)</div>
                    <div class="flex items-center px-2 py-0.5 rounded-lg hover:bg-white/10"><span class="legend-dot dot-green"></span>Fully Paid</div>
                </div>
            </div>
        </div>

        <!-- Scrollable Booking List -->
        <div class="flex-1 max-h-[700px] overflow-y-auto custom-scrollbar bg-white relative p-4 lg:p-6">
            @foreach($calendarData['days'] as $day => $data)
            <div class="mb-8">
                <div class="day-header rounded-xl mb-4 flex justify-between items-center shadow-sm border border-slate-200">
                    <span class="text-lg">{{ $data['date'] }}</span>
                    <span class="text-xs bg-[#9E6B73]/10 text-[#9E6B73] px-3 py-1 rounded-full font-bold">{{ count($data['bookings']) }} Events</span>
                </div>

                @forelse($data['bookings'] as $b)
                @php
                $v = $b->viewData;
                $statusBadge = '<span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500 font-bold">' . $b->status . '</span>';
                if ($b->status === 'Completed') $statusBadge = '<span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-600 font-bold">Completed</span>';
                if ($b->status === 'Cancelled') $statusBadge = '<span class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-500 font-bold">Cancelled</span>';

                $termsBadge = $b->terms_agreed == 1 ? '<span class="ml-2 text-[10px] font-bold text-green-600 flex items-center gap-1 border border-green-200 px-1.5 py-0.5 rounded bg-white"><span class="material-symbols-rounded text-sm">check_circle</span> Terms Signed</span>' : '<span class="ml-2 text-[10px] font-bold text-gray-400 flex items-center gap-1 border border-gray-200 px-1.5 py-0.5 rounded bg-white"><span class="material-symbols-rounded text-sm">pending</span> Terms Pending</span>';
                @endphp

                <!-- ✅ Wrapped card in an Anchor Tag -->
                <a href="{{ route('booking.overview', ['id' => $b->id, 'back' => route('admin.calendar')]) }}" wire:navigate class="booking-card rounded-xl card-{{ $v['color'] }} mb-3">
                    <div class="w-full min-w-0">
                        <span class="booking-label">Time & Location</span>
                        <div class="booking-value">{{ \Carbon\Carbon::parse($b->start_time)->format('g:i A') }} - {{ $b->end_time ? \Carbon\Carbon::parse($b->end_time)->format('g:i A') : 'TBD' }}</div>
                        <div class="text-xs text-gray-500 truncate mt-0.5 w-full" title="{{ $b->address_line_1 }}">{{ $b->address_line_1 }}</div>
                    </div>
                    <div class="w-full min-w-0">
                        <span class="booking-label">Customer</span>
                        <div class="booking-value truncate w-full" title="{{ $b->customer_first_name }} {{ $b->customer_last_name }}">{{ $b->customer_first_name }} {{ $b->customer_last_name }}</div>
                        <div class="mt-1 flex items-center flex-wrap">{!! $statusBadge !!} {!! $termsBadge !!}</div>
                    </div>
                    <div class="w-full min-w-0">
                        <span class="booking-label">Staff & Del</span>
                        <div class="flex flex-col gap-1 mt-0.5">
                            <div class="flex items-center gap-1 text-[11px] text-gray-700" title="Operator"><span class="material-symbols-rounded text-[14px] text-[#9E6B73]">engineering</span> {{ $v['op_name'] }}</div>
                            <div class="flex items-center gap-1 text-[11px] text-gray-700" title="Deliverer"><span class="material-symbols-rounded text-[14px] text-blue-500">local_shipping</span> {{ $v['del_name'] }}</div>
                        </div>
                    </div>
                    <div class="w-full min-w-0">
                        <span class="booking-label">Rides & Install</span>
                        <div class="booking-value truncate text-xs mb-1 w-full" title="{{ $b->services_booked ?? 'None' }}">{{ $b->services_booked ?? 'None' }}</div>
                        <span class="pill pill-install">{{ $b->installation_plan ?? 'Standard' }}</span>
                    </div>
                    <div class="w-full min-w-0 text-left">
                        <span class="booking-label uppercase">{{ $v['label'] }}</span>
                        <div class="booking-value text-[#1e293b] text-base font-bold mt-0.5">${{ number_format($b->total_amount, 2) }}</div>
                        <div class="text-xs flex flex-col gap-0.5 mt-1">
                            <span class="text-[#9ca3af]">Paid: ${{ number_format($b->real_paid, 2) }}</span>
                            @if($v['balance_due'] > 0)
                            <span class="text-red-500 font-bold">Bal: ${{ number_format($v['balance_due'], 2) }}</span>
                            @else
                            <span class="text-green-500 font-bold">Fully Paid</span>
                            @endif
                        </div>
                        <div class="text-[10px] text-gray-400 mt-2 flex items-center justify-start gap-1">
                            <span class="material-symbols-rounded text-[12px]">{{ $v['pay_icon'] }}</span> {{ $v['pay_label'] }}
                        </div>
                        @if($b->booked_by)
                        <div class="text-[10px] font-bold text-[#9E6B73]/60 mt-2 flex items-center gap-1">
                            <span class="material-symbols-rounded text-xs">person_add</span>
                            By: {{ $b->booked_by }}
                        </div>
                        @endif
                    </div>
                </a>
                @empty
                <div class="p-3 text-center text-gray-300 italic bg-white border border-gray-100 rounded-b-xl text-sm">No bookings.</div>
                @endforelse
            </div>
            @endforeach
        </div>
    </div>
</div>