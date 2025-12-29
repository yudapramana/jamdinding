<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Pendaftaran Ulang Peserta</h1>
          <p class="mb-0 text-muted text-sm">
            Mengelola peserta & tim yang terdaftar pada event aktif (daftar ulang).
          </p>

          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
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
        <!-- HEADER -->
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

              <strong class="mr-3">|</strong>

              <label class="mb-0 mr-1 text-sm text-muted">Status Daftar Ulang</label>
              <select
                v-model="filters.reregistration_status"
                class="form-control form-control-sm w-auto mr-2"
              >
                <option value="">Semua</option>
                <option value="not_yet">Belum Hadir</option>
                <option value="verified">Terverifikasi</option>
                <option value="rejected">Diskualifikasi</option>
              </select>
            </div>

            <strong class="mr-2">|</strong>

            <label class="mb-0 mr-1 text-sm text-muted">Cabang</label>
            <select
              v-model="filters.event_group_id"
              class="form-control form-control-sm w-auto mr-2"
            >
              <option value="">--Belum Dipilih--</option>
              <option
                v-for="g in eventGroups"
                :key="g.id"
                :value="String(g.id)"
              >
                {{ g.full_name || g.name || g.group_name || ('Golongan #' + g.id) }}
              </option>
            </select>

            <!-- RIGHT -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto mt-2 mt-sm-0"
              style="min-width: 260px"
              placeholder="Cari nama / NIK / kontingen..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>Peserta / Tim</th>
                <th style="width: 200px;">Identitas</th>
                <th>Cabang / Golongan</th>
                <th style="width: 160px;">Kontingen</th>
                <!-- <th style="width: 170px;" class="text-center">Progress Lampiran</th> -->
                <th style="width: 140px;" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center py-4">
                  Memuat data daftar ulang...
                </td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center py-4">
                  Belum ada data daftar ulang.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.unit_type + '-' + item.id">
                <td class="text-center">{{ rowNumber(index) }}</td>

                <!-- PESERTA / TIM -->
                <td>
                  <span class="text-muted text-xs">
                    {{ item.participant_number || '-' }}
                  </span>
                  <strong class="ml-1">{{ item.display_name }}</strong>

                  <div class="mt-1 d-flex align-items-center flex-wrap">

                    <!-- UNIT TYPE -->
                    <span
                      class="badge mr-1"
                      :class="item.unit_type === 'team' ? 'badge-info' : 'badge-secondary'"
                    >
                      {{ item.unit_type === 'team' ? 'GRUP' : 'INDIVIDU' }}
                    </span>

                    <!-- =========================
                    INDIVIDUAL → GENDER
                    ========================== -->
                    <span
                      v-if="item.unit_type === 'individual' && item.leader?.gender"
                      class="badge mr-1 gender-badge"
                      :class="item.leader.gender === 'MALE' ? 'badge-primary' : 'badge-pink'"
                    >
                      <i
                        :class="item.leader.gender === 'MALE'
                          ? 'fas fa-mars'
                          : 'fas fa-venus'"
                      ></i>
                    </span>

                    <!-- =========================
                    TEAM → CATEGORY (PUTRA / PUTRI)
                    ========================== -->
                    <span
                      v-if="item.unit_type === 'team' && item.event_category?.category_name"
                      class="badge mr-1 gender-badge"
                      :class="categoryBadgeClass(item.event_category.category_name)"
                      :title="'Kategori ' + item.event_category.category_name"
                    >
                      <i :class="categoryIcon(item.event_category.category_name)"></i>
                    </span>

                    <!-- STATUS DAFTAR ULANG -->
                    <span
                      class="badge"
                      :class="reregistrationBadgeClass(item.reregistration_status)"
                    >
                      {{ reregistrationStatusLabel(item.reregistration_status) }}
                    </span>

                  </div>
                </td>


                <!-- IDENTITAS -->
                <td>
                  <!-- INDIVIDUAL -->
                  <template v-if="item.unit_type === 'individual'">
                    <strong>{{ item.leader.nik }}</strong>
                    <div class="text-xs text-muted">
                      Umur:
                      {{ item.age_year }}T
                      {{ item.age_month }}B
                      {{ item.age_day }}H
                    </div>
                  </template>

                  <!-- TEAM -->
                  <!-- TEAM: tombol per peserta -->
                  <template v-if="item.unit_type === 'team'">
                    <div
                      v-for="member in item.participants.filter(
                        m => m.registration_status === 'verified'
                      )"
                      :key="member.event_participant_id"
                      class="border rounded p-1 mb-1"
                    >

                      <!-- BELUM DAFTAR ULANG -->
                      <button
                        v-if="['not_yet', null, ''].includes(member.reregistration_status)"
                        class="btn btn-outline-warning btn-xs w-100 text-left"
                        @click="openReRegisterModal(member)"
                      >
                        <i class="fas fa-user-check mr-1"></i>
                        Daftar Ulang: {{ member.participant?.full_name }}
                      </button>

                      <!-- SUDAH DIPROSES -->
                      <div v-else>
                        <strong>{{ member.participant?.full_name }}</strong>

                        <div class="text-xs text-muted">
                          NIK: {{ member.participant?.nik || '-' }}
                        </div>

                        <span
                          class="badge mt-1"
                          :class="member.reregistration_status === 'verified'
                            ? 'badge-success'
                            : 'badge-danger'"
                        >
                          <i
                            :class="member.reregistration_status === 'verified'
                              ? 'fas fa-check-circle'
                              : 'fas fa-times-circle'"
                            class="mr-1"
                          ></i>
                          {{ member.reregistration_status === 'verified'
                            ? 'Terverifikasi'
                            : 'Diskualifikasi' }}
                        </span>
                      </div>

                    </div>
                  </template>




                </td>


                <!-- CABANG -->
                <td>
                  <strong>{{ item.event_group?.full_name || '-' }}</strong>
                  <div
                    v-if="item.unit_type === 'individual' && item.event_group?.max_age"
                    class="text-xs text-muted"
                  >
                    Batas:
                    {{ item.event_group.max_age - 1 }}T 11B 29H
                  </div>
                </td>

                <!-- KONTINGEN -->
                <td>
                  <span class="badge badge-light border">
                    {{ item.contingent || '-' }}
                  </span>
                </td>

                <!-- PROGRESS -->
                <!-- <td class="text-center">
                  <div class="progress progress-sm">
                    <div
                      class="progress-bar d-flex justify-content-center align-items-center"
                      :class="progressClass(item.leader?.lampiran_completion_percent)"
                      :style="{ width: (item.leader?.lampiran_completion_percent || 0) + '%' }"
                    >
                      {{ item.leader?.lampiran_completion_percent || 0 }}%
                    </div>
                  </div>
                </td> -->

                <!-- AKSI -->
                <td class="text-center">
                  <div class="btn-group btn-group-sm">

                    <!-- LIHAT DATA -->
                    <button
                      class="btn btn-outline-primary btn-xs"
                      title="Lihat Data"
                      @click="openViewModal(item)"
                    >
                      <i class="fas fa-eye"></i>
                    </button>

                    <!-- DAFTAR ULANG -->
                    <!-- <button
                      v-if="canReRegister(item)"
                      class="btn btn-outline-warning btn-xs"
                      title="Proses Daftar Ulang"
                      @click="openReRegisterModal(item)"
                    >
                      <i class="fas fa-user-check"></i>
                    </button> -->

                    <!-- DAFTAR ULANG INDIVIDU -->
                    <button
                      v-if="item.unit_type === 'individual' && canReRegister(item)"
                      class="btn btn-outline-warning btn-xs"
                      title="Proses Daftar Ulang"
                      @click="openReRegisterModal(item)"
                    >
                      <i class="fas fa-user-check"></i>
                    </button>

                    <!-- DAFTAR ULANG TIM -->
                    <button
                      v-if="item.unit_type === 'team' && canReRegisterTeam(item)"
                      class="btn btn-outline-warning btn-xs"
                      title="Proses Daftar Ulang Tim"
                      @click="openReRegisterTeamModal(item)"
                    >
                      <i class="fas fa-check-double"></i>
                    </button>


                    <!-- DRAW NOMOR -->
                    <button
                      v-if="canDrawNumber(item)"
                      class="btn btn-outline-success btn-xs"
                      title="Undian Nomor Peserta"
                      @click="openDrawModal(item)"
                    >
                      <i class="fas fa-dice"></i>
                    </button>

                  </div>
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

    <ViewParticipantModal :selected-participant="selectedParticipant" />

    <ReRegisterModal
      :event-participant="selectedReRegister"
      @updated="handleReRegisterUpdated"
      @request-draw="openDrawModal"
    />

    <DrawNumberModal
      :event-participant="selectedReRegister"
      @assigned="handleReRegisterUpdated"
    />

    <!-- ✅ MODAL BARU -->
    <ReRegisterTeamModal
      :event-team="selectedTeam"
      @updated="handleReRegisterUpdated"
    />

    <ViewTeamModal :selected-team="selectedParticipant" />


  </section>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../stores/AuthUserStore'
