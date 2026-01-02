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

const activeCategoryId = ref('all')

// =======================
// ROUTE & SELECTOR
// =======================
const selectedCompetitionId = ref(String(route.params.id || ''))
const competitionId = computed(() => selectedCompetitionId.value || null)

// selector meta
const isLoadingMeta = ref(false)
const competitionsByRound = ref([])

// =======================
// DATA
// =======================
const loading = ref(false)
const q = ref('')
const status = ref('')
const competition = ref(null)
const judges = ref([])
const participants = ref([])
const fields = ref([])

// =========================
// DETECT TEAM/GROUP MODE
// =========================
const isTeam = computed(() => {
  const c = competition.value || {}
  if (typeof c.is_team !== 'undefined') return Boolean(c.is_team)
  return Boolean(c?.event_group?.is_team)
})

// =======================
// CATEGORY TABS
// =======================
const categoryTabs = computed(() => {
  const list = participants.value || []

  if (isTeam.value) {
    return [{ id: 'all', name: 'Semua Kontingen', count: list.length }]
  }

  const map = new Map()
  for (const p of list) {
    const cid = String(p?.event_category_id || '')
    if (!cid) continue
    const cname = p?.category_name || p?.category || '-'

    if (!map.has(cid)) map.set(cid, { id: cid, name: cname, count: 0 })
    map.get(cid).count++
  }

  return [
    { id: 'all', name: 'Semua Peserta', count: list.length },
    ...Array.from(map.values()),
  ]
})

const filteredParticipants = computed(() => {
  if (isTeam.value) return participants.value || []
  if (activeCategoryId.value === 'all') return participants.value || []
  return (participants.value || []).filter(
    (p) => String(p?.event_category_id || '') === String(activeCategoryId.value)
  )
})

watch(() => selectedCompetitionId.value, () => {
  activeCategoryId.value = 'all'
})

// =======================
// FETCH META
// =======================
const fetchMeta = async () => {
  if (!eventId.value) return
  isLoadingMeta.value = true
  try {
    const { data } = await axios.get(`/api/v1/events/${eventId.value}/competitions/tree`)
    competitionsByRound.value = data.rounds || []
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat daftar kompetisi.', 'error')
  } finally {
    isLoadingMeta.value = false
  }
}

// =======================
// FETCH DETAIL DATA
// =======================
const fetchData = async () => {
  if (!competitionId.value) {
    competition.value = null
    judges.value = []
    participants.value = []
    fields.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get(
      `/api/v1/event-competitions/${competitionId.value}/scores-detail`,
      { params: { search: q.value || undefined, status: status.value || undefined } }
    )

    competition.value = data.competition
    judges.value = data.judges || []
    participants.value = data.participants || []
    fields.value = data.fields || []
  } finally {
    loading.value = false
  }
}

// =======================
// URL SYNC
// =======================
const syncUrl = async (id) => {
  if (!id) return
  if (String(route.params.id) !== String(id)) {
    await router.replace({
      name: 'admin.event.scores.detail.index',
      params: { id: String(id) },
      query: { ...route.query },
    })
  }
}

// =======================
// HELPERS
// =======================
const fmt = (n) => (Number(n ?? 0)).toFixed(2)
const judgeLabel = (index) => `H${index + 1}`

const fieldAvgIndividu = (p, fieldComponentId) => {
  const vals = (judges.value || [])
    .map((j) => p?.field_scores?.[j.id]?.[fieldComponentId]?.score)
    .filter((v) => v !== null && v !== undefined && v !== '')

  if (!vals.length) return null
  const sum = vals.reduce((a, b) => a + Number(b), 0)
  return Math.round((sum / vals.length) * 100) / 100
}

const membersPreview = (names = [], max = 14) => {
  const arr = Array.isArray(names) ? names.filter(Boolean) : []
  if (!arr.length) return '-'
  if (arr.length <= max) return arr.join(', ')
  return `${arr.slice(0, max).join(', ')}, ...`
}

const getFieldScore = (p, jId, fieldComponentId) => {
  const v = p?.field_scores?.[jId]?.[fieldComponentId]?.score
  if (v === null || v === undefined || v === '') return null
  return Number(v)
}

