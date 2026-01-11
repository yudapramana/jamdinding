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

// ======================
// PERMISSION (SEEDER)
// ======================

// Menu level
const canCore = computed(() => authUserStore.can('manage.core'))
const canMaster = computed(() => authUserStore.can('manage.master'))
const canEvent = computed(() => authUserStore.can('manage.event'))
const canParticipantMenu = computed(() => authUserStore.can('manage.event.participant'))
const canJudgesMenu = computed(() => authUserStore.can('manage.event.judges'))
const canScoringMenu = computed(() => authUserStore.can('manage.event.scoring'))
const canScoresMenu = computed(() => authUserStore.can('manage.event.scores'))
const canRankingMenu = computed(() => authUserStore.can('manage.event.ranking'))
const canContingentMenu = computed(() => authUserStore.can('manage.event.contingent'))
const canCoCardMenu = computed(() => authUserStore.can('manage.event.co-card'))

// Submenu visibility (avoid empty sections)
const showCoreSection = computed(() =>
  authUserStore.can('manage.core.branches-groups-categories') ||
  authUserStore.can('manage.core.fields') ||
  authUserStore.can('manage.core.permissions') ||
  authUserStore.can('manage.core.medal-rules')
)

const showMasterSection = computed(() =>
  authUserStore.can('manage.master.branches') ||
  authUserStore.can('manage.master.groups') ||
  authUserStore.can('manage.master.categories') ||
  authUserStore.can('manage.master.field-components') ||
  authUserStore.can('manage.master.judges') 
)

const showEventSection = computed(() =>
  authUserStore.can('manage.event.index') ||
  authUserStore.can('manage.event.stages') ||
  authUserStore.can('manage.event.locations') ||
  authUserStore.can('manage.event.branches') ||
  authUserStore.can('manage.event.groups') ||
  authUserStore.can('manage.event.categories') ||
  authUserStore.can('manage.event.user') ||
  authUserStore.can('manage.event.medal-rules') ||
  authUserStore.can('manage.event.judge-panels')
)

const showParticipantSection = computed(() =>
  authUserStore.can('manage.event.participant.bank-data') ||
  authUserStore.can('manage.event.participant.registration') ||
  authUserStore.can('manage.event.participant.reregistration') ||
  authUserStore.can('manage.event.participant.final')
)

const showJudgesSection = computed(() =>
  authUserStore.can('manage.event.judges.users') ||
  authUserStore.can('manage.event.judges.panels')
)

const showScoringSection = computed(() =>
  authUserStore.can('manage.event.scoring.field-components') ||
  authUserStore.can('manage.event.scoring.index') ||
  authUserStore.can('manage.event.scoring.input-default') ||
  authUserStore.can('manage.event.scoring.input-specific')
)

const showScoresSection = computed(() =>
  authUserStore.can('manage.event.scores.select') ||
  authUserStore.can('manage.event.scores.index') ||
  authUserStore.can('manage.event.scores.detail.select') ||
  authUserStore.can('manage.event.scores.detail.index')
)

const showRankingSection = computed(() =>
  authUserStore.can('manage.event.ranking.select') ||
  authUserStore.can('manage.event.ranking.index')
)

const showContingentSection = computed(() =>
  authUserStore.can('manage.event.contingent.standings')
)

const showCoCardSection = computed(() =>
  authUserStore.can('manage.event.co-card')
)

// ======================
// LINKS (SCORING/SCORES/RANKING selectors)
// ======================
const lastCompetitionId = computed(() => {
  return localStorage.getItem('last_scoring_competition_id') || null
})

const scoringLink = computed(() => {
  // selector pakai default (tanpa id) atau specific (dengan id)
  if (!lastCompetitionId.value) return { name: 'admin.event.scoring.input-default' }
  return { name: 'admin.event.scoring.input-specific', params: { id: lastCompetitionId.value } }
})

const scoresSelectLink = computed(() => ({ name: 'admin.event.scores.select' }))
const scoresDetailSelectLink = computed(() => ({ name: 'admin.event.scores.detail.select' }))
const rankingSelectLink = computed(() => ({ name: 'admin.event.ranking.select' }))

// ======================
// ACTIVE/OPEN helpers
// ======================
const isNameActive = (name) => route.name === name

const isAnyNameActive = (names = []) => {
  const cur = route.name
  return !!cur && names.includes(cur)
}

