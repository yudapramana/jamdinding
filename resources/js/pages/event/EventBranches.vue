<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Cabang Lomba per Event (Event Branches)</h1>
          <p class="mb-0 text-muted text-sm">
            Mengatur cabang lomba yang diaktifkan pada event terpilih.
            Data ini dapat digenerate dari Master Branches lalu disesuaikan.
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
            + Tambah Cabang
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
              placeholder="Cari nama cabang..."
            />
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Nama Cabang</th>
                <th>Nama Lengkap</th>
                <th style="width: 90px;" class="text-center">Status</th>
                <th style="width: 90px;" class="text-center">Urutan</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data event branches...</td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center">
                  Belum ada cabang terdaftar untuk event ini.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Generate dari Template</strong> atau <strong>Tambah Cabang Event</strong>.
                  </small>
                </td>
              </tr>
              <tr
                v-for="(item, index) in items"
                :key="item.id"
              >
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td><strong>{{ item.branch_name }}</strong></td>
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
                        class="btn btn-info"
                        title="Kelola Golongan untuk Cabang ini"
                        @click="goToEventGroups(item)"
                        >
                        <i class="fas fa-users"></i>
                        </button>

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
              {{ meta.total || 0 }} cabang event
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

    <!-- MODAL TAMBAH / EDIT EVENT BRANCH -->
    <div
      class="modal fade"
      id="eventBranchModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="eventBranchModalLabel"
    >
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h5 class="modal-title" id="eventBranchModalLabel">
              <i class="fas fa-code-branch mr-1"></i>
              {{ isEdit ? 'Edit Cabang Event' : 'Tambah Cabang Event' }}
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
                    <label class="mb-1">Nama Cabang (Denormalisasi)</label>
                    <input
                      v-model="form.branch_name"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Jika kosong, akan diisi otomatis dari master branch"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Lengkap (Full Name)</label>
                    <input
                      v-model="form.full_name"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Cabang Tilawah Al Qur'an Dewasa"
                    />
                    <small class="text-muted">
                      Jika dikosongkan, dapat disamakan dengan Nama Cabang.
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
import { useRouter } from 'vue-router' // 👈 TAMBAH INI


const authUserStore = useAuthUserStore()
const router = useRouter() // 👈

const goToEventGroups = (item) => {
  // item = event_branch row
  router.push({
    name: 'admin.event.groups',
    query: {
      branch_id: item.branch_id, // filter by cabang ini
    },
  })
}


// ✅ event aktif diambil dari AuthUserStore
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

const items = ref([])
const branches = ref([])

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
  branch_name: '',
  full_name: '',
  status: 'active',
  order_number: null,
})

const fetchBranches = async () => {
  try {
    const res = await axios.get('/api/v1/branches', { params: { simple: 1 } })
    branches.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat branches:', error)
    Swal.fire('Gagal', 'Gagal memuat daftar cabang (branches).', 'error')
  }
}

const fetchItems = async (page = 1) => {
  if (!eventId.value) {
    return
  }

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-branches', {
      params: {
        event_id: eventId.value,
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
    console.error('Gagal memuat event_branches:', error)
    // optional: logout kalau 401
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    } else {
      Swal.fire('Gagal', 'Gagal memuat data event branches.', 'error')
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
      'Silakan pilih event melalui Portal Event sebelum mengelola cabang.',
      'warning'
    )
    return
  }

  isEdit.value = false
  form.value = {
    id: null,
    branch_id: '',
    branch_name: '',
    full_name: '',
    status: 'active',
    order_number: (meta.value.total || 0) + 1,
  }
  $('#eventBranchModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    branch_id: item.branch_id,
    branch_name: item.branch_name,
    full_name: item.full_name,
    status: item.status,
    order_number: item.order_number,
  }
  $('#eventBranchModal').modal('show')
}

const submitForm = async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum mengelola cabang.',
      'warning'
    )
    return
  }

  isSubmitting.value = true

  const payload = {
    event_id: eventId.value,
    branch_id: form.value.branch_id,
    branch_name: form.value.branch_name,
    full_name: form.value.full_name,
    status: form.value.status,
    order_number: form.value.order_number,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-branches/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Cabang event berhasil diperbarui.', 'success')
    } else {
      await axios.post('/api/v1/event-branches', payload)
      Swal.fire('Berhasil', 'Cabang event berhasil ditambahkan.', 'success')
    }

    $('#eventBranchModal').modal('hide')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan event_branch:', error)
    let message = 'Gagal menyimpan data cabang event.'
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
    title: 'Hapus Cabang Event?',
    text: `Yakin ingin menghapus cabang "${item.branch_name}" dari event ini?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/event-branches/${item.id}`)
    Swal.fire('Terhapus', 'Cabang event berhasil dihapus.', 'success')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus event_branch:', error)
    Swal.fire('Gagal', 'Gagal menghapus cabang event.', 'error')
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
    text: 'Cabang event akan dibuat berdasarkan Master Branches. Cabang yang sudah ada tidak akan diduplikasi.',
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
    await axios.post('/api/v1/event-branches/generate-from-template', {
      event_id: eventId.value,
    })

    Swal.fire('Berhasil', 'Cabang event berhasil digenerate dari template.', 'success')
    fetchItems(1)
  } catch (error) {
    console.error('Gagal generate dari template:', error)
    let message = 'Gagal generate cabang dari template.'
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

// perPage change
watch(
  () => perPage.value,
  () => {
    fetchItems(1)
  }
)

// kalau eventData baru ter-set setelah halaman ini dibuka, auto load data
watch(
  () => eventId.value,
  (val) => {
    if (val) {
      fetchItems(1)
    }
  }
)

onMounted(() => {
  fetchBranches()
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
