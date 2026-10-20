<template>
  <nav class="main-header navbar navbar-expand-md navbar-light bg-white shadow-sm border-bottom-0">
    <div class="container-fluid px-4">
      
      <!-- Brand Logo & Title -->
      <router-link to="/kafilah/maqra-drawings" class="navbar-brand d-flex align-items-center">
        <!-- Anda bisa mengganti icon ini dengan logo instansi/MTQ -->
        <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center mr-2 shadow-sm" style="width: 35px; height: 35px;">
          <i class="fas fa-quran"></i>
        </div>
        <span class="brand-text font-weight-bold text-dark tracking-wide">
          e-Maqra <sup class="text-success font-weight-bolder">PRO</sup>
        </span>
      </router-link>

      <!-- Tombol Toggler untuk Mobile -->
      <button class="navbar-toggler order-1 border-0" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      

      <!-- Right Menu (User Profile & Utilities) -->
      <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto align-items-center">
        
        <!-- Indikator Waktu Real-time -->
        <li class="nav-item d-none d-lg-block mr-3 border-right pr-3">
          <div class="text-muted text-sm d-flex flex-column align-items-end" style="line-height: 1.2;">
            <strong class="text-dark">{{ currentTime }}</strong>
            <small>{{ currentDate }}</small>
          </div>
        </li>

        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown user-menu">
          <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-toggle="dropdown">
            <div class="user-avatar bg-light text-success border border-success rounded-circle d-flex justify-content-center align-items-center elevation-1" style="width: 30px; height: 30px;">
              <i class="fas fa-user-tie"></i>
            </div>
            <span class="d-none d-md-inline ml-2 font-weight-bold text-dark">{{ authUserStore.user?.name || 'Administrator' }}</span>
          </a>
          
          <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right border-0 shadow-lg rounded" style="min-width: 280px;">
            <!-- User Image Header -->
            <li class="user-header bg-success d-flex flex-column justify-content-center align-items-center rounded-top">
               <div class="bg-white text-success rounded-circle d-flex justify-content-center align-items-center elevation-2 mb-2" style="width: 70px; height: 70px; font-size: 2rem;">
                  <i class="fas fa-user-tie"></i>
               </div>
              <p class="mb-0 text-white font-weight-bold" style="font-size: 1.1rem;">
                {{ authUserStore.user?.name || 'Pengguna Sistem' }}
              </p>
              <small class="text-light text-wrap px-2">
                Kontingen: {{ authUserStore.user?.contingent_name || authUserStore.user?.district?.name || '-' }}
              </small>
            </li>
            
            <!-- Menu Body -->
            <li class="user-body border-bottom">
              <div class="row text-center text-sm">
                <div class="col-12 text-muted">
                  Peran Akses: <strong>{{ authUserStore.user?.role?.name || 'Kafilah' }}</strong>
                </div>
              </div>
            </li>
            
            <!-- Menu Footer -->
            <li class="user-footer d-flex justify-content-between bg-light rounded-bottom">
              <button class="btn btn-default btn-sm btn-flat rounded">
                <i class="fas fa-id-card mr-1"></i> Profil
              </button>
              <button class="btn btn-danger btn-sm btn-flat rounded" @click="handleLogout">
                <i class="fas fa-sign-out-alt mr-1"></i> Keluar
              </button>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthUserStore } from '../stores/AuthUserStore'
import Swal from 'sweetalert2'

const router = useRouter()
const authUserStore = useAuthUserStore()

// State untuk Waktu Real-time
const currentTime = ref('')
const currentDate = ref('')
let timer = null

const updateClock = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(() => {
  updateClock()
  timer = setInterval(updateClock, 1000)
})

onUnmounted(() => {
  clearInterval(timer)
})

const handleLogout = async () => {
  const result = await Swal.fire({
    title: 'Keluar dari Aplikasi?',
    text: "Sesi Anda akan diakhiri.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal'
  })

  if (result.isConfirmed) {
    try {
      await authUserStore.logout() // Sesuaikan dengan method logout di store Anda
      router.push('/login')
    } catch (error) {
      router.push('/login')
    }
  }
}
</script>

<style scoped>
.font-weight-medium {
  font-weight: 500;
}
.tracking-wide {
  letter-spacing: 0.05em;
}
.navbar-light .navbar-nav .nav-link:hover {
  color: #28a745;
}
</style>