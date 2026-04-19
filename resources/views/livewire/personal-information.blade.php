<div>
    @if (session()->has('profile_message'))
    <div class="mb-8 p-5 rounded-3xl border flex items-center gap-4 shadow-sm animate-in fade-in slide-in-from-top-4 duration-500 {{ session('profile_type') === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' }}">
        <div class="w-10 h-10 rounded-2xl {{ session('profile_type') === 'success' ? 'bg-green-100 border-green-200' : 'bg-red-100 border-red-200' }} flex items-center justify-center border shrink-0">
            <span class="material-symbols-rounded text-xl leading-none">{{ session('profile_type') === 'success' ? 'check_circle' : 'error' }}</span>
        </div>
        <span class="font-extrabold text-sm md:text-base tracking-tight">{{ session('profile_message') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">

            <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-32">
                <div class="bg-white border border-gray-100 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-gray-200/50 transition-all hover:shadow-gray-300/50">
                    <div class="h-32 bg-gradient-to-br from-[#9E6B73] via-[#86545C] to-[#6b3e45]"></div>
                    <div class="px-8 pb-10">
                        <div class="-mt-16 flex flex-col items-center gap-5">
                            <div class="w-32 h-32 rounded-[2rem] bg-white p-2 shadow-2xl shrink-0 z-10">
                                <div class="w-full h-full rounded-[1.5rem] bg-gray-900 text-white flex items-center justify-center text-5xl font-black shadow-inner">
                                    {{ $this->initials }}
                                </div>
                            </div>
                            <div class="w-full min-w-0 text-center">
                                <h2 class="text-2xl sm:text-3xl font-black text-gray-900 leading-tight break-words tracking-tight">
                                    {{ trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) }}
                                </h2>
                                <p class="text-gray-400 font-bold text-sm mt-1 uppercase tracking-widest opacity-80">{{ auth()->user()->role ?: 'Member' }}</p>
                                <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 shadow-inner">
                                    <span class="material-symbols-rounded text-gray-400 text-sm">mail</span>
                                    <span class="text-gray-600 text-xs font-bold truncate">{{ auth()->user()->email ?? 'No email' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-10 pt-8 border-t border-gray-50 grid grid-cols-2 gap-4">
                            <div class="bg-gray-50/50 rounded-3xl p-5 border border-gray-100 text-center">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Since</p>
                                <p class="text-base font-black text-gray-900 mt-1">
                                    {{ auth()->user()->created_at ? auth()->user()->created_at->format('M Y') : '—' }}
                                </p>
                            </div>
                            <div class="bg-gray-50/50 rounded-3xl p-5 border border-gray-100 text-center">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Updated</p>
                                <p class="text-base font-black text-gray-900 mt-1">
                                    {{ auth()->user()->change_passtime ? auth()->user()->change_passtime->format('M d') : 'Never' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden lg:block bg-[#9E6B73] rounded-[2.5rem] p-8 border border-[#9E6B73]/10 shadow-xl shadow-[#9E6B73]/20 relative overflow-hidden group transition-all duration-500 hover:scale-[1.02]">
                    <div class="absolute -right-8 -bottom-8 opacity-10 group-hover:scale-110 transition-transform duration-700">
                        <span class="material-symbols-rounded text-[10rem]">help_center</span>
                    </div>
                    <div class="relative z-10">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center text-white mb-6">
                            <span class="material-symbols-rounded">lightbulb</span>
                        </div>
                        <h3 class="text-white text-xl font-black mb-3">Security Tip</h3>
                        <p class="text-white/80 text-sm font-semibold leading-relaxed">
                            A strong password combined with regular updates ensures your account remains secure and your data protected.
                        </p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8 space-y-8">

                <div class="bg-white border border-gray-100 rounded-[2.5rem] shadow-xl shadow-gray-200/40 overflow-hidden group transition-all">
                    <div class="px-8 py-8 border-b border-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-[#FDF2F4] text-[#9E6B73] flex items-center justify-center border border-[#9E6B73]/10 transform transition-transform group-hover:rotate-12">
                                <span class="material-symbols-rounded text-3xl font-black">person</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-gray-900 truncate">General Information</h3>
                                <p class="text-sm font-bold text-gray-400 opacity-80">Update your basic profile details</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="updateProfile" class="p-8 md:p-10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div class="space-y-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">First Name</label>
                                <input type="text" wire:model="first_name" required class="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800 placeholder-gray-300">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Last Name</label>
                                <input type="text" wire:model="last_name" required class="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800 placeholder-gray-300">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Contact Number</label>
                                <input type="text" wire:model="contact_no" class="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800 placeholder-gray-300">
                            </div>
                            <div class="space-y-2 text-red-100">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Account Role</label>
                                <div class="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 text-[#9E6B73] font-black opacity-80 cursor-not-allowed select-none">
                                    {{ auth()->user()->role }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                            <div class="space-y-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Birthday</label>
                                <input type="date" wire:model="birthday" class="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800 text-sm h-[58px]">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Age</label>
                                <input type="number" wire:model="age" class="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Gender</label>
                                <div class="relative">
                                    <select wire:model="gender" class="w-full pl-6 pr-12 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800 appearance-none cursor-pointer h-[58px]">
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                        <option value="Prefer not to say">Prefer not to say</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-5 pointer-events-none text-[#9E6B73]">
                                        <span class="material-symbols-rounded">expand_more</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-10 space-y-2">
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Residential Address</label>
                            <textarea wire:model="address" rows="3" class="w-full px-6 py-5 rounded-[2rem] bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800 placeholder-gray-300 leading-normal resize-none"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="group relative inline-flex items-center gap-3 bg-[#9E6B73] hover:bg-[#86545C] text-white font-black py-5 px-10 rounded-2xl transition-all duration-300 shadow-2xl shadow-[#9E6B73]/40 hover:shadow-[#9E6B73]/60 hover:-translate-y-1 active:scale-95 overflow-hidden">
                                <span class="relative z-10 flex items-center gap-3">
                                    <span wire:loading.remove wire:target="updateProfile" class="material-symbols-rounded">save</span>
                                    <span wire:loading wire:target="updateProfile" class="animate-spin material-symbols-rounded">sync</span>
                                    Save Profile Settings
                                </span>
                                <div class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white border border-gray-100 rounded-[2.5rem] shadow-xl shadow-gray-200/40 overflow-hidden group">
                    <div class="px-8 py-8 border-b border-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-[#FDF2F4] text-[#9E6B73] flex items-center justify-center border border-[#9E6B73]/10 transform transition-transform group-hover:scale-110">
                                <span class="material-symbols-rounded text-3xl font-black">security</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-gray-900">Security Credentials</h3>
                                <p class="text-sm font-bold text-gray-400 opacity-80">Manage your access password</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="changePassword" class="p-8 md:p-10">
                        @if (session()->has('password_message'))
                        <div class="mb-8 p-5 rounded-[1.5rem] border bg-green-50 border-green-200 text-green-800 flex items-center gap-4 animate-in fade-in zoom-in duration-300">
                            <span class="material-symbols-rounded">check_circle</span>
                            <span class="font-extrabold">{{ session('password_message') }}</span>
                        </div>
                        @endif

                        <div class="space-y-8">
                            <div x-data="{ show: false }" class="space-y-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Current Password</label>
                                <div class="relative">
                                    <span class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-400 flex items-center z-10 transition-colors group-focus-within:text-[#9E6B73]">
                                        <span class="material-symbols-rounded text-xl">password</span>
                                    </span>
                                    <input :type="show ? 'text' : 'password'" wire:model="current_password" required class="w-full pl-16 pr-14 py-4.5 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800">
                                    <button type="button" @click="show = !show" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition-colors p-2 z-10">
                                        <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                    </button>
                                </div>
                                @error('current_password') <span class="text-red-500 text-xs mt-1 ml-1 font-bold">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div x-data="{ show: false }" class="space-y-2">
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">New Password</label>
                                    <div class="relative">
                                        <span class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                                            <span class="material-symbols-rounded text-xl">lock_reset</span>
                                        </span>
                                        <input :type="show ? 'text' : 'password'" wire:model="new_password" required class="w-full pl-16 pr-14 py-4.5 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800">
                                        <button type="button" @click="show = !show" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition-colors p-2 z-10">
                                            <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                        </button>
                                    </div>
                                    @error('new_password') <span class="text-red-500 text-xs mt-1 ml-1 font-bold">{{ $message }}</span> @enderror
                                </div>

                                <div x-data="{ show: false }" class="space-y-2">
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Confirm New Password</label>
                                    <div class="relative">
                                        <span class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                                            <span class="material-symbols-rounded text-xl">verified_user</span>
                                        </span>
                                        <input :type="show ? 'text' : 'password'" wire:model="confirm_password" required class="w-full pl-16 pr-14 py-4.5 rounded-2xl bg-gray-50 border border-gray-100 focus:bg-white focus:ring-4 focus:ring-[#9E6B73]/10 focus:border-[#9E6B73] outline-none transition-all font-bold text-gray-800">
                                        <button type="button" @click="show = !show" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition-colors p-2 z-10">
                                            <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                        </button>
                                    </div>
                                    @error('confirm_password') <span class="text-red-500 text-xs mt-1 ml-1 font-bold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-12 flex flex-col md:flex-row items-center justify-between gap-6 pt-10 border-t border-gray-50">
                            <div class="flex items-center gap-4 px-6 py-3 rounded-2xl bg-gray-50/50 border border-gray-100">
                                <div class="w-8 h-8 rounded-xl bg-orange-100 flex items-center justify-center text-orange-600">
                                    <span class="material-symbols-rounded text-sm">priority_high</span>
                                </div>
                                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">
                                    Min. 6 characters required
                                </p>
                            </div>
                            <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-3 bg-gray-900 hover:bg-black text-white font-black py-5 px-12 rounded-2xl transition-all shadow-2xl hover:shadow-gray-300 hover:-translate-y-1 active:scale-95">
                                <span wire:loading.remove wire:target="changePassword" class="material-symbols-rounded">shield_lock</span>
                                <span wire:loading wire:target="changePassword" class="animate-spin material-symbols-rounded">sync</span>
                                Update Credentials
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <div class="mt-20 flex flex-col items-center gap-6">
            <div class="h-px w-20 bg-gray-200"></div>
            <p class="text-[10px] font-black text-gray-300 uppercase tracking-[0.3em]">
                BigFun Entertainment • Account Security Hub
            </p>
        </div>
    </div>
</div>