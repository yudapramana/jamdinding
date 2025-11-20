<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Pengaturan Hak Akses Role</h1>
        <p class="mb-0 text-muted text-sm">
          Pilih role lalu atur menu / permission yang boleh diakses.
        </p>
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
            placeholder="Cari nama role atau slug..."
          />
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Nama Role</th>
                <th>Slug</th>
                <th style="width: 80px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoadingRoles">
                <td colspan="4" class="text-center">Memuat data role...</td>
              </tr>
              <tr v-else-if="filteredRoles.length === 0">
                <td colspan="4" class="text-center">Tidak ada role ditemukan.</td>
              </tr>
              <tr
                v-for="(role, index) in filteredRoles"
                :key="role.id"
              >
                <td>{{ index + 1 }}</td>
                <td>{{ role.name }}</td>
                <td><code>{{ role.slug }}</code></td>
                <td class="text-center">
                  <button
                    class="btn btn-sm btn-outline-success"
                    title="Atur Permission"
                    @click="openPermissionModal(role)"
                  >
                    <i class="fas fa-sliders-h"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>

    <!-- MODAL SETTING MENU / PERMISSION -->
    <div class="modal fade" id="rolePermissionModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2" style="background-color:#28a745;color:#fff;">
            <h5 class="modal-title mb-0">
              <strong>Setting Menu Event</strong>
              <span v-if="currentRole" class="d-block text-sm">
                Role: {{ currentRole.name }} ({{ currentRole.slug }})
              </span>
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span style="color:#fff;">&times;</span>
            </button>
          </div>

          <div class="modal-body" style="background:#f4f6f9;">
            <div v-if="isLoadingModal" class="text-center my-3">
              <i class="fas fa-spinner fa-spin mr-1"></i> Memuat permission...
            </div>

            <div v-else>
              <!-- Beranda (jika ada permission group 'dashboard') -->
              <div v-if="groupedPermissions.dashboard && groupedPermissions.dashboard.permissions.length"
                   class="card mb-3 border-0 shadow-sm">
                <div class="card-header py-2 bg-danger text-white d-flex align-items-center">
                  <div class="custom-control custom-checkbox mb-0">
                    <!-- contoh: centang semua permission di group dashboard -->
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      id="group-dashboard"
                      :checked="isGroupFullyChecked('dashboard')"
                      @change="toggleGroup('dashboard', $event.target.checked)"
                    />
                    <label class="custom-control-label font-weight-bold" for="group-dashboard">
                      Beranda
                    </label>
                  </div>
                </div>
                <div class="card-body py-2">
                  <div
                    v-for="perm in groupedPermissions.dashboard.permissions"
                    :key="perm.id"
                    class="custom-control custom-checkbox mb-1"
                  >
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      :id="'perm-'+perm.id"
                      :value="perm.id"
                      v-model="selectedPermissionIds"
                    />
                    <label class="custom-control-label" :for="'perm-'+perm.id">
                      {{ perm.name }}
                      <small class="text-muted">({{ perm.slug }})</small>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Master Data -->
              <div v-if="groupedPermissions.master && groupedPermissions.master.permissions.length"
                   class="card mb-3 border-0 shadow-sm">
                <div class="card-header py-2 bg-danger text-white d-flex align-items-center">
                  <div class="custom-control custom-checkbox mb-0">
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      id="group-master"
                      :checked="isGroupFullyChecked('master')"
                      @change="toggleGroup('master', $event.target.checked)"
                    />
                    <label class="custom-control-label font-weight-bold" for="group-master">
                      Master Data
                    </label>
                  </div>
                </div>
                <div class="card-body py-2">
                  <div
                    v-for="perm in groupedPermissions.master.permissions"
                    :key="perm.id"
                    class="custom-control custom-checkbox mb-1"
                  >
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      :id="'perm-'+perm.id"
                      :value="perm.id"
                      v-model="selectedPermissionIds"
                    />
                    <label class="custom-control-label" :for="'perm-'+perm.id">
                      {{ perm.name }}
                      <small class="text-muted">({{ perm.slug }})</small>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Master / Manage Event -->
              <div v-if="groupedPermissions.event && groupedPermissions.event.permissions.length"
                   class="card mb-3 border-0 shadow-sm">
                <div class="card-header py-2 bg-danger text-white d-flex align-items-center">
                  <div class="custom-control custom-checkbox mb-0">
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      id="group-event"
                      :checked="isGroupFullyChecked('event')"
                      @change="toggleGroup('event', $event.target.checked)"
                    />
                    <label class="custom-control-label font-weight-bold" for="group-event">
                      Master Acara / Event
                    </label>
                  </div>
                </div>
                <div class="card-body py-2">
                  <div
                    v-for="perm in groupedPermissions.event.permissions"
                    :key="perm.id"
                    class="custom-control custom-checkbox mb-1"
                  >
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      :id="'perm-'+perm.id"
                      :value="perm.id"
                      v-model="selectedPermissionIds"
                    />
                    <label class="custom-control-label" :for="'perm-'+perm.id">
                      {{ perm.name }}
                      <small class="text-muted">({{ perm.slug }})</small>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Group lain (opsional) -->
              <div
                v-for="group in otherGroups"
                :key="group.key"
                class="card mb-3 border-0 shadow-sm"
              >
                <div class="card-header py-2 bg-danger text-white d-flex align-items-center">
                  <div class="custom-control custom-checkbox mb-0">
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      :id="'group-'+group.key"
                      :checked="isGroupFullyChecked(group.key)"
                      @change="toggleGroup(group.key, $event.target.checked)"
                    />
                    <label class="custom-control-label font-weight-bold" :for="'group-'+group.key">
                      {{ group.title }}
                    </label>
                  </div>
                </div>
                <div class="card-body py-2">
                  <div
                    v-for="perm in group.permissions"
                    :key="perm.id"
                    class="custom-control custom-checkbox mb-1"
                  >
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      :id="'perm-'+perm.id"
                      :value="perm.id"
                      v-model="selectedPermissionIds"
                    />
                    <label class="custom-control-label" :for="'perm-'+perm.id">
                      {{ perm.name }}
                      <small class="text-muted">({{ perm.slug }})</small>
                    </label>
                  </div>
                </div>
              </div>

              <p class="text-muted text-sm mb-0">
                Centang menu yang boleh diakses oleh role
                <strong>{{ currentRole?.name }}</strong>.
              </p>
            </div>
          </div>

          <div class="modal-footer py-2">
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              data-dismiss="modal"
            >
              Batal
            </button>
            <button
              type="button"
              class="btn btn-success btn-sm"
              :disabled="isSaving"
              @click="savePermissions"
            >
              <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
              <i v-else class="fas fa-save mr-1"></i>
              Simpan Pengaturan
            </button>
          </div>

        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import Swal from 'sweetalert2';

