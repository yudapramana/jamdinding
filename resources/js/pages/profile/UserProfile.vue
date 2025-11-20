<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useToastr } from '@/toastr';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import { useScreenDisplayStore } from '../../stores/ScreenDisplayStore.js';
import { useMasterDataStore } from '../../stores/MasterDataStore.js';
import { useSettingStore } from '../../stores/SettingStore.js';

const screenDisplayStore = useScreenDisplayStore();
const authUserStore = useAuthUserStore();
const masterDataStore = useMasterDataStore();
const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);
const toastr = useToastr();
const errors = ref([]);
const image_cloud_id = ref('');
const isLoading = ref(false);
const isChangingPassword = ref(false);

const widget = window.cloudinary.createUploadWidget(
    {
        cloud_name: "pesselkemenag",
        upload_preset: "profile_picture"
    },
    (error, result) => {
        if (!error && result && result.event === 'success') {
            image_cloud_id.value = result.info.secure_url;
        }
        if (!error && result && result.event == 'close') {
            handleFileChange();
        }
    }
);

function openUploadWidget() {
    widget.open();
}

const updateProfile = () => {
    isLoading.value = true;
    axios.put('/api/profile', {
        nip: authUserStore.user.employee.nip,
        full_name: authUserStore.user.employee.full_name,
        date_of_birth: authUserStore.user.employee.date_of_birth,
        gender: authUserStore.user.employee.gender,
        phone_number: authUserStore.user.employee.phone_number,
        email: authUserStore.user.employee.email,
        job_title: authUserStore.user.employee.job_title,
        id_work_unit: authUserStore.user.employee.id_work_unit,
        employment_status: authUserStore.user.employee.employment_status,
        tmt_pangkat: authUserStore.user.employee.tmt_pangkat,
        tmt_jabatan: authUserStore.user.employee.tmt_jabatan,
    })
        .then((response) => {
            toastr.success('Profile updated successfully!');
        })
        .catch((error) => {
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
            }
        }).finally(() => {
            isLoading.value = false;
        });
};

const logout = () => {
    authUserStore.logout();
};

const fileInput = ref(null);

const openFileInput = () => {
    fileInput.value.click();
};

const handleFileChange = (event) => {
    if (image_cloud_id.value != '') {
        axios.post('/api/upload-profile-image', {
            profile_picture: image_cloud_id.value
        })
            .then((response) => {
                authUserStore.getAuthUser();
                toastr.success('Image uploaded successfully!');
            });
    } else {
        toastr.error('Image failed to upload!');
    }
};

const changePasswordForm = reactive({
    currentPassword: '',
    password: '',
    passwordConfirmation: '',
});

const handleChangePassword = () => {
    isChangingPassword.value = true;
    errors.value = '';
    axios.post('/api/change-user-password', changePasswordForm)
        .then((response) => {
            toastr.success(response.data.message);
            for (const field in changePasswordForm) {
                changePasswordForm[field] = '';
            }
        })
        .catch((error) => {
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
            }
        })
        .finally(() => {
            isChangingPassword.value = false;
        });
};

onMounted(() => {
    masterDataStore.getWorkunitList();
});
</script>



