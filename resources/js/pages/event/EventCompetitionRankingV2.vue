<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Ranking Kompetisi</h1>
          <p class="mb-0 text-muted text-sm">
            Pilih kompetisi berdasarkan ronde, lalu sistem akan menampilkan ranking akhir.
          </p>

          <div v-if="competition" class="text-muted text-sm mt-1">
            Group:
            <strong>{{ competition.event_group.full_name }}</strong>
            • Mode:
            <span
              class="badge"
              :class="scoringMode === 'BY_PANEL'
                ? 'badge-info'
                : 'badge-warning'"
            >
              {{ scoringMode }}
            </span>
            •
            <span class="badge badge-light border">
              {{ isTeam ? 'REGU' : 'INDIVIDU' }}
            </span>
          </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="d-flex align-items-center gap-2">
          <button
            class="btn btn-sm btn-outline-secondary mr-2"
            :disabled="isLoadingMeta"
            @click="fetchCompetitions"
          >
            <i class="fas fa-sync mr-1"></i> Refresh
          </button>

          <!-- 🔥 PUBLISH BUTTON -->
          <button
            class="btn btn-sm btn-success"
            :disabled="!canPublish || isPublishing"
            @click="publishRanking"
          >
            <i
              class="fas mr-1"
              :class="isPublishing ? 'fa-spinner fa-spin' : 'fa-bullhorn'"
            ></i>
            {{ isPublishing ? 'Publishing...' : 'Publish Ranking' }}
          </button>

        </div>
      </div>

    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">

      <!-- ============ SELECT KOMPETISI ============ -->
      <div class="card shadow-sm">
        <div class="card-body py-2">
          <div class="form-row align-items-end">

            <!-- SELECT KOMPETISI -->
            <div class="form-group col-md-8 mb-0">
              <label class="text-sm mb-1 d-block">
                Pilih Kompetisi <span class="text-danger">*</span>
              </label>

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

              <small v-if="isLoadingMeta" class="text-muted">
                <i class="fas fa-spinner fa-spin mr-1"></i>
                Memuat daftar kompetisi...
              </small>
            </div>

            <!-- BUTTON -->
            <div class="form-group col-md-4 mb-0">
              <button
                class="btn btn-sm btn-outline-primary w-100"
                style="height: 31px"
                :disabled="!selectedCompetitionId || isLoadingRanking"
                @click="fetchRanking"
              >
                <i class="fas fa-list mr-1"></i>
                Tampilkan Ranking
              </button>
            </div>

          </div>
        </div>
      </div>


      <!-- ============ RANKING TABLE ============ -->
      <div class="card mt-3 shadow-sm">
        <div class="card-body p-0 table-responsive">

          <div class="px-3 pt-2" v-if="ranking.length">
            <ul class="nav nav-pills nav-sm">
              <li class="nav-item">
                <a
                  href="#"
                  class="nav-link"
                  :class="{ active: activeCategory === 'all' }"
                  @click.prevent="activeCategory = 'all'"
                >
                  All
                </a>
              </li>

              <li class="nav-item">
                <a
                  href="#"
                  class="nav-link"
                  :class="{ active: activeCategory === 'putra' }"
                  @click.prevent="activeCategory = 'putra'"
                >
                  Putra
                </a>
              </li>

              <li class="nav-item">
                <a
                  href="#"
                  class="nav-link"
                  :class="{ active: activeCategory === 'putri' }"
                  @click.prevent="activeCategory = 'putri'"
                >
                  Putri
                </a>
              </li>
            </ul>
          </div>


          <table class="table table-bordered table-sm mb-0 mt-2">
            <thead class="thead-light">
              <!-- REGU -->
              <tr v-if="isTeam">
                <th style="width:60px" class="text-center">Rank</th>
                <th>Kontingen</th>
                <th class="text-center" style="width:120px">Jumlah Anggota</th>
                <th class="text-center" style="width:140px">Final Score</th>
              </tr>

              <!-- INDIVIDU -->
              <tr v-else>
                <th style="width:60px" class="text-center">Rank</th>
                <th>Peserta</th>
                <th>Kontingen</th>
                <th class="text-center" style="width:140px">Final Score</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoadingRanking">
                <td :colspan="isTeam ? 4 : 4" class="text-center p-3 text-muted">
                  <i class="fas fa-spinner fa-spin mr-1"></i>
                  Memuat ranking...
                </td>
              </tr>

              <tr v-else-if="!selectedCompetitionId">
                <td :colspan="isTeam ? 4 : 4" class="text-center p-3 text-muted">
                  Silakan pilih kompetisi terlebih dahulu.
                </td>
              </tr>

              <tr v-else-if="ranking.length === 0">
                <td :colspan="isTeam ? 4 : 4" class="text-center p-3 text-muted">
                  Ranking belum tersedia.
                </td>
              </tr>

              <!-- REGU -->
              <tr
                v-else-if="isTeam"
                v-for="r in filteredRanking"
                :key="r.group_key"
              >
                <td class="text-center font-weight-bold">
                  {{ r.rank }}
                </td>

                <td>
                  <div class="font-weight-bold">
                    {{ r.contingent }}
                  </div>

                  <ul class="text-xs text-muted mb-0 pl-3 mt-1">
                    <li
                      v-for="m in r.members"
                      :key="m.event_participant_id"
                    >
                      {{ m.full_name }}
                    </li>
                  </ul>
                </td>


                <td class="text-center align-middle">
                  {{ r.member_count }}
                </td>

                <td class="text-center font-weight-bold align-middle text-primary">
                  {{ formatScore(r.final_score) }}
                </td>
              </tr>

              <!-- INDIVIDU -->
              <tr
                v-else
                v-for="r in filteredRanking"
                :key="r.event_participant_id"
              >
                <td class="text-center font-weight-bold align-middle">
                  {{ r.rank }}
                </td>

                <td class="align-middle">
                  <div class="font-weight-bold">
                    {{ r.full_name }}
                  </div>
                  <div class="text-muted text-xs">
                    {{ r.event_category?.full_name }}
                  </div>
                </td>

                <td class="align-middle">
                  {{ r.contingent || '-' }}
                </td>

                <td class="text-center font-weight-bold align-middle text-primary">
                  {{ formatScore(r.final_score) }}
                </td>
              </tr>
            </tbody>
          </table>

        </div>

        <div class="card-footer bg-white text-muted text-sm border-top-0">
          Total {{ isTeam ? 'kontingen' : 'peserta' }}:
          <strong>{{ filteredRanking.length }}</strong>
        </div>
      </div>

      <div
        v-if="competition && !canPublish"
        class="alert alert-warning py-2 mt-2 text-sm shadow-sm"
      >
        <i class="fas fa-info-circle mr-1"></i>
        Ranking hanya dapat dipublish pada <strong>babak FINAL</strong>.
      </div>


      <div class="card mt-3 shadow-sm" v-if="detail && activeCategory !== 'all'">
        <div class="card-body py-2 text-sm text-muted">
          <strong class="text-dark"><i class="fas fa-balance-scale mr-1"></i> Aturan Penentuan Peringkat (Tie Break):</strong>
          <ol class="mb-0 pl-3 mt-1">
            <li>Peringkat ditentukan berdasarkan <strong>Final Score</strong> tertinggi.</li>
            <li>Jika Final Score sama, maka dibandingkan <strong>rata-rata nilai per bidang</strong>, dari urutan pertama.</li>
            <li>Jika rata-rata bidang sama, dibandingkan <strong>Nilai Ketua Majelis</strong> pada bidang prioritas pertama.</li>
            <li>Jika masih sama, urutan ditentukan secara alfabetis kontingen/nama.</li>
          </ol>
          <div class="mt-2 text-xs">
            <span class="d-inline-block tie-break-highlight px-2 py-1 border rounded text-dark mr-1">Sorotan warna</span> 
            menandakan titik di mana persaingan seri (tie-break) berhasil diputuskan.
          </div>
        </div>
      </div>


      <!-- ============ RANKING DETAIL TABLE ============ -->
      <div
        v-if="detail && activeCategory !== 'all'"
        class="card mt-3 shadow-sm"
      >
        <div class="card-header bg-white py-2">
          <strong>
            Detail Nilai – {{ activeCategory.toUpperCase() }}
          </strong>
        </div>

        <div v-if="isLoadingDetail" class="card-body text-center text-muted py-4">
          <i class="fas fa-spinner fa-spin mr-1"></i>
          Memuat detail nilai...
        </div>

        <!-- COMPACT TABLE -->
        <div v-else class="card-body p-0 table-responsive compact-table-container">
          <table class="table table-bordered table-sm table-compact mb-0">
            <thead class="thead-light">
              <tr>
                <th rowspan="2" class="text-center align-middle" style="width:40px">#</th>
                <th rowspan="2" class="align-middle">Kontingen</th>
                <th rowspan="2" class="align-middle">Peserta</th>

                <th
                  v-for="f in detail.fields"
                  :key="'f-head-' + f.id"
                  :colspan="getJudgesByField(f.id).length + 1"
                  class="text-center align-middle"
                >
                  {{ f.name }}
                </th>

                <th rowspan="2" class="text-center align-middle" style="width:80px">
                  Final<br/>Score
                </th>
              </tr>

              <tr>
                <template v-for="f in detail.fields" :key="'f-sub-' + f.id">
                  <th
                    v-for="(j, jIdx) in getJudgesByField(f.id)"
                    :key="'j-' + f.id + '-' + j.id"
                    class="text-center align-middle px-1"
                  >
                    <!-- SEJAJARKAN LABEL KETUA -->
                    <div class="d-inline-flex align-items-center justify-content-center">
                      H{{ jIdx + 1 }}
                      <span v-if="j.is_chief" class="badge badge-warning text-xxs ml-1 px-1" title="Ketua Majelis">K</span>
                    </div>
                  </th>

                  <th class="text-center align-middle text-primary" style="width:50px">
                    Avg
                  </th>
                </template>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in detail.rows"
                :key="isTeam ? row.contingent : row.event_participant_id"
              >
                <!-- RANK -->
                <td class="text-center font-weight-bold">
                  {{ idx + 1 }}
                </td>

                <td>
                  <div class="font-weight-bold text-truncate" style="max-width: 150px;" :title="row.contingent">
                    {{ row.contingent }}
                  </div>
                </td>

                <!-- PESERTA / KONTINGEN -->
                <td v-if="!isTeam">
                  <div class="font-weight-bold text-truncate" style="max-width: 150px;" :title="row.full_name">
                    {{ row.full_name }}
                  </div>
                </td>
                <td v-else>
                  <div class="font-weight-bold text-truncate" style="max-width: 150px;" :title="row.contingent">
                    {{ row.contingent }}
                  </div>
                </td>
                
                <!-- FIELD × JUDGE -->
                <template v-for="f in detail.fields" :key="'f-row-' + f.id">
                  <td
                    v-for="j in getJudgesByField(f.id)"
                    :key="'cell-' + f.id + '-' + j.id"
                    class="text-center"
                    :class="{'tie-break-highlight': isTieBreakCell(row, 'chief_score', f.id, j.id)}"
                  >
                    <span v-if="row.scores?.[f.id]?.[j.id]?.length">
                      {{
                        formatScore(
                          row.scores[f.id][j.id].reduce((a, b) => a + b, 0)
                          / row.scores[f.id][j.id].length
                        )
                      }}
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>

                  <td 
                    class="text-center font-weight-bold text-primary"
                    :class="isTieBreakCell(row, 'field_avg', f.id) ? 'tie-break-highlight' : 'bg-light'"
                  >
                    {{ formatScore(row.field_avg?.[f.id]) }}
                  </td>
                </template>

                <!-- FINAL SCORE -->
                <td class="text-center font-weight-bold text-success">
                  {{ formatScore(row.final_score) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '@/stores/AuthUserStore'

/* ================= STATE ================= */
const isPublishing = ref(false)
const authUserStore = useAuthUserStore()
const eventId = computed(() => authUserStore.eventData?.id)

const competitionsByRound = ref([])
const selectedCompetitionId = ref('')

const competition = ref(null)
const ranking = ref([])

const isLoadingMeta = ref(false)
const isLoadingRanking = ref(false)

// filter kategori
const activeCategory = ref('all') // all | putra | putri

// ================= DETAIL STATE =================
const detail = ref(null)
const isLoadingDetail = ref(false)

// mapping putra/putri → event_category_id
const getActiveCategoryId = () => {
  if (activeCategory.value === 'all') return null
  const row = ranking.value.find(r => {
    const name = r.event_category?.full_name?.toLowerCase() || ''
    return name.includes(activeCategory.value)
  })
  return row?.event_category?.id || null
}

const getCellScore = (row, fieldId, judgeId) => {
  const vals = row.scores?.[fieldId]?.[judgeId] || []
  if (!vals.length) return null
  return vals.reduce((a, b) => a + b, 0) / vals.length
}

const canPublish = computed(() => {
  if (!competition.value) return false
  if (!ranking.value.length) return false
  const roundName = competition.value?.round?.name?.toLowerCase() || ''
  return roundName.includes('final')
})


/* ================= COMPUTED ================= */
const scoringMode = computed(() => competition.value?.event_group?.judge_assignment_mode)
const isTeam = computed(() => !!competition.value?.event_group?.is_team)

const normalizeCategory = (cat) => {
  if (!cat) return ''
  const name = cat.toLowerCase()
  if (name.includes('putra')) return 'putra'
  if (name.includes('putri')) return 'putri'
  return ''
}

const filteredRanking = computed(() => {
  if (activeCategory.value === 'all') return ranking.value
  return ranking.value.filter(r => {
    const catName = r.event_category?.full_name || ''
    return normalizeCategory(catName) === activeCategory.value
  })
})

/* ================= TIE BREAK LOGIC ================= */
// Menyimpan cell mana saja yang perlu disorot karena memecah seri
const tieBreaks = computed(() => {
  const map = new Map()
  if (!detail.value || !detail.value.rows || detail.value.rows.length < 2) return map

  const rows = detail.value.rows
  const fields = detail.value.fields
  const judges = detail.value.judges
  const chiefJudgeId = judges.find(j => j.is_chief)?.id

  const markTieBreak = (rowKey, type, fieldId, judgeId = null) => {
    if (!map.has(rowKey)) map.set(rowKey, [])
    map.get(rowKey).push({ type, fieldId, judgeId })
  }

  for (let i = 0; i < rows.length - 1; i++) {
    const r1 = rows[i]
    const r2 = rows[i + 1]
    const key1 = isTeam.value ? r1.contingent : r1.event_participant_id
    const key2 = isTeam.value ? r2.contingent : r2.event_participant_id

    // Jika final score seri, kita cari di mana serinya terpecahkan
    if (r1.final_score === r2.final_score) {
      let tieBrokenAtField = null

      // Cek rata-rata bidang
      for (const f of fields) {
        if (r1.field_avg[f.id] !== r2.field_avg[f.id]) {
          tieBrokenAtField = f.id
          break
        }
      }

      if (tieBrokenAtField) {
        // Terpecahkan di rata-rata bidang
        markTieBreak(key1, 'field_avg', tieBrokenAtField)
        markTieBreak(key2, 'field_avg', tieBrokenAtField)
      } 
      else if (chiefJudgeId && fields.length > 0) {
        // Jika rata-rata bidang sama semua, cek nilai Ketua di bidang pertama
        const firstFid = fields[0].id
        const s1 = r1.scores?.[firstFid]?.[chiefJudgeId]?.[0] ?? 0
        const s2 = r2.scores?.[firstFid]?.[chiefJudgeId]?.[0] ?? 0
        
        if (s1 !== s2) {
          markTieBreak(key1, 'chief_score', firstFid, chiefJudgeId)
          markTieBreak(key2, 'chief_score', firstFid, chiefJudgeId)
        }
      }
    }
  }
  return map
})

const isTieBreakCell = (row, type, fieldId, judgeId = null) => {
  const key = isTeam.value ? row.contingent : row.event_participant_id
  const marks = tieBreaks.value.get(key)
  if (!marks) return false
  return marks.some(m => m.type === type && m.fieldId === fieldId && m.judgeId === judgeId)
}

/* ================= HELPERS ================= */
const getJudgesByField = (fieldId) => {
  if (!detail.value?.rows?.length) return []
  const set = new Set()
  detail.value.rows.forEach(r => {
    const judges = r.scores?.[fieldId]
    if (judges) {
      Object.keys(judges).forEach(jid => set.add(Number(jid)))
    }
  })
  return detail.value.judges.filter(j => set.has(j.id))
}

const formatScore = (v) => {
  const n = Number(v)
  if (!Number.isFinite(n)) return '-'
  return n.toFixed(2)
}

/* ================= API ================= */
const fetchCompetitions = async () => {
  if (!eventId.value) return
  isLoadingMeta.value = true
  try {
    const { data } = await axios.get(`/api/v1/events/${eventId.value}/competitions/tree`)
    competitionsByRound.value = data.rounds || []
  } catch (e) {
    Swal.fire('Gagal', 'Gagal memuat daftar kompetisi', 'error')
  } finally {
    isLoadingMeta.value = false
  }
}

const fetchRanking = async () => {
  if (!selectedCompetitionId.value) return
  isLoadingRanking.value = true
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${selectedCompetitionId.value}/ranking-v2`)
    competition.value = data.competition
    ranking.value = data.ranking || []
  } catch (e) {
    Swal.fire('Gagal', 'Gagal memuat ranking', 'error')
  } finally {
    isLoadingRanking.value = false
  }
}

const publishRanking = async () => {
  if (!competition.value || isPublishing.value) return
  const roundName = competition.value?.round?.name || ''
  if (!roundName.toLowerCase().includes('final')) {
    Swal.fire('Tidak Diizinkan', 'Publish hanya diperbolehkan untuk babak FINAL.', 'warning')
    return
  }
  const confirm = await Swal.fire({
    title: 'Publish Ranking Final?',
    html: `
      <div class="text-left text-sm">
        <p><strong>Kompetisi:</strong> ${competition.value.full_name}</p>
        <p><strong>Babak:</strong> ${roundName}</p>
        <hr/>
        <p>
          Sistem akan:
          <ul>
            <li>📊 Mengambil ranking final</li>
            <li>🏅 Menetapkan <b>Top 6</b> per kategori</li>
            <li>📌 Mengisi medal standing & leaderboard</li>
          </ul>
        </p>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Publish',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#28a745',
  })

  if (!confirm.isConfirmed) return
  isPublishing.value = true
  try {
    await axios.post(`/api/v1/event-competitions/${competition.value.id}/ranking/publish`)
    Swal.fire('Berhasil', 'Ranking final berhasil dipublish (Top 6).', 'success')
  } catch (e) {
    const msg = e.response?.data?.message || 'Terjadi kesalahan saat publish ranking.'
    Swal.fire('Gagal', msg, 'error')
  } finally {
    isPublishing.value = false
  }
}

