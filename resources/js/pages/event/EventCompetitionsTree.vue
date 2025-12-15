<!-- /pages/event/EventCompetitionsTree.vue -->
<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Kompetisi Event</h1>
          <p class="mb-0 text-muted text-sm">
            Mengelola daftar kompetisi per babak (Round) dalam event aktif.
          </p>

          <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
          </p>
        </div>

        <div class="btn-group">
          <button class="btn btn-sm btn-primary" :disabled="!eventId" @click="openCreateModal">
            <i class="fas fa-plus mr-1"></i> Tambah Kompetisi
          </button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="!eventId" @click="reloadTree">
            <i class="fas fa-sync mr-1"></i> Refresh
          </button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="!eventId" @click="resetTreeView">
            Reset View
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
            <div class="d-flex flex-wrap align-items-center">
              <label class="mb-0 mr-2 text-sm text-muted">Cari</label>
              <input
                v-model="search"
                type="text"
                class="form-control form-control-sm w-auto"
                style="min-width: 260px"
                placeholder="Cari nama kompetisi / venue..."
              />
            </div>

            <div class="text-muted text-sm mt-2 mt-sm-0">
              Tampilan tree dikelompokkan per <strong>Babak</strong>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div id="tree-container"></div>
        </div>
      </div>
    </div>

    <!-- MODAL CREATE/EDIT -->
    <div class="modal fade" id="competitionModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Kompetisi' : 'Tambah Kompetisi' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <div class="alert alert-info text-sm mb-3" v-if="eventId">
              Event: <strong>{{ eventData?.event_name }}</strong>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="mb-1">Babak (Round) <span class="text-danger">*</span></label>
                <select v-model="form.round_id" class="form-control form-control-sm">
                  <option value="" disabled>-- Pilih Babak --</option>
                  <option v-for="r in rounds" :key="r.id" :value="r.id">
                    {{ r.name }}
                  </option>
                </select>
              </div>

              <div class="form-group col-md-6">
                <label class="mb-1">Golongan Event (Event Group) <span class="text-danger">*</span></label>
                <select v-model="form.event_group_id" class="form-control form-control-sm">
                  <option value="" disabled>-- Pilih Golongan --</option>
                  <option v-for="g in eventGroups" :key="g.id" :value="g.id">
                    {{ g.full_name || g.name }}
                  </option>
                </select>
              </div>

              <div class="form-group col-md-8">
                <label class="mb-1">Nama Kompetisi (otomatis) <span class="text-danger">*</span></label>
                <input v-model="form.full_name" type="text" class="form-control form-control-sm" readonly />
                <small class="text-muted">
                  Otomatis mengikuti <strong>Event Group</strong>. (Kalau mau tambah suffix babak, tinggal ubah logic.)
                </small>
              </div>

              <div class="form-group col-md-4">
                <label class="mb-1">Status <span class="text-danger">*</span></label>
                <select v-model="form.status" class="form-control form-control-sm">
                  <option v-for="s in statusOptions" :key="s.value" :value="s.value">
                    {{ s.label }}
                  </option>
                </select>
              </div>

              <div class="form-group col-md-4">
                <label class="mb-1">Jadwal (scheduled_at)</label>
                <input v-model="form.scheduled_at" type="datetime-local" class="form-control form-control-sm" />
              </div>

              <div class="form-group col-md-6">
                <label class="mb-1">Venue</label>
                <input v-model="form.venue" type="text" class="form-control form-control-sm" placeholder="Lokasi/ruangan" />
              </div>

              <div class="form-group col-md-2 d-flex align-items-end">
                <div class="custom-control custom-checkbox">
                  <input id="isTeam" type="checkbox" class="custom-control-input" v-model="form.is_team" />
                  <label class="custom-control-label" for="isTeam">Team</label>
                </div>
              </div>
            </div>

            <small class="text-muted">
              * Kombinasi unik: event + group + round (akan ditolak jika duplikat).
            </small>
          </div>

          <div class="modal-footer">
            <button class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
            <button class="btn btn-sm btn-primary" :disabled="isSubmitting" @click="submitForm">
              <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
              Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import $ from 'jquery'
