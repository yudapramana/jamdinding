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
      <li class="nav-item text-right d-none d-md-block">
        <!-- EVENT AKTIF -->
        <p v-if="eventId" class="mb-0 text-sm text-muted">
          <!-- Event aktif: -->
          <strong>{{ eventData?.event_name }}</strong>
          <span v-if="eventData?.event_year">
            ({{ eventData.event_year }})
          </span>
          • Lokasi:
          <strong>{{ eventData?.event_location || '-' }}</strong>
        </p>

        <!-- STAGE AKTIF -->
        <p class="mb-0 text-xs text-muted" v-if="eventStages.length">
          Tahapan saat ini:
          <strong>
            {{ activeStage?.name || 'Tidak Aktif' }}
          </strong>
        </p>
      </li>
    </ul>
  </nav>
</template>

