<template>
  <div class="bg-slate-950 text-slate-100 min-h-screen">
    <!-- NAVBAR -->
    <header class="sticky top-0 z-20 bg-slate-950/90 border-b border-slate-800 backdrop-blur">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <!-- Brand -->
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center text-sm font-bold">
            MTQ
          </div>
          <div>
            <p class="text-sm font-semibold leading-tight">e-MTQ Platform</p>
            <p class="text-[11px] text-slate-300">
              Digital Event Management untuk Musabaqah Tilawatil Qur’an
            </p>
          </div>
        </div>

        <!-- Menu desktop -->
        <nav class="hidden md:flex items-center gap-6 text-xs text-slate-200">
          <a href="#fitur" class="hover:text-emerald-400" @click.prevent="scrollToSection('fitur')">Fitur</a>
          <a href="#event" class="hover:text-emerald-400" @click.prevent="scrollToSection('event')">Portofolio Event</a>
          <a href="#harga" class="hover:text-emerald-400" @click.prevent="scrollToSection('harga')">Paket Harga</a>
          <a href="#kontak" class="hover:text-emerald-400" @click.prevent="scrollToSection('kontak')">Kontak</a>
        </nav>

        <!-- Action buttons -->
        <div class="hidden md:flex items-center gap-2">
          <button
            class="px-3 py-1.5 text-xs border border-slate-600 rounded-full hover:border-emerald-400 hover:text-emerald-300 transition"
            @click="scrollToSection('kontak')"
          >
            Jadwalkan Demo
          </button>
          <button
            class="px-3.5 py-1.5 text-xs rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold"
            @click="goToLogin"
          >
            Masuk Portal Event
          </button>
        </div>

        <!-- Mobile: tombol hamburger -->
        <button
          class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-slate-200 hover:text-emerald-300 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
          type="button"
          @click="toggleMobileNav"
          :aria-expanded="mobileNavOpen ? 'true' : 'false'"
        >
          <span class="sr-only">Buka menu</span>
          <svg
            v-if="!mobileNavOpen"
            class="h-5 w-5"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg
            v-else
            class="h-5 w-5"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Mobile menu dropdown -->
      <div
        v-if="mobileNavOpen"
        class="md:hidden border-t border-slate-800 bg-slate-950/95"
      >
        <div class="max-w-6xl mx-auto px-4 py-3 space-y-2 text-sm">
          <a
            href="#fitur"
            class="block py-1 text-slate-200 hover:text-emerald-400"
            @click.prevent="scrollToAndClose('fitur')"
          >
            Fitur
          </a>
          <a
            href="#event"
            class="block py-1 text-slate-200 hover:text-emerald-400"
            @click.prevent="scrollToAndClose('event')"
          >
            Portofolio Event
          </a>
          <a
            href="#harga"
            class="block py-1 text-slate-200 hover:text-emerald-400"
            @click.prevent="scrollToAndClose('harga')"
          >
            Paket Harga
          </a>
          <a
            href="#kontak"
            class="block py-1 text-slate-200 hover:text-emerald-400"
            @click.prevent="scrollToAndClose('kontak')"
          >
            Kontak
          </a>

          <div class="pt-2 flex flex-col gap-2">
            <button
              class="w-full px-3 py-1.5 text-xs border border-slate-600 rounded-full hover:border-emerald-400 hover:text-emerald-300 transition"
              @click="scrollToAndClose('kontak')"
            >
              Jadwalkan Demo
            </button>
            <button
              class="w-full px-3.5 py-1.5 text-xs rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold"
              @click="goToLogin"
            >
              Masuk Portal Event
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- HERO SECTION -->
    <section class="bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">
      <div class="max-w-6xl mx-auto px-4 py-10 sm:py-12 md:py-16 grid md:grid-cols-2 gap-8 md:gap-10 items-center">
        <!-- Kiri -->
        <div>
          <p class="text-[11px] uppercase tracking-[0.25em] text-emerald-400 mb-2">
            Solusi Digital untuk Panitia MTQ
          </p>
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold leading-tight mb-4">
            Kelola <span class="text-emerald-400">Pendaftaran, Jadwal, dan Penilaian</span> MTQ
            dalam Satu Platform Terintegrasi.
          </h1>
          <p class="text-sm text-slate-200 mb-4">
            e-MTQ Platform membantu Kementerian Agama, LPTQ, pemerintah daerah, dan lembaga
            penyelenggara MTQ untuk mengelola seluruh tahapan event secara profesional – mulai dari
            pendaftaran kafilah, penempatan cabang lomba, hingga rekapitulasi nilai dewan hakim.
          </p>
          <ul class="text-sm text-slate-200/90 space-y-1.5 mb-6">
            <li>• Pendaftaran online per cabang, golongan, dan kategori usia.</li>
            <li>• Dashboard panitia &amp; dewan hakim dengan akses terpisah.</li>
            <li>• Rekap nilai otomatis dan hasil yang mudah dipublikasikan.</li>
            <li>• Support multi-event (Kabupaten, Provinsi, Nasional) dalam satu sistem.</li>
          </ul>
          <div class="flex flex-wrap gap-3">
            <button
              class="px-4 py-2 text-xs md:text-sm rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold"
              @click="scrollToSection('event')"
            >
              Lihat Contoh Event MTQ
            </button>
            <button
              class="px-4 py-2 text-xs md:text-sm rounded-full border border-slate-500 hover:border-emerald-400 text-slate-100"
              @click="scrollToSection('fitur')"
            >
              Pelajari Fitur Lengkap
            </button>
          </div>
          <p class="mt-4 text-[11px] text-slate-400">
            Cocok untuk: Kemenag Kab/Kota, Pemprov, LPTQ, dan lembaga pendidikan yang mengadakan MTQ internal.
          </p>
        </div>

        <!-- Kanan: preview -->
        <div class="relative mt-4 md:mt-0">
          <div class="absolute -inset-3 bg-emerald-500/20 blur-3xl opacity-60"></div>
          <div class="relative bg-slate-900 border border-slate-700 rounded-2xl p-3 sm:p-4 shadow-xl shadow-slate-900/80">
            <div class="flex items-center justify-between mb-3">
              <p class="text-xs font-semibold text-slate-100">Preview Panel Panitia</p>
              <span
                class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/50 text-emerald-300"
              >
                Realtime Monitoring
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
              <div class="bg-slate-800/80 rounded-xl p-3 border border-slate-700">
                <p class="font-semibold mb-1 text-slate-50">Pendaftaran</p>
                <p class="text-slate-300">
                  Pantau jumlah peserta per cabang &amp; kafilah dalam satu tampilan.
                </p>
              </div>
              <div class="bg-slate-800/80 rounded-xl p-3 border border-slate-700">
                <p class="font-semibold mb-1 text-slate-50">Jadwal Lomba</p>
                <p class="text-slate-300">Atur jadwal per majelis dengan notifikasi otomatis.</p>
              </div>
              <div class="bg-slate-800/80 rounded-xl p-3 border border-slate-700">
                <p class="font-semibold mb-1 text-slate-50">Penilaian Hakim</p>
                <p class="text-slate-300">
                  Input nilai by device, rekap otomatis sesuai kaidah penjurian.
                </p>
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

    <!-- FITUR UTAMA -->
    <section id="fitur" class="bg-slate-950 border-t border-slate-800">
      <div class="max-w-6xl mx-auto px-4 py-8 md:py-10">
        <h2 class="text-lg sm:text-xl font-semibold mb-1">Fitur Utama e-MTQ Platform</h2>
        <p class="text-sm text-slate-300 mb-6">
          Dibangun khusus untuk karakteristik penyelenggaraan MTQ di Indonesia.
        </p>

        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <h3 class="font-semibold mb-2 text-emerald-300">Multi Event &amp; Multi Lembaga</h3>
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
            <h3 class="font-semibold mb-2 text-emerald-300">Laporan &amp; Dokumentasi</h3>
            <p class="text-slate-200 text-[13px]">
              Export laporan peserta, daftar pemenang, sertifikat digital, hingga dokumentasi
              nilai per majelis dalam format yang siap dilampirkan sebagai bukti kegiatan.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- PORTOFOLIO EVENT -->
    <section id="event" class="bg-slate-950 border-t border-slate-800">
      <div class="max-w-6xl mx-auto px-4 py-8 md:py-10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
          <div>
            <h2 class="text-lg sm:text-xl font-semibold">Event MTQ yang Menggunakan e-MTQ</h2>
            <p class="text-sm text-slate-300">
              Contoh event yang telah atau sedang dikonfigurasi melalui sistem kami.
              Klik salah satu untuk mencoba mode “Portal Event”.
            </p>
          </div>

          <!-- badge jumlah event -->
          <span
            v-if="events && events.length"
            class="inline-flex self-start sm:self-auto text-[11px] px-3 py-1 rounded-full bg-slate-900 border border-slate-700 text-slate-200"
          >
            {{ events.length }} event terdaftar
          </span>
        </div>

        <!-- state loading / error / empty -->
        <p
          v-if="loadingEvents"
          class="text-sm text-slate-300"
        >
          Sedang memuat data event...
        </p>

        <p
          v-else-if="eventsError"
          class="text-sm text-red-400"
        >
          {{ eventsError }}
        </p>

        <p
          v-else-if="!events || !events.length"
          class="text-sm text-slate-300"
        >
          Belum ada event aktif. Silakan hubungi kami untuk membuat event pertama Anda.
        </p>

        <!-- list event -->
        <div v-else class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <button
            v-for="event in events"
            :key="event.id || event.event_key"
            type="button"
            class="text-left bg-slate-900 border border-slate-800 hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-500/20 transition rounded-xl p-4 flex flex-col h-full"
            @click="selectEvent(event)"
          >
            <div class="flex items-start gap-3 mb-2">
              <!-- Logo / inisial -->
              <div class="flex-shrink-0">
                <img
                  v-if="event.logo_event"
                  :src="event.logo_event"
                  :alt="`Logo ${event.event_name}`"
                  class="w-9 h-9 rounded-lg object-contain bg-slate-950"
                >
                <div
                  v-else
                  class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/40 flex items-center justify-center text-[11px] font-semibold text-emerald-300"
                >
                  {{ getEventInitials(event.event_name) }}
                </div>
              </div>
              <div class="flex-1">
                <p class="text-[13px] font-semibold text-slate-50 leading-snug">
                  {{ event.event_name }}
                </p>
                <p class="text-[11px] text-slate-300">
                  {{ event.app_name }}
                </p>
              </div>
            </div>

            <div class="text-[11px] text-slate-300 space-y-1 mb-3">
              <p>
                Lokasi: <span class="font-medium">{{ event.event_location || '-' }}</span>
              </p>
              <p>
                Jadwal:
                <span class="font-medium">
                  {{ formatDate(event.start_date) }}
                  &ndash;
                  {{ formatDate(event.end_date) }}
                </span>
              </p>
              <p
                v-if="event.event_tagline"
                class="italic text-slate-300/90"
              >
                “{{ event.event_tagline }}”
              </p>
            </div>

            <div class="mt-auto flex items-center justify-between text-[11px] pt-2 border-t border-slate-800">
              <span class="text-slate-400">
                Klik untuk masuk portal event
              </span>
              <span class="inline-flex items-center gap-1 text-emerald-300">
                <span>Masuk</span>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-3 h-3"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M9 5.25 15.75 12 9 18.75"
                  />
                </svg>
              </span>
            </div>
          </button>
        </div>

        <p class="mt-4 text-[11px] text-slate-400">
          Catatan: Event di atas dapat dikonfigurasi ulang sesuai format MTQ di daerah atau lembaga Anda.
        </p>
      </div>
    </section>

    <!-- PAKET HARGA -->
    <section id="harga" class="bg-slate-950 border-t border-slate-800">
      <div class="max-w-6xl mx-auto px-4 py-8 md:py-10">
        <h2 class="text-lg sm:text-xl font-semibold mb-1">Paket Layanan e-MTQ</h2>
        <p class="text-sm text-slate-300 mb-6">
          Biaya menyesuaikan skala event dan kebutuhan kustomisasi.
        </p>

        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
            <p class="text-xs font-semibold text-emerald-300 uppercase mb-1">Basic</p>
            <p class="text-lg font-bold mb-2">Event Lokal</p>
            <p class="text-[13px] text-slate-200 mb-3">
              Cocok untuk MTQ tingkat kecamatan / internal lembaga.
            </p>
            <ul class="text-[12px] text-slate-200 space-y-1">
              <li>• Pendaftaran peserta online.</li>
              <li>• Panel panitia &amp; hakim dasar.</li>
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

    <!-- KONTAK -->
    <section id="kontak" class="bg-slate-950 border-t border-slate-800">
      <div class="max-w-6xl mx-auto px-4 py-8 md:py-10">
        <div
          class="bg-slate-900 border border-slate-700 rounded-2xl p-5 md:p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
        >
          <div>
            <h2 class="text-lg sm:text-xl font-semibold mb-1">Ingin e-MTQ Dipakai di Event Anda?</h2>
            <p class="text-sm text-slate-300">
              Hubungi kami untuk presentasi singkat via Zoom/Meeting dan demo langsung sistem e-MTQ.
            </p>
          </div>
          <div class="text-sm text-slate-200">
            <p>WhatsApp: <span class="font-semibold">+62-8xx-xxxx-xxxx</span></p>
            <p>Email: <span class="font-semibold">admin@emtq-platform.id</span></p>
            <button
              class="mt-2 w-full md:w-auto px-4 py-2 rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs"
              @click="scrollToSection('event')"
            >
              Coba Lihat Event Contoh
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="border-t border-slate-800 py-4 text-center text-[11px] text-slate-400">
      e-MTQ Platform &copy; {{ currentYear }} • Solusi Digital Manajemen Lomba MTQ.
    </footer>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthUserStore } from '../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

