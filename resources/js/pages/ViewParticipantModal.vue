<template>
  <!-- Modal Lihat Data Peserta -->
  <div class="modal fade" id="viewParticipantModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header py-2">
          <h5 class="modal-title">
            <i class="fas fa-id-card-alt mr-2"></i>
            Detail Peserta
          </h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body" v-if="selectedParticipant">
          <div class="row">
            <!-- BIODATA & RIWAYAT VERIFIKASI (KIRI) -->
            <div class="col-md-8 mb-3">
              <!-- BIODATA -->
              <div class="card shadow-sm border mb-3">
                <div class="card-header border-0 d-flex justify-content-between align-items-center py-2">
                  <span class="font-weight-bold">Biodata Peserta</span>
                </div>

                <div class="card-body p-0">
                  <table class="table table-sm mb-0">
                    <tbody>
                      <tr>
                        <th style="width:35%;">Nama</th>
                        <td class="text-uppercase font-weight-bold">
                          {{ selectedParticipant.participant?.full_name }}
                        </td>
                      </tr>
                      <tr>
                        <th>NIK</th>
                        <td class="text-monospace">
                          {{ selectedParticipant.participant?.nik }}
                        </td>
                      </tr>
                      <tr>
                        <th>Tempat Lahir</th>
                        <td class="text-uppercase">
                          {{ selectedParticipant.participant?.place_of_birth || '-' }}
                        </td>
                      </tr>
                      <tr>
                        <th>Tanggal Lahir</th>
                        <td>
                          <span class="text-danger font-weight-bold mr-2">
                            {{ formatDate(selectedParticipant.participant?.date_of_birth) }}
                          </span>
                          <span v-if="selectedParticipant.age_year != null">
                            ({{ selectedParticipant.age_year }}T
                            {{ selectedParticipant.age_month }}B
                            {{ selectedParticipant.age_day }}H)
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <th>Telepon</th>
                        <td>{{ selectedParticipant.participant?.phone_number || '-' }}</td>
                      </tr>
                      <tr>
                        <th>Jenis Kelamin</th>
                        <td class="text-uppercase">
                          {{
                            selectedParticipant.participant?.gender === 'MALE' ||
                            selectedParticipant.participant?.gender === 'L'
                              ? 'LAKI-LAKI'
                              : 'PEREMPUAN'
                          }}
                        </td>
                      </tr>
                      <tr>
                        <th>Cabang Lomba</th>
                        <td class="text-uppercase">
                          {{
                             selectedParticipant.event_group?.full_name
                            || selectedParticipant.event_branch?.full_name
                            || '-'
                          }}
                        </td>
                      </tr>
                      <tr>
                        <th>Kategori</th>
                        <td class="text-uppercase">
                          {{
                            [
                              selectedParticipant.event_category?.category_name
                            ].filter(Boolean).join(' / ') || '-'
                          }}
                        </td>
                      </tr>
                      <tr>
                        <th>Asal / Kontingen</th>
                        <td class="text-uppercase">
                          {{
                            selectedParticipant.contingent
                            || selectedParticipant.participant?.district_name
                            || selectedParticipant.participant?.regency_name
                            || selectedParticipant.participant?.province_name
                            || '-'
                          }}
                        </td>
                      </tr>
                      <tr>
                        <th>Alamat</th>
                        <td class="text-uppercase">
                          {{ selectedParticipant.participant?.address || '-' }}
                        </td>
                      </tr>
                      <tr>
                        <th>Pendidikan</th>
                        <td class="text-uppercase">
                          {{ selectedParticipant.participant?.education || '-' }}
                        </td>
                      </tr>
                      <tr>
                        <th>Nomor Rekening</th>
                        <td class="text-uppercase">
                          {{ selectedParticipant.participant?.bank_account_number || '-' }}
                        </td>
                      </tr>
                      <tr>
                        <th>Akun Rekening</th>
                        <td class="text-uppercase">
                          {{ selectedParticipant.participant?.bank_account_name || '-' }}
                        </td>
                      </tr>
                      <tr>
                        <th>Bank Rekening</th>
                        <td class="text-uppercase">
                          {{ selectedParticipant.participant?.bank_name || '-' }}
                        </td>
                      </tr>
                      <tr>
                        <th>Kategori Peserta</th>
                        <td class="text-uppercase">
                          PESERTA INTI
                        </td>
                      </tr>
                      <tr>
                        <th>Terbit KTP</th>
                        <td class="text-danger font-weight-bold">
                          {{ formatDate(selectedParticipant.participant?.tanggal_terbit_ktp) }}
                        </td>
                      </tr>
                      <tr>
                        <th>Terbit KK</th>
                        <td class="text-danger font-weight-bold">
                          {{ formatDate(selectedParticipant.participant?.tanggal_terbit_kk) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <!-- Catatan Daftar Ulang -->
                  <div
                    v-if="selectedParticipant?.reregistration_status === 'rejected'"
                    class="p-3 border-top"
                  >
                    <div class="alert alert-danger mb-0">
                      <div class="font-weight-bold mb-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Daftar Ulang Ditolak
                      </div>

                      <div class="text-sm">
                        {{ selectedParticipant?.reregistration_notes || 'Tidak ada catatan penolakan.' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- VERIFIKASI PESERTA BOX -->
              <div class="card shadow-sm border mt-3">
                <div class="card-header border-0 py-2 d-flex justify-content-between align-items-center bg-light">
                  <div class="d-flex align-items-center">
                    <span class="font-weight-bold mr-2"><i class="fas fa-clipboard-check text-primary mr-1"></i> Riwayat Verifikasi</span>
                    <span v-if="verificationEntries.length" class="badge badge-primary border">
                      {{ verificationEntries.length }} data
                    </span>
                  </div>
                  <button 
                    class="btn btn-sm btn-outline-primary font-weight-bold" 
                    @click="fetchVerificationHistory" 
                    :disabled="isLoadingHistory"
                  >
                    <i class="fas fa-sync-alt mr-1" :class="{ 'fa-spin': isLoadingHistory }"></i>
                    {{ isLoadingHistory ? 'Memuat...' : 'Muat Riwayat Lengkap' }}
                  </button>
                </div>

                <div class="card-body p-2 bg-white">
                  <div v-if="isLoadingHistory" class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Memuat riwayat...
                  </div>

                  <template v-else>
                    <!-- JIKA ADA RIWAYAT YANG DIKLIK / DETAIL -->
                    <div v-if="selectedVerificationDetail" class="detail-verifikasi">
                      <button class="btn btn-sm btn-light border w-100 mb-2 font-weight-bold text-left" @click="closeVerificationDetail">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Riwayat
                      </button>

                      <div class="border rounded p-3 bg-light shadow-sm">
                        <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                          <div>
                            <div class="font-weight-bold d-flex align-items-center flex-wrap">
                              <span class="badge px-2 py-1 mr-2 mb-1" :class="registrationBadgeClass(selectedVerificationDetail.status)">
                                {{ registrationStatusLabel(selectedVerificationDetail.status).toUpperCase() }}
                              </span>
                              
                              <span v-if="selectedVerificationDetail.registration_status" class="badge badge-light border px-2 py-1 mr-2 mb-1" title="Keputusan Status Pendaftaran">
                                <i class="fas fa-flag text-muted mr-1"></i>
                                Keputusan: {{ registrationStatusLabel(selectedVerificationDetail.registration_status) }}
                              </span>

                              <span class="text-muted text-xs mb-1">
                                #{{ selectedVerificationDetail.id || '-' }}
                              </span>
                            </div>
                            <div class="text-xs text-muted mt-2">
                              Waktu Verifikasi:
                              <strong class="text-dark">{{ formatDateTime(selectedVerificationDetail.verified_at || selectedVerificationDetail.created_at) }}</strong>
                            </div>
                            <div class="text-xs text-muted">
                              Petugas:
                              <strong class="text-dark">{{ selectedVerificationDetail.verified_by?.name || selectedVerificationDetail.verifier?.name || selectedVerificationDetail.verified_by_name || '-' }}</strong>
                            </div>
                          </div>
                          <div class="text-right">
                            <div class="text-xs text-muted">Checklist Progress</div>
                            <div class="font-weight-bold h5 mb-0 text-primary">
                              {{ countChecked(selectedVerificationDetail).checked }} / {{ countChecked(selectedVerificationDetail).total }}
                            </div>
                          </div>
                        </div>

                        <!-- Badges ringkas -->
                        <div class="mt-3">
                          <div class="text-xs font-weight-bold text-muted mb-1">Pemeriksaan Dokumen Fisik/File:</div>
                          <div class="d-flex flex-wrap">
                            <span class="badge border mr-1 mb-1 py-1 px-2" :class="selectedVerificationDetail.checked_photo ? 'badge-success' : 'badge-light text-muted'">Foto</span>
                            <span class="badge border mr-1 mb-1 py-1 px-2" :class="selectedVerificationDetail.checked_id_card ? 'badge-success' : 'badge-light text-muted'">KTP</span>
                            <span class="badge border mr-1 mb-1 py-1 px-2" :class="selectedVerificationDetail.checked_family_card ? 'badge-success' : 'badge-light text-muted'">KK</span>
                            <span class="badge border mr-1 mb-1 py-1 px-2" :class="selectedVerificationDetail.checked_bank_book ? 'badge-success' : 'badge-light text-muted'">Tabungan</span>
                            <span class="badge border mr-1 mb-1 py-1 px-2" :class="selectedVerificationDetail.checked_certificate ? 'badge-success' : 'badge-light text-muted'">Sertifikat</span>
                            <span class="badge border mr-1 mb-1 py-1 px-2" :class="selectedVerificationDetail.checked_other ? 'badge-success' : 'badge-light text-muted'">Lainnya</span>
                          </div>
                        </div>

                        <div class="mt-2">
                          <div class="text-xs font-weight-bold text-muted mb-1">Pemeriksaan Kesesuaian Data:</div>
                          <div class="d-flex flex-wrap">
                            <span class="badge badge-info mr-1 mb-1 py-1 px-2" v-if="selectedVerificationDetail.checked_identity">Identitas</span>
                            <span class="badge badge-info mr-1 mb-1 py-1 px-2" v-if="selectedVerificationDetail.checked_contact">Kontak</span>
                            <span class="badge badge-info mr-1 mb-1 py-1 px-2" v-if="selectedVerificationDetail.checked_domicile">Domisili</span>
                            <span class="badge badge-info mr-1 mb-1 py-1 px-2" v-if="selectedVerificationDetail.checked_education">Pendidikan</span>
                            <span class="badge badge-info mr-1 mb-1 py-1 px-2" v-if="selectedVerificationDetail.checked_bank_account">Rekening</span>
                            <span class="badge badge-info mr-1 mb-1 py-1 px-2" v-if="selectedVerificationDetail.checked_document_dates">Tgl Dokumen</span>
                          </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="selectedVerificationDetail.notes" class="mt-3 text-sm border-top pt-2">
                          <div class="text-muted text-xs font-weight-bold mb-1">Catatan Penolakan / Perbaikan:</div>
                          <div class="border border-danger rounded p-2 bg-white text-danger font-weight-bold">
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ selectedVerificationDetail.notes }}
                          </div>
                        </div>

                        <!-- field_matches (DETAIL UI & JSON) -->
                        <div v-if="parsedFieldMatches" class="mt-3 border-top pt-3">
                          <div class="text-xs font-weight-bold text-dark mb-3">
                            <i class="fas fa-list-check text-primary mr-1"></i> Detail Kesesuaian Data (Validasi per Item):
                          </div>
                          
                          <!-- Masonry Grid yang merapatkan posisi setiap kategori ke atas -->
                          <div class="masonry-layout">
                            <div v-for="(fields, category) in parsedFieldMatches" :key="category" class="card shadow-sm border mb-3 masonry-item">
                              <div class="card-header bg-white py-1 px-2 border-bottom text-center">
                                <span class="text-xs font-weight-bold text-uppercase text-secondary">{{ getFieldLabel(category, null) }}</span>
                              </div>
                              <ul class="list-group list-group-flush text-xs">
                                <li v-for="(value, key) in fields" :key="key" class="list-group-item d-flex justify-content-between align-items-center py-1 px-2">
                                  <span class="text-muted">{{ getFieldLabel(category, key) }}</span>
                                  <span v-if="value === true" class="badge badge-success px-2"><i class="fas fa-check mr-1"></i>Sesuai</span>
                                  <span v-else-if="value === false" class="badge badge-danger px-2"><i class="fas fa-times mr-1"></i>Tidak Sesuai</span>
                                </li>
                              </ul>
                            </div>
                          </div>

                          <details class="mt-2 pt-2 text-center">
                            <summary class="text-xs font-weight-bold text-muted" style="cursor:pointer; outline: none;">
                              <i class="fas fa-code mr-1"></i> Lihat Data Mentah JSON
                            </summary>
                            <pre class="mb-0 mt-2 p-2 bg-dark text-light text-left rounded text-xs" style="max-height: 200px; overflow-y: auto; border: 1px solid #444;">{{ safeJson(selectedVerificationDetail.field_matches) }}</pre>
                          </details>
                        </div>
                      </div>
                    </div>

                    <!-- JIKA DAFTAR RIWAYAT -->
                    <div v-else>
                      <div v-if="verificationEntries.length === 0" class="text-muted text-sm text-center py-4 bg-light border rounded border-dashed">
                        <i class="fas fa-clipboard text-secondary fa-2x mb-2 opacity-50"></i><br>
                        Belum ada catatan verifikasi untuk peserta ini. <br>
                        <small>Klik "Muat Riwayat Lengkap" untuk mengecek ulang ke server.</small>
                      </div>

                      <div v-else>
                        <div
                          v-for="(v, idx) in verificationEntries"
                          :key="v.id || idx"
                          class="border rounded p-3 mb-2 bg-white shadow-sm riwayat-hover"
                          @click="viewVerificationDetail(v)"
                          style="cursor: pointer;"
                        >
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <div class="font-weight-bold mb-1 d-flex flex-wrap align-items-center">
                                <span class="badge mr-2 mb-1" :class="registrationBadgeClass(v.status)">
                                  {{ registrationStatusLabel(v.status).toUpperCase() }}
                                </span>
                                
                                <span v-if="v.registration_status" class="badge badge-light border mb-1" title="Keputusan Status Pendaftaran">
                                  <i class="fas fa-flag text-muted mr-1"></i>
                                  Keputusan: {{ registrationStatusLabel(v.registration_status) }}
                                </span>
                              </div>
                              
                              <div class="text-xs text-muted">
                                <i class="far fa-clock mr-1"></i> <strong>{{ formatDateTime(v.verified_at || v.created_at) }}</strong>
                              </div>
                              <div class="text-xs text-muted mt-1">
                                <i class="far fa-user mr-1"></i> Oleh: <strong>{{ v.verified_by?.name || v.verifier?.name || v.verified_by_name || 'Petugas Sistem' }}</strong>
                              </div>
                            </div>
                            
                            <div class="text-right d-flex flex-column align-items-end justify-content-center h-100">
                              <span class="badge badge-light border mb-2 text-muted">
                                Checklist: {{ countChecked(v).checked }}/{{ countChecked(v).total }}
                              </span>
                              <div class="text-primary text-xs font-weight-bold bg-light px-2 py-1 rounded">
                                Lihat Detail <i class="fas fa-chevron-right ml-1 text-xs"></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>

                </div>
              </div>

            </div>

            <!-- BERKAS + TANGGAL (KANAN) -->
            <div class="col-md-4">
              <!-- BERKAS PESERTA -->
              <div class="card shadow-sm border mb-3">
                <div class="card-header border-0 py-2">
                  <span class="font-weight-bold">Berkas Peserta</span>
                </div>
                <div class="card-body p-0">
                  <div
                    v-if="selectedParticipant.participant?.photo_url"
                    class="mx-auto rounded-circle overflow-hidden border mt-3 mb-3"
                    style="width:160px;height:160px;"
                  >
                    <img
                      :src="selectedParticipant.participant.photo_url"
                      alt="Foto Peserta"
                      class="img-fluid"
                      style="object-fit:cover;width:100%;height:100%;"
                    />
                  </div>

                  <div
                    v-else
                    class="mx-auto text-muted mt-4 mb-4"
                    style="align-items: center; text-align: center;"
                  >
                    <i class="fas fa-user-circle fa-4x opacity-50 mb-2"></i><br>
                    Tidak ada foto
                  </div>

                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Foto</span>
                      <span
                        class="badge badge-pill px-3"
                        :class="hasFileDetail('photo_url') ? 'badge-success' : 'badge-secondary'"
                        @click="openFileDetail('photo_url')"
                        style="cursor: pointer;"
                      >
                        <i :class="hasFileDetail('photo_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                        {{ hasFileDetail('photo_url') ? 'Ada' : 'Kosong' }}
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>KTP</span>
                      <span
                        class="badge badge-pill px-3"
                        :class="hasFileDetail('id_card_url') ? 'badge-success' : 'badge-secondary'"
                        @click="openFileDetail('id_card_url')"
                        style="cursor: pointer;"
                      >
                        <i :class="hasFileDetail('id_card_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                        {{ hasFileDetail('id_card_url') ? 'Ada' : 'Kosong' }}
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Kartu Keluarga</span>
                      <span
                        class="badge badge-pill px-3"
                        :class="hasFileDetail('family_card_url') ? 'badge-success' : 'badge-secondary'"
                        @click="openFileDetail('family_card_url')"
                        style="cursor: pointer;"
                      >
                        <i :class="hasFileDetail('family_card_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                        {{ hasFileDetail('family_card_url') ? 'Ada' : 'Kosong' }}
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Buku Tabungan</span>
                      <span
                        class="badge badge-pill px-3"
                        :class="hasFileDetail('bank_book_url') ? 'badge-success' : 'badge-secondary'"
                        @click="openFileDetail('bank_book_url')"
                        style="cursor: pointer;"
                      >
                        <i :class="hasFileDetail('bank_book_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                        {{ hasFileDetail('bank_book_url') ? 'Ada' : 'Kosong' }}
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Piagam</span>
                      <span
                        class="badge badge-pill px-3"
                        :class="hasFileDetail('certificate_url') ? 'badge-success' : 'badge-secondary'"
                        @click="openFileDetail('certificate_url')"
                        style="cursor: pointer;"
                      >
                        <i :class="hasFileDetail('certificate_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                        {{ hasFileDetail('certificate_url') ? 'Ada' : 'Kosong' }}
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Akta</span>
                      <span
                        class="badge badge-pill px-3"
                        :class="hasFileDetail('other_url') ? 'badge-success' : 'badge-secondary'"
                        @click="openFileDetail('other_url')"
                        style="cursor: pointer;"
                      >
                        <i :class="hasFileDetail('other_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                        {{ hasFileDetail('other_url') ? 'Ada' : 'Kosong' }}
                      </span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- TANGGAL DATA -->
              <div class="card shadow-sm border mb-3">
                <div class="card-body p-0">
                  <table class="table table-sm mb-0 mx-auto text-center">
                    <tbody>
                      <tr>
                        <th class="py-2">
                          <i class="fas fa-sign-in-alt text-muted mr-1"></i> Tanggal Input Biodata<br />
                          <span class="text-danger font-weight-bold mt-1 d-block">
                            {{ formatDateTime(selectedParticipant.participant?.created_at) }}
                          </span>
                        </th>
                      </tr>
                      <tr>
                        <th class="py-2">
                          <i class="fas fa-edit text-muted mr-1"></i> Tanggal Update Biodata<br />
                          <span class="text-danger font-weight-bold mt-1 d-block">
                            {{ formatDateTime(selectedParticipant.participant?.updated_at) }}
                          </span>
                        </th>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="modal-footer py-2 bg-light">
          <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed, ref, watch } from 'vue'
import axios from 'axios'
import { 
  formatDate, 
  formatDateTime,
  registrationBadgeClass,
  registrationStatusLabel,
} from './EventParticipantHelpers'

const props = defineProps({
  selectedParticipant: { type: Object, default: null },
})

const isLoadingHistory = ref(false)
const fetchedVerifications = ref([])
const selectedVerificationDetail = ref(null)

// Reset state saat modal dibuka kembali / berganti peserta
watch(() => props.selectedParticipant, () => {
  fetchedVerifications.value = []
  selectedVerificationDetail.value = null
})

// Fungsi memanggil API ParticipantVerificationController@index
const fetchVerificationHistory = async () => {
  const identifier = props.selectedParticipant?.id
  if (!identifier) return

  isLoadingHistory.value = true
  try {
    const res = await axios.get(`/api/v1/event-participants/${identifier}/verifications`)
    fetchedVerifications.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat riwayat verifikasi:', error)
  } finally {
    isLoadingHistory.value = false
  }
}

// Handler klik detail riwayat
const viewVerificationDetail = (v) => {
  selectedVerificationDetail.value = v
}

// Kembali ke daftar dari detail
const closeVerificationDetail = () => {
  selectedVerificationDetail.value = null
}

const hasFileDetail = (field) => {
  if (!props.selectedParticipant?.participant) return false
  return !!props.selectedParticipant.participant[field]
}

const openFileDetail = (field) => {
  const url = props.selectedParticipant?.participant?.[field]
  if (!url) return
  window.open(url, '_blank')
}

const verificationEntries = computed(() => {
  if (fetchedVerifications.value.length > 0) {
    return fetchedVerifications.value
  }

  const sp = props.selectedParticipant
  if (!sp) return []

  const latest =
    sp.latestVerification ||
    sp.latest_verification ||
    sp.latest_verification_data ||
    null

  const list =
    sp.verifications ||
    sp.participant_verifications ||
    sp.verification_logs ||
    null

  if (Array.isArray(list) && list.length) return list
  if (latest) return [latest]
  return []
})

const hasVerificationsLoaded = computed(() => {
  if (fetchedVerifications.value.length > 0) return true
  
  const sp = props.selectedParticipant
  if (!sp) return false
  return (
    'verifications' in sp ||
    'latestVerification' in sp ||
    'latest_verification' in sp ||
    'participant_verifications' in sp
  )
})

const verificationStatusClass = (status) => {
  if (status === 'verified') return 'badge-success'
  if (status === 'rejected') return 'badge-danger'
  return 'badge-secondary'
}

const verificationStatusLabel = (status) => {
  const labels = {
    'bank_data': 'Bank Data',
    'process': 'Proses',
    'need_revision': 'Revisi',
    'verified': 'Terverifikasi',
    'rejected': 'Ditolak',
    'disqualified': 'Mundur/Gugur'
  }
  return labels[status] || status || '-'
}

const countChecked = (v) => {
  if (!v) return { checked: 0, total: 0 }
  const keys = [
    'checked_photo','checked_id_card','checked_family_card','checked_bank_book',
    'checked_certificate','checked_other','checked_identity','checked_contact',
    'checked_domicile','checked_education','checked_bank_account','checked_document_dates'
  ]
  const total = keys.length
  const checked = keys.reduce((sum, k) => sum + (v[k] ? 1 : 0), 0)
  return { checked, total }
}

const safeJson = (obj) => {
  try {
    return obj ? JSON.stringify(obj, null, 2) : ''
  } catch {
    return ''
  }
}

// -------------------------------------------------------------
// HELPER UNTUK KONVERSI FIELD_MATCHES KE UI YANG LEBIH RAPI
// -------------------------------------------------------------

// Mendapatkan nilai label per kategori dan field
const getFieldLabel = (category, key) => {
  const categoryLabels = {
    identity: 'Identitas',
    contact: 'Kontak',
    domicile: 'Domisili',
    education: 'Pendidikan',
    bank_account: 'Rekening Bank',
    document_dates: 'Tanggal Dokumen',
    documents: 'Dokumen Fisik'
  }

  const fieldLabels = {
    identity: {
      nik: 'NIK',
      full_name: 'Nama Lengkap',
      place_of_birth: 'Tempat Lahir',
      date_of_birth: 'Tanggal Lahir',
      gender: 'Jenis Kelamin'
    },
    contact: {
      phone_number: 'No. Telepon'
    },
    domicile: {
      province_id: 'ID Provinsi',
      regency_id: 'ID Kab/Kota',
      district_id: 'ID Kecamatan',
      village_id: 'ID Desa/Kel.',
      address: 'Alamat',
      province_name: 'Nama Provinsi',
      regency_name: 'Nama Kab/Kota',
      district_name: 'Nama Kecamatan',
      village_name: 'Nama Desa/Kel.'
    },
    education: {
      education: 'Pendidikan'
    },
    bank_account: {
      bank_account_number: 'No. Rekening',
      bank_account_name: 'Nama Rekening',
      bank_name: 'Nama Bank'
    },
    document_dates: {
      tanggal_terbit_ktp: 'Tgl Terbit KTP',
      tanggal_terbit_kk: 'Tgl Terbit KK'
    },
    documents: {
      photo_url: 'Foto',
      id_card_url: 'KTP',
      family_card_url: 'Kartu Keluarga',
      bank_book_url: 'Buku Tabungan',
      certificate_url: 'Sertifikat/Piagam',
      other_url: 'Akta Kelahiran' // Catatan Khusus
    }
  }

  if (key === null) return categoryLabels[category] || category
  return fieldLabels[category]?.[key] || key
}

// Compute proper JSON dan filter data null
const parsedFieldMatches = computed(() => {
  let data = selectedVerificationDetail.value?.field_matches
  if (!data) return null
  
  if (typeof data === 'string') {
    try {
      data = JSON.parse(data)
    } catch (e) {
      return null
    }
  }

  // Filter membuang field yang bernilai null, 
  // dan jika satu kategori (misal: domisili) kosong, kotak tersebut tidak akan dirender
  const filteredData = {}
  
  if (data && typeof data === 'object') {
    for (const category in data) {
      if (data[category] && typeof data[category] === 'object') {
        const fields = data[category]
        const validFields = {}
        let hasValidField = false
        
        for (const key in fields) {
          // Hanya masukkan ke data jika valuenya tidak null
          if (fields[key] !== null) {
            validFields[key] = fields[key]
            hasValidField = true
          }
        }
        
        if (hasValidField) {
          filteredData[category] = validFields
        }
      }
    }
  }
  
  return Object.keys(filteredData).length > 0 ? filteredData : null
})
</script>

<style scoped>
.riwayat-hover:hover {
  background-color: #f0f7ff !important;
  border-color: #b8daff !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
  transition: all 0.2s ease-in-out;
}
.riwayat-hover {
  transition: all 0.2s ease-in-out;
}
.border-dashed {
  border: 2px dashed #dee2e6;
}

/* Kustomisasi css kolom (Masonry) untuk layout box yang rapat */
.masonry-layout {
  column-count: 2;
  column-gap: 1rem;
}
.masonry-item {
  break-inside: avoid;
  page-break-inside: avoid;
  display: inline-block; /* Mencegah terpotong */
  width: 100%;
}

@media (max-width: 767px) {
  .masonry-layout {
    column-count: 1;
  }
}
</style>