const authUserStore = useAuthUserStore()

// ====== STATE ROLE LIST ======
const roles = ref([])
const isLoadingRoles = ref(false)
const search = ref('')

// ====== STATE MODAL ======
const currentRole = ref(null)
const isLoadingModal = ref(false)
const isSaving = ref(false)

const allPermissions = ref([])           // semua permission dari /permissions-simple
const rolePermissionPivots = ref([])     // data pivot permission_role utk role aktif
const selectedPermissionIds = ref([])    // array of permission_id yg dicentang

// ====== FETCH ROLES ======
const fetchRoles = async () => {
  isLoadingRoles.value = true
  try {
    const res = await axios.get('/api/v1/roles-simple')
    roles.value = res.data || []
  } catch (error) {
    console.error('Gagal memuat roles:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    isLoadingRoles.value = false
  }
}

// search lokal
const filteredRoles = computed(() => {
  if (!search.value) return roles.value
  const q = search.value.toLowerCase()
  return roles.value.filter(r =>
    (r.name && r.name.toLowerCase().includes(q)) ||
    (r.slug && r.slug.toLowerCase().includes(q))
  )
})

watch(
  search,
  useDebounceFn(() => {}, 300) // hanya filter client-side
)

// ====== FETCH PERMISSIONS (sekali di awal) ======
const fetchPermissions = async () => {
  try {
    const res = await axios.get('/api/v1/permissions-simple')
    allPermissions.value = res.data || []
  } catch (error) {
    console.error('Gagal memuat permissions:', error)
  }
}

// ====== GROUPING PERMISSION BY PREFIX SLUG ======
const groupedPermissions = computed(() => {
  const groups = {
    dashboard: { key: 'dashboard', title: 'Beranda', permissions: [] },
    master:    { key: 'master',    title: 'Master Data', permissions: [] },
    event:     { key: 'event',     title: 'Master Acara / Event', permissions: [] },
    other:     { key: 'other',     title: 'Lainnya', permissions: [] },
  }

  allPermissions.value.forEach(p => {
    const prefix = (p.slug || '').split('.')[0]

    if (prefix === 'master') {
      groups.master.permissions.push(p)
    } else if (prefix === 'event') {
      groups.event.permissions.push(p)
    } else if (prefix === 'dashboard') {
      groups.dashboard.permissions.push(p)
    } else {
      groups.other.permissions.push(p)
    }
  })

  return groups
})

// untuk looping group lain
const otherGroups = computed(() => {
  return Object.values(groupedPermissions.value).filter(
    g => g.key === 'other' && g.permissions.length
  )
})

// helper group check
const isGroupFullyChecked = (groupKey) => {
  const group = groupedPermissions.value[groupKey]
  if (!group || !group.permissions.length) return false
  return group.permissions.every(p => selectedPermissionIds.value.includes(p.id))
}

const toggleGroup = (groupKey, checked) => {
  const group = groupedPermissions.value[groupKey]
  if (!group) return

  const ids = group.permissions.map(p => p.id)

  if (checked) {
    // tambahkan semua jika belum ada
    selectedPermissionIds.value = Array.from(new Set([
      ...selectedPermissionIds.value,
      ...ids,
    ]))
  } else {
    // hilangkan semua dari group
    selectedPermissionIds.value = selectedPermissionIds.value.filter(
      id => !ids.includes(id)
    )
  }
}

// ====== OPEN MODAL: AMBIL PERMISSIONS ROLE ======
const openPermissionModal = async (role) => {
  currentRole.value = role
  isLoadingModal.value = true
  selectedPermissionIds.value = []
  rolePermissionPivots.value = []

  $('#rolePermissionModal').modal('show')

  try {
    // 1) pastikan permission sudah ada
    if (!allPermissions.value.length) {
      await fetchPermissions()
    }

    // 2) ambil pivot permission_role untuk role ini
    const res = await axios.get('/api/v1/permission-roles', {
      params: { role_id: role.id, per_page: 1000 },
    })

    rolePermissionPivots.value = res.data.data || []

    // isi checkbox berdasarkan pivot
    selectedPermissionIds.value = rolePermissionPivots.value.map(
      (pr) => pr.permission_id
    )
  } catch (error) {
    console.error('Gagal memuat permission role:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    isLoadingModal.value = false
  }
}

// ====== SIMPAN: SYNC PERMISSIONS UNTUK ROLE ======
const savePermissions = async () => {
  if (!currentRole.value) return
  isSaving.value = true

  try {
    const currentIds = rolePermissionPivots.value.map(pr => pr.permission_id)
    const currentByPermId = {}
    rolePermissionPivots.value.forEach(pr => {
      currentByPermId[pr.permission_id] = pr
    })

    const newIds = selectedPermissionIds.value

    const toAdd = newIds.filter(id => !currentIds.includes(id))
    const toRemove = currentIds.filter(id => !newIds.includes(id))

    // ADD
    for (const permId of toAdd) {
      await axios.post('/api/v1/permission-roles', {
        role_id: currentRole.value.id,
        permission_id: permId,
      })
    }

    // REMOVE
    for (const permId of toRemove) {
      const pivot = currentByPermId[permId]
      if (pivot?.id) {
        await axios.delete(`/api/v1/permission-roles/${pivot.id}`)
      }
    }

    // refresh pivot utk role
    await openPermissionModal(currentRole.value)

    // alert('Pengaturan permission role berhasil disimpan.')
    Swal.fire({
            icon: 'success',
            title: 'Pengaturan permission role berhasil disimpan.',
            showConfirmButton: false,
            timer: 2000
        });
    $('#rolePermissionModal').modal('hide')
  } catch (error) {
    console.error('Gagal menyimpan permission role:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan pengaturan hak akses.')
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  fetchRoles()
  fetchPermissions() // boleh paralel
})
</script>
