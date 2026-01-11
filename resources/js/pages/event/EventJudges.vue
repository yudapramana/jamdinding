<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Manajemen Dewan Hakim Event</h1>
          <p class="mb-0 text-muted text-sm">
            Kelola data User, Master Hakim, dan Penugasan Hakim pada Event
          </p>
        </div>

        <button
          class="btn btn-primary btn-sm"
          :disabled="!eventId"
          @click="openCreateModal"
        >
          <i class="fas fa-user-plus mr-1"></i>
          Tambah Hakim
        </button>
      </div>
    </div>

    <p v-if="!eventId" class="text-danger text-sm mt-2">
      <i class="fas fa-exclamation-triangle mr-1"></i>
      Event belum dipilih.
    </p>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">

        

        <!-- HEADER -->
        <div class="card-header py-2">
          <div class="d-flex flex-wrap align-items-center justify-content-between">

            <!-- LEFT: FILTERS -->
            <div class="d-flex flex-wrap align-items-center gap-2">

              <!-- PER PAGE -->
              <span class="text-xs text-muted">Tampil</span>
              <select
                v-model.number="perPage"
                class="form-control form-control-sm w-auto"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="text-xs text-muted">entri</span>

            </div>

            <!-- RIGHT: SEARCH -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm mt-2 mt-md-0"
              style="width: 220px"
              placeholder="Cari nama / NIK / username"
            />

          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th width="40">#</th>
                <th>Hakim</th>
                <th>NIK</th>
                <th>Spesialisasi</th>
                <th>Sertifikasi</th>
                <th>Kode</th>
                <th style="width: 90px;" class="text-center">Status</th>
                <th style="width: 120px;" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center text-muted">
                  Memuat data hakim...
                </td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center text-muted">
                  Belum ada hakim pada event ini
                </td>
              </tr>

              <tr
                v-for="(row, index) in items"
                :key="row.id"
              >
                <td class="text-center">{{ rowNumber(index) }}</td>

                <td>
                  <strong>{{ row.master_judge.full_name }}</strong>
                  <div class="text-xs text-muted">
                    {{ row.user.username }} • {{ row.user.email }}
                  </div>
                </td>

                <td>{{ row.master_judge.nik || '-' }}</td>
                <td>{{ row.master_judge.specialization || '-' }}</td>
                <td>{{ row.master_judge.certification_level || '-' }}</td>
                <td>{{ row.judge_code || '-' }}</td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="row.is_active ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>

                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-warning btn-xs"
                      @click="openEditModal(row)"
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </button>

                    <button
                      class="btn btn-outline-danger btn-xs"
                      @click="deleteItem(row)"
                      title="Hapus"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="card-footer clearfix py-2">
            <div class="d-flex justify-content-between align-items-center">
              <div class="text-muted text-sm">
                Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }}
                dari {{ meta.total || 0 }} data
              </div>
              <ul class="pagination pagination-sm m-0">
                <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                  <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
                </li>
                <li class="page-item disabled">
                  <span class="page-link">
                    Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
                  </span>
                </li>
                <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                  <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">»</a>
                </li>
              </ul>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- MODAL FORM -->
    <div class="modal fade" id="judgeModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

          <!-- HEADER -->
          <div class="modal-header py-2">
            <h5 class="modal-title text-sm font-weight-bold">
              {{ isEdit ? 'Edit Hakim Event' : 'Tambah Hakim Event' }}
            </h5>
            <button class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

            <div class="modal-body pt-2">
              <form>

              <!-- ================= IDENTITAS & AKUN ================= -->
              <div class="mb-3">
                <h6 class="mb-1 font-weight-bold text-muted">
                  IDENTITAS & AKUN HAKIM anjing
                </h6>
                <hr class="mt-1 mb-3" />

                <!-- ROW 1 -->
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group mb-1">
                      <label class="mb-1">
                        NIK <span class="text-danger">*</span>
                      </label>

                      <div class="input-group input-group-sm">
                        <input
                          v-model="form.master_judge.nik"
                          class="form-control form-control-sm"
                          :class="{ 'is-invalid': nikError }"
                          placeholder="16 digit NIK"
                          @blur="onNikChangedAndSyncUsername"
                        />

                        <div class="input-group-append">
                          <span class="input-group-text">
                            <i v-if="isCheckingNik" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-id-card"></i>
                          </span>
                        </div>
                      </div>

                      <div v-if="nikError" class="invalid-feedback d-block">
                        {{ nikError }}
                      </div>

                      <small class="text-muted text-xs">
                        Wajib 16 digit, akan dicek otomatis
                      </small>
                    </div>

                  </div>

                  <div class="col-md-4">
                    <div class="form-group mb-1">
                      <label class="mb-1">Nama Lengkap</label>
                      <input
                        v-model="form.user.name"
                        class="form-control form-control-sm"
                        required
                      />
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group mb-1">
                      <label class="mb-1">Email</label>
                      <input
                        v-model="form.user.email"
                        type="email"
                        class="form-control form-control-sm"
                        required
                      />
                    </div>
                  </div>
                </div>

                <!-- ROW 2 -->
                <div class="row mt-2">
                  <div class="col-md-3">
                    <div class="form-group mb-1">
                      <label class="mb-1">Tanggal Lahir</label>
                      <input
                        v-model="form.master_judge.date_of_birth"
                        type="date"
                        class="form-control form-control-sm"
                        readonly
                      />
                    </div>
                  </div>

                  <div class="col-md-3">
                    <div class="form-group mb-1">
                      <label class="mb-1">Jenis Kelamin</label>
                      <select
                        v-model="form.master_judge.gender"
                        class="form-control form-control-sm"
                        readonly
                      >
                        <option value="MALE">Laki-laki</option>
                        <option value="FEMALE">Perempuan</option>
                      </select>
                    </div>
                  </div>

                  <div class="col-md-3">
                    <div class="form-group mb-1">
                      <label class="mb-1">Spesialisasi</label>
                      <select v-model="form.master_judge.specialization" class="form-control form-control-sm">
                        <option value="">- Pilih Spesialisasi -</option>
                        <option v-for="s in specializationOptions" :key="s" :value="s">
                          {{ s }}
                        </option>
                      </select>

                    </div>
                  </div>

                  <div class="col-md-3">
                    <div class="form-group mb-1">
                      <label class="mb-1">Sertifikasi</label>
                      <select v-model="form.master_judge.certification_level" class="form-control form-control-sm">
                        <option value="">- Pilih Sertifikasi -</option>
                        <option v-for="c in certificationOptions" :key="c" :value="c">
                          {{ c }}
                        </option>
                      </select>

                    </div>
                  </div>
                </div>

                <!-- ROW 3 -->
                <div class="row mt-2">
                  <div class="col-md-3">
                    <div class="form-group mb-1">
                      <label class="mb-1">Pendidikan</label>
                      <select
                        v-model="form.master_judge.education"
                        class="form-control form-control-sm"
                      >
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="D3">D3</option>
                        <option value="D4">D4</option>
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                      </select>
                    </div>
                  </div>

                  <div class="col-md-3 d-flex align-items-end">
                    <div class="form-group mb-1">
                      <div class="custom-control custom-switch">
                        <input
                          type="checkbox"
                          class="custom-control-input"
                          id="judgeActive"
                          v-model="form.master_judge.is_active"
                        />
                        <label class="custom-control-label" for="judgeActive">
                          Hakim Aktif
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ================= DATA REKENING ================= -->
              <div class="mb-2">
                <h6 class="mb-1 font-weight-bold text-muted">
                  DATA REKENING (OPSIONAL)
                </h6>
                <hr class="mt-1 mb-3" />

                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group mb-1">
                      <label class="mb-1">Nama Bank</label>
                      <select
                        v-model="form.master_judge.bank_name"
                        class="form-control form-control-sm"
                      >
                        <option
                          v-for="bank in bankOptions"
                          :key="bank"
                          :value="bank"
                          >
                          {{ bank }}
                          </option>
                      </select>
                    </div>
                  </div>

                  <div class="col-md-3">
                    <div class="form-group mb-1">
                      <label class="mb-1">No. Rekening</label>
                      <input
                        v-model="form.master_judge.bank_account_number"
                        class="form-control form-control-sm"
                      />
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group mb-1">
                      <label class="mb-1">Nama Pemilik Rekening</label>
                      <input
                        v-model="form.master_judge.bank_account_name"
                        class="form-control form-control-sm"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </form>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer py-2">
              <button class="btn btn-sm btn-secondary" data-dismiss="modal">
                Batal
              </button>
              <button
                class="btn btn-sm btn-primary"
                type="button"
                :disabled="isSubmitting"
                @click="submitForm"
              >
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
                <i v-else class="fas fa-save mr-1"></i>
                {{ isEdit ? 'Update' : 'Simpan' }}
              </button>


            </div>

        </div>
      </div>
    </div>

  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import { useDebounceFn } from '@vueuse/core'
