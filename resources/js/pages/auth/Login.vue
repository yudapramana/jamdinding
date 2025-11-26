<script setup>
import axios from 'axios'
import { reactive, ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import { useMasterDataStore } from '../../stores/MasterDataStore'
import { TurnstileWidget } from '@delaneydev/laravel-turnstile-vue'

// Stores
const authUserStore = useAuthUserStore()
const masterDataStore = useMasterDataStore()
const router = useRouter()

// -----------------------------
//  EVENT KEY & DETAIL EVENT
// -----------------------------
const loadingEvent = ref(true)

// Ambil event_data dari localStorage / cookie
const getEventDataFromStorage = () => {
  loadingEvent.value = true
  let raw = ''

  // 1. Coba localStorage
  try {
    raw = localStorage.getItem('event_data') || ''
  } catch (e) {}

  // 2. Jika kosong → coba cookie
  if (!raw) {
    const cookie = document.cookie
      .split('; ')
      .find(row => row.startsWith('event_data='))
    if (cookie) {
      raw = decodeURIComponent(cookie.split('=')[1])
    }
  } 
  
  if (raw) {
    try {
      authUserStore.eventData = JSON.parse(raw)
    } catch (e) {
      console.error('Gagal parse event_data dari storage/cookie:', e)
      authUserStore.eventData = null
    }
  } else {
    authUserStore.eventData = null
  }

  loadingEvent.value = false
}

// Ambil event_key dari localStorage / cookie / eventData
const getEventKeyFromStorage = () => {
  let key = ''

  // 1. Coba localStorage
  try {
    key = localStorage.getItem('event_key') || ''
  } catch (e) {}

  // 2. Jika kosong → coba cookie
  if (!key) {
    const cookie = document.cookie
      .split('; ')
      .find(row => row.startsWith('event_key='))
    if (cookie) {
      key = decodeURIComponent(cookie.split('=')[1])
    }
  }

  // 3. Jika masih kosong tapi sudah punya eventData → ambil dari sana
  if (!key && authUserStore.eventData.value?.event_key) {
    key = authUserStore.eventData.value.event_key
  }

  authUserStore.selectedEventKey = key
  
}

// Poster kiri (bisa diganti file Anda sendiri)
const posterSrc = computed(() => {
  // kalau backend/landing sudah simpan poster_url di event_data
  if (authUserStore.eventData?.poster_url) return authUserStore.eventData.poster_url

  // kalau logo_event ingin dipakai sebagai poster
  if (authUserStore.eventData?.logo_event) return authUserStore.eventData.logo_event

  // fallback poster statis
  return 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1763442267/PandanViewMandeh/uaccfb20uvhpvjyafdoj.png'
})

// Logo header kanan (misalnya logo_event)
const headerLogoSrc = computed(() => {
  if (authUserStore.eventData?.logo_event) return authUserStore.eventData.logo_event
  return null
})

const headerLogoSponsor1 = computed(() => {
  if (authUserStore.eventData?.logo_sponsor_1) return authUserStore.eventData.logo_sponsor_1
  return null
})

const headerLogoSponsor2 = computed(() => {
  if (authUserStore.eventData?.logo_sponsor_2) return authUserStore.eventData.logo_sponsor_2
  return null
})

const headerLogoSponsor3 = computed(() => {
  if (authUserStore.eventData?.logo_sponsor_3) return authUserStore.eventData.logo_sponsor_3
  return null
})

onMounted(() => {
  // urutan: ambil event_data dulu, lalu event_key
  getEventDataFromStorage()
  getEventKeyFromStorage()

  window.addEventListener('pageshow', onPageShow)
})

onBeforeUnmount(() => {
  window.removeEventListener('pageshow', onPageShow)
})

// -----------------------------
// FORM LOGIN
// -----------------------------
const form = reactive({
  username: '',
  password: '',
  remember: true,
})

// other states
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const captchaToken = ref('')

// Captcha Site Key
// const siteKey = '0x4AAAAAABg5q-5b8cUPKPGt'

// -----------------------------
// Turnstile Helpers
// -----------------------------
// const resetTurnstile = async () => {
//   captchaToken.value = ''
//   await nextTick()
//   try {
//     if (window?.turnstile) window.turnstile.reset()
//   } catch {}
// }

const onPageShow = (e) => {
  // if (e.persisted) resetTurnstile()
}

// -----------------------------
// HANDLE SUBMIT LOGIN
// -----------------------------
const handleSubmit = async () => {
  // if (!captchaToken.value) {
  //   errorMessage.value = 'Mohon selesaikan verifikasi captcha.'
  //   return
  // }

  if (!authUserStore.selectedEventKey) {
    errorMessage.value = 'Event belum dipilih. Silakan kembali ke halaman landing.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    await axios.post('/login', {
      ...form,
      event_key: authUserStore.selectedEventKey,
      'cf-turnstile-response': captchaToken.value,
    })

    await authUserStore.getAuthUser()
    await masterDataStore.getDoctypeList()
    await authUserStore.getMyDocuments()

    authUserStore.eventKey = authUserStore.selectedEventKey
    authUserStore.selectedEventKey = authUserStore.selectedEventKey

    authUserStore.isAuthenticated = true
    authUserStore.activeLayout = 'admin'

    router.push('/admin/dashboard')
  } catch (error) {
    console.error('Login error:', error)
    let msg = error.response?.data?.message || 'Terjadi kesalahan saat login.'

    if (error.response?.status === 422) {
      const errs = error.response?.data?.errors || {}
      if (errs['cf-turnstile-response']?.length) msg = errs['cf-turnstile-response'][0]
      else if (errs.username?.length) msg = errs.username[0]
      else if (errs.password?.length) msg = errs.password[0]
    }

    errorMessage.value = msg
  } finally {
    await resetTurnstile()
    setTimeout(() => (loading.value = false), 400)
  }
}
</script>


