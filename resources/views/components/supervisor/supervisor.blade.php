<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'BigFun') }}</title>

    @vite(['resources/css/app.css'])

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @livewireStyles
</head>

<body class="antialiased bg-[#FFF5F7] text-[#2D3748] font-['Poppins']"
    x-data="{ isCollapsed: false, isMobileOpen: false }"
    :class="{ 'overflow-hidden': isMobileOpen }">

    <div x-show="isMobileOpen"
        x-transition.opacity
        @click="isMobileOpen = false"
        class="fixed inset-0 z-50 bg-gray-900/50 lg:hidden"
        style="display: none;"></div>

    <x-supervisor.sidebar />

    <main id="mainContent"
        :class="{ 'lg:ml-20': isCollapsed, 'lg:ml-72': !isCollapsed }"
        class="flex-1 transition-all duration-300 ease-in-out min-h-screen pt-16 lg:pt-0">

        <div class="lg:hidden h-16 bg-white border-b border-gray-100 flex items-center px-4 fixed top-0 w-full z-40 shadow-sm">
            <button @click="isMobileOpen = true" class="text-gray-500 hover:text-[#9E6B73] mr-4 focus:outline-none">
                <span class="material-symbols-rounded text-2xl mt-1">menu</span>
            </button>
            <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="BigFun" class="h-6 w-auto">
        </div>

        <div class="p-4 lg:p-8">
            {{ $slot }}
        </div>

    </main>

    @livewireScripts
</body>

</html>