<!-- <script setup>

import { useAuthUserStore } from "../stores/AuthUserStore.js";
import { useScreenDisplayStore } from '../stores/ScreenDisplayStore.js';
import { useSettingStore } from "../stores/SettingStore.js";
import { reactive, ref, nextTick, onMounted } from 'vue';

const screenDisplayStore = useScreenDisplayStore();
const authUserStore = useAuthUserStore();
const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);

onMounted(() => {
    // initiation
    console.log('initiation')
    settingStore.resetMaintenance()
    settingStore.getSetting()
});


</script>

<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                    Beranda 
                    <span v-if="settingStore.setting.maintenance == true" 
                            class="badge bg-warning text-dark px-2 py-1" 
                            style="font-size: 0.7rem; vertical-align: middle;">
                        Maintenance
                    </span>
                    </h1>
                    
                </div>
                <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Beranda</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>


    <div class="content">
        <div class="container-fluid">
            
            <div v-if="settingStore.setting.maintenance == true" class="row">
                <div class="col-12 mb-2">
                    <div class="alert alert-warning alert-dismissible fade show " role="alert">
                        <strong>Pemberitahuan Pemeliharaan Sistem</strong><br>
                        Yth. Bapak/Ibu Pengguna SIGARDA, <br>
                        Sehubungan dengan keterbatasan kapasitas penyimpanan pada server, kami informasikan bahwa proses pemeliharaan (maintenance) akan berlangsung selama 3 hari.

                        <br>Selama periode tersebut, akan dilakukan proses verifikasi dan validasi (verval) agar dokumen yang telah tersimpan dapat dipindahkan ke Google Drive / Google Cloud Storage Bucket.

                        </br>Atas perhatian dan pengertiannya, kami ucapkan terima kasih.

                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-9 col-sm-12 col-lg-9">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0 pb-0 mb-0">
                            {{ authUserStore.user?.employee?.job_title }}
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-12">
                                    <h2 class="lead"><b>{{ authUserStore.user.name }}</b></h2>
                                    <ul class="ml-5 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i class="far fa-id-card"></i></span> {{ authUserStore.user.username }}</li>
                                        <li class="small"><span class="fa-li"><i
                                                    class="fas fa-lg fa-building"></i></span> {{ authUserStore.user?.employee?.work_unit?.unit_name }}</li>
                                    </ul>
                                </div>
                                
                            </div>
                        </div>
                        <div class="card-footer b-0 p-0">
                            <div class="btn-group" style="width: 100% !important;">
                                
                                <router-link to="/user/profile" class="btn btn-sm btn-primary" style="display: block; font-size: small;">
                                    <i class="fas fa-user"></i>&nbsp;Lihat Profil
                                </router-link>
                                <router-link to="/user/upload" class="btn btn-sm btn-success" style="display: block; font-size: small;">
                                    Input Dokumen <i class="fas fa-arrow-circle-right"></i>
                                </router-link>
                                <a
                                    v-if="authUserStore.user.role == 'SUPERADMIN'"
                                    href="/oauth/google"
                                    class="btn btn-sm btn-warning"
                                    style="display: block; font-size: small;"
                                    >
                                    <i class="fab fa-google"></i>&nbsp;Connect Google
                                </a>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>

            
        </div>
    </div>
</template> -->


<script setup>
import { useAuthUserStore } from "../stores/AuthUserStore.js";
import { useScreenDisplayStore } from '../stores/ScreenDisplayStore.js';
import { useSettingStore } from "../stores/SettingStore.js";
import { reactive, ref, nextTick, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import $ from 'jquery';
import 'jstree/dist/jstree';
import 'jstree/dist/themes/default/style.css';
import { useMasterDataStore } from '../stores/MasterDataStore';

const router = useRouter();
const screenDisplayStore = useScreenDisplayStore();
const authUserStore = useAuthUserStore();
const masterDataStore = useMasterDataStore();
const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);

const fullTree = ref([]);                 // pohon work unit dari /monitor
const unitById = ref(new Map());          // map cepat id -> unit (beserta parentId & children)
const treeReady = ref(false);
const treeInstance = ref(null);
const employeesCache = ref(new Map());    // opsional cache: unitId -> array employees

