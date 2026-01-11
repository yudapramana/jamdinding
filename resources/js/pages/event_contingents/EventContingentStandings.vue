<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Klasemen Perolehan Juara Kontingen</h1>
          <p class="mb-0 text-muted text-sm">
            Rekap perolehan juara dan total poin setiap kontingen pada event aktif.
          </p>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="d-flex gap-2">
          <button
            class="btn btn-outline-success btn-sm mr-2"
            @click="exportExcel"
            :disabled="!eventId || isExporting || isBuilding"
          >
            <i class="fas fa-file-excel mr-1"></i>
            Export Excel
          </button>

          <button
            class="btn btn-outline-danger btn-sm mr-2"
            @click="exportPdf"
            :disabled="!eventId || isExporting || isBuilding"
          >
            <i class="fas fa-file-pdf mr-1"></i>
            Export PDF
          </button>

          <button
            class="btn btn-primary btn-sm"
            @click="buildMedalStanding"
            :disabled="!eventId || isLoading || isExporting || isBuilding"
          >
            <i
              class="fas mr-1"
              :class="isBuilding ? 'fa-spinner fa-spin' : 'fa-calculator'"
            ></i>
            {{ isBuilding ? 'Menghitung...' : 'Hitung Klasemen' }}
          </button>
        </div>
      </div>

      <p v-if="!eventId" class="text-danger text-sm mt-2 mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Event belum dipilih. Silakan pilih event melalui Portal Event.
      </p>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead>
              <!-- TITLE -->
              <tr class="bg-light">
                <th colspan="9" class="text-center py-2">
                  <div class="font-weight-bold text-uppercase">
                    Daftar Perolehan Juara per Kontingen
                  </div>
                  <div class="text-muted text-sm">
                    {{ eventData?.event_name }}
                    <span v-if="eventData?.event_year">
                      ({{ eventData.event_year }})
                    </span>
                  </div>
                </th>
              </tr>

              <!-- HEADER -->
              <tr class="thead-light">
                <th rowspan="2" style="width:40px" class="text-center align-middle">No</th>
                <th rowspan="2" class="align-middle">Kontingen</th>
                <th colspan="6" class="text-center">Perolehan Juara</th>
                <th rowspan="2" style="width:90px" class="text-center align-middle">
                  Total<br>Poin
                </th>
              </tr>

              <tr class="thead-light text-center">
                <th style="width:70px">Juara I</th>
                <th style="width:70px">Juara II</th>
                <th style="width:70px">Juara III</th>
                <th style="width:80px">Har. I</th>
                <th style="width:80px">Har. II</th>
                <th style="width:80px">Har. III</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="9" class="text-center text-muted py-3">
                  <i class="fas fa-spinner fa-spin mr-1"></i>
                  Memuat klasemen kontingen...
                </td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="9" class="text-center text-muted py-3">
                  Belum ada data perolehan juara untuk event ini.
                </td>
              </tr>

              <tr
                v-else
                v-for="(item, index) in items"
                :key="item.region_name"
              >
                <td class="text-center">{{ index + 1 }}</td>

                <td>
                  <strong>{{ item.region_name }}</strong>
                </td>

                <td class="text-center">
                  <span class="badge badge-warning">{{ Number(item.juara_1) }}</span>
                </td>
                <td class="text-center">
                  <span class="badge badge-secondary">{{ Number(item.juara_2) }}</span>
                </td>
                <td class="text-center">
                  <span class="badge badge-secondary">{{ Number(item.juara_3) }}</span>
                </td>

                <td class="text-center">
                  <span class="badge badge-light">{{ Number(item.harapan_1) }}</span>
                </td>
                <td class="text-center">
                  <span class="badge badge-light">{{ Number(item.harapan_2) }}</span>
                </td>
                <td class="text-center">
                  <span class="badge badge-light">{{ Number(item.harapan_3) }}</span>
                </td>

                <td class="text-center font-weight-bold">
                  {{ Number(item.total_point) }}
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
table th {
  vertical-align: middle !important;
}

table thead th {
  white-space: nowrap;
}

.table td {
  vertical-align: middle;
}

.badge {
  font-size: 0.85em;
  min-width: 28px;
  padding: 0.35em 0.55em;
}

.btn i.fa-spinner {
  font-size: 0.9em;
}

.text-sm {
  font-size: 0.875rem;
}

.gap-2 > * {
  margin-left: 0.25rem;
}
</style>
