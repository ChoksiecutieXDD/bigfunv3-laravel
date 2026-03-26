    <div x-data="{
        show: false,
        message: '',
        type: 'success',
        init() {
            @if(session()->has('toast_success'))
                this.message = '{{ session('toast_success') }}';
                this.type = 'success';
                this.show = true;
                setTimeout(() => this.show = false, 3000);
            @endif

            @if(session()->has('toast_error'))
                this.message = '{{ session('toast_error') }}';
                this.type = 'error';
                this.show = true;
                setTimeout(() => this.show = false, 3000);
            @endif
        },
        showToast(detail) {
            let payload = Array.isArray(detail) ? detail[0] : detail;
            
            if (payload) {
                this.message = payload.message || 'Action completed.';
                this.type = payload.type || 'success';
            }
            
            this.show = true;
            setTimeout(() => this.show = false, 3000);
        }
    }"
        x-on:show-toast.window="showToast($event.detail)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-x-full"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-full"
        style="display: none;"
        class="fixed top-6 right-6 z-[9999] min-w-[280px] p-4 rounded-xl shadow-2xl font-semibold text-white flex items-center gap-3 cursor-pointer" :class="type === 'error' ? 'bg-red-500' : (type === 'warning' ? 'bg-amber-500' : 'bg-emerald-500')"
        @click="show = false"
        x-cloak>
        <span class="material-symbols-rounded text-[22px]" x-text="type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'check_circle')"></span>
        <span x-text="message" class="text-[14px] tracking-wide flex-1"></span>
        <button class="opacity-70 hover:opacity-100 transition-opacity flex items-center justify-center focus:outline-none">
            <span class="material-symbols-rounded text-[18px]">close</span>
        </button>
    </div>