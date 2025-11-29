  <template>
    <section class="content-header">
      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="mb-1">Bank Data Peserta per Status</h1>
            <p class="mb-0 text-muted text-sm">
              Event:
              <strong>{{ eventInfo?.nama_event || '-' }}</strong>
              <span v-if="eventInfo?.lokasi_event">
                • {{ eventInfo.lokasi_event }}
              </span>
            </p>
          </div>
          
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- SIDEBAR STATUS -->
          <div class="col-md-3">
            <div class="card card-outline card-primary">
              <div class="card-header py-2">
                <h3 class="card-title text-sm mb-0">
                  <i class="fas fa-filter mr-1"></i> Status Pendaftaran
                </h3>
              </div>
              <div class="list-group list-group-flush">
                <button
                  v-for="s in statusList"
                  :key="s.key"
                  type="button"
                  class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                  :class="{ active: activeStatus === s.key }"
                  @click="changeStatus(s.key)"
                >
                  <span class="text-capitalize">
                    <i :class="s.icon" class="mr-1"></i>
                    {{ s.label }}
                  </span>
                  <span
                    class="badge badge-pill"
                    :class="s.badgeClass"
                  >
                    {{ statusCounts[s.key] || 0 }}
                  </span>
                </button>
              </div>
            </div>
          </div>

          <!-- MAIN TABLE -->
          <div class="col-md-9">
            <div class="card">
              <!-- HEADER: perPage + search -->
              <div class="card-header">
                <div class="row w-100">
                  <div class="col-md-6 d-flex align-items-center">
                    <label class="mb-0 mr-2 text-sm text-muted">Tampilkan</label>
                    <select
                      v-model.number="perPage"
                      class="form-control form-control-sm w-auto"
                    >
                      <option :value="10">10</option>
                      <option :value="25">25</option>
                      <option :value="50">50</option>
                      <option :value="100">100</option>
                    </select>
                    <span class="ml-2 text-sm text-muted">entri</span>
                  </div>

                  <div class="col-md-6 d-flex justify-content-end">
                    <input
                      v-model="search"
                      type="text"
                      class="form-control form-control-sm w-75"
                      placeholder="Cari nama, NIK, atau nomor HP..."
                    />
                  </div>
                </div>
              </div>

              <!-- TABLE -->
              <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-hover text-sm mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th style="width: 40px;">#</th>
                      <th>Nama Peserta</th>
                      <th>NIK</th>
                      <th>Cabang / Golongan</th>
                      <th>Asal</th>
                      <th>Status</th>
                      <th style="width: 110px;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="isLoading">
                      <td colspan="7" class="text-center">Memuat data...</td>
                    </tr>
                    <tr v-else-if="participants.length === 0">
                      <td colspan="7" class="text-center">
                        Tidak ada data peserta dengan status
                        <strong class="text-capitalize">{{ activeStatus }}</strong>.
                      </td>
                    </tr>
                    <tr
                      v-for="(p, index) in participants"
                      :key="p.id"
                    >
                      <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>

                      <td>
                        <strong>{{ p.full_name }}</strong>
                        <div class="text-xs text-muted">
                          {{ p.gender === 'MALE' ? 'Laki-laki' : 'Perempuan' }},
                          lahir {{ p.place_of_birth }}
                        </div>
                      </td>

                      <td>
                        {{ p.nik }}
                        <div class="text-xs text-muted">
                          Umur:
                          {{ p.age_year }}T
                          {{ p.age_month }}B
                          {{ p.age_day }}H
                        </div>
                      </td>

                      <td>
                        <span class="text-sm">
                          {{ p.competition_branch?.name || '-' }}
                        </span>
                        <div class="text-xs text-muted" v-if="p.competition_branch">
                          Batas:
                          {{ p.competition_branch.max_age - 1 }}T
                          11B
                          29H
                        </div>
                      </td>

                      <td>
                        <span class="text-sm">
                          {{ getAsalWilayah(p) }}
                        </span>
                      </td>

                      <td>
                        <span
                          class="badge badge-sm text-uppercase"
                          :class="statusBadgeClass(p.status_pendaftaran)"
                        >
                          {{ p.status_pendaftaran || 'bankdata' }}
                        </span>
                      </td>

                      <td class="text-center">
                        <div class="btn-group btn-group-sm">

                          <!-- LIHAT DATA -->
                          <button
                            class="btn btn-outline-primary btn-xs"
                            title="Lihat Data Peserta"
                            @click="openViewModal(p)"
                          >
                            <i class="fas fa-eye"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- FOOTER: pagination -->
              <div class="card-footer clearfix">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="text-muted text-sm">
                    Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
                    {{ meta.total || 0 }} peserta
                  </div>
                  <ul class="pagination pagination-sm m-0">
                    <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                      <a
                        class="page-link"
                        href="#"
                        @click.prevent="changePage(meta.current_page - 1)"
                      >
                        «
                      </a>
                    </li>
                    <li class="page-item disabled">
                      <span class="page-link">
                        Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
                      </span>
                    </li>
                    <li
                      class="page-item"
                      :class="{ disabled: meta.current_page === meta.last_page }"
                    >
                      <a
                        class="page-link"
                        href="#"
                        @click.prevent="changePage(meta.current_page + 1)"
                      >
                        »
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- di sini tetap bisa include modal2 yang sama dengan ParticipantList.vue:
                - Modal Tambah/Edit (participantModal)
                - Modal Lihat (viewParticipantModal)
                - Modal Mutasi, dll.
                Tinggal copy dari file sebelumnya -->
          </div>
        </div>
      </div>

      <!-- Modal Lihat Data Peserta -->
      <div class="modal fade" id="viewParticipantModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header py-2">
              <h5 class="modal-title">
                <i class="fas fa-id-card-alt mr-2"></i>
                Detail Peserta
              </h5>
              <button type="button" class="close" data-dismiss="modal">
                <span>&times;</span>
              </button>
            </div>

            <div class="modal-body" v-if="selectedParticipant">
              <div class="row">
                <!-- BIODATA -->
                
                <div class="col-md-8 mb-3">
                  <div class="card shadow-sm border">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center py-2">
                      <span class="font-weight-bold">Biodata Peserta</span>
                    </div>

                    <div class="card-body p-0">
                      <table class="table table-sm mb-0">
                        <tbody>
                          <tr>
                            <th style="width:35%;">Nama</th>
                            <td class="text-uppercase font-weight-bold">
                              {{ selectedParticipant.full_name }}
                            </td>
                          </tr>
                          <tr>
                            <th>NIK</th>
                            <td class="text-monospace">
                              {{ selectedParticipant.nik }}
                            </td>
                          </tr>
                          <tr>
                            <th>Tempat Lahir</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.place_of_birth || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Tanggal Lahir</th>
                            <td>
                              <span class="text-danger font-weight-bold mr-2">
                                {{ formatDate(selectedParticipant.date_of_birth) }}
                              </span>
                              <span v-if="selectedParticipant.age_year != null">
                                ({{ selectedParticipant.age_year }}T
                                {{ selectedParticipant.age_month }}B
                                {{ selectedParticipant.age_day }}H)
                              </span>
                            </td>
                          </tr>
                          <tr>
                            <th>Telepon</th>
                            <td>{{ selectedParticipant.phone_number || '-' }}</td>
                          </tr>
                          <tr>
                            <th>Jenis Kelamin</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.gender === 'MALE' ? 'LAKI-LAKI' : 'PEREMPUAN' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Cabang Lomba</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.competition_branch?.name || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Asal</th>
                            <td class="text-uppercase">
                              {{ getAsalWilayah(selectedParticipant) }}
                            </td>
                          </tr>
                          <tr>
                            <th>Alamat</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.address || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Pendidikan</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.education || '-' }}
                            </td>
                          </tr>
                          <!-- <tr>
                            <th>Detail Rekening</th>
                            <td>
                              {{ selectedParticipant.bank_account_number || '-' }} <br>
                              a.n <span class="text-uppercase">{{ selectedParticipant.bank_account_name || '-' }}</span><br>
                              {{ selectedParticipant.bank_name || '-' }}<br>
                            </td>
                          </tr> -->
                          <tr>
                            <th>Nomor Rekening</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.bank_account_number || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Akun Rekening</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.bank_account_name || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Bank Rekening</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.bank_name || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Kategori</th>
                            <td class="text-uppercase">
                              <!-- kalau punya field kategori sendiri bisa ganti -->
                              PESERTA INTI
                            </td>
                          </tr>
                          <tr>
                            <th>Terbit KTP</th>
                            <td class="text-danger font-weight-bold">
                              {{ formatDate(selectedParticipant.tanggal_terbit_ktp) }}
                            </td>
                          </tr>

                          <tr>
                            <th>Terbit KK</th>
                            <td class="text-danger font-weight-bold">
                              {{ formatDate(selectedParticipant.tanggal_terbit_kk) }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  
                </div>

                <!-- BERKAS + TANGGAL -->
                <div class="col-md-4">
                  <!-- BERKAS PESERTA -->
                  <div class="card shadow-sm border mb-3">
                    <div class="card-header border-0 py-2">
                      <span class="font-weight-bold">Berkas Peserta</span>
                    </div>
                    <div class="card-body p-0">
                      <div
                        v-if="selectedParticipant.photo_url"
                        class="mx-auto rounded-circle overflow-hidden border"
                        style="width:180px;height:180px;"
                      >
                        <img
                          :src="selectedParticipant.photo_url"
                          alt="Foto Peserta"
                          class="img-fluid"
                          style="object-fit:cover;width:100%;height:100%;"
                        />
                      </div>

                      <div v-else class="mx-auto text-muted" style="align-items: center; text-align: center;">
                        Tidak ada foto
                      </div>
                      <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                          <span>Foto</span>
                          <span
                            class="badge badge-pill"
                            :class="hasFileDetail('photo_url') ? 'badge-success' : 'badge-secondary'"
                            @click="openFileDetail('photo_url')"
                            style="cursor: pointer;"
                          >
                            <i :class="hasFileDetail('photo_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                            {{ hasFileDetail('photo_url') ? 'Ada' : 'Kosong' }}
                          </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                          <span>KTP</span>
                          <span
                            class="badge badge-pill"
                            :class="hasFileDetail('id_card_url') ? 'badge-success' : 'badge-secondary'"
                            @click="openFileDetail('id_card_url')"
                            style="cursor: pointer;"
                          >
                            <i :class="hasFileDetail('id_card_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                            {{ hasFileDetail('id_card_url') ? 'Ada' : 'Kosong' }}
                          </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                          <span>Kartu Keluarga</span>
                          <span
                            class="badge badge-pill"
                            :class="hasFileDetail('family_card_url') ? 'badge-success' : 'badge-secondary'"
                            @click="openFileDetail('family_card_url')"
                            style="cursor: pointer;"
                          >
                            <i :class="hasFileDetail('family_card_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                            {{ hasFileDetail('family_card_url') ? 'Ada' : 'Kosong' }}
                          </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                          <span>Buku Tabungan</span>
                          <span
                            class="badge badge-pill"
                            :class="hasFileDetail('bank_book_url') ? 'badge-success' : 'badge-secondary'"
                            @click="openFileDetail('bank_book_url')"
                            style="cursor: pointer;"
                          >
                            <i :class="hasFileDetail('bank_book_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                            {{ hasFileDetail('bank_book_url') ? 'Ada' : 'Kosong' }}
                          </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                          <span>Piagam Penghargaan</span>
                          <span
                            class="badge badge-pill"
                            :class="hasFileDetail('certificate_url') ? 'badge-success' : 'badge-secondary'"
                            @click="openFileDetail('certificate_url')"
                            style="cursor: pointer;"
                          >
                            <i :class="hasFileDetail('certificate_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                            {{ hasFileDetail('certificate_url') ? 'Ada' : 'Kosong' }}
                          </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                          <span>Berkas Lain</span>
                          <span
                            class="badge badge-pill"
                            :class="hasFileDetail('other_url') ? 'badge-success' : 'badge-secondary'"
                            @click="openFileDetail('other_url')"
                            style="cursor: pointer;"
                          >
                            <i :class="hasFileDetail('other_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                            {{ hasFileDetail('other_url') ? 'Ada' : 'Kosong' }}
                          </span>
                        </li>
                      </ul>
                    </div>
                  </div>

                  <!-- TANGGAL DATA -->
                  <div class="card shadow-sm border">
                    <div class="card-body p-0">
                      <table class="table table-sm mb-0 mx-auto text-center">
                        <tbody>
                          <tr>
                            <th>Tanggal Input Data<br></br>
                            <span class="text-right text-danger font-weight-bold">
                              {{ formatDateTime(selectedParticipant.created_at) }}
                            </span>
                            </th>
                          </tr>

                          <tr>
                            <th>Tanggal Update Data<br></br>
                            <span class="text-right text-danger font-weight-bold">
                              {{ formatDateTime(selectedParticipant.updated_at) }}
                            </span>
                            </th>
                          </tr>

                          
                        </tbody>

                      </table>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <div class="modal-footer py-2">
              <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
                Tutup
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const selectedParticipant = ref(null)
const authUserStore = useAuthUserStore()

// =========================
// EVENT INFO (dari localStorage / cookie seperti sebelumnya)
// =========================
const eventInfo = ref(null)
const eventId = ref(null)

const getEventInfoFromStorage = () => {
  let raw = ''
  try {
    raw = localStorage.getItem('event_data') || ''
  } catch (e) {}

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
      eventInfo.value = JSON.parse(raw)
      eventId.value = eventInfo.value.id || null
    } catch (e) {
      console.error('Gagal parse event_data:', e)
      eventInfo.value = null
      eventId.value = null
    }
  }
}

