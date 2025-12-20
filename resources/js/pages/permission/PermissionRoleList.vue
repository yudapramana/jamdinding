<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Pengaturan Hak Akses Role</h1>
          <p class="mb-0 text-muted text-sm">
            Pilih role lalu atur menu / permission yang boleh diakses.
          </p>
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
              <tr v-for="(role, index) in filteredRoles" :key="role.id">
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

    <!-- MODAL -->
    <div class="modal fade" id="rolePermissionModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2 bg-primary text-white">
            <div>
              <h5 class="modal-title mb-0">
                <i class="fas fa-user-shield mr-1"></i> Pengaturan Hak Akses
              </h5>
              <small v-if="currentRole" class="d-block">
                Role: <strong>{{ currentRole.name }}</strong>
                (<code class="text-white">{{ currentRole.slug }}</code>)
              </small>
            </div>

            <button type="button" class="close" data-dismiss="modal">
              <span class="text-white">&times;</span>
            </button>
          </div>

          <div class="modal-body" style="background:#f4f6f9;">
            <div v-if="isLoadingModal" class="text-center my-3">
              <i class="fas fa-spinner fa-spin mr-1"></i> Memuat permission...
            </div>

            <div v-else>
              <div class="mb-2">
                <input
                  v-model="permSearch"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Cari permission... (contoh: manage.event.judges)"
                />
              </div>

              <div v-if="permissionGroups.length === 0" class="text-center text-muted py-4">
                Tidak ada permission sesuai pencarian.
              </div>

              <div
                v-for="g in permissionGroups"
                :key="g.key"
                class="card mb-3 border-0 shadow-sm"
              >
                <div class="card-header py-2 bg-dark text-white d-flex align-items-center">
                  <div class="custom-control custom-checkbox mb-0">
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      :id="'group-'+g.key"
                      :checked="isGroupFullyChecked(g.key)"
                      @change="toggleGroup(g.key, $event.target.checked)"
                    />
                    <label class="custom-control-label font-weight-bold" :for="'group-'+g.key">
                      {{ g.title }}
                      <small class="ml-1" style="opacity:.9;">({{ g.key }})</small>
                    </label>
                  </div>
                </div>

                <div class="card-body py-2">
                  <div
                    v-for="perm in g.permissions"
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
                Centang permission yang boleh diakses oleh role
                <strong>{{ currentRole?.name }}</strong>.
              </p>
            </div>

          </div>

          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
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
import Swal from 'sweetalert2'

const authUserStore = useAuthUserStore()

// ====== ROLE LIST ======
const roles = ref([])
const isLoadingRoles = ref(false)
const search = ref('')

// ====== MODAL ======
const currentRole = ref(null)
const isLoadingModal = ref(false)
const isSaving = ref(false)

const permSearch = ref('')
const allPermissions = ref([])
const rolePermissionPivots = ref([])
const selectedPermissionIds = ref([])

// ====== FETCH ROLES ======
const fetchRoles = async () => {
  isLoadingRoles.value = true
  try {
    const res = await axios.get('/api/v1/roles-simple')
    roles.value = res.data || []
  } catch (error) {
    console.error('Gagal memuat roles:', error)
    if (error.response && error.response.status === 401) authUserStore.logout()
  } finally {
    isLoadingRoles.value = false
  }
}

// filter local
const filteredRoles = computed(() => {
  if (!search.value) return roles.value
  const q = search.value.toLowerCase()
  return roles.value.filter(r =>
    (r.name && r.name.toLowerCase().includes(q)) ||
    (r.slug && r.slug.toLowerCase().includes(q))
  )
})

watch(search, useDebounceFn(() => {}, 250))

// ====== FETCH PERMISSIONS ======
const fetchPermissions = async () => {
  try {
    const res = await axios.get('/api/v1/permissions-simple')
    allPermissions.value = res.data || []
  } catch (error) {
    console.error('Gagal memuat permissions:', error)
  }
}

// =====================================================
// GROUPING (SESUAI PERMISSIONS BARU)
// =====================================================
const GROUP_TITLES = {
  'manage.core': 'CORE',
  'manage.master': 'MASTER DATA',
  'manage.event': 'EVENT',
  'manage.event.participant': 'PESERTA',
  'manage.event.judges': 'HAKIM',
  'manage.event.scoring': 'PENILAIAN',
  'manage.event.scores': 'REKAP NILAI',
  'manage.event.ranking': 'RANKING',
  'manage.event.results': 'PEROLEHAN JUARA',
}

const GROUP_ORDER = [
  'manage.core',
  'manage.master',
  'manage.event',
  'manage.event.participant',
  'manage.event.judges',
  'manage.event.scoring',
  'manage.event.scores',
  'manage.event.ranking',
  'manage.event.results',
]

// slug menu yang level “menu” (bukan halaman spesifik)
const MENU_SLUGS = new Set([
  'manage.core',
  'manage.master',
  'manage.event',
  'manage.event.participant',
  'manage.event.judges',
  'manage.event.scoring',
  'manage.event.scores',
  'manage.event.ranking',
  'manage.event.results',
])

