<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Pengisian Nilai</h1>
          <p class="mb-0 text-muted text-sm">
            Input nilai 1 peserta pada 1 kompetisi (matrix komponen × hakim).
          </p>

          <p v-if="competition" class="mb-0 mt-1 text-sm text-muted">
            Kompetisi: <strong>{{ competition.full_name }}</strong>
          </p>

          <p v-if="participant" class="mb-0 mt-1 text-sm text-muted">
            Peserta: <strong>{{ participant.full_name }}</strong> • NIK: <strong>{{ participant.nik }}</strong>
            • Kontingen: <strong>{{ participant.contingent || '-' }}</strong>
          </p>
        </div>

        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="isLoading" @click="loadForm">
            <i class="fas fa-sync mr-1"></i> Refresh
          </button>
          <button class="btn btn-sm btn-primary" :disabled="isSaving || isLoading" @click="saveDraft(true)">
            <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i> Simpan Draft
          </button>
          <button class="btn btn-sm btn-warning" :disabled="isLoading" @click="submitAll">
            <i class="fas fa-paper-plane mr-1"></i> Submit
          </button>
          <button class="btn btn-sm btn-danger" :disabled="isLoading" @click="lockAll">
            <i class="fas fa-lock mr-1"></i> Lock
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body p-0 table-responsive">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:320px">Komponen</th>
                <th v-for="j in judges" :key="j.id" class="text-center" style="min-width:220px">
                  <div><strong>{{ j.name }}</strong></div>
                  <div class="text-xs text-muted">
                    Total: <strong>{{ totalsByJudge[j.id] ?? 0 }}</strong>
                    <span v-if="sheetStatusByJudge[j.id]" class="badge ml-1"
                      :class="statusBadge(sheetStatusByJudge[j.id])">
                      {{ sheetStatusByJudge[j.id] }}
                    </span>
                  </div>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td :colspan="1 + judges.length" class="text-center py-4">Memuat form penilaian...</td>
              </tr>

              <tr v-else-if="components.length === 0">
                <td :colspan="1 + judges.length" class="text-center py-4">
                  Komponen penilaian belum di-set untuk event group ini.
                </td>
              </tr>

              <tr v-for="c in components" :key="c.id">
                <td>
                  <div><strong>{{ c.field_name }}</strong></div>
                  <div class="text-xs text-muted">
                    Max: <strong>{{ num(c.max_score) }}</strong>
                    <span v-if="c.weight !== null && c.weight !== undefined">
                      • Bobot: <strong>{{ c.weight }}%</strong>
                    </span>
                  </div>
                </td>

                <td v-for="j in judges" :key="j.id" class="text-center">
                  <input
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm text-center"
                    :max="c.max_score || null"
                    v-model.number="matrix[j.id][c.id].score"
                    :disabled="sheetStatusByJudge[j.id] === 'locked'"
                    @input="debouncedAutosave()"
                  />
                  <small class="text-xs text-muted d-block mt-1">
                    W: <strong>{{ num(calcWeighted(j.id, c.id)) }}</strong>
                  </small>
                </td>
              </tr>
            </tbody>

            <tfoot v-if="components.length && judges.length">
              <tr class="bg-light">
                <th class="text-right">TOTAL</th>
                <th v-for="j in judges" :key="j.id" class="text-center">
                  <strong>{{ totalsByJudge[j.id] ?? 0 }}</strong>
                </th>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="card-footer text-sm text-muted">
          Autosave: tiap input akan simpan draft (debounce). Gunakan Submit/Lock untuk finalisasi.
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useDebounceFn } from '@vueuse/core'
import { useRoute } from 'vue-router'

const route = useRoute()

// asumsi route params: competitionId, query: event_participant_id
const competitionId = computed(() => route.params.id)
const eventParticipantId = ref(route.query.event_participant_id || '')

const isLoading = ref(false)
const isSaving = ref(false)

const competition = ref(null)
const participant = ref(null)
const judges = ref([])
const components = ref([])
const scoresheets = ref([])

// matrix[judgeId][componentId] = { score, notes }
const matrix = ref({})

// status sheet per judge
const sheetStatusByJudge = computed(() => {
  const map = {}
  for (const s of scoresheets.value) map[s.judge_id] = s.status
  return map
})

