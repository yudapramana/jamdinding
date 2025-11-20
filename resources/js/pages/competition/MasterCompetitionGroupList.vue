<template>
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-2">Master Group Kompetisi</h1>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                    + Tambah Group
                </button>
            </div>
            <p class="text-muted text-sm mb-0">
                Group kompetisi digunakan sebagai pengelompokan utama cabang lomba (misalnya: Tilawah, Tahfizh, Syarhil).
            </p>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <input
                        v-model="search"
                        type="text"
                        class="form-control form-control-sm w-50"
                        placeholder="Cari kode atau nama group..."
                    />
                    <span class="text-muted text-sm">
                        Total: <strong>{{ meta.total || 0 }}</strong> group
                    </span>
                </div>

                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 120px;">Kode</th>
                                <th>Nama Group</th>
                                <th style="width: 80px;">Urutan</th>
                                <th>Deskripsi</th>
                                <th style="width: 90px;">Status</th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading">
                                <td colspan="7" class="text-center">Memuat data...</td>
                            </tr>
                            <tr v-else-if="groups.length === 0">
                                <td colspan="7" class="text-center">
                                    Belum ada data group kompetisi.
                                    <br />
                                    <small class="text-muted">
                                        Klik <strong>Tambah Group</strong> untuk menambahkan.
                                    </small>
                                </td>
                            </tr>
                            <tr v-for="(group, index) in groups" :key="group.id">
                                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                                <td><code>{{ group.code }}</code></td>
                                <td><strong>{{ group.name }}</strong></td>
                                <td class="text-center">{{ group.order_number }}</td>
                                <td>{{ group.description || '-' }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="group.is_active ? 'badge-success' : 'badge-secondary'"
                                    >
                                        {{ group.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            class="btn btn-warning"
                                            title="Edit"
                                            @click="openEditModal(group)"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <button
                                            class="btn btn-danger"
                                            title="Hapus"
                                            @click="deleteGroup(group)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted text-sm">
                            Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
                            {{ meta.total || 0 }} group
                        </div>
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                                <a
                                    class="page-link"
                                    href="#"
                                    @click.prevent="changePage(meta.current_page - 1)"
                                >
                                    «
                                </a>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">
                                    Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
                                </span>
                            </li>
                            <li
                                class="page-item"
                                :class="{ disabled: meta.current_page === meta.last_page }"
                            >
                                <a
                                    class="page-link"
                                    href="#"
                                    @click.prevent="changePage(meta.current_page + 1)"
                                >
                                    »
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit -->
        <div class="modal fade" id="groupModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="groupModalLabel">
                            <i class="fas fa-layer-group me-2"></i>
                            {{ isEdit ? 'Edit Group Kompetisi' : 'Tambah Group Kompetisi' }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body pt-2">
                        <form @submit.prevent="submitForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Kode Group</label>
                                        <input
                                            v-model="form.code"
                                            class="form-control form-control-sm"
                                            required
                                            placeholder="Misal: TILAWAH"
                                        />
                                        <small class="text-muted">
                                            Gunakan huruf kapital dan underscore, misalnya: TILAWAH, TAHFIZH, SYARHIL.
                                        </small>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Nama Group</label>
                                        <input
                                            v-model="form.name"
                                            class="form-control form-control-sm"
                                            required
                                            placeholder="Misal: Tilawah Al-Qur'an"
                                        />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Urutan Tampil</label>
                                        <input
                                            v-model.number="form.order_number"
                                            type="number"
                                            min="1"
                                            class="form-control form-control-sm"
                                            required
                                        />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Status</label>
                                        <select
                                            v-model="form.is_active"
                                            class="form-control form-control-sm"
                                        >
                                            <option :value="true">Aktif</option>
                                            <option :value="false">Nonaktif</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Deskripsi</label>
                                        <textarea
                                            v-model="form.description"
                                            rows="5"
                                            class="form-control form-control-sm"
                                            placeholder="Contoh: Group untuk seluruh cabang Tilawah Al-Qur'an dengan berbagai golongan dan kategori."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <button
                                    type="submit"
                                    class="btn btn-sm btn-primary"
                                    :disabled="isSubmitting"
                                >
                                    <i
                                        v-if="isSubmitting"
                                        class="fas fa-spinner fa-spin me-1"
                                    ></i>
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
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

const groups = ref([])
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
const isSubmitting = ref(false)

const form = ref({
  id: null,
  code: '',
  name: '',
  order_number: 1,
  description: '',
  is_active: true,
})

const fetchGroups = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/v1/master-competition-groups', {
      params: {
        page,
        search: search.value,
      },
    })

    const paginated = response.data.data
    groups.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    if (error.response && error.response.status === 401) {
      console.warn('Unauthorized. Logging out...')
      authUserStore.logout()
    } else {
      console.error('Gagal memuat group kompetisi:', error)
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchGroups(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    code: '',
    name: '',
    order_number: (meta.value.total || 0) + 1,
    description: '',
    is_active: true,
  }
  $('#groupModal').modal('show')
}

const openEditModal = (group) => {
  isEdit.value = true
  form.value = {
    id: group.id,
    code: group.code,
    name: group.name,
    order_number: group.order_number,
    description: group.description,
    is_active: !!group.is_active,
  }
  $('#groupModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    code: form.value.code,
    name: form.value.name,
    order_number: form.value.order_number,
    description: form.value.description,
    is_active: form.value.is_active,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(
        `/api/v1/master-competition-groups/${form.value.id}`,
        payload
      )
    } else {
      await axios.post('/api/v1/master-competition-groups', payload)
    }

    $('#groupModal').modal('hide')
    fetchGroups(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan group kompetisi:', error)
    alert('Gagal menyimpan data group.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteGroup = async (group) => {
  if (!confirm(`Yakin ingin menghapus group "${group.name}"?`)) return

  try {
    await axios.delete(`/api/v1/master-competition-groups/${group.id}`)
    fetchGroups(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus group kompetisi:', error)
    alert('Gagal menghapus group.')
  }
}

// debounce search
watch(
  () => search.value,
  useDebounceFn(() => fetchGroups(1), 400)
)

onMounted(() => {
  fetchGroups()
})
</script>
