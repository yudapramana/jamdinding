<template>
    <div v-if="showUploadModal" class="modal fade show" style="display: block;" tabindex="-1" aria-modal="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column;">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Dokumen Pegawai: {{ selectedDoctype?.text }}</h5>
                    <button type="button" class="close" @click="closeUploadModal"><span>&times;</span></button>
                </div>

                <div class="modal-body overflow-auto" style="flex: 1 1 auto;">
                    <form @submit.prevent="submitUpload" id="uploadForm">
                        <!-- Hidden ID_DOC_TYPE -->
                        <input type="hidden" :value="selectedDoctype?.id" />

                        <div class="form-group">
                            <label>Tipe Dokumen</label>
                            <input type="text" class="form-control" :value="selectedDoctype?.text" readonly>
                        </div>

                        <div class="form-group">
                            <label>Nomor Dokumen</label>
                            <input v-model="uploadForm.doc_number" type="text" class="form-control"  autofocus>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Dokumen</label>
                            <input v-model="uploadForm.doc_date" type="date" class="form-control">
                        </div>

                        <div class="form-group">

                            <label>Pilih Parameter (Opsional)</label><br>
                            <div class="btn-group mb-2 flex-wrap">
                                <button v-for="item in masterDataStore.docParameters" :key="item" type="button"
                                    class="btn btn-xs btn-outline-secondary mb-1"
                                    :class="{ active: uploadForm.parameter === item }"
                                    @click="uploadForm.parameter = item">
                                    {{ item }}
                                </button>
                            </div>

                            <div class="input-group mb-3">
                                <input v-model="uploadForm.parameter" type="text" class="form-control" readonly>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-info"
                                        @click="uploadForm.parameter = ''">Reset</button>
                                </div>
                            </div>
                            <small class="form-text text-muted mb-2">
                                <strong>Info:</strong> Gunakan parameter diatas jika dokumen pada tipe ini bersifat
                                multiple atau lebih dari
                                satu,
                                seperti <em>Akte Kelahiran Anak</em>, <em>SKP 2 Tahun Terakhir</em>, <em>SK
                                    Jabatan</em>, dan
                                sejenisnya.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Pilih File (PDF)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="exampleInputFile"
                                    accept="application/pdf" @change="handleFileChange" required>
                                <label class="custom-file-label" for="exampleInputFile">
                                    {{ uploadForm.fileName || 'Pilih file' }}
                                </label>
                            </div>
                        </div>



                        <div v-if="loadingUpload" class="mb-3">
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    :class="progressBarColor" role="progressbar"
                                    :style="{ width: uploadProgress + '%' }" :aria-valuenow="uploadProgress"
                                    aria-valuemin="0" aria-valuemax="100">
                                    {{ uploadProgress }}%
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" :disabled="loadingUpload" form="uploadForm">
                        <span v-if="loadingUpload">
                            <i class="fas fa-spinner fa-spin"></i> Uploading...
                        </span>
                        <span v-else>
                            Upload
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>