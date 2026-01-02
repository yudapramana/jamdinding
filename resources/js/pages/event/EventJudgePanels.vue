<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Majelis Hakim (Cabang / Golongan)</h1>
          <p class="mb-0 text-muted text-sm">
            Kelola hakim default per cabang dan override per golongan dalam satu tempat.
          </p>
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
            </div>

            <!-- RIGHT -->
            <div class="d-flex align-items-center mt-2 mt-sm-0">
              <input
                v-model="search"
                type="text"
                class="form-control form-control-sm w-auto"
                style="min-width: 280px"
                placeholder="Cari cabang / golongan..."
              />
            </div>
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:50px">#</th>
                <th>Cabang / Golongan</th>
                <th style="width:380px">Majelis Hakim</th>
                <th style="width:170px" class="text-center">Mode</th>
                <th style="width:160px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="5" class="text-center py-4">Memuat data majelis hakim...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="5" class="text-center py-4">
                  Belum ada data golongan untuk event ini.
                </td>
              </tr>

              <tr v-for="(row, index) in items" :key="row.id">
                <td class="text-center">{{ rowNumber(index) }}</td>

                <td>
                  <div><strong>{{ row.branch_name }}</strong></div>
                  <div class="text-xs text-muted">{{ row.group_name }}</div>
                  <div class="text-xs text-muted">{{ row.full_name }}</div>
                </td>

                <!-- <td>
                  <div class="d-flex flex-wrap">
                    <span
                      v-for="j in (row.effective_judges || [])"
                      :key="j.id"
                      class="badge badge-light border mr-1 mb-1"
                    >
                      <i class="fas fa-user mr-1"></i>{{ j.name }}
                    </span>

                    <span v-if="(row.effective_judges || []).length === 0" class="text-muted text-xs">
                      Belum ada hakim.
                    </span>
                  </div>
                </td> -->

                <td>
                  <div v-if="(row.effective_judges || []).length">
                    <div
                      v-for="(j, idx) in row.effective_judges"
                      :key="j.id"
                      class="mb-1"
                    >
                      <span
                        class="badge border"
                        :class="j.is_chief ? 'badge-warning' : 'badge-light'"
                      >
                        <i class="fas fa-gavel mr-1" v-if="j.is_chief"></i>
                        <i class="fas fa-user mr-1" v-else></i>
                        {{ j.name }}
                        <span v-if="j.is_chief" class="ml-1">(Ketua)</span>
                      </span>
                    </div>
                  </div>

                  <span v-else class="text-muted text-xs">
                    Belum ada hakim.
                  </span>
                </td>


                <td class="text-center">
                  <span
                    class="badge"
                    :class="row.use_custom_judges ? 'badge-warning' : 'badge-success'"
                  >
                    <i :class="row.use_custom_judges ? 'fas fa-random mr-1' : 'fas fa-layer-group mr-1'"></i>
                    {{ row.use_custom_judges ? 'CUSTOM (Golongan)' : 'DEFAULT (Cabang)' }}
                  </span>
                </td>

                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-primary btn-xs"
                      title="Kelola Hakim Cabang (Default)"
                      :disabled="!row.event_branch_id"
                      @click="openBranchModal(row)"
                    >
                      <i class="fas fa-sitemap"></i>
                    </button>

                    <button
                      class="btn btn-outline-warning btn-xs"
                      title="Kelola Hakim Golongan (Override)"
                      @click="openGroupModal(row)"
                    >
                      <i class="fas fa-users-cog"></i>
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
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total || 0 }} data
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

    <!-- MODAL: Default Cabang -->
    <div class="modal fade" id="branchJudgesModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Hakim Cabang (Default) — {{ branchModal.title }}
            </h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>

          <div class="modal-body">
            <div class="form-group">
              <label class="text-sm text-muted mb-1">Cari Hakim</label>
              <input v-model="branchModal.userSearch" class="form-control form-control-sm" placeholder="Ketik nama..." />
              <div v-if="branchModal.userOptions.length" class="border rounded mt-2 p-2" style="max-height:220px; overflow:auto;">
                <div v-for="u in branchModal.userOptions" :key="u.id" class="d-flex align-items-center justify-content-between py-1">
                  <div>
                    <strong class="text-sm">{{ u.name }}</strong>
                  </div>
                  <button class="btn btn-sm btn-outline-primary" @click="addJudge(branchModal, u)">
                    <i class="fas fa-plus mr-1"></i> Tambah
                  </button>
                </div>
              </div>
            </div>

            <hr class="my-2" />

            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Daftar Hakim Cabang</strong>
                <small class="text-muted">Pilih 1 ketua majelis (opsional)</small>
              </div>

              <div v-if="branchModal.selected.length === 0" class="text-muted text-sm">
                Belum ada hakim dipilih.
              </div>

              <div v-else class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Hakim</th>
                      <th style="width:140px" class="text-center">Ketua</th>
                      <th style="width:90px" class="text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="j in branchModal.selected" :key="j.user_id">
                      <td><strong>{{ j.name }}</strong></td>
                      <td class="text-center">
                        <input type="radio" name="branchChief" :checked="j.is_chief" @change="setChief(branchModal, j.user_id)" />
                      </td>
                      <td class="text-center">
                        <button class="btn btn-xs btn-outline-danger" @click="removeJudge(branchModal, j.user_id)">
                          <i class="fas fa-times"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
            <button class="btn btn-sm btn-primary" :disabled="branchModal.saving" @click="saveBranchJudges">
              <i class="fas fa-save mr-1"></i> Simpan
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: Override Golongan -->
    <div class="modal fade" id="groupJudgesModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Hakim Golongan (Override) — {{ groupModal.title }}
            </h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>

          <div class="modal-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <strong>Override untuk golongan ini</strong>
                <div class="text-xs text-muted">Jika OFF, sistem memakai default cabang.</div>
              </div>

              <div class="custom-control custom-switch">
                <input
                  type="checkbox"
                  class="custom-control-input"
                  id="toggleCustom"
                  v-model="groupModal.use_custom_judges"
                  @change="saveToggleCustom"
                />
                <label class="custom-control-label" for="toggleCustom">
                  Gunakan hakim khusus
                </label>
              </div>
            </div>

            <div v-if="!groupModal.use_custom_judges" class="alert alert-light border text-sm mb-0">
              Override dimatikan. Untuk mengubah majelis, aktifkan toggle di atas.
            </div>

            <div v-else>
              <div class="form-group">
                <label class="text-sm text-muted mb-1">Cari Hakim</label>
                <input v-model="groupModal.userSearch" class="form-control form-control-sm" placeholder="Ketik nama..." />
                <div v-if="groupModal.userOptions.length" class="border rounded mt-2 p-2" style="max-height:220px; overflow:auto;">
                  <div v-for="u in groupModal.userOptions" :key="u.id" class="d-flex align-items-center justify-content-between py-1">
                    <div>
                      <strong class="text-sm">{{ u.name }}</strong>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" @click="addJudge(groupModal, u)">
                      <i class="fas fa-plus mr-1"></i> Tambah
                    </button>
                  </div>
                </div>
              </div>

              <hr class="my-2" />

              <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong>Daftar Hakim Golongan</strong>
                  <small class="text-muted">Pilih 1 ketua majelis (opsional)</small>
                </div>

                <div v-if="groupModal.selected.length === 0" class="text-muted text-sm">
                  Belum ada hakim dipilih.
                </div>

                <div v-else class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th>Hakim</th>
                        <th style="width:140px" class="text-center">Ketua</th>
                        <th style="width:90px" class="text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="j in groupModal.selected" :key="j.user_id">
                        <td><strong>{{ j.name }}</strong></td>
                        <td class="text-center">
                          <input type="radio" name="groupChief" :checked="j.is_chief" @change="setChief(groupModal, j.user_id)" />
                        </td>
                        <td class="text-center">
                          <button class="btn btn-xs btn-outline-danger" @click="removeJudge(groupModal, j.user_id)">
                            <i class="fas fa-times"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
            <button class="btn btn-sm btn-warning" :disabled="groupModal.saving || !groupModal.use_custom_judges" @click="saveGroupJudges">
              <i class="fas fa-save mr-1"></i> Simpan Override
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

