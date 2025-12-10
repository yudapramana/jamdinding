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
            Event aktif: <strong>{{ authUserStore.eventData.event_name }}</strong>
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
                  <strong>{{ event.event_name }}</strong>
                  <div class="text-xs text-muted">
                    key: <code>{{ event.event_key }}</code>
                  </div>
                </td>
                <td>{{ event.app_name }}</td>
                <td>{{ event.event_location || '-' }}</td>
                <td>
                  {{ formatDate(event.start_date) }}
                  &ndash;
                  {{ formatDate(event.end_date) }}
                </td>
                <td>
                  <span class="badge badge-info text-uppercase">
                    {{ event.event_level }}
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
                      v-model="form.event_name"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Aplikasi</label>
                    <input
                      v-model="form.app_name"
                      class="form-control form-control-sm"
                      required
                      placeholder="Contoh: e-MTQ Pesisir Selatan"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Lokasi Event</label>
                    <input
                      v-model="form.event_location"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Painan, Kab. Pesisir Selatan"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">tagline</label>
                    <input
                      v-model="form.event_tagline"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Merajut Ukhuwah dengan Kalam Ilahi"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Token Hasil Penilaian</label>
                    <input
                      v-model="form.assessment_token"
                      class="form-control form-control-sm"
                      placeholder="Opsional: token untuk publikasi hasil"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Mulai</label>
                    <input
                      v-model="form.start_date"
                      type="date"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Selesai</label>
                    <input
                      v-model="form.end_date"
                      type="date"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Batas Umur Peserta</label>
                    <input
                      v-model="form.age_limit_date"
                      type="date"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Tingkat Event</label>
                    <select
                      v-model="form.event_level"
                      class="form-control form-control-sm"
                      required
                    >
                      <option value="national">national</option>
                      <option value="province">province</option>
                      <option value="regency">Kabupaten/Kota</option>
                      <option value="district">Kecamatan</option>
                    </select>
                  </div>

                  <!-- WILAYAH EVENT SESUAI SKEMA -->
                  <div class="form-group mb-2">
                    <label class="mb-1">province Event</label>
                    <select
                      v-model="form.province_id"
                      class="form-control form-control-sm"
                      :disabled="form.event_level === 'national'"
                    >
                      <option :value="''">-- Pilih province --</option>
                      <option
                        v-for="p in provinceOptions"
                        :key="p.id"
                        :value="p.id"
                      >
                        {{ p.name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Kabupaten / Kota Event</label>
                    <select
                      v-model="form.regency_id"
                      class="form-control form-control-sm"
                      :disabled="
                        form.event_level === 'national' ||
                        form.event_level === 'province' ||
                        !form.province_id ||
                        isLoadingRegencies
                      "
                    >
                      <option :value="''">
                        {{ isLoadingRegencies ? 'Memuat Kab/Kota...' : '-- Pilih Kab/Kota --' }}
                      </option>
                      <option
                        v-for="r in regencyOptions"
                        :key="r.id"
                        :value="r.id"
                      >
                        {{ r.name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Kecamatan Event</label>
                    <select
                      v-model="form.district_id"
                      class="form-control form-control-sm"
                      :disabled="
                        form.event_level === 'national' ||
                        form.event_level === 'province' ||
                        form.event_level === 'regency' ||
                        !form.regency_id ||
                        isLoadingDistricts
                      "
                    >
                      <option :value="''">
                        {{ isLoadingDistricts ? 'Memuat Kecamatan...' : '-- Pilih Kecamatan --' }}
                      </option>
                      <option
                        v-for="d in districtOptions"
                        :key="d.id"
                        :value="d.id"
                      >
                        {{ d.name }}
                      </option>
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

// ===== MASTER WILAYAH =====
const provinceOptions = ref([])
const regencyOptions = ref([])
const districtOptions = ref([])
const isLoadingRegencies = ref(false)
const isLoadingDistricts = ref(false)
const isInitRegion = ref(false) // guard supaya watcher tidak jalan saat init/edit

// ===== FORM / MODAL =====
const isEdit = ref(false)
const isSubmitting = ref(false)
const form = ref({
  id: null,
  event_key: '',
  event_name: '',
  app_name: '',
  event_location: '',
  event_tagline: '',
  assessment_token: '',
  start_date: '',
  end_date: '',
  age_limit_date: '',
  logo_event: '',
  logo_sponsor_1: '',
  logo_sponsor_2: '',
  logo_sponsor_3: '',
  event_level: 'regency',
  province_id: '',
  regency_id: '',
  district_id: '',
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
  return `${day} ${bulanIndo[parseInt(month, 10) - 1]} ${year}`
}

// untuk input type="date": pastikan format YYYY-MM-DD
const toDateInput = (val) => {
  if (!val) return ''
  if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val
  return String(val).substring(0, 10)
}

// =============================
// FETCH MASTER WILAYAH
// =============================
const fetchProvinceOptions = async () => {
  try {
    const res = await axios.get('/api/v1/get/provinces')
    provinceOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal memuat daftar province:', e)
  }
}

const fetchRegencyOptions = async (preserveSelection = false) => {
  if (!form.value.province_id) {
    regencyOptions.value = []
    districtOptions.value = []
    if (!preserveSelection) {
      form.value.regency_id = ''
      form.value.district_id = ''
    }
    return
  }

  isLoadingRegencies.value = true

  if (!preserveSelection) {
    regencyOptions.value = []
    form.value.regency_id = ''
    districtOptions.value = []
    form.value.district_id = ''
  }

  try {
    const res = await axios.get('/api/v1/get/regencies', {
      params: { province_id: form.value.province_id },
    })
    regencyOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal memuat kab/kota:', e)
  } finally {
    isLoadingRegencies.value = false
  }
}

const fetchDistrictOptions = async (preserveSelection = false) => {
  if (!form.value.regency_id) {
    districtOptions.value = []
    if (!preserveSelection) {
      form.value.district_id = ''
    }
    return
  }

  isLoadingDistricts.value = true

  if (!preserveSelection) {
    districtOptions.value = []
    form.value.district_id = ''
  }

  try {
    const res = await axios.get('/api/v1/get/districts', {
      params: { regency_id: form.value.regency_id },
    })
    districtOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal memuat kecamatan:', e)
  } finally {
    isLoadingDistricts.value = false
  }
}

// =============================
// FETCH EVENTS
// =============================
const fetchEvents = async (page = 1) => {
  isLoading.value = true

  try {
    const params = {
      page,
      search: search.value,
    }

    // Kalau BUKAN superadmin → kirim event_id dari localStorage
    if (!isSuperadmin.value && authUserStore.eventData?.id) {
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

// =============================
// MODAL CREATE / EDIT
// =============================
const resetForm = () => {
  form.value = {
    id: null,
    event_key: '',
    event_name: '',
    app_name: '',
    event_location: '',
    event_tagline: '',
    assessment_token: '',
    start_date: '',
    end_date: '',
    age_limit_date: '',
    logo_event: '',
    logo_sponsor_1: '',
    logo_sponsor_2: '',
    logo_sponsor_3: '',
    event_level: 'regency',
    province_id: '',
    regency_id: '',
    district_id: '',
    is_active: true,
  }
}

const openCreateModal = () => {
  isEdit.value = false
  isInitRegion.value = true
  resetForm()
  // opsi wilayah sudah di-load di onMounted
  regencyOptions.value = []
  districtOptions.value = []
  isInitRegion.value = false
  $('#eventModal').modal('show')
}

const openEditModal = async (event) => {
  isEdit.value = true
  isInitRegion.value = true

  form.value = {
    id: event.id,
    event_key: event.event_key,
    event_name: event.event_name,
    app_name: event.app_name,
    event_location: event.event_location,
    event_tagline: event.event_tagline,
    assessment_token: event.assessment_token,
    start_date: toDateInput(event.start_date),
    end_date: toDateInput(event.end_date),
    age_limit_date: toDateInput(event.age_limit_date),
    logo_event: event.logo_event,
    logo_sponsor_1: event.logo_sponsor_1,
    logo_sponsor_2: event.logo_sponsor_2,
    logo_sponsor_3: event.logo_sponsor_3,
    event_level: event.event_level || 'regency',
    province_id: event.province_id || '',
    regency_id: event.regency_id || '',
    district_id: event.district_id || '',
    is_active: !!event.is_active,
  }

  // Pastikan master wilayah sudah ada
  if (!provinceOptions.value.length) {
    await fetchProvinceOptions()
  }

  // Load kab/kota & kecamatan sesuai existing event
  if (form.value.province_id) {
    await fetchRegencyOptions(true)
  }
  if (form.value.regency_id) {
    await fetchDistrictOptions(true)
  }

  isInitRegion.value = false
  $('#eventModal').modal('show')
}

// =============================
// SUBMIT
// =============================
const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    event_key: form.value.event_key,
    event_name: form.value.event_name,
    app_name: form.value.app_name,
    event_location: form.value.event_location,
    event_tagline: form.value.event_tagline,
    assessment_token: form.value.assessment_token,
    start_date: form.value.start_date || null,
    end_date: form.value.end_date || null,
    age_limit_date: form.value.age_limit_date || null,
    logo_event: form.value.logo_event,
    logo_sponsor_1: form.value.logo_sponsor_1,
    logo_sponsor_2: form.value.logo_sponsor_2,
    logo_sponsor_3: form.value.logo_sponsor_3,
    event_level: form.value.event_level,
    province_id: form.value.province_id || null,
    regency_id: form.value.regency_id || null,
    district_id: form.value.district_id || null,
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
  if (!confirm(`Yakin ingin menghapus event "${event.event_name}"?`)) {
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

// =============================
// WATCHERS
// =============================
watch(
  () => search.value,
  useDebounceFn(() => fetchEvents(1), 400)
)

watch(
  () => form.value.province_id,
  () => {
    if (isInitRegion.value) return
    fetchRegencyOptions()
  }
)

watch(
  () => form.value.regency_id,
  () => {
    if (isInitRegion.value) return
    fetchDistrictOptions()
  }
)

onMounted(async () => {
  await fetchProvinceOptions()
  fetchEvents()
})
</script>
