<template>
    <div class="tree">
        <ul class="list-unstyled mb-0">
            <li v-for="(doctype, index) in filteredTree" :key="doctype.id" class="mb-2">
                <div class="d-flex align-items-center justify-between cursor-pointer py-1 px-2 bg-light rounded small">
                    <div @click="toggleExpand(doctype)" class="flex-grow-1 d-flex align-items-center">
                        <i :class="doctype.expanded ? 'fas fa-folder-open text-warning' : 'fas fa-folder text-secondary'"
                            class="mr-2"></i>
                        <span class="ml-2 font-weight-bold">
                            {{ index + 1 }}. {{ doctype.text }}
                            <span class="badge badge-pill badge-primary ml-2">{{ doctype.files.length }}</span>
                        </span>
                    </div>
                    <button class="btn btn-sm btn-success ml-2" @click="openUploadModal(doctype)">+ Upload</button>
                </div>

                <ul v-show="doctype.expanded" class="pl-4 mt-1">

                    <li v-for="file in doctype.files" :key="file.id"
                        class="cursor-pointer p-1 rounded hover-bg-light small">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div @click="previewFile(file)" class="d-flex align-items-center">
                                <i class="fas fa-file-pdf text-danger mr-2"></i>
                                <span>{{ file.file_name }}</span>
                                <span class="badge badge-sm ml-2" :class="badgeClass(file.status)">
                                    {{ file.status || 'Pending' }}
                                </span>
                            </div>
                            <button v-if="file.status === 'Rejected'" class="btn btn-sm btn-outline-danger ml-2"
                                @click="reuploadFile(file, doctype)">
                                Reupload
                            </button>
                        </div>

                        <div v-if="file.status === 'Rejected' && file.verif_notes" class="text-danger mt-1 ml-4 small">
                            <i class="fas fa-info-circle"></i> Catatan: {{ file.verif_notes }}
                        </div>
                    </li>


                    <li v-if="!doctype.files.length" class="text-muted small ml-4 mt-1">
                        Tidak ada file diupload.
                    </li>
                </ul>
            </li>
        </ul>
    </div>

    <PreviewModal 
    :preview-url="previewUrl" 
    :selected-preview-file="selectedPreviewFile" 
    :is-loading-verval="isLoadingVerval"
    :vervalLogs="vervalLogs"
    :pdfError="pdfError"
    />
    <!-- <UploadModal /> -->
</template>

<script setup>
import axios from 'axios';
import PreviewModal from './PreviewModal.vue';
import UploadModal from './UploadModal.vue';
import { ref, computed, onMounted } from 'vue';

const pdfError = ref(false);
const selectedPreviewFile = ref(null);
const previewUrl = ref(null);
const isLoadingVerval = ref(false);
const vervalLogs = ref([]);
const onIframeLoad = () => { pdfError.value = false; };
const onIframeError = () => { pdfError.value = true; };

const props = defineProps({
    filteredTree: Array,
});

const toggleExpand = (doctype) => {
    doctype.expanded = !doctype.expanded;
};

const fetchVervalLog = async (fileId) => {
    isLoadingVerval.value = true;
    try {
        const res = await axios.get(`/api/document-log/${fileId}`);
        vervalLogs.value = res.data.data || [];
    } catch (error) {
        console.error('Gagal mengambil log verval:', error);
        vervalLogs.value = [];
    } finally {
        isLoadingVerval.value = false;
    }
};

const previewFile = async (file) => {
    console.log('file');
    console.log(file);

    pdfError.value = false;
    selectedPreviewFile.value = file;
    previewUrl.value = file.file_url;
    try {
        const res = await fetch(file.file_url, { method: 'HEAD' });

        if (!res.ok) {
            if (res.status === 404) {
                pdfError.value = 'not_found';
            } else {
                pdfError.value = true;
            }
            return;
        }

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('pdf')) {
            pdfError.value = true;
        }
    } catch (err) {
        pdfError.value = true;
    }
    await fetchVervalLog(file.id);
};

const badgeClass = (status) => {
  switch (status) {
    case 'Approved':
      return 'badge-success'; // Hijau
    case 'Rejected':
      return 'badge-danger';  // Merah
    default:
      return 'badge-secondary'; // Abu-abu untuk Pending atau undefined
  }
};
</script>