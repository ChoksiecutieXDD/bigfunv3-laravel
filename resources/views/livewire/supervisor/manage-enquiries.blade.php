<div class="w-full max-w-360 mx-auto space-y-8 pb-12" wire:init="loadEnquiries">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-white drop-shadow-sm">Enquiries</h2>
            <p class="text-white/80 mt-1 text-sm font-medium">Respond to customer questions via Gmail.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="loadEnquiries" class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white border border-white/20 px-6 py-3 rounded-2xl font-bold shadow-lg transition-all flex items-center gap-2 text-sm hover:scale-105 active:scale-95 group">
                <span class="material-symbols-rounded group-hover:rotate-180 transition-transform duration-500" wire:loading.class="animate-spin" wire:target="loadEnquiries">refresh</span>
                <span wire:loading.remove wire:target="loadEnquiries">Refresh Inbox</span>
                <span wire:loading wire:target="loadEnquiries">Syncing...</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white p-8 rounded-[2.5rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 relative overflow-hidden group hover:shadow-orange-200/20 transition-all duration-500">
            <div class="absolute top-0 right-0 w-32 h-32 bg-orange-50 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-110 transition-transform duration-700"></div>
            <div class="bg-orange-100 text-orange-600 rounded-2xl w-14 h-14 flex items-center justify-center mb-6 relative z-10 shadow-sm">
                <span class="material-symbols-rounded text-3xl">mark_email_unread</span>
            </div>
            <div class="relative z-10">
                <p class="text-[10px] font-extrabold text-slate-400 mb-1 uppercase tracking-[0.15em]">Recent Inbox</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-extrabold text-[#1E293B] tracking-tight">
                        <span wire:loading.remove wire:target="loadEnquiries">{{ count($emails) }}</span>
                        <span wire:loading wire:target="loadEnquiries" class="animate-pulse">--</span>
                    </h3>
                    <span class="text-xs font-bold text-orange-500">Unread</span>
                </div>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 relative overflow-hidden group hover:shadow-blue-200/20 transition-all duration-500">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-110 transition-transform duration-700"></div>
            <div class="bg-blue-100 text-blue-600 rounded-2xl w-14 h-14 flex items-center justify-center mb-6 relative z-10 shadow-sm">
                <span class="material-symbols-rounded text-3xl">cloud_sync</span>
            </div>
            <div class="relative z-10">
                <p class="text-[10px] font-extrabold text-slate-400 mb-1 uppercase tracking-[0.15em]">Sync Status</p>
                <h3 class="text-xl font-extrabold mt-2 tracking-tight {{ $syncStatus === 'Active' ? 'text-green-500' : ($syncStatus === 'Waiting to sync...' ? 'text-[#1E293B]' : 'text-red-500') }}">{{ $syncStatus }}</h3>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 relative overflow-hidden group hover:shadow-pink-200/20 transition-all duration-500">
            <div class="absolute top-0 right-0 w-32 h-32 bg-pink-50 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-110 transition-transform duration-700"></div>
            <div class="bg-pink-100 text-pink-600 rounded-2xl w-14 h-14 flex items-center justify-center mb-6 relative z-10 shadow-sm">
                <span class="material-symbols-rounded text-3xl">inbox_customize</span>
            </div>
            <div class="relative z-10">
                <p class="text-[10px] font-extrabold text-slate-400 mb-1 uppercase tracking-[0.15em]">Total Fetched</p>
                <h3 class="text-4xl font-extrabold text-[#1E293B] mt-2 tracking-tight">
                    <span wire:loading.remove wire:target="loadEnquiries">{{ count($emails) }}</span>
                    <span wire:loading wire:target="loadEnquiries" class="animate-pulse">--</span>
                </h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-gray-100 flex flex-col h-187.5 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white rounded-t-[3rem] gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-plum/10 flex items-center justify-center text-plum">
                    <span class="material-symbols-rounded text-2xl">mail</span>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#1E293B]">Gmail Inbox</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Direct sync with bigfun.qld.au@gmail.com</p>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-xs font-bold text-slate-500">Live Status</span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/50 overflow-x-hidden custom-scrollbar">

            <div wire:loading.flex wire:target="loadEnquiries" class="flex flex-col items-center justify-center h-full w-full py-20">
                <div class="relative w-20 h-20 mb-6">
                    <div class="absolute inset-0 border-4 border-plum/10 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-plum rounded-full border-t-transparent animate-spin"></div>
                    <div class="absolute inset-4 bg-plum/5 rounded-full animate-pulse flex items-center justify-center">
                        <span class="material-symbols-rounded text-plum text-xl">sync</span>
                    </div>
                </div>
                <p class="text-sm text-[#1E293B] font-extrabold uppercase tracking-[0.2em] animate-pulse">Synchronizing</p>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase tracking-wider">Accessing Gmail Securely...</p>
            </div>

            @if ($syncError)
            <div wire:loading.remove wire:target="loadEnquiries" class="flex flex-col items-center justify-center h-full text-center">
                <span class="material-symbols-rounded text-6xl text-gray-300 mb-4">link_off</span>
                <h3 class="text-xl font-bold text-gray-600 mb-2">Gmail Disconnected</h3>
                <p class="text-sm text-gray-400 mb-6 max-w-sm">{{ $syncError }}</p>
                <a href="{{ route('google.setup') }}" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <span class="material-symbols-rounded">login</span> Reconnect Account
                </a>
            </div>
            @elseif (empty($emails) && $syncStatus !== 'Waiting to sync...')
            <div wire:loading.remove wire:target="loadEnquiries" class="text-center p-10 text-gray-400 font-medium h-full flex items-center justify-center">
                Inbox is empty. All caught up!
            </div>
            @else
            <div wire:loading.remove wire:target="loadEnquiries" class="space-y-4">
                @foreach ($emails as $email)
                <div wire:click="openReplyModal('{{ $email['id'] }}', '{{ addslashes($email['name']) }}', '{{ $email['email'] }}', '{{ addslashes($email['subject']) }}', '{{ addslashes($email['snippet']) }}')"
                    class="bg-white p-6 rounded-4xl border border-gray-100 shadow-sm hover:shadow-xl hover:shadow-plum/5 hover:-translate-y-1 transition-all duration-300 group cursor-pointer relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-plum opacity-50 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex flex-col lg:flex-row gap-6 justify-between items-start lg:items-center">
                        <div class="flex items-start gap-5 w-full">
                            <div class="w-14 h-14 rounded-2xl bg-linear-to-br from-plum to-plum-dark text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-plum/20 shrink-0">
                                {{ strtoupper(substr($email['name'], 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between mb-1.5">
                                    <h4 class="font-extrabold text-[#1E293B] text-lg truncate pr-3">{{ $email['name'] }}</h4>
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest px-3 py-1 rounded-full text-amber-600 bg-amber-50 border border-amber-100 flex items-center gap-1.5 shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> New Enquiry
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs text-slate-400 font-bold">{{ $email['email'] }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                    <span class="text-[10px] text-slate-400 font-extrabold uppercase tracking-tight">{{ $email['date'] }}</span>
                                </div>
                                <p class="text-sm text-slate-700 font-bold line-clamp-1 group-hover:text-plum transition-colors leading-tight">{{ $email['subject'] }}</p>
                                <p class="text-xs text-slate-400 line-clamp-1 mt-1.5 italic font-medium opacity-80">"{{ $email['snippet'] }}"</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 w-full lg:w-auto mt-4 lg:mt-0 border-t lg:border-t-0 pt-4 lg:pt-0">
                             <button class="flex-1 lg:flex-none px-6 py-2.5 rounded-xl bg-slate-50 text-plum text-xs font-bold hover:bg-plum hover:text-white transition-all shadow-sm flex items-center justify-center gap-2">
                                <span class="material-symbols-rounded text-sm">reply</span> Reply
                             </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <template x-teleport="body">
        <!-- SYSTEM RESPONSE MODAL -->
        <div x-show="$wire.replyModalOpen" class="fixed inset-0 z-10000 flex items-center justify-center p-4" x-cloak>
            <div x-show="$wire.replyModalOpen"
                x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="$wire.replyModalOpen = false"></div>

            <div x-show="$wire.replyModalOpen"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative bg-white w-full max-w-2xl rounded-3xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden z-10 transition-all">
                
                <div class="px-8 py-8 border-b border-slate-50 flex justify-between items-center bg-white shrink-0">
                    <div class="flex items-center gap-4 text-plum">
                        <div class="w-12 h-12 rounded-xl bg-plum/10 flex items-center justify-center">
                            <span class="material-symbols-rounded text-2xl font-bold">reply</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase">System Response</h3>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5" x-text="'TARGET: ' + ($wire.replyName || 'Recipient Details...')"></p>
                        </div>
                    </div>
                    <button type="button" @click="$wire.replyModalOpen = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-50 rounded-xl">
                        <span class="material-symbols-rounded text-2xl font-bold">close</span>
                    </button>
                </div>

                <div class="p-8 overflow-y-auto custom-scrollbar grow bg-white">
                    <form wire:submit="sendReply" class="space-y-8">
                        @if ($replySnippet)
                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 relative group overflow-hidden">
                            <div class="absolute top-0 right-0 p-4 opacity-5">
                                <span class="material-symbols-rounded text-4xl font-bold">format_quote</span>
                            </div>
                            <label class="block text-[10px] font-black text-slate-300 mb-4 uppercase tracking-widest">Original Reference No.</label>
                            <div class="text-[14px] font-bold text-slate-600 italic leading-relaxed line-clamp-4 relative z-10">
                                "{{ $replySnippet }}"
                            </div>
                        </div>
                        @endif

                        <div class="grid grid-cols-1 gap-8">
                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Communication Subject</label>
                                <input type="text" wire:model="replySubject" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:border-plum/30 outline-none text-[15px] font-black text-slate-800 transition-all" required>
                            </div>

                            <div class="input-group">
                                <label class="block text-[11px] font-black text-slate-400 mb-3 uppercase tracking-widest">Message Payload</label>
                                <textarea wire:model="replyBody" rows="10" class="w-full px-6 py-5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:border-plum/30 outline-none text-[14px] font-medium leading-relaxed resize-none transition-all custom-scrollbar placeholder:text-slate-300" placeholder="Enter your response here..." required></textarea>
                            </div>

                            <div class="input-group">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">Supporting Assets</label>
                                    <span class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">Optional ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â· Max 5MB Limit</span>
                                </div>
                                <div class="flex flex-col gap-4">
                                    <input type="file" wire:model="attachments" id="reply_attachments" multiple class="hidden">
                                    <label for="reply_attachments" class="flex items-center justify-center gap-4 w-full py-6 border-2 border-dashed border-slate-200 rounded-3xl hover:border-plum/40 hover:bg-slate-50 transition-all cursor-pointer group">
                                        <div class="w-10 h-10 rounded-xl bg-slate-50 group-hover:bg-plum/10 flex items-center justify-center text-slate-400 group-hover:text-plum transition-all shadow-sm">
                                            <span class="material-symbols-rounded text-xl font-bold">attach_file_add</span>
                                        </div>
                                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest group-hover:text-slate-600 transition-colors">Select Assets To Include</span>
                                    </label>

                                    <div wire:loading wire:target="attachments" class="flex items-center gap-4 p-5 bg-blue-50/50 rounded-2xl border border-blue-100 animate-pulse">
                                        <div class="w-5 h-5 border-2 border-blue-200 border-t-blue-500 rounded-full animate-spin"></div>
                                        <span class="text-[11px] font-black text-blue-500 uppercase tracking-widest">Uploading Assets To Secure Storage...</span>
                                    </div>

                                    @if ($attachments)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                                        @foreach ($attachments as $index => $file)
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-[18px] border border-slate-100 group hover:border-plum/20 transition-all">
                                            <div class="flex items-center gap-3 overflow-hidden">
                                                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-400 group-hover:text-plum transition-colors shadow-sm">
                                                    <span class="material-symbols-rounded text-lg font-bold">description</span>
                                                </div>
                                                <span class="text-[13px] font-bold text-slate-700 truncate">{{ $file->getClientOriginalName() }}</span>
                                            </div>
                                            <button type="button" wire:click="removeAttachment({{ $index }})" class="text-slate-300 hover:text-rose-500 transition-all transform hover:scale-110 shrink-0">
                                                <span class="material-symbols-rounded text-xl font-bold">cancel</span>
                                            </button>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-slate-50 flex gap-4">
                            <button type="button" @click="$wire.replyModalOpen = false" class="px-8 py-5 text-slate-600 font-black text-[11px] hover:bg-slate-50 rounded-2xl transition-all uppercase tracking-[0.2em] border border-slate-100">Cancel</button>
                            <button type="submit" class="grow py-5 bg-plum text-white rounded-2xl font-black hover:bg-plum-dark shadow-xl shadow-plum/20 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-[11px] flex items-center justify-center gap-3">
                                <span wire:loading.remove wire:target="sendReply" class="flex items-center gap-3">
                                    <span class="material-symbols-rounded text-lg font-bold">send</span>
                                    Execute Transmission
                                </span>
                                <span wire:loading wire:target="sendReply" class="flex items-center gap-3">
                                    <span class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                    Broadcasting...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

</div>