<script setup>
import { useRouter, useRoute } from 'vue-router'
import { useAuthUserStore } from '../stores/AuthUserStore'
import { useSettingStore } from '../stores/SettingStore'
import { computed, ref, watch } from 'vue'

const router = useRouter()
const route = useRoute()

const settingStore = useSettingStore()
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance)

const authUserStore = useAuthUserStore()

const logout = () => authUserStore.logout()

// ====== permission helpers ======
const canCore = computed(() => authUserStore.can('manage.core'))
const canMaster = computed(() => authUserStore.can('manage.master'))
const canEvent = computed(() => authUserStore.can('manage.event'))

const showMasterSection = computed(() =>
  authUserStore.can('manage.master.branches') ||
  authUserStore.can('manage.master.groups') ||
  authUserStore.can('manage.master.categories') ||
  authUserStore.can('manage.master.fields-components') ||
  authUserStore.can('manage.master.participants')
)

const showPesertaSection = computed(() =>
  authUserStore.can('manage.event.participant.bank-data') ||
  authUserStore.can('manage.event.participant.registration') ||
  authUserStore.can('manage.event.participant.reregistration') ||
  authUserStore.can('manage.event.participant.final')
)

const showCompetitionSection = computed(() =>
  authUserStore.can('manage.event-competitions') ||
  authUserStore.can('manage.event-competitions.judges.user') ||
  authUserStore.can('manage.event-competitions.judges-panel') ||
  authUserStore.can('manage.event-competitions.fields-components') ||
  authUserStore.can('manage.event-competitions.scoring.index') ||
  authUserStore.can('manage.event-competitions.scoring.input-default')
)

// ====== scoring link ======
const lastCompetitionId = computed(() => {
  return localStorage.getItem('last_scoring_competition_id') || null
})

const scoringLink = computed(() => {
  if (!lastCompetitionId.value) {
    return { name: 'admin.event-competitions.scoring.input-default' }
  }
  return {
    name: 'admin.event-competitions.scoring',
    params: { id: lastCompetitionId.value },
  }
})

// ====== helpers active/open ======
const isPathActive = (prefix) => route.path.startsWith(prefix)
const isNameActive = (name) => route.name === name

const openMenu = ref({
  core: false,
  master: false,
  eventData: false,
  peserta: false,
  competition: false,
})

const toggleMenu = (key) => {
  openMenu.value[key] = !openMenu.value[key]
}

// auto-open submenu sesuai route aktif
const syncOpenByRoute = () => {
  openMenu.value.core =
    isPathActive('/admin/core-branches-groups-categories') ||
    isPathActive('/admin/core-list-fields') ||
    isPathActive('/admin/core-permission-role')

  openMenu.value.master =
    isPathActive('/admin/master-branches') ||
    isPathActive('/admin/master-groups') ||
    isPathActive('/admin/master-categories') ||
    isPathActive('/admin/master-field-components') ||
    isPathActive('/admin/master-participants')

  openMenu.value.eventData =
    isPathActive('/admin/events') ||
    isPathActive('/admin/event-stage') ||
    isPathActive('/admin/event-branches') ||
    isPathActive('/admin/event-groups') ||
    isPathActive('/admin/event-categories') ||
    isPathActive('/admin/event-users')

  openMenu.value.peserta =
    isNameActive('admin.event.participants.bank-data') ||
    isNameActive('admin.event.participants.registration') ||
    isNameActive('admin.event.participants.reregistration') ||
    isNameActive('admin.event.participants.final')

  openMenu.value.competition =
    isNameActive('admin.event-competition.judges.user') ||
    isNameActive('admin.event-competition.judges-panel') ||
    isPathActive('/admin/event-field-components') ||
    isNameActive('admin.event-competitions.scoring.index') ||
    isNameActive('admin.event-competitions.scoring.input-default') ||
    isNameActive('admin.event-competitions.scoring')
}

watch(
  () => [route.path, route.name],
  () => syncOpenByRoute(),
  { immediate: true }
)
</script>