const getGroupKeyFromSlug = (slug = '') => {
  const s = String(slug || '')

  // paling spesifik dulu
  if (s.startsWith('manage.event.participant')) return 'manage.event.participant'
  if (s.startsWith('manage.event.judges')) return 'manage.event.judges'
  if (s.startsWith('manage.event.scoring')) return 'manage.event.scoring'
  if (s.startsWith('manage.event.scores')) return 'manage.event.scores'
  if (s.startsWith('manage.event.ranking')) return 'manage.event.ranking'
  if (s.startsWith('manage.event.results')) return 'manage.event.results'

  if (s.startsWith('manage.event')) return 'manage.event'
  if (s.startsWith('manage.core')) return 'manage.core'
  if (s.startsWith('manage.master')) return 'manage.master'
  return 'other'
}

// urutkan: menu dulu, lalu submenu. kemudian by depth, lalu alfabet.
const sortPerms = (a, b) => {
  const as = String(a.slug || '')
  const bs = String(b.slug || '')

  const aIsMenu = MENU_SLUGS.has(as)
  const bIsMenu = MENU_SLUGS.has(bs)
  if (aIsMenu !== bIsMenu) return aIsMenu ? -1 : 1

  const ad = as.split('.').length
  const bd = bs.split('.').length
  if (ad !== bd) return ad - bd

  return as.localeCompare(bs)
}

const permissionGroups = computed(() => {
  const q = (permSearch.value || '').toLowerCase().trim()

  const map = {}
  GROUP_ORDER.forEach(k => {
    map[k] = { key: k, title: GROUP_TITLES[k] || k, permissions: [] }
  })
  map.other = { key: 'other', title: 'LAINNYA', permissions: [] }

  const passes = (p) => {
    if (!q) return true
    const s1 = (p.slug || '').toLowerCase()
    const s2 = (p.name || '').toLowerCase()
    return s1.includes(q) || s2.includes(q)
  }

  for (const p of (allPermissions.value || [])) {
    if (!passes(p)) continue
    const key = getGroupKeyFromSlug(p.slug)
    if (!map[key]) map[key] = { key, title: GROUP_TITLES[key] || key, permissions: [] }
    map[key].permissions.push(p)
  }

  Object.values(map).forEach(g => g.permissions.sort(sortPerms))

  return [
    ...GROUP_ORDER.map(k => map[k]).filter(g => g && g.permissions.length),
    ...(map.other.permissions.length ? [map.other] : []),
  ]
})

// ====== MODAL OPEN ======
const openPermissionModal = async (role) => {
  permSearch.value = ''
  currentRole.value = role
  isLoadingModal.value = true
  selectedPermissionIds.value = []
  rolePermissionPivots.value = []

  $('#rolePermissionModal').modal('show')

  try {
    if (!allPermissions.value.length) await fetchPermissions()

    const res = await axios.get('/api/v1/permission-roles', {
      params: { role_id: role.id, per_page: 1000 },
    })

    rolePermissionPivots.value = res.data.data || []
    selectedPermissionIds.value = rolePermissionPivots.value.map(pr => pr.permission_id)
  } catch (error) {
    console.error('Gagal memuat permission role:', error)
    if (error.response && error.response.status === 401) authUserStore.logout()
  } finally {
    isLoadingModal.value = false
  }
}

// ====== SAVE ======
const savePermissions = async () => {
  if (!currentRole.value) return
  isSaving.value = true
  try {
    await axios.post(`/api/v1/roles/${currentRole.value.id}/sync-permissions`, {
      permission_ids: selectedPermissionIds.value,
    })

    Swal.fire({
      icon: 'success',
      title: 'Hak akses berhasil disimpan.',
      showConfirmButton: false,
      timer: 1400,
    })
    $('#rolePermissionModal').modal('hide')
  } catch (error) {
    console.error('Gagal sync permissions:', error)
    Swal.fire('Gagal', error.response?.data?.message || 'Gagal menyimpan hak akses.', 'error')
  } finally {
    isSaving.value = false
  }
}

// ====== GROUP CHECK ======
const isGroupFullyChecked = (groupKey) => {
  const g = permissionGroups.value.find(x => x.key === groupKey)
  if (!g || !g.permissions.length) return false
  return g.permissions.every(p => selectedPermissionIds.value.includes(p.id))
}

const toggleGroup = (groupKey, checked) => {
  const g = permissionGroups.value.find(x => x.key === groupKey)
  if (!g) return
  const ids = g.permissions.map(p => p.id)

  if (checked) {
    selectedPermissionIds.value = Array.from(new Set([...selectedPermissionIds.value, ...ids]))
  } else {
    selectedPermissionIds.value = selectedPermissionIds.value.filter(id => !ids.includes(id))
  }
}

onMounted(() => {
  fetchRoles()
  fetchPermissions()
})
</script>
