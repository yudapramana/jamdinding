<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Manajemen Dewan Hakim</h1>
          <p class="mb-0 text-muted text-sm">
            Mengelola user khusus role <strong>DEWAN_HAKIM</strong> pada event aktif.
          </p>
        </div>

        <div>
          <button
            class="btn btn-sm btn-primary"
            :disabled="!eventId"
            @click="openCreateModal"
          >
            <i class="fas fa-plus mr-1"></i> Tambah Dewan Hakim
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
            <!-- LEFT -->
            <div class="d-flex flex-wrap align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-2">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <label class="mb-0 text-sm text-muted mr-3">Entri</label>

              <label class="mb-0 mr-1 text-sm text-muted">Status</label>
              <select v-model="filters.is_active" class="form-control form-control-sm w-auto mr-2">
                <option value="">Semua</option>
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
              </select>
            </div>

            <!-- RIGHT -->
            <div class="d-flex align-items-center mt-2 mt-sm-0">
              <input
                v-model="search"
                type="text"
                class="form-control form-control-sm w-auto"
                style="min-width: 280px"
                placeholder="Cari nama / username / email..."
              />
            </div>
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:50px">#</th>
                <th>Nama</th>
                <th style="width:200px">Username</th>
                <!-- <th style="width:240px">Email</th> -->
                <th style="width:110px" class="text-center">Status</th>
                <th style="width:150px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center py-4">Memuat data dewan hakim...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center py-4">
                  Belum ada user dewan hakim untuk event ini.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Tambah Dewan Hakim</strong> untuk menambahkan user.
                  </small>
                </td>
              </tr>

              <tr v-for="(u, index) in items" :key="u.id">
                <td class="text-center">{{ rowNumber(index) }}</td>

                <td>
                  <strong>{{ u.name }}</strong>
                  <div class="text-xs text-muted">
                    <span class="badge badge-light border mr-1">DEWAN_HAKIM</span>
                    <span v-if="u.can_multiple_role" class="badge badge-light border">multi-role</span>
                  </div>
                </td>

                <td>
                  <span class="badge badge-light border">{{ u.username }}</span>
                </td>

                <!-- <td>{{ u.email }}</td> -->

                <td class="text-center">
                  <span class="badge" :class="u.is_active ? 'badge-success' : 'badge-secondary'">
                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>

                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary btn-xs" title="Edit" @click="openEditModal(u)">
                      <i class="fas fa-edit"></i>
                    </button>

                    <button
                      class="btn btn-outline-warning btn-xs"
                      :title="u.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                      @click="toggleActive(u)"
                    >
                      <i class="fas" :class="u.is_active ? 'fa-user-slash' : 'fa-user-check'"></i>
                    </button>

                    <button class="btn btn-outline-danger btn-xs" title="Hapus" @click="confirmDelete(u)">
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
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total || 0 }} dewan hakim
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}</span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>

    <!-- MODAL CREATE/EDIT -->
    <div class="modal fade" id="judgeUserModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ form.id ? 'Edit Dewan Hakim' : 'Tambah Dewan Hakim' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>

          <div class="modal-body">
            <div class="alert alert-light border text-sm mb-3">
              User yang dibuat lewat halaman ini otomatis diberi role <strong>DEWAN_HAKIM</strong> dan
              diset ke <strong>event aktif</strong>.
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="text-sm text-muted mb-1">Nama</label>
                <input v-model.trim="form.name" type="text" class="form-control form-control-sm" placeholder="Nama lengkap" />
              </div>

              <div class="form-group col-md-6">
                <label class="text-sm text-muted mb-1">Username</label>
                <input v-model.trim="form.username" type="text" class="form-control form-control-sm" placeholder="Username unik" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="text-sm text-muted mb-1">Email</label>
                <input v-model.trim="form.email" type="email" class="form-control form-control-sm" placeholder="email@domain" />
              </div>

              <div class="form-group col-md-6">
                <label class="text-sm text-muted mb-1">
                  Password
                  <small class="text-muted">(kosongkan jika tidak ingin mengubah)</small>
                </label>
                <input v-model="form.password" type="password" class="form-control form-control-sm" placeholder="Password" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label class="text-sm text-muted mb-1">Status</label>
                <select v-model="form.is_active" class="form-control form-control-sm">
                  <option :value="true">Aktif</option>
                  <option :value="false">Nonaktif</option>
                </select>
              </div>

              <div class="form-group col-md-4">
                <label class="text-sm text-muted mb-1">Multi-role</label>
                <select v-model="form.can_multiple_role" class="form-control form-control-sm">
                  <option :value="true">Ya</option>
                  <option :value="false">Tidak</option>
                </select>
              </div>

              <div class="form-group col-md-4">
                <label class="text-sm text-muted mb-1">Event</label>
                <input class="form-control form-control-sm" :value="eventData?.event_name || '-'" disabled />
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
            <button class="btn btn-sm btn-primary" :disabled="saving || !eventId" @click="submitForm">
              <i class="fas fa-save mr-1"></i> Simpan
            </button>
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

