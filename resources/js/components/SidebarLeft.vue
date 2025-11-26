<script setup>
import { useRouter } from 'vue-router';
import { useAuthUserStore } from '../stores/AuthUserStore';
import { useSettingStore } from '../stores/SettingStore';
import CloudImage from '../components/CloudImage.vue';
import { getActivePinia } from "pinia"

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
    <aside class="main-sidebar sidebar-dark-primary elevation-4">

        <a href="#" class="brand-link">
            <img src="/app_logo.png" alt="AdminLTE Logo"
                class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">{{ settingStore.setting.app_name }}</span>
        </a>

        <div class="sidebar">

            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <!-- <CloudImage :image-name="authUserStore.user.avatar" /> -->

                    <img :src="authUserStore.user.avatar" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block" style="font-size: small;">{{ authUserStore.user.name }}</a>
                </div>
            </div>


            



            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                    <li class="nav-header">BERANDA</li>
                    <li class="nav-item">
                        <router-link to="/admin/dashboard" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Beranda</p>
                        </router-link>
                    </li>

                    <template v-if="authUserStore.can('master.manage.stage')">
                        <li class="nav-header">MASTER</li>

                        <!-- Master Template Tahapan -->
                        <li class="nav-item" v-if="authUserStore.can('master.manage.stage')">
                            <router-link
                                to="/admin/master-stage"
                                class="nav-link"
                                active-class="active"
                                :class="{ 'active': $route.path.startsWith('/admin/master-stage') }"
                            >
                                <i class="nav-icon fas fa-layer-group"></i>
                                <p>Tahapan</p>
                            </router-link>
                        </li>

                        <li
                            class="nav-item menu-open"
                        >
                            <a href="#" class="nav-link"
                                :class="{
                                    'active':
                                        $route.path.startsWith('/admin/master-competition-group') ||
                                        $route.path.startsWith('/admin/master-competition-category') ||
                                        $route.path.startsWith('/admin/master-competition-branch')
                                }"
                            >
                                <i class="nav-icon fas fa-book"></i>
                                <p>
                                    Master Lomba
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">

                                <!-- Master Group -->
                                <li class="nav-item" v-if="authUserStore.can('master.manage.group')">
                                    <router-link
                                        to="/admin/master-competition-group"
                                        class="nav-link"
                                        active-class="active"
                                        :class="{ 'active': $route.path.startsWith('/admin/master-competition-group') }"
                                    >
                                        <i class="fas fa-layer-group nav-icon"></i>
                                        <p>Cabang</p>
                                    </router-link>
                                </li>

                                <!-- Master Category -->
                                <li class="nav-item" v-if="authUserStore.can('master.manage.category')">
                                    <router-link
                                        to="/admin/master-competition-category"
                                        class="nav-link"
                                        active-class="active"
                                        :class="{ 'active': $route.path.startsWith('/admin/master-competition-category') }"
                                    >
                                        <i class="fas fa-tags nav-icon"></i> 
                                        <p>Kategori</p>
                                    </router-link>
                                </li>

                                <!-- Master Branch -->
                                <li class="nav-item" v-if="authUserStore.can('master.manage.branch')">
                                    <router-link
                                        to="/admin/master-competition-branch"
                                        class="nav-link"
                                        active-class="active"
                                        :class="{ 'active': $route.path.startsWith('/admin/master-competition-branch') }"
                                    >
                                        <i class="fas fa-sitemap nav-icon"></i>
                                        <p>Golongan</p>
                                    </router-link>
                                </li>

                            </ul>
                        </li>

                        <li class="nav-item" v-if="authUserStore.can('master.manage.level')">
                            <router-link
                                to="/admin/master-permission-role"
                                class="nav-link"
                                active-class="active"
                                :class="{ 'active': $route.path.startsWith('/admin/master-permission-role') }"
                            >
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>Hak Akses Role</p>
                            </router-link>
                        </li>
                    </template>


                    <li class="nav-header">MANAGE EVENT</li>

                    <!-- Data Event -->
                    <li class="nav-item" v-if="authUserStore.can('event.manage')">
                    <router-link
                        to="/admin/events"
                        class="nav-link"
                        active-class="active"
                        :class="{ 'active': $route.path.startsWith('/admin/events') }"
                        v-if="
                        authUserStore.user?.role?.slug === 'superadmin' ||
                        authUserStore.user?.role?.slug === 'admin_event'
                        "
                    >
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Data Event</p>
                    </router-link>
                    </li>

                    <!-- Tahapan Event -->
                    <li class="nav-item" v-if="authUserStore.can('event.manage.stage')">
                        <router-link
                            to="/admin/event-stage"
                            class="nav-link"
                            active-class="active"
                            :class="{ 'active': $route.path.startsWith('/admin/event-stage') }"
                        >
                            <i class="nav-icon fas fa-stream"></i>
                            <p>Tahapan Event</p>
                        </router-link>
                    </li>

                    <li class="nav-item" v-if="authUserStore.can('event.manage.branch')">
                        <router-link
                            to="/admin/event-competition-branch"
                            class="nav-link"
                            active-class="active"
                            :class="{ 'active': $route.path.startsWith('/admin/event-competition-branch') }"
                            
                        >
                            <i class="nav-icon fas fa-sitemap"></i>
                            <p>Cabang / Golongan</p>
                        </router-link>
                    </li>

                    <li class="nav-item" v-if="authUserStore.can('event.manage.user')">
                        <router-link
                            to="/admin/event-users"
                            class="nav-link"
                            active-class="active"
                            :class="{ 'active': $route.path.startsWith('/admin/event-users') }"
                        >
                            <i class="nav-icon fas fa-users-cog"></i>
                            <p>User Event</p>
                        </router-link>
                    </li>

                    <li class="nav-item" v-if="authUserStore.can('event.participant.repository')">
                        <router-link
                            to="/admin/participants"
                            class="nav-link"
                            active-class="active"
                            :class="{ 'active': $route.path.startsWith('/admin/participants') }"
                        >
                            <i class="nav-icon fas fa-users"></i>
                            <p>Repositori Peserta</p>
                        </router-link>
                    </li>




                    


                    

                    <li class="nav-header">LAIN-LAIN</li>

                    <li class="nav-item">
                    <router-link
                        to="/admin/docusers"
                        :class="$route.path.startsWith('/admin/docusers') ? 'active' : ''"
                        v-if="authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'ADMIN'"
                        active-class="active"
                        class="nav-link"
                    >
                        <i class="nav-icon fas fa-file-upload"></i>
                        <p>Daftar Upload</p>
                    </router-link>
                    </li>

                    <li class="nav-item">
                    <router-link
                        to="/admin/docprogress"
                        :class="$route.path.startsWith('/admin/docprogress') ? 'active' : ''"
                        v-if="authUserStore.user.role == 'SUPERADMIN'"
                        active-class="active"
                        class="nav-link"
                    >
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Progress</p>
                    </router-link>
                    </li>

                    <li class="nav-item">
                    <router-link
                        to="/admin/vervals"
                        :class="$route.path.startsWith('/admin/vervals') ? 'active' : ''"
                        active-class="active"
                        class="nav-link"
                    >
                        <i class="nav-icon fas fa-clipboard-check"></i>
                        <p>Verval</p>
                    </router-link>
                    </li>

                    <li class="nav-item">
                    <router-link
                        to="/admin/verval-history"
                        :class="$route.path.startsWith('/admin/verval-history') ? 'active' : ''"
                        active-class="active"
                        class="nav-link"
                    >
                        <i class="nav-icon fas fa-history"></i>
                        <p>Log Verval</p>
                    </router-link>
                    </li>

                    <li class="nav-item">
                    <router-link
                        to="/admin/monitor-workout"
                        :class="$route.path.startsWith('/admin/monitor-workout') ? 'active' : ''"
                        v-if="authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'ADMIN' || authUserStore.user.role == 'REVIEWER'"
                        active-class="active"
                        class="nav-link"
                    >
                        <i class="nav-icon fas fa-user-check"></i>
                        <p>Monitor Pegawai</p>
                    </router-link>
                    </li>

                    


                    <li class="nav-header">KELOLA</li>

                    
                    <li class="nav-item">
                        <router-link to="/admin/workunits" :class="$route.path.startsWith('/admin/workunits') ? 'active' : ''" v-if="authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'ADMIN'"
                            active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>
                                Unit Kerja
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item" v-if="authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'ADMIN' || authUserStore.user.role == 'REVIEWER'">
                        <router-link to="/admin/users" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Pengguna
                            </p>
                        </router-link>
                    </li>
                    <!-- <li class="nav-item" v-if="authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'ADMIN'"> -->
                    <li class="nav-item">
                        <router-link to="/admin/admins" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-user"></i>
                            <p>
                                Pengelola
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item" v-if="authUserStore.user.role == 'SUPERADMIN'">
                        <router-link to="/admin/settings" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>
                                Pengaturan
                            </p>
                        </router-link>
                    </li>
                   
                    <li class="nav-item">
                        <form class="nav-link">
                            <a href="#" @click.prevent="logout">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>
                                    Keluar
                                </p>
                            </a>
                        </form>

                    </li>
                </ul>
            </nav>

        </div>

    </aside>
</template>