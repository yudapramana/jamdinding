<template>
    <div v-if="previewUrl" class="modal fade show" style="display: block;" tabindex="-1" aria-modal="true" id="preview-modal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header p-2" :class="isApprovedPreview ? 'bg-success' : 'bg-secondary'">
                    <!-- <h5 class="modal-title">📄 Preview Dokumen</h5> -->
                     <!-- <h5 class="modal-title">
                        📄 {{ isApprovedPreview ? 'Dokumen Disetujui' : 'Dokumen Diajukan' }}
                    </h5> -->
                    <span v-if="isApprovedPreview">
                        <span style="font-size:x-large !important;">Dokumen Disetujui</span> 
                        <span class="badge bg-success ms-2">File tersimpan di GCloud Bucket Storage</span>
                    </span>
                    <span v-else>
                        <span style="font-size:x-large !important;">Dokumen Diajukan</span> 
                        <span class="badge bg-warning ms-2">Menunggu Persetujuan</span>
                    </span>
                    <button type="button" class="close" @click="$emit('close')"><span>&times;</span></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row">
                        <div class="col-md-6 mb-3" >
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Tipe Dokumen</th>
                                        <td>{{ selectedPreviewFile?.doc_type_text || '—' }}</td>
                                    </tr>
                                    <!-- <tr>
                                        <th>Nomor Dokumen</th>
                                        <td>{{ selectedPreviewFile?.doc_number || '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Dokumen</th>
                                        <td>{{ selectedPreviewFile?.doc_date || '—' }}</td>
                                    </tr> -->
                                    <tr>
                                        <th>Parameter</th>
                                        <td>{{ selectedPreviewFile?.parameter || '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge" :class="badgeClass(selectedPreviewFile?.status)">
                                                {{ selectedPreviewFile?.status || 'Pending' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="selectedPreviewFile?.verif_notes">
                                        <th>Catatan Verifikator</th>
                                        <td class="text-danger">{{ selectedPreviewFile.verif_notes }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- <div class="col-md-12 mt-4"> -->
                            <h6 class="text-secondary mb-2">
                                <i class="fas fa-clipboard-check mr-1"></i> Riwayat Verifikasi
                            </h6>

                            <div v-if="isLoadingVerval" class="text-muted small d-flex align-items-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Mengambil data...
                            </div>

                            <ul v-else-if="vervalLogs.length" class="list-group list-group-unbordered small mb-2">
                                <li v-for="(log, idx) in vervalLogs" :key="idx" class="list-group-item py-2 px-2"
                                    style="line-height: 1.4;">
                                    <div>
                                        <span class="font-weight-bold text-sm">{{ log.status }}</span>
                                        <span class="text-muted mx-1">oleh</span>
                                        <span class="font-italic text-sm">{{ log.verifier_name }}</span>
                                        <small class="text-muted"> pada {{ log.verified_at }}</small>
                                    </div>
                                    <div v-if="log.notes" class="text-danger mt-1 small">
                                        <i class="fas fa-comment-dots mr-1"></i>{{ log.notes }}
                                    </div>
                                </li>
                            </ul>
                            <div v-else class="text-muted small">Tidak ada log verifikasi.</div>
                        </div>
                        <!-- </div> -->
                        <div class="col-md-6">
                            <iframe v-if="previewUrl && !pdfError" ref="pdfFrame"
                                :src="`${previewUrl}#toolbar=0&navpanes=0&scrollbar=0`" class="w-100" frameborder="0"
                                style="height: 70vh; border: 1px solid #ccc;" @load="onIframeLoad"
                                @error="onIframeError"></iframe>

                            <div v-else class="text-muted text-center py-5">
                                <p v-if="pdfError">Gagal memuat dokumen. Pastikan file tersedia dan dapat diakses.
                                </p>
                                <p v-else>File tidak tersedia.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Modal } from 'bootstrap'

const props = defineProps({
    previewUrl: String,
    selectedPreviewFile: Object,
    isLoadingVerval: Boolean,
    vervalLogs: Array,
    pdfError: Boolean,
    isApprovedPreview: Boolean
});

const emits = defineEmits(['close']);

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