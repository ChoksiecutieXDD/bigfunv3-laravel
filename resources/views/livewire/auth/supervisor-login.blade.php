<div class="flex w-full h-full" x-data="{ showLogoutModal: @json(session('logged_out')), showResetModal: @json(session('password_reset_success')) }">
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

            <form wire:submit="login" x-data="{ show: false }" x-init="$wire.set('role', 'Supervisor')" class="space-y-6 animate-enter delay-100">

                <div class="input-group relative">
                    <input type="email" wire:model="email" placeholder="Email Address" required
                        class="modern-input w-full py-4 pl-14 pr-4 bg-gray-50 rounded-2xl text-gray-800 shadow-sm outline-none placeholder-gray-400 font-medium border border-gray-100 focus:bg-white transition-all">
                    <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">mail</span>
                </div>

                <div class="input-group relative">
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

                <button type="submit" wire:loading.attr="disabled" wire:target="login"
                    class="w-full py-4 bg-[#9E6B73] text-white font-bold rounded-2xl text-lg hover:bg-[#86545C] hover:shadow-lg hover:shadow-[#9E6B73]/30 hover:-translate-y-1 transition-all duration-300 flex justify-center items-center gap-3 disabled:opacity-75 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-none">
                    Login as Supervisor
                </button>

                <div class="pt-2">
                    <a href="/login" 
                        class="w-full py-4 bg-gray-100 text-gray-600 font-bold rounded-2xl text-lg hover:bg-gray-200 hover:text-gray-800 transition-all duration-300 flex justify-center items-center gap-2 group">
                        <span class="material-symbols-rounded group-hover:-translate-x-1 transition-transform">arrow_back</span>
                        Admin & Staff Login
                    </a>
                </div>
            </form>

            <p class="mt-8 text-center text-xs text-gray-400 animate-enter delay-300 pb-4">
                © 2026 BigFun Management System
            </p>
        </div>
    </div>

    <div wire:loading.flex wire:target="login" class="fixed inset-0 z-[100] items-center justify-center bg-gray-900/60 backdrop-blur-sm transition-opacity" style="display: none;">
        <div class="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center max-w-sm w-full mx-4 animate-enter">
            <div class="w-16 h-16 border-4 border-[#9E6B73]/20 border-t-[#9E6B73] rounded-full animate-spin mb-6"></div>

            <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">Authenticating...</h3>
            <p class="text-gray-500 text-center text-sm">Verifying credentials and preparing your workspace.</p>
        </div>
    </div>

    <!-- PASSWORD RESET SUCCESS MODAL -->
    <template x-if="showResetModal">
        <div class="fixed inset-0 z-[110] flex items-center justify-center px-4">
            <div x-transition.opacity @click="showResetModal = false" class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"></div>
            
            <div x-transition.scale.origin.bottom class="bg-white rounded-[2.5rem] p-8 shadow-2xl relative z-10 max-w-sm w-full border border-gray-100 flex flex-col items-center text-center animate-enter">
                <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-rounded text-green-500 text-4xl animate-bounce">lock_reset</span>
                </div>
                
                <h3 class="text-2xl font-black text-gray-800 mb-2">Password Updated!</h3>
                <p class="text-gray-500 font-medium mb-8">Your supervisor account password has been successfully reset. You can now log in.</p>
                
                <button @click="showResetModal = false" class="w-full py-4 bg-[#9E6B73] text-white font-bold rounded-2xl hover:bg-[#86545C] transition-all shadow-lg hover:shadow-[#9E6B73]/20 active:scale-95">
                    Login Now
                </button>
            </div>
        </div>
    </template>

    <!-- LOGOUT NOTIFICATION MODAL -->
    <template x-if="showLogoutModal">
        <div class="fixed inset-0 z-[110] flex items-center justify-center px-4">
            <div x-transition.opacity @click="showLogoutModal = false" class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"></div>
            
            <div x-transition.scale.origin.bottom class="bg-white rounded-[2.5rem] p-8 shadow-2xl relative z-10 max-w-sm w-full border border-gray-100 flex flex-col items-center text-center animate-enter">
                <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-rounded text-green-500 text-4xl animate-bounce">check_circle</span>
                </div>
                
                <h3 class="text-2xl font-black text-gray-800 mb-2">Logged Out</h3>
                <p class="text-gray-500 font-medium mb-8">You've been safely signed out. See you again soon!</p>
                
                <button @click="showLogoutModal = false" class="w-full py-4 bg-gray-900 text-white font-bold rounded-2xl hover:bg-[#9E6B73] transition-all shadow-lg hover:shadow-[#9E6B73]/20 active:scale-95">
                    Got it, thanks!
                </button>
            </div>
        </div>
    </template>
</div>