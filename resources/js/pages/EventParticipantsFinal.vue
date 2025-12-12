<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Pendaftaran Ulang Peserta</h1>
          <p class="mb-0 text-muted text-sm">
            Mengelola peserta yang terdaftar pada event aktif, yaitu daftar ulang.
          </p>

          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
          </p>
        </div>
      </div>

      <p v-if="!eventId" class="text-danger text-sm mt-2 mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Event belum dipilih. Silakan pilih event melalui Portal Event terlebih dahulu.
      </p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">
            <!-- LEFT -->
            <div class="d-flex flex-wrap align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-2">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <label class="mb-0 text-sm text-muted mr-3">Entri</label>

              <!-- <strong class="mr-3">|</strong>

              <label class="mb-0 mr-1 text-sm text-muted">Status Daftar Ulang</label>
              <select
                v-model="filters.reregistration_status"
                class="form-control form-control-sm w-auto mr-2"
              >
                <option value="">Semua</option>
                <option value="not_yet">Belum Hadir</option>
                <option value="verified">Terverifikasi</option>
                <option value="rejected">Diskualifikasi</option>
              </select> -->
            </div>

            <!-- RIGHT -->
            <div class="d-flex align-items-center mt-2 mt-sm-0">
              <input
                v-model="search"
                type="text"
                class="form-control form-control-sm w-auto"
                style="min-width: 240px"
                placeholder="Cari NIK / Nama / Kontingen..."
              />

              <button
                class="btn btn-sm btn-outline-danger ml-2"
                :disabled="!eventId || isLoading"
                @click="downloadKafilahPdf"
                title="Download PDF Daftar Kafilah"
              >
                <i class="fas fa-file-pdf mr-1"></i> Download PDF
              </button>
            </div>

          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 180px;">NIK</th>
                <th>Peserta</th>
                <th>Cabang / Golongan</th>
                <th style="width: 160px;">Kontingen</th>
                <!-- <th style="width: 170px;" class="text-center">Progress Lampiran</th> -->
                <th style="width: 140px;" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center py-4">Memuat data peserta event...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center py-4">
                  Belum ada peserta terdaftar untuk event ini.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Tambah Peserta Event</strong> untuk menambahkan peserta.
                  </small>
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center">{{ rowNumber(index) }}</td>

                

                <td>
                  <strong>{{ item.participant?.nik || '-' }}</strong>
                  <div v-if="item.age_year !== null && item.age_year !== undefined" class="text-xs text-muted">
                    <!-- Umur: {{ item.age_year }}T {{ item.age_month }}B {{ item.age_day }}H -->
                  </div>
                </td>

                <td>
                  <strong>{{ item.participant?.full_name || '-' }}</strong>
                  <!-- <div class="mt-1 d-flex align-items-center flex-wrap">
                    <span
                      class="badge mr-1 gender-badge"
                      :class="item.participant?.gender === 'MALE' ? 'badge-primary' : 'badge-pink'"
                      title="Jenis Kelamin"
                    >
                      <i :class="item.participant?.gender === 'MALE' ? 'fas fa-mars' : 'fas fa-venus'"></i>
                    </span>

                    <span class="badge" :class="reregistrationBadgeClass(item.reregistration_status)">
                      {{ reregistrationStatusLabel(item.reregistration_status) }}
                    </span>
                  </div> -->
                </td>

                <td>
                  <strong>{{ item.event_category?.full_name || '-' }}</strong>
                  <div class="text-xs text-muted" v-if="item.event_group">
                    <!-- Batas: {{ (item.event_group?.max_age ?? 0) - 1 }}T 11B 29H -->
                  </div>
                </td>

                <td>
                  <span class="badge badge-light border">
                    {{ item.contingent || '-' }}
                  </span>
                </td>

                <!-- <td class="text-center">
                  <div class="progress progress-sm">
                    <div
                      class="progress-bar d-flex justify-content-center align-items-center"
                      :class="progressClass(item?.participant?.lampiran_completion_percent)"
                      role="progressbar"
                      :style="{ width: (item?.participant?.lampiran_completion_percent || 0) + '%' }"
                      :aria-valuenow="item?.participant?.lampiran_completion_percent || 0"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    >
                      {{ item?.participant?.lampiran_completion_percent || 0 }}%
                    </div>
                  </div>
                </td> -->

                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <!-- LIHAT DATA -->
                    <button
                      class="btn btn-outline-primary btn-xs"
                      title="Lihat Data Peserta"
                      @click="openViewModal(item)"
                    >
                      <i class="fas fa-eye"></i>
                    </button>

                    <!-- DAFTAR ULANG (HANYA YANG SUDAH DITERIMA) -->
                    <button
                      v-if="canReRegister(item)"
                      class="btn btn-outline-warning btn-xs"
                      title="Proses Daftar Ulang"
                      @click="openReRegisterModal(item)"
                    >
                      <i class="fas fa-user-check"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total || 0 }} peserta event
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}</span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <ViewParticipantModal :selected-participant="selectedParticipant" />

    <!-- MODAL DAFTAR ULANG -->
    <ReRegisterModal
      :event-participant="selectedReRegister"
      @updated="handleReRegisterUpdated"
    />
  </section>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../stores/AuthUserStore'
