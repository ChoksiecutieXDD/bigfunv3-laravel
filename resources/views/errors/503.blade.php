<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable | BigFun</title>

    <!-- Standard Vite setup for the TALL stack -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Inline fallback for your app.css typography & colors -->
    <style>
        [x-cloak] {
            display: none !important;
        }

        :root {
            --color-plum: #9E6B73;
            --color-plum-dark: #86545C;
            --color-plum-light: #FDF2F4;
            --color-text-main: #2D3748;
            --color-bg-page: #FFF5F7;
            --color-bg-custom: #C5A8A3;
            --font-poppins: 'Poppins', sans-serif;
        }

        body {
            font-family: var(--font-poppins);
        }
    </style>
</head>

<body class="bg-[var(--color-bg-page)] text-[var(--color-text-main)] antialiased min-h-screen flex items-center justify-center selection:bg-[var(--color-plum)] selection:text-[var(--color-plum-light)] font-['Poppins']">

    <!-- Alpine Component -->
    <div
        class="max-w-2xl px-6 py-16 text-center"
        x-data="{ isHovered: false }">

        <!-- Icon & Heading -->
        <div class="relative inline-block mb-8">
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 text-[var(--color-plum)] animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-16 h-16">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
                </svg>
            </div>
            
            <h1
                class="text-6xl md:text-8xl font-black tracking-tighter text-[var(--color-plum)] transition-transform duration-500 ease-out drop-shadow-sm mt-8"
                :class="isHovered ? 'scale-105' : 'scale-100'"
                @mouseenter="isHovered = true"
                @mouseleave="isHovered = false">
                Maintenance
            </h1>
        </div>

        <!-- Copy -->
        <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-4">We'll be right back!</h2>
        <p class="text-lg font-medium text-[var(--color-text-main)] opacity-75 max-w-md mx-auto leading-relaxed">
            BigFun is currently undergoing scheduled maintenance to improve our systems. We expect to be back online shortly. 
        </p>

        <!-- Dynamic Loader Overlay -->
        <div class="mt-12 flex flex-col items-center">
            <div class="flex space-x-2">
                <div class="w-3 h-3 bg-[var(--color-plum)] rounded-full animate-pulse"></div>
                <div class="w-3 h-3 bg-[var(--color-plum)] rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                <div class="w-3 h-3 bg-[var(--color-plum)] rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
            </div>
            <p class="mt-4 text-xs font-bold uppercase tracking-widest text-[var(--color-bg-custom)]">Optimizing Systems</p>
        </div>

        <div class="mt-12 pt-8 border-t border-[var(--color-plum-light)]">
            <p class="text-sm font-semibold text-[var(--color-plum-dark)]">Thank you for your patience.</p>
        </div>

    </div>

</body>

</html>