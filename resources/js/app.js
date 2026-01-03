// import './bootstrap';
// import 'admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js';
// import 'admin-lte/plugins/bs-custom-file-input/bs-custom-file-input.min.js';
// import 'admin-lte/dist/js/adminlte.min.js';
// import 'admin-lte/plugins/select2/css/select2.min.css';
// import 'admin-lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css';
// import 'admin-lte/plugins/select2/js/select2.full.min.js';

// 1) jQuery harus paling awal dan di-global-kan
import $ from 'jquery';
window.$ = window.jQuery = $;

// 2) (opsional) util axios/lodash, TAPI pastikan bootstrap 5 tidak diimport di sini
import './bootstrap';

// 3) CSS Select2 + Theme Bootstrap4
import 'admin-lte/plugins/select2/css/select2.min.css';
import 'admin-lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css';

// 4) AdminLTE dependencies (Bootstrap 4 bundle) & AdminLTE
import 'admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

// 5) Plugin lain
import 'admin-lte/plugins/bs-custom-file-input/bs-custom-file-input.min.js';

// 6) Select2 JS (setelah jQuery & sebelum kode yang memakainya)
import 'admin-lte/plugins/select2/js/select2.full.min.js';

// 7) (opsional) cek cepat di console dev
if (process.env.NODE_ENV !== 'production') {
  console.log('jQuery:', typeof $.fn === 'object' ? $.fn.jquery : 'missing');
  console.log('Select2:', typeof $.fn.select2);
}


import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import Routes from './routes.js';
import App from './App.vue';
import { useAuthUserStore } from './stores/AuthUserStore.js';
import Select2 from 'vue3-select2-component';
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { useSettingStore } from './stores/SettingStore.js';

const vuetify = createVuetify({
    components,
    directives
})
const pinia = createPinia();
const app = createApp(App);
const router = createRouter({
    routes: Routes,
    history: createWebHistory(),
});

app.use(pinia);
app.use(router);
app.use(vuetify);
app.component('Select2', Select2);

const authUserStore = useAuthUserStore(pinia);
const settingStore = useSettingStore(pinia);

router.beforeEach(async (to, from) => {
    console.log('App.js Duluan bagian router.beforeEach');
    console.log('authUserStore.isAuthenticated');
    console.log(authUserStore.isAuthenticated);

    if (authUserStore.isAuthenticated) {
        await settingStore.getSetting();
    }

    // Layout untuk route user
    if (to.name?.startsWith('admin.')) {
        authUserStore.activeLayout = 'admin';
        document.body.classList.add('sidebar-mini');
        document.body.classList.remove('layout-top-nav');
    } else {
        authUserStore.activeLayout = 'user';
        document.body.classList.add('layout-top-nav');
        document.body.classList.remove('sidebar-mini');
    }

    return true;
});


app.mount('#app');
