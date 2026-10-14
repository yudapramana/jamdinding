<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Bank Maqra Event</h1>
          <p class="mb-0 text-muted text-sm">
            Kelola data undian maqra (Surat & Ayat) untuk event yang sedang aktif.
          </p>
        </div>
        <div class="d-flex gap-2">
          <!-- Tombol Download Template -->
          <a
            class="btn btn-outline-success btn-sm mr-2"
            :href="templateImportUrl"
            download="Template_Import_Maqra.xlsx"
          >
            <i class="fas fa-download mr-1"></i>
            Download Template
          </a>

          <!-- Tombol Import Baru -->
          <button
            class="btn btn-success btn-sm"
            :disabled="!selectedGroupId"
            @click="openImportModal"
          >
            <i class="fas fa-file-import mr-1"></i>
            Import Excel/CSV
          </button>
          
          <button
            class="btn btn-primary btn-sm"
            :disabled="!selectedGroupId"
            @click="openCreateModal"
          >
            <i class="fas fa-plus mr-1"></i>
            Tambah Maqra
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">
            <!-- LEFT: perPage + golongan -->
            <div class="d-flex align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select
                v-model.number="perPage"
                class="form-control form-control-sm w-auto mr-2"
                :disabled="!selectedGroupId"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <label class="mb-0 text-sm text-muted mr-2">|</label>

              <select
                v-model="selectedGroupId"
                class="form-control form-control-sm w-auto"
                style="min-width: 260px"
                @change="fetchMaqras(1)"
              >
                <option value="">-- Pilih Golongan Cabang Lomba --</option>
                <option v-for="group in filteredEventGroups" :key="group.id" :value="group.id">
                  {{ group.full_name }}
                </option>
              </select>
            </div>

            <!-- RIGHT: search -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width: 220px"
              placeholder="Cari nama surat..."
              :disabled="!selectedGroupId"
            />
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <div v-if="!selectedGroupId" class="p-5 text-center text-muted">
            <i class="fas fa-filter fa-3x mb-3 text-light"></i>
            <h5>Silakan pilih Golongan terlebih dahulu</h5>
            <p class="text-sm">Data maqra akan ditampilkan berdasarkan golongan yang dipilih.</p>
          </div>

          <table v-else class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 50px;" class="text-center">No</th>
                <th>Surat</th>
                <th class="text-center">Ayat</th>
                <th class="text-center">Juz / Hal</th>
                <th class="text-center">Status</th>
                <th style="width: 140px;" class="text-center">Aksi</th> <!-- Diperlebar untuk 3 tombol -->
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center py-4">
                  <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data maqra...
                </td>
              </tr>
              <tr v-else-if="maqras.length === 0">
                <td colspan="6" class="text-center py-4">Belum ada data maqra untuk golongan ini.</td>
              </tr>
              <tr v-for="(maqra, index) in maqras" :key="maqra.id">
                <td class="text-center">{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>
                  <strong>{{ maqra.surah_number }}. Surah {{ maqra.surah_name }}</strong>
                </td>
                <td class="text-center">
                  <span class="badge badge-light border">
                    Ayat {{ maqra.start_ayah }} - {{ maqra.end_ayah }}
                  </span>
                </td>
                <td class="text-center text-muted">
                  {{ maqra.juz ? `Juz ${maqra.juz}` : '-' }} 
                  / 
                  {{ maqra.page ? `Hal ${maqra.page}` : '-' }}
                </td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="maqra.is_available ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ maqra.is_available ? 'Tersedia' : 'Dicabut' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <!-- TOMBOL BARU: PREVIEW AYAT -->
                    <button
                      class="btn btn-outline-info"
                      title="Lihat Ayat"
                      @click="openAyatPreviewModal(maqra)"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button
                      class="btn btn-outline-warning"
                      title="Edit"
                      @click="openEditModal(maqra)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-outline-danger"
                      title="Hapus"
                      @click="deleteMaqra(maqra)"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="selectedGroupId" class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} maqra
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL BARU: PREVIEW AYAT -->
    <div class="modal fade" id="previewAyatModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header py-2 bg-light">
            <h5 class="modal-title">
              <i class="fas fa-quran mr-2 text-success"></i>
              Pratinjau Maqra: Surah {{ activePreviewMaqra?.surah_name }} 
              (Ayat {{ activePreviewMaqra?.start_ayah }} - {{ activePreviewMaqra?.end_ayah }})
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body bg-white">
            <div v-if="isLoadingAyat" class="text-center py-5">
              <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
              <p>Mengambil data ayat...</p>
            </div>
            
            <div v-else-if="ayatPreviewData.length === 0" class="text-center py-5 text-muted">
              <i class="fas fa-exclamation-triangle fa-2x mb-3 text-warning"></i>
              <p>Gagal memuat teks ayat. Pastikan koneksi internet tersedia.</p>
            </div>

            <div v-else class="ayat-container">
              <div 
                v-for="(ayat, idx) in ayatPreviewData" 
                :key="idx"
                class="ayat-item border-bottom pb-4 mb-4"
              >
                <div class="d-flex justify-content-between mb-2">
                  <span class="badge badge-success align-self-start mt-2">Ayat {{ ayat.nomorAyat }}</span>
                  <!-- Teks Arab rata kanan (RTL) -->
                  <div class="text-right text-arab" dir="rtl" style="font-size: 28px; line-height: 2.2; font-family: 'Amiri', 'Traditional Arabic', serif;">
                    {{ ayat.teksArab }} 
                    <span class="arab-number ml-2">({{ toArabicNumber(ayat.nomorAyat) }})</span>
                  </div>
                </div>
                <!-- Teks Latin/Terjemahan rata kiri -->
                <div class="mt-3 text-muted text-justify">
                  <p class="mb-1 text-sm font-italic" v-html="ayat.teksLatin"></p>
                  <p class="mb-0 text-dark">{{ ayat.teksIndonesia }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Tambah/Edit Maqra -->
    <div class="modal fade" id="maqraModal" tabindex="-1" role="dialog" data-backdrop="static">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              <i class="fas fa-book-open mr-2"></i>
              {{ isEdit ? 'Edit Maqra' : 'Tambah Maqra Baru' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-3">
            <form @submit.prevent="submitForm">
              
              <div class="alert alert-info text-sm py-2 mb-3">
                <strong>Golongan Target:</strong> 
                {{ selectedGroupData ? `${selectedGroupData.full_name}` : '-' }}
              </div>

              <div class="row">
                <div class="col-md-6 border-right">
                  <h6 class="border-bottom pb-2 mb-3 text-primary">Detail Surat</h6>
                  
                  <div class="form-group mb-3">
                    <label class="mb-1">Pilih Surat <span class="text-danger">*</span></label>
                    <select
                      v-model="form.surah_number"
                      class="form-control"
                      required
                      @change="handleSurahChange"
                    >
                      <option value="">-- Pilih Surat --</option>
                      <option v-for="s in quranSurahList" :key="s.number" :value="s.number">
                        {{ String(s.number).padStart(3, '0') }}. {{ s.name }} ({{ s.numberOfAyahs }} Ayat)
                      </option>
                    </select>
                  </div>
                  
                  <div class="form-group mb-3">
                    <label class="mb-1">Nama Surat</label>
                    <input
                      v-model="form.surah_name"
                      class="form-control bg-light"
                      readonly
                    />
                  </div>

                  <div class="row">
                    <div class="col-6">
                      <div class="form-group mb-2">
                        <label class="mb-1">Ayat Mulai <span class="text-danger">*</span></label>
                        <input
                          v-model.number="form.start_ayah"
                          type="number"
                          class="form-control"
                          min="1"
                          :max="currentSurahMaxAyah"
                          required
                        />
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="form-group mb-2">
                        <label class="mb-1">Ayat Akhir <span class="text-danger">*</span></label>
                        <input
                          v-model.number="form.end_ayah"
                          type="number"
                          class="form-control"
                          :min="form.start_ayah || 1"
                          :max="currentSurahMaxAyah"
                          required
                        />
                      </div>
                    </div>
                  </div>
                  <small v-if="currentSurahMaxAyah" class="text-muted text-xs">
                    *Maksimal ayat untuk surat ini adalah {{ currentSurahMaxAyah }}.
                  </small>
                </div>

                <div class="col-md-6 pl-4">
                  <h6 class="border-bottom pb-2 mb-3 text-primary">Atribut Penanda</h6>
                  
                  <div class="row">
                    <div class="col-6">
                      <div class="form-group mb-3">
                        <label class="mb-1">
                          Juz <small class="text-muted">(Opsional)</small>
                          <!-- Spinner akan muncul saat loading -->
                          <i v-if="isFetchingJuzPage" class="fas fa-spinner fa-spin text-primary ml-1 text-xs"></i>
                        </label>
                        <input
                          v-model.number="form.juz"
                          type="number"
                          class="form-control"
                          min="1"
                          max="30"
                          :placeholder="isFetchingJuzPage ? 'Mencari...' : 'Contoh: 15'"
                          :disabled="isFetchingJuzPage"
                        />
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="form-group mb-3">
                        <label class="mb-1">
                          Halaman <small class="text-muted">(Opsional)</small>
                          <!-- Spinner akan muncul saat loading -->
                          <i v-if="isFetchingJuzPage" class="fas fa-spinner fa-spin text-primary ml-1 text-xs"></i>
                        </label>
                        <input
                          v-model.number="form.page"
                          type="number"
                          class="form-control"
                          min="1"
                          max="604"
                          :placeholder="isFetchingJuzPage ? 'Mencari...' : 'Contoh: 280'"
                          :disabled="isFetchingJuzPage"
                        />
                      </div>
                    </div>
                  </div>

                  <div class="form-group mb-3">
                    <label class="mb-1">Status Ketersediaan</label>
                    <div class="custom-control custom-switch mt-1">
                      <input 
                        type="checkbox" 
                        class="custom-control-input" 
                        id="isAvailableSwitch" 
                        v-model="form.is_available"
                      >
                      <label class="custom-control-label font-weight-normal" for="isAvailableSwitch">
                        {{ form.is_available ? 'Tersedia untuk Diundi' : 'Sudah Dicabut / Dinonaktifkan' }}
                      </label>
                    </div>
                    <small class="form-text text-muted">
                      Jika dimatikan, maqra ini tidak akan muncul saat proses cabut undian.
                    </small>
                  </div>
                </div>
              </div>

              <div class="text-right mt-4 pt-3 border-top">
                <button type="button" class="btn btn-secondary btn-sm mr-2" data-dismiss="modal">Batal</button>
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="isSubmitting || !isFormValid"
                >
                  <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-save mr-1"></i>
                  Simpan Maqra
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>

    <!-- Modal Import Maqra -->
    <div class="modal fade" id="importMaqraModal" tabindex="-1" role="dialog" data-backdrop="static">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header py-2 bg-success text-white">
            <h5 class="modal-title">
              <i class="fas fa-file-import mr-2"></i>
              Import Data Maqra
            </h5>
            <button type="button" class="close text-white" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-3">
             <div class="alert alert-info text-sm py-2 mb-3">
                <strong>Golongan Target:</strong> 
                {{ selectedGroupData ? `${selectedGroupData.full_name}` : '-' }}
              </div>

            <form @submit.prevent="submitImport">
              <div class="form-group">
                <label>Pilih File Excel / CSV <span class="text-danger">*</span></label>
                <div class="custom-file">
                  <input 
                    type="file" 
                    class="custom-file-input" 
                    id="customFile" 
                    accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                    @change="handleFileUpload"
                    required
                  >
                  <label class="custom-file-label" for="customFile">
                    {{ selectedFile ? selectedFile.name : 'Pilih file...' }}
                  </label>
                </div>
                <a
                  :href="templateImportUrl"
                  download="Template_Import_Maqra.xlsx"
                  class="btn btn-outline-success btn-sm mt-2"
                >
                  <i class="fas fa-download mr-1"></i>
                  Download Template Excel
                </a>
                <small class="form-text text-muted mt-2">
                  Format header harus berurutan: Nomor Surat, Nama Surat, Ayat Mulai, Ayat Akhir, Juz (opsional), Halaman (opsional), Status Ketersediaan (1=Tersedia, 0=Dicabut).
                </small>
              </div>

              <!-- Menampilkan Error jika ada baris yang gagal diimport -->
              <div v-if="importErrors.length > 0" class="alert alert-warning text-sm mt-3" style="max-height: 150px; overflow-y: auto;">
                <strong>Perhatian!</strong> Beberapa baris gagal diimpor:
                <ul class="mb-0 pl-3">
                  <li v-for="(err, idx) in importErrors" :key="idx">{{ err }}</li>
                </ul>
              </div>

              <div class="text-right mt-4 pt-3 border-top">
                <button type="button" class="btn btn-secondary btn-sm mr-2" data-dismiss="modal">Batal</button>
                <button
                  type="submit"
                  class="btn btn-sm btn-success"
                  :disabled="isImporting || !selectedFile"
                >
                  <i v-if="isImporting" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-upload mr-1"></i>
                  Upload & Import
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
import * as QuranMeta from 'quran-meta'
import * as QuranMetai18n from "quran-meta/i18n"
import * as QuranMetahafs from "quran-meta/hafs"

const masterStore = useMasterDataStore()
const authUserStore = useAuthUserStore()

// =============================
// FITUR BARU: IMPORT EXCEL/CSV
// =============================
// File template ada di folder public/templates/
const templateImportUrl = '/templates/Template_Import_Maqra.xlsx'

const selectedFile = ref(null)
const isImporting = ref(false)
const importErrors = ref([])

const openImportModal = () => {
  selectedFile.value = null
  importErrors.value = []
  // Reset input file (agar bisa milih file yang sama lagi)
  const fileInput = document.getElementById('customFile')
  if(fileInput) fileInput.value = ''
  
  $('#importMaqraModal').modal('show')
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
  }
}

const submitImport = async () => {
  if (!selectedFile.value || !selectedGroupId.value) return

  isImporting.value = true
  importErrors.value = []

  // Gunakan FormData untuk mengirim file
  const formData = new FormData()
  formData.append('event_group_id', selectedGroupId.value)
  formData.append('file', selectedFile.value)

  try {
    // Sesuaikan Header Content-Type untuk Upload File
    const res = await axios.post('/api/v1/event-maqras/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    const data = res.data
    
    // Jika ada error pada beberapa baris, tampilkan, jangan langsung tutup modal
    if (data.errors && data.errors.length > 0) {
       importErrors.value = data.errors
       alert(`${data.message}\nSilakan cek pesan error di dalam form.`)
       // Tetap muat ulang data untuk melihat yang berhasil masuk
       fetchMaqras(1)
    } else {
       alert(data.message || 'Import berhasil!')
       $('#importMaqraModal').modal('hide')
       fetchMaqras(1)
    }

  } catch (error) {
    console.error('Gagal import data:', error)
    alert(error.response?.data?.message || 'Gagal mengimpor file.')
  } finally {
    isImporting.value = false
  }
}

// =============================
// FITUR BARU: PREVIEW AYAT
// =============================
const activePreviewMaqra = ref(null)
const ayatPreviewData = ref([])
const isLoadingAyat = ref(false)

// Fungsi helper untuk mengonversi angka ke angka Arab
const toArabicNumber = (number) => {
  const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
  return String(number).split('').map(char => arabicNumbers[parseInt(char)]).join('');
}

const openAyatPreviewModal = async (maqra) => {
  activePreviewMaqra.value = maqra
  ayatPreviewData.value = []
  
  $('#previewAyatModal').modal('show')
  isLoadingAyat.value = true

  try {
    // KARENA quran-meta HANYA MENYIMPAN META (BUKAN TEKS ARAB), 
    // Kita panggil API Publik EQuran (https://equran.id/apidev) untuk mendapatkan teksnya.
    const res = await axios.get(
      `https://equran.id/api/v2/surat/${maqra.surah_number}`, 
      { withCredentials: false } 
    )
    
    const semuaAyat = res.data.data.ayat
    
    // Filter array ayat hanya untuk range start_ayah sampai end_ayah
    const maqraRange = semuaAyat.filter(a => 
      a.nomorAyat >= maqra.start_ayah && a.nomorAyat <= maqra.end_ayah
    )
    
    ayatPreviewData.value = maqraRange
  } catch (error) {
    console.error('Gagal mengambil data teks Al-Quran:', error)
  } finally {
    isLoadingAyat.value = false
  }
}

// ===== MASTER DATA EVENT =====
const selectedGroupId = ref('')
const quranSurahList = ref([])

// Nilai boolean dari API bisa berupa true / 1 / '1' / 'true' tergantung query backend
const hasMaqraDrawing = (g) => [true, 1, '1', 'true'].includes(g.has_maqra_drawing)

// Memfilter golongan dari MasterDataStore berdasarkan kolom has_maqra_drawing
const filteredEventGroups = computed(() => {
  return (masterStore.eventGroups || []).filter(hasMaqraDrawing)
})

// Selalu ambil ulang dari server (force) agar tidak memakai cache lama di localStorage
const loadGroups = async () => {
  try {
    await masterStore.loadAllEventGroups(true)
  } catch (error) {
    console.error('Gagal memuat event groups:', error)
  }
}

// Jika eventData baru tersedia setelah halaman dibuka (mis. setelah refresh browser),
// muat ulang golongan. Tanpa ini, store terisi [] karena event_id belum ada saat onMounted.
watch(
  () => authUserStore.eventData?.id,
  (id) => {
    if (id) loadGroups()
  }
)

// Hitung data golongan yang sedang dipilih untuk UI Modal
const selectedGroupData = computed(() => {
  return filteredEventGroups.value.find(g => g.id === selectedGroupId.value) || null
})

// ===== LIST DATA MAQRA =====
const maqras = ref([])
const meta = ref({ current_page: 1, per_page: 10, total: 0, from: 0, to: 0, last_page: 1 })
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)

// ===== FORM / MODAL =====
const isEdit = ref(false)
const isSubmitting = ref(false)
const currentSurahMaxAyah = ref(0)
const isFetchingJuzPage = ref(false)

const form = ref({
  id: null,
  event_group_id: '',
  surah_number: '',
  surah_name: '',
  start_ayah: '',
  end_ayah: '',
  juz: '',
  page: '',
  is_available: true,
})

const isFormValid = computed(() => {
  if (!form.value.surah_number || !form.value.start_ayah || !form.value.end_ayah) return false
  if (form.value.start_ayah > form.value.end_ayah) return false
  if (form.value.end_ayah > currentSurahMaxAyah.value) return false
  return true
})

// =============================
// LIFECYCLE
// =============================
onMounted(async () => {
  await loadGroups()

  // Ambil daftar nama surat berbahasa Indonesia
  const allsurahs = QuranMetai18n.getSurahNames("id")
  
  const formattedSurahs = []

  // Loop dimulai dari indeks 1 sampai 114 karena indeks 0 kosong (sesuai gambar)
  for (let i = 1; i < allsurahs.length; i++) {
    const surahItem = allsurahs[i]
    
    // Pastikan elemen ada dan memiliki setidaknya nama surat
    if (surahItem && surahItem.length > 0) {
      // Ambil meta untuk mendapatkan total ayat
      // Pastikan QuranMeta sudah diimport: import * as QuranMeta from 'quran-meta'
      const meta = QuranMetahafs.getSurahMeta(i, allsurahs)
      console.log('meta');
      console.log(meta);
      
      formattedSurahs.push({
        number: meta.surahNum,                         // Nomor surat (1 - 114)
        name: surahItem[0],                // Nama surat (contoh: "Al-Fatihah")
        translation: surahItem[1] || '',   // Arti surat (opsional, contoh: "Pembukaan")
        numberOfAyahs: meta.ayahCount // Total ayat
      })
    }
  }

  console.log(formattedSurahs)

  // Masukkan data yang sudah di-format ke dalam state
  quranSurahList.value = formattedSurahs
})

// =============================
// FETCH DATA MAQRA
// =============================
const fetchMaqras = async (page = 1) => {
  if (!selectedGroupId.value) return

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-maqras', {
      params: {
        page,
        event_group_id: selectedGroupId.value,
        search: search.value,
        per_page: perPage.value,
      }
    })

    // PERBAIKAN DI SINI: Tampung object paginasi dari Laravel
    const paginated = res.data.data

    // Ambil array datanya
    maqras.value = paginated.data || []
    
    // Ambil meta informasinya
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat data maqra:', error)
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchMaqras(page)
}

