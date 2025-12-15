<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Input Nilai Kompetisi</h1>
          <p class="mb-0 text-muted text-sm">
            Pilih kompetisi & peserta, lalu input nilai per hakim untuk semua komponen penilaian.
          </p>

          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
          </p>
        </div>

        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="!eventId || isLoadingMeta" @click="reloadAll">
            <i class="fas fa-sync mr-1"></i> Refresh
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

      <!-- SELECTOR -->
      <div class="card">
        <div class="card-header">
          <div class="form-row">
            <div class="form-group col-md-6 mb-2">
              <label class="mb-1 text-sm">Kompetisi <span class="text-danger">*</span></label>
              <select v-model="selectedCompetitionId" class="form-control form-control-sm" :disabled="!eventId || isLoadingMeta">
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

            <div class="form-group col-md-6 mb-2">
              <label class="mb-1 text-sm">Peserta (Event Participant) <span class="text-danger">*</span></label>
              <select v-model="selectedEventParticipantId" class="form-control form-control-sm" :disabled="!eventId || !selectedCompetitionId || !competitionInfo?.event_group_id || isLoadingParticipants"
>
                <option value="" disabled>
                  {{ isLoadingParticipants ? 'Memuat peserta...' : '-- Pilih Peserta --' }}
                </option>
                <option v-for="p in participants" :key="p.id" :value="String(p.id)">
                  {{ p.participant?.full_name || p.full_name || 'Peserta' }} — {{ p.participant?.nik || p.nik || '-' }}
                  • {{ p.event_category?.full_name || p.category_name || '-' }}
                  • {{ p.contingent || '-' }}
                </option>
              </select>
              <small v-if="!selectedCompetitionId" class="text-muted">Pilih kompetisi dulu.</small>
            </div>
          </div>
        </div>
      </div>

      <!-- GRID -->
      <div class="card" v-if="selectedCompetitionId && selectedEventParticipantId">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <strong>{{ formData?.competition?.full_name || 'Kompetisi' }}</strong>
            <div class="text-muted text-sm" v-if="formData?.participant">
              Peserta: <strong>{{ formData.participant.full_name }}</strong>
              <span class="ml-2">•</span>
              NIK: <strong>{{ formData.participant.nik || '-' }}</strong>
              <span class="ml-2">•</span>
              Kontingen: <strong>{{ formData.participant.contingent || '-' }}</strong>
            </div>
          </div>

          <div class="btn-group">
            <button class="btn btn-sm btn-outline-primary" :disabled="isLoadingForm" @click="loadForm">
              <i class="fas fa-sync mr-1"></i> Reload Form
            </button>
          </div>
        </div>

        <div class="card-body p-0 table-responsive" v-if="!isLoadingForm && judges.length && components.length">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="min-width: 280px">Komponen</th>
                <th
                  v-for="j in judges"
                  :key="'j-' + j.id"
                  style="min-width: 260px"
                  class="text-center"
                >
                  <div class="d-flex flex-column align-items-center">
                    <div class="font-weight-bold">{{ j.name }}</div>
                    <div class="text-muted text-xs mt-1">
                      Total: <span class="badge badge-light border">{{ format2(totalByJudge(j.id)) }}</span>
                      <span v-if="sheetStatusByJudge(j.id)" class="ml-1 badge" :class="statusBadge(sheetStatusByJudge(j.id))">
                        {{ sheetStatusByJudge(j.id) }}
                      </span>
                    </div>
                  </div>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="c in components" :key="'c-' + c.id">
                <td>
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <strong>{{ c.field_name }}</strong>
                      <div class="text-muted text-xs mt-1">
                        Max: <strong>{{ format2(c.max_score || 0) }}</strong>
                        • Weight: <strong>{{ c.weight ?? 0 }}%</strong>
                      </div>
                    </div>
                    <span class="badge badge-light border">
                      Weighted per judge: {{ format2(maxWeightedOfComponent(c)) }}
                    </span>
                  </div>
                </td>

                <td
                  v-for="j in judges"
                  :key="'cell-' + c.id + '-' + j.id"
                >
                  <div class="form-row no-gutters">
                    <div class="col-5 pr-1">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control form-control-sm"
                        :disabled="isLocked(j.id)"
                        :max="Number(c.max_score || 0) || null"
                        v-model.number="rowsMap[j.id].itemsMap[c.id].score"
                        @input="onScoreChange(j.id, c.id)"
                        placeholder="Score"
                      />
                      <small class="text-muted text-xs d-block mt-1">
                        W: <strong>{{ format2(weightedScore(j.id, c.id)) }}</strong>
                      </small>
                    </div>

                    <div class="col-7">
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        :disabled="isLocked(j.id)"
                        v-model="rowsMap[j.id].itemsMap[c.id].notes"
                        placeholder="Catatan"
                      />
                      <small class="text-muted text-xs d-block mt-1">
                        Max W: {{ format2(maxWeightedOfComponent(c)) }}
                      </small>
                    </div>
                  </div>
                </td>
              </tr>

              <!-- footer total row -->
              <tr class="bg-light">
                <td class="text-right font-weight-bold">
                  TOTAL
                </td>
                <td v-for="j in judges" :key="'total-' + j.id" class="text-center">
                  <span class="badge badge-primary">
                    {{ format2(totalByJudge(j.id)) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-body" v-else>
          <div v-if="isLoadingForm" class="text-center py-4">
            <i class="fas fa-spinner fa-spin mr-1"></i> Memuat form penilaian...
          </div>
          <div v-else class="text-center py-4 text-muted">
            Hakim / Komponen belum tersedia untuk kompetisi ini.
          </div>
        </div>

        <div class="card-footer">
          <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="text-muted text-sm">
              <span class="badge badge-light border">Auto hitung: score × (weight/100)</span>
              <span class="ml-2 badge badge-light border">Score dipotong otomatis jika melebihi max</span>
            </div>

            <div class="btn-group mt-2 mt-sm-0">
              <button
                v-if="showSaveDraft"
                class="btn btn-sm btn-outline-primary"
                :disabled="disableSaveDraft"
                @click="saveDraft"
              >
                <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
                Save Draft
              </button>

              <button
                v-if="showSubmit"
                class="btn btn-sm btn-outline-success"
                :disabled="disableSubmit"
                @click="submitScores"
              >
                Submit
              </button>

              <button
                v-if="showLock"
                class="btn btn-sm btn-outline-danger"
                :disabled="disableLock"
                @click="lockScores"
              >
                Lock
              </button>
            </div>

          </div>
        </div>
      </div>

      <!-- empty hint -->
      <div class="alert alert-info" v-else-if="eventId" style="border-radius: 8px;">
        Pilih <strong>kompetisi</strong> dan <strong>peserta</strong> untuk mulai input nilai.
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const route = useRoute()
const authUserStore = useAuthUserStore()
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)
const selectedCompetitionId = ref(String(route.params.id || ''))
const selectedEventParticipantId = ref(String(route.query.event_participant_id || ''))
const competitionInfo = ref(null) // { id, event_group_id, ... }

const fetchCompetitionInfo = async () => {
  if (!selectedCompetitionId.value) {
    competitionInfo.value = null
    return
  }
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${selectedCompetitionId.value}`)
    competitionInfo.value = data
  } catch (e) {
    console.error(e)
    competitionInfo.value = null
    Swal.fire('Gagal', 'Gagal memuat info kompetisi.', 'error')
  }
}


// =========================
// SELECTOR DATA
// =========================
const isLoadingMeta = ref(false)
const isLoadingParticipants = ref(false)

const competitionsByRound = ref([]) // [{id,name,competitions:[{id,full_name,...}]}]
const participants = ref([])        // event_participants simple list



// =========================
// FORM DATA (API form response)
// =========================
const isLoadingForm = ref(false)
const formData = ref(null)

const judges = computed(() => formData.value?.judges || [])
const components = computed(() => formData.value?.components || [])
const scoresheets = computed(() => formData.value?.scoresheets || [])

// rowsMap: { [judgeId]: { judge_id, status, itemsMap: { [componentId]: {score, notes} } } }
const rowsMap = ref({})

const isSaving = ref(false)

// =========================
// HELPERS
// =========================
const statusBadge = (s) => {
  if (s === 'draft') return 'badge-secondary'
  if (s === 'submitted') return 'badge-success'
  if (s === 'locked') return 'badge-danger'
  return 'badge-light'
}

const format2 = (n) => {
  const x = Number(n || 0)
  return x.toFixed(2)
}

const sheetStatusByJudge = (judgeId) => {
  const s = (scoresheets.value || []).find(x => String(x.judge_id) === String(judgeId))
  return s?.status || null
}

const isLocked = (judgeId) => sheetStatusByJudge(judgeId) === 'locked'

const weightedScore = (judgeId, componentId) => {
  const c = components.value.find(x => String(x.id) === String(componentId))
  const score = Number(rowsMap.value?.[judgeId]?.itemsMap?.[componentId]?.score || 0)
  const weight = Number(c?.weight || 0)
  return weight ? (score * (weight / 100)) : score
}

const totalByJudge = (judgeId) => {
  let total = 0
  for (const c of (components.value || [])) {
    total += Number(weightedScore(judgeId, c.id) || 0)
  }
  return Math.round(total * 100) / 100
}

const maxWeightedOfComponent = (c) => {
  const max = Number(c?.max_score || 0)
  const w = Number(c?.weight || 0)
  return w ? (max * (w / 100)) : max
}

const onScoreChange = (judgeId, componentId) => {
  const c = components.value.find(x => String(x.id) === String(componentId))
  const maxScore = Number(c?.max_score || 0)
  let v = Number(rowsMap.value[judgeId].itemsMap[componentId].score || 0)
  if (v < 0) v = 0
  if (maxScore > 0 && v > maxScore) v = maxScore
  rowsMap.value[judgeId].itemsMap[componentId].score = v
}

const overallStatus = computed(() => {
  const sheets = scoresheets.value || []
  if (!sheets.length) return 'new'

  const hasLocked = sheets.some(s => s.status === 'locked')
  if (hasLocked) return 'locked'

  const hasSubmitted = sheets.some(s => s.status === 'submitted')
  if (hasSubmitted) return 'submitted'

  const hasDraft = sheets.some(s => s.status === 'draft')
  if (hasDraft) return 'draft'

  return 'new'
})

// tombol tampil bertahap
const showSaveDraft = computed(() => overallStatus.value === 'new' || overallStatus.value === 'draft')
const showSubmit = computed(() => overallStatus.value === 'draft')
const showLock = computed(() => overallStatus.value === 'submitted')

// disable rule (biar aman)
const disableSaveDraft = computed(() => isSaving.value || !canSave.value || overallStatus.value === 'locked')
const disableSubmit = computed(() => isSaving.value || !canSubmit.value || overallStatus.value !== 'draft')
const disableLock = computed(() => isSaving.value || overallStatus.value !== 'submitted')


// =========================
// API CALLS
// =========================
const fetchMeta = async () => {
  if (!eventId.value) return
  isLoadingMeta.value = true
  try {
    // ambil tree/meta kompetisi per round (yang kamu sudah punya)
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

const fetchParticipants = async () => {
  if (!eventId.value) return

  const groupId = competitionInfo.value?.event_group_id
  if (!groupId) {
    participants.value = []
    return
  }

  isLoadingParticipants.value = true
  try {
    const { data } = await axios.get(`/api/v1/events/${eventId.value}/participants/simple`, {
      params: {
        per_page: 1000,
        event_group_id: groupId, // ✅ filter cabang/golongan sesuai kompetisi
      },
    })
    participants.value = data.data || data || []
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat daftar peserta (filtered).', 'error')
  } finally {
    isLoadingParticipants.value = false
  }
}


const loadForm = async () => {
  if (!selectedCompetitionId.value || !selectedEventParticipantId.value) return
  isLoadingForm.value = true
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${selectedCompetitionId.value}/scoring/form`, {
      params: { event_participant_id: selectedEventParticipantId.value },
    })
    formData.value = data
    hydrateRowsMapFromResponse()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal memuat form penilaian.', 'error')
  } finally {
    isLoadingForm.value = false
  }
}

