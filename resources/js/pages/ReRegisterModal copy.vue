<template>
  <div class="modal fade" id="reRegisterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">
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

        <div class="modal-body" v-if="ep && p">
          <!-- Ringkasan -->
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

          <!-- Foto: Wajah vs KTP -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="card h-100">
                <div class="card-header py-2"><strong>Foto Wajah</strong> <small class="text-muted">(photo_url)</small></div>
                <div class="card-body d-flex justify-content-center align-items-center">
                  <div class="photo-frame">
                    <img v-if="selfieUrl" :src="selfieUrl" alt="Foto Wajah" />
                    <div v-else class="text-muted text-sm">Tidak ada foto wajah</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="card h-100">
                <div class="card-header py-2"><strong>Foto KTP</strong> <small class="text-muted">(id_card_url)</small></div>
                <div class="card-body d-flex justify-content-center align-items-center">
                  <div class="photo-frame">
                    <!-- PDF -->
                    <div v-if="ktpUrl && ktpIsPdf" class="w-100 h-100 d-flex flex-column">
                        <iframe
                        class="pdf-frame flex-grow-1"
                        :src="ktpUrl"
                        title="KTP PDF"
                        ></iframe>

                        <div class="pt-2 text-center">
                        <a class="btn btn-outline-primary btn-sm" :href="ktpUrl" target="_blank" rel="noopener">
                            <i class="far fa-file-pdf mr-1"></i> Buka PDF
                        </a>
                        </div>
                    </div>

                    <!-- Image -->
                    <img v-else-if="ktpUrl && ktpIsImage" :src="ktpUrl" alt="Foto KTP" />

                    <!-- Unknown file type -->
                    <div v-else-if="ktpUrl" class="text-center">
                        <div class="text-muted text-sm mb-2">Dokumen KTP tidak bisa dipreview.</div>
                        <a class="btn btn-outline-primary btn-sm" :href="ktpUrl" target="_blank" rel="noopener">
                        <i class="far fa-file mr-1"></i> Buka Dokumen
                        </a>
                    </div>

                    <!-- Empty -->
                    <div v-else class="text-muted text-sm">Tidak ada foto KTP</div>
                    </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Dokumen lain -->
          <div class="card mb-3">
            <div class="card-header py-2"><strong>Lampiran Lainnya</strong></div>
            <div class="card-body">
              <div class="d-flex flex-wrap">
                <a v-if="kkUrl" class="btn btn-outline-secondary btn-sm mr-2 mb-2" :href="kkUrl" target="_blank">
                  <i class="far fa-file-alt mr-1"></i> KK
                </a>
                <a v-if="bankBookUrl" class="btn btn-outline-secondary btn-sm mr-2 mb-2" :href="bankBookUrl" target="_blank">
                  <i class="far fa-file-alt mr-1"></i> Buku Tabungan
                </a>
                <a v-if="certificateUrl" class="btn btn-outline-secondary btn-sm mr-2 mb-2" :href="certificateUrl" target="_blank">
                  <i class="far fa-file-alt mr-1"></i> Sertifikat
                </a>
                <a v-if="otherUrl" class="btn btn-outline-secondary btn-sm mr-2 mb-2" :href="otherUrl" target="_blank">
                  <i class="far fa-file-alt mr-1"></i> Lainnya
                </a>

                <span v-if="!kkUrl && !bankBookUrl && !certificateUrl && !otherUrl" class="text-muted text-sm">
                  Tidak ada lampiran tambahan.
                </span>
              </div>
            </div>
          </div>

          <!-- Checklist + Catatan -->
          <div class="card">
            <div class="card-header py-2"><strong>Checklist Verifikasi</strong></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="custom-control custom-checkbox mb-2">
                    <input class="custom-control-input" type="checkbox" id="c_face" v-model="checklist.face_match" />
                    <label class="custom-control-label text-sm" for="c_face">Wajah sesuai dengan foto KTP</label>
                  </div>
                  <div class="custom-control custom-checkbox mb-2">
                    <input class="custom-control-input" type="checkbox" id="c_nik" v-model="checklist.nik_match" />
                    <label class="custom-control-label text-sm" for="c_nik">NIK sesuai</label>
                  </div>
                  <div class="custom-control custom-checkbox mb-2">
                    <input class="custom-control-input" type="checkbox" id="c_name" v-model="checklist.name_match" />
                    <label class="custom-control-label text-sm" for="c_name">Nama sesuai</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="custom-control custom-checkbox mb-2">
                    <input class="custom-control-input" type="checkbox" id="c_birth" v-model="checklist.birth_match" />
                    <label class="custom-control-label text-sm" for="c_birth">TTL sesuai</label>
                  </div>
                  <div class="custom-control custom-checkbox mb-2">
                    <input class="custom-control-input" type="checkbox" id="c_cont" v-model="checklist.contingent_match" />
                    <label class="custom-control-label text-sm" for="c_cont">Kontingen sesuai</label>
                  </div>
                  <div class="custom-control custom-checkbox mb-2">
                    <input class="custom-control-input" type="checkbox" id="c_cat" v-model="checklist.category_match" />
                    <label class="custom-control-label text-sm" for="c_cat">Cabang/Golongan sesuai</label>
                  </div>
                </div>
              </div>

              <div class="form-group mt-3 mb-0">
                <label class="text-sm mb-1">
                  Catatan Verifikator
                  <span v-if="actionMode === 'rejected'" class="text-danger">*</span>
                </label>
                <textarea
                  v-model="note"
                  class="form-control"
                  rows="3"
                  placeholder="Contoh: wajah berbeda / NIK tidak sesuai / dokumen tidak valid / dsb."
                />
                <small class="text-muted">
                  Disimpan ke <code>event_participants.reregistration_notes</code>
                </small>
              </div>
            </div>
          </div>

          <div class="text-center my-3">
            <h1 class="display-3 font-weight-bold">
              {{ currentNumber ?? '--' }}
            </h1>
          </div>

          <div class="text-center">
            <button class="btn btn-success" @click="startDraw" :disabled="rolling">
              Mulai Undian
            </button>
            <button class="btn btn-danger ml-2" @click="stopDraw" :disabled="!rolling">
              Stop
            </button>
          </div>

        </div>

        <div class="modal-body" v-else>
          <div class="text-muted">Tidak ada data peserta.</div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal" :disabled="isSubmitting">Tutup</button>

          <button
            type="button"
            class="btn btn-danger"
            :disabled="isSubmitting || !ep"
            @click="submitRejected"
          >
            <i class="fas fa-times mr-1"></i> Tolak
          </button>

          <button
            type="button"
            class="btn btn-success"
            :disabled="isSubmitting || !ep || !isChecklistOk"
            @click="submitVerified"
          >
            <i class="fas fa-check mr-1"></i> ACC
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

