<div class="w-full max-w-[1440px] mx-auto space-y-8 pb-12" wire:init="loadEnquiries">

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

    <div class="bg-white rounded-[3rem] shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-gray-100 flex flex-col h-[750px] overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white rounded-t-[3rem] gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-[#9E6B73]/10 flex items-center justify-center text-[#9E6B73]">
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
                    <div class="absolute inset-0 border-4 border-[#9E6B73]/10 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-[#9E6B73] rounded-full border-t-transparent animate-spin"></div>
                    <div class="absolute inset-4 bg-[#9E6B73]/5 rounded-full animate-pulse flex items-center justify-center">
                        <span class="material-symbols-rounded text-[#9E6B73] text-xl">sync</span>
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
                    class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-xl hover:shadow-[#9E6B73]/5 hover:-translate-y-1 transition-all duration-300 group cursor-pointer relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-[#9E6B73] opacity-50 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex flex-col lg:flex-row gap-6 justify-between items-start lg:items-center">
                        <div class="flex items-start gap-5 w-full">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#9E6B73] to-[#86545C] text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-[#9E6B73]/20 shrink-0">
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
                                <p class="text-sm text-slate-700 font-bold line-clamp-1 group-hover:text-[#9E6B73] transition-colors leading-tight">{{ $email['subject'] }}</p>
                                <p class="text-xs text-slate-400 line-clamp-1 mt-1.5 italic font-medium opacity-80">"{{ $email['snippet'] }}"</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 w-full lg:w-auto mt-4 lg:mt-0 border-t lg:border-t-0 pt-4 lg:pt-0">
                             <button class="flex-1 lg:flex-none px-6 py-2.5 rounded-xl bg-slate-50 text-[#9E6B73] text-xs font-bold hover:bg-[#9E6B73] hover:text-white transition-all shadow-sm flex items-center justify-center gap-2">
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

    @if ($replyModalOpen)
    <div class="fixed inset-0 z-[10000] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('replyModalOpen', false)"></div>

            <div class="bg-white w-full max-w-2xl rounded-[2rem] shadow-2xl relative flex flex-col max-h-[90vh] overflow-hidden z-10">
                <div class="bg-[#9E6B73] p-6 flex justify-between items-start shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-rounded">reply</span> Reply to Enquiry
                        </h3>
                        <p class="text-pink-100 text-sm mt-1">Replying to {{ $replyName }} ({{ $replyEmail }})</p>
                    </div>
                    <button wire:click="$set('replyModalOpen', false)" class="text-white/70 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-full transition"><span class="material-symbols-rounded">close</span></button>
                </div>

                <div class="p-8 overflow-y-auto flex-1 custom-scrollbar">
                    <form wire:submit="sendReply">
                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Original Message Snippet</label>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-sm text-gray-600 italic leading-relaxed line-clamp-3">
                                {{ $replySnippet }}
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Subject</label>
                            <input type="text" wire:model="replySubject" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#9E6B73] focus:ring-2 focus:ring-[#9E6B73]/20 transition font-medium" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Your Reply</label>
                            <textarea wire:model="replyBody" rows="8" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#9E6B73] focus:ring-2 focus:ring-[#9E6B73]/20 transition resize-none" placeholder="Type your response here..." required></textarea>
                        </div>

                        <div class="mb-6">
                            <input type="file" wire:model="attachments" id="attachments" multiple class="hidden">
                            <div class="flex items-center gap-3">
                                <label for="attachments" class="text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition flex items-center gap-2 border border-gray-200 cursor-pointer">
                                    <span class="material-symbols-rounded text-sm">attach_file</span> Add Attachment
                                </label>
                                <span class="text-xs text-gray-400 italic" wire:loading.remove wire:target="attachments">Optional. Max 5MB.</span>
                                <span class="text-xs text-[#9E6B73] font-bold italic" wire:loading wire:target="attachments">Uploading...</span>
                            </div>
                            @if ($attachments)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($attachments as $file)
                                <div class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-xs font-bold border border-blue-100 flex items-center gap-1">
                                    <span class="material-symbols-rounded text-sm">description</span> {{ $file->getClientOriginalName() }}
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="$set('replyModalOpen', false)" class="px-6 py-3 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-50 transition">Cancel</button>
                            <button type="submit" class="bg-[#9E6B73] hover:bg-[#86545C] text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-[#9E6B73]/30 flex items-center gap-2 transition transform hover:scale-105 active:scale-95">
                                <span wire:loading.remove wire:target="sendReply" class="material-symbols-rounded">send</span>
                                <span wire:loading wire:target="sendReply" class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
                                <span wire:loading.remove wire:target="sendReply">Send Reply</span>
                                <span wire:loading wire:target="sendReply">Sending...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>