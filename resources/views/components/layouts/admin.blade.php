<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'BigFun Admin') }}</title>

    @vite(['resources/css/app.css'])

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @livewireStyles
</head>

<body class="bg-[var(--color-bg-custom)] block lg:flex text-gray-700"
    x-data="{ isCollapsed: false, isMobileOpen: false }"
    @resize.window="if(window.innerWidth >= 1024) isMobileOpen = false"
    :class="{ 'overflow-hidden': isMobileOpen }">

    <!-- Mobile Overlay -->
    <div x-show="isMobileOpen"
        x-transition.opacity
        @click="isMobileOpen = false"
        class="fixed inset-0 z-50 bg-gray-900/40 backdrop-blur-sm lg:hidden"
        style="display: none;"></div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-[60] bg-white shadow-2xl lg:shadow-xl flex flex-col h-full border-r border-gray-100 overflow-visible transition-all duration-300 ease-in-out"
        :class="{ 
            'w-20': isCollapsed, 
            'w-72': !isCollapsed, 
            '-translate-x-full lg:translate-x-0': !isMobileOpen, 
            'translate-x-0': isMobileOpen 
        }">

        <div class="h-20 flex items-center justify-center relative shrink-0 border-b border-gray-50 px-4">
            <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="BigFun" class="h-8 w-auto transition-opacity duration-300" x-show="!isCollapsed">
            <img src="{{ asset('assets/icon/bfun.png') }}" alt="B" class="h-8 w-auto transition-opacity duration-300" x-show="isCollapsed" style="display: none;">

            <button type="button" @click="isCollapsed = !isCollapsed" class="hidden lg:flex absolute -right-3 top-1/2 -translate-y-1/2 bg-white border border-gray-200 text-gray-400 p-1 rounded-full shadow-sm z-20 hover:text-[#9E6B73] transition-colors focus:outline-none" aria-label="Toggle sidebar size">
                <span class="material-symbols-rounded text-lg transition-transform duration-300" :class="isCollapsed ? 'rotate-180' : ''">chevron_left</span>
            </button>

            <button type="button" @click="isMobileOpen = false" class="lg:hidden absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73] focus:outline-none">
                <span class="material-symbols-rounded text-2xl">close</span>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto no-scrollbar py-6 px-3 overflow-x-hidden transition-all duration-300"
            :class="isCollapsed ? 'space-y-1' : 'space-y-6'">

            <div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-3 whitespace-nowrap transition-opacity duration-200" x-show="!isCollapsed">
                    Admin Workspace
                </div>
                <div class="space-y-1">
                    <x-sidebar.link href="/admin/dashboard" icon="dashboard" :active="request()->is('admin/dashboard*')">Dashboard</x-sidebar.link>
                    <x-sidebar.link href="/admin/calendar" icon="calendar_month" :active="request()->is('admin/calendar*')">Calendar</x-sidebar.link>
                    <x-sidebar.link href="/admin/bookings" icon="event_note" :active="request()->is('admin/bookings*')">Bookings</x-sidebar.link>
                    <x-sidebar.link href="/admin/staff" icon="group" :active="request()->is('admin/staff*')">Staff Team</x-sidebar.link>
                    <x-sidebar.link href="/admin/reports" icon="bar_chart" :active="request()->is('admin/reports*')">Reports</x-sidebar.link>
                </div>
            </div>

        </nav>

        <div class="p-4 border-t border-gray-100 whitespace-nowrap overflow-hidden bg-white shrink-0">
            <a href="{{ route('profile') }}" wire:navigate class="nav-item flex items-center gap-3 p-2 rounded-xl transition-all group {{ request()->routeIs('profile') ? 'bg-[#FDF2F4] ring-1 ring-[#9E6B73]/20' : 'hover:bg-gray-50' }}" :class="isCollapsed ? 'justify-center' : ''">
                <div class="w-9 h-9 rounded-full bg-[#9E6B73] text-white flex items-center justify-center shrink-0 font-bold shadow-sm text-[13px] tracking-wide">
                    {{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1) . substr(auth()->user()->last_name ?? '', 0, 1)) }}
                </div>
                <div class="profile-details overflow-hidden transition-all duration-300" x-show="!isCollapsed">
                    <p class="font-bold text-xs text-gray-700 truncate profile-name">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                    <p class="text-[10px] text-[#9E6B73] font-semibold truncate">{{ auth()->user()->role ?? 'Administrator' }}</p>
                </div>
            </a>
            <form method="POST" action="/logout" class="mt-2">
                @csrf
                <button type="submit" class="w-full nav-item flex items-center gap-3 p-2 rounded-xl text-red-400 hover:text-red-500 hover:bg-red-50 transition-all" :class="isCollapsed ? 'justify-center' : ''">
                    <span class="material-symbols-rounded text-xl shrink-0 group-hover:scale-110 transition-transform">logout</span>
                    <span class="nav-text text-xs font-bold uppercase" x-show="!isCollapsed">Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main id="mainContent"
        x-cloak
        :class="{ 'lg:ml-20': isCollapsed, 'lg:ml-72': !isCollapsed }"
        class="flex-1 transition-all duration-300 ease-in-out min-h-screen pt-16 lg:pt-0 flex flex-col">

        <div class="lg:hidden h-16 bg-white border-b border-gray-100 flex items-center px-4 fixed top-0 w-full z-40 shadow-sm">
            <button @click="isMobileOpen = true" class="text-gray-500 hover:text-[#9E6B73] mr-4 focus:outline-none">
                <span class="material-symbols-rounded text-2xl mt-1">menu</span>
            </button>
            <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="BigFun" class="h-6 w-auto">
        </div>

        <div class="p-4 lg:p-8 flex-1 w-full mx-auto">
            {{ $slot }}
        </div>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @livewireScripts
</body>

</html>