<template>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="#" class="brand-link">
      <img
        src="/app_logo.png"
        alt="AdminLTE Logo"
        class="brand-image img-circle elevation-3"
        style="opacity:.8"
      >
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
        <ul
          class="nav nav-pills nav-sidebar flex-column"
          data-widget="treeview"
          role="menu"
          data-accordion="false"
        >
          <!-- ===================== -->
          <!-- BERANDA -->
          <!-- ===================== -->
          <!-- <li class="nav-header">BERANDA</li> -->
          <li class="nav-item">
            <router-link to="/admin/dashboard" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Beranda</p>
            </router-link>
          </li>

          <!-- ===================== -->
          <!-- CORE -->
          <!-- ===================== -->
          <template v-if="canCore">
            <!-- <li class="nav-header">CORE</li> -->

            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.core }">
              <!-- parent clickable -->
              <a href="#" class="nav-link" :class="{ active: openMenu.core }" @click.prevent="toggleMenu('core')">
                <i class="nav-icon fas fa-cogs"></i>
                <p>
                  Core
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.core">
                <li class="nav-item" v-if="authUserStore.can('manage.core.branches')">
                  <router-link
                    to="/admin/core-branches-groups-categories"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/core-branches-groups-categories') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Cabang (Core)</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.core.fields')">
                  <router-link
                    to="/admin/core-list-fields"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/core-list-fields') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Bidang Penilaian (Core)</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.core.permissions')">
                  <router-link
                    to="/admin/core-permission-role"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/core-permission-role') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Hak Akses Role</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- ===================== -->
          <!-- MASTER DATA -->
          <!-- ===================== -->
          <template v-if="canMaster && showMasterSection">
            <!-- <li class="nav-header">MASTER DATA</li> -->

            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.master }">
              <a href="#" class="nav-link" :class="{ active: openMenu.master }" @click.prevent="toggleMenu('master')">
                <i class="nav-icon fas fa-database"></i>
                <p>
                  Master Data
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.master">
                <li class="nav-item" v-if="authUserStore.can('manage.master.branches')">
                  <router-link
                    to="/admin/master-branches"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/master-branches') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Master Cabang</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.master.groups')">
                  <router-link
                    to="/admin/master-groups"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/master-groups') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Master Golongan</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.master.categories')">
                  <router-link
                    to="/admin/master-categories"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/master-categories') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Master Kategori</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.master.fields-components')">
                  <router-link
                    to="/admin/master-field-components"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/master-field-components') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Master Bidang Penilaian</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.master.participants')">
                  <router-link
                    to="/admin/master-participants"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/master-participants') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Master Peserta</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- ===================== -->
          <!-- EVENT DATA -->
          <!-- ===================== -->
          <template v-if="canEvent">
            <!-- <li class="nav-header">EVENT DATA</li> -->

            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.eventData }">
              <a href="#" class="nav-link" :class="{ active: openMenu.eventData }" @click.prevent="toggleMenu('eventData')">
                <i class="nav-icon fas fa-calendar-alt"></i>
                <p>
                  Event Data
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.eventData">
                <li class="nav-item" v-if="authUserStore.can('manage.event.events')">
                  <router-link
                    to="/admin/events"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/events') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.stages')">
                  <router-link
                    to="/admin/event-stage"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/event-stage') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Tahapan Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.branches')">
                  <router-link
                    to="/admin/event-branches"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/event-branches') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Cabang Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.groups')">
                  <router-link
                    to="/admin/event-groups"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/event-groups') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Golongan Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.categories')">
                  <router-link
                    to="/admin/event-categories"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/event-categories') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Kategori Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.user')">
                  <router-link
                    to="/admin/event-users"
                    class="nav-link"
                    :class="{ active: isPathActive('/admin/event-users') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>User Event</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- ===================== -->
          <!-- EVENT PARTICIPANT -->
          <!-- ===================== -->
          <template v-if="showPesertaSection">
            <!-- <li class="nav-header">EVENT PARTICIPANT</li> -->

            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.peserta }">
              <a href="#" class="nav-link" :class="{ active: openMenu.peserta }" @click.prevent="toggleMenu('peserta')">
                <i class="nav-icon fas fa-users"></i>
                <p>
                  Peserta
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.peserta">
                <li class="nav-item" v-if="authUserStore.can('manage.event.participant.bank-data')">
                  <router-link
                    :to="{ name: 'admin.event.participants.bank-data' }"
                    class="nav-link"
                    :class="{ active: $route.name === 'admin.event.participants.bank-data' }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Bank Data</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.participant.registration')">
                  <router-link
                    :to="{ name: 'admin.event.participants.registration', params: { status: 'process' } }"
                    class="nav-link"
                    :class="{ active: $route.name === 'admin.event.participants.registration' }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p v-if="authUserStore.user?.role?.slug === 'verifikator'">Verifikasi</p>
                    <p v-else>Pendaftaran</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.participant.reregistration')">
                  <router-link
                    :to="{ name: 'admin.event.participants.reregistration' }"
                    class="nav-link"
                    :class="{ active: $route.name === 'admin.event.participants.reregistration' }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Daftar Ulang</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.participant.final')">
                  <router-link
                    :to="{ name: 'admin.event.participants.final' }"
                    class="nav-link"
                    :class="{ active: $route.name === 'admin.event.participants.final' }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Final</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- ===================== -->
          <!-- EVENT COMPETITION -->
          <!-- ===================== -->
          <template v-if="showCompetitionSection">
            <!-- <li class="nav-header">EVENT COMPETITION</li> -->

            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.competition }">
              <a href="#" class="nav-link" :class="{ active: openMenu.competition }" @click.prevent="toggleMenu('competition')">
                <i class="nav-icon fas fa-trophy"></i>
                <p>
                  Kompetisi
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.competition">
                <li class="nav-item" v-if="authUserStore.can('manage.event-competitions.judges.user')">
                  <router-link
                    :to="{ name: 'admin.event-competition.judges.user' }"
                    class="nav-link"
                    :class="{ active: $route.name === 'admin.event-competition.judges.user' }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>User Hakim</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event-competitions.judges-panel')">
                  <router-link
                    :to="{ name: 'admin.event-competition.judges-panel' }"
                    class="nav-link"
                    :class="{ active: $route.name === 'admin.event-competition.judges-panel' }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Majelis Hakim</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event-competitions.fields-components')">
                  <router-link
                    to="/admin/event-field-components"
                    class="nav-link"
                    :class="{ active: $route.path.startsWith('/admin/event-field-components') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Komponen Nilai</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event-competitions.scoring.index')">
                  <router-link
                    :to="{ name: 'admin.event-competitions.scoring.index' }"
                    class="nav-link"
                    :class="{ active: $route.name === 'admin.event-competitions.scoring.index' }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Kompetisi Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event-competitions.scoring.input-default')">
                  <router-link
                    :to="scoringLink"
                    class="nav-link"
                    :class="{
                      active:
                        $route.name === 'admin.event-competitions.scoring.input-default' ||
                        $route.name === 'admin.event-competitions.scoring.input-specific'
                    }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Input Nilai</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- ===================== -->
          <!-- LOGOUT -->
          <!-- ===================== -->
          <!-- <li class="nav-header">LOGOUT</li> -->
          <li class="nav-item">
            <a href="#" class="nav-link" @click.prevent="logout">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Keluar</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>
</template>
