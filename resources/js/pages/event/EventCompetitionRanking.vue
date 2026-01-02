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

// =========================
// TAB KATEGORI
// =========================
const activeCategoryId = ref('all')

// ==== selector state
const selectedCompetitionId = ref(String(route.params.id || ''))
const competitionsByRound = ref([])
const isLoadingMeta = ref(false)

// ==== ranking state
const loading = ref(false)
const competition = ref(null)
const ranking = ref([])

// ==== detail score matrix state
const isLoadingDetails = ref(false)
const detail = ref({
  judges: [],
  fields: [],
  rows: [],
})

const chiefJudgeId = ref(null)

const competitionId = computed(() => selectedCompetitionId.value || null)

// ✅ mode team dari backend ranking response
const isTeam = computed(() => !!competition.value?.is_team)

const fmt = (n) => {
  const x = Number(n)
  if (!Number.isFinite(x)) return '-'
  return x.toFixed(2)
}

const judgeLabel = (idx) => `H${idx + 1}`

const isChiefJudge = (judgeId) => {
  if (!judgeId) return false
  if (chiefJudgeId.value != null) return String(judgeId) === String(chiefJudgeId.value)
  const j = (detail.value.judges || []).find((x) => String(x.id) === String(judgeId))
  return !!j?.is_chief
}

