<div>
    <nav class="bg-white/85 backdrop-blur-[12px] border-b border-white/60 sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 py-4 sm:py-0 sm:h-20">
                <div class="flex items-center gap-4">
                    <a href="{{ $this->backLink }}" wire:navigate class="group p-2 rounded-xl text-gray-500 hover:text-[#9E6B73] hover:bg-[#FDF2F4] transition-all duration-300" title="Go Back">
                        <span class="material-symbols-rounded group-hover:-translate-x-1 transition-transform">arrow_back</span>
                    </a>
                    <div class="h-8 w-px bg-gray-200"></div>
                    <div class="min-w-0">
                        <h1 class="text-xl font-extrabold text-gray-800 tracking-tight leading-tight">Account Settings</h1>
                        <p class="text-xs text-gray-500 font-medium leading-snug break-words">Manage your profile & security</p>
                    </div>
                </div>
                <div class="flex items-center sm:justify-end">
                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-[#FDF2F4] text-[#9E6B73] border border-[#9E6B73]/20 shadow-sm">
                        {{ auth()->user()->role ?: 'User' }}
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        @if (session()->has('profile_message'))
        <div class="mb-8 p-4 rounded-2xl border flex items-center gap-3 shadow-sm {{ session('profile_type') === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' }}">
            <span class="material-symbols-rounded">{{ session('profile_type') === 'success' ? 'check_circle' : 'error' }}</span>
            <span class="font-bold">{{ session('profile_message') }}</span>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-28">
                <div class="bg-white/85 backdrop-blur-[12px] border border-white/60 rounded-[2rem] overflow-hidden shadow-lg shadow-gray-200/50">
                    <div class="h-28 bg-gradient-to-br from-[#9E6B73] to-[#86545C]"></div>
                    <div class="px-8 pb-8">
                        <div class="-mt-14 flex flex-col items-center gap-4">
                            <div class="w-28 h-28 rounded-3xl bg-white p-1.5 shadow-xl shrink-0 z-10">
                                <div class="w-full h-full rounded-2xl bg-gray-900 text-white flex items-center justify-center text-4xl font-black">
                                    {{ $this->initials }}
                                </div>
                            </div>
                            <div class="w-full min-w-0 text-center pb-1">
                                <h2 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight break-words">
                                    {{ trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) }}
                                </h2>
                                <p class="text-gray-500 text-sm font-medium mt-1 truncate">{{ auth()->user()->email ?? 'No email' }}</p>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 rounded-2xl p-3 border border-gray-100 text-center lg:text-left">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Member Since</p>
                                <p class="text-sm font-bold text-gray-700 mt-0.5">
                                    {{ auth()->user()->created_at ? auth()->user()->created_at->format('M Y') : '—' }}
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-2xl p-3 border border-gray-100 text-center lg:text-left">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pass Changed</p>
                                <p class="text-sm font-bold text-gray-700 mt-0.5">
                                    {{ auth()->user()->change_passtime ? \Carbon\Carbon::parse(auth()->user()->change_passtime)->format('M d, Y') : 'Never' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden lg:block bg-white/85 backdrop-blur-[12px] border border-white/60 rounded-[2rem] p-6 border-l-4 border-l-[#9E6B73]">
                    <h3 class="text-[#9E6B73] font-extrabold mb-2 flex items-center gap-2">
                        <span class="material-symbols-rounded">lightbulb</span> Quick Tip
                    </h3>
                    <p class="text-xs font-medium text-gray-600 leading-relaxed">
                        Keep your contact information updated to ensure you receive important notifications about your deliveries and account status.
                    </p>
                </div>
            </div>

            <div class="lg:col-span-8 space-y-8">

                <div class="bg-white/85 backdrop-blur-[12px] border border-white/60 rounded-[2rem] shadow-sm overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#FDF2F4] text-[#9E6B73] flex items-center justify-center border border-[#9E6B73]/10">
                            <span class="material-symbols-rounded text-2xl">person</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-900">General Information</h3>
                            <p class="text-xs text-gray-500 font-medium">Update your basic details</p>
                        </div>
                    </div>

                    <form wire:submit="updateProfile" class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">First Name</label>
                                <input type="text" wire:model="first_name" required class="w-full px-5 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 placeholder-gray-400 leading-tight">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Last Name</label>
                                <input type="text" wire:model="last_name" required class="w-full px-5 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 placeholder-gray-400 leading-tight">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Contact Number</label>
                                <input type="text" wire:model="contact_no" class="w-full px-5 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 placeholder-gray-400 leading-tight">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Role</label>
                                <input type="text" value="{{ auth()->user()->role }}" readonly class="w-full px-5 py-3.5 rounded-2xl bg-gray-100 border border-gray-200 text-gray-400 font-bold cursor-not-allowed select-none leading-tight">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Birthday</label>
                                <input type="date" wire:model="birthday" class="w-full px-5 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 text-sm leading-tight h-[54px]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Age</label>
                                <input type="number" wire:model="age" class="w-full px-5 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 leading-tight">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Gender</label>
                                <div class="relative">
                                    <select wire:model="gender" class="w-full pl-5 pr-10 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 appearance-none cursor-pointer leading-tight h-[54px]">
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                        <option value="Prefer not to say">Prefer not to say</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-[#9E6B73]">
                                        <span class="material-symbols-rounded">expand_more</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Residential Address</label>
                            <textarea wire:model="address" rows="3" class="w-full px-5 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 placeholder-gray-400 leading-normal"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 bg-[#9E6B73] hover:bg-[#86545C] text-white font-bold py-3.5 px-8 rounded-2xl transition-all shadow-lg shadow-pink-200 hover:shadow-xl active:scale-95">
                                <span wire:loading.remove wire:target="updateProfile" class="material-symbols-rounded">save</span>
                                <span wire:loading wire:target="updateProfile" class="animate-spin material-symbols-rounded">refresh</span>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white/85 backdrop-blur-[12px] border border-white/60 rounded-[2rem] shadow-sm overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#FDF2F4] text-[#9E6B73] flex items-center justify-center border border-[#9E6B73]/10">
                            <span class="material-symbols-rounded text-2xl">lock</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-900">Security</h3>
                            <p class="text-xs text-gray-500 font-medium">Change your password</p>
                        </div>
                    </div>

                    <form wire:submit="changePassword" class="p-8">

                        @if (session()->has('password_message'))
                        <div class="mb-6 p-4 rounded-2xl border flex items-center gap-3 shadow-sm bg-green-50 border-green-200 text-green-800">
                            <span class="material-symbols-rounded">check_circle</span>
                            <span class="font-bold">{{ session('password_message') }}</span>
                        </div>
                        @endif

                        <div class="space-y-6">

                            <div x-data="{ show: false }">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Current Password</label>
                                <div class="relative group">
                                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 flex items-center group-focus-within:text-[#9E6B73] transition-colors">
                                        <span class="material-symbols-rounded text-xl">key</span>
                                    </span>
                                    <input :type="show ? 'text' : 'password'" wire:model="current_password" required class="w-full pl-14 pr-12 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 leading-tight">
                                    <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition p-1">
                                        <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                    </button>
                                </div>
                                @error('current_password') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div x-data="{ show: false }">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">New Password</label>
                                    <div class="relative group">
                                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 flex items-center group-focus-within:text-[#9E6B73] transition-colors">
                                            <span class="material-symbols-rounded text-xl">lock_reset</span>
                                        </span>
                                        <input :type="show ? 'text' : 'password'" wire:model="new_password" required class="w-full pl-14 pr-12 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 leading-tight">
                                        <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition p-1">
                                            <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                        </button>
                                    </div>
                                    @error('new_password') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                                </div>

                                <div x-data="{ show: false }">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Confirm New Password</label>
                                    <div class="relative group">
                                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 flex items-center group-focus-within:text-[#9E6B73] transition-colors">
                                            <span class="material-symbols-rounded text-xl">verified</span>
                                        </span>
                                        <input :type="show ? 'text' : 'password'" wire:model="confirm_password" required class="w-full pl-14 pr-12 py-3.5 rounded-2xl bg-white/50 border border-gray-200 focus:ring-2 focus:ring-[#9E6B73] focus:border-transparent outline-none transition-all font-semibold text-gray-800 leading-tight">
                                        <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition p-1">
                                            <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                        </button>
                                    </div>
                                    @error('confirm_password') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-4 border-t border-gray-100 pt-6">
                            <p class="text-xs text-gray-500 font-medium">
                                <span class="material-symbols-rounded text-sm align-middle mr-1">info</span>
                                Minimum 6 characters required
                            </p>
                            <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white font-bold py-3.5 px-8 rounded-2xl transition-all shadow-lg hover:shadow-xl active:scale-95">
                                <span wire:loading.remove wire:target="changePassword" class="material-symbols-rounded">lock_reset</span>
                                <span wire:loading wire:target="changePassword" class="animate-spin material-symbols-rounded">refresh</span>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <p class="mt-10 text-center text-xs font-bold text-gray-400/80 uppercase tracking-widest">
            BigFun • Account Settings
        </p>
    </div>
</div>