<script setup>
import axios from "axios";
import { ref, onMounted, reactive, watch, toDisplayString } from "vue";
import { formatDate, formatDateString, formatDateStringHuman } from '../../helper.js';
import { Bootstrap4Pagination } from 'laravel-vue-pagination';
import Swal from 'sweetalert2';
import moment from 'moment';
import { useStorage } from '@vueuse/core';
import { useAuthUserStore } from "../../stores/AuthUserStore.js";
import { useLoadingStore } from "../../stores/LoadingStore.js";
import Select2 from 'vue3-select2-component';
import { useMasterDataStore } from "../../stores/MasterDataStore.js";
import { useScreenDisplayStore } from '../../stores/ScreenDisplayStore.js';

const screenDisplayStore = useScreenDisplayStore();
const previewData = ref(false);
const previewPDF = ref(false);
const loading = ref(false);
const placeholder = ref('Pilih Item');
const loadingStore = useLoadingStore();
const authUserStore = useAuthUserStore();
const masterDataStore = useMasterDataStore();
const reports = ref({ 'data': [] });

const mySelected = useStorage('SettingStore:mySelected', ref(''));

const myOptions = ref([
    {
        text: moment().subtract(2, 'months').format('MMMM  YYYY'),
        id: moment().subtract(2, 'months').format('YYYY-MM')
    },
    {
        text: moment().subtract(1, 'months').format('MMMM  YYYY'),
        id: moment().subtract(1, 'months').format('YYYY-MM')
    },
    {
        text: moment().format('MMMM  YYYY'),
        id: moment().format('YYYY-MM')
    },
]);

const getReports = (page = 1) => {
    loading.value = true;
    axios.get(`/api/reports?monthyear=${mySelected.value}&page=${page}&user_id=${masterDataStore.userId}`)
        .then((response) => {
            console.log(response.data);
            reports.value = response.data;
            loading.value = false;
            previewData.value = true;
            previewPDF.value = false;
            loading.value = false;
        }).catch((error) => {
            console.log(error.response.data)
            if (error.response && error.response.status === 401) {
                // Redirect langsung ke login page
                window.location.href = '/login';
            } else {
                console.error('Terjadi kesalahan:', error);
            }
        });
};

const getPDF = () => {
    loading.value = true;
    previewData.value = false;
    previewPDF.value = true;

    setTimeout(function () {
        loading.value = false;
    }, 2000)

}


onMounted(() => {
    masterDataStore.getOrgList();

    if (authUserStore.user.role == 'ADMIN') {
        masterDataStore.getUserListbyOrgID(authUserStore.user.org_id);
    }

});
</script>

