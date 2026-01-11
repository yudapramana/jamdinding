<template>

  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Majelis Hakim Event </h1>
          <p class="mb-0 text-muted text-sm">Pengelolaan majelis hakim berdasarkan skema event</p>
        </div>

        <div class="btn-group btn-group-sm">
          <button
            class="btn btn-sm btn-primary"
            :disabled="!eventId"
            @click="openCreateModal"
          >
            + Tambah Majelis
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

        <!-- HEADER -->
        <div class="card-header py-2">
          <div class="d-flex flex-wrap justify-content-between align-items-center">

            <!-- LEFT -->
            <div class="d-flex align-items-center gap-2">
              <span class="text-xs text-muted">Tampil</span>
              <select
                v-model.number="perPage"
                class="form-control form-control-sm w-auto"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="text-xs text-muted">data</span>
            </div>

            <!-- RIGHT -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm"
              style="width:220px"
              placeholder="Cari nama / kode majelis"
            />

          </div>
        </div>


        <!-- TABLE -->
        <div class="card-body p-0 table-responsive">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th width="50">#</th>
                <th>Nama Majelis</th>
                <th>Lokasi</th>
                <th>Hakim</th>
                <th width="80" class="text-center">Status</th>
                <th width="120" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="loading">
                <td colspan="5" class="text-center py-4">Memuat data...</td>
              </tr>

              <tr v-else-if="panels.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">
                  Belum ada majelis hakim.
                </td>
              </tr>

              <tr v-for="(p, i) in panels" :key="p.id">
                <td class="text-center">{{ rowNumber(i) }}</td>

                <td>
                  <strong>{{ p.name }}</strong>
                  <div class="text-xs text-muted">{{ p.code || '-' }}</div>
                </td>

                <td>
                  <select
                    class="form-control form-control-sm"
                    :value="p.event_location_id"
                    @change="assignLocation(p.id, $event.target.value)"
                  >
                    <option value="">— Belum Ditentukan —</option>
                    <option
                      v-for="loc in locations"
                      :key="loc.id"
                      :value="loc.id"
                    >
                      {{ loc.code ? loc.code + ' - ' : '' }}{{ loc.name }}
                    </option>
                  </select>
                </td>


                <!-- JUDGES -->
                <td>
                  <div v-if="p.judges.length">
                    <div v-for="j in p.judges" :key="j.event_judge_id">
                      <span
                        class="badge mr-1 mb-1"
                        :class="j.is_chief ? 'badge-warning' : 'badge-light border'"
                      >
                        <i class="fas fa-gavel mr-1" v-if="j.is_chief"></i>
                        <i class="fas fa-user mr-1" v-else></i>
                        {{ j.full_name }}
                        <span v-if="j.is_chief">(Ketua)</span>
                      </span>
                    </div>
                  </div>
                  <span v-else class="text-muted text-xs">
                    Belum ada hakim
                  </span>
                </td>

                <td class="text-center">
                  <span
                    class="badge"
                    :class="p.is_active ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ p.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>

                <td class="text-center">
                  <button
                    class="btn btn-xs btn-outline-primary"
                    title="Kelola Anggota"
                    @click="openMembersModal(p)"
                  >
                    <i class="fas fa-users-cog"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <div class="card-footer clearfix py-2">
            <div class="d-flex justify-content-between align-items-center">
              <div class="text-muted text-sm">
                Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }}
                dari {{ meta.total || 0 }} data
              </div>
              <ul class="pagination pagination-sm m-0">
                <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                  <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
                </li>
                <li class="page-item disabled">
                  <span class="page-link">
                    Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
                  </span>
                </li>
                <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                  <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">»</a>
                </li>
              </ul>
            </div>
          </div>

      </div>
    </div>

    <!-- MODAL TAMBAH MAJELIS -->
    <div class="modal fade" id="createPanelModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">Tambah Majelis Hakim</h5>
            <button class="close" data-dismiss="modal">&times;</button>
          </div>

          <div class="modal-body">
            <div class="form-group">
              <label class="text-sm text-muted">Nama Majelis *</label>
              <input
                v-model="createModal.name"
                class="form-control form-control-sm"
                placeholder="Contoh: Majelis Tilawah Dewasa"
                readonly
              />
            </div>

            <div class="form-group">
              <label class="text-sm text-muted">Kode</label>
              <input
                v-model="createModal.code"
                class="form-control form-control-sm"
                placeholder="MJ-A (opsional)"
                readonly
              />
            </div>

            <div class="form-group mb-0">
              <label class="text-sm text-muted">Catatan</label>
              <textarea
                v-model="createModal.notes"
                rows="2"
                class="form-control form-control-sm"
              />
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-sm btn-secondary" data-dismiss="modal">
              Batal
            </button>
            <button
              class="btn btn-sm btn-primary"
              :disabled="createModal.saving"
              @click="savePanel"
            >
              <i class="fas fa-save mr-1"></i> Simpan
            </button>
          </div>

        </div>
      </div>
    </div>



    <!-- MODAL KELOLA ANGGOTA -->
    <div class="modal fade" id="membersModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">
              Anggota Majelis — {{ membersModal.title }}
            </h5>
            <button class="close" data-dismiss="modal">&times;</button>
          </div>

          <div class="modal-body">

            <!-- SEARCH -->
            <div class="form-group">
              <label class="text-sm text-muted mb-1">Cari Hakim</label>
              <input
                v-model="membersModal.search"
                class="form-control form-control-sm"
                placeholder="Ketik nama..."
              />

              <div v-if="membersModal.options.length"
                class="border rounded mt-2 p-2"
                style="max-height:200px; overflow:auto"
              >
                <div
                  v-for="u in membersModal.options"
                  :key="u.id"
                  class="d-flex justify-content-between align-items-center py-1"
                >
                  <span>{{ u.full_name }}</span>
                  <button
                    class="btn btn-xs btn-outline-primary"
                    @click="addJudge(u)"
                  >
                    Tambah
                  </button>
                </div>
              </div>
            </div>

            <hr>

            <!-- SELECTED -->
            <div v-if="membersModal.selected.length === 0" class="text-muted text-sm">
              Belum ada hakim.
            </div>

            <table v-else class="table table-sm table-bordered">
              <thead class="thead-light">
                <tr>
                  <th>Dewan Hakim</th>
                  <th width="120" class="text-center">Ketua</th>
                  <th width="80" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="j in membersModal.selected" :key="j.event_judge_id">
                  <td>{{ j.full_name }}</td>
                  <td class="text-center">
                    <input
                      type="radio"
                      name="chief"
                      :checked="j.is_chief"
                      @change="setChief(j.event_judge_id)"
                    >
                  </td>
                  <td class="text-center">
                    <button
                      class="btn btn-xs btn-outline-danger"
                      @click="removeJudge(j.event_judge_id)"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>

          </div>

          <div class="modal-footer">
            <button class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
            <button class="btn btn-sm btn-primary" @click="saveMembers">
              Simpan
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
import Swal from 'sweetalert2'
import { useAuthUserStore } from '@/stores/AuthUserStore'
import { useDebounceFn } from '@vueuse/core'

