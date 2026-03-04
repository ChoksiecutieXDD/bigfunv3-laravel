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
        window.addEventListener('notify', (event) => {

            const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
            const message = data.message || 'Action completed.';
            const type = data.type || 'success';

            // Changed this to use your much better showToast function!
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