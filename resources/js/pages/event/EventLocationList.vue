<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Lokasi / Majelis</h1>
          <p class="text-muted text-sm">Pengaturan lokasi pelaksanaan cabang / majelis</p>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreate">
          + Tambah Lokasi
        </button>
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <!-- FILTER -->
        <div class="card-header d-flex justify-content-between align-items-center w-100">
          <div class="d-flex align-items-center">
            <label class="mb-0 mr-2 text-sm text-muted">Tampilkan</label>
            <select
              v-model.number="perPage"
              class="form-control form-control-sm w-auto mr-2"
              @change="fetchData(1)"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="text-sm text-muted">entri</span>
          </div>

          <input
            v-model="search"
            class="form-control form-control-sm w-50"
            placeholder="Cari nama / kode majelis..."
          />
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width:50px">No</th>
                <th>Kode</th>
                <th>Nama Lokasi</th>
                <th>Koordinat</th>
                <th>Status</th>
                <th style="width:100px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="locations.length === 0">
                <td colspan="6" class="text-center">Belum ada lokasi.</td>
              </tr>
              <tr v-for="(row, index) in locations" :key="row.id">
                <td>
                  {{ index + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>
                <td>{{ row.code || '-' }}</td>
                <td><strong>{{ row.name }}</strong></td>
                <td class="text-nowrap">
                  {{ row.latitude }}, {{ row.longitude }}
                </td>
                <td>
                  <span
                    class="badge"
                    :class="row.is_active ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-xs btn-warning mr-1" @click="openEdit(row)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-xs btn-danger" @click="remove(row.id)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <div class="card-footer clearfix">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} – {{ meta.to || 0 }} dari {{ meta.total || 0 }} lokasi
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL ================= -->
    <div class="modal fade" id="locationModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Lokasi' : 'Tambah Lokasi' }}
            </h5>
            <button class="close" data-dismiss="modal">&times;</button>
          </div>

          <div class="modal-body">
            <!-- FORM -->
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-2">
                  <label>Kode Lokasi</label>
                  <input v-model="form.code" class="form-control form-control-sm" />
                </div>

                <div class="form-group mb-2">
                  <label>Nama Lokasi</label>
                  <input v-model="form.name" class="form-control form-control-sm" required />
                </div>

                <div class="form-group mb-2">
                  <label>Alamat</label>
                  <textarea v-model="form.address" rows="2" class="form-control form-control-sm"></textarea>
                </div>

                <div class="form-group mb-2">
                  <label>Status</label>
                  <select v-model="form.is_active" class="form-control form-control-sm">
                    <option :value="true">Aktif</option>
                    <option :value="false">Nonaktif</option>
                  </select>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group mb-2">
                  <label>Latitude</label>
                  <input v-model="form.latitude" class="form-control form-control-sm" readonly />
                </div>
                <div class="form-group mb-2">
                  <label>Longitude</label>
                  <input v-model="form.longitude" class="form-control form-control-sm" readonly />
                </div>
                <div class="form-group mb-2">
                  <label>Catatan</label>
                  <textarea v-model="form.notes" rows="2" class="form-control form-control-sm"></textarea>
                </div>
              </div>
            </div>

            <!-- MAP -->
            <!-- SEARCH MAP -->
            <div class="input-group input-group-sm mb-2">
              <input
                v-model="mapSearch"
                class="form-control"
                placeholder="Cari lokasi (contoh: Masjid Carocok Painan)"
                @keyup.enter="searchMapLocation"
              />
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" @click="searchMapLocation">
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>

            
            <!-- MAP -->
            <div id="map" style="height:300px" class="mt-2"></div>

            <small class="text-muted">
              Ketik nama lokasi lalu geser peta untuk presisi (marker di tengah).
            </small>

          </div>

          <div class="modal-footer">
            <button class="btn btn-primary btn-sm" @click="save">
              <i class="fas fa-save mr-1"></i> Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { useAuthUserStore } from '@/stores/AuthUserStore'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'
import 'leaflet-fullscreen'



const auth = useAuthUserStore()
const eventId = auth.eventData.id

/* ================= STATE ================= */
const locations = ref([])
const search = ref('')
const loading = ref(false)

const perPage = ref(10)
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const isEdit = ref(false)
const form = ref({
  id: null,
  code: '',
  name: '',
  address: '',
  latitude: '',
  longitude: '',
  notes: '',
  is_active: true,
})

/* ================= MAP ================= */
let map = null
let centerMarker = null
const mapSearch = ref('')


const initMapIfNeeded = () => {
  if (map) return

  map = L.map('map', {
    center: [-1.348545, 100.5641798],
    zoom: 13,
    fullscreenControl: true,
    fullscreenControlOptions: {
      position: 'topleft'
    }
  })


  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
  }).addTo(map)

  centerMarker = L.marker(map.getCenter()).addTo(map)

  map.on('move', () => {
    const center = map.getCenter()
    centerMarker.setLatLng(center)
    form.value.latitude = +center.lat.toFixed(7)
    form.value.longitude = +center.lng.toFixed(7)
  })
}


