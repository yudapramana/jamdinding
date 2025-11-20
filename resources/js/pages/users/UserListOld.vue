<template>
      <section class="content-header">
        <div class="container-fluid">
          <h1 class="mb-2">Daftar Pengguna</h1>
        </div>
      </section>
  
      <section class="content">
        <div class="container-fluid">
            <div class="card">
            <div class="card-header">
                <input
                v-model="search"
                type="text"
                class="form-control"
                placeholder="Cari nama, email, NIP, atau unit kerja..."
                />
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-hover text-sm">
                <thead class="thead-light">
                    <tr>
                    <th style="width: 40px;">#</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>NIP</th>
                    <th>Unit Kerja</th>
                    <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading">
                    <td colspan="6" class="text-center">Memuat data...</td>
                    </tr>
                    <tr v-else-if="users.length === 0">
                    <td colspan="6" class="text-center">Tidak ada data pengguna ditemukan.</td>
                    </tr>
                    <tr v-for="(user, index) in users" :key="user.id">
                    <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                    <td><strong>{{ user.employee?.full_name || user.name }}</strong></td>
                    <td>{{ user.employee.job_title }}</td>
                    <td>{{ user.employee?.nip || '-' }}</td>
                    <td>{{ user.employee?.work_unit?.unit_name || '-' }}</td>
                    <td>{{ user.employee?.employment_status || '-' }}</td>
                    </tr>
                </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} pengguna
                </div>
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                    <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page - 1)">«</a>
                    </li>
                    <li class="page-item disabled">
                    <span class="page-link">
                        Halaman {{ meta.current_page }} / {{ meta.last_page }}
                    </span>
                    </li>
                    <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                    <a class="page-link" href="#" @click.prevent="fetchUsers(meta.current_page + 1)">»</a>
                    </li>
                </ul>
                </div>
            </div>
            </div>
        </div>
      </section>
  </template>
  
  <script setup>
  import { ref, watch } from 'vue'
  import { useDebounceFn } from '@vueuse/core'
  import axios from 'axios'
  
  const users = ref([])
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
  
  const fetchUsers = async (page = 1) => {
    isLoading.value = true
    try {
      const response = await axios.get('/api/users', {
        params: {
          page,
          search: search.value,
        },
      })
  
      users.value = response.data.data
      meta.value = {
        ...meta.value,
        ...response.data.meta,
        ...response.data,
      }
    } catch (error) {
      console.error('Gagal memuat data pengguna:', error)
    } finally {
      isLoading.value = false
    }
  }
  
  watch(search, useDebounceFn(() => fetchUsers(1), 400))
  
  fetchUsers()
  </script>
  