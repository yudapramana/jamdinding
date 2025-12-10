<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Master Komponen Penilaian per Master Group</h1>
          <p class="mb-0 text-muted text-sm">
            Mengatur komponen penilaian (Lagu, Suara, Tajwid, dll.) beserta bobot & nilai maksimum
            untuk setiap Master Group (Cabang + Golongan).
          </p>
        </div>
        <!-- Optional: tombol global (kalau mau tetap ada) -->
        <!-- <button class="btn btn-primary btn-sm" @click="openCreateModal()">
          + Tambah Komponen ke Master Group
        </button> -->
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <div class="card">
        <div class="card-header">
          <div class="d-flex flex-wrap justify-content-between align-items-center w-100">
            <!-- LEFT: perPage -->
            <div class="d-flex align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-2">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <label class="mb-0 text-sm text-muted">Master Group / halaman</label>
            </div>

            <!-- RIGHT: search -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width: 220px"
              placeholder="Cari nama master group..."
            />
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;"></th>
                <th style="width: 40px;">#</th>
                <th>Master Group</th>
                <th>Cabang</th>
                <th>Golongan</th>
                <th style="width: 80px;" class="text-center">Maks Usia</th>
                <th style="width: 80px;" class="text-center">Tim?</th>
                <th style="width: 80px;" class="text-center">Urutan</th>
                <th style="width: 80px;" class="text-center">Bidang</th>
                <!-- <th style="width: 100px;" class="text-center">Aksi</th> -->
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="9" class="text-center">Memuat data master group...</td>
              </tr>

              <tr v-else-if="groups.length === 0">
                <td colspan="9" class="text-center">
                  Belum ada master group.
                  <br />
                  <small class="text-muted">
                    Silakan buat Master Group terlebih dahulu.
                  </small>
                </td>
              </tr>

              <!-- ✅ Setiap group punya 2 row: main + detail -->
              <template v-else v-for="(group, idx) in groups" :key="group.id">
                <!-- ROW UTAMA -->
                <tr>
                  <td class="text-center align-middle">
                    <button
                      class="btn btn-xs btn-outline-secondary"
                      @click="toggleExpand(group.id)"
                    >
                      <i
                        class="fas"
                        :class="isExpanded(group.id) ? 'fa-chevron-up' : 'fa-chevron-down'"
                      ></i>
                    </button>
                  </td>
                  <td>{{ idx + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                  <td><strong>{{ group.full_name }}</strong></td>
                  <td>{{ group.branch_name }}</td>
                  <td>{{ group.group_name }}</td>
                  <td class="text-center">{{ group.max_age ?? '-' }}</td>
                  <td class="text-center">
                    <span
                      class="badge"
                      :class="group.is_team ? 'badge-success' : 'badge-secondary'"
                    >
                      {{ group.is_team ? 'Tim' : 'Individu' }}
                    </span>
                  </td>
                  <td class="text-center">{{ group.order_number ?? '-' }}</td>
                  <td>{{ (group.field_components || group.fieldComponents || []).length }}</td>


                  <!-- <td class="text-center">
                    <button
                      type="button"
                      class="btn btn-xs btn-primary"
                      @click="openCreateModal(group)"
                    >
                      + Komponen
                    </button>
                  </td> -->
                </tr>

                <!-- ROW DETAIL (EXPAND) -->
                <tr v-if="isExpanded(group.id)">
                  <td></td>
                  <td colspan="8" class="bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="text-sm">
                        <strong>Komponen Penilaian:</strong>
                        <span class="text-muted">
                          {{ (group.field_components || group.fieldComponents || []).length }} komponen
                        </span>
                      </div>
                      <button
                        class="btn btn-xs btn-primary"
                        @click="openCreateModal(group)"
                      >
                        + Tambah Komponen untuk "{{ group.full_name }}"
                      </button>
                    </div>

                    <table class="table table-sm table-bordered mb-0">
                      <thead class="thead-light">
                        <tr>
                          <th style="width: 40px;">#</th>
                          <th>Komponen</th>
                          <th style="width: 80px;" class="text-center">Bobot %</th>
                          <th style="width: 110px;" class="text-center">Max Nilai / Hakim</th>
                          <th style="width: 80px;" class="text-center">Urutan</th>
                          <th style="width: 80px;" class="text-center">Default?</th>
                          <th style="width: 110px;" class="text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-if="(group.field_components || group.fieldComponents || []).length === 0"
                        >
                          <td colspan="7" class="text-center text-muted">
                            Belum ada komponen penilaian untuk master group ini.
                          </td>
                        </tr>
                        <tr
                          v-for="(fc, idx2) in (group.field_components || group.fieldComponents || [])"
                          :key="fc.id"
                        >
                          <td>{{ idx2 + 1 }}</td>
                          <td>{{ fc.field_name }}</td>
                          <td class="text-center">{{ fc.default_weight ?? '-' }}</td>
                          <td class="text-center">{{ fc.default_max_score ?? '-' }}</td>
                          <td class="text-center">{{ fc.default_order ?? '-' }}</td>
                          <td class="text-center">
                            <span
                              class="badge"
                              :class="fc.is_default ? 'badge-success' : 'badge-secondary'"
                            >
                              {{ fc.is_default ? 'Ya' : 'Tidak' }}
                            </span>
                          </td>
                          <td class="text-center">
                            <div class="btn-group btn-group-sm">
                              <button
                                class="btn btn-warning"
                                @click="openEditModal(fc, group)"
                              >
                                <i class="fas fa-edit"></i>
                              </button>
                              <button
                                class="btn btn-danger"
                                @click="deleteItem(fc, group)"
                              >
                                <i class="fas fa-trash"></i>
                              </button>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </template>
            </tbody>

          </table>
        </div>

        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} master group
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

    <!-- MODAL TAMBAH / EDIT FIELD COMPONENT -->
    <div
      class="modal fade"
      id="masterFieldComponentModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="masterFieldComponentModalLabel"
    >
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h5 class="modal-title" id="masterFieldComponentModalLabel">
              <i class="fas fa-sliders-h mr-1"></i>
              {{ isEdit ? 'Edit Komponen Penilaian' : 'Tambah Komponen Penilaian' }}
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
                    <label class="mb-1">Master Group</label>
                    <select
                      v-model="form.master_group_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih master group --</option>
                      <option
                        v-for="g in masterGroups"
                        :key="g.id"
                        :value="g.id"
                      >
                        {{ g.full_name || g.branch_name + ' - ' + g.group_name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Komponen Penilaian</label>
                    <select
                      v-model="form.field_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih komponen --</option>
                      <option
                        v-for="f in fields"
                        :key="f.id"
                        :value="f.id"
                      >
                        {{ f.name }}
                      </option>
                    </select>
                    <small class="text-muted">
                      Data diambil dari master List Fields.
                    </small>
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Urutan Tampil (Default Order)</label>
                    <input
                      v-model.number="form.default_order"
                      type="number"
                      min="1"
                      class="form-control form-control-sm"
                    />
                  </div>
                </div>

                <div class="col-md-6">

                  <div class="form-group mb-2">
                    <label class="mb-1">Bobot (%)</label>
                    <input
                      v-model.number="form.default_weight"
                      type="number"
                      min="0"
                      max="100"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Max Nilai per Hakim</label>
                    <input
                      v-model.number="form.default_max_score"
                      type="number"
                      min="0"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Digunakan sebagai Template Default?</label>
                    <select
                      v-model="form.is_default"
                      class="form-control form-control-sm"
                    >
                      <option :value="false">Tidak</option>
                      <option :value="true">Ya</option>
                    </select>
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

const groups = ref([])          // master groups + field components
const masterGroups = ref([])    // untuk select di modal
const fields = ref([])

const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)

const expandedGroups = ref([])  // array of group IDs yang sedang expand

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
  master_group_id: '',
  field_id: '',
  default_weight: null,
  default_max_score: null,
  default_order: null,
  is_default: false,
})

