import axios from 'axios'
import { ref } from 'vue'


// =========================
// Helper Format & Badge
// =========================


export const bankOptions = [
  'BRI',
  'BNI',
  'MANDIRI',
  'BTN',
  'BSI',
  'BRI SYARIAH',
  'BNI SYARIAH',
  'MANDIRI SYARIAH',
  'BCA',
  'CIMB NIAGA',
  'PERMATA',
  'PANIN',
  'OCBC NISP',
  'DANAMON',
  'MEGA',
  'SINARMAS',
  'BUKOPIN',
  'MAYBANK',
  'BTPN',
  'J TRUST BANK',
  'BANK DKI',
  'BANK BJB',
  'BANK BJB SYARIAH',
  'BANK JATENG',
  'BANK JATIM',
  'BANK SUMUT',
  'BANK NAGARI',
  'BANK RIAU KEPRI',
  'BANK SUMSEL BABEL',
  'BANK LAMPUNG',
  'BANK KALSEL',
  'BANK KALBAR',
  'BANK KALTIMTARA',
  'BANK SULSEL BAR',
  'BANK SULTRA',
  'BANK SULUTGO',
  'BANK NTB SYARIAH',
  'BANK NTT',
  'BANK PAPUA',
  'BANK MALUKU MALUT',
]