import ViewParticipantModal from './ViewParticipantModal.vue'
import ReRegisterModal from './ReRegisterModal.vue'
import { reregistrationBadgeClass, reregistrationStatusLabel } from './EventParticipantHelpers'
import DrawNumberModal from './DrawNumberModal.vue'
import ReRegisterTeamModal from './ReRegisterTeamModal.vue'
import ViewTeamModal from './ViewTeamModal.vue'

const selectedTeam = ref(null)

const openReRegisterTeamModal = (team) => {
  selectedTeam.value = team
  $('#reRegisterTeamModal').modal('show')
}


// AUTH
const authUserStore = useAuthUserStore()
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

// STATE
const items = ref([])
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const eventBranches = ref([])   // event_branches (cabang/golongan)
const eventGroups = ref([])
const eventCategories = ref([])


const filters = ref({
  reregistration_status: '',
  event_group_id: '',      // ✅ filter cabang/golongan
})

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

// MODAL
const selectedParticipant = ref(null)
const selectedReRegister = ref(null)

const openViewModal = (row) => {
  selectedParticipant.value = row
  if (row.unit_type === 'team') {
    $('#viewTeamModal').modal('show')
  } else {
    $('#viewParticipantModal').modal('show')
  }
}


const openReRegisterModal = (row) => {
  selectedReRegister.value = row
  $('#reRegisterModal').modal('show')
}

