<script setup>
import { ref, reactive } from 'vue';
import { useToastr } from '@/toastr';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import { useScreenDisplayStore } from '../../stores/ScreenDisplayStore.js';
import axios from 'axios';
import { useRouter } from 'vue-router';

const authUserStore = useAuthUserStore();
const screenDisplayStore = useScreenDisplayStore();
const toastr = useToastr();
const isChangingPassword = ref(false);
const errors = ref({});
const router = useRouter();

const changePasswordForm = reactive({
    currentPassword: authUserStore.user.username,
    password: '',
    passwordConfirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const handleChangePassword = async () => {
    isChangingPassword.value = true;
    errors.value = {};
    try {
        const response = await axios.post('/api/change-user-password', changePasswordForm);
        toastr.success(response.data.message);

        for (const field in changePasswordForm) {
            changePasswordForm[field] = '';
        }

        authUserStore.user.must_change_password = false;
        console.log('authUserStore.user.must_change_password');
        console.log(authUserStore.user.must_change_password);

        // Ensure the route exists and is correctly spelled
        router.push('/user/dashboard');
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        }
    } finally {
        isChangingPassword.value = false;
    }
};

</script>

<template>

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Ubah Password</h1>
                </div>
                <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Ubah Password</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm rounded">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Ubah Password</h4>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="handleChangePassword">

                                <!-- Current Password -->
                                <div class="form-group mb-3">
                                    <label for="currentPassword">Kata Sandi Saat Ini</label>
                                    <div class="input-group">
                                        <input :type="showCurrentPassword ? 'text' : 'password'"
                                            v-model="changePasswordForm.currentPassword" id="currentPassword"
                                            class="form-control" placeholder="Masukkan Kata Sandi Saat Ini" />
                                        <button class="btn btn-outline-secondary" type="button"
                                            @click="showCurrentPassword = !showCurrentPassword">
                                            <i :class="showCurrentPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                        </button>
                                    </div>
                                    <span class="text-danger text-sm" v-if="errors?.current_password">
                                        {{ errors.current_password[0] }}
                                    </span>
                                </div>

                                <!-- New Password -->
                                <div class="form-group mb-3">
                                    <label for="newPassword">Kata Sandi Baru</label>
                                    <div class="input-group">
                                        <input :type="showNewPassword ? 'text' : 'password'"
                                            v-model="changePasswordForm.password" id="newPassword" class="form-control"
                                            placeholder="Kata Sandi Baru" />
                                        <button class="btn btn-outline-secondary" type="button"
                                            @click="showNewPassword = !showNewPassword">
                                            <i :class="showNewPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                        </button>
                                    </div>
                                    <span class="text-danger text-sm" v-if="errors?.password">
                                        {{ errors.password[0] }}
                                    </span>
                                </div>

                                <!-- Confirm Password -->
                                <div class="form-group mb-3">
                                    <label for="passwordConfirmation">Konfirmasi Kata Sandi Baru</label>
                                    <div class="input-group">
                                        <input :type="showConfirmPassword ? 'text' : 'password'"
                                            v-model="changePasswordForm.passwordConfirmation" id="passwordConfirmation"
                                            class="form-control" placeholder="Konfirmasi Kata Sandi Baru" />
                                        <button class="btn btn-outline-secondary" type="button"
                                            @click="showConfirmPassword = !showConfirmPassword">
                                            <i :class="showConfirmPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success" :disabled="isChangingPassword">
                                        <i v-if="isChangingPassword" class="fa fa-spinner fa-spin me-1"></i>
                                        <i v-else class="fa fa-save me-1"></i>
                                        Save Changes
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    padding-top: 50px;
}
</style>
