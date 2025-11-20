<template>
  <!-- <div class="content-wrapper p-3"> -->
  <section class="content-header">
    <div class="container-fluid">
      <h3 class="text-primary">📂 Upload Dokumen</h3>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- HOW-TO BANNER -->
      <div v-if="showHowto" class="alert alert-info d-flex align-items-start p-3 rounded-lg mb-3">
        <i class="fas fa-info-circle fa-lg mr-2 mt-1"></i>
        <div class="flex-grow-1">
          <div class="font-weight-bold mb-1">
            Cara memperbarui dokumen & menampilkan perubahan
          </div>

          <ol class="mb-2 pl-3">
            <li>Buka folder <strong>jenis dokumen</strong> yang ingin diperbarui.</li>
            
            <li>
              Jika status dokumen masih <span class="badge badge-secondary">Antrian&nbsp;Verval</span> atau
              <span class="badge badge-danger">Ditolak</span>,
              tekan tombol <span class="badge badge-sm badge-primary">Perbarui</span> pada file yang salah.
            </li>
            
            <li>
              Jika status dokumen sudah <span class="badge badge-success">Disetujui</span>, terdapat dua pilihan:
              <ol type="a" class="mt-2">
                <li>Jika file ingin diubah, klik tombol <span class="badge badge-sm badge-outline-primary">Ajukan&nbsp;Perubahan</span> lalu isi alasan kenapa dokumen perlu diganti. Sistem akan mengubah status menjadi <strong>Antrian&nbsp;Verval</strong> agar bisa diperbarui kembali.</li>
                <li>Jika file tidak ingin diubah, biarkan saja tanpa tindakan.</li>
              </ol>
            </li>
            
            <li>
              Untuk dokumen dengan tipe <em>multiple</em>, pilih <strong>Parameter</strong> yang sesuai dari tombol pilihan,
              kemudian unggah file PDF yang benar dan tekan <strong>Simpan</strong>.
            </li>
            
            <li>
              Setelah melakukan unggah atau permintaan perubahan, proses pembaruan biasanya membutuhkan waktu sekitar 1–2 menit.
            </li>
            
            <li>
              Jika data belum muncul setelah pembaruan, tekan tombol
              <strong><i class="fas fa-sync"></i> Sinkron&nbsp;Data</strong> untuk memuat ulang daftar dokumen.
            </li>
          </ol>


          <div class="small text-muted">
            Catatan:
            Bila muncul badge <span class="badge badge-warning">Fitur Maintenance</span>,
            pembaruan sementara dinonaktifkan oleh admin.
          </div>
        </div>

        <button type="button" class="close ml-2" aria-label="Close" @click="dismissHowto">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <!-- END HOW-TO BANNER -->



      <div class="card shadow-sm rounded-lg">
        <div class="card-body p-3">

          <!-- Search Box -->
          <!-- <SearchBox :search-query="searchQuery" /> -->
          <div class="input-group mb-3">
            <input type="text" v-model="searchQuery" class="form-control" placeholder="Cari dokumen...">
            <div class="input-group-append">
              <button :disabled="isLoading" class="btn btn-outline-secondary" type="button" @click="refreshData">
                <!-- <i class="fas fa-sync"></i> -->
                <i v-if="isLoading" class="fa fa-spinner fa-spin mr-1"></i>
                <span v-else><i  class="fas fa-sync"></i> Sinkron Data</span>

              </button>
              <button class="btn btn-outline-secondary" type="button" @click="clearSearch">Reset</button>
            </div>
          </div>

          <!-- Loading State -->
          <LoadingState v-if="isLoading" />

          <!-- Tree List -->
          <!-- <TreeList v-else-if="filteredTree.length" :filtered-tree="filteredTree" /> -->

          <div v-else-if="filteredTree.length" class="tree">
            <ul class="list-unstyled mb-0">
              <li v-for="(doctype, index) in filteredTree" :key="doctype.id" class="mb-2">
                <div class="d-flex align-items-center justify-between cursor-pointer py-1 px-2 bg-light rounded small">
                  <div @click="toggleExpand(doctype)" class="flex-grow-1 d-flex align-items-center">
                    <i :class="doctype.expanded ? 'fas fa-folder-open text-warning' : 'fas fa-folder text-secondary'"
                      class="mr-2"></i>
                    <span class="ml-2 font-weight-bold">
                      {{ index + 1 }}. {{ doctype.text }}
                      <span class="text-warning" v-show="doctype.mandatory == 1">*</span>
                      <span class="badge badge-pill badge-primary ml-2">{{ doctype.files.length }}</span>
                    </span>
                  </div>
                  <button v-if="settingStore.showMaintenanceBadge" type="button" class="btn btn-warning btn-sm ml-2" disabled>Fitur Maintenance</button>
                  <button v-else class="btn btn-sm btn-success ml-2" @click="openUploadModal(doctype)">+ Upload</button>
                </div>

                <ul v-show="doctype.expanded" class="pl-4 mt-1">

                  <li v-for="file in doctype.files" :key="file.id"
                    class="cursor-pointer p-1 rounded hover-bg-light small">
                    <div class="d-flex align-items-center justify-content-between w-100">
                      <div class="d-flex align-items-center">
                        <i class="fas fa-file-pdf text-danger mr-2"></i>
                        <span @click="previewFile(file)">{{ file.file_name }}</span>
                        <span v-if="file.status == 'Pending'" class="badge badge-sm ml-2" :class="badgeClass(file.status)">
                          Antrian Verval
                        </span>

                        <span v-if="file.status == 'Approved'" class="badge badge-sm ml-2" :class="badgeClass(file.status)">
                          Disetujui
                        </span>
                        
                        <template v-if="settingStore.showMaintenanceBadge">
                            <span v-if="file.status !== 'Approved'" class="badge badge-sm badge-warning ml-2" disabled>
                              Maintenance
                            </span>
                        </template>
                        <template v-else>
                          <span v-if="file.status !== 'Approved'" class="badge badge-sm badge-primary ml-2"  @click="reuploadFile(file, doctype)">
                            Perbarui
                          </span>
                        </template>

                        <button
                          v-if="!settingStore.showMaintenanceBadge && file.status === 'Approved'"
                          class="btn btn-xs btn-outline-primary ml-2"
                          @click="requestChange(file)"
                        >
                          Ajukan Perubahan
                        </button>
                        
                      </div>
                      <!-- <button v-if="file.status === 'Rejected'" class="btn btn-sm btn-outline-danger ml-2"
                        @click="reuploadFile(file, doctype)">
                        Perbarui
                      </button> -->
                    </div>

                    <div v-if="file.status === 'Rejected' && file.verif_notes" class="text-danger mt-1 ml-4 small">
                      <i class="fas fa-info-circle"></i> Catatan: {{ file.verif_notes }}
                    </div>
                  </li>


                  <li v-if="!doctype.files.length" class="text-muted small ml-4 mt-1">
                    Tidak ada file diupload.
                  </li>
                </ul>
              </li>
            </ul>
          </div>
          <div v-else class="text-center p-5">
            <span class="text-muted">Data tidak ditemukan.</span>
          </div>

        </div>
      </div>
    </div>
  </section>


  <!-- Preview Modal -->
  <PreviewModal v-if="previewUrl" :preview-url="previewUrl" :selected-preview-file="selectedPreviewFile"
    :is-loading-verval="isLoadingVerval" :vervalLogs="vervalLogs" :pdfError="pdfError" :isApprovedPreview="isApprovedPreview"
    @close="previewUrl = null; selectedPreviewFile = null; vervalLogs = [];" />

  <!-- Upload Modal -->
  <div v-if="showUploadModal" class="modal fade show" style="display: block;" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ isEditMode ? 'Edit Dokumen Pegawai' : 'Upload Dokumen Pegawai' }} <!--: {{ selectedDoctype?.text }} -->
          </h5>
          <button type="button" class="close" @click="closeUploadModal"><span>&times;</span></button>
        </div>

        <div class="modal-body overflow-auto" style="flex: 1 1 auto;">
          <form @submit.prevent="submitUpload" id="uploadForm">
            <!-- Hidden ID_DOC_TYPE -->
            <input type="hidden" :value="selectedDoctype?.id" />

            <div class="form-group">
              <label>Tipe Dokumen</label>
              <input type="text" class="form-control" :value="selectedDoctype?.text" readonly>
            </div>

            <div class="form-group" v-if="selectedDoctype.multiple == 1">
              <label>Pilih Parameter</label><br>
              <div class="btn-group mb-2 flex-wrap">
                <button v-for="item in masterDataStore.docParameters" :key="item" type="button"
                  class="btn btn-xs btn-outline-secondary mb-1" :class="{ active: uploadForm.parameter === item }"
                  @click="uploadForm.parameter = item">
                  {{ item }}
                </button>
              </div>

              <!-- <div class="input-group mb-3">
                <input v-model="uploadForm.parameter" type="text" class="form-control" readonly>
                <div class="input-group-append">
                  <button type="button" class="btn btn-info" @click="uploadForm.parameter = ''">Reset</button>
                </div>
              </div> -->

              <div class="input-group mb-3">
                <input v-model="uploadForm.parameter" type="text" class="form-control" required @keydown.prevent
                  placeholder="Pilih parameter dari tombol di atas" readonly>
                <div class="input-group-append">
                  <button type="button" class="btn btn-info" @click="uploadForm.parameter = ''">Reset</button>
                </div>
              </div>

              <small class="form-text text-muted mb-2">
                <strong>Info:</strong> Gunakan parameter jika dokumen ini bisa lebih dari satu, seperti
                <em>Akte Anak</em>, <em>SK Jabatan</em>, dst.
              </small>
            </div>

            <div class="form-group">
              <label>File Dokumen (PDF)</label>

              <!-- Tampilkan file lama jika sedang edit -->
              <div v-if="isEditMode && existingFileUrl" class="mb-2">
                <a :href="existingFileUrl" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-file-pdf"></i> Lihat Dokumen Lama
                </a>
                <br />
                <small class="text-muted">Jika tidak ingin mengganti file, biarkan kosong.</small>
              </div>

              <div class="custom-file">
                <input type="file" class="custom-file-input" id="fileInput" accept="application/pdf"
                  @change="handleFileChange" :required="!isEditMode">
                <label class="custom-file-label" for="fileInput">
                  {{ uploadForm.fileName || 'Pilih file' }}
                </label>
              </div>
            </div>

            <div v-if="loadingUpload" class="mb-3">
              <div class="progress" style="height: 20px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" :class="progressBarColor"
                  role="progressbar" :style="{ width: uploadProgress + '%' }" :aria-valuenow="uploadProgress"
                  aria-valuemin="0" aria-valuemax="100">
                  {{ uploadProgress }}%
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" :disabled="loadingUpload" form="uploadForm">
            <span v-if="loadingUpload">
              <i class="fas fa-spinner fa-spin"></i> Uploading...
            </span>
            <span v-else>
              {{ isEditMode ? 'Simpan Perubahan' : 'Upload' }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>



  <!-- Backdrop -->
  <div v-if="showChangeModal" class="modal-backdrop fade show"></div>


  <!-- Change Request Modal -->
  <div v-if="showChangeModal"
     class="modal d-block" tabindex="-1" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Minta Perubahan Dokumen (Approved → Pending)</h6>
          <button type="button" class="close" @click="showChangeModal=false"><span>&times;</span></button>
        </div>

        <div class="modal-body">
          <div class="alert alert-warning small">
            Mengajukan perubahan akan mengubah status dokumen ini menjadi <strong>Pending</strong>.
            Verifikator akan meninjau ulang dokumen Anda. Pastikan alasan jelas.
          </div>

          <div class="row">
            <!-- Kolom kiri: ringkasan & alasan -->
            <div class="col-lg-5">
              <div class="card mb-3">
                <div class="card-body p-3">
                  <div class="font-weight-bold mb-2">Ringkasan Dokumen</div>
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 py-1 d-flex justify-content-between">
                      <span class="text-muted">Jenis Dokumen</span>
                      <span class="text-right font-weight-bold">
                        {{ targetChangeFile?.doc_type_text || '—' }}
                      </span>
                    </li>
                    <li class="list-group-item px-0 py-1 d-flex justify-content-between" v-if="targetChangeFile?.parameter">
                      <span class="text-muted">Parameter</span>
                      <span class="text-right">{{ targetChangeFile.parameter }}</span>
                    </li>
                    <li class="list-group-item px-0 py-1 d-flex justify-content-between" v-if="targetChangeFile?.doc_number">
                      <span class="text-muted">Nomor Dokumen</span>
                      <span class="text-right">{{ targetChangeFile.doc_number }}</span>
                    </li>
                    <li class="list-group-item px-0 py-1 d-flex justify-content-between" v-if="targetChangeFile?.doc_date">
                      <span class="text-muted">Tanggal Dokumen</span>
                      <span class="text-right">{{ targetChangeFile.doc_date }}</span>
                    </li>
                    <li class="list-group-item px-0 py-1 d-flex justify-content-between">
                      <span class="text-muted">Status Saat Ini</span>
                      <span class="text-right">
                        <span class="badge" :class="badgeClass(targetChangeFile?.status)">
                          {{ targetChangeFile?.status || 'Pending' }}
                        </span>
                      </span>
                    </li>
                    <li class="list-group-item px-0 py-1 d-flex justify-content-between" v-if="targetChangeFile?.file_name">
                      <span class="text-muted">Nama File</span>
                      <span class="text-right text-truncate" style="max-width: 60%">{{ targetChangeFile.file_name }}</span>
                    </li>
                  </ul>
                </div>
              </div>

              <div>
                <label class="mb-1">Alasan perubahan</label>
                <textarea v-model="changeReason" class="form-control" rows="4"
                          placeholder="Contoh: Salah parameter/tahun/salah nama pada dokumen, dsb."></textarea>
              </div>
            </div>

            <!-- Kolom kanan: preview dokumen lama -->
            <div class="col-lg-7">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="font-weight-bold">Dokumen Lama</div>
                <div>
                  <a v-if="filePreviewLink(targetChangeFile)" :href="filePreviewLink(targetChangeFile)" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> Buka Dokumen
                  </a>
                </div>
              </div>

              <div class="border rounded" style="height: 70vh; overflow: hidden;">
                <iframe
                  v-if="filePreviewLink(targetChangeFile)"
                  :src="filePreviewLink(targetChangeFile)"
                  style="border:0; width:100%; height:100%;"
                  @load="() => { /* noop */ }"
                ></iframe>
                <div v-else class="h-100 d-flex align-items-center justify-content-center text-muted">
                  Tidak dapat menampilkan preview dokumen.
                </div>
              </div>

              <small class="text-muted d-block mt-2">
                Catatan: Jika preview tidak tampil, gunakan tombol <strong>Buka Dokumen</strong> di kanan atas.
              </small>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showChangeModal=false">Batal</button>
          <button class="btn btn-primary" :disabled="submittingChange || !changeReason.trim()" @click="submitChangeRequest">
            <i v-if="submittingChange" class="fa fa-spinner fa-spin mr-1"></i>
            Kirim Permintaan
          </button>
        </div>
      </div>
    </div>
  </div>





</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useMasterDataStore } from '../../stores/MasterDataStore';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import LoadingState from './LoadingState.vue';
import SearchBox from './SearchBox.vue';
import TreeList from './TreeList.vue';
import PreviewModal from './PreviewModal.vue';
import { useSettingStore } from '../../stores/SettingStore';


