<div class="min-h-screen w-full flex items-center justify-center p-6 relative overflow-hidden bg-[#FDF2F4]">

    <div class="absolute top-0 left-0 w-96 h-96 bg-[#E3D5CA] rounded-full blur-[80px] -translate-x-1/2 -translate-y-1/2 opacity-60 pointer-events-none"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-[#ffe4e6] rounded-full blur-[80px] translate-x-1/3 translate-y-1/3 opacity-60 pointer-events-none"></div>

    <div class="w-full max-w-md bg-white p-8 md:p-10 rounded-3xl shadow-2xl relative z-10 animate-enter">

        <div class="text-center mb-8">
            <a href="/home" class="inline-block hover:scale-105 transition-transform duration-300">
                <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="Logo" class="h-16 w-auto mb-6 mx-auto">
            </a>
            <h1 class="text-3xl font-bold text-gray-800">Set New Password</h1>
            <p class="text-gray-500 text-sm mt-2">Please create a strong password.</p>
        </div>

        @if ($isValid)
        <form wire:submit="updatePassword" class="space-y-5">

            <div x-data="{ show: false }" class="relative group">
                <input :type="show ? 'text' : 'password'" wire:model="password" placeholder="New Password" required
                    class="modern-input w-full py-4 pl-14 pr-12 bg-gray-50 rounded-2xl border border-gray-100 focus:outline-none transition-all text-gray-800 font-medium focus:bg-white">
                <span class="material-symbols-rounded absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-[#9E6B73]">lock</span>

                <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition p-2">
                    <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>

            <div x-data="{ show: false }" class="relative group">
                <input :type="show ? 'text' : 'password'" wire:model="password_confirmation" placeholder="Confirm Password" required
                    class="modern-input w-full py-4 pl-14 pr-12 bg-gray-50 rounded-2xl border border-gray-100 focus:outline-none transition-all text-gray-800 font-medium focus:bg-white">
                <span class="material-symbols-rounded absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-[#9E6B73]">lock_reset</span>

                <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] transition p-2">
                    <span class="material-symbols-rounded text-xl" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>

            @error('password')
            <div class="text-red-500 text-xs font-bold text-center mt-1">{{ $message }}</div>
            @enderror

            <button type="submit"
                class="w-full py-4 bg-[#9E6B73] text-white font-bold rounded-2xl text-lg hover:bg-[#86545C] hover:shadow-lg hover:-translate-y-1 transition-all duration-300 mt-2 flex justify-center items-center gap-2">
                <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                <span wire:loading wire:target="updatePassword">Saving...</span>
            </button>
        </form>
        @else
        <div class="text-center py-6">
            <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500 shadow-sm">
                <span class="material-symbols-rounded text-3xl">link_off</span>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Link Expired</h3>
            <p class="text-gray-500 mb-6 font-medium text-sm leading-relaxed">{{ $errorMsg }}</p>
            <a href="{{ route('password.request') }}" class="inline-block w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl transition">Request New Link</a>
        </div>
        @endif

        <p class="mt-8 text-center text-xs text-gray-400">
            © 2026 BigFun Management System
        </p>
    </div>
</div>