<template>
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                <h1 class="mb-1">Master Komponen Penilaian (List Fields)</h1>
                <p class="mb-0 text-muted text-sm">
                    Digunakan untuk mendefinisikan komponen penilaian, misalnya: Lagu, Suara, Tajwid, Adab, Fashahah, dll.
                </p>
                </div>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                + Tambah Komponen
                </button>
            </div>
        </div>
    </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">

            <!-- LEFT: perPage + Status -->
            <div class="d-flex flex-wrap align-items-center">

              <!-- perPage -->
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
              style="min-width: 200px"
              placeholder="Cari Kode / Nama Komponen..."
            />

          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 120px;">Kode</th>
                <th>Nama Komponen</th>
                <th>Deskripsi</th>
                <th style="width: 80px;" class="text-center">Urutan</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data komponen...</td>
              </tr>
              <tr v-else-if="listFields.length === 0">
                <td colspan="6" class="text-center">
                  Belum ada data komponen penilaian.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Tambah Komponen</strong> untuk menambahkan.
                  </small>
                </td>
              </tr>
              <tr
                v-for="(item, index) in listFields"
                :key="item.id"
              >
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td><code>{{ item.code || '-' }}</code></td>
                <td><strong>{{ item.name }}</strong></td>
                <td>{{ item.description || '-' }}</td>
                <td class="text-center">{{ item.order_number || '-' }}</td>
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
                      @click="deleteListField(item)"
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
              {{ meta.total || 0 }} komponen
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

    <!-- MODAL: TAMBAH / EDIT LIST FIELD -->
    <div class="modal fade" id="listFieldModal" tabindex="-1" role="dialog" aria-labelledby="listFieldModalLabel">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="listFieldModalLabel">
              <i class="fas fa-list-alt mr-1"></i>
              {{ isEdit ? 'Edit Komponen Penilaian' : 'Tambah Komponen Penilaian' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <form @submit.prevent="submitForm">
              <div class="row">
                <div class="col-md-5">
                  <div class="form-group mb-2">
                    <label class="mb-1">Kode (opsional)</label>
                    <input
                      v-model="form.code"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Contoh: LAGU, SUARA, TAJWID"
                    />
                    <small class="text-muted">
                      Kode singkat untuk memudahkan mapping ke konfigurasi lain.
                    </small>
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Komponen</label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Urutan Tampil</label>
                    <input
                      v-model.number="form.order_number"
                      type="number"
                      min="1"
                      class="form-control form-control-sm"
                    />
                    <small class="text-muted">
                      Urutan komponen saat tampil di form penilaian (boleh dikosongkan).
                    </small>
                  </div>
                </div>

                <div class="col-md-7">
                  <div class="form-group mb-2">
                    <label class="mb-1">Deskripsi</label>
                    <textarea
                      v-model="form.description"
                      rows="5"
                      class="form-control form-control-sm"
                      placeholder="Contoh: Penilaian aspek tajwid meliputi makhraj, sifat huruf, panjang pendek, dan hukum bacaan."
                    ></textarea>
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
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

const listFields = ref([])
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
const perPage = ref(10)


const form = ref({
  id: null,
  code: '',
  name: '',
  description: '',
  order_number: null,
})

const fetchListFields = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/v1/list-fields', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
      },
    })

    const paginated = response.data.data // paginator Laravel di key data
    listFields.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat list_fields:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchListFields(page)
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    code: '',
    name: '',
    description: '',
    order_number: (meta.value.total || 0) + 1,
  }
  $('#listFieldModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    code: item.code,
    name: item.name,
    description: item.description,
    order_number: item.order_number,
  }
  $('#listFieldModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    code: form.value.code,
    name: form.value.name,
    description: form.value.description,
    order_number: form.value.order_number,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/list-fields/${form.value.id}`, payload)
    } else {
      await axios.post('/api/v1/list-fields', payload)
    }

    $('#listFieldModal').modal('hide')
    fetchListFields(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan list_field:', error)
    alert('Gagal menyimpan data komponen.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteListField = async (item) => {
  if (!confirm(`Yakin ingin menghapus komponen "${item.name}"?`)) return

  try {
    await axios.delete(`/api/v1/list-fields/${item.id}`)
    fetchListFields(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus list_field:', error)
    alert('Gagal menghapus komponen.')
  }
}

// debounce untuk search
watch(
  () => search.value,
  useDebounceFn(() => fetchListFields(1), 400)
)

watch(
  () => perPage.value,
  () => {
    fetchListFields(1)
  }
)

onMounted(() => {
  fetchListFields()
})
</script>
