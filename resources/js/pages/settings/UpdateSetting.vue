<script setup>
import { onMounted, ref } from 'vue';
import { useToastr } from '@/toastr';

const settings = ref({});
const original = ref({}); // simpan nilai awal dari server
const errors = ref(null);
const toastr = useToastr();

const isOn = (v) => v === true || v === 1 || v === '1' || v === 'true' || v === 'on';

const getSettings = async () => {
  const { data } = await axios.get('/api/settings');
  settings.value = data;
  original.value = { ...data }; // snapshot awal
};

const updateSettings = async () => {
  errors.value = null;

  const prevOn = isOn(original.value.maintenance);
  const nextOn = isOn(settings.value.maintenance);

  // Konfirmasi hanya saat toggle dari OFF -> ON
  if (!prevOn && nextOn) {
    const ok = confirm(
      'Turn ON maintenance mode? This will log out ALL users immediately.'
    );
    if (!ok) return;
  }

  try {
    await axios.post('/api/settings', settings.value);
    toastr.success('Settings updated successfully!');
    // Setelah sukses, perbarui snapshot agar konfirmasi tidak muncul lagi kalau tidak ada perubahan
    original.value = { ...settings.value };

    // Jika mau paksa reload saat menjadi ON, baru aktifkan ini:
    // if (!prevOn && nextOn) location.reload();
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors;
    }
  }
};

onMounted(getSettings);
</script>

<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Settings</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
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

                        <form @submit.prevent="updateSettings()">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="appName">App Display Name</label>
                                    <input v-model="settings.app_name" type="text" class="form-control" id="appName"
                                        placeholder="Enter app display name">
                                    <span class="text-danger text-sm" v-if="errors && errors.app_name">{{ errors.app_name[0]
                                    }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="dateFormat">Date Format</label>
                                    <select v-model="settings.date_format" class="form-control">
                                        <option value="m/d/Y">MM/DD/YYYY</option>
                                        <option value="d/m/Y">DD/MM/YYYY</option>
                                        <option value="Y-m-d">YYYY-MM-DD</option>
                                        <option value="F j, Y">Month DD, YYYY</option>
                                        <option value="j F Y">DD Month YYYY</option>
                                    </select>
                                    <span class="text-danger text-sm" v-if="errors && errors.date_format">{{
                                        errors.date_format[0] }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="paginationLimit">Pagination Limit</label>
                                    <input v-model="settings.pagination_limit" type="text" class="form-control"
                                        id="paginationLimit" placeholder="Enter pagination limit">
                                    <span class="text-danger text-sm" v-if="errors && errors.pagination_limit">{{
                                        errors.pagination_limit[0] }}</span>
                                </div>

                                 <!-- NEW: Maintenance Mode toggle -->
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="maintenanceSwitch"
                                            v-model="settings.maintenance"
                                            :true-value="'1'"
                                            :false-value="'0'"
                                        />
                                        <label class="custom-control-label" for="maintenanceSwitch">
                                        Maintenance Mode
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Saat ON, semua user akan langsung di-logout.
                                    </small>
                                    <span class="text-danger text-sm" v-if="errors && errors.maintenance">
                                        {{ errors.maintenance[0] }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>Save
                                    Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</div></template>