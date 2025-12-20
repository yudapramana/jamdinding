<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const route = useRoute()
const router = useRouter()
const authUserStore = useAuthUserStore()

const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

// route param (bisa kosong)
const competitionId = computed(() => String(route.params.id || ''))

// selector
const isLoadingMeta = ref(false)
const competitionsByRound = ref([]) // [{id,name,competitions:[{id,full_name}]}]
const selectedCompetitionId = ref(String(route.params.id || ''))

// data rekap
const loading = ref(false)
const q = ref('')
const status = ref('') // draft/submitted/locked
const competition = ref(null)
const judges = ref([])
const participants = ref([])

const fmt = (n) => (Number(n || 0)).toFixed(2)

// =========================
// META: load competitions tree
// =========================
const fetchMeta = async () => {
  if (!eventId.value) return
  isLoadingMeta.value = true
  try {
    const { data } = await axios.get(`/api/v1/events/${eventId.value}/competitions/tree`, {
      params: { search: '' },
    })
    competitionsByRound.value = data.rounds || []
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat daftar kompetisi.', 'error')
  } finally {
    isLoadingMeta.value = false
  }
}

// =========================
// DATA: fetch scores
// =========================
const fetchData = async () => {
  // ✅ jangan call API kalau belum ada kompetisi dipilih
  const id = selectedCompetitionId.value || competitionId.value
  if (!id) {
    competition.value = null
    judges.value = []
    participants.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${id}/scores`, {
      params: { search: q.value || undefined, status: status.value || undefined },
    })
    competition.value = data.competition
    judges.value = data.judges || []
    participants.value = data.participants || []
  } finally {
    loading.value = false
  }
}

const updateScoresheet = async (scoresheetId, payload) => {
  const id = selectedCompetitionId.value || competitionId.value
  if (!id || !scoresheetId) return
  await axios.patch(`/api/v1/event-competitions/${id}/scoresheets/${scoresheetId}`, payload)
  await fetchData()
}

// =========================
// URL sync (tanpa :id -> pilih -> masuk :id)
// =========================
const pushCompetitionToUrl = async (id) => {
  if (!id) return
  if (route.name !== 'admin.event-competitions.scores.index' || String(route.params.id) !== String(id)) {
    await router.replace({
      name: 'admin.event-competitions.scores.index',
      params: { id: String(id) },
      query: { ...route.query },
    })
  }
}

// =========================
// lifecycle
// =========================
onMounted(async () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
    return
  }
  await fetchMeta()

  // kalau dibuka dengan :id, langsung load
  if (selectedCompetitionId.value) {
    await fetchData()
  }
})

// route id berubah (misal user buka dari link)
watch(
  () => route.params.id,
  async (val) => {
    const v = String(val || '')
    selectedCompetitionId.value = v
    await fetchData()
  }
)

// saat user memilih kompetisi dari dropdown
watch(
  () => selectedCompetitionId.value,
  async (val, oldVal) => {
    if (!val) {
      competition.value = null
      judges.value = []
      participants.value = []
      return
    }
    // await pushCompetitionToUrl(val)
    await fetchData()
  }
)
</script>

<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Rekap Nilai Kompetisi</h1>
          <p class="mb-0 text-muted text-sm">
            {{ competition?.full_name || 'Pilih kompetisi terlebih dahulu.' }}
          </p>

          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
          </p>
        </div>

        <button class="btn btn-sm btn-outline-secondary" @click="fetchData" :disabled="loading || !selectedCompetitionId">
          <i class="fas fa-sync"></i> Refresh
        </button>
      </div>

      <!-- SELECT COMPETITION -->
      <div class="row mt-3">
        <div class="col-md-12 mb-2">
          <label class="mb-1 text-sm">Kompetisi <span class="text-danger">*</span></label>
          <select
            v-model="selectedCompetitionId"
            class="form-control form-control-sm"
            :disabled="!eventId || isLoadingMeta"
          >
            <option value="" disabled>-- Pilih Kompetisi --</option>

            <optgroup
              v-for="r in competitionsByRound"
              :key="'round-' + r.id"
              :label="r.name"
            >
              <option v-for="c in r.competitions" :key="c.id" :value="String(c.id)">
                {{ c.full_name }}
              </option>
            </optgroup>
          </select>

          <small v-if="isLoadingMeta" class="text-muted">
            <i class="fas fa-spinner fa-spin mr-1"></i> Memuat daftar kompetisi...
          </small>
        </div>
      </div>

      <!-- FILTER -->
      <div class="row">
        <div class="col-md-6 mb-2">
          <input
            v-model="q"
            class="form-control form-control-sm"
            placeholder="Cari nama peserta..."
            :disabled="!selectedCompetitionId"
            @keyup.enter="fetchData"
          >
        </div>
        <div class="col-md-3 mb-2">
          <select v-model="status" class="form-control form-control-sm" :disabled="!selectedCompetitionId" @change="fetchData">
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="locked">Locked</option>
          </select>
        </div>
        <div class="col-md-3 mb-2">
          <button class="btn btn-sm btn-primary w-100" @click="fetchData" :disabled="loading || !selectedCompetitionId">
            <i class="fas fa-search"></i> Filter
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:50px">#</th>
                <th>Peserta</th>
                <th>Kontingen</th>
                <th v-for="j in judges" :key="j.id" class="text-center">
                  {{ j.name }}
                </th>
                <th class="text-center">AVG</th>
                <th class="text-center">SUM</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="!selectedCompetitionId">
                <td :colspan="6 + judges.length" class="text-center p-3 text-muted">
                  Silakan pilih kompetisi terlebih dahulu.
                </td>
              </tr>

              <tr v-else-if="loading">
                <td :colspan="6 + judges.length" class="text-center p-3 text-muted">Loading...</td>
              </tr>

              <tr v-else-if="participants.length === 0">
                <td :colspan="6 + judges.length" class="text-center p-3 text-muted">Tidak ada data.</td>
              </tr>

              <tr v-else v-for="(p, idx) in participants" :key="p.event_participant_id">
                <td>{{ idx + 1 }}</td>
                <td>
                  <div class="font-weight-bold">{{ p.full_name }}</div>
                  <div class="text-muted text-xs">
                    {{ p.branch || '-' }} • {{ p.group || '-' }} • {{ p.category || '-' }}
                  </div>
                </td>
                <td>{{ p.contingent || '-' }}</td>

                <td v-for="j in judges" :key="j.id" class="text-center">
                  <div class="d-flex flex-column align-items-center">
                    <span>{{ fmt(p.scores?.[j.id]) }}</span>

                    <div class="btn-group btn-group-sm mt-1">
                      <button
                        class="btn btn-outline-secondary"
                        :disabled="!p.scoresheet_id_by_judge?.[j.id]"
                        @click="updateScoresheet(p.scoresheet_id_by_judge?.[j.id], { status: 'locked' })"
                        title="Lock"
                      >
                        <i class="fas fa-lock"></i>
                      </button>
                      <button
                        class="btn btn-outline-secondary"
                        :disabled="!p.scoresheet_id_by_judge?.[j.id]"
                        @click="updateScoresheet(p.scoresheet_id_by_judge?.[j.id], { status: 'submitted' })"
                        title="Unlock (Submitted)"
                      >
                        <i class="fas fa-unlock"></i>
                      </button>
                    </div>

                    <small class="text-muted mt-1">
                      {{ p.status_by_judge?.[j.id] || '-' }}
                    </small>
                  </div>
                </td>

                <td class="text-center font-weight-bold">{{ fmt(p.avg_score) }}</td>
                <td class="text-center">{{ fmt(p.sum_score) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer text-muted text-sm">
          Total peserta: <strong>{{ participants.length }}</strong>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.text-xs { font-size: .75rem; }
</style>
