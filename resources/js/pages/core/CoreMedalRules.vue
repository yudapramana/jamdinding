<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Master Aturan Medali</h1>
          <p class="mb-0 text-muted text-sm">
            Digunakan untuk mendefinisikan jenis medali dan poin (Juara, Harapan, dll).
          </p>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Aturan Medali
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- HEADER -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center w-100">
            <div>
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm d-inline-block w-auto">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
              <span class="text-sm text-muted ml-1">Entri</span>
            </div>

            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width: 220px"
              placeholder="Cari kode / nama medali..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 120px;">Kode</th>
                <th>Nama Medali</th>
                <th style="width: 90px;" class="text-center">Urutan</th>
                <th style="width: 90px;" class="text-center">Poin</th>
                <th style="width: 80px;" class="text-center">Status</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center text-muted">
                  Belum ada aturan medali.
                </td>
              </tr>
              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td><code>{{ item.medal_code }}</code></td>
                <td><strong>{{ item.medal_name }}</strong></td>
                <td class="text-center">{{ item.order_number }}</td>
                <td class="text-center">{{ item.point }}</td>
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
                    <button class="btn btn-warning" @click="openEditModal(item)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger" @click="deleteItem(item)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total || 0 }} data
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>

    <!-- MODAL -->
    <div class="modal fade" id="medalRuleModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Aturan Medali' : 'Tambah Aturan Medali' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Kode Medali</label>
                    <input v-model="form.medal_code" class="form-control form-control-sm" required />
                  </div>
                  <div class="form-group">
                    <label>Nama Medali</label>
                    <input v-model="form.medal_name" class="form-control form-control-sm" required />
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Urutan</label>
                    <input v-model.number="form.order_number" type="number" min="1" class="form-control form-control-sm" />
                  </div>
                  <div class="form-group">
                    <label>Poin</label>
                    <input v-model.number="form.point" type="number" min="0" class="form-control form-control-sm" />
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Status</label>
                    <select v-model="form.is_active" class="form-control form-control-sm">
                      <option :value="true">Aktif</option>
                      <option :value="false">Nonaktif</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="text-right">
                <button class="btn btn-primary btn-sm" :disabled="isSubmitting">
                  <i class="fas fa-save mr-1"></i> Simpan
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

const items = ref([])
const meta = ref({})
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)

const form = ref({
  id: null,
  medal_code: '',
  medal_name: '',
  order_number: 1,
  point: 0,
  is_active: true,
})

const fetchData = async (page = 1) => {
  isLoading.value = true
  const res = await axios.get('/api/v1/medal-rules', {
    params: { page, per_page: perPage.value, search: search.value },
  })
  items.value = res.data.data.data
  meta.value = res.data.data
  isLoading.value = false
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = { id: null, medal_code: '', medal_name: '', order_number: 1, point: 0, is_active: true }
  $('#medalRuleModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#medalRuleModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true
  if (isEdit.value) {
    await axios.put(`/api/v1/medal-rules/${form.value.id}`, form.value)
  } else {
    await axios.post('/api/v1/medal-rules', form.value)
  }
  $('#medalRuleModal').modal('hide')
  fetchData(meta.value.current_page)
  isSubmitting.value = false
}

const deleteItem = async (item) => {
  if (!confirm(`Hapus aturan "${item.medal_name}"?`)) return
  await axios.delete(`/api/v1/medal-rules/${item.id}`)
  fetchData(meta.value.current_page)
}

const changePage = (page) => fetchData(page)

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
onMounted(fetchData)
</script>
