<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Daftar Pengelola</h1>
        <div class="btn-group">
          <!-- <button class="btn btn-outline-secondary btn-sm" @click="toggleSelectAll">
            <i class="fas fa-check-square mr-1"></i>{{ allSelected ? 'Batal Pilih Semua' : 'Pilih Semua' }}
          </button>
          <button class="btn btn-danger btn-sm" :disabled="selectedIds.length === 0" @click="bulkDelete">
            <i class="fas fa-trash mr-1"></i>Hapus Terpilih ({{ selectedIds.length }})
          </button> -->
          <button
            class="btn btn-primary btn-sm"
            @click="openCreateModal"
            :disabled="authUserStore.user?.role !== 'SUPERADMIN'"
            title="Hanya SUPERADMIN yang bisa mempromosikan role"
          >
            + Tambah Pengelola
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
            placeholder="Cari nama, email, NIP, atau unit kerja..."
          />
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width: 36px;">
                  <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" />
                </th>
                <th style="width: 40px;">#</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <!-- <th style="width: 80px;">Aksi</th> -->
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="displayedUsers.length === 0">
                <td colspan="7" class="text-center">Tidak ada data Pengelola ditemukan.</td>
              </tr>

              <AdminRow
                v-else
                v-for="(user, index) in displayedUsers"
                :key="user.id"
                :user="decorateUser(user)"
                :index="index + offsetIndex"
                :select-all="allSelected || selectedIds.includes(user.id)"
                @toggleSelection="onToggleSelection"
                @editUser="openEditModal"
                @confirmUserDeletion="confirmUserDeletion"
                @userDeleted="refreshAfterChildUpdate"
              />
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
              Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} pengguna (role ≠ "User")
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: Promosi Role dari User yang Ada -->
    <div class="modal fade" id="adminPromoteModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              <i class="fas fa-user-shield me-2"></i>Tambah Pengelola (Promosi Role)
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <div class="form-group mb-2">
              <label class="mb-1">Cari User</label>
              <input
                v-model="candidateSearch"
                type="text"
                class="form-control form-control-sm"
                placeholder="Ketik nama atau email…"
              />
              <small class="text-muted">Hanya SUPERADMIN yang bisa mempromosikan role.</small>
            </div>

            <div class="table-responsive border rounded">
              <table class="table table-sm m-0">
                <thead>
                  <tr>
                    <th style="width: 36px;"></th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role Saat Ini</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isSearching">
                    <td colspan="4" class="text-center">Mencari…</td>
                  </tr>
                  <tr v-else-if="candidates.length === 0">
                    <td colspan="4" class="text-center">Belum ada hasil. Ketik kata kunci di atas.</td>
                  </tr>
                  <tr
                    v-for="u in candidates"
                    :key="u.id"
                    :class="{ 'table-active': selectedCandidateId === u.id }"
                    style="cursor: pointer"
                    @click="selectedCandidateId = u.id"
                  >
                    <td>
                      <input
                        type="radio"
                        name="candidate"
                        :value="u.id"
                        v-model="selectedCandidateId"
                        @click.stop
                      />
                    </td>
                    <td>
                      <strong>{{ u.employee?.full_name || u.name }}</strong>
                      <div class="text-muted" style="font-size: 12px;">
                        {{ u.employee?.job_title || '-' }} • {{ u.employee?.work_unit?.unit_name || '-' }}
                      </div>
                    </td>
                    <td>{{ u.email }}</td>
                    <td>{{ (u.role && String(u.role)) || (u.roles?.[0]?.name || '-') }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="form-group mt-3">
              <label class="mb-1">Role Target</label>
              <select
                class="form-control form-control-sm"
                v-model="selectedRoleValue"
                :disabled="authUserStore.user?.role !== 'SUPERADMIN'"
              >
                <option disabled value="">— Pilih role —</option>
                <option :value="ROLE_MAP.SUPERADMIN">SUPERADMIN</option>
                <option :value="ROLE_MAP.ADMIN">ADMIN</option>
                <option :value="ROLE_MAP.REVIEWER">REVIEWER</option>
              </select>
              <small class="text-muted">USER tidak tersedia di sini karena fokusnya promosi.</small>
            </div>
          </div>

          <div class="modal-footer py-2">
            <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Tutup</button>
            <button
              type="button"
              class="btn btn-primary btn-sm"
              :disabled="!selectedCandidateId || !selectedRoleValue || isPromoting"
              @click="promoteRole"
            >
              <i v-if="isPromoting" class="fas fa-spinner fa-spin mr-1"></i>
              Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- /MODAL -->
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import AdminRow from './AdminRow.vue'
import { useMasterDataStore } from '../../stores/MasterDataStore.js'
import { useAuthUserStore } from '../../stores/AuthUserStore.js'
import { useTableIndexStore } from "../../stores/TableIndexStore"
import { useToastr } from "../../toastr.js"
import { formatDate } from '../../helper.js'

const masterDataStore = useMasterDataStore()
const authUserStore = useAuthUserStore()
const tableIndexStore = useTableIndexStore()
const toastr = useToastr()

const users = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1
})
const search = ref('')
const isLoading = ref(false)
const isEdit = ref(false) // masih dipakai jika kamu tetap ingin fitur edit lama
const isSubmitting = ref(false)
const form = ref({})
const selectedIds = ref([])
const allSelected = ref(false)

// ====== KONST MAP ROLE (samakan dengan backend/enum) ======
const ROLE_MAP = {
  SUPERADMIN: 1,
  ADMIN: 2,
  USER: 3,
  REVIEWER: 4
}

// ====== MODAL PROMOSI ROLE ======
const candidateSearch = ref('')
const isSearching = ref(false)
const candidates = ref([])
const selectedCandidateId = ref(null)
const selectedRoleValue = ref('')
const isPromoting = ref(false)

