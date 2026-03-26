<div x-data="{
        show: false,
        title: '',
        message: '',
        type: 'danger', // 'danger', 'warning', or 'info'
        confirmEvent: '',
        confirmParams: null,

        openModal(event) {
            const data = event.detail;
            const payload = Array.isArray(data) ? data[0] : data;

            this.title = payload.title || 'Confirm Action';
            this.message = payload.message || 'Are you sure you want to proceed?';
            this.type = payload.type || 'danger';
            this.confirmEvent = payload.event || '';
            this.confirmParams = payload.params || null;

            this.show = true;
        },

        confirm() {
            if (this.confirmEvent) {
                // Dispatch the event back to Livewire
                $dispatch(this.confirmEvent, { id: this.confirmParams });
            }
            this.show = false;
        }
    }"
    @open-modal.window="openModal"
    x-show="show"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    style="display: none;"
    x-cloak>

    <div x-show="show"
        x-transition.opacity.duration.300ms
        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
        @click="show = false"></div>

    <div x-show="show"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-90 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
        class="relative w-full max-w-sm bg-white rounded-[24px] shadow-2xl p-6 md:p-8 flex flex-col items-center text-center z-[101]">

        <div class="w-16 h-16 rounded-full flex items-center justify-center mb-5"
            :class="{
                 'bg-red-50 text-red-500': type === 'danger',
                 'bg-amber-50 text-amber-500': type === 'warning',
                 'bg-plum-light text-plum': type === 'info'
             }">
            <span class="material-symbols-rounded text-3xl" x-text="type === 'danger' ? 'delete_forever' : (type === 'info' ? 'info' : 'warning')"></span>
        </div>

        <h3 class="text-xl font-bold text-slate-800 mb-2" x-text="title"></h3>
        <p class="text-[14px] font-medium text-slate-500 mb-8 leading-relaxed" x-text="message"></p>

        <div class="flex w-full gap-3">
            <button type="button" @click="show = false" class="flex-1 py-3.5 text-slate-600 font-bold text-[15px] hover:bg-slate-50 rounded-xl transition-colors">
                Cancel
            </button>
            <button type="button" @click="confirm()" class="flex-1 py-3.5 text-white font-bold text-[15px] rounded-xl shadow-md transition-all active:scale-95"
                :class="{
                        'bg-red-500 hover:bg-red-600 shadow-red-500/20': type === 'danger',
                        'bg-amber-500 hover:bg-amber-600 shadow-amber-500/20': type === 'warning',
                        'bg-plum hover:bg-plum-dark shadow-plum/20': type === 'info'
                    }"
                x-text="type === 'danger' ? 'Yes, Delete' : 'Confirm'">
            </button>
        </div>
    </div>
</div>