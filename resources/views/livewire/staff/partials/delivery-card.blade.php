@php
    $status = $item->status ?? 'Pending';
    $statusClass = 'bg-orange-50 text-orange-600 border-orange-100';
    if ($status === 'Confirmed') $statusClass = 'bg-green-50 text-green-700 border-green-100';
    if ($status === 'Completed') $statusClass = 'bg-blue-50 text-blue-700 border-blue-100';

    $isMyDelivery = ($item->lead_deliverer === $fullName);
@endphp

<div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-50 hover:shadow-xl hover:border-gray-200 transition-all duration-300 flex flex-col md:flex-row items-stretch gap-5 group relative overflow-hidden">
    <!-- Date Badge -->
    <div class="flex-shrink-0 w-20 bg-gray-50 rounded-xl py-3 text-center border border-gray-100 flex flex-col justify-center group-hover:bg-pink-50 group-hover:border-pink-100 transition-colors">
        <span class="block text-[10px] font-black text-[#9E6B73] uppercase tracking-widest leading-none">{{ \Carbon\Carbon::parse($item->event_date)->format('M') }}</span>
        <span class="block text-2xl font-black text-gray-800 my-1">{{ \Carbon\Carbon::parse($item->event_date)->format('d') }}</span>
        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ \Carbon\Carbon::parse($item->event_date)->format('D') }}</span>
    </div>

    <!-- Main Content -->
    <div class="flex-1 w-full min-w-0 flex flex-col justify-center">
        <div class="flex flex-wrap items-center gap-3 mb-4 pb-3 border-b border-gray-50">
            <span class="text-gray-400 font-black text-xs tracking-widest">#{{ $item->id }}</span>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase border {{ $statusClass }}">{{ $status }}</span>
            @if ($isMyDelivery)
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase border bg-blue-50 text-blue-700 border-blue-100 flex items-center gap-1">
                    <span class="material-symbols-rounded text-xs">verified</span>
                    My Delivery
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-4 gap-x-6">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                    <span class="material-symbols-rounded text-indigo-400 text-lg">person</span>
                </div>
                <div class="overflow-hidden">
                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest leading-none mb-1">Customer</p>
                    <p class="text-xs font-bold text-gray-800 truncate">{{ $item->customer_first_name }} {{ $item->customer_last_name }}</p>
                    <p class="text-[10px] text-gray-500 font-medium truncate mt-0.5">{{ $item->customer_phone ?? 'No Phone' }}</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center shrink-0">
                    <span class="material-symbols-rounded text-teal-400 text-lg">location_on</span>
                </div>
                <div class="overflow-hidden">
                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest leading-none mb-1">Destination</p>
                    <p class="text-xs font-bold text-gray-700 line-clamp-2 leading-relaxed" title="{{ $item->address_line_1 }}">{{ $item->address_line_1 }}, {{ $item->suburb }}</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <span class="material-symbols-rounded text-amber-400 text-lg">local_shipping</span>
                </div>
                <div class="overflow-hidden">
                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest leading-none mb-1">Assignments</p>
                    <div class="space-y-1 mt-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-bold text-gray-400 w-8">DRIVER:</span>
                            <span class="text-[10px] font-black {{ $isMyDelivery ? 'text-blue-600' : 'text-gray-700' }}">{{ $item->lead_deliverer ?? '--' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-bold text-gray-400 w-8">OP:</span>
                            <span class="text-[10px] font-black text-gray-700">{{ $item->lead_operator ?? '--' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-row md:flex-col gap-2 shrink-0 pt-4 md:pt-0 border-t md:border-t-0 border-gray-50 justify-center">
        <a href="{{ route('staff.bookings.overview', $item->id) }}"
            class="flex-1 md:flex-none px-5 py-2.5 bg-gray-900 text-white text-[11px] font-black rounded-xl hover:bg-[#9E6B73] transition-all flex items-center justify-center gap-2 shadow-sm hover:shadow-lg">
            <span class="material-symbols-rounded text-sm">visibility</span> VIEW
        </a>

        <a href="http://maps.google.com/?q={{ urlencode($item->address_line_1 . ', ' . $item->suburb) }}" target="_blank"
            class="flex-1 md:flex-none px-5 py-2.5 bg-white border border-gray-200 text-gray-600 text-[11px] font-black rounded-xl hover:bg-gray-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <span class="material-symbols-rounded text-sm text-blue-500">map</span> MAP
        </a>
    </div>
</div>
