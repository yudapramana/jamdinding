<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Golongan per Event (Event Groups)</h1>
          <p class="mb-0 text-muted text-sm">
            Mengatur golongan (group) yang diaktifkan pada event terpilih.
            Data ini dapat digenerate dari Master Groups lalu disesuaikan.
          </p>

          <p v-if="selectedBranchId" class="mb-0 mt-1 text-sm text-muted">
            Filter: hanya menampilkan golongan untuk cabang
            <strong>
                {{
                branches.find(b => b.id === selectedBranchId)?.name || 'ID: ' + selectedBranchId
                }}
            </strong>
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
            + Tambah Golongan Event
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
              placeholder="Cari cabang / golongan..."
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
                <th>Nama Lengkap</th>
                <th style="width: 80px;" class="text-center">Maks. Umur</th>
                <th style="width: 90px;" class="text-center">Status</th>
                <th style="width: 80px;" class="text-center">Tim?</th>
                <th style="width: 80px;" class="text-center">Urutan</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="9" class="text-center">Memuat data event groups...</td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="9" class="text-center">
                  Belum ada golongan terdaftar untuk event ini.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Generate dari Template</strong> atau <strong>Tambah Golongan Event</strong>.
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
                <td>{{ item.full_name }}</td>
                <td class="text-center">{{ item.max_age ?? '-' }}</td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="item.status === 'active' ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ item.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="item.is_team ? 'badge-info' : 'badge-secondary'"
                  >
                    {{ item.is_team ? 'Tim' : 'Individu' }}
                  </span>
                </td>
                <td class="text-center">{{ item.order_number ?? '-' }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button
                        class="btn btn-info"
                        title="Kelola Kategori untuk Golongan ini"
                        @click="goToEventCategories(item)"
                        >
                        <i class="fas fa-venus-mars"></i>
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
              {{ meta.total || 0 }} golongan event
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

    <!-- MODAL TAMBAH / EDIT EVENT GROUP -->
    <div
      class="modal fade"
      id="eventGroupModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="eventGroupModalLabel"
    >
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h5 class="modal-title" id="eventGroupModalLabel">
              <i class="fas fa-users mr-1"></i>
              {{ isEdit ? 'Edit Golongan Event' : 'Tambah Golongan Event' }}
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
                    <label class="mb-1">Nama Lengkap (Full Name)</label>
                    <input
                      v-model="form.full_name"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Hafalan 10 Juz - Remaja"
                    />
                    <small class="text-muted">
                      Jika dikosongkan, akan digenerate dari nama cabang + golongan.
                    </small>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1">Maksimal Umur</label>
                    <input
                      v-model.number="form.max_age"
                      type="number"
                      min="0"
                      class="form-control form-control-sm"
                    />
                    <small class="text-muted">
                      0 berarti tidak dibatasi umur.
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
                    <label class="mb-1">Jenis</label>
                    <select
                      v-model="form.is_team"
                      class="form-control form-control-sm"
                    >
                      <option :value="false">Individu</option>
                      <option :value="true">Tim</option>
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
import { useRoute, useRouter } from 'vue-router' // 👈

const authUserStore = useAuthUserStore()

const route = useRoute()   // 👈
const router = useRouter() // 👈

const selectedBranchId = computed(() => {
  const raw = route.query.branch_id
  return raw ? Number(raw) : null
})


// event aktif dari store
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

const items = ref([])
const branches = ref([])
const groups = ref([])

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
  full_name: '',
  max_age: 0,
  status: 'active',
  is_team: false,
  order_number: null,
})

const fetchBranchesAndGroups = async () => {
  try {
    const [resBranches, resGroups] = await Promise.all([
      axios.get('/api/v1/branches', { params: { simple: 1 } }),
      axios.get('/api/v1/groups', { params: { simple: 1 } }),
    ])

    branches.value = resBranches.data.data || []
    groups.value = resGroups.data.data || []
  } catch (error) {
    console.error('Gagal memuat branches / groups:', error)
    Swal.fire('Gagal', 'Gagal memuat daftar cabang dan golongan.', 'error')
  }
}

const goToEventCategories = (item) => {
  router.push({
    name: 'admin.event.categories',
    query: {
      branch_id: item.branch_id,
      group_id: item.group_id,
    },
  })
}

const fetchItems = async (page = 1) => {
  if (!eventId.value) {
    return
  }

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-groups', {
      params: {
        event_id: eventId.value,
        branch_id: selectedBranchId.value || undefined, // 👈 filter optional
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
    console.error('Gagal memuat event_groups:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    } else {
      Swal.fire('Gagal', 'Gagal memuat data event groups.', 'error')
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
      'Silakan pilih event melalui Portal Event sebelum mengelola golongan.',
      'warning'
    )
    return
  }

  isEdit.value = false
  form.value = {
    id: null,
    branch_id: '',
    group_id: '',
    full_name: '',
    max_age: 0,
    status: 'active',
    is_team: false,
    order_number: (meta.value.total || 0) + 1,
  }
  $('#eventGroupModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    branch_id: item.branch_id,
    group_id: item.group_id,
    full_name: item.full_name,
    max_age: item.max_age,
    status: item.status,
    is_team: !!item.is_team,
    order_number: item.order_number,
  }
  $('#eventGroupModal').modal('show')
}

const submitForm = async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum mengelola golongan.',
      'warning'
    )
    return
  }

  isSubmitting.value = true

  const payload = {
    event_id: eventId.value,
    branch_id: form.value.branch_id,
    group_id: form.value.group_id,
    full_name: form.value.full_name,
    max_age: form.value.max_age,
    status: form.value.status,
    is_team: form.value.is_team,
    order_number: form.value.order_number,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-groups/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Golongan event berhasil diperbarui.', 'success')
    } else {
      await axios.post('/api/v1/event-groups', payload)
      Swal.fire('Berhasil', 'Golongan event berhasil ditambahkan.', 'success')
    }

    $('#eventGroupModal').modal('hide')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan event_group:', error)
    let message = 'Gagal menyimpan data golongan event.'
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
    title: 'Hapus Golongan Event?',
    text: `Yakin ingin menghapus golongan "${item.full_name}" dari event ini?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/event-groups/${item.id}`)
    Swal.fire('Terhapus', 'Golongan event berhasil dihapus.', 'success')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus event_group:', error)
    Swal.fire('Gagal', 'Gagal menghapus golongan event.', 'error')
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
    text: 'Golongan event akan dibuat berdasarkan Master Groups. Kombinasi yang sudah ada tidak akan diduplikasi.',
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
    await axios.post('/api/v1/event-groups/generate-from-template', {
      event_id: eventId.value,
    })

    Swal.fire('Berhasil', 'Golongan event berhasil digenerate dari template.', 'success')
    fetchItems(1)
  } catch (error) {
    console.error('Gagal generate dari template (event_groups):', error)
    let message = 'Gagal generate golongan dari template.'
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
  fetchBranchesAndGroups()
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