export const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  if (Number.isNaN(d.getTime())) return dateStr
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}-${month}-${year}`
}

export const formatDateTime = (dateTimeStr) => {
  if (!dateTimeStr) return ''
  const d = new Date(dateTimeStr)
  if (Number.isNaN(d.getTime())) return dateTimeStr
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  const hh = String(d.getHours()).padStart(2, '0')
  const mm = String(d.getMinutes()).padStart(2, '0')
  return `${day}-${month}-${year} ${hh}:${mm}`
}

export const registrationBadgeClass = (status) => {
  switch (status) {
    case 'bank_data':
      return 'badge-secondary'
    case 'process':
      return 'badge-info'
    case 'verified':
      return 'badge-success'
    case 'need_revision':
      return 'badge-warning'
    case 'rejected':
      return 'badge-danger'
    case 'disqualified':
      return 'badge-dark'
    default:
      return 'badge-light'
  }
}

export const registrationStatusLabel = (status) => {
  switch (status) {
    case 'bank_data':
      return 'Bank Data'
    case 'process':
      return 'Diproses'
    case 'verified':
      return 'Terverifikasi'
    case 'need_revision':
      return 'Perlu Perbaikan'
    case 'rejected':
      return 'Ditolak'
    case 'disqualified':
      return 'Diskualifikasi'
    default:
      return status
  }
}

export const reregistrationBadgeClass = (status) => {
  switch (status) {
    case 'not_yet':
      return 'badge-secondary'
    case 'verified':
      return 'badge-success'
    case 'rejected':
      return 'badge-danger'
    default:
      return 'badge-light'
  }
}

export const reregistrationStatusLabel = (status) => {
  switch (status) {
    case 'not_yet':
      return 'Belum Hadir'
    case 'verified':
      return 'Lolos Daftar Ulang'
    case 'rejected':
      return 'Tidak Lolos Daftar Ulang'
    default:
      return status
  }
}

// =========================
// NIK Parsing (standar Indonesia)
// =========================
// Digit 7-12 (index 6-11): YYMMDD
// Female: hari +40
export const parseNikToDobGender = (nikRaw) => {
  const nik = String(nikRaw || '').replace(/\D/g, '')
  if (nik.length !== 16) return null

  const ddStr = nik.slice(6, 8)
  const mmStr = nik.slice(8, 10)
  const yyStr = nik.slice(10, 12)

  let day = parseInt(ddStr, 10)
  const month = parseInt(mmStr, 10)
  const year2 = parseInt(yyStr, 10)

  if (Number.isNaN(day) || Number.isNaN(month) || Number.isNaN(year2)) {
    return null
  }

  let gender = 'MALE'
  if (day > 40) {
    day -= 40
    gender = 'FEMALE'
  }

  if (day < 1 || day > 31 || month < 1 || month > 12) {
    return null
  }

  const now = new Date()
  const currentYear2 = now.getFullYear() % 100
  const fullYear = year2 <= currentYear2 ? 2000 + year2 : 1900 + year2

  const yyyy = String(fullYear).padStart(4, '0')
  const mm = String(month).padStart(2, '0')
  const dd = String(day).padStart(2, '0')

  return { date: `${yyyy}-${mm}-${dd}`, gender }
}


export const createRegionHelpers = ({
  form,
  provinceOptions,
  regencyOptions,
  districtOptions,
  villageOptions,
  isPrivileged,
  currentUser,
  eventInfo,
  isLoadingRegencies,
  isLoadingDistricts,
  isLoadingVillages,
}) => {

  // cache district_id terakhir yang sudah di-load desanya
  const lastLoadedVillageDistrictId = ref(null)

  // ==================================================
  // WILAYAH: FETCH MASTER
  // ==================================================
  const fetchProvinceOptions = async () => {
    if (!isPrivileged.value) {
      provinceOptions.value = []
      const u = currentUser.value

      if (u.province) {
        provinceOptions.value = [u.province]
        if (!form.value.participant.province_id) {
          form.value.participant.province_id = u.province.id
        }
      } else if (u.province_id) {
        provinceOptions.value = [
          { id: u.province_id, name: 'Provinsi Akun' },
        ]
        if (!form.value.participant.province_id) {
          form.value.participant.province_id = u.province_id
        }
      }
      return
    }

    try {
      const res = await axios.get('/api/v1/get/provinces')
      provinceOptions.value = res.data.data || res.data || []
    } catch (e) {
      console.error('Gagal load provinsi:', e)
    }
  }

  const fetchRegencyOptions = async (preserveSelection = false) => {
    if (!form.value.participant.province_id) {
      regencyOptions.value = []
      districtOptions.value = []
      villageOptions.value = []
      if (!preserveSelection) {
        form.value.participant.regency_id = ''
        form.value.participant.district_id = ''
        form.value.participant.village_id = null
      }
      return
    }

    if (!isPrivileged.value) {
      regencyOptions.value = []
      const u = currentUser.value
        console.log('u')
        console.log(currentUser)
      if (u.regency) {
        regencyOptions.value = [u.regency]
        form.value.participant.regency_id = u.regency.id
      } else if (u.regency_id) {
        regencyOptions.value = [
          { id: u.regency_id, name: 'Kab/Kota Akun' },
        ]
        form.value.participant.regency_id = u.regency_id
      }

      return
    }

    isLoadingRegencies.value = true

    if (!preserveSelection) {
      regencyOptions.value = []
      form.value.participant.regency_id = ''
      districtOptions.value = []
      form.value.participant.district_id = ''
      villageOptions.value = []
      form.value.participant.village_id = null
    }

    try {
      const res = await axios.get('/api/v1/get/regencies', {
        params: { province_id: form.value.participant.province_id },
      })
      regencyOptions.value = res.data.data || res.data || []
    } catch (e) {
      console.error('Gagal load kab/kota:', e)
    } finally {
      isLoadingRegencies.value = false
    }
  }

  const fetchDistrictOptions = async (preserveSelection = false) => {
    if (!form.value.participant.regency_id) {
      districtOptions.value = []
      villageOptions.value = []
      if (!preserveSelection) {
        form.value.participant.district_id = ''
        form.value.participant.village_id = null
      }
      return
    }

    if (!isPrivileged.value) {
      districtOptions.value = []
      const u = currentUser.value

      if (u.district) {
        districtOptions.value = [u.district]
        form.value.participant.district_id = u.district.id
      } else if (u.district_id) {
        districtOptions.value = [
          { id: u.district_id, name: 'Kecamatan Akun' },
        ]
        form.value.participant.district_id = u.district_id
      } else {
        try {
          const res = await axios.get('/api/v1/get/districts', {
            params: { regency_id: form.value.participant.regency_id },
          })
          districtOptions.value = res.data.data || res.data || []
        } catch (e) {
          console.error('Gagal load kecamatan:', e)
        } finally {
          isLoadingDistricts.value = false
        }
      }

      return
    }

    isLoadingDistricts.value = true

    if (!preserveSelection) {
      districtOptions.value = []
      form.value.participant.district_id = ''
      villageOptions.value = []
      form.value.participant.village_id = null
    }

    try {
      const res = await axios.get('/api/v1/get/districts', {
        params: { regency_id: form.value.participant.regency_id },
      })
      districtOptions.value = res.data.data || res.data || []
    } catch (e) {
      console.error('Gagal load kecamatan:', e)
    } finally {
      isLoadingDistricts.value = false
    }
  }

  const fetchVillageOptions = async (preserveSelection = false) => {
    const currentDistrictId = form.value.participant.district_id

    // Kalau belum pilih kecamatan → kosongkan desa
    if (!currentDistrictId) {
      villageOptions.value = []
      if (!preserveSelection) {
        form.value.participant.village_id = null
      }
      lastLoadedVillageDistrictId.value = null
      return
    }

    // ✅ CEGAH REQUEST BERULANG
    // 1) Kalau district sama dengan yang sudah pernah di-load
    // 2) Dan kita sudah punya villageOptions
    // → tidak perlu hit API lagi
    if (
      lastLoadedVillageDistrictId.value === currentDistrictId &&
      villageOptions.value.length
    ) {
      return
    }

    // kalau preserveSelection = true dan data sudah ada untuk district ini → skip
    if (
      preserveSelection &&
      lastLoadedVillageDistrictId.value === currentDistrictId &&
      villageOptions.value.length
    ) {
      return
    }

    isLoadingVillages.value = true

    if (!preserveSelection) {
      villageOptions.value = []
      form.value.participant.village_id = null
    }

    try {
      const res = await axios.get('/api/v1/get/villages', {
        params: { district_id: currentDistrictId },
      })
      villageOptions.value = res.data.data || res.data || []
      // set district yang sudah di-load
      lastLoadedVillageDistrictId.value = currentDistrictId
    } catch (e) {
      console.error('Gagal load kel/desa:', e)
    } finally {
      isLoadingVillages.value = false
    }
  }


  // ==================================================
  // WILAYAH: APPLY EVENT LEVEL KE FORM
  // ==================================================
  const applyEventRegionToForm = async (row = null) => {
    if (!eventInfo.value) return

    const te = eventInfo.value.event_level
    const rowData = row || {}

    if (te === 'province') {
      if (eventInfo.value.province_id) {
        form.value.participant.province_id = eventInfo.value.province_id
        await fetchRegencyOptions(true)
      }
      if (rowData.regency_id) {
        form.value.participant.regency_id = rowData.regency_id
        await fetchDistrictOptions(true)
      }
      if (rowData.district_id) {
        form.value.participant.district_id = rowData.district_id
        await fetchVillageOptions(true)
      }
      if (rowData.village_id) {
        form.value.participant.village_id = rowData.village_id
      }
      return
    }

    if (te === 'regency') {
      if (eventInfo.value.province_id) {
        form.value.participant.province_id = eventInfo.value.province_id
        await fetchRegencyOptions(true)
      }
      if (eventInfo.value.regency_id) {
        form.value.participant.regency_id = eventInfo.value.regency_id
        await fetchDistrictOptions(true)
      }
      if (rowData.district_id) {
        form.value.participant.district_id = rowData.district_id
        await fetchVillageOptions(true)
      }
      if (rowData.village_id) {
        form.value.participant.village_id = rowData.village_id
      }
      return
    }

    if (te === 'district') {
      if (eventInfo.value.province_id) {
        form.value.participant.province_id = eventInfo.value.province_id
        await fetchRegencyOptions(true)
      }
      if (eventInfo.value.regency_id) {
        form.value.participant.regency_id = eventInfo.value.regency_id
        await fetchDistrictOptions(true)
      }
      if (eventInfo.value.district_id) {
        form.value.participant.district_id = eventInfo.value.district_id
        await fetchVillageOptions(true)
      }
      if (rowData.village_id) {
        form.value.participant.village_id = rowData.village_id
      }
      return
    }

    // national / default
    if (rowData.province_id) {
      form.value.participant.province_id = rowData.province_id
      await fetchRegencyOptions(true)
    }
    if (rowData.regency_id) {
      form.value.participant.regency_id = rowData.regency_id
      await fetchDistrictOptions(true)
    }
    if (rowData.district_id) {
      form.value.participant.district_id = rowData.district_id
      await fetchVillageOptions(true)
    }
    if (rowData.village_id) {
      form.value.participant.village_id = rowData.village_id
    }
  }

  return {
    fetchProvinceOptions,
    fetchRegencyOptions,
    fetchDistrictOptions,
    fetchVillageOptions,
    applyEventRegionToForm,
  }
}


export const createEmptyEventParticipantForm = (eventId = null) => ({
  participant: {
    id: null,
    nik: '',
    full_name: '',
    phone_number: '',
    place_of_birth: '',
    date_of_birth: '',
    gender: '',
    education: 'SMA',
    address: '',
    province_id: null,
    regency_id: null,
    district_id: null,
    village_id: null,
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
  },

  event_participant: {
    id: null,
    event_id: eventId,
    participant_id: null,
    event_category_id: '',
    contingent: '',
    registration_status: 'bank_data',
    registration_notes: '',
    reregistration_status: 'not_yet',
    reregistration_notes: '',
  },
})


export const eventParticipantRequiredFields = [
  'participant.nik',
  'participant.full_name',
  'participant.phone_number',
  'participant.place_of_birth',
  'participant.date_of_birth',
  'participant.gender',
  'participant.education',
  'participant.address',
  'participant.province_id',
  'participant.regency_id',
  'participant.district_id',
  'participant.village_id',
  'participant.bank_account_number',
  'participant.bank_account_name',
  'participant.bank_name',
  'participant.tanggal_terbit_ktp',
  'participant.tanggal_terbit_kk',
  'event_participant.event_category_id',
]

export const getNestedFieldValue = (obj, path) => {
  return path.split('.').reduce((o, key) => (o ? o[key] : undefined), obj)
}



export const MAX_EVENT_PARTICIPANT_FILE_SIZE = 1024 * 1024

export const eventParticipantAttachmentFields = [
  'photo_url',
  'id_card_url',
  'family_card_url',
  'bank_book_url',
  'certificate_url',
  'other_url',
]

export const createAttachmentHandlers = ({ files, fileErrors, form }) => {
  const onFileChange = (event, field) => {
    // ... pindahkan isi fungsi onFileChange kamu di sini, tapi gunakan
    // files.value & fileErrors.value yang dikirim dari luar
    fileErrors.value[field] = ''
    const file = event.target.files[0]

    if (!file) {
      files.value[field] = null
      return
    }

    if (field === 'photo_url') {
      const allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png']
      const fileType = file.type

      if (!allowedImageTypes.includes(fileType)) {
        fileErrors.value[field] = 'Foto harus berupa file JPG atau PNG.'
        event.target.value = ''
        files.value[field] = null
        return
      }
    } else {
      if (file.type !== 'application/pdf') {
        fileErrors.value[field] = 'Format file harus PDF.'
        event.target.value = ''
        files.value[field] = null
        return
      }
    }

    if (file.size > MAX_FILE_SIZE) {
      fileErrors.value[field] = 'Ukuran file maksimal 1 MB.'
      event.target.value = ''
      files.value[field] = null
      return
    }

    files.value[field] = file
  }

  const openFile = (field) => {
    let fileObj = files.value[field]
    let url = ''

    if (fileObj) {
      url = URL.createObjectURL(fileObj)
    } else if (form.value.participant[field]) {
      url = form.value.participant[field]
    }

    if (url) window.open(url, '_blank')
  }

  const hasFile = (field) => {
    return !!files.value[field] || !!form.value.participant[field]
  }

  const resetFiles = () => {
    eventParticipantAttachmentFields.forEach((f) => {
      files.value[f] = null
      fileErrors.value[f] = ''
    })
  }

  return { onFileChange, openFile, hasFile, resetFiles }
}
