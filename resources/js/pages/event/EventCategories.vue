<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Kategori per Event (Event Categories)</h1>
          <p class="mb-0 text-muted text-sm">
            Mengatur kategori (Putra/Putri, dsb) yang diaktifkan pada event terpilih
            untuk setiap kombinasi Cabang + Golongan.
            Data ini dapat digenerate dari Master Categories lalu disesuaikan.
          </p>

          <p v-if="selectedBranchId || selectedGroupId" class="mb-0 mt-1 text-sm text-muted">
            Filter:
            <span v-if="selectedBranchId">
              Cabang
              <strong>
                {{ branches.find(b => b.id === selectedBranchId)?.name || ('ID: ' + selectedBranchId) }}
              </strong>
            </span>
            <span v-if="selectedGroupId">
              • Golongan
              <strong>
                {{ groups.find(g => g.id === selectedGroupId)?.name || ('ID: ' + selectedGroupId) }}
              </strong>
            </span>
          </p>


          <!-- Info event aktif -->
          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
          </p>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">
          <button
            class="btn btn-outline-secondary btn-sm mr-sm-2 mb-2 mb-sm-0"
            @click="generateFromTemplate"
            :disabled="isGenerating || !eventId"
          >
            <i v-if="isGenerating" class="fas fa-spinner fa-spin mr-1"></i>
            <i v-else class="fas fa-magic mr-1"></i>
            Generate dari Template
          </button>
          <button
            class="btn btn-primary btn-sm"
            @click="openCreateModal"
            :disabled="!eventId"
          >
            + Tambah Kategori
          </button>
        </div>
      </div>

      <p v-if="!eventId" class="text-danger text-sm mt-2 mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Event belum dipilih. Silakan pilih event melalui Portal Event terlebih dahulu.
      </p>
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

            <!-- RIGHT: search -->
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
                <th>Nama Lengkap</th>
                <th style="width: 90px;" class="text-center">Status</th>
                <th style="width: 80px;" class="text-center">Urutan</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="8" class="text-center">Memuat data event categories...</td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="8" class="text-center">
                  Belum ada kategori terdaftar untuk event ini.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Generate dari Template</strong> atau <strong>Tambah Kategori Event</strong>.
                  </small>
                </td>
              </tr>
              <tr
                v-for="(item, index) in items"
                :key="item.id"
              >
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td><strong>{{ item.branch_name }}</strong></td>
                <td><strong>{{ item.group_name }}</strong></td>
                <td><strong>{{ item.category_name }}</strong></td>
                <td>{{ item.full_name }}</td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="item.status === 'active' ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ item.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-center">{{ item.order_number ?? '-' }}</td>
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
              {{ meta.total || 0 }} kategori event
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

    <!-- MODAL TAMBAH / EDIT EVENT CATEGORY -->
    <div
      class="modal fade"
      id="eventCategoryModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="eventCategoryModalLabel"
    >
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h5 class="modal-title" id="eventCategoryModalLabel">
              <i class="fas fa-venus-mars mr-1"></i>
              {{ isEdit ? 'Edit Kategori Event' : 'Tambah Kategori Event' }}
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
                    <label class="mb-1">Cabang Lomba</label>
                    <select
                      v-model="form.branch_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih cabang --</option>
                      <option
                        v-for="b in branches"
                        :key="b.id"
                        :value="b.id"
                      >
                        {{ b.name }}
                        <span v-if="b.code"> ({{ b.code }})</span>
                      </option>
                    </select>
                    <small class="text-muted">
                      Data diambil dari master <strong>Branches</strong>.
                    </small>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Golongan</label>
                    <select
                      v-model="form.group_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih golongan --</option>
                      <option
                        v-for="g in groups"
                        :key="g.id"
                        :value="g.id"
                      >
                        {{ g.name }}
                        <span v-if="g.code"> ({{ g.code }})</span>
                      </option>
                    </select>
                    <small class="text-muted">
                      Data diambil dari master <strong>Groups</strong>.
                    </small>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Kategori</label>
                    <select
                      v-model="form.category_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih kategori --</option>
                      <option
                        v-for="c in categories"
                        :key="c.id"
                        :value="c.id"
                      >
                        {{ c.name }}
                        <span v-if="c.code"> ({{ c.code }})</span>
                      </option>
                    </select>
                    <small class="text-muted">
                      Data diambil dari master <strong>Categories</strong>.
                    </small>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Lengkap (Full Name)</label>
                    <input
                      v-model="form.full_name"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Hafalan 10 Juz - Remaja - Putra"
                    />
                    <small class="text-muted">
                      Jika dikosongkan, akan digenerate dari Cabang + Golongan + Kategori.
                    </small>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Status</label>
                    <select
                      v-model="form.status"
                      class="form-control form-control-sm"
                    >
                      <option value="active">Aktif</option>
                      <option value="inactive">Nonaktif</option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Urutan Tampil</label>
                    <input
                      v-model.number="form.order_number"
                      type="number"
                      min="1"
                      class="form-control form-control-sm"
                    />
                  </div>
                </div>
              </div>

              <div class="text-right mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="isSubmitting || !eventId"
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
import { ref, watch, onMounted, computed } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import { useRoute } from 'vue-router' // 👈

