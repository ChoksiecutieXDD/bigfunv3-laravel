<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BigFun - Australia's Premier Choice for premium amusement rides, giant inflatables, and arcade games. Perfect for corporate events and private parties.">
    <title>BigFun | Australia's Premier Amusement Rides & Party Hire</title>
    <link rel="icon" type="image/png" href="/assets/icon/bfun.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css'])

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0..1,0&display=block" />

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

    <div class="fixed top-[-100px] right-[-100px] w-150 h-150 bg-plum/5 rounded-full blur-3xl pointer-events-none z-[-2]"></div>
    <div class="fixed bottom-0 left-0 w-125 h-125 bg-blue-100/30 rounded-full blur-3xl pointer-events-none z-[-2]"></div>

    <nav id="navbar" class="nav-container fixed top-0 w-full z-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <a href="/" class="flex-shrink-0 group">
                    <img src="/assets/icon/bgfunlogo.png" alt="BigFun - Premium Amusement Rides" width="180" height="48" class="h-10 md:h-12 w-auto transition-transform duration-300 group-hover:scale-105">
                </a>
                <div class="flex items-center gap-8">
                    @auth
                        @php
                            $role = Auth::user()->role;
                            $dashboardUrl = '/staff/dashboard';
                            if ($role === 'Administrator' || $role === 'Admin') {
                                $dashboardUrl = '/admin/dashboard';
                            } elseif ($role === 'Supervisor') {
                                $dashboardUrl = '/supervisor/calendar';
                            }
                        @endphp
                        <a href="{{ $dashboardUrl }}" class="text-sm font-bold text-plum hover:text-plum-dark transition-colors flex items-center gap-1 bg-plum/5 hover:bg-plum/10 px-4 py-2 rounded-full border border-plum/15">
                            <span class="material-symbols-rounded text-lg">dashboard</span>
                            <span class="hidden sm:inline">Go to Panel</span>
                        </a>
                    @else
                        <a href="/login" class="text-sm font-bold text-gray-500 hover:text-plum transition-colors flex items-center gap-1">
                            <span class="material-symbols-rounded text-lg">lock</span>
                            <span class="hidden sm:inline">Staff Portal</span>
                        </a>
                    @endauth
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
        <div class="absolute top-0 right-0 w-200 h-200 bg-plum rounded-full blur-[200px] opacity-15 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <!-- CTA Section -->
            <div class="flex flex-col lg:flex-row justify-between items-center pb-16 border-b border-gray-700/50">
                <div class="text-center lg:text-left mb-10 lg:mb-0 max-w-2xl">
                    <h2 class="text-4xl lg:text-5xl font-black mb-4 tracking-tight leading-tight">Ready to make memories?</h2>
                    <p class="text-xl text-gray-400 font-medium">Get a personalized quote for your event in under 2 minutes.</p>
                </div>
                <a href="https://bigfun.com.au/" class="btn-plum text-xl font-bold py-5 px-14 rounded-full hover:scale-105 transition-all shadow-2xl flex-shrink-0">
                    Start Now
                </a>
            </div>

            <!-- Bottom Navigation & Copyright -->
            <div class="pt-12 grid grid-cols-1 md:grid-cols-3 items-center gap-10">
                <!-- Brand -->
                <div class="flex flex-col items-center md:items-start gap-4 order-2 md:order-1">
                    <img src="/assets/icon/bgfunlogo.png" alt="BigFun Logo Footer" width="140" height="38" class="h-9 w-auto brightness-0 invert opacity-90">
                    <p class="text-xs font-semibold text-gray-500 tracking-wider uppercase">&copy; 2026 BigFun. All rights reserved.</p>
                </div>

                <!-- Links -->
                <div class="flex justify-center gap-8 text-sm font-bold text-gray-400 order-1 md:order-2">
                    <a href="#" class="hover:text-white transition-colors">Privacy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms</a>
                    <a href="https://bigfun.com.au/contact/" class="hover:text-white transition-colors">Contact</a>
                </div>

                <!-- Actions -->
                <div class="flex justify-center md:justify-end items-center gap-4 order-3">
                    @auth
                        @php
                            $role = Auth::user()->role;
                            $dashboardUrl = '/staff/dashboard';
                            if ($role === 'Administrator' || $role === 'Admin') {
                                $dashboardUrl = '/admin/dashboard';
                            } elseif ($role === 'Supervisor') {
                                $dashboardUrl = '/supervisor/calendar';
                            }
                        @endphp
                        <a href="{{ $dashboardUrl }}" class="group text-white transition-all flex items-center gap-2.5 px-6 py-3 rounded-full bg-plum hover:bg-plum-dark shadow-lg">
                            <span class="material-symbols-rounded text-lg group-hover:rotate-12 transition-transform">dashboard</span> 
                            <span class="text-sm font-bold">Go to Panel</span>
                        </a>
                    @else
                        <a href="/login" class="group hover:text-white text-gray-300 transition-all flex items-center gap-2.5 px-6 py-3 rounded-full bg-gray-800/80 hover:bg-plum border border-gray-700 hover:border-plum shadow-lg">
                            <span class="material-symbols-rounded text-lg group-hover:rotate-12 transition-transform">lock</span> 
                            <span class="text-sm font-bold">Staff Portal</span>
                        </a>
                        <a href="/supervisor/login" class="opacity-10 hover:opacity-100 transition-all duration-500 text-plum p-2.5 rounded-full hover:bg-white/5" title="Supervisor Access">
                            <span class="material-symbols-rounded text-2xl">admin_panel_settings</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </footer>

    <a href="/system/settings" class="fixed bottom-0 right-0 w-10 h-10 opacity-0 z-9999 cursor-default"></a>

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

    <x-session-monitor />
    @livewireScripts
</body>

</html>