import {
  bankOptions,
} from '../EventParticipantHelpers'

const authUserStore = useAuthUserStore()
const eventId = computed(() => authUserStore.eventData?.id || null)

const items = ref([])
const search = ref('')
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)
const nikError = ref('')
const isCheckingNik = ref(false)
const nikStatus = ref(null)
// possible values: null | 'available' | 'exists' | 'already_registered'

const perPage = ref(25)

const specializationOptions = [
  'Tilawah',
  'Tahfidz',
  'Tafsir',
  'Fahmil',
  'Syarhil',
  'Khat',
  'Qiraat',
  'Hadits',
]

const certificationOptions = [
  'Nasional',
  'Provinsi',
  'Kabupaten',
  'Kecamatan',
  'Internal',
  'Non Sertifikasi',
]


const validateNikBasic = (nik) => {
  if (!nik) return 'NIK wajib diisi'
  if (!/^\d{16}$/.test(nik)) return 'NIK harus 16 digit angka'
  return ''
}

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchItems(page)
}

const rowNumber = (i) =>
  i + 1 + (meta.value.current_page - 1) * meta.value.per_page

const emptyForm = () => ({
  user: {
    id: null,
    name: '',
    email: '',
    username: '',
  },
  master_judge: {
    id: null,
    nik: '',
    date_of_birth: '',
    gender: '',
    specialization: '',
    certification_level: '',
    full_name: '',

    // ⬇️ TAMBAHAN SESUAI SKEMA
    education: 'SMA',
    bank_name: '',
    bank_account_number: '',
    bank_account_name: '',
    is_active: true,
  },
  event_judge: {
    id: null,
    is_active: true,
  },
})


