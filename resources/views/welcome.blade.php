<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BigFun - Amusement Rides & Game Hire</title>

    <link rel="icon" type="image/png" href="/assets/icon/bfun.png">

    @vite(['resources/css/app.css'])

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>

<body class="min-h-screen flex flex-col relative selection:bg-plum selection:text-white">

    <div class="fixed top-[-100px] right-[-100px] w-[600px] h-[600px] bg-[#9E6B73]/5 rounded-full blur-3xl pointer-events-none z-[-2]"></div>
    <div class="fixed bottom-0 left-0 w-[500px] h-[500px] bg-blue-100/30 rounded-full blur-3xl pointer-events-none z-[-2]"></div>

    <nav id="navbar" class="nav-container nav-transparent fixed top-0 w-full z-50">
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

    <div class="flex flex-col gap-16 lg:gap-24">

        <main class="relative pt-28 lg:pt-36">
            <div class="hero-bg"></div>
            <div class="w-full max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                    <div class="space-y-6 text-center lg:text-left relative z-10">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-[#9E6B73]/10 shadow-sm animate-bounce-slow">
                            <span class="flex h-2.5 w-2.5 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                            </span>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Australia's Premier Choice</span>
                        </div>

                        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black leading-[1.1] text-gray-900 tracking-tight">
                            Events That <br>
                            <span class="text-gradient">Pop.</span>
                        </h1>

                        <p class="text-lg text-gray-600 leading-relaxed font-medium max-w-lg mx-auto lg:mx-0">
                            We deliver the fun. Premium amusement rides, giant inflatables, and arcade games for corporate events and private parties.
                        </p>

                        <div class="pt-4 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="https://bigfun.com.au/" class="btn-plum text-base font-bold py-4 px-10 rounded-full flex items-center justify-center gap-2 group shadow-xl">
                                Check Availability
                            </a>
                            <a href="https://bigfun.com.au/" class="btn-white text-base font-bold py-4 px-10 rounded-full flex items-center justify-center gap-2 group shadow-sm">
                                Browse Catalogue
                            </a>
                        </div>

                        <div class="pt-6 flex flex-wrap justify-center lg:justify-start gap-8 text-sm font-bold text-gray-400">
                            <div class="flex items-center gap-2"><span class="material-symbols-rounded text-[#9E6B73] text-xl">verified</span> Fully Insured</div>
                            <div class="flex items-center gap-2"><span class="material-symbols-rounded text-[#9E6B73] text-xl">engineering</span> Safety Certified</div>
                        </div>
                    </div>

                    <div class="relative h-[550px] hidden lg:block">
                        <div class="w-full h-full relative">
                            <div class="img-card absolute top-0 right-0 w-80 h-[400px] z-20" style="background-image: url('/assets/img/mechanical-surf.jpg');"></div>

                            <div class="img-card absolute bottom-10 right-52 w-64 h-64 z-30" style="background-image: url('/assets/img/splash.jpg');">
                                <div class="absolute -top-5 -left-5 bg-white px-5 py-2.5 rounded-2xl shadow-lg flex items-center gap-2">
                                    <span class="material-symbols-rounded text-[#9E6B73] text-xl">star</span>
                                    <span class="text-sm font-bold text-gray-800">Fan Favorite</span>
                                </div>
                            </div>

                            <div class="img-card absolute top-20 left-10 w-60 h-72 z-10 opacity-90" style="background-image: url('/assets/img/premiumbull.jpg'); transform: rotate(-8deg);"></div>
                        </div>
                    </div>

                </div>
            </div>
        </main>

        <section class="bg-white border-y border-gray-100 py-12 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-12 text-center divide-x divide-gray-100">
                    <div>
                        <p class="text-4xl lg:text-5xl font-black text-[#9E6B73]">500+</p>
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-wide mt-2">Events Hosted</p>
                    </div>
                    <div>
                        <p class="text-4xl lg:text-5xl font-black text-[#9E6B73]">150+</p>
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-wide mt-2">Rides Available</p>
                    </div>
                    <div>
                        <p class="text-4xl lg:text-5xl font-black text-[#9E6B73]">15y</p>
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-wide mt-2">Experience</p>
                    </div>
                    <div>
                        <p class="text-4xl lg:text-5xl font-black text-[#9E6B73]">100%</p>
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-wide mt-2">Safety Record</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative z-20 py-10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-4xl font-black text-gray-900 sm:text-5xl mb-4">Why Book With BigFun?</h2>
                    <p class="text-lg text-gray-600">We don't just drop off equipment; we deliver peace of mind.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 hover:border-[#9E6B73]/30 transition-colors">
                        <span class="material-symbols-rounded text-4xl text-[#9E6B73] mb-4 block">health_and_safety</span>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Uncompromising Safety</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Every ride is rigorously tested, fully insured up to $20M, and operated by trained professionals to ensure zero stress.</p>
                    </div>
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 hover:border-[#9E6B73]/30 transition-colors">
                        <span class="material-symbols-rounded text-4xl text-[#9E6B73] mb-4 block">schedule</span>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">On-Time, Every Time</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Your event schedule is tight. Our logistics team guarantees punctual delivery, setup, and pack-down so you stay on track.</p>
                    </div>
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 hover:border-[#9E6B73]/30 transition-colors">
                        <span class="material-symbols-rounded text-4xl text-[#9E6B73] mb-4 block">support_agent</span>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Event Support</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">From selecting the right rides to on-site management, our dedicated staff is there to assist you from start to finish.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <h2 class="text-4xl font-black text-gray-900 sm:text-5xl mb-4">Explore Our Collection</h2>
                    <p class="text-lg text-gray-600">Whatever your event theme, we have the perfect entertainment solution.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="group relative rounded-[2.5rem] overflow-hidden h-[400px] shadow-2xl cursor-pointer">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110" style="background-image: url('/assets/img/mech-bull.jpg');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-80 group-hover:opacity-70 transition-opacity"></div>
                        <div class="absolute bottom-0 left-0 p-8 w-full">
                            <h3 class="text-2xl font-bold text-white mb-2">Mechanical Bulls</h3>
                            <p class="text-white/80 text-sm font-medium mb-4">The ultimate party challenge.</p>
                            <span class="inline-block bg-[#9E6B73] text-white text-xs font-bold px-4 py-2 rounded-full opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0">View Details</span>
                        </div>
                    </div>

                    <div class="group relative rounded-[2.5rem] overflow-hidden h-[400px] shadow-2xl cursor-pointer md:-mt-8">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110" style="background-image: url('/assets/img/jumpcastle.png');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-80 group-hover:opacity-70 transition-opacity"></div>
                        <div class="absolute bottom-0 left-0 p-8 w-full">
                            <h3 class="text-2xl font-bold text-white mb-2">Jumping Castles</h3>
                            <p class="text-white/80 text-sm font-medium mb-4">Slides, castles, and obstacle courses.</p>
                            <span class="inline-block bg-[#9E6B73] text-white text-xs font-bold px-4 py-2 rounded-full opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0">View Details</span>
                        </div>
                    </div>

                    <div class="group relative rounded-[2.5rem] overflow-hidden h-[400px] shadow-2xl cursor-pointer">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110" style="background-image: url('/assets/img/cash-cube.jpg');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-80 group-hover:opacity-70 transition-opacity"></div>
                        <div class="absolute bottom-0 left-0 p-8 w-full">
                            <h3 class="text-2xl font-bold text-white mb-2">Interactive Games</h3>
                            <p class="text-white/80 text-sm font-medium mb-4">Cash cubes, arcade, and fun.</p>
                            <span class="inline-block bg-[#9E6B73] text-white text-xs font-bold px-4 py-2 rounded-full opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0">View Details</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-20 border-t border-gray-100">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-black text-gray-900">How It Works</h2>
                </div>

                <div class="grid md:grid-cols-3 gap-12 relative">
                    <div class="hidden md:block absolute top-12 left-24 right-24 h-0.5 bg-gradient-to-r from-transparent via-[#9E6B73]/20 to-transparent z-0"></div>

                    <div class="relative z-10 text-center feature-card p-8 rounded-[2.5rem] bg-white">
                        <div class="w-24 h-24 mx-auto bg-white border-[6px] border-[#FDF2F4] rounded-full flex items-center justify-center mb-6 shadow-sm">
                            <span class="material-symbols-rounded text-5xl text-[#9E6B73]">search</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">1. Browse</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">Explore our extensive catalog and find the perfect rides for your event.</p>
                    </div>

                    <div class="relative z-10 text-center feature-card p-8 rounded-[2.5rem] bg-white">
                        <div class="w-24 h-24 mx-auto bg-white border-[6px] border-[#FDF2F4] rounded-full flex items-center justify-center mb-6 shadow-sm">
                            <span class="material-symbols-rounded text-5xl text-[#9E6B73]">calendar_month</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">2. Book Instantly</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">Check availability in real-time and secure your date online in minutes.</p>
                    </div>

                    <div class="relative z-10 text-center feature-card p-8 rounded-[2.5rem] bg-white">
                        <div class="w-24 h-24 mx-auto bg-white border-[6px] border-[#FDF2F4] rounded-full flex items-center justify-center mb-6 shadow-sm">
                            <span class="material-symbols-rounded text-5xl text-[#9E6B73]">celebration</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">3. We Deliver Fun</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">Our professional team handles delivery, setup, and operation. You enjoy!</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative z-20 pb-10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-black text-gray-900 mb-4">What Our Clients Say</h2>
                    <p class="text-lg text-gray-600">Don't just take our word for it.</p>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="flex text-yellow-400 mb-4">
                                <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span>
                            </div>
                            <p class="text-gray-600 text-sm italic mb-6">"Absolutely fantastic service! The mechanical bull was the highlight of our corporate party. The staff was professional and made sure everyone was safe."</p>
                        </div>
                        <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                            <div class="w-10 h-10 bg-[#9E6B73] rounded-full flex items-center justify-center text-white font-bold">SM</div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900">Sarah Mitchell</h4>
                                <p class="text-xs text-gray-500">Corporate Event Coordinator</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="flex text-yellow-400 mb-4">
                                <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span>
                            </div>
                            <p class="text-gray-600 text-sm italic mb-6">"Hired a jumping castle for my son's birthday. The team arrived early, set up quickly, and the kids had a blast. Highly recommend BigFun!"</p>
                        </div>
                        <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                            <div class="w-10 h-10 bg-[#2D3748] rounded-full flex items-center justify-center text-white font-bold">JD</div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900">James Davies</h4>
                                <p class="text-xs text-gray-500">Private Party</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between hidden lg:flex">
                        <div>
                            <div class="flex text-yellow-400 mb-4">
                                <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span>
                            </div>
                            <p class="text-gray-600 text-sm italic mb-6">"We've used them for three years in a row for our school fete. Reliable, clean equipment, and the best customer service in the industry."</p>
                        </div>
                        <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">PT</div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900">Principal Thompson</h4>
                                <p class="text-xs text-gray-500">School Fundraiser</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>

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

    <a href="/settings" class="fixed bottom-0 right-0 w-10 h-10 opacity-0 z-[9999] cursor-default" title=""></a>

    <script src="/assets/js/landing.js"></script>

</body>

</html>