/* ================= WATCH ================= */
watch(selectedCompetitionId, () => {
  ranking.value = []
  competition.value = null
  activeCategory.value = 'all'
})

watch(activeCategory, async (val) => {
  if (val === 'all' || !selectedCompetitionId.value) {
    detail.value = null
    return
  }
  const categoryId = getActiveCategoryId()
  if (!categoryId) {
    detail.value = null
    return
  }
  isLoadingDetail.value = true
  try {
    const { data } = await axios.get(
      `/api/v1/event-competitions/${selectedCompetitionId.value}/ranking-v2/details`,
      { params: { event_category_id: categoryId } }
    )
    detail.value = data
  } catch (e) {
    Swal.fire('Gagal', 'Gagal memuat detail nilai', 'error')
    detail.value = null
  } finally {
    isLoadingDetail.value = false
  }
})

/* ================= LIFECYCLE ================= */
onMounted(fetchCompetitions)
</script>

<style scoped>
.text-xs {
  font-size: 0.75rem;
}

.text-xxs {
  font-size: 0.65rem;
}

/* =========================================
   STYLE COMPACT TABLE 1 BARIS UTUH
========================================== */
.compact-table-container {
  overflow-x: auto;
}

.table-compact {
  font-size: 0.8rem; /* Mengecilkan tulisan */
  white-space: nowrap; /* Mencegah tulisan turun ke bawah / wrapping */
}

.table-compact th, 
.table-compact td {
  padding: 0.35rem 0.5rem !important; /* Padding diminimalkan */
}

/* =========================================
   STYLE INDIKATOR TIE BREAK
========================================== */
.tie-break-highlight {
  background-color: #fff3cd !important; /* Warna kuning muda / warning */
  color: #856404 !important;
  font-weight: 700;
  box-shadow: inset 0 0 0 1px #ffeeba; /* Memberi efek border tipis ke dalam */
}
</style>