const hydrateRowsMapFromResponse = () => {
  const jList = formData.value?.judges || []
  const cList = formData.value?.components || []
  const sheets = formData.value?.scoresheets || []

  const map = {}

  for (const j of jList) {
    const existing = sheets.find(s => String(s.judge_id) === String(j.id))
    const items = existing?.items || []

    const itemsMap = {}
    for (const c of cList) {
      const it = items.find(x => String(x.event_field_component_id) === String(c.id))
      itemsMap[c.id] = {
        event_field_component_id: c.id,
        score: Number(it?.score ?? 0),
        notes: it?.notes ?? '',
      }
      // enforce max
      const maxScore = Number(c?.max_score || 0)
      if (maxScore > 0 && itemsMap[c.id].score > maxScore) itemsMap[c.id].score = maxScore
      if (itemsMap[c.id].score < 0) itemsMap[c.id].score = 0
    }

    map[j.id] = {
      judge_id: j.id,
      status: existing?.status || 'draft',
      itemsMap,
    }
  }

  rowsMap.value = map
}

// payload rows untuk saveDraft
const buildPayloadRows = () => {
  const out = []
  for (const j of (judges.value || [])) {
    const row = rowsMap.value?.[j.id]
    if (!row) continue

    const items = (components.value || []).map(c => ({
      event_field_component_id: c.id,
      score: Number(row.itemsMap?.[c.id]?.score ?? 0),
      notes: row.itemsMap?.[c.id]?.notes ?? null,
    }))

    out.push({
      judge_id: j.id,
      items,
    })
  }
  return out
}

