<script setup>
import { useRouter } from 'vue-router'
import { useRoute } from 'vue-router'
import { useAuthUserStore } from '../stores/AuthUserStore'
import { useSettingStore } from '../stores/SettingStore'
import { computed } from 'vue'

const router = useRouter()
const route = useRoute()

const settingStore = useSettingStore()
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance)

const authUserStore = useAuthUserStore()

const logout = () => {
  authUserStore.logout()
}

// active helper
const isRouteActive = (name) => route.name === name

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
  authUserStore.can('manage.event.participant.final') ||
  authUserStore.can('manage.event.judges.user') ||
  authUserStore.can('manage.event.judges-panel') ||
  authUserStore.can('manage.event.competitions') ||
  authUserStore.can('manage.event.competitions.scorings')
)

// ambil kompetisi terakhir yang dipilih di tree
const lastCompetitionId = computed(() => {
  return localStorage.getItem('last_scoring_competition_id') || null
})

// link scoring (kalau belum ada, arahkan ke list kompetisi)
const scoringLink = computed(() => {
  if (!lastCompetitionId.value) {
    return { name: 'admin.event-competitions' }
  }
  return {
    name: 'admin.event-competitions.scoring',
    params: { id: lastCompetitionId.value },
  }
})
</script>

<template>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="#" class="brand-link">
      <img
        src="/app_logo.png"
        alt="AdminLTE Logo"
        class="brand-image img-circle elevation-3"
        style="opacity: .8"
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
          <!-- CORE (manage.core.*) -->
          <!-- ===================== -->
          <template v-if="canCore">
            <li class="nav-header">CORE</li>

            <li class="nav-item" v-if="authUserStore.can('manage.core.branches')">
              <router-link
                to="/admin/core-branches-groups-categories"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/core-branches-groups-categories') }"
              >
                <i class="nav-icon fas fa-sitemap"></i>
                <p>Cabang (Core)</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.core.fields')">
              <router-link
                to="/admin/core-list-fields"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/core-list-fields') }"
              >
                <i class="nav-icon fas fa-list-alt"></i>
                <p>Bidang Penilaian (Core)</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.core.permissions')">
              <router-link
                to="/admin/core-permission-role"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.path.startsWith('/admin/core-permission-role') }"
              >
                <i class="nav-icon fas fa-user-shield"></i>
                <p>Hak Akses Role</p>
              </router-link>
            </li>
          </template>

          <!-- ===================== -->
          <!-- MASTER DATA (manage.master.*) -->
          <!-- ===================== -->
          <template v-if="canMaster && showMasterSection">
            <li class="nav-header">MASTER DATA</li>

            <li class="nav-item" v-if="authUserStore.can('manage.master.branches')">
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

            <li class="nav-item" v-if="authUserStore.can('manage.master.groups')">
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

            <li class="nav-item" v-if="authUserStore.can('manage.master.categories')">
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

            <li class="nav-item" v-if="authUserStore.can('manage.master.fields-components')">
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

            <li class="nav-item" v-if="authUserStore.can('manage.master.participants')">
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
          <!-- MANAGED DATA (manage.event.*) -->
          <!-- ===================== -->
          <template v-if="canEvent">
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
          <!-- PESERTA + HAKIM + KOMPETISI/SCORING -->
          <!-- ===================== -->
          <template v-if="showPesertaSection">
            <li class="nav-header">PESERTA</li>

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

            <li class="nav-item" v-if="authUserStore.can('manage.event.participant.registration')">
              <router-link
                :to="{ name: 'admin.event.participants.registration', params: { status: 'process' } }"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.name === 'admin.event.participants.registration' }"
              >
                <i class="nav-icon fas fa-user-plus"></i>
                <p v-if="authUserStore.user?.role?.slug === 'verifikator'">Verifikasi</p>
                <p v-else>Pendaftaran</p>
              </router-link>
            </li>

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

            <!-- ===== HAKIM ===== -->
            <li class="nav-header" v-if="authUserStore.can('manage.event.judges') || authUserStore.can('manage.event.judges.user') || authUserStore.can('manage.event.judges-panel')">
              HAKIM
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.judges.user')">
              <router-link
                :to="{ name: 'admin.event.judges.user' }"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.name === 'admin.event.judges.user' }"
              >
                <i class="nav-icon fas fa-users-cog"></i>
                <p>User Hakim</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.judges-panel')">
              <router-link
                :to="{ name: 'admin.event.judges-panel' }"
                class="nav-link"
                active-class="active"
                :class="{ active: $route.name === 'admin.event.judges-panel' }"
              >
                <i class="nav-icon fas fa-gavel"></i>
                <p>Majelis Hakim</p>
              </router-link>
            </li>

            <!-- ===== KOMPETISI ===== -->
            <li class="nav-header" v-if="authUserStore.can('manage.event.scoring') || authUserStore.can('manage.event.scoring.competition')">
              KOMPETISI
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.scoring.competitions')">
              <router-link
                :to="{ name: 'admin.event-competitions' }"
                class="nav-link"
                :class="{ active: isRouteActive('admin.event-competitions') }"
              >
                <i class="nav-icon fas fa-sitemap"></i>
                <p>Kompetisi Event</p>
              </router-link>
            </li>

            <li class="nav-item" v-if="authUserStore.can('manage.event.competitions.scoring.input-default')">
              <router-link
                :to="scoringLink"
                class="nav-link"
                :class="{ active: isRouteActive('admin.event-competitions.scoring.input-default') }"
              >
                <i class="nav-icon fas fa-star"></i>
                <p>Input Nilai</p>
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
