<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Satuan Kerja</h1>
                </div>
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
          <h5>Work Unit Tree</h5>
          <button class="btn btn-sm btn-primary" @click="fetchTree">Refresh</button>
        </div>
        <div id="tree-container" class="mb-3"></div>
  
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-success" @click="openAddModal">Tambah Unit</button>
          <button class="btn btn-sm btn-danger" @click="deleteWorkUnit" :disabled="!selectedUnit.id">Hapus Unit</button>
        </div>
  
        <!-- Edit/Add Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">{{ isEditing ? 'Edit' : 'Tambah' }} Work Unit</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
              </div>
              <form @submit.prevent="isEditing ? updateWorkUnit() : createWorkUnit()">
                <div class="modal-body">
                  <div class="form-group">
                    <label>Unit Name</label>
                    <input v-model="selectedUnit.unit_name" class="form-control form-control-sm" required />
                  </div>
                  <div class="form-group">
                    <label>Unit Code</label>
                    <input v-model="selectedUnit.unit_code" class="form-control form-control-sm" required />
                  </div>
                  <div class="form-group">
                    <label>Parent Unit</label>
                    <select v-model="selectedUnit.parent_unit" class="form-control form-control-sm">
                      <option :value="null">-- Tidak Ada --</option>
                      <option v-for="unit in flatUnits" :key="unit.id" :value="unit.id">
                        {{ unit.unit_name }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, nextTick } from 'vue';
  import axios from 'axios';
  import $ from 'jquery';
  import 'jstree/dist/jstree';
  import 'jstree/dist/themes/default/style.css';
  
  const selectedUnit = ref({ id: null, unit_name: '', unit_code: '', parent_unit: null });
  const isEditing = ref(false);
  const flatUnits = ref([]);
  
  const fetchTree = () => {
    axios.get('/api/work-units/tree')
      .then(res => {
        flatUnits.value = flattenUnits(res.data);
        const treeData = convertToJsTree(res.data);
        renderTree(treeData);
      });
  };
  
  const flattenUnits = (units, result = []) => {
    for (const unit of units) {
      result.push({ id: unit.id, unit_name: unit.unit_name });
      if (unit.children?.length) flattenUnits(unit.children, result);
    }
    return result;
  };
  
  const convertToJsTree = (units) => {
    return units.flatMap(unit => {
      const node = {
        id: unit.id,
        parent: unit.parent_unit ?? '#',
        text: unit.unit_name,
        original: unit,
        state: {
          opened: true
        }
      };
      const children = unit.children ? convertToJsTree(unit.children) : [];
      return [node, ...children];
    });
  };
  
  const renderTree = (data) => {
    nextTick(() => {
      const $tree = $('#tree-container');
  
      if ($tree.jstree(true)) {
        $tree.jstree(true).destroy();
      }
  
      $tree.jstree({
        core: {
          data,
          themes: { stripes: true }
        }
      });
  
      $tree.on("select_node.jstree", (e, data) => {
        selectedUnit.value = {
          id: data.node.id,
          unit_name: data.node.original.original.unit_name,
          unit_code: data.node.original.original.unit_code,
          parent_unit: data.node.original.original.parent_unit,
        };
        isEditing.value = true;
        $('#editModal').modal('show');
      });
    });
  };
  
  const updateWorkUnit = () => {
    axios.put(`/api/work-units/${selectedUnit.value.id}`, selectedUnit.value)
      .then(() => {
        $('#editModal').modal('hide');
        fetchTree();
      });
  };
  
  const createWorkUnit = () => {
    axios.post('/api/work-units', selectedUnit.value)
      .then(() => {
        $('#editModal').modal('hide');
        fetchTree();
      });
  };
  
  const deleteWorkUnit = () => {
    if (!selectedUnit.value.id || !confirm('Hapus unit ini?')) return;
  
    axios.delete(`/api/work-units/${selectedUnit.value.id}`)
      .then(() => {
        selectedUnit.value = { id: null, unit_name: '', unit_code: '', parent_unit: null };
        fetchTree();
      });
  };
  
  const openAddModal = () => {
    selectedUnit.value = { id: null, unit_name: '', unit_code: '', parent_unit: null };
    isEditing.value = false;
    $('#editModal').modal('show');
  };
  
  onMounted(fetchTree);
  </script>
  
  <style scoped>
  #tree-container {
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 12px;
    background-color: #fafafa;
  }
  </style>
  