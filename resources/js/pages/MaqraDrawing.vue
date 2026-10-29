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
              
              <select 
                v-model="selectedGroupId" 
                class="form-control form-control-sm w-auto" 
                :disabled="isLoading"
                @change="onFilterChange"
                title="Cabang / Golongan"
              >
                <option value="">-- Pilih Golongan --</option>
                <option v-for="group in availableEventGroups" :key="group.id" :value="group.id">
                  {{ group.full_name }}
                </option>
              </select>

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

            <!-- RIGHT: TABS -->
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
        
        <!-- CARD BODY -->
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
                    <th style="width: 170px;" class="text-center">Aksi</th>
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
                        v-if="!isRegistered(p)"
                        class="btn btn-xs px-2 font-weight-bold btn-warning"
                        disabled
                      >
                        <i class="fas fa-user-clock"></i> Belum Daftar
                      </button>
                      <button 
                        v-else
                        class="btn btn-xs px-2 font-weight-bold" 
                        :class="isDrawingOpen ? 'btn-success' : 'btn-secondary'"
                        @click="openDrawModal(p)"
                        :disabled="!isDrawingOpen"
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
               <h5>{{ isSubmittingDraw ? 'Memverifikasi & Menyimpan Hasil...' : 'Mempersiapkan Bank Soal...' }}</h5>
            </div>

            <!-- PENCABUTAN DITUTUP -->
            <div v-else-if="isModalDrawingClosed" class="py-5">
              <i class="fas fa-lock fa-4x text-danger mb-3"></i>
              <h4 class="font-weight-bold text-danger mb-2">Pencabutan Maqra Sedang Ditutup</h4>
              <p class="text-muted mb-0">Silakan hubungi panitia atau admin untuk membuka akses pencabutan.</p>
            </div>

            <div v-else class="row justify-content-center">
              
              <!-- PANEL MAQRA (AMPLOP) -->
              <div v-if="showMaqraPanel" class="col-md-5 mx-2">
                <div class="card border-primary shadow" style="min-height: 250px;">
                  <div class="card-header bg-primary text-white font-weight-bold">MAQRA BACAAN</div>
                  <div class="card-body">

                    <!-- AMPLOP BELUM DIPILIH -->
                    <div v-if="!finalMaqra" class="d-flex flex-wrap justify-content-center gap-2">
                      <button
                        v-for="(maqra) in availableMaqras" :key="maqra.id"
                        class="btn btn-outline-primary m-1 px-4 py-3"
                        @click="selectMaqra(maqra)"
                      >
                        <i class="fas fa-envelope fa-2x"></i>
                      </button>
                    </div>

                    <!-- AMPLOP TERPILIH, TAPI BELUM DISUBMIT KE SERVER -->
                    <div v-else-if="finalMaqra && !isDrawSuccess" class="h-100 d-flex flex-column justify-content-center align-items-center bg-secondary text-white rounded p-4">
                      <i class="fas fa-envelope fa-3x mb-3"></i>
                      <h4 class="font-weight-bold mb-1">Amplop Terpilih</h4>
                    </div>

                    <!-- KETIKA BERHASIL (API SUKSES MENYIMPAN) -> TAMPILKAN ISINYA -->
                    <div v-else class="h-100 d-flex flex-column justify-content-center align-items-center bg-success text-white rounded p-4">
                      <i class="fas fa-envelope-open-text fa-3x mb-3"></i>
                      <h4 class="font-weight-bold mb-1">Surah {{ finalMaqra.surah_name }}</h4>
                      <h5 class="mb-0">Ayat {{ finalMaqra.start_ayah }} - {{ finalMaqra.end_ayah }}</h5>
                      <span v-if="finalMaqra.juz" class="badge badge-light mt-2 text-dark">Juz {{ finalMaqra.juz }}</span>
                    </div>

                  </div>
                </div>
              </div>

              <!-- PANEL PAKET SOAL (AMPLOP) -->
              <div v-if="showPackagePanel && isMaqraStepFinished" class="col-md-5 mx-2">
                <div class="card border-warning shadow" style="min-height: 250px;">
                  <div class="card-header bg-warning font-weight-bold text-dark">PAKET SOAL</div>
                  <div class="card-body">
                    
                    <div v-if="!finalPackage" class="d-flex flex-wrap justify-content-center gap-2">
                      <button 
                        v-for="(pkg) in availablePackages" :key="pkg.id"
                        class="btn btn-outline-secondary m-1 px-4 py-3"
                        @click="selectPackage(pkg)"
                      >
                        <i class="fas fa-envelope fa-2x"></i>
                      </button>
                    </div>

                    <div v-else-if="finalPackage && !isDrawSuccess" class="h-100 d-flex flex-column justify-content-center align-items-center bg-secondary text-white rounded p-4">
                      <i class="fas fa-envelope fa-3x mb-3"></i>
                      <h4 class="font-weight-bold mb-1">Amplop Terpilih</h4>
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
            <div v-if="!isModalLoading && !isModalDrawingClosed && availableMaqras.length === 0 && availablePackages.length === 0" class="alert alert-danger mt-4 text-left text-sm">
              <i class="fas fa-exclamation-triangle mr-2"></i> Bank Maqra & Soal Kosong atau belum disiapkan untuk Golongan ini.
            </div>
          </div>

          <div class="modal-footer justify-content-between bg-dark border-0 py-3">
             <div v-if="!isModalDrawingClosed" class="d-flex align-items-center ml-2 text-white text-sm">
                <input type="checkbox" id="disableItem" v-model="disableItemAfterDraw" class="mr-2" style="width: 16px; height: 16px;">
                <label for="disableItem" class="mb-0">Tandai Maqra/Soal telah dicabut (Tidak bisa dipilih orang lain)</label>
             </div>
             
             <!-- Tombol Tutup Manual -->
             <button class="btn btn-light btn-sm px-4 mr-2" @click="closeDrawModal" :disabled="isSubmittingDraw">
               {{ isDrawSuccess ? 'Selesai & Tutup' : 'Batal / Tutup' }}
             </button>
             
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
const availableEventGroups = computed(() => {
  const roleName = currentUser.value?.role?.name?.toLowerCase() || ''
  let baseGroups = []
  if (roleName.includes('panitera')) {
    const assignment = currentUser.value?.clerk_assignment || currentUser.value?.clerkAssignment
    if (assignment?.event_judge_panel?.event_groups) {
      baseGroups = assignment.event_judge_panel.event_groups
    } else if (assignment?.event_judge_panel_id) {
      baseGroups = (masterStore.eventGroups || []).filter(
        (g) => g.event_judge_panel_id === assignment.event_judge_panel_id
      )
    }
  } else {
    baseGroups = masterStore.eventGroups || []
  }
  return baseGroups.filter(group => group.has_maqra_drawing === true || group.has_maqra_drawing === 1)
})