const HOWTO_KEY = 'sigarda_upload_howto_hidden';
const showHowto = ref(true);

const dismissHowto = () => {
  showHowto.value = false;
  try { localStorage.setItem(HOWTO_KEY, '1'); } catch (e) {}
};

onMounted(async () => {
  // restore banner visibility
  try { showHowto.value = localStorage.getItem(HOWTO_KEY) !== '1'; } catch (e) {}
  
  isLoading.value = true
  await authUserStore.getDocsUpdateState()
  console.log('eh kepanggil fetchdata didalam onMounted Doclist')
  await fetchData()
  isLoading.value = false
});

// helper preview link mengikuti pola preview yang sudah ada
const filePreviewLink = (file) => {
  if (!file) return null;

  // Jika tersedia file_path gunakan endpoint preview internal
  if (file.file_path) {
    return buildPreviewUrl(file.file_path); // sudah didefinisikan di kode Anda
  }

  // Fallback ke file_url (untuk kasus non-approved atau file publik)
  if (file.file_url) return file.file_url;

  return null;
};


// di <script setup>
const showChangeModal = ref(false);
const changeReason = ref('');
const submittingChange = ref(false);
const targetChangeFile = ref(null);

const requestChange = (file) => {
  targetChangeFile.value = file;
  changeReason.value = '';
  showChangeModal.value = true;
};

