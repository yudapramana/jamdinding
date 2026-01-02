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

const categoryTabs = computed(() => {
  const map = new Map()
  const list = ranking.value || []

  for (const r of list) {
    const cid = String(r?.event_category_id || '')
    if (!cid) continue

    const cname = r?.category_name || r?.category || '-'

    if (!map.has(cid)) {
      map.set(cid, {
        id: cid,
        name: cname,
        count: 0,
      })
    }
    map.get(cid).count++
  }

  return [
    { id: 'all', name: 'Semua Peserta', count: list.length },
    ...Array.from(map.values()),
  ]
})

const filteredRanking = computed(() => {
  if (activeCategoryId.value === 'all') return ranking.value || []
  return (ranking.value || []).filter(
    r => String(r.event_category_id) === String(activeCategoryId.value)
  )
})



// ==== selector state (tanpa :id)
const selectedCompetitionId = ref(String(route.params.id || ''))
const competitionsByRound = ref([]) // rounds tree
const isLoadingMeta = ref(false)

// ==== ranking state
const loading = ref(false)
const competition = ref(null)
const ranking = ref([])

const competitionId = computed(() => selectedCompetitionId.value || null)

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

const fetchRanking = async () => {
  if (!competitionId.value) {
    competition.value = null
    ranking.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${competitionId.value}/ranking`)
    competition.value = data.competition
    ranking.value = data.ranking || []
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
  } finally {
    loading.value = false
  }
}

const publishTop4 = async () => {
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
    await axios.post(
      `/api/v1/event-competitions/${competitionId.value}/ranking/publish`,
      { top_n: 4 }
    )
    Swal.fire('Published', 'Top 4 berhasil dipublish.', 'success')
  } finally {
    loading.value = false
  }
}


const onCompetitionChange = async () => {
  if (!selectedCompetitionId.value) return

  // simpan last untuk shortcut menu lain
  localStorage.setItem('last_scoring_competition_id', String(selectedCompetitionId.value))

  // pindah ke route detail agar URL punya :id (enak untuk share/bookmark)
  router.replace({
    name: 'admin.event.ranking.index',
    params: { id: selectedCompetitionId.value },
  })
}

const fmt = (n) => (Number(n || 0)).toFixed(2)

const isFinalRound = computed(() => {
  const name = (competition.value?.round?.name || '').toString().trim().toLowerCase()

  return name.includes('final')
})

const canPublish = computed(() => {
  const slug = (authUserStore.user?.role?.slug || '').toString().trim().toLowerCase()
  const name = (authUserStore.user?.role?.name || '').toString().trim().toLowerCase()

  return slug === 'superadmin' || slug === 'super_admin' || name === 'superadmin'
})


const medalPreview = computed(() => {
  const list = filteredRanking.value || []

  return list.slice(0, 4).map((r, idx) => ({
    ...r,
    medal:
      idx === 0 ? '🥇 Emas'
      : idx === 1 ? '🥈 Perak'
      : idx === 2 ? '🥉 Perunggu'
      : idx === 3 ? '4️⃣ Peringkat 4'
      : null,
  }))
})


const exportCategory = async () => {
  if (!competitionId.value || activeCategoryId.value === 'all') return

  const { data } = await axios.get(
    `/api/v1/event-competitions/${competitionId.value}/ranking/export`,
    { params: { event_category_id: activeCategoryId.value } }
  )

  console.log('EXPORT DATA', data)
  Swal.fire('OK', 'Data siap diexport (lihat console / lanjutkan Excel).', 'success')
}



onMounted(async () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
    return
  }
  await fetchMeta()
  await fetchRanking()
})

// kalau user buka langsung /:id/ranking
watch(
  () => route.params.id,
  async (val) => {
    selectedCompetitionId.value = String(val || '')
    await fetchRanking()
  }
)

// kalau event aktif berubah
watch(
  () => eventId.value,
  async (val) => {
    if (!val) return
    await fetchMeta()
    await fetchRanking()
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
            @click="publishTop4"
            :disabled="
              loading ||
              !competitionId ||
              activeCategoryId === 'all' ||
              filteredRanking.length === 0 ||
              !isFinalRound ||
              !canPublish
            "
          >
            <i class="fas fa-award"></i> Publish Top 4
          </button>


          <button
            class="btn btn-sm btn-outline-success"
            :disabled="!competitionId || activeCategoryId === 'all'"
            @click="exportCategory"
          >
            <i class="fas fa-file-excel"></i> Export
          </button>
        </div>
        <!-- <small v-if="competitionId && !isFinalRound" class="text-muted d-block mt-1">
            <i class="fas fa-info-circle mr-1"></i>
            Publish hanya tersedia pada babak <strong>Final</strong>
          </small>
          <small v-if="competitionId && !canPublish" class="text-muted d-block mt-1">
            <i class="fas fa-lock mr-1"></i>
            Publish hanya dapat dilakukan oleh <strong>SUPERADMIN</strong>
          </small> -->
      </div>

      <div class="text-muted text-xs mt-1">
        Round: <strong>{{ competition?.round?.name || '-' }}</strong>
        • Final? <strong>{{ isFinalRound ? 'YES' : 'NO' }}</strong>
        • Role: <strong>{{ authUserStore.user?.role?.slug || authUserStore.user?.role?.name || '-' }}</strong>
        • Can publish? <strong>{{ canPublish ? 'YES' : 'NO' }}</strong>
      </div>

      <small v-if="competitionId && activeCategoryId === 'all'" class="text-muted d-block mt-1">
        <i class="fas fa-info-circle mr-1"></i>
        Pilih tab kategori (Putra/Putri) dulu untuk publish.
      </small>


      <!-- selector (tanpa :id) -->
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
                <optgroup v-for="r in competitionsByRound" :key="'r-'+r.id" :label="r.name">
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

      <div
            v-if="competitionId && isFinalRound && activeCategoryId !== 'all' && medalPreview.length"
            class="alert alert-warning py-2 mb-3"
            style="border-radius:8px;"
          >
            <div class="font-weight-bold mb-1">
              <i class="fas fa-award mr-1"></i> Preview Medali ({{ categoryTabs.find(c => c.id === activeCategoryId)?.name }})
            </div>

            <ul class="mb-0 pl-3 text-sm">
              <li v-for="m in medalPreview" :key="'m-'+m.event_participant_id">
                {{ m.medal }} —
                <strong>{{ m.full_name }}</strong>
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
              <li class="nav-item" v-for="t in categoryTabs" :key="'cat-'+t.id">
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

          

          

          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:60px" class="text-center">Rank</th>
                <th>Peserta</th>
                <th>Kontingen</th>
                <th class="text-center">Hakim</th>
                <th class="text-center">Final Score</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="5" class="text-center p-3 text-muted">Loading...</td>
              </tr>

              <tr v-else-if="!competitionId">
                <td colspan="5" class="text-center p-3 text-muted">Silakan pilih kompetisi.</td>
              </tr>

              <tr v-else-if="ranking.length === 0">
                <td colspan="5" class="text-center p-3 text-muted">Belum ada ranking (nilai belum ada?).</td>
              </tr>

              <tr v-else v-for="r in filteredRanking" :key="r.event_participant_id">
                <td class="text-center font-weight-bold">{{ r.rank }}</td>
                <td>
                  <div class="font-weight-bold">{{ r.full_name }}</div>
                  <div class="text-muted text-xs">
                    {{ r.branch || '-' }} • {{ r.group || '-' }} • {{ r.category || '-' }}
                  </div>
                </td>
                <td>{{ r.contingent || '-' }}</td>
                <td class="text-center">{{ r.judge_count }}</td>
                <td class="text-center font-weight-bold">{{ fmt(r.final_score) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer text-muted text-sm">
          Total peserta: <strong>{{ ranking.length }}</strong>
        </div>
      </div>
    </div>
  </section>
</template>
