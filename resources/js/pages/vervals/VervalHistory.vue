<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Verval History Saya</h1>
        <small class="text-muted">Log verifikasi yang saya lakukan</small>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-md-4 mb-2">
              <input v-model="search" type="text" class="form-control"
                     placeholder="Cari nomor, nama pegawai, NIP, file, catatan…" />
            </div>
            <div class="col-md-3 mb-2">
              <select v-model="status" class="form-control">
                <option value="">— Semua Status —</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <input v-model="dateFrom" type="date" class="form-control" />
            </div>
            <div class="col-md-2 mb-2">
              <input v-model="dateTo" type="date" class="form-control" />
            </div>
            <div class="col-md-1 mb-2">
              <button class="btn btn-secondary btn-block" @click="resetFilter">Reset</button>
            </div>
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>Tanggal</th>
                <th>Dokumen</th>
                <th>Pemilik</th>
                <th>Status</th>
                <th>Catatan</th>
                <th style="width: 70px;">File</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data…</td>
              </tr>
              <tr v-else-if="logs.length === 0">
                <td colspan="7" class="text-center">Belum ada log verifikasi.</td>
              </tr>
              <tr v-else v-for="(row, i) in logs" :key="row.id">
                <td>{{ i + 1 + offsetIndex }}</td>
                <td>
                  <div>{{ format(row.created_at) }}</div>
                  <!-- <small class="text-muted">by: {{ row.verifier?.name || '-' }}</small> -->
                </td>
                <td>
                  <div><strong> {{ row.document?.doctype?.type_name || '-' }} </strong> &middot; <span v-html="row.document?.parameter || '<i>n/a</i>'"></span></div>
                  <small class="text-muted">
                    <!-- Tgl: {{ row.document?.doc_date ? format(row.document.doc_date) : '-' }}  &middot; -->
                    {{ row.document?.file_name || '-' }}
                  </small>
                </td>
                <td>
                  <div><strong>{{ row.document?.employee?.full_name || '-' }}</strong></div>
                  <small class="text-muted">{{ row.document?.employee?.nip || '-' }}</small>
                </td>
                <td>
                  <span :class="['badge', badgeClass(row.verval_status)]">{{ row.verval_status }}</span>
                </td>
                <td>
                  <a href="#" @click.prevent="openNotes(row)">
                    {{ shortNotes(row.verif_notes) }}
                  </a>
                </td>
                <td class="text-center">
                  <a v-if="row.document?.file_path" :href="fileUrl(row.document.file_path)" target="_blank" title="Lihat file">
                    <i class="fas fa-file"></i>
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
              Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} log
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="fetchLogs(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a class="page-link" href="#" @click.prevent="fetchLogs(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>

    <!-- Modal Catatan -->
    <div class="modal fade" id="notesModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">Catatan Verifikasi</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <p style="white-space: pre-wrap;">{{ activeNotes || '-' }}</p>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>
    <!-- /Modal Catatan -->
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { useToastr } from '../../toastr.js'
import { formatDate } from '../../helper.js'
import { useAuthUserStore } from '../../stores/AuthUserStore.js'

const toastr = useToastr()
const authUserStore = useAuthUserStore()

const logs = ref([])
const meta = ref({ current_page: 1, per_page: 10, total: 0, from: 0, to: 0, last_page: 1 })
const isLoading = ref(false)

const search = ref('')
const status = ref('')
// const statuses = [
//   'Uploaded','Reuploaded','Approved','Rejected','Uploaded by Admin','Reuploaded by Admin'
// ]
const statuses = [
  'Approved','Rejected','Uploaded by Admin','Reuploaded by Admin'
]
const dateFrom = ref('')
const dateTo = ref('')

const activeNotes = ref('')

const format = (d) => formatDate(d, 'DD/MM/YYYY HH:mm')

const offsetIndex = computed(() => (meta.value.current_page - 1) * (meta.value.per_page || 10))

const buildPreviewUrl = (path) =>
  `/api/preview/pdf?path=${encodeURI(path)}`;

const fileUrl = (path) => {
  // sesuaikan base url storage kamu
  // contoh umum: /storage/ + path (jika pakai laravel storage:link)
  if (!path) return '#'
  var url = buildPreviewUrl(path);
  return url;
  // return `/storage/${path}`.replace(/\/+/, '/')
}

const badgeClass = (s) => {
  switch (s) {
    case 'Approved': return 'badge-success'
    case 'Rejected': return 'badge-danger'
    case 'Reuploaded':
    case 'Reuploaded by Admin': return 'badge-warning'
    case 'Uploaded':
    case 'Uploaded by Admin': return 'badge-info'
    default: return 'badge-secondary'
  }
}

const shortNotes = (txt) => {
  if (!txt) return '-'
  return txt.length > 40 ? txt.slice(0, 40) + '…' : txt
}

const openNotes = (row) => {
  activeNotes.value = row?.verif_notes || ''
  $('#notesModal').modal('show')
}

const fetchLogs = async (page = 1) => {
  isLoading.value = true
  try {
    const { data } = await axios.get('/api/verval-logs', {
      params: {
        page,
        per_page: meta.value.per_page,
        search: search.value || undefined,
        status: status.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        // verified_by: authUserStore.user?.id, // opsional, default pakai auth()->id() di backend
      }
    })
    logs.value = data?.data || []
    meta.value = { ...meta.value, ...data?.meta, ...data }
  } catch (e) {
    toastr.error('Gagal memuat verval history')
  } finally {
    isLoading.value = false
  }
}

const resetFilter = () => {
  search.value = ''
  status.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  fetchLogs(1)
}

watch(search, useDebounceFn(() => fetchLogs(1), 350))
watch([status, dateFrom, dateTo], useDebounceFn(() => fetchLogs(1), 350))

onMounted(() => {
  fetchLogs()
})
</script>
