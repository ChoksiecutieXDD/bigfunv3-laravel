<div x-data="{
    toasts: [],
    icons: {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info',
        primary: 'stars'
    },
    addToast(detail) {
        let payload = Array.isArray(detail) ? detail[0] : detail;
        if (!payload) return;

        const id = Date.now();
        const type = payload.type || 'success';
        const title = payload.title || (type.charAt(0).toUpperCase() + type.slice(1));
        const message = payload.message || '';

        if (this.toasts.length >= 3) {
            this.toasts.shift();
        }

        this.toasts.push({
            id: id,
            visible: true,
            title: title,
            message: message || title,
            type: type,
            icon: this.icons[type] || 'notifications'
        });

        // Auto remove after 5 seconds
        setTimeout(() => {
            const toast = this.toasts.find(t => t.id === id);
            if (toast) toast.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 500); // Wait for transition
        }, 5000);
    },
    init() {
        @if(session()->has('toast_success'))
            this.addToast({ title: 'Success', message: '{{ session('toast_success') }}', type: 'success' });
        @endif

        @if(session()->has('toast_error'))
            this.addToast({ title: 'Error', message: '{{ session('toast_error') }}', type: 'error' });
        @endif
    }
}"
    x-on:show-toast.window="addToast($event.detail)"
    x-on:notify.window="addToast($event.detail)"
    class="fixed top-6 right-6 z-[999999] flex flex-col gap-3 pointer-events-none" 
    style="width: 380px;"
    x-cloak>
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
            x-transition:enter="transition ease-out duration-400"
            x-transition:enter-start="opacity-0 translate-x-8 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-8 scale-95"
            class="pointer-events-auto w-full bg-slate-900/95 backdrop-blur-xl border rounded-2xl shadow-2xl p-4 flex items-start gap-3"
            :class="{
                'border-emerald-500/40': toast.type === 'success',
                'border-red-500/40': toast.type === 'error',
                'border-amber-500/40': toast.type === 'warning',
                'border-blue-500/40': toast.type === 'info',
                'border-[#9E6B73]/40': toast.type === 'primary'
            }"
            @click="toast.visible = false">
            
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                :class="{
                    'bg-emerald-500/15 text-emerald-400': toast.type === 'success',
                    'bg-red-500/15 text-red-400': toast.type === 'error',
                    'bg-amber-500/15 text-amber-400': toast.type === 'warning',
                    'bg-blue-500/15 text-blue-400': toast.type === 'info',
                    'bg-[#9E6B73]/15 text-[#9E6B73]': toast.type === 'primary'
                }">
                <span class="material-symbols-rounded text-xl" x-text="toast.icon"></span>
            </div>

            <div class="flex-1 min-w-0">
                <h4 class="font-bold text-sm text-white" x-text="toast.title"></h4>
                <p class="text-xs text-slate-400 mt-0.5 leading-relaxed" x-text="toast.message"></p>
            </div>

            <button @click.stop="toast.visible = false" class="text-slate-600 hover:text-slate-300 transition shrink-0 p-1 rounded-lg hover:bg-white/10">
                <span class="material-symbols-rounded text-base">close</span>
            </button>
        </div>
    </template>
</div>