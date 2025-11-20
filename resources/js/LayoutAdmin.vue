<script setup>

import { useRouter, useRoute } from 'vue-router';
import { ref, onMounted, computed, watch } from 'vue';
import AppNavbar from './components/AppNavbar.vue';
import SidebarLeft from './components/SidebarLeft.vue';
import SidebarRight from './components/SidebarRight.vue';
import AppFooter from './components/AppFooter.vue';
import { useAuthUserStore } from './stores/AuthUserStore.js';
import { useSettingStore } from './stores/SettingStore.js';
import { useScreenDisplayStore } from './stores/ScreenDisplayStore.js';

const route = useRoute();
const router = useRouter();
const screenDisplayStore = useScreenDisplayStore();
const deferredPrompt = ref(null);
const authUserStore = useAuthUserStore();
const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);

const logout = () => {
    authUserStore.logout();
};

const currentThemeMode = computed(() => {
    return settingStore.theme === 'dark' ? 'dark-mode' : '';
});

const dismiss = async () => {
    deferredPrompt.value = null;
}
const install = async () => {
    deferredPrompt.prompt();
}


const backbuttonHandler = () => {
    // navigator.app.exitApp()
};

onMounted(() => {
    settingStore.getSetting();
    screenDisplayStore.getScreenSize();
    window.addEventListener("beforeinstallprompt", e => {
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt.value = e;
    });
    window.addEventListener("appinstalled", () => {
        deferredPrompt.value = null;
    });
    window.addEventListener('resize', screenDisplayStore.toggleIsMobile);

    window.onpopstate = event => {
        console.log('back button');
        // navigator.app.exitApp()
        // if (route.path == "/login") {
        //     router.push("/admin/dashboard"); // redirect to home, for example
        // }
    };

    document.addEventListener('backbutton', backbuttonHandler, false);

});
</script>



<template>

    <body class="hold-transition sidebar-mini">
        <AppNavbar v-if="authUserStore.isAuthenticated" />
        <SidebarLeft v-if="authUserStore.isAuthenticated" />
        <div class="content-wrapper">
            <router-view></router-view>
        </div>
        <SidebarRight v-if="authUserStore.isAuthenticated" />
        <AppFooter v-if="authUserStore.isAuthenticated" />

        <!-- <v-bottom-navigation grow v-if="screenDisplayStore.isMobile">
            <v-btn value="dashboard" to="/admin/dashboard">
                <v-icon>mdi-home</v-icon>

                <span>Beranda</span>
            </v-btn>

            <v-btn value="reports" to="/admin/reports">
                <v-icon>mdi-book</v-icon>

                <span>Laporan</span>
            </v-btn>

            <v-btn value="monitor" to="/admin/org-reports"
                v-if="authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'ADMIN' || authUserStore.user.role == 'REVIEWER'">
                <v-icon>mdi-monitor</v-icon>

                <span>Monitor</span>
            </v-btn>

            <v-btn value="profile" to="/admin/profile">
                <v-icon>mdi-account</v-icon>

                <span>Profil</span>
            </v-btn>

            
        </v-bottom-navigation> -->

        <VLayoutItem model-value position="bottom" class="text-end" size="88"
            v-if="screenDisplayStore.isMobile && authUserStore.isAuthenticated && route.name == 'admin.reports'">
            <div class="ma-4">
                <VBtn to="/admin/reports/create" icon="mdi-plus" size="large" color="primary" elevation="8" />
            </div>
        </VLayoutItem>
    </body>
</template>