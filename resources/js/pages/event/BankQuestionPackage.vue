<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Bank Soal Event</h1>
          <p class="mb-0 text-muted text-sm">
            Kelola paket soal (beserta sub-soal) untuk event yang sedang aktif.
          </p>
        </div>
        <div class="d-flex gap-2">
          <a
            class="btn btn-outline-success btn-sm mr-2"
            :href="templateImportUrl"
            download="Template_Import_Paket_Soal.xlsx"
          >
            <i class="fas fa-download mr-1"></i>
            Download Template
          </a>

          <button
            class="btn btn-success btn-sm mr-2"
            :disabled="!selectedGroupId || !selectedRoundId"
            @click="openImportModal"
          >
            <i class="fas fa-file-import mr-1"></i>
            Import Excel/CSV
          </button>

          <button
            class="btn btn-primary btn-sm"
            :disabled="!selectedGroupId || !selectedRoundId"
            @click="openCreateModal"
          >
            <i class="fas fa-plus mr-1"></i>
            Tambah Paket Soal
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header py-2">
          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">
            <!-- LEFT: perPage + golongan + 3 opsi babak -->
            <div class="d-flex align-items-center gap-2">
              <select
                v-model.number="perPage"
                class="form-control form-control-sm w-auto"
                :disabled="!selectedGroupId"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>

              <select
                v-model="selectedGroupId"
                class="form-control form-control-sm w-auto"
                style="min-width: 230px"
                @change="fetchPackages(1)"
              >
                <option value="">-- Pilih Golongan Cabang Lomba --</option>
                <option v-for="group in filteredEventGroups" :key="group.id" :value="group.id">
                  {{ group.full_name }}
                </option>
              </select>

              <!-- 3 PILIHAN BABAK -->
              <select
                v-model="selectedRoundId"
                class="form-control form-control-sm w-auto"
                @change="fetchPackages(1)"
                :disabled="!selectedGroupId"
              >
                <option value="1">Penyisihan</option>
                <option value="2">Semifinal</option>
                <option value="3">Final</option>
              </select>
            </div>

            <!-- RIGHT: search -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto mt-2 mt-md-0"
              style="min-width: 220px"
              placeholder="Cari nomor paket..."
              :disabled="!selectedGroupId || !selectedRoundId"
            />
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <div v-if="!selectedGroupId || !selectedRoundId" class="p-5 text-center text-muted">
            <i class="fas fa-filter fa-3x mb-3 text-light"></i>
            <h5>Silakan pilih Golongan dan Babak terlebih dahulu</h5>
            <p class="text-sm">Data paket soal akan ditampilkan berdasarkan filter yang dipilih.</p>
          </div>

          <table v-else class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 50px;" class="text-center">No</th>
                <th style="width: 160px;">Nomor Paket</th>
                <th class="text-center" style="width: 110px;">Sub-soal</th>
                <th>Cuplikan Soal</th>
                <th class="text-center" style="width: 110px;">Status</th>
                <th style="width: 140px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center py-4">
                  <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data paket soal...
                </td>
              </tr>
              <tr v-else-if="packages.length === 0">
                <td colspan="6" class="text-center py-4">Belum ada paket soal untuk golongan dan babak ini.</td>
              </tr>
              <tr v-for="(pkg, index) in packages" :key="pkg.id">
                <td class="text-center">{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td><strong>{{ pkg.package_number }}</strong></td>
                <td class="text-center">
                  <span class="badge badge-light border">{{ countQuestions(pkg) }} soal</span>
                </td>
                <td class="text-muted">{{ firstQuestion(pkg) }}</td>
                <td class="text-center">
                  <span class="badge" :class="pkg.is_available ? 'badge-success' : 'badge-secondary'">
                    {{ pkg.is_available ? 'Tersedia' : 'Dicabut' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" title="Lihat Soal" @click="openPreviewModal(pkg)">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-warning" title="Edit" @click="openEditModal(pkg)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-danger" title="Hapus" @click="deletePackage(pkg)">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="selectedGroupId && selectedRoundId" class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total || 0 }} paket
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}</span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: PREVIEW PAKET SOAL -->
    <div class="modal fade" id="previewPackageModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header py-2 bg-light">
            <h5 class="modal-title">
              <i class="fas fa-file-alt mr-2 text-success"></i>
              Pratinjau: {{ activePreviewPackage?.package_number }}
            </h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body bg-white py-2 px-3">
            <div v-for="(item, idx) in previewItems" :key="idx" class="border-bottom pb-2 mb-2">
              <span class="badge badge-success mb-1">Soal {{ item.number || idx + 1 }}</span>
              <div style="white-space: pre-line;">{{ item.question }}</div>
            </div>
            <div v-if="previewItems.length === 0" class="text-center text-muted py-4">
              Paket ini belum memiliki isi soal.
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: TAMBAH / EDIT PAKET SOAL -->
    <div class="modal fade" id="packageModal" tabindex="-1" role="dialog" data-backdrop="static">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              <i class="fas fa-file-alt mr-2"></i>
              {{ isEdit ? 'Edit Paket Soal' : 'Tambah Paket Soal Baru' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body py-2 px-3">
            <form @submit.prevent="submitForm">
              <div class="alert alert-info text-sm py-2 mb-2">
                <strong>Golongan Target:</strong> {{ selectedGroupData ? selectedGroupData.full_name : '-' }} <br>
                <strong>Babak:</strong> {{ roundLabel(selectedRoundId) }}
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1 small font-weight-bold">Nomor Paket <span class="text-danger">*</span></label>
                    <input
                      v-model="form.package_number"
                      type="text"
                      maxlength="50"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Paket A / Paket 01"
                      required
                    />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1 small font-weight-bold">Status Ketersediaan</label>
                    <div class="custom-control custom-switch mt-1">
                      <input type="checkbox" class="custom-control-input" id="isPackageAvailableSwitch" v-model="form.is_available">
                      <label class="custom-control-label font-weight-normal" for="isPackageAvailableSwitch">
                        {{ form.is_available ? 'Tersedia untuk Diundi' : 'Sudah Dicabut' }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                <h6 class="mb-0 text-primary small font-weight-bold">Isi Soal ({{ form.question_content.length }} sub-soal)</h6>
                <button type="button" class="btn btn-outline-primary btn-xs" @click="addSubQuestion">
                  <i class="fas fa-plus mr-1"></i> Tambah Sub-soal
                </button>
              </div>

              <div style="max-height: 45vh; overflow-y: auto;" class="pr-1">
                <div v-for="(item, idx) in form.question_content" :key="idx" class="border rounded bg-light p-2 mb-2">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge badge-secondary">Soal {{ idx + 1 }}</span>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0" :disabled="form.question_content.length === 1" @click="removeSubQuestion(idx)">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <textarea v-model="item.question" class="form-control form-control-sm" rows="3" placeholder="Tulis isi soal..."></textarea>
                </div>
              </div>

              <div class="text-right mt-2 pt-2 border-top">
                <button type="button" class="btn btn-secondary btn-sm mr-2" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary" :disabled="isSubmitting || !isFormValid">
                  <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i> Simpan Paket
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: IMPORT PAKET SOAL -->
    <div class="modal fade" id="importPackageModal" tabindex="-1" role="dialog" data-backdrop="static">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header py-2 bg-success text-white">
            <h5 class="modal-title"><i class="fas fa-file-import mr-2"></i> Import Paket Soal</h5>
            <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body py-2 px-3">
            <div class="alert alert-info text-sm py-2 mb-2">
              <strong>Golongan Target:</strong> {{ selectedGroupData ? selectedGroupData.full_name : '-' }} <br>
              <strong>Babak:</strong> {{ roundLabel(selectedRoundId) }}
            </div>
            <form @submit.prevent="submitImport">
              <div class="form-group mb-2">
                <label class="mb-1 small font-weight-bold">Pilih File Excel / CSV <span class="text-danger">*</span></label>
                <div class="custom-file">
                  <input type="file" class="custom-file-input" id="customPackageFile" accept=".csv, .xlsx, .xls" @change="handleFileUpload" required>
                  <label class="custom-file-label" for="customPackageFile">{{ selectedFile ? selectedFile.name : 'Pilih file...' }}</label>
                </div>
              </div>
              <div v-if="importErrors.length > 0" class="alert alert-warning text-sm mt-2" style="max-height: 150px; overflow-y: auto;">
                <strong>Perhatian!</strong> Beberapa baris gagal diimpor:
                <ul class="mb-0 pl-3">
                  <li v-for="(err, idx) in importErrors" :key="idx">{{ err }}</li>
                </ul>
              </div>
              <div class="text-right mt-2 pt-2 border-top">
                <button type="button" class="btn btn-secondary btn-sm mr-2" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success" :disabled="isImporting || !selectedFile">
                  <i v-if="isImporting" class="fas fa-spinner fa-spin mr-1"></i> Upload & Import
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
import { useMasterDataStore } from '../../stores/MasterDataStore'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const masterStore = useMasterDataStore()
const authUserStore = useAuthUserStore()

const extractError = (error, fallback) => {
  const errors = error.response?.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (first && first[0]) return first[0]
  }
  return error.response?.data?.message || fallback
}

const templateImportUrl = '/templates/Template_Import_Paket_Soal.xlsx'
const selectedFile = ref(null)
const isImporting = ref(false)
const importErrors = ref([])

const selectedGroupId = ref('')
const selectedRoundId = ref('1') // Default Penyisihan

const roundLabel = (roundId) => {
  switch (String(roundId)) {
    case '1': return 'Penyisihan'
    case '2': return 'Semifinal'
    case '3': return 'Final'
    default: return '-'
  }
}

const openImportModal = () => {
  selectedFile.value = null
  importErrors.value = []
  const fileInput = document.getElementById('customPackageFile')
  if (fileInput) fileInput.value = ''
  $('#importPackageModal').modal('show')
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) selectedFile.value = file
}

const submitImport = async () => {
  if (!selectedFile.value || !selectedGroupId.value || !selectedRoundId.value) return

  isImporting.value = true
  importErrors.value = []

  const formData = new FormData()
  formData.append('event_group_id', selectedGroupId.value)
  formData.append('round_id', selectedRoundId.value)
  formData.append('file', selectedFile.value)

  try {
    const res = await axios.post('/api/v1/event-question-packages/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    const data = res.data

    if (data.errors && data.errors.length > 0) {
      importErrors.value = data.errors
      alert(`${data.message}\nSilakan cek pesan error di dalam form.`)
      fetchPackages(1)
    } else {
      alert(data.message || 'Import berhasil!')
      $('#importPackageModal').modal('hide')
      fetchPackages(1)
    }
  } catch (error) {
    alert(extractError(error, 'Gagal mengimpor file.'))
  } finally {
    isImporting.value = false
  }
}

const hasMaqraDrawing = (g) => [true, 1, '1', 'true'].includes(g.has_maqra_drawing)
const filteredEventGroups = computed(() => (masterStore.eventGroups || []).filter(hasMaqraDrawing))

const loadGroups = async () => {
  try {
    await masterStore.loadAllEventGroups(true)
  } catch (error) {
    console.error('Gagal memuat event groups:', error)
  }
}

watch(
  () => authUserStore.eventData?.id,
  (id) => { if (id) loadGroups() }
)

const selectedGroupData = computed(() => {
  return filteredEventGroups.value.find(g => g.id === selectedGroupId.value) || null
})

const packages = ref([])
const meta = ref({ current_page: 1, per_page: 10, total: 0, from: 0, to: 0, last_page: 1 })
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)

const getContent = (pkg) => {
  let content = pkg?.question_content
  if (typeof content === 'string') {
    try { content = JSON.parse(content) } catch (e) { content = [] }
  }
  return Array.isArray(content) ? content : []
}

const countQuestions = (pkg) => getContent(pkg).length
const firstQuestion = (pkg) => {
  const first = getContent(pkg)[0]?.question || ''
  return first.length > 100 ? first.slice(0, 100) + '…' : first || '-'
}

const fetchPackages = async (page = 1) => {
  if (!selectedGroupId.value || !selectedRoundId.value) return

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-question-packages', {
      params: {
        page,
        event_group_id: selectedGroupId.value,
        round_id: selectedRoundId.value,
        search: search.value,
        per_page: perPage.value,
      }
    })

    const paginated = res.data.data
    packages.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat data paket soal:', error)
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchPackages(page)
}