// TABLE
const items = ref([])
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const rowNumber = (index) => index + 1 + (meta.value.current_page - 1) * meta.value.per_page

const fetchItems = async (page = 1) => {
  if (!eventId.value) return
  isLoading.value = true
  try {
    const res = await axios.get(`/api/v1/events/${eventId.value}/judge-panels`, {
      params: { page, per_page: perPage.value, search: search.value },
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
    Swal.fire('Gagal', 'Gagal memuat data majelis hakim.', 'error')
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchItems(page)
}

// ===== User Search (sesuaikan endpoint user kamu) =====
// Harus return array [{id,name}]
const searchUsers = async (keyword) => {
  const q = (keyword || '').trim()
  if (q.length < 2) return []
  try {
    // contoh: /api/v1/users?search=...&role=JUDGE
    const res = await axios.get('/api/v1/users', { params: { search: q, role: 'JUDGE', per_page: 10 } })
    const data = res.data?.data || res.data || []
    return Array.isArray(data) ? data : (data.data || [])
  } catch (e) {
    return []
  }
}

// ===== Modal helper =====
const makeModalState = () => ({
  title: '',
  id: null,            // event_branch_id / event_group_id
  saving: false,
  userSearch: '',
  userOptions: [],
  selected: [],        // [{user_id,name,is_chief}]
  use_custom_judges: false, // hanya group modal
})

const branchModal = ref(makeModalState())
const groupModal  = ref(makeModalState())

const addJudge = (modal, u) => {
  const exists = modal.selected.some(x => x.user_id === u.id)
  if (exists) return
  modal.selected.push({ user_id: u.id, name: u.name, is_chief: false })
}

const removeJudge = (modal, userId) => {
  modal.selected = modal.selected.filter(x => x.user_id !== userId)
}

const setChief = (modal, userId) => {
  modal.selected = modal.selected.map(x => ({ ...x, is_chief: x.user_id === userId }))
}

// ===== Open Branch Modal =====
const openBranchModal = async (row) => {
  if (!row.event_branch_id) return
  branchModal.value = makeModalState()
  branchModal.value.id = row.event_branch_id
  branchModal.value.title = row.branch_name

  try {
    const res = await axios.get(`/api/v1/event-branches/${row.event_branch_id}/judges`)
    branchModal.value.selected = (res.data?.judges || []).map(j => ({
      user_id: j.id, name: j.name, is_chief: !!j.is_chief
    }))
    $('#branchJudgesModal').modal('show')
  } catch (e) {
    Swal.fire('Gagal', 'Gagal memuat hakim cabang.', 'error')
  }
}

// ===== Save Branch Judges =====
const saveBranchJudges = async () => {
  if (!branchModal.value.id) return
  if (branchModal.value.selected.length === 0) {
    Swal.fire('Validasi', 'Pilih minimal 1 hakim.', 'warning')
    return
  }
  branchModal.value.saving = true
  try {
    await axios.put(`/api/v1/event-branches/${branchModal.value.id}/judges`, {
      judges: branchModal.value.selected.map(x => ({ user_id: x.user_id, is_chief: x.is_chief })),
    })
    $('#branchJudgesModal').modal('hide')
    Swal.fire('Berhasil', 'Hakim cabang disimpan.', 'success')
    fetchItems(meta.value.current_page || 1)
  } catch (e) {
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan hakim cabang.', 'error')
  } finally {
    branchModal.value.saving = false
  }
}

// ===== Open Group Modal =====
const openGroupModal = async (row) => {
  groupModal.value = makeModalState()
  groupModal.value.id = row.id
  groupModal.value.title = row.full_name
  groupModal.value.use_custom_judges = !!row.use_custom_judges

  try {
    const res = await axios.get(`/api/v1/event-groups/${row.id}/judges`)
    groupModal.value.use_custom_judges = !!res.data?.use_custom_judges
    groupModal.value.selected = (res.data?.judges || []).map(j => ({
      user_id: j.id, name: j.name, is_chief: !!j.is_chief
    }))
    $('#groupJudgesModal').modal('show')
  } catch (e) {
    Swal.fire('Gagal', 'Gagal memuat hakim golongan.', 'error')
  }
}

const saveToggleCustom = async () => {
  if (!groupModal.value.id) return
  try {
    await axios.patch(`/api/v1/event-groups/${groupModal.value.id}/use-custom-judges`, {
      use_custom_judges: !!groupModal.value.use_custom_judges,
    })
    // refresh tabel biar badge mode & effective berubah
    fetchItems(meta.value.current_page || 1)
  } catch (e) {
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan pengaturan.', 'error')
  }
}

// ===== Save Group Judges (override) =====
const saveGroupJudges = async () => {
  if (!groupModal.value.id) return
  if (!groupModal.value.use_custom_judges) return
  if (groupModal.value.selected.length === 0) {
    Swal.fire('Validasi', 'Pilih minimal 1 hakim untuk override.', 'warning')
    return
  }

  groupModal.value.saving = true
  try {
    await axios.put(`/api/v1/event-groups/${groupModal.value.id}/judges`, {
      judges: groupModal.value.selected.map(x => ({ user_id: x.user_id, is_chief: x.is_chief })),
    })
    $('#groupJudgesModal').modal('hide')
    Swal.fire('Berhasil', 'Hakim golongan disimpan.', 'success')
    fetchItems(meta.value.current_page || 1)
  } catch (e) {
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan hakim golongan.', 'error')
  } finally {
    groupModal.value.saving = false
  }
}

// Watch search user input (branch)
watch(() => branchModal.value.userSearch, useDebounceFn(async (val) => {
  branchModal.value.userOptions = await searchUsers(val)
}, 300))

// Watch search user input (group)
watch(() => groupModal.value.userSearch, useDebounceFn(async (val) => {
  groupModal.value.userOptions = await searchUsers(val)
}, 300))

// WATCHERS TABLE
watch(() => search.value, useDebounceFn(() => fetchItems(1), 400))
watch(() => perPage.value, () => fetchItems(1))
watch(() => eventId.value, (val) => { if (val) fetchItems(1) })

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
