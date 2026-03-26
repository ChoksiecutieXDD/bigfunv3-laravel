<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Booking Administration - BigFun' }}</title>

    <link rel="icon" type="image/png" href="/assets/icon/bfun.png">

    @vite(['resources/css/app.css'])

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />

    @livewireStyles
</head>

<body class="min-h-screen relative bg-bg-page font-['Poppins'] text-text-main {{ $bodyClass ?? '' }}">

    @include('components.ui.toast') {{-- Top-right corner notifications --}}
    @include('components.ui.alert') {{-- Top-center important banners --}}
    @include('components.ui.confirm') {{-- Centered action confirmation modals --}}

    <header class="w-full bg-white shadow-sm border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-[1400px] mx-auto px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-plum flex items-center justify-center text-white shadow-sm">
                    <span class="material-symbols-rounded text-[20px]">settings</span>
                </div>
                <span class="font-bold text-text-main tracking-wider uppercase text-sm">System Management</span>
            </div>
            <a href="/settings" wire:navigate class="text-sm font-semibold text-slate-500 hover:text-plum transition-colors flex items-center gap-1">
                <span class="material-symbols-rounded text-[20px]">arrow_back</span> Back
            </a>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    {{ $scripts ?? '' }}
    @livewireScripts
</body>

</html>