watch(perPage, () => {
  if (selectedGroupId.value) fetchMaqras(1)
})

watch(
  () => search.value,
  useDebounceFn(() => {
    if (selectedGroupId.value) fetchMaqras(1)
  }, 400)
)

// Tambahkan watcher untuk memantau perubahan pada start_ayah agar Juz dan Halaman update otomatis
watch(
  () => form.value.start_ayah,
  () => {
    updateJuzAndPage()
  }
)

// =============================
// QURAN META LOGIC
// =============================
const handleSurahChange = () => {
  if (!form.value.surah_number) {
    form.value.surah_name = ''
    currentSurahMaxAyah.value = 0
    form.value.start_ayah = ''
    form.value.end_ayah = ''
    form.value.juz = ''
    form.value.page = ''
    return
  }

  // Cari di array yang sudah kita buat sendiri di onMounted
  const meta = quranSurahList.value.find(s => Number(s.number) === Number(form.value.surah_number))
  
  if (meta) {
    form.value.surah_name = meta.name 
    currentSurahMaxAyah.value = meta.numberOfAyahs
    
    // Reset ayat jika di luar batas
    if (form.value.start_ayah > meta.numberOfAyahs) form.value.start_ayah = meta.numberOfAyahs
    if (form.value.end_ayah > meta.numberOfAyahs) form.value.end_ayah = meta.numberOfAyahs
    
    // Otomatis isi atribut penanda (juz & page) jika ayat terisi
    updateJuzAndPage()
  }
}

