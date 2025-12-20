<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

const loading = ref(false)
const rows = ref([])

// ui state
const search = ref('')
const sortKey = ref('total_point')
const sortDir = ref('desc') // 'asc'|'desc'

// ===== helpers =====
const toInt = (v) => Number(v || 0)

const badgeClass = (v) => {
  const n = toInt(v)
  if (n >= 10) return 'badge-success'
  if (n >= 5) return 'badge-primary'
  if (n >= 1) return 'badge-info'
  return 'badge-light border'
}

const sortIcon = (key) => {
  if (sortKey.value !== key) return 'fas fa-sort text-muted'
  return sortDir.value === 'asc'
    ? 'fas fa-sort-up'
    : 'fas fa-sort-down'
}

// ===== data fetch (server mode always on, no pagination) =====
const fetchData = async () => {
  if (!eventId.value) {
    rows.value = []
    return
  }

  loading.value = true
  try {
    const params = {
      search: search.value || '',
      // per_page besar supaya "1 halaman"
      per_page: 1000,
      sort: sortKey.value,
      dir: sortDir.value,
    }

    const { data } = await axios.get(`/api/v1/events/${eventId.value}/contingents`, { params })

    // paginate atau array
    const list = Array.isArray(data) ? data : (data.data || [])
    rows.value = list
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat perolehan juara kontingen.', 'error')
  } finally {
    loading.value = false
  }
}

// debounce search agar nyaman
const debouncedFetch = useDebounceFn(() => fetchData(), 350)

watch(search, () => debouncedFetch())

const onSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'desc'
  }
  fetchData()
}

watch(
  () => eventId.value,
  async (val) => {
    if (!val) return
    await fetchData()
  }
)

onMounted(async () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
    return
  }
  await fetchData()
})

// ===== stats =====
const totalContingent = computed(() => (rows.value || []).length)
const totalParticipant = computed(() =>
  (rows.value || []).reduce((sum, r) => sum + toInt(r.total_participant), 0)
)
</script>

