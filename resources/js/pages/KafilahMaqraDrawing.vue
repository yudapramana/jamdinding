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

              <span class="badge badge-light border text-secondary px-2 py-1 text-xs" v-if="!isPrivilegedAdmin">
                <i class="fas fa-map-marker-alt text-primary mr-1"></i> {{ userContingent }}
              </span>

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
                  <option v-for="group in availableGroups" :key="group.id" :value="String(group.id)">
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

              <!-- STATUS GERBANG -->
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
                    <th style="width: 110px;">No. Tampil</th>
                    <th>Identitas Peserta / Regu</th>
                    <th style="width: 120px;" class="text-center">Aksi</th>
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

                  <template v-else>
                    <tr v-for="(p, index) in filteredPending" :key="(p.is_team ? 't-' : 'p-') + p.participant_id">
                      <td class="text-center align-middle">{{ index + 1 }}</td>

                      <td class="align-middle">
                        <div class="text-primary text-xs font-weight-bold mb-1">{{ p.group_name }}</div>
                        <span class="badge" :class="p.is_drawing_open ? 'badge-success' : 'badge-danger'" style="font-size: 0.65rem;">
                          <i class="fas" :class="p.is_drawing_open ? 'fa-lock-open' : 'fa-lock'"></i>
                          {{ p.is_drawing_open ? 'Gerbang Terbuka' : 'Gerbang Ditutup' }}
                        </span>
                      </td>

                      <td class="font-weight-bold text-md align-middle">
                        <template v-if="isRegistered(p)">{{ p.participant_number || '-' }}</template>
                        <span v-else class="badge badge-warning text-dark" style="font-size: 0.65rem;">
                          <i class="fas fa-exclamation-triangle mr-1"></i> No. urut kosong
                        </span>
                      </td>

                      <td class="align-middle">
                        <template v-if="p.is_team">
                          <span class="badge badge-info mb-1"><i class="fas fa-users mr-1"></i> BEREGU</span>
                          <strong class="text-dark ml-2">{{ p.name }}</strong>
                          <div v-if="p.members" class="text-xs text-muted mt-1" style="white-space: normal; line-height: 1.3;">
                            <i class="fas fa-user-friends text-info mr-1"></i>
                            {{ formatMembers(p.members) }}
                          </div>
                        </template>
                        <template v-else>
                          <span class="badge badge-secondary mb-1"><i class="fas fa-user mr-1"></i> INDIVIDU</span>
                          <span class="text-dark font-weight-bold ml-2">{{ p.name }}</span>
                        </template>
                      </td>

                      <!-- TOMBOL AKSI: Disinkronkan dengan style Panitera -->
                      <td class="text-center align-middle">
                        <button
                          class="btn btn-xs px-3 font-weight-bold"
                          :class="actionState(p).btnClass"
                          :disabled="!actionState(p).canDraw"
                          :title="actionState(p).title"
                          @click="openDrawModal(p)"
                        >
                          <i class="fas mr-1" :class="actionState(p).icon"></i>
                          {{ actionState(p).label }}
                        </button>
                      </td>
                    </tr>
                  </template>
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

                  <template v-else>
                    <tr v-for="(h, index) in filteredHistory" :key="(h.is_team ? 't-' : 'p-') + h.participant_id">
                      <td class="text-center align-middle">{{ index + 1 }}</td>

                      <td class="align-middle">
                        <div class="text-primary text-xs font-weight-bold">{{ h.group_name }}</div>
                        <strong class="text-md">{{ h.participant_number || '-' }}</strong>

                        <div class="mt-1" v-if="h.is_team">
                          <span class="badge badge-info mb-1"><i class="fas fa-users mr-1"></i> BEREGU</span>
                          <strong class="text-dark ml-2">{{ h.name }}</strong>
                          <div v-if="h.members" class="text-xs text-muted mt-1" style="white-space: normal; line-height: 1.3;">
                            <i class="fas fa-user-friends text-info mr-1"></i>
                            {{ formatMembers(h.members) }}
                          </div>
                        </div>
                        <div class="mt-1" v-else>
                          <span class="badge badge-secondary mb-1"><i class="fas fa-user mr-1"></i> INDIVIDU</span>
                          <span class="text-muted ml-2">{{ h.name }}</span>
                        </div>
                      </td>

                      <td class="align-middle">
                        <div v-if="h.drawing_data?.event_maqra">
                          Surah <strong>{{ h.drawing_data.event_maqra.surah_name }}</strong>
                          <div class="text-xs text-muted">Ayat {{ h.drawing_data.event_maqra.start_ayah }} - {{ h.drawing_data.event_maqra.end_ayah }}</div>
                        </div>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td class="align-middle">
                        <span v-if="h.drawing_data?.event_question_package" class="badge badge-primary px-2 py-1">
                          {{ h.drawing_data.event_question_package.package_number }}
                        </span>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td class="align-middle">
                        <small class="text-muted">{{ formatTime(h.drawing_data?.drawn_at) }}</small>
                      </td>
                      <td class="text-center align-middle">
                        <button class="btn btn-outline-secondary btn-xs" title="Cetak Struk">
                          <i class="fas fa-print"></i>
                        </button>
                      </td>
                    </tr>
                  </template>
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
            <h3 class="mb-1 text-warning">{{ activeParticipant?.group_name }}</h3>
            <div class="mb-0 text-light d-flex flex-column align-items-center justify-content-center" style="font-size: 1.2rem;">
              <div>
                <span class="font-weight-bold mr-2">{{ activeParticipant?.participant_number }}</span>
                - {{ activeParticipant?.name }}
                <span v-if="activeParticipant?.is_team" class="badge badge-info ml-2 align-top text-sm">BEREGU</span>
              </div>
              <div v-if="activeParticipant?.is_team && activeParticipant?.members" class="text-sm text-info mt-2">
                <i class="fas fa-users mr-1"></i>
                {{ formatMembers(activeParticipant.members) }}
              </div>
            </div>
          </div>

          <div class="modal-body py-5 bg-light">
            <!-- Loading Indicator -->
            <div v-if="isModalLoading || isSubmittingDraw" class="py-5">
              <i class="fas fa-circle-notch fa-spin fa-3x text-primary mb-3"></i>
              <h5>{{ isSubmittingDraw ? 'Memverifikasi & Menyimpan Hasil...' : 'Mempersiapkan Bank Soal...' }}</h5>
            </div>

            <div v-else class="row justify-content-center">

              <!-- PANEL MAQRA (AMPLOP) -->
              <div v-if="showMaqraPanel" class="col-md-5 mx-2 mb-3">
                <div class="card border-primary shadow" style="min-height: 250px;">
                  <div class="card-header bg-primary text-white font-weight-bold">
                    MAQRA BACAAN
                    <small v-if="!finalMaqra" class="d-block font-weight-normal">Silakan pilih salah satu amplop</small>
                  </div>
                  <div class="card-body">

                    <!-- AMPLOP BELUM DIPILIH -->
                    <div v-if="!finalMaqra" class="d-flex flex-wrap justify-content-center gap-2 envelope-grid">
                      <button
                        v-for="(m, idx) in availableMaqras"
                        :key="m.id"
                        class="btn btn-outline-primary envelope-btn m-1"
                        :disabled="isSubmittingDraw || isDrawSuccess"
                        @click="selectMaqra(m)"
                      >
                        <i class="fas fa-envelope fa-2x"></i>
                        <span class="envelope-no">{{ idx + 1 }}</span>
                      </button>
                    </div>

                    <!-- AMPLOP TERPILIH, TAPI BELUM DISUBMIT KE SERVER -->
                    <div v-else-if="finalMaqra && !isDrawSuccess" class="h-100 d-flex flex-column justify-content-center align-items-center bg-secondary text-white rounded p-4">
                      <i class="fas fa-envelope fa-3x mb-3"></i>
                      <h4 class="font-weight-bold mb-1">Amplop Terpilih</h4>
                    </div>

                    <!-- KETIKA BERHASIL -> TAMPILKAN ISINYA -->
                    <div v-else class="h-100 d-flex flex-column justify-content-center align-items-center bg-success text-white rounded p-4 reveal-anim">
                      <i class="fas fa-envelope-open-text fa-3x mb-3"></i>
                      <h4 class="font-weight-bold mb-1">Surah {{ finalMaqra.surah_name }}</h4>
                      <h5 class="mb-0">Ayat {{ finalMaqra.start_ayah }} - {{ finalMaqra.end_ayah }}</h5>
                      <span v-if="finalMaqra.juz" class="badge badge-light mt-2 text-dark">Juz {{ finalMaqra.juz }}</span>
                    </div>

                  </div>
                </div>
              </div>

              <!-- PANEL PAKET SOAL (AMPLOP) -->
              <div v-if="showPackagePanel && isMaqraStepFinished" class="col-md-5 mx-2 mb-3">
                <div class="card border-warning shadow" style="min-height: 250px;">
                  <div class="card-header bg-warning font-weight-bold text-dark">
                    PAKET SOAL
                    <small v-if="!finalPackage" class="d-block font-weight-normal">Silakan pilih salah satu amplop</small>
                  </div>
                  <div class="card-body">

                    <!-- AMPLOP BELUM DIPILIH -->
                    <div v-if="!finalPackage" class="d-flex flex-wrap justify-content-center gap-2 envelope-grid">
                      <button
                        v-for="(pkg, idx) in availablePackages"
                        :key="pkg.id"
                        class="btn btn-outline-secondary envelope-btn m-1"
                        :disabled="isSubmittingDraw || isDrawSuccess"
                        @click="selectPackage(pkg)"
                      >
                        <i class="fas fa-envelope fa-2x"></i>
                        <span class="envelope-no">{{ idx + 1 }}</span>
                      </button>
                    </div>

                    <!-- AMPLOP TERPILIH, TAPI BELUM DISUBMIT -->
                    <div v-else-if="finalPackage && !isDrawSuccess" class="h-100 d-flex flex-column justify-content-center align-items-center bg-secondary text-white rounded p-4">
                      <i class="fas fa-envelope fa-3x mb-3"></i>
                      <h4 class="font-weight-bold mb-1">Amplop Terpilih</h4>
                    </div>

                    <!-- KETIKA BERHASIL -> TAMPILKAN ISINYA -->
                    <div v-else class="h-100 d-flex flex-column justify-content-center align-items-center bg-success text-white rounded p-4 reveal-anim">
                      <i class="fas fa-envelope-open-text fa-3x mb-3"></i>
                      <h3 class="font-weight-bold mb-0">{{ finalPackage.package_number }}</h3>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <div v-if="!isModalLoading && !isSubmittingDraw && availableMaqras.length === 0 && availablePackages.length === 0" class="alert alert-danger mt-4 text-left text-sm">
              <i class="fas fa-exclamation-triangle mr-2"></i> Bank Maqra & Soal kosong atau belum disiapkan panitia untuk golongan ini.
            </div>
          </div>

          <div class="modal-footer justify-content-between bg-dark border-0 py-3">
            <div class="d-flex align-items-center ml-2 text-white text-sm">
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
const isDrawingOpen = ref(false)

