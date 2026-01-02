<script setup>
import { computed } from 'vue'
import { useSettingStore } from '../stores/SettingStore'
import { useAuthUserStore } from '../stores/AuthUserStore'
import { useMasterDataStore } from '../stores/MasterDataStore'

const settingStore = useSettingStore()
const authUserStore = useAuthUserStore()
const masterDataStore = useMasterDataStore()

// normalisasi maintenance
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance)

// ================================
// EVENT AKTIF (AuthUserStore)
// ================================
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

// ================================
// EVENT STAGES (MasterDataStore)
// ================================
const eventStages = computed(() => masterDataStore.eventStages || [])

// cari stage yang sedang berlangsung
const activeStage = computed(() => {
  const now = new Date()
  return (
    eventStages.value.find(
      s =>
        s.is_active &&
        new Date(s.start_date) <= now &&
        new Date(s.end_date) >= now
    ) || null
  )
})

// ================================
// SWITCH LAYOUT
// ================================
const handleSwitchLayout = () => {
  if (authUserStore.user?.can_multiple_role) {
    authUserStore.switchLayout()
  }
}



// ================================
// ENVIRONMENT LABEL
// ================================
const environmentLabel = computed(() =>
  settingStore.isDevelopment ? 'DEV' : 'PROD'
)

const environmentClass = computed(() =>
  settingStore.isDevelopment
    ? 'badge badge-warning'
    : 'badge badge-success'
)

</script>


<template>
  <nav
    class="main-header navbar navbar-expand"
    :class="settingStore.theme === 'dark' ? 'navbar-dark' : 'navbar-light'"
  >
    <!-- LEFT -->
    <ul class="navbar-nav">
      <li class="nav-item" id="toggleMenuIcon">
        <a
          class="nav-link"
          href="#"
          role="button"
          @click.prevent="settingStore.toggleMenuIcon"
        >
          <i class="fas fa-bars"></i>
        </a>
      </li>

      <li class="nav-item">
        <a
          class="nav-link"
          href="#"
          role="button"
          @click.prevent="settingStore.changeTheme"
        >
          <i
            class="far"
            :class="settingStore.theme === 'dark' ? 'fa-moon' : 'fa-sun'"
          ></i>
        </a>
      </li>
    </ul>

    <!-- RIGHT -->
    <ul class="navbar-nav ml-auto align-items-center">
      <li class="nav-item d-none d-md-block text-right">
        <!-- BARIS 1: ENV + EVENT -->
        <div class="d-flex align-items-center justify-content-end">
          <span
            :class="environmentClass"
            class="mr-2"
            style="font-size: 10px; letter-spacing: 0.5px;"
            title="Application Environment"
          >
            {{ environmentLabel }}
          </span>

          <strong class="text-sm">
            {{ eventData?.event_name || 'Event belum dipilih' }}
          </strong>

          <span
            v-if="eventData?.event_year"
            class="ml-1 text-muted text-xs"
          >
            ({{ eventData.event_year }})
          </span>
        </div>

        <!-- BARIS 2: STAGE -->
        <div class="text-xs text-muted">
          Tahap:
          <strong>
            {{ activeStage?.name || 'Tidak Aktif' }}
          </strong>
        </div>
      </li>
    </ul>

  </nav>
</template>

