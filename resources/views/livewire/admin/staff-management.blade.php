<div x-data="{ addModal: false, editModal: false }"
    @open-modal.window="if($event.detail.modal === 'addModal') addModal = true; if($event.detail.modal === 'editModal') editModal = true;"
    @close-modal.window="addModal = false; editModal = false;"
    @keydown.escape.window="addModal = false; editModal = false;"
    class="w-full max-w-[1440px] mx-auto">

    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 lg:gap-18">
        <div>
            <h1 class="text-3xl font-extrabold text-white drop-shadow-sm">Staff Management</h1>
            <p class="text-white/90 font-medium mt-1">Manage administrators, supervisors, and general staff.</p>
        </div>

        <button type="button" @click="addModal = true"
            class="hidden lg:inline-flex shrink-0 bg-[#9E6B73] hover:bg-[#86545C] text-white border-none rounded-[14px] px-[18px] py-[12px] font-bold items-center gap-[10px] shadow-[0_10px_20px_rgba(0,0,0,.12)] cursor-pointer transition-all duration-150 ease-in-out active:scale-95">
            <span class="material-symbols-rounded">person_add</span> Add New Staff
        </button>
    </div>

    @if (session()->has('message'))
    <div class="mt-4 rounded-[14px] px-[14px] py-[12px] font-extrabold bg-white shadow-[0_12px_26px_rgba(0,0,0,.08)] flex items-center gap-[10px] border {{ session('message_type') === 'success' ? 'border-green-200' : 'border-red-200' }}">
        <span class="w-2.5 h-2.5 rounded-full {{ session('message_type') === 'success' ? 'bg-green-500' : 'bg-red-500' }}"></span>
        <div>{{ session('message') }}</div>
    </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-[26px] mt-[26px]">
        <div class="bg-white rounded-[18px] px-[22px] py-[18px] shadow-[0_12px_26px_rgba(0,0,0,.10)] flex flex-col items-center justify-center min-h-[92px]">
            <div class="text-[34px] font-black m-0 leading-none">{{ $stats['Total'] }}</div>
            <div class="mt-2 text-[12px] tracking-[1.5px] uppercase font-extrabold text-[#8a8f98]">TOTAL USERS</div>
        </div>
        <div class="bg-white rounded-[18px] px-[22px] py-[18px] shadow-[0_12px_26px_rgba(0,0,0,.10)] flex flex-col items-center justify-center min-h-[92px]">
            <div class="text-[34px] font-black m-0 leading-none text-[#6b5bd3]">{{ $stats['Administrator'] }}</div>
            <div class="mt-2 text-[12px] tracking-[1.5px] uppercase font-extrabold text-[#8a8f98]">ADMINS</div>
        </div>
        <div class="bg-white rounded-[18px] px-[22px] py-[18px] shadow-[0_12px_26px_rgba(0,0,0,.10)] flex flex-col items-center justify-center min-h-[92px]">
            <div class="text-[34px] font-black m-0 leading-none text-[#a45b5b]">{{ $stats['Supervisor'] }}</div>
            <div class="mt-2 text-[12px] tracking-[1.5px] uppercase font-extrabold text-[#8a8f98]">SUPERVISORS</div>
        </div>
        <div class="bg-white rounded-[18px] px-[22px] py-[18px] shadow-[0_12px_26px_rgba(0,0,0,.10)] flex flex-col items-center justify-center min-h-[92px]">
            <div class="text-[34px] font-black m-0 leading-none text-[#2d7dd2]">{{ $stats['Staff'] }}</div>
            <div class="mt-2 text-[12px] tracking-[1.5px] uppercase font-extrabold text-[#8a8f98]">STAFF</div>
        </div>
    </div>

    <div class="mt-[26px] grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-[26px] items-start">
        @foreach ($users as $u)
        @php
        $displayRole = in_array($u->role, ['Deliverer', 'Operator']) ? 'Staff' : $u->role;
        @endphp
        <div class="w-full bg-white rounded-[20px] shadow-[0_16px_34px_rgba(0,0,0,.12)] px-[18px] pt-[18px] pb-[16px] box-border">
            <div class="w-[70px] h-[70px] rounded-full bg-[#f5f5f5] border border-[#eee] flex items-center justify-center font-black text-[#9aa0a6] mx-auto mt-[6px] mb-[10px]">
                {{ $this->getInitials($u->first_name, $u->last_name) }}
            </div>
            <p class="text-center text-[20px] font-black m-0">{{ $u->first_name }} {{ $u->last_name }}</p>
            <div class="flex justify-center mt-2.5">
                <span class="inline-flex items-center justify-center border px-[12px] py-[5px] rounded-full text-[12px] font-black tracking-[.8px] uppercase whitespace-nowrap {{ $this->getRoleBadgeClass($displayRole) }}" style="border-color:rgba(0,0,0,.04);">
                    {{ $displayRole }}
                </span>
            </div>
            <div class="h-[1px] bg-[#f0f0f0] my-[14px]"></div>
            <div class="flex gap-[10px] items-center text-[#59606a] font-semibold text-[14px]">
                <span class="material-symbols-rounded text-[18px] text-[#9aa0a6]">mail</span>
                <div class="max-w-[240px] truncate" title="{{ $u->email }}">{{ $u->email }}</div>
            </div>
            <div class="flex gap-[10px] items-center text-[#59606a] font-semibold text-[14px] mt-1.5">
                <span class="material-symbols-rounded text-[18px] text-[#9aa0a6]">call</span>
                <div>{{ $u->contact_no ?? 'N/A' }}</div>
            </div>
            <div class="flex gap-[10px] items-center text-[#8a8f98] font-semibold text-[12px] mt-2.5">
                <span class="material-symbols-rounded text-[16px]">
                    {{ $u->is_active ? 'toggle_on' : 'toggle_off' }}
                </span>
                <div>{{ $u->is_active ? 'Active' : 'Inactive' }}</div>
            </div>
            <div class="mt-[14px] flex gap-[10px] items-center">
                <button type="button" wire:click="loadEditStaff({{ $u->user_id }})"
                    class="flex-1 rounded-[10px] px-[12px] py-[10px] font-extrabold border border-[#e9e9e9] bg-white cursor-pointer transition-all duration-150 hover:bg-[#fafafa] active:scale-95">
                    Edit
                </button>

                <a href="{{ route('admin.staff.profile', $u->user_id) }}" wire:navigate
                    class="flex-1 rounded-[10px] px-[12px] py-[10px] font-black border border-transparent bg-[#9E6B73] text-white cursor-pointer text-center hover:bg-[#86545C]">
                    Profile
                </a>

                <button wire:click="deleteStaff({{ $u->user_id }})" wire:confirm="Delete this staff permanently? This cannot be undone." type="button" title="Delete"
                    class="w-[44px] min-w-[44px] rounded-[10px] py-[10px] border border-[#ffe2e2] bg-[#fff5f5] cursor-pointer text-[#ef4444] flex justify-center items-center">
                    <span class="material-symbols-rounded text-[#ef4444]">delete</span>
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <button @click="addModal = true" class="lg:hidden fixed bottom-6 right-6 w-14 h-14 bg-[#9E6B73] text-white rounded-full shadow-2xl flex items-center justify-center hover:bg-[#86545C] transition transform active:scale-90 border-4 border-white z-30">
        <span class="material-symbols-rounded text-2xl">person_add</span>
    </button>

    <template x-teleport="body">
        <!-- ADD STAFF MODAL -->
        <div x-show="addModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="addModal" 
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addModal = false"></div>

            <div x-show="addModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative w-full max-w-lg bg-white rounded-[24px] shadow-2xl flex flex-col max-h-[90vh] overflow-hidden z-10 border border-slate-100 font-sans">
                
                <!-- Header -->
                <div class="px-8 py-5 border-b border-slate-50 flex justify-between items-center bg-white shrink-0">
                    <div class="flex items-center gap-3 text-[#9D686E]">
                        <span class="material-symbols-rounded text-2xl">person_add</span>
                        <h3 class="font-black text-lg text-slate-800 tracking-tight uppercase">Add New Staff</h3>
                    </div>
                    <button type="button" @click="addModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-8 flex-1 overflow-y-auto custom-scrollbar">
                    <form wire:submit="addStaff" class="space-y-6" id="addStaffForm">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">First Name</label>
                                <input wire:model="first_name" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all">
                            </div>
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Last Name</label>
                                <input wire:model="last_name" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all">
                            </div>
                            <div class="col-span-full">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Email Address</label>
                                <input type="email" wire:model="email" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all">
                                @error('email') <span class="text-rose-500 text-[10px] font-bold mt-2 ml-1 block uppercase tracking-wide">{{ $message }}</span> @enderror
                            </div>
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Assign Role</label>
                                <div class="relative">
                                    <select wire:model="role" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all appearance-none cursor-pointer">
                                        <option value="Administrator">Administrator</option>
                                        <option value="Supervisor">Supervisor</option>
                                        <option value="Staff">Staff Member</option>
                                    </select>
                                    <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Contact Number</label>
                                <input wire:model="contact_no" placeholder="09xxxxxxxxx" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all">
                            </div>
                        </div>
                        
                        <div class="bg-amber-50 rounded-2xl p-5 border border-amber-100 flex items-start gap-4 shadow-sm">
                            <span class="material-symbols-rounded text-amber-500 text-xl shrink-0 mt-0.5">lock_reset</span>
                            <div>
                                <p class="text-[11px] font-black text-amber-800 uppercase tracking-widest mb-1">Security Protocol</p>
                                <p class="text-[12px] font-bold text-amber-700/80 leading-relaxed tracking-tight">The initial system access key is preset to <span class="text-slate-900 bg-white/80 px-2 py-0.5 rounded-lg border border-amber-200">BigFun2025</span>. Staff members should update this upon initial verification.</p>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-8 py-6 border-t border-slate-50 bg-white shrink-0 flex gap-4">
                    <button type="button" @click="addModal = false" class="flex-1 py-4.5 text-slate-500 font-black text-[11px] hover:bg-slate-50 rounded-[18px] transition-all uppercase tracking-[0.2em] border border-slate-100">Decline</button>
                    <button type="submit" form="addStaffForm" class="flex-[1.5] py-4.5 bg-slate-900 text-white rounded-[18px] font-black hover:bg-slate-800 transition shadow-xl shadow-slate-900/20 active:scale-[0.98] uppercase tracking-[0.2em] text-[11px]">
                        <span wire:loading.remove wire:target="addStaff">Authorise Account</span>
                        <span wire:loading wire:target="addStaff" class="flex items-center justify-center gap-2">
                            <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <!-- EDIT STAFF MODAL -->
        <div x-show="editModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
            <div x-show="editModal" 
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="editModal = false"></div>

            <div x-show="editModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative w-full max-w-lg bg-white rounded-[24px] shadow-2xl flex flex-col max-h-[90vh] overflow-hidden z-10 border border-slate-100 font-sans">
                
                <!-- Header -->
                <div class="px-8 py-5 border-b border-slate-50 flex justify-between items-center bg-white shrink-0">
                    <div class="flex items-center gap-3 text-[#9D686E]">
                        <span class="material-symbols-rounded text-2xl">manage_accounts</span>
                        <h3 class="font-black text-lg text-slate-800 tracking-tight uppercase">Update Personnel</h3>
                    </div>
                    <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-8 flex-1 overflow-y-auto custom-scrollbar">
                    <form wire:submit="updateStaff" class="space-y-6" id="updateStaffForm">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">First Name</label>
                                <input wire:model="edit_first_name" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all">
                            </div>
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Last Name</label>
                                <input wire:model="edit_last_name" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all">
                            </div>
                            <div class="col-span-full">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Email Identity</label>
                                <input type="email" wire:model="edit_email" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all">
                                @error('edit_email') <span class="text-rose-500 text-[10px] font-bold mt-2 ml-1 block uppercase tracking-wide">{{ $message }}</span> @enderror
                            </div>
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Designated Role</label>
                                <div class="relative">
                                    <select wire:model="edit_role" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all appearance-none cursor-pointer">
                                        <option value="Administrator">Administrator</option>
                                        <option value="Supervisor">Supervisor</option>
                                        <option value="Staff">Staff Member</option>
                                    </select>
                                    <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><span class="material-symbols-rounded">expand_more</span></span>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">Contact Identity</label>
                                <input wire:model="edit_contact_no" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-[#9D686E]/20 outline-none text-[13px] font-bold text-slate-700 transition-all">
                            </div>
                        </div>

                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 mt-2 flex items-center justify-between shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-rounded text-[#9D686E]">shield_person</span>
                                <span class="text-[13px] font-bold text-slate-700 tracking-tight">Access Authority Status</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="edit_is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-[#9D686E]/10 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                <span class="ml-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-16" x-text="$wire.edit_is_active ? 'Enabled' : 'Locked'"></span>
                            </label>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-8 py-6 border-t border-slate-50 bg-white shrink-0 flex gap-4">
                    <button type="button" @click="editModal = false" class="flex-1 py-4.5 text-slate-500 font-black text-[11px] hover:bg-slate-50 rounded-[18px] transition-all uppercase tracking-[0.2em] border border-slate-100">Cancel</button>
                    <button type="submit" form="updateStaffForm" class="flex-[1.5] py-4.5 bg-slate-900 text-white rounded-[18px] font-black hover:bg-slate-800 transition shadow-xl shadow-slate-900/20 active:scale-[0.98] uppercase tracking-[0.2em] text-[11px]">
                        <span wire:loading.remove wire:target="updateStaff">Confirm Registry</span>
                        <span wire:loading wire:target="updateStaff" class="flex items-center justify-center gap-2">
                            <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            Syncing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </template>
    </div>

</div>
