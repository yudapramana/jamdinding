import axios from 'axios';
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useStorage } from '@vueuse/core';
import { useRouter } from 'vue-router';

export const useAuthUserStore = defineStore('AuthUserStore', () => {
    const router = useRouter();

    const docsUpdateState = useStorage('AuthUserStore:docsUpdateState', ref(true));
    const docsProgressState = useStorage('AuthUserStore:docsProgressState', ref(false));
    const firstLoadState = useStorage('AuthUserStore:firstLoadState', ref(true));
    const isAuthenticated = useStorage('AuthUserStore:isAuthenticated', ref(false));
    const activeLayout = useStorage('AuthUserStore:activeLayout', ref('user'));
    const isLoading = useStorage('AuthUserStore:isLoading', ref(false));
    const isLoggingOut = useStorage('AuthUserStore:isLoggingOut', ref(false)); // 👈 optional, jika butuh pisah loading logout
    const eventData = useStorage('AuthUserStore:eventData', {});
    const selectedEventKey = useStorage('AuthUserStore:selectedEventKey', '');

    const user = useStorage('AuthUserStore:user', ref({
        name: '',
        email: '',
        role: {},
        avatar: '',
        nama_pemeriksa: '',
        nip_pemeriksa: '',
        print_layout: '',
        jabatan: '',
        org_name: '',
        org_id: '',
        username: '',
        nip: '',
        full_name: '',
        date_of_birth: '',
        gender: '',
        phone_number: '',
        job_title: '',
        id_work_unit: '',
        employment_status: '',
        tmt_pangkat: '',
        tmt_jabatan: '',
        employee: {},
        doctypes: [],
        must_change_password: true,
        can_multiple_role: null,
        roles: [],
        rolenames: [],
        permissions: [],
    }));

    // ==================================================
    // MANDAT KECAMATAN/WILAYAH (khusus role PENDAFTARAN)
    // ==================================================
    const mandateStatus = useStorage('AuthUserStore:mandateStatus', ref({
        allowed: true,       // default true → tidak nge-block role lain / sebelum dicek
        checked: false,      // sudah pernah fetch atau belum
        status: null,        // not_uploaded | uploaded | approved | rejected
        region_type: null,
        region_id: null,
        message: '',
    }));

    /**
     * Role yang WAJIB cek mandat sebelum bisa input bank data.
     * Kalau nanti ada role lain yang perlu dicek juga, tinggal tambah di sini.
     */
    const isMandateCheckRequired = computed(() => {
        const slug = user.value?.role?.slug || '';
        return slug === 'pendaftaran';
    });

    const fetchMandateStatus = async () => {
        // role selain 'pendaftaran' tidak perlu cek mandat sama sekali
        if (!isMandateCheckRequired.value) {
            mandateStatus.value = {
                allowed: true,
                checked: true,
                status: null,
                region_type: null,
                region_id: null,
                message: '',
            };
            return;
        }

        if (!eventData.value?.id) {
            mandateStatus.value = {
                allowed: false,
                checked: true,
                status: 'not_uploaded',
                region_type: null,
                region_id: null,
                message: 'Event belum dipilih.',
            };
            return;
        }

        try {
            const response = await axios.get(`/api/v1/events/${eventData.value.id}/mandate-status`);
            const status = response.data.status || 'not_uploaded';

            const fallbackMessages = {
                not_uploaded: 'Wilayah Anda belum upload mandat untuk event ini.',
                uploaded: 'Mandat wilayah Anda sudah diupload, menunggu persetujuan (approval).',
                approved: '',
                rejected: 'Mandat wilayah Anda ditolak. Silakan upload ulang mandat yang valid.',
            };

            mandateStatus.value = {
                allowed: status === 'approved',
                checked: true,
                status,
                region_type: response.data.region_type,
                region_id: response.data.region_id,
                message: response.data.message ?? fallbackMessages[status] ?? '',
            };
        } catch (error) {
            handleAuthError(error);
            // fail-safe: gagal cek → anggap belum boleh, biar aman
            mandateStatus.value = {
                allowed: false,
                checked: true,
                status: null,
                region_type: null,
                region_id: null,
                message: 'Gagal memeriksa status mandat.',
            };
        }
    };

    const resetMandateStatus = () => {
        mandateStatus.value = {
            allowed: true,
            checked: false,
            status: null,
            region_type: null,
            region_id: null,
            message: '',
        };
    };

    const preserveEventStorage = () => {
        const savedEventData = localStorage.getItem('AuthUserStore:eventData')
        const savedEventKey  = localStorage.getItem('AuthUserStore:selectedEventKey')

        localStorage.clear()
        sessionStorage.clear()

        // Balikkan lagi yang penting
        if (savedEventData !== null) {
            localStorage.setItem('AuthUserStore:eventData', savedEventData)
        }
        if (savedEventKey !== null) {
            localStorage.setItem('AuthUserStore:selectedEventKey', savedEventKey)
        }
    }

    const can = (permission) => {
        const u = user.value;

        // Jika user belum ter-load
        if (!u || !u.role) return false;

        // Superadmin: semua boleh
        if (u.role.slug === 'superadmin') return true;

        // Proteksi ketika permissions undefined / null / bukan array
        const perms = Array.isArray(u.permissions) ? u.permissions : [];

        return perms.includes(permission);
    };

    const myDocuments = useStorage('AuthUserStore:myDocuments', ref([]));
    const userDocuments = ref([]);
    const isAdminRole = useStorage('AuthUserStore:isAdminRole', ref(false));

    const switchLayout = () => {
        activeLayout.value = activeLayout.value === 'admin' ? 'user' : 'admin';
        router.push({ name: activeLayout.value === 'admin' ? 'admin.dashboard' : 'user.dashboard' });
    };


    const syncFiles = async () => {
        try {
            const response = await axios.get('/api/sync-files');
        } catch (error) {
            handleAuthError(error);
        }
    };

    const syncFilesIndividual = async (user_id = null) => {
        try {
            const response = await axios.get('/api/sync-files', {
                params: user_id ? { user_id } : {} // kirim kalau ada
            });
            return response.data;
        } catch (error) {
            handleAuthError(error);
        }
    };

    const getMyDocuments = async () => {
        try {
            console.log('getMyDocuments Running');
            console.log('getMyDocuments docsUpdate State: ' + docsUpdateState.value);
            // isLoading.value = true;
            if (firstLoadState.value || docsUpdateState.value) {
                const response = await axios.get('/api/my-documents');
                myDocuments.value = response.data.data;
                firstLoadState.value = false;
                docsUpdateState.value = false;
            }
        } catch (error) {
            handleAuthError(error);
        }
        // finally {
        //     isLoading.value = false;
        // }
    };

    const getDocumentsByUserId = async (userId) => {
        try {
            // isLoading.value = true;
            const response = await axios.get(`/api/user-documents/${userId}`);
            userDocuments.value = response.data.data;
        } catch (error) {
            handleAuthError(error);
        }
        
    };

    const getDocsUpdateState = async () => {
        try {
            console.log('getDocsUpdateState Running');

            // isLoading.value = true;
            const response = await axios.get('/api/docs-update-state');
            console.log(response.data);
            docsUpdateState.value = response.data.docs_update_state;
            docsProgressState.value = response.data.docs_progress_state;
            user.value.employee.progress_dokumen = response.data.progress_dokumen;
        } catch (error) {
            handleAuthError(error);
            docsUpdateState.value = false;
        }
        
    };

    const getAuthUser = async () => {
        try {
            console.log('getAuthUser Running');
            isLoading.value = true;
            const response = await axios.get('/api/profile');
            user.value = response.data;
            docsUpdateState.value = response.data.docs_update_state;

            const roles = response.data.role_names || [];
            isAdminRole.value = roles.includes('SUPERADMIN') ||
                roles.includes('ADMIN') ||
                roles.includes('REVIEWER');

            isAuthenticated.value = true;
            
        } catch (error) {
            handleAuthError(error);
        } finally {
            setTimeout(() => {
                isLoading.value = false;
            }, 2000);
        }
    };

    

    const logout = async () => {
        try {
            docsProgressState.value = true;
            docsUpdateState.value = true;
            isLoggingOut.value = true;
            await axios.post('/logout');

            // 💡 GUNAKAN PRESERVE EVENT STORAGE AGAR EVENT TIDAK HILANG SAAT LOGOUT
            preserveEventStorage();

            // Bersihkan cookie aplikasi
            document.cookie.split(";").forEach(cookie => {
                const eqPos = cookie.indexOf("=");
                const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
            });

            if ('caches' in window) {
                const cacheNames = await caches.keys();
                await Promise.all(cacheNames.map(name => caches.delete(name)));
            }

            isAuthenticated.value = false;
            isAdminRole.value = false;
            user.value = {};
            myDocuments.value = [];
            resetMandateStatus();
            
            await axios.get('/sanctum/csrf-cookie');
            
        } catch (error) {
            console.error("Logout gagal:", error);
        } finally {
            // 💡 Matikan loading di sini agar state tersimpan bersih sebelum reload
            isLoggingOut.value = false;
            
            // Arahkan kembali ke halaman landing
            window.location.href = '/';
        }
    };

    const handleAuthError = async (error) => {
        if (error.response && error.response.status === 401) {
            // window.location.href = '/login';

            docsProgressState.value = true;
            docsUpdateState.value = true;


            // Bersihkan data
            preserveEventStorage()

            // localStorage.clear()
            // sessionStorage.clear()
            document.cookie.split(";").forEach(cookie => {
                const eqPos = cookie.indexOf("=");
                const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
            });

            if ('caches' in window) {
                const cacheNames = await caches.keys();
                await Promise.all(cacheNames.map(name => caches.delete(name)));
            }

            isAuthenticated.value = false;
            isAdminRole.value = false;
            user.value = {};
            myDocuments.value = [];

            await axios.get('/sanctum/csrf-cookie');
            // router.push('/login');
            window.location.href = '/';


        } else {
            console.error('Terjadi kesalahan:', error);
        }
    };

    return {
        user,
        isAuthenticated,
        docsUpdateState,
        docsProgressState,
        firstLoadState,
        myDocuments,
        userDocuments,
        isAdminRole,
        activeLayout,
        isLoading,
        isLoggingOut,
        eventData,
        selectedEventKey,
        mandateStatus,
        isMandateCheckRequired,
        getAuthUser,
        getDocsUpdateState,
        getMyDocuments,
        syncFiles,
        getDocumentsByUserId,
        logout,
        switchLayout,
        handleAuthError,
        syncFilesIndividual,
        fetchMandateStatus,
        resetMandateStatus, 
        can
    };
});
