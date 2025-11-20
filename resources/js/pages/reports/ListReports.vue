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
import { useScreenDisplayStore } from '../../stores/ScreenDisplayStore.js';

const screenDisplayStore = useScreenDisplayStore();
const loadingStore = useLoadingStore();
const authUserStore = useAuthUserStore();
const reports = ref({ 'data': [] });


const getReports = (page = 1) => {
    loadingStore.toggleLoading();
    axios.get(`/api/reports?monthyear=${mySelected.value}&page=${page}`)
        .then((response) => {
            // console.log(response.data);
            reports.value = response.data;
            loadingStore.toggleLoading();
        })
        .catch((error) => {
            if (error.response && error.response.status === 401) {
                // Redirect langsung ke login page
                window.location.href = '/login';
            } else {
                console.error('Terjadi kesalahan:', error);
            }
        });
};

const deleteWork = (report_x, work_id) => {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/api/reports/${work_id}`)
                .then((response) => {
                    reports.value.data[report_x].works = reports.value.data[report_x].works.filter(work => work.id !== work_id);
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        icon: "success"
                    });
                });
        }
    });
};

const deleteReport = (report_x, report_id) => {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/api/parent-reports/${report_id}`)
                .then((response) => {
                    reports.value.data = reports.value.data.filter(report => report.id !== report_id);
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        icon: "success"
                    });
                });
        }
    });
};


const mySelected = useStorage('SettingStore:mySelected', ref(''));