const submitChangeRequest = async () => {
  if (!changeReason.value.trim()) {
    return Swal.fire({ icon: 'warning', title: 'Alasan wajib diisi' });
  }
  submittingChange.value = true;
  try {
    await axios.post(`/api/documents/${targetChangeFile.value.id}/request-change`, {
      reason: changeReason.value
    });

    Swal.fire({ icon: 'success', title: 'Permintaan perubahan diajukan. Status diubah ke Pending.' });

    // refresh folder saat ini agar status langsung berubah di UI
    const docTypeId = targetChangeFile.value.id_doc_type || targetChangeFile.value.doc_type_id || targetChangeFile.value.doc_type;
    await refreshSingleFolder(docTypeId);

    showChangeModal.value = false;
  } catch (err) {
    authUserStore.handleAuthError(err);
    Swal.fire({ icon: 'error', title: 'Gagal mengajukan perubahan' });
  } finally {
    submittingChange.value = false;
  }
};

const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);
const treeData = ref([]);
const previewUrl = ref(null);
const searchQuery = ref('');
const isLoading = ref(false);
const selectedPreviewFile = ref(null);
const vervalLogs = ref([]);
const isLoadingVerval = ref(false);
const isEditMode = ref(false); // true = edit, false = upload baru
const existingFileUrl = ref(''); // e.g. '/storage/docs/123456.pdf'
const isApprovedPreview = ref(false);



