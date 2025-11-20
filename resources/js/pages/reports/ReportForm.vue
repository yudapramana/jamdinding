<script setup>
import { ref, onMounted, reactive, watch, toDisplayString } from "vue";
import { useRouter, useRoute } from 'vue-router';
import { useToastr } from '@/toastr';
import { Form, Field } from 'vee-validate';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/themes/light.css'
import { useAuthUserStore } from "../../stores/AuthUserStore.js";

const loading = ref(false);

const authUserStore = useAuthUserStore();
const router = useRouter();
const route = useRoute();
const toastr = useToastr();
const form = reactive({
    date: '',
    work_name: '',
    work_detail: '',
    volume: 0,
    unit: '',
    evidence: '',
    evidence_url: '',
});
const editReportUserID = ref('');

const handleSubmit = (values, actions) => {

    loading.value = true;
    if (editMode.value) {
        editReport(values, actions)
    } else {
        createReport(values, actions);
    }


};

const createReport = (values, actions) => {
    axios.post('/api/reports/create', form)
        .then((response) => {
            router.push('/admin/reports');
            toastr.success('Laporan Kerja berhasil dibuat');
        })
        .catch((error) => {
            actions.setErrors(error.response.data.errors);
        })
        .finally(() => {
            loading.value = false;
        });
};

const editReport = (values, actions) => {
    axios.put(`/api/reports/${route.params.id}/edit`, form)
        .then((response) => {
            router.push('/admin/reports');
            toastr.success('Laporan Kerja berhasil diubah!');
        })
        .catch((error) => {
            actions.setErrors(error.response.data.errors);
        })
        .finally(() => {
            loading.value = false;
        });
};

const editMode = ref(false);
const getReport = () => {
    axios.get(`/api/reports/${route.params.id}/edit`)
        .then((response) => {
            console.log(response);

            console.log('authUserStore.user.id');
            console.log(authUserStore.user.id);
            console.log('response.data.report.user_id');
            console.log(response.data.report.user_id);

            if (editMode.value && authUserStore.user.id != response.data.report.user_id) {
                router.push('/admin/reports');
            } else {
                form.date = response.data.report.date;
                form.work_name = response.data.work_name;
                form.work_detail = response.data.work_detail;
                form.volume = response.data.volume;
                form.unit = response.data.unit_value;
                form.evidence = response.data.evidence;
                form.evidence_url = response.data.evidence_url;
            }
        });
};

onMounted(() => {

    if (route.name === 'admin.reports.edit') {
        editMode.value = true;
        getReport();
    }

    flatpickr(".flatpickr", {
        dateFormat: "Y-m-d",
        disableMobile: true,
    });
});

// watch(() => [form.work_detail], function (val) {
//     console.log('summernote called')
//     $('#summernote').summernote("code", val);
// });

</script>

<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <span v-if="editMode">Edit Laporan </span>
                        <span v-else>Buat Laporan</span>

                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <router-link to="/admin/dashboard">Home</router-link>
                        </li>
                        <li class="breadcrumb-item">
                            <router-link to="/admin/reports">Laporan Kerja</router-link>
                        </li>
                        <li class="breadcrumb-item active">
                            <span v-if="editMode">Edit</span>
                            <span v-else>Buat</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card" style="margin-bottom: 100px">
                        <div class="card-body">
                            <Form @submit="handleSubmit" v-slot:default="{ errors }">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="date">Tanggal</label>
                                            <input v-model="form.date" type="date" class="form-control flatpickr"
                                                :class="{ 'is-invalid': errors.date }" id="date">
                                            <span class="invalid-feedback">{{ errors.date }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="work_name">Uraian Tugas / RHK / Pekerjaan</label>
                                            <input v-model="form.work_name" type="text" class="form-control"
                                                :class="{ 'is-invalid': errors.work_name }" id="work_name"
                                                placeholder="Masukkan pekerjaan...">
                                            <span class="invalid-feedback">{{ errors.work_name }}</span>
                                        </div>
                                    </div>
                                </div>
                                 <div class="form-group">
                                    <label for="work_detail">Deskripsi Tugas (Opsional)</label>
                                    <!-- <input v-model="form.work_detail" type="text" class="form-control"
                                        :class="{ 'is-invalid': errors.work_detail }" id="work_detail"
                                        placeholder="Masukkan deskripsi tugas..."> -->
                                     <textarea v-model="form.work_detail" class="form-control"
                                        :class="{ 'is-invalid': errors.work_detail }" id="work_detail" rows="3"
                                        placeholder="Masukkan deskripsi tugas..."></textarea> 
                                     <span class="invalid-feedback">{{ errors.work_detail }}</span>
                                </div> 

                                <!-- <div class="form-group">
                                    <label for="work_detail">Deskripsi Tugas (Opsional)</label>
                                    

                                    <SummernoteEditor v-model="form.work_detail"  />

                                    <span class="invalid-feedback">{{ errors.work_detail }}</span>
                                </div> -->


                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="volume">Jumlah Output</label>
                                            <input v-model="form.volume" type="text" class="form-control"
                                                :class="{ 'is-invalid': errors.volume }" id="volume"
                                                placeholder="Masukkan Jumlah...">
                                            <span class="invalid-feedback">{{ errors.volume }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">

                                        <div class="form-group">
                                            <label for="client">Satuan Output</label>
                                            <select v-model="form.unit" id="client" class="form-control"
                                                :class="{ 'is-invalid': errors.unit }">
                                                <option value="1">Berkas</option>
                                                <option value="2">Data</option>
                                                <option value="3">Dokumen</option>
                                                <option value="4">Kali</option>
                                                <option value="5">Kegiatan</option>
                                                <option value="6">Laporan</option>
                                                <option value="7">Volume</option>
                                                <option value="8">Modul</option>
                                                <option value="9">Jam</option>
                                                <option value="10">Surat</option>

                                            </select>
                                            <span class="invalid-feedback">{{ errors.unit }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="evidence">Nama Bukti Dukung</label>
                                    <input v-model="form.evidence" type="text" class="form-control"
                                        :class="{ 'is-invalid': errors.evidence }" id="evidence"
                                        placeholder="Nama Bukti, Ex: Dokumentasi Foto">
                                    <span class="invalid-feedback">{{ errors.evidence }}</span>
                                </div>

                                <div class="form-group">
                                    <label for="evidence_url">Link Bukti Dukung (Opsional)</label>
                                    <textarea v-model="form.evidence_url" class="form-control"
                                        :class="{ 'is-invalid': errors.evidence_url }" id="evidence_url" rows="3"
                                        placeholder="Link Bukti, Ex: https://drive.google.com/file/d/x65t7...dst"></textarea>
                                    <span class="invalid-feedback">{{ errors.evidence_url }}</span>
                                </div>


                                <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                                    <div v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status">
                                        <span class="sr-only ">Loading...</span>
                                    </div>
                                    <span v-else>Submit</span>
                                </button>
                            </Form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>