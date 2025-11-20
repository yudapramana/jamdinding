import axios from 'axios';
import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import { useLoadingStore } from "./LoadingStore";
import { useStorage } from '@vueuse/core';
import { useAuthUserStore } from "./AuthUserStore.js";

export const useMasterDataStore = defineStore('MasterDataStore', () => {
    const orgId = useStorage('MasterDataStore:orgId', ref(''));
    const userId = useStorage('MasterDataStore:userId', ref(''));

    const orgList = useStorage('MasterDataStore:orgList', ref([]));
    const userList = useStorage('MasterDataStore:userList', ref({}));
    const doctypeList = useStorage('MasterDataStore:doctypeList', ref([]));
    const workunitList = useStorage('MasterDataStore:workunitList', ref([]));
    const workUnitMonitorList = useStorage('MasterDataStore:workUnitMonitorList', ref([]));
    const selfWorkUnitMonitorList = useStorage('MasterDataStore:selfWorkUnitMonitorList', ref([]));
    const docParameters = useStorage('MasterDataStore:docParameters', ref([1, 2, 3, 4, 5, 2011, 2012, 2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025, 'D2', 'D3', 'S1', 'S2', 'S3', 'IIa', 'IIb', 'IIc', 'IId', 'IIIa', 'IIIb', 'IIIc', 'IIId', 'IVa', 'IVb', 'IVc', 'IVd', 'Suami', 'Istri']));

    const loadingStore = useLoadingStore();
    const authUserStore = useAuthUserStore();

    const getWorkUnitMonitorList = async () => {

        console.log('getWorkUnitMonitorList start');
        if (workUnitMonitorList.value.length == 0) {
            console.log('getWorkUnitMonitorList runned');
            loadingStore.toggleLoading();
            await axios.get('/api/work-units/monitor')
                .then((response) => {
                    // workUnitMonitorList.value = response.data.data;
                    workUnitMonitorList.value = response;
                    loadingStore.toggleLoading();
                    console.log('workUnitMonitorList hasbeenfetched');
                }).catch((error) => {
                    loadingStore.toggleLoading();
                    authUserStore.handleAuthError(error);
                });
        }
    };

    const getSelfWorkUnitMonitorList = async () => {

        console.log('getSelfWorkUnitMonitorList start');
        if (selfWorkUnitMonitorList.value.length == 0) {
            console.log('getSelfWorkUnitMonitorList runned');
            loadingStore.toggleLoading();
            await axios.get('/api/work-units/self-monitor')
                .then((response) => {
                    // selfWorkUnitMonitorList.value = response.data.data;
                    selfWorkUnitMonitorList.value = response;
                    loadingStore.toggleLoading();
                    console.log('selfWorkUnitMonitorList hasbeenfetched');
                }).catch((error) => {
                    loadingStore.toggleLoading();
                    authUserStore.handleAuthError(error);
                });
        }
    };

    const getWorkunitList = async () => {


        if (workunitList.value.length == 0) {
            loadingStore.toggleLoading();
            await axios.get('/api/master', {
                params: {
                    type: 'workunits',
                }
            })
                .then((response) => {
                    workunitList.value = response.data.data;
                    loadingStore.toggleLoading();
                    console.log('workunitList hasbeenfetched');
                }).catch((error) => {
                    loadingStore.toggleLoading();
                    authUserStore.handleAuthError(error);
                });
        }
    };

    const getDoctypeList = async (userId = null) => {
        console.log('doctypeList.value.length:', doctypeList.value.length);
        // console.log('doctypeList.value:', doctypeList.value);
    
        if (doctypeList.value.length === 0 || authUserStore.user.role == 'SUPERADMIN' || authUserStore.user.role == 'ADMIN') {
            loadingStore.toggleLoading();
    
            try {
                const response = await axios.get('/api/master', {
                    params: {
                        type: 'doctypes',
                        ...(userId && { user_id: userId }) // tambahkan user_id jika ada
                    }
                });
    
                doctypeList.value = response.data.data;
                // console.log('doctypeList has been fetched:', doctypeList.value);
            } catch (error) {
                console.error('doctypeList fetch error:', error);
                authUserStore.handleAuthError(error);
            } finally {
                loadingStore.toggleLoading();
            }
        }
    };

    const getUserList = async (org) => {
        // console.log('orgId');
        // console.log(org);

        loadingStore.toggleLoading();
        await axios.get('/api/master', {
            params: {
                type: 'users',
                id: org
            }
        })
            .then((response) => {
                userList.value = response.data.data;
                loadingStore.toggleLoading();
            }).catch((error) => {
                loadingStore.toggleLoading();
                authUserStore.handleAuthError(error);
            });
    };


    const employeesCacheByUnit = reactive({});          // { [unitId]: Employee[] }

    // ===== Actions =====
    function setEmployees(unitId, list) {
        employeesCacheByUnit[String(unitId)] = Array.isArray(list) ? list : [];
        // persist ke sessionStorage
        try {
        sessionStorage.setItem('empCache', JSON.stringify(employeesCacheByUnit));
        } catch (_) {}
    }

    function hydrateEmployeesCache() {
        try {
        const raw = sessionStorage.getItem('empCache');
        if (!raw) return;
        const parsed = JSON.parse(raw) || {};
        // reactive-object friendly: assign per-key
        for (const k of Object.keys(parsed)) {
            employeesCacheByUnit[k] = parsed[k];
        }
        } catch (_) {}
    }

    function clearEmployeesCache() {
        for (const k of Object.keys(employeesCacheByUnit)) {
        delete employeesCacheByUnit[k];
        }
        try {
        sessionStorage.removeItem('empCache');
        } catch (_) {}
    }

    return {
        orgId,
        userId,
        orgList,
        userList,
        doctypeList,
        workunitList,
        docParameters,
        workUnitMonitorList,
        selfWorkUnitMonitorList,
        employeesCacheByUnit,
        getUserList,
        getDoctypeList,
        getWorkunitList,
        getWorkUnitMonitorList,
        // actions
        setEmployees,
        hydrateEmployeesCache,
        getWorkUnitMonitorList,
        clearEmployeesCache,
        getSelfWorkUnitMonitorList
    };
});