// =========================
// META
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
// RANKING SUMMARY
// =========================
const fetchRanking = async () => {
  if (!competitionId.value) {
    competition.value = null
    ranking.value = []
    detail.value = { judges: [], fields: [], rows: [] }
    chiefJudgeId.value = null
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${competitionId.value}/ranking`)
    competition.value = data.competition
    ranking.value = data.ranking || []
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat ranking.', 'error')
  } finally {
    loading.value = false
  }
}

const recalc = async () => {
  if (!competitionId.value) return
  loading.value = true
  try {
    const { data } = await axios.post(`/api/v1/event-competitions/${competitionId.value}/ranking/recalculate`)
    ranking.value = data.ranking || []
    Swal.fire('OK', 'Ranking dihitung ulang.', 'success')
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal hitung ulang ranking.', 'error')
  } finally {
    loading.value = false
  }
}

const publishTop6 = async () => {
  if (!competitionId.value) return
  const res = await Swal.fire({
    title: 'Publish Ranking?',
    text: 'Akan membuat Medal Standings & update Contingent.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Publish',
  })
  if (!res.isConfirmed) return

  loading.value = true
  try {
    await axios.post(`/api/v1/event-competitions/${competitionId.value}/ranking/publish`, { top_n: 6 })
    Swal.fire('Published', 'Top 6 berhasil dipublish.', 'success')
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal publish ranking.', 'error')
  } finally {
    loading.value = false
  }
}

const onCompetitionChange = async () => {
  if (!selectedCompetitionId.value) return
  localStorage.setItem('last_scoring_competition_id', String(selectedCompetitionId.value))

  router.replace({
    name: 'admin.event.ranking.index',
    params: { id: selectedCompetitionId.value },
  })
}

// =========================
// FINAL ROUND + PERMISSION
// =========================
const isFinalRound = computed(() => {
  const name = (competition.value?.round?.name || '').toString().trim().toLowerCase()
  return name.includes('final')
})

const canPublish = computed(() => {
  const slug = (authUserStore.user?.role?.slug || '').toString().trim().toLowerCase()
  const name = (authUserStore.user?.role?.name || '').toString().trim().toLowerCase()
  return slug === 'superadmin' || slug === 'super_admin' || name === 'superadmin'
})

// =========================
// TAB LIST: dibuat dari ranking
// =========================
const categoryTabs = computed(() => {
  const map = new Map()
  const list = ranking.value || []

  for (const r of list) {
    const cid = String(r?.event_category_id ?? '')
    if (!cid) continue

    const cname = r?.category_name || r?.category || '-'
    if (!map.has(cid)) map.set(cid, { id: cid, name: cname, count: 0 })
    map.get(cid).count++
  }

  return [{ id: 'all', name: 'Semua Kontingen', count: list.length }, ...Array.from(map.values())]
})

const filteredRanking = computed(() => {
  if (activeCategoryId.value === 'all') return ranking.value || []
  return (ranking.value || []).filter((r) => String(r.event_category_id) === String(activeCategoryId.value))
})

// =========================
// PREVIEW MEDAL
// =========================
const medalPreview = computed(() => {
  const list = filteredRanking.value || []
  return list.slice(0, 4).map((r, idx) => ({
    ...r,
    medal:
      idx === 0
        ? '🥇 Emas'
        : idx === 1
          ? '🥈 Perak'
          : idx === 2
            ? '🥉 Perunggu'
            : idx === 3
              ? '4️⃣ Peringkat 4'
              : null,
  }))
})

// =========================
// EXPORT
// =========================
const exportCategory = async () => {
  if (!competitionId.value || activeCategoryId.value === 'all') return
  try {
    await axios.get(`/api/v1/event-competitions/${competitionId.value}/ranking/export`, {
      params: { event_category_id: activeCategoryId.value },
    })
    Swal.fire('OK', 'Data siap diexport (lihat console / lanjutkan Excel).', 'success')
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal export data.', 'error')
  }
}

// =========================
// DETAIL normalize
// =========================
const normalizeRankingDetail = (data) => {
  const judges = data?.judges || []
  const fieldsRaw = data?.fields || []
  const rowsRaw = data?.participants || data?.rows || []

  const fields = (fieldsRaw || [])
    .map((f) => ({
      ...f,
      id: f.id ?? f.event_field_component_id ?? null,
      name: f.name ?? f.field_name ?? '-',
      order_number: f.order_number ?? 999999,
    }))
    .filter((f) => f.id != null)
    .sort((a, b) => (a.order_number ?? 999999) - (b.order_number ?? 999999))

  const rows = (rowsRaw || []).map((r) => ({
    ...r,
    // TEAM payload pakai "scores"
    scores: r.scores || {},
    // INDIVIDU payload pakai field_scores
    field_scores: r.field_scores || {},
    final_score: r.final_score ?? r.avg_score ?? 0,
  }))

  return { judges, fields, rows }
}

const fetchRankingDetails = async () => {
  // detail hanya ketika pilih kategori (bukan all)
  if (!competitionId.value || activeCategoryId.value === 'all') {
    detail.value = { judges: [], fields: [], rows: [] }
    chiefJudgeId.value = null
    return
  }

  isLoadingDetails.value = true
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${competitionId.value}/ranking/details`, {
      params: { event_category_id: activeCategoryId.value, debug: 1 },
    })

    detail.value = normalizeRankingDetail(data)
    chiefJudgeId.value = data?.debug?.chief_judge_id ?? null
  } catch (e) {
    console.error(e)
    detail.value = { judges: [], fields: [], rows: [] }
    chiefJudgeId.value = null
    Swal.fire('Gagal', 'Gagal memuat detail nilai.', 'error')
  } finally {
    isLoadingDetails.value = false
  }
}

// =========================
// HELPERS: TEAM (field × hakim)
// =========================
const getTeamScore = (row, judgeId, fieldId) => {
  const jKey = String(judgeId)
  const fKey = String(fieldId)

  // 1) Format paling ideal: scores_by_field[judgeId][fieldId] = number
  const byJudge1 = row?.scores_by_field?.[jKey] ?? row?.scores_by_field?.[judgeId]
  if (byJudge1) {
    const cell = byJudge1?.[fKey] ?? byJudge1?.[fieldId]
    if (cell != null && cell !== '') {
      const num = Number(typeof cell === 'object' ? (cell.weighted_score ?? cell.score) : cell)
      return Number.isFinite(num) ? num : null
    }
  }

  // 2) Alternatif: field_scores (samakan dengan individu) jika backend mengirim ini juga untuk TEAM
  const byJudge2 = row?.field_scores?.[jKey] ?? row?.field_scores?.[judgeId]
  if (byJudge2) {
    const cell = byJudge2?.[fKey] ?? byJudge2?.[fieldId]
    if (cell != null && cell !== '') {
      const num = Number(typeof cell === 'object' ? (cell.weighted_score ?? cell.score) : cell)
      return Number.isFinite(num) ? num : null
    }
  }

  // 3) Fallback terakhir: kalau backend cuma kirim total per hakim (bukan per bidang)
  // tidak bisa dipecah per field -> return null
  return null
}


