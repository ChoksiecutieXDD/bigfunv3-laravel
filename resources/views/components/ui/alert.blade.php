<div x-data="{
        show: false,
        message: '',
        type: 'info',
        init() {
            @if(session()->has('success'))
                this.message = '{{ session('success') }}';
                this.type = 'success';
                this.show = true;
                setTimeout(() => this.show = false, 5000);
            @endif

            @if(session()->has('error'))
                this.message = '{{ session('error') }}';
                this.type = 'error';
                this.show = true;
                setTimeout(() => this.show = false, 5000);
            @endif
        }, // <--- THIS WAS MISSING!

        showAlert(detail) {
            let payload = Array.isArray(detail) ? detail[0] : detail;
            
            if (payload) {
                this.message = payload.message || 'System Notice';
                this.type = payload.type || 'info';
            }
            
            this.show = true;
            setTimeout(() => this.show = false, 5000);
        }
    }"
    x-on:show-alert.window="showAlert($event.detail)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="opacity-0 -translate-y-5"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200 transform"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-5"
    style="display: none;"
    class="fixed top-6 left-1/2 -translate-x-1/2 z-9999 flex items-center gap-3 px-6 py-4 rounded-2xl text-white shadow-2xl cursor-pointer"
    :class="type === 'error' ? 'bg-red-500' : (type === 'success' ? 'bg-emerald-500' : 'bg-plum')"
    @click="show = false"
    x-cloak>

    <span class="material-symbols-rounded text-2xl" x-text="type === 'error' ? 'error' : (type === 'success' ? 'check_circle' : 'info')"></span>
    <span class="text-sm font-bold tracking-wide" x-text="message"></span>
    <button class="ml-4 hover:text-white/70 transition-colors focus:outline-none flex items-center">
        <span class="material-symbols-rounded text-xl">close</span>
    </button>
</div>