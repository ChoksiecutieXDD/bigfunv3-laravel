<div
    @execute-clear-cache.window="$wire.clearCache()"
    @execute-change-environment.window="$wire.changeEnvironment($event.detail.id)"
    @execute-force-logout.window="$wire.forceLogout()">
    <div class="fixed top-[-20%] left-[-10%] w-[500px] h-[500px] bg-plum rounded-full blur-[150px] opacity-20 pointer-events-none z-0"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[600px] h-[600px] bg-blue-900 rounded-full blur-[150px] opacity-20 pointer-events-none z-0"></div>

    <div class="max-w-7xl mx-auto relative z-10 p-4 md:p-6">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    <span class="material-symbols-rounded text-plum text-4xl">settings_system_daydream</span>
                    System Configuration
                </h1>
                <p class="text-slate-400 mt-1">Manage core application settings, databases, and mail servers.</p>
            </div>
            <a href="/" wire:navigate class="shrink-0 px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors flex items-center gap-2 text-sm font-semibold">
                <span class="material-symbols-rounded text-lg">arrow_back</span>
                Return to App
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">

            @php
            $envColor = match($currentEnv) {
            'local', 'development' => 'text-amber-400',
            'staging' => 'text-blue-400',
            default => 'text-emerald-400'
            };
            @endphp

            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400 shrink-0">
                        <span class="material-symbols-rounded">database</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Data Management</h2>
                        <p class="text-xs text-slate-400">Manage records and backups</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <a href="/system/db-view" wire:navigate class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-plum hover:bg-slate-800 transition-all group cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400 group-hover:text-plum transition-colors">table_view</span>
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

            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-full bg-plum/10 flex items-center justify-center text-plum shrink-0">
                        <span class="material-symbols-rounded">memory</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">System Maintenance</h2>
                        <p class="text-xs text-slate-400">Performance and availability</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <button type="button"
                        x-data
                        @click="$dispatch('open-modal', { 
                            title: 'Clear System Cache?', 
                            message: 'This will clear compiled views, config, and route caches. Proceed?', 
                            type: 'warning', 
                            event: 'execute-clear-cache' 
                        })"
                        class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-plum hover:bg-slate-800 transition-all group disabled:opacity-50">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400 group-hover:text-plum transition-colors">cleaning_services</span>
                            <div class="text-left">
                                <span class="font-medium text-slate-200 block" wire:loading.remove wire:target="clearCache">Clear System Cache</span>
                                <span class="font-medium text-plum block" wire:loading wire:target="clearCache">Clearing...</span>
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
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" wire:change="toggleMaintenance" class="sr-only peer" {{ $isMaintenance ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full lg:col-span-2">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-400 shrink-0">
                        <span class="material-symbols-rounded">inventory_2</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Business Modules</h2>
                        <p class="text-xs text-slate-400">Manage application content</p>
                    </div>
                </div>

                <div class="space-y-6 w-full">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900/50 border border-slate-700 w-full">
                        <div>
                            <h3 class="font-bold text-slate-200">Product Management</h3>
                            <p class="text-xs text-slate-400 mt-1">Add new rides, update pricing, or edit existing inventory items.</p>
                        </div>
                        <a href="/system/inventory" wire:navigate class="flex shrink-0 items-center gap-2 bg-plum hover:bg-plum-dark text-white px-6 py-3 rounded-xl font-bold text-sm transition shadow-lg shadow-plum/20 transform hover:-translate-y-0.5 active:scale-[0.98]">
                            <span class="material-symbols-rounded text-lg">edit_square</span> Manage Products
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-400 shrink-0">
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
                        <input type="text" value="{{ config('mail.mailers.smtp.host') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-plum focus:border-plum block p-3 outline-none transition-colors" disabled>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Port</label>
                            <input type="number" value="{{ config('mail.mailers.smtp.port') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-plum focus:border-plum block p-3 outline-none transition-colors" disabled>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Encryption</label>
                            <select class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-plum focus:border-plum block p-3 outline-none transition-colors appearance-none" disabled>
                                <option value="tls" {{ config('mail.mailers.smtp.encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ config('mail.mailers.smtp.encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Username (Email)</label>
                        <input type="email" value="{{ config('mail.mailers.smtp.username') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-plum focus:border-plum block p-3 outline-none transition-colors" disabled>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">App Password (Temporary)</label>
                        <input type="password" value="{{ config('mail.mailers.smtp.password') }}" placeholder="********" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-plum focus:border-plum block p-3 outline-none transition-colors" disabled>
                    </div>

                    <button type="button" wire:click="testSmtp" wire:loading.attr="disabled" class="w-full py-3 bg-plum hover:bg-plum-dark text-white text-sm font-bold rounded-xl transition-all duration-300 mt-3 flex items-center justify-center gap-2 shadow-lg shadow-plum/20 active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed disabled:active:scale-100">
                        <span wire:loading.remove wire:target="testSmtp">Test Connection</span>
                        <span wire:loading wire:target="testSmtp">Sending Test Email...</span>
                    </button>
                </div>
            </div>

            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400 shrink-0">
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
                            <select
                                x-data
                                @change="$dispatch('open-modal', { 
                                    title: 'Change Environment?', 
                                    message: 'Changing the environment can affect database connections and error reporting. Proceed?', 
                                    type: 'warning', 
                                    event: 'execute-change-environment',
                                    params: $event.target.value
                                }); $event.target.value = '{{ $currentEnv }}';"
                                class="w-full bg-slate-800 border border-slate-600 font-bold rounded-xl {{ $envColor }} focus:ring-plum focus:border-plum p-3 outline-none transition-colors appearance-none cursor-pointer">
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

                    <button type="button"
                        x-data
                        @click="$dispatch('open-modal', { 
                            title: 'Force Logout All Sessions?', 
                            message: 'This will instantly log out every user on all devices. You will need to log back in immediately. Proceed?', 
                            type: 'danger', 
                            event: 'execute-force-logout' 
                        })"
                        class="w-full mt-2 py-3 border border-red-500/30 text-red-400 hover:bg-red-500/10 text-sm font-bold rounded-xl transition-colors flex justify-center items-center gap-2">
                        <span class="material-symbols-rounded text-lg">logout</span>
                        Force Logout All Sessions
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>