// =========================
// HELPERS: TEAM
// =========================
const memberText = (row) => {
  const cnt = row?.member_count ?? null
  const names = Array.isArray(row?.member_names) ? row.member_names : []
  if (!cnt && !names.length) return '-'
  const left = cnt != null ? `${cnt} anggota` : `${names.length} anggota`
  if (!names.length) return left
  return `${left} — ${names.join(', ')}`
}

const judgeCount = (row) => Object.keys(row?.scores || {}).length

// total_score per judge (TEAM: dari scores, fallback ke totals/count)
const getJudgeTotal = (row, judgeId) => {
  const jKey = String(judgeId)

  // ✅ TEAM avg per judge
  const v1 = row?.scores?.[jKey] ?? row?.scores?.[judgeId]
  if (v1 != null && v1 !== '') {
    const num = Number(v1)
    return Number.isFinite(num) ? num : null
  }

  // ✅ fallback jika backend hanya kirim totals/counts
  const tot = row?.judge_totals?.[jKey] ?? row?.judge_totals?.[judgeId]
  const cnt = row?.judge_counts?.[jKey] ?? row?.judge_counts?.[judgeId]
  if (tot == null || cnt == null || Number(cnt) <= 0) return null

  const num = Number(tot) / Number(cnt)
  return Number.isFinite(num) ? num : null
}

// =========================
// HELPERS: INDIVIDU (field matrix)
// =========================
const getScore = (row, judgeId, fieldId) => {
  const jKey = String(judgeId)
  const fKey = String(fieldId)

  const byJudge = row?.field_scores?.[jKey] ?? row?.field_scores?.[judgeId]
  if (!byJudge) return null

  const cell = byJudge?.[fKey] ?? byJudge?.[fieldId]
  if (cell == null) return null

  if (typeof cell === 'object') {
    const v = cell.weighted_score ?? cell.score
    if (v == null) return null
    const num = Number(v)
    return Number.isFinite(num) ? num : null
  }

  const num = Number(cell)
  return Number.isFinite(num) ? num : null
}

const getFieldAvg = (row, fieldId) => {
  const vals = (detail.value.judges || [])
    .map((j) => getScore(row, j.id, fieldId))
    .filter((v) => Number.isFinite(Number(v)))

  if (!vals.length) return null
  const s = vals.reduce((a, b) => a + Number(b), 0)
  return s / vals.length
}

// =========================
// Tie-break map (INDIVIDU: gunakan fields, TEAM: tidak perlu)
// =========================
const tieBreakMap = computed(() => {
  if (isTeam.value) return new Map()

  const rows = detail.value.rows || []
  const fields = detail.value.fields || []

  const byScore = new Map()
  for (const r of rows) {
    const key = String(Number(r.final_score ?? 0).toFixed(2))
    if (!byScore.has(key)) byScore.set(key, [])
    byScore.get(key).push(r)
  }

  const out = new Map()

  for (const [, group] of byScore.entries()) {
    if (!group || group.length <= 1) continue

    // RULE #2 (field avg)
    let decidingFieldId = null
    let maxVal = null

    for (const f of fields) {
      const avgs = group.map((r) => getFieldAvg(r, f.id)).filter((v) => v != null)
      if (!avgs.length) continue

      const mx = Math.max(...avgs)
      const mn = Math.min(...avgs)

      if (Number(mx.toFixed(4)) !== Number(mn.toFixed(4))) {
        decidingFieldId = f.id
        maxVal = mx
        break
      }
    }

    if (decidingFieldId != null && maxVal != null) {
      const tol = 1e-6
      for (const r of group) {
        const v = getFieldAvg(r, decidingFieldId)
        if (v == null) continue
        if (Math.abs(v - maxVal) <= tol) {
          out.set(`${r.event_participant_id}|${decidingFieldId}`, 'win')
        }
      }
    }
  }

  return out
})

