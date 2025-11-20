<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Data Event MTQ</h1>
          <p class="mb-0 text-muted text-sm">
            Kelola konfigurasi event Musabaqah Tilawatil Qur'an.
          </p>
        </div>
        <button
          v-if="isSuperadmin"
          class="btn btn-primary btn-sm"
          @click="openCreateModal"
        >
          <i class="fas fa-plus mr-1"></i>
          Tambah Event
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <input
            v-model="search"
            type="text"
            class="form-control form-control-sm w-50"
            placeholder="Cari nama event, aplikasi, atau lokasi..."
          />
          <span v-if="!isSuperadmin && authUserStore.eventData" class="text-muted text-sm">
            Event aktif: <strong>{{ authUserStore.eventData.nama_event }}</strong>
          </span>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Nama Event</th>
                <th>Nama Aplikasi</th>
                <th>Lokasi</th>
                <th>Periode</th>
                <th>Tingkat</th>
                <th>Status</th>
                <th style="width: 90px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="8" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="events.length === 0">
                <td colspan="8" class="text-center">Tidak ada data event.</td>
              </tr>
              <tr v-for="(event, index) in events" :key="event.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>
                  <strong>{{ event.nama_event }}</strong>
                  <div class="text-xs text-muted">
                    key: <code>{{ event.event_key }}</code>
                  </div>
                </td>
                <td>{{ event.nama_aplikasi }}</td>
                <td>{{ event.lokasi_event || '-' }}</td>
                <td>
                  {{ formatDate(event.tanggal_mulai) }}
                  &ndash;
                  {{ formatDate(event.tanggal_selesai) }}
                </td>
                <td>
                  <span class="badge badge-info text-uppercase">
                    {{ event.tingkat_event }}
                  </span>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="event.is_active ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ event.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-warning"
                      title="Edit"
                      @click="openEditModal(event)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      v-if="isSuperadmin"
                      class="btn btn-outline-danger"
                      title="Hapus"
                      @click="deleteEvent(event)"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} event
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
    </div>

    <!-- Modal Tambah/Edit Event -->
    <div class="modal fade" id="eventModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="eventModalLabel">
              <i class="fas fa-calendar-alt mr-2"></i>
              {{ isEdit ? 'Edit Event' : 'Tambah Event' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <form @submit.prevent="submitForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1">Event Key</label>
                    <input
                      v-model="form.event_key"
                      class="form-control form-control-sm"
                      :readonly="isEdit"
                      required
                      placeholder="Contoh: MTQ_KAB_PESISIRSELATAN_2027"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Event</label>
                    <input
                      v-model="form.nama_event"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Aplikasi</label>
                    <input
                      v-model="form.nama_aplikasi"
                      class="form-control form-control-sm"
                      required
                      placeholder="Contoh: e-MTQ Pesisir Selatan"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Lokasi Event</label>
                    <input
                      v-model="form.lokasi_event"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Painan, Kab. Pesisir Selatan"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Tagline</label>
                    <input
                      v-model="form.tagline"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Merajut Ukhuwah dengan Kalam Ilahi"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Token Hasil Penilaian</label>
                    <input
                      v-model="form.token_hasil_penilaian"
                      class="form-control form-control-sm"
                      placeholder="Opsional: token untuk publikasi hasil"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Mulai</label>
                    <input
                      v-model="form.tanggal_mulai"
                      type="date"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Selesai</label>
                    <input
                      v-model="form.tanggal_selesai"
                      type="date"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Batas Umur Peserta</label>
                    <input
                      v-model="form.tanggal_batas_umur"
                      type="date"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Tingkat Event</label>
                    <select
                      v-model="form.tingkat_event"
                      class="form-control form-control-sm"
                      required
                    >
                      <option value="nasional">Nasional</option>
                      <option value="provinsi">Provinsi</option>
                      <option value="kabupaten_kota">Kabupaten/Kota</option>
                      <option value="kecamatan">Kecamatan</option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Logo Event (URL / path)</label>
                    <input
                      v-model="form.logo_event"
                      class="form-control form-control-sm"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Logo Sponsor 1</label>
                    <input
                      v-model="form.logo_sponsor_1"
                      class="form-control form-control-sm"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Logo Sponsor 2</label>
                    <input
                      v-model="form.logo_sponsor_2"
                      class="form-control form-control-sm"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Logo Sponsor 3</label>
                    <input
                      v-model="form.logo_sponsor_3"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Status</label>
                    <select
                      v-model="form.is_active"
                      class="form-control form-control-sm"
                    >
                      <option :value="true">Aktif</option>
                      <option :value="false">Nonaktif</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="text-end mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="isSubmitting"
                >
                  <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-save mr-1"></i>
                  Simpan
                </button>
              </div>
            </form>
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

const authUserStore = useAuthUserStore()

// ===== ROLE CHECK =====
const isSuperadmin = computed(() => {
  return authUserStore.user?.role?.slug === 'superadmin'
})

// ===== LIST DATA =====
const events = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})
const search = ref('')
const isLoading = ref(false)