const showUploadModal = ref(false);
const selectedDoctype = ref(null);
const loadingUpload = ref(false);
const uploadProgress = ref(0);
const progressBarColor = computed(() => {
  if (uploadProgress.value < 30) {
    return 'bg-danger'; // Merah di bawah 30%
  } else if (uploadProgress.value < 70) {
    return 'bg-warning'; // Oranye 30%-69%
  } else {
    return 'bg-success'; // Hijau 70%-100%
  }
});
const pdfFrame = ref(null);
const pdfError = ref(false);
const onIframeLoad = () => {
  // Iframe load berhasil
  pdfError.value = false;
};

const onIframeError = () => {
  // Jika iframe gagal load
  pdfError.value = true;
};

const badgeClass = (status) => {
  switch (status) {
    case 'Approved':
      return 'badge-success'; // Hijau
    case 'Rejected':
      return 'badge-danger';  // Merah
    default:
      return 'badge-secondary'; // Abu-abu untuk Pending atau undefined
  }
};

const masterDataStore = useMasterDataStore();
const authUserStore = useAuthUserStore();

const uploadForm = ref({
  doc_number: '',
  doc_date: '',
  parameter: '',
  file: null,
  fileName: '',
  file_id: null
});

const fetchVervalLog = async (fileId) => {
  isLoadingVerval.value = true;
  try {
    const res = await axios.get(`/api/document-log/${fileId}`);
    vervalLogs.value = res.data.data || [];
  } catch (error) {
    authUserStore.handleAuthError(error);
    vervalLogs.value = [];
  } finally {
    isLoadingVerval.value = false;
  }
};