const saveDraft = async () => {
  if (!selectedCompetitionId.value || !selectedEventParticipantId.value) return

  const res = await Swal.fire({
    title: 'Simpan draft?',
    text: 'Nilai akan disimpan sebagai draft untuk semua hakim.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Simpan',
    cancelButtonText: 'Batal',
  })
  if (!res.isConfirmed) return

  isSaving.value = true
  try {
    await axios.post(`/api/v1/event-competitions/${selectedCompetitionId.value}/scoring/draft`, {
      event_participant_id: selectedEventParticipantId.value,
      rows: buildPayloadRows(),
    })
    Swal.fire('Berhasil', 'Draft tersimpan.', 'success')
    await loadForm()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan draft.', 'error')
  } finally {
    isSaving.value = false
  }
}

const submitScores = async () => {
  if (!selectedCompetitionId.value || !selectedEventParticipantId.value) return

  const res = await Swal.fire({
    title: 'Submit nilai?',
    text: 'Status nilai akan menjadi submitted (selama belum locked).',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Submit',
    cancelButtonText: 'Batal',
  })
  if (!res.isConfirmed) return

  isSaving.value = true
  try {
    await axios.post(`/api/v1/event-competitions/${selectedCompetitionId.value}/scoring/submit`, {
      event_participant_id: selectedEventParticipantId.value,
    })
    Swal.fire('Berhasil', 'Nilai submitted.', 'success')
    await loadForm()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal submit nilai.', 'error')
  } finally {
    isSaving.value = false
  }
}

