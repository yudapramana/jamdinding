<template>
  <section class="content-header py-1">
    <div class="container-fluid">

      <!-- Baris 1: Judul + Dropdown Select2 (compact) -->
      <div class="d-flex align-items-center flex-nowrap mb-1">
        <h1 class="m-0 font-weight-bold text-dark text-truncate pr-2">
          Verifikasi Dokumen Pegawai
        </h1>

        <div class="ml-auto">
          <select
            ref="workUnitSelect"
            class="select2bs4 wu-compact"
            data-placeholder="Semua Unit Kerja"
            style="width: 300px;"  
          >
            <option :value="null">Semua Unit Kerja</option>
            <option v-for="wu in workUnits" :key="wu.id" :value="wu.id">
              {{ wu.unit_code }} — {{ wu.unit_name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Baris 2: Sisa antrian + tombol (rapat & di kanan) -->
      <div class="d-flex align-items-center justify-content-end">
        <span class="badge badge-info badge-pill mr-2">
          Sisa: {{ remainingCountDisplay }}
        </span>
        <button class="btn btn-outline-primary btn-sm mr-1" @click="claimFive" :disabled="isClaiming">
          <i v-if="isClaiming" class="fas fa-spinner fa-spin mr-1"></i>
          Ambil 5 Dokumen
        </button>
        <button class="btn btn-secondary btn-sm" @click="refreshAll">Refresh</button>
      </div>

    </div>
  </section>





  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header d-flex">
          <input
            v-model="search"
            type="text"
            class="form-control mr-2"
            placeholder="Cari nama atau NIP pegawai (tugas saya)..."
          />
          <button class="btn btn-secondary btn-sm" @click="refreshAll">
            Refresh
          </button>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama</th>
                <th>NIP</th>
                <th>Jenis Dokumen</th>
                <th>Nama File</th>
                <th>Status</th>
                <th style="width: 160px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="documents.length === 0">
                <td colspan="7" class="text-center">
                  Belum ada dokumen yang ditugaskan ke Anda.
                  <br />
                  <small class="text-muted">Klik “Ambil 5 Dokumen” untuk mengambil dari antrian umum.</small>
                </td>
              </tr>
              <tr v-for="(doc, index) in documents" :key="doc.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>{{ doc.employee.full_name }}</td>
                <td>{{ doc.employee.nip }}</td>
                <td>{{ doc.doc_type.type_name }}</td>
                <td class="text-truncate" style="max-width: 240px;">{{ doc.file_name }}</td>
                <td>
                  <span
                    :class="{
                      'badge badge-warning': doc.status === 'Pending',
                      'badge badge-success': doc.status === 'Approved',
                      'badge badge-danger': doc.status === 'Rejected'
                    }"
                  >
                    {{ doc.status }}
                  </span>
                </td>
                <td>
                  <button v-if="settingStore.showMaintenanceBadge" type="button" class="btn btn-warning btn-sm mr-1 mb-1" disabled>
                      Maintenance
                  </button>
                  <button v-else class="btn btn-sm btn-primary mr-1 mb-1" @click="openVerifModal(doc)">
                    Verifikasi
                  </button>
                  
                  <button
                    class="btn btn-sm btn-outline-danger"
                    @click="releaseDoc(doc)"
                    :disabled="isReleasingId === doc.id"
                    title="Lepaskan assignment dokumen ini"
                  >
                    <i v-if="isReleasingId === doc.id" class="fas fa-spinner fa-spin mr-1"></i>
                    Lepas
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer clearfix">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
              Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} dokumen (tugas saya)
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Verifikasi -->
    <div class="modal fade" id="verifModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">Verifikasi Dokumen Pegawai</h5>
             <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
          </div>

          <div class="modal-body row">
            <div class="col-md-7">
              <div class="border rounded p-2" style="height: 500px; overflow: hidden;">
                <iframe
                  v-if="selectedDoc?.file_url && !pdfError"
                  ref="pdfFrame"
                  :src="iframeSrc"
                  width="100%"
                  height="100%"
                  frameborder="0"
                  style="border: 1px solid #ccc;"
                  @load="onIframeLoad"
                  @error="onIframeError"
                ></iframe>

                <div v-else class="text-muted text-center py-5">
                  <p v-if="pdfError">Gagal memuat dokumen. Pastikan file tersedia dan dapat diakses.</p>
                  <p v-else>File tidak tersedia.</p>
                </div>
              </div>
            </div>

            <div class="col-md-5">
              <div class="mb-3">
                <table class="table table-bordered table-sm">
                  <tbody>
                    <tr>
                      <th>Nama</th>
                      <td>{{ selectedDoc?.employee?.full_name || '-' }}</td>
                    </tr>
                    <tr>
                      <th>NIP</th>
                      <td>{{ selectedDoc?.employee?.nip || '-' }}</td>
                    </tr>
                    <tr>
                      <th>Jenis Dokumen</th>
                      <td>{{ selectedDoc?.doc_type?.type_name || '-' }}</td>
                    </tr>
                    <tr>
                      <th>Parameter</th>
                      <td v-html="selectedDoc?.parameter || '<i>no_param</i>'"></td>
                    </tr>
                    <tr>
                      <th>Filename</th>
                      <td style="font-size: smaller;">{{ selectedDoc?.file_name || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <form @submit.prevent="submitVerif">
                <div class="form-group">
                  <label>Status Verifikasi</label>
                  <select v-model="verifForm.status" class="form-control" required>
                    <option value="Approved">Disetujui</option>
                    <option value="Rejected">Ditolak</option>
                  </select>
                </div>

                <div class="form-group" v-if="verifForm.status === 'Rejected'">
                  <label>Pilih Alasan Penolakan (opsional)</label>
                  <select class="form-control mb-2" @change="onRejectionNoteSelect($event)">
                    <option value="">-- Pilih alasan penolakan standar --</option>
                    <option v-for="item in rejectionNotes" :key="item.code" :value="item.note">
                      {{ item.code }} - {{ item.note }}
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Catatan Verifikasi</label>
                  <textarea
                    v-model="verifForm.verif_notes"
                    class="form-control"
                    rows="4"
                    placeholder="Tulis catatan jika dokumen ditolak..."
                  ></textarea>
                </div>

                <div class="text-end mt-3">
                  <button v-if="settingStore.showMaintenanceBadge" type="button" class="btn btn-warning btn-sm mr-1 mb-1" disabled>
                      Maintenance
                  </button>
                  <button v-else type="submit" class="btn btn-sm btn-primary" :disabled="isSubmitting">
                    <i v-if="isSubmitting" class="fas fa-spinner fa-spin me-1"></i>
                    Simpan Verifikasi
                  </button>

                  
                </div>
              </form>
            </div>
          </div> <!-- /.modal-body -->
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, watch, computed, nextTick } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useSettingStore } from '../../stores/SettingStore'