const isExpanded = (groupId) => expandedGroups.value.includes(groupId)

const toggleExpand = (groupId) => {
  if (isExpanded(groupId)) {
    expandedGroups.value = expandedGroups.value.filter(id => id !== groupId)
  } else {
    expandedGroups.value.push(groupId)
  }
}

const fetchOptions = async () => {
  try {
    const [resGroups, resFields] = await Promise.all([
      axios.get('/api/v1/master-groups', { params: { simple: 1 } }),
      axios.get('/api/v1/list-fields', { params: { simple: 1 } }),
    ])

    masterGroups.value = resGroups.data.data || []
    fields.value = resFields.data.data || []
  } catch (error) {
    console.error('Gagal memuat options master groups / fields:', error)
    Swal.fire('Gagal', 'Gagal memuat master group atau list field.', 'error')
  }
}

const fetchGroupsWithComponents = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/master-groups', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        with_fields: 1,
      },
    })

    const paginated = res.data.data
    groups.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat master groups:', error)
    Swal.fire('Gagal', 'Gagal memuat data master groups.', 'error')
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchGroupsWithComponents(page)
}

const openCreateModal = (group = null) => {
  isEdit.value = false
  form.value = {
    id: null,
    master_group_id: group ? group.id : '',
    field_id: '',
    default_weight: null,
    default_max_score: null,
    default_order: 1,
    is_default: false,
  }

  // Set default_order = jumlah komponen + 1 kalau group dikirim
  if (group && (group.field_components || group.fieldComponents)) {
    const comps = group.field_components || group.fieldComponents
    form.value.default_order = (comps?.length || 0) + 1
  }

  $('#masterFieldComponentModal').modal('show')
}

