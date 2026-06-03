<div class="p-4 md:p-6">
    <style>
        /* Your custom scrollbars */
        .custom-scrollbar::-webkit-scrollbar {
            height: 10px;
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #0F172A;
            border-radius: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 relative z-10 w-full max-w-7xl mx-auto">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <span class="material-symbols-rounded text-blue-400 text-3xl">table_view</span>
                Database Viewer
            </h1>
            <p class="text-slate-400 mt-1 text-sm">Viewing schema: <span class="text-emerald-400 font-mono">{{ $dbName }}</span></p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('system.settings') }}" wire:navigate class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors flex items-center gap-2 text-sm font-semibold shadow-sm">
                <span class="material-symbols-rounded text-lg">settings</span>
                Back to Settings
            </a>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-6 flex-1 w-full max-w-7xl mx-auto relative z-10">

        <div class="w-full md:w-64 flex flex-col bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-xl shrink-0 h-50 md:h-[calc(100vh-150px)]">
            <div class="p-4 border-b border-slate-700/50 bg-slate-800/80 rounded-t-2xl shrink-0">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tables</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                @forelse ($tables as $table)
                <button wire:click.prevent="selectTable('{{ $table }}')"
                    class="w-full text-left block px-4 py-3 rounded-xl text-sm transition-all duration-200 
                        {{ $selectedTable === $table ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20 font-semibold' : 'text-slate-300 hover:bg-slate-700/50 border border-transparent' }}">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-rounded text-base {{ $selectedTable === $table ? 'text-blue-400' : 'text-slate-500' }}">dataset</span>
                        {{ $table }}
                    </div>
                </button>
                @empty
                <p class="text-sm text-slate-500 p-2">No tables found.</p>
                @endforelse
            </div>
        </div>

        <div class="flex-1 flex flex-col bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-xl md:h-[calc(100vh-150px)] min-h-125 w-full overflow-hidden relative">

            <div wire:loading wire:target="selectTable" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-2xl">
                <div class="flex flex-col items-center text-blue-400">
                    <span class="material-symbols-rounded text-4xl animate-spin">refresh</span>
                    <span class="mt-2 text-sm font-semibold">Loading Data...</span>
                </div>
            </div>

            @if ($selectedTable)
            <div class="p-4 border-b border-slate-700/50 bg-slate-800/80 flex justify-between items-center rounded-t-2xl shrink-0">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    Data: <span class="text-emerald-400 font-mono">{{ $selectedTable }}</span>
                </h2>
                <span class="text-xs text-slate-400 bg-slate-900 px-3 py-1 rounded-full border border-slate-700">
                    Top 100 Rows
                </span>
            </div>

            <div class="flex-1 w-full overflow-auto p-0 custom-scrollbar">
                <table class="w-full text-left border-collapse text-sm min-w-max">
                    <thead class="bg-slate-900/90 sticky top-0 z-10 shadow-sm backdrop-blur-md">
                        <tr>
                            @foreach ($columns as $col)
                            <th class="p-4 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-700 whitespace-nowrap">
                                {{ $col }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @forelse ($tableData as $row)
                        <tr class="hover:bg-slate-700/30 transition-colors">
                            @foreach ($columns as $col)
                            <td class="p-4 text-slate-300 whitespace-nowrap">
                                @php
                                $val = $row[$col] ?? null;
                                @endphp

                                @if ($val === null)
                                <span class="text-slate-600 italic">NULL</span>
                                @elseif (strlen((string)$val) > 60)
                                {{ substr((string)$val, 0, 60) }}<span class="text-blue-400 font-bold ml-1">...</span>
                                @else
                                {{ $val }}
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="p-8 text-center text-slate-500 italic">
                                No records found in this table.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @else
            <div class="flex-1 flex flex-col items-center justify-center text-slate-500">
                <span class="material-symbols-rounded text-6xl mb-4 opacity-50">database</span>
                <p>Select a table from the sidebar to view data.</p>
            </div>
            @endif

        </div>
    </div>
</div>