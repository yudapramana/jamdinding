{{-- resources/views/maintenance.blade.php --}}
@php
    // Variabel opsional yang bisa kamu lempar dari `php artisan down --render`
    // $message     = $message     ?? 'Kami sedang melakukan pemeliharaan sistem.';
    // $until       = $until       ?? null; // string waktu, mis: '09:30 WIB'
    // $retryAfter  = $retryAfter  ?? null; // detik, dari opsi --retry
    // $contact     = $contact     ?? 'helpdesk@kemenag-pessel.go.id';
    $appName = config('app.name', 'SIGARDA');
@endphp
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $appName }} — Maintenance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <style>
        :root {
            --bg: #0b1220;
            --card: #121a2b;
            --muted: #9fb0d0;
            --text: #e7eefc;
            --accent: #2bb4ff;
            --ok: #62d26f;
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            background: radial-gradient(1200px 600px at 10% 10%, #14203b 0%, #0b1220 60%) no-repeat, var(--bg);
            color: var(--text);
            line-height: 1.55;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .wrap {
            width: 100%;
            max-width: 720px
        }

        .card {
            background: linear-gradient(180deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .01));
            border: 1px solid rgba(255, 255, 255, .06);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .35);
            backdrop-filter: blur(6px);
        }

        .badge {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            font-size: 12px;
            color: var(--muted);
            border: 1px solid rgba(255, 255, 255, .1);
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .02)
        }

        .title {
            font-size: 28px;
            margin: 14px 0 6px
        }

        .muted {
            color: var(--muted)
        }

        .row {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 14px
        }

        .pill {
            border: 1px solid rgba(255, 255, 255, .1);
            padding: 10px 12px;
            border-radius: 12px;
            min-width: 140px
        }

        .btn {
            appearance: none;
            border: 1px solid rgba(255, 255, 255, .14);
            background: transparent;
            color: var(--text);
            padding: 12px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600
        }

        .btn:hover {
            border-color: rgba(255, 255, 255, .28)
        }

        .btn-primary {
            background: linear-gradient(180deg, rgba(43, 180, 255, .25), rgba(43, 180, 255, .15));
            border-color: rgba(43, 180, 255, .6)
        }

        .btn-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px
        }

        .footer {
            margin-top: 18px;
            font-size: 13px;
            color: var(--muted)
        }

        .spinner {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .18);
            border-top-color: var(--accent);
            animation: spin 1s linear infinite
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        .timer {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            color: var(--ok)
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            letter-spacing: .2px
        }

        .logo-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 18px var(--accent)
        }

        .hr {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .08), transparent);
            border: 0;
            margin: 18px 0
        }

        @media (max-width:420px) {
            .title {
                font-size: 22px
            }

            .pill {
                min-width: unset;
                flex: 1
            }
        }
    </style>
</head>

<body>
    <div class="wrap" role="main" aria-labelledby="maint-title">
        <div class="card">
            <div class="badge">
                <span class="spinner" aria-hidden="true"></span>
                <span>Maintenance sedang berlangsung</span>
            </div>

            <h1 id="maint-title" class="title">{{ $appName }} sementara tidak dapat diakses</h1>
            <p class="muted" id="maint-desc">
                {{ $message ?? 'Kami sedang melakukan pemeliharaan untuk meningkatkan kinerja dan keamanan sistem. Mohon menunggu sebentar.' }}
            </p>

            <div class="row" aria-live="polite">
                @if (!empty($until))
                    <div class="pill">
                        <strong>Perkiraan selesai</strong><br>
                        <span class="muted">{{ $until }}</span>
                    </div>
                @endif

                @if (!empty($retryAfter))
                    <div class="pill">
                        <strong>Coba lagi dalam</strong><br>
                        <span class="timer" id="retry-timer" data-retry="{{ (int) $retryAfter }}"></span>
                    </div>
                @endif

                <div class="pill">
                    <strong>Status</strong><br>
                    <span class="muted">HTTP 503 — Service Unavailable</span>
                </div>
            </div>

            <div class="btn-row">
                <button class="btn btn-primary" onclick="location.reload()">Muat Ulang</button>
                <button class="btn" onclick="window.history.length>1?history.back():location.reload()">Kembali</button>
                @if (!empty($contact))
                    <a class="btn" href="mailto:{{ $contact }}">Hubungi Admin</a>
                @endif
            </div>

            <hr class="hr">

            <div class="footer">
                &copy; {{ date('Y') }} {{ $appName }} • Dikelola oleh Kemenag Pesisir Selatan.
                <br>
                <span class="muted">Keamanan data menjadi prioritas kami. Terima kasih atas pengertiannya.</span>
            </div>
        </div>
    </div>

    @if (!empty($retryAfter))
        <script>
            (function() {
                var span = document.getElementById('retry-timer');
                var s = parseInt(span?.dataset.retry || '0', 10);
                if (!span || !s) return;

                function fmt(sec) {
                    var h = Math.floor(sec / 3600),
                        m = Math.floor((sec % 3600) / 60),
                        d = sec % 60;
                    return (h ? (h + 'j ') : '') + (m ? (m + 'm ') : '') + d + 'd';
                }

                function tick() {
                    span.textContent = fmt(s);
                    if (s <= 0) return;
                    s--;
                    setTimeout(tick, 1000);
                }
                tick();
            })();
        </script>
    @endif
</body>

</html>