// AUTH & EVENT
const authUserStore = useAuthUserStore()
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

// TABLE STATE
const items = ref([])
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)

const filters = ref({
  is_active: '',
})

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const rowNumber = (index) => index + 1 + (meta.value.current_page - 1) * meta.value.per_page

// FORM
const saving = ref(false)
const form = ref(resetForm())

function resetForm () {
  return {
    id: null,
    name: '',
    username: '',
    email: '',
    password: '',
    is_active: true,
    can_multiple_role: true,
  }
}

const openCreateModal = () => {
  form.value = resetForm()
  $('#judgeUserModal').modal('show')
}

const openEditModal = (u) => {
  form.value = {
    id: u.id,
    name: u.name || '',
    username: u.username || '',
    email: u.email || '',
    password: '',
    is_active: !!u.is_active,
    can_multiple_role: !!u.can_multiple_role,
  }
  $('#judgeUserModal').modal('show')
}

// API LIST
const fetchItems = async (page = 1) => {
  if (!eventId.value) return
  isLoading.value = true
  try {
    const res = await axios.get(`/api/v1/events/${eventId.value}/judges`, {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        is_active: filters.value.is_active,
      },
    })

    const paginated = res.data
    items.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat data dewan hakim.', 'error')
  } finally {
    isLoading.value = false
  }
}

// SUBMIT (CREATE/UPDATE)
const submitForm = async () => {
  if (!eventId.value) return

  const payload = {
    name: form.value.name,
    username: form.value.username,
    email: form.value.email,
    is_active: !!form.value.is_active,
    can_multiple_role: !!form.value.can_multiple_role,
    password: form.value.password || null, // null => backend abaikan
    event_id: eventId.value,              // selalu event aktif
  }

  // validasi ringan di frontend
  if (!payload.name || !payload.username || !payload.email) {
    Swal.fire('Validasi', 'Nama, username, dan email wajib diisi.', 'warning')
    return
  }

  saving.value = true
  try {
    if (!form.value.id) {
      await axios.post(`/api/v1/events/${eventId.value}/judges`, payload)
      Swal.fire('Berhasil', 'Dewan hakim berhasil ditambahkan.', 'success')
    } else {
      await axios.put(`/api/v1/judges/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Dewan hakim berhasil diperbarui.', 'success')
    }

    $('#judgeUserModal').modal('hide')
    fetchItems(meta.value.current_page || 1)
  } catch (e) {
    const msg = e?.response?.data?.message || 'Gagal menyimpan data.'
    Swal.fire('Gagal', msg, 'error')
  } finally {
    saving.value = false
  }
}

// TOGGLE ACTIVE
const toggleActive = async (u) => {
  const next = !u.is_active
  const ok = await Swal.fire({
    title: next ? 'Aktifkan user?' : 'Nonaktifkan user?',
    text: `${u.name} (${u.username})`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya',
    cancelButtonText: 'Batal',
  })

  if (!ok.isConfirmed) return

  try {
    await axios.patch(`/api/v1/judges/${u.id}/toggle-active`, { is_active: next })
    fetchItems(meta.value.current_page || 1)
  } catch (e) {
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal mengubah status.', 'error')
  }
}

// DELETE
const confirmDelete = async (u) => {
  const ok = await Swal.fire({
    title: 'Hapus user?',
    text: `${u.name} (${u.username})`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal',
  })

  if (!ok.isConfirmed) return

  try {
    await axios.delete(`/api/v1/judges/${u.id}`)
    Swal.fire('Berhasil', 'User berhasil dihapus.', 'success')
    fetchItems(1)
  } catch (e) {
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menghapus user.', 'error')
  }
}

// PAGINATION
const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchItems(page)
}

// WATCHERS
watch(() => search.value, useDebounceFn(() => fetchItems(1), 400))
watch(() => perPage.value, () => fetchItems(1))
watch(() => ({ ...filters.value }), () => fetchItems(1))
watch(() => eventId.value, (val) => { if (val) fetchItems(1) })

// MOUNTED
onMounted(() => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
  } else {
    fetchItems(1)
  }
})
</script>

<style scoped>
.btn-xs { padding: 2px 6px !important; font-size: 0.65rem !important; line-height: 1 !important; }
.btn-xs i { font-size: 0.55rem !important; }
.text-xs { font-size: 0.75rem; }
</style>