const userContingent = computed(() => {
  return authUserStore.user?.contingent_name || authUserStore.user?.district?.name || 'Kafilah Anda'
})

// State Data Peserta dari API
const pendingParticipants = ref([])
const historyParticipants = ref([])
const availableGroups = ref([])

// ===== HELPER =====
const isRegistered = (p) => {
  if (typeof p.is_registered === 'boolean') return p.is_registered
  return p.branch_sequence !== null && p.branch_sequence !== undefined && p.branch_sequence !== ''
}

// ⭐️ ACTION STATE SINKRON DENGAN PANITERA
const actionState = (p) => {
  if (!isRegistered(p)) {
    return {
      canDraw: false,
      btnClass: 'btn-warning text-dark',
      icon: 'fa-user-clock', 
      label: 'Belum Daftar',
      title: 'Nomor urut cabang belum diisi. Hubungi panitia pendaftaran.'
    }
  }
  if (!p.is_drawing_open) {
    return {
      canDraw: false,
      btnClass: 'btn-secondary',
      icon: 'fa-lock',
      label: 'Ditutup',
      title: 'Akses cabut sedang ditutup Panitia'
    }
  }
  return {
    canDraw: true,
    btnClass: 'btn-success',
    icon: 'fa-random',
    label: 'Cabut',
    title: 'Cabut Maqra'
  }
}

