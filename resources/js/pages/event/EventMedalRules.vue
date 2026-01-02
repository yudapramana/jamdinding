<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Aturan Medali Event</h1>
          <p class="mb-0 text-muted text-sm">
            Mengatur aturan medali khusus untuk event aktif (override dari template).
          </p>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">
          <button
            class="btn btn-outline-secondary btn-sm mr-sm-2 mb-2 mb-sm-0"
            @click="generateFromTemplate"
            :disabled="isGenerating || !eventId"
          >
            <i v-if="isGenerating" class="fas fa-spinner fa-spin mr-1"></i>
            <i v-else class="fas fa-magic mr-1"></i>
            Generate dari Template
          </button>

          <button
            class="btn btn-primary btn-sm"
            @click="openCreateModal"
            :disabled="!eventId"
          >
            + Tambah Aturan Medali
          </button>
        </div>
      </div>

      <p v-if="!eventId" class="text-danger text-sm mt-2 mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Event belum dipilih. Silakan pilih event melalui Portal Event terlebih dahulu.
      </p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">
            <!-- LEFT -->
            <div class="d-flex align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-2">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
              <label class="mb-0 text-sm text-muted">Entri</label>
            </div>

            <!-- RIGHT -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width: 220px"
              placeholder="Cari kode / nama medali..."
            />
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th style="width:140px">Kode</th>
                <th>Nama Medali</th>
                <th style="width:90px" class="text-center">Urutan</th>
                <th style="width:90px" class="text-center">Poin</th>
                <th style="width:90px" class="text-center">Status</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">
                  Memuat aturan medali event...
                </td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center">
                  Belum ada aturan medali untuk event ini.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Generate dari Template</strong> atau
                    <strong>Tambah Aturan Medali</strong>.
                  </small>
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

        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} aturan
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
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
    <div class="modal fade" id="eventMedalRuleModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Aturan Medali Event' : 'Tambah Aturan Medali Event' }}
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
                    <label class="mb-1">Kode Medali</label>
                    <input v-model="form.medal_code" class="form-control form-control-sm" required />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Nama Medali</label>
                    <input v-model="form.medal_name" class="form-control form-control-sm" required />
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group mb-2">
                    <label class="mb-1">Urutan</label>
                    <input
                      v-model.number="form.order_number"
                      type="number"
                      min="1"
                      class="form-control form-control-sm"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="mb-1">Poin</label>
                    <input
                      v-model.number="form.point"
                      type="number"
                      min="0"
                      class="form-control form-control-sm"
                    />
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group mb-2">
                    <label class="mb-1">Status</label>
                    <select v-model="form.is_active" class="form-control form-control-sm">
                      <option :value="true">Aktif</option>
                      <option :value="false">Nonaktif</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="text-right mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="isSubmitting || !eventId"
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
import { ref, watch, onMounted, computed } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

/* ======================
 |  EVENT AKTIF
 ====================== */
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

/* ======================
 |  STATE
 ====================== */
const items = ref([])
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)
const isGenerating = ref(false)

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
  medal_code: '',
  medal_name: '',
  order_number: 1,
  point: 0,
  is_active: true,
})

/* ======================
 |  FETCH DATA
 ====================== */
const fetchItems = async (page = 1) => {
  if (!eventId.value) return

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-medal-rules', {
      params: {
        event_id: eventId.value,
        page,
        per_page: perPage.value,
        search: search.value,
      },
    })

    const paginated = res.data.data
    items.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat event_medal_rules:', error)
    Swal.fire('Gagal', 'Gagal memuat aturan medali event.', 'error')
  } finally {
    isLoading.value = false
  }
}

/* ======================
 |  CRUD
 ====================== */
const openCreateModal = () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event terlebih dahulu.', 'warning')
    return
  }

  isEdit.value = false
  form.value = {
    id: null,
    medal_code: '',
    medal_name: '',
    order_number: (meta.value.total || 0) + 1,
    point: 0,
    is_active: true,
  }
  $('#eventMedalRuleModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#eventMedalRuleModal').modal('show')
}

const submitForm = async () => {
  if (!eventId.value) return

  isSubmitting.value = true
  try {
    const payload = {
      event_id: eventId.value,
      medal_code: form.value.medal_code,
      medal_name: form.value.medal_name,
      order_number: form.value.order_number,
      point: form.value.point,
      is_active: form.value.is_active,
    }

    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-medal-rules/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Aturan medali event diperbarui.', 'success')
    } else {
      await axios.post('/api/v1/event-medal-rules', payload)
      Swal.fire('Berhasil', 'Aturan medali event ditambahkan.', 'success')
    }

    $('#eventMedalRuleModal').modal('hide')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan event_medal_rule:', error)
    Swal.fire('Gagal', 'Gagal menyimpan aturan medali event.', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus Aturan Medali?',
    text: `Yakin ingin menghapus "${item.medal_name}" dari event ini?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/event-medal-rules/${item.id}`)
    Swal.fire('Terhapus', 'Aturan medali event berhasil dihapus.', 'success')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus event_medal_rule:', error)
    Swal.fire('Gagal', 'Gagal menghapus aturan medali event.', 'error')
  }
}

/* ======================
 |  GENERATE
 ====================== */
const generateFromTemplate = async () => {
  if (!eventId.value) return

  const result = await Swal.fire({
    title: 'Generate dari Template?',
    text: 'Aturan medali event akan disalin dari Master Aturan Medali.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Generate',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  isGenerating.value = true
  try {
    await axios.post('/api/v1/event-medal-rules/generate-from-template', {
      event_id: eventId.value,
    })

    Swal.fire('Berhasil', 'Aturan medali event berhasil digenerate.', 'success')
    fetchItems(1)
  } catch (error) {
    console.error('Gagal generate event_medal_rules:', error)
    Swal.fire('Gagal', 'Gagal generate aturan medali event.', 'error')
  } finally {
    isGenerating.value = false
  }
}

/* ======================
 |  WATCHERS
 ====================== */
watch(
  () => search.value,
  useDebounceFn(() => fetchItems(1), 400)
)

watch(
  () => perPage.value,
  () => fetchItems(1)
)

watch(
  () => eventId.value,
  (val) => {
    if (val) fetchItems(1)
  }
)

onMounted(() => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event terlebih dahulu.',
      'info'
    )
  } else {
    fetchItems()
  }
})
</script>
