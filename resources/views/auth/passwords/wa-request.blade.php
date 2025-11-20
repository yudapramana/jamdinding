@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 fw-semibold mb-2">Reset Password via WhatsApp</h1>
                        <p class="text-muted mb-4">Masukkan nomor WhatsApp Anda untuk menerima kode OTP.</p>

                        {{-- Form kirim OTP --}}
                        <form id="request-otp-form" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text">+62</span>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="81234567890" inputmode="numeric" required>
                                    <div class="invalid-feedback">
                                        Nomor WhatsApp wajib diisi.
                                    </div>
                                </div>
                                <div class="form-text">Format contoh: 81234567890 atau 081234567890</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" id="btn-submit" class="btn btn-primary">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                    Kirim OTP
                                </button>
                            </div>
                        </form>

                        <div id="req-result" class="mt-3" style="min-height: 1.5rem;"></div>

                        <hr class="my-4">

                        <a href="{{ route('password.wa.reset') }}" class="link-primary text-decoration-underline">
                            Sudah punya OTP? Masukkan di sini
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script --}}
    <script>
        (function() {
            const form = document.getElementById('request-otp-form');
            const phoneInput = document.getElementById('phone');
            const btn = document.getElementById('btn-submit');
            const spinner = btn.querySelector('.spinner-border');
            const result = document.getElementById('req-result');

            function showAlert(message, type = 'success') {
                result.innerHTML = `<div class="alert alert-${type} mb-0" role="alert">${message}</div>`;
            }

            function normalizePhone(raw) {
                let p = raw.replace(/\s+/g, '');
                if (p.startsWith('+62')) p = p.slice(3);
                if (p.startsWith('62')) p = p.slice(2);
                if (p.startsWith('0')) p = p.slice(1);
                return '0' + p; // kirim 08… ke API kamu; controller akan normalisasi ke 62…
            }

            const csrf =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                document.querySelector('input[name="_token"]')?.value || '';

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                result.innerHTML = '';

                // bootstrap client-side validation sederhana
                if (!phoneInput.value.trim()) {
                    phoneInput.classList.add('is-invalid');
                    return;
                } else {
                    phoneInput.classList.remove('is-invalid');
                }

                const phone = normalizePhone(phoneInput.value.trim());

                // UI loading state
                btn.disabled = true;
                spinner.classList.remove('d-none');

                try {
                    const res = await fetch('{{ route('api.password.wa.request') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf, // <— penting untuk web middleware
                        },
                        credentials: 'same-origin', // <— kirim cookie sesi (wajib)
                        body: JSON.stringify({
                            phone
                        })
                    });

                    const json = await res.json().catch(() => null);

                    if (res.ok && json && (json.ok === true || json.message)) {
                        showAlert(json.message || 'OTP dikirim.');
                    } else {
                        const msg = (json && (json.message || json.error)) || 'Gagal mengirim OTP.';
                        showAlert(msg, 'danger');
                    }
                } catch (err) {
                    showAlert('Terjadi kesalahan jaringan. Coba lagi.', 'danger');
                } finally {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                }
            });
        })();
    </script>
@endsection
