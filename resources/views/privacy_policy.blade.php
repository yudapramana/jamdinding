@extends('layouts.app')

@section('title', 'Privacy Policy — SIGARDA')
@section('meta_description', 'Privacy Policy for SIGARDA (Sistem Informasi Gerbang Arsip Digital Pegawai) — Ministry of Religious Affairs, Pesisir Selatan.')
@section('og_title', 'Privacy Policy — SIGARDA')
@section('og_description', 'Privacy policy for SIGARDA covering lawful processing, retention, data subject rights, and security controls.')

@section('content')
    <!-- HERO -->
    <header class="py-5 bg-soft border-bottom">
        <div class="container">
            <span class="badge badge-soft rounded-pill mb-2">Policy Document</span>
            <h1 class="fw-bold mb-1">Privacy Policy (EN) — Kebijakan Privasi (ID)</h1>
            <p class="text-muted mb-0">Version: <strong>1.0</strong> · Effective Date: <strong>9 September 2025</strong></p>
            <small class="text-muted">System: SIGARDA — <em>Sistem Informasi Gerbang Arsip Digital Pegawai</em></small>
            <div class="mt-3 d-flex gap-2 flex-wrap lang-switch">
                <div class="btn-group" role="group" aria-label="Language switch">
                    <button class="btn btn-outline-success btn-sm active" id="btn-en" data-lang="en">English</button>
                    <button class="btn btn-outline-secondary btn-sm" id="btn-id" data-lang="id">Bahasa Indonesia</button>
                </div>
                <a href="/" class="btn btn-outline-success btn-sm">← Back to Home</a>
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
                                <li><a href="#en-summary">Executive Summary</a></li>
                                <li><a href="#en-scope">Scope</a></li>
                                <li><a href="#en-defs">Key Definitions</a></li>
                                <li><a href="#en-law-principles">Legal Bases & Principles</a></li>
                                <li><a href="#en-data-types">Data Categories</a></li>
                                <li><a href="#en-collection">How We Collect Data</a></li>
                                <li><a href="#en-purposes">Processing Purposes</a></li>
                                <li><a href="#en-legal-basis-map">Legal Basis by Purpose</a></li>
                                <li><a href="#en-sharing">Data Sharing & Recipients</a></li>
                                <li><a href="#en-location-transfer">Storage Location & Transfers</a></li>
                                <li><a href="#en-retention">Retention & Archiving</a></li>
                                <li><a href="#en-security">Information Security</a></li>
                                <li><a href="#en-rights">Data Subject Rights</a></li>
                                <li><a href="#en-cookies">Cookies & Similar Tech</a></li>
                                <li><a href="#en-automated">Automated Decisions & Profiling</a></li>
                                <li><a href="#en-children">Children</a></li>
                                <li><a href="#en-changes">Policy Changes</a></li>
                                <li><a href="#en-contact">Contact & Complaints</a></li>
                                <li><a href="#en-gov">Government‑Specific Terms</a></li>
                                <li><a href="#en-appendix">Appendix</a></li>
                            </ol>
                            <ol class="mb-0 d-none" id="toc-id">
                                <li><a href="#id-ringkasan">Ringkasan Eksekutif</a></li>
                                <li><a href="#id-ruanglingkup">Ruang Lingkup</a></li>
                                <li><a href="#id-definisi">Definisi Singkat</a></li>
                                <li><a href="#id-dasarhukum">Dasar Hukum & Prinsip</a></li>
                                <li><a href="#id-jenisdata">Jenis Data</a></li>
                                <li><a href="#id-pengumpulan">Cara Pengumpulan Data</a></li>
                                <li><a href="#id-tujuan">Tujuan Pemrosesan</a></li>
                                <li><a href="#id-legalbasis">Dasar Pemrosesan per Tujuan</a></li>
                                <li><a href="#id-berbagi">Berbagi Data & Penerima</a></li>
                                <li><a href="#id-lokasi">Lokasi Penyimpanan & Transfer</a></li>
                                <li><a href="#id-retensi">Retensi & Arsip</a></li>
                                <li><a href="#id-keamanan">Keamanan Informasi</a></li>
                                <li><a href="#id-hak">Hak Subjek Data</a></li>
                                <li><a href="#id-kuki">Kuki & Teknologi Serupa</a></li>
                                <li><a href="#id-otomatis">Keputusan Otomatis & Profiling</a></li>
                                <li><a href="#id-anak">Anak</a></li>
                                <li><a href="#id-perubahan">Perubahan Kebijakan</a></li>
                                <li><a href="#id-kontak">Kontak & Pengaduan</a></li>
                                <li><a href="#id-pemerintahan">Ketentuan Khusus Pemerintahan</a></li>
                                <li><a href="#id-lampiran">Lampiran</a></li>
                            </ol>
                        </div>
                    </div>
                </aside>

                <!-- DOCUMENT: ENGLISH PRIMARY -->
                <section class="col-lg-8 doc">
                    <div class="card border-0 shadow-sm" id="lang-en">
                        <div class="card-body">
                            <p><strong>Data Controller:</strong> [Office of the Ministry of Religious Affairs — Pesisir Selatan] ("we")<br>
                                <strong>PPID/DPO Contact:</strong><br>
                                [Yossef Yuda, S.HI, MA] · [198008042005011007@kemenag.go.id] · [+62 812-6158-2843]<br>
                                [MH2H+9PW, Jl. Imam Bonjol, Painan, IV Jurai, Pesisir Selatan, West Sumatra 25651]
                            </p>
                            <hr>
                            <h2 id="en-summary">1) Executive Summary</h2>
                            <p>SIGARDA supports digital personnel archiving and administrative services for ASN/PPPK. We process personal data pursuant to the <strong>Indonesian Personal Data Protection Law No. 27/2022</strong>, <strong>Presidential Regulation 95/2018 (SPBE)</strong>, <strong>Gov. Regulation 71/2019 (Electronic Systems & Transactions)</strong>, and archival rules (<strong>Law 43/2009</strong> & ANRI/JRA). This policy explains the data we process, purposes, legal bases, retention, recipients, data‑subject rights, and security.</p>

                            <h2 id="en-scope">2) Scope</h2>
                            <p>This policy applies to all SIGARDA users: civil servants/contracted personnel, administrators, verifiers, and authorized internal/external parties.</p>

                            <h2 id="en-defs">3) Key Definitions</h2>
                            <ul>
                                <li><strong>Personal Data:</strong> any information relating to an identified or identifiable natural person.</li>
                                <li><strong>Processing:</strong> any operation on personal data (collection, storage, alteration, disclosure, deletion, etc.).</li>
                                <li><strong>Controller/Processor/Sub‑processor</strong> as commonly defined in data‑protection frameworks.</li>
                                <li><strong>JRA:</strong> Government archival retention schedule.</li>
                            </ul>

                            <h2 id="en-law-principles">4) Legal Bases & Principles</h2>
                            <p>We rely on:</p>
                            <ul>
                                <li><strong>Legal obligation & public task</strong> under official authority.</li>
                                <li><strong>Contract/performance</strong> for internal employment services.</li>
                                <li><strong>Consent</strong> for optional features (e.g., email/WhatsApp notices, certain integrations).</li>
                                <li><strong>Legitimate interests</strong> (service quality & security) with safeguards and balancing tests.</li>
                            </ul>
                            <p>Principles: <strong>lawfulness</strong>, <strong>purpose limitation</strong>, <strong>data minimisation</strong>, <strong>accuracy</strong>, <strong>storage limitation</strong>, <strong>integrity & confidentiality</strong>, <strong>accountability</strong>.</p>

                            <h2 id="en-data-types">5) Data Categories</h2>
                            <p><strong>Identity & Employment:</strong> NIP/NIK, name, birth date, religion, address, email, phone, unit, position, rank/grade, employment status, SK history (CPNS/PNS, rank, KGB, transfer), education & certifications, NPWP, BPJS, family data (as required).<br>
                                <strong>Archive Docs:</strong> decrees, diplomas & transcripts, training certificates, performance records, contracts, and supporting documents per JRA.<br>
                                <strong>e‑Signature & Integrations:</strong> BSrE certificate identifiers, signing logs, document hashes.<br>
                                <strong>Technical:</strong> activity logs (time, IP, user‑agent), RBAC data, audit trail, upload/download evidence, temp files, file metadata.<br>
                                <strong>Cookies & Telemetry</strong> (if enabled): UI preferences, session cookies, error traces for service improvement.
                            </p>
                            <div class="alert alert-info"><strong>Note:</strong> We do not request sensitive data beyond mandate (e.g., biometrics, health) unless required by law. If required, we will inform you explicitly.</div>

                            <h2 id="en-collection">6) How We Collect Data</h2>
                            <ul>
                                <li>Direct input by staff/operators via SIGARDA forms.</li>
                                <li>Synchronization from internal systems (e.g., SIMPEG/HRIS) under agreements & mandate.</li>
                                <li>Uploads by authorized users/officials.</li>
                                <li>Government services integration (e.g., <strong>BSrE</strong> for e‑signature) as required.</li>
                            </ul>

                            <h2 id="en-purposes">7) Processing Purposes</h2>
                            <ul>
                                <li>Personnel archiving, administration, and document issuance.</li>
                                <li>Verification and compliance (SPBE, archives, audits).</li>
                                <li>Internal reporting & statistics (using non‑identifiable data where possible).</li>
                                <li>Security: fraud prevention, rate‑limiting, security audits, and access enforcement.</li>
                                <li>Service quality, debugging, and maintenance.</li>
                            </ul>

                            <h2 id="en-legal-basis-map">8) Legal Basis by Purpose</h2>
                            <ul>
                                <li>Administration & archiving → <strong>Legal obligation/Public task</strong>.</li>
                                <li>Verification, audits, reporting → <strong>Legal obligation/Public task</strong>.</li>
                                <li>Optional notifications (email/WhatsApp) → <strong>Consent</strong>.</li>
                                <li>Technical analytics & service improvement → <strong>Legitimate interests</strong> with safeguards.</li>
                            </ul>

                            <h2 id="en-sharing">9) Data Sharing & Recipients</h2>
                            <p>Data may be shared with:</p>
                            <ul>
                                <li>Authorized internal units (Subbag TU, HR).</li>
                                <li>Government agencies (<strong>BKN</strong>, <strong>Regional MoRA</strong>, <strong>ANRI</strong> for archives, <strong>BSSN/BSrE</strong> for e‑signature).</li>
                                <li><strong>Processors/Sub‑processors</strong> (data centers, email/SMS gateways, logging/observability) bound by DPAs and confidentiality.</li>
                            </ul>
                            <div class="alert alert-secondary">We <strong>do not sell</strong> personal data. Law‑enforcement requests are handled according to applicable procedures and law.</div>

                            <h2 id="en-location-transfer">10) Storage Location & International Transfers</h2>
                            <p>Data is stored on designated infrastructure [Jakarta, <strong>Indonesia</strong> / <strong>internal government DC</strong>]. Cross‑border transfers are <strong>not</strong> performed unless required/permitted by law with adequate safeguards and notice.</p>

                            <h2 id="en-retention">11) Retention & Archiving</h2>
                            <ul>
                                <li>Retention follows <strong>ANRI/MoRA JRA</strong> and archival regulations.</li>
                                <li>Access logs & audit trails are retained per SPBE/security standards [e.g., ≥ 2 years] or internal policy.</li>
                                <li>Upon expiry, data is deleted/anonymized or transferred to static archives per ANRI rules.</li>
                            </ul>

                            <h2 id="en-security">12) Information Security</h2>
                            <ul>
                                <li><strong>Encryption</strong> in transit (TLS 1.2+) and at rest for sensitive files.</li>
                                <li><strong>RBAC</strong>, strong authentication, and <strong>MFA</strong> for admin accounts.</li>
                                <li><strong>Monitoring & audit logs</strong>, anomaly alerting, periodic access reviews.</li>
                                <li><strong>Environment isolation</strong>, server hardening, encrypted backups & recovery tests.</li>
                                <li><strong>Vulnerability/Penetration testing</strong> and patch management.</li>
                                <li><strong>DPAs/SLAs</strong> with sub‑processors, including confidentiality and security duties.</li>
                            </ul>
                            <div class="alert alert-warning">No system is 100% secure. We operate incident response per SOPs and applicable breach‑notification duties.</div>

                            <h2 id="en-rights">13) Data Subject Rights</h2>
                            <p>Under the PDP Law, you may:</p>
                            <ul>
                                <li>Obtain information on processing.</li>
                                <li>Access and obtain a copy of your personal data.</li>
                                <li>Rectify incomplete/inaccurate data.</li>
                                <li>Request deletion <strong>where not contrary</strong> to archival/legal obligations.</li>
                                <li>Restrict/object to processing in certain conditions under law.</li>
                                <li>Withdraw consent for optional features without affecting prior lawful processing.</li>
                                <li>Lodge complaints.</li>
                            </ul>
                            <p><strong>How to exercise:</strong> Submit a request to the <strong>PPID/DPO</strong> via [email/portal]. We will respond within regulatory timeframes. Identity verification may be required.</p>

                            <h2 id="en-cookies">14) Cookies & Similar Technologies</h2>
                            <p>SIGARDA uses <strong>session cookies</strong> for authentication and preferences. We do not use third‑party advertising trackers. Disabling session cookies may impair login functions.</p>

                            <h2 id="en-automated">15) Automated Decisions & Profiling</h2>
                            <p>No automated decision‑making with legal or similar significant effects without human involvement. Automated grouping/validation (e.g., file‑format checks) is for efficiency only.</p>

                            <h2 id="en-children">16) Children</h2>
                            <p>SIGARDA is intended for personnel management (adults) and not for children.</p>

                            <h2 id="en-changes">17) Policy Changes</h2>
                            <p>We may update this policy. The latest revision date appears at the top. Material changes will be announced through official channels.</p>

                            <h2 id="en-contact">18) Contact & Complaints</h2>
                            <p>Privacy queries, rights requests, or complaints:<br>
                                <strong>PPID/DPO SIGARDA</strong><br>
                                Email: [pessel@kemenag.go.id]<br>
                                Address: [MH2H+9PW, Jl. Imam Bonjol, Painan, IV Jurai, Pesisir Selatan, West Sumatra 25651]<br>
                                Phone: [(0756) 21305]
                            </p>
                            <p>If unresolved, you may escalate to the <strong>competent data‑protection supervisory authority</strong> under applicable law.</p>

                            <h2 id="en-gov">19) Government‑Specific Terms</h2>
                            <ul>
                                <li>Access restricted to authorized officials per assignment/mandate.</li>
                                <li>Public‑information services through <strong>PPID</strong> under the Public Information Law, while protecting personal data.</li>
                                <li>Recordkeeping follows government archival and SPBE standards.</li>
                                <li>e‑Signature integration complies with <strong>BSrE/BSSN</strong> requirements.</li>
                            </ul>

                            <h2 id="en-appendix">20) Appendix</h2>
                            <p><strong>Legal References</strong></p>
                            <ul>
                                <li>Law No. 27/2022 on Personal Data Protection</li>
                                <li>Presidential Regulation 95/2018 on SPBE</li>
                                <li>Government Regulation 71/2019 on Electronic Systems & Transactions</li>
                                <li>Law 43/2009 on Archiving & ANRI JRA</li>
                                <li>[Internal SPBE/Security policies of MoRA]</li>
                            </ul>
                            <hr>
                            <p class="text-muted">Last updated: 9 September 2025 · <a href="/">Back to SIGARDA Homepage</a></p>
                            <p class="text-muted mb-0">Quick links: <a href="/#about">About</a> · <a href="/#features">Features</a> · <a href="/#contact">Contact</a></p>
                        </div>
                    </div>

                    <!-- DOCUMENT: INDONESIAN -->
                    <div class="card border-0 shadow-sm d-none" id="lang-id">
                        <div class="card-body">
                            <p><strong>Pengendali Data:</strong> [Kantor Kementerian Agama Kabupaten Pesisir Selatan] ("Kami")<br>
                                <strong>Kontak PPID/DPO:</strong><br>
                                [Yossef Yuda, S.HI, MA] · [198008042005011007@kemenag.go.id] · [+62 812-6158-2843]<br>
                                [MH2H+9PW, Jl. Imam Bonjol, Painan, Kec. IV Jurai, Kab. Pesisir Selatan, Sumatera Barat 25651]
                            </p>
                            <hr>
                            <h2 id="id-ringkasan">1) Ringkasan Eksekutif</h2>
                            <p>SIGARDA digunakan untuk pengelolaan arsip kepegawaian dan layanan administrasi digital bagi ASN/PPPK. Kami memproses data pribadi sesuai <strong>UU 27/2022 tentang PDP</strong>, <strong>Perpres 95/2018 (SPBE)</strong>, <strong>PP 71/2019</strong>, serta aturan kearsipan (<strong>UU 43/2009</strong> & ANRI/JRA). Dokumen ini menjelaskan jenis data, tujuan, dasar pemrosesan, retensi, penerima data, hak subjek data, dan keamanan.</p>

                            <h2 id="id-ruanglingkup">2) Ruang Lingkup</h2>
                            <p>Kebijakan ini berlaku untuk seluruh pengguna SIGARDA: ASN/PPPK, admin, verifikator, dan pihak internal/eksternal yang sah.</p>

                            <h2 id="id-definisi">3) Definisi Singkat</h2>
                            <ul>
                                <li><strong>Data Pribadi:</strong> data tentang orang yang teridentifikasi/teridentifikasi.</li>
                                <li><strong>Pemrosesan:</strong> setiap operasi terhadap data pribadi.</li>
                                <li><strong>Pengendali/Prosesor/Sub‑pemroses</strong> sebagaimana dipahami umum.</li>
                                <li><strong>JRA:</strong> Jadwal Retensi Arsip pemerintah.</li>
                            </ul>

                            <h2 id="id-dasarhukum">4) Dasar Hukum & Prinsip</h2>
                            <p>Kami mendasarkan pada:</p>
                            <ul>
                                <li><strong>Kewajiban hukum & tugas publik</strong> dalam wewenang resmi.</li>
                                <li><strong>Perjanjian/pelaksanaan</strong> layanan kepegawaian internal.</li>
                                <li><strong>Persetujuan</strong> untuk fitur opsional.</li>
                                <li><strong>Kepentingan sah</strong> (mutu layanan & keamanan) dengan <em>safeguard</em>.</li>
                            </ul>
                            <p>Prinsip: <strong>legalitas</strong>, <strong>pembatasan tujuan</strong>, <strong>minimisasi data</strong>, <strong>akurasi</strong>, <strong>pembatasan retensi</strong>, <strong>integritas & kerahasiaan</strong>, <strong>akuntabilitas</strong>.</p>

                            <h2 id="id-jenisdata">5) Jenis Data</h2>
                            <p><strong>Identitas & Kepegawaian:</strong> NIP/NIK, nama, TTL, agama, alamat, email, telepon, unit kerja, jabatan, golongan/pangkat, status, riwayat SK (CPNS/PNS, pangkat, KGB, mutasi), pendidikan & sertifikasi, NPWP, BPJS, data keluarga (seperlunya).<br>
                                <strong>Dokumen Arsip:</strong> SK, ijazah & transkrip, sertifikat pelatihan, penilaian kinerja, kontrak, dan dokumen pendukung sesuai JRA.<br>
                                <strong>TTE & Integrasi:</strong> identitas sertifikat BSrE, log penandatanganan, hash dokumen.<br>
                                <strong>Teknis:</strong> log aktivitas (waktu, IP, user agent), RBAC, <em>audit trail</em>, bukti unggah/unduh, berkas sementara, metadata file.<br>
                                <strong>Kuki & Telemetri</strong> (jika diaktifkan): preferensi UI, kuki sesi, jejak kesalahan.
                            </p>
                            <div class="alert alert-info"><strong>Catatan:</strong> Kami tidak meminta data sensitif di luar mandat kecuali diwajibkan hukum. Jika diwajibkan, akan diinformasikan secara jelas.</div>

                            <h2 id="id-pengumpulan">6) Cara Pengumpulan Data</h2>
                            <ul>
                                <li>Input langsung melalui formulir SIGARDA.</li>
                                <li>Sinkronisasi dari sistem internal (SIMPEG/HRIS) sesuai perjanjian & mandat.</li>
                                <li>Unggah berkas oleh pengguna/pejabat berwenang.</li>
                                <li>Integrasi layanan pemerintah (mis. <strong>BSrE</strong> untuk TTE) sesuai ketentuan.</li>
                            </ul>

                            <h2 id="id-tujuan">7) Tujuan Pemrosesan</h2>
                            <ul>
                                <li>Pengarsipan kepegawaian, administrasi, dan penerbitan dokumen.</li>
                                <li>Verifikasi & kepatuhan (SPBE, kearsipan, audit).</li>
                                <li>Pelaporan internal & statistik (non‑identifiable bila dimungkinkan).</li>
                                <li>Keamanan: pencegahan penipuan, <em>rate limiting</em>, audit keamanan, penegakan akses.</li>
                                <li>Peningkatan mutu layanan, <em>debugging</em>, pemeliharaan.</li>
                            </ul>

                            <h2 id="id-legalbasis">8) Dasar Pemrosesan per Tujuan</h2>
                            <ul>
                                <li>Administrasi & arsip → <strong>Kewajiban hukum/Tugas publik</strong>.</li>
                                <li>Verifikasi, audit, pelaporan → <strong>Kewajiban hukum/Tugas publik</strong>.</li>
                                <li>Notifikasi opsional → <strong>Persetujuan</strong>.</li>
                                <li>Analitik teknis & perbaikan layanan → <strong>Kepentingan sah</strong> dengan <em>safeguard</em>.</li>
                            </ul>

                            <h2 id="id-berbagi">9) Berbagi Data & Penerima</h2>
                            <p>Data dapat dibagikan kepada:</p>
                            <ul>
                                <li>Unit internal berwenang (Subbag TU, Kepegawaian).</li>
                                <li>Instansi pemerintah terkait (<strong>BKN</strong>, <strong>Kanwil Kemenag</strong>, <strong>ANRI</strong>, <strong>BSSN/BSrE</strong>).</li>
                                <li><strong>Prosesor/Sub‑pemroses</strong> (pusat data, gateway email/SMS, logging) berdasar DPA & kerahasiaan.</li>
                            </ul>
                            <div class="alert alert-secondary">Kami <strong>tidak</strong> menjual data pribadi. Permintaan penegak hukum dipenuhi sesuai prosedur & peraturan.</div>

                            <h2 id="id-lokasi">10) Lokasi Penyimpanan & Transfer</h2>
                            <p>Data disimpan pada infrastruktur yang ditunjuk [Jakarta, <strong>Indonesia</strong> / <strong>DC internal pemerintah</strong>]. Transfer lintas negara <strong>tidak</strong> dilakukan kecuali diwajibkan/diperbolehkan dengan <em>safeguard</em> memadai dan pemberitahuan.</p>

                            <h2 id="id-retensi">11) Retensi & Arsip</h2>
                            <ul>
                                <li>Mengikuti <strong>JRA ANRI/Kemenag</strong> dan regulasi kearsipan.</li>
                                <li>Log akses & <em>audit trail</em> disimpan sesuai standar SPBE/keamanan [mis. ≥ 2 tahun] atau kebijakan internal.</li>
                                <li>Setelah berakhir, data dihapus/dianonimkan atau dialihmedia menjadi arsip statis sesuai ketentuan.</li>
                            </ul>

                            <h2 id="id-keamanan">12) Keamanan Informasi</h2>
                            <ul>
                                <li><strong>Enkripsi</strong> saat transit (TLS 1.2+) dan saat tersimpan untuk berkas sensitif.</li>
                                <li><strong>RBAC</strong>, autentikasi kuat, dan <strong>MFA</strong> untuk akun admin.</li>
                                <li><strong>Pemantauan & audit log</strong>, <em>alerting</em> anomali, tinjauan akses berkala.</li>
                                <li><strong>Isolasi lingkungan</strong>, <em>hardening</em> server, <em>backup</em> terenkripsi & uji pemulihan.</li>
                                <li><strong>Uji kerentanan/penetrasi</strong> dan pengelolaan patch.</li>
                                <li><strong>DPA/SLA</strong> dengan sub‑pemroses, termasuk kerahasiaan & kewajiban keamanan.</li>
                            </ul>
                            <div class="alert alert-warning">Tidak ada sistem yang 100% aman. Kami melakukan respons insiden sesuai SOP & kewajiban notifikasi insiden.</div>

                            <h2 id="id-hak">13) Hak Subjek Data</h2>
                            <p>Sesuai UU PDP, Anda berhak untuk:</p>
                            <ul>
                                <li>Memperoleh informasi pemrosesan.</li>
                                <li>Mengakses & memperoleh salinan data pribadi.</li>
                                <li>Memperbaiki data yang tidak akurat/tidak lengkap.</li>
                                <li>Meminta penghapusan <strong>sepanjang tidak bertentangan</strong> dengan kewajiban arsip/hukum.</li>
                                <li>Membatasi/menolak pemrosesan dalam kondisi tertentu menurut hukum.</li>
                                <li>Menarik persetujuan untuk fitur opsional tanpa memengaruhi pemrosesan sah sebelumnya.</li>
                                <li>Menyampaikan pengaduan.</li>
                            </ul>
                            <p><strong>Cara mengajukan:</strong> Kirim permohonan ke <strong>PPID/DPO</strong> melalui [email/portal]. Kami menindaklanjuti sesuai tenggat regulasi. Verifikasi identitas mungkin diperlukan.</p>

                            <h2 id="id-kuki">14) Kuki & Teknologi Serupa</h2>
                            <p>SIGARDA menggunakan <strong>session cookie</strong> untuk autentikasi & preferensi. Kami tidak menggunakan pelacak iklan pihak ketiga. Menonaktifkan cookie sesi dapat menghambat fungsi login.</p>

                            <h2 id="id-otomatis">15) Keputusan Otomatis & Profiling</h2>
                            <p>SIGARDA <strong>tidak</strong> melakukan keputusan otomatis berdampak hukum signifikan tanpa campur tangan manusia. Pengelompokan/validasi otomatis hanya untuk efisiensi.</p>

                            <h2 id="id-anak">16) Anak</h2>
                            <p>SIGARDA ditujukan untuk pengelolaan pegawai (dewasa), bukan anak.</p>

                            <h2 id="id-perubahan">17) Perubahan Kebijakan</h2>
                            <p>Kebijakan dapat diperbarui. Tanggal revisi terakhir tercantum di bagian atas. Perubahan material diumumkan via kanal resmi.</p>

                            <h2 id="id-kontak">18) Kontak & Pengaduan</h2>
                            <p>Pertanyaan, permintaan hak, atau pengaduan:
                                <br><strong>PPID/DPO SIGARDA</strong><br>
                                Email: [pessel@kemenag.go.id]<br>
                                Alamat: [MH2H+9PW, Jl. Imam Bonjol, Painan, Kec. IV Jurai, Kab. Pesisir Selatan, Sumatera Barat 25651]<br>
                                Telepon: [(0756) 21305]
                            </p>
                            <p>Jika tidak terselesaikan, Anda dapat mengadu ke <strong>otoritas pengawas perlindungan data</strong> yang berwenang.</p>

                            <h2 id="id-pemerintahan">19) Ketentuan Khusus Pemerintahan</h2>
                            <ul>
                                <li>Akses dibatasi bagi pejabat berwenang berdasarkan surat tugas/mandat.</li>
                                <li>Layanan informasi publik melalui <strong>PPID</strong> dengan tetap melindungi data pribadi.</li>
                                <li><em>Recordkeeping</em> mengikuti standar kearsipan pemerintah & SPBE.</li>
                                <li>Integrasi TTE mematuhi ketentuan <strong>BSrE/BSSN</strong>.</li>
                            </ul>

                            <h2 id="id-lampiran">20) Lampiran</h2>
                            <p><strong>Dasar Hukum & Referensi</strong></p>
                            <ul>
                                <li>UU 27/2022 Perlindungan Data Pribadi</li>
                                <li>Perpres 95/2018 SPBE</li>
                                <li>PP 71/2019 PSTE</li>
                                <li>UU 43/2009 Kearsipan & ANRI JRA</li>
                                <li>[Kebijakan internal SPBE/Keamanan Kemenag]</li>
                            </ul>
                            <hr>
                            <p class="text-muted">Terakhir diperbarui: 9 September 2025 · <a href="/">Kembali ke Landing Page SIGARDA</a></p>
                            <p class="text-muted mb-0">Tautan cepat: <a href="/#about">Tentang</a> · <a href="/#features">Fitur</a> · <a href="/#contact">Kontak</a></p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
@endsection
