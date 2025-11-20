<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useToastr } from '@/toastr';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import CloudImage from '../../components/CloudImage.vue';
import { useScreenDisplayStore } from '../../stores/ScreenDisplayStore.js';

const screenDisplayStore = useScreenDisplayStore();
const image_cloud_id = ref('');
const widget = window.cloudinary.createUploadWidget(
    {
        cloud_name: "kemenagpessel",
        upload_preset: "profile_picture_pegawai"
    },
    (error, result) => {
        if (!error && result && result.event === 'success') {
            console.log('Done Uploading...', result.info);
            image_cloud_id.value = result.info.secure_url;
        }

        if (!error && result && result.event == 'close') {
            handleFileChange();
        }
    }
)

function openUploadWidget() {
    widget.open();
}

const authUserStore = useAuthUserStore();
const toastr = useToastr();

const errors = ref([]);
const updateProfile = () => {
    axios.put('/api/profile', {
        name: authUserStore.user.name,
        email: authUserStore.user.email,
        role: authUserStore.user.role,
        nama_pemeriksa: authUserStore.user.nama_pemeriksa,
        nip_pemeriksa: authUserStore.user.nip_pemeriksa,
        jabatan: authUserStore.user.jabatan,
        print_layout: authUserStore.user.print_layout,
    })
        .then((response) => {
            toastr.success('Profile updated successfully!');
        })
        .catch((error) => {
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
            }
        });
};

const logout = () => {
    authUserStore.logout();
};

const changePasswordForm = reactive({
    currentPassword: '',
    password: '',
    passwordConfirmation: '',
});

const handleChangePassword = () => {
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
        });
};

const fileInput = ref(null);

const openFileInput = () => {
    fileInput.value.click();
};

const handleFileChange = (event) => {
    // const file = event.target.files[0];
    // authUserStore.user.avatar = URL.createObjectURL(file);

    // const formData = new FormData();
    // formData.append('profile_picture', image_cloud_id);

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
                                <!-- <input @change="handleFileChange" ref="fileInput" type="file" class="d-none"> -->
                                <img @click="openUploadWidget" class="profile-user-img img-circle"
                                    :src="authUserStore.user?.avatar" alt="User profile picture">
                                <!-- <input @change="handleFileChange" ref="fileInput" type="hidden" class="d-none"> -->
                                <!-- <img @click="openUploadWidget" class="profile-user-img img-circle" :src="authUserStore.user.avatar" alt="User profile picture"> -->
                                <!-- profile_picture_pegawai/hhmk4hzeqytpoehlne2a -->
                                <!-- <CloudImage @click="openUploadWidget" :image-name="authUserStore.user.avatar" /> -->

                            </div>

                            <h3 class="profile-username text-center">{{ authUserStore.user.name }}</h3>



                            <p class="text-muted text-center">{{ authUserStore.user.role }}</p>
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
                                    <form @submit.prevent="updateProfile()" class="form-horizontal">
                                        <div class="form-group row">
                                            <label for="inputName" class="col-sm-2 col-form-label">Name</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.name" type="text" class="form-control"
                                                    id="inputName" placeholder="Name">
                                                <span class="text-danger text-sm" v-if="errors && errors.name">{{
                                                    errors.name[0] }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.email" type="email" class="form-control "
                                                    id="inputEmail" placeholder="Email">
                                                <span class="text-danger text-sm" v-if="errors && errors.email">{{
                                                    errors.email[0] }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="jabatan" class="col-sm-2 col-form-label">Jabatan</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.jabatan" type="text"
                                                    class="form-control " id="jabatan" placeholder="Nama Pemeriksa">
                                                <span class="text-danger text-sm" v-if="errors && errors.jabatan">{{
                                                    errors.jabatan[0] }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="nama_pemeriksa" class="col-sm-2 col-form-label">Nama
                                                Pemeriksa</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.nama_pemeriksa" type="text"
                                                    class="form-control " id="nama_pemeriksa" placeholder="Nama Pemeriksa">
                                                <span class="text-danger text-sm" v-if="errors && errors.nama_pemeriksa">{{
                                                    errors.nama_pemeriksa[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="nip_pemeriksa" class="col-sm-2 col-form-label">NIP Pemeriksa</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.nip_pemeriksa" type="text"
                                                    class="form-control " id="nip_pemeriksa" placeholder="Nama Pemeriksa">
                                                <span class="text-danger text-sm" v-if="errors && errors.nip_pemeriksa">{{
                                                    errors.nip_pemeriksa[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="print_layout" class="col-sm-2 col-form-label">Print Layout</label>
                                            <div class="col-sm-10">
                                                <select v-model="authUserStore.user.print_layout" name="print_layout"
                                                    id="print_layout" class="form-control">
                                                    <option value="L">Landscape</option>
                                                    <option value="P">Portrait</option>
                                                </select>
                                                <!-- <input v-model="authUserStore.user.print_layout" type="text"
                                                    class="form-control " id="print_layout" placeholder="Nama Pemeriksa"> -->
                                                <span class="text-danger text-sm" v-if="errors && errors.print_layout">{{
                                                    errors.print_layout[0] }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="offset-sm-2 col-sm-10">
                                                <button type="submit" class="btn btn-success"><i
                                                        class="fa fa-save mr-1"></i> Save Changes</button>
                                            </div>
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
                                                    v-if="errors && errors.current_password">{{ errors.current_password[0]
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
                                                <button type="submit" class="btn btn-success"><i
                                                        class="fa fa-save mr-1"></i> Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button @click.prevent="logout" type="button" class="btn btn-danger btn-block"><i class="fas fa-sign-out-alt"></i> Logout</button>

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