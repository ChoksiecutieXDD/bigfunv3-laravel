<x-settings-layout title="System Settings - BigFun" bodyClass="p-6 md:p-10">

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

        <livewire:system-settings />

    </div>

</x-settings-layout>