const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);

// pastikan jQuery & Select2 sudah tersedia global (AdminLTE 3)
const workUnitSelect = ref(null)
// tetap gunakan state lama:
const workUnits = ref([])                // <— list dropdown
const selectedWorkUnit = ref(null)       // <— id yang dipilih (nullable)

// ⇩ muat Select2 **hanya jika belum ada**
const ensureSelect2Ready = async () => {
  if (!window.jQuery) throw new Error('jQuery belum ter-load');
  if (!window.jQuery.fn || !window.jQuery.fn.select2) {
    // lazy-load UMD Select2 ke instance jQuery global
    await import('admin-lte/plugins/select2/js/select2.full.min.js');
  }
};

// INIT select2 + sinkronisasi dengan Vue
const initSelect2WorkUnit = () => {
  const $el = window.$(workUnitSelect.value)
  // hancurkan dulu jika pernah di-init
  if ($el.hasClass('select2-hidden-accessible')) {
    $el.select2('destroy')
  }
  $el.select2({
    theme: 'bootstrap4',
    width: 'style',           // patuhi style="width: 240px;"
    dropdownAutoWidth: true,
    placeholder: 'Semua Unit Kerja',
    allowClear: true,
    minimumResultsForSearch: 8 // tampilkan search hanya bila opsi >= 8
  })
   // ⇩ nilai dari select2 dinormalisasi
  $el.on('change', function () {
    selectedWorkUnit.value = toWorkUnitId($el.val());
    fetchRemaining();
  });
  // set nilai awal
  const initVal = selectedWorkUnit.value == null ? null : String(selectedWorkUnit.value)
  $el.val(initVal).trigger('change.select2')
}

const search = ref('')
const documents = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const isClaiming = ref(false)
const isReleasingId = ref(null)

const remainingCount = ref(null) // sisa antrian global
const remainingCountDisplay = computed(() =>
  remainingCount.value === null ? '—' : remainingCount.value
)

const pdfFrame = ref(null)
const pdfError = ref(false)
const selectedDoc = ref(null)
const verifForm = ref({ status: '', verif_notes: '' })

const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
const iframeSrc = computed(() => {
  const url = selectedDoc.value?.file_url
  if (!url) return ''
  return isMobile ? `https://docs.google.com/gview?url=${encodeURIComponent(url)}` : url
})

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1
})

