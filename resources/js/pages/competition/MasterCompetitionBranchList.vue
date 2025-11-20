<template>
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-2">Master Cabang / Golongan Kompetisi</h1>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                    + Tambah Cabang/Golongan
                </button>
            </div>
            <p class="text-muted text-sm mb-0">
                Cabang/golongan kompetisi adalah kombinasi Group, Kategori, Type (Putra/Putri), dan Format (individu/grup).
            </p>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <input
                                v-model="search"
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Cari kode, nama, atau kombinasi..."
                            />
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select
                                v-model="filterGroup"
                                class="form-control form-control-sm"
                            >
                                <option value="">-- Semua Group --</option>
                                <option v-for="g in groupOptions" :key="g.id" :value="g.id">
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select
                                v-model="filterCategory"
                                class="form-control form-control-sm"
                            >
                                <option value="">-- Semua Kategori --</option>
                                <option v-for="c in categoryOptions" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 text-right">
                            <span class="text-muted text-sm">
                                Total: <strong>{{ meta.total || 0 }}</strong> cabang
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <!-- <th style="width: 120px;">Kode</th> -->
                                <th>Nama Cabang/Golongan</th>
                                <th>Group</th>
                                <th>Kategori</th>
                                <th style="width: 80px;">Type</th>
                                <th style="width: 80px;">Format</th>
                                <th style="width: 80px;">Max Umur</th>
                                <th style="width: 80px;">Urutan</th>
                                <th style="width: 90px;">Status</th>
                                <th style="width: 90px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading">
                                <td colspan="11" class="text-center">Memuat data...</td>
                            </tr>
                            <tr v-else-if="branches.length === 0">
                                <td colspan="11" class="text-center">
                                    Belum ada data cabang/golongan kompetisi.
                                    <br />
                                    <small class="text-muted">
                                        Klik <strong>Tambah Cabang/Golongan</strong> untuk menambahkan.
                                    </small>
                                </td>
                            </tr>
                            <tr v-for="(branch, index) in branches" :key="branch.id">
                                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                                <!-- <td><code>{{ branch.code }}</code></td> -->
                                <td><strong>{{ branch.name }}</strong></td>
                                <td>{{ branch.group?.name || '-' }}</td>
                                <td>{{ branch.category?.name || '-' }}</td>
                                <td>{{ branch.type }}</td>
                                <td>{{ branch.format }}</td>
                                <td class="text-center">
                                    <span v-if="branch.max_age > 0">{{ branch.max_age }}</span>
                                    <span v-else>-</span>
                                </td>
                                <td class="text-center">{{ branch.order_number }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="branch.is_active ? 'badge-success' : 'badge-secondary'"
                                    >
                                        {{ branch.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            class="btn btn-warning"
                                            @click="openEditModal(branch)"
                                            title="Edit"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            class="btn btn-danger"
                                            @click="deleteBranch(branch)"
                                            title="Hapus"
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
                            {{ meta.total || 0 }} cabang
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

        <!-- Modal Tambah / Edit Branch -->
        <div class="modal fade" id="masterBranchModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="masterBranchModalLabel">
                            <i class="fas fa-sitemap me-2"></i>
                            {{ isEdit ? 'Edit Cabang/Golongan' : 'Tambah Cabang/Golongan' }}
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
                                        <label class="mb-1">Kode</label>
                                        <input
                                            v-model="form.code"
                                            class="form-control form-control-sm"
                                            required
                                            placeholder="Misal: TILAWAH_ANAK_PUTRA"
                                        />
                                        <small class="text-muted">
                                            Gunakan huruf kapital & underscore (kode unik).
                                        </small>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Group Kompetisi</label>
                                        <select
                                            v-model="form.master_competition_group_id"
                                            class="form-control form-control-sm"
                                            required
                                        >
                                            <option value="">-- Pilih Group --</option>
                                            <option
                                                v-for="g in groupOptions"
                                                :key="g.id"
                                                :value="g.id"
                                            >
                                                {{ g.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Kategori Kompetisi</label>
                                        <select
                                            v-model="form.master_competition_category_id"
                                            class="form-control form-control-sm"
                                            required
                                        >
                                            <option value="">-- Pilih Kategori --</option>
                                            <option
                                                v-for="c in categoryOptions"
                                                :key="c.id"
                                                :value="c.id"
                                            >
                                                {{ c.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Type</label>
                                        <select
                                            v-model="form.type"
                                            class="form-control form-control-sm"
                                            required
                                        >
                                            <option value="Putra">Putra</option>
                                            <option value="Putri">Putri</option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Format</label>
                                        <select
                                            v-model="form.format"
                                            class="form-control form-control-sm"
                                            required
                                        >
                                            <option value="individu">Individu</option>
                                            <option value="grup">Grup</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Nama Cabang/Golongan</label>
                                        <input
                                            v-model="form.name"
                                            class="form-control form-control-sm"
                                            required
                                            placeholder="Misal: Tilawah Anak-Anak Putra"
                                        />
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Maksimal Umur</label>
                                        <input
                                            v-model.number="form.max_age"
                                            type="number"
                                            min="0"
                                            class="form-control form-control-sm"
                                            placeholder="0 = tanpa batasan"
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
                                        <label class="mb-1">Deskripsi</label>
                                        <textarea
                                            v-model="form.description"
                                            rows="3"
                                            class="form-control form-control-sm"
                                            placeholder="Contoh: Tilawah Golongan Anak-Anak Putra, maksimal usia 12 tahun."
                                        ></textarea>
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

const branches = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const search = ref('')
const filterGroup = ref('')
const filterCategory = ref('')

const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)

const form = ref({
  id: null,
  code: '',
  master_competition_group_id: '',
  master_competition_category_id: '',
  type: 'Putra',
  format: 'individu',
  name: '',
  max_age: 0,
  order_number: 1,
  description: '',
  is_active: true,
})

// options group & category
const groupOptions = ref([])
const categoryOptions = ref([])

const fetchOptions = async () => {
  try {
    // anggap API master-competition-groups & categories sudah ada (pola sama)
    const [groupRes, categoryRes] = await Promise.all([
      axios.get('/api/v1/master-competition-groups', { params: { per_page: 999 } }),
      axios.get('/api/v1/master-competition-categories', { params: { per_page: 999 } }),
    ])

    const groupPaginated = groupRes.data.data
    groupOptions.value = groupPaginated.data || []

    const categoryPaginated = categoryRes.data.data
    categoryOptions.value = categoryPaginated.data || []
  } catch (error) {
    console.error('Gagal memuat master group/category:', error)
  }
}

const fetchBranches = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/v1/master-competition-branches', {
      params: {
        page,
        search: search.value,
        group_id: filterGroup.value || undefined,
        category_id: filterCategory.value || undefined,
      },
    })

    const paginated = response.data.data
    branches.value = paginated.data || []
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
      console.error('Gagal memuat cabang kompetisi:', error)
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchBranches(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    code: '',
    master_competition_group_id: '',
    master_competition_category_id: '',
    type: 'Putra',
    format: 'individu',
    name: '',
    max_age: 0,
    order_number: (meta.value.total || 0) + 1,
    description: '',
    is_active: true,
  }
  $('#masterBranchModal').modal('show')
}

const openEditModal = (branch) => {
  isEdit.value = true
  form.value = {
    id: branch.id,
    code: branch.code,
    master_competition_group_id: branch.master_competition_group_id,
    master_competition_category_id: branch.master_competition_category_id,
    type: branch.type,
    format: branch.format,
    name: branch.name,
    max_age: branch.max_age,
    order_number: branch.order_number,
    description: branch.description,
    is_active: !!branch.is_active,
  }
  $('#masterBranchModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    code: form.value.code,
    master_competition_group_id: form.value.master_competition_group_id,
    master_competition_category_id: form.value.master_competition_category_id,
    type: form.value.type,
    format: form.value.format,
    name: form.value.name,
    max_age: form.value.max_age,
    order_number: form.value.order_number,
    description: form.value.description,
    is_active: form.value.is_active,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(
        `/api/v1/master-competition-branches/${form.value.id}`,
        payload
      )
    } else {
      await axios.post('/api/v1/master-competition-branches', payload)
    }

    $('#masterBranchModal').modal('hide')
    fetchBranches(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan cabang kompetisi:', error)
    alert('Gagal menyimpan data cabang/golongan.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteBranch = async (branch) => {
  if (!confirm(`Yakin ingin menghapus cabang/golongan "${branch.name}"?`)) return

  try {
    await axios.delete(`/api/v1/master-competition-branches/${branch.id}`)
    fetchBranches(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus cabang kompetisi:', error)
    alert('Gagal menghapus cabang/golongan.')
  }
}

// pencarian & filter
watch(
  () => search.value,
  useDebounceFn(() => fetchBranches(1), 400)
)

watch(
  () => [filterGroup.value, filterCategory.value],
  useDebounceFn(() => fetchBranches(1), 300),
  { deep: true }
)

onMounted(async () => {
  await fetchOptions()
  fetchBranches()
})
</script>

<style scoped>
.table td {
  vertical-align: middle;
}
</style>