const isTieWin = (row, fieldId) => tieBreakMap.value.get(`${row.event_participant_id}|${fieldId}`) === 'win'

const tieBreakTooltipMap = computed(() => {
  const map = new Map()
  const dbg = detail.value?.debug || null
  if (!dbg?.pairs) return map

  for (const p of dbg.pairs) {
    const rule = p.decided_by?.rule
    const fcId = p.decided_by?.field_component_id
    if (!rule || !fcId) continue

    // row A menang
    if (p.decided_by?.a > p.decided_by?.b) {
      map.set(`${p.a.id}|${fcId}`, {
        rule,
        field_id: fcId,
        value: p.decided_by.a,
      })
    }

    // row B menang
    if (p.decided_by?.b > p.decided_by?.a) {
      map.set(`${p.b.id}|${fcId}`, {
        rule,
        field_id: fcId,
        value: p.decided_by.b,
      })
    }
  }

  return map
})

const tieBreakTooltip = (row, fieldId) => {
  const key = `${row.group_key || row.event_participant_id}|${fieldId}`
  const info = tieBreakTooltipMap.value.get(key)
  if (!info) return null

  if (info.rule === 2) {
    return `Tie-break Rule #2\nRerata Bidang\nNilai: ${fmt(info.value)}`
  }

  if (info.rule === 3) {
    return `Tie-break Rule #3\nHakim Ketua\nNilai: ${fmt(info.value)}`
  }

  return null
}


// =========================
// LIFECYCLE
// =========================
onMounted(async () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
    return
  }
  await fetchMeta()
  await fetchRanking()
  await fetchRankingDetails()
})

watch(
  () => route.params.id,
  async (val) => {
    selectedCompetitionId.value = String(val || '')
    activeCategoryId.value = 'all'
    await fetchRanking()
    await fetchRankingDetails()
  }
)

watch(
  () => eventId.value,
  async (val) => {
    if (!val) return
    activeCategoryId.value = 'all'
    await fetchMeta()
    await fetchRanking()
    await fetchRankingDetails()
  }
)

watch(
  () => activeCategoryId.value,
  async () => {
    await fetchRankingDetails()
  }
)
</script>