const formatDate = (val) => {
  if (!val) return '-'
  const str = String(val).substring(0, 10)
  const [year, month, day] = str.split('-')
  const bulanIndo = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
  ]
  return `${day} ${bulanIndo[parseInt(month, 10) - 1]} ${year}`
}

const formatDateTime = (value) => {
  if (!value) return '-'

  const d = new Date(value)
  if (isNaN(d)) return '-'

  const pad = (n) => (n < 10 ? '0' + n : n)

  const day = pad(d.getDate())
  const month = pad(d.getMonth() + 1)
  const year = d.getFullYear()

  const hour = pad(d.getHours())
  const minute = pad(d.getMinutes())
  const second = pad(d.getSeconds())

  return `${day}-${month}-${year} ${hour}:${minute}:${second}`
}


const toDateInput = (val) => {
  if (!val) return ''
  if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val
  return String(val).substring(0, 10)
}

// =========================
// STATUS LIST + COUNTS
// =========================
const statusList = [
  {
    key: 'proses',
    label: 'Proses',
    badgeClass: 'badge-warning',
    icon: 'fas fa-hourglass-half',
  },
  {
    key: 'diterima',
    label: 'Diterima',
    badgeClass: 'badge-success',
    icon: 'fas fa-check-circle',
  },
  {
    key: 'perbaiki',
    label: 'Perbaiki',
    badgeClass: 'badge-info',
    icon: 'fas fa-tools',
  },
  {
    key: 'mundur',
    label: 'Mundur',
    badgeClass: 'badge-secondary',
    icon: 'fas fa-sign-out-alt',
  },
  {
    key: 'tolak',
    label: 'Tolak',
    badgeClass: 'badge-danger',
    icon: 'fas fa-times-circle',
  },
]