const fetchCandidates = async () => {
  if (!candidateSearch.value || candidateSearch.value.trim().length < 2) {
    candidates.value = []
    return
  }
  isSearching.value = true
  try {
    const { data } = await axios.get('/api/users', {
      params: {
        search: candidateSearch.value,
        per_page: 10 // batasi agar ringan
      }
    })
    candidates.value = data?.data || []
  } catch (e) {
    toastr.error('Gagal mencari user')
  } finally {
    isSearching.value = false
  }
}

const debouncedFetchCandidates = useDebounceFn(fetchCandidates, 350)

watch(candidateSearch, () => debouncedFetchCandidates())

const promoteRole = async () => {
  if (!selectedCandidateId.value || !selectedRoleValue.value) return
  if (authUserStore.user?.role !== 'SUPERADMIN') {
    toastr.error('Hanya SUPERADMIN yang dapat mempromosikan role')
    return
  }
  isPromoting.value = true
  try {
    await axios.patch(`/api/users/${selectedCandidateId.value}/change-role`, {
      role: selectedRoleValue.value
    })
    toastr.success('Role berhasil dipromosikan')
    // refresh tabel utama
    await fetchUsers(meta.value.current_page)
    // reset dan tutup modal
    selectedCandidateId.value = null
    selectedRoleValue.value = ''
    candidateSearch.value = ''
    candidates.value = []
    $('#adminPromoteModal').modal('hide')
  } catch (e) {
    toastr.error(e?.response?.data?.message || 'Gagal mempromosikan role')
  } finally {
    isPromoting.value = false
  }
}

// ====== DATA UTAMA LIST ADMIN/OP ======
const fetchUsers = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/users', {
      params: {
        page,
        search: search.value,
        exclude_role: 'User'
      }
    })
    users.value = response.data.data
    meta.value = {
      ...meta.value,
      ...response.data.meta,
      ...response.data
    }
    tableIndexStore.setFrom(meta.value.from || 0)
    selectedIds.value = []
    allSelected.value = false
  } catch (error) {
    if (error.response && error.response.status === 401) {
      console.warn('Unauthorized. Logging out...')
      authUserStore.logout()
    } else {
      console.error('Gagal memuat data Pengelola:', error)
    }
  } finally {
    isLoading.value = false
  }
}

const displayedUsers = computed(() => {
  // fallback filter non-User bila backend belum memfilter
  return (users.value || []).filter(u => {
    const roles = u.roles || []
    // bila pakai kolom langsung (enum int) `u.role`, maka:
    if (u.role != null) {
      return Number(u.role) !== ROLE_MAP.USER
    }
    return !roles.some(r => (r?.name || '').toLowerCase() === 'user')
  })
})

const offsetIndex = computed(() =>
  (meta.value.current_page - 1) * (meta.value.per_page || 10)
)

const decorateUser = (u) => {
  return {
    ...u,
    name: u.employee?.full_name || u.name,
    jabatan: u.employee?.job_title || '-',
    org_name: u.employee?.work_unit?.unit_name || '-',
    formatted_created_at: u.formatted_created_at || formatDate(u.created_at, 'DD/MM/YYYY'),
    role: u.role // tampilkan apa adanya
    // role: u.roles?.[0]?.name || u.role // tampilkan apa adanya
  }
}

const openCreateModal = () => {
  // BUKA MODAL PROMOSI ROLE
  selectedCandidateId.value = null
  selectedRoleValue.value = ''
  candidateSearch.value = ''
  candidates.value = []
  $('#adminPromoteModal').modal('show')
}

// (opsional) masih disediakan jika kamu masih pakai edit lama
const openEditModal = (user) => {
  isEdit.value = true
  form.value = {
    ...user,
    ...user.employee,
    id_work_unit: user.employee?.id_work_unit,
    role_names: (user.roles || []).map(r => r.name)
  }
  // $('#adminEditModal').modal('show') // jika masih ada modal edit lama
}

const confirmUserDeletion = async (id) => {
  if (!confirm('Yakin ingin menghapus pengguna ini?')) return
  try {
    await axios.delete(`/api/users/${id}`)
    toastr.success('Pengguna berhasil dihapus')
    if (displayedUsers.value.length === 1 && meta.value.current_page > 1) {
      await fetchUsers(meta.value.current_page - 1)
    } else {
      await fetchUsers(meta.value.current_page)
    }
  } catch (error) {
    toastr.error('Gagal menghapus pengguna')
  }
}

const bulkDelete = async () => {
  if (selectedIds.value.length === 0) return
  if (!confirm(`Hapus ${selectedIds.value.length} pengguna terpilih?`)) return
  try {
    await axios.post('/api/users/bulk-delete', { ids: selectedIds.value })
    toastr.success('Pengguna terpilih berhasil dihapus')
    await fetchUsers(meta.value.current_page)
  } catch (e) {
    toastr.error(e?.response?.data?.message || 'Gagal menghapus massal')
  }
}

const toggleSelectAll = () => {
  allSelected.value = !allSelected.value
  if (allSelected.value) {
    selectedIds.value = displayedUsers.value.map(u => u.id)
  } else {
    selectedIds.value = []
  }
}

const onToggleSelection = (user) => {
  const i = selectedIds.value.indexOf(user.id)
  if (i >= 0) selectedIds.value.splice(i, 1)
  else selectedIds.value.push(user.id)
  allSelected.value = selectedIds.value.length === displayedUsers.value.length
}

const refreshAfterChildUpdate = () => {
  fetchUsers(meta.value.current_page)
}

watch(search, useDebounceFn(() => fetchUsers(1), 400))

onMounted(() => {
  fetchUsers()
  masterDataStore.getWorkunitList()
})
</script>