const props = defineProps({
  eventParticipant: { type: Object, default: null },
})

const emit = defineEmits(['updated'])

const ep = computed(() => props.eventParticipant || null)
const p = computed(() => ep.value?.participant || null)

const isSubmitting = ref(false)
const note = ref('')
const actionMode = ref('') // 'verified' | 'rejected'

const checklist = ref({
  face_match: false,
  nik_match: false,
  name_match: false,
  birth_match: false,
  contingent_match: false,
  category_match: false,
})

const numbers = ref([])
const rolling = ref(false)
const currentNumber = ref(null)
const intervalId = ref(null)


const isChecklistOk = computed(() => {
  // minimal wajib untuk ACC
  return (
    checklist.value.face_match &&
    checklist.value.nik_match &&
    checklist.value.name_match &&
    checklist.value.birth_match
  )
})

const selfieUrl = computed(() => p.value?.photo_url || null)
const ktpUrl = computed(() => p.value?.id_card_url || null)
const kkUrl = computed(() => p.value?.family_card_url || null)
const bankBookUrl = computed(() => p.value?.bank_book_url || null)
const certificateUrl = computed(() => p.value?.certificate_url || null)
const otherUrl = computed(() => p.value?.other_url || null)

const addressLine = computed(() => {
  if (!p.value) return ''
  const parts = [
    p.value.address,
    p.value.village_name,
    p.value.district_name,
    p.value.regency_name,
    p.value.province_name,
  ].filter(Boolean)
  return parts.join(', ')
})

