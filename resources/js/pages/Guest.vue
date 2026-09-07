<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans">
        
        <header class="bg-emerald-900 text-white sticky top-0 z-50 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center border border-emerald-700">
                            <span class="text-xl font-bold text-amber-400">PST</span>
                        </div>
                        <div>
                            <h1 class="text-lg sm:text-xl font-bold tracking-wide">MTQ KABUPATEN PESISIR SELATAN</h1>
                            <p class="text-xs text-emerald-200">Wahana Syiar Islam dan Prestasi Generasi Qur'ani</p>
                        </div>
                    </div>

                    <nav class="hidden md:flex items-center space-x-6 text-sm font-medium">
                        <a href="#beranda" class="hover:text-amber-400 transition">Beranda</a>
                        <a href="#tentang" class="hover:text-amber-400 transition">Tentang</a>
                        <a href="#cabang" class="hover:text-amber-400 transition">Cabang Lomba</a>
                        <a href="#jadwal" class="hover:text-amber-400 transition">Jadwal</a>
                        <a href="#informasi" class="hover:text-amber-400 transition">Informasi</a>
                    </nav>

                    <div class="flex items-center space-x-3">
                        <a href="/login" class="bg-amber-500 hover:bg-amber-600 text-emerald-950 font-semibold px-4 py-2 rounded-lg text-sm shadow transition">
                            Login Peserta
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <section id="beranda" class="relative bg-gradient-to-br from-emerald-900 via-emerald-800 to-teal-900 text-white py-20 lg:py-28 overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#f59e0b_1px,transparent_1px)] [background-size:16px_16px]"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                <span class="inline-block bg-amber-400/20 text-amber-300 border border-amber-400/30 px-4 py-1.5 rounded-full text-xs font-semibold tracking-wider uppercase mb-6">
                    Resmi • Kabupaten Pesisir Selatan 2026
                </span>
                <h2 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6 max-w-4xl mx-auto leading-tight">
                    Musabaqah Tilawatil Qur'an <br class="hidden sm:inline" /> Tingkat Kabupaten Pesisir Selatan
                </h2>
                <p class="text-base sm:text-lg text-emerald-100 max-w-2xl mx-auto mb-10 leading-relaxed">
                    Mewujudkan masyarakat yang religius, berakhlak mulia, serta mencintai Al-Qur'an melalui ajang kompetisi tilawah, tahfiz, dan pendalaman ilmu Al-Qur'an.
                </p>
                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                    <a href="#jadwal" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-emerald-950 font-bold px-8 py-3.5 rounded-xl shadow-lg transition">
                        Lihat Jadwal & Arena
                    </a>
                    <a href="#cabang" class="w-full sm:w-auto bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold px-8 py-3.5 rounded-xl backdrop-blur-sm transition">
                        Cabang Musabaqah
                    </a>
                </div>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-20 w-full">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-slate-100 flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl">📍</div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Lokasi Utama</p>
                        <h4 class="text-base font-bold text-slate-800">Painan, Pesisir Selatan</h4>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-slate-100 flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xl">🏆</div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Cabang Diperlombakan</p>
                        <h4 class="text-base font-bold text-slate-800">Tilawah, Tahfiz, Tartil, dll</h4>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-slate-100 flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xl">👥</div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Kecamatan Peserta</p>
                        <h4 class="text-base font-bold text-slate-800">15 Kecamatan Se-Pessel</h4>
                    </div>
                </div>
            </div>
        </section>

        <section id="tentang" class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-emerald-700 font-bold text-sm tracking-wider uppercase">Tentang Kegiatan</span>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2 mb-6">
                        Menjunjung Tinggi Syiar Islam di Bumi Langkuik Kapa
                    </h3>
                    <p class="text-slate-600 leading-relaxed mb-4">
                        MTQ Kabupaten Pesisir Selatan merupakan agenda resmi keagamaan berkala yang mempertemukan kafilah-kafilah terbaik dari seluruh kecamatan di Kabupaten Pesisir Selatan. Ajang ini tidak hanya sekadar kompetisi seni baca Al-Qur'an, tetapi juga momentum strategis untuk membina kader-kader Qur'ani yang unggul.
                    </p>
                    <p class="text-slate-600 leading-relaxed mb-6">
                        Dengan sistem penilaian digital yang transparan dan akuntabel, gelaran tahun ini berkomitmen untuk melahirkan qari-qariah dan hafizh-hafizhah berprestasi yang siap mengharumkan nama daerah di tingkat provinsi maupun nasional.
                    </p>
                    <div class="flex items-center space-x-4">
                        <div class="border-l-4 border-emerald-600 pl-4">
                            <p class="text-sm font-semibold text-slate-800">"Al-Qur'an Pedoman Hidupku"</p>
                            <span class="text-xs text-slate-500">Panitia Pelaksana MTQ Pessel 2026</span>
                        </div>
                    </div>
                </div>
                <div class="bg-emerald-800 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden flex flex-col justify-center min-h-[320px]">
                    <div class="absolute right-0 bottom-0 opacity-10 text-9xl font-extrabold select-none">QS</div>
                    <h4 class="text-xl font-bold mb-4 text-amber-400">Informasi Penting Pendaftaran</h4>
                    <ul class="space-y-3 text-emerald-100 text-sm">
                        <li class="flex items-start space-x-2">
                            <span class="text-amber-400 font-bold">✓</span>
                            <span>Verifikasi berkas kafilah dilakukan terpusat melalui admin kecamatan masing-masing.</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span class="text-amber-400 font-bold">✓</span>
                            <span>Peserta wajib melampirkan akta kelahiran, KTP/Kartu Identitas Anak, serta surat mandate resmi.</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span class="text-amber-400 font-bold">✓</span>
                            <span>Buku panduan teknis musabaqah dapat diunduh melalui panel informasi di bawah.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="cabang" class="bg-slate-100 py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="text-emerald-700 font-bold text-sm tracking-wider uppercase">Kategori Musabaqah</span>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2">Cabang Lomba Utama</h3>
                    <p class="text-slate-600 mt-2 text-sm">Berbagai cabang musabaqah Al-Qur'an yang dilombakan pada perhelatan tahun ini.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="cabang in daftarCabang" :key="cabang.id" class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition border border-slate-200 flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-700 rounded-xl flex items-center justify-center font-bold text-xl mb-4">
                                {{ cabang.icon }}
                            </div>
                            <h4 class="text-lg font-bold text-slate-900 mb-2">{{ cabang.nama }}</h4>
                            <p class="text-slate-600 text-xs leading-relaxed mb-4">{{ cabang.deskripsi }}</p>
                        </div>
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 py-1 px-2.5 rounded-md inline-block w-max">
                            {{ cabang.golongan }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section id="informasi" class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-12">
                <div>
                    <span class="text-emerald-700 font-bold text-sm tracking-wider uppercase">Pusat Informasi</span>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2">Berita & Pengumuman Terbaru</h3>
                </div>
                <span class="text-sm text-slate-500 mt-2 md:mt-0">Update langsung dari Sekretariat Panitia</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="item in pengumuman" :key="item.id" class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200 flex flex-col justify-between">
                    <div>
                        <div class="bg-emerald-900 h-36 flex items-center justify-center text-amber-400 font-bold text-lg px-4 text-center">
                            MTQ PESSEL 2026
                        </div>
                        <div class="p-6">
                            <span class="text-xs text-emerald-700 font-semibold bg-emerald-50 px-2.5 py-1 rounded-md">{{ item.tanggal }}</span>
                            <h4 class="text-base font-bold text-slate-900 mt-3 mb-2">{{ item.judul }}</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">{{ item.ringkasan }}</p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-0">
                        <a href="#" class="text-xs font-bold text-emerald-700 hover:text-emerald-900 flex items-center space-x-1">
                            <span>Baca Selengkapnya</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <footer class="bg-slate-900 text-slate-400 py-12 mt-auto border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <div>
                        <h4 class="text-white font-bold text-base mb-3">MTQ Kabupaten Pesisir Selatan</h4>
                        <p class="text-xs leading-relaxed text-slate-400">
                            Website resmi pendaftaran, penjurian, dan informasi pelaksanaan Musabaqah Tilawatil Qur'an di Kabupaten Pesisir Selatan.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-base mb-3">Tautan Cepat</h4>
                        <ul class="space-y-2 text-xs">
                            <li><a href="#beranda" class="hover:text-amber-400 transition">Beranda Utama</a></li>
                            <li><a href="#tentang" class="hover:text-amber-400 transition">Tentang Kegiatan</a></li>
                            <li><a href="#cabang" class="hover:text-amber-400 transition">Cabang Musabaqah</a></li>
                            <li><a href="/login" class="hover:text-amber-400 transition">Login Sistem</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-base mb-3">Sekretariat Panitia</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Kantor Bupati Pesisir Selatan<br />
                            Jl. Agus Salim, Painan, Sumatera Barat<br />
                            Email: info@mtq.pesisirselatankab.go.id
                        </p>
                    </div>
                </div>
                <div class="border-t border-slate-800 pt-6 text-center text-xs text-slate-500">
                    &copy; 2026 Pemerintah Kabupaten Pesisir Selatan. All rights reserved. Developed with Laravel & Vue.
                </div>
            </div>
        </footer>

    </div>
</template>

<script setup>
import { ref } from 'vue'

const daftarCabang = ref([
    { id: 1, icon: '📖', nama: 'Tilawah Al-Qur\'an', golongan: 'Anak-anak, Remaja, Dewasa', deskripsi: 'Melagungkan ayat-ayat suci Al-Qur\'an dengan kaidah tajwid, lagu, dan suara yang indah.' },
    { id: 2, icon: '🧠', nama: 'Hifzhil Al-Qur\'an', golongan: '1 Juz, 5 Juz, 10 Juz, 20 Juz, 30 Juz', deskripsi: 'Uji hafalan Al-Qur\'an dengan ketepatan dan kefasihan bacaan sesuai kaidah.' },
    { id: 3, icon: '✍️', nama: 'Tartil Al-Qur\'an', golongan: 'Anak-anak & Remaja', deskripsi: 'Membaca Al-Qur\'an dengan tartil, pelafalan huruf yang tepat, serta adab tilawah.' },
    { id: 4, icon: '📜', nama: 'Fahm & Syarh Al-Qur\'an', golongan: 'Beregu / Fahmil & Syarhil', deskripsi: 'Pemahaman isi kandungan Al-Qur\'an serta kemampuan menyampaikan pesan dalam bentuk pidato.' }
])

const pengumuman = ref([
    { id: 1, tanggal: '06 September 2026', judul: 'Rapat Koordinasi Persiapan Kafilah Kecamatan', ringkasan: 'Panitia pelaksana mengundang seluruh perwakilan LPTQ kecamatan untuk mengikuti rakor teknis persiapan akhir.' },
    { id: 2, tanggal: '01 September 2026', judul: 'Pembukaan Sistem Pendaftaran Peserta MTQ', ringkasan: 'Aplikasi pendaftaran online resmi dibuka bagi seluruh admin kecamatan untuk menginput data kafilah terbaik.' },
    { id: 3, tanggal: '25 Agustus 2026', judul: 'Penetapan Arena Utama dan Cabang Lomba', ringkasan: 'Lokasi masjid dan aula pusat kegiatan musabaqah di kota Painan telah ditetapkan secara resmi.' }
])
</script>

<style scoped>
/* Styling tambahan spesifik jika diperlukan */
</style>