const openMenu = ref({
  core: false,
  master: false,
  event: false,
  participant: false,
  judges: false,
  judgesEvent: false,
  scoring: false,
  scores: false,
  ranking: false,
  contingent: false,
})

const toggleMenu = (key) => {
  openMenu.value[key] = !openMenu.value[key]
}

// auto-open sesuai route aktif
const syncOpenByRoute = () => {

  openMenu.value.judgesEvent = isAnyNameActive([
    'admin.event.judge.index',
    'admin.event.judge.panels',
  ])



  openMenu.value.core = isAnyNameActive([
    'admin.core.branches-groups-categories',
    'admin.core.fields',
    'admin.core.permissions',
    'admin.core.medal-rules',
  ])

  openMenu.value.master = isAnyNameActive([
    'admin.master.branches',
    'admin.master.groups',
    'admin.master.categories',
    'admin.master.field-components',
    'admin.master.judges',
  ])

  openMenu.value.event = isAnyNameActive([
    'admin.events.index',
    'admin.event.stages',
    'admin.event.locations',
    'admin.event.branches',
    'admin.event.groups',
    'admin.event.categories',
    'admin.event.users',
    'admin.event.medal-rules',
    'admin.event.judge-panels',
  ])

  openMenu.value.participant = isAnyNameActive([
    'admin.event.participants.bank-data',
    'admin.event.participants.registration',
    'admin.event.participants.reregistration',
    'admin.event.participants.final',
  ])

  openMenu.value.judges = isAnyNameActive([
    'admin.event.judges.users',
    'admin.event.judges.panel',
  ])

  openMenu.value.scoring = isAnyNameActive([
    'admin.event.scoring.field-components',
    'admin.event.scoring.index',
    'admin.event.scoring.input-default',
    'admin.event.scoring.input-specific',
  ])

  openMenu.value.scores = isAnyNameActive([
    'admin.event.scores.select',
    'admin.event.scores.index',
    'admin.event.scores.detail.select',
    'admin.event.scores.detail.index',
  ])

  openMenu.value.ranking = isAnyNameActive([
    'admin.event.ranking.select',
    'admin.event.ranking.index',
  ])

  openMenu.value.contingent = isAnyNameActive([
    'admin.event.contingent.standing',
  ])
}

