@extends('layouts.app')

@section('title', 'Terms of Service — SIGARDA')
@section('meta_description', 'Terms of Service for SIGARDA (Sistem Informasi Gerbang Arsip Digital Pegawai) — Ministry of Religious Affairs, Pesisir Selatan.')
@section('og_title', 'Terms of Service — SIGARDA')
@section('og_description', 'Official Terms of Service for using SIGARDA (Sistem Informasi Gerbang Arsip Digital Pegawai).')

@section('content')
    <!-- HERO -->
    <header class="py-5 bg-soft border-bottom">
        <div class="container">
            <span class="badge badge-soft rounded-pill mb-2">Policy Document</span>
            <h1 class="fw-bold mb-2">Terms of Service (EN) — Ketentuan Layanan (ID)</h1>
            <p class="text-muted mb-0">Version: <strong>1.0</strong> · Effective Date: <strong>9 September 2025</strong></p>
            <small class="text-muted">System: SIGARDA — <em>Sistem Informasi Gerbang Arsip Digital Pegawai</em></small>
            <div class="mt-3 d-flex gap-2 flex-wrap lang-switch">
                <div class="btn-group" role="group" aria-label="Language switch">
                    <button class="btn btn-outline-success btn-sm active" id="btn-en" data-lang="en">English</button>
                    <button class="btn btn-outline-secondary btn-sm" id="btn-id" data-lang="id">Bahasa Indonesia</button>
                </div>
                <a href="/" class="btn btn-outline-success btn-sm">← Back to Home</a>
                <a href="/privacy-policy" class="btn btn-outline-secondary btn-sm">Privacy Policy</a>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">🖨️ Print</button>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="py-4">
        <div class="container">
            <div class="row g-4">
                <!-- TOC -->
                <aside class="col-lg-4">
                    <div class="card sticky-top toc" style="top: 90px;">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Table of Contents</h5>
                            <ol class="mb-0" id="toc-en">
                                <li><a href="#en-overview">Overview</a></li>
                                <li><a href="#en-acceptance">Acceptance of Terms</a></li>
                                <li><a href="#en-eligibility">Eligibility</a></li>
                                <li><a href="#en-accounts">Accounts & Security</a></li>
                                <li><a href="#en-use">Permitted Use</a></li>
                                <li><a href="#en-restrictions">Restrictions</a></li>
                                <li><a href="#en-ip">Intellectual Property</a></li>
                                <li><a href="#en-license">License & Access</a></li>
                                <li><a href="#en-thirdparty">Third‑Party Services</a></li>
                                <li><a href="#en-availability">Service Availability</a></li>
                                <li><a href="#en-changes">Changes to Service</a></li>
                                <li><a href="#en-disclaimer">Disclaimer of Warranties</a></li>
                                <li><a href="#en-liability">Limitation of Liability</a></li>
                                <li><a href="#en-indemnity">Indemnification</a></li>
                                <li><a href="#en-termination">Termination</a></li>
                                <li><a href="#en-force-majeure">Force Majeure</a></li>
                                <li><a href="#en-law">Governing Law</a></li>
                                <li><a href="#en-contact">Contact</a></li>
                            </ol>
                            <ol class="mb-0 d-none" id="toc-id">
                                <li><a href="#id-ringkasan">Ikhtisar</a></li>
                                <li><a href="#id-penerimaan">Penerimaan Ketentuan</a></li>
                                <li><a href="#id-kelayakan">Kelayakan</a></li>
                                <li><a href="#id-akun">Akun & Keamanan</a></li>
                                <li><a href="#id-penggunaan">Penggunaan yang Diperbolehkan</a></li>
                                <li><a href="#id-larangan">Larangan</a></li>
                                <li><a href="#id-hki">Hak Kekayaan Intelektual</a></li>
                                <li><a href="#id-lisensi">Lisensi & Akses</a></li>
                                <li><a href="#id-integrasi">Layanan Pihak Ketiga</a></li>
                                <li><a href="#id-ketersediaan">Ketersediaan Layanan</a></li>
                                <li><a href="#id-perubahan">Perubahan Layanan</a></li>
                                <li><a href="#id-penafian">Penafian Jaminan</a></li>
                                <li><a href="#id-tanggungjawab">Batasan Tanggung Jawab</a></li>
                                <li><a href="#id-gantirugi">Ganti Rugi</a></li>
                                <li><a href="#id-pengakhiran">Pengakhiran</a></li>
                                <li><a href="#id-force-majeure">Kejadian Kahar</a></li>
                                <li><a href="#id-hukum">Hukum yang Berlaku</a></li>
                                <li><a href="#id-kontak">Kontak</a></li>
                            </ol>
                        </div>
                    </div>
                </aside>

                <!-- DOCUMENT: ENGLISH PRIMARY -->
                <section class="col-lg-8 doc">
                    <div class="card border-0 shadow-sm" id="lang-en">
                        <div class="card-body">
                            <h2 class="mb-3" id="en-overview">1) Overview</h2>
                            <p>These Terms of Service ("Terms") govern your use of SIGARDA. By accessing or using the system, you agree to be bound by these Terms and applicable laws.</p>

                            <h2 id="en-acceptance">2) Acceptance of Terms</h2>
                            <p>By using SIGARDA, you acknowledge that you have read and agree to comply with these Terms, the <a href="/privacy-policy">Privacy Policy</a>, and applicable government regulations (SPBE, data‑protection, and archival laws).</p>

                            <h2 id="en-eligibility">3) Eligibility</h2>
                            <p>SIGARDA is intended for official use by authorized personnel (ASN/PPPK and relevant officers). It is not available for public use.</p>

                            <h2 id="en-accounts">4) Accounts & Security</h2>
                            <ul>
                                <li>You are responsible for safeguarding your credentials and activities under your account.</li>
                                <li>Use strong passwords and enable <strong>MFA</strong> (if available).</li>
                                <li>Activities may be logged in audit trails for compliance and security.</li>
                            </ul>

                            <h2 id="en-use">5) Permitted Use</h2>
                            <p>Use SIGARDA solely for authorized government and employment‑related tasks, including digital archiving, verification, reporting, and official administrative services.</p>

                            <h2 id="en-restrictions">6) Restrictions</h2>
                            <ul>
                                <li>Unauthorized access or data misuse is prohibited.</li>
                                <li>Uploading malware, disrupting services, or breaching security is strictly forbidden.</li>
                                <li>Credentials must not be shared or transferred.</li>
                            </ul>

                            <h2 id="en-ip">7) Intellectual Property</h2>
                            <p>All rights to the software, UI, logos, and documentation belong to the managing institution (MoRA Pesisir Selatan), unless otherwise stated. Rights in personnel documents follow archival law.</p>

                            <h2 id="en-license">8) License & Access</h2>
                            <p>We grant authorized users a limited, non‑exclusive, non‑transferable right to access and use SIGARDA according to assigned roles. Access may be revoked upon violations or security risks.</p>

                            <h2 id="en-thirdparty">9) Third‑Party Services</h2>
                            <ul>
                                <li><strong>BSrE/BSSN e‑Signature:</strong> Users must safeguard their devices and certificates.</li>
                                <li><strong>Storage/Sync:</strong> If enabled, integrations with object storage/Google Drive follow provider policies and DPAs; necessary metadata exchange may occur.</li>
                                <li><strong>Notifications:</strong> Email/SMS/WhatsApp (optional) via official providers, based on lawful basis/consent.</li>
                            </ul>

                            <h2 id="en-availability">10) Service Availability & Maintenance</h2>
                            <ul>
                                <li>We strive for reasonable availability; scheduled/incident maintenance may cause temporary disruption.</li>
                                <li>Maintenance notices may appear via banners or official channels.</li>
                                <li>Backups are performed per policy; recovery is prioritised by criticality.</li>
                            </ul>

                            <h2 id="en-changes">11) Changes to Service & Terms</h2>
                            <p>We may update features or these Terms to meet regulatory/operational needs. Material changes will be communicated via official channels; the effective date appears at the top.</p>

                            <h2 id="en-disclaimer">12) Disclaimer of Warranties</h2>
                            <p>SIGARDA is provided "as is" and "as available". To the extent permitted by law, we disclaim implied warranties including fitness for a particular purpose, non‑infringement, and uninterrupted availability.</p>

                            <h2 id="en-liability">13) Limitation of Liability</h2>
                            <p>To the extent allowed by law, we shall not be liable for indirect, incidental, special, consequential, or data/profit losses arising from use of SIGARDA. Maximum liability (if any) is limited to what is expressly required by applicable law.</p>

                            <h2 id="en-indemnity">14) Indemnification</h2>
                            <p>You agree to indemnify and hold us harmless from third‑party claims resulting from your breach of these Terms, violation of law, or misuse through your account, except where caused by our negligence.</p>

                            <h2 id="en-termination">15) Termination</h2>
                            <p>We may suspend or terminate access upon violations, security risks, or per internal policy. Data and archival obligations remain subject to archival and legal requirements.</p>

                            <h2 id="en-force-majeure">16) Force Majeure</h2>
                            <p>We are not responsible for delays/failures due to events beyond reasonable control (disasters, power/network failures, government actions, widescale cyberattacks, etc.).</p>

                            <h2 id="en-law">17) Governing Law & Dispute Resolution</h2>
                            <ul>
                                <li>These Terms are governed by the laws of the Republic of Indonesia.</li>
                                <li>Disputes shall be settled amicably first; if unresolved, via competent courts where the institution is domiciled.</li>
                            </ul>

                            <h2 id="en-contact">18) Contact</h2>
                            <p>For questions about these Terms:<br>
                                <strong>PPID/DPO SIGARDA</strong><br>
                                Email: [pessel@kemenag.go.id]<br>
                                Address: [MH2H+9PW, Jl. Imam Bonjol, Painan, IV Jurai, Pesisir Selatan, West Sumatra 25651]<br>
                                Phone: [(0756) 21305]
                            </p>

                            <hr>
                            <p class="text-muted">Last updated: 9 September 2025 · <a href="/">Back to SIGARDA Homepage</a></p>
                            <p class="text-muted mb-0">Quick links: <a href="/#about">About</a> · <a href="/#features">Features</a> · <a href="/privacy-policy">Privacy Policy</a></p>
                        </div>
                    </div>

                    <!-- DOCUMENT: INDONESIAN -->
                    <div class="card border-0 shadow-sm d-none" id="lang-id">
                        <div class="card-body">
                            <h2 class="mb-3" id="id-ringkasan">1) Ikhtisar</h2>
                            <p>Ketentuan Layanan ("Ketentuan") ini mengatur penggunaan SIGARDA. Dengan mengakses atau menggunakan sistem, Anda setuju terikat pada Ketentuan dan peraturan perundang‑undangan yang berlaku.</p>

                            <h2 id="id-penerimaan">2) Penerimaan Ketentuan</h2>
                            <p>Dengan menggunakan SIGARDA, Anda menyatakan telah membaca dan menyetujui Ketentuan ini, <a href="/privacy-policy">Kebijakan Privasi</a>, serta regulasi pemerintah yang berlaku (SPBE, perlindungan data, dan kearsipan).</p>

                            <h2 id="id-kelayakan">3) Kelayakan</h2>
                            <p>SIGARDA ditujukan untuk penggunaan kedinasan oleh pegawai/pejabat berwenang (ASN/PPPK dan pejabat terkait). Tidak tersedia untuk publik umum.</p>

                            <h2 id="id-akun">4) Akun & Keamanan</h2>
                            <ul>
                                <li>Anda bertanggung jawab menjaga kredensial dan seluruh aktivitas pada akun Anda.</li>
                                <li>Gunakan kata sandi kuat dan aktifkan <strong>MFA</strong> (bila tersedia).</li>
                                <li>Aktivitas dapat dicatat dalam <em>audit trail</em> untuk kepatuhan dan keamanan.</li>
                            </ul>

                            <h2 id="id-penggunaan">5) Penggunaan yang Diperbolehkan</h2>
                            <p>Gunakan SIGARDA hanya untuk tugas kedinasan dan kepegawaian yang berwenang, termasuk pengarsipan digital, verifikasi, pelaporan, dan layanan administrasi resmi.</p>

                            <h2 id="id-larangan">6) Larangan</h2>
                            <ul>
                                <li>Akses tanpa kewenangan atau penyalahgunaan data.</li>
                                <li>Mengunggah malware, mengganggu layanan, atau melanggar keamanan.</li>
                                <li>Berbagi atau memindahtangankan kredensial.</li>
                            </ul>

                            <h2 id="id-hki">7) Hak Kekayaan Intelektual</h2>
                            <p>Seluruh hak atas perangkat lunak, antarmuka, logo, dan dokumentasi dimiliki instansi pengelola (Kemenag Pesisir Selatan), kecuali dinyatakan lain. Hak atas dokumen kepegawaian mengikuti hukum kearsipan.</p>

                            <h2 id="id-lisensi">8) Lisensi & Akses</h2>
                            <p>Kami memberikan hak akses terbatas, non‑eksklusif, tidak dapat dipindahtangankan kepada pengguna berwenang sesuai perannya. Akses dapat dicabut apabila terjadi pelanggaran atau risiko keamanan.</p>

                            <h2 id="id-integrasi">9) Layanan Pihak Ketiga</h2>
                            <ul>
                                <li><strong>TTE BSrE/BSSN:</strong> Pengguna wajib menjaga perangkat & sertifikatnya.</li>
                                <li><strong>Penyimpanan/Sinkronisasi:</strong> Jika diaktifkan, integrasi dengan object storage/Google Drive tunduk pada kebijakan penyedia & DPA; pertukaran metadata yang diperlukan dapat terjadi.</li>
                                <li><strong>Notifikasi:</strong> Email/SMS/WhatsApp (opsional) melalui penyedia resmi, berdasarkan dasar hukum/persetujuan yang relevan.</li>
                            </ul>

                            <h2 id="id-ketersediaan">10) Ketersediaan Layanan & Pemeliharaan</h2>
                            <ul>
                                <li>Kami berupaya menjaga ketersediaan secara wajar; pemeliharaan terjadwal/insidentil dapat menyebabkan gangguan sementara.</li>
                                <li>Pemberitahuan pemeliharaan disampaikan melalui kanal resmi atau banner aplikasi.</li>
                                <li>Cadangan data dilakukan sesuai kebijakan; pemulihan diprioritaskan berdasarkan tingkat kritikal.</li>
                            </ul>

                            <h2 id="id-perubahan">11) Perubahan Layanan & Ketentuan</h2>
                            <p>Kami dapat memperbarui fitur atau Ketentuan ini untuk memenuhi kebutuhan regulasi/operasional. Perubahan material akan diinformasikan; tanggal berlaku tercantum di bagian atas.</p>

                            <h2 id="id-penafian">12) Penafian Jaminan</h2>
                            <p>SIGARDA disediakan "sebagaimana adanya" dan "sebagaimana tersedia". Sepanjang diizinkan hukum, kami menafikan jaminan tersirat, termasuk kesesuaian untuk tujuan tertentu, non‑pelanggaran, dan ketersediaan tanpa gangguan.</p>

                            <h2 id="id-tanggungjawab">13) Batasan Tanggung Jawab</h2>
                            <p>Sepanjang diperbolehkan hukum, kami tidak bertanggung jawab atas kerugian tidak langsung, insidental, khusus, konsekuensial, atau kehilangan data/keuntungan akibat penggunaan SIGARDA. Batas tanggung jawab (jika ada) mengikuti ketentuan hukum yang berlaku.</p>

                            <h2 id="id-gantirugi">14) Ganti Rugi</h2>
                            <p>Anda setuju untuk membebaskan dan mengganti rugi kami dari klaim pihak ketiga akibat pelanggaran Ketentuan, pelanggaran hukum, atau penyalahgunaan melalui akun Anda, sepanjang bukan akibat kelalaian kami.</p>

                            <h2 id="id-pengakhiran">15) Pengakhiran</h2>
                            <p>Kami dapat menangguhkan/mengakhiri akses jika terjadi pelanggaran, risiko keamanan, atau berdasarkan kebijakan internal. Kewajiban terkait data & kearsipan mengikuti ketentuan peraturan.</p>

                            <h2 id="id-force-majeure">16) Kejadian Kahar (Force Majeure)</h2>
                            <p>Kami tidak bertanggung jawab atas keterlambatan/kegagalan layanan karena peristiwa di luar kendali wajar (bencana, kegagalan listrik/jaringan, kebijakan pemerintah, serangan siber skala besar, dll.).</p>

                            <h2 id="id-hukum">17) Hukum yang Berlaku & Sengketa</h2>
                            <ul>
                                <li>Ketentuan ini diatur hukum Negara Republik Indonesia.</li>
                                <li>Sengketa diselesaikan musyawarah; bila tidak tercapai, melalui peradilan berwenang di domisili instansi.</li>
                            </ul>

                            <h2 id="id-kontak">18) Kontak</h2>
                            <p>Pertanyaan terkait Ketentuan ini:<br>
                                <strong>PPID/DPO SIGARDA</strong><br>
                                Email: [pessel@kemenag.go.id]<br>
                                Alamat: [MH2H+9PW, Jl. Imam Bonjol, Painan, Kec. IV Jurai, Kab. Pesisir Selatan, Sumatera Barat 25651]<br>
                                Telepon: [(0756) 21305]
                            </p>

                            <hr>
                            <p class="text-muted">Terakhir diperbarui: 9 September 2025 · <a href="/">Kembali ke Beranda SIGARDA</a></p>
                            <p class="text-muted mb-0">Tautan cepat: <a href="/#about">Tentang</a> · <a href="/#features">Fitur</a> · <a href="/privacy-policy">Kebijakan Privasi</a></p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
@endsection