const authStore = useAuthUserStore()
const eventId = computed(() => authStore.eventData?.id || null)

const panels = ref([])
const loading = ref(false)
const locations = ref([])
const perPage = ref(25)
const search = ref('')


const assignLocation = async (panelId, locationId) => {
  try {
    await axios.put(
      `/api/v1/event-judge-panels/${panelId}/assign-location`,
      {
        event_location_id: locationId || null
      }
    )

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Lokasi majelis disimpan',
      showConfirmButton: false,
      timer: 1500
    })

    fetchPanels()

  } catch (e) {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: 'Gagal menyimpan lokasi',
      showConfirmButton: false,
      timer: 1500
    })
  }
}


const fetchLocations = async () => {
  if (!eventId.value) return

  const res = await axios.get(
    `/api/v1/events/${eventId.value}/locations`,
    { params: { simple: 1 } }
  )

  locations.value = res.data.data || res.data
}


const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchPanels(page)
}

const rowNumber = (i) =>
  i + 1 + (meta.value.current_page - 1) * meta.value.per_page

const fetchPanels = async (page = 1) => {
  if (!eventId.value) return

  loading.value = true

  const res = await axios.get(
    `/api/v1/events/${eventId.value}/judge-panels`,
    {
      params: {
        page,
        per_page: perPage.value,
        search: search.value
      }
    }
  )

  panels.value = res.data.data
  meta.value = res.data.meta
  loading.value = false
}


