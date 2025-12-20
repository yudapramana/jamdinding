<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Input Nilai Kompetisi</h1>
          <p class="mb-0 text-muted text-sm">
            Pilih kompetisi & {{ isTeam ? 'tim' : 'peserta' }}, lalu input nilai per hakim untuk semua komponen penilaian.
          </p>

          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
          </p>

          <div v-if="competitionInfo" class="text-muted text-sm mt-1">
            Group:
            <strong>{{ competitionInfo?.event_group?.full_name || ('#' + (competitionInfo?.event_group_id || '-')) }}</strong>
            • Mode:
            <span class="badge" :class="isTeam ? 'badge-info' : 'badge-light border'">
              {{ isTeam ? 'TEAM' : 'INDIVIDUAL' }}
            </span>
          </div>
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

            <div class="form-group col-md-6 mb-2">
              <label class="mb-1 text-sm">
                {{ isTeam ? 'Tim' : 'Peserta' }} (Event Participant)
                <span class="text-danger">*</span>
              </label>

              <!-- ✅ DROPDOWN: grouped by category -> contingent -->
              <select
                v-model="selectedEventParticipantId"
                class="form-control form-control-sm"
                :disabled="!eventId || !selectedCompetitionId || !competitionInfo?.event_group_id || isLoadingParticipants"
              >
                <option value="" disabled>
                  {{
                    isLoadingParticipants
                      ? `Memuat ${isTeam ? 'tim' : 'peserta'}...`
                      : `-- Pilih ${isTeam ? 'Tim' : 'Peserta'} --`
                  }}
                </option>

                <option v-if="!isLoadingParticipants && participantOptions.length === 0" value="" disabled>
                  Tidak ada data {{ isTeam ? 'tim' : 'peserta' }}.
                </option>

                <!-- ✅ key di template (bukan di optgroup) -->
                <template v-for="cat in participantOptions" :key="'cat-' + cat.id">
                  <optgroup :label="`${cat.name} (${cat.count})`">
                    <!-- ✅ key di template (bukan di option header) -->
                    <template v-for="cont in cat.contingents" :key="'cont-' + cat.id + '-' + cont.name">
                      <!-- subheader contingent (fake group) -->
                      <option disabled class="opt-contingent-header">
                        — {{ cont.name }} ({{ cont.count }})
                      </option>

                      <!-- actual options -->
                      <option
                        v-for="p in cont.items"
                        :key="'p-' + p.id"
                        :value="String(p.id)"
                      >
                        {{ optionLabel(p, cat.name, cont.name) }}
                      </option>
                    </template>
                  </optgroup>
                </template>
              </select>


              <small v-if="!selectedCompetitionId" class="text-muted">Pilih kompetisi dulu.</small>
              <small v-else-if="competitionInfo && isTeam" class="text-muted">
                Mode team aktif: daftar yang tampil adalah tim pada group ini.
              </small>
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
              <template v-if="isTeam">
                Tim:
                <strong>{{ formData.participant.team_name || formData.participant.contingent || formData.participant.full_name }}</strong>
                <span class="ml-2">•</span>
                Kategori: <strong>{{ formData.participant.category_name || '-' }}</strong>
              </template>
              <template v-else>
                Peserta: <strong>{{ formData.participant.full_name }}</strong>
                <span class="ml-2">•</span>
                NIK: <strong>{{ formData.participant.nik || '-' }}</strong>
                <span class="ml-2">•</span>
                Kontingen: <strong>{{ formData.participant.contingent || '-' }}</strong>
              </template>
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
                      Total:
                      <span class="badge badge-light border">{{ format2(totalByJudge(j.id)) }}</span>
                      <span
                        v-if="sheetStatusByJudge(j.id)"
                        class="ml-1 badge"
                        :class="statusBadge(sheetStatusByJudge(j.id))"
                      >
                        {{ sheetStatusByJudge(j.id) }}
                      </span>
                    </div>
                  </div>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="c in components"
                :key="'c-' + c.id"
                :class="{ 'table-warning': !rangeInfoByComponent(c.id).ok }"
              >
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
                      Max Weighted: {{ format2(maxWeightedOfComponent(c)) }}
                    </span>
                  </div>
                </td>

                <td v-for="j in judges" :key="'cell-' + c.id + '-' + j.id">
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
                        Range max: {{ RANGE_LIMIT }} poin
                      </small>
                    </div>
                  </div>
                </td>
              </tr>

              <tr class="bg-light">
                <td class="text-right font-weight-bold">TOTAL</td>
                <td v-for="j in judges" :key="'total-' + j.id" class="text-center">
                  <span class="badge badge-primary">{{ format2(totalByJudge(j.id)) }}</span>
                </td>
              </tr>

              <tr class="bg-light">
                <td class="text-right font-weight-bold">FINAL (AVG Hakim)</td>
                <td :colspan="judges.length" class="text-center">
                  <span class="badge badge-warning" style="font-size: 13px;">
                    {{ format2(finalAverage) }}
                  </span>
                  <span class="text-muted text-xs ml-2">
                    (rata-rata total semua hakim)
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
          <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div class="text-muted text-sm">
              <div class="mb-1">
                <span class="badge badge-light border">Auto hitung: score × (weight/100)</span>
                <span class="ml-2 badge badge-light border">Score dipotong jika melebihi max</span>
              </div>
              <div class="text-xs">
                Range antar hakim per komponen maksimal <strong>{{ RANGE_LIMIT }}</strong> poin.
              </div>
            </div>

            <div v-if="!isRangeValid" class="alert alert-warning py-2 mb-2" style="border-radius:8px; max-width: 680px;">
              <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                <div>
                  <div class="font-weight-bold">Validasi gagal</div>
                  <div class="text-sm">
                    Selisih nilai antar hakim pada satu bidang tidak boleh lebih dari <strong>{{ RANGE_LIMIT }}</strong> poin.
                  </div>
                  <ul class="mb-0 mt-1 text-sm">
                    <li v-for="x in invalidComponents" :key="'inv-' + x.component_id">
                      <strong>{{ x.field_name }}</strong> — rentang: {{ format2(x.min) }} s/d {{ format2(x.max) }}
                      (selisih {{ format2(x.diff) }})
                    </li>
                  </ul>
                </div>
              </div>
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

      <div class="alert alert-info" v-else-if="eventId" style="border-radius: 8px;">
        Pilih <strong>kompetisi</strong> dan <strong>{{ isTeam ? 'tim' : 'peserta' }}</strong> untuk mulai input nilai.
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
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

