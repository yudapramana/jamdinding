<script setup>
import { useRouter, useRoute } from 'vue-router';
import { computed, onMounted } from 'vue';
import { useAuthUserStore } from './stores/AuthUserStore';
import { useSettingStore } from './stores/SettingStore';
import AdminLayout from './LayoutAdmin.vue';
import GuestLayout from './LayoutGuest.vue';
import Landing from './pages/Landing.vue';
import BaseLoading from './components/BaseLoading.vue'; // pastikan file ini ada

const route = useRoute();
const router = useRouter();
const authUserStore = useAuthUserStore();
const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);

// Tema (dark / light)
const currentThemeMode = computed(() =>
  settingStore.theme === 'dark' ? 'dark-mode' : ''
);

// Cek layout
const showAdminLayout = computed(() =>
  authUserStore.activeLayout === 'admin' && authUserStore.isAdminRole
);

const showUserLayout = computed(() =>
  authUserStore.activeLayout === 'user'
);

// Cek apakah data masih loading
const isAppLoading = computed(() =>
  authUserStore.isLoading || authUserStore.isLoggingOut
);

// Load data awal user saat login
onMounted(async () => {
  if (authUserStore.isAuthenticated) {
    await authUserStore.getAuthUser();
    await authUserStore.getMyDocuments();
  }
});
</script>

<template>
  <div :class="currentThemeMode">
    <!-- Loading spinner saat login atau logout -->
    <BaseLoading v-if="isAppLoading" />

    <!-- Jika sudah login -->
    <div v-else-if="authUserStore.isAuthenticated" class="wrapper" id="app">
      <v-app app>
        <!-- <GuestLayout v-if="showUserLayout" /> -->
        <!-- <AdminLayout v-else-if="showAdminLayout" /> -->
        <AdminLayout />
        <!-- <div v-else class="p-4 text-center text-danger">
          <p>Layout tidak tersedia untuk role ini.</p>
        </div> -->
      </v-app>
    </div>

    <!-- Jika belum login -->
    <div v-else >
      <router-view></router-view>
    </div>
  </div>
</template>