<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-start flex-wrap">
        <div>
          <h1 class="mb-1">Perolehan Juara Kontingen</h1>
          <p class="mb-0 text-muted text-sm">
            Rekap Juara I / II / III / Harapan I berdasarkan kontingen pada event aktif.
          </p>

          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
          </p>

          <div class="mt-2 d-flex flex-wrap" style="gap:8px;">
            <span class="badge badge-light border">
              <i class="fas fa-flag mr-1"></i> Kontingen: <strong>{{ totalContingent }}</strong>
            </span>
            <span class="badge badge-light border">
              <i class="fas fa-users mr-1"></i> Total peserta: <strong>{{ totalParticipant }}</strong>
            </span>
          </div>
        </div>

        <div class="d-flex align-items-center mt-2 mt-md-0" style="gap:8px;">
          <div class="input-group input-group-sm" style="min-width:320px;">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input
              v-model="search"
              type="text"
              class="form-control"
              placeholder="Cari kontingen/kecamatan..."
            />
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" :disabled="loading" @click="fetchData">
                <i class="fas fa-sync"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <p v-if="!eventId" class="text-danger text-sm mt-2 mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Event belum dipilih. Silakan pilih event melalui Portal Event terlebih dahulu.
      </p>
    </div>
  </section>

  <section class="content" v-if="eventId">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover table-sm text-sm mb-0">
            <thead class="thead-light">
              <!-- header baris 1: group seperti gambar -->
              <tr class="sticky-head">
                <th rowspan="2" style="width:60px" class="text-center align-middle">No</th>
                <th rowspan="2" class="align-middle">
                  <span class="d-inline-flex align-items-center" style="gap:6px;">
                    Kontingen / Kecamatan
                    <a href="#" @click.prevent="onSort('contingent')" class="text-muted">
                      <i :class="sortIcon('contingent')"></i>
                    </a>
                  </span>
                </th>
                <th colspan="4" class="text-center align-middle">
                  Perolehan Juara per Cabang / Golongan
                </th>
                <th rowspan="2" class="text-center align-middle" style="width:120px;">
                  <span class="d-inline-flex align-items-center justify-content-center w-100" style="gap:6px;">
                    Total Poin
                    <a href="#" @click.prevent="onSort('total_point')" class="text-muted">
                      <i :class="sortIcon('total_point')"></i>
                    </a>
                  </span>
                </th>
              </tr>

              <!-- header baris 2 -->
              <tr class="sticky-head">
                <th class="text-center" style="width:105px;">
                  <span class="d-inline-flex align-items-center" style="gap:6px;">
                    Juara I
                    <a href="#" @click.prevent="onSort('gold_count')" class="text-muted">
                      <i :class="sortIcon('gold_count')"></i>
                    </a>
                  </span>
                </th>
                <th class="text-center" style="width:105px;">
                  <span class="d-inline-flex align-items-center" style="gap:6px;">
                    Juara II
                    <a href="#" @click.prevent="onSort('silver_count')" class="text-muted">
                      <i :class="sortIcon('silver_count')"></i>
                    </a>
                  </span>
                </th>
                <th class="text-center" style="width:105px;">
                  <span class="d-inline-flex align-items-center" style="gap:6px;">
                    Juara III
                    <a href="#" @click.prevent="onSort('bronze_count')" class="text-muted">
                      <i :class="sortIcon('bronze_count')"></i>
                    </a>
                  </span>
                </th>
                <th class="text-center" style="width:115px;">
                  <span class="d-inline-flex align-items-center" style="gap:6px;">
                    Harapan I
                    <a href="#" @click.prevent="onSort('fourth_count')" class="text-muted">
                      <i :class="sortIcon('fourth_count')"></i>
                    </a>
                  </span>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center p-4 text-muted">
                  <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data...
                </td>
              </tr>

              <tr v-else-if="rows.length === 0">
                <td colspan="7" class="text-center p-4 text-muted">
                  Tidak ada data.
                </td>
              </tr>

              <tr v-else v-for="(r, idx) in rows" :key="r.id">
                <td class="text-center font-weight-bold">{{ idx + 1 }}</td>

                <td>
                  <div class="font-weight-bold text-truncate" style="max-width:520px;">
                    {{ r.contingent }}
                  </div>
                  <div class="text-muted text-xs">
                    Peserta: <strong>{{ r.total_participant ?? 0 }}</strong>
                  </div>
                </td>

                <td class="text-center">
                  <span class="badge badge-pill" :class="badgeClass(r.gold_count)">
                    {{ r.gold_count ?? 0 }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge badge-pill" :class="badgeClass(r.silver_count)">
                    {{ r.silver_count ?? 0 }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge badge-pill" :class="badgeClass(r.bronze_count)">
                    {{ r.bronze_count ?? 0 }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge badge-pill" :class="badgeClass(r.fourth_count)">
                    {{ r.fourth_count ?? 0 }}
                  </span>
                </td>

                <td class="text-center font-weight-bold">
                  <span class="badge badge-warning badge-pill" style="font-size:12px;">
                    {{ r.total_point ?? 0 }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer py-2 text-muted text-sm d-flex justify-content-between flex-wrap">
          <span>
            Sorting:
            <strong>{{ sortKey }}</strong> ({{ sortDir }})
          </span>
          <span v-if="search">
            Filter:
            <strong>"{{ search }}"</strong>
          </span>
        </div>
      </div>
    </div>
  </section>

  
</template>
<!-- tiny css scoped -->
  <style scoped>
  .table-sm td, .table-sm th { padding: .45rem .55rem; }
  .sticky-head th {
    position: sticky;
    top: 0;
    z-index: 2;
  }
  thead tr.sticky-head:nth-child(2) th {
    top: 38px; /* tinggi kira-kira header row 1 */
    z-index: 3;
  }
  </style>