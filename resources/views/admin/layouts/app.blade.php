<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ setting('app_name') }}</title>


    {{-- 
    @php
        $cwd = getcwd();
        $cssName = basename(glob($cwd . '/build/assets/*.css')[0], '.css');
        $jsName = basename(glob($cwd . '/build/assets/*.js')[0], '.js');
        $css = asset('build/assets/' . $cssName . '.css');
        $js = asset('build/assets/' . $jsName . '.js');
    @endphp --}}


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- @vite('resources/js/app.js') --}}

    <!-- add this code -->
    {{-- <link rel="stylesheet" href="{{ $css }}" id="css">
    <script src="{{ $js }}" id="js"></script> --}}

    {{-- <script>
        window.RECAPTCHA_SITE_KEY = "{{ config('services.recaptcha.key') }}";
    </script> --}}


    {{-- <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.key') }}"></script>

    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LdWR14rAAAAALerCoTtXI0Fq-FMCUk8XGlffOjQ"></script>

    <script>
        function onClick(e) {
            e.preventDefault();
            grecaptcha.enterprise.ready(async () => {
                const token = await grecaptcha.enterprise.execute('6LdWR14rAAAAALerCoTtXI0Fq-FMCUk8XGlffOjQ', {
                    action: 'LOGIN'
                });
            });
        }
    </script> --}}

    <script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
    <script src="https://widget.cloudinary.com/v2.0/global/all.js" type="text/javascript"></script>
    <!-- PWA  -->
    <meta name="theme-color" content="#6777ef" />
    <!-- Favicons -->
    <link href="{{ asset('app_logo.png') }}" rel="icon">
    <link rel="apple-touch-icon" href="{{ asset('app_logo.png') }}">
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    {{-- Test git pull push --}}
    <style>
        [v-cloak]>* {
            display: none
        }

        [v-cloak]::before {
            content: "Loading…";
            margin: 0 auto;
        }
    </style>
</head>

{{-- <body class="hold-transition sidebar-mini"> --}}

<body class="layout-top-nav">


    <div id="app" v-cloak></div>




    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleMenuIcon = document.getElementById('toggleMenuIcon');
            const body = document.querySelector('body');

            if (toggleMenuIcon) {
                toggleMenuIcon.addEventListener('click', () => {
                    if (body.classList.contains('sidebar-collapse')) {
                        localStorage.setItem('sidebarState', 'expanded');
                    } else {
                        localStorage.setItem('sidebarState', 'collapsed');
                    }
                });

                const sidebarState = localStorage.getItem('sidebarState');
                if (sidebarState === 'collapsed') {
                    body.classList.add('sidebar-collapse');
                }
            }
        });
    </script>

    <script src="{{ asset('/sw.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script>
    <script>
        if (!navigator.serviceWorker.controller) {
            navigator.serviceWorker.register("/sw.js").then(function(reg) {
                console.log("Service worker has been registered for scope: " + reg.scope);
            });
        }
    </script>
</body>



</html>
