<x-settings-layout title="Database Viewer - BigFun" bodyClass="p-4 md:p-6">
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

    <livewire:database-viewer />
</x-settings-layout>