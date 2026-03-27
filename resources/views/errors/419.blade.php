<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 | Page Expired</title>

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

        <!-- Error Code -->
        <h1
            class="text-9xl md:text-[12rem] font-black tracking-tighter text-[var(--color-plum)] transition-transform duration-500 ease-out drop-shadow-sm"
            :class="isHovered ? 'scale-110' : 'scale-100'"
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false">
            419
        </h1>

        <!-- Decorative Info Icon -->
        <div class="mt-6 flex justify-center text-[var(--color-bg-custom)]">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 animate-pulse">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 9a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V15a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V9z" clip-rule="evenodd" />
            </svg>
        </div>

        <!-- Copy -->
        <h2 class="mt-8 text-3xl md:text-4xl font-bold tracking-tight">Page Expired.</h2>
        <p class="mt-4 text-lg font-medium text-[var(--color-text-main)] opacity-75 max-w-md mx-auto">
            Sorry, your session has expired. This often happens if you've been on a page for too long without activity. 
        </p>

        <!-- Actions -->
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">

            <!-- Refresh Button -->
            <button
                onclick="window.location.reload()"
                @click="loading = true"
                class="relative inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold text-[var(--color-bg-page)] transition-all duration-300 bg-[var(--color-plum)] rounded-full shadow-md hover:bg-[var(--color-plum-dark)] hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-[var(--color-plum-light)] overflow-hidden w-full sm:w-auto">
                <span x-show="!loading" class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Refresh Session
                </span>

                <!-- Alpine Loading Spinner -->
                <span x-show="loading" x-cloak class="flex items-center gap-2">
                    <svg class="w-5 h-5 animate-spin text-[var(--color-bg-page)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Refreshing...
                </span>
            </button>

            <!-- Secondary Button (Goes Home) -->
            <a
                href="{{ url('/') }}"
                class="inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold transition-all duration-300 bg-[var(--color-plum-light)] border-2 border-[var(--color-bg-custom)] text-[var(--color-plum-dark)] rounded-full hover:bg-[var(--color-bg-custom)] hover:text-white hover:border-[var(--color-bg-custom)] focus:outline-none focus:ring-4 focus:ring-[var(--color-plum-light)] w-full sm:w-auto">
                Return Home
            </a>

        </div>

    </div>

</body>

</html>
