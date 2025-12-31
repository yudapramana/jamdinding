<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useToastr } from '@/toastr'
import { useSettingStore } from '../../stores/SettingStore'

const toastr = useToastr()
const settingStore = useSettingStore()

/**
 * =========================
 * LOCAL FORM STATE
 * =========================
 * - maintenance: BOOLEAN
 */
const settings = ref({
  app_name: '',
  date_format: 'YYYY-MM-DD',
  pagination_limit: 10,
  maintenance: false,
  environment: 'development',
})

const original = ref({})
const errors = ref(null)

/**
 * =========================
 * FETCH SETTINGS
 * =========================
 */
const getSettings = async () => {
  try {
    const { data } = await axios.get('/api/settings')

    // 🔑 Frontend selalu BOOLEAN
    settings.value = {
      ...data,
      maintenance: ['1', 1, true, 'true', 'on'].includes(data.maintenance),
    }

    original.value = { ...settings.value }

    // 🔑 APPLY KE STORE (SINGLE SOURCE OF TRUTH)
    settingStore.applySettings(settings.value)

  } catch (error) {
    toastr.error('Gagal memuat pengaturan aplikasi.')
  }
}

/**
 * =========================
 * UPDATE SETTINGS
 * =========================
 */
const updateSettings = async () => {
  errors.value = null

  const prevOn = original.value.maintenance === true
  const nextOn = settings.value.maintenance === true

  // Konfirmasi hanya saat OFF → ON
  if (!prevOn && nextOn) {
    const ok = confirm(
      'Turn ON maintenance mode?\n\nSemua user akan otomatis logout.'
    )
    if (!ok) return
  }

  try {
    // 🔑 Backend tetap terima string
    const payload = {
      ...settings.value,
      maintenance: settings.value.maintenance ? '1' : '0',
    }

    await axios.post('/api/settings', payload)

    toastr.success('Pengaturan berhasil diperbarui')

    original.value = { ...settings.value }

    // 🔑 UPDATE STORE (BOOLEAN)
    settingStore.applySettings(settings.value)

    // Optional: reload jika maintenance baru ON
    if (!prevOn && nextOn) {
      location.reload()
    }

  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      toastr.error('Gagal menyimpan pengaturan.')
    }
  }
}

onMounted(getSettings)
</script>

<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Settings</h1>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">General Setting</h3>
            </div>

            <form @submit.prevent="updateSettings">
              <div class="card-body">
                <!-- APP NAME -->
                <div class="form-group">
                  <label>App Display Name</label>
                  <input
                    v-model="settings.app_name"
                    type="text"
                    class="form-control"
                    placeholder="Enter app display name"
                  />
                  <span v-if="errors?.app_name" class="text-danger text-sm">
                    {{ errors.app_name[0] }}
                  </span>
                </div>

                <!-- DATE FORMAT -->
                <div class="form-group">
                  <label>Date Format</label>
                  <select v-model="settings.date_format" class="form-control">
                    <option value="m/d/Y">MM/DD/YYYY</option>
                    <option value="d/m/Y">DD/MM/YYYY</option>
                    <option value="Y-m-d">YYYY-MM-DD</option>
                    <option value="F j, Y">Month DD, YYYY</option>
                    <option value="j F Y">DD Month YYYY</option>
                  </select>
                  <span v-if="errors?.date_format" class="text-danger text-sm">
                    {{ errors.date_format[0] }}
                  </span>
                </div>

                <!-- PAGINATION -->
                <div class="form-group">
                  <label>Pagination Limit</label>
                  <input
                    v-model.number="settings.pagination_limit"
                    type="number"
                    min="1"
                    class="form-control"
                  />
                  <span v-if="errors?.pagination_limit" class="text-danger text-sm">
                    {{ errors.pagination_limit[0] }}
                  </span>
                </div>

                <!-- MAINTENANCE MODE -->
                <div class="form-group">
                  <div class="custom-control custom-switch">
                    <input
                      type="checkbox"
                      class="custom-control-input"
                      id="maintenanceSwitch"
                      v-model="settings.maintenance"
                    />
                    <label class="custom-control-label" for="maintenanceSwitch">
                      Maintenance Mode
                    </label>
                  </div>
                  <small class="form-text text-muted">
                    Saat ON, semua user akan otomatis logout.
                  </small>
                  <span v-if="errors?.maintenance" class="text-danger text-sm">
                    {{ errors.maintenance[0] }}
                  </span>
                </div>

                <!-- ENVIRONMENT -->
                <div class="form-group">
                  <label>Application Environment</label>
                  <select v-model="settings.environment" class="form-control">
                    <option value="development">Development</option>
                    <option value="production">Production</option>
                  </select>

                  <small class="form-text text-muted">
                    Development: debug aktif.<br />
                    Production: mode aman.
                  </small>

                  <span v-if="errors?.environment" class="text-danger text-sm">
                    {{ errors.environment[0] }}
                  </span>
                </div>
              </div>

              <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-save mr-1"></i>
                  Save Changes
                </button>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>