import 'jstree/dist/jstree'
import 'jstree/dist/themes/default/style.css'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const router = useRouter()
const authUserStore = useAuthUserStore()
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

// meta options
const rounds = ref([])
const eventGroups = ref([])

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'ongoing', label: 'Ongoing' },
  { value: 'finished', label: 'Finished' },
  { value: 'cancelled', label: 'Cancelled' },
]

// tree state
const fullTree = ref([])
const search = ref('')

// modal state
const isEdit = ref(false)
const isSubmitting = ref(false)
const form = ref(emptyForm())

function emptyForm () {
  return {
    id: null,
    event_group_id: '',
    round_id: '',
    full_name: '',
    status: 'draft',
    is_team: false,
    scheduled_at: '',
    venue: '',
  }
}

const escapeHtml = (s) =>
  String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')

const statusBadge = (status) => {
  const s = String(status || '')
  if (s === 'draft') return 'badge-secondary'
  if (s === 'ongoing') return 'badge-info'
  if (s === 'finished') return 'badge-success'
  if (s === 'cancelled') return 'badge-danger'
  return 'badge-light'
}

const formatDateTime = (str) => {
  if (!str) return '-'
  const d = new Date(str)
  if (Number.isNaN(d.getTime())) return str
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yy = d.getFullYear()
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  return `${dd}-${mm}-${yy} ${hh}:${mi}`
}

const selectedGroup = computed(() => {
  const id = String(form.value.event_group_id || '')
  return (eventGroups.value || []).find(g => String(g.id) === id) || null
})

// auto full_name from event_groups.full_name
watch(
  () => form.value.event_group_id,
  (gid) => {
    const g = (eventGroups.value || []).find(x => String(x.id) === String(gid))
    form.value.full_name = g?.full_name || ''
  }
)

// ===================== API =====================
const fetchMeta = async () => {
  if (!eventId.value) return
  const { data } = await axios.get(`/api/v1/events/${eventId.value}/competitions/meta`)
  rounds.value = data.rounds || []
  eventGroups.value = data.event_groups || []
}

const fetchTree = async () => {
  if (!eventId.value) return
  const { data } = await axios.get(`/api/v1/events/${eventId.value}/competitions/tree`, {
    params: { search: search.value || '' },
  })
  fullTree.value = data.rounds || []
}