const form = ref(emptyForm())

const fetchItems = async (page = 1) => {
  if (!eventId.value) return
  isLoading.value = true

  try {
    const res = await axios.get(
      `/api/v1/events/${eventId.value}/judges`,
      {
        params: {
          page,
          per_page: perPage.value,
          search: search.value,
        },
      }
    )

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

  } catch {
    Swal.fire('Error', 'Gagal memuat data hakim', 'error')
  } finally {
    isLoading.value = false
  }
}



const openCreateModal = () => {
  isEdit.value = false
  form.value = emptyForm()
  $('#judgeModal').modal('show')
}


const openEditModal = (row) => {
  isEdit.value = true
  originalNik.value = row.master_judge.nik
  form.value = {
    user: { ...row.user },
    master_judge: { ...row.master_judge },
    event_judge: { ...row },
  }
  $('#judgeModal').modal('show')
}

const submitForm = async () => {
  if (nikStatus.value === 'already_registered') {
    Swal.fire('Tidak dapat disimpan', 'Hakim sudah terdaftar', 'warning')
    return
  }

  if (nikError.value) {
    Swal.fire('Validasi gagal', nikError.value, 'warning')
    return
  }

  isSubmitting.value = true

  try {
    const res = await axios.post('/api/v1/save-event-judges', {
      event_id: eventId.value,
      ...form.value,
    })

    const saved = res.data.data

    if (!isEdit.value) {
      // CREATE
      if (meta.value.current_page === 1) {
        items.value.unshift(saved)

        if (items.value.length > perPage.value) {
          items.value.pop()
        }

        meta.value.total++
        meta.value.to++
      }
    }

    if (isEdit.value) {
      const idx = findRowIndex(saved.id)
      if (idx !== -1) {
        // ⚠️ penting: replace object, jangan mutate dalam
        items.value.splice(idx, 1, saved)
      }
    }



    Swal.fire('Berhasil', 'Data hakim disimpan', 'success')
    $('#judgeModal').modal('hide')

  } catch (e) {
    Swal.fire('Gagal', e.response?.data?.message || 'Gagal menyimpan', 'error')
  } finally {
    isSubmitting.value = false
  }
}




const findRowIndex = (id) =>
  items.value.findIndex(i => i.id === id)



const deleteItem = async (row) => {
  const confirm = await Swal.fire({
    title: 'Hapus Hakim?',
    text: row.master_judge.full_name,
    showCancelButton: true,
    confirmButtonColor: '#d33',
  })

  if (!confirm.isConfirmed) return

  await axios.delete(`/api/v1/event-judges/${row.id}`)

  const idx = findRowIndex(row.id)
  if (idx !== -1) {
    items.value.splice(idx, 1)
    meta.value.total--
    meta.value.to--
  }

  Swal.fire('Dihapus', 'Hakim berhasil dihapus', 'success')
}


// HELPER
// =========================
// NIK Parsing Helpers
// =========================

