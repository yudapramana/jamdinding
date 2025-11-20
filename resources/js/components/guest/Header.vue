<script setup>
import { useRouter } from 'vue-router';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import { useSettingStore } from '../../stores/SettingStore';
import CloudImage from '../../components/CloudImage.vue';

const router = useRouter();
const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);
const authUserStore = useAuthUserStore();

const logout = () => {
    authUserStore.logout();
};
</script>

<template>
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
        <div class="container">
            <a href="../../index3.html" class="navbar-brand">
                <!-- <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="AdminLTE Logo"
                    class="brand-image img-circle elevation-3" style="opacity: .8"> -->
                <span class="brand-text font-weight-light">{{ settingStore.setting.app_name }}</span>
            </a>

            <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse"
                aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse order-3" id="navbarCollapse">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <router-link to="/user/dashboard" active-class="active" class="nav-link">Home</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/user/profile" active-class="active" class="nav-link">Profil</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/user/docs" active-class="active" class="nav-link">Dokumen</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/user/upload" active-class="active" class="nav-link">Upload</router-link>
                    </li>
                    <!-- <li class="nav-item">
                        <router-link to="/user/flipbook" active-class="active" class="nav-link">Flipbook</router-link>
                    </li> -->
                </ul>
            </div>

            <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
                <li v-if="authUserStore.user.can_multiple_role" class="nav-item">
                    <button @click="authUserStore.switchLayout" class="btn btn-sm btn-primary mt-2 mr-2">
                        Admin
                    </button>
                </li>

                <!-- <li class="nav-item dropdown">
                    <span class="nav-link">{{ authUserStore.user.employee.full_name }}</span>
                </li> -->

                <li class="nav-item dropdown">
                    <a href="#" @click.prevent="logout" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</template>