const activeStatus = ref('proses') // default tab
const statusCounts = ref({
  proses: 0,
  diterima: 0,
  perbaiki: 0,
  mundur: 0,
  tolak: 0,
})

// helper badge status di table
const statusBadgeClass = (status) => {
  switch (status) {
    case 'proses':
      return 'badge-warning'
    case 'diterima':
      return 'badge-success'
    case 'perbaiki':
      return 'badge-info'
    case 'mundur':
      return 'badge-secondary'
    case 'tolak':
      return 'badge-danger'
    default:
      return 'badge-light'
  }
}

// =========================
// LIST & PAGINATION STATE
// =========================
const participants = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})
const perPage = ref(10)
const search = ref('')
const isLoading = ref(false)

// =========================
// HELPER ASAL WILAYAH
// (silakan sesuaikan dengan versi di ParticipantList.vue Anda)
// =========================
const getAsalWilayah = (p) => {
  const te = eventInfo.value?.tingkat_event
  if (!p) return '-'

  if (te === 'provinsi') {
    return p.regency?.name || '-'
  }
  if (te === 'kabupaten_kota') {
    return p.district?.name || '-'
  }
  if (te === 'kecamatan') {
    return p.village?.name || p.district?.name || '-'
  }
  return p.province?.name || '-'
}

