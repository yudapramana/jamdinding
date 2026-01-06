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
            :disabled="!canExport"
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

            <!-- PILIH JENIS KOKARDE -->
            <div class="col-md-6">
              <div class="form-group">
                <label>Jenis Kokarde <span class="text-danger">*</span></label>
                <select v-model="printType" class="form-control">
                  <option value="participant">Peserta</option>
                  <option value="role">Panitia / Role</option>
                </select>
              </div>
            </div>


            <!-- PILIH KONTINGEN -->
            <div v-if="printType === 'participant'" class="col-md-6">
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

            <!-- PILIH ROLE PANITIA -->
            <div v-if="printType === 'role'" class="col-md-6">
              <div class="form-group">
                <label>
                  Role Panitia
                  <span class="text-danger">*</span>
                </label>

                <select
                  v-model="roleId"
                  class="form-control"
                  :disabled="roleOptions.length === 0"
                >
                  <option value="">-- Pilih Role --</option>

                  <option
                    v-for="role in roleOptions"
                    :key="role.id"
                    :value="role.id"
                  >
                    {{ role.label ?? role.name }}
                  </option>
                </select>

                <small v-if="roleOptions.length === 0" class="text-muted">
                  Role panitia belum tersedia
                </small>
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
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../stores/AuthUserStore'


const canExport = computed(() => {
  if (!eventId.value || isExporting.value) {
    return false
  }

  if (printType.value === 'participant') {
    return !!regionId.value
  }

  if (printType.value === 'role') {
    return !!roleId.value
  }

  return false
})



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

const printType   = ref('participant')
const roleId      = ref('')
const roleOptions = ref([])


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

const fetchRoles = async () => {
  try {
    const res = await axios.get('/api/v1/roles')
    roleOptions.value = res.data || []
  } catch (error) {
    console.error('Gagal memuat roles:', error)
  }
}



/* ======================
 |  EXPORT PDF
 ====================== */
const exportPdf = () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', '', 'warning')
    return
  }

  if (printType.value === 'participant' && !regionId.value) {
    Swal.fire('Kontingen belum dipilih', '', 'warning')
    return
  }

  if (printType.value === 'role' && !roleId.value) {
    Swal.fire('Role belum dipilih', '', 'warning')
    return
  }


  let url =
    `/api/v1/event-kokarde/export/pdf` +
    `?event_id=${eventId.value}&type=${printType.value}`

  if (printType.value === 'participant') {
    url += `&region_id=${regionId.value}`
  }

  if (printType.value === 'role') {
    url += `&role_id=${roleId.value}`
  }

  window.open(url, '_blank')
}

watch(printType, (val) => {
  if (val === 'participant') {
    roleId.value = ''
  } else {
    regionId.value = ''
  }
})

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
    fetchRoles()
  }
})
</script>

<style scoped>
.alert {
  font-size: 0.9rem;
}
</style>
