<template>
  <!-- Bagian Header -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Pencabutan Maqra & Soal</h1>
          <p class="mb-0 text-muted text-sm">
            Fasilitas pengundian maqra bacaan dan paket soal peserta.
          </p>
        </div>
      </div>
    </div>
  </section>

  <div class="content">
    <div class="container-fluid">
      
      <!-- HEADER FILTER -->
      <div class="card shadow-sm mb-3">
        <div class="card-header py-2">
          <div class="d-flex flex-wrap align-items-center justify-content-between">
            
            <!-- LEFT: FILTERS -->
            <div class="d-flex flex-wrap align-items-center gap-2">
              
              <!-- FILTER CABANG/GOLONGAN -->
              <select 
                v-model="selectedGroupId" 
                class="form-control form-control-sm w-auto" 
                :disabled="isLoading"
                @change="onFilterChange"
                title="Cabang / Golongan"
              >
                <option value="">-- Pilih Golongan --</option>
                <!-- PERUBAHAN DI SINI: Gunakan availableEventGroups -->
                <option v-for="group in availableEventGroups" :key="group.id" :value="group.id">
                  {{ group.full_name }}
                </option>
              </select>

              <!-- KONTROL ADMIN: BUKA / TUTUP PENCABUTAN -->
              <button
                v-if="isPrivilegedAdmin && selectedGroupId"
                type="button"
                class="btn btn-sm font-weight-bold px-3 ml-2"
                :class="isDrawingOpen ? 'btn-success' : 'btn-danger'"
                :disabled="isToggling || isLoading"
                @click="toggleDrawingAccess"
              >
                <i class="fas" :class="isDrawingOpen ? 'fa-lock-open' : 'fa-lock'"></i>
                {{ isDrawingOpen ? 'Akses Terbuka' : 'Akses Tertutup' }}
              </button>

              <!-- FILTER BABAK: Disesuaikan dengan 3 Opsi -->
              <select 
                v-model="selectedRoundId" 
                class="form-control form-control-sm w-auto"
                :disabled="isLoading"
                @change="onFilterChange"
                title="Babak"
              >
                <option value="1">Penyisihan</option>
                <option value="2">Semifinal</option>
                <option value="3">Final</option>
              </select>
              
              <!-- TOMBOL REFRESH -->
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                title="Muat ulang antrean"
                :disabled="isLoading"
                @click="loadData"
              >
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': isLoading }"></i>
                <span class="ml-1 d-none d-sm-inline">Refresh</span>
              </button>

            </div>

            <!-- RIGHT: TABS / NAV PILLS -->
            <ul class="nav nav-pills mt-2 mt-md-0" style="font-size: 0.875rem;" role="tablist">
              <li class="nav-item">
                <a class="nav-link active py-1 px-3" data-toggle="pill" href="#pills-pending">
                  <i class="fas fa-list-ul mr-1"></i> Antrean
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link py-1 px-3" data-toggle="pill" href="#pills-history">
                  <i class="fas fa-history mr-1"></i> Riwayat
                </a>
              </li>
            </ul>

          </div>
        </div>
        
        <!-- CARD BODY / TAB CONTENT -->
        <div class="card-body table-responsive p-0">
          <div class="tab-content" id="pills-tabContent">
            
            <!-- TAB: ANTREAN -->
            <div class="tab-pane fade show active" id="pills-pending">
              <table class="table table-bordered table-hover text-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>No. Peserta</th>
                    <th>Nama / Regu</th>
                    <th>Utusan</th>
                    <th style="width: 120px;" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoading">
                    <td colspan="5" class="text-center py-4">
                      <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
                    </td>
                  </tr>
                  <tr v-else-if="!selectedGroupId">
                    <td colspan="5" class="text-center py-4 text-muted">
                      <i class="fas fa-info-circle mr-1"></i> Pilih golongan lomba terlebih dahulu.
                    </td>
                  </tr>
                  <tr v-else-if="pendingParticipants.length === 0">
                    <td colspan="5" class="text-center py-4">
                      Tidak ada antrean tersisa.
                    </td>
                  </tr>
                  
                  <tr v-for="(p, index) in pendingParticipants" :key="p.participant_id">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td class="font-weight-bold">{{ p.participant_number }}</td>
                    <td>{{ p.name }}</td>
                    <td>
                      <span class="badge badge-light border">{{ p.contingent || '-' }}</span>
                    </td>
                    <td class="text-center">
                      <button 
                        class="btn btn-xs px-2 font-weight-bold" 
                        :class="isDrawingOpen ? 'btn-success' : 'btn-secondary'"
                        @click="openDrawModal(p)"
                        :disabled="!isDrawingOpen"
                        :title="!isDrawingOpen ? 'Pencabutan sedang ditutup oleh Admin' : 'Cabut Maqra'"
                      >
                        <i class="fas" :class="isDrawingOpen ? 'fa-random' : 'fa-lock'"></i> 
                        {{ isDrawingOpen ? 'Cabut' : 'Ditutup' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TAB: RIWAYAT -->
            <div class="tab-pane fade" id="pills-history">
              <table class="table table-bordered table-hover text-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>Peserta</th>
                    <th>Hasil Maqra</th>
                    <th>Hasil Paket</th>
                    <th>Waktu Cabut</th>
                    <th style="width: 80px;" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoading">
                    <td colspan="6" class="text-center py-4">
                      <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
                    </td>
                  </tr>
                  <tr v-else-if="historyParticipants.length === 0">
                    <td colspan="6" class="text-center py-4 text-muted">
                      Belum ada riwayat pencabutan.
                    </td>
                  </tr>
                  
                  <tr v-for="(h, index) in historyParticipants" :key="h.participant_id">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td>
                      <strong>{{ h.participant_number }}</strong><br>
                      <small class="text-muted">{{ h.name }}</small>
                    </td>
                    <td>
                      <div v-if="h.drawing_data.event_maqra">
                        Surah {{ h.drawing_data.event_maqra.surah_name }}
                        <div class="text-xs text-muted">Ayat {{ h.drawing_data.event_maqra.start_ayah }} - {{ h.drawing_data.event_maqra.end_ayah }}</div>
                      </div>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <span v-if="h.drawing_data.event_question_package" class="badge badge-primary">
                        {{ h.drawing_data.event_question_package.package_number }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <small>
                         {{ new Date(h.drawing_data.drawn_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                      </small>
                    </td>
                    <td class="text-center">
                      <button class="btn btn-outline-secondary btn-xs" title="Cetak Struk">
                        <i class="fas fa-print"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- MODAL PENCABUTAN KIOSK MODE -->
    <div class="modal fade kiosk-modal" id="drawModal" data-backdrop="static" data-keyboard="false">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content text-center">
          
          <div class="modal-header bg-dark text-white border-0 py-4 d-block">
            <h3 class="mb-1">PENCABUTAN MAQRA & SOAL</h3>
            <p class="mb-0 text-warning" style="font-size: 1.2rem;">
              {{ activeParticipant?.participant_number }} - {{ activeParticipant?.name }}
            </p>
          </div>

          <div class="modal-body py-5 bg-light">
            <!-- Loading Indicator Modal -->
            <div v-if="isModalLoading || isSubmittingDraw" class="py-5">
               <i class="fas fa-circle-notch fa-spin fa-3x text-primary mb-3"></i>
               <h5>{{ isSubmittingDraw ? 'Menyimpan Hasil Undian...' : 'Mempersiapkan Bank Soal...' }}</h5>
            </div>

            <div v-else class="row justify-content-center">
              
              <!-- PANEL MAQRA -->
              <div v-if="showMaqraPanel" class="col-md-5 mx-2">
                <div class="card border-primary shadow" style="min-height: 250px;">
                  <div class="card-header bg-primary text-white font-weight-bold">MAQRA BACAAN</div>
                  <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    
                    <div v-if="!isRollingMaqra && !finalMaqra" class="text-muted">
                      <i class="fas fa-book-open fa-3x mb-3"></i>
                      <p>Siap diacak</p>
                    </div>
                    
                    <div v-if="isRollingMaqra || finalMaqra" class="w-100 p-3" :class="{'pulse-anim': isRollingMaqra, 'bg-success text-white rounded': finalMaqra}">
                      <h4 class="font-weight-bold mb-1">Surah {{ displayedMaqra?.surah_name }}</h4>
                      <h5 class="mb-0">Ayat {{ displayedMaqra?.start_ayah }} - {{ displayedMaqra?.end_ayah }}</h5>
                      <span v-if="displayedMaqra?.juz" class="badge badge-light mt-2 text-dark">Juz {{ displayedMaqra.juz }}</span>
                    </div>

                    <button v-if="!finalMaqra" class="btn btn-primary mt-4 px-5 rounded-pill font-weight-bold" @click="startRollMaqra" :disabled="isRollingMaqra">
                      <i v-if="isRollingMaqra" class="fas fa-spinner fa-spin mr-1"></i>
                      {{ isRollingMaqra ? 'MENGACAK...' : 'ACAK MAQRA' }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- PANEL PAKET SOAL (AMPLOP) -->
              <div v-if="showPackagePanel && isMaqraStepFinished" class="col-md-5 mx-2">
                <div class="card border-warning shadow" style="min-height: 250px;">
                  <div class="card-header bg-warning font-weight-bold text-dark">PAKET SOAL</div>
                  <div class="card-body">
                    
                    <div v-if="!finalPackage" class="d-flex flex-wrap justify-content-center gap-2">
                      <!-- Grid Amplop tertutup -->
                      <button 
                        v-for="(pkg) in availablePackages" :key="pkg.id"
                        class="btn btn-outline-secondary m-1 px-4 py-3"
                        @click="selectPackage(pkg)"
                      >
                        <i class="fas fa-envelope fa-2x"></i>
                      </button>
                    </div>

                    <div v-else class="h-100 d-flex flex-column justify-content-center align-items-center bg-success text-white rounded p-4">
                      <i class="fas fa-envelope-open-text fa-3x mb-3"></i>
                      <h3 class="font-weight-bold mb-0">{{ finalPackage.package_number }}</h3>
                    </div>

                  </div>
                </div>
              </div>
            </div>
            
            <!-- PESAN JIKA ITEM HABIS -->
            <div v-if="!isModalLoading && availableMaqras.length === 0 && availablePackages.length === 0" class="alert alert-danger mt-4 text-left text-sm">
              <i class="fas fa-exclamation-triangle mr-2"></i> Bank Maqra & Soal Kosong atau belum disiapkan untuk Golongan ini.
            </div>
          </div>

          <div class="modal-footer justify-content-between bg-dark border-0 py-3">
             <div class="d-flex align-items-center ml-2 text-white text-sm">
                <input type="checkbox" id="disableItem" v-model="disableItemAfterDraw" class="mr-2" style="width: 16px; height: 16px;">
                <label for="disableItem" class="mb-0">Tandai Maqra/Soal telah dicabut (Tidak bisa dipilih orang lain)</label>
             </div>
             
             <!-- Tombol Batal/Tutup dipindah ke kanan -->
             <button class="btn btn-light btn-sm px-4 mr-2" @click="closeDrawModal" :disabled="isSubmittingDraw">Tutup</button>
             
             <!-- Tombol SIMPAN HASIL telah dihapus karena penyimpanan dilakukan otomatis -->
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useMasterDataStore } from '../stores/MasterDataStore'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../stores/AuthUserStore'

const masterStore = useMasterDataStore()
const authUserStore = useAuthUserStore()
const currentUser = computed(() => authUserStore.user || {})

// State Data Filter & Loading
const isLoading = ref(false)
const selectedGroupId = ref('')
const selectedRoundId = ref('1')
// --- STATE FILTER CABANG DINAMIS (TERMASUK ROLE PANITERA) ---
const availableEventGroups = computed(() => {
  const roleName = currentUser.value?.role?.name?.toLowerCase() || ''
  let baseGroups = []

  // 1. Jika rolenya adalah Panitera, ambil cabang sesuai tugas Majelisnya
  if (roleName.includes('panitera')) {
    const assignment = currentUser.value?.clerk_assignment || currentUser.value?.clerkAssignment

    if (assignment?.event_judge_panel?.event_groups) {
      baseGroups = assignment.event_judge_panel.event_groups
    } else if (assignment?.event_judge_panel_id) {
      // Fallback: Filter dari data master jika relasi nested tidak ada
      baseGroups = (masterStore.eventGroups || []).filter(
        (g) => g.event_judge_panel_id === assignment.event_judge_panel_id
      )
    }
  } 
  // 2. Jika bukan Panitera (Admin/Superadmin), tampilkan semua
  else {
    baseGroups = masterStore.eventGroups || []
  }

  // 3. Wajib: Hanya kembalikan golongan yang memiliki fitur pencabutan maqra aktif
  return baseGroups.filter(group => 
    group.has_maqra_drawing === true || group.has_maqra_drawing === 1
  )
})

// State Data Peserta
const pendingParticipants = ref([])
const historyParticipants = ref([])

// State Modal Draw & Submitting
const isModalLoading = ref(false)
const isSubmittingDraw = ref(false)
const activeParticipant = ref(null)
const availableMaqras = ref([])
const availablePackages = ref([])
const disableItemAfterDraw = ref(true)

// Visual State Maqra & Paket
const isRollingMaqra = ref(false)
const displayedMaqra = ref(null)
const finalMaqra = ref(null)
const finalPackage = ref(null)
let rollInterval = null

// Fisher-Yates Shuffle
const shuffleArray = (array) => {
  let currentIndex = array.length, randomIndex;
  while (currentIndex !== 0) {
    randomIndex = Math.floor(Math.random() * currentIndex);
    currentIndex--;
    [array[currentIndex], array[randomIndex]] = [array[randomIndex], array[currentIndex]];
  }
  return array;
}

onMounted(async () => {
  await masterStore.loadAllEventGroups()
})

const onFilterChange = () => {
  if (selectedGroupId.value) {
    debouncedLoadData()
  } else {
    pendingParticipants.value = []
    historyParticipants.value = []
  }
}

// Gunakan Debounce agar tidak nge-spam backend saat klik pindah dropdown cepat
const debouncedLoadData = useDebounceFn(() => {
  loadData()
}, 300)

// Tambahkan state baru untuk menyimpan status gerbang
const isDrawingOpen = ref(false)
const isToggling = ref(false)

// Cek apakah user adalah admin (Sesuaikan dengan auth user store Anda)
const isPrivilegedAdmin = computed(() => {
  const roleName = currentUser.value?.role?.name || ''
  return ['SUPERADMIN', 'ADMIN_EVENT'].includes(roleName)
})

// Modifikasi loadData untuk menangkap 'is_drawing_open'
const loadData = async () => {
  if (!selectedGroupId.value || !selectedRoundId.value) return
  
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-maqra-drawings/participants', {
      params: { event_group_id: selectedGroupId.value, round_id: selectedRoundId.value }
    })
    isDrawingOpen.value = res.data.is_drawing_open // Tangkap status gerbang
    pendingParticipants.value = res.data.pending
    historyParticipants.value = res.data.history
  } catch (error) {
    Swal.fire('Gagal', 'Terjadi kesalahan saat mengambil data antrean.', 'error')
  } finally {
    isLoading.value = false
  }
}

// Method baru untuk Admin menekan toggle Buka/Tutup
const toggleDrawingAccess = async () => {
  if (!selectedGroupId.value) return;
  isToggling.value = true
  
  const targetStatus = !isDrawingOpen.value
  
  try {
    const res = await axios.post('/api/v1/event-maqra-drawings/toggle-status', {
      event_group_id: selectedGroupId.value,
      is_open: targetStatus
    })
    
    isDrawingOpen.value = targetStatus
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: res.data.message,
      timer: 1500,
      showConfirmButton: false
    })
  } catch (error) {
    Swal.fire('Gagal', 'Tidak dapat mengubah status akses.', 'error')
  } finally {
    isToggling.value = false
  }
}

const loadAvailableItems = async () => {
  isModalLoading.value = true
  try {
    const resItems = await axios.get('/api/v1/event-maqra-drawings/available-items', {
      params: { event_group_id: selectedGroupId.value, round_id: selectedRoundId.value }
    })
    availableMaqras.value = resItems.data.maqras || []
    availablePackages.value = resItems.data.packages || []
    
    if (availablePackages.value.length > 0) {
      availablePackages.value = shuffleArray([...availablePackages.value])
    }
  } catch (error) {
     Swal.fire('Gagal', 'Tidak dapat mengambil bank Maqra & Soal', 'error')
  } finally {
    isModalLoading.value = false
  }
}

// BUKA MODAL PENCABUTAN
const openDrawModal = async (participant) => {
  activeParticipant.value = participant
  finalMaqra.value = null
  finalPackage.value = null
  displayedMaqra.value = null
  isRollingMaqra.value = false
  isSubmittingDraw.value = false
  
  $('#drawModal').modal('show')
  await loadAvailableItems() // Fetch item sesaat modal dibuka agar valid (real-time)
}

const closeDrawModal = () => {
  if (isSubmittingDraw.value) return // Jangan tutup jika sedang simpan
  clearInterval(rollInterval)
  $('#drawModal').modal('hide')
}

// LOGIKA VISIBILITAS PANEL (BERDASARKAN HASIL FETCH)
const showMaqraPanel = computed(() => availableMaqras.value.length > 0)
const showPackagePanel = computed(() => availablePackages.value.length > 0)

const isMaqraStepFinished = computed(() => {
  if (!showMaqraPanel.value) return true;
  return finalMaqra.value !== null;
})

// VALIDASI SEBELUM SIMPAN
const isReadyToSave = computed(() => {
  if (showMaqraPanel.value && !finalMaqra.value) return false;
  if (showPackagePanel.value && !finalPackage.value) return false;
  if (!showMaqraPanel.value && !showPackagePanel.value) return false;
  return true;
})

// LOGIKA ACAK MAQRA
const startRollMaqra = () => {
  if (availableMaqras.value.length === 0) return
  isRollingMaqra.value = true
  
  let count = 0
  rollInterval = setInterval(() => {
    const randomIndex = Math.floor(Math.random() * availableMaqras.value.length)
    displayedMaqra.value = availableMaqras.value[randomIndex]
    count++
    
    // Berhenti setelah 30 putaran
    if (count > 30) {
      clearInterval(rollInterval)
      isRollingMaqra.value = false
      finalMaqra.value = displayedMaqra.value
      
      // AUTO-SAVE: Jika tidak ada panel soal, langsung submit
      if (!showPackagePanel.value && isReadyToSave.value) {
          // Beri jeda sedikit agar pengguna melihat hasilnya sebelum loading submit
          setTimeout(() => {
              submitDraw()
          }, 800)
      }
    }
  }, 80) // Sedikit dipercepat animasinya (100ms -> 80ms)
}

// LOGIKA PILIH PAKET
const selectPackage = (pkg) => {
  finalPackage.value = pkg
  
  // AUTO-SAVE: Setelah memilih amplop, langsung submit
  if (isReadyToSave.value) {
      // Beri jeda sedikit agar efek amplop terbuka terlihat
      setTimeout(() => {
          submitDraw()
      }, 500)
  }
}

// SUBMIT HASIL KE BACKEND
const submitDraw = async () => {
  isSubmittingDraw.value = true
  try {
    await axios.post('/api/v1/event-maqra-drawings/draw', {
      round_id: selectedRoundId.value,
      event_participant_id: activeParticipant.value.participant_id,
      is_team: activeParticipant.value.is_team,
      exam_type: 'tilawah', 
      event_maqra_id: finalMaqra.value?.id,
      event_question_package_id: finalPackage.value?.id,
      disable_item: disableItemAfterDraw.value
    })
    
    isSubmittingDraw.value = false 
    closeDrawModal()
    
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Undian otomatis tersimpan.',
      timer: 1500,
      showConfirmButton: false
    })
    
    loadData() // Refresh tabel di background
  } catch (error) {
    isSubmittingDraw.value = false
    Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan undian.', 'error')
  } 
}
</script>

<style scoped>
.btn-xs {
    padding: 2px 8px !important;
    font-size: 0.75rem !important;
    line-height: 1.5 !important;
}

.text-xs {
  font-size: 0.75rem;
}

.gap-2 {
  gap: .5rem;
}

/* Kiosk Modal Override */
.kiosk-modal .modal-content {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 0 40px rgba(0,0,0,0.5);
}
.kiosk-modal::before {
  content: '';
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.85);
  z-index: -1;
}
.pulse-anim {
  animation: pulse 0.1s infinite;
  opacity: 0.7;
}
@keyframes pulse {
  0% { transform: scale(0.98); }
  50% { transform: scale(1.02); }
  100% { transform: scale(0.98); }
}
</style>