// =========================
// FETCH LIST PESERTA (per status)
// =========================
const fetchParticipants = async (page = 1) => {
  if (!eventId.value) {
    participants.value = []
    return
  }

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/participants', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        event_id: eventId.value,
        status_pendaftaran: activeStatus.value, // ⬅ filter status di backend
      },
    })

    participants.value = res.data.data || []
    meta.value = {
      current_page: res.data.current_page,
      per_page: res.data.per_page,
      total: res.data.total,
      from: res.data.from,
      to: res.data.to,
      last_page: res.data.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat peserta:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    isLoading.value = false
  }
}

// =========================
// FETCH STATUS COUNTS
// backend disarankan sediakan endpoint:
// GET /api/v1/participants/status-counts?event_id=XX
// return: { proses: 10, diterima: 5, perbaiki: 2, mundur: 1, tolak: 3 }
// =========================
const fetchStatusCounts = async () => {
  if (!eventId.value) return

  try {
    const res = await axios.get('/api/v1/get/participants/status-counts', {
      params: { event_id: eventId.value },
    })

    statusCounts.value = {
      proses: res.data.proses || 0,
      diterima: res.data.diterima || 0,
      perbaiki: res.data.perbaiki || 0,
      mundur: res.data.mundur || 0,
      tolak: res.data.tolak || 0,
    }
  } catch (error) {
    console.error('Gagal memuat rekap status peserta:', error)
  }
}

