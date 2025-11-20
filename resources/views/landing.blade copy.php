<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SIGARDA — Personnel Digital Archiving System (ASN)</title>
    <meta name="description" content="SIGARDA is a Personnel Digital Archiving System for the Ministry of Religious Affairs (Pesisir Selatan) — secure, structured, and regulation-compliant." />

    <!-- Canonical (verified domain) -->
    <link rel="canonical" href="https://sigarda.kemenagpessel.com/" />

    <!-- Open Graph -->
    <meta property="og:title" content="SIGARDA — Personnel Digital Archiving System (ASN)" />
    <meta property="og:description" content="Secure, structured, and regulation-compliant management of personnel archives." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://sigarda.kemenagpessel.com/" />
    <meta property="og:image" content="https://sigarda.kemenagpessel.com/assets/sigarda-og.png" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:locale:alternate" content="id_ID" />

    <link rel="icon" href="/assets/sigarda-icon.png" />

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --sigarda: #0f766e;
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

        body {
            background: #f8f9fa
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

    <!-- Structured Data -->
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
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <img src="/images/sigarda-icon.png" alt="SIGARDA Logo" width="34" height="34" class="rounded-2">
                <span class="fw-bold">SIGARDA</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#data">Data Transparency</a></li>
                    <li class="nav-item"><a class="nav-link" href="#privacy">Privacy</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item ms-lg-3 lang-switch">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Language switch">
                            <button class="btn btn-outline-success active" id="btn-en" data-lang="en">EN</button>
                            <button class="btn btn-outline-secondary" id="btn-id" data-lang="id">ID</button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO EN -->
    <section class="py-5 bg-soft" id="hero-en">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <span class="badge badge-soft rounded-pill mb-3">SIGARDA — Personnel Digital Archiving</span>
                    <h1 class="display-5 fw-bold">
                        Manage Civil Servant Archives <span class="text-sigarda">Securely & Systematically</span>
                    </h1>
                    <p class="lead text-muted">
                        The official application of the <strong>Ministry of Religious Affairs, Pesisir Selatan</strong> to upload,
                        verify, store, and search employee digital records in line with security and compliance standards.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="#about" class="btn btn-success px-4">Learn about SIGARDA</a>
                        <a href="#privacy" class="btn btn-outline-success px-4">Privacy Policy</a>
                    </div>
                    <p class="small text-muted mt-2">This page is public — no login required.</p>
                </div>
                <div class="col-lg-5">
                    <div class="p-4 bg-white border rounded-4 hero-shadow">
                        <h2 class="h5 mb-3">Transparency Snapshot</h2>
                        <ul class="list-check small mb-3">
                            <li>Clear app & institution identity.</li>
                            <li>Available description of functions and workflow.</li>
                            <li>Scopes & purposes of data access disclosed.</li>
                            <li>Privacy Policy hosted on verified domain.</li>
                        </ul>
                        <div class="note p-3 rounded-3 small">
                            Note: SIGARDA only requests the minimum access required to operate.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HERO ID -->
    <section class="py-5 bg-soft d-none" id="hero-id">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <span class="badge badge-soft rounded-pill mb-3">SIGARDA — Sistem Arsip Digital Kepegawaian</span>
                    <h1 class="display-5 fw-bold">
                        Kelola Arsip Kepegawaian ASN secara <span class="text-sigarda">Aman & Terstruktur</span>
                    </h1>
                    <p class="lead text-muted">
                        Aplikasi resmi <strong>Kementerian Agama Kabupaten Pesisir Selatan</strong> untuk mengunggah,
                        memverifikasi, menyimpan, dan menelusuri arsip digital pegawai sesuai standar keamanan & kepatuhan.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="#tentang" class="btn btn-success px-4">Pelajari SIGARDA</a>
                        <a href="#privasi" class="btn btn-outline-success px-4">Kebijakan Privasi</a>
                    </div>
                    <p class="small text-muted mt-2">Halaman ini publik — tidak memerlukan login.</p>
                </div>
                <div class="col-lg-5">
                    <div class="p-4 bg-white border rounded-4 hero-shadow">
                        <h2 class="h5 mb-3">Ringkasan Transparansi</h2>
                        <ul class="list-check small mb-3">
                            <li>Identitas aplikasi & instansi jelas.</li>
                            <li>Deskripsi fungsi dan alur kerja tersedia.</li>
                            <li>Izin (scopes) & tujuan akses data dijelaskan.</li>
                            <li>Kebijakan Privasi pada domain terverifikasi.</li>
                        </ul>
                        <div class="note p-3 rounded-3 small">
                            Catatan: SIGARDA hanya meminta akses minimum yang diperlukan untuk bekerja.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT EN -->
    <section id="about" class="py-5">
        <div class="container" id="about-en">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h4">About SIGARDA</h2>
                            <p class="text-muted">
                                SIGARDA supports digitization of personnel archives within
                                the Ministry of Religious Affairs, Pesisir Selatan: document upload, classification, tiered verification,
                                and retention management per regulations.
                            </p>
                            <ul class="list-check">
                                <li>Clear identity: official government application.</li>
                                <li>Functions: upload, store, search, verify, download.</li>
                                <li>Security: role-based access, HTTPS, audit trail.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5">Key Benefits</h3>
                            <ul class="list-check">
                                <li>Reduce paper archives, speed up HR services.</li>
                                <li><span class="kbd">Google Drive</span> integration (optional) for redundancy & availability.</li>
                                <li>Complete verification trail for audit & compliance.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABOUT ID -->
        <div class="container d-none" id="about-id">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h4">Tentang SIGARDA</h2>
                            <p class="text-muted">
                                SIGARDA mendukung digitalisasi arsip pegawai: unggah dokumen, klasifikasi, verifikasi berjenjang,
                                hingga retensi arsip sesuai ketentuan.
                            </p>
                            <ul class="list-check">
                                <li>Identitas jelas: aplikasi resmi instansi pemerintah.</li>
                                <li>Fungsional: unggah, simpan, telusur, verifikasi, unduh.</li>
                                <li>Keamanan: RBAC, HTTPS, audit trail.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5">Manfaat Utama</h3>
                            <ul class="list-check">
                                <li>Kurangi arsip kertas, percepat layanan kepegawaian.</li>
                                <li>Integrasi <span class="kbd">Google Drive</span> (opsional) untuk redundansi.</li>
                                <li>Jejak verifikasi lengkap untuk audit & kepatuhan.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES EN -->
    <section id="features" class="py-5 bg-light">
        <div class="container" id="features-en">
            <h2 class="h3 mb-4">Application Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Upload & Classification</h3>
                            <p class="text-muted mb-0">Upload PDFs/images, categorize to archival standards, add metadata.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Tiered Verification</h3>
                            <p class="text-muted mb-0">Verification flow by authorized staff with notes & status.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Fast Search</h3>
                            <p class="text-muted mb-0">Search by name, NIP, type, or date.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Drive Integration (Optional)</h3>
                            <p class="text-muted mb-0">Store a copy to institution’s Google Drive for reliability.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Security & Audit</h3>
                            <p class="text-muted mb-0">RBAC, in-transit encryption (HTTPS), activity logs.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Export & Retention</h3>
                            <p class="text-muted mb-0">Manage retention & export per internal policies.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FEATURES ID -->
        <div class="container d-none" id="features-id">
            <h2 class="h3 mb-4">Fitur Aplikasi</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Unggah & Klasifikasi</h3>
                            <p class="text-muted mb-0">Unggah PDF/gambar, kategorikan sesuai standar arsip, beri metadata.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Verifikasi Berjenjang</h3>
                            <p class="text-muted mb-0">Alur verifikasi oleh petugas berwenang dengan catatan & status.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Pencarian Cepat</h3>
                            <p class="text-muted mb-0">Cari dokumen berdasarkan nama, NIP, jenis, atau tanggal.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Integrasi Drive (Opsional)</h3>
                            <p class="text-muted mb-0">Simpan salinan ke Google Drive instansi.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Keamanan & Audit</h3>
                            <p class="text-muted mb-0">RBAC, enkripsi in-transit (HTTPS), log aktivitas.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">Ekspor & Retensi</h3>
                            <p class="text-muted mb-0">Kelola retensi & ekspor sesuai kebijakan internal.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- DATA TRANSPARENCY EN -->
    <section id="data" class="py-5">
        <div class="container" id="data-en">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h4">Data Transparency & Permissions (OAuth Scopes)</h2>
                            <p class="text-muted">
                                SIGARDA requests only the minimum access for core functions. If Google Drive integration is enabled by admins, these scopes may be requested:
                            </p>
                            <ul class="list-check">
                                <li><span class="kbd">openid, email, profile</span> — User identity & basic personalization.</li>
                                <li><span class="kbd">https://www.googleapis.com/auth/drive.file</span> — Create/manage <em>only</em> files created by SIGARDA.</li>
                                <li><span class="kbd">https://www.googleapis.com/auth/drive.metadata.readonly</span> — Read relevant file metadata for sync.</li>
                            </ul>
                            <p class="small text-muted mb-0">Reason: keep a copy in institution’s Drive for availability & disaster recovery.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5">Use, Storage, Sharing & Deletion</h3>
                            <ul class="list-check">
                                <li><strong>Use:</strong> authentication & personnel archive management.</li>
                                <li><strong>Storage:</strong> institution servers; copy to Drive (optional).</li>
                                <li><strong>Sharing:</strong> not shared with third parties beyond operational/legal needs.</li>
                                <li><strong>Deletion:</strong> per retention policy; data subject requests accepted.</li>
                                <li><strong>Control:</strong> revoke at <span class="kbd">myaccount.google.com/permissions</span>.</li>
                            </ul>
                            <p class="small text-muted mb-0">Full details in the <a href="#privacy">Privacy Policy</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DATA TRANSPARENCY ID -->
        <div class="container d-none" id="data-id">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h4">Transparansi Data & Izin (OAuth Scopes)</h2>
                            <p class="text-muted">
                                SIGARDA hanya meminta akses minimum. Jika integrasi Google Drive diaktifkan admin, izin berikut mungkin diminta:
                            </p>
                            <ul class="list-check">
                                <li><span class="kbd">openid, email, profile</span> — Identitas pengguna & personalisasi.</li>
                                <li><span class="kbd">https://www.googleapis.com/auth/drive.file</span> — Membuat/mengelola <em>hanya</em> file yang dibuat SIGARDA.</li>
                                <li><span class="kbd">https://www.googleapis.com/auth/drive.metadata.readonly</span> — Melihat metadata relevan untuk sinkronisasi.</li>
                            </ul>
                            <p class="small text-muted mb-0">Alasan: salinan di Drive instansi untuk ketersediaan & pemulihan bencana.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5">Penggunaan, Penyimpanan, Berbagi & Penghapusan</h3>
                            <ul class="list-check">
                                <li><strong>Penggunaan:</strong> otentikasi & pengelolaan arsip kepegawaian.</li>
                                <li><strong>Penyimpanan:</strong> server instansi; salinan ke Drive (opsional).</li>
                                <li><strong>Berbagi:</strong> tidak ke pihak ketiga di luar kebutuhan operasional/hukum.</li>
                                <li><strong>Penghapusan:</strong> sesuai retensi; permintaan subjek data diterima.</li>
                                <li><strong>Kontrol:</strong> cabut di <span class="kbd">myaccount.google.com/permissions</span>.</li>
                            </ul>
                            <p class="small text-muted mb-0">Detail lengkap pada <a href="#privasi">Kebijakan Privasi</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRIVACY EN -->
    <section id="privacy" class="py-5 bg-light">
        <div class="container" id="privacy-en">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h4">Privacy Policy</h2>
                    <p class="text-muted">
                        Read the official Privacy Policy to understand how your data is collected, used, stored, shared, and the choices/controls you have.
                    </p>
                    <a class="btn btn-outline-success" href="/privacy-policy" rel="noopener">Open Privacy Policy</a>
                    <p class="small text-muted mt-3 mb-0">
                        <strong>Important:</strong> The URL above <em>must exactly match</em> the link shown on the Google OAuth Consent Screen.
                    </p>
                </div>
            </div>
        </div>

        <!-- PRIVACY ID -->
        <div class="container d-none" id="privasi">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h4">Kebijakan Privasi</h2>
                    <p class="text-muted">
                        Baca Kebijakan Privasi resmi untuk memahami pengumpulan, penggunaan, penyimpanan, pembagian data, serta pilihan/kontrol yang tersedia.
                    </p>
                    <a class="btn btn-outline-success" href="/privacy-policy" rel="noopener">Buka Kebijakan Privasi</a>
                    <p class="small text-muted mt-3 mb-0">
                        <strong>Penting:</strong> URL di atas <em>harus sama persis</em> dengan yang ada pada Google OAuth Consent Screen.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ EN -->
    <section class="py-5">
        <div class="container" id="faq-en">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h4">Frequently Asked Questions</h2>
                            <h3 class="h6 mt-3">Is this page accessible without login?</h3>
                            <p class="text-muted">Yes. SIGARDA info & Privacy Policy are public.</p>
                            <h3 class="h6">Who manages SIGARDA?</h3>
                            <p class="text-muted">Office of the Ministry of Religious Affairs — Pesisir Selatan.</p>
                            <h3 class="h6">Is all data stored in Google Drive?</h3>
                            <p class="text-muted">No. Drive is used only if enabled. Core archives remain on institution servers.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5">Eligibility Checklist (Google Verification)</h3>
                            <ul class="list-check">
                                <li>Clear & consistent app identity and branding.</li>
                                <li>Complete, understandable description of app functions.</li>
                                <li>Transparent scopes and purposes for data access.</li>
                                <li>Privacy Policy hosted on a verified institution domain.</li>
                                <li>Publicly viewable page without login.</li>
                            </ul>
                            {{-- <div class="alert alert-warning mt-3 mb-0" role="alert">
                                <strong>Hosting Note:</strong> Do not host on third-party platforms (Google Sites, social media, etc.). Use an institution-owned domain.
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ ID -->
        <div class="container d-none" id="faq-id">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h4">Pertanyaan Umum</h2>
                            <h3 class="h6 mt-3">Apakah halaman ini bisa diakses tanpa login?</h3>
                            <p class="text-muted">Ya. Informasi SIGARDA & Kebijakan Privasi tersedia untuk publik.</p>
                            <h3 class="h6">Siapa pengelola SIGARDA?</h3>
                            <p class="text-muted">Kantor Kementerian Agama Kabupaten Pesisir Selatan.</p>
                            <h3 class="h6">Apakah semua data disimpan di Google Drive?</h3>
                            <p class="text-muted">Tidak. Drive hanya digunakan bila diaktifkan. Arsip inti berada di server instansi.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5">Checklist Kelayakan (Verifikasi Google)</h3>
                            <ul class="list-check">
                                <li>Identitas & brand aplikasi jelas dan konsisten.</li>
                                <li>Deskripsi fungsi aplikasi lengkap & mudah dipahami.</li>
                                <li>Transparansi izin (scopes) & tujuan akses data tercantum.</li>
                                <li>Kebijakan Privasi pada domain terverifikasi milik instansi.</li>
                                <li>Halaman publik dapat dilihat tanpa login.</li>
                            </ul>
                            {{-- <div class="alert alert-warning mt-3 mb-0" role="alert">
                                <strong>Catatan Hosting:</strong> Jangan host di platform pihak ketiga. Gunakan domain milik instansi.
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT EN -->
    <section id="contact" class="py-5 bg-light">
        <div class="container" id="contact-en">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h4">Official Contact</h2>
                    <p class="text-muted small">
                        For app questions, data policy, or data-subject requests (access, correction, deletion):
                    </p>
                    <ul class="mb-0">
                        <li><strong>Institution:</strong> Office of the Ministry of Religious Affairs — Pesisir Selatan</li>
                        <li><strong>Address:</strong> <em>Jl. Imam Bonjol No.1, IV Jurai, Painan, Pesisir Selatan, West Sumatra, Indonesia</em></li>
                        <li><strong>Email:</strong> <a href="mailto:sigarda@kemenagpessel.com">sigarda@kemenagpessel.com</a></li>
                        <li><strong>Phone:</strong> <a href="tel:+6289677542744">+62-896-7754-2744</a></li>
                        <li><strong>Service Hours:</strong> Monday–Friday, 08:00–16:00 WIB</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- CONTACT ID -->
        <div class="container d-none" id="kontak">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h4">Kontak Resmi</h2>
                    <p class="text-muted small">
                        Untuk pertanyaan aplikasi, kebijakan data, atau permintaan hak subjek data (akses, koreksi, penghapusan):
                    </p>
                    <ul class="mb-0">
                        <li><strong>Instansi:</strong> Kantor Kementerian Agama Kabupaten Pesisir Selatan</li>
                        <li><strong>Alamat:</strong> <em>Jl. Imam Bonjol No.1, IV Jurai, Painan, Pesisir Selatan, Sumatera Barat, Indonesia</em></li>
                        <li><strong>Email:</strong> <a href="mailto:sigarda@kemenagpessel.com">sigarda@kemenagpessel.com</a></li>
                        <li><strong>Telepon:</strong> <a href="tel:+6289677542744">+62-896-7754-2744</a></li>
                        <li><strong>Jam Layanan:</strong> Senin–Jumat, 08.00–16.00 WIB</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row gy-3">
                <div class="col-lg-6">
                    <h5 class="mb-2">SIGARDA</h5>
                    <p class="small mb-0">Digital archiving system for managing civil servant personnel records — Ministry of Religious Affairs, Pesisir Selatan.</p>
                </div>
                <div class="col-6 col-lg-3">
                    <h6 class="mb-2">Links</h6>
                    <ul class="list-unstyled small mb-0">
                        <li><a class="link-light link-underline-opacity-0" href="/#about">About</a></li>
                        <li><a class="link-light link-underline-opacity-0" href="/#features">Features</a></li>
                        <li><a class="link-light link-underline-opacity-0" href="/privacy-policy">Privacy Policy</a></li>
                        <li><a class="link-light link-underline-opacity-0" href="/terms-and-conditions">Terms & Conditions</a></li>
                        <li><a class="link-light link-underline-opacity-0" href="/terms-of-service">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-3">
                    <h6 class="mb-2">Legal</h6>
                    <ul class="list-unstyled small mb-0">
                        <li>© <span id="year"></span> Kemenag Pesisir Selatan</li>
                        <li>This page is publicly accessible without login.</li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary my-3">
            <div class="small">Hosted on an institution-owned domain. Ensure this URL matches the Google OAuth Consent Screen.</div>
        </div>
    </footer>

    <script>
        // Language switcher
        const btnEn = document.getElementById('btn-en');
        const btnId = document.getElementById('btn-id');

        const pairs = [
            ['hero-en', 'hero-id'],
            ['about-en', 'about-id'],
            ['features-en', 'features-id'],
            ['data-en', 'data-id'],
            ['privacy-en', 'privasi'],
            ['faq-en', 'faq-id'],
            ['contact-en', 'kontak']
        ];

        function setLang(lang) {
            const isEN = lang === 'en';
            btnEn.classList.toggle('btn-outline-success', !isEN);
            btnEn.classList.toggle('btn-success', isEN);
            btnEn.classList.toggle('active', isEN);
            btnId.classList.toggle('btn-outline-secondary', isEN);
            btnId.classList.toggle('btn-success', !isEN);
            btnId.classList.toggle('active', !isEN);

            // Toggle pairs
            pairs.forEach(([en, id]) => {
                document.getElementById(en)?.classList.toggle('d-none', !isEN);
                document.getElementById(id)?.classList.toggle('d-none', isEN);
            });

            // Update hash anchors so navbar links point to visible sections
            document.querySelectorAll('.navbar .nav-link').forEach(a => {
                const href = a.getAttribute('href') || '';
                const base = href.replace('#tentang', '#about')
                    .replace('#fitur', '#features')
                    .replace('#privasi', '#privacy')
                    .replace('#kontak', '#contact');
                if (isEN) {
                    a.setAttribute('href', base);
                } else {
                    a.setAttribute('href', base
                        .replace('#about', '#tentang')
                        .replace('#features', '#fitur')
                        .replace('#privacy', '#privasi')
                        .replace('#contact', '#kontak'));
                }
            });

            // Smooth scroll to top after switching
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            // Update <html lang>
            document.documentElement.setAttribute('lang', isEN ? 'en' : 'id');
        }

        btnEn.addEventListener('click', () => setLang('en'));
        btnId.addEventListener('click', () => setLang('id'));

        // Footer year
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
