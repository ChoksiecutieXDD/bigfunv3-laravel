<div x-data="{ addModal: false, editModal: false }"
    @open-modal.window="if($event.detail.modal === 'addModal') addModal = true; if($event.detail.modal === 'editModal') editModal = true;"
    @close-modal.window="addModal = false; editModal = false;"
    @keydown.escape.window="addModal = false; editModal = false;"
    class="w-full max-w-[1440px] mx-auto">

    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 lg:gap-18">
        <div>
            <div class="text-[38px] leading-[1.1] font-extrabold text-white tracking-[.2px]">Staff Management</div>
            <div class="text-white/85 mt-1.5 font-medium">Manage administrators, supervisors, and general staff.</div>
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

                <a href="{{ route('supervisor.staff.profile', $u->user_id) }}"
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

    <div x-show="addModal" class="fixed inset-0 bg-black/35 flex items-center justify-center z-[9999] p-[18px]" x-cloak>
        <div class="w-[560px] max-w-full bg-white rounded-[18px] shadow-[0_22px_60px_rgba(0,0,0,.22)] overflow-hidden" @click.away="addModal = false">
            <div class="px-[18px] py-[16px] flex items-center justify-between border-b border-[#f0f0f0]">
                <h3 class="text-[18px] font-black m-0">Add New Staff</h3>
                <button type="button" @click="addModal = false" class="border-none bg-[#f6f6f6] w-[40px] h-[40px] rounded-[12px] cursor-pointer flex items-center justify-center">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="px-[18px] pt-[16px] pb-[18px]">
                <form wire:submit="addStaff">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-[12px]">
                        <div>
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">First Name</label>
                            <input wire:model="first_name" required class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                        </div>
                        <div>
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">Last Name</label>
                            <input wire:model="last_name" required class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                        </div>
                        <div class="col-span-1 sm:col-span-2">
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">Email</label>
                            <input type="email" wire:model="email" required class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                            @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">Role</label>
                            <select wire:model="role" required class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                                <option value="Administrator">Administrator</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">Contact No</label>
                            <input wire:model="contact_no" placeholder="09xxxxxxxxx" class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                        </div>
                    </div>
                    <div class="mt-[10px] text-[12px] text-[#6b7280] font-bold">
                        Default password will be: <b class="text-gray-900">BigFun2025</b>
                    </div>
                    <div class="flex gap-[10px] justify-end mt-[14px]">
                        <button type="button" @click="addModal = false" class="border border-[#e7e7e7] bg-white rounded-[12px] px-[14px] py-[10px] font-black cursor-pointer hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="border-none bg-[#9E6B73] text-white rounded-[12px] px-[14px] py-[10px] font-black cursor-pointer hover:bg-[#86545C]">
                            <span wire:loading.remove wire:target="addStaff">Create Account</span>
                            <span wire:loading wire:target="addStaff">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="editModal" class="fixed inset-0 bg-black/35 flex items-center justify-center z-[9999] p-[18px]" x-cloak>
        <div class="w-[560px] max-w-full bg-white rounded-[18px] shadow-[0_22px_60px_rgba(0,0,0,.22)] overflow-hidden" @click.away="editModal = false">
            <div class="px-[18px] py-[16px] flex items-center justify-between border-b border-[#f0f0f0]">
                <h3 class="text-[18px] font-black m-0">Edit Staff</h3>
                <button type="button" @click="editModal = false" class="border-none bg-[#f6f6f6] w-[40px] h-[40px] rounded-[12px] cursor-pointer flex items-center justify-center">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="px-[18px] pt-[16px] pb-[18px]">
                <form wire:submit="updateStaff">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-[12px]">
                        <div>
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">First Name</label>
                            <input wire:model="edit_first_name" required class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                        </div>
                        <div>
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">Last Name</label>
                            <input wire:model="edit_last_name" required class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                        </div>
                        <div class="col-span-1 sm:col-span-2">
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">Email</label>
                            <input type="email" wire:model="edit_email" required class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                            @error('edit_email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">Role</label>
                            <select wire:model="edit_role" required class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                                <option value="Administrator">Administrator</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[12px] font-black text-[#6b7280] block mb-[6px]">Contact No</label>
                            <input wire:model="edit_contact_no" class="w-full px-[12px] py-[11px] rounded-[12px] border border-[#e7e7e7] outline-none font-semibold focus:border-[#d3b0b4] focus:ring-4 focus:ring-[#9E6B73]/10">
                        </div>
                    </div>
                    <div class="mt-[12px] flex gap-[10px] items-center">
                        <input type="checkbox" wire:model="edit_is_active" id="is_active_check" class="w-[18px] h-[18px] text-[#9E6B73] rounded border-gray-300 focus:ring-[#9E6B73]">
                        <label for="is_active_check" class="m-0 text-[13px] font-bold text-gray-700 cursor-pointer">Active</label>
                    </div>
                    <div class="flex gap-[10px] justify-end mt-[14px]">
                        <button type="button" @click="editModal = false" class="border border-[#e7e7e7] bg-white rounded-[12px] px-[14px] py-[10px] font-black cursor-pointer hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="border-none bg-[#9E6B73] text-white rounded-[12px] px-[14px] py-[10px] font-black cursor-pointer hover:bg-[#86545C]">
                            <span wire:loading.remove wire:target="updateStaff">Save Changes</span>
                            <span wire:loading wire:target="updateStaff">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>