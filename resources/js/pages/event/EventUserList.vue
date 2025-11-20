<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Daftar User Event</h1>
          <p class="text-sm text-muted mb-0" v-if="eventInfo">
            Event:
            <strong>{{ eventInfo.nama_event || eventInfo.name }}</strong>
            <span v-if="eventInfo.nama_aplikasi || eventInfo.app_name">
              — {{ eventInfo.nama_aplikasi || eventInfo.app_name }}
            </span>
            <br />
            <span class="text-xs">
              Tingkat:
              <strong>{{ eventInfo.tingkat_event || '-' }}</strong>
            </span>
          </p>
          <p class="text-sm text-danger mb-0" v-else>
            Event belum dipilih. Silakan kembali ke landing dan pilih event terlebih dahulu.
          </p>
        </div>

        <div class="btn-group">
          <!-- Tombol generate user otomatis -->
          <button
            class="btn btn-success btn-sm"
            v-if="eventId && canGenerateUsers"
            :disabled="isGenerating"
            @click="openGenerateModal"
          >
            <i v-if="isGenerating" class="fas fa-spinner fa-spin mr-1"></i>
            <i v-else class="fas fa-users-cog mr-1"></i>
            Generate User
          </button>

          <!-- Tombol tambah user manual -->
          <button
            class="btn btn-primary btn-sm"
            @click="openCreateModal"
            :disabled="!eventId"
          >
            <i class="fas fa-user-plus mr-1"></i>
            Tambah User
          </button>
        </div>

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
            placeholder="Cari nama, email, atau username..."
          />
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Nama</th>
                <!-- <th>Email</th> -->
                <th>Username</th>
                <th>Role</th>
                <th>Event</th>
                <th style="width: 80px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="users.length === 0">
                <td colspan="7" class="text-center">Tidak ada user ditemukan.</td>
              </tr>
              <tr v-for="(u, index) in users" :key="u.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td><strong>{{ u.name }}</strong></td>
                <!-- <td>{{ u.email }}</td> -->
                <td>{{ u.username }}</td>
                <td>
                  <span class="badge badge-info" v-if="u.role">
                    {{ u.role.name }}
                  </span>
                  <span class="text-muted" v-else>-</span>
                </td>
                <td>
                  <span v-if="u.event">
                    {{ u.event.nama_event || u.event.name || ('Event #' + u.event.id) }}
                  </span>
                  <span v-else class="text-muted">Global</span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-warning"
                      title="Edit"
                      @click="openEditModal(u)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-outline-danger"
                      title="Hapus"
                      @click="deleteUser(u)"
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
              {{ meta.total || 0 }} user
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page - 1)">
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
                <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page + 1)">
                  »
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Tambah/Edit User -->
    <div class="modal fade" id="eventUserModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="eventUserModalLabel">
              <i class="fas fa-user-edit mr-2"></i>
              {{ isEdit ? 'Edit User Event' : 'Tambah User Event' }}
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
                    <label class="mb-1">Nama Lengkap</label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Email</label>
                    <input
                      v-model="form.email"
                      type="email"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Username</label>
                    <input
                      v-model="form.username"
                      type="text"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">
                      Password
                      <small v-if="isEdit" class="text-muted">(kosongkan jika tidak diubah)</small>
                    </label>
                    <input
                      v-model="form.password"
                      type="password"
                      class="form-control form-control-sm"
                      :required="!isEdit"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="mb-1">Role</label>
                    <select
                      v-model="form.role_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="null">-- Pilih Role --</option>
                      <option
                        v-for="r in roleOptions"
                        :key="r.id"
                        :value="r.id"
                      >
                        {{ r.name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Event (otomatis dari context)</label>
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      :value="eventInfo ? (eventInfo.nama_event || ('Event #' + eventId)) : 'Global / Tidak terikat event'"
                      readonly
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Avatar (URL opsional)</label>
                    <input
                      v-model="form.avatar"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="https://..."
                    />
                  </div>
                </div>
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


    <!-- Modal Generate User -->
    <div class="modal fade" id="generateUserModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              <i class="fas fa-users-cog mr-2"></i>
              Generate User Otomatis
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <p class="text-sm mb-2" v-if="eventInfo">
              Event:
              <strong>{{ eventInfo.nama_event || eventInfo.name }}</strong><br>
              Tingkat:
              <strong>{{ eventInfo.tingkat_event }}</strong>
            </p>
            <p class="text-xs text-muted mb-3">
              Sistem akan membuat user untuk
              <span v-if="eventInfo && eventInfo.tingkat_event === 'provinsi'">
                setiap <strong>kabupaten/kota</strong> pada provinsi ini.
              </span>
              <span v-else-if="eventInfo && eventInfo.tingkat_event === 'kabupaten_kota'">
                setiap <strong>kecamatan</strong> pada kabupaten/kota ini.
              </span>
            </p>

            <form @submit.prevent="submitGenerateUsers">
              <div class="form-group mb-2">
                <label class="mb-1">Role untuk User Hasil Generate</label>
                <select
                  v-model="generateForm.role_id"
                  class="form-control form-control-sm"
                  required
                >
                  <option :value="null">-- Pilih Role --</option>
                  <option
                    v-for="r in roleOptions"
                    :key="r.id"
                    :value="r.id"
                  >
                    {{ r.name }}
                  </option>
                </select>
              </div>

              <div class="form-group mb-2">
                <label class="mb-1">
                  Default Password
                  <small class="text-muted">
                    (opsional, default: <strong>event_key</strong>)
                  </small>
                </label>
                <input
                  v-model="generateForm.default_password"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Kosongkan untuk pakai event_key"
                />
              </div>

              <div class="form-group mb-2">
                <label class="mb-1">
                  Email Domain
                  <small class="text-muted">
                    (opsional, default: <code>mtq.local</code>)
                  </small>
                </label>
                <input
                  v-model="generateForm.email_domain"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="misal: mtq.sumbar, mtq.kemenag.go.id"
                />
              </div>

              <div class="alert alert-warning text-xs py-2 mb-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                User yang sudah ada dengan kombinasi <strong>event_id + username</strong>
                tidak akan dibuat ulang (akan di-skip).
              </div>

              <div class="text-right mt-3">
                <button
                  type="button"
                  class="btn btn-sm btn-secondary mr-2"
                  data-dismiss="modal"
                  :disabled="isGenerating"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  class="btn btn-sm btn-success"
                  :disabled="isGenerating || !generateForm.role_id"
                >
                  <i v-if="isGenerating" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-play mr-1"></i>
                  Generate
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
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import Swal from 'sweetalert2';

const authUserStore = useAuthUserStore()

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
const isSubmitting = ref(false)
const isEdit = ref(false)
const form = ref({})

const roleOptions = ref([])

// Event context dari localStorage / cookie
const eventInfo = ref(null)
const eventId = ref(null)

// state untuk generate user otomatis
const isGenerating = ref(false)

const generateForm = ref({
  role_id: null,
  default_password: '',
  email_domain: '',
})

const openGenerateModal = () => {
  if (!eventId.value || !eventInfo.value) {
    alert('Event belum terdeteksi. Tidak dapat generate user.')
    return
  }

  if (!canGenerateUsers.value) {
    alert('Generate user hanya bisa untuk event tingkat provinsi atau kabupaten_kota.')
    return
  }

  // reset form
  generateForm.value = {
    role_id: null,
    default_password: '',
    email_domain: '',
  }

  $('#generateUserModal').modal('show')
}



// bisa generate user kalau tingkat_event provinsi atau kabupaten_kota
const canGenerateUsers = computed(() => {
  if (!eventInfo.value) return false
  const level = eventInfo.value.tingkat_event
  return level === 'provinsi' || level === 'kabupaten_kota'
})

const getEventInfoFromStorage = () => {
  let raw = ''

  try {
    raw = localStorage.getItem('event_data') || ''
  } catch (e) {}

  if (!raw) {
    const cookie = document.cookie
      .split('; ')
      .find(row => row.startsWith('event_data='))
    if (cookie) {
      raw = decodeURIComponent(cookie.split('=')[1])
    }
  }

  if (raw) {
    try {
      eventInfo.value = JSON.parse(raw)
      eventId.value = eventInfo.value.id || null
    } catch (e) {
      console.error('Gagal parse event_data:', e)
      eventInfo.value = null
      eventId.value = null
    }
  }
}

const fetchRoles = async () => {
  try {
    const res = await axios.get('/api/v1/roles')
    roleOptions.value = res.data
  } catch (error) {
    console.error('Gagal memuat roles:', error)
  }
}

const fetchUsers = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/users', {
      params: {
        page,
        search: search.value,
        event_id: eventId.value,
      },
    })

    users.value = res.data.data || []
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
      console.error('Gagal memuat data user:', error)
    }
  } finally {
    isLoading.value = false
  }
}