const authUserStore = useAuthUserStore()
const route = useRoute() // 👈

const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

const selectedBranchId = computed(() => {
  const raw = route.query.branch_id
  return raw ? Number(raw) : null
})

const selectedGroupId = computed(() => {
  const raw = route.query.group_id
  return raw ? Number(raw) : null
})

const items = ref([])
const branches = ref([])
const groups = ref([])
const categories = ref([])

const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)
const isGenerating = ref(false)

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
  full_name: '',
  status: 'active',
  order_number: null,
})

const fetchOptions = async () => {
  try {
    const [resBranches, resGroups, resCategories] = await Promise.all([
      axios.get('/api/v1/branches', { params: { simple: 1 } }),
      axios.get('/api/v1/groups', { params: { simple: 1 } }),
      axios.get('/api/v1/categories', { params: { simple: 1 } }),
    ])

    branches.value = resBranches.data.data || []
    groups.value = resGroups.data.data || []
    categories.value = resCategories.data.data || []
  } catch (error) {
    console.error('Gagal memuat branches / groups / categories:', error)
    Swal.fire('Gagal', 'Gagal memuat daftar cabang, golongan, dan kategori.', 'error')
  }
}

const fetchItems = async (page = 1) => {
  if (!eventId.value) return

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-categories', {
      params: {
        event_id: eventId.value,
        branch_id: selectedBranchId.value || undefined, // 👈
        group_id: selectedGroupId.value || undefined,   // 👈
        page,
        per_page: perPage.value,
        search: search.value,
        from_crud: 1,
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
    console.error('Gagal memuat event_categories:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    } else {
      Swal.fire('Gagal', 'Gagal memuat data event categories.', 'error')
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchItems(page)
}

const openCreateModal = () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum mengelola kategori.',
      'warning'
    )
    return
  }

  isEdit.value = false
  form.value = {
    id: null,
    branch_id: selectedBranchId.value || '',
    group_id: selectedGroupId.value || '',
    category_id: '',
    full_name: '',
    status: 'active',
    order_number: (meta.value.total || 0) + 1,
  }
  $('#eventCategoryModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    branch_id: item.branch_id,
    group_id: item.group_id,
    category_id: item.category_id,
    full_name: item.full_name,
    status: item.status,
    order_number: item.order_number,
  }
  $('#eventCategoryModal').modal('show')
}

const submitForm = async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum mengelola kategori.',
      'warning'
    )
    return
  }

  isSubmitting.value = true

  const payload = {
    event_id: eventId.value,
    branch_id: form.value.branch_id,
    group_id: form.value.group_id,
    category_id: form.value.category_id,
    full_name: form.value.full_name,
    status: form.value.status,
    order_number: form.value.order_number,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-categories/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Kategori event berhasil diperbarui.', 'success')
    } else {
      await axios.post('/api/v1/event-categories', payload)
      Swal.fire('Berhasil', 'Kategori event berhasil ditambahkan.', 'success')
    }

    $('#eventCategoryModal').modal('hide')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan event_category:', error)
    let message = 'Gagal menyimpan data kategori event.'
    if (error.response && error.response.status === 422 && error.response.data.message) {
      message = error.response.data.message
    }
    Swal.fire('Gagal', message, 'error')
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus Kategori Event?',
    text: `Yakin ingin menghapus kategori "${item.full_name}" dari event ini?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/event-categories/${item.id}`)
    Swal.fire('Terhapus', 'Kategori event berhasil dihapus.', 'success')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus event_category:', error)
    Swal.fire('Gagal', 'Gagal menghapus kategori event.', 'error')
  }
}

const generateFromTemplate = async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum generate dari template.',
      'warning'
    )
    return
  }

  const result = await Swal.fire({
    title: 'Generate dari Template?',
    text: 'Kategori event akan dibuat berdasarkan Master Categories. Kombinasi yang sudah ada tidak akan diduplikasi.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Generate',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
  })

  if (!result.isConfirmed) return

  isGenerating.value = true
  try {
    await axios.post('/api/v1/event-categories/generate-from-template', {
      event_id: eventId.value,
    })

    Swal.fire('Berhasil', 'Kategori event berhasil digenerate dari template.', 'success')
    fetchItems(1)
  } catch (error) {
    console.error('Gagal generate dari template (event_categories):', error)
    let message = 'Gagal generate kategori dari template.'
    if (error.response && error.response.data && error.response.data.message) {
      message = error.response.data.message
    }
    Swal.fire('Gagal', message, 'error')
  } finally {
    isGenerating.value = false
  }
}

// search debounce
watch(
  () => search.value,
  useDebounceFn(() => fetchItems(1), 400)
)

watch(
  () => perPage.value,
  () => {
    fetchItems(1)
  }
)

watch(
  () => eventId.value,
  (val) => {
    if (val) {
      fetchItems(1)
    }
  }
)

onMounted(() => {
  fetchOptions()
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event terlebih dahulu.',
      'info'
    )
  } else {
    fetchItems()
  }
})
</script>