// ===== Utils =====
const escapeHtml = (s) =>
  String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

// Helper: flatten + simpan parentId agar cepat saat expandToUnit
const indexUnits = (units) => {
  const map = new Map();
  const walk = (arr, parentId = null) => {
    for (const u of arr) {
      map.set(String(u.id), { ...u, parentId });
      if (u.children?.length) walk(u.children, u.id);
    }
  };
  walk(units);
  return map;
};

// Siapkan data root nodes (top-level units = parent_unit null/#)
const getRootNodes = () => {
  const roots = fullTree.value;
  return roots.map(u => ({
    id: String(u.id),
    text: escapeHtml(u.unit_name),
    type: 'workunit',
    children: true, // biar jsTree tau ada anak (lazy)
  }));
};

// Ambil child workunit untuk node tertentu dari fullTree (tanpa hit API)
const getChildUnitNodes = (unitId) => {
  const u = unitById.value.get(String(unitId));
  if (!u) return [];
  const children = u.children || [];
  return children.map(cu => ({
    id: String(cu.id),
    text: escapeHtml(cu.unit_name),
    type: 'workunit',
    children: true,
  }));
};

// Fetch employees; gunakan cache store agar hemat
const fetchEmployees = async (unitId) => {
  const key = String(unitId);
  if (masterDataStore.employeesCacheByUnit[key]) {
    return masterDataStore.employeesCacheByUnit[key];
  }
  const res = await axios.get(`/api/work-units/${key}/employees`);
  const list = Array.isArray(res.data) ? res.data : (res.data.data ?? []);
  masterDataStore.setEmployees(key, list);
  return list;
};

// ===== Auto Open Helpers =====

// Buka node hingga kedalaman tertentu (depth 0 = hanya node itu saja terbuka)
// startId bisa '#'(root) untuk membuka semua root dan anak2nya hingga depth
const openToDepth = async (inst, startId = '#', depth = 1) => {
  const openNode = (id) =>
    new Promise((resolve) => inst.open_node(id, resolve, false));

  const openRecursive = async (id, d) => {
    await new Promise((resolve) => {
      inst.open_node(id, async () => {
        if (d > 0) {
          const kids = inst.get_node(id)?.children || [];
          for (const k of kids) {
            await openRecursive(k, d - 1);
          }
        }
        resolve();
      }, false);
    });
  };

  if (depth < 0) return;

  // Pastikan root siap
  if (startId === '#') {
    // Buka semua root child sampai depth
    const roots = inst.get_node('#')?.children || [];
    for (const rid of roots) {
      await openRecursive(rid, depth);
    }
    return;
  }

  await openRecursive(startId, depth);
};

// Buka path menuju unit tertentu (berdasarkan unitById dengan parentId)
const expandToUnit = async (inst, unitIdStr) => {
  if (!unitIdStr) return;
  let cursor = unitById.value.get(String(unitIdStr));
  if (!cursor) return;

  const pathIds = [];
  while (cursor) {
    pathIds.unshift(String(cursor.id));
    cursor = cursor.parentId ? unitById.value.get(String(cursor.parentId)) : null;
  }

  // Buka root dahulu agar child tersedia
  await new Promise((resolve) => inst.open_node('#', resolve, false));

  // Buka tiap id berurutan agar lazy loader terpanggil
  // (kalau suatu node belum exist di DOM, membuka parent akan memunculkan anaknya)
  for (const id of pathIds) {
    await new Promise((resolve) => inst.open_node(id, resolve, false));
  }
};

