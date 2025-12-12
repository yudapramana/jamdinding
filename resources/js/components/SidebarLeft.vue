<script setup>
import { useRouter } from 'vue-router';
import { useAuthUserStore } from '../stores/AuthUserStore';
import { useSettingStore } from '../stores/SettingStore';
import CloudImage from '../components/CloudImage.vue';
import { getActivePinia } from "pinia";

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
          <img :src="authUserStore.user.avatar" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block" style="font-size: small;">{{ authUserStore.user.name }}</a>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

          <!-- ===================== -->
          <!-- BERANDA -->
          <!-- ===================== -->
          <li class="nav-header">BERANDA</li>
          <li class="nav-item">
            <router-link to="/admin/dashboard" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Beranda</p>
            </router-link>
          </li>

          <!-- ===================== -->
          <!-- CORE (MASTER.CORE.*) -->
          <!-- ===================== -->
          <template v-if="authUserStore.can('master.core')">
            <li class="nav-header">CORE</li>

            <li class="nav-item" v-if="authUserStore.can('master.core.branches')">
              <router-link
                to="/admin/master-branches-groups-categories"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/master-branches-groups-categories') }"
              >
                <i class="nav-icon fas fa-sitemap"></i>
                <p>Cabang (Core)</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('master.core.fields')">
              <router-link
                to="/admin/master-list-fields"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/master-list-fields') }"
              >
                <i class="nav-icon fas fa-list-alt"></i>
                <p>Bidang Penilaian (Core)</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('master.core.permissions')">
              <router-link
                to="/admin/master-permission-role"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/master-permission-role') }"
              >
                <i class="nav-icon fas fa-user-shield"></i>
                <p>Hak Akses Role</p>
              </router-link>
            </li>
          </template>

          <!-- ===================== -->
          <!-- MASTER DATA (MASTER.MANAGE.*) -->
          <!-- ===================== -->
          <template v-if="authUserStore.can('master.manage.branches')
                        || authUserStore.can('master.manage.groups')
                        || authUserStore.can('master.manage.categories')
                        || authUserStore.can('master.manage.fields-components')
                        || authUserStore.can('master.manage.participants')">

            <li class="nav-header">MASTER DATA</li>

            <li class="nav-item" v-if="authUserStore.can('master.manage.branches')">
              <router-link
                to="/admin/master-branches"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/master-branches') }"
              >
                <i class="nav-icon fas fa-sitemap"></i>
                <p>Master Cabang</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('master.manage.groups')">
              <router-link
                to="/admin/master-groups"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/master-groups') }"
              >
                <i class="nav-icon fas fa-layer-group"></i>
                <p>Master Golongan</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('master.manage.categories')">
              <router-link
                to="/admin/master-categories"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/master-categories') }"
              >
                <i class="nav-icon fas fa-tags"></i>
                <p>Master Kategori</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('master.manage.fields-components')">
              <router-link
                to="/admin/master-field-components"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/master-field-components') }"
              >
                <i class="nav-icon fas fa-sliders-h"></i>
                <p>Master Bidang Penilaian</p>
              </router-link>
            </li>

            <!-- jika ada halaman master peserta -->
            <li class="nav-item" v-if="authUserStore.can('master.manage.participants')">
              <router-link
                to="/admin/master-participants"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/master-participants') }"
              >
                <i class="nav-icon fas fa-user"></i>
                <p>Master Peserta</p>
              </router-link>
            </li>
          </template>

          <!-- ===================== -->
          <!-- MANAGED DATA (MANAGE.EVENT.*) -->
          <!-- ===================== -->
          <template v-if="authUserStore.can('manage.event')">
            <li class="nav-header">MANAGED DATA</li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.events')">
              <router-link
                to="/admin/events"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/events') }"
              >
                <i class="nav-icon fas fa-calendar-alt"></i>
                <p>Event</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.stages')">
              <router-link
                to="/admin/event-stage"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/event-stage') }"
              >
                <i class="nav-icon fas fa-stream"></i>
                <p>Tahapan Event</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.branches')">
              <router-link
                to="/admin/event-branches"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/event-branches') }"
              >
                <i class="nav-icon fas fa-code-branch"></i>
                <p>Cabang Event</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.groups')">
              <router-link
                to="/admin/event-groups"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/event-groups') }"
              >
                <i class="nav-icon fas fa-users"></i>
                <p>Golongan Event</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.categories')">
              <router-link
                to="/admin/event-categories"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/event-categories') }"
              >
                <i class="nav-icon fas fa-venus-mars"></i>
                <p>Kategori Event</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.fields-components')">
              <router-link
                to="/admin/event-field-components"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/event-field-components') }"
              >
                <i class="nav-icon fas fa-sliders-h"></i>
                <p>Bidang Penilaian Event</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.user')">
              <router-link
                to="/admin/event-users"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/event-users') }"
              >
                <i class="nav-icon fas fa-users-cog"></i>
                <p>User Event</p>
              </router-link>
            </li>
          </template>

          <!-- ===================== -->
          <!-- PESERTA (MANAGE.EVENT.PARTICIPANT.*) -->
          <!-- ===================== -->
          <template v-if="authUserStore.can('manage.event.participant.bank-data')
                        || authUserStore.can('manage.event.participant.registration')
                        || authUserStore.can('manage.event.participant.reregistration')">

            <li class="nav-header">PESERTA</li>

            <!-- BANK DATA -->
            <li class="nav-item" v-if="authUserStore.can('manage.event.participant.bank-data')">
              <router-link
                :to="{ name: 'admin.event.participants.bank-data' }"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.name === 'admin.event.participants.bank-data' }"

              >
                <i class="nav-icon fas fa-database"></i>
                <p>Bank Data</p>
              </router-link>
            </li>

            <!-- PENDAFTARAN / VERIFIKASI -->
            <li class="nav-item" v-if="authUserStore.can('manage.event.participant.registration')">
              <router-link
                :to="{ name: 'admin.event.participants.registration', params: { status: 'process' } }"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.name === 'admin.event.participants.registration' }"
              >
                <i class="nav-icon fas fa-user-plus"></i>
                <p v-if="authUserStore.user?.role?.slug === 'verifikator'">
                  Verifikasi
                </p>
                <p v-else>
                  Pendaftaran
                </p>
              </router-link>
            </li>

            <!-- DAFTAR ULANG -->
            <li class="nav-item" v-if="authUserStore.can('manage.event.participant.reregistration')">
              <router-link
                :to="{ name: 'admin.event.participants.reregistration' }"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.name === 'admin.event.participants.reregistration' }"
              >
                <i class="nav-icon fas fa-user-check"></i>
                <p>Daftar Ulang</p>
              </router-link>
            </li>

            <!-- PESERTA FINAL -->
            <li class="nav-item" v-if="authUserStore.can('manage.event.participant.final')">
              <router-link
                :to="{ name: 'admin.event.participants.final' }"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.name === 'admin.event.participants.final' }"
              >
                <i class="nav-icon fas fa-user-check"></i>
                <p>Final</p>
              </router-link>
            </li>
          </template>

          <!-- ===================== -->
          <!-- LOGOUT -->
          <!-- ===================== -->
          <li class="nav-item">
            <form class="nav-link">
              <a href="#" @click.prevent="logout">
                <i class="nav-icon fas fa-sign-out-alt"></i>
                <p>Keluar</p>
              </a>
            </form>
          </li>

        </ul>
      </nav>

    </div>
  </aside>
</template>
