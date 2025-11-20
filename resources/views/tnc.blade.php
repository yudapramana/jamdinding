@extends('layouts.app')

@section('title', 'Terms & Conditions — SIGARDA')
@section('meta_description', 'Terms & Conditions for using SIGARDA (Sistem Informasi Gerbang Arsip Digital Pegawai) — Ministry of Religious Affairs, Pesisir Selatan.')
@section('og_title', 'Terms & Conditions — SIGARDA')
@section('og_description', 'Official Terms & Conditions for using SIGARDA for compliant and secure personnel archiving services.')

@section('content')
    <!-- HERO -->
    <header class="py-5 bg-soft border-bottom">
        <div class="container">
            <span class="badge badge-soft rounded-pill mb-2">Policy Document</span>
            <h1 class="fw-bold mb-2">Terms & Conditions (EN) — Syarat & Ketentuan (ID)</h1>
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
                                <li><a href="#en-intro">Introduction</a></li>
                                <li><a href="#en-accept">Acceptance of Terms</a></li>
                                <li><a href="#en-eligibility">User Eligibility</a></li>
                                <li><a href="#en-accounts">Accounts, Security & Authentication</a></li>
                                <li><a href="#en-permitted">Permitted Use</a></li>
                                <li><a href="#en-prohibited">Prohibited Use</a></li>
                                <li><a href="#en-ip">Intellectual Property</a></li>
                                <li><a href="#en-license">License & Access Rights</a></li>
                                <li><a href="#en-integrations">Third‑Party Integrations</a></li>
                                <li><a href="#en-availability">Service Availability & Maintenance</a></li>
                                <li><a href="#en-changes">Changes to Service & Terms</a></li>
                                <li><a href="#en-disclaimer">Disclaimer of Warranties</a></li>
                                <li><a href="#en-liability">Limitation of Liability</a></li>
                                <li><a href="#en-indemnity">Indemnification</a></li>
                                <li><a href="#en-termination">Termination of Access</a></li>
                                <li><a href="#en-force-majeure">Force Majeure</a></li>
                                <li><a href="#en-law">Governing Law & Disputes</a></li>
                                <li><a href="#en-contact">Official Contact</a></li>
                                <li><a href="#en-gov">Government‑Specific Provisions</a></li>
                                <li><a href="#en-defs">Definitions & Order of Precedence</a></li>
                            </ol>
                            <ol class="mb-0 d-none" id="toc-id">
                                <li><a href="#id-pendahuluan">Pendahuluan</a></li>
                                <li><a href="#id-penerimaan">Penerimaan Ketentuan</a></li>
                                <li><a href="#id-kelayakan">Kelayakan Pengguna</a></li>
                                <li><a href="#id-akun">Akun, Keamanan & Otentikasi</a></li>
                                <li><a href="#id-diizinkan">Penggunaan yang Diperbolehkan</a></li>
                                <li><a href="#id-larangan">Larangan Penggunaan</a></li>
                                <li><a href="#id-hki">Hak Kekayaan Intelektual</a></li>
                                <li><a href="#id-lisensi">Lisensi & Hak Akses</a></li>
                                <li><a href="#id-integrasi">Integrasi Pihak Ketiga</a></li>
                                <li><a href="#id-ketersediaan">Ketersediaan Layanan & Pemeliharaan</a></li>
                                <li><a href="#id-perubahan">Perubahan Layanan & Ketentuan</a></li>
                                <li><a href="#id-penafian">Penafian Jaminan</a></li>
                                <li><a href="#id-tanggungjawab">Batasan Tanggung Jawab</a></li>
                                <li><a href="#id-gantirugi">Ganti Rugi</a></li>
                                <li><a href="#id-pengakhiran">Pengakhiran Akses</a></li>
                                <li><a href="#id-force-majeure">Kejadian Kahar (Force Majeure)</a></li>
                                <li><a href="#id-hukum">Hukum yang Berlaku & Sengketa</a></li>
                                <li><a href="#id-kontak">Kontak Resmi</a></li>
                                <li><a href="#id-pemerintahan">Ketentuan Khusus Pemerintahan</a></li>
                                <li><a href="#id-definisi">Definisi & Urutan Prioritas</a></li>
                            </ol>
                        </div>
                    </div>
                </aside>

                <!-- DOCUMENT: EN (PRIMARY) -->
                <section class="col-lg-8 doc">
                    <div class="card border-0 shadow-sm" id="lang-en">
                        <div class="card-body">
                            <p><strong>System Owner:</strong> [Office of the Ministry of Religious Affairs, Pesisir Selatan] ("we/us")<br>
                                <strong>System:</strong> SIGARDA — Personnel Digital Archiving Information System.
                            </p>

                            <hr>
                            <h2 id="en-intro">1) Introduction</h2>
                            <p>These Terms & Conditions ("Terms") govern your use of SIGARDA by civil servants (ASN/PPPK), authorized officers, and relevant parties. By using SIGARDA, you confirm that you have read, understood, and agree to these Terms.</p>

                            <h2 id="en-accept">2) Acceptance of Terms</h2>
                            <ul>
                                <li>Your use of SIGARDA is subject to these Terms, the <a href="/privacy-policy">Privacy Policy</a>, and applicable laws and regulations (SPBE, archival, and data protection).</li>
                                <li>If you use SIGARDA on behalf of an institution, you represent that you are authorized to bind the institution to these Terms.</li>
                            </ul>

                            <h2 id="en-eligibility">3) User Eligibility</h2>
                            <p>Users are employees/officers with proper mandate or authority. The system is not intended for public use.</p>

                            <h2 id="en-accounts">4) Accounts, Security & Authentication</h2>
                            <ul>
                                <li>You are responsible for keeping credentials confidential and for activities under your account.</li>
                                <li>Use strong passwords and enable <strong>MFA</strong> (if available).</li>
                                <li>Shared accounts are prohibited; access must be <em>person‑accountable</em>.</li>
                                <li>Activities are logged in audit trails for security and compliance purposes.</li>
                            </ul>

                            <h2 id="en-permitted">5) Permitted Use</h2>
                            <p>Use SIGARDA only for official purposes, including:</p>
                            <ul>
                                <li>Managing personnel archives in line with the JRA.</li>
                                <li>Administrative services and issuance of employment documents.</li>
                                <li>Verification, reporting, and audits under mandate.</li>
                            </ul>

                            <h2 id="en-prohibited">6) Prohibited Use</h2>
                            <ul>
                                <li>Access without authorization or beyond granted authority.</li>
                                <li>Altering/distributing data without legal basis.</li>
                                <li>Uploading harmful content (malware) or disrupting service integrity (e.g., <span class="kbd">DDoS</span>, <span class="kbd">bruteforce</span>).</li>
                                <li>Transferring credentials or <em>sessions</em> to others.</li>
                            </ul>

                            <h2 id="en-ip">7) Intellectual Property</h2>
                            <p>IP rights in the software, interface, logos, and guidance content belong to the managing institution, unless stated otherwise. Rights in personnel/archival documents follow applicable archival law.</p>

                            <h2 id="en-license">8) License & Access Rights</h2>
                            <p>We grant a limited, non‑exclusive, non‑transferable right for authorized users to access SIGARDA according to their roles. Rights may be revoked upon violations or security risks.</p>

                            <h2 id="en-integrations">9) Third‑Party Integrations</h2>
                            <ul>
                                <li><strong>e‑Signature (BSrE/BSSN):</strong> Use follows BSrE rules; users must protect their devices and certificates.</li>
                                <li><strong>Storage & Sync:</strong> If enabled, integration with object storage or Google Drive is subject to provider policies and DPAs. You agree to the necessary metadata exchange to deliver the service.</li>
                                <li><strong>Notifications:</strong> Email/SMS/WhatsApp (optional) via official providers based on appropriate legal basis/consent.</li>
                            </ul>

                            <h2 id="en-availability">10) Service Availability & Maintenance</h2>
                            <ul>
                                <li>We strive for reasonable availability. Scheduled/incidental maintenance may cause temporary disruption.</li>
                                <li>Maintenance notices are communicated via internal channels or in‑app banners.</li>
                                <li>Backups are performed per policy; recovery may be prioritized by criticality.</li>
                            </ul>

                            <h2 id="en-changes">11) Changes to Service & Terms</h2>
                            <p>We may update features or these Terms to align with regulatory/operational needs. Material changes will be announced via official channels. The effective date appears at the top.</p>

                            <h2 id="en-disclaimer">12) Disclaimer of Warranties</h2>
                            <p>SIGARDA is provided "as is" and "as available". To the extent permitted by law, we disclaim implied warranties including fitness for a particular purpose, non‑infringement, and uninterrupted availability.</p>

                            <h2 id="en-liability">13) Limitation of Liability</h2>
                            <p>To the extent permitted by law, we are not liable for indirect, incidental, special, consequential damages, or loss of data/profits arising from use of SIGARDA. Any maximum liability (if applicable) is limited as expressly required by law.</p>

                            <h2 id="en-indemnity">14) Indemnification</h2>
                            <p>You agree to indemnify and hold us harmless from third‑party claims arising from breach of these Terms, violation of law, or misuse via your account, except where caused by our negligence.</p>

                            <h2 id="en-termination">15) Termination of Access</h2>
                            <p>We may suspend or terminate access in case of violations, security risks, or per internal policy. Data and archival obligations are handled according to archival and legal requirements.</p>

                            <h2 id="en-force-majeure">16) Force Majeure</h2>
                            <p>We are not responsible for delays/failures due to events beyond reasonable control (disasters, power/network failures, government actions, widescale cyberattacks, etc.).</p>

                            <h2 id="en-law">17) Governing Law & Disputes</h2>
                            <ul>
                                <li>These Terms are governed by the laws of the Republic of Indonesia.</li>
                                <li>Disputes are resolved amicably first; if unresolved, through courts with jurisdiction where the institution is domiciled.</li>
                            </ul>

                            <h2 id="en-contact">18) Official Contact</h2>
                            <p>Questions about these Terms:<br>
                                <strong>PPID/DPO SIGARDA</strong><br>
                                Email: [pessel@kemenag.go.id]<br>
                                Address: [MH2H+9PW, Jl. Imam Bonjol, Painan, IV Jurai, Pesisir Selatan, West Sumatra 25651]<br>
                                Phone: [(0756) 21305]
                            </p>

                            <h2 id="en-gov">19) Government‑Specific Provisions</h2>
                            <ul>
                                <li>Access based on assignment/authorization by competent officials and the <em>need‑to‑know</em> principle.</li>
                                <li>Public information services follow PPID mechanisms while protecting personal data.</li>
                                <li>e‑Signature implementation complies with <strong>BSrE/BSSN</strong> and the institution's information security policies.</li>
                            </ul>

                            <h2 id="en-defs">20) Definitions & Order of Precedence</h2>
                            <p>Terms not defined here refer to the Privacy Policy or applicable regulations. In the event of conflict: (1) laws and regulations, (2) internal SPBE/archival policies, (3) these Terms, (4) technical documentation.</p>

                            <hr>
                            <p class="text-muted">Last updated: 9 September 2025 · <a href="/">Back to SIGARDA Homepage</a></p>
                            <p class="text-muted mb-0">Quick links: <a href="/#about">About</a> · <a href="/#features">Features</a> · <a href="/privacy-policy">Privacy Policy</a></p>
                        </div>
                    </div>

                    <!-- DOCUMENT: ID -->
                    <div class="card border-0 shadow-sm d-none" id="lang-id">
                        <div class="card-body">
                            <p><strong>Pengelola Sistem:</strong> [Kantor Kementerian Agama Kabupaten Pesisir Selatan] ("Kami")<br>
                                <strong>Sistem:</strong> SIGARDA — Sistem Informasi Gerbang Arsip Digital Pegawai.
                            </p>

                            <hr>
                            <h2 id="id-pendahuluan">1) Pendahuluan</h2>
                            <p>Dokumen Syarat & Ketentuan ("Ketentuan") ini mengatur penggunaan SIGARDA oleh ASN/PPPK, pejabat berwenang, dan pihak terkait lainnya. Dengan menggunakan SIGARDA, Anda menyatakan telah membaca, memahami, dan menyetujui Ketentuan ini.</p>

                            <h2 id="id-penerimaan">2) Penerimaan Ketentuan</h2>
                            <ul>
                                <li>Penggunaan SIGARDA tunduk pada Ketentuan ini, <a href="/privacy-policy">Kebijakan Privasi</a>, serta peraturan yang berlaku (SPBE, kearsipan, dan perlindungan data pribadi).</li>
                                <li>Jika Anda menggunakan SIGARDA atas nama instansi, Anda menyatakan berwenang untuk mengikat instansi pada Ketentuan ini.</li>
                            </ul>

                            <h2 id="id-kelayakan">3) Kelayakan Pengguna</h2>
                            <p>Pengguna adalah pegawai/pejabat dengan mandat atau kewenangan yang sah. Sistem tidak ditujukan untuk publik umum.</p>

                            <h2 id="id-akun">4) Akun, Keamanan & Otentikasi</h2>
                            <ul>
                                <li>Anda bertanggung jawab menjaga kerahasiaan kredensial dan aktivitas pada akun Anda.</li>
                                <li>Gunakan kata sandi kuat dan aktifkan <strong>MFA</strong> (bila tersedia).</li>
                                <li>Akun bersama dilarang; akses harus <em>person‑accountable</em>.</li>
                                <li>Aktivitas dicatat dalam <em>audit trail</em> untuk keamanan dan kepatuhan.</li>
                            </ul>

                            <h2 id="id-diizinkan">5) Penggunaan yang Diperbolehkan</h2>
                            <p>Gunakan SIGARDA hanya untuk tujuan kedinasan, termasuk:</p>
                            <ul>
                                <li>Pengelolaan arsip kepegawaian sesuai JRA.</li>
                                <li>Layanan administrasi dan penerbitan dokumen kepegawaian.</li>
                                <li>Verifikasi, pelaporan, dan audit sesuai mandat.</li>
                            </ul>

                            <h2 id="id-larangan">6) Larangan Penggunaan</h2>
                            <ul>
                                <li>Akses tanpa kewenangan atau melampaui wewenang.</li>
                                <li>Mengubah/menyebarkan data tanpa dasar hukum.</li>
                                <li>Mengunggah konten berbahaya (malware) atau mengganggu layanan (mis. <span class="kbd">DDoS</span>, <span class="kbd">bruteforce</span>).</li>
                                <li>Memindahtangankan kredensial atau <em>session</em> kepada pihak lain.</li>
                            </ul>

                            <h2 id="id-hki">7) Hak Kekayaan Intelektual</h2>
                            <p>Hak KI atas perangkat lunak, antarmuka, logo, dan materi panduan dimiliki instansi pengelola, kecuali dinyatakan lain. Hak atas dokumen/arsip pegawai mengikuti hukum kearsipan.</p>

                            <h2 id="id-lisensi">8) Lisensi & Hak Akses</h2>
                            <p>Kami memberikan hak akses terbatas, non‑eksklusif, tidak dapat dipindahtangankan kepada pengguna berwenang sesuai peran. Hak dapat dicabut bila terjadi pelanggaran atau risiko keamanan.</p>

                            <h2 id="id-integrasi">9) Integrasi Pihak Ketiga</h2>
                            <ul>
                                <li><strong>TTE (BSrE/BSSN):</strong> Penggunaan mengikuti ketentuan BSrE; pengguna wajib menjaga perangkat & sertifikat.</li>
                                <li><strong>Penyimpanan & Sinkronisasi:</strong> Jika diaktifkan, integrasi dengan object storage/Google Drive tunduk pada kebijakan penyedia & DPA; pertukaran metadata yang diperlukan dapat terjadi.</li>
                                <li><strong>Notifikasi:</strong> Email/SMS/WhatsApp (opsional) melalui penyedia resmi berdasarkan dasar hukum/persetujuan.</li>
                            </ul>

                            <h2 id="id-ketersediaan">10) Ketersediaan Layanan & Pemeliharaan</h2>
                            <ul>
                                <li>Kami berupaya menjaga ketersediaan secara wajar; pemeliharaan terjadwal/insidentil dapat menyebabkan gangguan sementara.</li>
                                <li>Pemberitahuan pemeliharaan melalui kanal resmi atau banner aplikasi.</li>
                                <li>Pencadangan data dilakukan sesuai kebijakan; pemulihan diprioritaskan berdasarkan kritikalitas.</li>
                            </ul>

                            <h2 id="id-perubahan">11) Perubahan Layanan & Ketentuan</h2>
                            <p>Kami dapat memperbarui fitur atau Ketentuan untuk menyesuaikan kebutuhan regulasi/operasional. Perubahan material akan diinformasikan; tanggal berlaku tercantum di bagian atas.</p>

                            <h2 id="id-penafian">12) Penafian Jaminan</h2>
                            <p>SIGARDA disediakan "sebagaimana adanya" dan "sebagaimana tersedia". Sepanjang diizinkan hukum, kami menafikan jaminan tersirat, termasuk kesesuaian untuk tujuan tertentu, non‑pelanggaran, dan ketersediaan tanpa gangguan.</p>

                            <h2 id="id-tanggungjawab">13) Batasan Tanggung Jawab</h2>
                            <p>Sepanjang diperbolehkan hukum, kami tidak bertanggung jawab atas kerugian tidak langsung, insidental, khusus, konsekuensial, atau kehilangan data/keuntungan akibat penggunaan SIGARDA. Batas tanggung jawab (jika ada) mengikuti ketentuan hukum yang berlaku.</p>

                            <h2 id="id-gantirugi">14) Ganti Rugi</h2>
                            <p>Anda setuju untuk membebaskan dan mengganti rugi kami dari klaim pihak ketiga akibat pelanggaran Ketentuan, pelanggaran hukum, atau penyalahgunaan melalui akun Anda, sepanjang bukan karena kelalaian kami.</p>

                            <h2 id="id-pengakhiran">15) Pengakhiran Akses</h2>
                            <p>Kami dapat menangguhkan/mengakhiri akses jika terjadi pelanggaran, risiko keamanan, atau berdasarkan kebijakan internal. Kewajiban terkait data & kearsipan mengikuti ketentuan peraturan.</p>

                            <h2 id="id-force-majeure">16) Kejadian Kahar (Force Majeure)</h2>
                            <p>Kami tidak bertanggung jawab atas keterlambatan/kegagalan layanan karena peristiwa di luar kendali wajar (bencana, kegagalan listrik/jaringan, kebijakan pemerintah, serangan siber skala besar, dll.).</p>

                            <h2 id="id-hukum">17) Hukum yang Berlaku & Sengketa</h2>
                            <ul>
                                <li>Ketentuan ini diatur oleh hukum Negara Republik Indonesia.</li>
                                <li>Sengketa diselesaikan musyawarah terlebih dahulu; jika tidak tercapai, melalui peradilan berwenang di domisili instansi.</li>
                            </ul>

                            <h2 id="id-kontak">18) Kontak Resmi</h2>
                            <p>Pertanyaan terkait Ketentuan ini:<br>
                                <strong>PPID/DPO SIGARDA</strong><br>
                                Email: [pessel@kemenag.go.id]<br>
                                Alamat: [MH2H+9PW, Jl. Imam Bonjol, Painan, Kec. IV Jurai, Kab. Pesisir Selatan, Sumatera Barat 25651]<br>
                                Telepon: [(0756) 21305]
                            </p>

                            <h2 id="id-pemerintahan">19) Ketentuan Khusus Pemerintahan</h2>
                            <ul>
                                <li>Akses berdasarkan surat tugas/otorisasi pejabat berwenang dan prinsip <em>need‑to‑know</em>.</li>
                                <li>Layanan informasi publik mengikuti mekanisme PPID dengan tetap melindungi data pribadi.</li>
                                <li>Penerapan TTE mematuhi <strong>BSrE/BSSN</strong> dan kebijakan keamanan informasi instansi.</li>
                            </ul>

                            <h2 id="id-definisi">20) Definisi & Urutan Prioritas</h2>
                            <p>Istilah yang tidak didefinisikan di sini merujuk pada Kebijakan Privasi atau peraturan yang berlaku. Jika terjadi pertentangan: (1) peraturan perundang‑undangan, (2) kebijakan internal SPBE/kearsipan, (3) Ketentuan ini, (4) dokumentasi teknis.</p>

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
