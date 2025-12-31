<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Cetak Kokarde Peserta MTQ</h1>
          <p class="mb-0 text-muted text-sm">
            Generate dan unduh kokarde peserta MTQ per kontingen dalam format PDF (A6).
          </p>

          <!-- INFO EVENT AKTIF -->
          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year">
              ({{ eventData.event_year }})
            </span>
            • Lokasi:
            <strong>{{ eventData?.event_location || '-' }}</strong>
          </p>
        </div>

        <!-- ACTION -->
        <div>
          <button
            class="btn btn-outline-danger btn-sm"
            @click="exportPdf"
            :disabled="!eventId || !regionId || isExporting"
          >
            <i class="fas fa-file-pdf mr-1"></i>
            Export PDF
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

        <div class="card-body">
          <div class="row">

            <!-- PILIH KONTINGEN -->
            <div class="col-md-6">
              <div class="form-group">
                <label>
                  Kontingen
                  <span class="text-danger">*</span>
                </label>

                <select
                  v-model="regionId"
                  class="form-control"
                  :disabled="isLoading"
                >
                  <option value="">-- Pilih Kontingen --</option>
                  <option
                    v-for="r in regions"
                    :key="r.id"
                    :value="r.id"
                  >
                    {{ r.name }}
                  </option>
                </select>
              </div>
            </div>

          </div>

          <!-- INFO -->
          <div class="alert alert-info text-sm mb-0">
            <i class="fas fa-info-circle mr-1"></i>
            File PDF akan berisi <strong>seluruh peserta</strong> dari kontingen terpilih,
            dengan <strong>1 peserta = 1 halaman A6</strong>.
          </div>

        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../stores/AuthUserStore'

/* ======================
 |  AUTH & EVENT
 ====================== */
const authUserStore = useAuthUserStore()

const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

/* ======================
 |  STATE
 ====================== */
const regions = ref([])
const regionId = ref('')
const isLoading = ref(false)
const isExporting = ref(false)

/* ======================
 |  FETCH KONTINGEN
 ====================== */
const fetchRegions = async () => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/master', {
      params: {
        type: 'event_regions',
        event_id: eventId.value,
      },
    })
    regions.value = res.data.data || []
  } catch (error) {
    console.error(error)
    Swal.fire('Gagal', 'Gagal memuat data kontingen.', 'error')
  } finally {
    isLoading.value = false
  }
}


/* ======================
 |  EXPORT PDF
 ====================== */
const exportPdf = async () => {
  if (!eventId.value || !regionId.value) {
    Swal.fire(
      'Data belum lengkap',
      'Silakan pilih event dan kontingen terlebih dahulu.',
      'warning'
    )
    return
  }

  isExporting.value = true

  try {
    const url =
      `/api/v1/event-kokarde/export/pdf` +
      `?event_id=${eventId.value}&region_id=${regionId.value}`

    window.open(url, '_blank')
  } catch (error) {
    console.error(error)
    Swal.fire('Gagal', 'Gagal mengunduh PDF kokarde.', 'error')
  } finally {
    isExporting.value = false
  }
}

/* ======================
 |  INIT
 ====================== */
onMounted(() => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event terlebih dahulu.',
      'info'
    )
  } else {
    fetchRegions()
  }
})
</script>

<style scoped>
.alert {
  font-size: 0.9rem;
}
</style>