const myOptions = ref([
    {
        text: moment().subtract(5, 'months').format('MMMM  YYYY'),
        id: moment().subtract(5, 'months').format('YYYY-MM')
    },
    {
        text: moment().subtract(4, 'months').format('MMMM  YYYY'),
        id: moment().subtract(4, 'months').format('YYYY-MM')
    },
    {
        text: moment().subtract(3, 'months').format('MMMM  YYYY'),
        id: moment().subtract(3, 'months').format('YYYY-MM')
    },
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

const myChangeEvent = () => {
    // console.log(mySelected.value);
    getReports();
};

const printLCKB = () => {
    // window.location = '/print-lckb/' + mySelected.value;
    if (!authUserStore.user.nama_pemeriksa) {
        alert('Nama dan NIP pemeriksa belum diisi!. Harap diisi di profil!');
    } else if (!mySelected.value) {
        alert('Pilih bulan dan tahun terlebih dahulu!');
    } else {
        window.open('/print-lckb/' + mySelected.value, '_blank');
    }
}

onMounted(() => {
    // console.log(mySelected.value);
    getReports();
});
</script>

<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Laporan Kerja</h1>
                </div>
                <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Laporan Kerja</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>


    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between mb-2">
                        <div v-if="!screenDisplayStore.isMobile">
                            <router-link to="/admin/reports/create">
                                <button class="btn btn-primary">
                                    <i class="fa fa-plus-circle mr-1"></i>
                                    Tambah
                                </button>
                            </router-link>

                        </div>
                        <div class="btn-group">

                            <button @click="printLCKB()" class="btn btn-secondary">
                                <span class="mr-1">Print</span>
                            </button>

                            <select v-model="mySelected" class="custom-select rounded-0" @change="myChangeEvent()">
                                <option v-for="opt in myOptions" :value="opt.id" :key="opt.id">{{ opt.text }}</option>
                            </select>


                            <!-- <Select2 v-model="monthYear" :options="myOptions" @change="myChangeEvent" class="custom-select rounded-0"
                                @select="mySelectEvent"  
                                :settings="{
                                    selectionCssClass : 'custom-select rounded-0'
                                }" /> -->


                        </div>


                    </div>

                    <!-- <div class="timeline" v-if="isMobile">
                        <div class="time-label">
                            <span class="bg-red">10 Feb. 2014</span>
                        </div>
                        <template v-for="report in reports.data" :key="report.id">
                            <template v-for="work in report.works" :key="work.id">
                                <div>
                                    <div class="timeline-item">
                                        <span class="time"> <router-link :to="`/admin/reports/${work.id}/edit`"
                                                class="badge badge-info right" style="margin-right: 5px;"> Edit
                                            </router-link>
                                            <a class="badge badge-danger right" href="#"
                                                @click.prevent="$event => deleteWork(index, work.id)">
                                                Delete
                                            </a></span>
                                        <h3 class="timeline-header"> <span class="time"><i class="fas fa-clock"></i> {{
                                            formatDateStringHuman(report.date) }}</span></h3>
                                        <div class="timeline-body">
                                            [{{ work.volume }} {{ work.unit }}] {{ work.work_name }}<br>
                                            <span style="font-size: smaller;">
                                                {{ work.work_detail }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>
</template>

</div> -->

                    <!-- TRY -->
                    <div class="timeline" v-if="screenDisplayStore.isMobile"
                        :style="reports.last_page == 1 ? 'margin-bottom: 100px' : ''">
                        <hr>
                        <!-- v-if="loadingStore.isLoading" -->
                        <div class="text-center" v-if="loadingStore.isLoading">
                            <div class="overlay">
                                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                                <div class="text-bold pt-2">Loading...</div>
                            </div>
                        </div>
                        <template v-if="reports.data.length > 0">
                            <template v-for="(report, index) in reports.data">
                                <template v-if="report.works.length > 0">

                                    <template v-for="(work, iSub) in report.works" :key="work.id">
                                        <div v-if="iSub === 0" class="time-label">
                                            <span class="bg-primary badge badge-primary badge-sm">
                                                {{
                    formatDateStringHuman(report.date) }}
                                            </span>
                                        </div>
                                        <div style="margin-bottom: 10px;">
                                            <div class="timeline-item">
                                                <span class="time">
                                                    <router-link :to="`/admin/reports/${work.id}/edit`"
                                                        class="badge badge-info right" style="margin-right: 3px;">
                                                        Ubah
                                                    </router-link>
                                                    <a href="#" class="badge badge-danger right"
                                                        @click.prevent="$event => deleteWork(index, work.id)">
                                                        Hapus
                                                    </a>
                                                </span>
                                                <h3 class="timeline-header"
                                                    style="font-size: smaller;line-height: 1 !important;"> <span
                                                        class="time"> {{
                    formatDateStringHuman(report.date) }}
                                                    </span></h3>
                                                <div class="timeline-body"
                                                    style="font-size: small; line-height: 1 !important;">
                                                    [{{ work.volume }} {{ work.unit }}] {{ work.work_name }}<br>
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
                                        <a class="badge badge-danger" style="margin-left: 3px;" href="#"
                                            @click.prevent="$event => deleteReport(index, report.id)">
                                            <!-- <i class="fa fa-trash text-danger"></i> --> hapus
                                        </a>
                                    </div>
                                    <div :key="report.id">
                                        .: Belum ada Data :.
                                    </div>
                                </template>
                            </template>
                        </template>
                        <template v-else>
                            <div class="text-center">
                                .: Belum ada Data :.
                            </div>
                        </template>
                    </div>
                    <!-- ENDTRY -->



                    <div class="card" v-if="!screenDisplayStore.isMobile">
                        <div class="card-body">
                            <div class="table-responsive overlay-wrapper">
                                <div v-if="loadingStore.isLoading" class="overlay"><i
                                        class="fas fa-3x fa-sync-alt fa-spin"></i>
                                    <div class="text-bold pt-2">Loading...</div>
                                </div>
                                <table class="table table-bordered" style="line-height: 1 !important;">
                                    <thead>
                                        <tr>
                                            <th scope="col text-sm">Tanggal</th>
                                            <th scope="col text-sm" class="text-center">#</th>
                                            <th scope="col text-sm" width="50%">Pekerjaan</th>
                                            <!-- <th scope="col text-sm">Detil Kerja</th> -->
                                            <th scope="col text-sm" class="text-right">Vol</th>
                                            <th scope="col text-sm">Unit</th>
                                            <th scope="col text-sm">Eviden</th>
                                            <th scope="col text-sm">Options</th>

                                        </tr>
                                    </thead>
                                    <tbody v-if="reports.data?.length > 0">


                                        <template v-for="(report, index) in reports.data">
                                            <template v-if="report.works.length > 0">

                                                <tr v-for="(work, iSub) in report.works" :key="work.id">
                                                    <td v-if="iSub === 0" :rowspan="report.works.length"
                                                        class="s text-sm" style="white-space: pre;">
                                                        {{ formatDateString(report.date) }}</td>
                                                    <td class="text-center text-sm">{{ iSub + 1 }}</td>
                                                    <td class="s text-sm">
                                                        {{ work.work_name }} <br>
                                                        <p class="text-muted m-0 p-0"
                                                            style="font-size:smaller !important;" v-html="work.work_detail"></p>
                                                    </td>
                                                    <!-- <td class="s">{{ work.work_detail }}</td> -->
                                                    <td class="s text-right text-sm">{{ work.volume }}</td>
                                                    <td class="s text-sm">{{ work.unit }}</td>
                                                    <td class="s text-sm">
                                                        <template v-if="work.evidence_url">
                                                            <a target="blank" :href="work.evidence_url ?? '#'">{{
                    work.evidence }}</a>
                                                        </template>
                                                        <template v-else>
                                                            {{ work.evidence }}
                                                        </template>

                                                    </td>
                                                    <td class="text-center">
                                                        <router-link :to="`/admin/reports/${work.id}/edit`">
                                                            <i class="fa fa-edit"></i>
                                                        </router-link>
                                                        <a href="#"
                                                            @click.prevent="$event => deleteWork(index, work.id)">
                                                            <i class="fa fa-trash text-danger ml-2"></i>
                                                        </a>
                                                    </td>


                                                </tr>
                                            </template>

                                            <template v-else>
                                                <tr :key="report.id">
                                                    <td class="s text-sm" style="white-space: pre;">
                                                        {{ formatDateString(report.date) }}</td>
                                                    <td class="s text-center text-sm">
                                                        <a href="#"
                                                            @click.prevent="$event => deleteReport(index, report.id)">
                                                            <i class="fa fa-trash text-danger"></i>
                                                        </a>
                                                    </td>
                                                    <td colspan="7" class="text-center text-sm">Belum ada data...</td>
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
                                            <td colspan="7" class="text-center">Tidak ada hasil, silahkan pilih bulan...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <Bootstrap4Pagination :data="reports" @pagination-change-page="getReports" :limit="1"
                        :keepLength="true" style="margin-bottom: 100px;" />
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

.time-label {
    margin-bottom: 5px !important;
}

.timeline {
    margin: 0 0 0;
}
</style>