const formatMembers = (members) => (Array.isArray(members) ? members.join(', ') : members)

const formatTime = (val) => {
  if (!val) return '-'
  return new Date(val).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

const matchesFilter = (item) => {
  const matchGroup = selectedGroupId.value
    ? String(item.event_group_id) === String(selectedGroupId.value)
    : true
  const q = searchQuery.value.trim().toLowerCase()
  const matchSearch = q
    ? (String(item.name || '').toLowerCase().includes(q) ||
       String(item.participant_number || '').toLowerCase().includes(q))
    : true
  return matchGroup && matchSearch
}

const filteredPending = computed(() => pendingParticipants.value.filter(matchesFilter))
const filteredHistory = computed(() => historyParticipants.value.filter(matchesFilter))

// State Modal Pencabutan
const isModalLoading = ref(false)
const isSubmittingDraw = ref(false)
const isDrawSuccess = ref(false) // ⭐️ STATE BARU (Mencegah isi bocor)
const activeParticipant = ref(null)
const availableMaqras = ref([])
const availablePackages = ref([])
const disableItemAfterDraw = ref(true)

// Hasil pilihan amplop
const finalMaqra = ref(null)
const finalPackage = ref(null)

// Acak urutan amplop (Fisher-Yates) agar posisi isi tidak bisa ditebak
const shuffleArray = (array) => {
  const arr = [...array]
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]]
  }
  return arr
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
        event_group_id: selectedGroupId.value
      }
    })

    isDrawingOpen.value = selectedGroupId.value ? !!res.data.is_drawing_open : false

    pendingParticipants.value = res.data.pending || []
    historyParticipants.value = res.data.history || []
    availableGroups.value = res.data.available_groups || []
  } catch (error) {
    Swal.fire('Gagal', 'Terjadi kesalahan saat mengambil antrean peserta kafilah.', 'error')
  } finally {
    isLoading.value = false
  }
}