const fetchData = async () => {
  console.log('eh kepanggil fetchdata didalam');
  // const docsRes = await axios.get('/api/my-documents');
  isLoading.value = true;
  await masterDataStore.getDoctypeList();
  await authUserStore.getMyDocuments();
  const doctypeList = masterDataStore.doctypeList;
  const uploadedDocs = authUserStore.myDocuments;

  treeData.value = doctypeList.map((doctype) => {
    const relatedFiles = uploadedDocs.filter(doc =>
      doc.id_doc_type === doctype.id ||
      doc.doc_type_id === doctype.id ||
      doc.doc_type === doctype.id
    ).sort((a, b) => a.file_name.localeCompare(b.file_name)); // Urutkan berdasarkan nama


    console.log('doctype.text');
    console.log(doctype.text);

    return {
      id: doctype.id,
      text: doctype.text,
      mandatory: doctype.mandatory,
      multiple: doctype.multiple,
      expanded: true,
      files: relatedFiles.map(file => ({
        ...file,
        doc_type_text: doctype.text,
      }))
    };

  });

  isLoading.value = false;
};

const toggleExpand = (doctype) => {
  doctype.expanded = !doctype.expanded;
};

const buildPreviewUrl = (path) => `/api/preview/pdf?path=${encodeURI(path)}`;

