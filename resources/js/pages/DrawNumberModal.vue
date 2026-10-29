<template>
  <!-- Menambahkan class kiosk-modal agar tampilan lebih fokus seperti di MaqraDrawing -->
  <div class="modal fade kiosk-modal" id="drawNumberModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content text-center">

        <!-- HEADER -->
        <div class="modal-header bg-dark text-white border-0 py-4 d-block">
          <h3 class="mb-1">UNDIAN NOMOR TAMPIL</h3>
          <button class="close text-white" style="position: absolute; right: 15px; top: 15px;" data-dismiss="modal" :disabled="isSubmitting">
            <span>&times;</span>
          </button>
        </div>

        <!-- BODY -->
        <div class="modal-body py-5 bg-light" v-if="ep">

          <!-- NAMA -->
          <p class="mb-4 text-warning font-weight-bold" style="font-size: 1.2rem; color: #d39e00 !important;">
            <template v-if="isTeam">
              Tim: {{ ep.display_name }} ({{ ep.members_count }} Anggota)
            </template>
            <template v-else>
              {{ ep.participant?.full_name }}
            </template>
          </p>

          <!-- Loading Indicator -->
          <div v-if="isLoading || isSubmitting" class="py-5">
             <i class="fas fa-circle-notch fa-spin fa-3x text-primary mb-3"></i>
             <h5>{{ isSubmitting ? 'Menyimpan Nomor Undian...' : 'Menyiapkan Amplop Undian...' }}</h5>
          </div>

          <div v-else class="row justify-content-center">
            
            <!-- PANEL AMPLOP -->
            <div class="col-md-8 mx-2">
              <div class="card border-primary shadow" style="min-height: 250px;">
                <div class="card-header bg-primary text-white font-weight-bold">AMPLOP NOMOR TAMPIL</div>
                <div class="card-body">

                  <!-- KONDISI: BELUM MEMILIH -->
                  <div v-if="!currentNumber" class="d-flex flex-wrap justify-content-center gap-2">
                    <!-- Grid Amplop tertutup -->
                    <button
                      v-for="(num, idx) in availableNumbers" :key="idx"
                      class="btn btn-outline-primary m-1 px-4 py-3"
                      @click="selectEnvelope(num)"
                      :disabled="isSubmitting"
                    >
                      <i class="fas fa-envelope fa-2x"></i>
                    </button>
                  </div>

                  <!-- KONDISI: AMPLOP SUDAH DIPILIH -->
                  <div v-else class="h-100 d-flex flex-column justify-content-center align-items-center bg-success text-white rounded p-4 mx-auto" style="max-width: 300px;">
                    <i class="fas fa-envelope-open-text fa-3x mb-3"></i>
                    <h5 class="font-weight-bold mb-1">Nomor Anda:</h5>
                    <h1 class="display-3 font-weight-bold mb-0">{{ currentNumber }}</h1>
                  </div>

                </div>
              </div>
            </div>

          </div>

          <!-- PESAN JIKA NOMOR HABIS -->
          <div v-if="!isLoading && availableNumbers.length === 0 && !currentNumber" class="alert alert-danger mt-4 text-left text-sm">
            <i class="fas fa-exclamation-triangle mr-2"></i> Nomor undian tampil kosong, sudah habis, atau gagal dimuat.
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer justify-content-center bg-dark border-0 py-3">
          <button class="btn btn-light btn-sm px-4" data-dismiss="modal" :disabled="isSubmitting">
            Tutup
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({
  eventParticipant: Object, // 🔑 item dari index()
})

const emit = defineEmits(['assigned'])

const ep = computed(() => props.eventParticipant || null)

/**
 * 🔑 PENENTUAN JENIS DAN ID
 */
const isTeam = computed(() => ep.value?.unit_type === 'team')
const baseId = computed(() => ep.value?.id || null)

// State Logic
const availableNumbers = ref([])
const currentNumber = ref(null)
const isLoading = ref(false)
const isSubmitting = ref(false)

/**
 * FISHER-YATES SHUFFLE
 * Digunakan agar posisi amplop acak dan tidak berurutan 1, 2, 3...
 */
const shuffleArray = (array) => {
  let currentIndex = array.length, randomIndex;
  while (currentIndex !== 0) {
    randomIndex = Math.floor(Math.random() * currentIndex);
    currentIndex--;
    [array[currentIndex], array[randomIndex]] = [array[randomIndex], array[currentIndex]];
  }
  return array;
}

/**
 * ▶️ FETCH NOMOR (OTOMATIS SAAT MODAL DIBUKA)
 */
const loadAvailableNumbers = async () => {
  if (!baseId.value) return

  isLoading.value = true
  try {
    const url = isTeam.value
      ? `/api/v1/event-teams/${baseId.value}/draw-number`
      : `/api/v1/event-participants/${baseId.value}/draw-number`

    const { data } = await axios.post(url)

    if (!data?.numbers || data.numbers.length === 0) {
      throw new Error('Nomor undian kosong')
    }

    // Acak nomor yang dikembalikan dari backend agar posisi amplop rahasia
    availableNumbers.value = shuffleArray([...data.numbers])

  } catch (e) {
    Swal.fire(
      'Gagal',
      e.response?.data?.message || 'Gagal memuat bank nomor undian',
      'error'
    )
  } finally {
    isLoading.value = false
  }
}

/**
 * ✉️ PILIH AMPLOP
 */
const selectEnvelope = (num) => {
  if (currentNumber.value || isSubmitting.value) return
  
  // Tampilkan angka terbuka
  currentNumber.value = num

  // Auto-Save: Berikan jeda 800ms agar peserta bisa melihat amplop terbuka lalu simpan
  setTimeout(() => {
    assignNumber(num)
  }, 800)
}

/**
 * ⏹️ SIMPAN / ASSIGN NUMBER KE BACKEND
 */
const assignNumber = async (num) => {
  isSubmitting.value = true

  try {
    const url = isTeam.value
      ? `/api/v1/event-teams/${baseId.value}/assign-number`
      : `/api/v1/event-participants/${baseId.value}/assign-number`

    const res = await axios.post(url, {
      branch_sequence: num,
    })

    Swal.fire({
      icon: 'success',
      title: 'Nomor Ditetapkan!',
      text: `Nomor tampil anda adalah: ${res.data?.participant_number || num}`,
      timer: 2000,
      showConfirmButton: false
    })

    emit('assigned')
    
    // Tutup modal perlahan setelah sukses
    setTimeout(() => {
       $('#drawNumberModal').modal('hide')
    }, 1000)

  } catch (e) {
    Swal.fire(
      'Gagal',
      e.response?.data?.message || 'Gagal menetapkan nomor',
      'error'
    )
    currentNumber.value = null // reset agar bisa pilih lagi jika gagal
  } finally {
    isSubmitting.value = false
  }
}

/**
 * 👀 WATCHER PROPS
 * Setiap kali tombol modal diklik untuk peserta baru, reset dan muat ulang amplop
 */
watch(() => props.eventParticipant, (newVal) => {
  currentNumber.value = null
  isSubmitting.value = false
  availableNumbers.value = []
  
  if (newVal) {
    loadAvailableNumbers()
  }
})
</script>

<style scoped>
.text-xs { font-size: 0.75rem; }
.gap-2 { gap: .5rem; }

/* Kiosk Modal Override (Adaptasi dari MaqraDrawing) */
.kiosk-modal .modal-content {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 0 40px rgba(0,0,0,0.5);
}
.kiosk-modal::before {
  content: '';
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.85);
  z-index: -1;
}
</style>