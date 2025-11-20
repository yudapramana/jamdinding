<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useToastr } from "../../toastr.js";
import { useAuthUserStore } from '../../stores/AuthUserStore';

const toastr = useToastr();
const authUserStore = useAuthUserStore();

const props = defineProps({
  user: { type: Object, required: true },
  index: { type: Number, required: true },
  selectAll: { type: Boolean, default: false }
});

const emit = defineEmits(['userDeleted', 'editUser', 'confirmUserDeletion', 'toggleSelection']);

const roles = ref([
  { name: 'SUPERADMIN', value: 1 },
  { name: 'ADMIN', value: 2 },
  { name: 'USER', value: 3 },
  { name: 'REVIEWER', value: 4 }
]);

const changeRole = (user, roleValue) => {
  axios.patch(`/api/users/${user.id}/change-role`, { role: roleValue })
    .then(() => {
      toastr.success('Role berhasil diubah');
    //   authUserStore.getAuthUser();
      // opsional: emit untuk menyuruh parent refresh data
      emit('userDeleted'); // gunakan sebagai 'refresh' sederhana bila mau
    })
    .catch((e) => {
      toastr.error(e?.response?.data?.message || 'Gagal mengubah role');
    });
}

const toggleSelection = () => {
  emit('toggleSelection', props.user);
}
</script>

<template>
  <tr>
    <td><input type="checkbox" :checked="selectAll" @change="toggleSelection" /></td>
    <td>{{ index + 1 }}</td>
    <td>
      {{ user.name }}
      <p class="text-muted m-0 p-0" style="font-size: small !important;">
        {{ user.jabatan }} pada {{ user.org_name }}
      </p>
    </td>
    <td>{{ user.email }}</td>
    <!-- <td>{{ user.formatted_created_at }}</td> -->
    <td width="20%">
      <template v-if="authUserStore.user?.role === 'SUPERADMIN'">
        <select
          name="role"
          id="role"
          class="form-control"
          style="width: 200px;"
          @change="changeRole(user, $event.target.value)"
        >
          <option
            v-for="role in roles"
            :key="role.value"
            :value="role.value"
            :selected="user.role === role.name"
          >
            {{ role.name }}
          </option>
        </select>
      </template>
      <template v-else>
        {{ user.role }}
      </template>
    </td>
    <!-- <td>
      <a href="#" @click.prevent="$emit('editUser', user)">
        <i class="fa fa-edit"></i>
      </a>
      <a href="#" class="ml-2" @click.prevent="$emit('confirmUserDeletion', user.id)">
        <i class="fa fa-trash text-danger"></i>
      </a>
    </td> -->
  </tr>
</template>