const searchMapLocation = async () => {
  if (!mapSearch.value || mapSearch.value.length < 3) return

  try {
    const res = await axios.get('/api/v1/map/search', {
      params: { q: mapSearch.value }
    })

    if (!res.data || res.data.length === 0) {
      alert('Lokasi tidak ditemukan')
      return
    }

    const loc = res.data[0]
    const lat = parseFloat(loc.lat)
    const lng = parseFloat(loc.lon)

    map.setView([lat, lng], 16)
    centerMarker.setLatLng([lat, lng])

    form.value.latitude = +lat.toFixed(7)
    form.value.longitude = +lng.toFixed(7)

    if (!form.value.address && loc.display_name) {
      form.value.address = loc.display_name
    }

  } catch (err) {
    console.error(err)
    alert('Gagal mencari lokasi')
  }
}



/* ================= API ================= */
const fetchData = async (page = 1) => {
  loading.value = true
  const res = await axios.get(`/api/v1/events/${eventId}/locations`, {
    params: {
      page,
      per_page: perPage.value,
      search: search.value,
    }
  })

  const paginated = res.data.data
  locations.value = paginated.data
  meta.value = {
    current_page: paginated.current_page,
    per_page: paginated.per_page,
    total: paginated.total,
    from: paginated.from,
    to: paginated.to,
    last_page: paginated.last_page,
  }

  loading.value = false
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

/* ================= MODAL ================= */
const openCreate = () => {
  isEdit.value = false
  form.value = {
    id: null,
    code: '',
    name: '',
    address: '',
    latitude: '',
    longitude: '',
    notes: '',
    is_active: true,
  }

  $('#locationModal').modal('show')

  $('#locationModal').one('shown.bs.modal', async () => {
    await nextTick()
    initMapIfNeeded()
    map.invalidateSize()
    map.setView([-1.348545, 100.5641798], 13)
  })
}

const openEdit = (row) => {
  isEdit.value = true
  form.value = { ...row }

  $('#locationModal').modal('show')

  $('#locationModal').one('shown.bs.modal', async () => {
    await nextTick()
    initMapIfNeeded()
    map.invalidateSize()
    map.setView([row.latitude, row.longitude], 15)
    centerMarker.setLatLng([row.latitude, row.longitude])
  })
}

/* ================= CRUD ================= */
const save = async () => {
  const payload = {
    code: form.value.code,
    name: form.value.name,
    address: form.value.address,
    latitude: form.value.latitude,
    longitude: form.value.longitude,
    notes: form.value.notes,
    is_active: form.value.is_active,
  }

  if (isEdit.value) {
    await axios.put(`/api/v1/event-locations/${form.value.id}`, payload)
  } else {
    await axios.post('/api/v1/event-locations', {
      ...payload,
      event_id: eventId
    })
  }

  $('#locationModal').modal('hide')
  fetchData(meta.value.current_page)
}

const remove = async (id) => {
  if (!confirm('Hapus lokasi ini?')) return
  await axios.delete(`/api/v1/event-locations/${id}`)
  fetchData(meta.value.current_page)
}

/* ================= WATCH ================= */
watch(search, () => fetchData(1))
onMounted(() => fetchData())
</script>

<style scoped>
/* AdminLTE + Leaflet FIX */
.leaflet-container img {
  max-width: none !important;
}

.leaflet-control-fullscreen a {
  background-size: 16px 16px;
}



</style>