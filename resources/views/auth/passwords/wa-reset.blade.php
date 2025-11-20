@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 fw-semibold mb-3">Masukkan OTP &amp; Password Baru</h1>

                        {{-- Alert error global (opsional) --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.wa.reset.submit') }}" novalidate>
                            @csrf

                            {{-- Phone --}}
                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor WhatsApp</label>
                                <div class="input-group">
                                    {{-- <span class="input-group-text">+62</span> --}}
                                    <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="081234567890" inputmode="numeric" value="{{ old('phone', $phone ?? '') }}" required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Nomor WhatsApp wajib diisi.</div>
                                    @enderror
                                </div>
                                <div class="form-text">Contoh: 081234XXXXX</div>
                            </div>

                            {{-- OTP --}}
                            <div class="mb-3">
                                <label for="otp" class="form-label">Kode OTP (6 digit)</label>
                                <input type="text" id="otp" name="otp" class="form-control @error('otp') is-invalid @enderror" placeholder="••••••" maxlength="6" inputmode="numeric" pattern="\d{6}" required>
                                @error('otp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">Masukkan 6 digit OTP.</div>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div class="mb-3">
                                <label for="password" class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePass" tabindex="-1">
                                        <span class="d-inline" data-show="t">Show</span>
                                        <span class="d-none" data-show="f">Hide</span>
                                    </button>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Password minimal 8 karakter.</div>
                                    @enderror
                                </div>
                                <div class="form-text">Gunakan kombinasi huruf, angka, dan simbol untuk keamanan lebih baik.</div>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePass2" tabindex="-1">
                                        <span class="d-inline" data-show="t">Show</span>
                                        <span class="d-none" data-show="f">Hide</span>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    Reset Password
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="small">
                            Belum menerima OTP? Pastikan nomor benar dan coba kirim ulang dari halaman
                            <a href="{{ route('password.wa.request') }}" class="link-primary text-decoration-underline">permintaan OTP</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script kecil untuk UX --}}
    <script>
        (function() {
            const phone = document.getElementById('phone');
            const otp = document.getElementById('otp');

            // Normalisasi ringan: hanya angka
            [phone, otp].forEach(el => {
                el && el.addEventListener('input', () => {
                    el.value = el.value.replace(/[^\d]/g, '');
                });
            });

            // Toggle password visibility
            function attachToggle(btnId, inputId) {
                const btn = document.getElementById(btnId);
                const input = document.getElementById(inputId);
                if (!btn || !input) return;
                btn.addEventListener('click', () => {
                    const showing = input.type === 'text';
                    input.type = showing ? 'password' : 'text';
                    // swap label
                    btn.querySelector('[data-show="t"]').classList.toggle('d-none', !showing);
                    btn.querySelector('[data-show="f"]').classList.toggle('d-none', showing);
                });
            }
            attachToggle('togglePass', 'password');
            attachToggle('togglePass2', 'password_confirmation');

            // Bootstrap 5 validation style (opsional)
            const forms = document.querySelectorAll('form[novalidate]');
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
@endsection
