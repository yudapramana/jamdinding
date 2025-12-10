<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Master Golongan Cabang (Master Groups)</h1>
          <p class="mb-0 text-muted text-sm">
            Digunakan untuk membuat kombinasi Cabang + Golongan sebagai template event MTQ.
            Contoh: Hafalan Al Qur'an - 10 Juz.
          </p>
        </div>

        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Master Group
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- CARD -->
      <div class="card">
        <div class="card-header">

          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">
            
            <!-- Left: perPage -->
            <div class="d-flex align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-2">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <label class="mb-0 text-sm text-muted">Entri</label>
            </div>

            <!-- Right: search -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              placeholder="Cari cabang / golongan..."
              style="min-width: 220px"
            />

          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th width="40">#</th>
                <th>Cabang</th>
                <th>Golongan</th>
                <th>Full Name</th>
                <th width="80" class="text-center">Max Usia</th>
                <th width="70" class="text-center">Tim?</th>
                <th width="80" class="text-center">Urutan</th>
                <th width="100" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="8" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="8" class="text-center">
                  Belum ada master group.
                  <br><small class="text-muted">Klik Tambah Master Group untuk membuat template.</small>
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>{{ item.branch_name }}</td>
                <td>{{ item.group_name }}</td>
                <td><strong>{{ item.full_name }}</strong></td>
                <td class="text-center">{{ item.max_age }}</td>
                <td class="text-center">
                  <span class="badge" :class="item.is_team ? 'badge-success' : 'badge-secondary'">
                    {{ item.is_team ? 'Ya' : 'Tidak' }}
                  </span>
                </td>
                <td class="text-center">{{ item.order_number || '-' }}</td>
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
        <div class="card-footer d-flex justify-content-between align-items-center py-2">
          <div class="text-muted text-sm">
            Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} data
          </div>

          <ul class="pagination pagination-sm m-0">
            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
              <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
            </li>

            <li class="page-item disabled">
              <span class="page-link">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
            </li>

            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
              <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">»</a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- MODAL -->
    <div class="modal fade" id="masterGroupModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h5 class="modal-title">
              <i class="fas fa-layer-group mr-1"></i>
              {{ isEdit ? 'Edit Master Group' : 'Tambah Master Group' }}
            </h5>
            <button class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">

              <div class="row">
                <div class="col-md-6">

                  <!-- Branch -->
                  <div class="form-group mb-2">
                    <label>Cabang</label>
                    <select v-model="form.branch_id" class="form-control form-control-sm" required>
                      <option disabled value="">-- pilih cabang --</option>
                      <option v-for="b in branches" :value="b.id">{{ b.name }}</option>
                    </select>
                  </div>

                  <!-- Group -->
                  <div class="form-group mb-2">
                    <label>Golongan</label>
                    <select v-model="form.group_id" class="form-control form-control-sm" required>
                      <option disabled value="">-- pilih golongan --</option>
                      <option v-for="g in groups" :value="g.id">{{ g.name }}</option>
                    </select>
                  </div>

                  <!-- Max Age -->
                  <div class="form-group mb-2">
                    <label>Maksimal Usia</label>
                    <input
                      v-model.number="form.max_age"
                      type="number"
                      class="form-control form-control-sm"
                      min="0"
                      required
                    />
                  </div>

                </div>

                <div class="col-md-6">

                  <div class="form-group mb-2">
                    <label>Urutan</label>
                    <input v-model.number="form.order_number" type="number" class="form-control form-control-sm" />
                  </div>

                  <div class="form-group mb-2">
                    <label>Tim?</label>
                    <select v-model="form.is_team" class="form-control form-control-sm">
                      <option :value="false">Tidak</option>
                      <option :value="true">Ya (Tim)</option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label>Status</label>
                    <select v-model="form.is_active" class="form-control form-control-sm">
                      <option :value="true">Aktif</option>
                      <option :value="false">Nonaktif</option>
                    </select>
                  </div>

                </div>
              </div>

              <div class="text-right mt-3">
                <button class="btn btn-primary btn-sm" :disabled="isSubmitting">
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
import { ref, watch, onMounted } from "vue"
import { useDebounceFn } from "@vueuse/core"
import axios from "axios"
import Swal from "sweetalert2"

const items = ref([])
const branches = ref([])
const groups = ref([])

const search = ref("")
const perPage = ref(10)

const isEdit = ref(false)
const isSubmitting = ref(false)
const isLoading = ref(false)

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const form = ref({
  id: null,
  branch_id: "",
  group_id: "",
  max_age: 0,
  is_team: false,
  order_number: null,
  is_active: true,
})

// fetch branches + groups
const loadOptions = async () => {
  const res1 = await axios.get("/api/v1/branches?simple=1")
  branches.value = res1.data.data || []

  const res2 = await axios.get("/api/v1/groups?simple=1")
  groups.value = res2.data.data || []
}

const fetchItems = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get("/api/v1/master-groups", {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
      },
    })

    const paginated = res.data.data
    items.value = paginated.data || []
    meta.value = paginated

  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    branch_id: "",
    group_id: "",
    max_age: 0,
    is_team: false,
    order_number: (meta.value.total || 0) + 1,
    is_active: true,
  }
  $("#masterGroupModal").modal("show")
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    branch_id: item.branch_id,
    group_id: item.group_id,
    max_age: item.max_age,
    is_team: !!item.is_team,
    order_number: item.order_number,
    is_active: !!item.is_active,
  }
  $("#masterGroupModal").modal("show")
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = { ...form.value }

  try {
    if (isEdit.value) {
      await axios.put(`/api/v1/master-groups/${form.value.id}`, payload)
    } else {
      await axios.post(`/api/v1/master-groups`, payload)
    }

    $("#masterGroupModal").modal("hide")
    fetchItems(meta.value.current_page)

    Swal.fire("Berhasil", "Data berhasil disimpan", "success")

  } catch (e) {
    console.error(e)
    Swal.fire("Gagal", "Tidak dapat menyimpan data", "error")
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  const confirm = await Swal.fire({
    title: "Hapus Data?",
    text: `Hapus master group: ${item.full_name}?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, hapus",
    cancelButtonText: "Batal",
  })

  if (!confirm.isConfirmed) return

  try {
    await axios.delete(`/api/v1/master-groups/${item.id}`)
    fetchItems(meta.value.current_page)

    Swal.fire("Berhasil", "Data berhasil dihapus", "success")

  } catch (e) {
    Swal.fire("Gagal", "Tidak dapat menghapus data", "error")
  }
}

watch(() => search.value, useDebounceFn(() => fetchItems(1), 400))
watch(() => perPage.value, () => fetchItems(1))

onMounted(() => {
  loadOptions()
  fetchItems()
})
</script>