const previewFile = async (file) => {
  console.log('file');
  console.log(file);

  isApprovedPreview.value = (file.status === 'Approved'); // 👈 simpan status

  if(file.status == 'Approved') {

    pdfError.value = false;
    selectedPreviewFile.value = file;
    isApprovedPreview.value = (file.status === 'Approved'); // 👈 simpan status

    const path = file?.file_path;
    if (!path) { pdfError.value = true; return; }

    previewUrl.value = buildPreviewUrl(path);
    
  } else {
    pdfError.value = false;
      selectedPreviewFile.value = file;
      previewUrl.value = file.file_url;
      try {
        const res = await fetch(file.file_url, { method: 'HEAD' });

        if (!res.ok) {
          if (res.status === 404) {
            pdfError.value = 'not_found';
          } else {
            pdfError.value = true;
          }
          return;
        }

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('pdf')) {
          pdfError.value = true;
        }
      } catch (err) {
        pdfError.value = true;
      }
  } 
  
  await fetchVervalLog(file.id);
};

const clearSearch = () => {
  searchQuery.value = '';
};

const refreshData = async () => {
  isLoading.value = true;
  await authUserStore.syncFiles();
  await authUserStore.getDocsUpdateState();
  console.log('eh kepanggil fetchdata didalam onMounted Doclist');
  await fetchData();
  isLoading.value = false;
};

const filteredTree = computed(() => {
  if (!searchQuery.value) return treeData.value;
  const q = searchQuery.value.toLowerCase();

  return treeData.value.filter(doctype =>
    doctype.text.toLowerCase().includes(q) ||
    doctype.files.some(file => file.file_name.toLowerCase().includes(q))
  );
});

const openUploadModal = (doctype) => {
  selectedDoctype.value = doctype;
  isEditMode.value = false; // matikan mode edit
  existingFileUrl.value = '';
  showUploadModal.value = true;
  uploadForm.value = {
    doc_number: '',
    doc_date: '',
    parameter: '',
    file: null
  };
};

const closeUploadModal = () => {
  showUploadModal.value = false;
  isEditMode.value = false;
  uploadForm.value = {
    doc_number: '',
    doc_date: '',
    parameter: '',
    file: null,
    file_id: null
  };
  existingFileUrl.value = '';
};


const handleFileChange = (e) => {
  const file = e.target.files[0];

  if (file) {
    uploadForm.value.file = file;
    uploadForm.value.fileName = file.name;
  }

  if (!file) {
    uploadForm.value.file = null;
    uploadForm.value.fileName = '';
    return;
  }

  if (file.type !== 'application/pdf') {
    Swal.fire({
      icon: 'error',
      title: 'Format Salah',
      text: 'File harus berformat PDF!'
    });
    e.target.value = '';
    uploadForm.value.file = null;
    return;
  }

  const maxSize = 1 * 1024 * 1024; // 1MB
  if (file.size > maxSize) {
    Swal.fire({
      icon: 'error',
      title: 'Ukuran File Terlalu Besar',
      text: 'Ukuran maksimal file adalah 1MB!'
    });
    e.target.value = '';
    uploadForm.value.file = null;
    return;
  }

  uploadForm.value.file = file;
  uploadForm.value.fileName = file.name;
};

const reuploadFile = (file, doctype) => {
  selectedDoctype.value = doctype;
  showUploadModal.value = true;
  isEditMode.value = true; // aktifkan mode edit

  // Set file yang sedang diedit
  existingFileUrl.value = file.file_url || ''; // gunakan URL file lama untuk preview
  uploadForm.value = {
    doc_number: file.doc_number || '',
    doc_date: file.doc_date || '',
    parameter: file.parameter || '',
    file: null,               // file baru, jika ada
    file_id: file.id          // ID file lama untuk keperluan update (PATCH)
  };

  console.log('uploadForm');
  console.log(uploadForm);
};

