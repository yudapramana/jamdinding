<template>
  <div class="content pt-3">
    <div class="container-fluid">
      
      <!-- MAIN COMPACT CARD / ONE PAGE SHOW -->
      <div class="card shadow-sm border-0 mb-3">
        
        <!-- COMPACT NAVBAR / TOOLBAR HEADER -->
        <div class="card-header py-2 bg-white border-bottom">
          
          <!-- ROW 1: BRANDING / STATUS & TAB PILIHAN -->
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            
            <!-- LEFT: TITLE & KONTINGEN BADGE -->
            <div class="d-flex align-items-center gap-2">
              <span class="font-weight-bold text-dark mb-0" style="font-size: 1.05rem;">
                <i class="fas fa-random text-success mr-1"></i> Pencabutan Maqra & Soal
              </span>

              <!-- Badge Kontingen User Biasa -->
              <span class="badge badge-light border text-secondary px-2 py-1 text-xs" v-if="!isPrivilegedAdmin">
                <i class="fas fa-map-marker-alt text-primary mr-1"></i> {{ userContingent }}
              </span>

              <!-- Badge Mode Admin -->
              <span class="badge badge-warning px-2 py-1 text-xs" v-else-if="selectedContingent">
                <i class="fas fa-user-shield mr-1"></i> Admin: {{ selectedContingent }}
              </span>
            </div>

            <!-- RIGHT: TAB SWITCHER -->
            <div>
              <ul class="nav nav-pills nav-sm" style="font-size: 0.85rem;" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active py-1 px-3" data-toggle="pill" href="#pills-pending">
                    <i class="fas fa-list-ul mr-1"></i> Belum Cabut
                    <span class="badge badge-pill badge-danger ml-1" v-if="pendingParticipants.length">
                      {{ pendingParticipants.length }}
                    </span>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link py-1 px-3" data-toggle="pill" href="#pills-history">
                    <i class="fas fa-history mr-1"></i> Riwayat
                    <span class="badge badge-pill badge-secondary ml-1" v-if="historyParticipants.length">
                      {{ historyParticipants.length }}
                    </span>
                  </a>
                </li>
              </ul>
            </div>

          </div>

          <!-- ROW 2: FILTERS & SEARCH BAR -->
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 pt-2 border-top">
            
            <!-- LEFT: INPUT FILTERS -->
            <div class="d-flex flex-wrap align-items-center gap-2">
              
              <!-- FILTER BABAK -->
              <div class="input-group input-group-sm w-auto">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-right-0"><i class="fas fa-layer-group text-muted"></i></span>
                </div>
                <select 
                  v-model="selectedRoundId" 
                  class="form-control form-control-sm border-left-0"
                  :disabled="isLoading || (isPrivilegedAdmin && !selectedContingent)"
                  @change="loadData"
                  title="Babak"
                >
                  <option value="1">Penyisihan</option>
                  <option value="2">Semifinal</option>
                  <option value="3">Final</option>
                </select>
              </div>

              <!-- FILTER KONTINGEN (Hanya Superadmin / Admin Event) -->
              <div v-if="isPrivilegedAdmin" class="input-group input-group-sm w-auto">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-right-0"><i class="fas fa-flag text-muted"></i></span>
                </div>
                <select
                  v-model="selectedContingent"
                  class="form-control form-control-sm border-left-0 border-primary font-weight-bold"
                  title="Pilih Kontingen (Mode Admin)"
                  @change="loadData"
                >
                  <option value="">-- Pilih Kontingen --</option>
                  <option
                    v-for="reg in masterDataStore.eventRegions"
                    :key="reg.id"
                    :value="reg.name || reg.region_name"
                  >
                    {{ reg.name || reg.region_name }}
                  </option>
                </select>
              </div>

              <!-- FILTER GOLONGAN -->
              <div class="input-group input-group-sm w-auto">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-right-0"><i class="fas fa-filter text-muted"></i></span>
                </div>
                <select 
                  v-model="selectedGroupId" 
                  class="form-control form-control-sm border-left-0" 
                  title="Filter Cabang / Golongan"
                  @change="loadData"
                >
                  <option value="">-- Semua Golongan --</option>
                  <option v-for="group in availableGroups" :key="group.id" :value="group.id">
                    {{ group.name }}
                  </option>
                </select>
              </div>
              
              <!-- REFRESH BUTTON -->
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                title="Muat ulang data"
                :disabled="isLoading || !selectedContingent"
                @click="loadData"
              >
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': isLoading }"></i>
              </button>

              <!-- STATUS GERBANG (Hanya tampil jika Golongan dipilih) -->
              <div v-if="selectedGroupId" class="ml-2">
                 <span class="badge px-2 py-1" :class="isDrawingOpen ? 'badge-success' : 'badge-danger'">
                   <i class="fas" :class="isDrawingOpen ? 'fa-lock-open' : 'fa-lock'"></i> 
                   {{ isDrawingOpen ? 'Gerbang Terbuka' : 'Gerbang Ditutup' }}
                 </span>
              </div>

            </div>

            <!-- RIGHT: COMPACT SEARCH -->
            <div class="input-group input-group-sm w-auto" style="min-width: 210px;">
              <input
                v-model="searchQuery"
                type="text"
                class="form-control form-control-sm"
                placeholder="Cari nama / no tampil..."
              />
              <div class="input-group-append">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
              </div>
            </div>

          </div>

        </div>
        
        <!-- CARD BODY / TAB CONTENT -->
        <div class="card-body table-responsive p-0">
          <div class="tab-content" id="pills-tabContent">
            
            <!-- TAB: ANTREAN (Belum Cabut) -->
            <div class="tab-pane fade show active" id="pills-pending">
              <table class="table table-bordered table-hover text-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>Cabang / Golongan</th>
                    <th style="width: 100px;">No. Tampil</th>
                    <th>Identitas Peserta / Regu</th>
                    <th style="width: 110px;" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoading">
                    <td colspan="5" class="text-center py-4">
                      <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data peserta kafilah...
                    </td>
                  </tr>
                  <tr v-else-if="!selectedContingent">
                    <td colspan="5" class="text-center py-4 text-muted">
                      <i class="fas fa-info-circle mr-1"></i> Silakan pilih kontingen terlebih dahulu.
                    </td>
                  </tr>
                  <tr v-else-if="filteredPending.length === 0">
                    <td colspan="5" class="text-center py-4 text-muted">
                      Tidak ada antrean tersisa atau data tidak ditemukan.
                    </td>
                  </tr>
                  
                  <tr v-for="(p, index) in filteredPending" :key="p.participant_id">
                    <td class="text-center align-middle">{{ index + 1 }}</td>
                    
                    <!-- PERBAIKAN: Tampilkan Status Gerbang di Bawah Nama Golongan -->
                    <td class="align-middle">
                      <div class="text-primary text-xs font-weight-bold mb-1">{{ p.group_name }}</div>
                      <span class="badge" :class="p.is_drawing_open ? 'badge-success' : 'badge-danger'" style="font-size: 0.65rem;">
                        <i class="fas" :class="p.is_drawing_open ? 'fa-lock-open' : 'fa-lock'"></i>
                        {{ p.is_drawing_open ? 'Gerbang Terbuka' : 'Gerbang Ditutup' }}
                      </span>
                    </td>
                    
                    <td class="font-weight-bold text-md align-middle">{{ p.participant_number }}</td>
                    
                    <td class="align-middle">
                      <template v-if="p.is_team">
                        <span class="badge badge-info mb-1"><i class="fas fa-users mr-1"></i> BEREGU</span>
                        <strong class="text-dark ml-2">{{ p.name }}</strong>
                        <div v-if="p.members" class="text-xs text-muted mt-1" style="white-space: normal; line-height: 1.3;">
                          <i class="fas fa-user-friends text-info mr-1"></i>
                          {{ Array.isArray(p.members) ? p.members.join(', ') : p.members }}
                        </div>
                      </template>
                      <template v-else>
                        <span class="badge badge-secondary mb-1"><i class="fas fa-user mr-1"></i> INDIVIDU</span>
                        <span class="text-dark font-weight-bold ml-2">{{ p.name }}</span>
                      </template>
                    </td>

                    <!-- PERBAIKAN: Tombol Aksi Berdasarkan Status p.is_drawing_open -->
                    <td class="text-center align-middle">
                      <button 
                        class="btn btn-xs px-3 font-weight-bold" 
                        :class="p.is_drawing_open ? 'btn-success' : 'btn-secondary'"
                        @click="openDrawModal(p)"
                        :disabled="!p.is_drawing_open"
                        :title="!p.is_drawing_open ? 'Akses Cabut sedang ditutup Panitia' : 'Cabut Maqra'"
                      >
                        <i class="fas" :class="p.is_drawing_open ? 'fa-random' : 'fa-lock'"></i> 
                        {{ p.is_drawing_open ? 'Cabut' : 'Ditutup' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TAB: RIWAYAT (Sudah Cabut) -->
            <div class="tab-pane fade" id="pills-history">
              <table class="table table-bordered table-hover text-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>Cabang & Identitas</th>
                    <th>Hasil Maqra</th>
                    <th>Hasil Paket</th>
                    <th>Waktu Cabut</th>
                    <th style="width: 70px;" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoading">
                    <td colspan="6" class="text-center py-4">
                      <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
                    </td>
                  </tr>
                  <tr v-else-if="!selectedContingent">
                    <td colspan="6" class="text-center py-4 text-muted">
                      <i class="fas fa-info-circle mr-1"></i> Silakan pilih kontingen terlebih dahulu.
                    </td>
                  </tr>
                  <tr v-else-if="filteredHistory.length === 0">
                    <td colspan="6" class="text-center py-4 text-muted">
                      Belum ada riwayat pencabutan.
                    </td>
                  </tr>
                  
                  <tr v-for="(h, index) in filteredHistory" :key="h.participant_id">
                    <td class="text-center align-middle">{{ index + 1 }}</td>
                    
                    <td class="align-middle">
                      <div class="text-primary text-xs font-weight-bold">{{ h.group_name }}</div>
                      <strong class="text-md">{{ h.participant_number }}</strong>
                      
                      <div class="mt-1" v-if="h.is_team">
                        <span class="badge badge-info mb-1"><i class="fas fa-users mr-1"></i> BEREGU</span>
                        <strong class="text-dark ml-2">{{ h.name }}</strong>
                        <div v-if="h.members" class="text-xs text-muted mt-1" style="white-space: normal; line-height: 1.3;">
                           <i class="fas fa-user-friends text-info mr-1"></i>
                           {{ Array.isArray(h.members) ? h.members.join(', ') : h.members }}
                        </div>
                      </div>
                      <div class="mt-1" v-else>
                        <span class="badge badge-secondary mb-1"><i class="fas fa-user mr-1"></i> INDIVIDU</span>
                        <span class="text-muted ml-2">{{ h.name }}</span>
                      </div>
                    </td>

                    <td class="align-middle">
                      <div v-if="h.drawing_data.event_maqra">
                        Surah <strong>{{ h.drawing_data.event_maqra.surah_name }}</strong>
                        <div class="text-xs text-muted">Ayat {{ h.drawing_data.event_maqra.start_ayah }} - {{ h.drawing_data.event_maqra.end_ayah }}</div>
                      </div>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td class="align-middle">
                      <span v-if="h.drawing_data.event_question_package" class="badge badge-primary px-2 py-1">
                        {{ h.drawing_data.event_question_package.package_number }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td class="align-middle">
                      <small class="text-muted">
                         {{ new Date(h.drawing_data.drawn_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                      </small>
                    </td>
                    <td class="text-center align-middle">
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

    <!-- MODAL PENCABUTAN KIOSK MODE (AUTO SUBMIT) -->
    <div class="modal fade kiosk-modal" id="drawModal" data-backdrop="static" data-keyboard="false">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content text-center">
          
          <div class="modal-header bg-dark text-white border-0 py-4 d-block">
            <h3 class="mb-1 text-warning">{{ activeParticipant?.group_name }}</h3>
            <div class="mb-0 text-light d-flex flex-column align-items-center justify-content-center" style="font-size: 1.2rem;">
              <div>
                <span class="font-weight-bold mr-2">{{ activeParticipant?.participant_number }}</span> 
                - {{ activeParticipant?.name }}
                <span v-if="activeParticipant?.is_team" class="badge badge-info ml-2 align-top text-sm">BEREGU</span>
              </div>
              <div v-if="activeParticipant?.is_team && activeParticipant?.members" class="text-sm text-info mt-2">
                <i class="fas fa-users mr-1"></i> 
                {{ Array.isArray(activeParticipant.members) ? activeParticipant.members.join(', ') : activeParticipant.members }}
              </div>
            </div>
          </div>

          <div class="modal-body py-5 bg-light">
            <!-- Loading Indicator -->
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
            
            <div v-if="!isModalLoading && availableMaqras.length === 0 && availablePackages.length === 0" class="alert alert-danger mt-4 text-left text-sm">
              <i class="fas fa-exclamation-triangle mr-2"></i> Bank Maqra & Soal Kosong atau belum disiapkan panitia untuk golongan ini.
            </div>
          </div>

          <div class="modal-footer justify-content-between bg-dark border-0 py-3">
             <div class="d-flex align-items-center ml-2 text-white text-sm">
                <input type="checkbox" id="disableItem" v-model="disableItemAfterDraw" class="mr-2" style="width: 16px; height: 16px;">
                <label for="disableItem" class="mb-0">Tandai Maqra/Soal telah dicabut (Tidak bisa dipilih orang lain)</label>
             </div>
             
             <button class="btn btn-light btn-sm px-4 mr-2" @click="closeDrawModal" :disabled="isSubmittingDraw">Tutup</button>
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
import { useAuthUserStore } from '../stores/AuthUserStore'
import { useMasterDataStore } from '../stores/MasterDataStore'

const authUserStore = useAuthUserStore()
const masterDataStore = useMasterDataStore()

// State Data User & Pengecekan Akses Superadmin/Admin Event
const currentUser = computed(() => authUserStore.user || {})
const isPrivilegedAdmin = computed(() => {
  const roleName = currentUser.value?.role?.name || ''
  return ['SUPERADMIN', 'ADMIN_EVENT'].includes(roleName)
})

// State Filter & Loading
const isLoading = ref(false)
const selectedRoundId = ref('1')
const selectedGroupId = ref('')
const selectedContingent = ref('')
const searchQuery = ref('')
const isDrawingOpen = ref(false) // ➕ State Status Gerbang dari API

// Mengambil nama kontingen asli dari user login
const userContingent = computed(() => {
  return authUserStore.user?.contingent_name || authUserStore.user?.district?.name || 'Kafilah Anda'
})

// State Data Peserta dari API
const pendingParticipants = ref([])
const historyParticipants = ref([])
const availableGroups = ref([])

// Filter Lokal di Frontend
const filteredPending = computed(() => {
  return pendingParticipants.value.filter(p => {
    const matchGroup = selectedGroupId.value ? p.event_group_id === selectedGroupId.value : true;
    const matchSearch = searchQuery.value ? 
      (p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
       p.participant_number.toLowerCase().includes(searchQuery.value.toLowerCase())) 
      : true;
    return matchGroup && matchSearch;
  })
})

const filteredHistory = computed(() => {
  return historyParticipants.value.filter(h => {
    const matchGroup = selectedGroupId.value ? h.event_group_id === selectedGroupId.value : true;
    const matchSearch = searchQuery.value ? 
      (h.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
       h.participant_number.toLowerCase().includes(searchQuery.value.toLowerCase())) 
      : true;
    return matchGroup && matchSearch;
  })
})

// State Modal Pencabutan
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

// Shuffle Soal
const shuffleArray = (array) => {
  let currentIndex = array.length, randomIndex;
  while (currentIndex !== 0) {
    randomIndex = Math.floor(Math.random() * currentIndex);
    currentIndex--;
    [array[currentIndex], array[randomIndex]] = [array[randomIndex], array[currentIndex]];
  }
  return array;
}

onMounted(() => {
  if (!isPrivilegedAdmin.value) {
    selectedContingent.value = userContingent.value
    loadData()
  } else {
    selectedContingent.value = ''
  }
})

const loadData = async () => {
  if (!selectedContingent.value) return 
  
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/kafilah/maqra-drawings/participants', {
      params: { 
        round_id: selectedRoundId.value,
        contingent: selectedContingent.value,
        event_group_id: selectedGroupId.value // ➕ Kirim Group ID jika ada untuk ngecek gerbang
      }
    })
    
    // ➕ Tangkap status buka/tutup gerbang (jika filter grup diset, cek gerbangnya)
    if(selectedGroupId.value && res.data.is_drawing_open !== undefined) {
       isDrawingOpen.value = res.data.is_drawing_open
    } else {
       // Reset gerbang jika tampilan all / filter grup kosong
       isDrawingOpen.value = false 
    }

    pendingParticipants.value = res.data.pending
    historyParticipants.value = res.data.history
    availableGroups.value = res.data.available_groups
  } catch (error) {
    Swal.fire('Gagal', 'Terjadi kesalahan saat mengambil antrean peserta kafilah.', 'error')
  } finally {
    isLoading.value = false
  }
}

const loadAvailableItems = async (groupId) => {
  isModalLoading.value = true
  try {
    const resItems = await axios.get('/api/v1/kafilah/maqra-drawings/available-items', {
      params: { event_group_id: groupId, round_id: selectedRoundId.value }
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

// BUKA MODAL
const openDrawModal = async (participant) => {
  // Pengecekan per-peserta
  if (!participant.is_drawing_open) {
    Swal.fire('Akses Ditutup', 'Pencabutan maqra untuk golongan ini sedang ditutup oleh Panitia.', 'warning')
    return
  }

  activeParticipant.value = participant
  finalMaqra.value = null
  finalPackage.value = null
  displayedMaqra.value = null
  isRollingMaqra.value = false
  isSubmittingDraw.value = false
  
  $('#drawModal').modal('show')
  await loadAvailableItems(participant.event_group_id)
}

const closeDrawModal = () => {
  if (isSubmittingDraw.value) return 
  clearInterval(rollInterval)
  $('#drawModal').modal('hide')
}

// PANEL VISIBILITY
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

// LOGIKA ACAK MAQRA
const startRollMaqra = () => {
  if (availableMaqras.value.length === 0) return
  isRollingMaqra.value = true
  
  let count = 0
  rollInterval = setInterval(() => {
    const randomIndex = Math.floor(Math.random() * availableMaqras.value.length)
    displayedMaqra.value = availableMaqras.value[randomIndex]
    count++
    
    if (count > 30) {
      clearInterval(rollInterval)
      isRollingMaqra.value = false
      finalMaqra.value = displayedMaqra.value
      
      if (!showPackagePanel.value && isReadyToSave.value) {
          setTimeout(() => { submitDraw() }, 800)
      }
    }
  }, 80)
}

// LOGIKA PILIH PAKET SOAL
const selectPackage = (pkg) => {
  finalPackage.value = pkg
  
  if (isReadyToSave.value) {
      setTimeout(() => { submitDraw() }, 500)
  }
}

// SUBMIT OTOMATIS KE BACKEND
const submitDraw = async () => {
  isSubmittingDraw.value = true
  try {
    await axios.post('/api/v1/kafilah/maqra-drawings/draw', {
      round_id: selectedRoundId.value,
      event_participant_id: activeParticipant.value.participant_id,
      is_team: activeParticipant.value.is_team,
      exam_type: 'tilawah', // Atau sesuaikan dengan exam type
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
    
    loadData() 
  } catch (error) {
    isSubmittingDraw.value = false
    const msg = error.response?.data?.message || 'Terjadi kesalahan saat menyimpan undian.'
    Swal.fire('Gagal', msg, 'error')
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