const rejectionNotes = [
  { code: 'G001', note: 'Dokumen yang diunggah tidak sesuai dengan jenis dokumen yang dipilih.' },
  { code: 'G002', note: 'Identitas pada dokumen tidak sesuai dengan data pegawai.' },
  { code: 'G003', note: 'Dokumen bukan termasuk dokumen resmi yang dibutuhkan.' },
  { code: 'G004', note: 'Informasi pada dokumen tidak terbaca dengan jelas (buram/tidak fokus).' },
  { code: 'G005', note: 'Dokumen tidak mencantumkan informasi identitas penting (nama, tanggal lahir, NIP/NIM, dsb.).' },
  { code: 'G006', note: 'Dokumen tidak mencantumkan cap resmi, tanda tangan, atau atribut legalitas lainnya.' },
  { code: 'G007', note: 'Format atau isi dokumen tidak relevan dengan tujuan verifikasi.' },
  { code: 'G008', note: 'Dokumen rusak atau tidak dapat dibuka.' },
  { code: 'G009', note: 'Tanggal dokumen tidak valid atau tidak sesuai konteks.' },
  { code: 'G010', note: 'Dokumen mengandung data palsu atau terindikasi tidak asli.' }
]


// === API helper: Work Units ===
const fetchWorkUnits = async () => {
  try {
    // Endpoint sederhana: GET /api/work-units -> [{id, unit_name, unit_code, parent_unit}]
    const { data } = await axios.get('/api/work-units/fetch')
    // Antisipasi bentuk data: bisa array langsung atau {data: []}
    workUnits.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (e) {
    workUnits.value = []
  }
}


// === API calls ===
const fetchDocuments = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/emp-documents', {
      params: {
        search: search.value,
        page,
        per_page: meta.value.per_page
      }
    })
    documents.value = res.data.data || []
    meta.value = {
      ...meta.value,
      ...res.data.meta,
      ...res.data
    }
  } catch (err) {
    console.error('Gagal memuat dokumen', err)
  } finally {
    isLoading.value = false
  }
}

// utils
const toWorkUnitId = (val) => {
  if (val === undefined || val === null) return null;
  if (val === '' || val === 'null') return null;
  const n = parseInt(val, 10);
  return Number.isNaN(n) ? null : n;
};

const fetchRemaining = async () => {
  try {
    const params = {};
    const wu = toWorkUnitId(selectedWorkUnit.value);
    if (wu !== null) params.id_work_unit = wu; // ⇦ kirim hanya jika angka valid

    const { data } = await axios.get('/api/emp-documents/remaining', { params });
    remainingCount.value = typeof data?.remaining === 'number' ? data.remaining : null;
  } catch (e) {
    remainingCount.value = null;
  }
};

const refreshAll = async () => {
  await Promise.all([fetchDocuments(meta.value.current_page), fetchRemaining()])
}

const claimFive = async () => {
  isClaiming.value = true
  try {
    // ambil 5 dokumen dari antrian
    // kirim id_work_unit yang terpilih ke backend
    const { data } = await axios.post('/api/emp-documents/claim', {
      count: 5,
      id_work_unit: toWorkUnitId(selectedWorkUnit.value)
    })
    await refreshAll()

    const claimedCount = data?.claimed ?? (Array.isArray(data?.data) ? data.data.length : (data?.data ? 1 : 0))
    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: `${claimedCount} dokumen berhasil diambil dari antrian.`,
      timer: 1800,
      showConfirmButton: false
    })

    // opsional: buka modal untuk dokumen pertama hasil klaim
    if (Array.isArray(data?.data) && data.data.length) {
      openVerifModal(data.data[0])
    } else if (documents.value.length) {
      openVerifModal(documents.value[0])
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'Tidak ada dokumen tersedia untuk di-claim'
    Swal.fire({ icon: 'info', title: 'Info', text: msg })
  } finally {
    isClaiming.value = false
  }
}