// === GENERATE USER OTOMATIS BERDASARKAN tingkat_event ===
const submitGenerateUsers = async () => {
  if (!eventId.value || !eventInfo.value) return

  if (!generateForm.value.role_id) {
    alert('Silakan pilih role terlebih dahulu untuk user hasil generate.')
    return
  }

  const level = eventInfo.value.tingkat_event
  let msg = 'Generate user otomatis untuk event ini?'

  if (level === 'provinsi') {
    msg =
      'Generate user untuk SEMUA kabupaten/kota pada provinsi ini?\n' +
      'Nama & username user akan dibentuk dari: KODE_EVENT + nama kab/kota.\n' +
      'Password & domain email mengikuti pengaturan di form.'
  } else if (level === 'kabupaten_kota') {
    msg =
      'Generate user untuk SEMUA kecamatan pada kabupaten/kota ini?\n' +
      'Nama & username user akan dibentuk dari: KODE_EVENT + nama kecamatan.\n' +
      'Password & domain email mengikuti pengaturan di form.'
  }

  if (!confirm(msg)) return

  isGenerating.value = true
  try {
    const payload = {
      role_id: generateForm.value.role_id,
    }

    if (generateForm.value.default_password) {
      payload.default_password = generateForm.value.default_password
    }
    if (generateForm.value.email_domain) {
      payload.email_domain = generateForm.value.email_domain
    }

    const res = await axios.post(`/api/v1/events/${eventId.value}/generate-users`, payload)

    $('#generateUserModal').modal('hide')

    Swal.fire({
            icon: 'success',
            title: 'Generate User Berhasil',
            showConfirmButton: false,
            timer: 2000
        });


    // alert(res.data?.message || 'Generate user berhasil.')
    await fetchUsers(meta.value.current_page)
  } catch (error) {
    console.error('Gagal generate user:', error)
    alert(error.response?.data?.message || 'Gagal generate user otomatis.')
  } finally {
    isGenerating.value = false
  }
}


