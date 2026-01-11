<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="mb-1">Hakim Event</h1>
        <p class="mb-0 text-muted text-sm">
          Pengelolaan dewan hakim untuk event terpilih
        </p>

        <p v-if="!eventId" class="text-danger text-sm mt-1 mb-0">
          <i class="fas fa-exclamation-triangle mr-1"></i>
          Event belum dipilih.
        </p>
      </div>

      <button
        class="btn btn-sm btn-primary"
        @click="openCreateModal"
        :disabled="!eventId"
      >
        <i class="fas fa-plus mr-1"></i> Tambah Hakim
      </button>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select
                v-model.number="perPage"
                class="form-control form-control-sm w-auto mr-2"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
              <span class="text-sm text-muted">entri</span>
            </div>

            <input
              v-model="search"
              class="form-control form-control-sm w-50"
              placeholder="Cari nama / NIK..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th width="50">#</th>
                <th>Nama</th>
                <th>NIK</th>
                <th>Kode</th>
                <th>Spesialisasi</th>
                <th>Sertifikasi</th>
                <th class="text-center">Status</th>
                <th width="140" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="text-center py-4">
                  Memuat data hakim event...
                </td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="8" class="text-center py-4">
                  Belum ada hakim pada event ini.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center">{{ rowNumber(index) }}</td>

                <td><strong>{{ item.user.name }}</strong></td>

                <td>
                  <span class="badge badge-light border">
                    {{ item.user.username }}
                  </span>
                </td>

                <td>{{ item.judge_code || '-' }}</td>
                <td>{{ item.specialization || '-' }}</td>
                <td>{{ item.certification_level || '-' }}</td>

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
                      class="btn btn-outline-primary btn-xs"
                      title="Edit"
                      @click="openEditModal(item)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>

                    <button
                      class="btn btn-outline-warning btn-xs"
                      title="Toggle Status"
                      @click="toggleActive(item)"
                    >
                      <i class="fas fa-user-check"></i>
                    </button>

                    <button
                      class="btn btn-outline-danger btn-xs"
                      title="Hapus"
                      @click="confirmDelete(item)"
                    >
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
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }}
              dari {{ meta.total || 0 }} hakim
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

    <!-- ================= MODAL ================= -->
    <div class="modal fade" id="judgeModal" tabindex="-1">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h6 class="modal-title mb-0">
              {{ form.id ? 'Edit Hakim Event' : 'Tambah Hakim Event' }}
            </h6>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">

            <div class="alert alert-light border py-2 px-2 mb-3 text-xs">
              User akan dibuat otomatis dengan role
              <strong>DEWAN_HAKIM</strong>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-2">
                  <label class="text-sm mb-1">Nama</label>
                  <input v-model="form.name" class="form-control form-control-sm" />
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group mb-2">
                  <label class="text-sm mb-1">NIK</label>
                  <input v-model="form.nik" class="form-control form-control-sm" />
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-2">
                  <label class="text-sm mb-1">Password</label>
                  <input v-model="form.password" type="password" class="form-control form-control-sm" />
                  <small class="text-muted text-xs">Kosongkan jika tidak diubah</small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group mb-2">
                  <label class="text-sm mb-1">Spesialisasi</label>
                  <input v-model="form.specialization" class="form-control form-control-sm" />
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-2">
                  <label class="text-sm mb-1">Sertifikasi</label>
                  <select v-model="form.certification_level" class="form-control form-control-sm">
                    <option value="">-</option>
                    <option>Kabupaten</option>
                    <option>Provinsi</option>
                    <option>Nasional</option>
                  </select>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group mb-2">
                  <label class="text-sm mb-1">Status</label>
                  <select v-model="form.is_active" class="form-control form-control-sm">
                    <option :value="true">Aktif</option>
                    <option :value="false">Nonaktif</option>
                  </select>
                </div>
              </div>
            </div>

          </div>

          <div class="modal-footer py-2">
            <button class="btn btn-light btn-sm" data-dismiss="modal">Tutup</button>
            <button class="btn btn-primary btn-sm" @click="submitForm">
              <i class="fas fa-save mr-1"></i> Simpan
            </button>
          </div>

        </div>
      </div>
    </div>

  </section>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

/* ================= STATE ================= */
const items = ref([])
const loading = ref(false)
const search = ref('')
const perPage = ref(10)

const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const form = ref(resetForm())

function resetForm () {
  return {
    id: null,
    name: '',
    nik: '',
    password: '',
    specialization: '',
    certification_level: '',
    is_active: true,
  }
}

/* ================= TABLE ================= */
const rowNumber = (index) =>
  index + 1 + (meta.value.current_page - 1) * meta.value.per_page

const fetchData = async (page = 1) => {
  if (!eventId.value) return
  loading.value = true
  try {
    const res = await axios.get('/api/v1/event-judges', {
      params: {
        event_id: eventId.value,
        page,
        per_page: perPage.value,
        search: search.value,
      },
    })

    items.value = res.data.data
    meta.value = {
      current_page: res.data.current_page,
      per_page: res.data.per_page,
      total: res.data.total,
      from: res.data.from,
      to: res.data.to,
      last_page: res.data.last_page,
    }
  } catch (e) {
    Swal.fire('Gagal', 'Gagal memuat data hakim event.', 'error')
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

/* ================= MODAL ================= */
const openCreateModal = () => {
  if (!eventId.value) return
  form.value = resetForm()
  $('#judgeModal').modal('show')
}

const openEditModal = (item) => {
  form.value = {
    id: item.id,
    name: item.user.name,
    nik: item.user.username,
    password: '',
    specialization: item.specialization,
    certification_level: item.certification_level,
    is_active: !!item.is_active,
  }
  $('#judgeModal').modal('show')
}

/* ================= CRUD ================= */
const submitForm = async () => {
  if (!eventId.value) return

  if (!form.value.name || !form.value.nik) {
    Swal.fire('Validasi', 'Nama dan NIK wajib diisi.', 'warning')
    return
  }

  try {
    const payload = {
      event_id: eventId.value,
      ...form.value,
    }

    if (!form.value.id) {
      await axios.post('/api/v1/event-judges', payload)
      Swal.fire('Berhasil', 'Hakim event berhasil ditambahkan.', 'success')
    } else {
      await axios.put(`/api/v1/event-judges/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Hakim event berhasil diperbarui.', 'success')
    }

    $('#judgeModal').modal('hide')
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan data.', 'error')
  }
}

const toggleActive = async (item) => {
  const ok = await Swal.fire({
    title: 'Ubah status?',
    text: item.user.name,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya',
  })

  if (!ok.isConfirmed) return

  await axios.patch(`/api/v1/event-judges/${item.id}/toggle-active`)
  fetchData(meta.value.current_page)
}

const confirmDelete = async (item) => {
  const ok = await Swal.fire({
    title: 'Hapus hakim?',
    text: item.user.name,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Hapus',
  })

  if (!ok.isConfirmed) return

  await axios.delete(`/api/v1/event-judges/${item.id}`)
  Swal.fire('Berhasil', 'Hakim event berhasil dihapus.', 'success')
  fetchData(1)
}

/* ================= WATCH ================= */
watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
watch(eventId, (val) => { if (val) fetchData(1) })

onMounted(() => {
  if (eventId.value) fetchData(1)
})
</script>

<style scoped>
.btn-xs {
  padding: 2px 6px !important;
  font-size: 0.65rem !important;
}
.text-xs {
  font-size: 0.75rem;
}
</style>
