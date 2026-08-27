<template>
  <div class="mtq-theme min-h-screen bg-[var(--bg)] text-[var(--ink)]">
    <!-- ORNAMENT BORDER (signature motif, used once, quietly) -->
    <div class="ornament-strip" aria-hidden="true"></div>

    <!-- NAVBAR -->
    <header class="sticky top-0 z-30 bg-[var(--primary)] text-[var(--cream)] shadow-sm">
      <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
        <!-- Brand -->
        <div class="flex items-center gap-2.5">
          <span class="star8" aria-hidden="true"></span>
          <div class="leading-tight">
            <p class="font-display text-[15px] font-semibold tracking-wide">e-MTQ</p>
            <p class="text-[10px] text-[var(--cream-dim)] -mt-0.5">Sistem Informasi MTQ</p>
          </div>
        </div>

        <!-- Menu desktop -->
        <nav class="hidden md:flex items-center gap-6 text-[12.5px] font-medium">
          <a href="#pilih-event" class="hover:text-[var(--gold)] transition-colors" @click.prevent="scrollToSection('pilih-event')">Pilih MTQ</a>
          <a href="#fitur" class="hover:text-[var(--gold)] transition-colors" @click.prevent="scrollToSection('fitur')">Fitur</a>
          <a href="#layanan" class="hover:text-[var(--gold)] transition-colors" @click.prevent="scrollToSection('layanan')">Layanan</a>
          <a href="#kontak" class="hover:text-[var(--gold)] transition-colors" @click.prevent="scrollToSection('kontak')">Kontak</a>
        </nav>

        <!-- Action -->
        <button
          class="hidden md:inline-flex items-center gap-1.5 px-3.5 py-1.5 text-[12.5px] rounded-md bg-[var(--gold)] text-[#2a2106] font-semibold hover:bg-[var(--gold-light)] transition-colors focus-ring"
          @click="goToLogin"
        >
          Masuk Portal
        </button>

        <!-- Mobile toggle -->
        <button
          class="md:hidden p-2 -mr-2 rounded-md hover:bg-white/10 focus-ring"
          :aria-expanded="mobileNavOpen"
          aria-label="Buka menu"
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
      <div v-if="mobileNavOpen" class="md:hidden border-t border-white/10 bg-[var(--primary-dark)]">
        <div class="px-4 py-3 space-y-3 text-[13px]">
          <a class="block" @click="scrollToAndClose('pilih-event')">Pilih MTQ</a>
          <a class="block" @click="scrollToAndClose('fitur')">Fitur</a>
          <a class="block" @click="scrollToAndClose('layanan')">Layanan</a>
          <a class="block" @click="scrollToAndClose('kontak')">Kontak</a>
          <button
            class="w-full mt-1 px-4 py-2.5 rounded-md bg-[var(--gold)] text-[#2a2106] text-[13px] font-semibold focus-ring"
            @click="goToLogin"
          >
            Masuk Portal
          </button>
        </div>
      </div>
    </header>

    <!-- HERO -->
    <section class="bg-gradient-to-b from-[var(--primary)] to-[var(--primary-dark)] text-[var(--cream)]">
      <div class="max-w-6xl mx-auto px-4 pt-9 pb-10 md:py-14 grid md:grid-cols-5 gap-8 items-center">
        <div class="md:col-span-3">
          <p class="eyebrow mb-2.5">Sistem Resmi &middot; Kementerian Agama &amp; LPTQ</p>
          <h1 class="font-display text-[28px] md:text-[38px] leading-[1.15] font-semibold mb-3.5">
            Kelola Musabaqah Tilawatil Qur'an,
            <span class="text-[var(--gold)]">dari Daftar sampai Hasil</span>
          </h1>
          <p class="text-[13.5px] md:text-sm text-[var(--cream-dim)] mb-5 max-w-md">
            e-MTQ merangkas pendaftaran peserta, penjadwalan cabang &amp; golongan,
            penilaian dewan hakim, hingga rekapitulasi hasil dalam satu sistem yang tertib dan terdokumentasi.
          </p>
          <ul class="space-y-1.5 mb-6 text-[13px] text-[var(--cream-dim)]">
            <li class="flex gap-2"><span class="star8 sm" aria-hidden="true"></span>Data peserta, cabang, dan golongan lomba terpusat</li>
            <li class="flex gap-2"><span class="star8 sm" aria-hidden="true"></span>Akses terpisah untuk panitia, dewan hakim, dan admin</li>
            <li class="flex gap-2"><span class="star8 sm" aria-hidden="true"></span>Rekap nilai &amp; penetapan hasil otomatis</li>
          </ul>
          <div class="flex flex-wrap gap-2.5">
            <button
              class="px-4 py-2.5 rounded-md bg-[var(--gold)] text-[#2a2106] text-[13px] font-semibold hover:bg-[var(--gold-light)] transition-colors focus-ring"
              @click="scrollToSection('pilih-event')"
            >
              Pilih Penyelenggaraan MTQ →
            </button>
            <button
              class="px-4 py-2.5 rounded-md border border-white/25 text-[13px] hover:bg-white/5 transition-colors focus-ring"
              @click="scrollToSection('fitur')"
            >
              Lihat Fitur Sistem
            </button>
          </div>
        </div>

        <!-- Preview card -->
        <div class="md:col-span-2 relative">
          <div class="corner-ornament" aria-hidden="true"></div>
          <div class="bg-[var(--cream)]/[0.06] border border-white/15 rounded-xl p-4 backdrop-blur-sm">
            <p class="eyebrow mb-3 text-[var(--gold)]">Modul Sistem</p>
            <div class="grid grid-cols-3 gap-2 text-[10.5px] font-medium">
              <div class="bg-white/10 rounded-lg p-2.5 text-center">Pendaftaran</div>
              <div class="bg-white/10 rounded-lg p-2.5 text-center">Penjadwalan</div>
              <div class="bg-white/10 rounded-lg p-2.5 text-center">Penilaian</div>
            </div>
            <p class="mt-3.5 text-[11.5px] text-[var(--cream-dim)] leading-relaxed">
              Satu sistem untuk akurasi data, transparansi penilaian, dan dokumentasi kegiatan MTQ.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- PILIH EVENT -->
    <section id="pilih-event" class="border-t border-[var(--border)]">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="flex flex-col gap-1 mb-5">
          <p class="eyebrow text-[var(--primary)]">Langkah 1 dari 1</p>
          <h2 class="font-display text-xl md:text-2xl font-semibold">Pilih Penyelenggaraan MTQ untuk Masuk</h2>
          <p class="text-[13px] text-[var(--ink-dim)]">
            Ketuk salah satu MTQ di bawah untuk melanjutkan ke halaman login.
          </p>
        </div>

        <!-- Search -->
        <div class="flex items-center gap-2 mb-5 bg-white border border-[var(--border)] rounded-lg px-3 py-2 max-w-md focus-within:border-[var(--primary)] transition-colors">
          <svg class="w-4 h-4 text-[var(--ink-dim)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M17 10.5A6.5 6.5 0 1 1 4 10.5a6.5 6.5 0 0 1 13 0Z" />
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama atau lokasi MTQ..."
            class="w-full text-[13px] bg-transparent outline-none placeholder:text-[var(--ink-dim)]"
          />
        </div>

        <!-- Loading -->
        <div v-if="loadingEvents" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3.5" aria-live="polite">
          <div v-for="n in 3" :key="n" class="rounded-xl border border-[var(--border)] bg-white p-4 h-[104px] animate-pulse">
            <div class="h-3 w-2/3 bg-[var(--border)] rounded mb-3"></div>
            <div class="h-2.5 w-1/2 bg-[var(--border)] rounded mb-2"></div>
            <div class="h-2.5 w-1/3 bg-[var(--border)] rounded"></div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredEvents.length === 0" class="rounded-xl border border-dashed border-[var(--border)] bg-white p-8 text-center">
          <p class="text-sm font-medium mb-1">
            {{ events.length === 0 ? 'Belum ada penyelenggaraan MTQ yang tercatat' : 'Tidak ditemukan hasil untuk pencarian ini' }}
          </p>
          <p class="text-[12.5px] text-[var(--ink-dim)] mb-4">
            Anda tetap dapat masuk ke portal secara langsung.
          </p>
          <button
            class="px-4 py-2 rounded-md bg-[var(--primary)] text-white text-[13px] font-semibold hover:bg-[var(--primary-dark)] transition-colors focus-ring"
            @click="goToLogin"
          >
            Masuk Portal
          </button>
        </div>

        <!-- Event grid -->
        <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
          <button
            v-for="event in filteredEvents"
            :key="event.id"
            class="group text-left rounded-xl border border-[var(--border)] bg-white p-4 hover:border-[var(--primary)] hover:shadow-md transition-all focus-ring"
            @click="selectEvent(event)"
          >
            <div class="flex items-start justify-between gap-2 mb-2">
              <span v-if="event.level" class="badge">{{ event.level }}</span>
              <span v-else class="badge">MTQ</span>
              <svg class="w-4 h-4 text-[var(--ink-dim)] group-hover:text-[var(--primary)] group-hover:translate-x-0.5 transition-all shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
              </svg>
            </div>
            <p class="font-display text-[15px] font-semibold leading-snug mb-1 group-hover:text-[var(--primary)] transition-colors">
              {{ event.event_name }}
            </p>
            <p class="text-[12px] text-[var(--ink-dim)] flex items-center gap-1 mb-2.5">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z" />
                <circle cx="12" cy="9.5" r="2.2" stroke-width="1.5" />
              </svg>
              {{ event.event_location || 'Lokasi belum ditentukan' }}
            </p>
            <p class="pill-cta">Pilih &amp; masuk ke portal</p>
          </button>
        </div>
      </div>
    </section>

    <!-- FITUR -->
    <section id="fitur" class="border-t border-[var(--border)] bg-[var(--bg-alt)]">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <h2 class="font-display text-lg md:text-xl font-semibold mb-1">Ruang Lingkup Sistem</h2>
        <p class="text-[13px] text-[var(--ink-dim)] mb-6">Fitur inti untuk penyelenggaraan MTQ yang tertib dan terdokumentasi.</p>

        <div class="grid md:grid-cols-3 gap-3.5 text-sm">
          <div class="feature-card">
            <span class="star8" aria-hidden="true"></span>
            <h3 class="font-display font-semibold mt-2.5 mb-1">Multi Event</h3>
            <p class="text-[12.5px] text-[var(--ink-dim)]">Mendukung MTQ tingkat kecamatan, kabupaten/kota, provinsi, hingga nasional.</p>
          </div>
          <div class="feature-card">
            <span class="star8" aria-hidden="true"></span>
            <h3 class="font-display font-semibold mt-2.5 mb-1">Penilaian Dewan Hakim</h3>
            <p class="text-[12.5px] text-[var(--ink-dim)]">Input nilai terstruktur sesuai kaidah penjurian dan ketentuan lomba.</p>
          </div>
          <div class="feature-card">
            <span class="star8" aria-hidden="true"></span>
            <h3 class="font-display font-semibold mt-2.5 mb-1">Pelaporan &amp; Arsip</h3>
            <p class="text-[12.5px] text-[var(--ink-dim)]">Data peserta, hasil lomba, dan dokumentasi kegiatan tersimpan sistematis.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- LAYANAN -->
    <section id="layanan" class="border-t border-[var(--border)]">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <h2 class="font-display text-lg md:text-xl font-semibold mb-1">Skema Layanan</h2>
        <p class="text-[13px] text-[var(--ink-dim)] mb-6">Disesuaikan dengan skala penyelenggaraan MTQ Anda.</p>

        <div class="grid md:grid-cols-3 gap-3.5 text-sm">
          <div class="scale-card">
            <p class="eyebrow text-[var(--primary)] mb-1">Skala Lokal</p>
            <p class="text-[12.5px] text-[var(--ink-dim)]">MTQ tingkat kecamatan atau internal lembaga.</p>
          </div>
          <div class="scale-card">
            <p class="eyebrow text-[var(--primary)] mb-1">Kabupaten / Kota</p>
            <p class="text-[12.5px] text-[var(--ink-dim)]">MTQ resmi dengan pengelolaan kafilah dan majelis.</p>
          </div>
          <div class="scale-card">
            <p class="eyebrow text-[var(--primary)] mb-1">Provinsi / Nasional</p>
            <p class="text-[12.5px] text-[var(--ink-dim)]">Event besar dengan koordinasi dan dukungan intensif.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- KONTAK -->
    <section id="kontak" class="border-t border-[var(--border)] bg-[var(--bg-alt)]">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="rounded-xl bg-[var(--primary)] text-[var(--cream)] p-6 md:p-7 relative overflow-hidden">
          <div class="corner-ornament light" aria-hidden="true"></div>
          <h2 class="font-display text-lg md:text-xl font-semibold mb-1.5">Informasi &amp; Koordinasi</h2>
          <p class="text-[13px] text-[var(--cream-dim)] mb-4 max-w-md">
            Untuk koordinasi, presentasi sistem, atau penggunaan aplikasi e-MTQ, silakan hubungi kami.
          </p>
          <div class="flex flex-col sm:flex-row gap-x-6 gap-y-1 text-[13px]">
            <p>Email: <strong class="text-[var(--gold)]">admin@emtq-platform.id</strong></p>
            <p>WhatsApp: <strong class="text-[var(--gold)]">+62-8xx-xxxx-xxxx</strong></p>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="border-t border-[var(--border)] py-4 text-center text-[11px] text-[var(--ink-dim)]">
      © {{ currentYear }} Sistem Informasi e-MTQ &middot; Mendukung Penyelenggaraan MTQ.
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
const searchQuery = ref('')