const openCreateModal = () => {
  if (!eventId.value) {
    if (!confirm('Event belum terdeteksi. Tambah user sebagai global (tanpa event)?')) {
      return
    }
  }

  isEdit.value = false
  form.value = {
    id: null,
    name: '',
    email: '',
    username: '',
    password: '',
    role_id: null,
    avatar: '',
    event_id: eventId.value, // boleh null untuk global
  }

  $('#eventUserModal').modal('show')
}

const openEditModal = (u) => {
  isEdit.value = true
  form.value = {
    id: u.id,
    name: u.name,
    email: u.email,
    username: u.username,
    password: '',
    role_id: u.role_id || (u.role ? u.role.id : null),
    avatar: u.avatar || '',
    event_id: u.event_id,
  }

  $('#eventUserModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    name: form.value.name,
    email: form.value.email,
    username: form.value.username,
    role_id: form.value.role_id,
    avatar: form.value.avatar,
    event_id: eventId.value ?? form.value.event_id ?? null,
  }

  if (form.value.password) {
    payload.password = form.value.password
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/users/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/users', payload)
    }

    $('#eventUserModal').modal('hide')
    await fetchUsers(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan user:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan data user.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteUser = async (u) => {
  if (!confirm(`Yakin ingin menghapus user "${u.name}"?`)) return

  try {
    await axios.delete(`/api/v1/users/${u.id}`)
    await fetchUsers(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus user:', error)
    alert(error.response?.data?.message || 'Gagal menghapus user.')
  }
}

watch(
  search,
  useDebounceFn(() => {
    fetchUsers(1)
  }, 400)
)

onMounted(() => {
  getEventInfoFromStorage()
  fetchRoles()
  fetchUsers()
})
</script>
