<template>
    <section class="content-header">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">Cabang/Golongan per Event</h1>
                    <p class="text-sm text-muted mb-0" v-if="eventInfo">
                        Event: <strong>{{ eventInfo.nama_event || eventInfo.name }}</strong>
                        <span v-if="eventInfo.nama_aplikasi || eventInfo.app_name">
                            &mdash; {{ eventInfo.nama_aplikasi || eventInfo.app_name }}
                        </span>
                    </p>
                    <p class="text-sm text-danger mb-0" v-else>
                        Event belum dipilih. Silakan kembali ke halaman landing dan pilih event terlebih dahulu.
                    </p>
                </div>

                <div class="btn-group btn-group-sm">
                    <button
                        class="btn btn-outline-secondary"
                        @click="generateFromMaster"
                        :disabled="!eventId || isGenerating"
                        title="Generate dari Template Master Cabang"
                    >
                        <i v-if="isGenerating" class="fas fa-spinner fa-spin mr-1"></i>
                        <i v-else class="fas fa-magic mr-1"></i>
                        <span class="d-none d-md-inline">Generate dari Master</span>
                    </button>

                    <button
                        class="btn btn-primary"
                        @click="openCreateModal"
                        :disabled="!eventId"
                    >
                        <i class="fas fa-plus mr-1"></i>
                        <span class="d-none d-md-inline">Tambah</span>
                    </button>
                </div>
            </div>

        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <!-- Filter & Search -->
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <input
                                v-model="search"
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Cari kode, nama, atau kombinasi..."
                            />
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select
                                v-model="filterGroup"
                                class="form-control form-control-sm"
                            >
                                <option value="">-- Semua Group --</option>
                                <option v-for="g in groupOptions" :key="g.id" :value="g.id">
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select
                                v-model="filterCategory"
                                class="form-control form-control-sm"
                            >
                                <option value="">-- Semua Kategori --</option>
                                <option v-for="c in categoryOptions" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 text-right">
                            <span class="text-muted text-sm">
                                Total: <strong>{{ meta.total || 0 }}</strong> cabang
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <!-- <th style="width: 120px;">Kode</th> -->
                                <th>Nama Cabang/Golongan</th>
                                <th>Group</th>
                                <th>Kategori</th>
                                <th style="width: 80px;">Type</th>
                                <th style="width: 80px;">Format</th>
                                <th style="width: 80px;">Max</th>
                                <!-- <th style="width: 80px;">Urutan</th> -->
                                <th style="width: 90px;">Status</th>
                                <th style="width: 90px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!eventId">
                                <td colspan="11" class="text-center">
                                    Event belum dipilih. Silakan kembali ke halaman landing dan pilih event.
                                </td>
                            </tr>
                            <tr v-else-if="isLoading">
                                <td colspan="11" class="text-center">Memuat data...</td>
                            </tr>
                            <tr v-else-if="branches.length === 0">
                                <td colspan="11" class="text-center">
                                    Belum ada data cabang/golongan untuk event ini.
                                </td>
                            </tr>
                            <tr v-for="(branch, index) in branches" :key="branch.id">
                                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                                <!-- <td><code>{{ branch.code }}</code></td> -->
                                <td><strong>{{ branch.name }}</strong></td>
                                <td>{{ branch.group?.name || '-' }}</td>
                                <td>{{ branch.category?.name || '-' }}</td>
                                <td>{{ branch.type }}</td>
                                <td>{{ branch.format }}</td>
                                <td class="text-center">
                                    <span v-if="branch.max_age > 0">{{ branch.max_age }}</span>
                                    <span v-else>-</span>
                                </td>
                                <!-- <td class="text-center">{{ branch.order_number }}</td> -->
                                <td>
                                    <span
                                        class="badge"
                                        :class="branch.is_active ? 'badge-success' : 'badge-secondary'"
                                    >
                                        {{ branch.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            class="btn btn-warning"
                                            @click="openEditModal(branch)"
                                            title="Edit"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- <button
                                            class="btn btn-danger"
                                            @click="deleteBranch(branch)"
                                            title="Hapus"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button> -->
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer clearfix" v-if="eventId">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted text-sm">
                            Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
                            {{ meta.total || 0 }} cabang
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

        <!-- Modal Tambah/Edit -->
        <div class="modal fade" id="eventBranchModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="eventBranchModalLabel">
                            <i class="fas fa-sitemap me-2"></i>
                            {{ isEdit ? 'Edit Cabang' : 'Tambah Cabang' }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body pt-2">
                        <form @submit.prevent="submitForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Event info (read-only) -->
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Event</label>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            :value="eventInfo ? (eventInfo.nama_event || eventInfo.name) : ''"
                                            readonly
                                        />
                                    </div>

                                     <div class="form-group mb-2">
                                        <label class="mb-1">Nama Cabang/Golongan</label>
                                        <input
                                            v-model="form.name"
                                            class="form-control form-control-sm"
                                            required
                                            placeholder="Misal: Tilawah Anak-Anak Putra"
                                            readonly
                                        />
                                        <small class="text-muted">
                                            {{ form.code }}
                                        </small>
                                    </div>

                                    <!-- Group Kompetisi (FK ke master_competition_groups) -->
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Group Kompetisi</label>
                                        <select readonly
                                            v-model="form.master_competition_group_id"
                                            class="form-control form-control-sm"
                                        >
                                            <option :value="null">-- Pilih Group --</option>
                                            <option
                                                v-for="g in groupOptions"
                                                :key="g.id"
                                                :value="g.id"
                                            >
                                                {{ g.code }} — {{ g.name }}
                                            </option>
                                        </select>
                                        <!-- <small class="text-muted">
                                            Opsional, untuk mengelompokkan cabang (misal: Tilawah, Tahfizh, Fahmil).
                                        </small> -->
                                    </div>

                                    <!-- Kategori Kompetisi (FK ke master_competition_categories) -->
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Kategori Kompetisi</label>
                                        <select readonly
                                            v-model="form.master_competition_category_id"
                                            class="form-control form-control-sm"
                                        >
                                            <option :value="null">-- Pilih Kategori --</option>
                                            <option
                                                v-for="c in categoryOptions"
                                                :key="c.id"
                                                :value="c.id"
                                            >
                                                {{ c.code }} — {{ c.name }}
                                            </option>
                                        </select>
                                        <!-- <small class="text-muted">
                                            Opsional, misal: Putra, Putri, Reguler, Beregu, dsb.
                                        </small> -->
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Type</label>
                                        <select readonly
                                            v-model="form.type"
                                            class="form-control form-control-sm"
                                            required
                                        >
                                            <option value="Putra">Putra</option>
                                            <option value="Putri">Putri</option>
                                        </select>
                                    </div>

                                    <!-- <div class="form-group mb-2">
                                        <label class="mb-1">Kode</label>
                                        <input
                                            v-model="form.code"
                                            class="form-control form-control-sm"
                                            required
                                            placeholder="Misal: TILAWAH_ANAK_PUTRA"
                                        />
                                        <small class="text-muted">
                                            Kode unik per event.
                                        </small>
                                    </div> -->

                                    

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Format</label>
                                        <select readonly
                                            v-model="form.format"
                                            class="form-control form-control-sm"
                                            required
                                        >
                                            <option value="individu">Individu</option>
                                            <option value="grup">Grup</option>
                                        </select>
                                    </div>

                                   
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">Maksimal Umur</label>
                                        <input
                                            v-model.number="form.max_age"
                                            type="number"
                                            min="0"
                                            class="form-control form-control-sm"
                                            placeholder="0 = tanpa batasan"
                                        />
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Urutan Tampil</label>
                                        <input
                                            v-model.number="form.order_number"
                                            type="number"
                                            min="1"
                                            class="form-control form-control-sm"
                                            required
                                        />
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="mb-1">Deskripsi</label>
                                        <textarea
                                            v-model="form.description"
                                            rows="3"
                                            class="form-control form-control-sm"
                                            placeholder="Contoh: Tilawah golongan Anak-Anak Putra untuk event ini."
                                        ></textarea>
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

                                    <!-- Info relasi master (opsional, read-only) -->

                                    <!-- <div class="form-group mb-0">
                                        <label class="mb-1 text-sm text-muted">
                                            Info Event Golongan
                                        </label>
                                        <p class="text-xs text-muted mb-0">
                                            Kode Cabang/Golongan : {{ form.code || '-' }}<br />
                                        </p>
                                    </div> -->


                                    <div class="form-group mb-0">
                                        <label class="mb-1 text-sm text-muted">
                                            Info Template Master (opsional)
                                        </label>
                                        <p class="text-xs text-muted mb-0">
                                            Master Branch ID: {{ form.master_competition_branch_id || '-' }}<br />
                                            Group ID: {{ form.master_competition_group_id || '-' }},
                                            Category ID: {{ form.master_competition_category_id || '-' }}
                                        </p>
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
import Swal from 'sweetalert2';

const authUserStore = useAuthUserStore()

const branches = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const search = ref('')
const filterGroup = ref('')
const filterCategory = ref('')
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)
const isGenerating = ref(false)


// Event info dari storage/cookie
const eventInfo = ref(null)
const eventId = ref(null)

// form data
const form = ref({
  id: null,
  event_id: null,
  master_competition_branch_id: null,
  code: '',
  master_competition_group_id: null,
  master_competition_category_id: null,
  type: 'Putra',
  format: 'individu',
  name: '',
  max_age: 0,
  order_number: 1,
  description: '',
  is_active: true,
})

// options group & category
const groupOptions = ref([])
const categoryOptions = ref([])




const generateFromMaster = async () => {
  if (!eventId.value) {
    alert('Event belum dipilih. Silakan kembali ke landing dan pilih event terlebih dahulu.')
    return
  }

  if (!confirm('Generate ulang cabang dari template master?\nData cabang event yang ada akan diganti.')) {
    return
  }

  isGenerating.value = true

  try {
    await axios.post(`/api/v1/events/${eventId.value}/competition-branches/generate-from-master`)

    // Setelah generate, reload data table
    await fetchBranches(1)

    // alert('Cabang/golongan event berhasil digenerate dari template master.')
    Swal.fire({
        icon: 'success',
        title: 'Cabang/golongan event berhasil digenerate dari template master.',
        showConfirmButton: false,
        timer: 2000
    });
  } catch (error) {
    console.error('Gagal generate cabang dari master:', error)
    alert(error.response?.data?.message || 'Gagal generate cabang dari master.')
  } finally {
    isGenerating.value = false
  }
}


const fetchOptions = async () => {
  try {
    // anggap API master-competition-groups & categories sudah ada (pola sama)
    const [groupRes, categoryRes] = await Promise.all([
      axios.get('/api/v1/master-competition-groups', { params: { per_page: 999 } }),
      axios.get('/api/v1/master-competition-categories', { params: { per_page: 999 } }),
    ])

    const groupPaginated = groupRes.data.data
    groupOptions.value = groupPaginated.data || []

    const categoryPaginated = categoryRes.data.data
    categoryOptions.value = categoryPaginated.data || []
  } catch (error) {
    console.error('Gagal memuat master group/category:', error)
  }
}

const fetchBranches = async (page = 1) => {
  if (!eventId.value) {
    branches.value = []
    meta.value = {
      current_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0,
      last_page: 1,
    }
    return
  }

  isLoading.value = true
  try {
    const response = await axios.get('/api/v1/event-competition-branches', {
      params: {
        page,
        search: search.value,
        event_id: eventId.value,
        group_id: filterGroup.value || undefined,
        category_id: filterCategory.value || undefined,
      },
    })

    const paginated = response.data.data
    branches.value = paginated.data || []
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
      console.error('Gagal memuat cabang event:', error)
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchBranches(page)
}

const openCreateModal = () => {
  if (!eventId.value) {
    alert('Event belum dipilih. Silakan kembali ke landing dan pilih event dulu.')
    return
  }

  isEdit.value = false
  form.value = {
    id: null,
    event_id: eventId.value,
    master_competition_branch_id: null,
    code: '',
    master_competition_group_id: null,          // <- penting
    master_competition_category_id: null,       // <- penting
    type: 'Putra',
    format: 'individu',
    name: '',
    max_age: 0,
    order_number: (meta.value.total || 0) + 1,
    description: '',
    is_active: true,
  }
  $('#eventBranchModal').modal('show')
}

const openEditModal = (branch) => {
  isEdit.value = true
  form.value = {
    id: branch.id,
    event_id: branch.event_id,
    master_competition_branch_id: branch.master_competition_branch_id,
    code: branch.code,
    master_competition_group_id: branch.master_competition_group_id,   // <- penting
    master_competition_category_id: branch.master_competition_category_id, // <- penting
    type: branch.type,
    format: branch.format,
    name: branch.name,
    max_age: branch.max_age,
    order_number: branch.order_number,
    description: branch.description,
    is_active: !!branch.is_active,
  }
  $('#eventBranchModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    event_id: form.value.event_id,
    master_competition_branch_id: form.value.master_competition_branch_id,
    code: form.value.code,
    master_competition_group_id: form.value.master_competition_group_id,
    master_competition_category_id: form.value.master_competition_category_id,
    type: form.value.type,
    format: form.value.format,
    name: form.value.name,
    max_age: form.value.max_age,
    order_number: form.value.order_number,
    description: form.value.description,
    is_active: form.value.is_active,
    }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(
        `/api/v1/event-competition-branches/${form.value.id}`,
        payload
      )
    } else {
      await axios.post('/api/v1/event-competition-branches', payload)
    }

    $('#eventBranchModal').modal('hide')
    fetchBranches(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan cabang event:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan data cabang event.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteBranch = async (branch) => {
  if (!confirm(`Yakin ingin menghapus cabang event "${branch.name}"?`)) return

  try {
    await axios.delete(`/api/v1/event-competition-branches/${branch.id}`)
    fetchBranches(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus cabang event:', error)
    alert('Gagal menghapus cabang event.')
  }
}

// re-fetch jika eventId atau search berubah
watch(
  () => eventId.value,
  () => {
    fetchBranches(1)
  }
)

watch(
  () => search.value,
  useDebounceFn(() => fetchBranches(1), 400)
)

watch(
  () => [filterGroup.value, filterCategory.value],
  useDebounceFn(() => fetchBranches(1), 300),
  { deep: true }
)

onMounted(async () => {
    eventInfo.value = authUserStore.eventData
    eventId.value = authUserStore.eventData.id
    await fetchOptions()
    fetchBranches()
})
</script>

<style scoped>
.table td {
  vertical-align: middle;
}
</style>
