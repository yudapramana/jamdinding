<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Master Cabang MTQ (Master Branches)</h1>
          <p class="mb-0 text-muted text-sm">
            Digunakan sebagai master cabang global yang akan dipakai sebagai template di berbagai event.
          </p>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Master Cabang
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">
            <!-- LEFT: perPage -->
            <div class="d-flex flex-wrap align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-2">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <label class="mb-0 mr-1 text-sm text-muted">Entri</label>
            </div>

            <!-- RIGHT: Search -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width: 220px"
              placeholder="Cari nama cabang / full name..."
            />
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 160px;">Cabang</th>
                <th>Branch Name</th>
                <th>Full Name</th>
                <th style="width: 80px;" class="text-center">Urutan</th>
                <th style="width: 90px;" class="text-center">Status</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data master cabang...</td>
              </tr>
              <tr v-else-if="masterBranches.length === 0">
                <td colspan="7" class="text-center">
                  Belum ada data master cabang.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Tambah Master Cabang</strong> untuk menambahkan.
                  </small>
                </td>
              </tr>
              <tr
                v-for="(item, index) in masterBranches"
                :key="item.id"
              >
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>
                  <div>
                    <strong>{{ item.branch_name }}</strong>
                    <br />
                    <small class="text-muted">
                      ID: {{ item.branch_id }}
                    </small>
                  </div>
                </td>
                <td>{{ item.branch_name }}</td>
                <td>{{ item.full_name }}</td>
                <td class="text-center">{{ item.order_number || '-' }}</td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="item.is_active ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-warning"
                      @click="openEditModal(item)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-danger"
                      @click="deleteMasterBranch(item)"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} master cabang
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a
                  href="#"
                  class="page-link"
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
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a
                  href="#"
                  class="page-link"
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

    <!-- MODAL: TAMBAH / EDIT MASTER BRANCH -->
    <div
      class="modal fade"
      id="masterBranchModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="masterBranchModalLabel"
    >
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="masterBranchModalLabel">
              <i class="fas fa-sitemap mr-1"></i>
              {{ isEdit ? 'Edit Master Cabang' : 'Tambah Master Cabang' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <form @submit.prevent="submitForm">
              <div class="row">
                <div class="col-md-6">
                  <!-- branch_id -->
                  <div class="form-group mb-2">
                    <label class="mb-1">Cabang (dari tabel branches)</label>
                    <select
                      v-model.number="form.branch_id"
                      class="form-control form-control-sm"
                      required
                      @change="syncBranchName"
                    >
                      <option :value="null" disabled>Pilih Cabang...</option>
                      <option
                        v-for="b in branchOptions"
                        :key="b.id"
                        :value="b.id"
                      >
                        {{ b.name }}
                      </option>
                    </select>
                    <small class="text-muted">
                      Pilih cabang dasar yang akan dijadikan master.
                    </small>
                  </div>

                  <!-- branch_name -->
                  <div class="form-group mb-2">
                    <label class="mb-1">Branch Name</label>
                    <input
                      v-model="form.branch_name"
                      type="text"
                      class="form-control form-control-sm"
                      required
                    />
                    <small class="text-muted">
                      Nama pendek/teknis cabang. Biasanya sama dengan nama cabang di atas.
                    </small>
                  </div>

                  <!-- order_number -->
                  <div class="form-group mb-2">
                    <label class="mb-1">Urutan Tampil</label>
                    <input
                      v-model.number="form.order_number"
                      type="number"
                      min="1"
                      class="form-control form-control-sm"
                    />
                    <small class="text-muted">
                      Urutan master cabang saat ditampilkan (boleh dikosongkan).
                    </small>
                  </div>

                  <!-- status -->
                  <div class="form-group mb-2">
                    <label class="mb-1">Status</label>
                    <select
                      v-model="form.is_active"
                      class="form-control form-control-sm"
                    >
                      <option :value="true">Aktif</option>
                      <option :value="false">Nonaktif</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-6">
                  <!-- full_name -->
                  <div class="form-group mb-2">
                    <label class="mb-1">Full Name</label>
                    <input
                      v-model="form.full_name"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Hafalan Al Qur'an 10 Juz"
                      required
                    />
                    <small class="text-muted">
                      Nama lengkap cabang yang akan tampil di publik atau dokumen resmi.
                    </small>
                  </div>

                  <div class="alert alert-info py-2 px-3 mt-3 text-sm">
                    <strong>Catatan:</strong> Data master cabang ini dapat digunakan sebagai
                    template konfigurasi cabang pada berbagai event, tanpa perlu input ulang.
                  </div>
                </div>
              </div>

              <div class="text-right mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="isSubmitting"
                >
                  <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
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
import { ref, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

const masterBranches = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)

// options cabang dari tabel branches
const branchOptions = ref([])

const form = ref({
  id: null,
  branch_id: null,
  branch_name: '',
  full_name: '',
  order_number: null,
  is_active: true,
})

const fetchMasterBranches = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/v1/master-branches', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
      },
    })

    const paginated = response.data.data
    masterBranches.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat master_branches:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    } else {
      Swal.fire('Gagal', 'Gagal memuat data master cabang.', 'error')
    }
  } finally {
    isLoading.value = false
  }
}

