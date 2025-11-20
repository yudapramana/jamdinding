<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Monitor Satuan Kerja</h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Daftar Satker</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Monitoring Pegawai</h5>
        <!-- <button class="btn btn-sm btn-primary" @click="reloadTree">Refresh</button> -->
          <div class="btn-group">
           <button class="btn btn-sm btn-primary" @click="reloadTree">
             Refresh
           </button>
           <button class="btn btn-sm btn-outline-secondary" @click="resetTreeView">
             Reset View
           </button>
         </div>
      </div>

      <div id="tree-container"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import $ from 'jquery';
import 'jstree/dist/jstree';
import 'jstree/dist/themes/default/style.css';
import { useMasterDataStore } from '../../stores/MasterDataStore';
import { useAuthUserStore } from '../../stores/AuthUserStore';
const authUserStore = useAuthUserStore();
const router = useRouter();

const fullTree = ref([]);                 // pohon work unit dari /monitor
const unitById = ref(new Map());          // map cepat id -> unit (beserta children)
const treeReady = ref(false);
const treeInstance = ref(null);
const employeesCache = ref(new Map());    // opsional cache: unitId -> array employees

// (Opsional) sanitasi sederhana untuk teks bebas (hindari HTML injeksi di label)
const escapeHtml = (s) =>
  String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