// ===== jsTree Builder =====
const buildJsTree = async () => {
  await nextTick();
  const $tree = $('#tree-container');

  // Hancurkan instance lama jika ada
  if ($tree.jstree(true)) $tree.jstree(true).destroy();

  // Bersihkan event lama agar tidak dobel
  $tree.off('click', '.js-doc-link');
  $tree.off('.jstree');

  // Auto-open saat tree ready (attach BEFORE init supaya tidak ketinggalan event)
  $tree.one('ready.jstree', async () => {
    const inst = $tree.jstree(true);
    treeInstance.value = inst;
    treeReady.value = true;

    // 1) Buka semua root beserta 1 level anak (sesuaikan kedalaman bila perlu)
    await openToDepth(inst, '#', 1);

    // 2) (opsional) Buka jalur hingga unit kerja user saat ini
    const myUnitId = authUserStore.user?.employee?.work_unit?.id
                  ?? authUserStore.user?.employee?.id_work_unit;
    if (myUnitId) {
      await expandToUnit(inst, String(myUnitId));
    }
  });

  // Init jsTree
  $tree.jstree({
    core: {
      check_callback: true,
      themes: { stripes: true },
      html_titles: true, // izinkan HTML di node text
      // Lazy loader utama: dipanggil tiap kali butuh children
      data: async (node, cb) => {
        try {
          // Root
          if (node.id === '#') {
            cb(getRootNodes());
            return;
          }

          // Kalau node employee, tidak punya anak
          if (node.type === 'employee') {
            cb([]);
            return;
          }

          // Node workunit: gabungkan children unit + employees
          const unitId = node.id;
          const childUnitNodes = getChildUnitNodes(unitId);

          // helper warna progress
          const progressClass = (p) => {
            if (p >= 80) return 'bg-success';
            if (p >= 60) return 'bg-info';
            if (p >= 40) return 'bg-warning';
            return 'bg-danger';
          };

          // employees on-demand (dengan progress bar)
          const employees = await fetchEmployees(unitId);
          const employeeNodes = employees.map(emp => {
            const progress = Number(emp.progress_dokumen ?? 0);
            const progressStr = (isFinite(progress) ? progress : 0).toFixed(2);
            const safeName = escapeHtml(emp.full_name || '');
            const barCls = progressClass(progress);

            return {
              id: `emp-${unitId}-${emp.id}`,
              type: 'employee',
              children: false,
              text: `
                <span class="w-100">
                  <span class="emp-label">${safeName} — ${progress}%</span>
                  <div class="progress progress-xxs">
                    <div class="progress-bar ${barCls} progress-bar-striped" role="progressbar"
                        aria-valuenow="${progressStr}" aria-valuemin="0" aria-valuemax="100"
                        style="width: ${Math.max(0, Math.min(100, Number(progress) || 0))}%">
                      <span class="sr-only">${progressStr}% Complete</span>
                    </div>
                  </div>
                </span>
              `
            };
          });

          // Jika tidak ada apa-apa, tampilkan node info (opsional)
          if (childUnitNodes.length === 0 && employeeNodes.length === 0) {
            cb([{
              id: `info-empty-${unitId}`,
              text: 'Belum ada data.',
              type: 'info',
              children: false,
              li_attr: { class: 'text-muted' }
            }]);
            return;
          }

          cb([...childUnitNodes, ...employeeNodes]);
        } catch (e) {
          cb([{
            id: `info-error-${node.id}-${Date.now()}`,
            text: 'Gagal memuat data.',
            type: 'info',
            children: false,
            li_attr: { class: 'text-danger' }
          }]);
        }
      }
    },
    plugins: ['types', 'state'],
    state: {
      key: 'monitor-tree-v2', // ganti key agar state lama tidak bentrok
    },
    types: {
      workunit: { icon: 'far fa-folder' },
      employee: { icon: 'far fa-user' },
      info: { icon: 'far fa-circle' }
    }
  });

  // Delegasi klik tombol "Dokumen" -> push ke Vue Router
  $tree.on('click', '.js-doc-link', (e) => {
    e.preventDefault();
    e.stopPropagation(); // cegah jsTree select/expand saat tombol diklik
    const userId = e.currentTarget.getAttribute('data-user-id');
    if (!userId) return;
    router.push(`/admin/users/${userId}/documents`);
  });

  // (Opsional) saat klik node bisa dipakai untuk aksi lain
  $tree.on('select_node.jstree', (_e, data) => {
    // contoh: console.log('selected', data.node.id, data.node.type)
  });

  // Set instance refs (jaga-jaga jika ready belum terpanggil karena cache state)
  treeInstance.value = $tree.jstree(true);
  treeReady.value = true;
};

const reloadTree = async () => {
  // kosongkan cache employee agar fresh
  masterDataStore.clearEmployeesCache();
  await buildJsTree();
};

