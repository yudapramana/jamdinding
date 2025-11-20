<template>
    <div>
      <DocumentTree
        :user-id="userId"
        :document-types="documentTypes"
      />
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRoute } from 'vue-router'
  import axios from 'axios';
  import DocumentTree from '../../components/documents/DocumentTree.vue'
  import { useMasterDataStore } from '../../stores/MasterDataStore';

  const route = useRoute()
  const userId = route.params.id
  const documentTypes = ref([])
  const masterDataStore = useMasterDataStore();
  
  onMounted(async () => {
    await masterDataStore.getDoctypeList();
    documentTypes.value = masterDataStore.doctypeList;
  })
  </script>