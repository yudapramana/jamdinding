<script setup>
import axios from 'axios'
import { reactive, ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import { useMasterDataStore } from '../../stores/MasterDataStore'
import { TurnstileWidget } from '@delaneydev/laravel-turnstile-vue'

// Stores
const authUserStore = useAuthUserStore()
const masterDataStore = useMasterDataStore()
const router = useRouter()

// -----------------------------
//  EVENT KEY: Ambil dari localStorage / cookie
// -----------------------------
const selectedEventKey = ref('')

const getEventKeyFromStorage = () => {
  let key = ''

  // 1. Coba localStorage
  try {
    key = localStorage.getItem('event_key') || '';
    console.log('key localstorage: ');
    console.log(key);
  } catch (e) {}

  // 2. Jika kosong → coba cookie
  if (!key) {
    const cookie = document.cookie
      .split('; ')
      .find(row => row.startsWith('event_key='))
    if (cookie) {
      key = decodeURIComponent(cookie.split('=')[1])
    }
    console.log('key cookie: ');
    console.log(key);
  }

  selectedEventKey.value = key
}

onMounted(() => {
  getEventKeyFromStorage() // <-- Ambil event key saat halaman dibuka
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
const siteKey = '0x4AAAAAABg5q-5b8cUPKPGt'

// -----------------------------
// Turnstile Helpers
// -----------------------------
const resetTurnstile = async () => {
  captchaToken.value = ''
  await nextTick()
  try {
    if (window?.turnstile) window.turnstile.reset()
  } catch {}
}

const onPageShow = (e) => {
  if (e.persisted) resetTurnstile()
}

onMounted(() => {
  window.addEventListener('pageshow', onPageShow)
})

onBeforeUnmount(() => {
  window.removeEventListener('pageshow', onPageShow)
})

// -----------------------------
// HANDLE SUBMIT LOGIN
// -----------------------------
const handleSubmit = async () => {
  if (!captchaToken.value) {
    errorMessage.value = 'Mohon selesaikan verifikasi captcha.'
    return
  }

  if (!selectedEventKey.value) {
    errorMessage.value = 'Event belum dipilih. Silakan kembali ke halaman landing.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    // 🔥 Tambahkan event_key dalam request login
    await axios.post('/login', {
      ...form,
      event_key: selectedEventKey.value, // <--- penting
      'cf-turnstile-response': captchaToken.value,
    })

    // Muat data user
    await authUserStore.getAuthUser()
    await masterDataStore.getDoctypeList()
    await authUserStore.getMyDocuments()

    // Simpan event_key di store agar seluruh aplikasi bisa pakai
    authUserStore.eventKey = selectedEventKey.value
    masterDataStore.selectedEventKey = selectedEventKey.value

    authUserStore.isAuthenticated = true
    authUserStore.activeLayout = 'user'

    router.push('/user/dashboard')
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
  <div class="login-page">
    <transition name="fade">
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
        <p>Memproses login...</p>
      </div>
    </transition>

    <div class="login-box">
      <div class="login-logo">
        <img
          src="http://res.cloudinary.com/dezj1x6xp/image/upload/v1749880420/PandanViewMandeh/LOGO_SIGARDA_FIX_rw2u1g.png"
          alt="logo Ekin"
          width="100%"
        />
      </div>

      <div class="card card-outline card-success">
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
                placeholder="NIP Anda"
                required
                autocomplete="username"
              />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
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
                  class="input-group-text"
                  type="button"
                  @click="showPassword = !showPassword"
                  tabindex="-1"
                >
                  <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
            </div>

            <div class="text-center row justify-content-center">
              <div class="form-group recaptcha-container mx-auto">
                <!-- v-model akan otomatis mengisi captchaToken dengan token terbaru -->
                <TurnstileWidget
                  v-model="captchaToken"
                  :sitekey="siteKey"
                  theme="light"
                />
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-8">
                <div class="icheck-primary">
                  <input type="checkbox" id="remember" v-model="form.remember" />
                  <label for="remember">&nbsp; Remember Me</label>
                </div>
              </div>
              <div class="col-4">
                <button
                  type="submit"
                  class="btn btn-primary btn-block"
                  :disabled="loading || !captchaToken"
                >
                  <span v-if="loading">Memuat...</span>
                  <span v-else>Sign In</span>
                </button>
              </div>
            </div>
          </form>

          <p class="mb-0">
            Lupa Password?
            <a href="/password/wa/request" target="_blank">Reset Disini</a>
          </p>
          <a
            class="text-sm text-muted"
            href="https://wa.me/6282298476941?text=Assalamualaikum Wr Wb Admin Sigarda,%2C%20Saya%20Ingin%20Bertanya%20Tentang%20Sigarda,%20NIP%20Saya%20adalah:"
            target="_blank"
            >Tanya admin?</a
          >
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-logo {
  margin-top: -50px !important;
  margin-bottom: 0px !important;
}

.loading-overlay {
  position: fixed;
  z-index: 999;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
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
</style>
