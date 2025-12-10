<template>

    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-2">Master Cabang Golongan Kategori</h1>
            </div>
            <p class="text-muted text-sm mb-0">
                Master Cabang Golongan Kategori. Master ini akan dipakai sebagai template saat generate Cabang pada setiap event.
            </p>
        </div>
    </section>
  <section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-6">
                <!-- ================== BRANCHES ================== -->
                <div class="card">
                    <div class="card-header">

                        <div class="d-flex flex-wrap justify-content-between align-items-center w-100">

                            <!-- LEFT: perPage + Status -->
                            <div class="d-flex flex-wrap align-items-center">
                                <h4 class="mb-0">Master Cabang</h4><br>
                            </div>

                            <!-- RIGHT: Search -->
                            

                            <div class="d-flex align-items-center gap-2">
                                <input
                                v-model="branchSearch"
                                type="text"
                                class="form-control form-control-sm mr-2"
                                style="width: 220px;"
                                placeholder="Cari code / nama cabang..."
                                />
                                <button class="btn btn-primary btn-sm" @click="openBranchCreateModal">
                                + Tambah Cabang
                                </button>
                            </div>

                        </div>
                    </div>

                    <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 110px;">Kode</th>
                            <th>Nama Cabang</th>
                            <th style="width: 80px;" class="text-center">Regu?</th>
                            <th style="width: 80px;" class="text-center">Urutan</th>
                            <th style="width: 80px;" class="text-center">Status</th>
                            <th style="width: 110px;" class="text-center">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="branchLoading">
                            <td colspan="7" class="text-center">Memuat data cabang...</td>
                        </tr>
                        <tr v-else-if="branches.length === 0">
                            <td colspan="7" class="text-center">
                            Belum ada data cabang lomba.
                            <br />
                            <small class="text-muted">
                                Klik <strong>Tambah Cabang</strong> untuk menambahkan.
                            </small>
                            </td>
                        </tr>
                        <tr
                            v-for="(item, index) in branches"
                            :key="item.id"
                        >
                            <td>{{ index + 1 + (branchMeta.current_page - 1) * branchMeta.per_page }}</td>
                            <td><code>{{ item.code || '-' }}</code></td>
                            <td><strong>{{ item.name }}</strong></td>
                            <td class="text-center">
                            <span class="badge" :class="item.is_team ? 'badge-info' : 'badge-secondary'">
                                {{ item.is_team ? 'Regu' : 'Perorangan' }}
                            </span>
                            </td>
                            <td class="text-center">{{ item.order_number || '-' }}</td>
                            <td class="text-center">
                            <span class="badge" :class="item.is_active ? 'badge-success' : 'badge-secondary'">
                                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            </td>
                            <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-warning" @click="openBranchEditModal(item)">
                                <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" @click="deleteBranch(item)">
                                <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    </div>

                    <div class="card-footer clearfix py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted text-sm">
                        Menampilkan {{ branchMeta.from || 0 }} - {{ branchMeta.to || 0 }} dari
                        {{ branchMeta.total || 0 }} cabang
                        </div>
                        <ul class="pagination pagination-sm m-0">
                        <li class="page-item" :class="{ disabled: branchMeta.current_page === 1 }">
                            <a
                            href="#"
                            class="page-link"
                            @click.prevent="changeBranchPage(branchMeta.current_page - 1)"
                            >
                            «
                            </a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">
                            Halaman {{ branchMeta.current_page }} / {{ branchMeta.last_page || 1 }}
                            </span>
                        </li>
                        <li class="page-item" :class="{ disabled: branchMeta.current_page === branchMeta.last_page }">
                            <a
                            href="#"
                            class="page-link"
                            @click.prevent="changeBranchPage(branchMeta.current_page + 1)"
                            >
                            »
                            </a>
                        </li>
                        </ul>
                    </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- ================== GROUPS ================== -->
                <div class="card">
                    <div class="card-header">



                        <div class="d-flex flex-wrap justify-content-between align-items-center w-100">

                            <!-- LEFT: perPage + Status -->
                            <div class="d-flex flex-wrap align-items-center">
                                <h4 class="mb-0">Master Golongan</h4><br>
                            </div>

                            <!-- RIGHT: Search -->
                            

                            <div class="d-flex align-items-center gap-2">
                                <input
                                v-model="groupSearch"
                                type="text"
                                class="form-control form-control-sm mr-2"
                                style="width: 220px;"
                                placeholder="Cari code / nama golongan..."
                                />
                                <button class="btn btn-primary btn-sm" @click="openGroupCreateModal">
                                + Tambah Golongan
                                </button>
                            </div>

                        </div>

                    </div>

                    <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 110px;">Kode</th>
                            <th>Nama Golongan</th>
                            <th style="width: 80px;" class="text-center">Urutan</th>
                            <th style="width: 80px;" class="text-center">Status</th>
                            <th style="width: 110px;" class="text-center">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="groupLoading">
                            <td colspan="6" class="text-center">Memuat data golongan...</td>
                        </tr>
                        <tr v-else-if="groups.length === 0">
                            <td colspan="6" class="text-center">
                            Belum ada data golongan.
                            <br />
                            <small class="text-muted">
                                Klik <strong>Tambah Golongan</strong> untuk menambahkan.
                            </small>
                            </td>
                        </tr>
                        <tr
                            v-for="(item, index) in groups"
                            :key="item.id"
                        >
                            <td>{{ index + 1 + (groupMeta.current_page - 1) * groupMeta.per_page }}</td>
                            <td><code>{{ item.code || '-' }}</code></td>
                            <td><strong>{{ item.name }}</strong></td>
                            <td class="text-center">{{ item.order_number || '-' }}</td>
                            <td class="text-center">
                            <span class="badge" :class="item.is_active ? 'badge-success' : 'badge-secondary'">
                                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            </td>
                            <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-warning" @click="openGroupEditModal(item)">
                                <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" @click="deleteGroup(item)">
                                <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    </div>

                    <div class="card-footer clearfix py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted text-sm">
                        Menampilkan {{ groupMeta.from || 0 }} - {{ groupMeta.to || 0 }} dari
                        {{ groupMeta.total || 0 }} golongan
                        </div>
                        <ul class="pagination pagination-sm m-0">
                        <li class="page-item" :class="{ disabled: groupMeta.current_page === 1 }">
                            <a
                            href="#"
                            class="page-link"
                            @click.prevent="changeGroupPage(groupMeta.current_page - 1)"
                            >
                            «
                            </a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">
                            Halaman {{ groupMeta.current_page }} / {{ groupMeta.last_page || 1 }}
                            </span>
                        </li>
                        <li class="page-item" :class="{ disabled: groupMeta.current_page === groupMeta.last_page }">
                            <a
                            href="#"
                            class="page-link"
                            @click.prevent="changeGroupPage(groupMeta.current_page + 1)"
                            >
                            »
                            </a>
                        </li>
                        </ul>
                    </div>
                    </div>
                </div>

            </div>
        </div>

      

      

      <!-- ================== CATEGORIES ================== -->
      <div class="card mt-3">
        <div class="card-header ">

            <div class="d-flex flex-wrap justify-content-between align-items-center w-100">

                <!-- LEFT: perPage + Status -->
                <div class="d-flex flex-wrap align-items-center">
                    <h4 class="mb-0">Master Kategori</h4><br>
                </div>

                <!-- RIGHT: Search -->
                

                <div class="d-flex align-items-center gap-2">
                    <input
                    v-model="categorySearch"
                    type="text"
                    class="form-control form-control-sm mr-2"
                    style="width: 220px;"
                    placeholder="Cari code / nama kategori..."
                    />
                    <button class="btn btn-primary btn-sm" @click="openCategoryCreateModal">
                    + Tambah Kategori
                    </button>
                </div>

            </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 110px;">Kode</th>
                <th>Nama Kategori</th>
                <th style="width: 80px;" class="text-center">Urutan</th>
                <th style="width: 80px;" class="text-center">Status</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="categoryLoading">
                <td colspan="6" class="text-center">Memuat data kategori...</td>
              </tr>
              <tr v-else-if="categories.length === 0">
                <td colspan="6" class="text-center">
                  Belum ada data kategori.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Tambah Kategori</strong> untuk menambahkan.
                  </small>
                </td>
              </tr>
              <tr
                v-for="(item, index) in categories"
                :key="item.id"
              >
                <td>{{ index + 1 + (categoryMeta.current_page - 1) * categoryMeta.per_page }}</td>
                <td><code>{{ item.code || '-' }}</code></td>
                <td><strong>{{ item.name }}</strong></td>
                <td class="text-center">{{ item.order_number || '-' }}</td>
                <td class="text-center">
                  <span class="badge" :class="item.is_active ? 'badge-success' : 'badge-secondary'">
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-warning" @click="openCategoryEditModal(item)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger" @click="deleteCategory(item)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ categoryMeta.from || 0 }} - {{ categoryMeta.to || 0 }} dari
              {{ categoryMeta.total || 0 }} kategori
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: categoryMeta.current_page === 1 }">
                <a
                  href="#"
                  class="page-link"
                  @click.prevent="changeCategoryPage(categoryMeta.current_page - 1)"
                >
                  «
                </a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ categoryMeta.current_page }} / {{ categoryMeta.last_page || 1 }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: categoryMeta.current_page === categoryMeta.last_page }">
                <a
                  href="#"
                  class="page-link"
                  @click.prevent="changeCategoryPage(categoryMeta.current_page + 1)"
                >
                  »
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </div>

    <!-- ============= MODAL BRANCH ============= -->
    <div class="modal fade" id="branchModal" tabindex="-1" role="dialog" aria-labelledby="branchModalLabel">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="branchModalLabel">
              <i class="fas fa-code-branch mr-1"></i>
              {{ branchIsEdit ? 'Edit Cabang Lomba' : 'Tambah Cabang Lomba' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body pt-2">
            <form @submit.prevent="submitBranchForm">
              <div class="form-group mb-2">
                <label class="mb-1">Kode (opsional)</label>
                <input
                  v-model="branchForm.code"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Contoh: HIFZH10, FAHM, TAFSIR"
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Nama Cabang</label>
                <input
                  v-model="branchForm.name"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Jenis Peserta</label>
                <select
                  v-model="branchForm.is_team"
                  class="form-control form-control-sm"
                >
                  <option :value="false">Perorangan</option>
                  <option :value="true">Regu / Tim</option>
                </select>
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Urutan Tampil</label>
                <input
                  v-model.number="branchForm.order_number"
                  type="number"
                  min="1"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Status</label>
                <select
                  v-model="branchForm.is_active"
                  class="form-control form-control-sm"
                >
                  <option :value="true">Aktif</option>
                  <option :value="false">Nonaktif</option>
                </select>
              </div>

              <div class="text-right mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="branchSubmitting"
                >
                  <i v-if="branchSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-save mr-1"></i>
                  Simpan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============= MODAL GROUP ============= -->
    <div class="modal fade" id="groupModal" tabindex="-1" role="dialog" aria-labelledby="groupModalLabel">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="groupModalLabel">
              <i class="fas fa-layer-group mr-1"></i>
              {{ groupIsEdit ? 'Edit Golongan' : 'Tambah Golongan' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body pt-2">
            <form @submit.prevent="submitGroupForm">
              <div class="form-group mb-2">
                <label class="mb-1">Kode (opsional)</label>
                <input
                  v-model="groupForm.code"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Contoh: DEWASA, REMAJA, 10JUZ"
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Nama Golongan</label>
                <input
                  v-model="groupForm.name"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Urutan Tampil</label>
                <input
                  v-model.number="groupForm.order_number"
                  type="number"
                  min="1"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Status</label>
                <select
                  v-model="groupForm.is_active"
                  class="form-control form-control-sm"
                >
                  <option :value="true">Aktif</option>
                  <option :value="false">Nonaktif</option>
                </select>
              </div>

              <div class="text-right mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="groupSubmitting"
                >
                  <i v-if="groupSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-save mr-1"></i>
                  Simpan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============= MODAL CATEGORY ============= -->
    <div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="categoryModalLabel">
              <i class="fas fa-venus-mars mr-1"></i>
              {{ categoryIsEdit ? 'Edit Kategori' : 'Tambah Kategori' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body pt-2">
            <form @submit.prevent="submitCategoryForm">
              <div class="form-group mb-2">
                <label class="mb-1">Kode (opsional)</label>
                <input
                  v-model="categoryForm.code"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Contoh: PUTRA, PUTRI"
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Nama Kategori</label>
                <input
                  v-model="categoryForm.name"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Urutan Tampil</label>
                <input
                  v-model.number="categoryForm.order_number"
                  type="number"
                  min="1"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="form-group mb-2">
                <label class="mb-1">Status</label>
                <select
                  v-model="categoryForm.is_active"
                  class="form-control form-control-sm"
                >
                  <option :value="true">Aktif</option>
                  <option :value="false">Nonaktif</option>
                </select>
              </div>

              <div class="text-right mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="categorySubmitting"
                >
                  <i v-if="categorySubmitting" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-save mr-1"></i>
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

// ================= BRANCHES =================
const branches = ref([])
const branchMeta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})
const branchSearch = ref('')
const branchLoading = ref(false)
const branchIsEdit = ref(false)
const branchSubmitting = ref(false)

const branchForm = ref({
  id: null,
  code: '',
  name: '',
  is_team: false,
  order_number: null,
  is_active: true,
})

const fetchBranches = async (page = 1) => {
  branchLoading.value = true
  try {
    const response = await axios.get('/api/v1/branches', {
      params: {
        page,
        search: branchSearch.value,
      },
    })
    const paginated = response.data.data
    branches.value = paginated.data || []
    branchMeta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat branches:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    branchLoading.value = false
  }
}

const changeBranchPage = (page) => {
  if (page < 1 || page > branchMeta.value.last_page) return
  fetchBranches(page)
}

const openBranchCreateModal = () => {
  branchIsEdit.value = false
  branchForm.value = {
    id: null,
    code: '',
    name: '',
    is_team: false,
    order_number: (branchMeta.value.total || 0) + 1,
    is_active: true,
  }
  $('#branchModal').modal('show')
}

const openBranchEditModal = (item) => {
  branchIsEdit.value = true
  branchForm.value = {
    id: item.id,
    code: item.code,
    name: item.name,
    is_team: !!item.is_team,
    order_number: item.order_number,
    is_active: !!item.is_active,
  }
  $('#branchModal').modal('show')
}

const submitBranchForm = async () => {
  branchSubmitting.value = true
  const payload = {
    code: branchForm.value.code,
    name: branchForm.value.name,
    is_team: branchForm.value.is_team,
    order_number: branchForm.value.order_number,
    is_active: branchForm.value.is_active,
  }

  try {
    if (branchIsEdit.value && branchForm.value.id) {
      await axios.put(`/api/v1/branches/${branchForm.value.id}`, payload)
    } else {
      await axios.post('/api/v1/branches', payload)
    }
    $('#branchModal').modal('hide')
    fetchBranches(branchMeta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan branch:', error)
    alert('Gagal menyimpan data cabang.')
  } finally {
    branchSubmitting.value = false
  }
}

const deleteBranch = async (item) => {
  if (!confirm(`Yakin ingin menghapus cabang "${item.name}"?`)) return

  try {
    await axios.delete(`/api/v1/branches/${item.id}`)
    fetchBranches(branchMeta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus branch:', error)
    alert('Gagal menghapus cabang.')
  }
}

// debounce untuk search branches
watch(
  () => branchSearch.value,
  useDebounceFn(() => fetchBranches(1), 400)
)

// ================= GROUPS =================
const groups = ref([])
const groupMeta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})
const groupSearch = ref('')
const groupLoading = ref(false)
const groupIsEdit = ref(false)
const groupSubmitting = ref(false)

const groupForm = ref({
  id: null,
  code: '',
  name: '',
  order_number: null,
  is_active: true,
})

const fetchGroups = async (page = 1) => {
  groupLoading.value = true
  try {
    const response = await axios.get('/api/v1/groups', {
      params: {
        page,
        search: groupSearch.value,
      },
    })
    const paginated = response.data.data
    groups.value = paginated.data || []
    groupMeta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat groups:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    groupLoading.value = false
  }
}

const changeGroupPage = (page) => {
  if (page < 1 || page > groupMeta.value.last_page) return
  fetchGroups(page)
}

const openGroupCreateModal = () => {
  groupIsEdit.value = false
  groupForm.value = {
    id: null,
    code: '',
    name: '',
    order_number: (groupMeta.value.total || 0) + 1,
    is_active: true,
  }
  $('#groupModal').modal('show')
}

const openGroupEditModal = (item) => {
  groupIsEdit.value = true
  groupForm.value = {
    id: item.id,
    code: item.code,
    name: item.name,
    order_number: item.order_number,
    is_active: !!item.is_active,
  }
  $('#groupModal').modal('show')
}

const submitGroupForm = async () => {
  groupSubmitting.value = true
  const payload = {
    code: groupForm.value.code,
    name: groupForm.value.name,
    order_number: groupForm.value.order_number,
    is_active: groupForm.value.is_active,
  }

  try {
    if (groupIsEdit.value && groupForm.value.id) {
      await axios.put(`/api/v1/groups/${groupForm.value.id}`, payload)
    } else {
      await axios.post('/api/v1/groups', payload)
    }
    $('#groupModal').modal('hide')
    fetchGroups(groupMeta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan group:', error)
    alert('Gagal menyimpan data golongan.')
  } finally {
    groupSubmitting.value = false
  }
}

const deleteGroup = async (item) => {
  if (!confirm(`Yakin ingin menghapus golongan "${item.name}"?`)) return

  try {
    await axios.delete(`/api/v1/groups/${item.id}`)
    fetchGroups(groupMeta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus group:', error)
    alert('Gagal menghapus golongan.')
  }
}

// debounce untuk search groups
watch(
  () => groupSearch.value,
  useDebounceFn(() => fetchGroups(1), 400)
)

// ================= CATEGORIES =================
const categories = ref([])
const categoryMeta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})
const categorySearch = ref('')
const categoryLoading = ref(false)
const categoryIsEdit = ref(false)
const categorySubmitting = ref(false)

const categoryForm = ref({
  id: null,
  code: '',
  name: '',
  order_number: null,
  is_active: true,
})

const fetchCategories = async (page = 1) => {
  categoryLoading.value = true
  try {
    const response = await axios.get('/api/v1/categories', {
      params: {
        page,
        search: categorySearch.value,
      },
    })
    const paginated = response.data.data
    categories.value = paginated.data || []
    categoryMeta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat categories:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    categoryLoading.value = false
  }
}

const changeCategoryPage = (page) => {
  if (page < 1 || page > categoryMeta.value.last_page) return
  fetchCategories(page)
}

const openCategoryCreateModal = () => {
  categoryIsEdit.value = false
  categoryForm.value = {
    id: null,
    code: '',
    name: '',
    order_number: (categoryMeta.value.total || 0) + 1,
    is_active: true,
  }
  $('#categoryModal').modal('show')
}

const openCategoryEditModal = (item) => {
  categoryIsEdit.value = true
  categoryForm.value = {
    id: item.id,
    code: item.code,
    name: item.name,
    order_number: item.order_number,
    is_active: !!item.is_active,
  }
  $('#categoryModal').modal('show')
}

const submitCategoryForm = async () => {
  categorySubmitting.value = true
  const payload = {
    code: categoryForm.value.code,
    name: categoryForm.value.name,
    order_number: categoryForm.value.order_number,
    is_active: categoryForm.value.is_active,
  }

  try {
    if (categoryIsEdit.value && categoryForm.value.id) {
      await axios.put(`/api/v1/categories/${categoryForm.value.id}`, payload)
    } else {
      await axios.post('/api/v1/categories', payload)
    }
    $('#categoryModal').modal('hide')
    fetchCategories(categoryMeta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan category:', error)
    alert('Gagal menyimpan data kategori.')
  } finally {
    categorySubmitting.value = false
  }
}

const deleteCategory = async (item) => {
  if (!confirm(`Yakin ingin menghapus kategori "${item.name}"?`)) return

  try {
    await axios.delete(`/api/v1/categories/${item.id}`)
    fetchCategories(categoryMeta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus category:', error)
    alert('Gagal menghapus kategori.')
  }
}

// debounce untuk search categories
watch(
  () => categorySearch.value,
  useDebounceFn(() => fetchCategories(1), 400)
)

// ================= ON MOUNT =================
onMounted(() => {
  fetchBranches()
  fetchGroups()
  fetchCategories()
})
</script>