watch(
  () => route.name,
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
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- BERANDA -->
          <li class="nav-item">
            <router-link :to="{ name: 'admin.dashboard' }" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Beranda</p>
            </router-link>
          </li>

          <!-- CORE -->
          <template v-if="canCore && showCoreSection">
            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.core }">
              <a href="#" class="nav-link" :class="{ active: openMenu.core }" @click.prevent="toggleMenu('core')">
                <i class="nav-icon fas fa-cogs"></i>
                <p>Core <i class="right fas fa-angle-left"></i></p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.core">
                <li class="nav-item" v-if="authUserStore.can('manage.core.branches-groups-categories')">
                  <router-link
                    :to="{ name: 'admin.core.branches-groups-categories' }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.core.branches-groups-categories') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Cabang / Golongan / Kategori (Core)</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.core.fields')">
                  <router-link
                    :to="{ name: 'admin.core.fields' }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.core.fields') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Bidang Penilaian (Core)</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.core.permissions')">
                  <router-link
                    :to="{ name: 'admin.core.permissions' }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.core.permissions') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Hak Akses Role</p>
                  </router-link>
                </li>

                <!-- ATURAN MEDALI -->
                <li class="nav-item" v-if="authUserStore.can('manage.core.medal-rules')">
                  <router-link
                    :to="{ name: 'admin.core.medal-rules' }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.core.medal-rules') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Aturan Medali</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- MASTER -->
          <template v-if="canMaster && showMasterSection">
            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.master }">
              <a href="#" class="nav-link" :class="{ active: openMenu.master }" @click.prevent="toggleMenu('master')">
                <i class="nav-icon fas fa-database"></i>
                <p>Master <i class="right fas fa-angle-left"></i></p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.master">
                <li class="nav-item" v-if="authUserStore.can('manage.master.branches')">
                  <router-link :to="{ name: 'admin.master.branches' }" class="nav-link" :class="{ active: isNameActive('admin.master.branches') }">
                    <i class="far fa-circle nav-icon"></i><p>Master Cabang</p>
                  </router-link>
                </li>
                <li class="nav-item" v-if="authUserStore.can('manage.master.groups')">
                  <router-link :to="{ name: 'admin.master.groups' }" class="nav-link" :class="{ active: isNameActive('admin.master.groups') }">
                    <i class="far fa-circle nav-icon"></i><p>Master Golongan</p>
                  </router-link>
                </li>
                <li class="nav-item" v-if="authUserStore.can('manage.master.categories')">
                  <router-link :to="{ name: 'admin.master.categories' }" class="nav-link" :class="{ active: isNameActive('admin.master.categories') }">
                    <i class="far fa-circle nav-icon"></i><p>Master Kategori</p>
                  </router-link>
                </li>
                <li class="nav-item" v-if="authUserStore.can('manage.master.field-components')">
                  <router-link :to="{ name: 'admin.master.field-components' }" class="nav-link" :class="{ active: isNameActive('admin.master.field-components') }">
                    <i class="far fa-circle nav-icon"></i><p>Master Bidang Penilaian</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- EVENT -->
          <template v-if="canEvent && showEventSection">
            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.event }">
              <a href="#" class="nav-link" :class="{ active: openMenu.event }" @click.prevent="toggleMenu('event')">
                <i class="nav-icon fas fa-calendar-alt"></i>
                <p>Event <i class="right fas fa-angle-left"></i></p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.event">
                <li class="nav-item" v-if="authUserStore.can('manage.event.index')">
                  <router-link :to="{ name: 'admin.events.index' }" class="nav-link" :class="{ active: isNameActive('admin.events.index') }">
                    <i class="far fa-circle nav-icon"></i><p>Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.stages')">
                  <router-link :to="{ name: 'admin.event.stages' }" class="nav-link" :class="{ active: isNameActive('admin.event.stages') }">
                    <i class="far fa-circle nav-icon"></i><p>Tahapan Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.locations')">
                  <router-link
                    :to="{ name: 'admin.event.locations' }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.event.locations') }"
                  >
                    <i class="fas fa-map-marker-alt nav-icon"></i>
                    <p>Lokasi Event</p>
                  </router-link>
                </li>


                <li class="nav-item" v-if="authUserStore.can('manage.event.branches')">
                  <router-link :to="{ name: 'admin.event.branches' }" class="nav-link" :class="{ active: isNameActive('admin.event.branches') }">
                    <i class="far fa-circle nav-icon"></i><p>Cabang Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.groups')">
                  <router-link :to="{ name: 'admin.event.groups' }" class="nav-link" :class="{ active: isNameActive('admin.event.groups') }">
                    <i class="far fa-circle nav-icon"></i><p>Golongan Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.categories')">
                  <router-link :to="{ name: 'admin.event.categories' }" class="nav-link" :class="{ active: isNameActive('admin.event.categories') }">
                    <i class="far fa-circle nav-icon"></i><p>Kategori Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.user')">
                  <router-link :to="{ name: 'admin.event.users' }" class="nav-link" :class="{ active: isNameActive('admin.event.users') }">
                    <i class="far fa-circle nav-icon"></i><p>User Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.medal-rules')">
                  <router-link :to="{ name: 'admin.event.medal-rules' }" class="nav-link" :class="{ active: isNameActive('admin.event.medal-rules') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Aturan Medali Event</p>
                  </router-link>
                </li>
                <!-- MAJELIS HAKIM EVENT -->
                <!-- <li class="nav-item" v-if="authUserStore.can('manage.event.judge-panels')">
                  <router-link
                    :to="{ name: 'admin.event.judge-panels' }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.event.judge-panels') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Majelis Hakim Event</p>
                  </router-link>
                </li> -->
              </ul>
            </li>

            <!-- ================= DEWAN HAKIM ================= -->
            <li
              class="nav-item has-treeview"
              v-if="authUserStore.can('manage.event.judge')"
              :class="{ 'menu-open': openMenu.judgesEvent }"
            >
              <a
                href="#"
                class="nav-link"
                :class="{ active: openMenu.judgesEvent }"
                @click.prevent="toggleMenu('judgesEvent')"
              >
                <i class="nav-icon fas fa-gavel"></i>
                <p>
                  Dewan Hakim
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.judgesEvent">

                <!-- DAFTAR HAKIM EVENT -->
                <li class="nav-item">
                  <router-link
                    :to="{ name: 'admin.event.judge.index' }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.event.judge.index') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Hakim Event</p>
                  </router-link>
                </li>

                <!-- MAJELIS HAKIM EVENT -->
                <li class="nav-item">
                  <router-link
                    :to="{ name: 'admin.event.judge.panels' }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.event.judge.panels') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Majelis Hakim</p>
                  </router-link>
                </li>

              </ul>
            </li>


          </template>

          <!-- PARTICIPANT -->
          <template v-if="canParticipantMenu && showParticipantSection">
            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.participant }">
              <a href="#" class="nav-link" :class="{ active: openMenu.participant }" @click.prevent="toggleMenu('participant')">
                <i class="nav-icon fas fa-users"></i>
                <p>Peserta <i class="right fas fa-angle-left"></i></p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.participant">
                <li class="nav-item" v-if="authUserStore.can('manage.event.participant.bank-data')">
                  <router-link :to="{ name: 'admin.event.participants.bank-data' }" class="nav-link" :class="{ active: isNameActive('admin.event.participants.bank-data') }">
                    <i class="far fa-circle nav-icon"></i><p>Bank Data</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.participant.registration')">
                  <router-link
                    :to="{ name: 'admin.event.participants.registration', params: { status: 'process' } }"
                    class="nav-link"
                    :class="{ active: isNameActive('admin.event.participants.registration') }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p v-if="authUserStore.user?.role?.slug === 'verifikator'">Verifikasi</p>
                    <p v-else>Pendaftaran</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.participant.reregistration')">
                  <router-link :to="{ name: 'admin.event.participants.reregistration' }" class="nav-link" :class="{ active: isNameActive('admin.event.participants.reregistration') }">
                    <i class="far fa-circle nav-icon"></i><p>Daftar Ulang</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.participant.final')">
                  <router-link :to="{ name: 'admin.event.participants.final' }" class="nav-link" :class="{ active: isNameActive('admin.event.participants.final') }">
                    <i class="far fa-circle nav-icon"></i><p>Final</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- JUDGES -->
          <!-- <template v-if="canJudgesMenu && showJudgesSection">
            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.judges }">
              <a href="#" class="nav-link" :class="{ active: openMenu.judges }" @click.prevent="toggleMenu('judges')">
                <i class="nav-icon fas fa-gavel"></i>
                <p>Hakim <i class="right fas fa-angle-left"></i></p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.judges">
                <li class="nav-item" v-if="authUserStore.can('manage.event.judges.users')">
                  <router-link :to="{ name: 'admin.event.judges.users' }" class="nav-link" :class="{ active: isNameActive('admin.event.judges.users') }">
                    <i class="far fa-circle nav-icon"></i><p>User Hakim</p>
                  </router-link>
                </li>
                <li class="nav-item" v-if="authUserStore.can('manage.event.judges.panels')">
                  <router-link :to="{ name: 'admin.event.judges.panel' }" class="nav-link" :class="{ active: isNameActive('admin.event.judges.panel') }">
                    <i class="far fa-circle nav-icon"></i><p>Majelis Hakim</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template> -->

          <!-- CO-CARD -->
          <template v-if="canCoCardMenu && showCoCardSection">
            <li class="nav-item" v-if="authUserStore.can('manage.event.co-card')">
              <router-link
                :to="{ name: 'admin.event.co-card' }"
                class="nav-link"
                :class="{ active: isNameActive('admin.event.co-card') }"
              >
                <i class="nav-icon fas fa-award"></i>
                <p>Kokarde</p>
              </router-link>
            </li>
          </template>

          <!-- SCORING -->
          <template v-if="canScoringMenu && showScoringSection">
            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.scoring }">
              <a href="#" class="nav-link" :class="{ active: openMenu.scoring }" @click.prevent="toggleMenu('scoring')">
                <i class="nav-icon fas fa-trophy"></i>
                <p>Penilaian <i class="right fas fa-angle-left"></i></p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.scoring">
                <li class="nav-item" v-if="authUserStore.can('manage.event.scoring.field-components')">
                  <router-link :to="{ name: 'admin.event.scoring.field-components' }" class="nav-link" :class="{ active: isNameActive('admin.event.scoring.field-components') }">
                    <i class="far fa-circle nav-icon"></i><p>Komponen Nilai</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.scoring.index')">
                  <router-link :to="{ name: 'admin.event.scoring.index' }" class="nav-link" :class="{ active: isNameActive('admin.event.scoring.index') }">
                    <i class="far fa-circle nav-icon"></i><p>Kompetisi Event</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.scoring.input-default') || authUserStore.can('manage.event.scoring.input-specific')">
                  <router-link
                    :to="scoringLink"
                    class="nav-link"
                    :class="{
                      active:
                        isNameActive('admin.event.scoring.input-default') ||
                        isNameActive('admin.event.scoring.input-specific')
                    }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Input Nilai</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template>

          <!-- SCORES -->
          <!-- <template v-if="canScoresMenu && showScoresSection">
            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.scores }">
              <a href="#" class="nav-link" :class="{ active: openMenu.scores }" @click.prevent="toggleMenu('scores')">
                <i class="nav-icon fas fa-clipboard-list"></i>
                <p>Rekap Nilai <i class="right fas fa-angle-left"></i></p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.scores">
                <li class="nav-item" v-if="authUserStore.can('manage.event.scores.select') || authUserStore.can('manage.event.scores.index')">
                  <router-link
                    :to="scoresSelectLink"
                    class="nav-link"
                    :class="{
                      active:
                        isNameActive('admin.event.scores.select') ||
                        isNameActive('admin.event.scores.index')
                    }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Rekap Nilai</p>
                  </router-link>
                </li>

                <li class="nav-item" v-if="authUserStore.can('manage.event.scores.detail.select') || authUserStore.can('manage.event.scores.detail.index')">
                  <router-link
                    :to="scoresDetailSelectLink"
                    class="nav-link"
                    :class="{
                      active:
                        isNameActive('admin.event.scores.detail.select') ||
                        isNameActive('admin.event.scores.detail.index')
                    }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Rekap Nilai Detail</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template> -->

          <!-- RANKING -->
          <template v-if="canRankingMenu && showRankingSection">
            <li
              class="nav-item"
              v-if="
                authUserStore.can('manage.event.ranking.select') ||
                authUserStore.can('manage.event.ranking.index')
              "
            >
              <router-link
                :to="rankingSelectLink"
                class="nav-link"
                :class="{
                  active:
                    isNameActive('admin.event.ranking.select') ||
                    isNameActive('admin.event.ranking.index')
                }"
              >
                <i class="nav-icon fas fa-sort-amount-up"></i>
                <p>Ranking</p>
              </router-link>
            </li>
          </template>

          <!-- <template v-if="canRankingMenu && showRankingSection">
            <li class="nav-item has-treeview" :class="{ 'menu-open': openMenu.ranking }">
              <a href="#" class="nav-link" :class="{ active: openMenu.ranking }" @click.prevent="toggleMenu('ranking')">
                <i class="nav-icon fas fa-sort-amount-up"></i>
                <p>Ranking <i class="right fas fa-angle-left"></i></p>
              </a>

              <ul class="nav nav-treeview" v-show="openMenu.ranking">
                <li class="nav-item" v-if="authUserStore.can('manage.event.ranking.select') || authUserStore.can('manage.event.ranking.index')">
                  <router-link
                    :to="rankingSelectLink"
                    class="nav-link"
                    :class="{
                      active:
                        isNameActive('admin.event.ranking.select') ||
                        isNameActive('admin.event.ranking.index')
                    }"
                  >
                    <i class="far fa-circle nav-icon"></i>
                    <p>Ranking Babak</p>
                  </router-link>
                </li>
              </ul>
            </li>
          </template> -->

          <!-- CONTINGENTS -->
          <template v-if="canContingentMenu && showContingentSection">
            <li class="nav-item" v-if="authUserStore.can('manage.event.contingent.standings')">
              <router-link
                :to="{ name: 'admin.event.contingent.standings' }"
                class="nav-link"
                :class="{ active: isNameActive('admin.event.contingent.standings') }"
              >
                <i class="nav-icon fas fa-award"></i>
                <p>Perolehan Juara</p>
              </router-link>
            </li>
          </template>


          <li class="nav-header">KELOLA</li>

                    
                   
          <li class="nav-item">
              <router-link to="/admin/settings" active-class="active" class="nav-link">
                  <i class="nav-icon fas fa-cog"></i>
                  <p>
                      Pengaturan
                  </p>
              </router-link>
          </li>

          <!-- LOGOUT -->
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
