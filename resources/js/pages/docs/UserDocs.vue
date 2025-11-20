<template>
    <!-- <div class="container mt-4"> -->
        <!-- <h3>Dokumen Pegawai</h3> -->
        <!-- <p class="text-muted">Menampilkan dokumen untuk User ID: {{ selectedUserId }}</p> -->

        <AdminDocumentViewer :userId="selectedUserId" />
    <!-- </div> -->
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import AdminDocumentViewer from '../../components/AdminDocumentViewer.vue'

// Ambil ID user dari route
const route = useRoute();
const selectedUserId = ref(null);

onMounted(() => {
    // selectedUserId.value = route.params.id;
    selectedUserId.value = parseInt(route.params.id, 10);
});
</script>

<style scoped>
.container {
    max-width: 960px;
}
</style>