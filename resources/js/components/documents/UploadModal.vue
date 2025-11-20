<template>
    <div class="modal d-block" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form @submit.prevent="submit">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ isReupload ? 'Reupload' : 'Upload' }} Dokumen</h5>
                        <button type="button" class="btn-close" @click="$emit('close')"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Tanggal Dokumen</label>
                            <input type="date" class="form-control" v-model="form.doc_date" required />
                        </div>
                        <div class="mb-3">
                            <label>File PDF</label>
                            <input type="file" class="form-control" accept="application/pdf" @change="handleFile"
                                required />
                        </div>
                        <div v-if="progress > 0" class="progress mb-2">
                            <div class="progress-bar" :style="{ width: progress + '%' }">{{ progress }}%</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="$emit('close')">Batal</button>
                        <button class="btn btn-primary" :disabled="loading">Upload</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-backdrop show"></div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios';
import Swal from 'sweetalert2'

const props = defineProps(['document', 'isReupload', 'userId'])
const emit = defineEmits(['close'])

const form = ref({
    doc_date: '',
    file: null,
})
const progress = ref(0)
const loading = ref(false)

const handleFile = (e) => {
    form.value.file = e.target.files[0]
}

const submit = async () => {
    if (!form.value.file) return
    const today = new Date().toISOString().split('T')[0]
    if (form.value.doc_date > today) {
        Swal.fire('Tanggal tidak valid', 'Tanggal dokumen tidak boleh di masa depan', 'warning')
        return
    }

    loading.value = true
    const data = new FormData()
    data.append('file', form.value.file)
    data.append('doc_date', form.value.doc_date)

    const url = props.isReupload
        ? `/api/documents/${props.document.id}`
        : `/api/users/${props.userId}/documents?type_id=${props.document.type_id}`

    const method = props.isReupload ? 'post' : 'post'

    try {
        await axios[method](url, data, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (e) => {
                progress.value = Math.round((e.loaded * 100) / e.total)
            },
        })
        Swal.fire('Berhasil', 'Dokumen berhasil diunggah', 'success')
        emit('close')
    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal upload'
        Swal.fire('Error', msg, 'error')
    } finally {
        loading.value = false
    }
}
</script>