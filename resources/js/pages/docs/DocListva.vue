<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useToastr } from '@/toastr';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import { useMasterDataStore } from "../../stores/MasterDataStore.js";
import { useScreenDisplayStore } from '../../stores/ScreenDisplayStore.js';

const screenDisplayStore = useScreenDisplayStore();
const authUserStore = useAuthUserStore();
const masterDataStore = useMasterDataStore();
const toastr = useToastr();
masterDataStore.getDoctypeList();

const documents = ref([]);
const errors = ref({});
const selectedFile = ref(null);
const uploadForm = reactive({
  id_doc_type: '',
  doc_number: '',
  doc_date: '',
  parameter: '',
});
const uploadProgress = ref(0);


// const docTypes = [
//   { label: 'Ijazah SD', code: 'IJZHSD' },
//   { label: 'Ijazah SMP/Sederajat', code: 'IJZHSMP' },
//   { label: 'Ijazah SMA/Sederajat', code: 'IJZHSMA' },
//   { label: 'Ijazah D2/D3/S1/A-IV/S2', code: 'IJZH' },
//   { label: 'Transkrip Nilai D2/D3/S1/S2', code: 'TRANSNILAI' },
// ];


const fetchDocuments = () => {
  axios.get('/api/employee/documents')
    .then(response => {
      documents.value = response.data.data;
    })
    .catch(error => {
      console.error(error);
    });
};
masterDataStore.getDoctypeList();

onMounted(() => {
  fetchDocuments();
  console.log('fetched Documents');
  masterDataStore.getDoctypeList();
  console.log('masterDataStore.doctypeList.value');
  console.log(masterDataStore.doctypeList);
});

const handleFileChange = (event) => {
  selectedFile.value = event.target.files[0];
};

// const uploadDocument = () => {
//   errors.value = {};
//   if (!selectedFile.value) {
//     toastr.error('Please select a file first.');
//     return;
//   }

//   const formData = new FormData();
//   formData.append('id_doc_type', uploadForm.id_doc_type);
//   formData.append('doc_number', uploadForm.doc_number);
//   formData.append('doc_date', uploadForm.doc_date);
//   formData.append('parameter', uploadForm.parameter);
//   formData.append('file', selectedFile.value);

//   axios.post('/api/employee/documents', formData, {
//     headers: {
//       'Content-Type': 'multipart/form-data',
//     },
//   })
//     .then(response => {
//       toastr.success('Document uploaded successfully!');
//       fetchDocuments();
//       selectedFile.value = null;
//       uploadForm.id_doc_type = '';
//       uploadForm.doc_number = '';
//       uploadForm.doc_date = '';
//       uploadForm.parameter = '';
//     })
//     .catch(error => {
//       if (error.response && error.response.status === 422) {
//         errors.value = error.response.data.errors;
//       }
//     });
// };

const uploadDocument = () => {
    errors.value = {};
    if (!selectedFile.value) {
        toastr.error('Please select a file first.');
        return;
    }

    const formData = new FormData();
    formData.append('id_doc_type', uploadForm.id_doc_type);
    formData.append('doc_number', uploadForm.doc_number);
    formData.append('doc_date', uploadForm.doc_date);
    formData.append('parameter', uploadForm.parameter);
    formData.append('file', selectedFile.value);

    axios.post('/api/employee/documents', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
        onUploadProgress: (progressEvent) => {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        }
    })
    .then(response => {
        toastr.success('Document uploaded successfully!');
        fetchDocuments();
        selectedFile.value = null;
        uploadForm.id_doc_type = '';
        uploadForm.doc_number = '';
        uploadForm.doc_date = '';
        uploadForm.parameter = '';
        uploadProgress.value = 0;
    })
    .catch(error => {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        }
        uploadProgress.value = 0;
    });
};

const viewFile = (path) => {
  window.open(path, '_blank');
};
</script>

<template>

  <div class="progress" v-if="uploadProgress > 0">
    <div class="progress-bar" role="progressbar" :style="{ width: uploadProgress + '%' }"
      :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100">{{ uploadProgress }}%</div>
  </div>

  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Employee Documents</h1>
        </div>
        <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Documents</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row" style="margin-bottom: 100px;">
        <div class="col-md-4">
          <div class="card card-primary card-outline">
            <div class="card-header">
              <h5 class="card-title">Upload New Document</h5>
            </div>
            <div class="card-body">
              <form @submit.prevent="uploadDocument">
                <div class="form-group">
                  <label>Document Type</label>
                  <select v-model="uploadForm.id_doc_type" class="form-control">
                    <option value="">Select Type</option>
                    <option v-for="(doc, index) in masterDataStore.doctypeList" :key="index" :value="doc.id">{{ doc.text }}</option>
                  </select>
                  <span class="text-danger text-sm" v-if="errors.id_doc_type">{{ errors.id_doc_type[0] }}</span>
                </div>
                <div class="form-group">
                  <label>Document Number</label>
                  <input v-model="uploadForm.doc_number" type="text" class="form-control" placeholder="Document Number">
                  <span class="text-danger text-sm" v-if="errors.doc_number">{{ errors.doc_number[0] }}</span>
                </div>
                <div class="form-group">
                  <label>Document Date</label>
                  <input v-model="uploadForm.doc_date" type="date" class="form-control">
                  <span class="text-danger text-sm" v-if="errors.doc_date">{{ errors.doc_date[0] }}</span>
                </div>
                <div class="form-group">
                  <label>Parameter</label>
                  <input v-model="uploadForm.parameter" type="text" class="form-control"
                    placeholder="Parameter (Optional)">
                  <span class="text-danger text-sm" v-if="errors.parameter">{{ errors.parameter[0] }}</span>
                </div>
                <div class="form-group">
                  <label>Choose File</label>
                  <input type="file" class="form-control-file" @change="handleFileChange">
                  <span class="text-danger text-sm" v-if="errors.file">{{ errors.file[0] }}</span>
                </div>
                <button type="submit" class="btn btn-success btn-block"><i class="fa fa-upload mr-1"></i>
                  Upload</button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-md-8">
          <div class="card">
            <div class="card-header p-2">
              <h5 class="card-title">Uploaded Documents</h5>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover table-bordered table-sm">
                <thead>
                  <tr>
                    <th>Type</th>
                    <th>Number</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>File</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="doc in documents" :key="doc.id">
                    <td>{{ doc.doctype?.type_name }}</td>
                    <td>{{ doc.doc_number }}</td>
                    <td>{{ doc.doc_date }}</td>
                    <td>
                      <span :class="{
                        'badge badge-warning': doc.status === 'Pending',
                        'badge badge-success': doc.status === 'Approved',
                        'badge badge-danger': doc.status === 'Rejected'
                      }">{{ doc.status }}</span>
                    </td>
                    <td>
                      <button @click="viewFile(doc.file_path)" class="btn btn-sm btn-primary">
                        <i class="fa fa-eye"></i> View
                      </button>
                    </td>
                  </tr>
                  <tr v-if="documents.length === 0">
                    <td colspan="5" class="text-center text-muted">No documents found.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.table th,
.table td {
  vertical-align: middle !important;
}
</style>
