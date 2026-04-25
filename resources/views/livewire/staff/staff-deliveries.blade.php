<div class="max-w-[1440px] mx-auto w-full pb-12 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 mb-8 mt-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-white drop-shadow-sm">My Assignments</h1>
            <p class="text-white/90 font-medium mt-1">Active routes and upcoming assignments.</p>
        </div>
    </div>

    <!-- Search Section -->
    <div class="bg-white/95 backdrop-blur-md rounded-2xl p-5 sm:p-6 mb-10 border border-white/50 shadow-xl shadow-black/10">
        <div class="flex items-center gap-3 mb-5 border-b border-gray-100 pb-3">
            <div class="w-8 h-8 rounded-lg bg-[#FDF2F4] flex items-center justify-center text-[#9E6B73]">
                <span class="material-symbols-rounded text-xl">local_shipping</span>
            </div>
            <h2 class="text-lg font-bold text-gray-800">Find Delivery</h2>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full">
            <div class="relative flex-grow">
                <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl">search</span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search ID, Customer, or Suburb..."
                    class="pl-12 pr-4 py-4 rounded-xl text-sm border-2 border-gray-50 focus:border-[#9E6B73] focus:ring-4 focus:ring-[#9E6B73]/5 w-full shadow-inner bg-gray-50/50 text-gray-800 transition-all font-medium">
            </div>
            @if(!empty($search))
                <button wire:click="$set('search', '')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-4 rounded-xl text-sm font-bold transition-all shadow-sm">Clear</button>
            @endif
        </div>
    </div>

    @if($pendingDeliveries->isEmpty() && $confirmedDeliveries->isEmpty())
        <div class="bg-white rounded-[2rem] p-16 text-center shadow-sm border border-white/50">
            <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300">
                <span class="material-symbols-rounded text-5xl">inventory_2</span>
            </div>
            <h3 class="text-2xl font-black text-gray-800">No Data Found</h3>
            <p class="text-gray-500 mt-2">No active bookings match your criteria.</p>
        </div>
    @else
        <!-- Pending Actions -->
        <div class="mb-12">
            <h2 class="text-xl font-black text-white mb-6 flex items-center gap-3">
                <span class="w-2.5 h-8 bg-orange-400 rounded-full shadow-sm"></span>
                Me as Delivery / Lead Operator
                <span class="bg-orange-100 text-orange-700 text-xs px-3 py-1 font-bold rounded-full border border-orange-200/50">{{ $pendingDeliveries->total() }}</span>
            </h2>

            @if($pendingDeliveries->isEmpty())
                <div class="bg-white/50 backdrop-blur-sm rounded-2xl p-8 text-center text-gray-500 font-medium italic text-sm border border-white/40 shadow-inner">
                    No pending actions on this page.
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($pendingDeliveries as $item)
                        @include('livewire.staff.partials.delivery-card', ['item' => $item, 'fullName' => $fullName])
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $pendingDeliveries->links(data: ['pageName' => 'pend_page']) }}
                </div>
            @endif
        </div>

        <!-- Scheduled & Confirmed -->
        <div class="mb-12">
            <h2 class="text-xl font-black text-white mb-6 flex items-center gap-3">
                <span class="w-2.5 h-8 bg-green-400 rounded-full shadow-sm"></span>
                Scheduled & Confirmed
                <span class="bg-green-100 text-green-700 text-xs px-3 py-1 font-bold rounded-full border border-green-200/50">{{ $confirmedDeliveries->total() }}</span>
            </h2>

            @if($confirmedDeliveries->isEmpty())
                <div class="bg-white/50 backdrop-blur-sm rounded-2xl p-8 text-center text-gray-500 font-medium italic text-sm border border-white/40 shadow-inner">
                    No confirmed schedules on this page.
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($confirmedDeliveries as $item)
                        @include('livewire.staff.partials.delivery-card', ['item' => $item, 'fullName' => $fullName])
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $confirmedDeliveries->links(data: ['pageName' => 'conf_page']) }}
                </div>
            @endif
        </div>
    @endif
</div>
