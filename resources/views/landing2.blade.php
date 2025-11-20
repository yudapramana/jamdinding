<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SIGARDA — Sistem Informasi Gerbang Arsip Digital ASN</title>
    <meta name="description" content="SIGARDA adalah Sistem Informasi Gerbang Arsip Digital untuk pengelolaan arsip kepegawaian ASN Kementerian Agama Kabupaten Pesisir Selatan secara aman, terstruktur, dan patuh regulasi." />

    <!-- Canonical (gunakan domain terverifikasi yang kamu miliki) -->
    <link rel="canonical" href="https://sigarda.kemenagpessel.go.id/" />

    <!-- Open Graph -->
    <meta property="og:title" content="SIGARDA — Sistem Informasi Gerbang Arsip Digital ASN" />
    <meta property="og:description" content="Pengelolaan arsip kepegawaian ASN yang aman, terstruktur, dan patuh regulasi." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://sigarda.kemenagpessel.go.id/" />
    <meta property="og:image" content="https://sigarda.kemenagpessel.go.id/assets/sigarda-og.png" />

    <!-- Favicon -->
    <link rel="icon" href="/assets/sigarda-icon.png" />

    <!-- Minimal CSS (tanpa framework) -->
    <style>
        :root {
            --primary: #0f766e;
            /* teal-700 */
            --primary-600: #0d9488;
            /* teal-600 */
            --dark: #0b132b;
            --muted: #475569;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --radius: 14px;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
            color: #0f172a;
            background: var(--bg);
            line-height: 1.6
        }

        a {
            color: var(--primary);
            text-decoration: none
        }

        a:hover {
            text-decoration: underline
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px
        }

        header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 30
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px
        }

        .brand {
            display: flex;
            gap: 10px;
            align-items: center
        }

        .brand img {
            height: 36px;
            width: 36px;
            border-radius: 8px
        }

        .brand span {
            font-weight: 700;
            letter-spacing: .2px
        }

        .nav a {
            margin-left: 18px;
            color: #0f172a
        }

        .hero {
            padding: 64px 0;
            background: linear-gradient(180deg, #ecfeff, transparent)
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 32px
        }

        .badge {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            background: #ccfbf1;
            color: #134e4a;
            border: 1px solid #99f6e4;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 13px
        }

        h1 {
            font-size: 40px;
            line-height: 1.15;
            margin: 14px 0 10px
        }

        .lead {
            color: var(--muted);
            font-size: 18px;
            margin-bottom: 22px
        }

        .cta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius: 999px;
            padding: 12px 18px;
            border: 1px solid var(--primary);
            background: var(--primary);
            color: white;
            font-weight: 600
        }

        .btn.secondary {
            background: #fff;
            color: var(--primary);
            border-color: var(--primary)
        }

        .btn:focus {
            outline: 3px solid #99f6e4;
            outline-offset: 2px
        }

        .hero-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px
        }

        .section {
            padding: 56px 0
        }

        .grid {
            display: grid;
            gap: 18px
        }

        .grid.features {
            grid-template-columns: repeat(3, 1fr)
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 8px
        }

        .muted {
            color: var(--muted)
        }

        .list-check {
            list-style: none;
            padding-left: 0;
            margin: 0
        }

        .list-check li {
            padding-left: 28px;
            position: relative;
            margin: 8px 0
        }

        .list-check li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            color: var(--primary);
            font-weight: 700
        }

        .kbd {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 2px 6px;
            border-radius: 6px
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px
        }

        footer {
            background: var(--dark);
            color: #e2e8f0;
            margin-top: 32px
        }

        footer a {
            color: #a7f3d0
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1.2fr;
            gap: 22px;
            padding: 28px 0
        }

        .small {
            font-size: 14px
        }

        .note {
            background: #fffbeb;
            border: 1px solid #fef08a;
            border-radius: 12px;
            padding: 12px
        }

        @media (max-width: 900px) {

            .hero-grid,
            .grid.features,
            .two-col,
            .footer-grid {
                grid-template-columns: 1fr
            }

            h1 {
                font-size: 32px
            }
        }
    </style>

    <!-- Structured Data -->
    <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"WebApplication",
    "name":"SIGARDA",
    "url":"https://sigarda.kemenagpessel.go.id/",
    "applicationCategory":"GovernmentApplication",
    "operatingSystem":"Web",
    "description":"Sistem Informasi Gerbang Arsip Digital untuk pengelolaan arsip kepegawaian ASN.",
    "provider":{
      "@type":"GovernmentOrganization",
      "name":"Kantor Kementerian Agama Kabupaten Pesisir Selatan"
    },
    "privacyPolicy":"https://sigarda.kemenagpessel.go.id/privacy-policy"
  }
  </script>
