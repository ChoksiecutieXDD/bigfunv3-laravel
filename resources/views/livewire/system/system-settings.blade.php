<div
    x-data="{
        showBrevoQuotaInfo: false,
        showGoogleQuotaInfo: false,
    }"
    @execute-clear-cache.window="$wire.clearCache()"
    @execute-change-mailer.window="$wire.executeChangeMailer($event.detail.params)"
    @execute-change-environment.window="$wire.changeEnvironment($event.detail.id)"
    @execute-reset-quota.window="$wire.executeResetQuota($event.detail.params)"
    @execute-test-smtp.window="$wire.testSmtp()"
    @execute-test-google-smtp.window="$wire.testGoogleSmtp()"
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

                    <button wire:click="exportDb" class="w-full min-h-[76px] flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-emerald-500/50 hover:bg-slate-800 transition-all group disabled:opacity-50">
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
                        class="w-full min-h-[76px] flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-plum hover:bg-slate-800 transition-all group disabled:opacity-50">
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
                $brevoUsed = config('mail.brevo.daily_email_used');
                $brevoLimit = config('mail.brevo.daily_email_limit');
                $brevoKeyName = config('mail.brevo.smtp_key_name');
                $brevoUsed = is_numeric($brevoUsed) ? (int) $brevoUsed : 0;
                $brevoLimit = is_numeric($brevoLimit) ? max(1, (int) $brevoLimit) : 300;
                $brevoRemaining = max(0, $brevoLimit - $brevoUsed);
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
                            <button type="button" @click="showBrevoQuotaInfo = true" class="inline-flex items-center gap-1.5 bg-slate-700/40 text-slate-200 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide border border-slate-600/30 hover:border-slate-500/60 transition min-w-[95px] justify-center">
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
                        class="w-full py-3 bg-plum hover:bg-plum-dark text-white text-sm font-bold rounded-xl transition-all duration-300 mt-6 flex items-center justify-center gap-2 shadow-lg shadow-plum/20">
                        <span wire:loading.remove wire:target="testSmtp">Test Primary Connection</span>
                        <span wire:loading wire:target="testSmtp">Sending Test Email...</span>
                    </button>
                </div>

                <!-- SECONDARY SMTP (GOOGLE) -->
                @php
                $googleUsed = config('mail.google_quota.daily_email_used');
                $googleLimit = config('mail.google_quota.daily_email_limit');
                $googleUsed = is_numeric($googleUsed) ? (int) $googleUsed : 0;
                $googleLimit = is_numeric($googleLimit) ? max(1, (int) $googleLimit) : 500;
                $googleRemaining = max(0, $googleLimit - $googleUsed);
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
                            <button type="button" @click="showGoogleQuotaInfo = true" class="inline-flex items-center gap-1.5 bg-slate-700/40 text-slate-200 px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide border border-slate-600/30 hover:border-slate-500/60 transition min-w-[95px] justify-center">
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
                        class="w-full py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold rounded-xl transition-all duration-300 mt-6 flex items-center justify-center gap-2 shadow-lg active:scale-[0.98]">
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
                        class="w-full mt-6 py-3 border border-red-500/30 text-red-400 hover:bg-red-500/10 text-sm font-bold rounded-xl transition-colors flex justify-center items-center gap-2">
                        <span class="material-symbols-rounded text-lg">logout</span>
                        Force Logout All Sessions
                    </button>
                </div>
            </div>

        </div>
    </div>

    <div x-teleport="body">
        <div x-show="showBrevoQuotaInfo" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
            <div x-show="showBrevoQuotaInfo"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showBrevoQuotaInfo = false"></div>

            <div x-show="showBrevoQuotaInfo"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative w-full max-w-lg rounded-[24px] bg-white p-8 text-slate-600 shadow-2xl z-[10001]">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Brevo Quota Information</h3>
                <div class="space-y-4 text-sm text-slate-500 leading-relaxed">
                    <p>
                        Your primary mailer is currently tracking <strong>{{ $brevoUsed }}</strong> sent emails out of a daily limit of <strong>{{ $brevoLimit }}</strong>. 
                        This limit is managed through your <code class="text-pink-600 bg-slate-50 px-1 rounded font-mono">.env</code> configuration file using the 
                        <code class="text-pink-600 bg-slate-50 px-1 rounded font-mono">MAIL_BREVO_DAILY_EMAIL_LIMIT</code> variable.
                    </p>
                    <p>
                        The daily usage is tracked by the <code class="text-pink-600 bg-slate-50 px-1 rounded font-mono">MAIL_BREVO_DAILY_EMAIL_USED</code> key. 
                        If you reach the limit, the system will prevent further automated emails to avoid deliverability issues.
                    </p>
                    <div class="p-4 rounded-2xl border border-emerald-100 bg-emerald-50 text-xs text-emerald-700">
                        <span class="font-bold block mb-1">Administrative Note:</span>
                        To reset the daily counter, you can use the button below. This will update the system tracking back to zero and clear the configuration cache.
                    </div>
                </div>
                <div class="mt-8 flex justify-between items-center">
                    <button type="button"
                        @click="$dispatch('open-modal', { 
                            title: 'Reset Brevo Counter?', 
                            message: 'This will reset the tracked daily email usage back to zero in the system. Proceed?', 
                            type: 'warning', 
                            event: 'execute-reset-quota',
                            params: 'brevo'
                        })"
                        class="text-xs font-bold text-red-500 hover:text-red-600 transition underline underline-offset-4">
                        Reset Daily Counter
                    </button>
                    <button type="button" @click="showBrevoQuotaInfo = false" class="rounded-xl bg-slate-100 px-6 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200 transition">Got it</button>
                </div>
            </div>
        </div>

        <div x-show="showGoogleQuotaInfo" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
            <div x-show="showGoogleQuotaInfo"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="showGoogleQuotaInfo = false"></div>

            <div x-show="showGoogleQuotaInfo"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative w-full max-w-lg rounded-[24px] bg-white p-8 text-slate-600 shadow-2xl z-[10001]">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Gmail Quota Information</h3>
                <div class="space-y-4 text-sm text-slate-500 leading-relaxed">
                    <p>
                        Your secondary Gmail mailer is currently tracking <strong>{{ $googleUsed }}</strong> sent emails out of a daily limit of <strong>{{ $googleLimit }}</strong>. 
                        This limit is defined by the <code class="text-pink-600 bg-slate-50 px-1 rounded font-mono">MAIL_GOOGLE_DAILY_EMAIL_LIMIT</code> variable in your configuration.
                    </p>
                    <p>
                        The daily usage is tracked by the <code class="text-pink-600 bg-slate-50 px-1 rounded font-mono">MAIL_GOOGLE_DAILY_EMAIL_USED</code> key. 
                        Gmail typically has strict outbound limits, so ensure this value stays within your account's allowed throughput.
                    </p>
                    <div class="p-4 rounded-2xl border border-emerald-100 bg-emerald-50 text-xs text-emerald-700">
                        <span class="font-bold block mb-1">Administrative Note:</span>
                        To reset the daily counter, you can use the button below. This will update the system tracking back to zero and clear the configuration cache.
                    </div>
                </div>
                <div class="mt-8 flex justify-between items-center">
                    <button type="button"
                        @click="$dispatch('open-modal', { 
                            title: 'Reset Gmail Counter?', 
                            message: 'This will reset the tracked daily email usage back to zero in the system. Proceed?', 
                            type: 'warning', 
                            event: 'execute-reset-quota',
                            params: 'google'
                        })"
                        class="text-xs font-bold text-red-500 hover:text-red-600 transition underline underline-offset-4">
                        Reset Daily Counter
                    </button>
                    <button type="button" @click="showGoogleQuotaInfo = false" class="rounded-xl bg-slate-100 px-6 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200 transition">Got it</button>
                </div>
            </div>
        </div>
    </div>
</div>