// State Data Peserta
const pendingParticipants = ref([])
const historyParticipants = ref([])

// State Modal Draw & Submitting
const isModalLoading = ref(false)
const isModalDrawingClosed = ref(false) 
const isSubmittingDraw = ref(false)
const isDrawSuccess = ref(false) // ⭐️ STATE BARU: Mencegah maqra terbuka sebelum sukses
const activeParticipant = ref(null)
const availableMaqras = ref([])
const availablePackages = ref([])
const disableItemAfterDraw = ref(true)

// Visual State Maqra & Paket (hasil pilihan amplop)
const finalMaqra = ref(null)
const finalPackage = ref(null)

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

const debouncedLoadData = useDebounceFn(() => {
  loadData()
}, 300)

const isDrawingOpen = ref(false)
const isToggling = ref(false)

const isRegistered = (p) => p?.branch_sequence !== null && p?.branch_sequence !== undefined && p?.branch_sequence !== ''

const isPrivilegedAdmin = computed(() => {
  const roleName = currentUser.value?.role?.name || ''
  return ['SUPERADMIN', 'ADMIN_EVENT'].includes(roleName)
})

const loadData = async () => {
  if (!selectedGroupId.value || !selectedRoundId.value) return
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-maqra-drawings/participants', {
      params: { event_group_id: selectedGroupId.value, round_id: selectedRoundId.value }
    })
    isDrawingOpen.value = res.data.is_drawing_open
    pendingParticipants.value = res.data.pending
    historyParticipants.value = res.data.history
  } catch (error) {
    Swal.fire('Gagal', 'Terjadi kesalahan saat mengambil data antrean.', 'error')
  } finally {
    isLoading.value = false
  }
}

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
      icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false
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
    isModalDrawingClosed.value = resItems.data.is_drawing_open === false
    isDrawingOpen.value = resItems.data.is_drawing_open !== false
    
    // Acak ulang maqra dan paket agar posisi amplop selalu baru
    availableMaqras.value = resItems.data.maqras ? shuffleArray([...resItems.data.maqras]) : []
    availablePackages.value = resItems.data.packages ? shuffleArray([...resItems.data.packages]) : []
  } catch (error) {
     Swal.fire('Gagal', 'Tidak dapat mengambil bank Maqra & Soal', 'error')
  } finally {
    isModalLoading.value = false
  }
}

