<template>
  <div class="bg-slate-950 text-slate-100 min-h-screen">
    <!-- NAVBAR -->
    <header class="sticky top-0 z-20 bg-slate-950/95 border-b border-slate-800 backdrop-blur">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <!-- Brand -->
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-md bg-emerald-600 flex items-center justify-center text-sm font-bold text-white">
            MTQ
          </div>
          <div>
            <p class="text-sm font-semibold leading-tight">
              Sistem Informasi e-MTQ
            </p>
            <p class="text-[11px] text-slate-300">
              Platform Resmi Manajemen Musabaqah Tilawatil Qur’an
            </p>
          </div>
        </div>

        <!-- Menu desktop -->
        <nav class="hidden md:flex items-center gap-6 text-xs text-slate-200">
          <a href="#fitur" class="hover:text-emerald-400" @click.prevent="scrollToSection('fitur')">Fitur Sistem</a>
          <a href="#event" class="hover:text-emerald-400" @click.prevent="scrollToSection('event')">Implementasi</a>
          <a href="#layanan" class="hover:text-emerald-400" @click.prevent="scrollToSection('layanan')">Layanan</a>
          <a href="#kontak" class="hover:text-emerald-400" @click.prevent="scrollToSection('kontak')">Kontak</a>
        </nav>

        <!-- Action -->
        <div class="hidden md:flex">
          <button
            class="px-4 py-1.5 text-xs rounded-md bg-emerald-600 hover:bg-emerald-500 text-white font-semibold"
            @click="goToLogin"
          >
            Akses Portal Resmi
          </button>
        </div>

        <!-- Mobile -->
        <button
          class="md:hidden p-2 rounded-md text-slate-200 hover:bg-slate-800"
          @click="mobileNavOpen = !mobileNavOpen"
        >
          <svg v-if="!mobileNavOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Mobile menu -->
      <div v-if="mobileNavOpen" class="md:hidden border-t border-slate-800 bg-slate-950">
        <div class="px-4 py-3 space-y-2 text-sm">
          <a class="block" @click="scrollToAndClose('fitur')">Fitur Sistem</a>
          <a class="block" @click="scrollToAndClose('event')">Implementasi</a>
          <a class="block" @click="scrollToAndClose('layanan')">Layanan</a>
          <a class="block" @click="scrollToAndClose('kontak')">Kontak</a>
          <button
            class="w-full mt-2 px-4 py-2 rounded-md bg-emerald-600 text-white text-xs font-semibold"
            @click="goToLogin"
          >
            Akses Portal Resmi
          </button>
        </div>
      </div>
    </header>

    <!-- HERO -->
    <section class="bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">
      <div class="max-w-6xl mx-auto px-4 py-12 md:py-16 grid md:grid-cols-2 gap-10 items-center">
        <div>
          <p class="text-[11px] uppercase tracking-widest text-emerald-400 mb-2">
            Sistem Informasi Resmi
          </p>
          <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-4">
            Sistem Informasi Terpadu
            <span class="text-emerald-400">Penyelenggaraan MTQ</span>
          </h1>
          <p class="text-sm text-slate-200 mb-4">
            e-MTQ merupakan sistem informasi yang dirancang untuk mendukung
            penyelenggaraan Musabaqah Tilawatil Qur’an secara tertib,
            terstruktur, dan terdokumentasi di lingkungan Kementerian Agama,
            LPTQ, dan Pemerintah Daerah.
          </p>
          <ul class="text-sm text-slate-200 space-y-1.5 mb-6">
            <li>• Pengelolaan data peserta, cabang, dan golongan lomba.</li>
            <li>• Pemisahan akses panitia, dewan hakim, dan administrator.</li>
            <li>• Rekapitulasi nilai dan penetapan hasil lomba.</li>
            <li>• Dukungan pelaksanaan MTQ multi-level.</li>
          </ul>
          <div class="flex gap-3">
            <button
              class="px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold"
              @click="scrollToSection('event')"
            >
              Lihat Implementasi Sistem
            </button>
            <button
              class="px-4 py-2 rounded-md border border-slate-600 text-sm"
              @click="scrollToSection('fitur')"
            >
              Ruang Lingkup Sistem
            </button>
          </div>
        </div>

        <!-- Preview -->
        <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
          <p class="text-xs font-semibold mb-3">Ringkasan Modul Sistem</p>
          <div class="grid grid-cols-3 gap-2 text-[11px] text-slate-200">
            <div class="bg-slate-800 rounded-lg p-2">Pendaftaran</div>
            <div class="bg-slate-800 rounded-lg p-2">Penjadwalan</div>
            <div class="bg-slate-800 rounded-lg p-2">Penilaian</div>
          </div>
          <p class="mt-4 text-[11px] text-slate-300">
            Sistem membantu meningkatkan akurasi data, transparansi penilaian,
            serta dokumentasi kegiatan MTQ.
          </p>
        </div>
      </div>
    </section>

    <!-- FITUR -->
    <section id="fitur" class="border-t border-slate-800">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <h2 class="text-xl font-semibold mb-2">Ruang Lingkup dan Fitur Sistem</h2>
        <p class="text-sm text-slate-300 mb-6">
          Fitur dirancang sesuai kebutuhan penyelenggaraan MTQ di lingkungan instansi resmi.
        </p>

        <div class="grid md:grid-cols-3 gap-4 text-sm">
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <h3 class="font-semibold text-emerald-300 mb-2">Pengelolaan Multi Event</h3>
            <p class="text-slate-200 text-[13px]">
              Mendukung pelaksanaan MTQ tingkat kabupaten/kota, provinsi, hingga nasional.
            </p>
          </div>
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <h3 class="font-semibold text-emerald-300 mb-2">Sistem Penilaian Hakim</h3>
            <p class="text-slate-200 text-[13px]">
              Input nilai terstruktur sesuai kaidah penjurian dan ketentuan lomba.
            </p>
          </div>
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <h3 class="font-semibold text-emerald-300 mb-2">Pelaporan dan Arsip</h3>
            <p class="text-slate-200 text-[13px]">
              Laporan peserta, hasil lomba, dan dokumentasi kegiatan tersimpan sistematis.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- EVENT -->
    <section id="event" class="border-t border-slate-800">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <h2 class="text-xl font-semibold mb-2">
          Implementasi Sistem e-MTQ
        </h2>
        <p class="text-sm text-slate-300 mb-6">
          Contoh pelaksanaan MTQ yang telah menggunakan sistem e-MTQ.
        </p>

        <p v-if="loadingEvents" class="text-sm text-slate-300">
          Memuat data implementasi sistem...
        </p>

        <div v-else class="grid md:grid-cols-3 gap-4 text-sm">
          <button
            v-for="event in events"
            :key="event.id"
            class="bg-slate-900 border border-slate-800 rounded-xl p-4 text-left hover:border-emerald-400"
            @click="selectEvent(event)"
          >
            <p class="font-semibold text-slate-50">{{ event.event_name }}</p>
            <p class="text-[11px] text-slate-300">{{ event.event_location || '-' }}</p>
          </button>
        </div>
      </div>
    </section>

    <!-- LAYANAN -->
    <section id="layanan" class="border-t border-slate-800">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <h2 class="text-xl font-semibold mb-2">Skema Layanan Sistem</h2>
        <p class="text-sm text-slate-300 mb-6">
          Skema layanan disesuaikan dengan skala dan kebutuhan penyelenggaraan MTQ.
        </p>

        <div class="grid md:grid-cols-3 gap-4 text-sm">
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="font-semibold mb-1">Skala Lokal</p>
            <p class="text-[13px] text-slate-200">Pelaksanaan MTQ tingkat kecamatan atau internal lembaga.</p>
          </div>
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="font-semibold mb-1">Skala Kabupaten/Kota</p>
            <p class="text-[13px] text-slate-200">MTQ resmi dengan pengelolaan kafilah dan majelis.</p>
          </div>
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="font-semibold mb-1">Skala Provinsi/Nasional</p>
            <p class="text-[13px] text-slate-200">Event besar dengan kebutuhan koordinasi dan dukungan intensif.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- KONTAK -->
    <section id="kontak" class="border-t border-slate-800">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="bg-slate-900 border border-slate-700 rounded-xl p-6">
          <h2 class="text-xl font-semibold mb-2">Informasi dan Koordinasi</h2>
          <p class="text-sm text-slate-300 mb-4">
            Untuk keperluan koordinasi, presentasi sistem, atau penggunaan aplikasi e-MTQ,
            silakan menghubungi kontak berikut.
          </p>
          <p class="text-sm">Email: <strong>admin@emtq-platform.id</strong></p>
          <p class="text-sm">WhatsApp: <strong>+62-8xx-xxxx-xxxx</strong></p>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="border-t border-slate-800 py-4 text-center text-[11px] text-slate-400">
      © {{ currentYear }} Sistem Informasi e-MTQ • Mendukung Penyelenggaraan MTQ.
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthUserStore } from '../stores/AuthUserStore'

const authUserStore = useAuthUserStore()
const mobileNavOpen = ref(false)
const currentYear = computed(() => new Date().getFullYear())

const events = ref([])
const loadingEvents = ref(false)

const scrollToSection = (id) => {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

const scrollToAndClose = (id) => {
  mobileNavOpen.value = false
  scrollToSection(id)
}

const goToLogin = () => {
  window.location.href = '/login'
}

const selectEvent = (event) => {
  authUserStore.eventData = event
  authUserStore.selectedEventKey = event.event_key || ''
  goToLogin()
}

const fetchEvents = async () => {
  loadingEvents.value = true
  try {
    const res = await axios.get('/api/v1/public-events')
    events.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } finally {
    loadingEvents.value = false
  }
}

onMounted(fetchEvents)
</script>
