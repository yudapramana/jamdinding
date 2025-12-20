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
// DETECT TEAM/GROUP MODE
// =========================
// backend ideal mengirim: competition.is_team + competition.event_group.is_team
const isTeam = computed(() => {
  const c = competition.value || {}
  if (typeof c.is_team !== 'undefined') return Boolean(c.is_team)
  return Boolean(c?.event_group?.is_team)
})

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
    // debug
    // console.log('competition payload:', competition.value)
    // console.log('isTeam computed:', isTeam.value)
  }
}

const updateScoresheet = async (scoresheetId, payload) => {
  const id = selectedCompetitionId.value || competitionId.value
  if (!id || !scoresheetId) return
  await axios.patch(`/api/v1/event-competitions/${id}/scoresheets/${scoresheetId}`, payload)
  await fetchData()
}

// =========================
// URL sync
// =========================
const pushCompetitionToUrl = async (id) => {
  if (!id) return
  if (route.name !== 'admin.event.scores.index' || String(route.params.id) !== String(id)) {
    await router.replace({
      name: 'admin.event.scores.index',
      params: { id: String(id) },
      query: { ...route.query },
    })
  }
}

// =========================
// INDIVIDU FILTER (tabs kategori hanya untuk non-team)
// =========================
const activeCategoryId = ref('all')

const categoryTabs = computed(() => {
  if (isTeam.value) {
    return [{ id: 'all', name: 'SEMUA KONTINGEN', count: (participants.value || []).length }]
  }

  const map = new Map()
  for (const p of participants.value || []) {
    const id = p.event_category_id ?? p.event_category?.id ?? null
    const name =
      p.category_name ??
      p.event_category?.category_name ??
      p.event_category?.full_name ??
      p.category ??
      null

    if (!id) continue
    if (!map.has(String(id))) {
      map.set(String(id), { id: String(id), name: String(name || 'Kategori'), count: 0 })
    }
    map.get(String(id)).count++
  }

  const arr = Array.from(map.values())
  const scoreOrder = (n) => {
    const s = (n || '').toLowerCase()
    if (s.includes('putera') || s.includes('putra')) return 1
    if (s.includes('puteri') || s.includes('putri')) return 2
    return 99
  }
  arr.sort((a, b) => {
    const ao = scoreOrder(a.name)
    const bo = scoreOrder(b.name)
    if (ao !== bo) return ao - bo
    return a.name.localeCompare(b.name)
  })

  return [
    { id: 'all', name: 'SEMUA PESERTA', count: (participants.value || []).length },
    ...arr.map((x) => ({
      id: x.id,
      name: `PESERTA ${x.name.toUpperCase()}`,
      count: x.count,
    })),
  ]
})

const filteredParticipants = computed(() => {
  const list = participants.value || []
  if (activeCategoryId.value === 'all') return list
  if (isTeam.value) return list // team mode tidak pakai tab kategori
  return list.filter((p) => String(p.event_category_id || '') === String(activeCategoryId.value))
})

watch(
  () => participants.value,
  () => {
    if (activeCategoryId.value === 'all') return
    const exists = (categoryTabs.value || []).some((t) => t.id === activeCategoryId.value)
    if (!exists) activeCategoryId.value = 'all'
  }
)

watch(
  () => isTeam.value,
  () => {
    activeCategoryId.value = 'all'
  }
)

// =========================
// TEAM MODE: GROUP BY CONTINGENT
// - 1 row = 1 contingent
// - Anggota = daftar nama peserta
// - Nilai per hakim = rata-rata skor peserta pada kontingen tsb
// =========================
const groupedByContingent = computed(() => {
  if (!isTeam.value) return []

  const list = filteredParticipants.value || []
  const map = new Map()

  for (const p of list) {
    const cont = String(p.contingent || '-')
    if (!map.has(cont)) {
      map.set(cont, {
        contingent: cont,
        members: [],
        // judgeScores: { [judgeId]: number[] } -> kumpulkan dulu agar bisa rata-rata
        judgeScores: {},
      })
    }
    const g = map.get(cont)

    g.members.push(p.full_name || '-')

    // kumpulkan nilai per hakim
    for (const j of judges.value || []) {
      const jid = j.id
      const val = Number(p.scores?.[jid] ?? 0)
      if (!g.judgeScores[jid]) g.judgeScores[jid] = []
      // kalau tidak ada scoresheet, jangan masukkan (biar rata-rata tidak bias 0)
      // tapi kalau kamu ingin dianggap 0, ganti kondisi ini.
      const hasScore = p.scores && typeof p.scores[jid] !== 'undefined'
      if (hasScore) g.judgeScores[jid].push(val)
    }
  }

  // build output rows
  const rows = []
  const conts = Array.from(map.keys()).sort((a, b) => {
    if (a === '-') return 1
    if (b === '-') return -1
    return a.localeCompare(b)
  })

  for (const cont of conts) {
    const g = map.get(cont)

    const scores = {} // avg per judge
    let sum = 0
    let judgeCount = 0

    for (const j of judges.value || []) {
      const jid = j.id
      const arr = g.judgeScores[jid] || []
      const avg = arr.length ? arr.reduce((s, x) => s + x, 0) / arr.length : 0
      scores[jid] = Number(avg.toFixed(2))
      sum += scores[jid]
      judgeCount++
    }

    const avgAll = judgeCount ? sum / judgeCount : 0

    rows.push({
      contingent: g.contingent,
      members: g.members, // list nama
      member_count: g.members.length,
      scores, // avg per judge
      sum_score: Number(sum.toFixed(2)),
      avg_score: Number(avgAll.toFixed(2)),
    })
  }

  return rows
})