const competitionInfo = ref(null) // harus include event_group {is_team,...}

const isTeam = computed(() => {
  return Boolean(competitionInfo.value?.event_group?.is_team)
})

// =========================
// SELECTOR DATA
// =========================
const isLoadingMeta = ref(false)
const isLoadingParticipants = ref(false)

const competitionsByRound = ref([])
const participants = ref([])

// =========================
// DROPDOWN GROUPING (category -> contingent)
// =========================
const getCategoryName = (p) =>
  p?.event_category?.full_name || p?.category_name || p?.eventCategory?.full_name || '-'

const getContingentName = (p) => {
  const c = (p?.contingent ?? '').toString().trim()
  return c || '-'
}

const optionLabel = (p, catName, contName) => {
  const category = (catName || getCategoryName(p) || '-').toString().trim()
  const contingent = (contName || getContingentName(p) || '-').toString().trim()

  if (isTeam.value) {
    const teamName = (p?.team_name || p?.contingent || 'Tim').toString().trim()
    const memberCount = p?.member_count != null ? ` (${p.member_count} anggota)` : ''
    const memberNames =
      p?.member_names && p.member_names.length
        ? ` — ${p.member_names.join(', ')}${p?.member_count && p.member_names.length < p.member_count ? ', ...' : ''}`
        : ''
    // Putra - Kontingen - Tim/TeamName ...
    return `${category} - ${contingent} - ${teamName}${memberCount}${memberNames}`
  }

  const name = (p?.participant?.full_name || p?.full_name || 'Peserta').toString().trim()
  const nik = (p?.participant?.nik || p?.nik || '').toString().trim()
  // Putra - Kontingen - Nama (NIK optional)
  return nik ? `${category} - ${contingent} - ${name} (${nik})` : `${category} - ${contingent} - ${name}`
}

const participantOptions = computed(() => {
  const list = participants.value || []
  const catMap = new Map()

  for (const p of list) {
    const catName = getCategoryName(p) || '-'
    const catId = String(p?.event_category_id ?? p?.event_category?.id ?? catName)

    if (!catMap.has(catId)) {
      catMap.set(catId, { id: catId, name: catName, count: 0, contMap: new Map() })
    }
    const cat = catMap.get(catId)
    cat.count++

    const contName = getContingentName(p) || '-'
    if (!cat.contMap.has(contName)) cat.contMap.set(contName, { name: contName, count: 0, items: [] })
    const cont = cat.contMap.get(contName)
    cont.count++
    cont.items.push(p)
  }

  // sort helper
  const sortText = (a, b) => a.localeCompare(b, 'id', { sensitivity: 'base' })

  const cats = Array.from(catMap.values())
    .map((c) => {
      const contingents = Array.from(c.contMap.values())
        .map((x) => {
          // sort items by name
          x.items.sort((pa, pb) => {
            const an = (pa?.participant?.full_name || pa?.full_name || pa?.team_name || pa?.contingent || '').toString()
            const bn = (pb?.participant?.full_name || pb?.full_name || pb?.team_name || pb?.contingent || '').toString()
            return sortText(an, bn)
          })
          return x
        })
        // sort contingent name
        .sort((a, b) => sortText(a.name, b.name))

      return { id: c.id, name: c.name, count: c.count, contingents }
    })
    // sort categories by name
    .sort((a, b) => sortText(a.name, b.name))

  return cats
})