</head>

<body>
    <header aria-label="Navigasi utama">
        <div class="container nav" role="navigation">
            <div class="brand">
                <img src="/images/sigarda-icon.png" alt="Logo SIGARDA" />
                <span>S I G A R D A</span>
            </div>
            <nav>
                <a href="#tentang">Tentang</a>
                <a href="#fitur">Fitur</a>
                <a href="#data">Transparansi Data</a>
                <a href="#privasi">Kebijakan Privasi</a>
                <a href="#kontak">Kontak</a>
            </nav>
        </div>
    </header>

    <main id="content">
        <!-- Hero -->
        <section class="hero" aria-labelledby="hero-title">
            <div class="container hero-grid">
                <div>
                    <span class="badge" aria-label="Identitas aplikasi">
                        <svg width="16" height="16" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l9 4.9v9.8L12 22 3 16.7V6.9L12 2zm0 2.2L5 7v7.7l7 4.1 7-4.1V7l-7-2.8z" />
                        </svg>
                        Sistem Informasi Gerbang Arsip Digital (SIGARDA)
                    </span>
                    <h1 id="hero-title">Kelola Arsip Kepegawaian ASN secara Aman & Terstruktur</h1>
                    <p class="lead">
                        SIGARDA adalah aplikasi resmi <strong>Kementerian Agama Kabupaten Pesisir Selatan</strong> untuk mengunggah,
                        memverifikasi, menyimpan, dan menemukan kembali arsip digital pegawai secara cepat dengan standar keamanan dan kepatuhan yang berlaku.
                    </p>
                    <div class="cta" role="group" aria-label="Tindakan utama">
                        <a class="btn" href="#tentang" aria-label="Pelajari tentang SIGARDA">
                            Pelajari SIGARDA
                        </a>
                        <a class="btn secondary" href="#privasi" aria-label="Baca Kebijakan Privasi">
                            Kebijakan Privasi
                        </a>
                    </div>
                    <p class="small muted" style="margin-top:10px;">
                        Halaman ini dapat diakses publik tanpa perlu masuk/login.
                    </p>
                </div>
                <aside class="hero-card" aria-label="Ringkasan kepatuhan & transparansi">
                    <h2 style="margin:0 0 8px">Ringkasan Transparansi</h2>
                    <ul class="list-check small">
                        <li>Menjelaskan fungsi aplikasi & identitas resmi.</li>
                        <li>Menguraikan data & izin (scopes) yang diminta serta tujuannya.</li>
                        <li>Mencantumkan tautan ke Kebijakan Privasi yang sesuai Consent Screen.</li>
                        <li>Dapat dihosting pada <span class="kbd">domain terverifikasi</span> milik instansi.</li>
                    </ul>
                    <div class="note small" style="margin-top:10px">
                        Catatan: Aplikasi hanya meminta akses minimum yang diperlukan untuk bekerja.
                    </div>
                </aside>
            </div>
        </section>

        <!-- Tentang -->
        <section id="tentang" class="section" aria-labelledby="tentang-title">
            <div class="container two-col">
                <div class="card">
                    <h2 id="tentang-title">Tentang SIGARDA</h2>
                    <p class="muted">
                        SIGARDA (Sistem Informasi Gerbang Arsip Digital) adalah platform untuk digitalisasi arsip pegawai di lingkungan
                        Kementerian Agama Kabupaten Pesisir Selatan. Aplikasi ini membantu proses <em>upload</em> dokumen,
                        penamaan/klasifikasi, verifikasi berjenjang, hingga retensi arsip sesuai ketentuan.
                    </p>
                    <ul class="list-check">
                        <li>Identitas jelas: aplikasi resmi milik instansi pemerintah.</li>
                        <li>Fungsional: unggah, simpan, telusur, verifikasi, dan unduh arsip.</li>
                        <li>Keamanan: kontrol akses berbasis peran, enkripsi saat transit, audit trail.</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Manfaat Utama</h3>
                    <ul class="list-check">
                        <li>Pengurangan arsip kertas dan percepatan layanan kepegawaian.</li>
                        <li>Integrasi penyimpanan ke <span class="kbd">Google Drive</span> (opsional) untuk redundansi & ketersediaan.</li>
                        <li>Jejak verifikasi dan kepatuhan audit yang lebih baik.</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Fitur -->
        <section id="fitur" class="section" aria-labelledby="fitur-title">
            <div class="container">
                <h2 id="fitur-title" style="margin-top:0">Fitur Aplikasi</h2>
                <div class="grid features" role="list">
                    <article class="card" role="listitem">
                        <h3>Unggah & Klasifikasi</h3>
                        <p class="muted">Unggah dokumen (PDF, gambar), kategorikan sesuai standar arsip, dan beri metadata.</p>
                    </article>
                    <article class="card" role="listitem">
                        <h3>Verifikasi Berjenjang</h3>
                        <p class="muted">Alur verifikasi dokumen oleh petugas berwenang dengan catatan & status.</p>
                    </article>
                    <article class="card" role="listitem">
                        <h3>Pencarian Cepat</h3>
                        <p class="muted">Cari dokumen berdasarkan nama, NIP, jenis dokumen, atau tanggal.</p>
                    </article>
                    <article class="card" role="listitem">
                        <h3>Integrasi Drive (Opsional)</h3>
                        <p class="muted">Simpan salinan arsip ke Google Drive instansi untuk keandalan.</p>
                    </article>
                    <article class="card" role="listitem">
                        <h3>Keamanan & Audit</h3>
                        <p class="muted">Kontrol akses peran, enkripsi in-transit (HTTPS), & log aktivitas.</p>
                    </article>
                    <article class="card" role="listitem">
                        <h3>Ekspor & Retensi</h3>
                        <p class="muted">Ekspor arsip dan kelola retensi sesuai kebijakan internal.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Transparansi Data & Scopes -->
        <section id="data" class="section" aria-labelledby="data-title">
            <div class="container two-col">
                <div class="card">
                    <h2 id="data-title">Transparansi Data & Izin (OAuth Scopes)</h2>
                    <p class="muted">
                        SIGARDA meminta akses minimum yang diperlukan untuk menjalankan fungsi inti.
                        Jika integrasi Google Drive diaktifkan oleh admin instansi, kami dapat meminta izin berikut:
                    </p>
                    <ul class="list-check">
                        <li><span class="kbd">openid, email, profile</span> — Mengidentifikasi pengguna, menampilkan nama & email untuk pengalaman yang dipersonalisasi.</li>
                        <li><span class="kbd">https://www.googleapis.com/auth/drive.file</span> — Membuat & mengelola <em>hanya</em> file yang dibuat melalui SIGARDA (bukan seluruh Drive).</li>
                        <li><span class="kbd">https://www.googleapis.com/auth/drive.metadata.readonly</span> — Melihat metadata file yang relevan untuk sinkronisasi & pencarian.</li>
                    </ul>
                    <p class="small muted">
                        Alasan: untuk menyimpan salinan arsip ke folder Drive instansi, menjaga ketersediaan dan pemulihan bencana.
                    </p>
                </div>
                <div class="card">
                    <h3>Penggunaan, Penyimpanan, Berbagi, & Penghapusan Data</h3>
                    <ul class="list-check">
                        <li><strong>Penggunaan:</strong> data dipakai untuk otentikasi pengguna & pengelolaan arsip pegawai.</li>
                        <li><strong>Penyimpanan:</strong> arsip disimpan di server milik instansi; salinan ke Drive (jika diaktifkan).</li>
                        <li><strong>Berbagi:</strong> tidak dibagikan ke pihak ketiga di luar keperluan operasional & kewajiban hukum.</li>
                        <li><strong>Penghapusan:</strong> pengguna berwenang dapat menghapus/meminta hapus arsip sesuai kebijakan retensi.</li>
                        <li><strong>Kontrol Pengguna:</strong> dapat mencabut izin di <span class="kbd">myaccount.google.com/permissions</span>.</li>
                    </ul>
                    <p class="small muted">
                        Detail lengkap tersedia pada <a href="#privasi">Kebijakan Privasi</a>.
                    </p>
                </div>
            </div>
        </section>

        <!-- Kebijakan Privasi (tautan harus identik dengan di consent screen) -->
        <section id="privasi" class="section" aria-labelledby="privasi-title">
            <div class="container card">
                <h2 id="privasi-title">Kebijakan Privasi</h2>
                <p class="muted">
                    Silakan membaca Kebijakan Privasi resmi kami untuk memahami bagaimana data dikumpulkan, digunakan, disimpan,
                    dibagikan, serta pilihan/ kontrol yang Anda miliki.
                </p>
                <p>
                    <a class="btn secondary" href="https://sigarda.kemenagpessel.go.id/privacy-policy" rel="noopener" aria-label="Buka Kebijakan Privasi">
                        Buka Kebijakan Privasi
                    </a>
                </p>
                <p class="small muted">
                    <strong>Penting:</strong> URL di atas <em>harus persis sama</em> dengan tautan yang Anda masukkan pada Google OAuth Consent Screen.
                </p>
            </div>
        </section>

        <!-- Pertanyaan Umum -->
        <section class="section" aria-labelledby="faq-title">
            <div class="container two-col">
                <div class="card">
                    <h2 id="faq-title">Pertanyaan Umum</h2>
                    <h3>Apakah halaman ini bisa diakses tanpa login?</h3>
                    <p class="muted">Ya. Halaman informasi SIGARDA & Kebijakan Privasi tersedia untuk publik.</p>
                    <h3>Siapa pengelola SIGARDA?</h3>
                    <p class="muted">Kantor Kementerian Agama Kabupaten Pesisir Selatan. Detail kontak ada di bagian bawah.</p>
                    <h3>Apakah semua data disimpan di Google Drive?</h3>
                    <p class="muted">Tidak. Drive hanya digunakan bila integrasi diaktifkan. Arsip inti tetap pada server instansi.</p>
                </div>
                <div class="card">
                    <h3>Checklist Kelayakan Halaman (untuk verifikasi Google)</h3>
                    <ul class="list-check">
                        <li>Identitas & brand aplikasi jelas dan konsisten.</li>
                        <li>Deskripsi fungsi aplikasi lengkap dan mudah dipahami.</li>
                        <li>Transparansi izin (scopes) & tujuan akses data tercantum.</li>
                        <li>Tautan Kebijakan Privasi di domain terverifikasi sendiri.</li>
                        <li>Halaman publik dapat dilihat tanpa login.</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Kontak -->
        <section id="kontak" class="section" aria-labelledby="kontak-title">
            <div class="container card">
                <h2 id="kontak-title">Kontak Resmi</h2>
                <p class="muted small">
                    Untuk pertanyaan terkait aplikasi, kebijakan data, atau permintaan hak subjek data (akses, koreksi, penghapusan),
                    silakan hubungi:
                </p>
                <ul style="margin-top:0">
                    <li><strong>Instansi:</strong> Kantor Kementerian Agama Kabupaten Pesisir Selatan</li>
                    <li><strong>Alamat:</strong> <em>Jl. Imam Bonjol No. 01, IV Jurai, Painan, Pesisir Selatan, Sumatera Barat, Indonesia</em></li>
                    <li><strong>Email:</strong> <a href="mailto:pessel@kemenag.go.id">pessel@kemenag.go.id</a></li>
                    <li><strong>Telepon:</strong> <a href="tel:+6289677542744">+62-896-7754-2744</a></li>
                    <li><strong>Jam Layanan:</strong> Senin–Jumat, 08.00–16.00 WIB</li>
                </ul>
            </div>
        </section>
    </main>

    <footer role="contentinfo">
        <div class="container footer-grid">
            <div>
                <h3 style="margin:0 0 8px">SIGARDA</h3>
                <p class="small">
                    Sistem Informasi Gerbang Arsip Digital untuk pengelolaan arsip kepegawaian ASN di lingkungan
                    Kementerian Agama Kabupaten Pesisir Selatan.
                </p>
            </div>
            <div>
                <h4 style="margin:0 0 8px">Tautan</h4>
                <ul class="small" style="list-style:none;padding-left:0;margin:0">
                    <li><a href="#tentang">Tentang</a></li>
                    <li><a href="#fitur">Fitur</a></li>
                    <li><a href="#data">Transparansi Data</a></li>
                    <li><a href="https://sigarda.kemenagpessel.go.id/privacy-policy">Kebijakan Privasi</a></li>
                </ul>
            </div>
            <div>
                <h4 style="margin:0 0 8px">Legal</h4>
                <ul class="small" style="list-style:none;padding-left:0;margin:0">
                    <li>© <span id="year"></span> Kemenag Pesisir Selatan</li>
                    <li>Semua hak cipta dilindungi.</li>
                </ul>
            </div>
        </div>
        <div style="border-top:1px solid #1f2937;padding:12px 0">
            <div class="container small">Dihost pada domain milik instansi & dapat diverifikasi kepemilikannya.</div>
        </div>
        <script>
            document.getElementById('year').textContent = new Date().getFullYear();
        </script>
    </footer>
</body>

</html>