// =======================
// TEAM/GROUP: aggregate by contingent
// =======================
const groupedContingents = computed(() => {
  if (!isTeam.value) return []

  const list = filteredParticipants.value || []
  const contMap = new Map()

  for (const p of list) {
    const cont = String(p?.contingent || '-')
    if (!contMap.has(cont)) {
      contMap.set(cont, {
        contingent: cont,
        members: [],
        fieldCollector: {}, // {fcId:{judgeId:[vals]}}
        totalCollector: {}, // {judgeId:[vals]}
      })
    }
    const g = contMap.get(cont)
    g.members.push(p?.full_name || '-')

    // total per judge (dari p.scores)
    for (const j of judges.value || []) {
      const jid = j.id
      const has = p?.scores && typeof p.scores[jid] !== 'undefined'
      if (!has) continue
      const v = Number(p.scores[jid] ?? 0)
      if (!g.totalCollector[jid]) g.totalCollector[jid] = []
      g.totalCollector[jid].push(v)
    }

    // field per judge
    for (const f of fields.value || []) {
      const fcId = f.event_field_component_id
      if (!g.fieldCollector[fcId]) g.fieldCollector[fcId] = {}

      for (const j of judges.value || []) {
        const jid = j.id
        const v = getFieldScore(p, jid, fcId)
        if (v === null) continue
        if (!g.fieldCollector[fcId][jid]) g.fieldCollector[fcId][jid] = []
        g.fieldCollector[fcId][jid].push(v)
      }
    }
  }

  const conts = Array.from(contMap.keys()).sort((a, b) => {
    if (a === '-') return 1
    if (b === '-') return -1
    return a.localeCompare(b)
  })

  const rows = []

  for (const cont of conts) {
    const g = contMap.get(cont)

    const field_scores = {} // fcId -> judgeId -> avg
    const field_avg = {} // fcId -> avg across judges

    for (const f of fields.value || []) {
      const fcId = f.event_field_component_id
      field_scores[fcId] = {}

      const judgeVals = []
      for (const j of judges.value || []) {
        const jid = j.id
        const arr = g.fieldCollector?.[fcId]?.[jid] || []
        const avg = arr.length ? arr.reduce((s, x) => s + x, 0) / arr.length : null
        field_scores[fcId][jid] = avg !== null ? Number(avg.toFixed(2)) : null
        if (avg !== null) judgeVals.push(avg)
      }

      field_avg[fcId] = judgeVals.length
        ? Number((judgeVals.reduce((s, x) => s + x, 0) / judgeVals.length).toFixed(2))
        : null
    }

    const total_by_judge = {}
    let sum = 0
    let judgeCount = 0

    for (const j of judges.value || []) {
      const jid = j.id
      const arr = g.totalCollector[jid] || []
      const avg = arr.length ? arr.reduce((s, x) => s + x, 0) / arr.length : 0
      total_by_judge[jid] = Number(avg.toFixed(2))
      sum += total_by_judge[jid]
      judgeCount++
    }

    const avg_score = judgeCount ? sum / judgeCount : 0

    rows.push({
      contingent: g.contingent,
      members: g.members,
      member_count: g.members.length,
      field_scores,
      field_avg,
      total_by_judge,
      sum_score: Number(sum.toFixed(2)),
      avg_score: Number(avg_score.toFixed(2)),
    })
  }

  return rows
})

// =======================
// COLSPAN HEADER/BODY
// =======================
// kolom awal:
// - individu: (#, Peserta, Kontingen) = 3
// - team: (#, Kontingen, Jumlah Anggota, Anggota) = 4
const baseCols = computed(() => (isTeam.value ? 4 : 3))

const totalColspan = computed(() => {
  // base + fields*(judges+1) + 2 (AVG Total, SUM Total)
  return baseCols.value + (fields.value.length * ((judges.value.length || 0) + 1)) + 2
})

// =======================
// LIFECYCLE
// =======================
onMounted(async () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event terlebih dahulu.', 'info')
    return
  }
  await fetchMeta()
  if (selectedCompetitionId.value) await fetchData()
})

watch(
  () => route.params.id,
  async (val) => {
    selectedCompetitionId.value = String(val || '')
    await fetchData()
  }
)