const releaseDoc = async (doc) => {
  if (!doc?.id) return
  const ok = confirm('Lepaskan assignment dokumen ini? Dokumen akan kembali ke antrian umum.')
  if (!ok) return

  isReleasingId.value = doc.id
  try {
    await axios.post(`/api/emp-documents/${doc.id}/release`)
    await refreshAll()
    Swal.fire({
      icon: 'success',
      title: 'Dilepas',
      text: 'Dokumen berhasil dilepas dari tugas Anda.',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal melepas dokumen.' })
  } finally {
    isReleasingId.value = null
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= meta.value.last_page) {
    fetchDocuments(page)
  }
}

const openVerifModal = (doc) => {
  selectedDoc.value = doc
  verifForm.value = {
    status: doc.status === 'Pending' ? 'Approved' : doc.status,
    verif_notes: doc.verif_notes || ''
  }
  $('#verifModal').modal('show')
}

const submitVerif = async () => {
  isSubmitting.value = true
  try {
    const response = await axios.put(`/api/emp-documents/${selectedDoc.value.id}/verify`, verifForm.value)
    $('#verifModal').modal('hide')
    await refreshAll()

    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: response.data.message || 'Dokumen berhasil diverifikasi.',
      timer: 2000,
      showConfirmButton: false
    })
  } catch (error) {
    let message = 'Gagal memverifikasi dokumen.'
    if (error.response) {
      if (error.response.status === 409 && error.response.data.code === 'DOCUMENT_ALREADY_VERIFIED') {
        message = error.response.data.message
      } else if (error.response.data.message) {
        message = error.response.data.message
      }
    }

    Swal.fire({
      icon: 'error',
      title: 'Verifikasi Gagal',
      text: message
    })
    $('#verifModal').modal('hide')
    await refreshAll()
  } finally {
    isSubmitting.value = false
  }
}

const onRejectionNoteSelect = (event) => {
  const selected = event.target.value
  if (selected) verifForm.value.verif_notes = selected
}

// === PDF helpers ===
const onIframeLoad = () => { pdfError.value = false }
const onIframeError = () => { pdfError.value = true }

// Cek file tersedia (HEAD) setiap kali file_url berubah
watch(
  () => selectedDoc.value?.file_url,
  async (newUrl) => {
    pdfError.value = false
    if (!newUrl) return
    try {
      const res = await fetch(newUrl, { method: 'HEAD' })
      if (!res.ok || !res.headers.get('content-type')?.includes('pdf')) {
        pdfError.value = true
      }
    } catch (err) {
      pdfError.value = true
    }
  }
)



// Debounce search
watch(search, useDebounceFn(() => fetchDocuments(1), 300));



// di onMounted
onMounted(async () => {
  await fetchWorkUnits();  // isi <option> dulu
  await nextTick();        // pastikan <select> sudah ter-render
  await ensureSelect2Ready();
  initSelect2WorkUnit();   // baru init
  await refreshAll();
});

// jika daftar work units berubah (misal dari API), re-init select2
watch(() => workUnits.value.length, async () => {
  await nextTick()
  initSelect2WorkUnit()
})

// jika selectedWorkUnit berubah dari tempat lain (opsional), sync ke select2
// watch(selectedWorkUnit, (val) => {
//   const $el = window.$(workUnitSelect.value);
//   const want = val == null ? null : String(val);
//   const cur  = $el.val() ?? null; // bisa string atau null
//   if (cur !== want) $el.val(want).trigger('change.select2');
// });
</script>

<style scoped>
  .modal-dialog.modal-fullscreen {
    width: 100% !important;
    max-width: 100% !important;
    height: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  .modal-content {
    height: 100% !important;
    border: 0;
    border-radius: 0;
  }
</style>


<style scoped>
.content-header { padding-top: .25rem; padding-bottom: .25rem; }
.content-header h1 { font-size: 1.05rem; line-height: 1.2; }
.badge.badge-pill { padding: .25rem .5rem; font-weight: 600; }
.input-group-sm .form-control { height: calc(1.8125rem + 2px); padding-top: .125rem; padding-bottom: .125rem; }
/* pastikan select2 container mengikuti tinggi input-group-sm */
.select2-container--bootstrap4 .select2-selection--single {
  min-height: calc(1.8125rem + 2px);
  line-height: 1.2;
  padding: .125rem .5rem;
}
.select2-container--bootstrap4 .select2-selection__arrow { height: 100%; }
</style>

<style scoped>
/* tinggi & font-size kecil */
.select2-container--bootstrap4 .select2-selection--single {
  min-height: 30px;                 /* ~sm */
  height: 30px;
  padding: 2px 6px;
  line-height: 1.2;
  font-size: .875rem;               /* sm */
  border-radius: .2rem;
}

/* rapikan teks & arrow */
.select2-container--bootstrap4 .select2-selection__rendered {
  padding-left: 2px;
  padding-right: 22px;              /* ruang untuk arrow */
}
.select2-container--bootstrap4 .select2-selection__arrow {
  height: 100%;
  right: 6px;
}

/* kelas helper untuk dropdown ini (opsional) */
.wu-compact + .select2-container--bootstrap4 {
  font-size: .875rem;
}
</style>