const parseNikToDobGender = (nikRaw) => {
  const nik = String(nikRaw || '').replace(/\D/g, '')
  if (nik.length !== 16) return null

  const ddStr = nik.slice(6, 8)
  const mmStr = nik.slice(8, 10)
  const yyStr = nik.slice(10, 12)

  let day = parseInt(ddStr, 10)
  const month = parseInt(mmStr, 10)
  const year2 = parseInt(yyStr, 10)

  if (Number.isNaN(day) || Number.isNaN(month) || Number.isNaN(year2)) {
    return null
  }

  let gender = 'MALE'
  if (day > 40) {
    day -= 40
    gender = 'FEMALE'
  }

  if (day < 1 || day > 31 || month < 1 || month > 12) {
    return null
  }

  const now = new Date()
  const currentYear2 = now.getFullYear() % 100
  const fullYear = year2 <= currentYear2 ? 2000 + year2 : 1900 + year2

  const yyyy = String(fullYear).padStart(4, '0')
  const mm = String(month).padStart(2, '0')
  const dd = String(day).padStart(2, '0')

  return { date: `${yyyy}-${mm}-${dd}`, gender }
}

const extractBirthdateFromNik = (nikRaw) => {
  const nik = (nikRaw || '').replace(/\D/g, '')
  if (nik.length !== 16) return null

  const parsed = parseNikToDobGender(nik)
  if (!parsed) return null

  return {
    dateOfBirth: parsed.date,
    gender: parsed.gender,
  }
}

const originalNik = ref(null)


const onNikChangedAndSyncUsername = async () => {
  const nik = (form.value.master_judge.nik || '').replace(/\D/g, '')
  form.value.master_judge.nik = nik
  nikError.value = ''

  if (
    isEdit.value &&
    form.value.master_judge.id &&
    form.value.master_judge.nik === originalNik.value
  ) {
    return
  }

  const basicError = validateNikBasic(nik)
  if (basicError) {
    nikError.value = basicError
    return
  }

  // username = NIK
  form.value.user.username = nik

  // auto DOB & gender
  const extracted = extractBirthdateFromNik(nik)
  if (extracted) {
    form.value.master_judge.date_of_birth ||= extracted.dateOfBirth
    form.value.master_judge.gender ||= extracted.gender
  }

  // === CHECK KE API ===
  isCheckingNik.value = true
  nikStatus.value = null

  try {
    const res = await axios.get('/api/v1/check-nik-judge', {
      params: {
        nik,
        event_id: eventId.value,
        master_judge_id: form.value.master_judge.id || null, // ⬅️ KRUSIAL
      },
    })


    nikStatus.value = res.data.status

    if (res.data.status === 'exists') {
      // LOAD DATA MASTER JUDGE
      form.value.master_judge = {
        ...form.value.master_judge,
        ...res.data.data.master_judge,
      }

      form.value.user = {
        ...form.value.user,
        ...res.data.data.user,
      }

      Swal.fire(
        'Info',
        'Data hakim ditemukan. Data dimuat otomatis.',
        'info'
      )
    }

    if (res.data.status === 'already_registered') {
      nikError.value = 'Dewan hakim sudah terdaftar pada event ini'
    }

  } catch (e) {
    nikError.value = e.response?.data?.message || 'Gagal validasi NIK'
  } finally {
    isCheckingNik.value = false
  }
}



watch(() => search.value, useDebounceFn(() => fetchItems(1), 400))
watch(() => perPage.value, () => fetchItems(1))
watch(() => eventId.value, (val) => val && fetchItems(1))


watch(
  () => form.value.master_judge.is_active,
  (val) => {
    form.value.event_judge.is_active = val
  }
)

watch(
  () => form.value.user.name,
  (val) => {
    if (val) {
      form.value.master_judge.full_name = val
    }
  }
)


onMounted(fetchItems)
</script>

<style scoped>
.badge-pink {
  background-color: #e83e8c;
  color: #fff;
}

.btn-xs {
    padding: 2px 5px !important;
    font-size: 0.65rem !important;
    line-height: 1 !important;
}

.btn-xs i {
    font-size: 0.55rem !important;
}

.lampiran-photo-card {
  min-height: 100%;
}

.lampiran-photo-frame {
  width: 180px;
  height: 260px;
  border: 1px solid #dee2e6;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.lampiran-photo-frame img {
  max-width: 100%;
  max-height: 100%;
  object-fit: cover;
}

.lampiran-photo-input {
  max-width: 220px;
}

.lampiran-row {
  margin-bottom: 1rem;
  border-bottom: 1px dashed #f0f0f0;
  padding-bottom: 0.5rem;
}

.text-xs {
  font-size: 0.75rem;
}

.badge-file:hover {
  opacity: 0.8;
}

.gap-2 {
  gap: .5rem;
}

</style>
