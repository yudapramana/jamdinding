<template>
  <!-- <div class="content-wrapper p-3"> -->
  <section class="content-header">
    <div class="container-fluid">
      <h3 class="text-primary">📂 Upload Dokumen</h3>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <div class="card shadow-sm rounded-lg">
        <div class="card-body p-3">

          <!-- Search Box -->
          <SearchBox :search-query="searchQuery" />

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
                      <span class="badge badge-pill badge-primary ml-2">{{ doctype.files.length }}</span>
                    </span>
                  </div>
                  <button class="btn btn-sm btn-success ml-2" @click="openUploadModal(doctype)">+ Upload</button>
                </div>

                <ul v-show="doctype.expanded" class="pl-4 mt-1">

                  <li v-for="file in doctype.files" :key="file.id"
                    class="cursor-pointer p-1 rounded hover-bg-light small">
                    <div class="d-flex align-items-center justify-content-between w-100">
                      <div @click="previewFile(file)" class="d-flex align-items-center">
                        <i class="fas fa-file-pdf text-danger mr-2"></i>
                        <span>{{ file.file_name }}</span>
                        <span class="badge badge-sm ml-2" :class="badgeClass(file.status)">
                          {{ file.status || 'Pending' }}
                        </span>
                      </div>
                      <button v-if="file.status === 'Rejected'" class="btn btn-sm btn-outline-danger ml-2"
                        @click="reuploadFile(file, doctype)">
                        Reupload
                      </button>
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
    :is-loading-verval="isLoadingVerval" :vervalLogs="vervalLogs" :pdfError="pdfError"
    @close="previewUrl = null; selectedPreviewFile = null; vervalLogs = [];" />

  <!-- Upload Modal -->
  <div v-if="showUploadModal" class="modal fade show" style="display: block;" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
          <h5 class="modal-title">Upload Dokumen Pegawai: {{ selectedDoctype?.text }}</h5>
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

            <div class="form-group">
              <label>Nomor Dokumen</label>
              <input v-model="uploadForm.doc_number" type="text" class="form-control" required autofocus>
            </div>

            <div class="form-group">
              <label>Tanggal Dokumen</label>
              <input v-model="uploadForm.doc_date" type="date" class="form-control" required>
            </div>

            <div class="form-group">

              <label>Pilih Parameter (Opsional)</label><br>
              <div class="btn-group mb-2 flex-wrap">
                <button v-for="item in masterDataStore.docParameters" :key="item" type="button"
                  class="btn btn-xs btn-outline-secondary mb-1" :class="{ active: uploadForm.parameter === item }"
                  @click="uploadForm.parameter = item">
                  {{ item }}
                </button>
              </div>

              <div class="input-group mb-3">
                <input v-model="uploadForm.parameter" type="text" class="form-control" readonly>
                <div class="input-group-append">
                  <button type="button" class="btn btn-info" @click="uploadForm.parameter = ''">Reset</button>
                </div>
              </div>
              <small class="form-text text-muted mb-2">
                <strong>Info:</strong> Gunakan parameter diatas jika dokumen pada tipe ini bersifat multiple atau lebih
                dari
                satu,
                seperti <em>Akte Kelahiran Anak</em>, <em>SKP 2 Tahun Terakhir</em>, <em>SK Jabatan</em>, dan
                sejenisnya.
              </small>
            </div>

            <div class="form-group">
              <label>Pilih File (PDF)</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="exampleInputFile" accept="application/pdf"
                  @change="handleFileChange" required>
                <label class="custom-file-label" for="exampleInputFile">
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
              Upload
            </span>
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


const treeData = ref([]);
const previewUrl = ref(null);
const searchQuery = ref('');
const isLoading = ref(false);
const selectedPreviewFile = ref(null);
const vervalLogs = ref([]);
const isLoadingVerval = ref(false);


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
    console.error('Gagal mengambil log verval:', error);
    vervalLogs.value = [];
  } finally {
    isLoadingVerval.value = false;
  }
};

const fetchData = async () => {
  console.log('eh kepanggil fetchdata didalam');
  // const docsRes = await axios.get('/api/my-documents');

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

    return {
      id: doctype.id,
      text: doctype.text,
      expanded: true,
      files: relatedFiles.map(file => ({
        ...file,
        doc_type_text: doctype.text
      }))
    };
  });
};

const toggleExpand = (doctype) => {
  doctype.expanded = !doctype.expanded;
};

const previewFile = async (file) => {
  console.log('file');
  console.log(file);

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
  await fetchVervalLog(file.id);
};

const clearSearch = () => {
  searchQuery.value = '';
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
  uploadForm.value = {
    doc_number: '',
    doc_date: '',
    parameter: '',
    file: null,
    file_id: null
  };
};


const handleFileChange = (e) => {
  const file = e.target.files[0];

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
  uploadForm.value = {
    doc_number: file.doc_number || '',
    doc_date: file.doc_date || '',
    parameter: file.parameter || '',
    file: null,
    file_id: file.id  // Tambahkan ID file untuk PATCH
  };
};

const submitUpload = async () => {
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
      title: uploadForm.value.file_id ? 'Reupload Berhasil!' : 'Upload Berhasil!',
      showConfirmButton: false,
      timer: 1500
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