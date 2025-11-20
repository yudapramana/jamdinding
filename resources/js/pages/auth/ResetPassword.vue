<script setup>
import axios from 'axios'
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { TurnstileWidget } from '@delaneydev/laravel-turnstile-vue'

const route = useRoute()
const router = useRouter()

// Jika link dari email Laravel menyertakan token & email di URL (?token=...&email=...)
// kita masuk ke mode "reset". Jika tidak ada token, mode "request".
const token = ref(route.query.token || '')
const emailFromQuery = ref(route.query.email || '')
const mode = computed(() => (token.value ? 'reset' : 'request'))

const siteKey = '0x4AAAAAABg5q-5b8cUPKPGt' // sama seperti di login
const captchaToken = ref('')

const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

// Form untuk minta link reset (REQUEST)
const requestForm = reactive({
  email: '',
})

// Form untuk set password baru (RESET)
const resetForm = reactive({
  email: emailFromQuery.value || '',
  password: '',
  password_confirmation: '',
})

onMounted(() => {
  // Kalau email ada di query, otomatis isi
  if (emailFromQuery.value && !resetForm.email) {
    resetForm.email = emailFromQuery.value
  }
})

// Kirim permintaan link reset password
const submitRequest = async () => {
  loading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  try {
    await axios.post('/forgot-password', {
      email: requestForm.email,
      'cf-turnstile-response': captchaToken.value,
    })
    successMessage.value = 'Link reset password sudah dikirim ke email Anda (cek juga folder Spam).'
  } catch (err) {
    // Laravel biasanya balas 422 untuk email yang tidak ditemukan; tampilkan pesan ramah
    errorMessage.value = err?.response?.data?.message
      || err?.response?.data?.errors?.email?.[0]
      || 'Gagal mengirim link reset. Pastikan email benar.'
  } finally {
    loading.value = false
  }
}

// Kirim form reset password dengan token
const submitReset = async () => {
  loading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  try {
    await axios.post('/reset-password', {
      token: token.value,
      email: resetForm.email,
      password: resetForm.password,
      password_confirmation: resetForm.password_confirmation,
      'cf-turnstile-response': captchaToken.value,
    })
    successMessage.value = 'Password berhasil direset. Anda akan diarahkan ke halaman login...'
    setTimeout(() => {
      router.push({ path: '/' }) // arahkan ke halaman login
    }, 1500)
  } catch (err) {
    errorMessage.value =
      err?.response?.data?.message
      || err?.response?.data?.errors?.email?.[0]
      || err?.response?.data?.errors?.password?.[0]
      || 'Gagal reset password. Periksa kembali data Anda.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <transition name="fade">
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
        <p>Memproses...</p>
      </div>
    </transition>

    <div class="login-box">
      <div class="login-logo">
        <img
          src="http://res.cloudinary.com/dezj1x6xp/image/upload/v1749880420/PandanViewMandeh/LOGO_SIGARDA_FIX_rw2u1g.png"
          alt="logo"
          width="100%"
        >
      </div>

      <div class="card card-outline card-success">
        <div class="card-body login-card-body">
          <p class="login-box-msg" v-if="mode === 'request'">
            Masukkan email Anda untuk menerima link reset password
          </p>
          <p class="login-box-msg" v-else>
            Atur password baru Anda
          </p>

          <transition name="fade">
            <div v-if="errorMessage" class="alert alert-danger" role="alert">
              {{ errorMessage }}
            </div>
          </transition>

          <transition name="fade">
            <div v-if="successMessage" class="alert alert-success" role="alert">
              {{ successMessage }}
            </div>
          </transition>

          <!-- MODE: REQUEST LINK RESET -->
          <form v-if="mode === 'request'" @submit.prevent="submitRequest" autocomplete="off">
            <div class="input-group mb-3">
              <input
                v-model="requestForm.email"
                type="email"
                class="form-control"
                placeholder="Email Anda"
                required
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
            </div>

            <div class="text-center row justify-content-center">
              <div class="form-group recaptcha-container mx-auto">
                <TurnstileWidget v-model="captchaToken" :sitekey="siteKey" theme="light" />
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block" :disabled="loading || !captchaToken">
                  Kirim Link Reset
                </button>
              </div>
            </div>

            <div class="text-center">
              <router-link :to="{ path: '/login' }">Kembali ke Login</router-link>
            </div>
          </form>

          <!-- MODE: SET PASSWORD BARU -->
          <form v-else @submit.prevent="submitReset" autocomplete="off">
            <div class="input-group mb-3">
              <input
                v-model="resetForm.email"
                type="email"
                class="form-control"
                placeholder="Email"
                required
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
            </div>

            <div class="input-group mb-3">
              <input
                v-model="resetForm.password"
                type="password"
                class="form-control"
                placeholder="Password baru"
                minlength="8"
                required
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
            </div>

            <div class="input-group mb-3">
              <input
                v-model="resetForm.password_confirmation"
                type="password"
                class="form-control"
                placeholder="Ulangi password baru"
                minlength="8"
                required
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
            </div>

            <div class="text-center row justify-content-center">
              <div class="form-group recaptcha-container mx-auto">
                <TurnstileWidget v-model="captchaToken" :sitekey="siteKey" theme="light" />
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block" :disabled="loading || !captchaToken">
                  Simpan Password Baru
                </button>
              </div>
            </div>

            <div class="text-center">
              <router-link :to="{ path: '/' }">Kembali ke Login</router-link>
            </div>
          </form>
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
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(255, 255, 255, 0.85);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  font-weight: bold; font-size: 16px; color: #555;
  transition: opacity 0.3s;
}

.spinner {
  border: 4px solid #ccc;
  border-top: 4px solid #28a745;
  border-radius: 50%;
  width: 40px; height: 40px;
  animation: spin 0.8s linear infinite;
  margin-bottom: 10px;
}
@keyframes spin { 0% {transform: rotate(0deg);} 100% {transform: rotate(360deg);} }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
