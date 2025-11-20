<template>
    <div>
      <h4>Dokumen Pegawai</h4>
      <div v-for="type in documentTypes" :key="type.id" class="mb-2">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center" @click="toggleExpand(type.id)">
            {{ type.name }}
            <i class="fas" :class="expanded[type.id] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
          </div>
          <div v-if="expanded[type.id]" class="card-body">
            <div v-if="documentsByType[type.id]?.length">
              <div v-for="doc in documentsByType[type.id]" :key="doc.id" class="d-flex justify-content-between mb-2">
                <span>
                  <i class="far fa-file-pdf me-2 text-danger"></i>{{ doc.name }}
                </span>
                <div>
                  <span class="badge me-2" :class="statusClass(doc.status)">{{ doc.status }}</span>
                  <button @click="preview(doc)" class="btn btn-sm btn-outline-primary me-1">Lihat</button>
                  <button @click="openUpload(doc, true)" class="btn btn-sm btn-outline-warning">Reupload</button>
                </div>
              </div>
            </div>
            <div v-else class="text-muted">Belum ada dokumen</div>
            <button @click="openUpload({ type_id: type.id }, false)" class="btn btn-sm btn-success mt-2">Upload</button>
          </div>
        </div>
      </div>
  
      <UploadModal v-if="showUploadModal" :document="selectedDocument" :is-reupload="isReupload" :user-id="userId" @close="showUploadModal = false; refresh()" />
      <PreviewModal v-if="previewUrl" :url="previewUrl" @close="previewUrl = ''" />
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, computed } from 'vue'
  import axios from 'axios';
  import UploadModal from './UploadModal.vue'
  import PreviewModal from './PreviewModal.vue'
  
  const props = defineProps({
    userId: String,
    documentTypes: Array,
  })
  
  const expanded = ref({})
  const documents = ref([])
  const showUploadModal = ref(false)
  const previewUrl = ref('')
  const selectedDocument = ref(null)
  const isReupload = ref(false)
  
  const documentsByType = computed(() => {
    const grouped = {}
    documents.value.forEach(doc => {
      if (!grouped[doc.type_id]) grouped[doc.type_id] = []
      grouped[doc.type_id].push(doc)
    })
    return grouped
  })
  
  const toggleExpand = (typeId) => {
    expanded.value[typeId] = !expanded.value[typeId]
  }
  
  const refresh = async () => {
    const res = await axios.get(`/api/users/${props.userId}/documents`)
    documents.value = res.data
  }
  
  const openUpload = (doc, reupload) => {
    selectedDocument.value = doc
    isReupload.value = reupload
    showUploadModal.value = true
  }
  
  const preview = (doc) => {
    previewUrl.value = doc.url
  }
  
  const statusClass = (status) => {
    return {
      waiting: 'bg-warning text-dark',
      accepted: 'bg-success',
      rejected: 'bg-danger',
    }[status] || 'bg-secondary'
  }
  
  onMounted(() => {
    props.documentTypes.forEach(t => (expanded.value[t.id] = true))
    refresh()
  })
  </script>
  