// ===================== jsTree build =====================
const buildJsTree = async () => {
  await nextTick()
  const $tree = $('#tree-container')

  // destroy + OFF semua handler supaya tidak dobel
  if ($tree.jstree(true)) $tree.jstree(true).destroy()
  $tree.off('.jstree')
  $tree.off('activate_node.jstree')
  $tree.off('select_node.jstree')
  $tree.off('click', '.js-edit-competition')
  $tree.off('click', '.js-del-competition')

  $tree
    .jstree({
      core: {
        check_callback: true,
        themes: { stripes: true },
        html_titles: true,
        data: (node, cb) => {
          // ROOT -> rounds
          if (node.id === '#') {
            const roots = (fullTree.value || []).map(r => ({
              id: `round-${r.id}`,
              type: 'round',
              children: true,
              data: { roundId: r.id },
              text: `
                <span class="tree-round">
                  <strong>${escapeHtml(r.name)}</strong>
                  <span class="badge badge-light border ml-2">${(r.competitions?.length || 0)} kompetisi</span>
                </span>
              `,
            }))
            cb(roots)
            return
          }

          // CHILD of round -> competitions
          if (node.type === 'round') {
            const roundId = String(node.data?.roundId || '')
            const round = (fullTree.value || []).find(x => String(x.id) === String(roundId))
            const comps = round?.competitions || []

            if (!comps.length) {
              cb([{
                id: `info-empty-${roundId}`,
                type: 'info',
                children: false,
                text: `<span class="text-muted">Belum ada kompetisi di babak ini.</span>`,
              }])
              return
            }

            cb(comps.map(c => {
              const teamBadge = c.is_team ? `<span class="badge badge-dark ml-2">TEAM</span>` : ''
              const statusCls = statusBadge(c.status)
              const dt = escapeHtml(formatDateTime(c.scheduled_at))
              const venue = escapeHtml(c.venue || '-')
              const eg = escapeHtml(c.event_group?.full_name || '-')

              return {
                id: `comp-${c.id}`,
                type: 'competition',
                children: false,
                data: { competitionId: c.id },
                text: `
                  <span class="tree-comp">
                    <span class="tree-comp-title">
                      <strong>${escapeHtml(c.full_name)}</strong>
                      ${teamBadge}
                      <span class="badge ${statusCls} ml-2">${escapeHtml(c.status)}</span>
                    </span>

                    <span class="tree-comp-sub text-muted">
                      <span class="mr-2"><i class="far fa-clock mr-1"></i>${dt}</span>
                      <span class="mr-2"><i class="fas fa-map-marker-alt mr-1"></i>${venue}</span>
                      <span class="mr-2"><i class="fas fa-layer-group mr-1"></i>${eg}</span>
                    </span>

                    <span class="tree-actions ml-2">
                      <a href="#" class="badge badge-info js-edit-competition" data-id="${c.id}">
                        <i class="fas fa-edit mr-1"></i>Edit
                      </a>
                      <a href="#" class="badge badge-danger js-del-competition ml-1" data-id="${c.id}">
                        <i class="fas fa-trash mr-1"></i>Hapus
                      </a>
                    </span>
                  </span>
                `,
              }
            }))
            return
          }

          cb([])
        },
      },
      plugins: ['types', 'state', 'search'],
      state: {
        key: 'event-competitions-tree-v2',
        filter: (s) => {
          // ✅ jangan restore selected node (biar tidak auto redirect)
          if (s?.core?.selected) s.core.selected = []
          return s
        },
      },
      types: {
        round: { icon: 'far fa-folder' },
        competition: { icon: 'far fa-flag' },
        info: { icon: 'far fa-circle' },
      },
    })

  // ✅ Klik node (activate) -> redirect / toggle
  $tree.on('activate_node.jstree', (e, data) => {
  if (!data || !data.node) return

  const node = data.node

  // klik round → expand / collapse
  if (node.type === 'round') {
    const inst = $('#tree-container').jstree(true)
    inst.toggle_node(node)
    return
  }

  // klik competition → redirect
  if (node.type === 'competition') {
    const compId = String(node.id).replace('comp-', '')
    router.push({
      name: 'admin.event-competitions.scoring.specific',
      params: { id: compId },
    })
  }
})


  // edit click (badge)
  $tree.on('click', '.js-edit-competition', async (e) => {
    e.preventDefault()
    e.stopPropagation()
    const id = e.currentTarget.getAttribute('data-id')
    if (!id) return false
    await openEditModalById(id)
    return false
  })

  // delete click (badge)
  $tree.on('click', '.js-del-competition', async (e) => {
    e.preventDefault()
    e.stopPropagation()
    const id = e.currentTarget.getAttribute('data-id')
    if (!id) return false
    await confirmDelete(id)
    return false
  })
}

const reloadTree = async () => {
  await fetchTree()
  await buildJsTree()
}

const resetTreeView = async () => {
  await nextTick()
  const inst = $('#tree-container').jstree(true)
  if (!inst) return
  try { inst.clear_state() } catch (_) {}
  try {
    inst.deselect_all(true)
    inst.close_all('#', 0)
    inst.redraw(true)
  } catch (_) {}
}

// jsTree search
const doTreeSearch = useDebounceFn((q) => {
  const inst = $('#tree-container').jstree(true)
  if (!inst) return
  inst.search(q)
}, 300)

