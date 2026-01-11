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
      <div class="card">
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
      <div class="card mt-3">
        <div class="card-body p-0 table-responsive">

          <div class="px-3 " v-if="ranking.length">
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


          <table class="table table-bordered table-sm mb-0">
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

                <!-- <td>
                  <div class="font-weight-bold">
                    {{ r.contingent }}
                  </div>
                  <div class="text-muted text-xs">
                    {{ r.event_category?.full_name }}
                  </div>
                </td> -->

                <td>
                  <div class="font-weight-bold">
                    {{ r.contingent }}
                  </div>

                  <ul class="text-xs text-muted mb-0 pl-3">
                    <li
                      v-for="m in r.members"
                      :key="m.event_participant_id"
                    >
                      {{ m.full_name }}
                    </li>
                  </ul>
                </td>


                <td class="text-center">
                  {{ r.member_count }}
                </td>

                <td class="text-center font-weight-bold">
                  {{ formatScore(r.final_score) }}
                </td>
              </tr>

              <!-- INDIVIDU -->
              <tr
                v-else
                v-for="r in filteredRanking"
                :key="r.event_participant_id"
              >
                <td class="text-center font-weight-bold">
                  {{ r.rank }}
                </td>

                <td>
                  <div class="font-weight-bold">
                    {{ r.full_name }}
                  </div>
                  <div class="text-muted text-xs">
                    {{ r.event_category?.full_name }}
                  </div>
                </td>

                <td>
                  {{ r.contingent || '-' }}
                </td>

                <td class="text-center font-weight-bold">
                  {{ formatScore(r.final_score) }}
                </td>
              </tr>
            </tbody>
          </table>

        </div>

        <div class="card-footer text-muted text-sm">
          Total {{ isTeam ? 'kontingen' : 'peserta' }}:
          <strong>{{ filteredRanking.length }}</strong>
        </div>
      </div>

      <div
        v-if="competition && !canPublish"
        class="alert alert-warning py-2 mt-2 text-sm"
      >
        <i class="fas fa-info-circle mr-1"></i>
        Ranking hanya dapat dipublish pada <strong>babak FINAL</strong>.
      </div>


      <div class="card mt-2" v-if="detail && activeCategory !== 'all'">
        <div class="card-body py-2 text-sm text-muted">
          <strong>Aturan Penentuan Peringkat (Tie Break):</strong>
          <ol class="mb-0 pl-3">
            <li>
              Peringkat ditentukan berdasarkan <strong>Final Score</strong> tertinggi.
            </li>
            <li>
              Jika Final Score sama, maka dibandingkan
              <strong>rata-rata nilai per bidang</strong>,
              dimulai dari bidang pertama sesuai urutan penilaian.
            </li>
            <li>
              Jika seluruh nilai bidang masih sama, maka urutan ditentukan secara
              <strong>stabil</strong> berdasarkan nama peserta / kontingen.
            </li>
          </ol>
        </div>
      </div>


      <!-- ============ RANKING DETAIL TABLE ============ -->
      <div
        v-if="detail && activeCategory !== 'all'"
        class="card mt-3"
      >
        <div class="card-header py-2">
          <strong>
            Detail Nilai – {{ activeCategory.toUpperCase() }}
          </strong>
        </div>

        <div v-if="isLoadingDetail" class="card-body text-center text-muted py-4">
          <i class="fas fa-spinner fa-spin mr-1"></i>
          Memuat detail nilai...
        </div>

        <div v-else class="card-body p-0 table-responsive">
          <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th rowspan="2" class="text-center" style="width:50px">#</th>

                <th rowspan="2">Kontingen</th>

                <th rowspan="2">Peserta</th>


                <th
                  v-for="f in detail.fields"
                  :key="'f-head-' + f.id"
                  :colspan="getJudgesByField(f.id).length + 1"
                  class="text-center"
                >
                  {{ f.name }}
                </th>


                <th rowspan="2" class="text-center" style="width:120px">
                  Final Score
                </th>
              </tr>

              <tr>
                <template v-for="f in detail.fields" :key="'f-sub-' + f.id">
                  <th
                    v-for="(j, jIdx) in getJudgesByField(f.id)"
                    :key="'j-' + f.id + '-' + j.id"
                    class="text-center"
                    style="width:70px"
                  >
                    H{{ jIdx + 1 }}
                  </th>

                  <th class="text-center" style="width:80px">
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
                  <div class="font-weight-bold">{{ row.contingent }}</div>
                </td>

                <!-- PESERTA / KONTINGEN -->
                <td v-if="!isTeam">
                  <div class="font-weight-bold">{{ row.full_name }}</div>
                </td>
                <td v-else>
                  <div class="font-weight-bold">{{ row.contingent }}</div>

                  <ul class="mb-0 pl-3 text-sm text-muted">
                    <li v-for="m in row.members" :key="m.id">
                      {{ m.full_name }}
                    </li>
                  </ul>
                </td>
                

                <!-- FIELD × JUDGE -->
                <template v-for="f in detail.fields" :key="'f-row-' + f.id">
                  <td
                    v-for="j in getJudgesByField(f.id)"
                    :key="'cell-' + f.id + '-' + j.id"
                    class="text-center"
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

                  <td class="text-center font-weight-bold bg-light">
                    {{ formatScore(row.field_avg?.[f.id]) }}
                  </td>
                </template>



                <!-- FINAL SCORE -->
                <td class="text-center font-weight-bold">
                  {{ formatScore(row.final_score) }}
                </td>
              </tr>
            </tbody>
          </table>

          <small class="text-muted d-block mt-2 px-2">
            * Nilai bidang = rata-rata nilai hakim pada bidang tersebut.<br />
            * Final Score = jumlah seluruh rata-rata bidang.
          </small>
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
const scoringMode = computed(() =>
  competition.value?.event_group?.judge_assignment_mode
)

const isTeam = computed(() =>
  !!competition.value?.event_group?.is_team
)

/* ================= COMPUTED ================= */
const normalizeCategory = (cat) => {
  if (!cat) return ''
  const name = cat.toLowerCase()
  if (name.includes('putra')) return 'putra'
  if (name.includes('putri')) return 'putri'
  return ''
}

const filteredRanking = computed(() => {
  if (activeCategory.value === 'all') {
    return ranking.value
  }

  return ranking.value.filter(r => {
    const catName = r.event_category?.full_name || ''
    return normalizeCategory(catName) === activeCategory.value
  })
})


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
    const { data } = await axios.get(
      `/api/v1/events/${eventId.value}/competitions/tree`
    )
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
    const { data } = await axios.get(
      `/api/v1/event-competitions/${selectedCompetitionId.value}/ranking-v2`
    )

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
    Swal.fire(
      'Tidak Diizinkan',
      'Publish hanya diperbolehkan untuk babak FINAL.',
      'warning'
    )
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
    const res = await axios.post(
      `/api/v1/event-competitions/${competition.value.id}/ranking/publish`
    )

    Swal.fire(
      'Berhasil',
      'Ranking final berhasil dipublish (Top 6).',
      'success'
    )
  } catch (e) {
    const msg =
      e.response?.data?.message ||
      'Terjadi kesalahan saat publish ranking.'

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
      {
        params: {
          event_category_id: categoryId,
        },
      }
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
</style>
