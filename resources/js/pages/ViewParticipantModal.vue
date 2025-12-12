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
            <!-- BIODATA -->
            <div class="col-md-8 mb-3">
              <div class="card shadow-sm border">
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

                  <!-- ✅ Catatan Daftar Ulang (muncul jika rejected) -->
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
            </div>

            <!-- BERKAS + TANGGAL -->
            <div class="col-md-4">
              <!-- BERKAS PESERTA -->
              <div class="card shadow-sm border mb-3">
                <div class="card-header border-0 py-2">
                  <span class="font-weight-bold">Berkas Peserta</span>
                </div>
                <div class="card-body p-0">
                  <div
                    v-if="selectedParticipant.participant?.photo_url"
                    class="mx-auto rounded-circle overflow-hidden border"
                    style="width:180px;height:180px;"
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
                    class="mx-auto text-muted"
                    style="align-items: center; text-align: center;"
                  >
                    Tidak ada foto
                  </div>

                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Foto</span>
                      <span
                        class="badge badge-pill"
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
                        class="badge badge-pill"
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
                        class="badge badge-pill"
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
                        class="badge badge-pill"
                        :class="hasFileDetail('bank_book_url') ? 'badge-success' : 'badge-secondary'"
                        @click="openFileDetail('bank_book_url')"
                        style="cursor: pointer;"
                      >
                        <i :class="hasFileDetail('bank_book_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                        {{ hasFileDetail('bank_book_url') ? 'Ada' : 'Kosong' }}
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Piagam Penghargaan</span>
                      <span
                        class="badge badge-pill"
                        :class="hasFileDetail('certificate_url') ? 'badge-success' : 'badge-secondary'"
                        @click="openFileDetail('certificate_url')"
                        style="cursor: pointer;"
                      >
                        <i :class="hasFileDetail('certificate_url') ? 'fas fa-check' : 'fas fa-times'"></i>
                        {{ hasFileDetail('certificate_url') ? 'Ada' : 'Kosong' }}
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Berkas Lain</span>
                      <span
                        class="badge badge-pill"
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
              <div class="card shadow-sm border">
                <div class="card-body p-0">
                  <table class="table table-sm mb-0 mx-auto text-center">
                    <tbody>
                      <tr>
                        <th>
                          Tanggal Input Biodata<br />
                          <span class="text-right text-danger font-weight-bold">
                            {{ formatDateTime(selectedParticipant.participant?.created_at) }}
                          </span>
                        </th>
                      </tr>

                      <tr>
                        <th>
                          Tanggal Update Biodata<br />
                          <span class="text-right text-danger font-weight-bold">
                            {{ formatDateTime(selectedParticipant.participant?.updated_at) }}
                          </span>
                        </th>
                      </tr>
                      <!-- kalau mau pakai tanggal registrasi lomba dari eventparticipant, bisa buka lagi yang ini
                      <tr>
                        <th>
                          Tanggal Registrasi Lomba<br />
                          <span class="text-right text-danger font-weight-bold">
                            {{ formatDateTime(selectedParticipant.created_at) }}
                          </span>
                        </th>
                      </tr>
                      <tr>
                        <th>
                          Update Registrasi Lomba<br />
                          <span class="text-right text-danger font-weight-bold">
                            {{ formatDateTime(selectedParticipant.updated_at) }}
                          </span>
                        </th>
                      </tr>
                      -->
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- VERIFIKASI PESERTA (participant_verifications) -->
              <div class="card shadow-sm border mt-3" v-if="hasVerificationsLoaded">
                <div class="card-header border-0 py-2 d-flex justify-content-between align-items-center">
                  <span class="font-weight-bold">Riwayat Verifikasi</span>

                  <span v-if="verificationEntries.length" class="badge badge-light border">
                    {{ verificationEntries.length }} data
                  </span>
                </div>

                <div class="card-body p-2">
                  <!-- Jika relasi tidak di-load sama sekali -->
                  <div v-if="!hasVerificationsLoaded" class="text-muted text-sm text-center py-2">
                    Verifikasi belum dimuat (aktifkan <code>withVerifications</code> di list).
                  </div>

                  <!-- Jika relasi di-load tapi kosong -->
                  <div v-else-if="verificationEntries.length === 0" class="text-muted text-sm text-center py-2">
                    Belum ada catatan verifikasi.
                  </div>

                  <!-- Jika ada data -->
                  <div v-else>
                    <div
                      v-for="(v, idx) in verificationEntries"
                      :key="v.id || idx"
                      class="border rounded p-2 mb-2"
                    >
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="font-weight-bold">
                            <span class="badge" :class="verificationStatusClass(v.status)">
                              {{ v.status || '-' }}
                            </span>
                            <span class="text-muted text-xs ml-2">
                              #{{ v.id || '-' }}
                            </span>
                          </div>

                          <div class="text-xs text-muted mt-1">
                            Waktu:
                            <strong>{{ formatDateTime(v.verified_at || v.created_at) }}</strong>
                          </div>

                          <div class="text-xs text-muted">
                            Petugas:
                            <strong>{{ v.verifier?.name || v.verified_by_name || v.verified_by || '-' }}</strong>
                          </div>
                        </div>

                        <div class="text-right">
                          <div class="text-xs text-muted">Checklist</div>
                          <div class="font-weight-bold">
                            {{ countChecked(v).checked }} / {{ countChecked(v).total }}
                          </div>
                        </div>
                      </div>

                      <!-- Badges ringkas -->
                      <div class="mt-2 d-flex flex-wrap">
                        <span class="badge badge-light border mr-1 mb-1" :class="v.checked_photo ? 'badge-success' : 'badge-secondary'">
                          Foto
                        </span>
                        <span class="badge badge-light border mr-1 mb-1" :class="v.checked_id_card ? 'badge-success' : 'badge-secondary'">
                          KTP
                        </span>
                        <span class="badge badge-light border mr-1 mb-1" :class="v.checked_family_card ? 'badge-success' : 'badge-secondary'">
                          KK
                        </span>
                        <span class="badge badge-light border mr-1 mb-1" :class="v.checked_bank_book ? 'badge-success' : 'badge-secondary'">
                          Tabungan
                        </span>
                        <span class="badge badge-light border mr-1 mb-1" :class="v.checked_certificate ? 'badge-success' : 'badge-secondary'">
                          Sertifikat
                        </span>
                        <span class="badge badge-light border mr-1 mb-1" :class="v.checked_other ? 'badge-success' : 'badge-secondary'">
                          Lainnya
                        </span>
                      </div>

                      <div class="mt-1 d-flex flex-wrap">
                        <span class="badge badge-info mr-1 mb-1" v-if="v.checked_identity">Identitas</span>
                        <span class="badge badge-info mr-1 mb-1" v-if="v.checked_contact">Kontak</span>
                        <span class="badge badge-info mr-1 mb-1" v-if="v.checked_domicile">Domisili</span>
                        <span class="badge badge-info mr-1 mb-1" v-if="v.checked_education">Pendidikan</span>
                        <span class="badge badge-info mr-1 mb-1" v-if="v.checked_bank_account">Rekening</span>
                        <span class="badge badge-info mr-1 mb-1" v-if="v.checked_document_dates">Tgl Dokumen</span>
                      </div>

                      <!-- Notes -->
                      <div v-if="v.notes" class="mt-2 text-sm">
                        <div class="text-muted text-xs mb-1">Catatan:</div>
                        <div class="border rounded p-2 bg-light">{{ v.notes }}</div>
                      </div>

                      <!-- field_matches (JSON) -->
                      <details v-if="v.field_matches" class="mt-2">
                        <summary class="text-xs text-muted" style="cursor:pointer;">
                          Detail field_matches
                        </summary>
                        <pre class="mb-0 mt-2 p-2 bg-dark text-light rounded text-xs">{{ safeJson(v.field_matches) }}</pre>
                      </details>
                    </div>
                  </div>
                </div>
              </div>


            </div>
          </div>
        </div>

        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue'