/* ===== CREATE PANEL MODAL ===== */
const createModal = ref({
  name: '',
  code: '',
  notes: '',
  saving: false,
  readonly: true
})


const openCreateModal = async () => {
  createModal.value = {
    name: 'Majelis (otomatis)',
    code: `${authStore.eventData.event_key}-MJ-*`,
    notes: '',
    saving: false,
    readonly: true
  }

  $('#createPanelModal').modal('show')
}


const savePanel = async () => {
  createModal.value.saving = true

  try {
    await axios.post(
      `/api/v1/events/${eventId.value}/judge-panels`
    )

    $('#createPanelModal').modal('hide')
    Swal.fire('Berhasil', 'Majelis hakim ditambahkan', 'success')
    fetchPanels()

  } catch (e) {
    Swal.fire('Gagal', 'Gagal menambah majelis', 'error')
  } finally {
    createModal.value.saving = false
  }
}



/* ===== MODAL MEMBERS ===== */
const membersModal = ref({
  panelId: null,
  title: '',
  search: '',
  options: [],
  selected: []
})

const openMembersModal = async (panel) => {
  membersModal.value = {
    panelId: panel.id,
    title: panel.name,
    search: '',
    options: [],
    selected: [...panel.judges]
  }
  $('#membersModal').modal('show')
}

const searchJudges = async (q) => {
  if (!q || q.length < 2) return []
  const res = await axios.get(
    `/api/v1/events/${eventId.value}/event-judges`,
    { params: { search: q } }
  )
  return res.data
}

watch(
  () => membersModal.value.search,
  useDebounceFn(async (v) => {
    membersModal.value.options = await searchJudges(v)
  }, 300)
)

const addJudge = (u) => {
  if (membersModal.value.selected.some(x => x.event_judge_id === u.id)) return
  membersModal.value.selected.push({
    event_judge_id: u.id,
    full_name: u.full_name,
    is_chief: false
  })
}

const removeJudge = (id) => {
  membersModal.value.selected =
    membersModal.value.selected.filter(x => x.event_judge_id !== id)
}

const setChief = (id) => {
  membersModal.value.selected =
    membersModal.value.selected.map(x => ({
      ...x,
      is_chief: x.event_judge_id === id
    }))
}

const saveMembers = async () => {
  await axios.put(
    `/api/v1/event-judge-panels/${membersModal.value.panelId}/members`,
    {
      judges: membersModal.value.selected.map(j => ({
        event_judge_id: j.event_judge_id,
        is_chief: j.is_chief
      }))
    }
  )
  $('#membersModal').modal('hide')
  Swal.fire('Berhasil', 'Anggota majelis disimpan', 'success')
  fetchPanels()
}



watch(perPage, () => fetchPanels(1))

watch(
  () => search.value,
  useDebounceFn(() => fetchPanels(1), 400)
)

watch(eventId, (val) => {
  if (val) {
    fetchPanels()
    fetchLocations()
  }
})

onMounted(() => {
  fetchPanels()
  fetchLocations()
})
</script>

<style scoped>
.text-xs { font-size: .75rem }
.btn-xs {
  padding: 2px 6px;
  font-size: .65rem;
}
</style>
