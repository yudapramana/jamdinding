<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header standings-header">
    <div class="container-fluid">
      <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div class="mr-3">
          <h1 class="standings-title">Klasemen Perolehan Juara Kontingen</h1>
          <p class="standings-subtitle mb-0">
            Rekap perolehan juara dan total poin setiap kontingen pada event aktif.
          </p>
        </div>

        <div class="btn-group btn-group-sm standings-actions" role="group">
          <button
            class="btn btn-outline-success"
            :disabled="!eventId || isExporting || isBuilding"
            title="Export ke Excel"
            @click="exportExcel"
          >
            <i class="fas fa-file-excel mr-1"></i>Excel
          </button>

          <button
            class="btn btn-outline-danger"
            :disabled="!eventId || isExporting || isBuilding"
            title="Export ke PDF"
            @click="exportPdf"
          >
            <i class="fas fa-file-pdf mr-1"></i>PDF
          </button>

          <button
            class="btn btn-primary"
            :disabled="!eventId || isLoading || isExporting || isBuilding"
            @click="buildMedalStanding"
          >
            <i
              class="fas mr-1"
              :class="isBuilding ? 'fa-spinner fa-spin' : 'fa-calculator'"
            ></i>
            {{ isBuilding ? 'Menghitung...' : 'Hitung Klasemen' }}
          </button>
        </div>
      </div>

      <p v-if="!eventId" class="text-danger text-sm mt-1 mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Event belum dipilih. Silakan pilih event melalui Portal Event.
      </p>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card standings-card">
        <!-- EVENT INFO -->
        <div class="card-header standings-card-header d-flex justify-content-between align-items-center">
          <div class="font-weight-bold">
            {{ eventData?.event_name || 'Event belum dipilih' }}
            <span v-if="eventData?.event_year" class="text-muted font-weight-normal">
              ({{ eventData.event_year }})
            </span>
          </div>
          <span v-if="items.length" class="text-muted text-sm">
            {{ items.length }} kontingen
          </span>
        </div>

        <div class="card-body table-responsive p-0 standings-scroll">
          <table class="table table-sm table-hover mb-0 standings-table">
            <thead>
              <tr>
                <th class="col-rank text-center">#</th>
                <th>Kontingen</th>
                <th
                  v-for="col in medalColumns"
                  :key="col.key"
                  class="col-medal text-center"
                  :title="col.title"
                >
                  <span class="medal-dot" :class="col.cls"></span>{{ col.label }}
                </th>
                <th class="col-total text-center">Poin</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td :colspan="colCount" class="text-center text-muted py-3">
                  <i class="fas fa-spinner fa-spin mr-1"></i>
                  Memuat klasemen kontingen...
                </td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td :colspan="colCount" class="text-center text-muted py-3">
                  Belum ada data perolehan juara untuk event ini.
                  Klik <strong>Hitung Klasemen</strong> untuk membuatnya.
                </td>
              </tr>

              <template v-else>
                <tr
                  v-for="(item, index) in items"
                  :key="item.region_name"
                  :class="{ 'is-top': index < 3 }"
                >
                  <td class="text-center">
                    <span class="rank-chip" :class="rankClass(index)">{{ index + 1 }}</span>
                  </td>

                  <td class="font-weight-bold">{{ item.region_name }}</td>

                  <td
                    v-for="col in medalColumns"
                    :key="col.key"
                    class="text-center"
                    :class="{ 'is-zero': !Number(item[col.key]) }"
                  >
                    {{ Number(item[col.key]) || '–' }}
                  </td>

                  <td class="text-center total-cell">{{ Number(item.total_point) }}</td>
                </tr>
              </template>
            </tbody>

            <tfoot v-if="!isLoading && items.length">
              <tr>
                <td></td>
                <td>Jumlah</td>
                <td v-for="col in medalColumns" :key="col.key" class="text-center">
                  {{ totals[col.key] }}
                </td>
                <td class="text-center">{{ totals.total_point }}</td>
              </tr>
            </tfoot>
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
import { useAuthUserStore } from '../../stores/AuthUserStore'

/* ======================
 |  STORE & EVENT
 ====================== */
const authUserStore = useAuthUserStore()
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

/* ======================
 |  STATE
 ====================== */
const items = ref([])
const isLoading = ref(false)
const isExporting = ref(false)
const isBuilding = ref(false)

/* ======================
 |  KOLOM MEDALI
 ====================== */
const medalColumns = [
  { key: 'juara_1', label: 'I', title: 'Juara I', cls: 'gold' },
  { key: 'juara_2', label: 'II', title: 'Juara II', cls: 'silver' },
  { key: 'juara_3', label: 'III', title: 'Juara III', cls: 'bronze' },
  { key: 'harapan_1', label: 'H I', title: 'Harapan I', cls: 'hope' },
  { key: 'harapan_2', label: 'H II', title: 'Harapan II', cls: 'hope' },
  { key: 'harapan_3', label: 'H III', title: 'Harapan III', cls: 'hope' },
]

