<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Account Settings | BigFun' }}</title>

    <link rel="icon" type="image/png" href="{{ asset('picture/bfun.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    @vite(['resources/css/app.css'])
    @livewireStyles
</head>

<body class="min-h-screen bg-[#F6F7FB] bg-[radial-gradient(1200px_600px_at_15%_-10%,rgba(158,107,115,.18),transparent_60%),radial-gradient(900px_500px_at_95%_10%,rgba(134,84,92,.14),transparent_55%)] text-[#2D3748] font-['Poppins'] w-full pb-12">

    <!-- Livewire injects the component content right here -->
    {{ $slot }}

    @livewireScripts
</body>

</html>