const openDrawModal = (row) => {
  selectedReRegister.value = row
  $('#drawNumberModal').modal('show')
}


const handleReRegisterUpdated = () => {
  fetchItems(meta.value.current_page || 1)
}

const canDrawNumber = (item) => {
  const verified = ['verified', 'diterima', 'accepted']
    .includes((item?.reregistration_status || '').toLowerCase())

  const noNumber =
    !item?.participant_number ||
    item.participant_number === '' ||
    item.participant_number === '-'

  return verified && noNumber
}

const fetchEventMasterData = async () => {
  if (!eventId.value) return
  try {
    const { data } = await axios.get(`/api/v1/events/${eventId.value}/simple`)
    // diasumsikan controller mengembalikan: event, branches, groups, categories
    eventBranches.value = data.branches || []
    eventGroups.value = data.groups || []
    eventCategories.value = data.categories || []
  } catch (error) {
    console.error('Gagal memuat master event (branches/groups/categories):', error)
    Swal.fire('Gagal', 'Gagal memuat daftar cabang event & golongan.', 'error')
  }
}


// HELPERS
const rowNumber = (index) =>
  index + 1 + (meta.value.current_page - 1) * meta.value.per_page

const progressClass = (percent = 0) => {
  const p = Number(percent || 0)
  if (p <= 20) return 'bg-danger'
  if (p <= 50) return 'bg-warning'
  if (p <= 80) return 'bg-info'
  return 'bg-success'
}

const categoryBadgeClass = (category) => {
  const c = String(category || '').toUpperCase()
  if (c === 'PUTRA') return 'badge-primary'
  if (c === 'PUTRI') return 'badge-pink'
  return 'badge-secondary'
}

const categoryIcon = (category) => {
  const c = String(category || '').toUpperCase()
  if (c === 'PUTRA') return 'fas fa-mars'
  if (c === 'PUTRI') return 'fas fa-venus'
  return 'fas fa-users'
}


const canReRegister = (item) => {
  if (item.unit_type !== 'individual') return false

  const regOk = ['verified'].includes(
    (item.registration_status || '').toLowerCase()
  )

  const notDoneYet = ['not_yet', '', null].includes(
    item.reregistration_status
  )

  return regOk && notDoneYet
}

const canReRegisterTeam = (item) => {
  if (item.unit_type !== 'team') return false

  // semua anggota sudah lolos verifikasi awal
  const allRegisteredVerified = item.participants?.every(
    p => p.registration_status === 'verified'
  )

  const allReRegisteredVerified = item.participants?.every(
    p => p.reregistration_status === 'verified'
  )

  // daftar ulang belum diputus
  const notYet = ['not_yet', null, ''].includes(item.reregistration_status)

  return allRegisteredVerified && allReRegisteredVerified && notYet
}



const canReRegisterMember = (ep) => {
  const verified = ep.registration_status === 'verified'
  const notYet   = ['not_yet', null, ''].includes(ep.reregistration_status)
  return verified && notYet
}



// API
const fetchItems = async (page = 1) => {
  if (!eventId.value) return
  isLoading.value = true

  try {
    const res = await axios.get(
      '/api/v1/event-participants/re-registration/index',
      {
        params: {
          event_id: eventId.value,
          page,
          per_page: perPage.value,
          search: search.value,
          reregistration_status: filters.value.reregistration_status || '',
          event_group_id: filters.value.event_group_id || '',   // ✅ tambah ini
        }
      }
    )


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
    Swal.fire('Gagal', 'Gagal memuat data daftar ulang.', 'error')
  } finally {
    isLoading.value = false
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
watch(() => eventId.value, (val) => val && fetchItems(1) && fetchEventMasterData())

onMounted(async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event terlebih dahulu.',
      'info'
    )
  } else {
    await fetchEventMasterData()
    fetchItems(1)
  }
})
</script>

<style scoped>
.badge-pink { background-color: #e83e8c; color: #fff; }
.btn-xs { padding: 2px 6px !important; font-size: 0.65rem !important; line-height: 1 !important; }
.btn-xs i { font-size: 0.55rem !important; }
.text-xs { font-size: 0.75rem; }
.gender-badge { width: 22px; text-align: center; }

.progress.progress-sm { height: 16px; font-size: 10px; }
</style>
