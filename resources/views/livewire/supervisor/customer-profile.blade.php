<div class="max-w-[1440px] mx-auto space-y-6">

    <!-- Navigation Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
        <div class="flex items-center gap-4">
            <a href="{{ route('supervisor.bookings.overview', ['id' => $booking->id, 'back' => $backUrl]) }}" wire:navigate class="bg-white hover:bg-gray-50 text-slate-600 p-2.5 rounded-xl border border-gray-200 transition shadow-sm flex items-center justify-center">
                <span class="material-symbols-rounded text-2xl">arrow_back</span>
            </a>
            <div>
                <h1 class="text-3xl font-extrabold text-[#1E293B]">Customer Profile</h1>
                <p class="text-slate-500 font-medium mt-1 uppercase tracking-wide text-[10px]">Reference Source: <span class="font-bold underline">Booking #{{ $booking->id }}</span></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="mailto:{{ $booking->customer_email }}" class="px-6 py-2.5 bg-[#9D686E] text-white rounded-xl font-bold shadow-lg shadow-[#9D686E]/20 hover:bg-[#855359] transition flex items-center gap-2 text-xs uppercase tracking-widest">
                <span class="material-symbols-rounded text-lg">mail</span> Contact Customer
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Sidebar: Profile & Stats -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Customer Identity Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-1 bg-[#9D686E]"></div>
                
                <div class="w-24 h-24 bg-slate-50 rounded-[2.5rem] mx-auto flex items-center justify-center text-4xl text-[#9D686E] mb-5 border-2 border-[#9D686E]/10 group-hover:rotate-6 transition-transform">
                    <span class="material-symbols-rounded text-5xl">person</span>
                </div>
                
                <h2 class="text-2xl font-black text-slate-800 leading-tight">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</h2>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">{{ $booking->customer_organization ?: 'Private Individual' }}</p>

                <div class="mt-8 space-y-4 text-left">
                    <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100/50">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-[#9D686E] shadow-sm"><span class="material-symbols-rounded text-xl">call</span></div>
                        <div>
                            <p class="text-[9px] uppercase font-black text-slate-400 tracking-widest">Primary Contact</p>
                            <p class="text-sm font-black text-slate-800">{{ $booking->customer_phone }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100/50">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-[#9D686E] shadow-sm"><span class="material-symbols-rounded text-xl">location_on</span></div>
                        <div class="min-w-0">
                            <p class="text-[9px] uppercase font-black text-slate-400 tracking-widest">Latest Deployment</p>
                            <p class="text-xs font-bold text-slate-800 leading-tight truncate">{{ $booking->address_line_1 }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase">{{ $booking->suburb }}, {{ $booking->state }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm group hover:border-[#9D686E]/30 transition-colors">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Total Bookings</span>
                    <p class="text-3xl font-black text-[#9D686E] tracking-tighter">{{ $totalBookings }}</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm group hover:border-green-200 transition-colors">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Lifetime Value</span>
                    <p class="text-3xl font-black text-green-600 tracking-tighter">${{ number_format($totalSpent, 0) }}</p>
                </div>
            </div>
        </div>

        <!-- Main: Booking History -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-8 py-5 border-b border-gray-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-black text-slate-800 flex items-center gap-3 uppercase text-sm tracking-widest">
                        <span class="material-symbols-rounded text-[#9D686E]">history</span> Booking History
                    </h3>
                    <span class="px-3 py-1 bg-white rounded-lg border border-slate-200 text-[10px] font-black text-slate-500 uppercase tracking-widest">Record for: {{ $booking->customer_email }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-white text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-gray-100">
                            <tr>
                                <th class="px-8 py-5">REF #</th>
                                <th class="px-8 py-5">Event Date</th>
                                <th class="px-8 py-5">Type / Area</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-right">Amount</th>
                                <th class="px-8 py-5"></th>
                            </tr>
                        </thead>
                        <tbody class="text-xs divide-y divide-gray-50 bg-white">
                            @forelse ($history as $row)
                            @php
                            $stColor = match ($row->status) {
                                'Completed' => 'bg-green-50 text-green-600 border-green-100',
                                'Confirmed' => 'bg-blue-50 text-blue-600 border-blue-100',
                                'Cancelled' => 'bg-red-50 text-red-600 border-red-100',
                                'Hold'      => 'bg-amber-50 text-amber-600 border-amber-100',
                                'Draft'     => 'bg-orange-50 text-orange-600 border-orange-100',
                                default     => 'bg-slate-50 text-slate-500 border-slate-100',
                            };
                            @endphp
                            <tr class="hover:bg-slate-50/30 transition group">
                                <td class="px-8 py-5 font-black text-slate-800">#{{ $row->id }}</td>
                                <td class="px-8 py-5 font-bold text-slate-600">{{ \Carbon\Carbon::parse($row->event_date)->format('d M Y') }}</td>
                                <td class="px-8 py-5">
                                    <span class="font-black text-slate-800 block text-[11px]">{{ $row->event_type }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $row->delivery_area ?: 'Standard' }}</span>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span class="px-3 py-1.5 rounded-lg text-[9px] font-black tracking-widest uppercase border {{ $stColor }}">
                                        {{ $row->status }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right font-black text-slate-800 tracking-tighter text-sm">${{ number_format($row->total_amount, 2) }}</td>
                                <td class="px-8 py-5 text-right">
                                    <a href="{{ route('supervisor.bookings.overview', $row->id) }}" wire:navigate class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 hover:bg-[#9D686E] hover:text-white transition-all shadow-sm group-hover:scale-105">
                                        <span class="material-symbols-rounded text-lg">open_in_new</span>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-20 text-center">
                                    <span class="material-symbols-rounded text-slate-200 text-6xl block mb-4">search_off</span>
                                    <span class="text-[11px] text-slate-300 font-bold uppercase tracking-[0.2em]">No booking history recorded for this client</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