const openDrawModal = async (participant) => {
  if (!isRegistered(participant)) {
    Swal.fire('Belum Daftar Ulang', 'Peserta ini belum melakukan daftar ulang.', 'warning')
    return
  }
  activeParticipant.value = participant
  finalMaqra.value = null
  finalPackage.value = null
  isSubmittingDraw.value = false
  isDrawSuccess.value = false // ⭐️ Reset state success
  isModalDrawingClosed.value = false
  availableMaqras.value = []
  availablePackages.value = []
  
  $('#drawModal').modal('show')
  await loadAvailableItems()
}

const closeDrawModal = () => {
  if (isSubmittingDraw.value) return 
  $('#drawModal').modal('hide')
}

const showMaqraPanel = computed(() => availableMaqras.value.length > 0)
const showPackagePanel = computed(() => availablePackages.value.length > 0)

const isMaqraStepFinished = computed(() => {
  if (!showMaqraPanel.value) return true;
  return finalMaqra.value !== null;
})

const isReadyToSave = computed(() => {
  if (showMaqraPanel.value && !finalMaqra.value) return false;
  if (showPackagePanel.value && !finalPackage.value) return false;
  if (!showMaqraPanel.value && !showPackagePanel.value) return false;
  return true;
})

// LOGIKA PILIH AMPLOP MAQRA
const selectMaqra = (maqra) => {
  if (finalMaqra.value || isSubmittingDraw.value || isDrawSuccess.value) return
  finalMaqra.value = maqra // ⭐️ UI akan menahan tampilannya karena isDrawSuccess masih false

  // Langsung submit jika tidak ada paket soal
  if (!showPackagePanel.value && isReadyToSave.value) {
     submitDraw()
  }
}

// LOGIKA PILIH PAKET
const selectPackage = (pkg) => {
  if (finalPackage.value || isSubmittingDraw.value || isDrawSuccess.value) return
  finalPackage.value = pkg
  
  if (isReadyToSave.value) {
     submitDraw()
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
    isDrawSuccess.value = true // ⭐️ API BERHASIL! SEKARANG TAMPILKAN ISI AMPLOPNYA!
    
    // ⭐️ DIHAPUS: Modal tidak otomatis menutup lagi agar user bisa membaca hasil akhir
    // closeDrawModal() 
    
    loadData() // Refresh tabel di background
  } catch (error) {
    isSubmittingDraw.value = false
    
    // ⭐️ RESET PILIHAN JIKA GAGAL: Kembalikan status amplop ke belum dipilih
    finalMaqra.value = null
    finalPackage.value = null
    isDrawSuccess.value = false

    if (error.response?.status === 403) {
      isDrawingOpen.value = false
      isModalDrawingClosed.value = true
      availableMaqras.value = []
      availablePackages.value = []
      Swal.fire('Ditutup', error.response.data?.message || 'Pencabutan Maqra sedang ditutup.', 'warning')
    } else {
      // ⭐️ MUNCULKAN ERROR RACE CONDITION DARI CONTROLLER
      Swal.fire('Gagal Tersimpan', error.response?.data?.message || 'Terjadi kesalahan saat menyimpan undian.', 'error')
      
      // ⭐️ REFRESH AMPLOP DI LAYAR (Membuang amplop yang sudah didahului peserta lain)
      loadAvailableItems()
    }
  } 
}
</script>

<style scoped>
.btn-xs { padding: 2px 8px !important; font-size: 0.75rem !important; line-height: 1.5 !important; }
.text-xs { font-size: 0.75rem; }
.gap-2 { gap: .5rem; }

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
</style>