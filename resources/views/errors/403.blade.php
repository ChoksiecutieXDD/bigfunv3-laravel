<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 | Access Denied</title>

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

<body class="bg-[var(--color-bg-page)] text-[var(--color-text-main)] antialiased min-h-screen flex items-center justify-center selection:bg-[var(--color-plum)] selection:text-[var(--color-plum-light)]">

    <!-- Alpine Component -->
    <div
        class="max-w-2xl px-6 py-16 text-center"
        x-data="{ isHovered: false, loading: false }">

        <!-- Icon & Error Code -->
        <div class="relative inline-block">
            <!-- Pulsing Lock Icon for 403 context -->
            <div class="absolute -top-10 left-1/2 -translate-x-1/2 text-[var(--color-plum-dark)] animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-12 h-12">
                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                </svg>
            </div>

            <h1
                class="text-9xl md:text-[12rem] font-black tracking-tighter text-[var(--color-plum)] transition-transform duration-500 ease-out drop-shadow-sm mt-4"
                :class="isHovered ? 'scale-110' : 'scale-100'"
                @mouseenter="isHovered = true"
                @mouseleave="isHovered = false">
                403
            </h1>
        </div>

        <!-- Copy -->
        <h2 class="mt-4 text-3xl md:text-4xl font-bold tracking-tight">Access Denied.</h2>
        <p class="mt-4 text-lg font-medium text-[var(--color-text-main)] opacity-75 max-w-md mx-auto">
            Hold up! It looks like you don't have the proper permissions to view this area. If you think you should be here, please contact support.
        </p>

        <!-- Actions -->
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">

            <!-- Primary Button (Returns Home) -->
            <a
                href="{{ url('/') }}"
                @click="loading = true"
                class="relative inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold text-[var(--color-bg-page)] transition-all duration-300 bg-[var(--color-plum)] rounded-full shadow-md hover:bg-[var(--color-plum-dark)] hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-[var(--color-plum-light)] overflow-hidden w-full sm:w-auto">
                <span x-show="!loading" class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Take Me Home
                </span>

                <!-- Alpine Loading Spinner -->
                <span x-show="loading" x-cloak class="flex items-center gap-2">
                    <svg class="w-5 h-5 animate-spin text-[var(--color-bg-page)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Routing...
                </span>
            </a>

            <!-- Secondary Button (Goes Back) -->
            <button
                onclick="window.history.back()"
                class="inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold transition-all duration-300 bg-[var(--color-plum-light)] border-2 border-[var(--color-bg-custom)] text-[var(--color-plum-dark)] rounded-full hover:bg-[var(--color-bg-custom)] hover:text-white hover:border-[var(--color-bg-custom)] focus:outline-none focus:ring-4 focus:ring-[var(--color-plum-light)] w-full sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Go Back
            </button>

        </div>

    </div>

</body>

</html>