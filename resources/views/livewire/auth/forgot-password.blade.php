<div class="flex w-full h-full">
    <div x-data="{ slide: 0, timer: null }"
        x-init="timer = setInterval(() => { slide = (slide + 1) % 3 }, 5000)"
        class="hidden lg:flex w-7/12 bg-login-image relative items-center justify-center overflow-hidden h-full">
        <div class="absolute inset-0 bg-linear-to-tr from-plum-dark/90 to-plum/40 mix-blend-multiply"></div>

        <div class="relative z-10 p-16 w-full max-w-3xl flex flex-col justify-center h-full">
            <div class="relative h-48 mb-8">
                <div x-show="slide === 0" x-transition.opacity.duration.500ms class="absolute inset-0">
                    <h2 class="text-6xl font-extrabold mb-6 leading-tight text-white drop-shadow-md">Account <br>Recovery.</h2>
                    <p class="text-xl text-white opacity-95 font-light drop-shadow-sm max-w-lg">Securely reset your access and get back to managing operations.</p>
                </div>

                <div x-show="slide === 1" x-transition.opacity.duration.500ms class="absolute inset-0" x-cloak>
                    <h2 class="text-6xl font-extrabold mb-6 leading-tight text-white drop-shadow-md">We've Got <br>Your Back.</h2>
                    <p class="text-xl text-white opacity-95 font-light drop-shadow-sm max-w-lg">Follow the simple steps to verify your identity and restore your account.</p>
                </div>

                <div x-show="slide === 2" x-transition.opacity.duration.500ms class="absolute inset-0" x-cloak>
                    <h2 class="text-6xl font-extrabold mb-6 leading-tight text-white drop-shadow-md">Manage Your <br>Operations.</h2>
                    <p class="text-xl text-white opacity-95 font-light drop-shadow-sm max-w-lg">Seamless logistics, scheduling, and staff management all in one secure platform.</p>
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                <button @click="slide = 0" :class="slide === 0 ? 'bg-white w-4' : 'bg-white/50 w-2'" class="h-2 rounded-full transition-all duration-300"></button>
                <button @click="slide = 1" :class="slide === 1 ? 'bg-white w-4' : 'bg-white/50 w-2'" class="h-2 rounded-full transition-all duration-300"></button>
                <button @click="slide = 2" :class="slide === 2 ? 'bg-white w-4' : 'bg-white/50 w-2'" class="h-2 rounded-full transition-all duration-300"></button>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-5/12 bg-white h-full flex flex-col justify-center p-8 sm:p-16 relative text-gray-800 overflow-y-auto no-scrollbar">
        <div class="max-w-md w-full mx-auto my-auto">

            @if(!$mailSent)
            <div class="mb-12 animate-enter text-center flex flex-col items-center">
                <a href="/" class="inline-block hover:scale-105 transition-transform duration-300">
                    <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="Logo" class="h-16 w-auto mb-6 mx-auto">
                </a>
                <h1 class="text-4xl font-bold text-gray-800">Forgot Password?</h1>
                <p class="text-gray-500 mt-3">Enter your registered email address below.</p>
            </div>

            <form wire:submit="sendResetLink" class="space-y-6 animate-enter delay-100">
                <div class="input-group relative">
                    <input type="email" wire:model="email" placeholder="Email Address" required
                        class="modern-input w-full py-4 pl-14 pr-4 bg-gray-50 rounded-2xl text-gray-800 shadow-sm outline-none placeholder-gray-400 font-medium border border-gray-100 focus:bg-white transition-all">
                    <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">mail</span>
                </div>

                @error('email')
                <div class="text-red-500 text-sm font-semibold text-center mt-2">{{ $message }}</div>
                @enderror

                <button type="submit"
                    class="w-full py-4 bg-plum text-white font-bold rounded-2xl text-lg hover:bg-plum-dark hover:shadow-lg hover:shadow-plum/30 hover:-translate-y-1 transition-all duration-300 flex justify-center items-center gap-2">
                    <span wire:loading.remove wire:target="sendResetLink">Send Reset Link</span>
                    <span wire:loading wire:target="sendResetLink">Verifying Account...</span>
                </button>
            </form>

            <div class="mt-8 text-center animate-enter delay-200">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-plum transition p-2">
                    <span class="material-symbols-rounded text-lg">arrow_back</span>
                    Back to Login
                </a>
            </div>
            @else
            <div class="text-center animate-enter">
                <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-8 text-green-500 shadow-inner">
                    <span class="material-symbols-rounded text-5xl">mark_email_read</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Check Your Email</h2>
                <p class="text-gray-500 mb-10 leading-relaxed">
                    We've sent password reset instructions to <br>
                    <span class="font-bold text-gray-800">{{ $email }}</span>
                </p>

                <a href="{{ route('login') }}" class="block w-full py-4 bg-gray-100 text-gray-700 font-bold rounded-2xl text-lg hover:bg-gray-200 transition-all text-center">
                    Back to Login
                </a>
            </div>
            @endif

            <p class="mt-12 text-center text-xs text-gray-400 pb-4">
                &copy; 2026 BigFun Management System
            </p>
        </div>
    </div>
</div>