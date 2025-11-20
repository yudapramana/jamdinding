<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>e-MTQ Platform – Digitalisasi Event MTQ Anda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bisa diganti dengan build Tailwind sendiri --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-950 text-slate-100">

    {{-- NAVBAR --}}
    <header class="sticky top-0 z-20 bg-slate-950/90 border-b border-slate-800 backdrop-blur">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center text-sm font-bold">
                    MTQ
                </div>
                <div>
                    <p class="text-sm font-semibold leading-tight">e-MTQ Platform</p>
                    <p class="text-[11px] text-slate-300">Digital Event Management untuk Musabaqah Tilawatil Qur’an</p>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-6 text-xs text-slate-200">
                <a href="#fitur" class="hover:text-emerald-400">Fitur</a>
                <a href="#event" class="hover:text-emerald-400">Portofolio Event</a>
                <a href="#harga" class="hover:text-emerald-400">Paket Harga</a>
                <a href="#kontak" class="hover:text-emerald-400">Kontak</a>
            </nav>

            <div class="flex items-center gap-2">
                <button onclick="scrollToSection('kontak')" class="hidden md:inline-flex px-3 py-1.5 text-xs border border-slate-600 rounded-full hover:border-emerald-400 hover:text-emerald-300 transition">
                    Jadwalkan Demo
                </button>
                <button onclick="goToLogin()" class="px-3.5 py-1.5 text-xs rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold">
                    Masuk Portal Event
                </button>
            </div>
        </div>
    </header>

    {{-- HERO SECTION --}}
    <section class="bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">
        <div class="max-w-6xl mx-auto px-4 py-12 md:py-16 grid md:grid-cols-2 gap-10 items-center">
            <div>
                <p class="text-[11px] uppercase tracking-[0.25em] text-emerald-400 mb-2">
                    Solusi Digital untuk Panitia MTQ
                </p>
                <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-4">
                    Kelola <span class="text-emerald-400">Pendaftaran, Jadwal, dan Penilaian</span> MTQ
                    dalam Satu Platform Terintegrasi.
                </h1>
                <p class="text-sm text-slate-200 mb-4">
                    e-MTQ Platform membantu Kementerian Agama, LPTQ, pemerintah daerah, dan lembaga
                    penyelenggara MTQ untuk mengelola seluruh tahapan event secara profesional – mulai dari
                    pendaftaran kafilah, penempatan cabang lomba, hingga rekapitulasi nilai dewan hakim.
                </p>
                <ul class="text-sm text-slate-200/90 space-y-1 mb-6">
                    <li>• Pendaftaran online per cabang, golongan, dan kategori usia.</li>
                    <li>• Dashboard panitia & dewan hakim dengan akses terpisah.</li>
                    <li>• Rekap nilai otomatis dan hasil yang mudah dipublikasikan.</li>
                    <li>• Support multi-event (Kabupaten, Provinsi, Nasional) dalam satu sistem.</li>
                </ul>
                <div class="flex flex-wrap gap-3">
                    <button onclick="scrollToSection('event')" class="px-4 py-2 text-xs md:text-sm rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold">
                        Lihat Contoh Event MTQ
                    </button>
                    <button onclick="scrollToSection('fitur')" class="px-4 py-2 text-xs md:text-sm rounded-full border border-slate-500 hover:border-emerald-400 text-slate-100">
                        Pelajari Fitur Lengkap
                    </button>
                </div>
                <p class="mt-4 text-[11px] text-slate-400">
                    Cocok untuk: Kemenag Kab/Kota, Pemprov, LPTQ, dan lembaga pendidikan yang mengadakan MTQ internal.
                </p>
            </div>

            {{-- “Preview” aplikasi + highlights --}}
            <div class="relative">
                <div class="absolute -inset-3 bg-emerald-500/20 blur-3xl opacity-60"></div>
                <div class="relative bg-slate-900 border border-slate-700 rounded-2xl p-4 shadow-xl shadow-slate-900/80">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-slate-100">Preview Panel Panitia</p>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/50 text-emerald-300">
                            Realtime Monitoring
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-[11px]">
                        <div class="bg-slate-800/80 rounded-xl p-3 border border-slate-700">
                            <p class="font-semibold mb-1 text-slate-50">Pendaftaran</p>
                            <p class="text-slate-300">Pantau jumlah peserta per cabang & kafilah dalam satu tampilan.</p>
                        </div>
                        <div class="bg-slate-800/80 rounded-xl p-3 border border-slate-700">
                            <p class="font-semibold mb-1 text-slate-50">Jadwal Lomba</p>
                            <p class="text-slate-300">Atur jadwal per majelis dengan notifikasi otomatis.</p>
                        </div>
                        <div class="bg-slate-800/80 rounded-xl p-3 border border-slate-700">
                            <p class="font-semibold mb-1 text-slate-50">Penilaian Hakim</p>
                            <p class="text-slate-300">Input nilai by device, rekap otomatis sesuai kaidah penjurian.</p>
                        </div>
                    </div>
                    <div class="mt-4 text-[11px] text-slate-300">
                        “Dengan e-MTQ, panitia tidak lagi direpotkan oleh kertas nilai, daftar peserta manual,
                        dan rekap Excel yang rawan salah.”
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FITUR UTAMA --}}
    <section id="fitur" class="bg-slate-950 border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-4 py-10">
            <h2 class="text-xl font-semibold mb-1">Fitur Utama e-MTQ Platform</h2>
            <p class="text-sm text-slate-300 mb-6">
                Dibangun khusus untuk karakteristik penyelenggaraan MTQ di Indonesia.
            </p>

            <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <h3 class="font-semibold mb-2 text-emerald-300">Multi Event & Multi Lembaga</h3>
                    <p class="text-slate-200 text-[13px]">
                        Satu platform untuk banyak event MTQ: kabupaten, provinsi, bahkan internal lembaga.
                        Setiap event punya konfigurasi sendiri (lokasi, cabang, kafilah, dan token penilaian).
                    </p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <h3 class="font-semibold mb-2 text-emerald-300">Digitalisasi Penilaian Hakim</h3>
                    <p class="text-slate-200 text-[13px]">
                        Dewan hakim melakukan input nilai langsung melalui aplikasi. Sistem menghitung
                        rata-rata, peringkat, dan hasil akhir secara otomatis sesuai aturan yang disepakati.
                    </p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <h3 class="font-semibold mb-2 text-emerald-300">Laporan & Dokumentasi</h3>
                    <p class="text-slate-200 text-[13px]">
                        Export laporan peserta, daftar pemenang, sertifikat digital, hingga dokumentasi
                        nilai per majelis dalam format yang siap dilampirkan sebagai bukti kegiatan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- PORTOFOLIO EVENT (PAKAI DATA TABEL events) --}}
    <section id="event" class="bg-slate-950 border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-4 py-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold">Event MTQ yang Menggunakan e-MTQ</h2>
                    <p class="text-sm text-slate-300">
                        Contoh event yang telah atau sedang dikonfigurasi melalui sistem kami.
                        Klik salah satu untuk mencoba mode “Portal Event”.
                    </p>
                </div>
                <span class="hidden md:inline-flex text-[11px] px-3 py-1 rounded-full bg-slate-900 border border-slate-700 text-slate-200">
                    {{ $events->count() }} event terdaftar
                </span>
            </div>

            @if ($events->isEmpty())
                <p class="text-sm text-slate-300">
                    Belum ada event aktif. Silakan hubungi kami untuk membuat event pertama Anda.
                </p>
            @else
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    @foreach ($events as $event)
                        {{-- KIRIM SELURUH OBJECT EVENT KE JS --}}
                        <button type="button" onclick='selectEvent(@json($event))' class="text-left bg-slate-900 border border-slate-800 hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-500/20 transition rounded-xl p-4 flex flex-col h-full">
                            <div class="flex items-start gap-3 mb-2">
                                {{-- Logo / inisial --}}
                                <div class="flex-shrink-0">
                                    @if ($event->logo_event)
                                        <img src="{{ $event->logo_event }}" alt="Logo {{ $event->nama_event }}" class="w-9 h-9 rounded-lg object-contain bg-slate-950">
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/40 flex items-center justify-center text-[11px] font-semibold text-emerald-300">
                                            {{ \Illuminate\Support\Str::of($event->nama_event)->explode(' ')->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode('') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-[13px] font-semibold text-slate-50 leading-snug">
                                        {{ $event->nama_event }}
                                    </p>
                                    <p class="text-[11px] text-slate-300">
                                        {{ $event->nama_aplikasi }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-300 space-y-1 mb-3">
                                <p>
                                    Lokasi: <span class="font-medium">{{ $event->lokasi_event ?? '-' }}</span>
                                </p>
                                <p>
                                    Jadwal:
                                    <span class="font-medium">
                                        {{ \Carbon\Carbon::parse($event->tanggal_mulai)->format('d M Y') }}
                                        &ndash;
                                        {{ \Carbon\Carbon::parse($event->tanggal_selesai)->format('d M Y') }}
                                    </span>
                                </p>
                                @if ($event->tagline)
                                    <p class="italic text-slate-300/90">
                                        “{{ $event->tagline }}”
                                    </p>
                                @endif
                            </div>

                            <div class="mt-auto flex items-center justify-between text-[11px] pt-2 border-t border-slate-800">
                                <span class="text-slate-400">
                                    Klik untuk masuk portal event
                                </span>
                                <span class="inline-flex items-center gap-1 text-emerald-300">
                                    <span>Masuk</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5.25 15.75 12 9 18.75" />
                                    </svg>
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif

            <p class="mt-4 text-[11px] text-slate-400">
                Catatan: Event di atas dapat dikonfigurasi ulang sesuai format MTQ di daerah atau lembaga Anda.
            </p>
        </div>
    </section>

    {{-- PAKET HARGA SINGKAT --}}
    <section id="harga" class="bg-slate-950 border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-4 py-10">
            <h2 class="text-xl font-semibold mb-1">Paket Layanan e-MTQ</h2>
            <p class="text-sm text-slate-300 mb-6">
                Biaya menyesuaikan skala event dan kebutuhan kustomisasi.
            </p>

            <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
                    <p class="text-xs font-semibold text-emerald-300 uppercase mb-1">Basic</p>
                    <p class="text-lg font-bold mb-2">Event Lokal</p>
                    <p class="text-[13px] text-slate-200 mb-3">
                        Cocok untuk MTQ tingkat kecamatan / internal lembaga.
                    </p>
                    <ul class="text-[12px] text-slate-200 space-y-1">
                        <li>• Pendaftaran peserta online.</li>
                        <li>• Panel panitia & hakim dasar.</li>
                        <li>• Rekap pemenang dan sertifikat.</li>
                    </ul>
                </div>

                <div class="bg-slate-900 border border-emerald-500 rounded-xl p-4 relative overflow-hidden">
                    <span class="absolute right-3 top-3 text-[10px] px-2 py-0.5 rounded-full bg-emerald-500 text-slate-950 font-semibold">
                        Paling Populer
                    </span>
                    <p class="text-xs font-semibold text-emerald-300 uppercase mb-1">Pro</p>
                    <p class="text-lg font-bold mb-2">Kabupaten / Kota</p>
                    <p class="text-[13px] text-slate-200 mb-3">
                        Untuk penyelenggaraan MTQ resmi tingkat kabupaten/kota dengan banyak cabang lomba.
                    </p>
                    <ul class="text-[12px] text-slate-200 space-y-1">
                        <li>• Semua fitur Basic.</li>
                        <li>• Manajemen kafilah per kecamatan.</li>
                        <li>• Jadwal majelis multi-hari.</li>
                        <li>• Laporan lengkap untuk dokumen kegiatan.</li>
                    </ul>
                </div>

                <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
                    <p class="text-xs font-semibold text-emerald-300 uppercase mb-1">Enterprise</p>
                    <p class="text-lg font-bold mb-2">Provinsi / Nasional</p>
                    <p class="text-[13px] text-slate-200 mb-3">
                        Untuk event berskala besar dengan kebutuhan integrasi dan support intensif.
                    </p>
                    <ul class="text-[12px] text-slate-200 space-y-1">
                        <li>• semua fitur Pro.</li>
                        <li>• Kustomisasi alur penjurian.</li>
                        <li>• Integrasi SSO/SPBE (opsional).</li>
                        <li>• Tim support saat pelaksanaan.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- KONTAK / CTA DEMO --}}
    <section id="kontak" class="bg-slate-950 border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-4 py-10">
            <div class="bg-slate-900 border border-slate-700 rounded-2xl p-5 md:p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold mb-1">Ingin e-MTQ Dipakai di Event Anda?</h2>
                    <p class="text-sm text-slate-300">
                        Hubungi kami untuk presentasi singkat via Zoom/Meeting dan demo langsung sistem e-MTQ.
                    </p>
                </div>
                <div class="text-sm text-slate-200">
                    <p>WhatsApp: <span class="font-semibold">+62-8xx-xxxx-xxxx</span></p>
                    <p>Email: <span class="font-semibold">admin@emtq-platform.id</span></p>
                    <button onclick="scrollToSection('event')" class="mt-2 w-full md:w-auto px-4 py-2 rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs">
                        Coba Lihat Event Contoh
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="border-t border-slate-800 py-4 text-center text-[11px] text-slate-400">
        e-MTQ Platform &copy; {{ now()->year }} • Solusi Digital Manajemen Lomba MTQ.
    </footer>

    <script>
        function selectEvent(event) {
            if (!event) return;

            // safety: pastikan object biasa
            try {
                // simpan ke localStorage
                localStorage.setItem('event_key', event.event_key || '');
                localStorage.setItem('event_data', JSON.stringify(event));
            } catch (e) {
                console.warn('Gagal simpan ke localStorage', e);
            }

            // simpan ke cookie (30 hari)
            var d = new Date();
            d.setTime(d.getTime() + (30 * 24 * 60 * 60 * 1000));
            var expires = ";expires=" + d.toUTCString() + ";path=/";

            document.cookie = "event_key=" + encodeURIComponent(event.event_key || '') + expires;
            document.cookie = "event_data=" + encodeURIComponent(JSON.stringify(event)) + expires;

            // redirect ke login portal
            window.location.href = "{{ route('login') }}";
        }

        function goToLogin() {
            window.location.href = "{{ route('login') }}";
        }

        function scrollToSection(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>