const lockScores = async () => {
  if (!selectedCompetitionId.value || !selectedEventParticipantId.value) return

  const res = await Swal.fire({
    title: 'Lock nilai?',
    text: 'Nilai akan dikunci dan tidak bisa diubah lagi.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Lock',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
  })
  if (!res.isConfirmed) return

  isSaving.value = true
  try {
    await axios.post(`/api/v1/event-competitions/${selectedCompetitionId.value}/scoring/lock`, {
      event_participant_id: selectedEventParticipantId.value,
    })
    Swal.fire('Berhasil', 'Nilai locked.', 'success')
    await loadForm()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal lock nilai.', 'error')
  } finally {
    isSaving.value = false
  }
}

const reloadAll = async () => {
  await fetchMeta()

  // kalau sudah ada kompetisi terpilih, baru ambil info group + participants
  if (selectedCompetitionId.value) {
    await fetchCompetitionInfo()
    await fetchParticipants()
  } else {
    participants.value = []
    competitionInfo.value = null
  }

  if (selectedCompetitionId.value && selectedEventParticipantId.value) {
    await loadForm()
  }
}


// =========================
// BUTTON ENABLE/DISABLE
// =========================
const canSave = computed(() => {
  if (!judges.value.length || !components.value.length) return false
  // jika semua locked, disable
  return judges.value.some(j => !isLocked(j.id))
})

const canSubmit = computed(() => {
  if (!judges.value.length) return false
  // submit masih boleh walau draft/submitted, tapi kalau semua locked ya percuma
  return judges.value.some(j => !isLocked(j.id))
})

const canLock = computed(() => {
  if (!judges.value.length) return false
  // lock selalu boleh selama ada sheet (atau akan update)
  return true
})

// =========================
// WATCHERS (auto load form)
// =========================

watch(() => route.params.id, (val) => {
  selectedCompetitionId.value = String(val || '')
})

watch(() => route.query.event_participant_id, (val) => {
  selectedEventParticipantId.value = String(val || '')
})

watch(() => eventId.value, async (val) => {
  if (!val) return
  await reloadAll()
})

watch(() => selectedCompetitionId.value, async (val) => {
  selectedEventParticipantId.value = ''
  formData.value = null
  rowsMap.value = {}
  participants.value = []
  competitionInfo.value = null

  if (!val) return

  await fetchCompetitionInfo()
  await fetchParticipants()
})


watch(() => selectedEventParticipantId.value, async (val) => {
  formData.value = null
  rowsMap.value = {}
  if (!val) return
  await loadForm()
})

// init (tanpa onMounted: biar simple — eventId biasanya sudah ada dari store)
if (eventId.value) {
  reloadAll()
} else {
  Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
}
</script>

<style scoped>
.text-xs { font-size: .75rem; }
</style>