import ViewParticipantModal from './ViewParticipantModal.vue'
import ReRegisterModal from './ReRegisterModal.vue'
import { reregistrationBadgeClass, reregistrationStatusLabel } from './EventParticipantHelpers'

// AUTH & EVENT
const authUserStore = useAuthUserStore()
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

// TABLE STATE
const items = ref([])
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)

const filters = ref({
  reregistration_status: '',
})

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

// VIEW MODAL
const selectedParticipant = ref(null)
const openViewModal = (row) => {
  selectedParticipant.value = row
  $('#viewParticipantModal').modal('show')
}

// DAFTAR ULANG MODAL
const selectedReRegister = ref(null)

const canReRegister = (p) => {
  // "HANYA YANG SUDAH DITERIMA": sesuaikan jika status-mu beda
  const regOk = ['verified', 'diterima', 'accepted'].includes((p?.registration_status || '').toLowerCase())
  const notDoneYet = ['not_yet', '', null].includes(p?.reregistration_status)
  return regOk && notDoneYet
}

const openReRegisterModal = (row) => {
  selectedReRegister.value = row
  $('#reRegisterModal').modal('show')
}

const handleReRegisterUpdated = () => {
  // refresh list biar status berubah di tabel
  fetchItems(meta.value.current_page || 1)
}

// HELPERS
const rowNumber = (index) => index + 1 + (meta.value.current_page - 1) * meta.value.per_page

const progressClass = (percent = 0) => {
  const p = Number(percent || 0)
  if (p <= 20) return 'bg-danger'
  if (p <= 50) return 'bg-warning'
  if (p <= 80) return 'bg-info'
  return 'bg-success'
}

// API LIST
const fetchItems = async (page = 1) => {
  if (!eventId.value) return
  isLoading.value = true
  try {
    const res = await axios.get(`/api/v1/events/${eventId.value}/participants`, {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        registration_status: 'verified',
        reregistration_status: 'verified',
      },
    })

    const paginated = res.data
    items.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error(error)
    Swal.fire('Gagal', 'Gagal memuat data peserta event.', 'error')
  } finally {
    isLoading.value = false
  }
}

// PAGINATION
const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchItems(page)
}

const downloadKafilahPdf = async () => {
  if (!eventId.value) return

  // pakai filter yang sama dengan fetchItems
  const params = new URLSearchParams({
    per_page: String(perPage.value || 10),
    search: search.value || '',
    registration_status: 'verified',
    reregistration_status: 'verified',
  })

  // download file (biar filename dari server terbaca)
  const url = `/api/v1/events/${eventId.value}/kafilah-pdf?${params.toString()}`
  window.open(url, '_blank')
}


// WATCHERS
watch(() => search.value, useDebounceFn(() => fetchItems(1), 400))
watch(() => perPage.value, () => fetchItems(1))
watch(() => ({ ...filters.value }), () => fetchItems(1))
watch(() => eventId.value, (val) => {
  if (val) fetchItems(1)
})

// MOUNTED
onMounted(() => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
  } else {
    fetchItems(1)
  }
})
</script>

<style scoped>
.badge-pink { background-color: #e83e8c; color: #fff; }
.btn-xs { padding: 2px 6px !important; font-size: 0.65rem !important; line-height: 1 !important; }
.btn-xs i { font-size: 0.55rem !important; }
.text-xs { font-size: 0.75rem; }
.gender-badge { width: 22px; text-align: center; }

.progress.progress-sm { height: 16px; font-size: 10px; }
</style>