const props = defineProps({
  loginUrl: {
    type: String,
    default: '/login',
  },
})

const mobileNavOpen = ref(false)
const currentYear = computed(() => new Date().getFullYear())

// state event dari API
const events = ref([])
const loadingEvents = ref(false)
const eventsError = ref(null)

const toggleMobileNav = () => {
  mobileNavOpen.value = !mobileNavOpen.value
}

const scrollToSection = (id) => {
  const el = document.getElementById(id)
  if (!el) return
  el.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const scrollToAndClose = (id) => {
  mobileNavOpen.value = false
  scrollToSection(id)
}

const goToLogin = () => {
  window.location.href = props.loginUrl || '/login'
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const getEventInitials = (nama) => {
  if (!nama) return 'MTQ'
  return nama
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() || '')
    .join('')
}

// fungsi klik event - SIMPAN KE STORE PINIA
const selectEvent = (event) => {
  if (!event) return

  authUserStore.eventData = event
  authUserStore.selectedEventKey = event.event_key || ''

  window.location.href = props.loginUrl || '/login'
}

// ======================
// FETCH EVENT DARI API
// ======================
const fetchEvents = async () => {
  loadingEvents.value = true
  eventsError.value = null

  try {
    const response = await axios.get('/api/v1/public-events')
    // backend bisa return { data: [...] } atau langsung [...]
    events.value = Array.isArray(response.data)
      ? response.data
      : (response.data.data || [])
  } catch (error) {
    console.error('Gagal memuat event:', error)
    eventsError.value = 'Gagal memuat data event. Silakan coba beberapa saat lagi.'
  } finally {
    loadingEvents.value = false
  }
}

onMounted(() => {
  fetchEvents()
})
</script>