<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Ranking Kompetisi</h1>
          <p class="mb-0 text-muted text-sm">
            {{ competition?.full_name || 'Pilih kompetisi untuk melihat ranking.' }}
            <span v-if="competitionId && competition" class="ml-2">
              <span class="badge" :class="isTeam ? 'badge-dark' : 'badge-secondary'">
                {{ isTeam ? 'TEAM' : 'INDIVIDU' }}
              </span>
            </span>
          </p>
        </div>

        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" @click="fetchRanking" :disabled="loading || !competitionId">
            <i class="fas fa-sync"></i> Refresh
          </button>
          <button class="btn btn-sm btn-primary" @click="recalc" :disabled="loading || !competitionId">
            <i class="fas fa-calculator"></i> Hitung Ulang
          </button>
          <button
            class="btn btn-sm btn-success"
            @click="publishTop6"
            :disabled="
              loading ||
              !competitionId ||
              activeCategoryId === 'all' ||
              filteredRanking.length === 0 ||
              !isFinalRound ||
              !canPublish
            "
          >
            <i class="fas fa-award"></i> Publish Top 6
          </button>

          <button class="btn btn-sm btn-outline-success" :disabled="!competitionId || activeCategoryId === 'all'" @click="exportCategory">
            <i class="fas fa-file-excel"></i> Export
          </button>
        </div>
      </div>

      <small v-if="competitionId && activeCategoryId === 'all'" class="text-muted d-block mt-1">
        <i class="fas fa-info-circle mr-1"></i>
        Pilih tab kategori (Putra/Putri) untuk publish & melihat detail nilai.
      </small>

      <!-- ✅ CARD: Deskripsi Mekanisme Penilaian -->
      <div class="card mt-3"  v-if="competitionId && isFinalRound && activeCategoryId !== 'all' && medalPreview.length">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="font-weight-bold mb-1">
                <i class="fas fa-info-circle mr-1"></i>
                Mekanisme Penilaian & Tie-break
              </div>

              <div class="text-muted text-sm">
                Ranking ditentukan berdasarkan <strong>Final Score</strong>.
                Jika terjadi nilai sama, sistem memakai urutan bidang dari <strong>Komponen Nilai Bidang</strong>
                (diurutkan berdasarkan urutan prioritas naik; null dianggap paling akhir) sebagai prioritas tie-break.
              </div>

              <ul class="text-sm mb-0 mt-2 pl-3">
                <li>
                  <strong>Rule #1</strong> — Urutkan <code>final_score</code> <strong>DESC</strong>.
                </li>
                <li>
                  <strong>Rule #2</strong> — Jika sama: bandingkan <strong>rerata nilai per bidang</strong>
                  sesuai urutan bidang.
                </li>
                <li>
                  <strong>Rule #3</strong> — Jika masih sama: bandingkan <strong>nilai Hakim Ketua</strong> per bidang.
                </li>
              </ul>

              <div class="text-muted text-xs mt-2" v-if="isTeam">
                <i class="fas fa-users mr-1"></i>
                Mode GROUP: 1 baris ranking = <strong>Kontingen + Kategori</strong> (Putra/Putri). Anggota digabung.
              </div>
            </div>

            <div class="text-right ml-3" style="min-width:220px;">
              <div class="text-muted text-xs">Hakim Ketua (ID)</div>
              <div class="font-weight-bold" style="font-size: 1.1rem;">
                <span v-if="chiefJudgeId">{{ chiefJudgeId }}</span>
                <span v-else class="text-muted">-</span>
              </div>
              <div class="text-muted text-xs mt-1">
                Kolom hakim ketua ditandai warna <span class="badge badge-warning">kuning</span>.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- selector -->
      <div class="card mt-3" v-if="eventId">
        <div class="card-body py-2">
          <div class="form-row align-items-end">
            <div class="form-group col-md-8 mb-0">
              <label class="mb-1 text-sm">Pilih Kompetisi <span class="text-danger">*</span></label>
              <select
                v-model="selectedCompetitionId"
                class="form-control form-control-sm"
                :disabled="isLoadingMeta"
                @change="onCompetitionChange"
              >
                <option value="" disabled>-- Pilih Kompetisi --</option>
                <optgroup v-for="r in competitionsByRound" :key="'r-' + r.id" :label="r.name">
                  <option v-for="c in r.competitions" :key="c.id" :value="String(c.id)">
                    {{ c.full_name }}
                  </option>
                </optgroup>
              </select>
              <small v-if="isLoadingMeta" class="text-muted">
                <i class="fas fa-spinner fa-spin mr-1"></i> Memuat daftar kompetisi...
              </small>
            </div>

            <div class="form-group col-md-4 mb-0">
              <button class="btn btn-sm btn-outline-secondary w-100" :disabled="isLoadingMeta" @click="fetchMeta">
                <i class="fas fa-sync mr-1"></i> Refresh Kompetisi
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- medal preview -->
      <div
        v-if="competitionId && isFinalRound && activeCategoryId !== 'all' && medalPreview.length"
        class="alert alert-warning py-2 mb-3"
        style="border-radius: 8px"
      >
        <div class="font-weight-bold mb-1">
          <i class="fas fa-award mr-1"></i>
          Preview Medali ({{ categoryTabs.find((c) => c.id === activeCategoryId)?.name }})
        </div>

        <ul class="mb-0 pl-3 text-sm">
          <li v-for="m in medalPreview" :key="'m-' + (m.group_key || m.event_participant_id)">
            {{ m.medal }} —
            <strong>{{ isTeam ? (m.full_name || m.contingent) : m.full_name }}</strong>
            ({{ fmt(m.final_score) }}) • {{ m.contingent || '-' }}
          </li>
        </ul>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body table-responsive p-0">
          <!-- CATEGORY TABS -->
          <div class="mb-2" v-if="competitionId && categoryTabs.length">
            <ul class="nav nav-pills">
              <li class="nav-item" v-for="t in categoryTabs" :key="'cat-' + t.id">
                <a
                  href="#"
                  class="nav-link"
                  :class="{ active: activeCategoryId === t.id }"
                  @click.prevent="activeCategoryId = t.id"
                  style="font-weight: 600"
                >
                  {{ t.name }}
                  <span class="badge badge-light border ml-2">{{ t.count }}</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- =========================
               TABLE 1: RANKING SUMMARY
               ========================= -->
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr v-if="isTeam">
                <th style="width: 60px" class="text-center">No</th>
                <th>Kontingen</th>
                <th>Anggota</th>
                <th class="text-center" style="width:110px">Hakim</th>
                <th class="text-center" style="width:130px">Final Score</th>
              </tr>

              <tr v-else>
                <th style="width: 60px" class="text-center">Rank</th>
                <th>Peserta</th>
                <th>Kontingen</th>
                <th class="text-center">Hakim</th>
                <th class="text-center">Final Score</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="loading">
                <td :colspan="isTeam ? 5 : 5" class="text-center p-3 text-muted">Loading...</td>
              </tr>

              <tr v-else-if="!competitionId">
                <td :colspan="isTeam ? 5 : 5" class="text-center p-3 text-muted">Silakan pilih kompetisi.</td>
              </tr>

              <tr v-else-if="ranking.length === 0">
                <td :colspan="isTeam ? 5 : 5" class="text-center p-3 text-muted">Belum ada ranking (nilai belum ada?).</td>
              </tr>

              <!-- TEAM summary -->
              <tr v-else-if="isTeam" v-for="(r, idx) in filteredRanking" :key="r.group_key || idx">
                <td class="text-center font-weight-bold">{{ idx + 1 }}</td>
                <td>
                  <div class="font-weight-bold">{{ r.full_name || r.contingent || '-' }}</div>
                  <div class="text-muted text-xs">{{ r.category_name || r.category || '-' }}</div>
                </td>
                <td class="text-muted text-xs">
                  {{ memberText(r) }}
                </td>
                <td class="text-center">{{ judgeCount(r) }}</td>
                <td class="text-center font-weight-bold">{{ fmt(r.final_score) }}</td>
              </tr>

              <!-- INDIVIDU summary -->
              <tr v-else v-for="r in filteredRanking" :key="r.event_participant_id">
                <td class="text-center font-weight-bold">{{ r.rank }}</td>
                <td>
                  <div class="font-weight-bold">{{ r.full_name }}</div>
                  <div class="text-muted text-xs">
                    {{ r.branch || '-' }} • {{ r.group || '-' }} • {{ r.category || '-' }}
                  </div>
                </td>
                <td>{{ r.contingent || '-' }}</td>
                <td class="text-center">{{ r.judge_count ?? Object.keys(r.scores || {}).length }}</td>
                <td class="text-center font-weight-bold">{{ fmt(r.final_score) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- =========================
               TABLE 2: DETAIL
               ========================= -->
          <div class="p-2 border-top" v-if="competitionId && activeCategoryId !== 'all'">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="font-weight-bold">
                <i class="fas fa-list mr-1"></i>
                Detail Nilai
                <small v-if="chiefJudgeId" class="text-muted ml-2">• Ketua: <strong>{{ chiefJudgeId }}</strong></small>
              </div>
              <small class="text-muted">
                Kategori: <strong>{{ categoryTabs.find((c) => c.id === activeCategoryId)?.name || '-' }}</strong>
              </small>
            </div>

            <div v-if="isLoadingDetails" class="text-center text-muted py-3">
              <i class="fas fa-spinner fa-spin mr-1"></i> Memuat detail nilai...
            </div>

            <div v-else-if="!detail.judges.length" class="text-center text-muted py-3">
              Detail nilai belum tersedia (judges kosong).
            </div>

            <!-- ✅ TEAM DETAIL: Bidang (top) → Hakim (sub) -->
            <div v-else-if="isTeam" class="table-responsive">
              <table class="table table-bordered table-sm text-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th rowspan="2" class="text-center align-middle" style="width:30px">#</th>
                    <th rowspan="2" class="align-middle" style="min-width:160px">Kontingen</th>
                    <!-- <th rowspan="2" class="text-center align-middle" style="width:90px">Jml Anggota</th> -->
                    <th rowspan="2" class="align-middle" style="min-width:260px">Anggota</th>

                    <th
                      v-for="f in detail.fields"
                      :key="'fh-' + f.id"
                      class="text-center"
                      :colspan="detail.judges.length + 1"
                    >
                      {{ f.name }}
                    </th>

                    <th rowspan="2" class="text-center align-middle" style="width:110px">
                      Final<br />Score
                    </th>
                  </tr>

                  <tr>
                    <template v-for="f in detail.fields" :key="'fs-' + f.id">
                      <th
                        v-for="(j, jIdx) in detail.judges"
                        :key="'fj-' + f.id + '-' + j.id"
                        class="text-center"
                        style="width:70px"
                        :class="{ 'chief-col': isChiefJudge(j.id) }"
                        :title="isChiefJudge(j.id) ? 'Hakim Ketua' : ''"
                      >
                        {{ judgeLabel(jIdx) }}
                      </th>
                      <th class="text-center" style="width:90px">Rerata</th>
                    </template>
                  </tr>
                </thead>



                <tbody>
                  <tr v-for="(row, idx) in detail.rows" :key="row.group_key">
                    <td class="text-center font-weight-bold">{{ idx + 1 }}</td>

                    <td>
                      <div class="font-weight-bold">{{ row.contingent }}</div>
                      <!-- <div class="text-muted text-xs">{{ row.category_name }}</div> -->
                    </td>

                    <!-- <td class="text-center">{{ row.member_count }}</td> -->

                    <td class="text-muted text-xs">
                      {{ row.member_names.join(', ') }}
                    </td>

                    <!-- FIELD × JUDGE -->
                    <template v-for="f in detail.fields" :key="'bf-' + f.id">
                      <!-- nilai per hakim -->
                      <td
                        v-for="j in detail.judges"
                        :key="'bj-' + row.group_key + '-' + f.id + '-' + j.id"
                        class="text-center"
                        :class="{ 'chief-col': isChiefJudge(j.id) }"
                        :title="tieBreakTooltip(row, f.id)"
                      >
                        <span v-if="getTeamScore(row, j.id, f.id) != null">
                          {{ fmt(getTeamScore(row, j.id, f.id)) }}
                        </span>
                        <span v-else class="text-muted">-</span>
                      </td>

                      <!-- rerata bidang -->
                      <td
                        class="text-center font-weight-bold"
                        :class="{ 'tie-win': isTieWin(row, f.id) }"
                        :title="tieBreakTooltip(row, f.id)"
                      >
                        <span v-if="row.field_avg?.[f.id] != null">
                          {{ fmt(row.field_avg[f.id]) }}
                        </span>
                        <span v-else class="text-muted">-</span>
                      </td>
                    </template>

                    <!-- FINAL SCORE -->
                    <td class="text-center font-weight-bold">
                      {{ fmt(row.final_score) }}
                    </td>
                  </tr>
                </tbody>

              </table>

              <small class="text-muted d-block mt-2">
                * Mode TEAM: tabel menampilkan nilai per <strong>bidang</strong> (Tahfizh/Tajwid/dll) lalu per <strong>hakim</strong> (H1..Hn) + rerata.<br />
                * Kolom hakim ketua ditandai warna kuning lembut.
              </small>
            </div>


            <!-- ✅ INDIVIDU DETAIL: field × hakim -->
            <div v-else-if="!detail.fields.length" class="text-center text-muted py-3">
              Detail nilai belum tersedia (fields kosong).
            </div>

            <div v-else class="table-responsive">
              <table class="table table-bordered table-sm text-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th rowspan="2" style="width: 30px" class="text-center align-middle">#</th>
                    <th rowspan="2" style="min-width: 220px" class="align-middle">Peserta</th>
                    <th rowspan="2" style="min-width: 160px" class="align-middle">Kontingen</th>

                    <th
                      v-for="f in detail.fields"
                      :key="'grp-' + f.id"
                      class="text-center"
                      :colspan="detail.judges.length + 1"
                    >
                      {{ f.name }}
                    </th>

                    <th rowspan="2" class="text-center align-middle" style="width: 100px">Final Score</th>
                    <!-- <th rowspan="2" class="text-center align-middle" style="min-width: 100px">SUM Total</th> -->
                  </tr>

                  <tr>
                    <template v-for="f in detail.fields" :key="'sub-' + f.id">
                      <th
                        v-for="(j, jIdx) in detail.judges"
                        :key="'hj-' + f.id + '-' + j.id"
                        class="text-center"
                        style="width: 70px"
                        :class="{ 'chief-col': isChiefJudge(j.id) }"
                        :title="isChiefJudge(j.id) ? 'Hakim Ketua' : ''"
                      >
                        {{ judgeLabel(jIdx) }}
                      </th>
                      <th class="text-center" style="width: 90px">Rerata</th>
                    </template>
                  </tr>
                </thead>

                <tbody>
                  <tr v-if="detail.rows.length === 0">
                    <td :colspan="3 + detail.fields.length * (detail.judges.length + 1) + 2" class="text-center text-muted p-3">
                      Tidak ada data detail untuk kategori ini.
                    </td>
                  </tr>

                  <tr v-for="row in detail.rows" :key="'row-' + row.event_participant_id">
                    <td class="text-center font-weight-bold">{{ row.rank }}</td>
                    <td>
                      <div class="font-weight-bold">{{ row.full_name }}</div>
                      <!-- <div class="text-muted text-xs">
                        {{ row.branch || '-' }} • {{ row.group || '-' }} • {{ row.category || row.category_name || '-' }}
                      </div> -->
                    </td>
                    <td>{{ row.contingent || '-' }}</td>

                    <template v-for="f in detail.fields" :key="'rf-' + row.event_participant_id + '-' + f.id">
                      <td
                        v-for="j in detail.judges"
                        :key="'cell-' + row.event_participant_id + '-' + f.id + '-' + j.id"
                        class="text-center"
                        :class="{ 'chief-col': isChiefJudge(j.id) }"
                      >
                        <!-- :title="tieBreakTooltip(row, f.id)" -->


                        <span v-if="getScore(row, j.id, f.id) != null">{{ fmt(getScore(row, j.id, f.id)) }}</span>
                        <span v-else class="text-muted">-</span>
                      </td>

                      <td class="text-center font-weight-bold" :class="{ 'tie-win': isTieWin(row, f.id) }" :title="tieBreakTooltip(row, f.id)">
                        <span v-if="getFieldAvg(row, f.id) != null">{{ fmt(getFieldAvg(row, f.id)) }}</span>
                        <span v-else class="text-muted">-</span>
                      </td>
                    </template>

                    <td class="text-center font-weight-bold">
                      <span v-if="row.final_score != null">{{ fmt(row.final_score) }}</span>
                      <span v-else class="text-muted">-</span>
                    </td>

                    <!-- <td class="text-center font-weight-bold">
                      <span v-if="row.sum_score != null">{{ fmt(row.sum_score) }}</span>
                      <span v-else class="text-muted">-</span>
                    </td> -->
                  </tr>
                </tbody>
              </table>

              <small class="text-muted d-block mt-2">
                * Highlight hijau = pemenang tie-break pada <strong>bidang pertama</strong> yang berbeda.<br />
                * Kolom hakim ketua diwarnai kuning lembut.
              </small>
            </div>
          </div>
        </div>

        <div class="card-footer text-muted text-sm">
          Total {{ isTeam ? 'kontingen' : 'peserta' }}: <strong>{{ filteredRanking.length }}</strong>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.text-xs { font-size: 0.75rem; }

/* highlight pemenang tie-break */
.tie-win { background: #d4edda !important; }

/* kolom hakim ketua */
.chief-col { background: rgba(255, 193, 7, 0.18) !important; }
</style>
