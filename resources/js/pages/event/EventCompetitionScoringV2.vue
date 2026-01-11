<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Input Nilai Kompetisi</h1>
          <p class="mb-0 text-muted text-sm">
            Pilih kompetisi dan {{ isTeam ? 'tim' : 'peserta' }}, lalu input nilai sesuai penugasan majelis hakim.
          </p>

          <div v-if="competitionInfo" class="text-muted text-sm mt-1">
            Group:
            <strong>{{ competitionInfo.event_group.full_name }}</strong>
            • Mode:
            <span
              class="badge"
              :class="competitionInfo.event_group.judge_assignment_mode === 'BY_PANEL'
                ? 'badge-info'
                : 'badge-warning'"
            >
              {{ competitionInfo.event_group.judge_assignment_mode }}
            </span>
            •
            <span class="badge badge-light border">
              {{ isTeam ? 'TEAM' : 'INDIVIDUAL' }}
            </span>
          </div>
        </div>

        <button
          class="btn btn-sm btn-outline-secondary"
          :disabled="isLoadingMeta"
          @click="reloadAll"
        >
          <i class="fas fa-sync mr-1"></i> Refresh
        </button>
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">

      <!-- ============ SELECTOR ============ -->
      <div class="card">
        <div class="card-header">
          <div class="form-row">

            <!-- KOMPETISI -->
            <div class="form-group col-md-6 mb-2">
              <label class="text-sm mb-1">Kompetisi *</label>
              <select
                v-model="selectedCompetitionId"
                class="form-control form-control-sm"
                :disabled="isLoadingMeta"
              >
                <option value="" disabled>-- Pilih Kompetisi --</option>
                <optgroup
                  v-for="r in competitionsByRound"
                  :key="r.id"
                  :label="r.name"
                >
                  <option
                    v-for="c in r.competitions"
                    :key="c.id"
                    :value="String(c.id)"
                  >
                    {{ c.full_name }}
                  </option>
                </optgroup>
              </select>
            </div>

            <!-- PESERTA / TIM -->
            <div class="form-group col-md-6 mb-2">
              <label class="text-sm mb-1">
                {{ isTeam ? 'Tim' : 'Peserta' }} *
              </label>

              <select
                v-model="selectedEventParticipantId"
                class="form-control form-control-sm"
                :disabled="!competitionInfo || isLoadingParticipants"
              >
                <option value="" disabled>
                  {{ isLoadingParticipants ? 'Memuat...' : '-- Pilih --' }}
                </option>

                <template v-for="cat in participantOptions" :key="cat.id">
                  <optgroup :label="`${cat.name} (${cat.count})`">
                    <template
                      v-for="cont in cat.contingents"
                      :key="cont.name"
                    >
                      <option disabled class="opt-contingent-header">
                        — {{ cont.name }} ({{ cont.count }})
                      </option>
                      <option
                        v-for="p in cont.items"
                        :key="p.id"
                        :value="String(p.id)"
                      >
                        {{ optionLabel(p, cat.name, cont.name) }}
                      </option>
                    </template>
                  </optgroup>
                </template>
              </select>
            </div>

          </div>
        </div>
      </div>

      <!-- ============ FORM PENILAIAN ============ -->
      <div
        class="card mt-3"
        v-if="selectedCompetitionId && selectedEventParticipantId"
      >
        <div class="card-header d-flex justify-content-between align-items-center py-2">
          <strong class="text-sm">
            {{ formData?.competition?.full_name || 'Form Penilaian' }}
          </strong>

          <span
            class="badge ml-2"
            :class="{
              'badge-secondary': scoringStatus === 'draft',
              'badge-success': scoringStatus === 'submitted',
              'badge-dark': scoringStatus === 'locked',
            }"
          >
            {{ scoringStatus.toUpperCase() }}
          </span>


          <button
            class="btn btn-xs btn-outline-primary"
            :disabled="isLoadingForm"
            @click="loadForm"
          >
            <i class="fas fa-sync"></i>
          </button>
        </div>

        <div v-if="isLoadingForm" class="card-body text-center py-3">
          <i class="fas fa-spinner fa-spin mr-1"></i> Memuat form...
        </div>

        <div
          v-else-if="judges.length && components.length"
          class="card-body p-0 table-responsive"
        >
          <table class="table table-bordered table-sm table-compact mb-0">
            <thead class="thead-light">
              <!-- BARIS HEADER ATAS -->
              <tr>
                <th class="py-1"></th>
                <th
                  :colspan="judges.length"
                  class="text-center py-1 text-sm font-weight-bold"
                >
                  Majelis Dewan Hakim ({{ allJudgeInitials }})
                </th>
              </tr>

              <!-- BARIS HEADER NAMA HAKIM -->
              <tr>
                <th style="min-width:240px" class="py-1">Komponen</th>
                <th
                  v-for="j in judges"
                  :key="j.id"
                  class="text-center py-1"
                  style="min-width:70px"
                >
                  <div class="font-weight-bold text-sm">
                    {{ judgeInitials(j.name) }}
                  </div>
                </th>
              </tr>
            </thead>


            <tbody>
              <tr
                v-for="c in components"
                :key="c.id"
                :class="{ 'table-warning': !isComponentRangeValid(c) }"
              >

                <td class="py-1">
                  <strong class="text-sm">{{ c.field_name }}</strong>
                  <div class="text-muted text-xs">
                    Max {{ c.max_score }} • {{ c.weight }}%
                  </div>
                </td>

                <td
                  v-for="j in judges"
                  :key="j.id"
                  class="text-center py-1"
                >
                  <template v-if="canJudge(j.id, c)">
                    <input
                      type="number"
                      class="form-control form-control-xs text-center mx-auto"
                      style="width:60px"
                      min="0"
                      :max="c.max_score"
                      v-model.number="rowsMap[j.id][c.id].score"
                      @input="onScoreInput(j.id, c)"
                      :disabled="scoringStatus !== 'draft'"

                    />
                    <div class="text-muted text-xxs mt-1">
                      W: {{ weightedScore(j.id, c.id) }}
                    </div>
                  </template>

                  <template v-else>
                    <span class="badge badge-light border badge-xs">
                      –
                    </span>
                  </template>
                </td>
              </tr>

              <tr class="bg-light">
                <td class="text-right font-weight-bold py-1 text-sm">
                  TOTAL
                </td>
                <td
                  v-for="j in judges"
                  :key="j.id"
                  class="text-center py-1"
                >
                  <span class="badge badge-primary badge-sm">
                    {{ totalByJudge(j.id) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer py-2 text-right">
          <div
            v-if="!isAllRangeValid"
            class="alert alert-warning py-1 px-2 mb-2 text-sm"
          >
            Selisih nilai antar hakim pada satu komponen tidak boleh lebih dari
            <strong>{{ RANGE_LIMIT }}</strong> poin.
          </div>


          <button
            v-if="scoringStatus === 'draft'"
            class="btn btn-sm btn-primary"
            :disabled="isSaving || !isAllRangeValid"
            @click="saveDraft"
          >
            <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
            Simpan Draft
          </button>


          <button
            v-if="scoringStatus === 'draft'"
            class="btn btn-sm btn-success mr-2"
            :disabled="isSaving || !isAllRangeValid"
            @click="submitScore"
          >
            <i class="fas fa-paper-plane mr-1"></i>
            Submit
          </button>

        </div>
      </div>


      <div
        v-else
        class="alert alert-info mt-3"
      >
        Pilih <strong>kompetisi</strong> dan <strong>peserta/tim</strong> untuk mulai input nilai.
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const RANGE_LIMIT = 3
const onScoreInput = (judgeId, component) => {
  let v = Number(rowsMap.value[judgeId][component.id].score ?? 0)

  // ❌ tidak boleh minus
  if (v < 0) v = 0

  // ❌ tidak boleh lebih dari max
  if (component.max_score > 0 && v > component.max_score) {
    v = component.max_score
  }

  rowsMap.value[judgeId][component.id].score = v
}

const getScoresOfComponent = (component) => {
  return judges.value
    .filter(j => canJudge(j.id, component))
    .map(j => Number(rowsMap.value[j.id][component.id].score ?? 0))
}

const isComponentRangeValid = (component) => {
  const scores = getScoresOfComponent(component)
  if (scores.length < 2) return true

  const min = Math.min(...scores)
  const max = Math.max(...scores)

  return (max - min) <= RANGE_LIMIT
}

const isAllRangeValid = computed(() =>
  components.value.every(c => isComponentRangeValid(c))
)




/* ================= STATE ================= */
const authUserStore = useAuthUserStore()
const eventId = computed(() => authUserStore.eventData?.id)

const selectedCompetitionId = ref('')
const selectedEventParticipantId = ref('')

const competitionsByRound = ref([])
const participants = ref([])
const competitionInfo = ref(null)

const formData = ref(null)
const rowsMap = ref({})

const isLoadingMeta = ref(false)
const isLoadingParticipants = ref(false)
const isLoadingForm = ref(false)
const isSaving = ref(false)
const scoringStatus = ref('draft') 
// 'draft' | 'submitted' | 'locked'


/* ================= COMPUTED ================= */
const isTeam = computed(() => competitionInfo.value?.event_group?.is_team)
const judges = computed(() => formData.value?.judges || [])
const components = computed(() => formData.value?.components || [])

/* ================= PARTICIPANT GROUPING ================= */
const optionLabel = (p) => {
  // TEAM MODE (kalau nanti ada)
  if (isTeam.value) {
    return p.team_name || p.contingent || 'Tim'
  }

  // INDIVIDU
  const number = p.participant?.participant_number || '-'
  const name = p.participant?.full_name || '-'
  const nik = p.participant?.nik || ''

  return nik
    ? `${number} - ${name} (${nik})`
    : `${number} - ${name}`
}

const judgeInitials = (name = '') => {
  if (!name) return ''
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map(w => w[0].toUpperCase())
    .join('')
}

const allJudgeInitials = computed(() =>
  judges.value.map(j => judgeInitials(j.name)).join(', ')
)



const participantOptions = computed(() => {
  const list = participants.value || []
  const catMap = new Map()

  for (const p of list) {
    const catName = p.event_category?.full_name || 'Tanpa Kategori'
    const catId = String(p.event_category?.id || catName)

    if (!catMap.has(catId)) {
      catMap.set(catId, {
        id: catId,
        name: catName,
        count: 0,
        contingents: new Map(),
      })
    }

    const cat = catMap.get(catId)
    cat.count++

    const contName = p.contingent || '-'
    if (!cat.contingents.has(contName)) {
      cat.contingents.set(contName, {
        name: contName,
        count: 0,
        items: [],
      })
    }

    const cont = cat.contingents.get(contName)
    cont.count++
    cont.items.push(p)
  }

  // convert map → array
  return Array.from(catMap.values()).map((c) => ({
    id: c.id,
    name: c.name,
    count: c.count,
    contingents: Array.from(c.contingents.values()),
  }))
})


/* ================= HELPERS ================= */
const canJudge = (judgeId, component) => {
  // BY_PANEL → semua hakim menilai semua komponen
  if (formData.value?.event_group?.judge_assignment_mode === 'BY_PANEL') {
    return true
  }

  // BY_COMPONENT → cek assignment
  if (!component.assigned_judge_ids) return false

  return component.assigned_judge_ids.includes(judgeId)
}


const weightedScore = (judgeId, componentId) => {
  const c = components.value.find(x => x.id === componentId)
  const score = rowsMap.value[judgeId][componentId].score || 0
  return (score * (c.weight / 100)).toFixed(2)
}

const totalByJudge = (judgeId) => {
  let t = 0
  for (const c of components.value) {
    if (!canJudge(judgeId, c)) continue
    t += Number(weightedScore(judgeId, c.id))
  }
  return t.toFixed(2)
}

/* ================= API ================= */
const fetchMeta = async () => {
  isLoadingMeta.value = true
  const { data } = await axios.get(`/api/v1/events/${eventId.value}/competitions/tree`)
  competitionsByRound.value = data.rounds || []
  isLoadingMeta.value = false
}

const fetchCompetitionInfo = async () => {
  const { data } = await axios.get(`/api/v1/event-competitions/${selectedCompetitionId.value}`)
  competitionInfo.value = data
}

const fetchParticipants = async () => {
  isLoadingParticipants.value = true
  const { data } = await axios.get(`/api/v1/events/${eventId.value}/participants/simple`, {
    params: {
      event_group_id: competitionInfo.value.event_group_id,
      is_team: isTeam.value ? 1 : 0,
    },
  })
  participants.value = data.data || []
  isLoadingParticipants.value = false
}

const loadForm = async () => {
  isLoadingForm.value = true

  const { data } = await axios.get(
    `/api/v1/event-competitions/${selectedCompetitionId.value}/scoring/form-v2`,
    { params: { event_participant_id: selectedEventParticipantId.value } }
  )

  formData.value = data
  // Ambil status dari salah satu scoresheet (semua hakim harus sama)
  const firstSheet = data.scoresheets?.[0]
  scoringStatus.value = firstSheet?.status || 'draft'

  const sheets = data.scoresheets || []

  // ===============================
  // Build rowsMap with hydration
  // ===============================
  const map = {}

  for (const j of judges.value) {
    const sheet = sheets.find(s => s.event_judge_id === j.id)
    const items = sheet?.items || []

    map[j.id] = {}

    for (const c of components.value) {
      const item = items.find(i => i.event_field_component_id === c.id)

      map[j.id][c.id] = {
        score: item ? Number(item.score) : 0,
      }
    }
  }

  rowsMap.value = map
  isLoadingForm.value = false
}


const saveDraft = async () => {
  isSaving.value = true
  const rows = judges.value.map(j => ({
    event_judge_id: j.id,
    items: Object.entries(rowsMap.value[j.id]).map(([cid, v]) => ({
      event_field_component_id: cid,
      score: v.score,
    })),
  }))

  await axios.post(
    `/api/v1/event-competitions/${selectedCompetitionId.value}/scoring/draft-v2`,
    {
      event_participant_id: selectedEventParticipantId.value,
      rows,
    }
  )

  Swal.fire('Berhasil', 'Draft tersimpan', 'success')
  scoringStatus.value = 'draft'
  isSaving.value = false
}

const submitScore = async () => {
  const confirm = await Swal.fire({
    title: 'Submit Nilai?',
    text: 'Setelah submit, nilai tidak dapat diubah.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Submit',
    cancelButtonText: 'Batal',
  })

  if (!confirm.isConfirmed) return

  isSaving.value = true

  await axios.post(
    `/api/v1/event-competitions/${selectedCompetitionId.value}/scoring/submit-v2`,
    {
      event_participant_id: selectedEventParticipantId.value,
    }
  )

  scoringStatus.value = 'submitted'

  Swal.fire('Berhasil', 'Nilai berhasil disubmit', 'success')
  isSaving.value = false
}


/* ================= WATCH ================= */
watch(selectedCompetitionId, async (v) => {
  selectedEventParticipantId.value = ''
  competitionInfo.value = null
  participants.value = []
  formData.value = null
  if (!v) return
  await fetchCompetitionInfo()
  await fetchParticipants()
})

watch(selectedEventParticipantId, async (v) => {
  formData.value = null
  if (v) await loadForm()
})

onMounted(fetchMeta)
</script>

<style scoped>
.text-xs { font-size: 0.75rem; }
.opt-contingent-header { font-weight: 700; color: #495057; }

.table-compact th,
.table-compact td {
  padding: 0.3rem;
}

.form-control-xs {
  height: 26px;
  padding: 2px 4px;
  font-size: 0.75rem;
}

.text-xxs {
  font-size: 0.65rem;
}

.btn-xs {
  padding: 2px 6px;
  font-size: 0.75rem;
}

.badge-xs {
  font-size: 0.7rem;
  padding: 0.25em 0.4em;
}

.badge-sm {
  font-size: 0.75rem;
}

</style>