<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">LCHK Satker</h1>
                </div>
                <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">LCKH Satker</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>


    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="row">

                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="mont" style="font-size: small;">Bulan</label>
                                        <!-- <Select2 v-model="mySelected" :options="myOptions" :placeholder="placeholder" /> -->
                                        <select v-model="mySelected" class="custom-select rounded-0">
                                            <option v-for="opt in myOptions" :value="opt.id" :key="opt.id">{{ opt.text
                                                }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-6"
                                    v-if="authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'REVIEWER'">
                                    <div class="form-group">
                                        <label for="organization_id" style="font-size: small;">Satuan Kerja</label>

                                        <!-- <Dropdown v-model="masterDataStore.orgId" :options="masterDataStore.orgList" optionLabel="name" placeholder="Select a City" class="form-control" /> -->

                                        <!-- <Select2 v-model="masterDataStore.orgId" :options="masterDataStore.orgList"
                                            @change="masterDataStore.getUserList" @select="masterDataStore.getUserList"
                                            :placeholder="placeholder" /> -->

                                        <select v-model="masterDataStore.orgId" class="custom-select rounded-0"
                                            :placeholder="placeholder"
                                            @change="masterDataStore.getUserList(masterDataStore.orgId)"
                                            @select="masterDataStore.getUserList(masterDataStore.orgId)">
                                            <option v-for="opt in masterDataStore.orgList" :value="opt.id"
                                                :key="opt.id">{{
                                                    opt.text }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-6" v-else>
                                    <div class="form-group">
                                        <label for="organization_id" style="font-size: small;">Satuan Kerja</label>
                                        <input type="text" class="form-control" disabled
                                            :value="authUserStore.user.org_name">

                                    </div>
                                </div>

                                <div class="col-12"
                                    v-if="authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'REVIEWER'">
                                    <div class="form-group">
                                        <label for="user_id" style="font-size: small;">Pilih Pegawai</label>
                                        <!-- <Select2 v-model="masterDataStore.userId" :options="masterDataStore.userList"
                                            :placeholder="placeholder" /> -->

                                        <select v-model="masterDataStore.userId" class="custom-select rounded-0"
                                            :placeholder="placeholder">
                                            <option v-for="opt in masterDataStore.userList" :value="opt.id"
                                                :key="opt.id">{{
                                                    opt.text }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12" v-else>
                                    <div class="form-group">
                                        <label for="user_id" style="font-size: small;">Pilih Pegawai</label>
                                        <!-- <Select2 v-model="masterDataStore.userId" :options="masterDataStore.userList"
                                            :placeholder="placeholder" /> -->

                                        <select v-model="masterDataStore.userId" class="custom-select rounded-0"
                                            :placeholder="placeholder">
                                            <option v-for="opt in masterDataStore.userList" :value="opt.id"
                                                :key="opt.id">{{
                                                    opt.text }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-6">

                                    <button type="submit" class="btn btn-primary btn-block" :disabled="loading"
                                        @click.prevent="getReports">
                                        <div v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status">
                                            <span class="sr-only ">Loading...</span>
                                        </div>
                                        <span v-else>Preview Data</span>
                                    </button>
                                </div>

                                <div class="col-6">

                                    <button type="submit" class="btn btn-warning btn-block" :disabled="loading"
                                        @click.prevent="getPDF">
                                        <div v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status">
                                            <span class="sr-only ">Loading...</span>
                                        </div>
                                        <span v-else>Preview PDF</span>
                                    </button>
                                </div>
                            </div>

                            <!-- <select v-model="masterDataStore.orgId" class="custom-select rounded-0">
                                <option v-for="opt in masterDataStore.orgList" :value="opt.id" :key="opt.id">{{
                                    opt.text }}</option>
                            </select> -->
                        </div>
                    </div>


                    <!-- TRY -->
                    <div class="timeline" v-if="previewData && screenDisplayStore.isMobile">
                        <hr>
                        <template v-if="reports.data.length > 0">
                            <template v-for="(report, index) in reports.data">
                                <template v-if="report.works.length > 0">

                                    <template v-for="(work, iSub) in report.works" :key="work.id">
                                        <div v-if="iSub === 0" class="time-label">
                                            <span class="bg-primary badge badge-primary badge-sm">{{
                                                formatDateStringHuman(report.date) }}</span>
                                        </div>
                                        <div>
                                            <div class="timeline-item">
                                                <span class="time">
                                                    <!-- <router-link :to="`/admin/reports/${work.id}/edit`"
                                                    class="badge badge-info right" style="margin-right: 3px;">
                                                    Ubah
                                                </router-link>
                                                <a href="#" class="badge badge-danger right"
                                                    @click.prevent="$event => deleteWork(index, work.id)">
                                                    Hapus
                                                </a> -->
                                                    [{{ work.volume }} {{ work.unit }}]
                                                </span>
                                                <h3 class="timeline-header"
                                                    style="font-size: smaller;line-height: 1 !important;"> <span
                                                        class="time"> {{
                                                            formatDateStringHuman(report.date) }}</span></h3>
                                                <div class="timeline-body"
                                                    style="font-size: small; line-height: 1 !important;">
                                                    {{ work.work_name }}<br>
                                                    <span style="font-size: smaller;">
                                                        {{ work.work_detail }}
                                                    </span><br>
                                                    <div style="text-align: right;">

                                                        <span style="font-size: smaller; width:100%">
                                                            Bukti Dukung
                                                            <template v-if="work.evidence_url">
                                                                <a target="blank" :href="work.evidence_url ?? '#'">
                                                                    [ {{
                                                                        work.evidence }} ]</a>
                                                            </template>
                                                            <template v-else>
                                                                [ {{ work.evidence }} ]
                                                            </template>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                                <template v-else>
                                    <div class="time-label" :key="report.id">
                                        <span class="bg-primary badge badge-primary badge-sm">
                                            {{ formatDateStringHuman(report.date) }}
                                        </span>
                                        <!-- <a class="badge badge-danger" style="margin-left: 3px;" href="#"
                                        @click.prevent="$event => deleteReport(index, report.id)">
                                         hapus
                                    </a> -->
                                    </div>
                                    <div :key="report.id" class="text-center">
                                        .: Belum ada Data :.
                                    </div>
                                </template>
                            </template>
                        </template>
                        <template v-else>
                            <div class="text-center">
                                .: Belum ada data :.
                            </div>
                        </template>

                    </div>
                    <!-- ENDTRY -->

                    <!-- <div class="timeline" v-if="isMobile">
                        <template v-for="report in reports.data" :key="report.id">
                            <template v-for="work in report.works" :key="work.id">
                                <div>
                                    <div class="timeline-item">
                                        <span class="time"> {{ work.volume }} {{ work.unit }}
                                           
                                        </span>
                                        <h3 class="timeline-header"> <span class="time"><i class="fas fa-clock"></i> {{
                                            formatDateStringHuman(report.date) }}</span></h3>
                                        <div class="timeline-body">
                                             {{ work.work_name }}<br>
                                            <span style="font-size: smaller;">
                                                {{ work.work_detail }}
                                            </span>
                                        </div>
                                        
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div> -->

                    <div class="card" v-if="previewData && !screenDisplayStore.isMobile">
                        <div class="card-body">
                            <div class="table-responsive overlay-wrapper" id="example-table">
                                <div v-if="loadingStore.isLoading" class="overlay"><i
                                        class="fas fa-3x fa-sync-alt fa-spin"></i>
                                    <div class="text-bold pt-2">Loading...</div>
                                </div>


                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th scope="col">Tanggal</th>
                                            <th scope="col" class="text-center">#</th>
                                            <th scope="col" width="50%">Pekerjaan</th>
                                            <th scope="col" class="text-right">Volume</th>
                                            <th scope="col">Eviden</th>

                                        </tr>
                                    </thead>
                                    <tbody v-if="reports.data?.length > 0">


                                        <template v-for="(report, index) in reports.data">
                                            <template v-if="report.works.length > 0">

                                                <tr v-for="(work, iSub) in report.works" :key="work.id">
                                                    <td v-if="iSub === 0" :rowspan="report.works.length" class="s"
                                                        style="white-space: pre;">
                                                        {{ formatDateString(report.date) }}</td>
                                                    <td class="text-center">{{ iSub + 1 }}</td>
                                                    <td class="s">
                                                        {{ work.work_name }} <br>
                                                        <p class="text-muted m-0 p-0"
                                                            style="font-size: small !important;">
                                                            {{
                                                                work.work_detail }}</p>
                                                    </td>
                                                    <!-- <td class="s">{{ work.work_detail }}</td> -->
                                                    <td class="s text-center">{{ work.volume }}&nbsp;{{ work.unit }}
                                                    </td>
                                                    <td class="s">{{ work.evidence }}</td>


                                                </tr>
                                            </template>

                                            <template v-else>
                                                <tr :key="report.id">
                                                    <td class="s">{{
                                                        formatDateString(report.date) }}
                                                    </td>
                                                    <td class="s text-center">
                                                        <!-- <a href="#"
                                                            @click.prevent="$event => deleteReport(index, report.id)">
                                                            <i class="fa fa-trash text-danger"></i>
                                                        </a> -->
                                                    </td>
                                                    <td colspan="7" class="text-center">Belum ada data...</td>
                                                </tr>
                                            </template>

                                        </template>



                                        <!-- <template v-for="item in items">
                                        <tr v-for="(subitem, iSub) in item.sub" :key="subitem.id">
                                            <td>{{ subitem.date }}</td>
                                            <td v-if="iSub === 0" :rowspan="item.sub.length" class="s">{{ item.name }}</td>
                                        </tr>
                                    </template> -->
                                    </tbody>

                                    <tbody v-if="reports.data?.length === 0">
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada hasil, silahkan pilih
                                                bulan...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <Bootstrap4Pagination :data="reports" @pagination-change-page="getReports" :limit="1"
                        :keepLength="true" v-if="previewData" style="margin-bottom: 100px;" />


                    <div v-if="previewPDF">
                        <embed :src="`/preview-lckb/${mySelected}/${masterDataStore.userId}`" width="100%" height="600"
                            type='application/pdf'>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.select2-selection {
    height: auto !important;
}

.timeline::before {
    border-radius: 0 !important;
    background-color: transparent !important;
    bottom: 0;
    content: "";
    left: 0 !important;
    margin: 0;
}

.timeline>div>.timeline-item {
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.timeline>div {
    margin-bottom: 15px;
    margin-right: 0 !important;
}
</style>