const filteredEvents = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return events.value
  return events.value.filter((e) => {
    const name = (e.event_name || '').toLowerCase()
    const loc = (e.event_location || '').toLowerCase()
    return name.includes(q) || loc.includes(q)
  })
})

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

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap');

.mtq-theme {
  --bg: #FAF6EC;
  --bg-alt: #F2EBD8;
  --ink: #1C2420;
  --ink-dim: #5B6259;
  --border: #E4DAC0;
  --primary: #0B5D3B;
  --primary-dark: #073B26;
  --cream: #FBF6E9;
  --cream-dim: #CFE2D4;
  --gold: #C6A15B;
  --gold-light: #D7B978;
  font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
}

.font-display {
  font-family: 'Amiri', 'Plus Jakarta Sans', serif;
}

.eyebrow {
  font-family: 'IBM Plex Mono', ui-monospace, monospace;
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-weight: 500;
}

.badge {
  font-family: 'IBM Plex Mono', ui-monospace, monospace;
  font-size: 10px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  font-weight: 600;
  color: var(--primary-dark);
  background: var(--bg-alt);
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 2px 8px;
}

.pill-cta {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--primary);
}

.feature-card,
.scale-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px;
}

.focus-ring:focus-visible {
  outline: 2px solid var(--gold);
  outline-offset: 2px;
}

/* Eight-pointed star — signature motif, used sparingly */
.star8 {
  display: inline-block;
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  margin-top: 1px;
  background-color: var(--gold);
  clip-path: polygon(
    50% 0%, 61% 15%, 79% 6%, 79% 26%, 100% 30%, 87% 45%,
    100% 60%, 79% 65%, 79% 85%, 61% 76%, 50% 100%, 39% 76%,
    21% 85%, 21% 65%, 0% 60%, 13% 45%, 0% 30%, 21% 26%, 21% 6%, 39% 15%
  );
}
.star8.sm {
  width: 11px;
  height: 11px;
  margin-top: 3px;
}

.ornament-strip {
  height: 4px;
  width: 100%;
  background: repeating-linear-gradient(
    90deg,
    var(--gold) 0px,
    var(--gold) 6px,
    var(--primary) 6px,
    var(--primary) 12px
  );
}

.corner-ornament {
  position: absolute;
  top: -1px;
  right: -1px;
  width: 40px;
  height: 40px;
  border-top: 2px solid var(--gold);
  border-right: 2px solid var(--gold);
  border-top-right-radius: 12px;
  opacity: 0.8;
}
.corner-ornament.light {
  border-color: var(--gold);
}

@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}
</style>