watch(perPage, () => { if (selectedGroupId.value) fetchPackages(1) })
watch(
  () => search.value,
  useDebounceFn(() => { if (selectedGroupId.value) fetchPackages(1) }, 400)
)

const activePreviewPackage = ref(null)
const previewItems = computed(() => getContent(activePreviewPackage.value))
const openPreviewModal = (pkg) => {
  activePreviewPackage.value = pkg
  $('#previewPackageModal').modal('show')
}

const isEdit = ref(false)
const isSubmitting = ref(false)

const emptyForm = () => ({
  id: null,
  event_group_id: selectedGroupId.value,
  round_id: selectedRoundId.value,
  package_number: '',
  question_content: [{ question: '' }],
  is_available: true,
})

const form = ref(emptyForm())

const isFormValid = computed(() => {
  if (!String(form.value.package_number || '').trim()) return false
  return form.value.question_content.some(q => (q.question || '').trim() !== '')
})

const addSubQuestion = () => form.value.question_content.push({ question: '' })
const removeSubQuestion = (idx) => {
  if (form.value.question_content.length <= 1) return
  form.value.question_content.splice(idx, 1)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = emptyForm()
  $('#packageModal').modal('show')
}

const openEditModal = (pkg) => {
  isEdit.value = true
  const items = getContent(pkg).map(q => ({ question: q.question || '' }))

  form.value = {
    id: pkg.id,
    event_group_id: pkg.event_group_id,
    round_id: pkg.round_id,
    package_number: pkg.package_number,
    question_content: items.length ? items : [{ question: '' }],
    is_available: !!pkg.is_available,
  }
  $('#packageModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    event_group_id: form.value.event_group_id,
    round_id: form.value.round_id,
    package_number: String(form.value.package_number).trim(),
    is_available: form.value.is_available,
    question_content: form.value.question_content
      .filter(q => (q.question || '').trim() !== '')
      .map((q, i) => ({ number: i + 1, question: q.question.trim() })),
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-question-packages/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/event-question-packages', payload)
    }
    $('#packageModal').modal('hide')
    fetchPackages(meta.value.current_page)
  } catch (error) {
    alert(extractError(error, 'Gagal menyimpan paket soal.'))
  } finally {
    isSubmitting.value = false
  }
}

const deletePackage = async (pkg) => {
  if (!confirm(`Yakin ingin menghapus paket soal "${pkg.package_number}"?`)) return
  try {
    await axios.delete(`/api/v1/event-question-packages/${pkg.id}`)
    fetchPackages(meta.value.current_page)
  } catch (error) {
    alert(extractError(error, 'Gagal menghapus paket soal.'))
  }
}

onMounted(async () => {
  await loadGroups()
})
</script>