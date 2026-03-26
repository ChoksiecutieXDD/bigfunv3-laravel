<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'User Profile | BigFun' }}</title>

    <link rel="icon" type="image/png" href="{{ asset('picture/bfun.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-[#F6F7FB] bg-[radial-gradient(1200px_600px_at_15%_-10%,rgba(158,107,115,.18),transparent_60%),radial-gradient(900px_500px_at_95%_10%,rgba(134,84,92,.14),transparent_55%)] text-[#2D3748] font-['Poppins'] w-full pb-12 overflow-x-hidden">

    <!-- Premium Navigation Bar -->
    <nav class="bg-white/90 backdrop-blur-md border-b border-gray-100 sticky top-0 z-30 shadow-sm transition-all duration-300">
        <div class="max-w-[1660px] mx-auto px-4 md:px-8">
            <div class="flex items-center justify-between py-4 md:py-6">
                <!-- Left Section: Back Link & Title -->
                <div class="flex items-center gap-6">
                    <a href="{{ $backLink ?? '#' }}" wire:navigate class="group flex items-center justify-center p-3 rounded-2xl text-gray-400 hover:text-[#9E6B73] hover:bg-[#FDF2F4] border border-transparent hover:border-[#9E6B73]/20 transition-all duration-400 shadow-sm hover:shadow-md" title="Go Back">
                        <span class="material-symbols-rounded text-2xl group-hover:-translate-x-1.5 transition-transform duration-300">arrow_back</span>
                    </a>
                    
                    <div class="flex flex-col">
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl md:text-3xl lg:text-4xl font-black text-gray-900 tracking-tight leading-none">Account Settings</h1>
                            <span class="hidden sm:inline-block px-3 py-1 rounded-lg bg-[#FDF2F4] text-[#9E6B73] text-[10px] font-bold uppercase border border-[#9E6B73]/10 tracking-widest">Profile</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-500 mt-1.5 opacity-80">Manage your profile & security</p>
                    </div>
                </div>

                <!-- Right Section: User Profile Swatch -->
                <div class="flex items-center gap-4">
                    <div class="hidden md:flex flex-col items-end">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Role</span>
                        <span class="text-sm font-black text-[#9E6B73]">{{ auth()->user()->role ?: 'User' }}</span>
                    </div>
                    <div class="h-10 w-px bg-gray-100 mx-2 hidden md:block"></div>
                    <div class="w-12 h-12 rounded-2xl bg-gray-900 text-white flex items-center justify-center text-xl font-black shadow-lg shadow-gray-200">
                        {{ $initials ?? mb_substr(auth()->user()->first_name, 0, 1) }}
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container with max-width [1660px] -->
    <main class="max-w-[1660px] mx-auto px-4 md:px-8 py-10 transition-all duration-300">
        {{ $slot }}
    </main>

    @livewireScripts
</body>

</html>
