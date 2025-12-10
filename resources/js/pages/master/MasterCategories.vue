<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Master Kategori Cabang (Master Categories)</h1>
          <p class="mb-0 text-muted text-sm">
            Kombinasi Cabang + Golongan + Kategori sebagai template MTQ.
            Contoh: Hafalan Al Qur'an - 10 Juz - Putra.
          </p>
        </div>

        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Master Category
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <div class="card">
        <div class="card-header">
          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">
            <!-- LEFT: perPage -->
            <div class="d-flex align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-2">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <label class="mb-0 text-sm text-muted">Entri</label>
            </div>

            <!-- RIGHT: Search -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width: 220px"
              placeholder="Cari cabang / golongan / kategori..."
            />
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Cabang</th>
                <th>Golongan</th>
                <th>Kategori</th>
                <th>Full Name</th>
                <th style="width: 80px;" class="text-center">Urutan</th>
                <th style="width: 80px;" class="text-center">Status</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="8" class="text-center">Memuat data master categories...</td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="8" class="text-center">
                  Belum ada master category.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Tambah Master Category</strong> untuk menambahkan.
                  </small>
                </td>
              </tr>
              <tr
                v-for="(item, index) in items"
                :key="item.id"
              >
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>{{ item.branch_name }}</td>
                <td>{{ item.group_name }}</td>
                <td>{{ item.category_name }}</td>
                <td><strong>{{ item.full_name }}</strong></td>
                <td class="text-center">{{ item.order_number || '-' }}</td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="item.is_active ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-warning"
                      @click="openEditModal(item)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-danger"
                      @click="deleteItem(item)"
                    >
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
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} master category
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a
                  href="#"
                  class="page-link"
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
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a
                  href="#"
                  class="page-link"
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

    <!-- MODAL: TAMBAH / EDIT MASTER CATEGORY -->
    <div
      class="modal fade"
      id="masterCategoryModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="masterCategoryModalLabel"
    >
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h5 class="modal-title" id="masterCategoryModalLabel">
              <i class="fas fa-tags mr-1"></i>
              {{ isEdit ? 'Edit Master Category' : 'Tambah Master Category' }}
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
                    <label class="mb-1">Cabang</label>
                    <select
                      v-model="form.branch_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih cabang --</option>
                      <option v-for="b in branches" :key="b.id" :value="b.id">
                        {{ b.name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Golongan</label>
                    <select
                      v-model="form.group_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih golongan --</option>
                      <option v-for="g in groups" :key="g.id" :value="g.id">
                        {{ g.name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Kategori</label>
                    <select
                      v-model="form.category_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih kategori --</option>
                      <option v-for="c in categories" :key="c.id" :value="c.id">
                        {{ c.name }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1">Urutan Tampil</label>
                    <input
                      v-model.number="form.order_number"
                      type="number"
                      min="1"
                      class="form-control form-control-sm"
                    />
                    <small class="text-muted">
                      Urutan master category saat tampil (boleh dikosongkan).
                    </small>
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

                  <div class="alert alert-info py-2 px-3 mt-3 text-sm">
                    <strong>Catatan:</strong> Nama lengkap (full name) akan
                    dibentuk otomatis dari Cabang + Golongan + Kategori di server.
                  </div>
                </div>
              </div>

              <div class="text-right mt-3">
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
import { ref, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'

const items = ref([])
const branches = ref([])
const groups = ref([])
const categories = ref([])

const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const form = ref({
  id: null,
  branch_id: '',
  group_id: '',
  category_id: '',
  order_number: null,
  is_active: true,
})

const fetchOptions = async () => {
  try {
    const [resBranches, resGroups, resCategories] = await Promise.all([
      axios.get('/api/v1/branches?simple=1'),
      axios.get('/api/v1/groups?simple=1'),
      axios.get('/api/v1/categories?simple=1'),
    ])

    branches.value = resBranches.data.data || []
    groups.value = resGroups.data.data || []
    categories.value = resCategories.data.data || []
  } catch (error) {
    console.error('Gagal memuat options master:', error)
    Swal.fire('Gagal', 'Gagal memuat data cabang/golongan/kategori.', 'error')
  }
}

const fetchItems = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/master-categories', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
      },
    })

    const paginated = res.data.data
    items.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat master_categories:', error)
    Swal.fire('Gagal', 'Gagal memuat data master categories.', 'error')
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchItems(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    branch_id: '',
    group_id: '',
    category_id: '',
    order_number: (meta.value.total || 0) + 1,
    is_active: true,
  }
  $('#masterCategoryModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    branch_id: item.branch_id,
    group_id: item.group_id,
    category_id: item.category_id,
    order_number: item.order_number,
    is_active: !!item.is_active,
  }
  $('#masterCategoryModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    branch_id: form.value.branch_id,
    group_id: form.value.group_id,
    category_id: form.value.category_id,
    order_number: form.value.order_number,
    is_active: form.value.is_active,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/master-categories/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Master category berhasil diperbarui.', 'success')
    } else {
      await axios.post('/api/v1/master-categories', payload)
      Swal.fire('Berhasil', 'Master category berhasil ditambahkan.', 'success')
    }

    $('#masterCategoryModal').modal('hide')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan master_category:', error)
    Swal.fire('Gagal', 'Gagal menyimpan data master category.', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus Master Category?',
    text: `Yakin ingin menghapus "${item.full_name}"? Tindakan ini tidak bisa dibatalkan.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/master-categories/${item.id}`)
    Swal.fire('Terhapus', 'Master category berhasil dihapus.', 'success')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus master_category:', error)
    Swal.fire('Gagal', 'Gagal menghapus master category.', 'error')
  }
}

// search debounce
watch(
  () => search.value,
  useDebounceFn(() => fetchItems(1), 400)
)

// perPage change
watch(
  () => perPage.value,
  () => {
    fetchItems(1)
  }
)

onMounted(() => {
  fetchOptions()
  fetchItems()
})
</script>
