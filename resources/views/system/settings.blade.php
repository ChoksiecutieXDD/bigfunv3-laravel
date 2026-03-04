<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - BigFun</title>

    <link rel="icon" type="image/png" href="/assets/icon/bfun.png">

    @vite(['resources/css/app.css'])

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />
</head>

<body class="min-h-screen p-6 md:p-10 relative overflow-x-hidden bg-slate-900">

    @include('partials.alert')

    <div class="fixed top-[-20%] left-[-10%] w-[500px] h-[500px] bg-[#9E6B73] rounded-full blur-[150px] opacity-20 pointer-events-none z-0"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[600px] h-[600px] bg-blue-900 rounded-full blur-[150px] opacity-20 pointer-events-none z-0"></div>

    <div class="max-w-3xl mx-auto relative z-10">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    <span class="material-symbols-rounded text-[#9E6B73] text-4xl">settings_system_daydream</span>
                    System Configuration
                </h1>
                <p class="text-slate-400 mt-1">Manage core application settings, databases, and mail servers.</p>
            </div>
            <a href="/" class="shrink-0 px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors flex items-center gap-2 text-sm font-semibold">
                <span class="material-symbols-rounded text-lg">arrow_back</span>
                Return to App
            </a>
        </div>

        <div class="flex flex-col gap-6">

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

                    <button id="btn-export-backup" class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-emerald-500/50 hover:bg-slate-800 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400 group-hover:text-emerald-400 transition-colors">download</span>
                            <span id="export-text" class="font-medium text-slate-200">Export Full Backup (.sql)</span>
                        </div>
                    </button>
                </div>
            </div>

            @php
            // Laravel's built-in maintenance check
            $isMaintenance = app()->isDownForMaintenance();
            @endphp

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
                    <button id="btn-clear-cache" class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700 hover:border-[#9E6B73] hover:bg-slate-800 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400 group-hover:text-[#9E6B73] transition-colors">cleaning_services</span>
                            <div class="text-left">
                                <span class="font-medium text-slate-200 block" id="cache-text">Clear System Cache</span>
                                <span class="text-xs text-slate-500 block">Frees up temporary server files</span>
                            </div>
                        </div>
                    </button>

                    <div class="w-full flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-slate-700">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-slate-400">construction</span>
                            <div class="text-left">
                                <span class="font-medium text-slate-200 block">Maintenance Mode</span>
                                <span class="text-xs text-slate-500 block" id="maintenance-status-text">
                                    @if($isMaintenance)
                                    <span class="text-amber-500">Currently Active</span>
                                    @else
                                    Restricts public access
                                    @endif
                                </span>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="maintenance-toggle" class="sr-only peer" {{ $isMaintenance ? 'checked' : '' }}>
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

                <form id="smtp-form" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">SMTP Host</label>
                        <input type="text" id="smtp_host" value="{{ config('mail.mailers.smtp.host') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Port</label>
                            <input type="number" id="smtp_port" value="{{ config('mail.mailers.smtp.port') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Encryption</label>
                            <select id="smtp_enc" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors appearance-none">
                                <option value="tls" {{ config('mail.mailers.smtp.encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ config('mail.mailers.smtp.encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Username (Email)</label>
                        <input type="email" id="smtp_user" value="{{ config('mail.mailers.smtp.username') }}" placeholder="your-email@gmail.com" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1 pl-1">Password</label>
                        <input type="password" id="smtp_pass" placeholder="App Password / Mail Password" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-[#9E6B73] focus:border-[#9E6B73] block p-3 outline-none transition-colors">
                    </div>

                    <button type="button" id="btn-test-smtp" class="w-full py-3 bg-[#9E6B73] hover:bg-[#86545C] text-white text-sm font-bold rounded-xl transition-all duration-300 mt-3 flex items-center justify-center gap-2 shadow-lg shadow-black/20 active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed disabled:active:scale-100">
                        <span id="smtp-btn-text">Test Connection</span>
                    </button>
                </form>
            </div>

            @php
            // Get Laravel's environment natively
            $currentEnv = config('app.env');
            $envColor = match($currentEnv) {
            'local', 'development' => 'text-amber-400',
            'staging' => 'text-blue-400',
            default => 'text-emerald-400'
            };
            @endphp

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
                            <select id="env-select" class="w-full bg-slate-800 border border-slate-600 font-bold rounded-xl {{ $envColor }} focus:ring-[#9E6B73] focus:border-[#9E6B73] p-3 outline-none transition-colors appearance-none cursor-pointer">
                                <option value="development" class="text-amber-400" {{ $currentEnv === 'local' ? 'selected' : '' }}>Development Environment</option>
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
    </div>

    <script src="/assets/js/components.js"></script>
    <script src="/assets/js/settings.js"></script>
</body>

</html>