const fetchBranchOptions = async () => {
  try {
    const response = await axios.get('/api/v1/branches', {
      params: {
        per_page: 1000,
      },
    })
    const paginated = response.data.data
    branchOptions.value = paginated.data || []
  } catch (error) {
    console.error('Gagal memuat options branches:', error)
    Swal.fire('Gagal', 'Gagal memuat daftar cabang.', 'error')
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchMasterBranches(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    branch_id: null,
    branch_name: '',
    full_name: '',
    order_number: (meta.value.total || 0) + 1,
    is_active: true,
  }
  $('#masterBranchModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    branch_id: item.branch_id,
    branch_name: item.branch_name,
    full_name: item.full_name,
    order_number: item.order_number,
    is_active: !!item.is_active,
  }
  $('#masterBranchModal').modal('show')
}

// sinkronisasi branch_name ketika pilih branch
const syncBranchName = () => {
  const selected = branchOptions.value.find(b => b.id === form.value.branch_id)
  if (selected && !isEdit.value) {
    form.value.branch_name = selected.name || ''
    if (!form.value.full_name) {
      form.value.full_name = selected.name || ''
    }
  }
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    branch_id: form.value.branch_id,
    branch_name: form.value.branch_name,
    full_name: form.value.full_name,
    order_number: form.value.order_number,
    is_active: form.value.is_active,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/master-branches/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Master cabang berhasil diperbarui.', 'success')
    } else {
      await axios.post('/api/v1/master-branches', payload)
      Swal.fire('Berhasil', 'Master cabang berhasil ditambahkan.', 'success')
    }

    $('#masterBranchModal').modal('hide')
    fetchMasterBranches(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan master_branch:', error)
    Swal.fire('Gagal', 'Gagal menyimpan data master cabang.', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const deleteMasterBranch = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus Master Cabang?',
    text: `Yakin ingin menghapus master cabang "${item.full_name}"? Tindakan ini tidak bisa dibatalkan.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/master-branches/${item.id}`)
    Swal.fire('Terhapus', 'Master cabang berhasil dihapus.', 'success')
    fetchMasterBranches(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus master_branch:', error)
    Swal.fire('Gagal', 'Gagal menghapus master cabang.', 'error')
  }
}

// debounce untuk search
watch(
  () => search.value,
  useDebounceFn(() => fetchMasterBranches(1), 400)
)

// perPage change
watch(
  () => perPage.value,
  () => {
    fetchMasterBranches(1)
  }
)

onMounted(() => {
  fetchMasterBranches()
  fetchBranchOptions()
})
</script>