// =========================
// FORM DATA
// =========================
const isLoadingForm = ref(false)
const formData = ref(null)

const judges = computed(() => formData.value?.judges || [])
const components = computed(() => formData.value?.components || [])
const scoresheets = computed(() => formData.value?.scoresheets || [])

const rowsMap = ref({})
const isSaving = ref(false)

// =========================
// HELPERS / VALIDATION
// =========================
const RANGE_LIMIT = 3

const getScoresOfComponent = (componentId) => {
  const vals = []
  for (const j of judges.value || []) {
    const v = rowsMap.value?.[j.id]?.itemsMap?.[componentId]?.score
    if (v === null || v === undefined || v === '') continue
    vals.push(Number(v))
  }
  return vals
}

const rangeInfoByComponent = (componentId) => {
  const vals = getScoresOfComponent(componentId)
  if (!vals.length) return { ok: true, diff: 0, min: null, max: null }
  const min = Math.min(...vals)
  const max = Math.max(...vals)
  const diff = Math.round((max - min) * 100) / 100
  return { ok: diff <= RANGE_LIMIT, diff, min, max }
}

const invalidComponents = computed(() => {
  const bad = []
  for (const c of components.value || []) {
    const info = rangeInfoByComponent(c.id)
    if (!info.ok) bad.push({ component_id: c.id, field_name: c.field_name, ...info })
  }
  return bad
})

const isRangeValid = computed(() => invalidComponents.value.length === 0)

const statusBadge = (s) => {
  if (s === 'draft') return 'badge-secondary'
  if (s === 'submitted') return 'badge-success'
  if (s === 'locked') return 'badge-danger'
  return 'badge-light'
}

const format2 = (n) => Number(n || 0).toFixed(2)

const sheetStatusByJudge = (judgeId) => {
  const s = (scoresheets.value || []).find((x) => String(x.judge_id) === String(judgeId))
  return s?.status || null
}

const isLocked = (judgeId) => sheetStatusByJudge(judgeId) === 'locked'

const weightedScore = (judgeId, componentId) => {
  const c = components.value.find((x) => String(x.id) === String(componentId))
  const score = Number(rowsMap.value?.[judgeId]?.itemsMap?.[componentId]?.score || 0)
  const weight = Number(c?.weight || 0)
  return weight ? score * (weight / 100) : score
}

const totalByJudge = (judgeId) => {
  let total = 0
  for (const c of components.value || []) total += Number(weightedScore(judgeId, c.id) || 0)
  return Math.round(total * 100) / 100
}

const finalAverage = computed(() => {
  const jList = judges.value || []
  if (!jList.length) return 0
  const sum = jList.reduce((acc, j) => acc + Number(totalByJudge(j.id) || 0), 0)
  return Math.round((sum / jList.length) * 100) / 100
})

const maxWeightedOfComponent = (c) => {
  const max = Number(c?.max_score || 0)
  const w = Number(c?.weight || 0)
  return w ? max * (w / 100) : max
}

const onScoreChange = (judgeId, componentId) => {
  const c = components.value.find((x) => String(x.id) === String(componentId))
  const maxScore = Number(c?.max_score || 0)
  let v = Number(rowsMap.value[judgeId].itemsMap[componentId].score || 0)
  if (v < 0) v = 0
  if (maxScore > 0 && v > maxScore) v = maxScore
  rowsMap.value[judgeId].itemsMap[componentId].score = v
}

const overallStatus = computed(() => {
  const sheets = scoresheets.value || []
  if (!sheets.length) return 'new'
  if (sheets.some((s) => s.status === 'locked')) return 'locked'
  if (sheets.some((s) => s.status === 'submitted')) return 'submitted'
  if (sheets.some((s) => s.status === 'draft')) return 'draft'
  return 'new'
})

const showSaveDraft = computed(() => overallStatus.value === 'new' || overallStatus.value === 'draft')
const showSubmit = computed(() => overallStatus.value === 'draft')
const showLock = computed(() => overallStatus.value === 'submitted')