// Helper: flatten ke map id->unit (children tetap dipertahankan)
const indexUnits = (units) => {
  const map = new Map();
  const walk = (arr) => {
    for (const u of arr) {
      map.set(String(u.id), u);
      if (u.children?.length) walk(u.children);
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
    children: true,              // biar jsTree tau ada anak (lazy)
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

// Fetch employees; gunakan cache opsional agar hemat
const fetchEmployees = async (unitId) => {
  const key = String(unitId);
  // if (employeesCache.value.has(key)) return employeesCache.value.get(key);

  if (masterDataStore.employeesCacheByUnit[key]) {
     return masterDataStore.employeesCacheByUnit[key];
  }

  const res = await axios.get(`/api/work-units/${key}/employees`);
  const list = Array.isArray(res.data) ? res.data : (res.data.data ?? []);
  // employeesCache.value.set(key, list);
  masterDataStore.setEmployees(key, list);
  return list;
};

// Buat/muat ulang tree sepenuhnya
const buildJsTree = async () => {
  await nextTick();
  const $tree = $('#tree-container');

  // Hancurkan instance lama jika ada
  if ($tree.jstree(true)) $tree.jstree(true).destroy();

  // Pastikan event lama untuk tombol dokumen dibersihkan agar tidak dobel
  $tree.off('click', '.js-doc-link');

  $tree
    .off('.jstree')
    .jstree({
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

            
            // employees on-demand (dengan tombol Dokumen)
            const employees = await fetchEmployees(unitId);
            const canOpenDocs = ['SUPERADMIN', 'ADMIN'].includes(authUserStore.user?.role);

            const employeeNodes = employees.map(emp => {
              const progress = Number(emp.progress_dokumen ?? 0);
              const progressStr = progress.toFixed(2);
              const safeName = escapeHtml(emp.full_name);
              const barCls = progressClass(progress);

              return {
                id: `emp-${unitId}-${emp.id}`,
                type: 'employee',
                children: false,
                text: `
                  <span class="w-100">
                    <span class="emp-label">${safeName} — ${progress}%</span>
                    ${
                      canOpenDocs
                        ? `<a href="#"
                            class="badge badge-sm badge-info ml-2 js-doc-link"
                            data-user-id="${emp.id}">
                            <i class="far fa-folder"></i> dokumen
                          </a>`
                        : `<span class="badge badge-sm badge-secondary ml-2" title="Hanya untuk admin">
                            <i class="far fa-folder"></i> dokumen
                          </span>`
                    }
                    <div class="progress progress-xxs">
                      <div class="progress-bar ${barCls} progress-bar-striped" role="progressbar"
                          aria-valuenow="${progressStr}" aria-valuemin="0" aria-valuemax="100"
                          style="width: ${Math.max(0, Math.min(100, progress))}%">
                        <span class="sr-only">${progressStr}% Complete</span>
                      </div>
                    </div>
                  </span>
                `
              };
            });


            // const employees = await fetchEmployees(unitId);
            // const employeeNodes = employees.map(emp => {
            //   const progress = Number(emp.progress_dokumen ?? 0);
            //   const progressStr = progress.toFixed(2);
            //   const safeName = escapeHtml(emp.full_name);
            //   const barCls = progressClass(progress);

            //   return {
            //     id: `emp-${unitId}-${emp.id}`,
            //     type: 'employee',
            //     children: false,
            //     // sisipkan HTML: label + tombol "Dokumen" (delegasi klik ke router)
            //     text: `
            //       <span class="w-100">
            //         <span class="emp-label">${safeName} — ${progress}%</span>
            //         <a href="#"
            //           class="badge badge-sm badge-info ml-2 js-doc-link"
            //           data-user-id="${emp.id}">
            //           <i class="far fa-folder"></i> dokumen
            //         </a>
            //         <div class="progress progress-xxs">
            //           <div class="progress-bar ${barCls} progress-bar-striped" role="progressbar"
            //               aria-valuenow="${progressStr}" aria-valuemin="0" aria-valuemax="100" style="width: ${Math.max(0, Math.min(100, progress))}%">
            //             <span class="sr-only">${progressStr}% Complete</span>
            //           </div>
            //         </div>
            //       </span>
            //     `
            //   };
            // });

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
      // plugins: ['types'],
      plugins: ['types','state'],
      state: {
        key: 'monitor-tree-v1',     // kunci penyimpanan (ganti jika struktur berubah)
        // ttl: 0,                  // (opsional) biar tidak kadaluarsa
        // filter: (s) => s,        // (opsional) bisa memodif state sebelum apply
      },
      types: {
        workunit: { icon: 'far fa-folder' },
        employee: { icon: 'far fa-user' },
        info:     { icon: 'far fa-circle' }
      }
    });

  treeInstance.value = $tree.jstree(true);
  treeReady.value = true;

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
};

const reloadTree = async () => {
  // kosongkan cache employee agar fresh
  // employeesCache.value.clear();
  masterDataStore.clearEmployeesCache();
  await buildJsTree();
};

const resetTreeView = async () => {
  await nextTick();
  const $tree = $('#tree-container');
  const inst = $tree.jstree(true);
  if (!inst) return;

  // 1) hapus state (supaya tidak auto-expand lagi)
  try { inst.clear_state(); } catch (_) {}

  // 2) tutup semua node + clear selection
  try {
    inst.deselect_all(true);
    inst.close_all('#', 0); // tutup semua (tanpa animasi)
  } catch (_) {}

  // 3) (opsional) refresh ringan agar DOM bersih dari anak yang sempat diload
  //    ini tidak memanggil fetch ulang untuk root, hanya redraw.
  try { inst.redraw(true); } catch (_) {}

  // Catatan: kita tidak menghapus employees cache di store, supaya bila user expand lagi
  // tampilnya tetap cepat. Jika ingin benar-benar “bersih”, uncomment baris di bawah:
  // masterDataStore.clearEmployeesCache();
};



const masterDataStore = useMasterDataStore();

onMounted(async () => {
  masterDataStore.hydrateEmployeesCache();
  await masterDataStore.getWorkUnitMonitorList();
  const resMaster = masterDataStore.workUnitMonitorList;
  console.log('resMaster');
  console.log(resMaster);
  fullTree.value = resMaster.data || [];
  unitById.value = indexUnits(fullTree.value);
  await buildJsTree();
});
</script>

<style scoped>
#tree-container {
  border: 1px solid #ccc;
  border-radius: 6px;
  padding: 12px;
  background-color: #fafafa;
  min-height: 260px;
}
.text-muted { color: #6c757d !important; }
.text-danger { color: #dc3545 !important; }
/* Rapikan label pegawai & tombol */
#tree-container .emp-label { 
  margin-right: .5rem;
  width: 100%; 
}
</style>
