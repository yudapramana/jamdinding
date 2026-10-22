<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi Captcha - Detail Peserta MTQ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
        }

        .captcha-card {
            max-width: 380px;
        }

        .captcha-img {
            height: 40px;
            width: 120px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }

        .captcha-input {
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="card shadow-sm captcha-card w-100">
            <div class="card-body p-4">

                <div class="text-center mb-3">
                    <i class="fa-solid fa-shield-halved fa-2x text-success mb-2"></i>
                    <div class="fw-semibold">Verifikasi Keamanan</div>
                    <div class="text-muted small">
                        Masukkan kode pada gambar untuk melihat detail peserta.
                    </div>
                </div>

                <form method="POST" action="{{ route('public.participant.verify', $uuid) }}" autocomplete="off">
                    @csrf

                    <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                        <img id="captchaImg" class="captcha-img" alt="Captcha" data-src="{{ route('captcha', ['for' => 'participant']) }}" src="{{ route('captcha', ['for' => 'participant']) }}&t={{ now()->timestamp }}">

                        <button type="button" id="refreshCaptcha" class="btn btn-outline-secondary btn-sm" title="Ganti kode">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>

                    <div class="mb-3">
                        <input type="text" name="captcha" maxlength="10" required autofocus class="form-control text-center captcha-input @error('captcha') is-invalid @enderror" placeholder="Kode captcha">

                        @error('captcha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa-solid fa-eye me-1"></i> Lihat Data Peserta
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.getElementById('refreshCaptcha').addEventListener('click', function() {
            const img = document.getElementById('captchaImg');
            img.src = img.dataset.src + '&t=' + Date.now();
        });
    </script>
</body>

</html>