import { formatDate, formatDateTime } from './EventParticipantHelpers'

const props = defineProps({
  selectedParticipant: { type: Object, default: null },
})

const hasFileDetail = (field) => {
  if (!props.selectedParticipant?.participant) return false
  return !!props.selectedParticipant.participant[field]
}

const openFileDetail = (field) => {
  const url = props.selectedParticipant?.participant?.[field]
  if (!url) return
  window.open(url, '_blank')
}

/** ✅ Normalisasi verifications: bisa dari latestVerification, latest_verification, atau array verifications */
const verificationEntries = computed(() => {
  const sp = props.selectedParticipant
  if (!sp) return []

  // kemungkinan bentuk relasi yang dipakai backend
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
  const sp = props.selectedParticipant
  if (!sp) return false
  // kalau salah satu key ada, berarti memang di-load (meski kosong)
  return (
    'verifications' in sp ||
    'latestVerification' in sp ||
    'latest_verification' in sp ||
    'participant_verifications' in sp
  )
})

/** ✅ Helper status badge */
const verificationStatusClass = (status) => {
  if (status === 'verified') return 'badge-success'
  if (status === 'rejected') return 'badge-danger'
  return 'badge-secondary'
}

/** ✅ Hitung checklist ringkas (berapa yang dicentang) */
const countChecked = (v) => {
  if (!v) return { checked: 0, total: 0 }

  const keys = [
    // dokumen
    'checked_photo',
    'checked_id_card',
    'checked_family_card',
    'checked_bank_book',
    'checked_certificate',
    'checked_other',
    // kelompok data
    'checked_identity',
    'checked_contact',
    'checked_domicile',
    'checked_education',
    'checked_bank_account',
    'checked_document_dates',
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
</script>

