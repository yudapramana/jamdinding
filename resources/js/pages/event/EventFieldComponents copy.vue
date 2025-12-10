<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Komponen Penilaian per Golongan Event</h1>
          <p class="mb-0 text-muted text-sm">
            Mengatur komponen penilaian (Lagu, Suara, Tajwid, dll.) beserta bobot & nilai maksimum
            untuk setiap Golongan Event (Cabang + Golongan yang sudah diaktifkan di event).
          </p>

          <!-- Info event aktif -->
          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
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
            Generate dari Template Master
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
            <!-- LEFT: perPage -->
            <div class="d-flex align-items-center">
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-2">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <label class="mb-0 text-sm text-muted">Golongan / halaman</label>
            </div>

            <!-- RIGHT: search -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width: 220px"
              placeholder="Cari nama golongan / cabang..."
            />
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;"></th>
                <th style="width: 40px;">#</th>
                <th>Golongan Event</th>
                <!-- <th>Cabang</th>
                <th>Golongan</th> -->
                <th class="text-center">Maks Usia (Tahun)</th>
                <th class="text-center">Tim?</th>
                <th class="text-center">Urutan</th>
                <th class="text-center">Komponen</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="9" class="text-center">Memuat data golongan event...</td>
              </tr>

              <tr v-else-if="groups.length === 0">
                <td colspan="9" class="text-center">
                  Belum ada Event Group untuk event ini.
                  <br />
                  <small class="text-muted">
                    Silakan generate Event Groups terlebih dahulu.
                  </small>
                </td>
              </tr>

              <!-- Setiap group: row utama + row detail -->
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
                  <!-- <td>{{ group.branch_name }}</td>
                  <td>{{ group.group_name }}</td> -->
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
                  <td>
                    {{ (group.field_components || group.fieldComponents || []).length }}
                  </td>
                </tr>

                <!-- ROW DETAIL -->
                <tr v-if="isExpanded(group.id)">
                  <td></td>
                  <td colspan="8" class="bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="text-sm">
                        <strong>Komponen Penilaian:</strong>
                        <span class="text-muted">
                          {{ (group.field_components || group.fieldComponents || []).length }}
                          komponen
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
                          <th style="width: 110px;" class="text-center">Max Nilai</th>
                          <th style="width: 80px;" class="text-center">Urutan</th>
                          <th style="width: 110px;" class="text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-if="(group.field_components || group.fieldComponents || []).length === 0"
                        >
                          <td colspan="6" class="text-center text-muted">
                            Belum ada komponen penilaian untuk golongan event ini.
                          </td>
                        </tr>
                        <tr
                          v-for="(fc, idx2) in (group.field_components || group.fieldComponents || [])"
                          :key="fc.id"
                        >
                          <td>{{ idx2 + 1 }}</td>
                          <td>{{ fc.field_name }}</td>
                          <td class="text-center">{{ fc.weight ?? '-' }}</td>
                          <td class="text-center">{{ fc.max_score ?? '-' }}</td>
                          <td class="text-center">{{ fc.order_number ?? '-' }}</td>
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
              {{ meta.total || 0 }} golongan event
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

    <!-- MODAL TAMBAH / EDIT EVENT FIELD COMPONENT -->
    <div
      class="modal fade"
      id="eventFieldComponentModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="eventFieldComponentModalLabel"
    >
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h5 class="modal-title" id="eventFieldComponentModalLabel">
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
                    <label class="mb-1">Golongan Event</label>
                    <select
                      v-model="form.event_group_id"
                      class="form-control form-control-sm"
                      required
                    >
                      <option :value="''" disabled>-- pilih golongan event --</option>
                      <option
                        v-for="(g, idx) in eventGroupsSimple"
                        :key="g && g.id ? g.id : 'eg-' + idx"
                        :value="g ? g.id : null"
                        v-if="g"
                        >
                        {{ g.full_name || (g.branch_name + ' - ' + g.group_name) }}
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
                    <label class="mb-1">Urutan Tampil</label>
                    <input
                      v-model.number="form.order_number"
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
                      v-model.number="form.weight"
                      type="number"
                      min="0"
                      max="100"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group mb-2">
                    <label class="mb-1">Max Nilai</label>
                    <input
                      v-model.number="form.max_score"
                      type="number"
                      min="0"
                      class="form-control form-control-sm"
                    />
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

// event aktif
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

const groups = ref([])             // event_groups + field_components
const eventGroupsSimple = ref([])  // untuk select di modal
const fields = ref([])

const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)
const isGenerating = ref(false)