// ===== FORM / MODAL =====
const isEdit = ref(false)
const isSubmitting = ref(false)
const form = ref({
  id: null,
  event_key: '',
  nama_event: '',
  nama_aplikasi: '',
  lokasi_event: '',
  tagline: '',
  token_hasil_penilaian: '',
  tanggal_mulai: '',
  tanggal_selesai: '',
  tanggal_batas_umur: '',
  logo_event: '',
  logo_sponsor_1: '',
  logo_sponsor_2: '',
  logo_sponsor_3: '',
  tingkat_event: 'kabupaten_kota',
  is_active: true,
})

// Helper: jangan pakai new Date() untuk menghindari +1/-1 hari
const formatDate = (val) => {
  if (!val) return '-'
  const str = String(val).substring(0, 10)
  const [year, month, day] = str.split('-')
  const bulanIndo = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
  ]
  return `${day} ${bulanIndo[parseInt(month) - 1]} ${year}`
}

// untuk input type="date": pastikan format YYYY-MM-DD
const toDateInput = (val) => {
  if (!val) return ''
  if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val
  return String(val).substring(0, 10)
}

const fetchEvents = async (page = 1) => {
  isLoading.value = true

  try {
    const params = {
      page,
      search: search.value,
    }

    // Kalau BUKAN superadmin → kirim event_id dari localStorage
    if (!isSuperadmin.value && authUserStore.eventData.id) {
      params.event_id = authUserStore.eventData.id
    }

    const res = await axios.get('/api/v1/events', { params })

    events.value = res.data.data || []
    meta.value = {
      current_page: res.data.current_page,
      per_page: res.data.per_page,
      total: res.data.total,
      from: res.data.from,
      to: res.data.to,
      last_page: res.data.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat data event:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchEvents(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    event_key: '',
    nama_event: '',
    nama_aplikasi: '',
    lokasi_event: '',
    tagline: '',
    token_hasil_penilaian: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    tanggal_batas_umur: '',
    logo_event: '',
    logo_sponsor_1: '',
    logo_sponsor_2: '',
    logo_sponsor_3: '',
    tingkat_event: 'kabupaten_kota',
    is_active: true,
  }
  $('#eventModal').modal('show')
}

const openEditModal = (event) => {
  isEdit.value = true
  form.value = {
    id: event.id,
    event_key: event.event_key,
    nama_event: event.nama_event,
    nama_aplikasi: event.nama_aplikasi,
    lokasi_event: event.lokasi_event,
    tagline: event.tagline,
    token_hasil_penilaian: event.token_hasil_penilaian,
    tanggal_mulai: toDateInput(event.tanggal_mulai),
    tanggal_selesai: toDateInput(event.tanggal_selesai),
    tanggal_batas_umur: toDateInput(event.tanggal_batas_umur),
    logo_event: event.logo_event,
    logo_sponsor_1: event.logo_sponsor_1,
    logo_sponsor_2: event.logo_sponsor_2,
    logo_sponsor_3: event.logo_sponsor_3,
    tingkat_event: event.tingkat_event || 'kabupaten_kota',
    is_active: !!event.is_active,
  }
  $('#eventModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    event_key: form.value.event_key,
    nama_event: form.value.nama_event,
    nama_aplikasi: form.value.nama_aplikasi,
    lokasi_event: form.value.lokasi_event,
    tagline: form.value.tagline,
    token_hasil_penilaian: form.value.token_hasil_penilaian,
    tanggal_mulai: form.value.tanggal_mulai || null,
    tanggal_selesai: form.value.tanggal_selesai || null,
    tanggal_batas_umur: form.value.tanggal_batas_umur || null,
    logo_event: form.value.logo_event,
    logo_sponsor_1: form.value.logo_sponsor_1,
    logo_sponsor_2: form.value.logo_sponsor_2,
    logo_sponsor_3: form.value.logo_sponsor_3,
    tingkat_event: form.value.tingkat_event,
    is_active: form.value.is_active,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/events/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/events', payload)
    }

    $('#eventModal').modal('hide')
    fetchEvents(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan event:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan event.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteEvent = async (event) => {
  if (!confirm(`Yakin ingin menghapus event "${event.nama_event}"?`)) {
    return
  }

  try {
    await axios.delete(`/api/v1/events/${event.id}`)
    fetchEvents(1)
  } catch (error) {
    console.error('Gagal menghapus event:', error)
    alert(error.response?.data?.message || 'Gagal menghapus event.')
  }
}

watch(
  () => search.value,
  useDebounceFn(() => fetchEvents(1), 400)
)

onMounted(() => {
  fetchEvents()
})
</script>