const resetTreeView = async () => {
  await nextTick();
  const $tree = $('#tree-container');
  const inst = $tree.jstree(true);
  if (!inst) return;

  try { inst.clear_state(); } catch (_) { }
  try {
    inst.deselect_all(true);
    inst.close_all('#', 0);
  } catch (_) { }
  try { inst.redraw(true); } catch (_) { }
  // Tidak menghapus cache di store supaya expand ulang tetap cepat
};

// ===== Lifecycle =====
onMounted(async () => {
  masterDataStore.hydrateEmployeesCache();
  await masterDataStore.getSelfWorkUnitMonitorList();
  const resMaster = masterDataStore.selfWorkUnitMonitorList;
  fullTree.value = resMaster?.data || [];
  unitById.value = indexUnits(fullTree.value);

  await buildJsTree();

  // initiation
  settingStore.resetMaintenance();
  settingStore.getSetting();
});
</script>

<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">
            Beranda
            <span v-if="settingStore.setting.maintenance == true"
                  class="badge bg-warning text-dark px-2 py-1"
                  style="font-size: 0.7rem; vertical-align: middle;">
              Maintenance
            </span>
          </h1>
        </div>
        <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Beranda</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div v-if="settingStore.setting.maintenance == true" class="row">
        <div class="col-12 mb-2">
          <div class="alert alert-warning alert-dismissible fade show " role="alert">
            <strong>Pemberitahuan Pemeliharaan Sistem</strong><br>
            Yth. Bapak/Ibu Pengguna SIGARDA, <br>
            Sehubungan dengan keterbatasan kapasitas penyimpanan pada server, kami informasikan bahwa proses
            pemeliharaan (maintenance) akan berlangsung selama 3 hari.
            <br>Selama periode tersebut, akan dilakukan proses verifikasi dan validasi (verval) agar dokumen
            yang telah tersimpan dapat dipindahkan ke Google Drive / Google Cloud Storage Bucket.
            </br>Atas perhatian dan pengertiannya, kami ucapkan terima kasih.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12 col-sm-12 col-lg-12">
          <div class="card bg-light d-flex flex-fill">
            <div class="card-header text-muted border-bottom-0 pb-0 mb-0">
              {{ authUserStore.user?.employee?.job_title }}
            </div>
            <div class="card-body pt-0">
              <div class="row">
                <div class="col-12">
                  <h2 class="lead"><b>{{ authUserStore.user.name }}</b></h2>
                  <ul class="ml-5 mb-0 fa-ul text-muted">
                    <li class="small"><span class="fa-li"><i class="far fa-id-card"></i></span> {{ authUserStore.user.username }}</li>
                    <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> {{ authUserStore.user?.employee?.work_unit?.unit_name }}</li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card-footer b-0 p-0">
              <div class="btn-group" style="width: 100% !important;">
                <router-link to="/user/profile" class="btn btn-sm btn-primary" style="display: block; font-size: small;">
                  <i class="fas fa-user"></i>&nbsp;Lihat Profil
                </router-link>
                <router-link to="/user/upload" class="btn btn-sm btn-success" style="display: block; font-size: small;">
                  Input Dokumen <i class="fas fa-arrow-circle-right"></i>
                </router-link>
                <a v-if="authUserStore.user.role == 'SUPERADMIN'" href="/oauth/google"
                   class="btn btn-sm btn-warning" style="display: block; font-size: small;">
                  <i class="fab fa-google"></i>&nbsp;Connect Google
                </a>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Monitoring Pegawai</h5>
            <div class="btn-group">
              <button class="btn btn-sm btn-primary" @click="reloadTree">
                Refresh
              </button>
              <!-- <button class="btn btn-sm btn-outline-secondary" @click="resetTreeView">
                Reset View
              </button> -->
            </div>
          </div>

          <div id="tree-container"></div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
#tree-container {
  border: 1px solid #ccc;
  border-radius: 6px;
  padding: 12px;
  background-color: #fafafa;
  min-height: 100px;
}

/* Utilities */
.text-muted { color: #6c757d !important; }
.text-danger { color: #dc3545 !important; }

/* Rapikan label pegawai & tombol */
#tree-container .emp-label {
  margin-right: .5rem;
  width: 100%;
}

/* Perkecil progress bar ala AdminLTE */
.progress-xxs {
  height: 4px;
  margin-top: 4px;
}
</style>
