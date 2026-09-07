<script setup>
import { reactive, ref } from 'vue';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import { useScreenDisplayStore } from '../../stores/ScreenDisplayStore.js';
import axios from 'axios';
import Swal from 'sweetalert2'; 
import { useRouter } from 'vue-router'; 

const screenDisplayStore = useScreenDisplayStore();
const authUserStore = useAuthUserStore();
const router = useRouter(); 

const errors = ref([]);
const isLoading = ref(false);
const isChangingPassword = ref(false);

const updateProfile = () => {
    errors.value = {};
    isLoading.value = true;
    axios.put('/api/profile', {
        name: authUserStore.user.name,
        username: authUserStore.user.username,
        email: authUserStore.user.email,
    })
        .then((response) => {
            if (response.data.user) {
                authUserStore.user = response.data.user;
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: response.data.message || 'Profile updated successfully!',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    router.push('/admin/dashboard');
                }
            });
        })
        .catch((error) => {
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    text: 'Silakan periksa kembali data profil Anda.',
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan pada server!',
                });
            }
        })
        .finally(() => {
            isLoading.value = false;
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
    errors.value = {};
    isChangingPassword.value = true;
    axios.post('/api/change-user-password', changePasswordForm)
        .then((response) => {
            if (response.data.user) {
                console.log(response.data);
                authUserStore.user = response.data.user;
            }

            for (const field in changePasswordForm) {
                changePasswordForm[field] = '';
            }

            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: response.data.message || 'Password berhasil diubah!',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    router.push('/admin/dashboard');
                }
            });
        })
        .catch((error) => {
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    text: 'Silakan periksa kembali isian kata sandi Anda.',
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan pada server!',
                });
            }
        })
        .finally(() => {
            isChangingPassword.value = false;
        });
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

                <div class="col-md-12">
                    
                    <!-- 👇 TAMBAHAN: Warning Alert -->
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Peringatan Keamanan!</h5>
                        Demi keamanan sistem, harap <strong>segera mengubah password default</strong> Anda. 
                        <strong>Jangan pernah menyebarkan atau membagikan akun Anda</strong> (username & password) kepada orang lain.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <!-- 👆 AKHIR TAMBAHAN -->

                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item">
                                    <a class="nav-link" href="#profile" data-toggle="tab">
                                        <i class="fa fa-user mr-1"></i> Edit Profile
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="#changePassword" data-toggle="tab">
                                        <i class="fa fa-key mr-1"></i> Ubah Password
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane" id="profile">
                                    <form @submit.prevent="updateProfile()" class="form-horizontal">
                                        <div class="form-group row">
                                            <label for="inputName" class="col-sm-2 col-form-label">Name</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.name" type="text" class="form-control"
                                                    id="inputName" placeholder="Name">
                                                <span class="text-danger text-sm" v-if="errors && errors.name">
                                                    {{ errors.name[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="inputUsername" class="col-sm-2 col-form-label">Username</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.username" type="text" class="form-control"
                                                    id="inputUsername" placeholder="Username" readonly>
                                                <span class="text-danger text-sm" v-if="errors && errors.username">
                                                    {{ errors.username[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input v-model="authUserStore.user.email" type="email" class="form-control"
                                                    id="inputEmail" placeholder="Email">
                                                <span class="text-danger text-sm" v-if="errors && errors.email">
                                                    {{ errors.email[0] }}
                                                </span>
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
                                    </form>
                                </div>

                                <div class="tab-pane active" id="changePassword">
                                    <form @submit.prevent="handleChangePassword" class="form-horizontal">
                                        <div class="form-group row">
                                            <label for="currentPassword" class="col-sm-3 col-form-label">Kata Sandi Saat Ini</label>
                                            <div class="col-sm-9">
                                                <input v-model="changePasswordForm.currentPassword" type="password"
                                                    class="form-control" id="currentPassword"
                                                    placeholder="Kata Sandi Saat Ini">
                                                <span class="text-danger text-sm" v-if="errors && errors.current_password">
                                                    {{ errors.current_password[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="newPassword" class="col-sm-3 col-form-label">Kata Sandi Baru</label>
                                            <div class="col-sm-9">
                                                <input v-model="changePasswordForm.password" type="password"
                                                    class="form-control" id="newPassword" placeholder="Kata Sandi Baru">
                                                <span class="text-danger text-sm" v-if="errors && errors.password">
                                                    {{ errors.password[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="passwordConfirmation" class="col-sm-3 col-form-label">Konfirmasi Kata Sandi Baru</label>
                                            <div class="col-sm-9">
                                                <input v-model="changePasswordForm.passwordConfirmation" type="password"
                                                    class="form-control" id="passwordConfirmation"
                                                    placeholder="Konfirmasi Kata Sandi Baru">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-3 col-sm-9">
                                                <button type="submit" class="btn btn-success" :disabled="isChangingPassword">
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
                    </div>
                    <button @click.prevent="logout" type="button" class="btn btn-danger btn-block">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>