const membersPreview = (names = [], max = 6) => {
  const arr = Array.isArray(names) ? names.filter(Boolean) : []
  if (!arr.length) return '-'
  if (arr.length <= max) return arr.join(', ')
  return `${arr.slice(0, max).join(', ')}, ...`
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
  if (selectedCompetitionId.value) {
    await fetchData()
  }
})

watch(
  () => route.params.id,
  async (val) => {
    const v = String(val || '')
    selectedCompetitionId.value = v
    await fetchData()
  }
)

watch(
  () => selectedCompetitionId.value,
  async (val) => {
    if (!val) {
      competition.value = null
      judges.value = []
      participants.value = []
      return
    }
    await pushCompetitionToUrl(val)
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
            :placeholder="isTeam ? 'Cari nama kontingen/anggota...' : 'Cari nama peserta...'"
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

      <!-- CATEGORY TABS (ONLY INDIVIDU) -->
      <div class="mb-2" v-if="selectedCompetitionId && !isTeam && categoryTabs.length">
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

          <!-- =========================
               TABLE: TEAM/GROUP BY CONTINGENT
               ========================= -->
          <table v-if="isTeam" class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:50px">#</th>
                <th style="min-width:220px">Kontingen</th>
                <th style="min-width:420px">Anggota</th>
                <th v-for="j in judges" :key="j.id" class="text-center">
                  {{ j.name }}
                </th>
                <th class="text-center">AVG</th>
                <th class="text-center">SUM</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="!selectedCompetitionId">
                <td :colspan="5 + judges.length" class="text-center p-3 text-muted">
                  Silakan pilih kompetisi terlebih dahulu.
                </td>
              </tr>

              <tr v-else-if="loading">
                <td :colspan="5 + judges.length" class="text-center p-3 text-muted">Loading...</td>
              </tr>

              <tr v-else-if="groupedByContingent.length === 0">
                <td :colspan="5 + judges.length" class="text-center p-3 text-muted">Tidak ada data.</td>
              </tr>

              <tr v-else v-for="(g, idx) in groupedByContingent" :key="'cont-'+g.contingent">
                <td class="text-center">{{ idx + 1 }}</td>

                <td>
                  <div class="font-weight-bold">{{ g.contingent }}</div>
                  <small class="text-muted">{{ g.member_count }} anggota</small>
                </td>

                <td>
                  <div class="text-sm">
                    {{ membersPreview(g.members, 10) }}
                  </div>
                </td>

                <td v-for="j in judges" :key="j.id" class="text-center font-weight-bold">
                  {{ fmt(g.scores?.[j.id]) }}
                </td>

                <td class="text-center font-weight-bold">{{ fmt(g.avg_score) }}</td>
                <td class="text-center">{{ fmt(g.sum_score) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- =========================
               TABLE: INDIVIDU
               ========================= -->
          <table v-else class="table table-bordered table-hover text-sm mb-0">
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

              <tr v-else-if="filteredParticipants.length === 0">
                <td :colspan="6 + judges.length" class="text-center p-3 text-muted">Tidak ada data.</td>
              </tr>

              <tr v-else v-for="(p, idx) in filteredParticipants" :key="p.event_participant_id || idx">
                <td>{{ idx + 1 }}</td>
                <td>
                  <div class="font-weight-bold">{{ p.full_name }}</div>
                  <small class="text-muted">{{ p.category_name || '-' }}</small>
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
          Total:
          <strong>
            {{ isTeam ? groupedByContingent.length + ' kontingen' : filteredParticipants.length + ' peserta' }}
          </strong>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.text-xs { font-size: .75rem; }
</style>
