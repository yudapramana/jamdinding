<script setup>
import { useAuthUserStore } from "../stores/AuthUserStore.js";
import { useScreenDisplayStore } from '../stores/ScreenDisplayStore.js';

const screenDisplayStore = useScreenDisplayStore();

const authUserStore = useAuthUserStore();

</script>

<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Beranda</h1>
                    <!-- authUserStore.isAdminRole : {{ authUserStore.isAdminRole }} <br>
                    authUserStore.activeLayout : {{ authUserStore.activeLayout }} <br> -->
                </div>
                <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Beranda</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>


    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 col-sm-12 col-lg-6">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0 pb-0 mb-0">
                            {{ authUserStore.user?.employee?.job_title }}
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-12">
                                    <h2 class="lead"><b>{{ authUserStore.user.name }}</b></h2>
                                    <!-- <p class="text-muted text-sm"><b>NIP: </b> {{ authUserStore.user.username }} </p> -->
                                    <ul class="ml-5 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i class="far fa-id-card"></i></span> {{ authUserStore.user.username }}</li>
                                        <li class="small"><span class="fa-li"><i
                                                    class="fas fa-lg fa-building"></i></span> {{ authUserStore.user?.employee?.work_unit?.unit_name }}</li>
                                    </ul>
                                </div>
                                <!-- <div class="col-2 text-center">
                                    <img :src="authUserStore.user.avatar" alt="user-avatar"
                                        class="img-circle img-fluid">
                                </div> -->
                            </div>
                        </div>
                        <div class="card-footer b-0 p-0">
                            <div class="btn-group" style="width: 100% !important;">
                                <router-link to="/admin/vervals/" class="btn btn-sm btn-success" style="display: block; font-size: small;">
                                    Verval Dokumen <i class="fas fa-arrow-circle-right"></i>
                                </router-link>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
            </div>

           
        </div>
    </div>
</template>