const canSave = computed(() => judges.value.some((j) => !isLocked(j.id)))
const canSubmit = computed(() => judges.value.some((j) => !isLocked(j.id)))
const canLock = computed(() => judges.value.length > 0)

const disableSaveDraft = computed(() => isSaving.value || !canSave.value || overallStatus.value === 'locked' || !isRangeValid.value)
const disableSubmit = computed(() => isSaving.value || !canSubmit.value || overallStatus.value !== 'draft' || !isRangeValid.value)
const disableLock = computed(() => isSaving.value || !canLock.value || overallStatus.value !== 'submitted' || !isRangeValid.value)

// =========================
// API CALLS
// =========================
const fetchMeta = async () => {
  if (!eventId.value) return
  isLoadingMeta.value = true
  try {
    const { data } = await axios.get(`/api/v1/events/${eventId.value}/competitions/tree`, { params: { search: '' } })
    competitionsByRound.value = data.rounds || []
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat daftar kompetisi.', 'error')
  } finally {
    isLoadingMeta.value = false
  }
}

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
        per_page: 2000,
        event_group_id: groupId,
        is_team: isTeam.value ? 1 : 0,
      },
    })
    participants.value = data.data || data || []
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', `Gagal memuat daftar ${isTeam.value ? 'tim' : 'peserta'}.`, 'error')
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
    const existing = sheets.find((s) => String(s.judge_id) === String(j.id))
    const items = existing?.items || []

    const itemsMap = {}
    for (const c of cList) {
      const it = items.find((x) => String(x.event_field_component_id) === String(c.id))
      const maxScore = Number(c?.max_score || 0)

      let score = Number(it?.score ?? 0)
      if (score < 0) score = 0
      if (maxScore > 0 && score > maxScore) score = maxScore

      itemsMap[c.id] = {
        event_field_component_id: c.id,
        score,
        notes: it?.notes ?? '',
      }
    }

    map[j.id] = {
      judge_id: j.id,
      status: existing?.status || 'draft',
      itemsMap,
    }
  }

  rowsMap.value = map
}

const buildPayloadRows = () => {
  const out = []
  for (const j of judges.value || []) {
    const row = rowsMap.value?.[j.id]
    if (!row) continue

    const items = (components.value || []).map((c) => ({
      event_field_component_id: c.id,
      score: Number(row.itemsMap?.[c.id]?.score ?? 0),
      notes: row.itemsMap?.[c.id]?.notes ?? null,
    }))

    out.push({ judge_id: j.id, items })
  }
  return out
}

const saveDraft = async () => {
  if (!selectedCompetitionId.value || !selectedEventParticipantId.value) return
  if (!isRangeValid.value) {
    Swal.fire('Tidak bisa menyimpan', `Selisih nilai antar hakim pada satu bidang tidak boleh > ${RANGE_LIMIT} poin.`, 'warning')
    return
  }

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
  if (!isRangeValid.value) {
    Swal.fire('Tidak bisa menyimpan', `Selisih nilai antar hakim pada satu bidang tidak boleh > ${RANGE_LIMIT} poin.`, 'warning')
    return
  }

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
  if (!isRangeValid.value) {
    Swal.fire('Tidak bisa menyimpan', `Selisih nilai antar hakim pada satu bidang tidak boleh > ${RANGE_LIMIT} poin.`, 'warning')
    return
  }

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
// WATCHERS
// =========================
watch(
  () => route.params.id,
  (val) => {
    selectedCompetitionId.value = String(val || '')
  }
)

watch(
  () => route.query.event_participant_id,
  (val) => {
    selectedEventParticipantId.value = String(val || '')
  }
)

watch(
  () => eventId.value,
  async (val) => {
    if (!val) return
    await reloadAll()
  }
)

watch(
  () => selectedCompetitionId.value,
  async (val) => {
    selectedEventParticipantId.value = ''
    formData.value = null
    rowsMap.value = {}
    participants.value = []
    competitionInfo.value = null

    if (!val) return
    await fetchCompetitionInfo()
    await fetchParticipants()
  }
)

watch(
  () => selectedEventParticipantId.value,
  async (val) => {
    formData.value = null
    rowsMap.value = {}
    if (!val) return
    await loadForm()
  }
)

onMounted(async () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
    return
  }
  await reloadAll()
})
</script>

<style scoped>
.text-xs {
  font-size: 0.75rem;
}

/* subheader contingent di dalam optgroup */
.opt-contingent-header {
  font-weight: 700;
  color: #495057;
}
</style>