const colCount = medalColumns.length + 3

const totals = computed(() => {
  const sum = (key) =>
    items.value.reduce((acc, row) => acc + (Number(row[key]) || 0), 0)

  const result = { total_point: sum('total_point') }
  medalColumns.forEach((col) => {
    result[col.key] = sum(col.key)
  })
  return result
})

const rankClass = (index) =>
  ['rank-1', 'rank-2', 'rank-3'][index] || ''

/* ======================
 |  FETCH STANDINGS
 ====================== */
const fetchStandings = async () => {
  if (!eventId.value) return

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-contingent-standings', {
      params: { event_id: eventId.value },
    })

    items.value = res.data.data || []
  } catch {
    Swal.fire('Gagal', 'Gagal memuat klasemen kontingen.', 'error')
  } finally {
    isLoading.value = false
  }
}

/* ======================
 |  BUILD MEDAL STANDING
 ====================== */
const buildMedalStanding = async () => {
  if (!eventId.value || isBuilding.value) return

  const confirm = await Swal.fire({
    title: 'Hitung Klasemen?',
    text: 'Proses ini mungkin membutuhkan waktu beberapa saat.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hitung',
    cancelButtonText: 'Batal',
  })

  if (!confirm.isConfirmed) return

  isBuilding.value = true

  try {
    await axios.post('/api/v1/event-contingent-standings/build', {
      event_id: eventId.value,
    })

    await fetchStandings()

    Swal.fire(
      'Selesai',
      'Perhitungan klasemen berhasil diselesaikan.',
      'success'
    )
  } catch {
    Swal.fire(
      'Gagal',
      'Terjadi kesalahan saat menghitung klasemen.',
      'error'
    )
  } finally {
    isBuilding.value = false
  }
}

/* ======================
 |  EXPORT
 ====================== */
const exportExcel = () => {
  if (!eventId.value) return
  window.open(
    `/api/v1/event-contingent-standings/export/excel?event_id=${eventId.value}`,
    '_blank'
  )
}

const exportPdf = () => {
  if (!eventId.value) return
  window.open(
    `/api/v1/event-contingent-standings/export/pdf?event_id=${eventId.value}`,
    '_blank'
  )
}

/* ======================
 |  WATCH & LIFECYCLE
 ====================== */
watch(eventId, (val) => {
  if (val) fetchStandings()
})

onMounted(() => {
  if (eventId.value) fetchStandings()
})
</script>

<style scoped>
/* ---------- header ---------- */
.standings-header {
  padding-top: 0.6rem;
  padding-bottom: 0.5rem;
}

.standings-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 600;
  line-height: 1.3;
}

.standings-subtitle {
  font-size: 0.8rem;
  color: #6c757d;
}

.standings-actions .btn {
  padding-top: 0.2rem;
  padding-bottom: 0.2rem;
  font-size: 0.8rem;
}

/* ---------- card ---------- */
.standings-card {
  margin-bottom: 0;
}

.standings-card-header {
  padding: 0.4rem 0.75rem;
  font-size: 0.9rem;
  background: #f8f9fa;
}

.standings-scroll {
  max-height: calc(100vh - 190px);
  overflow-y: auto;
}

/* ---------- table ---------- */
.standings-table {
  font-size: 0.85rem;
}

.standings-table th,
.standings-table td {
  padding: 0.25rem 0.5rem;
  vertical-align: middle;
  border-color: #e9ecef;
}

.standings-table thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: #f1f3f5;
  border-top: 0;
  border-bottom: 1px solid #dee2e6;
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
}

.col-rank {
  width: 44px;
}

.col-medal {
  width: 58px;
}

.col-total {
  width: 64px;
}

.is-zero {
  color: #ced4da;
}

.total-cell {
  font-weight: 700;
  background: #f8fafc;
}

.standings-table tfoot td {
  position: sticky;
  bottom: 0;
  background: #f1f3f5;
  border-top: 1px solid #dee2e6;
  font-weight: 600;
}

/* ---------- medal markers ---------- */
.medal-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  margin-right: 4px;
  border-radius: 50%;
  background: #adb5bd;
}

.medal-dot.gold { background: #e0a800; }
.medal-dot.silver { background: #9aa5b1; }
.medal-dot.bronze { background: #b87333; }
.medal-dot.hope { background: #17a2b8; }

/* ---------- rank ---------- */
.rank-chip {
  display: inline-block;
  min-width: 22px;
  padding: 0 0.3rem;
  border-radius: 10px;
  font-size: 0.78rem;
  font-weight: 600;
  line-height: 20px;
}

.rank-chip.rank-1 { background: #fff3cd; color: #7a5b00; }
.rank-chip.rank-2 { background: #e9ecef; color: #495057; }
.rank-chip.rank-3 { background: #f6e3d3; color: #7a4a22; }

.text-sm {
  font-size: 0.85rem;
}
</style>