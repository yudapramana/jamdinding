<template>
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-2">Daftar Pengguna</h1>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">+ Tambah Pengguna</button>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <input v-model="search" type="text" class="form-control"
                        placeholder="Cari nama, email, NIP, atau unit kerja..." />
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>NIP</th>
                                <th>Unit Kerja</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading">
                                <td colspan="7" class="text-center">Memuat data...</td>
                            </tr>
                            <tr v-else-if="users.length === 0">
                                <td colspan="7" class="text-center">Tidak ada data pengguna ditemukan.</td>
                            </tr>
                            <tr v-for="(user, index) in users" :key="user.id">
                                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                                <td><strong>{{ user.employee?.full_name || user.name }}</strong></td>
                                <td>{{ user.employee?.job_title || '-' }}</td>
                                <td>{{ user.employee?.nip || '-' }}</td>
                                <td>{{ user.employee?.work_unit?.unit_name || '-' }}</td>
                                <td>{{ user.employee?.employment_status || '-' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning mr-1"
                                        @click="openEditModal(user)">Edit</button>
                                    <!-- <button class="btn btn-sm btn-danger" @click="deleteUser(user.id)">Hapus</button> -->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} pengguna
                        </div>
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                                <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page - 1)">«</a>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">
                                    Halaman {{ meta.current_page }} / {{ meta.last_page }}
                                </span>
                            </li>
                            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                                <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page + 1)">»</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit -->
        <!-- Modal Tambah/Edit -->
        <div class="modal fade" id="userModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="userModalLabel">
                            <i class="fas fa-user-edit me-2"></i>{{ isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body pt-2">
                        <form @submit.prevent="submitForm">
                            <div class="row">
                                <!-- Kiri -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Nama Lengkap</label>
                                        <input v-model="form.full_name" class="form-control form-control-sm" required readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Email</label>
                                        <input v-model="form.email" type="email" class="form-control form-control-sm" readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">NIP</label>
                                        <input v-model="form.nip" class="form-control form-control-sm" required readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Tanggal Lahir</label>
                                        <input v-model="form.date_of_birth" type="date"
                                            class="form-control form-control-sm" readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Jenis Kelamin</label>
                                        <select v-model="form.gender" class="form-control form-control-sm" readonly>
                                            <option value="M">Laki-laki</option>
                                            <option value="F">Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">No. HP</label>
                                        <input v-model="form.phone_number" class="form-control form-control-sm" />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Password</label>
                                        <input v-model="form.password" type="password"
                                            class="form-control form-control-sm" :required="!isEdit" />
                                    </div>
                                </div>

                                <!-- Kanan -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Jabatan</label>
                                        <input v-model="form.job_title" class="form-control form-control-sm" required readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Gol. Ruang</label>
                                        <input v-model="form.gol_ruang" class="form-control form-control-sm"
                                            placeholder="Contoh: III/b" required readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Unit Kerja</label>
                                        <select v-model="form.id_work_unit" class="form-control form-control-sm" readonly
                                            required>
                                            <option v-for="unit in masterDataStore.workunitList" :key="unit.id"
                                                :value="unit.id">
                                                {{ unit.text }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Status Kepegawaian</label>
                                        <select v-model="form.employment_status" class="form-control form-control-sm" readonly
                                            required>
                                            <option value="PNS">PNS</option>
                                            <option value="PPPK">PPPK</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">TMT Pangkat</label>
                                        <input v-model="form.tmt_pangkat" type="date"
                                            class="form-control form-control-sm" readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">TMT Jabatan</label>
                                        <input v-model="form.tmt_jabatan" type="date"
                                            class="form-control form-control-sm" readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">TMT Pensiun</label>
                                        <input v-model="form.tmt_pensiun" type="date"
                                            class="form-control form-control-sm" readonly />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Kategori Kepegawaian</label>
                                        <select v-model="form.employment_category" class="form-control form-control-sm">
                                            <option value="ACTIVE">Aktif</option>
                                            <option value="RETIRED">Pensiun</option>
                                            <option value="LEFT">Berhenti</option>
                                            <option value="DIED">Meninggal</option>
                                            <option value="MUTATION">Mutasi</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-sm btn-primary" :disabled="isSubmitting">
                                    <i v-if="isSubmitting" class="fas fa-spinner fa-spin me-1"></i>
                                    <i v-else class="fas fa-save me-1"></i>
                                    Simpan
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>


    </section>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import { useMasterDataStore } from '../../stores/MasterDataStore.js';
import { useAuthUserStore } from '../../stores/AuthUserStore.js';

const isSubmitting = ref(false)
const masterDataStore = useMasterDataStore();
const authUserStore = useAuthUserStore();

const users = ref([])
const meta = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    last_page: 1,
})
const search = ref('')
const isLoading = ref(false)
const isEdit = ref(false)
const form = ref({})

const fetchUsers = async (page = 1) => {
    isLoading.value = true
    try {
        const response = await axios.get('/api/users', {
            params: {
                page,
                search: search.value,
            },
        })

        users.value = response.data.data
        meta.value = {
            ...meta.value,
            ...response.data.meta,
            ...response.data,
        }
    } catch (error) {
        if (error.response && error.response.status === 401) {
            console.warn('Unauthorized. Logging out...');
            authUserStore.logout();
        } else {
            console.error('Gagal memuat data pengguna:', error);
        }
    } finally {
        isLoading.value = false
    }
}


const openCreateModal = () => {
    isEdit.value = false
    form.value = {}
    $('#userModal').modal('show') // jQuery + AdminLTE
}

const openEditModal = (user) => {
    isEdit.value = true
    form.value = {
        ...user,
        ...user.employee,
        id_work_unit: user.employee?.id_work_unit,
    }
    $('#userModal').modal('show') // jQuery + AdminLTE
}

const submitForm = async () => {
    isSubmitting.value = true

    try {
        if (isEdit.value) {
            await axios.put(`/api/users/${form.value.id}`, form.value)
        } else {
            await axios.post('/api/users', form.value)
        }
        fetchUsers()
        $('#userModal').modal('hide') // jQuery + AdminLTE
    } catch (error) {
        alert('Gagal menyimpan data: ' + error)
    } finally {
        isSubmitting.value = false
    }
}

const deleteUser = async (id) => {
    if (!confirm('Yakin ingin menghapus pengguna ini?')) return
    try {
        await axios.delete(`/api/users/${id}`)
        fetchUsers()
    } catch (error) {
        alert('Gagal menghapus pengguna')
    }
}

watch(search, useDebounceFn(() => fetchUsers(1), 400))
watch(() => form.nip, (newNip) => {
    form.username = newNip;
});
onMounted(() => {
    fetchUsers()
    masterDataStore.getWorkunitList();
})
</script>