<template>
  <div class="mtq-login-page">
    <transition name="fade">
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
        <p>Memproses login...</p>
      </div>
    </transition>

    <div class="mtq-login-container">
      <div class="mtq-login-card">
        <!-- KIRI: POSTER -->
        <div class="mtq-login-left d-none d-lg-flex">
          <div class="mtq-login-poster-wrapper">
            <img :src="posterSrc" alt="Poster MTQ" class="mtq-login-poster" />
          </div>
        </div>

        <!-- KANAN: HEADER EVENT + FORM -->
        <div class="mtq-login-right">
          <!-- HEADER EVENT -->
          <div class="mtq-login-header text-center mb-4">
            <div class="mtq-login-logos mb-3">
              <!-- Logo utama dari event -->
              <img
                v-if="headerLogoSrc"
                :src="headerLogoSrc"
                alt="Logo Event"
                class="mtq-login-logo-event"
              />

              <img
                v-if="headerLogoSponsor1"
                :src="headerLogoSponsor1"
                alt="Logo Event"
                class="mtq-login-logo-small"
              />

              <img
                v-if="headerLogoSponsor2"
                :src="headerLogoSponsor2"
                alt="Logo Event"
                class="mtq-login-logo-small"
              />

              <img
                v-if="headerLogoSponsor3"
                :src="headerLogoSponsor3"
                alt="Logo Event"
                class="mtq-login-logo-small"
              />

              <!-- Contoh: logo instansi statis di sini jika mau -->
              <!--
              <img src="/images/logo-kemenag.png" class="mtq-login-logo-small" />
              <img src="/images/logo-provinsi.png" class="mtq-login-logo-small" />
              <img src="/images/logo-lptq.png" class="mtq-login-logo-small" />
              -->
            </div>

            <h1 class="mtq-login-title">
              {{ authUserStore.eventData?.nama_aplikasi || 'e-MTQ' }}
            </h1>
            <p class="mtq-login-subtitle">
              {{ authUserStore.eventData?.nama_event || 'Portal MTQ Digital' }}
            </p>
            <p class="mtq-login-tagline">
              {{ authUserStore.eventData?.tagline || 'Pendaftaran peserta MTQ' }}
            </p>
          </div>

          <div class="card mtq-login-form-card">
            <div class="card-body login-card-body">
              <p class="login-box-msg">Silakan masuk untuk memulai sesi</p>

              <transition name="fade">
                <div v-if="errorMessage" class="alert alert-danger" role="alert">
                  {{ errorMessage }}
                </div>
              </transition>

              <form @submit.prevent="handleSubmit" autocomplete="off">
                <div class="input-group mb-3">
                  <input
                    v-model="form.username"
                    type="text"
                    class="form-control"
                    placeholder="Username / NIP"
                    required
                    autocomplete="username"
                  />
                  <div class="input-group-append">
                    <div class="input-group-text mtq-input-icon">
                      <span class="fas fa-user"></span>
                    </div>
                  </div>
                </div>

                <div class="input-group mb-3">
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    class="form-control"
                    placeholder="Password"
                    required
                    autocomplete="current-password"
                  />
                  <div class="input-group-append">
                    <button
                      class="input-group-text mtq-input-icon"
                      type="button"
                      @click="showPassword = !showPassword"
                      tabindex="-1"
                    >
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                </div>

                <!-- <div class="text-center row justify-content-center">
                  <div class="form-group recaptcha-container mx-auto">
                    <TurnstileWidget
                      v-model="captchaToken"
                      :sitekey="siteKey"
                      theme="light"
                    />
                  </div>
                </div> -->

                <div class="row mb-3 align-items-center">
                  <div class="col-8">
                    <div class="icheck-primary">
                      <input type="checkbox" id="remember" v-model="form.remember" />
                      <label for="remember">&nbsp; Remember Me</label>
                    </div>
                  </div>
                  <div class="col-4 text-right">
                    <button
                      type="submit"
                      class="btn btn-success btn-block"
                      :disabled="loading"
                    >
                      <!-- :disabled="loading || !captchaToken" -->

                      <span v-if="loading">Memuat...</span>
                      <span v-else>MASUK</span>
                    </button>
                  </div>
                </div>
              </form>

              <div class="mtq-login-footer-links">
                <p class="mb-1">
                  Lupa Password?
                  <a href="/password/wa/request" target="_blank">Reset di sini</a>
                </p>
                <p class="mb-0">
                  <a
                    class="text-sm text-muted"
                    href="https://wa.me/6282298476941?text=Assalamualaikum Wr Wb Admin Sigarda,%2C%20Saya%20Ingin%20Bertanya%20Tentang%20Sigarda,%20NIP%20Saya%20adalah:"
                    target="_blank"
                  >
                    Tanya admin?
                  </a>
                </p>
              </div>
            </div>
          </div>

          <p v-if="!loadingEvent && !authUserStore.eventData" class="mtq-event-warning">
            Event tidak ditemukan atau belum dipilih. Silakan kembali ke halaman utama.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mtq-login-page {
  min-height: 100vh;
  background: #f4f5f7;
  background-image: radial-gradient(circle at top left, #ffffff 0, #f4f5f7 55%, #e9ecf0 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 12px;
}

.mtq-login-container {
  width: 100%;
  max-width: 1000px;
}

.mtq-login-card {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
  display: flex;
  overflow: hidden;
  min-height: 460px;
}

/* KIRI */
.mtq-login-left {
  flex: 0 0 40%;
  background: linear-gradient(135deg, #f7f9fb 0%, #ffffff 50%, #f2f4f7 100%);
  padding: 24px;
  align-items: center;
  justify-content: center;
}

.mtq-login-poster-wrapper {
  border-radius: 12px;
  background: #ffffff;
  padding: 12px;
  box-shadow: 0 12px 26px rgba(0, 0, 0, 0.18);
}

.mtq-login-poster {
  display: block;
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}

/* KANAN */
.mtq-login-right {
  flex: 1;
  padding: 24px 24px 28px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.mtq-login-header {
  margin-bottom: 16px;
}

.mtq-login-logos {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
}

.mtq-login-logo-event {
  max-height: 56px;
  width: auto;
}

/* Logo kecil (jika diperlukan) */
.mtq-login-logo-small {
  max-height: 42px;
  width: auto;
}

.mtq-login-title {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 4px;
  color: #2f3a4a;
}

.mtq-login-subtitle {
  font-size: 1.05rem;
  font-weight: 600;
  margin-bottom: 4px;
  color: #2f3a4a;
}

.mtq-login-tagline {
  font-size: 0.9rem;
  color: #6b7280;
  margin-bottom: 0;
}

.mtq-login-form-card {
  /* border-radius: 12px;
  border: 1px solid #e5e7eb; */
  border: 0px !important;
}

.card {
  box-shadow: none !important;
}

/* Input & button styling */
.mtq-input-icon {
  background-color: #198754;
  color: #fff;
  border-color: #198754;
}

.mtq-input-icon i {
  font-size: 0.9rem;
}

.btn-success {
  background-color: #198754;
  border-color: #198754;
}

.btn-success:hover {
  background-color: #157347;
  border-color: #146c43;
}

.mtq-login-footer-links {
  margin-top: 8px;
  font-size: 0.85rem;
}

.mtq-event-warning {
  margin-top: 8px;
  font-size: 0.8rem;
  color: #b91c1c;
}

/* LOADING OVERLAY & TRANSISI */
.loading-overlay {
  position: fixed;
  z-index: 999;
  inset: 0;
  background-color: rgba(255, 255, 255, 0.85);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 16px;
  color: #555;
  transition: opacity 0.3s;
}

.spinner {
  border: 4px solid #ccc;
  border-top: 4px solid #28a745;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 0.8s linear infinite;
  margin-bottom: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.fade-enter-active,
.fade-leave-active { transition: opacity 0.3s ease; }

.fade-enter-from,
.fade-leave-to { opacity: 0; }

/* RESPONSIVE */
@media (max-width: 991.98px) {
  .mtq-login-card {
    flex-direction: column;
  }

  .mtq-login-right {
    padding: 20px 18px 24px;
  }

  .mtq-login-title {
    font-size: 1.2rem;
  }
}
</style>
