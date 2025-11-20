<!DOCTYPE html>
<html lang="en"> {{-- default EN, akan diubah via JS --}}

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'SIGARDA — Personnel Digital Archiving System (ASN)')</title>
    <meta name="description" content="@yield('meta_description', 'SIGARDA is a Personnel Digital Archiving System for the Ministry of Religious Affairs (Pesisir Selatan) — secure, structured, and regulation-compliant.')" />

    <link rel="canonical" href="https://sigarda.kemenagpessel.com/" />

    <meta property="og:title" content="@yield('og_title', 'SIGARDA — Personnel Digital Archiving System (ASN)')" />
    <meta property="og:description" content="@yield('og_description', 'Secure, structured, and regulation-compliant management of personnel archives.')" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://sigarda.kemenagpessel.com/" />
    <meta property="og:image" content="https://sigarda.kemenagpessel.com/assets/sigarda-og.png" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:locale:alternate" content="id_ID" />

    <link rel="icon" href="/assets/sigarda-icon.png" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Styles (inline cepat; boleh pindah ke /public/css/sigarda.css) --}}
    <style>
        :root {
            --sigarda: #0f766e;
        }

        body {
            background: #f8f9fa
        }

        .text-sigarda {
            color: var(--sigarda) !important;
        }

        .bg-soft {
            background: linear-gradient(180deg, #ecfeff, transparent)
        }

        .badge-soft {
            background: #ccfbf1;
            color: #134e4a;
            border: 1px solid #99f6e4
        }

        .list-check {
            list-style: none;
            padding-left: 0
        }

        .list-check li {
            position: relative;
            padding-left: 1.6rem;
            margin: .4rem 0
        }

        .list-check li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--sigarda);
            font-weight: 700
        }

        .kbd {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .85rem;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: .1rem .4rem;
            border-radius: .35rem
        }

        .hero-shadow {
            box-shadow: 0 8px 32px rgba(15, 118, 110, .12)
        }

        .note {
            background: #fffbeb;
            border: 1px solid #fef08a
        }

        .lang-switch .btn.active {
            pointer-events: none
        }

        @media print {

            .lang-switch,
            .btn,
            nav,
            footer {
                display: none !important;
            }

            body {
                background: #fff
            }
        }
    </style>

    {{-- Structured Data --}}
    <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"WebApplication",
    "name":"SIGARDA",
    "url":"https://sigarda.kemenagpessel.com/",
    "applicationCategory":"GovernmentApplication",
    "operatingSystem":"Web",
    "description":"Personnel Digital Archiving System for secure, compliant management of civil servant records.",
    "provider":{"@type":"GovernmentOrganization","name":"Office of the Ministry of Religious Affairs — Pesisir Selatan"},
    "privacyPolicy":"https://sigarda.kemenagpessel.com/privacy-policy"
  }
  </script>

    @stack('head')
</head>

<body>
    {{-- NAVBAR --}}
    @include('partials.navbar')

    {{-- HEADER / HERO --}}
    @yield('header')

    {{-- CONTENT --}}
    <main>
        @yield('content')
    </main>

    {{-- FOOTER --}}
    @include('partials.footer')

    {{-- Footer year --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const y = document.getElementById('year');
            if (y) y.textContent = new Date().getFullYear();
        });
    </script>

    {{-- Bootstrap bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Language persistence & toggling --}}
    <script src="/js/lang.js"></script>

    @stack('body')
</body>

</html>
