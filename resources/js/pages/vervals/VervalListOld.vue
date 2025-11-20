<template>
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="mb-2">Verifikasi Dokumen Pegawai</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex">
                    <input v-model="search" type="text" class="form-control mr-2"
                        placeholder="Cari nama atau NIP pegawai..." />
                    <button class="btn btn-secondary btn-sm" @click="fetchDocuments">
                        Refresh
                    </button>
                </div>

                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-hover text-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>NIP</th>
                                <th>Jenis Dokumen</th>
                                <th>Nama File</th>
                                <!-- <th>Nomor</th>
                                <th>Tanggal</th> -->
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading">
                                <td colspan="8" class="text-center">Memuat data...</td>
                            </tr>
                            <tr v-else-if="documents.length === 0">
                                <td colspan="8" class="text-center">Tidak ada dokumen ditemukan.</td>
                            </tr>
                            <tr v-for="(doc, index) in documents" :key="doc.id">
                                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                                <td>{{ doc.employee.full_name }}</td>
                                <td>{{ doc.employee.nip }}</td>
                                <td>{{ doc.doc_type.type_name }}</td>
                                <td>{{ doc.file_name }}</td>
                                <!-- <td>{{ doc.doc_number }}</td>
                                <td>{{ doc.doc_date }}</td> -->
                                <td>
                                    <span :class="{
                                        'badge badge-warning': doc.status === 'Pending',
                                        'badge badge-success': doc.status === 'Approved',
                                        'badge badge-danger': doc.status === 'Rejected',
                                    }">
                                        {{ doc.status }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary mr-1" @click="openVerifModal(doc)">
                                        Verifikasi
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer clearfix">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} dokumen
                        </div>
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page - 1)">«</a>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
                            </li>
                            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                                <a class="page-link" href="#" @click.prevent="changePage(meta.current_page + 1)">»</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Verifikasi -->
        <div class="modal fade" id="verifModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title">Verifikasi Dokumen Pegawai</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body row">
                        <div class="col-md-7">
                            <!-- <div class="border rounded p-2" style="height: 500px; overflow: hidden;">
                                <iframe v-if="selectedDoc?.file_url" :src="selectedDoc.file_url" width="100%"
                                    height="100%" frameborder="0" style="border: 1px solid #ccc;"></iframe>
                                <div v-else class="text-muted text-center py-5">
                                    <p>File tidak tersedia.</p>
                                </div>
                            </div> -->
                            <div class="border rounded p-2" style="height: 500px; overflow: hidden;">
                                <!-- <iframe v-if="selectedDoc?.file_url && !pdfError" ref="pdfFrame"
                                    :src="selectedDoc.file_url" width="100%" height="100%" frameborder="0"
                                    style="border: 1px solid #ccc;" @load="onIframeLoad"
                                    @error="onIframeError"></iframe> -->

                                <iframe v-if="selectedDoc?.file_url && !pdfError" ref="pdfFrame" :src="iframeSrc"
                                    width="100%" height="100%" frameborder="0" style="border: 1px solid #ccc;"
                                    @load="onIframeLoad" @error="onIframeError"></iframe>

                                <div v-else class="text-muted text-center py-5">
                                    <p v-if="pdfError">Gagal memuat dokumen. Pastikan file tersedia dan dapat diakses.
                                    </p>
                                    <p v-else>File tidak tersedia.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="mb-3">
                                <table class="table table-bordered table-sm">
                                    <tbody>
                                        <tr>
                                            <th>Nama</th>
                                            <td>{{ selectedDoc?.employee?.full_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>NIP</th>
                                            <td>{{ selectedDoc?.employee?.nip || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Jenis Dokumen</th>
                                            <td>{{ selectedDoc?.doc_type?.type_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Parameter</th>
                                            <td>{{ selectedDoc?.parameter || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Filename</th>
                                            <td style="font-size: smaller;">{{ selectedDoc?.file_name || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <form @submit.prevent="submitVerif">
                                <div class="form-group">
                                    <label>Status Verifikasi</label>
                                    <select v-model="verifForm.status" class="form-control" required>
                                        <option value="Approved">Disetujui</option>
                                        <option value="Rejected">Ditolak</option>
                                    </select>
                                </div>
                                <div class="form-group" v-if="verifForm.status === 'Rejected'">
                                    <label>Pilih Alasan Penolakan (opsional)</label>
                                    <select class="form-control mb-2" @change="onRejectionNoteSelect($event)">
                                        <option value="">-- Pilih alasan penolakan standar --</option>
                                        <option v-for="item in rejectionNotes" :key="item.code" :value="item.note">
                                            {{ item.code }} - {{ item.note }}
                                        </option>
                                    </select>
                                </div>
                                <!-- <div class="form-group">
                                    <label>Catatan Verifikasi</label>
                                    <textarea v-model="verifForm.verif_notes" class="form-control" rows="4"
                                        placeholder="Tulis catatan jika dokumen ditolak..."></textarea>
                                </div> -->
                                <div class="form-group">
                                    <label>Catatan Verifikasi</label>
                                    <textarea v-model="verifForm.verif_notes" class="form-control" rows="4"
                                        placeholder="Tulis catatan jika dokumen ditolak..."></textarea>
                                </div>

                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-sm btn-primary" :disabled="isSubmitting">
                                        <i v-if="isSubmitting" class="fas fa-spinner fa-spin me-1"></i>
                                        Simpan Verifikasi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div> <!-- /.modal-body -->
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';
import Swal from 'sweetalert2';

const search = ref('');
const documents = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const pdfFrame = ref(null);
const pdfError = ref(false);
const selectedDoc = ref(null);
const verifForm = ref({ status: '', verif_notes: '' });


const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)

const iframeSrc = computed(() => {
  const url = selectedDoc.value?.file_url
  if (!url) return ''
  return isMobile
    ? `https://docs.google.com/gview?url=${encodeURIComponent(url)}`
    : url
})


const meta = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    last_page: 1,
});

const rejectionNotes = [
    { code: 'G001', note: 'Dokumen yang diunggah tidak sesuai dengan jenis dokumen yang dipilih.' },
    { code: 'G002', note: 'Identitas pada dokumen tidak sesuai dengan data pegawai.' },
    { code: 'G003', note: 'Dokumen bukan termasuk dokumen resmi yang dibutuhkan.' },
    { code: 'G004', note: 'Informasi pada dokumen tidak terbaca dengan jelas (buram/tidak fokus).' },
    { code: 'G005', note: 'Dokumen tidak mencantumkan informasi identitas penting (nama, tanggal lahir, NIP/NIM, dsb.).' },
    { code: 'G006', note: 'Dokumen tidak mencantumkan cap resmi, tanda tangan, atau atribut legalitas lainnya.' },
    { code: 'G007', note: 'Format atau isi dokumen tidak relevan dengan tujuan verifikasi.' },
    { code: 'G008', note: 'Dokumen rusak atau tidak dapat dibuka.' },
    { code: 'G009', note: 'Tanggal dokumen tidak valid atau tidak sesuai konteks.' },
    { code: 'G010', note: 'Dokumen mengandung data palsu atau terindikasi tidak asli.' },
]

const fetchDocuments = async (page = 1) => {
    isLoading.value = true
    try {
        const res = await axios.get('/api/emp-documents', {
            params: {
                search: search.value,
                page,
                per_page: meta.value.per_page,
            },
        })
        documents.value = res.data.data
        meta.value = {
            ...meta.value,
            ...res.data.meta,
            ...res.data,
        }
    } catch (err) {
        console.error('Gagal memuat dokumen', err)
    } finally {
        isLoading.value = false
    }
}

const changePage = (page) => {
    if (page >= 1 && page <= meta.value.last_page) {
        fetchDocuments(page)
    }
}

const openVerifModal = (doc) => {
    selectedDoc.value = doc
    verifForm.value = {
        status: doc.status,
        verif_notes: doc.verif_notes || '',
    }
    $('#verifModal').modal('show')
}

// const submitVerif = async () => {
//     isSubmitting.value = true
//     try {
//         await axios.put(`/api/emp-documents/${selectedDoc.value.id}/verify`, verifForm.value)
//         $('#verifModal').modal('hide')
//         fetchDocuments(meta.value.current_page)
//     } catch (error) {
//         alert('Gagal memverifikasi dokumen.')
//     } finally {
//         isSubmitting.value = false
//     }
// }

const submitVerif = async () => {
    isSubmitting.value = true
    try {
        const response = await axios.put(`/api/emp-documents/${selectedDoc.value.id}/verify`, verifForm.value)
        $('#verifModal').modal('hide')
        fetchDocuments(meta.value.current_page)

        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: response.data.message || 'Dokumen berhasil diverifikasi.',
            timer: 2000,
            showConfirmButton: false,
        })
    } catch (error) {
        let message = 'Gagal memverifikasi dokumen.'
        if (error.response) {
            if (error.response.status === 409 && error.response.data.code === 'DOCUMENT_ALREADY_VERIFIED') {
                message = error.response.data.message
            } else if (error.response.data.message) {
                message = error.response.data.message
            }
        }

        Swal.fire({
            icon: 'error',
            title: 'Verifikasi Gagal',
            text: message,
        });
        $('#verifModal').modal('hide');
        fetchDocuments(meta.value.current_page);
    } finally {
        isSubmitting.value = false
    }
}

const onRejectionNoteSelect = (event) => {
    const selected = event.target.value
    if (selected) {
        verifForm.value.verif_notes = selected
    }
}


const checkPdfLoad = () => {
    try {
        const iframe = pdfFrame.value;

        // Jika iframe belum siap, skip
        if (!iframe || !iframe.contentDocument) return;

        const content = iframe.contentDocument.body.innerText || '';

        // Deteksi error umum dari PDF.js atau viewer
        if (content.toLowerCase().includes('error') || content.toLowerCase().includes('not found')) {
            pdfError.value = true;
        }
    } catch (e) {
        pdfError.value = true;
    }
};

const onIframeLoad = () => {
    // Iframe load berhasil
    pdfError.value = false;
};

const onIframeError = () => {
    // Jika iframe gagal load
    pdfError.value = true;
};


watch(search, useDebounceFn(() => fetchDocuments(1), 300))
// Trigger pengecekan setelah dokumen berubah dan iframe selesai render
// Alternatif: cek apakah file tersedia dulu
watch(() => selectedDoc.value?.file_url, async (newUrl) => {
    pdfError.value = false;

    if (!newUrl) return;

    try {
        const res = await fetch(newUrl, { method: 'HEAD' }); // hanya cek status tanpa ambil isi
        if (!res.ok || !res.headers.get('content-type')?.includes('pdf')) {
            pdfError.value = true;
        }
    } catch (err) {
        pdfError.value = true;
    }
});

onMounted(() => fetchDocuments())
</script>