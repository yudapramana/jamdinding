<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>e-MTQ Platform – Digitalisasi Event MTQ Anda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Tailwind via CDN, sama seperti file HTML awal --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Vite entry khusus landing --}}
    @vite('resources/js/landing.js')

    <link href="{{ asset('app_logo.png') }}" rel="icon">
    <link rel="apple-touch-icon" href="{{ asset('app_logo.png') }}">
    <link rel="manifest" href="{{ asset('/manifest.json') }}">

    <style>
        [v-cloak]>* {
            display: none
        }

        [v-cloak]::before {
            content: "Loading…";
            margin: 0 auto;
            color: #e5e7eb;
        }
    </style>

    <script>
        // kirim data events & login URL ke Vue
        window.__INITIAL_EVENTS__ = @json($events ?? []);
        window.__LOGIN_URL__ = "{{ route('login') }}";
    </script>
</head>

<body class="bg-slate-950 text-slate-100">
    <div id="app" v-cloak></div>
</body>

</html>
