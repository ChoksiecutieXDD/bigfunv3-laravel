<div class="flex w-full h-full" x-data="{ showLogoutModal: @json(session('logged_out')), showResetModal: @json(session('password_reset_success')) }">

    <div x-data="{ slide: 0 }" class="hidden lg:flex w-7/12 bg-login-image relative items-center justify-center overflow-hidden h-full">
        <div class="absolute inset-0 bg-linear-to-tr from-plum-dark/90 to-plum/40 mix-blend-multiply"></div>

        <div class="relative z-10 p-16 w-full max-w-3xl flex flex-col justify-center h-full">
            <div class="relative h-48 mb-8">
                <div x-show="slide === 0" x-transition.opacity.duration.500ms class="absolute inset-0">
                    <h2 class="text-6xl font-extrabold mb-6 leading-tight text-white drop-shadow-md">Manage Your <br>Operations.</h2>
                    <p class="text-xl text-white opacity-95 font-light drop-shadow-sm max-w-lg">Seamless logistics, scheduling, and staff management all in one secure platform.</p>
                </div>

                <div x-show="slide === 1" x-transition.opacity.duration.500ms class="absolute inset-0" x-cloak>
                    <h2 class="text-6xl font-extrabold mb-6 leading-tight text-white drop-shadow-md">Track Real-Time <br>Deliveries.</h2>
                    <p class="text-xl text-white opacity-95 font-light drop-shadow-sm max-w-lg">Monitor fleet location and get status updates instantly on the dashboard.</p>
                </div>

                <div x-show="slide === 2" x-transition.opacity.duration.500ms class="absolute inset-0" x-cloak>
                    <h2 class="text-6xl font-extrabold mb-6 leading-tight text-white drop-shadow-md">Streamline <br>Your Workflow.</h2>
                    <p class="text-xl text-white opacity-95 font-light drop-shadow-sm max-w-lg">Empower your team with automated tools that drive efficiency and growth.</p>
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="button" @click="slide = 0" :class="slide === 0 ? 'bg-white w-4' : 'bg-white/50 w-2'" class="h-2 rounded-full cursor-pointer hover:bg-white/80 transition-all duration-300"></button>
                <button type="button" @click="slide = 1" :class="slide === 1 ? 'bg-white w-4' : 'bg-white/50 w-2'" class="h-2 rounded-full cursor-pointer hover:bg-white/80 transition-all duration-300"></button>
                <button type="button" @click="slide = 2" :class="slide === 2 ? 'bg-white w-4' : 'bg-white/50 w-2'" class="h-2 rounded-full cursor-pointer hover:bg-white/80 transition-all duration-300"></button>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-5/12 bg-white h-full flex flex-col justify-center p-8 sm:p-16 relative text-gray-800 overflow-y-auto no-scrollbar">
        <div class="max-w-md w-full mx-auto my-auto">
            <div class="mb-12 animate-enter text-center flex flex-col items-center">
                <a href="/" class="inline-block hover:scale-105 transition-transform duration-300 focus:outline-none">
                    <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="Logo" class="h-16 w-auto mb-6 mx-auto">
                </a>
                <h1 class="text-4xl font-bold text-gray-800">Welcome Back!</h1>
                <p class="text-gray-500 mt-3">Please enter your details to sign in.</p>
            </div>

            <form wire:submit="login" class="space-y-6 animate-enter delay-100">

                <div class="input-group">
                    <select wire:model="role" required class="modern-input custom-select w-full py-4 pl-14 pr-12 bg-gray-50 rounded-2xl text-gray-700 shadow-sm outline-none appearance-none font-medium cursor-pointer border border-gray-100 focus:bg-white">
                        <option value="" disabled selected>Select Role</option>
                        <option value="Administrator">Administrator</option>
                        <option value="Staff">Staff</option>
                    </select>
                    <span class="material-symbols-rounded input-icon">work</span>
                </div>

                <div class="input-group">
                    <input type="email" wire:model="email" placeholder="Email Address" required class="modern-input w-full py-4 pl-14 pr-4 bg-gray-50 rounded-2xl text-gray-800 shadow-sm outline-none placeholder-gray-400 font-medium border border-gray-100 focus:bg-white">
                    <span class="material-symbols-rounded input-icon">mail</span>
                </div>

                <div x-data="{ showPassword: false }" class="input-group relative">
                    <input :type="showPassword ? 'text' : 'password'" wire:model="password" placeholder="Password" required class="modern-input w-full py-4 pl-14 pr-12 bg-gray-50 rounded-2xl text-gray-800 shadow-sm outline-none placeholder-gray-400 font-medium border border-gray-100 focus:bg-white">
                    <span class="material-symbols-rounded input-icon">lock</span>

                    <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-plum transition-colors z-20 focus:outline-none flex items-center justify-center">
                        <span class="material-symbols-rounded text-xl leading-none" x-text="showPassword ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>

                <div class="flex items-center justify-between text-sm animate-enter delay-200 mt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-gray-500 hover:text-gray-700 select-none">
                        <input type="checkbox" wire:model="remember" class="w-5 h-5 rounded text-plum focus:ring-plum border-gray-300">
                        <span>Remember me</span>
                    </label>
                    <a href="/forgot-password" class="font-semibold text-plum hover:text-plum-dark transition">Forgot Password?</a>
                </div>

                @error('auth')
                <span class="text-red-500 text-sm font-semibold block text-center mt-2">{{ $message }}</span>
                @enderror

                <button type="submit" wire:loading.attr="disabled" wire:target="login" class="w-full py-4 bg-plum text-white font-bold rounded-2xl text-lg hover:bg-plum-dark hover:shadow-lg hover:shadow-plum/30 hover:-translate-y-1 transition-all duration-300 animate-enter delay-300 mt-4 flex justify-center items-center gap-2 disabled:opacity-75 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-none">
                    <span wire:loading.remove wire:target="login">Sign In</span>
                    <span wire:loading wire:target="login">Signing In...</span>
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-gray-400 animate-enter delay-300 pb-4">
                Â© {{ date('Y') }} BigFun Management System
            </p>
        </div>
    </div>

    <!-- FULL SCREEN LOADING MODAL -->
    <div wire:loading.flex wire:target="login" class="fixed inset-0 z-100 items-center justify-center bg-gray-900/60 backdrop-blur-sm transition-opacity" style="display: none;">
        <div class="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center max-w-sm w-full mx-4 animate-enter">
            <div class="w-16 h-16 border-4 border-plum/20 border-t-plum rounded-full animate-spin mb-6"></div>

            <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">Authenticating...</h3>
            <p class="text-gray-500 text-center text-sm">Verifying credentials and preparing your workspace.</p>
        </div>
    </div>

    <!-- PASSWORD RESET SUCCESS MODAL -->
    <template x-if="showResetModal">
        <div class="fixed inset-0 z-110 flex items-center justify-center px-4">
            <div x-transition.opacity @click="showResetModal = false" class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"></div>
            
            <div x-transition.scale.origin.bottom class="bg-white rounded-[2.5rem] p-8 shadow-2xl relative z-10 max-w-sm w-full border border-gray-100 flex flex-col items-center text-center animate-enter">
                <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-rounded text-green-500 text-4xl animate-bounce">lock_reset</span>
                </div>
                
                <h3 class="text-2xl font-black text-gray-800 mb-2">Password Updated!</h3>
                <p class="text-gray-500 font-medium mb-8">Your password has been successfully reset. You can now log in with your new credentials.</p>
                
                <button @click="showResetModal = false" class="w-full py-4 bg-plum text-white font-bold rounded-2xl hover:bg-plum-dark transition-all shadow-lg hover:shadow-plum/20 active:scale-95">
                    Sign In Now
                </button>
            </div>
        </div>
    </template>

    <!-- LOGOUT NOTIFICATION MODAL -->
    <template x-if="showLogoutModal">
        <div class="fixed inset-0 z-110 flex items-center justify-center px-4">
            <div x-transition.opacity @click="showLogoutModal = false" class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"></div>
            
            <div x-transition.scale.origin.bottom class="bg-white rounded-[2.5rem] p-8 shadow-2xl relative z-10 max-w-sm w-full border border-gray-100 flex flex-col items-center text-center animate-enter">
                <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-rounded text-green-500 text-4xl animate-bounce">check_circle</span>
                </div>
                
                <h3 class="text-2xl font-black text-gray-800 mb-2">Logged Out</h3>
                <p class="text-gray-500 font-medium mb-8">You've been safely signed out. See you again soon!</p>
                
                <button @click="showLogoutModal = false" class="w-full py-4 bg-gray-900 text-white font-bold rounded-2xl hover:bg-plum transition-all shadow-lg hover:shadow-plum/20 active:scale-95">
                    Got it, thanks!
                </button>
            </div>
        </div>
    </template>
</div>