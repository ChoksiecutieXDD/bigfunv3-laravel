<div
    x-data="{
        showBrevoQuotaInfo: false,
        showGoogleQuotaInfo: false,
        showLogViewer: false,
    }"
    @open-brevo-info.window="showBrevoQuotaInfo = true"
    @open-google-info.window="showGoogleQuotaInfo = true"
    @open-logs.window="showLogViewer = true"
    @execute-clear-cache.window="$wire.clearCache()"
    @execute-change-mailer.window="$wire.executeChangeMailer($event.detail.id)"
    @execute-change-environment.window="$wire.changeEnvironment($event.detail.id)"
    @execute-reset-quota.window="$wire.executeResetQuota($event.detail.id)"
    @execute-test-smtp.window="$wire.testSmtp()"
    @execute-test-google-smtp.window="$wire.testGoogleSmtp()"
    @execute-force-logout.window="$wire.forceLogout()">
    <div class="fixed top-[-20%] left-[-10%] w-[500px] h-[500px] bg-plum rounded-full blur-[150px] opacity-20 pointer-events-none z-0"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[600px] h-[600px] bg-blue-900 rounded-full blur-[150px] opacity-20 pointer-events-none z-0"></div>

    @if($isUnlocked)
    {{-- Log Viewer Modal --}}
    <div x-show="showLogViewer" x-cloak class="fixed inset-0 z-[150] flex items-center justify-center p-4">
        <div x-show="showLogViewer"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-950/90 backdrop-blur-xl"
            @click="showLogViewer = false"></div>

        <div x-show="showLogViewer"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-8"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-8"
            class="relative w-full max-w-5xl h-[80vh] rounded-[32px] bg-slate-900 border border-slate-700/50 flex flex-col shadow-2xl overflow-hidden z-[151]">
            
            <div class="shrink-0 p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-plum/10 flex items-center justify-center text-plum text-xl">
                        <span class="material-symbols-rounded">terminal</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Application Log</h3>
                        <p class="text-xs text-slate-500">Showing last 32KB of records</p>
                    </div>
                </div>
                <button type="button" @click="showLogViewer = false" class="w-10 h-10 rounded-xl hover:bg-slate-800 text-slate-400 flex items-center justify-center transition cursor-pointer">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>

            <div class="flex-grow overflow-auto p-6 bg-slate-950 font-mono text-[11px] leading-relaxed custom-scrollbar">
                <pre class="whitespace-pre-wrap text-slate-400">@php
                    $formattedLogs = collect(explode("\n", $logs))->map(function($line) {
                        if (str_contains($line, '.ERROR:')) return '<span class="text-red-400 font-bold">'.$line.'</span>';
                        if (str_contains($line, '.WARNING:')) return '<span class="text-amber-400 italic">'.$line.'</span>';
                        if (str_contains($line, '.INFO:')) return '<span class="text-blue-400">'.$line.'</span>';
                        return $line;
                    })->implode("\n");
                @endphp{!! $formattedLogs !!}</pre>
            </div>

            <div class="shrink-0 p-4 border-t border-slate-800 flex justify-end bg-slate-900/50">
                <button type="button" @click="showLogViewer = false" class="px-6 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 font-bold text-sm transition cursor-pointer">
                    Close Logs
                </button>
            </div>
        </div>
    </div>

    {{-- Quota Informational Modals --}}
    <div x-show="showBrevoQuotaInfo" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4">
        <div x-show="showBrevoQuotaInfo"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-950/80 backdrop-blur-md"
            @click="showBrevoQuotaInfo = false"></div>

        <div x-show="showBrevoQuotaInfo"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="relative w-full max-w-lg rounded-[24px] bg-slate-800 border border-slate-700 p-8 text-slate-300 shadow-2xl z-[81]">
            <h3 class="text-xl font-bold text-white mb-4">Brevo Quota Information</h3>
            <div class="space-y-4 text-sm text-slate-400 leading-relaxed">
                <p>
                    <strong class="text-white">Note:</strong> Your daily usage resets automatically every day at midnight.
                </p>
                @php
                    $brevoUsed = $brevoQuota['used'] ?? 0;
                    $brevoLimit = $brevoQuota['limit'] ?? 300;
                @endphp
                <p>
                    Your primary mailer is currently tracking <strong class="text-emerald-400">{{ $brevoUsed }}</strong> sent emails out of a daily limit of <strong class="text-white">{{ $brevoLimit }}</strong>. 
                    This limit is managed through your <code class="text-plum bg-slate-900 px-1 rounded font-mono">.env</code> configuration file.
                </p>
                <div class="p-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 text-xs text-emerald-400">
                    <span class="font-bold block mb-1">Administrative Note:</span>
                    To reset the daily counter, you can use the button below. This will update the system tracking back to zero.
                </div>
            </div>
            <div class="mt-8 flex justify-between items-center">
                <button type="button"
                    @click="showBrevoQuotaInfo = false; $dispatch('open-modal', { 
                        title: 'Reset Brevo Counter?', 
                        message: 'This will reset the tracked daily email usage back to zero in the system. Proceed?', 
                        type: 'warning', 
                        event: 'execute-reset-quota',
                        params: 'brevo'
                    })"
                    class="text-xs font-bold text-red-500 hover:text-red-400 transition underline underline-offset-4 cursor-pointer">
                    Reset Daily Counter
                </button>
                <button type="button" @click="showBrevoQuotaInfo = false" class="rounded-xl bg-slate-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-slate-600 transition cursor-pointer">Got it</button>
            </div>
        </div>
    </div>

    <div x-show="showGoogleQuotaInfo" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4">
        <div x-show="showGoogleQuotaInfo"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-950/80 backdrop-blur-md"
            @click="showGoogleQuotaInfo = false"></div>

        <div x-show="showGoogleQuotaInfo"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="relative w-full max-w-lg rounded-[24px] bg-slate-800 border border-slate-700 p-8 text-slate-300 shadow-2xl z-[81]">
            <h3 class="text-xl font-bold text-white mb-4">Gmail Quota Information</h3>
            <div class="space-y-4 text-sm text-slate-400 leading-relaxed">
                <p>
                    <strong class="text-white">Note:</strong> Your daily usage resets automatically every day at midnight.
                </p>
                @php
                    $googleUsed = $googleQuota['used'] ?? 0;
                    $googleLimit = $googleQuota['limit'] ?? 500;
                @endphp
                <p>
                    Your secondary Gmail mailer is currently tracking <strong class="text-blue-400">{{ $googleUsed }}</strong> sent emails out of a daily limit of <strong class="text-white">{{ $googleLimit }}</strong>. 
                </p>
                <div class="p-4 rounded-2xl border border-blue-500/20 bg-blue-500/5 text-xs text-blue-400">
                    <span class="font-bold block mb-1">Administrative Note:</span>
                    To reset the daily counter, you can use the button below. This will update the system tracking back to zero.
                </div>
            </div>
            <div class="mt-8 flex justify-between items-center">
                <button type="button"
                    @click="showGoogleQuotaInfo = false; $dispatch('open-modal', { 
                        title: 'Reset Gmail Counter?', 
                        message: 'This will reset the tracked daily email usage back to zero in the system. Proceed?', 
                        type: 'warning', 
                        event: 'execute-reset-quota',
                        params: 'google'
                    })"
                    class="text-xs font-bold text-red-500 hover:text-red-400 transition underline underline-offset-4 cursor-pointer">
                    Reset Daily Counter
                </button>
                <button type="button" @click="showGoogleQuotaInfo = false" class="rounded-xl bg-slate-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-slate-600 transition cursor-pointer">Got it</button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10 p-4 md:p-6">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    <span class="material-symbols-rounded text-plum text-4xl">settings_system_daydream</span>
                    System Configuration
                </h1>
                <div class="flex items-center gap-3 mt-1.5">
                    <p class="text-slate-400 text-sm">Manage core settings and health.</p>
                    <span class="text-slate-600">•</span>
                    <a href="https://bigfunbooking.online/" target="_blank" class="text-plum hover:text-plum-dark text-xs font-bold flex items-center gap-1 transition">
                        <span class="material-symbols-rounded text-sm">language</span>
                        bigfunbooking.online
                    </a>
                </div>
            </div>
            <button type="button" wire:click="lockSystem" class="shrink-0 px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors flex items-center gap-2 text-sm font-semibold cursor-pointer">
                <span class="material-symbols-rounded text-lg">arrow_back</span>
                Return to App
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">

            @php
            $displayEnv = $currentEnv ?: config('app.env');
            $envColor = match($displayEnv) {
            'local', 'development' => 'text-amber-400',
            'staging' => 'text-blue-400',
            'production' => 'text-emerald-400',
            default => 'text-slate-400'
            };
            @endphp

            <!-- 1. DATA MANAGEMENT -->
            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400 shrink-0">
                        <span class="material-symbols-rounded">database</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Data Management</h2>
                        <p class="text-xs text-slate-400">Manage records and backups</p>
                    </div>
                </div>

                <div class="space-y-4 flex-grow">
                    <a href="/system/db-view" wire:navigate class="w-full min-h-[76px] flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-plum hover:bg-slate-800 transition-all group cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400 group-hover:text-plum transition-colors">table_view</span>
                            <span class="font-medium text-slate-200">Database Viewer</span>
                        </div>
                        <span class="material-symbols-rounded text-slate-500 group-hover:text-white">chevron_right</span>
                    </a>

                    <button wire:click="exportDb" class="w-full min-h-[76px] flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-emerald-500/50 hover:bg-slate-800 transition-all group disabled:opacity-50 cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400 group-hover:text-emerald-400 transition-colors">download</span>
                            <span class="font-medium text-slate-200" wire:loading.remove wire:target="exportDb">Export Full Backup (.sql)</span>
                            <span class="font-medium text-emerald-400" wire:loading wire:target="exportDb">Generating Backup...</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- 2. SYSTEM MAINTENANCE -->
            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-full bg-plum/10 flex items-center justify-center text-plum shrink-0">
                        <span class="material-symbols-rounded">memory</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">System Maintenance</h2>
                        <p class="text-xs text-slate-400">Performance and availability</p>
                    </div>
                </div>

                <div class="space-y-4 flex-grow">
                    <button type="button"
                        x-data
                        @click="$dispatch('open-modal', { 
                            title: 'Clear System Cache?', 
                            message: 'This will clear compiled views, config, and route caches. Proceed?', 
                            type: 'warning', 
                            event: 'execute-clear-cache' 
                        })"
                        class="w-full min-h-[76px] flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-plum hover:bg-slate-800 transition-all group disabled:opacity-50 cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400 group-hover:text-plum transition-colors">cleaning_services</span>
                            <div class="text-left">
                                <span class="font-medium text-slate-200 block" wire:loading.remove wire:target="clearCache">Clear System Cache</span>
                                <span class="font-medium text-plum block" wire:loading wire:target="clearCache">Clearing...</span>
                                <span class="text-xs text-slate-500 block">Frees up temporary server files</span>
                            </div>
                        </div>
                    </button>

                    <div class="w-full min-h-[76px] flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700">
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

            <!-- 3. BUSINESS MODULES (FULL WIDTH) -->
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

            <!-- 4. SMTP CONFIGURATION & ENVIRONMENT (SINGLE COLUMN STACK) -->
            <div class="lg:col-span-2 grid grid-cols-1 gap-6">
                <!-- DEFAULT MAILER CHOICE -->
                <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-6 md:p-8 rounded-3xl shadow-xl w-full">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-violet-500/10 flex items-center justify-center text-violet-400 shrink-0">
                            <span class="material-symbols-rounded text-[22px]">toggle_on</span>
                        </div>
                        <div>
                            <h2 id="default-mailer-heading" class="text-lg font-bold text-white">Default outbound mailer</h2>
                            <p class="text-xs text-slate-400">Pick one option. Laravel’s <code class="text-slate-500">MAIL_MAILER</code> in <code class="text-slate-500">.env</code> is updated to <code class="text-slate-500">smtp</code> (Brevo) or <code class="text-slate-500">google</code> (Gmail).</p>
                        </div>
                    </div>
                    @php $activeMail = (string) config('mail.default'); @endphp
                    @if(! in_array($activeMail, ['smtp', 'google'], true))
                    <p class="text-amber-400/90 text-xs mb-4 rounded-xl border border-amber-500/25 bg-amber-500/5 px-3 py-2">Outbound mailer is currently <strong class="text-amber-200">{{ $activeMail }}</strong>. Select Brevo or Gmail below to route app mail through that SMTP.</p>
                    @endif
                    <fieldset
                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                        wire:target="defaultMailer"
                        wire:loading.class="opacity-60 pointer-events-none"
                        role="radiogroup"
                        aria-labelledby="default-mailer-heading">
                        <legend class="sr-only">Default mail transport</legend>
                        {{-- Large native radios (accent + size) so the control reads clearly as a radio group in the browser. --}}
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-all border-slate-700 bg-slate-900/40 hover:border-slate-600 has-[:checked]:border-emerald-500/60 has-[:checked]:bg-emerald-500/10 has-[:checked]:ring-1 has-[:checked]:ring-emerald-500/35">
                            <input
                                type="radio"
                                name="default_outbound_mailer"
                                value="smtp"
                                x-data
                                @change="$dispatch('open-modal', { 
                                    title: 'Switch to Brevo?', 
                                    message: 'Changing the default outbound mailer will update system configuration and requires a full server refresh. Proceed?', 
                                    type: 'warning', 
                                    event: 'execute-change-mailer',
                                    params: 'smtp'
                                }); $event.target.checked = false;"
                                :checked="'{{ $defaultMailer }}' === 'smtp'"
                                class="mt-1 size-5 shrink-0 cursor-pointer rounded-full border-2 border-slate-600 bg-slate-900 text-emerald-500 accent-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-100">Primary — Brevo</span>
                                <span class="mt-0.5 block text-xs text-slate-400">Config mailer: <span class="font-mono text-slate-500">smtp</span> · host <span class="font-mono text-slate-500">smtp-relay.brevo.com</span></span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-all border-slate-700 bg-slate-900/40 hover:border-slate-600 has-[:checked]:border-blue-500/60 has-[:checked]:bg-blue-500/10 has-[:checked]:ring-1 has-[:checked]:ring-blue-500/35">
                            <input
                                type="radio"
                                name="default_outbound_mailer"
                                value="google"
                                x-data
                                @change="$dispatch('open-modal', { 
                                    title: 'Switch to Gmail?', 
                                    message: 'Changing the default outbound mailer will update system configuration and requires a full server refresh. Proceed?', 
                                    type: 'warning', 
                                    event: 'execute-change-mailer',
                                    params: 'google'
                                }); $event.target.checked = false;"
                                :checked="'{{ $defaultMailer }}' === 'google'"
                                class="mt-1 size-5 shrink-0 cursor-pointer rounded-full border-2 border-slate-600 bg-slate-900 text-blue-500 accent-blue-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-100">Secondary — Gmail</span>
                                <span class="mt-0.5 block text-xs text-slate-400">Config mailer: <span class="font-mono text-slate-500">google</span> · typical host <span class="font-mono text-slate-500">smtp.gmail.com</span></span>
                            </span>
                        </label>
                    </fieldset>
                    <p class="mt-3 text-[11px] leading-relaxed text-slate-500">Credentials are read from <code class="text-slate-600">.env</code> only (never stored in this page). Brevo: <code class="text-slate-600">MAIL_HOST</code>, <code class="text-slate-600">MAIL_USERNAME</code>, <code class="text-slate-600">MAIL_PASSWORD</code>, <code class="text-slate-600">MAIL_BREVO_SMTP_KEY_NAME</code>. Gmail: <code class="text-slate-600">MAIL_GOOGLE_*</code>.</p>
                </div>

                <!-- PRIMARY SMTP (BREVO) -->
                @php
                $brevoUsed = $brevoQuota['used'];
                $brevoLimit = $brevoQuota['limit'];
                $brevoRemaining = $brevoQuota['remaining'];
                $brevoKeyName = config('mail.brevo.smtp_key_name');
                @endphp
                <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-400 shrink-0">
                                <span class="material-symbols-rounded">forward_to_inbox</span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">SMTP Configuration</h2>
                                <p class="text-xs text-slate-400">Primary Mail Server (Brevo)</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
                            @if($defaultMailer === 'smtp')
                            <div class="inline-flex items-center gap-1 bg-violet-500/15 text-violet-300 px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider border border-violet-500/25 min-w-[80px] justify-center" title="This mailer is selected as default for the app">
                                <span class="material-symbols-rounded text-sm leading-none">check_circle</span>
                                In use
                            </div>
                            @endif
                            <div class="inline-flex items-center gap-2 bg-emerald-500/10 text-emerald-400 px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider border border-emerald-500/20 min-w-[80px] justify-center">
                                Primary
                            </div>
                            @if($brevoKeyName)
                            <div class="inline-flex items-center gap-1.5 bg-amber-500/10 text-amber-300 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide border border-amber-500/20 max-w-[180px] truncate" title="{{ $brevoKeyName }}">
                                Key: {{ $brevoKeyName }}
                            </div>
                            @endif
                            <div class="inline-flex items-center gap-1.5 bg-slate-600/30 text-slate-200 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide border border-slate-500/30 min-w-[90px] justify-center">
                                <span class="text-slate-400 font-semibold uppercase">Daily</span>
                                <span>{{ $brevoUsed }} / {{ $brevoLimit }}</span>
                            </div>
                            <button type="button" @click="showBrevoQuotaInfo = true" class="inline-flex items-center gap-1.5 bg-slate-700/40 text-slate-200 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide border border-slate-600/30 hover:border-slate-500/60 transition min-w-[95px] justify-center cursor-pointer" title="Daily resets at midnight">
                                <span class="material-symbols-rounded text-sm leading-none">info</span>
                                Quota Info
                            </button>
                        </div>
                    </div>
                    <p class="mb-5 text-[11px] text-slate-400">Remaining today: <span class="font-semibold text-slate-300">{{ $brevoRemaining }}</span> credits.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">SMTP Host</label>
                                <input type="text" value="{{ config('mail.mailers.smtp.host') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none" disabled>
                            </div>
                            <div class="grid grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Port</label>
                                    <input type="number" value="{{ config('mail.mailers.smtp.port') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none" disabled>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Encryption</label>
                                    <input type="text" readonly value="{{ strtoupper(config('mail.mailers.smtp.encryption') ?: 'TLS') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none uppercase cursor-default" tabindex="-1">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Username (Email)</label>
                                <input type="email" value="{{ config('mail.mailers.smtp.username') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none" disabled>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">SMTP Key (Token)</label>
                                <input type="password" value="********" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none" disabled autocomplete="new-password" readonly>
                            </div>
                        </div>
                    </div>

                    <button type="button" 
                        x-data
                        @click="$dispatch('open-modal', { 
                            title: 'Test Primary Connection?', 
                            message: 'This will send a test email to {{ config('mail.from.address') }} using the current Brevo configuration. Proceed?', 
                            type: 'info', 
                            event: 'execute-test-smtp' 
                        })"
                        wire:loading.attr="disabled" 
                        class="w-full py-3 bg-plum hover:bg-plum-dark text-white text-sm font-bold rounded-xl transition-all duration-300 mt-6 flex items-center justify-center gap-2 shadow-lg shadow-plum/20 cursor-pointer">
                        <span wire:loading.remove wire:target="testSmtp">Test Primary Connection</span>
                        <span wire:loading wire:target="testSmtp">Sending Test Email...</span>
                    </button>
                </div>

                <!-- SECONDARY SMTP (GOOGLE) -->
                @php
                $googleUsed = $googleQuota['used'];
                $googleLimit = $googleQuota['limit'];
                $googleRemaining = $googleQuota['remaining'];
                @endphp
                <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400 shrink-0">
                                <span class="material-symbols-rounded">forward_to_inbox</span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">Secondary Configuration</h2>
                                <p class="text-xs text-slate-400">Google App Password (Secondary)</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
                            @if($defaultMailer === 'google')
                            <div class="inline-flex items-center gap-1 bg-violet-500/15 text-violet-300 px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider border border-violet-500/25 min-w-[80px] justify-center" title="This mailer is selected as default for the app">
                                <span class="material-symbols-rounded text-sm leading-none">check_circle</span>
                                In use
                            </div>
                            @endif
                            <div class="inline-flex items-center gap-2 bg-slate-500/10 text-slate-400 px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider border border-slate-500/20 min-w-[80px] justify-center">
                                Secondary
                            </div>
                            <div class="inline-flex items-center gap-1.5 bg-slate-600/30 text-slate-200 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide border border-slate-500/30 min-w-[90px] justify-center">
                                <span class="text-slate-400 font-semibold uppercase">Daily</span>
                                <span>{{ $googleUsed }} / {{ $googleLimit }}</span>
                            </div>
                            <button type="button" @click="showGoogleQuotaInfo = true" class="inline-flex items-center gap-1.5 bg-slate-700/40 text-slate-200 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide border border-slate-600/30 hover:border-slate-500/60 transition min-w-[95px] justify-center cursor-pointer" title="Daily resets at midnight">
                                <span class="material-symbols-rounded text-sm leading-none">info</span>
                                Quota Info
                            </button>
                        </div>
                    </div>
                    <p class="mb-5 text-[11px] text-slate-400">Remaining today: <span class="font-semibold text-slate-300">{{ $googleRemaining }}</span> credits.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">SMTP Host</label>
                                <input type="text" value="{{ config('mail.mailers.google.host') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none" disabled>
                            </div>
                            <div class="grid grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Port</label>
                                    <input type="number" value="{{ config('mail.mailers.google.port') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none" disabled>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Encryption</label>
                                    <input type="text" readonly value="{{ strtoupper(config('mail.mailers.google.encryption') ?: 'TLS') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none uppercase cursor-default" tabindex="-1">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Username (Email)</label>
                                <input type="email" value="{{ config('mail.mailers.google.username') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none" disabled>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">App Password (Token)</label>
                                <input type="password" value="********" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl p-3 outline-none" disabled autocomplete="new-password" readonly>
                            </div>
                        </div>
                    </div>

                    <button type="button" 
                        x-data
                        @click="$dispatch('open-modal', { 
                            title: 'Test Secondary Connection?', 
                            message: 'This will send a test email to {{ config('mail.from.address') }} using the current Google configuration. Proceed?', 
                            type: 'info', 
                            event: 'execute-test-google-smtp' 
                        })"
                        wire:loading.attr="disabled" 
                        class="w-full py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold rounded-xl transition-all duration-300 mt-6 flex items-center justify-center gap-2 shadow-lg active:scale-[0.98] cursor-pointer">
                        <span wire:loading.remove wire:target="testGoogleSmtp">Test Secondary Connection</span>
                        <span wire:loading wire:target="testGoogleSmtp">Sending Test Email...</span>
                    </button>
                </div>

                <!-- ENVIRONMENT & SECURITY -->
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-700">
                                <span class="block text-xs text-slate-400 mb-2">App Environment</span>
                                <div class="relative" wire:key="env-select-{{ $currentEnv }}">
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
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-700 flex justify-between items-center h-full">
                                <div>
                                    <span class="block text-xs text-slate-400">PHP Version</span>
                                    <span class="block text-sm font-bold text-slate-200">{{ phpversion() }}</span>
                                </div>
                            </div>
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
                        class="w-full mt-6 py-3 border border-red-500/30 text-red-400 hover:bg-red-500/10 text-sm font-bold rounded-xl transition-colors flex justify-center items-center gap-2 cursor-pointer">
                        <span class="material-symbols-rounded text-lg">logout</span>
                        Force Logout All Sessions
                    </button>
                </div>
            </div>

            <!-- 5. MONITORING & HEALTH -->
            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full flex flex-col">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400 shrink-0">
                            <span class="material-symbols-rounded">monitor_heart</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Health Pulse</h2>
                            <p class="text-xs text-slate-400">Real-time service status</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    @foreach([
                        ['label' => 'Database', 'key' => 'db'],
                        ['label' => 'Cache System', 'key' => 'cache'],
                        ['label' => 'Google Webhook', 'key' => 'webhook'],
                        ['label' => 'Public Site', 'key' => 'public_site'],
                    ] as $item)
                    <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-700 flex flex-col gap-2">
                        <span class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500">{{ $item['label'] }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full {{ ($stats['health'][$item['key']] ?? false) ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]' : 'bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.5)]' }}"></div>
                            <span class="text-xs font-bold {{ ($stats['health'][$item['key']] ?? false) ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ ($stats['health'][$item['key']] ?? false) ? 'CONNECTED' : 'DISCONNECTED' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="space-y-4">
                    <button wire:click="openLogViewer" class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-plum hover:bg-slate-800 transition-all group cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400 group-hover:text-plum transition-colors">description</span>
                            <span class="font-medium text-slate-200">Application Log Viewer</span>
                        </div>
                        <div class="px-2 py-1 rounded-lg bg-slate-800 text-[10px] font-bold text-slate-500 group-hover:text-slate-300">VIEW LATEST</div>
                    </button>
                </div>
            </div>

            <!-- 6. RESOURCES & QUEUES -->
            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 p-8 rounded-3xl shadow-xl w-full flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-400 shrink-0">
                        <span class="material-symbols-rounded">speed</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Resources & Queues</h2>
                        <p class="text-xs text-slate-400">System capacity & jobs</p>
                    </div>
                </div>

                <div class="space-y-6">
                    {{-- Disk Usage --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-slate-400">DISK SPACE ({{ $stats['disk']['used'] ?? '0' }} / {{ $stats['disk']['total'] ?? '0' }})</span>
                            <span class="text-xs font-bold text-white">{{ $stats['disk']['percent'] ?? '0' }}%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-900 rounded-full overflow-hidden" 
                             style="--disk-progress: {{ $stats['disk']['percent'] ?? 0 }}%">
                            <div class="h-full bg-gradient-to-r from-plum to-plum-dark transition-all duration-1000" 
                                 style="width: var(--disk-progress)"></div>
                        </div>
                    </div>

                    {{-- Versions --}}
                    <div class="flex gap-4">
                        <div class="flex-1 p-3 rounded-xl bg-slate-900/50 border border-slate-700 flex flex-col items-center">
                            <span class="text-[9px] uppercase font-bold text-slate-500 mb-1">Laravel</span>
                            <span class="text-sm font-bold text-white">v{{ $stats['versions']['laravel'] ?? '?' }}</span>
                        </div>
                        <div class="flex-1 p-3 rounded-xl bg-slate-900/50 border border-slate-700 flex flex-col items-center">
                            <span class="text-[9px] uppercase font-bold text-slate-500 mb-1">PHP</span>
                            <span class="text-sm font-bold text-white">v{{ $stats['versions']['php'] ?? '?' }}</span>
                        </div>
                    </div>

                    {{-- Queues --}}
                    <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-700">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-xs font-extrabold text-slate-200">BACKGROUND JOBS</span>
                            <button type="button" 
                                wire:click="retryFailedJobs" 
                                @disabled(($stats['queue']['failed'] ?? 0) == 0) 
                                class="text-[10px] font-bold text-plum hover:text-plum-dark disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer">
                                RETRY FAILED
                            </button>
                        </div>
                        <div class="flex gap-8">
                            <div class="flex flex-col">
                                <span class="text-2xl font-black text-white">{{ $stats['queue']['pending'] ?? 0 }}</span>
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pending</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-black {{ ($stats['queue']['failed'] ?? 0) > 0 ? 'text-red-400' : 'text-slate-400' }}">{{ $stats['queue']['failed'] ?? 0 }}</span>
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Failed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    @else
    {{-- LOCKED STATE --}}
    <div class="min-h-screen flex flex-col items-center justify-center p-4 relative z-10">
        <div class="w-24 h-24 rounded-3xl bg-plum/10 text-plum flex items-center justify-center mb-8 animate-bounce">
            <span class="material-symbols-rounded text-5xl font-bold">lock</span>
        </div>
        
        <div class="w-full max-w-md bg-slate-800/40 backdrop-blur-2xl border border-slate-700/50 p-10 rounded-[40px] shadow-2xl">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-black text-white mb-2 uppercase tracking-tight">System Locked</h1>
                <p class="text-slate-400 text-sm font-medium">Please enter administrative password to continue</p>
            </div>

            <form wire:submit="unlockSystem" class="space-y-6">
                <div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="material-symbols-rounded text-slate-500 group-focus-within:text-plum transition-colors">key</span>
                        </div>
                        <input type="password" 
                            wire:model="systemPassword"
                            class="block w-full pl-12 pr-4 py-4 bg-slate-900/50 border border-slate-700 rounded-2xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-plum/50 focus:border-plum transition-all text-center tracking-[0.5em] font-bold" 
                            placeholder="••••••••" 
                            required>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="w-full py-4 bg-plum hover:bg-plum-dark text-white font-black rounded-2xl transition-all shadow-xl shadow-plum/20 transform active:scale-95 uppercase tracking-widest text-xs flex items-center justify-center gap-2">
                        <span class="material-symbols-rounded text-base">verified_user</span>
                        Authorize Access
                    </button>
                    
                    <a href="/" wire:navigate class="w-full py-4 bg-slate-700/30 hover:bg-slate-700/50 text-slate-400 hover:text-white font-bold rounded-2xl transition-all text-center uppercase tracking-widest text-[10px]">
                        Return to Dashboard
                    </a>
                </div>
            </form>
        </div>

        <p class="mt-8 text-slate-600 text-[10px] font-bold uppercase tracking-[0.2em]">BigFun Entertainment · System v3.0</p>
    </div>
    @endif
</div>