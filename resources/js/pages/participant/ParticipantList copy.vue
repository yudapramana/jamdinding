<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Bank Data Peserta</h1>
          <p class="mb-0 text-muted text-sm">
            Event:
            <strong>{{ eventInfo?.nama_event || '-' }}</strong>
            <span v-if="eventInfo?.lokasi_event"> • {{ eventInfo.lokasi_event }}</span>
          </p>
        </div>
        <button
          class="btn btn-primary btn-sm"
          @click="openCreateModal"
          :disabled="!eventId"
        >
          <i class="fas fa-user-plus mr-1"></i>
          Tambah Peserta
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <input
            v-model="search"
            type="text"
            class="form-control form-control-sm w-50"
            placeholder="Cari nama, NIK, atau nomor HP..."
          />
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Nama Peserta</th>
                <th>NIK</th>
                <th>Cabang / Golongan</th>
                <th>Asal</th>
                <th>HP</th>
                <th style="width: 90px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="participants.length === 0">
                <td colspan="7" class="text-center">Tidak ada data peserta.</td>
              </tr>
              <tr
                v-for="(p, index) in participants"
                :key="p.id"
              >
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>
                  <strong>{{ p.full_name }}</strong>
                  <div class="text-xs text-muted">
                    {{ p.gender === 'MALE' ? 'Laki-laki' : 'Perempuan' }},
                    lahir {{ p.place_of_birth }},
                    {{ formatDate(p.date_of_birth) }}
                  </div>
                </td>
                <td>{{ p.nik }}</td>
                <td>
                  <span class="text-sm">
                    {{ p.competition_branch?.name || '-' }}
                  </span>
                </td>
                <td>
                  <span class="text-sm">
                    {{ p.regency?.name || '-' }}
                  </span>
                </td>
                <td>{{ p.phone_number }}</td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-warning"
                      title="Edit"
                      @click="openEditModal(p)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-outline-danger"
                      title="Hapus"
                      @click="deleteParticipant(p)"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} peserta
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a
                  class="page-link"
                  href="#"
                  @click.prevent="changePage(meta.current_page - 1)"
                >
                  «
                </a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
                </span>
              </li>
              <li
                class="page-item"
                :class="{ disabled: meta.current_page === meta.last_page }"
              >
                <a
                  class="page-link"
                  href="#"
                  @click.prevent="changePage(meta.current_page + 1)"
                >
                  »
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Tambah / Edit Peserta -->
    <div class="modal fade" id="participantModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="participantModalLabel">
              <i class="fas fa-user-edit mr-2"></i>
              {{ isEdit ? 'Edit Peserta' : 'Tambah Peserta' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <form @submit.prevent="submitForm">
              <div class="row">
                <!-- Identitas -->
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="mb-1">NIK</label>
                    <input
                      v-model="form.nik"
                      @blur="onNikBlur"
                      class="form-control form-control-sm"
                      placeholder="Masukkan NIK"
                      maxlength="16"
                    />
                    <small v-if="nikError" class="text-danger">
                      {{ nikError }}
                    </small>
                    <!-- <small v-else class="text-muted">
                      Tanggal lahir & jenis kelamin akan otomatis terisi dari NIK yang valid.
                    </small> -->
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Lengkap</label>
                    <input
                      v-model="form.full_name"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">No. HP</label>
                    <input
                      v-model="form.phone_number"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Tempat Lahir</label>
                    <input
                      v-model="form.place_of_birth"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Lahir</label>
                    <input
                      v-model="form.date_of_birth"
                      type="date"
                      class="form-control form-control-sm"
                      required readonly
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Jenis Kelamin</label>
                    <select
                      v-model="form.gender"
                      class="form-control form-control-sm"
                      required readonly
                    >
                      <option value="MALE">Laki-laki</option>
                      <option value="FEMALE">Perempuan</option>
                    </select>
                  </div>
                </div>

                <!-- Lomba & Domisili -->
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="mb-1">Cabang / Golongan Event</label>
                    <select
                      v-model="form.event_competition_branch_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option value="" disabled>-- Pilih Cabang/Golongan --</option>
                      <option
                        v-for="b in branchOptions"
                        :key="b.id"
                        :value="b.id"
                      >
                        {{ b.name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Provinsi</label>
                    <select
                      v-model="form.province_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option value="" disabled>-- Pilih Provinsi --</option>
                      <option
                        v-for="p in provinceOptions"
                        :key="p.id"
                        :value="p.id"
                      >
                        {{ p.name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Kabupaten/Kota</label>
                    <select
                      v-model="form.regency_id"
                      class="form-control form-control-sm"
                      :disabled="!form.province_id || isLoadingRegencies"
                      required
                    >
                      <option value="" disabled>
                        {{ isLoadingRegencies ? 'Memuat Kabupaten/Kota...' : '-- Pilih Kabupaten/Kota --' }}
                      </option>
                      <option
                        v-for="r in regencyOptions"
                        :key="r.id"
                        :value="r.id"
                      >
                        {{ r.name }}
                      </option>
                    </select>
                    <small v-if="isLoadingRegencies" class="text-muted">
                      <i class="fas fa-spinner fa-spin mr-1"></i> Sedang memuat kabupaten/kota...
                    </small>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Kecamatan</label>
                    <select
                      v-model="form.district_id"
                      class="form-control form-control-sm"
                      :disabled="!form.regency_id || isLoadingDistricts"
                      required
                    >
                      <option value="" disabled>
                        {{ isLoadingDistricts ? 'Memuat Kecamatan...' : '-- Pilih Kecamatan --' }}
                      </option>
                      <option
                        v-for="d in districtOptions"
                        :key="d.id"
                        :value="d.id"
                      >
                        {{ d.name }}
                      </option>
                    </select>
                    <small v-if="isLoadingDistricts" class="text-muted">
                      <i class="fas fa-spinner fa-spin mr-1"></i> Sedang memuat kecamatan...
                    </small>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Kelurahan/Desa</label>
                    <select
                      v-model="form.village_id"
                      class="form-control form-control-sm"
                      :disabled="!form.district_id || isLoadingVillages"
                    >
                      <option :value="null">
                        {{ isLoadingVillages ? 'Memuat Kelurahan/Desa...' : '-- (Opsional) Pilih Kel/Desa --' }}
                      </option>
                      <option
                        v-for="v in villageOptions"
                        :key="v.id"
                        :value="v.id"
                      >
                        {{ v.name }}
                      </option>
                    </select>
                    <small v-if="isLoadingVillages" class="text-muted">
                      <i class="fas fa-spinner fa-spin mr-1"></i> Sedang memuat kelurahan/desa...
                    </small>
                  </div>


                  <div class="form-group mb-2">
                    <label class="mb-1">Alamat Lengkap</label>
                    <textarea
                      v-model="form.address"
                      rows="2"
                      class="form-control form-control-sm"
                      required
                    ></textarea>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Pendidikan Terakhir</label>
                    <select
                      v-model="form.education"
                      class="form-control form-control-sm"
                      required
                    >
                      <option value="SD">SD</option>
                      <option value="SMP">SMP</option>
                      <option value="SMA">SMA</option>
                      <option value="D1">D1</option>
                      <option value="D2">D2</option>
                      <option value="D3">D3</option>
                      <option value="D4">D4</option>
                      <option value="S1">S1</option>
                      <option value="S2">S2</option>
                      <option value="S3">S3</option>
                    </select>
                  </div>
                </div>

                <!-- Rekening & Dokumen -->
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="mb-1">No. Rekening</label>
                    <input
                      v-model="form.bank_account_number"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Pemilik Rekening</label>
                    <input
                      v-model="form.bank_account_name"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Bank</label>
                    <input
                      v-model="form.bank_name"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">URL Foto</label>
                    <input
                      v-model="form.photo_url"
                      class="form-control form-control-sm"
                      placeholder="Link foto peserta"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">URL KTP</label>
                    <input
                      v-model="form.id_card_url"
                      class="form-control form-control-sm"
                      placeholder="Link scan KTP"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">URL KK</label>
                    <input
                      v-model="form.family_card_url"
                      class="form-control form-control-sm"
                      placeholder="Link scan KK"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">URL Buku Tabungan</label>
                    <input
                      v-model="form.bank_book_url"
                      class="form-control form-control-sm"
                      placeholder="Link scan buku tabungan"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">URL Piagam / Sertifikat</label>
                    <input
                      v-model="form.certificate_url"
                      class="form-control form-control-sm"
                      placeholder="Link piagam MTQ"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">URL Dokumen Lain</label>
                    <input
                      v-model="form.other_url"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Terbit KTP</label>
                    <input
                      v-model="form.tanggal_terbit_ktp"
                      type="date"
                      class="form-control form-control-sm"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Tanggal Terbit KK</label>
                    <input
                      v-model="form.tanggal_terbit_kk"
                      type="date"
                      class="form-control form-control-sm"
                    />
                  </div>
                </div>
              </div>

              <div class="text-end mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="isSubmitting"
                >
                  <i
                    v-if="isSubmitting"
                    class="fas fa-spinner fa-spin mr-1"
                  ></i>
                  <i v-else class="fas fa-save mr-1"></i>
                  Simpan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

// LIST
const participants = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})
const search = ref('')
const isLoading = ref(false)

// EVENT INFO
const eventInfo = ref(null)
const eventId = ref(null)

// FORM / MODAL
const isEdit = ref(false)
const isSubmitting = ref(false)
const form = ref({
  id: null,
  event_competition_branch_id: '',
  nik: '',
  full_name: '',
  phone_number: '',
  place_of_birth: '',
  date_of_birth: '',
  gender: 'MALE',
  province_id: '',
  regency_id: '',
  district_id: '',
  village_id: null,
  address: '',
  education: 'SMA',
  bank_account_number: '',
  bank_account_name: '',
  bank_name: '',
  photo_url: '',
  id_card_url: '',
  family_card_url: '',
  bank_book_url: '',
  certificate_url: '',
  other_url: '',
  tanggal_terbit_ktp: '',
  tanggal_terbit_kk: '',
})

// Options (bisa diisi dari API master wilayah & branch)
const branchOptions = ref([])
const provinceOptions = ref([])
const regencyOptions = ref([])
const districtOptions = ref([])
const villageOptions = ref([])

const isLoadingRegencies = ref(false)
const isLoadingDistricts = ref(false)
const isLoadingVillages = ref(false)
const nikError = ref('')

// Helper ambil event_data dari storage/cookie
const getEventInfoFromStorage = () => {
  let raw = ''

  try {
    raw = localStorage.getItem('event_data') || ''
  } catch (e) {}

  if (!raw) {
    const cookie = document.cookie
      .split('; ')
      .find((row) => row.startsWith('event_data='))
    if (cookie) {
      raw = decodeURIComponent(cookie.split('=')[1])
    }
  }

  if (raw) {
    try {
      eventInfo.value = JSON.parse(raw)
      eventId.value = eventInfo.value.id || null
    } catch (e) {
      console.error('Gagal parse event_data:', e)
      eventInfo.value = null
    }
  }
}

// format tanggal untuk tampilan (tanpa new Date untuk hindari +1/-1)
const formatDate = (val) => {
  if (!val) return '-'
  const str = String(val).substring(0, 10)
  const [year, month, day] = str.split('-')
  const bulanIndo = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
  ]
  return `${day} ${bulanIndo[parseInt(month) - 1]} ${year}`
}

const toDateInput = (val) => {
  if (!val) return ''
  if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val
  return String(val).substring(0, 10)
}

// Ambil daftar peserta
const fetchParticipants = async (page = 1) => {
  if (!eventId.value) {
    participants.value = []
    return
  }

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/participants', {
      params: {
        page,
        search: search.value,
        event_id: eventId.value,
      },
    })

    participants.value = res.data.data || []
    meta.value = {
      current_page: res.data.current_page,
      per_page: res.data.per_page,
      total: res.data.total,
      from: res.data.from,
      to: res.data.to,
      last_page: res.data.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat peserta:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchParticipants(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    event_competition_branch_id: '',
    nik: '',
    full_name: '',
    phone_number: '',
    place_of_birth: '',
    date_of_birth: '',
    gender: 'MALE',
    province_id: '',
    regency_id: '',
    district_id: '',
    village_id: null,
    address: '',
    education: 'SMA',
    bank_account_number: '',
    bank_account_name: '',
    bank_name: '',
    photo_url: '',
    id_card_url: '',
    family_card_url: '',
    bank_book_url: '',
    certificate_url: '',
    other_url: '',
    tanggal_terbit_ktp: '',
    tanggal_terbit_kk: '',
  }
  $('#participantModal').modal('show')
}

const openEditModal = (p) => {
  isEdit.value = true
  form.value = {
    id: p.id,
    event_competition_branch_id: p.event_competition_branch_id,
    nik: p.nik,
    full_name: p.full_name,
    phone_number: p.phone_number,
    place_of_birth: p.place_of_birth,
    date_of_birth: toDateInput(p.date_of_birth),
    gender: p.gender || 'MALE',
    province_id: p.province_id,
    regency_id: p.regency_id,
    district_id: p.district_id,
    village_id: p.village_id,
    address: p.address,
    education: p.education || 'SMA',
    bank_account_number: p.bank_account_number,
    bank_account_name: p.bank_account_name,
    bank_name: p.bank_name,
    photo_url: p.photo_url,
    id_card_url: p.id_card_url,
    family_card_url: p.family_card_url,
    bank_book_url: p.bank_book_url,
    certificate_url: p.certificate_url,
    other_url: p.other_url,
    tanggal_terbit_ktp: toDateInput(p.tanggal_terbit_ktp),
    tanggal_terbit_kk: toDateInput(p.tanggal_terbit_kk),
  }
  $('#participantModal').modal('show')
}

const submitForm = async () => {
  if (!eventId.value) {
    alert('Event belum terdeteksi. Silakan pilih event terlebih dahulu.')
    return
  }

  isSubmitting.value = true

  const payload = {
    event_id: eventId.value,
    event_competition_branch_id: form.value.event_competition_branch_id,
    nik: form.value.nik,
    full_name: form.value.full_name,
    phone_number: form.value.phone_number,
    place_of_birth: form.value.place_of_birth,
    date_of_birth: form.value.date_of_birth || null,
    gender: form.value.gender,
    province_id: form.value.province_id,
    regency_id: form.value.regency_id,
    district_id: form.value.district_id,
    village_id: form.value.village_id,
    address: form.value.address,
    education: form.value.education,
    bank_account_number: form.value.bank_account_number,
    bank_account_name: form.value.bank_account_name,
    bank_name: form.value.bank_name,
    photo_url: form.value.photo_url,
    id_card_url: form.value.id_card_url,
    family_card_url: form.value.family_card_url,
    bank_book_url: form.value.bank_book_url,
    certificate_url: form.value.certificate_url,
    other_url: form.value.other_url,
    tanggal_terbit_ktp: form.value.tanggal_terbit_ktp || null,
    tanggal_terbit_kk: form.value.tanggal_terbit_kk || null,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/participants/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/participants', payload)
    }

    $('#participantModal').modal('hide')
    fetchParticipants(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan peserta:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan peserta.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteParticipant = async (p) => {
  if (!confirm(`Yakin ingin menghapus peserta "${p.full_name}"?`)) return

  try {
    await axios.delete(`/api/v1/participants/${p.id}`)
    fetchParticipants(1)
  } catch (error) {
    console.error('Gagal menghapus peserta:', error)
    alert(error.response?.data?.message || 'Gagal menghapus peserta.')
  }
}

// TODO: isi options dari API master branch & wilayah
const fetchBranchOptions = async () => {
  if (!eventId.value) return
  try {
    const res = await axios.get('/api/v1/get/event-competition-branches', {
      params: { event_id: eventId.value },
    })
    branchOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal memuat cabang/golongan event:', e)
  }
}

// misal kamu sudah punya endpoint untuk wilayah, bisa disesuaikan
const fetchProvinceOptions = async () => {
  try {
    const res = await axios.get('/api/v1/get/provinces')
    provinceOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal load provinsi:', e)
  }
}

const fetchRegencyOptions = async () => {
  if (!form.value.province_id) {
    regencyOptions.value = []
    districtOptions.value = []
    villageOptions.value = []
    form.value.regency_id = ''
    form.value.district_id = ''
    form.value.village_id = null
    return
  }

  isLoadingRegencies.value = true
  regencyOptions.value = []
  form.value.regency_id = ''
  districtOptions.value = []
  form.value.district_id = ''
  villageOptions.value = []
  form.value.village_id = null

  try {
    const res = await axios.get('/api/v1/get/regencies', {
      params: { province_id: form.value.province_id },
    })
    regencyOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal load kab/kota:', e)
  } finally {
    isLoadingRegencies.value = false
  }
}

const fetchDistrictOptions = async () => {
  if (!form.value.regency_id) {
    districtOptions.value = []
    villageOptions.value = []
    form.value.district_id = ''
    form.value.village_id = null
    return
  }

  isLoadingDistricts.value = true
  districtOptions.value = []
  form.value.district_id = ''
  villageOptions.value = []
  form.value.village_id = null

  try {
    const res = await axios.get('/api/v1/get/districts', {
      params: { regency_id: form.value.regency_id },
    })
    districtOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal load kecamatan:', e)
  } finally {
    isLoadingDistricts.value = false
  }
}

const fetchVillageOptions = async () => {
  if (!form.value.district_id) {
    villageOptions.value = []
    form.value.village_id = null
    return
  }

  isLoadingVillages.value = true
  villageOptions.value = []
  form.value.village_id = null

  try {
    const res = await axios.get('/api/v1/get/villages', {
      params: { district_id: form.value.district_id },
    })
    villageOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal load kel/desa:', e)
  } finally {
    isLoadingVillages.value = false
  }
}

const extractBirthdateFromNik = (nikRaw) => {
  // Normalisasi ke string dan ambil hanya digit
  const nik = String(nikRaw || '').replace(/\D/g, '')

  // Perlu minimal 12 digit untuk baca DDMMYY (idealnya 16 digit, tapi kita izinkan 12+)
  if (nik.length < 16) return null   // kalau mau strict 16 digit, ini pas

  // Format NIK: DDMMYY di posisi index 6-11 (7-12 secara 1-based)
  const ddStr = nik.slice(6, 8)
  const mmStr = nik.slice(8, 10)
  const yyStr = nik.slice(10, 12)

  let day = parseInt(ddStr, 10)
  const month = parseInt(mmStr, 10)
  const year2 = parseInt(yyStr, 10)

  if (Number.isNaN(day) || Number.isNaN(month) || Number.isNaN(year2)) {
    return null
  }

  // Jika perempuan → hari + 40
  let gender = 'MALE'
  if (day > 40) {
    day = day - 40
    gender = 'FEMALE'
  }

  // Validasi kasar tanggal
  if (day < 1 || day > 31 || month < 1 || month > 12) {
    return null
  }

  const now = new Date()
  const currentYear2 = now.getFullYear() % 100
  const fullYear = year2 <= currentYear2 ? 2000 + year2 : 1900 + year2

  const yyyy = String(fullYear).padStart(4, '0')
  const mm = String(month).padStart(2, '0')
  const dd = String(day).padStart(2, '0')

  return {
    dateOfBirth: `${yyyy}-${mm}-${dd}`,
    gender,
  }
}

const validateNik = () => {
  // reset error dulu
  nikError.value = ''

  const nikRaw = form.value.nik || ''
  const nik = nikRaw.replace(/\D/g, '')

  // 1. Harus diisi
  if (!nik) {
    nikError.value = 'NIK wajib diisi.'
    return false
  }

  // 2. Harus 16 digit
  if (nik.length !== 16) {
    nikError.value = 'NIK harus terdiri dari 16 digit angka.'
    return false
  }

  // 3. Coba extract tanggal lahir
  const result = extractBirthdateFromNik(nik)
  if (!result) {
    nikError.value = 'NIK tidak valid atau tanggal lahir tidak dapat dibaca dari NIK.'
    return false
  }

  // 4. Kalau valid, sekalian auto-isi tanggal lahir & gender
  form.value.date_of_birth = result.dateOfBirth
  form.value.gender = result.gender

  return true
}





watch(
  () => form.value.nik,
  (newNik) => {
    const result = extractBirthdateFromNik(newNik)

    // Kalau NIK belum lengkap / tidak valid → jangan apa-apa
    if (!result) return

    form.value.date_of_birth = result.dateOfBirth
    form.value.gender = result.gender
  }
)


const onNikBlur = () => {
  if (!form.value.nik) {
    nikError.value = ''
    return
  }
  validateNik()
}


// debounce pencarian
watch(
  () => search.value,
  useDebounceFn(() => fetchParticipants(1), 400)
)

// watcher dependency wilayah
watch(
  () => form.value.province_id,
  () => {
    form.value.regency_id = ''
    regencyOptions.value = []
    districtOptions.value = []
    villageOptions.value = []
    fetchRegencyOptions()
  }
)

watch(
  () => form.value.regency_id,
  () => {
    form.value.district_id = ''
    districtOptions.value = []
    villageOptions.value = []
    fetchDistrictOptions()
  }
)

watch(
  () => form.value.district_id,
  () => {
    form.value.village_id = null
    villageOptions.value = []
    fetchVillageOptions()
  }
)

onMounted(() => {
  getEventInfoFromStorage()
  fetchParticipants()
  fetchBranchOptions()
  fetchProvinceOptions()
})
</script>