const openEditModal = (fc, group = null) => {
  isEdit.value = true
  form.value = {
    id: fc.id,
    master_group_id: fc.master_group_id || (group ? group.id : ''),
    field_id: fc.field_id,
    default_weight: fc.default_weight,
    default_max_score: fc.default_max_score,
    default_order: fc.default_order,
    is_default: !!fc.is_default,
  }
  $('#masterFieldComponentModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    master_group_id: form.value.master_group_id,
    field_id: form.value.field_id,
    default_weight: form.value.default_weight,
    default_max_score: form.value.default_max_score,
    default_order: form.value.default_order,
    is_default: form.value.is_default,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/master-field-components/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Master field component berhasil diperbarui.', 'success')
    } else {
      await axios.post('/api/v1/master-field-components', payload)
      Swal.fire('Berhasil', 'Master field component berhasil ditambahkan.', 'success')
    }

    $('#masterFieldComponentModal').modal('hide')
    fetchGroupsWithComponents(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan master_field_component:', error)
    let message = 'Gagal menyimpan data master field component.'
    if (error.response && error.response.status === 422 && error.response.data.message) {
      message = error.response.data.message
    }
    Swal.fire('Gagal', message, 'error')
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (fc, group = null) => {
  const result = await Swal.fire({
    title: 'Hapus Komponen?',
    text: `Yakin ingin menghapus komponen "${fc.field_name}" dari master group ini?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/master-field-components/${fc.id}`)
    Swal.fire('Terhapus', 'Master field component berhasil dihapus.', 'success')
    fetchGroupsWithComponents(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus master_field_component:', error)
    Swal.fire('Gagal', 'Gagal menghapus data master field component.', 'error')
  }
}

// search debounce
watch(
  () => search.value,
  useDebounceFn(() => {
    expandedGroups.value = []  // reset expand saat ganti search
    fetchGroupsWithComponents(1)
  }, 400)
)

// perPage change
watch(
  () => perPage.value,
  () => {
    expandedGroups.value = []
    fetchGroupsWithComponents(1)
  }
)

onMounted(() => {
  fetchOptions()
  fetchGroupsWithComponents()
})
</script>