const isPdfUrl = (url) => {
  if (!url) return false
  // buang query string
  const clean = String(url).split('?')[0].toLowerCase()
  return clean.endsWith('.pdf')
}

const isImageUrl = (url) => {
  if (!url) return false
  const clean = String(url).split('?')[0].toLowerCase()
  return ['.jpg', '.jpeg', '.png', '.webp', '.gif'].some(ext => clean.endsWith(ext))
}

const ktpIsPdf = computed(() => isPdfUrl(ktpUrl.value))
const ktpIsImage = computed(() => isImageUrl(ktpUrl.value))


const badgeClass = (status) => {
  if (status === 'verified') return 'badge-success'
  if (status === 'rejected') return 'badge-danger'
  return 'badge-secondary'
}

watch(
  () => props.eventParticipant,
  () => {
    note.value = ''
    actionMode.value = ''
    checklist.value = {
      face_match: false,
      nik_match: false,
      name_match: false,
      birth_match: false,
      contingent_match: false,
      category_match: false,
    }
  }
)


const startDraw = async () => {
  try {
    const { data } = await axios.post(
      `/api/v1/event-participants/${props.eventParticipant.id}/draw-number`
    )

    numbers.value = data.numbers
    rolling.value = true

    intervalId.value = setInterval(() => {
      const idx = Math.floor(Math.random() * numbers.value.length)
      currentNumber.value = numbers.value[idx]
    }, 80)

  } catch (error) {
    const message =
      error.response?.data?.message ||
      'Gagal memulai undian nomor peserta.'

    Swal.fire('Gagal', message, 'error')
  }
}


const stopDraw = async () => {
  // pastikan interval berhenti
  if (intervalId.value) {
    clearInterval(intervalId.value)
    intervalId.value = null
  }

  rolling.value = false

  try {
    const res = await axios.post(
      `/api/v1/event-participants/${props.eventParticipant.id}/assign-number`,
      { branch_sequence: currentNumber.value }
    )

    Swal.fire(
      'Nomor Ditetapkan',
      `Nomor Peserta: ${res.data.participant_number}`,
      'success'
    )

    emit('updated')
    $('#reRegisterModal').modal('hide')

  } catch (error) {
    const message =
      error.response?.data?.message ||
      'Gagal menetapkan nomor peserta.'

    Swal.fire('Gagal', message, 'error')
  }
}




const submitRereg = async (payload) => {
  if (!ep.value?.id) return

  isSubmitting.value = true
  try {
    await axios.post(`/api/v1/event-participants/${ep.value.id}/re-registration`, payload)

    Swal.fire('Berhasil', 'Status daftar ulang berhasil diperbarui.', 'success')
    $('#reRegisterModal').modal('hide')
    emit('updated')
  } catch (e) {
    console.error(e)
    const msg = e?.response?.data?.message || 'Gagal memproses daftar ulang.'
    Swal.fire('Gagal', msg, 'error')
  } finally {
    isSubmitting.value = false
  }
}


const submitVerified = async () => {
  actionMode.value = 'verified'
  if (!isChecklistOk.value) {
    Swal.fire('Belum lengkap', 'Checklist inti harus lengkap sebelum ACC.', 'warning')
    return
  }

  await submitRereg({
    reregistration_status: 'verified',
    reregistration_notes: note.value || null,
  })
}

const submitRejected = async () => {
  actionMode.value = 'rejected'
  if (!note.value?.trim()) {
    Swal.fire('Wajib diisi', 'Catatan wajib diisi untuk penolakan daftar ulang.', 'warning')
    return
  }

  await submitRereg({
    reregistration_status: 'rejected',
    reregistration_notes: note.value.trim(),
  })
}

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

</style>