const submitUpload = async () => {

  console.log('uploadForm on BUTTON SUBMIT');
  console.log(uploadForm);


  if (!uploadForm.value.file && !uploadForm.value.file_id) {
    Swal.fire({
      icon: 'warning',
      title: 'File Belum Dipilih',
      text: 'Silakan pilih file terlebih dahulu.'
    });
    return;
  }

  loadingUpload.value = true;
  const formData = new FormData();
  formData.append('doc_number', uploadForm.value.doc_number);
  formData.append('doc_date', uploadForm.value.doc_date);
  formData.append('parameter', uploadForm.value.parameter);
  if (uploadForm.value.file) {
    formData.append('file', uploadForm.value.file);
  }
  formData.append('id_doc_type', selectedDoctype.value.id);

  try {
    if (uploadForm.value.file_id) {
      // PATCH reupload
      await axios.post(`/api/reupload-document/${uploadForm.value.file_id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          }
        }
      });
    } else {
      // Upload baru
      await axios.post('/api/upload-document', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          }
        }
      });
    }

    Swal.fire({
      icon: 'success',
      title: uploadForm.value.file_id 
        ? 'Dokumen berhasil diperbarui. Perubahan akan tampil dalam 1–2 menit, mohon tunggu sebentar.'
        : 'Dokumen berhasil diupload. Perubahan akan tampil dalam 1–2 menit, mohon tunggu sebentar.',
      showConfirmButton: false,
      timer: 2000
    });

    await refreshSingleFolder(selectedDoctype.value.id);
    closeUploadModal();
  } catch (error) {
    let message = 'Terjadi kesalahan saat mengupload file.';
    if (error.response?.status === 422 && error.response.data.errors) {
      const errors = error.response.data.errors;
      message = Object.values(errors).flat().join('\n');
    }

    Swal.fire({
      icon: 'error',
      title: 'Upload Gagal',
      text: message
    });
  } finally {
    loadingUpload.value = false;
    uploadProgress.value = 0;
  }
};


const refreshSingleFolder = async (id) => {
  // const docsRes = await axios.get('/api/my-documents');
  // const uploadedDocs = docsRes.data.data;
  // authUserStore.myDocuments.value = uploadedDocs;



  authUserStore.docsUpdateState = true;
  await authUserStore.getMyDocuments();
  const uploadedDocs = authUserStore.myDocuments;


  // const uploadedDocs = authUserStore.myDocuments;


  const relatedFiles = uploadedDocs.filter(doc =>
    doc.id_doc_type === id ||
    doc.doc_type_id === id ||
    doc.doc_type === id
  ).sort((a, b) => a.file_name.localeCompare(b.file_name)); // Tetap diurutkan

  const folder = treeData.value.find(f => f.id === id);
  if (folder) {
    folder.files = relatedFiles;
  }
};

onMounted(async () => {
  isLoading.value = true
  await authUserStore.getDocsUpdateState()
  console.log('eh kepanggil fetchdata didalam onMounted Doclist')
  await fetchData()
  isLoading.value = false
});
</script>

<style scoped>
.tree ul {
  list-style-type: none;
  padding-left: 0;
}

.tree li {
  margin-bottom: 0.5rem;
}

.cursor-pointer {
  cursor: pointer;
}

.hover-bg-light:hover {
  background-color: #f1f1f1;
}

.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.badge-sm {
  font-size: 0.65rem;
  padding: 0.25em 0.4em;
}

.alert ol { margin-bottom: .5rem; }

/* potong teks nama file panjang agar rapi */
.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Tambahan untuk tampilan mobile */
/* @media (max-width: 576px) {
  .content-wrapper {
    padding: 0.5rem !important; 
  }
  
  .card-body {
    padding: 0.5rem !important;
  }

  .modal-content {
    margin: 0 0.5rem; 
  }

  .tree li > div {
    padding: 0.5rem !important;
  }
} */
</style>