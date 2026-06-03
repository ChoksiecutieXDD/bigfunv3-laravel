<div class="max-w-200 mx-auto py-10 px-5 w-full">

    <a href="/supervisor/staff" wire:navigate class="inline-flex items-center gap-2 text-slate-500 font-semibold mb-5 transition-colors duration-200 no-underline bg-slate-100 px-4 py-2 rounded-xl hover:text-slate-800 hover:bg-slate-200 w-max">
        <span class="material-symbols-rounded text-lg">arrow_back</span> Back to List
    </a>

    <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05),0_2px_4px_-1px_rgba(0,0,0,0.03)]">

        <div class="bg-linear-to-r from-slate-50 to-white p-10 max-sm:px-5 max-sm:py-7.5 text-center border-b border-slate-200">
            <div class="w-30 h-30 rounded-full bg-plum-light text-plum text-[40px] font-extrabold flex items-center justify-center mx-auto mb-5 border-4 border-white shadow-[0_10px_25px_rgba(158,107,115,0.15)]">
                {{ $this->getInitials() }}
            </div>
            <h1 class="text-[28px] font-extrabold text-[#1a202c] mb-2">
                {{ $user->first_name }} {{ $user->last_name }}
            </h1>
            <span class="inline-block px-4 py-1.5 rounded-full text-[13px] font-bold uppercase tracking-[0.5px] bg-plum-light text-plum">
                {{ $user->role }}
            </span>
        </div>

        <div class="px-10 py-7.5 max-sm:px-5 max-sm:py-7.5">
            <div class="grid grid-cols-2 max-sm:grid-cols-1 gap-7.5 max-sm:gap-5">

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-1.5 tracking-[0.5px]">Email Address</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-2">
                        <span class="material-symbols-rounded text-slate-400 text-lg">mail</span>
                        {{ $user->email }}
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-1.5 tracking-[0.5px]">Contact Number</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-2">
                        <span class="material-symbols-rounded text-slate-400 text-lg">call</span>
                        {{ $user->contact_no ?: 'N/A' }}
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-1.5 tracking-[0.5px]">Account Status</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-2">
                        @if ($user->is_active)
                        <span class="material-symbols-rounded text-green-600 text-lg">check_circle</span>
                        <span class="text-green-600">Active</span>
                        @else
                        <span class="material-symbols-rounded text-red-600 text-lg">block</span>
                        <span class="text-red-600">Inactive</span>
                        @endif
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-1.5 tracking-[0.5px]">Date Joined</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-2">
                        <span class="material-symbols-rounded text-slate-400 text-lg">calendar_month</span>
                        {{ $user->created_at ? $user->created_at->format('F j, Y') : 'Unknown' }}
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-1.5 tracking-[0.5px]">User ID</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-2">
                        <span class="material-symbols-rounded text-slate-400 text-lg">badge</span>
                        #{{ $user->user_id }}
                    </span>
                </div>

            </div>
        </div>

    </div>
</div>