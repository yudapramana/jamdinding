<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Hak Akses Role (Permission Role)</h1>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          <i class="fas fa-plus mr-1"></i>
          Tambah Hak Akses
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <input
            v-model="search"
            type="text"
            class="form-control"
            placeholder="Cari role atau permission..."
          />
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Role</th>
                <th>Permission</th>
                <th>Slug Permission</th>
                <th style="width: 80px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="5" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="5" class="text-center">Belum ada relasi role–permission.</td>
              </tr>
              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>
                  <span class="badge badge-info" v-if="item.role">
                    {{ item.role.name }} ({{ item.role.slug }})
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <span v-if="item.permission">
                    {{ item.permission.name }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <code v-if="item.permission">{{ item.permission.slug }}</code>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-warning"
                      title="Edit"
                      @click="openEditModal(item)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-outline-danger"
                      title="Hapus"
                      @click="deleteItem(item)"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} relasi
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="fetchItems(meta.current_page - 1)">
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
                <a class="page-link" href="#" @click.prevent="fetchItems(meta.current_page + 1)">
                  »
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade" id="permRoleModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="permRoleModalLabel">
              <i class="fas fa-user-shield mr-2"></i>
              {{ isEdit ? 'Edit Hak Akses Role' : 'Tambah Hak Akses Role' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <form @submit.prevent="submitForm">
              <div class="form-group mb-2">
                <label class="mb-1">Role</label>
                <select
                  v-model="form.role_id"
                  class="form-control form-control-sm"
                  required
                >
                  <option :value="null">-- Pilih Role --</option>
                  <option v-for="r in roleOptions" :key="r.id" :value="r.id">
                    {{ r.name }} ({{ r.slug }})
                  </option>
                </select>
              </div>

              <div class="form-group mb-2">
                <label class="mb-1">Permission</label>
                <select
                  v-model="form.permission_id"
                  class="form-control form-control-sm"
                  required
                >
                  <option :value="null">-- Pilih Permission --</option>
                  <option v-for="p in permissionOptions" :key="p.id" :value="p.id">
                    {{ p.name }} — {{ p.slug }}
                  </option>
                </select>
              </div>

              <div class="text-end mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="isSubmitting"
                >
                  <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
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
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

const items = ref([])
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
const isSubmitting = ref(false)
const isEdit = ref(false)
const form = ref({
  id: null,
  role_id: null,
  permission_id: null,
})

const roleOptions = ref([])
const permissionOptions = ref([])

const fetchRoles = async () => {
  try {
    const res = await axios.get('/api/v1/roles-simple')
    roleOptions.value = res.data
  } catch (e) {
    console.error('Gagal memuat roles:', e)
  }
}

const fetchPermissions = async () => {
  try {
    const res = await axios.get('/api/v1/permissions-simple')
    permissionOptions.value = res.data
  } catch (e) {
    console.error('Gagal memuat permissions:', e)
  }
}

const fetchItems = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/permission-roles', {
      params: {
        page,
        search: search.value,
      },
    })

    items.value = res.data.data || []
    meta.value = {
      current_page: res.data.current_page,
      per_page: res.data.per_page,
      total: res.data.total,
      from: res.data.from,
      to: res.data.to,
      last_page: res.data.last_page,
    }
  } catch (error) {
    if (error.response && error.response.status === 401) {
      console.warn('Unauthorized. Logging out...')
      authUserStore.logout()
    } else {
      console.error('Gagal memuat relasi permission_role:', error)
    }
  } finally {
    isLoading.value = false
  }
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    role_id: null,
    permission_id: null,
  }
  $('#permRoleModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    role_id: item.role_id,
    permission_id: item.permission_id,
  }
  $('#permRoleModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true
  const payload = {
    role_id: form.value.role_id,
    permission_id: form.value.permission_id,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/permission-roles/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/permission-roles', payload)
    }

    $('#permRoleModal').modal('hide')
    await fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan relasi permission_role:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan data.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  if (!confirm(`Yakin ingin menghapus relasi "${item.role?.name} - ${item.permission?.name}"?`)) {
    return
  }

  try {
    await axios.delete(`/api/v1/permission-roles/${item.id}`)
    await fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus relasi:', error)
    alert(error.response?.data?.message || 'Gagal menghapus relasi.')
  }
}

watch(
  search,
  useDebounceFn(() => {
    fetchItems(1)
  }, 400)
)

onMounted(() => {
  fetchRoles()
  fetchPermissions()
  fetchItems()
})
</script>