const loadAvailableItems = async (groupId) => {
  isModalLoading.value = true
  availableMaqras.value = []
  availablePackages.value = []
  try {
    const resItems = await axios.get('/api/v1/kafilah/maqra-drawings/available-items', {
      params: { event_group_id: groupId, round_id: selectedRoundId.value }
    })
    availableMaqras.value = shuffleArray(resItems.data.maqras || [])
    availablePackages.value = shuffleArray(resItems.data.packages || [])
  } catch (error) {
    Swal.fire('Gagal', 'Tidak dapat mengambil bank Maqra & Soal', 'error')
  } finally {
    isModalLoading.value = false
  }
}

// BUKA MODAL
const openDrawModal = async (participant) => {
  if (!isRegistered(participant)) {
    Swal.fire('Belum Daftar', 'Nomor urut cabang peserta/regu ini belum diisi. Selesaikan pendaftaran terlebih dahulu.', 'warning')
    return
  }
  if (!participant.is_drawing_open) {
    Swal.fire('Akses Ditutup', 'Pencabutan maqra untuk golongan ini sedang ditutup oleh Panitia.', 'warning')
    return
  }

  activeParticipant.value = participant
  finalMaqra.value = null
  finalPackage.value = null
  isSubmittingDraw.value = false
  isDrawSuccess.value = false

  $('#drawModal').modal('show')
  await loadAvailableItems(participant.event_group_id)
}

const closeDrawModal = () => {
  if (isSubmittingDraw.value) return
  $('#drawModal').modal('hide')
}

// PANEL VISIBILITY
const showMaqraPanel = computed(() => availableMaqras.value.length > 0)
const showPackagePanel = computed(() => availablePackages.value.length > 0)

const isMaqraStepFinished = computed(() => {
  if (!showMaqraPanel.value) return true
  return finalMaqra.value !== null
})

const isReadyToSave = computed(() => {
  if (!showMaqraPanel.value && !showPackagePanel.value) return false
  if (showMaqraPanel.value && !finalMaqra.value) return false
  if (showPackagePanel.value && !finalPackage.value) return false
  return true
})


// ⭐️ LOGIKA PILIH AMPLOP MAQRA (AUTO SUBMIT)
const selectMaqra = (maqra) => {
  if (finalMaqra.value || isSubmittingDraw.value || isDrawSuccess.value) return
  finalMaqra.value = maqra
  
  if (!showPackagePanel.value && isReadyToSave.value) {
    submitDraw()
  }
}

// ⭐️ LOGIKA PILIH AMPLOP PAKET SOAL (AUTO SUBMIT)
const selectPackage = (pkg) => {
  if (finalPackage.value || isSubmittingDraw.value || isDrawSuccess.value) return
  finalPackage.value = pkg
  
  if (isReadyToSave.value) {
    submitDraw()
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
      exam_type: 'tilawah', // Sesuaikan dengan exam type
      event_maqra_id: finalMaqra.value?.id ?? null,
      event_question_package_id: finalPackage.value?.id ?? null,
      disable_item: disableItemAfterDraw.value
    })

    isSubmittingDraw.value = false
    isDrawSuccess.value = true // ⭐️ Buka isi Amplop!

    loadData() // Refresh tabel di background
  } catch (error) {
    isSubmittingDraw.value = false
    
    // ⭐️ RESET JIKA GAGAL (Tabrakan Amplop)
    finalMaqra.value = null
    finalPackage.value = null
    isDrawSuccess.value = false

    const msg = error.response?.data?.message || 'Terjadi kesalahan saat menyimpan undian.'
    await Swal.fire('Gagal Tersimpan', msg, 'error')

    // Refresh daftar amplop jika diambil orang lain (422)
    if (error.response?.status === 422 && activeParticipant.value) {
      await loadAvailableItems(activeParticipant.value.event_group_id)
    }
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

/* Amplop */
.envelope-grid {
  max-height: 320px;
  overflow-y: auto;
}

.envelope-btn {
  position: relative;
  width: 78px;
  padding: 14px 0 18px;
  transition: transform .15s ease, box-shadow .15s ease;
}

.envelope-btn:hover:not(:disabled) {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 4px 12px rgba(0,0,0,.15);
}

.envelope-no {
  position: absolute;
  bottom: 2px;
  left: 0;
  right: 0;
  font-size: .7rem;
  font-weight: bold;
}

.reveal-anim {
  animation: reveal .4s ease-out;
}

@keyframes reveal {
  0% { transform: scale(0.85); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
</style>