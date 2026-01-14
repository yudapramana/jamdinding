<template>
  <div
    class="modal fade"
    id="mutasiModal"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header">
          <h5 class="modal-title">Mutasi Peserta</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>

        <!-- BODY -->
        <div class="modal-body">

          <div class="form-group">
            <label>Provinsi</label>
            <select
              v-model="form.province_id"
              class="form-control form-control-sm"
              :class="{ 'is-invalid': errors.province_id }"
              @change="onProvinceChange"
            >
              <option value="">Pilih Provinsi</option>
              <option
                v-for="p in provinceOptions"
                :key="p.id"
                :value="p.id"
              >
                {{ p.name }}
              </option>
            </select>
            <div class="invalid-feedback">
              {{ errors.province_id }}
            </div>
          </div>

          <div class="form-group">
            <label>Kab / Kota</label>
            <select
              v-model="form.regency_id"
              class="form-control form-control-sm"
              :class="{ 'is-invalid': errors.regency_id }"
              @change="onRegencyChange"
            >
              <option value="">Pilih Kab / Kota</option>
              <option
                v-for="r in regencyOptions"
                :key="r.id"
                :value="r.id"
              >
                {{ r.name }}
              </option>
            </select>
            <div class="invalid-feedback">
              {{ errors.regency_id }}
            </div>
          </div>

          <div class="form-group mb-1">
            <label>Kecamatan</label>
            <select
              v-model="form.district_id"
              class="form-control form-control-sm"
              :class="{ 'is-invalid': errors.district_id }"
            >
              <option value="">Pilih Kecamatan</option>
              <option
                v-for="d in districtOptions"
                :key="d.id"
                :value="d.id"
              >
                {{ d.name }}
              </option>
            </select>
            <div class="invalid-feedback">
              {{ errors.district_id }}
            </div>
          </div>

          <small class="text-danger d-block mt-2">
            <i class="fas fa-exclamation-circle mr-1"></i>
            Disarankan mengisi hingga kecamatan sesuai KTP atau KK.
          </small>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-sm btn-secondary"
            data-dismiss="modal"
          >
            Tidak
          </button>
          <button
            type="button"
            class="btn btn-sm btn-primary"
            :disabled="isSubmitting"
            @click="submit"
          >
            <i
              v-if="isSubmitting"
              class="fas fa-spinner fa-spin mr-1"
            ></i>
            Pindahkan
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../stores/AuthUserStore'

const authUserStore = useAuthUserStore()
const currentUser = computed(() => authUserStore.user || {})

const props = defineProps({
  participantId: {
    type: Number,
    required: true,
  },
  initialRegion: {
    type: Object,
    default: () => ({
      province_id: '',
      regency_id: '',
      district_id: '',
    }),
  },
})

const emit = defineEmits(['success'])

const isSubmitting = ref(false)

const form = ref({
  province_id: '',
  regency_id: '',
  district_id: '',
})

const errors = ref({
  province_id: '',
  regency_id: '',
  district_id: '',
})

const provinceOptions = ref([])
const regencyOptions = ref([])
const districtOptions = ref([])

/* ================= API LOADERS ================= */

const loadProvinces = async () => {
    const u = currentUser.value
    if (u.province) {
        provinceOptions.value = [u.province]
        if (!form.value.province_id) {
            form.value.province_id = u.province.id
        }
    } else {
        const res = await axios.get('/api/v1/get/provinces')
        provinceOptions.value = res.data.data || res.data || []
    }
}

const loadRegencies = async () => {
    const u = currentUser.value
    if (u.regency) {
        regencyOptions.value = [u.regency]
        form.value.regency_id = u.regency.id
    } else {
        if (!form.value.province_id) return
        const res = await axios.get('/api/v1/get/regencies', {
            params: { province_id: form.value.province_id },
        })
        regencyOptions.value = res.data.data || res.data || []
    }
}

const loadDistricts = async () => {
  if (!form.value.regency_id) return
  const res = await axios.get('/api/v1/get/districts', {
    params: { regency_id: form.value.regency_id },
  })
  districtOptions.value = res.data.data || res.data || []
}

/* ================= CHANGE HANDLERS ================= */

const onProvinceChange = async () => {
  form.value.regency_id = ''
  form.value.district_id = ''
  regencyOptions.value = []
  districtOptions.value = []
  await loadRegencies()
}

const onRegencyChange = async () => {
  form.value.district_id = ''
  districtOptions.value = []
  await loadDistricts()
}

/* ================= VALIDATION ================= */

const validate = () => {
  errors.value = {
    province_id: '',
    regency_id: '',
    district_id: '',
  }

  if (!form.value.province_id) {
    errors.value.province_id = 'Provinsi wajib dipilih.'
  }
  if (!form.value.regency_id) {
    errors.value.regency_id = 'Kabupaten/Kota wajib dipilih.'
  }
  if (!form.value.district_id) {
    errors.value.district_id = 'Kecamatan wajib dipilih.'
  }

  return !Object.values(errors.value).some(Boolean)
}

/* ================= SUBMIT ================= */

const submit = async () => {
  if (!validate()) return

  isSubmitting.value = true
  try {
    await axios.post(
      `/api/v1/event-participants/${props.participantId}/mutasi-wilayah`,
      form.value
    )

    $('#mutasiModal').modal('hide')

    Swal.fire({
      icon: 'success',
      title: 'Wilayah peserta berhasil dipindahkan.',
    })

    emit('success')
  } catch (e) {
    const msg =
      e.response?.data?.message || 'Gagal memindahkan peserta.'
    Swal.fire('Gagal', msg, 'error')
  } finally {
    isSubmitting.value = false
  }
}

/* ================= INIT ================= */

const open = async () => {
  form.value = { ...props.initialRegion }
  await loadProvinces()
  await loadRegencies()
  await loadDistricts()
  $('#mutasiModal').modal('show')
}

defineExpose({ open })
</script>
