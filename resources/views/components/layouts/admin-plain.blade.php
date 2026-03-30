<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/assets/icon/bfun.png">
    <title>BigFun</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @livewireStyles
</head>

<body class="text-gray-700 min-h-screen overflow-x-hidden bg-gray-50 font-[Poppins]">

    <!-- Top Navigation Bar for Admin Focus Mode -->
    <nav class="fixed top-0 left-0 w-full h-16 bg-white z-50 shadow-sm flex items-center justify-between px-6 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="BigFun" class="h-8">
        </div>
        <a href="{{ route('admin.calendar') }}" class="flex items-center gap-2 px-4 py-2 bg-[#9D686E] text-white rounded-xl font-bold shadow-md shadow-[#9D686E]/20 hover:opacity-90 transition text-xs uppercase tracking-wide">
            <span class="material-symbols-rounded text-lg">arrow_back</span>
            <span class="hidden sm:inline">Back to Calendar</span>
        </a>
    </nav>

    <!-- Main Content Injection -->
    <main class="w-full min-h-screen pt-24 px-4 lg:px-8 pb-12 transition-all duration-300">
        {{ $slot }}
    </main>

    @livewireScripts
</body>

</html>