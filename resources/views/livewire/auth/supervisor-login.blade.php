<div class="flex w-full h-full">
    <div class="hidden lg:flex w-7/12 bg-login-image relative items-center justify-center overflow-hidden h-full">
        <div class="absolute inset-0 bg-gradient-to-tr from-[#86545C]/90 to-[#9E6B73]/40 mix-blend-multiply"></div>

        <div class="relative z-10 p-16 w-full max-w-3xl flex flex-col justify-center h-full text-white">
            <div class="animate-enter">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 mb-6">
                    <span class="material-symbols-rounded text-sm">verified_user</span>
                    <span class="text-xs font-semibold tracking-wider uppercase">Supervisor Access</span>
                </div>

                <h2 class="text-6xl font-extrabold mb-6 leading-tight drop-shadow-md">
                    Oversee with <br>Confidence.
                </h2>
                <p class="text-xl opacity-95 font-light drop-shadow-sm max-w-lg mb-8">
                    Manage schedules, approve requests, and monitor team performance from your dedicated dashboard.
                </p>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-5/12 bg-white h-full flex flex-col justify-center p-8 sm:p-16 relative text-gray-800 overflow-y-auto no-scrollbar">
        <div class="max-w-md w-full mx-auto my-auto">
            <div class="mb-10 animate-enter text-center">
                <a href="/" class="inline-block hover:scale-105 transition-transform duration-300 focus:outline-none">
                    <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="Logo" class="h-16 w-auto mb-6 mx-auto">
                </a>
                <h1 class="text-3xl font-bold text-gray-800">Supervisor Portal</h1>
                <p class="text-gray-500 mt-2">Secure access for management staff.</p>
            </div>

            <form wire:submit="login" class="space-y-6 animate-enter delay-100">
                <div class="input-group relative">
                    <input type="email" wire:model="email" placeholder="Email Address" required
                        class="modern-input w-full py-4 pl-14 pr-4 bg-gray-50 rounded-2xl text-gray-800 shadow-sm outline-none placeholder-gray-400 font-medium border border-gray-100 focus:bg-white transition-all">
                    <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">mail</span>
                </div>

                <div x-data="{ show: false }" class="input-group relative">
                    <input :type="show ? 'text' : 'password'" wire:model="password" placeholder="Password" required
                        class="modern-input w-full py-4 pl-14 pr-12 bg-gray-50 rounded-2xl text-gray-800 shadow-sm outline-none placeholder-gray-400 font-medium border border-gray-100 focus:bg-white transition-all">
                    <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">lock</span>

                    <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition-colors z-20">
                        <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>

                <div class="flex items-center justify-between text-sm animate-enter delay-200 mt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-gray-500 hover:text-gray-700 select-none">
                        <input type="checkbox" wire:model="remember" class="w-5 h-5 rounded text-[#9E6B73] focus:ring-[#9E6B73] border-gray-300 shadow-sm">
                        <span>Remember me</span>
                    </label>
                    <a href="/forgot-password" class="font-semibold text-[#9E6B73] hover:text-[#86545C] transition">Forgot Password?</a>
                </div>

                @error('auth')
                <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold text-center animate-pulse">
                    {{ $message }}
                </div>
                @enderror

                <button type="submit"
                    class="w-full py-4 bg-[#9E6B73] text-white font-bold rounded-2xl text-lg hover:bg-[#86545C] hover:shadow-lg hover:shadow-[#9E6B73]/30 hover:-translate-y-1 transition-all duration-300 flex justify-center items-center gap-3">
                    <span wire:loading.remove wire:target="login">Login as Supervisor</span>
                    <span wire:loading wire:target="login" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Authenticating...
                    </span>
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-gray-400 animate-enter delay-300 pb-4">
                © 2026 BigFun Management System
            </p>
        </div>
    </div>
</div>