<div class="flex flex-col gap-6">

    @php
    // Calculate color dynamically based on Livewire's $currentEnv state.
    // Moved inside the main div so Livewire has a single root element!
    $envColor = match($currentEnv) {
    'local', 'development' => 'text-amber-400',
    'staging' => 'text-blue-400',
    default => 'text-emerald-400'
    };
    @endphp

    <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400">
                <span class="material-symbols-rounded">database</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">Data Management</h2>
                <p class="text-xs text-slate-400">Manage records and backups</p>
            </div>
        </div>

        <div class="space-y-4">
            <a href="/system/db-view" class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-[#9E6B73] hover:bg-slate-800 transition-all group cursor-pointer">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-rounded text-slate-400 group-hover:text-[#9E6B73] transition-colors">table_view</span>
                    <span class="font-medium text-slate-200">Database Viewer</span>
                </div>
                <span class="material-symbols-rounded text-slate-500 group-hover:text-white">chevron_right</span>
            </a>

            <button wire:click="exportDb" class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-emerald-500/50 hover:bg-slate-800 transition-all group disabled:opacity-50">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-rounded text-slate-400 group-hover:text-emerald-400 transition-colors">download</span>
                    <span class="font-medium text-slate-200" wire:loading.remove wire:target="exportDb">Export Full Backup (.sql)</span>
                    <span class="font-medium text-emerald-400" wire:loading wire:target="exportDb">Generating Backup...</span>
                </div>
            </button>
        </div>
    </div>

    <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-full bg-[#9E6B73]/10 flex items-center justify-center text-[#9E6B73]">
                <span class="material-symbols-rounded">memory</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">System Maintenance</h2>
                <p class="text-xs text-slate-400">Performance and availability</p>
            </div>
        </div>

        <div class="space-y-4">
            <button wire:click="clearCache" class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-[#9E6B73] hover:bg-slate-800 transition-all group disabled:opacity-50">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-rounded text-slate-400 group-hover:text-[#9E6B73] transition-colors">cleaning_services</span>
                    <div class="text-left">
                        <span class="font-medium text-slate-200 block" wire:loading.remove wire:target="clearCache">Clear System Cache</span>
                        <span class="font-medium text-[#9E6B73] block" wire:loading wire:target="clearCache">Clearing...</span>
                        <span class="text-xs text-slate-500 block">Frees up temporary server files</span>
                    </div>
                </div>
            </button>

            <div class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-rounded text-slate-400">construction</span>
                    <div class="text-left">
                        <span class="font-medium text-slate-200 block">Maintenance Mode</span>
                        <span class="text-xs text-slate-500 block">
                            @if($isMaintenance)
                            <span class="text-amber-500">Currently Active</span>
                            @else
                            Restricts public access
                            @endif
                        </span>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:change="toggleMaintenance" class="sr-only peer" {{ $isMaintenance ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                </label>
            </div>
        </div>
    </div>

    <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-400">
                <span class="material-symbols-rounded">inventory_2</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">Business Modules</h2>
                <p class="text-xs text-slate-400">Manage application content</p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-700">
                <div>
                    <h3 class="font-bold text-slate-200">Product Management</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs">Add new rides, update pricing, or edit existing inventory items.</p>
                </div>
                <a href="/inventory" class="flex shrink-0 items-center gap-2 bg-[#9E6B73] hover:bg-[#86545C] text-white px-5 py-2.5 rounded-xl font-bold text-sm transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:scale-[0.98]">
                    <span class="material-symbols-rounded text-lg">edit_square</span> Manage Products
                </a>
            </div>
        </div>
    </div>

    <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-400">
                <span class="material-symbols-rounded">forward_to_inbox</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">SMTP Configuration</h2>
                <p class="text-xs text-slate-400">Mail server credentials</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">SMTP Host</label>
                <input type="text" value="{{ config('mail.mailers.smtp.host') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors" disabled>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Port</label>
                    <input type="number" value="{{ config('mail.mailers.smtp.port') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors" disabled>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Encryption</label>
                    <select class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors appearance-none" disabled>
                        <option value="tls" {{ config('mail.mailers.smtp.encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ config('mail.mailers.smtp.encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Username (Email)</label>
                <input type="email" value="{{ config('mail.mailers.smtp.username') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors" disabled>
            </div>

            <button type="button" wire:click="testSmtp" wire:loading.attr="disabled" class="w-full py-3 bg-[#9E6B73] hover:bg-[#86545C] text-white text-sm font-bold rounded-xl transition-all duration-300 mt-3 flex items-center justify-center gap-2 shadow-lg shadow-black/20 active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed disabled:active:scale-100">
                <span wire:loading.remove wire:target="testSmtp">Test Connection</span>
                <span wire:loading wire:target="testSmtp">Sending Test Email...</span>
            </button>
        </div>
    </div>

    <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                <span class="material-symbols-rounded">admin_panel_settings</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">Environment & Security</h2>
                <p class="text-xs text-slate-400">System environment variables</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-700">
                <span class="block text-xs text-slate-400 mb-2">App Environment</span>
                <div class="relative">
                    <select wire:change="changeEnvironment($event.target.value)" class="w-full bg-slate-800 border border-slate-600 font-bold rounded-xl {{ $envColor }} focus:ring-[#9E6B73] focus:border-[#9E6B73] p-3 outline-none transition-colors appearance-none cursor-pointer">
                        <option value="development" class="text-amber-400" {{ $currentEnv === 'local' || $currentEnv === 'development' ? 'selected' : '' }}>Development Environment</option>
                        <option value="staging" class="text-blue-400" {{ $currentEnv === 'staging' ? 'selected' : '' }}>Staging Environment</option>
                        <option value="production" class="text-emerald-400" {{ $currentEnv === 'production' ? 'selected' : '' }}>Production Environment</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                        <span class="material-symbols-rounded">expand_more</span>
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-700 flex justify-between items-center">
                <div>
                    <span class="block text-xs text-slate-400">PHP Version</span>
                    <span class="block text-sm font-bold text-slate-200">{{ phpversion() }}</span>
                </div>
            </div>

            <form action="/logout" method="POST" class="w-full mt-2">
                @csrf
                <button type="submit" class="w-full py-3 border border-red-500/30 text-red-400 hover:bg-red-500/10 text-sm font-bold rounded-xl transition-colors flex justify-center items-center gap-2">
                    <span class="material-symbols-rounded text-lg">logout</span>
                    Force Logout All Sessions
                </button>
            </form>
        </div>
    </div>

</div>