const updateJuzAndPage = () => {
  if (form.value.surah_number && form.value.start_ayah) {
    const suratId = Number(form.value.surah_number)
    const ayatMulai = Number(form.value.start_ayah)

    // 1. Aktifkan status loading
    isFetchingJuzPage.value = true
    
    // Kosongkan form sementara agar terlihat efek transisinya
    form.value.juz = ''
    form.value.page = ''

    // 2. Buat jeda buatan (contoh: 600 milidetik)
    setTimeout(() => {
      try {
        const juzId = QuranMetahafs.findJuz(suratId, ayatMulai)
        const pageId = QuranMetahafs.findPage(suratId, ayatMulai)
        
        form.value.juz = typeof juzId === 'object' && juzId !== null 
                         ? (juzId.juzNum || juzId.juz || '') 
                         : (juzId || '')
                         
        form.value.page = typeof pageId === 'object' && pageId !== null 
                          ? (pageId.pageNum || pageId.page || '') 
                          : (pageId || '')
      } catch (e) {
        console.error("Gagal mendapatkan Juz/Halaman:", e)
      } finally {
        // 3. Matikan status loading
        isFetchingJuzPage.value = false
      }
    }, 600) // Waktu jeda (600ms), silakan atur sesuai selera
  } else {
    // Jika ayat dihapus/kosong, reset juga juz dan halamannya
    form.value.juz = ''
    form.value.page = ''
  }
}

