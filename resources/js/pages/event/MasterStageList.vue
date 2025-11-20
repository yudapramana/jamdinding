<template>
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-2">Template Tahapan (Master)</h1>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                    + Tambah Template Tahapan
                </button>
            </div>
            <p class="text-muted text-sm mb-0">
                Master tahapan ini akan dipakai sebagai template saat generate tahapan pada setiap event.
            </p>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <input
                        v-model="search"
                        type="text"
                        class="form-control form-control-sm w-50"
                        placeholder="Cari nama tahapan..."
                    />
                    <span class="text-muted text-sm">
                        Total: <strong>{{ meta.total || 0 }}</strong> tahapan
                    </span>
                </div>

                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <!-- <th style="width: 80px;">Urutan</th> -->
                                <th>Nama Tahapan</th>
                                <th style="width: 80px;">Hari</th>
                                <th>Deskripsi</th>
                                <th style="width: 90px;">Status</th>
                                <th style="width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading">
                                <td colspan="7" class="text-center">Memuat data...</td>
                            </tr>
                            <tr v-else-if="stages.length === 0">
                                <td colspan="7" class="text-center">
                                    Belum ada template tahapan.
                                    <br />
                                    <small class="text-muted">
                                        Klik <strong>Tambah Template Tahapan</strong> untuk menambahkan.
                                    </small>
                                </td>
                            </tr>
                            <tr v-for="(stage, index) in stages" :key="stage.id">
                                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                                <!-- <td class="text-center">{{ stage.order_number }}</td> -->
                                <td><strong>{{ stage.name }}</strong></td>
                                <td class="text-center">{{ stage.days }}</td>
                                <td>{{ stage.description || '-' }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="stage.is_active ? 'badge-success' : 'badge-secondary'"
                                    >
                                        {{ stage.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                    <button
                                        class="btn btn-warning"
                                        @click="openEditModal(stage)"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button
                                        class="btn btn-danger"
                                        @click="deleteStage(stage)"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted text-sm">
                            Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
                            {{ meta.total || 0 }} tahapan
                        </div>
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                                <a
                                    class="page-link"
                                    href="#"
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
                            <li
                                class="page-item"
                                :class="{ disabled: meta.current_page === meta.last_page }"
                            >
                                <a
                                    class="page-link"
                                    href="#"
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

        <!-- Modal Tambah / Edit Master Stage -->
        <div class="modal fade" id="masterStageModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="masterStageModalLabel">
                            <i class="fas fa-layer-group me-2"></i>
                            {{ isEdit ? 'Edit Template Tahapan' : 'Tambah Template Tahapan' }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body pt-2">
                        <form @submit.prevent="submitForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Nama Tahapan</label>
                                        <input
                                            v-model="form.name"
                                            class="form-control form-control-sm"
                                            required
                                        />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Urutan Tahapan</label>
                                        <input
                                            v-model.number="form.order_number"
                                            type="number"
                                            min="1"
                                            class="form-control form-control-sm"
                                            required
                                        />
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Jumlah Hari</label>
                                        <input
                                            v-model.number="form.days"
                                            type="number"
                                            min="1"
                                            class="form-control form-control-sm"
                                            required
                                        />
                                        <small class="text-muted">
                                            Digunakan sebagai dasar generate tanggal otomatis pada event.
                                        </small>
                                    </div>
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
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Deskripsi</label>
                                        <textarea
                                            v-model="form.description"
                                            rows="5"
                                            class="form-control form-control-sm"
                                            placeholder="Contoh: Tahap verifikasi awal kelengkapan berkas peserta."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <button
                                    type="submit"
                                    class="btn btn-sm btn-primary"
                                    :disabled="isSubmitting"
                                >
                                    <i
                                        v-if="isSubmitting"
                                        class="fas fa-spinner fa-spin me-1"
                                    ></i>
                                    <i v-else class="fas fa-save me-1"></i>
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
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

const stages = ref([])
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
const isEdit = ref(false)
const isSubmitting = ref(false)

const form = ref({
  id: null,
  name: '',
  order_number: 1,
  days: 1,
  description: '',
  is_active: true,
})

const fetchStages = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/v1/stages', {
      params: {
        page,
        search: search.value,
      },
    })

    const paginated = response.data.data // paginator ada di key data
    stages.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    if (error.response && error.response.status === 401) {
      console.warn('Unauthorized. Logging out...')
      authUserStore.logout()
    } else {
      console.error('Gagal memuat master stages:', error)
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchStages(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    name: '',
    order_number: (meta.value.total || 0) + 1,
    days: 1,
    description: '',
    is_active: true,
  }
  $('#masterStageModal').modal('show')
}

const openEditModal = (stage) => {
  isEdit.value = true
  form.value = {
    id: stage.id,
    name: stage.name,
    order_number: stage.order_number,
    days: stage.days,
    description: stage.description,
    is_active: !!stage.is_active,
  }
  $('#masterStageModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    name: form.value.name,
    order_number: form.value.order_number,
    days: form.value.days,
    description: form.value.description,
    is_active: form.value.is_active,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/stages/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/stages', payload)
    }

    $('#masterStageModal').modal('hide')
    fetchStages(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan master stage:', error)
    alert('Gagal menyimpan data tahapan.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteStage = async (stage) => {
  if (!confirm(`Yakin ingin menghapus template tahapan "${stage.name}"?`)) return

  try {
    await axios.delete(`/api/v1/stages/${stage.id}`)
    fetchStages(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus master stage:', error)
    alert('Gagal menghapus tahapan.')
  }
}

// debounce untuk search
watch(
  () => search.value,
  useDebounceFn(() => fetchStages(1), 400)
)

onMounted(() => {
  fetchStages()
})
</script>
