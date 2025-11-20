<template>
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="mb-2">Daftar Upload</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex">
                    <select v-model="selectedWorkUnit" class="form-control mr-2" @change="fetchUsers">
                        <option disabled value="">-- Pilih Unit Kerja --</option>
                        <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">
                            {{ unit.text }}
                        </option>
                    </select>
                    <!-- <Select2 v-model="selectedWorkUnit" :options="workUnits" placeholder="Pilih Unit Kerja"
                        class="form-control w-100" @change="fetchUsers" @select="fetchUsers" /> -->
                    <!-- <div class="form-group w-100">
                        <Select2 v-model="selectedWorkUnit" :options="workUnits" placeholder="Pilih Unit Kerja"
                            class="custom-select2" @change="fetchUsers" @select="fetchUsers" />
                    </div> -->
                </div>

                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>NIP</th>
                                <!-- <th>Unit Kerja</th> -->
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading">
                                <td colspan="7" class="text-center">Memuat data...</td>
                            </tr>
                            <tr v-else-if="users.length === 0 && selectedWorkUnit">
                                <td colspan="7" class="text-center">Tidak ada data pengguna ditemukan.</td>
                            </tr>
                            <tr v-for="(user, index) in users" :key="user.id">
                                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                                <td><strong>{{ user.employee?.full_name || user.name }}</strong></td>
                                <td>{{ user.employee?.job_title || '-' }}</td>
                                <td>{{ user.employee?.nip || '-' }}</td>
                                <!-- <td>{{ user.employee?.work_unit?.unit_name || '-' }}</td> -->
                                <td>{{ user.employee?.employment_status || '-' }}</td>
                                <td width="25%">
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-success" role="progressbar"
                                            :style="{ width: `${user.employee?.progress_dokumen || 0}%` }"
                                            :aria-valuenow="user.employee?.progress_dokumen || 0" aria-valuemin="0"
                                            aria-valuemax="100"></div>
                                    </div>
                                    <small>{{ user.employee?.progress_dokumen || 0 }}%</small>
                                </td>
                                <td>
                                    <router-link :to="`/admin/users/${user.id}/documents`"
                                        class="btn btn-sm btn-info mr-1">
                                        Dokumen
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix" v-if="selectedWorkUnit">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} pengguna
                        </div>
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                                <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page - 1)">«</a>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
                            </li>
                            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                                <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page + 1)">»</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useMasterDataStore } from '../../stores/MasterDataStore.js';
import Select2 from 'vue3-select2-component'
import { useAuthUserStore } from '../../stores/AuthUserStore.js';

const users = ref([]);
const workUnits = ref([]);
const selectedWorkUnit = ref('');
const isLoading = ref(false);
const masterDataStore = useMasterDataStore();
const authUserStore = useAuthUserStore();

const meta = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    last_page: 1,
})

const fetchWorkUnits = async () => {
    try {
        await masterDataStore.getWorkunitList();
        workUnits.value = masterDataStore.workunitList;
        //   const res = await axios.get('/api/work-units')
        //   workUnits.value = res.data
    } catch (error) {
        authUserStore.handleAuthError(error);
    }
}

const fetchUsers = async (page = 1) => {
    if (!selectedWorkUnit.value) return
    isLoading.value = true
    try {
        const res = await axios.get('/api/users', {
            params: {
                page,
                work_unit_id: selectedWorkUnit.value,
            },
        })
        users.value = res.data.data
        meta.value = {
            ...meta.value,
            ...res.data.meta,
            ...res.data,
        }
    } catch (error) {
        authUserStore.handleAuthError(error);
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchWorkUnits()
})
</script>

<style scoped>
/* Tambahkan ini di <style scoped> atau global */
.custom-select2 .select2-container {
  width: 100% !important;
  height: auto !important;
  display: block;
}

.custom-select2 .select2-selection--single {
  height: calc(2.25rem + 2px); /* Bootstrap default */
  line-height: 2.25rem;
  padding: 0.375rem 0.75rem;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  font-size: 1rem;
  background-color: #fff;
}

.custom-select2 .select2-selection__arrow {
  height: 100%;
  top: 0.5rem;
  right: 0.75rem;
}

.custom-select2 .select2-selection__rendered {
  line-height: 1.5;
}
</style>