watch(
  () => selectedCompetitionId.value,
  async (val) => {
    if (!val) return
    await syncUrl(val)
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
            <span v-if="selectedCompetitionId && competition" class="ml-2">
              <span class="badge" :class="isTeam ? 'badge-info' : 'badge-secondary'">
                {{ isTeam ? 'TEAM / GROUP (By Kontingen)' : 'INDIVIDU' }}
              </span>
            </span>
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
          <select v-model="selectedCompetitionId" class="form-control form-control-sm" :disabled="!eventId || isLoadingMeta">
            <option value="" disabled>-- Pilih Kompetisi --</option>

            <optgroup v-for="r in competitionsByRound" :key="'round-' + r.id" :label="r.name">
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
            :placeholder="isTeam ? 'Cari kontingen/anggota...' : 'Cari nama peserta...'"
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

      <!-- CATEGORY TABS -->
      <div class="mb-2" v-if="selectedCompetitionId && categoryTabs.length">
        <ul class="nav nav-pills">
          <li class="nav-item" v-for="t in categoryTabs" :key="'tab-'+t.id">
            <a
              href="#"
              class="nav-link"
              :class="{ active: activeCategoryId === t.id }"
              @click.prevent="activeCategoryId = t.id"
              style="font-weight:600;"
            >
              {{ t.name }}
              <span class="badge badge-light border ml-2">{{ t.count }}</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <!-- HEADER ROW 1 -->
              <tr>
                <th rowspan="2" style="width:50px" class="align-middle text-center">#</th>

                <!-- INDIVIDU -->
                <template v-if="!isTeam">
                  <th rowspan="2" class="align-middle">Peserta</th>
                  <th rowspan="2" class="align-middle">Kontingen</th>
                </template>

                <!-- TEAM/GROUP -->
                <template v-else>
                  <th rowspan="2" class="align-middle">Kontingen</th>
                  <th rowspan="2" class="align-middle text-center" style="width:110px;">Jml Anggota</th>
                  <th rowspan="2" class="align-middle">Anggota</th>
                </template>

                <!-- per bidang -->
                <th
                  v-for="f in fields"
                  :key="'g-'+f.event_field_component_id"
                  class="text-center align-middle"
                  :colspan="(judges?.length || 0) + 1"
                  style="white-space:nowrap;"
                >
                  {{ f.name }}
                </th>

                <th rowspan="2" class="text-center align-middle">AVG Total</th>
                <th rowspan="2" class="text-center align-middle">SUM Total</th>
              </tr>

              <!-- HEADER ROW 2 -->
              <tr>
                <template v-for="f in fields" :key="'h-'+f.event_field_component_id">
                  <th
                    v-for="(j, jIdx) in judges"
                    :key="`h-${f.event_field_component_id}-${j.id}`"
                    class="text-center"
                    style="white-space:nowrap;"
                  >
                    {{ judgeLabel(jIdx) }}
                  </th>
                  <th class="text-center" style="white-space:nowrap;">Rerata</th>
                </template>
              </tr>
            </thead>

            <tbody>
              <tr v-if="!selectedCompetitionId">
                <td :colspan="totalColspan" class="text-center p-3 text-muted">
                  Silakan pilih kompetisi terlebih dahulu.
                </td>
              </tr>

              <tr v-else-if="loading">
                <td :colspan="totalColspan" class="text-center p-3 text-muted">
                  Loading...
                </td>
              </tr>

              <tr v-else-if="isTeam && groupedContingents.length === 0">
                <td :colspan="totalColspan" class="text-center p-3 text-muted">
                  Tidak ada data.
                </td>
              </tr>

              <tr v-else-if="!isTeam && filteredParticipants.length === 0">
                <td :colspan="totalColspan" class="text-center p-3 text-muted">
                  Tidak ada data.
                </td>
              </tr>

              <!-- TEAM/GROUP ROWS -->
              <template v-else-if="isTeam">
                <tr v-for="(g, idx) in groupedContingents" :key="'cont-'+g.contingent">
                  <td class="text-center">{{ idx + 1 }}</td>

                  <td>
                    <div class="font-weight-bold">{{ g.contingent }}</div>
                  </td>

                  <td class="text-center font-weight-bold">
                    {{ g.member_count }}
                  </td>

                  <td>
                    <div>{{ membersPreview(g.members, 16) }}</div>
                  </td>

                  <template v-for="f in fields" :key="'gb-'+f.event_field_component_id">
                    <td v-for="j in judges" :key="`gb-${g.contingent}-${f.event_field_component_id}-${j.id}`" class="text-center">
                      {{
                        g.field_scores?.[f.event_field_component_id]?.[j.id] !== null &&
                        g.field_scores?.[f.event_field_component_id]?.[j.id] !== undefined
                          ? fmt(g.field_scores[f.event_field_component_id][j.id])
                          : '-'
                      }}
                    </td>

                    <td class="text-center font-weight-bold">
                      {{
                        g.field_avg?.[f.event_field_component_id] !== null &&
                        g.field_avg?.[f.event_field_component_id] !== undefined
                          ? fmt(g.field_avg[f.event_field_component_id])
                          : '-'
                      }}
                    </td>
                  </template>

                  <td class="text-center font-weight-bold">{{ fmt(g.avg_score) }}</td>
                  <td class="text-center">{{ fmt(g.sum_score) }}</td>
                </tr>
              </template>

              <!-- INDIVIDU ROWS -->
              <template v-else>
                <tr v-for="(p, idx) in filteredParticipants" :key="p.event_participant_id">
                  <td class="text-center">{{ idx + 1 }}</td>

                  <td>
                    <div class="font-weight-bold">{{ p.full_name }}</div>
                  </td>

                  <td>{{ p.contingent || '-' }}</td>

                  <template v-for="f in fields" :key="'b-'+f.event_field_component_id">
                    <td
                      v-for="j in judges"
                      :key="`b-${p.event_participant_id}-${f.event_field_component_id}-${j.id}`"
                      class="text-center"
                    >
                      {{
                        p.field_scores?.[j.id]?.[f.event_field_component_id]
                          ? fmt(p.field_scores[j.id][f.event_field_component_id].score)
                          : '-'
                      }}
                    </td>

                    <td class="text-center font-weight-bold">
                      {{
                        fieldAvgIndividu(p, f.event_field_component_id) !== null
                          ? fmt(fieldAvgIndividu(p, f.event_field_component_id))
                          : '-'
                      }}
                    </td>
                  </template>

                  <td class="text-center font-weight-bold">{{ fmt(p.avg_score) }}</td>
                  <td class="text-center">{{ fmt(p.sum_score) }}</td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div class="card-footer text-muted text-sm">
          Total:
          <strong>
            {{ isTeam ? groupedContingents.length + ' kontingen' : filteredParticipants.length + ' peserta' }}
          </strong>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.table thead th {
  vertical-align: middle;
}
</style>
