<template>
  <div class="modal fade" id="reRegisterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header">
          <div>
            <h5 class="modal-title">Proses Daftar Ulang</h5>
            <div class="text-sm text-muted">
              Cocokkan wajah (photo_url) dengan KTP (id_card_url) dan validasi data peserta.
            </div>
          </div>
          <button type="button" class="close" data-dismiss="modal" :disabled="isSubmitting">
            <span>&times;</span>
          </button>
        </div>

        <!-- BODY -->
        <div class="modal-body" v-if="ep && p">

          <!-- RINGKASAN -->
          <div class="card mb-3">
            <div class="card-body py-3">
              <div class="d-flex flex-wrap justify-content-between">
                <div class="mb-2">
                  <div class="h6 mb-1">{{ p.full_name }}</div>
                  <div class="text-sm text-muted">
                    NIK: <strong>{{ p.nik }}</strong>
                    <span class="mx-2">•</span>
                    JK: <strong>{{ p.gender }}</strong>
                    <span class="mx-2">•</span>
                    HP: <strong>{{ p.phone_number || '-' }}</strong>
                  </div>
                  <div class="text-sm text-muted">
                    TTL: <strong>{{ p.place_of_birth }}</strong>, <strong>{{ p.date_of_birth }}</strong>
                  </div>
                  <div class="text-sm text-muted" v-if="addressLine">
                    Alamat: {{ addressLine }}
                  </div>
                </div>

                <div class="text-sm">
                  <div class="mb-1">
                    Kontingen:
                    <span class="badge badge-light border">{{ ep.contingent || '-' }}</span>
                  </div>
                  <div class="mb-1">
                    Cabang/Golongan:
                    <span class="badge badge-info">{{ ep.event_category?.full_name || '-' }}</span>
                  </div>
                  <div>
                    Status sekarang:
                    <span class="badge" :class="badgeClass(ep.reregistration_status)">
                      {{ ep.reregistration_status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- FOTO -->
          <div class="row">
            <!-- FOTO WAJAH -->
            <div class="col-md-6 mb-3">
              <div class="card h-100">
                <div class="card-header py-2">
                  <strong>Foto Wajah</strong>
                  <small class="text-muted">(photo_url)</small>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                  <div class="photo-frame">
                    <img
                      v-if="selfieUrl"
                      :src="selfieUrl"
                      alt="Foto Wajah"
                      loading="lazy"
                    />
                    <div v-else class="text-muted text-sm">
                      Tidak ada foto wajah
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- FOTO KTP -->
            <div class="col-md-6 mb-3">
              <div class="card h-100">
                <div class="card-header py-2">
                  <strong>Foto KTP</strong>
                  <small class="text-muted">(id_card_url)</small>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                  <div class="photo-frame">

                    <!-- PDF -->
                    <div v-if="ktpUrl && ktpIsPdf" class="w-100 h-100 d-flex flex-column">
                      <iframe
                        class="pdf-frame flex-grow-1"
                        :src="ktpUrl"
                        title="KTP PDF"
                      />
                      <div class="pt-2 text-center">
                        <a class="btn btn-outline-primary btn-sm" :href="ktpUrl" target="_blank">
                          <i class="far fa-file-pdf mr-1"></i> Buka PDF
                        </a>
                      </div>
                    </div>

                    <!-- IMAGE -->
                    <img
                      v-else-if="ktpUrl && ktpIsImage"
                      :src="ktpUrl"
                      alt="Foto KTP"
                      loading="lazy"
                    />

                    <!-- FILE LAIN -->
                    <div v-else-if="ktpUrl" class="text-center">
                      <div class="text-muted text-sm mb-2">
                        Dokumen KTP tidak bisa dipreview
                      </div>
                      <a class="btn btn-outline-primary btn-sm" :href="ktpUrl" target="_blank">
                        <i class="far fa-file mr-1"></i> Buka Dokumen
                      </a>
                    </div>

                    <div v-else class="text-muted text-sm">
                      Tidak ada foto KTP
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- LAMPIRAN -->
          <div class="card mb-3">
            <div class="card-header py-2"><strong>Lampiran Lainnya</strong></div>
            <div class="card-body">
              <div class="d-flex flex-wrap">
                <a v-if="kkUrl" class="btn btn-outline-secondary btn-sm mr-2 mb-2" :href="kkUrl" target="_blank">KK</a>
                <a v-if="bankBookUrl" class="btn btn-outline-secondary btn-sm mr-2 mb-2" :href="bankBookUrl" target="_blank">Buku Tabungan</a>
                <a v-if="certificateUrl" class="btn btn-outline-secondary btn-sm mr-2 mb-2" :href="certificateUrl" target="_blank">Sertifikat</a>
                <a v-if="otherUrl" class="btn btn-outline-secondary btn-sm mr-2 mb-2" :href="otherUrl" target="_blank">Lainnya</a>

                <span
                  v-if="!kkUrl && !bankBookUrl && !certificateUrl && !otherUrl"
                  class="text-muted text-sm"
                >
                  Tidak ada lampiran tambahan
                </span>
              </div>
            </div>
          </div>

          <!-- CHECKLIST -->
          <div class="card mb-3">
            <div class="card-header py-2"><strong>Checklist Verifikasi</strong></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="custom-control custom-checkbox mb-2" v-for="(label, key) in checklistLeft" :key="key">
                    <input class="custom-control-input" type="checkbox" :id="key" v-model="checklist[key]" />
                    <label class="custom-control-label text-sm" :for="key">{{ label }}</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="custom-control custom-checkbox mb-2" v-for="(label, key) in checklistRight" :key="key">
                    <input class="custom-control-input" type="checkbox" :id="key" v-model="checklist[key]" />
                    <label class="custom-control-label text-sm" :for="key">{{ label }}</label>
                  </div>
                </div>
              </div>

              <div class="form-group mt-3 mb-0">
                <label class="text-sm mb-1">Catatan Verifikator</label>
                <textarea
                  v-model="note"
                  class="form-control"
                  rows="3"
                  placeholder="Catatan verifikasi"
                />
              </div>
            </div>
          </div>

          <!-- KEPUTUSAN -->
          <div class="form-group">
            <label class="text-sm">Keputusan</label>
            <select v-model="actionMode" class="form-control">
              <option value="">-- pilih keputusan --</option>
              <option value="verified">ACC / Terima</option>
              <option value="rejected">Tolak</option>
            </select>
          </div>

          <!-- DRAW NUMBER (HANYA ACC) -->
          <div v-if="actionMode === 'verified'" class="card mt-4">
            <div class="card-header py-2 text-center">
              <strong>Undian Nomor Peserta</strong>
            </div>
            <div class="card-body text-center">
              <div
                class="display-3 font-weight-bold roulette"
                :class="numberClass"
              >
                {{ currentNumber ?? '--' }}
              </div>

              <div class="mt-3">
                <button class="btn btn-success mr-2" @click="startDraw" :disabled="rolling">
                  Mulai
                </button>
                <button class="btn btn-danger" @click="stopDraw" :disabled="!rolling">
                  Stop
                </button>
              </div>
            </div>
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal" :disabled="isSubmitting">
            Tutup
          </button>
          <button type="button" class="btn btn-primary" :disabled="isSubmitting" @click="submit">
            Submit
          </button>
        </div>

      </div>
    </div>
  </div>
</template>


<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({ eventParticipant: Object })
const emit = defineEmits(['updated'])

const ep = computed(() => props.eventParticipant || null)
const p = computed(() => ep.value?.participant || null)

const isSubmitting = ref(false)
const note = ref('')
const actionMode = ref('')

const checklist = ref({
  face_match: false,
  nik_match: false,
  name_match: false,
  birth_match: false,
  contingent_match: false,
  category_match: false,
})

const checklistLeft = {
  face_match: 'Wajah sesuai KTP',
  nik_match: 'NIK sesuai',
  name_match: 'Nama sesuai',
}

const checklistRight = {
  birth_match: 'TTL sesuai',
  contingent_match: 'Kontingen sesuai',
  category_match: 'Cabang/Golongan sesuai',
}

const isChecklistOk = computed(() =>
  checklist.value.face_match &&
  checklist.value.nik_match &&
  checklist.value.name_match &&
  checklist.value.birth_match
)

// ===== URL SAFE =====
const safeUrl = (u) => (typeof u === 'string' && u.trim() !== '' ? u : null)

const selfieUrl = computed(() => safeUrl(p.value?.photo_url))
const ktpUrl = computed(() => safeUrl(p.value?.id_card_url))
const kkUrl = computed(() => safeUrl(p.value?.family_card_url))
const bankBookUrl = computed(() => safeUrl(p.value?.bank_book_url))
const certificateUrl = computed(() => safeUrl(p.value?.certificate_url))
const otherUrl = computed(() => safeUrl(p.value?.other_url))

const addressLine = computed(() => {
  if (!p.value) return ''
  return [
    p.value.address,
    p.value.village_name,
    p.value.district_name,
    p.value.regency_name,
    p.value.province_name,
  ].filter(Boolean).join(', ')
})

// ===== FILE TYPE =====
const isPdfUrl = (url) => String(url).split('?')[0].toLowerCase().endsWith('.pdf')
const isImageUrl = (url) =>
  ['.jpg', '.jpeg', '.png', '.webp', '.gif'].some(ext =>
    String(url).split('?')[0].toLowerCase().endsWith(ext)
  )

const ktpIsPdf = computed(() => ktpUrl.value && isPdfUrl(ktpUrl.value))
const ktpIsImage = computed(() => ktpUrl.value && isImageUrl(ktpUrl.value))

// ===== DRAW NUMBER =====
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

const startDraw = async () => {
  try {
    const { data } = await axios.post(
      `/api/v1/event-participants/${ep.value.id}/draw-number`
    )
    numbers.value = data.numbers
    rolling.value = true
    spin(40)
  } catch (e) {
    Swal.fire('Gagal', e.response?.data?.message || 'Gagal memulai undian', 'error')
  }
}

const spin = (delay) => {
  if (!rolling.value) return
  const idx = Math.floor(Math.random() * numbers.value.length)
  currentNumber.value = numbers.value[idx]
  timer = setTimeout(() => spin(Math.min(delay + 8, 200)), delay)
}

const stopDraw = async () => {
  rolling.value = false
  clearTimeout(timer)

  try {
    const res = await axios.post(
      `/api/v1/event-participants/${ep.value.id}/assign-number`,
      { branch_sequence: currentNumber.value }
    )
    Swal.fire('Nomor Ditetapkan', res.data.participant_number, 'success')
  } catch (e) {
    Swal.fire('Gagal', e.response?.data?.message || 'Gagal menetapkan nomor', 'error')
  }
}

// ===== SUBMIT =====
const submit = async () => {
  if (!actionMode.value) {
    Swal.fire('Pilih keputusan', 'Silakan pilih ACC atau Tolak.', 'warning')
    return
  }

  if (actionMode.value === 'verified') {
    if (!isChecklistOk.value) {
      Swal.fire('Checklist belum lengkap', 'Lengkapi checklist inti sebelum ACC.', 'warning')
      return
    }
    if (!currentNumber.value) {
      Swal.fire('Nomor belum ditentukan', 'Lakukan undian nomor terlebih dahulu.', 'warning')
      return
    }
  }

  isSubmitting.value = true

  try {
    await axios.post(
      `/api/v1/event-participants/${ep.value.id}/re-registration`,
      {
        reregistration_status: actionMode.value,
        reregistration_notes: note.value || null,
      }
    )

    Swal.fire('Berhasil', 'Daftar ulang berhasil diproses.', 'success')
    emit('updated')
    $('#reRegisterModal').modal('hide')

  } catch (error) {
    // 🔥 Ambil pesan error paling aman
    let message = 'Gagal memproses daftar ulang.'

    if (error.response) {
      // Laravel business rule / validation
      if (error.response.data?.message) {
        message = error.response.data.message
      } else if (error.response.data?.errors) {
        // gabungkan semua error validation
        message = Object.values(error.response.data.errors)
          .flat()
          .join('<br>')
      }
    } else if (error.message) {
      // Network / JS error
      message = error.message
    }

    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      html: message, // pakai html agar <br> terbaca
    })

  } finally {
    isSubmitting.value = false
  }
}


const badgeClass = (status) => {
  if (status === 'verified') return 'badge-success'
  if (status === 'rejected') return 'badge-danger'
  return 'badge-secondary'
}

watch(() => props.eventParticipant, () => {
  note.value = ''
  actionMode.value = ''
  currentNumber.value = null
  rolling.value = false
  checklist.value = {
      face_match: false,
      nik_match: false,
      name_match: false,
      birth_match: false,
      contingent_match: false,
      category_match: false,
    }
})
</script>


<style scoped>
.photo-frame {
  width: 100%;
  max-width: 520px;
  height: 340px;
  border: 1px solid #dee2e6;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 6px;
}

.photo-frame img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.pdf-frame {
  width: 100%;
  height: 100%;
  border: 0;
}

.roulette {
  transition: transform 0.08s ease-out;
}

.even { color: #007bff; }
.odd  { color: #e83e8c; }
</style>