// total per judge dari matrix (weighted)
const totalsByJudge = computed(() => {
  const out = {}
  for (const j of judges.value) {
    let t = 0
    for (const c of components.value) {
      t += Number(calcWeighted(j.id, c.id) || 0)
    }
    out[j.id] = round2(t)
  }
  return out
})

const statusBadge = (s) => {
  if (s === 'draft') return 'badge-secondary'
  if (s === 'submitted') return 'badge-warning'
  if (s === 'locked') return 'badge-danger'
  return 'badge-light'
}

const num = (v) => Number(v || 0).toFixed(2)
const round2 = (v) => Math.round((Number(v || 0) + Number.EPSILON) * 100) / 100

const calcWeighted = (judgeId, componentId) => {
  const cell = matrix.value?.[judgeId]?.[componentId]
  if (!cell) return 0
  const c = components.value.find(x => String(x.id) === String(componentId))
  if (!c) return 0
  const score = Math.min(Number(cell.score || 0), Number(c.max_score || 0) || Number(cell.score || 0))
  const weight = Number(c.weight || 0)
  return weight ? round2(score * (weight / 100)) : round2(score)
}

const initMatrix = () => {
  const m = {}
  for (const j of judges.value) {
    m[j.id] = {}
    for (const c of components.value) {
      m[j.id][c.id] = { score: 0, notes: '' }
    }
  }

  // isi dari existing scoresheets/items
  for (const s of scoresheets.value) {
    const jid = s.judge_id
    if (!m[jid]) continue
    for (const it of (s.items || [])) {
      const cid = it.event_field_component_id
      if (!cid || !m[jid][cid]) continue
      m[jid][cid].score = Number(it.score || 0)
      m[jid][cid].notes = it.notes || ''
    }
  }

  matrix.value = m
}

const loadForm = async () => {
  if (!competitionId.value || !eventParticipantId.value) {
    Swal.fire('Info', 'event_participant_id wajib (query param).', 'info')
    return
  }

  isLoading.value = true
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${competitionId.value}/scoring/form`, {
      params: { event_participant_id: eventParticipantId.value },
    })

    competition.value = data.competition
    participant.value = data.participant
    judges.value = data.judges || []
    components.value = data.components || []
    scoresheets.value = data.scoresheets || []

    initMatrix()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal memuat form nilai.', 'error')
  } finally {
    isLoading.value = false
  }
}

const saveDraft = async (showToast = false) => {
  if (!competitionId.value || !eventParticipantId.value) return

  isSaving.value = true
  try {
    const rows = judges.value.map(j => ({
      judge_id: j.id,
      items: components.value.map(c => ({
        event_field_component_id: c.id,
        score: matrix.value?.[j.id]?.[c.id]?.score ?? 0,
        notes: matrix.value?.[j.id]?.[c.id]?.notes ?? null,
      })),
    }))

    await axios.post(`/api/v1/event-competitions/${competitionId.value}/scoring/save`, {
      event_participant_id: eventParticipantId.value,
      rows,
    })

    if (showToast) Swal.fire('OK', 'Draft tersimpan.', 'success')
    await loadForm()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan draft.', 'error')
  } finally {
    isSaving.value = false
  }
}

const debouncedAutosave = useDebounceFn(() => saveDraft(false), 700)

const submitAll = async () => {
  const res = await Swal.fire({
    title: 'Submit nilai?',
    text: 'Status akan menjadi submitted (masih bisa lock).',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, submit',
    cancelButtonText: 'Batal',
  })
  if (!res.isConfirmed) return

  try {
    await axios.post(`/api/v1/event-competitions/${competitionId.value}/scoring/submit`, {
      event_participant_id: eventParticipantId.value,
    })
    Swal.fire('Berhasil', 'Nilai berhasil submitted.', 'success')
    await loadForm()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal submit nilai.', 'error')
  }
}

const lockAll = async () => {
  const res = await Swal.fire({
    title: 'Lock nilai?',
    text: 'Nilai yang sudah lock tidak bisa diubah.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, lock',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
  })
  if (!res.isConfirmed) return

  try {
    await axios.post(`/api/v1/event-competitions/${competitionId.value}/scoring/lock`, {
      event_participant_id: eventParticipantId.value,
    })
    Swal.fire('Berhasil', 'Nilai berhasil locked.', 'success')
    await loadForm()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal lock nilai.', 'error')
  }
}

loadForm()
</script>

<style scoped>
.text-xs { font-size: .75rem; }
</style>
