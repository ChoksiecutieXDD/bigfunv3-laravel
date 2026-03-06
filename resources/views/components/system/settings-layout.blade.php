<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'BigFun - Management System' }}</title>

    <link rel="icon" type="image/png" href="/assets/icon/bfun.png">

    @vite(['resources/css/app.css'])

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />

    @livewireStyles
</head>

<body class="min-h-screen relative overflow-x-hidden bg-slate-900 {{ $bodyClass ?? '' }}">

    @include('partials.alert')

    {{ $slot }}

    <script src="/assets/js/components.js"></script>

    {{ $scripts ?? '' }}

    @livewireScripts

    <script>
        // Changed to 'show-toast' to match your Livewire component dispatches
        window.addEventListener('show-toast', (event) => {

            // Livewire 3 handles dispatched event details slightly differently depending on the array structure
            const data = event.detail;
            const message = data.message || (Array.isArray(data) && data[0] ? data[0].message : 'Action completed.');
            const type = data.type || (Array.isArray(data) && data[0] ? data[0].type : 'success');

            if (typeof showToast === 'function') {
                showToast(message, type);
            } else if (typeof showAlert === 'function') {
                showAlert(message, type); // Fallback
            } else {
                alert(`[${type.toUpperCase()}] ${message}`);
            }

        });
    </script>
</body>

</html>