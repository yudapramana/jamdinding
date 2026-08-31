<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Mandat {{ regionLabel }}</h1>
          <p class="mb-0 text-muted text-sm">
            <span v-if="isAdminView">
              Daftar mandat seluruh {{ regionLabelPlural }} pada event aktif.
            </span>
            <span v-else>
              Upload mandat {{ regionLabel }} Anda untuk dapat mengelola bank data peserta.
            </span>
          </p>
        </div>

        <!-- Tombol upload hanya muncul jika: belum ada mandat, ATAU mandat sebelumnya ditolak -->
        <button
          v-if="!isAdminView && canUploadMandate"
          class="btn btn-primary btn-sm"
          :disabled="!eventId"
          @click="openUploadModal"
        >
          <i class="fas fa-upload mr-1"></i>
          {{ myMandate ? 'Upload Ulang Mandat' : 'Upload Mandat' }}
        </button>

        <!-- Info status ketika tombol upload tidak tersedia (menunggu persetujuan / sudah disetujui) -->
        <span
          v-else-if="!isAdminView && myMandate"
          class="badge"
          :class="statusBadgeClass(myMandate.status)"
        >
          {{ statusLabel(myMandate.status) }}
        </span>
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
        <div class="card-header py-2">
          <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex flex-wrap align-items-center gap-2">
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

              <select
                v-if="isAdminView"
                v-model="filters.status"
                class="form-control form-control-sm w-auto"
              >
                <option value="">Semua Status</option>
                <option value="not_uploaded">Belum Upload</option>
                <option value="uploaded">Menunggu Persetujuan</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
              </select>

              <!-- ➕ TOMBOL REFRESH -->
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                title="Muat ulang data mandat"
                :disabled="isLoading"
                @click="refreshMandates"
              >
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': isLoading }"></i>
                <span class="ml-1 d-none d-sm-inline">Refresh</span>
              </button>
            </div>
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>{{ regionLabel }}</th>
                <th style="width: 130px;" class="text-center">Status</th>
                <th style="width: 100px;" class="text-center">File</th>
                <th v-if="isAdminView">Diupload Oleh</th>
                <th style="width: 150px;">Tanggal Upload</th>
                <th v-if="isAdminView">Catatan</th>
                <th style="width: 140px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td :colspan="isAdminView ? 8 : 6" class="text-center">
                  Memuat data mandat...
                </td>
              </tr>
              <tr v-else-if="mandates.length === 0">
                <td :colspan="isAdminView ? 8 : 6" class="text-center">
                  <template v-if="isAdminView">
                    Belum ada data mandat untuk event ini.
                  </template>
                  <template v-else>
                    Anda belum mengupload mandat untuk event ini.
                    <br />
                    <small class="text-muted">
                      Klik <strong>Upload Mandat</strong> untuk memulai.
                    </small>
                  </template>
                </td>
              </tr>
              <tr v-for="(item, index) in mandates" :key="item.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>
                  <strong>{{ item.region_name || '-' }}</strong>
                </td>
                <td class="text-center">
                  <span class="badge" :class="statusBadgeClass(item.status)">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="text-center">
                  <a
                    v-if="item.mandate_file_url"
                    :href="item.mandate_file_url"
                    target="_blank"
                    class="btn btn-outline-primary btn-xs"
                    title="Lihat File"
                  >
                    <i class="fas fa-file-alt"></i>
                  </a>
                  <span v-else class="text-muted">-</span>
                </td>
                <td v-if="isAdminView">
                  {{ item.uploaded_by?.name || '-' }}
                </td>
                <td>
                  {{ item.uploaded_at ? formatDateTime(item.uploaded_at) : '-' }}
                </td>
                <td v-if="isAdminView">
                  <small class="text-muted">{{ item.notes || '-' }}</small>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm" v-if="isAdminView">
                    <button
                      v-if="item.status === 'uploaded'"
                      class="btn btn-success btn-xs"
                      title="Setujui"
                      @click="approveMandate(item)"
                    >
                      <i class="fas fa-check"></i>
                    </button>
                    <button
                      v-if="item.status === 'uploaded'"
                      class="btn btn-danger btn-xs"
                      title="Tolak"
                      @click="openRejectModal(item)"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                    <span v-if="!['uploaded'].includes(item.status)" class="text-muted text-xs">
                      -
                    </span>
                  </div>
                  <span v-else class="text-muted text-xs">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} data
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">
                  «
                </a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">
                  »
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: UPLOAD MANDAT (khusus role pendaftaran) -->
    <div class="modal fade" id="uploadMandateModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              <i class="fas fa-upload mr-1"></i>
              Upload Mandat {{ regionLabel }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <form @submit.prevent="submitUpload">
              <div class="form-group mb-2">
                <label class="mb-1">File Mandat <span class="text-danger">*</span></label>
                <div class="custom-file">
                  <input
                    type="file"
                    class="custom-file-input"
                    id="mandateFileInput"
                    accept="application/pdf,image/jpeg,image/png,image/jpg"
                    @change="onMandateFileChange"
                    :class="{ 'is-invalid': uploadErrors.mandate_file }"
                  />
                  <label class="custom-file-label" for="mandateFileInput">
                    {{ mandateFile ? mandateFile.name : 'Pilih file...' }}
                  </label>
                </div>
                <small class="text-muted d-block mt-1 text-xs">
                  Format <strong>PDF/JPG/PNG</strong>, maksimal <strong>2 MB</strong>.
                </small>
                <div class="invalid-feedback d-block" v-if="uploadErrors.mandate_file">
                  {{ uploadErrors.mandate_file }}
                </div>
              </div>

              <div class="form-group mb-2">
                <label class="mb-1">Catatan (opsional)</label>
                <textarea
                  v-model="uploadNotes"
                  rows="2"
                  class="form-control form-control-sm"
                  placeholder="Catatan tambahan bila diperlukan"
                ></textarea>
              </div>

              <div v-if="myMandate?.status === 'rejected' && myMandate?.notes" class="alert alert-danger py-2 px-3 text-sm">
                <strong>Alasan penolakan sebelumnya:</strong> {{ myMandate.notes }}
              </div>

              <div class="text-right mt-3">
                <button type="submit" class="btn btn-sm btn-primary" :disabled="isUploading">
                  <i v-if="isUploading" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-save mr-1"></i>
                  Simpan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: TOLAK MANDAT (khusus admin) -->
    <div class="modal fade" id="rejectMandateModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              <i class="fas fa-times-circle mr-1"></i>
              Tolak Mandat
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <p class="text-sm">
              Menolak mandat <strong>{{ rejectTarget?.region_name }}</strong>.
              Wilayah tersebut harus mengupload ulang.
            </p>
            <div class="form-group mb-2">
              <label class="mb-1">Alasan Penolakan <span class="text-danger">*</span></label>
              <textarea
                v-model="rejectNotes"
                rows="3"
                class="form-control form-control-sm"
                placeholder="Jelaskan alasan penolakan..."
                required
              ></textarea>
            </div>
            <div class="text-right mt-3">
              <button
                type="button"
                class="btn btn-sm btn-danger"
                :disabled="isRejecting || !rejectNotes"
                @click="submitReject"
              >
                <i v-if="isRejecting" class="fas fa-spinner fa-spin mr-1"></i>
                <i v-else class="fas fa-times mr-1"></i>
                Tolak Mandat
              </button>
            </div>
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
import { formatDateTime } from '../EventParticipantHelpers'

const authUserStore = useAuthUserStore()

const currentUser = computed(() => authUserStore.user || {})
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

const roleSlug = computed(() => currentUser.value?.role?.slug || '')
const isAdminView = computed(() => ['superadmin', 'admin_event'].includes(roleSlug.value))

// label region dinamis, di-set dari response meta backend (getContingentRegionType)
const regionTypeLabel = ref('district')

const regionLabelMap = {
  province: 'Provinsi',
  regency: 'Kabupaten/Kota',
  district: 'Kecamatan',
  village: 'Kelurahan/Desa',
}

const regionLabel = computed(() => regionLabelMap[regionTypeLabel.value] || 'Wilayah')
const regionLabelPlural = computed(() => regionLabel.value)

// ==================================================
// STATE TABEL
// ==================================================
const mandates = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const perPage = ref(10)
const isLoading = ref(false)
const filters = ref({ status: '' })

// mandat milik user sendiri (role pendaftaran) — dipakai untuk tombol upload
const myMandate = computed(() => (!isAdminView.value ? mandates.value[0] || null : null))

// ➕ TAMBAHAN: tombol upload hanya boleh tampil jika belum ada mandat sama sekali,
// atau mandat sebelumnya berstatus 'rejected'. Ketika 'uploaded' (menunggu persetujuan)
// atau 'approved' (disetujui), tombol upload disembunyikan.
const canUploadMandate = computed(() => {
  if (!myMandate.value) return true
  return myMandate.value.status === 'rejected'
})

const fetchMandates = async (page = 1) => {
  if (!eventId.value) return

  isLoading.value = true
  try {
    const res = await axios.get(`/api/v1/events/${eventId.value}/region-mandates`, {
      params: {
        page,
        per_page: perPage.value,
        status: filters.value.status || '',
      },
    })

    const paginated = res.data.data
    mandates.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }

    if (res.data.meta?.region_type_label) {
      regionTypeLabel.value = res.data.meta.region_type_label
    }
  } catch (error) {
    console.error('Gagal memuat data mandat:', error)
    if (error.response?.status === 401) {
      authUserStore.logout()
    } else {
      const msg = error.response?.data?.message || 'Gagal memuat data mandat.'
      Swal.fire('Gagal', msg, 'error')
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchMandates(page)
}

// ➕ TAMBAHAN: refresh data di halaman saat ini (tanpa reset ke halaman 1)
const refreshMandates = () => {
  fetchMandates(meta.value.current_page)
}

// ==================================================
// STATUS HELPERS
// ==================================================
const statusLabel = (status) => {
  const map = {
    not_uploaded: 'Belum Upload',
    uploaded: 'Menunggu Persetujuan',
    approved: 'Disetujui',
    rejected: 'Ditolak',
  }
  return map[status] || status
}

const statusBadgeClass = (status) => {
  const map = {
    not_uploaded: 'badge-secondary',
    uploaded: 'badge-info',
    approved: 'badge-success',
    rejected: 'badge-danger',
  }
  return map[status] || 'badge-light'
}

// ==================================================
// UPLOAD (role pendaftaran)
// ==================================================
const mandateFile = ref(null)
const uploadNotes = ref('')
const uploadErrors = ref({})
const isUploading = ref(false)

const openUploadModal = () => {
  // Guard tambahan: cegah membuka modal upload jika status tidak mengizinkan
  // (misalnya dipanggil langsung/manual di luar tombol yang sudah di-guard v-if)
  if (!canUploadMandate.value) {
    Swal.fire(
      'Tidak dapat upload',
      myMandate.value?.status === 'approved'
        ? 'Mandat Anda sudah disetujui dan tidak dapat diupload ulang.'
        : 'Mandat Anda sedang menunggu persetujuan.',
      'info'
    )
    return
  }

  mandateFile.value = null
  uploadNotes.value = ''
  uploadErrors.value = {}
  $('#uploadMandateModal').modal('show')
}

const onMandateFileChange = (e) => {
  const file = e.target.files[0]
  mandateFile.value = file || null
  uploadErrors.value.mandate_file = ''
}

const submitUpload = async () => {
  uploadErrors.value = {}

  if (!mandateFile.value) {
    uploadErrors.value.mandate_file = 'File mandat wajib diupload.'
    return
  }

  isUploading.value = true
  try {
    const fd = new FormData()
    fd.append('mandate_file', mandateFile.value)
    if (uploadNotes.value) fd.append('notes', uploadNotes.value)

    await axios.post(`/api/v1/events/${eventId.value}/region-mandates/upload`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    Swal.fire('Berhasil', 'Mandat berhasil diupload dan menunggu persetujuan.', 'success')
    $('#uploadMandateModal').modal('hide')

    await fetchMandates(meta.value.current_page)
    // refresh status mandat global di store, supaya halaman lain (Bank Data) ikut update
    await authUserStore.fetchMandateStatus()
  } catch (error) {
    console.error('Gagal upload mandat:', error)

    if (error.response?.status === 422) {
      const errors = error.response.data?.errors || {}
      Object.keys(errors).forEach((key) => {
        uploadErrors.value[key] = errors[key][0]
      })
    }

    const msg = error.response?.data?.message || 'Gagal mengupload mandat.'
    Swal.fire('Gagal', msg, 'error')
  } finally {
    isUploading.value = false
  }
}

// ==================================================
// APPROVE / REJECT (role admin)
// ==================================================
const approveMandate = async (item) => {
  const result = await Swal.fire({
    title: 'Setujui Mandat?',
    text: `Setujui mandat untuk ${item.region_name}?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Setujui',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  try {
    await axios.post(`/api/v1/region-mandates/${item.id}/approve`)
    Swal.fire('Berhasil', 'Mandat berhasil disetujui.', 'success')
    fetchMandates(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyetujui mandat:', error)
    const msg = error.response?.data?.message || 'Gagal menyetujui mandat.'
    Swal.fire('Gagal', msg, 'error')
  }
}

const rejectTarget = ref(null)
const rejectNotes = ref('')
const isRejecting = ref(false)

const openRejectModal = (item) => {
  rejectTarget.value = item
  rejectNotes.value = ''
  $('#rejectMandateModal').modal('show')
}

const submitReject = async () => {
  if (!rejectNotes.value) return

  isRejecting.value = true
  try {
    await axios.post(`/api/v1/region-mandates/${rejectTarget.value.id}/reject`, {
      notes: rejectNotes.value,
    })

    Swal.fire('Berhasil', 'Mandat berhasil ditolak.', 'success')
    $('#rejectMandateModal').modal('hide')
    fetchMandates(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menolak mandat:', error)
    const msg = error.response?.data?.message || 'Gagal menolak mandat.'
    Swal.fire('Gagal', msg, 'error')
  } finally {
    isRejecting.value = false
  }
}

// ==================================================
// WATCHERS & MOUNTED
// ==================================================
watch(
  () => perPage.value,
  () => fetchMandates(1)
)

watch(
  () => filters.value.status,
  () => fetchMandates(1)
)

watch(
  () => eventId.value,
  (val) => {
    if (val) fetchMandates(1)
  }
)

onMounted(() => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event terlebih dahulu.',
      'info'
    )
  } else {
    fetchMandates()
  }
})
</script>

<style scoped>
.btn-xs {
  padding: 2px 5px !important;
  font-size: 0.65rem !important;
  line-height: 1 !important;
}

.text-xs {
  font-size: 0.75rem;
}

.gap-2 {
  gap: 0.5rem;
}
</style>