<div class="max-w-[1600px] mx-auto space-y-6" wire:init="loadEnquiries">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Enquiries</h2>
            <p class="text-gray-500 mt-1 text-sm font-medium">Respond to customer questions via Gmail.</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="loadEnquiries" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-[#9E6B73] border border-[#9E6B73]/20 px-4 py-2.5 rounded-xl font-bold shadow-sm transition flex items-center gap-2 text-sm">
                <span class="material-symbols-rounded" wire:loading.class="animate-spin" wire:target="loadEnquiries">refresh</span>
                <span wire:loading.remove wire:target="loadEnquiries">Refresh Inbox</span>
                <span wire:loading wire:target="loadEnquiries">Syncing...</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-[2rem] shadow-lg relative overflow-hidden bg-orange-50">
            <div class="bg-orange-100 text-orange-500 rounded-xl w-12 h-12 flex items-center justify-center mb-2">
                <span class="material-symbols-rounded text-2xl">mark_email_unread</span>
            </div>
            <div class="relative z-10 pt-1">
                <p class="text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">Inbox (Recent)</p>
                <h3 class="text-3xl font-extrabold text-[#2D3748] mt-2">
                    <span wire:loading.remove wire:target="loadEnquiries">{{ count($emails) }}</span>
                    <span wire:loading wire:target="loadEnquiries" class="animate-pulse">--</span>
                </h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[2rem] shadow-lg relative overflow-hidden bg-blue-50">
            <div class="bg-blue-100 text-blue-500 rounded-xl w-12 h-12 flex items-center justify-center mb-2">
                <span class="material-symbols-rounded text-2xl">check_circle</span>
            </div>
            <div class="relative z-10 pt-1">
                <p class="text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">Sync Status</p>
                <h3 class="text-lg font-bold mt-2 {{ $syncStatus === 'Active' ? 'text-green-500' : ($syncStatus === 'Waiting to sync...' ? 'text-[#2D3748]' : 'text-red-500') }}">{{ $syncStatus }}</h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[2rem] shadow-lg relative overflow-hidden bg-pink-50">
            <div class="bg-[#9E6B73]/10 text-[#9E6B73] rounded-xl w-12 h-12 flex items-center justify-center mb-2">
                <span class="material-symbols-rounded text-2xl">inbox</span>
            </div>
            <div class="relative z-10 pt-1">
                <p class="text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">Total Fetched</p>
                <h3 class="text-3xl font-extrabold text-[#9E6B73] mt-2">
                    <span wire:loading.remove wire:target="loadEnquiries">{{ count($emails) }}</span>
                    <span wire:loading wire:target="loadEnquiries" class="animate-pulse">--</span>
                </h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-black-200/50 border border-gray-100 flex flex-col h-[700px]">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gray-50/50 rounded-t-[2.5rem] gap-4">
            <div class="flex items-center gap-3">
                <span class="material-symbols-rounded text-[#9E6B73] text-3xl">mail</span>
                <h3 class="text-lg font-bold text-[#2D3748]">Gmail Inbox</h3>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-400">bigfun.qld.au@gmail.com</span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-4 bg-gray-50/30 overflow-x-hidden custom-scrollbar">

            <div wire:loading.flex wire:target="loadEnquiries" class="flex-col items-center justify-center h-full w-full">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-200 border-t-[#9E6B73]"></div>
                <p class="text-sm text-gray-400 mt-3 font-medium">Syncing with Google...</p>
            </div>

            @if ($syncError)
            <div wire:loading.remove wire:target="loadEnquiries" class="flex flex-col items-center justify-center h-full text-center">
                <span class="material-symbols-rounded text-6xl text-gray-300 mb-4">link_off</span>
                <h3 class="text-xl font-bold text-gray-600 mb-2">Gmail Disconnected</h3>
                <p class="text-sm text-gray-400 mb-6 max-w-sm">{{ $syncError }}</p>
                <a href="/google/setup" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition flex items-center gap-2">
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
                    class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group cursor-pointer relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-orange-400"></div>
                    <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
                        <div class="flex items-start gap-4 w-full">
                            <div class="w-12 h-12 rounded-2xl bg-[#9E6B73] text-white flex items-center justify-center font-bold text-lg shrink-0">
                                {{ strtoupper(substr($email['name'], 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-bold text-[#2D3748] text-lg truncate pr-2">{{ $email['name'] }}</h4>
                                    <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-lg text-orange-500 bg-orange-50 flex items-center gap-1 shrink-0">
                                        <span class="material-symbols-rounded text-xs">mark_email_unread</span> Inbox
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 font-medium mb-1"><span>{{ $email['email'] }}</span> • <span>{{ $email['date'] }}</span></p>
                                <p class="text-sm text-gray-600 font-medium line-clamp-1 group-hover:text-[#9E6B73] transition-colors">{{ $email['subject'] }}</p>
                                <p class="text-xs text-gray-400 line-clamp-1 mt-1 italic">"{{ $email['snippet'] }}"</p>
                            </div>
                        </div>
                        <button class="hidden sm:flex w-10 h-10 rounded-xl bg-gray-50 items-center justify-center text-gray-400 group-hover:bg-[#9E6B73] group-hover:text-white transition shrink-0">
                            <span class="material-symbols-rounded">reply</span>
                        </button>
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