// =============================
// MODAL ACTIONS
// =============================
const resetForm = () => {
  form.value = {
    id: null,
    event_group_id: selectedGroupId.value,
    surah_number: '',
    surah_name: '',
    start_ayah: '',
    end_ayah: '',
    juz: '',
    page: '',
    is_available: true,
  }
  currentSurahMaxAyah.value = 0
}

const openCreateModal = () => {
  isEdit.value = false
  resetForm()
  $('#maqraModal').modal('show')
}

const openEditModal = (maqra) => {
  isEdit.value = true
  
  const meta = quranSurahList.value.find(s => s.number === maqra.surah_number)
  currentSurahMaxAyah.value = meta ? meta.numberOfAyahs : 0

  form.value = {
    id: maqra.id,
    event_group_id: maqra.event_group_id,
    surah_number: maqra.surah_number,
    surah_name: maqra.surah_name,
    start_ayah: maqra.start_ayah,
    end_ayah: maqra.end_ayah,
    juz: maqra.juz || '',
    page: maqra.page || '',
    is_available: !!maqra.is_available,
  }
  
  $('#maqraModal').modal('show')
}

// =============================
// SUBMIT & DELETE ACTIONS
// =============================
const submitForm = async () => {
  isSubmitting.value = true
  const payload = { ...form.value }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-maqras/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/event-maqras', payload)
    }
    $('#maqraModal').modal('hide')
    fetchMaqras(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan maqra:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan maqra.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteMaqra = async (maqra) => {
  if (!confirm(`Yakin ingin menghapus maqra Surat ${maqra.surah_name} Ayat ${maqra.start_ayah}-${maqra.end_ayah}?`)) return

  try {
    await axios.delete(`/api/v1/event-maqras/${maqra.id}`)
    fetchMaqras(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus maqra:', error)
    alert(error.response?.data?.message || 'Gagal menghapus maqra.')
  }
}
</script>