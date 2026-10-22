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
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">

      <!-- ============ SELECTOR ============ -->
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <div class="form-row">

            <!-- KOMPETISI -->
            <div class="form-group col-md-6 mb-2">
              <label class="text-sm mb-1">Kompetisi <span class="text-danger">*</span></label>
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
                {{ isTeam ? 'Tim' : 'Peserta' }} <span class="text-danger">*</span>
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
        class="card mt-3 shadow-sm"
        v-if="selectedCompetitionId && selectedEventParticipantId"
      >
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
          <div>
            <strong class="text-md d-block">
              {{ formData?.competition?.full_name || 'Form Penilaian' }}
            </strong>
            <div class="mt-1">
              <span
                class="badge"
                :class="{
                  'badge-secondary': scoringStatus === 'draft',
                  'badge-success': scoringStatus === 'submitted',
                  'badge-dark': scoringStatus === 'locked',
                }"
              >
                STATUS: {{ scoringStatus.toUpperCase() }}
              </span>
            </div>
          </div>

          <button
            class="btn btn-sm btn-outline-primary"
            :disabled="isLoadingForm"
            @click="loadForm"
          >
            <i class="fas fa-sync mr-1"></i> Reload Form
          </button>
        </div>

        <div v-if="isLoadingForm" class="card-body text-center py-4">
          <i class="fas fa-spinner fa-spin mr-2"></i> Memuat form penilaian...
        </div>

        <div
          v-else-if="judges.length && components.length"
          class="card-body p-0"
        >
          <!-- KETERANGAN INITIAL HAKIM -->
          <div class="px-3 pt-3 pb-2 bg-light border-bottom">
            <span class="text-sm font-weight-bold d-block mb-2 text-muted">
              <i class="fas fa-users mr-1"></i> Keterangan Majelis Hakim:
            </span>
            <div class="d-flex flex-wrap">
              <div 
                v-for="j in judges" 
                :key="'legend-'+j.id" 
                class="mr-3 mb-2 px-2 py-1 bg-white border rounded text-sm shadow-sm"
              >
                <strong class="text-primary">{{ judgeInitials(j.name) }}</strong> &nbsp;—&nbsp; <span class="text-muted">{{ j.name }}</span>
              </div>
            </div>
          </div>

          <!-- TABEL SCORING -->
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm table-compact mb-0">
              <thead class="thead-light">
                <!-- BARIS HEADER ATAS -->
                <tr>
                  <th class="py-2 align-middle border-bottom-0"></th>
                  <th
                    :colspan="judges.length"
                    class="text-center py-2 text-sm font-weight-bold border-bottom-0"
                  >
                    Penilaian Dewan Hakim
                  </th>
                </tr>

                <!-- BARIS HEADER NAMA HAKIM -->
                <tr>
                  <th style="min-width:260px" class="py-2 align-middle">Komponen Penilaian</th>
                  <th
                    v-for="j in judges"
                    :key="j.id"
                    class="text-center py-2 align-middle"
                    style="min-width:85px; width: 85px;"
                  >
                    <div class="font-weight-bold text-sm text-primary">
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
                  <td class="py-2 align-middle">
                    <strong class="text-sm d-block">{{ c.field_name }}</strong>
                    <div class="text-muted text-xs mt-1">
                      Nilai Maksimal: <strong>{{ c.max_score }}</strong> &bull; Bobot: <strong>{{ c.weight }}%</strong>
                    </div>
                  </td>

                  <td
                    v-for="j in judges"
                    :key="j.id"
                    class="text-center py-2 align-middle"
                  >
                    <template v-if="canJudge(j.id, c)">
                      <input
                        type="number"
                        class="form-control text-center mx-auto scoring-input"
                        min="0"
                        :max="c.max_score"
                        v-model.number="rowsMap[j.id][c.id].score"
                        @input="onScoreInput(j.id, c)"
                        :disabled="scoringStatus !== 'draft'"
                      />
                      <div class="text-muted text-xxs mt-1 font-weight-bold">
                        W: {{ weightedScore(j.id, c.id) }}
                      </div>
                    </template>

                    <template v-else>
                      <span class="badge badge-light border badge-xs text-muted">
                        TIDAK MENILAI
                      </span>
                    </template>
                  </td>
                </tr>

                <tr class="bg-light">
                  <td class="text-right font-weight-bold py-2 align-middle text-sm">
                    TOTAL KESELURUHAN
                  </td>
                  <td
                    v-for="j in judges"
                    :key="j.id"
                    class="text-center py-2 align-middle"
                  >
                    <span class="badge badge-primary badge-sm px-2 py-1 shadow-sm" style="font-size: 14px;">
                      {{ totalByJudge(j.id) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card-footer bg-white py-3 text-right border-top">
          <div
            v-if="!isAllRangeValid"
            class="alert alert-warning py-2 px-3 mb-3 text-sm text-left shadow-sm"
            style="border-radius: 6px;"
          >
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Selisih nilai antar hakim pada satu komponen tidak boleh lebih dari
            <strong>{{ RANGE_LIMIT }}</strong> poin.
          </div>

          <button
            v-if="scoringStatus === 'draft'"
            class="btn btn-outline-primary mr-2"
            :disabled="isSaving || !isAllRangeValid"
            @click="saveDraft"
          >
            <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
            <i v-else class="fas fa-save mr-1"></i>
            Simpan Draft
          </button>

          <button
            v-if="scoringStatus === 'draft'"
            class="btn btn-success"
            :disabled="isSaving || !isAllRangeValid"
            @click="submitScore"
          >
            <i class="fas fa-paper-plane mr-1"></i>
            Submit Permanen
          </button>
        </div>
      </div>

      <div
        v-else
        class="alert alert-info mt-3 shadow-sm text-center py-4"
        style="border-radius: 8px;"
      >
        <i class="fas fa-hand-pointer fa-2x mb-2 d-block text-lightblue"></i>
        Silakan pilih <strong>kompetisi</strong> dan <strong>peserta/tim</strong> di atas untuk mulai melakukan input nilai.
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
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
const route = useRoute()
const eventId = computed(() => authUserStore.eventData?.id)

const selectedCompetitionId = ref('')
const selectedEventParticipantId = ref('')

const competitionsByRound = computed(() => authUserStore.eventCompetitionsTree)
const participants = ref([])
const competitionInfo = ref(null)

const formData = ref(null)
const rowsMap = ref({})

const isLoadingMeta = ref(false)
const isLoadingParticipants = ref(false)
const isLoadingForm = ref(false)
const isSaving = ref(false)
const scoringStatus = ref('draft') 

/* ================= COMPUTED ================= */
const isTeam = computed(() => competitionInfo.value?.event_group?.is_team)
const judges = computed(() => formData.value?.judges || [])
const components = computed(() => formData.value?.components || [])

/* ================= PARTICIPANT GROUPING ================= */
const optionLabel = (p) => {
  if (isTeam.value) {
    return p.team_name || p.contingent || 'Tim'
  }
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

  return Array.from(catMap.values()).map((c) => ({
    id: c.id,
    name: c.name,
    count: c.count,
    contingents: Array.from(c.contingents.values()),
  }))
})


/* ================= HELPERS ================= */
const canJudge = (judgeId, component) => {
  if (formData.value?.event_group?.judge_assignment_mode === 'BY_PANEL') {
    return true
  }
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
  await authUserStore.fetchEventCompetitionsTree(eventId.value)
  isLoadingMeta.value = false

  await nextTick()
  if (route.params.id) {
    selectedCompetitionId.value = String(route.params.id)
  }
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
      only_verified: 1,
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
  const firstSheet = data.scoresheets?.[0]
  scoringStatus.value = firstSheet?.status || 'draft'

  const sheets = data.scoresheets || []
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

  await nextTick()
  if (route.query.event_participant_id) {
    selectedEventParticipantId.value = String(route.query.event_participant_id)
  }
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
  padding: 0.5rem 0.4rem; /* Padding diperbesar agar tidak terlalu sesak */
}

/* =========================================
   STYLE INPUT ANGKA YANG BERSIH & RAPI
========================================== */
.scoring-input {
  width: 70px !important;
  height: 32px;
  padding: 4px;
  font-size: 0.95rem;
  font-weight: 600;
  border: 1px solid #ced4da;
  border-radius: 6px;
  transition: all 0.2s;
}

.scoring-input:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

/* Hilangkan panah spinner bawaan input number */
.scoring-input::-webkit-outer-spin-button,
.scoring-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.scoring-input[type=number] {
  -moz-appearance: textfield;
}

.text-xxs {
  font-size: 0.7rem;
}

.badge-xs {
  font-size: 0.7rem;
  padding: 0.35em 0.5em;
}

.badge-sm {
  font-size: 0.8rem;
}
</style>