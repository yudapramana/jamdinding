<script setup>
import { useRouter, useRoute } from 'vue-router';
import { computed, onMounted } from 'vue';
import { useAuthUserStore } from './stores/AuthUserStore';
import { useSettingStore } from './stores/SettingStore';

import AdminLayout from './LayoutAdmin.vue';
import GuestLayout from './LayoutGuest.vue';
import MaqraLayout from './LayoutMaqra.vue'; // Import layout baru
import BaseLoading from './components/BaseLoading.vue';

const route = useRoute();
const router = useRouter();
const authUserStore = useAuthUserStore();
const settingStore = useSettingStore();

settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);

const currentThemeMode = computed(() =>
  settingStore.theme === 'dark' ? 'dark-mode' : ''
);

const isAppLoading = computed(() =>
  authUserStore.isLoading || authUserStore.isLoggingOut
);

// MENGAMBIL LAYOUT DARI META ROUTER
const currentLayoutComponent = computed(() => {
  // Jika sedang login, cek meta.layout dari vue-router
  if (authUserStore.isAuthenticated) {
    const layout = route.meta.layout;
    
    if (layout === 'maqra') return MaqraLayout;
    if (layout === 'guest') return GuestLayout;
    
    // Default fallback untuk user yang sudah login adalah AdminLayout
    return AdminLayout; 
  }
  
  // Jika belum login, return null (nanti di-handle di template v-else)
  return null;
});

onMounted(async () => {
  if (authUserStore.isAuthenticated) {
    await authUserStore.getAuthUser();
  }
});
</script>

<template>
  <div :class="currentThemeMode">
    <!-- Loading spinner -->
    <BaseLoading v-if="isAppLoading" />

    <!-- JIKA SUDAH LOGIN: Render komponen secara dinamis -->
    <div v-else-if="authUserStore.isAuthenticated" class="wrapper" id="app">
      <v-app app>
        <component :is="currentLayoutComponent" />
      </v-app>
    </div>

    <!-- JIKA BELUM LOGIN: Tampilkan router-view langsung (seperti Landing / Login) -->
    <div v-else>
      <router-view></router-view>
    </div>
  </div>
</template>