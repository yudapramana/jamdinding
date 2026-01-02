<template>
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <!-- <div>
                    <h1 class="mb-1">Data Tahapan Peserta</h1>
                    <p class="mb-0 text-muted text-sm">
                        Event:
                        <strong>{{ eventInfo?.nama_event || '-' }}</strong>
                        <span v-if="eventInfo?.lokasi_event"> • {{ eventInfo.lokasi_event }}</span>
                    </p>
                </div> -->

                <div>
                    <h1 class="mb-1">Data Tahapan Peserta</h1>
                    <p class="mb-0 text-muted text-sm">
                        Mengatur Tahapan Event.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button
                        class="btn btn-outline-secondary btn-sm mr-2"
                        @click="confirmGenerate"
                        :disabled="!eventId || isGenerating || isLoading"
                    >
                        <i v-if="isGenerating" class="fas fa-spinner fa-spin mr-1"></i>
                        <i v-else class="fas fa-magic mr-1"></i>
                        Generate dari Template
                    </button>

                    <button class="btn btn-primary btn-sm" @click="openCreateModal">
                        + Tambah Tahapan
                    </button>
                </div>
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
                        placeholder="Cari nama tahapan..."
                    />
                    <span class="text-muted text-sm" v-if="eventData">
                        Periode Event:
                        <strong>
                            {{ formatDate(eventData.start_date) }} s.d. {{ formatDate(eventData.end_date) }}
                        </strong>
                    </span>
                </div>

                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">No</th>
                                <!-- <th style="width: 80px;">Urutan</th> -->
                                <th>Nama Tahapan</th>
                                <th style="width: 150px;">Tanggal Awal</th>
                                <th style="width: 150px;">Tanggal Akhir</th>
                                <th style="width: 90px;">Status</th>
                                <th style="width: 90px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading">
                                <td colspan="7" class="text-center">Memuat data...</td>
                            </tr>
                            <tr v-else-if="stages.length === 0">
                                <td colspan="7" class="text-center">Tidak ada data tahapan.</td>
                            </tr>
                            <tr v-for="(stage, index) in stages" :key="stage.id">
                                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                                <!-- <td class="text-center">{{ stage.order_number }}</td> -->
                                <td><strong>{{ stage.name }}</strong></td>
                                <td>{{ formatDate(stage.start_date) || '-' }}</td>
                                <td>{{ formatDate(stage.end_date) || '-' }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="stage.is_active ? 'badge-success' : 'badge-secondary'"
                                    >
                                        {{ stage.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-warning"
                                        @click="openEditModal(stage)"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted text-sm">
                            Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
                            {{ meta.total || 0 }} tahapan
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

        <!-- Modal Tambah / Edit Tahapan -->
        <div class="modal fade" id="stageModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="stageModalLabel">
                            <i class="fas fa-list-alt me-2"></i>
                            {{ isEdit ? 'Edit Tahapan' : 'Tambah Tahapan' }}
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
                                        <label class="mb-1">Nama Tahapan</label>
                                        <input
                                            v-model="form.name"
                                            class="form-control form-control-sm"
                                            required
                                        />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Urutan Tahapan</label>
                                        <input
                                            v-model.number="form.order_number"
                                            type="number"
                                            min="1"
                                            class="form-control form-control-sm"
                                            required
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

                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Tanggal Awal</label>
                                        <input
                                            v-model="form.start_date"
                                            type="date"
                                            class="form-control form-control-sm"
                                        />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Tanggal Akhir</label>
                                        <input
                                            v-model="form.end_date"
                                            type="date"
                                            class="form-control form-control-sm"
                                        />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Keterangan</label>
                                        <textarea
                                            v-model="form.notes"
                                            rows="3"
                                            class="form-control form-control-sm"
                                            placeholder="Opsional: catatan internal untuk tahapan ini..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <button
                                    type="submit"
                                    class="btn btn-sm btn-primary"
                                    :disabled="isSubmitting"
                                >
                                    <i
                                        v-if="isSubmitting"
                                        class="fas fa-spinner fa-spin me-1"
                                    ></i>
                                    <i v-else class="fas fa-save me-1"></i>
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
import { ref, watch, onMounted, computed } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import Swal from 'sweetalert2';

const authUserStore = useAuthUserStore()



// DATA TABLE
const stages = ref([])
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

// MODAL / FORM
const isEdit = ref(false)
const isSubmitting = ref(false)
const form = ref({
  id: null,
  event_id: null,
  stage_id: null,
  name: '',
  order_number: 1,
  start_date: '',
  end_date: '',
  notes: '',
  is_active: true,
})

// INFO EVENT (dari localStorage / cookie event_data)
const eventInfo = ref(null)
const eventId = ref(null)
// ✅ event aktif diambil dari AuthUserStore
const eventData = computed(() => authUserStore.eventData || null)

const toDateInput = (val) => {
  if (!val) return ''
  // kalau sudah dalam format YYYY-MM-DD, pakai saja
  if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val
  // kalau ada spasi / T / jam di belakang → ambil 10 karakter pertama
  return String(val).substring(0, 10)
}


const formatDate = (val) => {
  if (!val) return ''

  // Ambil hanya bagian YYYY-MM-DD
  const str = String(val).substring(0, 10)
  const [year, month, day] = str.split('-')

  const bulanIndo = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
  ]

  return `${day} ${bulanIndo[parseInt(month) - 1]} ${year}`
}

const fetchStages = async (page = 1) => {
  if (!eventId.value) {
    stages.value = []
    return
  }

  isLoading.value = true
  try {
    const response = await axios.get(`/api/v1/events/${eventId.value}/stages`, {
      params: {
        page,
        search: search.value,
      },
    })

    const paginated = response.data.data // lihat bentuk JSON dari controller

    stages.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    if (error.response && error.response.status === 401) {
      console.warn('Unauthorized. Logging out...')
      authUserStore.logout()
    } else {
      console.error('Gagal memuat data tahapan:', error)
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchStages(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    event_id: eventId.value,
    stage_id: null,
    name: '',
    order_number: (meta.value.total || 0) + 1,
    start_date: '',
    end_date: '',
    notes: '',
    is_active: true,
  }
  $('#stageModal').modal('show')
}

const openEditModal = (stage) => {
  isEdit.value = true
  form.value = {
    id: stage.id,
    event_id: stage.event_id,
    stage_id: stage.stage_id,
    name: stage.name,
    order_number: stage.order_number,
    // 🔧 penting: normalize ke YYYY-MM-DD untuk input date
    start_date: toDateInput(stage.start_date),
    end_date: toDateInput(stage.end_date),
    notes: stage.notes,
    is_active: !!stage.is_active,
  }
  console.log('form.value')
  console.log(form.value)
  $('#stageModal').modal('show')
}

const submitForm = async () => {
  if (!eventId.value) {
    alert('Event belum terdeteksi. Silakan kembali ke halaman utama dan pilih event.')
    return
  }

  isSubmitting.value = true

  const payload = {
    event_id: eventId.value,
    stage_id: form.value.stage_id,
    name: form.value.name,
    order_number: form.value.order_number,
    start_date: form.value.start_date || null,
    end_date: form.value.end_date || null,
    notes: form.value.notes,
    is_active: form.value.is_active,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-stages/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/event-stages', payload)
    }
    $('#stageModal').modal('hide')
    fetchStages(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan tahapan:', error)
    alert('Gagal menyimpan tahapan.')
  } finally {
    isSubmitting.value = false
  }
}

// STATUS TOMBOL GENERATE
const isGenerating = ref(false)

/**
 * Tampilkan konfirmasi sebelum generate
 */
const confirmGenerate = () => {
    if (!eventId.value) {
        alert('Event belum terdeteksi. Silakan pilih event terlebih dahulu.')
        return
    }

    if (stages.value.length > 0) {
        if (!confirm('Tahapan untuk event ini sudah ada. Generate ulang akan menambah tahapan baru berdasarkan template master.\n\nLanjutkan?')) {
            return
        }
    }

    generateFromTemplate()
}

/**
 * Panggil API generate dari template:
 * POST /api/v1/events/{eventId}/stages/generate-default
 */
const generateFromTemplate = async () => {
    isGenerating.value = true

    try {
        await axios.post(`/api/v1/events/${eventId.value}/stages/generate-default`)

        // reload tabel setelah generate
        await fetchStages(1)

        // alert('Tahapan berhasil digenerate dari template master.')
        Swal.fire({
            icon: 'success',
            title: 'Tahapan berhasil digenerate dari template master.',
            showConfirmButton: false,
            timer: 2000
        });
    } catch (error) {
        console.error('Gagal generate tahapan:', error)
        alert('Gagal generate tahapan.')
    } finally {
        isGenerating.value = false
    }
}


// Debounce pencarian
watch(
  () => search.value,
  useDebounceFn(() => fetchStages(1), 400)
)

onMounted(() => {
    eventInfo.value = authUserStore.eventData
    eventId.value = authUserStore.eventData.id
    fetchStages()
})
</script>
