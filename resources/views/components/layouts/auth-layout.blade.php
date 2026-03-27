<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Login - Appointment Manager' }}</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/icon/bfun.png') }}">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}" />

    @livewireStyles
</head>

<body class="h-screen w-screen overflow-hidden flex items-center justify-center relative text-gray-800">

    <div class="blob bg-[#E3D5CA] w-96 h-96 rounded-full fixed top-0 left-0 -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
    <div class="blob bg-[#ffe4e6] w-96 h-96 rounded-full fixed bottom-0 right-0 translate-x-1/3 translate-y-1/3 pointer-events-none"></div>
    
    <div class="w-full h-screen flex shadow-2xl overflow-hidden relative z-10">
        {{ $slot }}
    </div>

    @livewireScripts
</body>

</html>