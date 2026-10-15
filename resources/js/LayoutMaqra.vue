<script setup>
import { useRouter, useRoute } from 'vue-router';
import { ref, onMounted, computed } from 'vue';
import AppNavbar from './components/AppNavbar.vue';
import AppFooter from './components/AppFooter.vue';
import { useAuthUserStore } from './stores/AuthUserStore.js';
import { useSettingStore } from './stores/SettingStore.js';
import { useScreenDisplayStore } from './stores/ScreenDisplayStore.js';

const route = useRoute();
const router = useRouter();
const screenDisplayStore = useScreenDisplayStore();
const authUserStore = useAuthUserStore();
const settingStore = useSettingStore();

onMounted(() => {
    settingStore.getSetting();
    screenDisplayStore.getScreenSize();
    window.addEventListener('resize', screenDisplayStore.toggleIsMobile);
});
</script>

<template>
    <!-- Tambahkan layout-top-nav untuk menghilangkan sidebar dan memusatkan navbar -->
    <body class="hold-transition layout-top-nav">
        <div class="wrapper">
            <AppNavbar v-if="authUserStore.isAuthenticated" />
            
            <div class="content-wrapper">
                <router-view></router-view>
            </div>
            
            <AppFooter v-if="authUserStore.isAuthenticated" />
        </div>
    </body>
</template>