<template>
  <div class="modal fade" id="drawNumberModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header">
          <h5 class="modal-title">Undian Nomor Peserta</h5>
          <button class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>

        <!-- BODY -->
        <div class="modal-body text-center" v-if="ep">

          <!-- NAMA -->
          <div class="mb-2 text-muted">
            <template v-if="isTeam">
              Tim: <strong>{{ ep.display_name }}</strong>
              <div class="text-xs mt-1">
                Jumlah anggota: {{ ep.members_count }}
              </div>
            </template>

            <template v-else>
              {{ ep.participant?.full_name }}
            </template>
          </div>

          <!-- NOMOR -->
          <div class="display-2 font-weight-bold roulette" :class="numberClass">
            {{ currentNumber ?? '--' }}
          </div>

          <!-- ACTION -->
          <div class="mt-4">
            <button
              class="btn btn-success mr-2"
              @click="startDraw"
              :disabled="rolling"
            >
              Mulai
            </button>

            <button
              class="btn btn-danger"
              @click="stopDraw"
              :disabled="!rolling"
            >
              Stop
            </button>
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button class="btn btn-light" data-dismiss="modal">
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
 * 🔑 PENENTUAN JENIS
 */
const isTeam = computed(() => ep.value?.unit_type === 'team')

/**
 * 🔑 ID UNTUK API
 */
const baseId = computed(() => ep.value?.id || null)

const numbers = ref([])
const rolling = ref(false)
const currentNumber = ref(null)
let timer = null

const numberClass = computed(() =>
  currentNumber.value == null
    ? ''
    : currentNumber.value % 2 === 0
      ? 'even'
      : 'odd'
)

/**
 * ▶️ START DRAW
 */
const startDraw = async () => {
  if (!baseId.value) {
    Swal.fire('Error', 'Data peserta tidak valid', 'error')
    return
  }

  try {
    const url = isTeam.value
      ? `/api/v1/event-teams/${baseId.value}/draw-number`
      : `/api/v1/event-participants/${baseId.value}/draw-number`

    const { data } = await axios.post(url)

    if (!data?.numbers || data.numbers.length === 0) {
      throw new Error('Nomor undian kosong')
    }

    numbers.value = data.numbers
    rolling.value = true
    spin(40)

  } catch (e) {
    Swal.fire(
      'Gagal',
      e.response?.data?.message || 'Gagal memulai undian',
      'error'
    )
  }
}

const spin = (delay) => {
  if (!rolling.value) return
  const idx = Math.floor(Math.random() * numbers.value.length)
  currentNumber.value = numbers.value[idx]
  timer = setTimeout(() => spin(Math.min(delay + 10, 200)), delay)
}

/**
 * ⏹️ STOP & ASSIGN
 */
const stopDraw = async () => {
  rolling.value = false
  clearTimeout(timer)

  if (!currentNumber.value) {
    Swal.fire('Error', 'Nomor belum ditentukan', 'warning')
    return
  }

  try {
    const url = isTeam.value
      ? `/api/v1/event-teams/${baseId.value}/assign-number`
      : `/api/v1/event-participants/${baseId.value}/assign-number`

    const res = await axios.post(url, {
      branch_sequence: currentNumber.value,
    })

    Swal.fire(
      'Nomor Ditetapkan',
      res.data?.participant_number || currentNumber.value,
      'success'
    )

    emit('assigned')

  } catch (e) {
    Swal.fire(
      'Gagal',
      e.response?.data?.message || 'Gagal menetapkan nomor',
      'error'
    )
  }
}

watch(() => props.eventParticipant, () => {
  currentNumber.value = null
  rolling.value = false
  numbers.value = []
})
</script>

<style scoped>
.roulette {
  transition: transform 0.08s ease-out;
}
.even { color: #007bff; }
.odd  { color: #e83e8c; }
.text-xs { font-size: 0.75rem; }
</style>
