<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'BigFun - Amusement Rides & Game Hire' }}</title>

    <link rel="icon" type="image/png" href="/assets/icon/bfun.png">

    @vite(['resources/css/app.css'])

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />

    <style>
        /* Define the transition state */
        .nav-container {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
        }

        /* The 'Scrolled' state */
        .nav-scrolled {
            background-color: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px);
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col relative selection:bg-plum selection:text-white">

    <div class="fixed top-[-100px] right-[-100px] w-[600px] h-[600px] bg-[#9E6B73]/5 rounded-full blur-3xl pointer-events-none z-[-2]"></div>
    <div class="fixed bottom-0 left-0 w-[500px] h-[500px] bg-blue-100/30 rounded-full blur-3xl pointer-events-none z-[-2]"></div>

    <nav id="navbar" class="nav-container fixed top-0 w-full z-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <a href="/" class="flex-shrink-0 group">
                    <img src="/assets/icon/bgfunlogo.png" alt="BigFun Logo" class="h-10 md:h-12 w-auto transition-transform duration-300 group-hover:scale-105">
                </a>
                <div class="flex items-center gap-8">
                    <a href="/login" class="text-sm font-bold text-gray-500 hover:text-[#9E6B73] transition-colors flex items-center gap-1">
                        <span class="material-symbols-rounded text-lg">lock</span>
                        <span class="hidden sm:inline">Staff Portal</span>
                    </a>
                    <a href="https://bigfun.com.au/" class="btn-plum text-sm font-bold py-3.5 px-7 rounded-full flex items-center gap-2">
                        Get Quote
                        <span class="material-symbols-rounded text-lg">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{ $slot }}

    <footer class="bg-[#2D3748] text-white pt-24 pb-12 mt-12 rounded-t-[3rem] relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[800px] h-[800px] bg-[#9E6B73] rounded-full blur-[200px] opacity-15 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-center pb-20 border-b border-gray-700">
                <div class="text-center md:text-left mb-10 md:mb-0 max-w-2xl">
                    <h2 class="text-4xl lg:text-5xl font-black mb-4 tracking-tight">Ready to make memories?</h2>
                    <p class="text-xl text-gray-400 font-medium">Get a personalized quote for your event in under 2 minutes.</p>
                </div>
                <a href="https://bigfun.com.au/" class="btn-plum text-xl font-bold py-5 px-14 rounded-full hover:scale-105 transition-transform shadow-xl flex-shrink-0">
                    Start Now
                </a>
            </div>

            <div class="pt-12 flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 opacity-80">
                    <img src="/assets/icon/bgfunlogo.png" alt="Logo" class="h-8 w-auto brightness-0 invert">
                    <span class="hidden sm:block h-1 w-1 rounded-full bg-gray-600"></span>
                    <span class="text-sm font-bold text-gray-400">© 2026 BigFun. All rights reserved.</span>
                </div>

                <div class="flex flex-wrap justify-center md:justify-end items-center gap-6 sm:gap-8 text-sm font-bold text-gray-400">
                    <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms & Conditions</a>
                    <a href="https://bigfun.com.au/contact/" class="hover:text-white transition-colors">Contact Us</a>

                    <div class="flex items-center gap-2">
                        <a href="/login" class="hover:text-white text-gray-300 transition-colors flex items-center gap-2 px-4 py-2 rounded-full bg-gray-800/50 hover:bg-gray-700 border border-gray-700 hover:border-gray-600">
                            <span class="material-symbols-rounded text-base">lock</span> Staff Portal
                        </a>
                        <a href="/supervisor/login" class="opacity-5 hover:opacity-100 transition-opacity duration-500 text-[#9E6B73] p-2 rounded-full hover:bg-white/5" title="Supervisor Access">
                            <span class="material-symbols-rounded text-xl block">admin_panel_settings</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <a href="/system/settings" class="fixed bottom-0 right-0 w-10 h-10 opacity-0 z-[9999] cursor-default"></a>

    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('nav-scrolled');
            } else {
                navbar.classList.remove('nav-scrolled');
            }
        });
    </script>

    {{ $scripts ?? '' }}
</body>

</html>