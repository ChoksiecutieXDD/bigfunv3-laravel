<div class="max-w-[800px] mx-auto py-[40px] px-[20px] w-full">

    <a href="/admin/staff" wire:navigate class="inline-flex items-center gap-[8px] text-slate-500 font-semibold mb-[20px] transition-colors duration-200 no-underline bg-slate-100 px-[16px] py-[8px] rounded-[12px] hover:text-slate-800 hover:bg-slate-200 w-max">
        <span class="material-symbols-rounded text-lg">arrow_back</span> Back to List
    </a>

    <div class="bg-white border border-slate-200 rounded-[24px] overflow-hidden shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05),0_2px_4px_-1px_rgba(0,0,0,0.03)]">

        <div class="bg-gradient-to-r from-slate-50 to-white p-[40px] max-sm:px-[20px] max-sm:py-[30px] text-center border-b border-slate-200">
            <div class="w-[120px] h-[120px] rounded-full bg-[#FDF2F4] text-[#9E6B73] text-[40px] font-extrabold flex items-center justify-center mx-auto mb-[20px] border-[4px] border-white shadow-[0_10px_25px_rgba(158,107,115,0.15)]">
                {{ $this->getInitials() }}
            </div>
            <h1 class="text-[28px] font-extrabold text-[#1a202c] mb-[8px]">
                {{ $user->first_name }} {{ $user->last_name }}
            </h1>
            <span class="inline-block px-[16px] py-[6px] rounded-full text-[13px] font-bold uppercase tracking-[0.5px] bg-[#FDF2F4] text-[#9E6B73]">
                {{ $user->role }}
            </span>
        </div>

        <div class="px-[40px] py-[30px] max-sm:px-[20px] max-sm:py-[30px]">
            <div class="grid grid-cols-2 max-sm:grid-cols-1 gap-[30px] max-sm:gap-[20px]">

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-[6px] tracking-[0.5px]">Email Address</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-[8px]">
                        <span class="material-symbols-rounded text-slate-400 text-lg">mail</span>
                        {{ $user->email }}
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-[6px] tracking-[0.5px]">Contact Number</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-[8px]">
                        <span class="material-symbols-rounded text-slate-400 text-lg">call</span>
                        {{ $user->contact_no ?: 'N/A' }}
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-[6px] tracking-[0.5px]">Account Status</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-[8px]">
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
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-[6px] tracking-[0.5px]">Date Joined</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-[8px]">
                        <span class="material-symbols-rounded text-slate-400 text-lg">calendar_month</span>
                        {{ $user->created_at ? $user->created_at->format('F j, Y') : 'Unknown' }}
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-[12px] font-bold uppercase text-slate-400 mb-[6px] tracking-[0.5px]">User ID</span>
                    <span class="text-[16px] font-semibold text-slate-700 flex items-center gap-[8px]">
                        <span class="material-symbols-rounded text-slate-400 text-lg">badge</span>
                        #{{ $user->user_id }}
                    </span>
                </div>

            </div>
        </div>

    </div>
</div>