<template>


    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Profile</h1>
                </div>
                <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row" style="margin-bottom: 100px;">
                <div class="col-md-3">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <img @click="openUploadWidget" class="profile-user-img img-circle"
                                    :src="authUserStore.user.avatar" alt="User profile picture">
                            </div>
                            <h3 class="profile-username text-center">{{ authUserStore.user.employee.full_name }}</h3>
                            <p class="text-muted text-center text-sm">{{ authUserStore.user.employee.job_title }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#profile" data-toggle="tab"><i
                                            class="fa fa-user mr-1"></i> Edit Profile</a></li>
                                <li class="nav-item"><a class="nav-link" href="#changePassword" data-toggle="tab"><i
                                            class="fa fa-key mr-1"></i> Change
                                        Password</a></li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active" id="profile">
                                    <!-- <form @submit.prevent="updateProfile()" class="form-horizontal">
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">NIP</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.nip" type="text"
                                                    class="form-control" placeholder="NIP" readonly>
                                                <span class="text-danger text-sm" v-if="errors && errors.nip">{{
                                                    errors.nip[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Full Name</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.full_name" type="text"
                                                    class="form-control" placeholder="Full Name" readonly>
                                                <span class="text-danger text-sm" v-if="errors && errors.full_name">{{
                                                    errors.full_name[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Date of Birth</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.date_of_birth" type="date"
                                                    class="form-control" readonly>
                                                <span class="text-danger text-sm"
                                                    v-if="errors && errors.date_of_birth">{{ errors.date_of_birth[0]
                                                    }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Gender</label>
                                            <div class="col-sm-10">
                                                <select v-model="authUserStore.user.employee.gender" readonly
                                                    class="form-control">
                                                    <option value="">Select</option>
                                                    <option value="M">Male</option>
                                                    <option value="F">Female</option>
                                                </select>
                                                <span class="text-danger text-sm" v-if="errors && errors.gender">{{
                                                    errors.gender[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Phone</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.phone_number" type="text"
                                                    class="form-control" placeholder="Phone Number">
                                                <span class="text-danger text-sm"
                                                    v-if="errors && errors.phone_number">{{ errors.phone_number[0]
                                                    }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.email" type="email" readonly
                                                    class="form-control" placeholder="Email">
                                                <span class="text-danger text-sm" v-if="errors && errors.email">{{
                                                    errors.email[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Job Class</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.gol_ruang" type="text" readonly
                                                    class="form-control" placeholder="Job Title">
                                                <span class="text-danger text-sm" v-if="errors && errors.gol_ruang">{{
                                                    errors.gol_ruang[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Job Title</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.job_title" type="text" readonly
                                                    class="form-control" placeholder="Job Title">
                                                <span class="text-danger text-sm" v-if="errors && errors.job_title">{{
                                                    errors.job_title[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Work Unit</label>
                                            <div class="col-sm-10">
                                                <select v-model="authUserStore.user.employee.id_work_unit" readonly
                                                    class="form-control">
                                                    <option value="">Select Work Unit</option>
                                                    <option v-for="unit in masterDataStore.workunitList" :key="unit.id"
                                                        :value="unit.id">
                                                        {{ unit.text }}
                                                    </option>
                                                </select>
                                                <span class="text-danger text-sm"
                                                    v-if="errors && errors.id_work_unit">{{ errors.id_work_unit[0]
                                                    }}</span>
                                            </div>
                                        </div>


                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Status</label> 
                                            <div class="col-sm-10">
                                                <select v-model="authUserStore.user.employee.employment_status" readonly
                                                    class="form-control">
                                                    <option value="">Select</option>
                                                    <option value="PNS">PNS</option>
                                                    <option value="PPPK">PPPK</option>
                                                </select>
                                                <span class="text-danger text-sm"
                                                    v-if="errors && errors.employment_status">{{
                                                        errors.employment_status[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">TMT Pangkat</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.tmt_pangkat" type="date" readonly
                                                    class="form-control">
                                                <span class="text-danger text-sm" v-if="errors && errors.tmt_pangkat">{{
                                                    errors.tmt_pangkat[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">TMT Jabatan</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.tmt_jabatan" type="date" readonly
                                                    class="form-control">
                                                <span class="text-danger text-sm" v-if="errors && errors.tmt_jabatan">{{
                                                    errors.tmt_jabatan[0] }}</span>
                                            </div>
                                        </div>

                                         <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">TMT Pensiun</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.employee.tmt_pensiun" type="date" readonly
                                                    class="form-control">
                                                <span class="text-danger text-sm" v-if="errors && errors.tmt_pensiun">{{
                                                    errors.tmt_pensiun[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="offset-sm-2 col-sm-10">
                                                <button type="submit" class="btn btn-success" :disabled="isLoading">
                                                    <i v-if="isLoading" class="fa fa-spinner fa-spin mr-1"></i>
                                                    <i v-else class="fa fa-save mr-1"></i>
                                                    Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    </form> -->

                                    <span class="text-warning d-block my-2">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Harap mengisi nomor HP / WhatsApp sebelum mengisi dokumen.
                                    </span>


                                    <form @submit.prevent="updateProfile()" class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">NIP (Nomor Induk
                                                Pegawai)</label>
                                            <input v-model="authUserStore.user.employee.nip" type="text"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.nip">{{ errors.nip[0]
                                                }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Nama Lengkap</label>
                                            <input v-model="authUserStore.user.employee.full_name" type="text"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.full_name">{{
                                                errors.full_name[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Tanggal Lahir</label>
                                            <input v-model="authUserStore.user.employee.date_of_birth" type="date"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.date_of_birth">{{
                                                errors.date_of_birth[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Jenis Kelamin</label>
                                            <select v-model="authUserStore.user.employee.gender"
                                                class="form-control form-control-sm" disabled>
                                                <option value="">Pilih</option>
                                                <option value="M">Laki-laki</option>
                                                <option value="F">Perempuan</option>
                                            </select>
                                            <span class="text-danger text-sm" v-if="errors?.gender">{{ errors.gender[0]
                                                }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Nomor HP</label>
                                            <input v-model="authUserStore.user.employee.phone_number" type="text"
                                                class="form-control form-control-sm" placeholder="08xxxx">
                                            <span class="text-danger text-sm" v-if="errors?.phone_number">{{
                                                errors.phone_number[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Email</label>
                                            <input v-model="authUserStore.user.employee.email" type="email"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.email">{{ errors.email[0]
                                                }}</span>
                                        </div>

                                        <!-- <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Golongan / Ruang</label>
                                            <input v-model="authUserStore.user.employee.gol_ruang" type="text"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.gol_ruang">{{
                                                errors.gol_ruang[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Jabatan</label>
                                            <input v-model="authUserStore.user.employee.job_title" type="text"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.job_title">{{
                                                errors.job_title[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Unit Kerja</label>
                                            <select v-model="authUserStore.user.employee.id_work_unit"
                                                class="form-control form-control-sm" disabled>
                                                <option value="">Pilih Unit Kerja</option>
                                                <option v-for="unit in masterDataStore.workunitList" :key="unit.id"
                                                    :value="unit.id">{{ unit.text }}</option>
                                            </select>
                                            <span class="text-danger text-sm" v-if="errors?.id_work_unit">{{
                                                errors.id_work_unit[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Status Kepegawaian</label>
                                            <select v-model="authUserStore.user.employee.employment_status"
                                                class="form-control form-control-sm" disabled>
                                                <option value="">Pilih</option>
                                                <option value="PNS">PNS</option>
                                                <option value="PPPK">PPPK</option>
                                            </select>
                                            <span class="text-danger text-sm" v-if="errors?.employment_status">{{
                                                errors.employment_status[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">TMT Pangkat</label>
                                            <input v-model="authUserStore.user.employee.tmt_pangkat" type="date"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.tmt_pangkat">{{
                                                errors.tmt_pangkat[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">TMT Jabatan</label>
                                            <input v-model="authUserStore.user.employee.tmt_jabatan" type="date"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.tmt_jabatan">{{
                                                errors.tmt_jabatan[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">TMT Pensiun</label>
                                            <input v-model="authUserStore.user.employee.tmt_pensiun" type="date"
                                                class="form-control form-control-sm" readonly>
                                            <span class="text-danger text-sm" v-if="errors?.tmt_pensiun">{{
                                                errors.tmt_pensiun[0] }}</span>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Kategori</label>
                                            <select v-model="authUserStore.user.employee.employment_category"
                                                class="form-control form-control-sm" disabled>
                                                <option value="">Pilih</option>
                                                <option value="ACTIVE">Aktif</option>
                                                <option value="RETIRED">Pensiun</option>
                                                <option value="LEFT">Berhenti</option>
                                                <option value="DIED">Meninggal Dunia</option>
                                            </select>
                                            <span class="text-danger text-sm" v-if="errors?.employment_category">{{ errors.gender[0]
                                                }}</span>
                                        </div> -->

                                        <hr/>

                                        <div class="col-12">
                                            <hr />
                                            <button v-if="settingStore.showMaintenanceBadge" type="button" class="btn btn-warning btn-sm" disabled>
                                                Maintenance
                                            </button>
                                            <button v-else type="submit" class="btn btn-success btn-sm" :disabled="isLoading">
                                                <i v-if="isLoading" class="fa fa-spinner fa-spin me-1"></i>
                                                <i v-else class="fa fa-save me-1"></i>
                                                Simpan Perubahan
                                            </button>
                                            
                                        </div>
                                    </form>



                                </div>

                                <div class="tab-pane" id="changePassword">
                                    <form @submit.prevent="handleChangePassword" class="form-horizontal">
                                        <div class="form-group row">
                                            <label for="currentPassword" class="col-sm-3 col-form-label">Kata Sandi Saat Ini</label>
                                            <div class="col-sm-9">
                                                <input v-model="changePasswordForm.currentPassword" type="password"
                                                    class="form-control " id="currentPassword"
                                                    placeholder="Kata Sandi Saat Ini">
                                                <span class="text-danger text-sm"
                                                    v-if="errors && errors.current_password">{{
                                                        errors.current_password[0]
                                                    }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="newPassword" class="col-sm-3 col-form-label">Kata Sandi Baru</label>
                                            <div class="col-sm-9">
                                                <input v-model="changePasswordForm.password" type="password"
                                                    class="form-control " id="newPassword" placeholder="Kata Sandi Baru">
                                                <span class="text-danger text-sm" v-if="errors && errors.password">{{
                                                    errors.password[0] }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="passwordConfirmation" class="col-sm-3 col-form-label">Konfirmasi Kata Sandi Baru</label>
                                            <div class="col-sm-9">
                                                <input v-model="changePasswordForm.passwordConfirmation" type="password"
                                                    class="form-control " id="passwordConfirmation"
                                                    placeholder="Konfirmasi Kata Sandi Baru">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-3 col-sm-9">
                                                <button type="submit" class="btn btn-success"
                                                    :disabled="isChangingPassword">
                                                    <i v-if="isChangingPassword" class="fa fa-spinner fa-spin mr-1"></i>
                                                    <i v-else class="fa fa-save mr-1"></i>
                                                    Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <button @click.prevent="logout" type="button" class="btn btn-danger btn-block"><i
                                class="fas fa-sign-out-alt"></i> Logout</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<style>
.profile-user-img:hover {
    background-color: blue;
    cursor: pointer;
}
</style>