// =========================
// HANDLER UI
// =========================
const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchParticipants(page)
}

const changeStatus = (key) => {
  if (activeStatus.value === key) return
  activeStatus.value = key
  meta.value.current_page = 1
  fetchParticipants(1)
}


// cek apakah sebuah field lampiran sudah terisi file / url
const hasFile = (field) => {
  return !!(files.value[field] || form.value[field])
}

const hasFileDetail = (field) => {
  if (!selectedParticipant.value) return false
  return !!selectedParticipant.value[field]
}

// stub: sesuaikan dengan ParticipantList.vue Anda
const openViewModal = (p) => {
  selectedParticipant.value = p
  $('#viewParticipantModal').modal('show')
}

const openFileDetail = (field) => {
  if (!selectedParticipant.value) return
  const url = selectedParticipant.value[field]
  if (!url) return
  window.open(url, '_blank')
}

// =========================
// WATCHERS
// =========================
watch(
  () => search.value,
  useDebounceFn(() => fetchParticipants(1), 400)
)

watch(
  () => perPage.value,
  () => {
    fetchParticipants(1)
  }
)

watch(
  () => activeStatus.value,
  () => {
    // setiap ganti status, refresh juga rekap
    fetchStatusCounts()
  }
)

// =========================
// MOUNTED
// =========================
onMounted(async () => {
  getEventInfoFromStorage()
  await fetchStatusCounts()
  await fetchParticipants()
})
</script>

<style scoped>
.btn-xs {
  padding: 2px 5px !important;
  font-size: 0.65rem !important;
  line-height: 1 !important;
}

.btn-xs i {
  font-size: 0.55rem !important;
}

.list-group-item.active {
  background-color: #007bff;
  border-color: #007bff;
  color: #fff;
}

.list-group-item.active .badge {
  background-color: rgba(255, 255, 255, 0.3);
}
</style>