watch(() => search.value, (q) => {
  reloadTree()
  doTreeSearch(q)
})

// ===================== MODAL =====================
const openCreateModal = async () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
    return
  }
  isEdit.value = false
  form.value = emptyForm()
  $('#competitionModal').modal('show')
}

const openEditModalById = async (id) => {
  try {
    const { data } = await axios.get(`/api/v1/event-competitions/${id}`)
    isEdit.value = true
    form.value = {
      id: data.id,
      event_group_id: data.event_group_id,
      round_id: data.round_id,
      full_name: '',
      status: data.status,
      is_team: !!data.is_team,
      scheduled_at: data.scheduled_at ? String(data.scheduled_at).slice(0, 16) : '',
      venue: data.venue || '',
    }

    // pastikan full_name terisi (kalau watcher tidak terpanggil)
    const g = (eventGroups.value || []).find(x => String(x.id) === String(form.value.event_group_id))
    form.value.full_name = g?.full_name || data.full_name || ''

    $('#competitionModal').modal('show')
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat data kompetisi.', 'error')
  }
}

const submitForm = async () => {
  if (!eventId.value) return

  // ensure full_name dari event group
  const g = selectedGroup.value
  form.value.full_name = g?.full_name || form.value.full_name || ''

  if (!form.value.round_id || !form.value.event_group_id) {
    Swal.fire('Validasi', 'Round dan Event Group wajib diisi.', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    const payload = {
      event_id: eventId.value,
      round_id: form.value.round_id,
      event_group_id: form.value.event_group_id,
      full_name: form.value.full_name,
      status: form.value.status,
      is_team: form.value.is_team,
      scheduled_at: form.value.scheduled_at ? form.value.scheduled_at.replace('T', ' ') : null,
      venue: form.value.venue,
    }

    if (isEdit.value && form.value.id) {
      await axios.put(`/api/v1/event-competitions/${form.value.id}`, payload)
      Swal.fire('Berhasil', 'Kompetisi berhasil diperbarui.', 'success')
    } else {
      await axios.post(`/api/v1/events/${eventId.value}/competitions`, payload)
      Swal.fire('Berhasil', 'Kompetisi berhasil ditambahkan.', 'success')
    }

    $('#competitionModal').modal('hide')
    await reloadTree()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', e?.response?.data?.message || 'Terjadi kesalahan saat menyimpan.', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const confirmDelete = async (id) => {
  const res = await Swal.fire({
    title: 'Hapus kompetisi?',
    text: 'Data yang dihapus tidak dapat dikembalikan.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
  })
  if (!res.isConfirmed) return

  try {
    await axios.delete(`/api/v1/event-competitions/${id}`)
    Swal.fire('Terhapus', 'Kompetisi berhasil dihapus.', 'success')
    await reloadTree()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal menghapus kompetisi.', 'error')
  }
}

// ===================== MOUNT =====================
onMounted(async () => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
    return
  }

  try {
    await fetchMeta()
    await fetchTree()
    await buildJsTree()
  } catch (e) {
    console.error(e)
    Swal.fire('Gagal', 'Gagal memuat data kompetisi.', 'error')
  }
})

watch(() => eventId.value, async (val) => {
  if (!val) return
  await fetchMeta()
  await reloadTree()
})
</script>

<style scoped>
#tree-container{
  border:1px solid #ccc;
  border-radius:6px;
  padding:12px;
  background-color:#fafafa;
  min-height:260px;
}

.text-xs{ font-size:.75rem; }

.tree-comp{
  display:inline-flex;
  flex-wrap:wrap;
  align-items:center;
  gap:6px;
  width:100%;
}

.tree-comp-title{
  display:inline-flex;
  align-items:center;
  gap:6px;
}

.tree-comp-sub{ font-size:.75rem; }

.tree-actions a.badge{
  cursor:pointer;
  text-decoration:none;
}

#tree-container .jstree-anchor{ cursor:pointer; }
</style>