const expandedGroups = ref([])

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
  event_group_id: '',
  field_id: '',
  weight: null,
  max_score: null,
  order_number: null,
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
  if (!eventId.value) return

  try {
    const [resGroups, resFields] = await Promise.all([
      axios.get('/api/v1/event-groups', {
        params: {
          event_id: eventId.value,
          simple: 1,
        },
      }),
      axios.get('/api/v1/list-fields', { params: { simple: 1 } }),
    ])

    eventGroupsSimple.value = (resGroups.data.data || []).filter(Boolean)
    fields.value = resFields.data.data || []
  } catch (error) {
    console.error('Gagal memuat options event groups / fields:', error)
    Swal.fire('Gagal', 'Gagal memuat Golongan Event atau List Fields.', 'error')
  }
}

const fetchGroupsWithComponents = async (page = 1) => {
  if (!eventId.value) return

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/event-groups', {
      params: {
        event_id: eventId.value,
        page,
        per_page: perPage.value,
        search: search.value,
        with_fields: 1, // minta relasi fieldComponents
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
    console.error('Gagal memuat event groups:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    } else {
      Swal.fire('Gagal', 'Gagal memuat data Golongan Event.', 'error')
    }
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchGroupsWithComponents(page)
}

const openCreateModal = (group = null) => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum mengelola komponen penilaian.',
      'warning'
    )
    return
  }

  isEdit.value = false
  form.value = {
    id: null,
    event_group_id: group ? group.id : '',
    field_id: '',
    weight: null,
    max_score: null,
    order_number: 1,
  }

  if (group && (group.field_components || group.fieldComponents)) {
    const comps = group.field_components || group.fieldComponents
    form.value.order_number = (comps?.length || 0) + 1
  }

  $('#eventFieldComponentModal').modal('show')
}

const openEditModal = (fc, group = null) => {
  isEdit.value = true
  form.value = {
    id: fc.id,
    event_group_id: fc.event_group_id || (group ? group.id : ''),
    field_id: fc.field_id,
    weight: fc.weight,
    max_score: fc.max_score,
    order_number: fc.order_number,
  }
  $('#eventFieldComponentModal').modal('show')
}

const submitForm = async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum mengelola komponen penilaian.',
      'warning'
    )
    return
  }

  isSubmitting.value = true

  const payload = {
    event_group_id: form.value.event_group_id,
    field_id: form.value.field_id,
    weight: form.value.weight,
    max_score: form.value.max_score,
    order_number: form.value.order_number,
  }

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-field-components/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Komponen penilaian event berhasil diperbarui.', 'success')
    } else {
      await axios.post('/api/v1/event-field-components', payload)
      Swal.fire('Berhasil', 'Komponen penilaian event berhasil ditambahkan.', 'success')
    }

    $('#eventFieldComponentModal').modal('hide')
    fetchGroupsWithComponents(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan event_field_component:', error)
    let message = 'Gagal menyimpan data komponen penilaian event.'
    if (error.response && error.response.status === 422 && error.response.data.message) {
      message = error.response.data.message
    }
    Swal.fire('Gagal', message, 'error')
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (fc) => {
  const result = await Swal.fire({
    title: 'Hapus Komponen?',
    text: `Yakin ingin menghapus komponen "${fc.field_name}" dari golongan event ini?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/event-field-components/${fc.id}`)
    Swal.fire('Terhapus', 'Komponen penilaian event berhasil dihapus.', 'success')
    fetchGroupsWithComponents(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus event_field_component:', error)
    Swal.fire('Gagal', 'Gagal menghapus komponen penilaian event.', 'error')
  }
}

const generateFromTemplate = async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum generate dari template.',
      'warning'
    )
    return
  }

  const result = await Swal.fire({
    title: 'Generate dari Template Master?',
    text: 'Komponen penilaian event akan dibuat berdasarkan Master Field Components. Data yang sudah ada tidak akan diduplikasi.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Generate',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
  })

  if (!result.isConfirmed) return

  isGenerating.value = true
  try {
    const res = await axios.post('/api/v1/event-field-components/generate-from-template', {
      event_id: eventId.value,
    })

    const msg = res.data.message || 'Komponen penilaian event berhasil digenerate dari template.'
    Swal.fire('Berhasil', msg, 'success')
    expandedGroups.value = []
    fetchGroupsWithComponents(1)
  } catch (error) {
    console.error('Gagal generate dari template:', error)
    let message = 'Gagal generate komponen dari template.'
    if (error.response && error.response.data && error.response.data.message) {
      message = error.response.data.message
    }
    Swal.fire('Gagal', message, 'error')
  } finally {
    isGenerating.value = false
  }
}

// search debounce
watch(
  () => search.value,
  useDebounceFn(() => {
    expandedGroups.value = []
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

// kalau eventId baru ter-set setelah halaman dibuka
watch(
  () => eventId.value,
  (val) => {
    if (val) {
      fetchOptions()
      fetchGroupsWithComponents(1)
    }
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
    fetchOptions()
    fetchGroupsWithComponents()
  }
})
</script>
