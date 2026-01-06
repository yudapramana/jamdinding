<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Pendaftaran Peserta Event</h1>
          <p class="mb-0 text-muted text-sm">
            Mengelola peserta yang terdaftar pada event aktif, termasuk status pendaftaran dan daftar ulang.
          </p>
        </div>
      </div>

      <p v-if="!eventId" class="text-danger text-sm mt-2 mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Event belum dipilih. Silakan pilih event melalui Portal Event terlebih dahulu.
      </p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <!-- SIDEBAR STATUS -->
        <div class="col-md-2">
          <div class="card card-outline card-primary">
            <div class="card-header py-2">
              <h3 class="card-title text-sm mb-0">
                <i class="fas fa-filter mr-1"></i> Status 
              </h3>
            </div>

            <div class="list-group list-group-flush text-sm">
              <button
                v-for="s in statusList"
                :key="s.key"
                type="button"
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                :class="{ active: activeStatus === s.key }"
                @click="changeStatus(s.key)"
              >
                <span class="text-capitalize">{{ s.label }}</span>
                <span class="badge badge-pill" :class="s.badgeClass">
                  {{ statusCounts[s.key] || 0 }}
                </span>
              </button>
            </div>
          </div>
        </div>

        <!-- KONTEN UTAMA -->
        <div class="col-md-10">
          <div class="card">
            <div class="card-header">
              <div class="d-flex flex-wrap align-items-center justify-content-between">

            <!-- LEFT: FILTERS -->
            <div class="d-flex flex-wrap align-items-center gap-2">

              <!-- PER PAGE -->
              <select
                v-model.number="perPage"
                class="form-control form-control-sm w-auto"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="text-xs text-muted">entri</span>

              <!-- CABANG / GOLONGAN -->
              <select
                v-model="filters.event_group_id"
                class="form-control form-control-sm w-auto"
                title="Cabang / Golongan"
              >
                <option value="">Semua Cabang</option>
                <option
                  v-for="g in masterDataStore.eventGroups"
                  :key="g.id"
                  :value="String(g.id)"
                >
                  {{ g.full_name || g.name || g.group_name || ('Gol #' + g.id) }}
                </option>
              </select>

            </div>

            <!-- RIGHT: SEARCH -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm mt-2 mt-md-0"
              style="width: 220px"
              placeholder="Cari nama / NIK / kontingen"
            />

          </div>
            </div>

            <div class="card-body table-responsive p-0">
              <table class="table table-bordered table-hover text-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 40px;">#</th>
                    <th>Peserta</th>
                    <th>NIK &amp; Umur</th>
                    <th>Cabang / Golongan</th>
                    <th>Kontingen</th>
                    <th>Progress Lampiran</th>
                    <th style="width: 120px;" class="text-center">Aksi</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-if="isLoading">
                    <td colspan="7" class="text-center">Memuat data peserta event...</td>
                  </tr>

                  <tr v-else-if="items.length === 0">
                    <td colspan="7" class="text-center">
                      Belum ada peserta terdaftar untuk event ini.
                      <br />
                      <small class="text-muted">
                        Klik <strong>Tambah Peserta Event</strong> untuk menambahkan peserta.
                      </small>
                    </td>
                  </tr>

                  <tr v-for="(item, index) in items" :key="item.id">
                    <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>

                    <td>
                      <strong>{{ item.participant?.full_name }}</strong>
                      <br />
                      <span
                        class="badge mr-1"
                        style="width: 17px;"
                        :class="item.participant?.gender === 'MALE' ? 'badge-primary' : 'badge-pink'"
                      >
                        <i :class="item.participant?.gender === 'MALE' ? 'fas fa-mars' : 'fas fa-venus'"></i>
                      </span>

                      <span class="badge" :class="registrationBadgeClass(item.registration_status)">
                        {{ registrationStatusLabel(item.registration_status) }}
                      </span>
                    </td>

                    <td>
                      <strong>{{ item.participant?.nik }}</strong>
                      <div v-if="item.age_year !== null" class="text-xs text-muted">
                        Umur: {{ item.age_year }}T {{ item.age_month }}B {{ item.age_day }}H
                      </div>
                    </td>

                    <td>
                      <strong>{{ item.event_group?.full_name }}</strong>
                      <div v-if="item.event_group" class="text-xs text-muted">
                        Batas: {{ (item.event_group?.max_age ?? 0) - 1 }}T 11B 29H
                      </div>
                    </td>

                    <td>
                      <span class="badge badge-light border">
                        {{ item.contingent || '-' }}
                      </span>
                    </td>

                    <td class="align-center text-center">
                      <div class="progress" style="height: 16px; font-size: 10px;">
                        <div
                          class="progress-bar d-flex justify-content-center align-items-center"
                          :class="progressBarClass(item.participant?.lampiran_completion_percent)"
                          role="progressbar"
                          :style="{ width: (item.participant?.lampiran_completion_percent || 0) + '%' }"
                          :aria-valuenow="item.participant?.lampiran_completion_percent || 0"
                          aria-valuemin="0"
                          aria-valuemax="100"
                        >
                          {{ item.participant?.lampiran_completion_percent || 0 }}%
                        </div>
                      </div>
                    </td>

                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <!-- PREVIEW DATA -->
                        <button
                          class="btn btn-outline-primary btn-xs"
                          title="Lihat Data Peserta"
                          @click="openViewModal(item)"
                        >
                          <i class="fas fa-eye"></i>
                        </button>

                        <!-- CETAK / LIHAT BIODATA PDF -->
                        <button
                          class="btn btn-outline-danger btn-xs"
                          title="Cetak Biodata (PDF)"
                          @click="openBiodataPdf(item)"
                        >
                          <i class="far fa-file-pdf"></i>
                        </button>

                        <button
                          v-if="canShowVerifyButton(item)"
                          class="btn btn-outline-success btn-xs"
                          title="Verifikasi Peserta"
                          @click="openVerification(item)"
                        >
                          <i class="fas fa-clipboard-check"></i>
                        </button>

                      </div>

                      
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="card-footer clearfix py-2">
              <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted text-sm">
                  Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total || 0 }} peserta event
                </div>

                <ul class="pagination pagination-sm m-0">
                  <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                    <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
                  </li>

                  <li class="page-item disabled">
                    <span class="page-link">Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}</span>
                  </li>

                  <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                    <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">»</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- /col-md-10 -->
      </div>
    </div>

    <!-- MODAL VERIFIKASI PESERTA -->
    <div class="modal fade" id="showVerificationModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">Verifikasi Data & Dokumen Peserta</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <!-- ✅ selectedEventParticipant = row event_participants -->
          <!-- ✅ selectedParticipant      = data participants -->
          <div class="modal-body" v-if="selectedEventParticipant && selectedParticipant">
            <!-- Info singkat peserta -->
            <div class="mb-3">
              <h6 class="mb-1">{{ selectedParticipant.full_name || '-' }}</h6>
              <p class="mb-0 text-muted text-sm">
                NIK: {{ selectedParticipant.nik || '-' }}<br>
                Cabang: {{ selectedBranchName }}<br>
                Status Saat Ini:
                <span class="badge ml-1" :class="registrationBadgeClass(selectedEventParticipant.registration_status)">
                  {{ registrationStatusLabel(selectedEventParticipant.registration_status) }}
                </span>
              </p>
            </div>

            <hr class="my-2">

            

            <!-- ====================== -->
            <!-- 1. DOKUMEN UNGGAHAN -->
            <!-- ====================== -->
            <div class="card mb-3">
              <div class="card-header py-2">
                <strong>1. Dokumen Unggahan Peserta</strong>
                <div class="small text-muted">PDF ditampilkan di panel, gambar tampil sebagai preview.</div>
              </div>

              <div class="card-body p-2">

                <!-- FOTO -->
                <div class="mb-3" v-if="selectedParticipant.photo_url">
                  <div class="d-flex flex-wrap">
                    <div class="mb-2 mr-md-3" style="min-width:240px; max-width:260px;">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>Foto Peserta</strong>
                      </div>

                      <div class="form-group mb-1">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="chkPhoto" v-model="verificationForm.checked_photo">
                          <label class="form-check-label" for="chkPhoto">Sudah dicek</label>
                        </div>
                      </div>

                      <div class="form-group mb-0">
                        <label class="small mb-1">Hasil kesesuaian</label>
                        <select class="form-control form-control-sm" v-model="verificationForm.field_matches.documents.photo_url">
                          <option :value="null">Belum dinilai</option>
                          <option :value="true">Sesuai</option>
                          <option :value="false">Tidak sesuai</option>
                        </select>
                      </div>
                    </div>

                    <div class="flex-grow-1">
                      <template v-if="String(selectedParticipant.photo_url).toLowerCase().endsWith('.pdf')">
                        <div style="height:360px; border:1px solid #dee2e6;" class="mb-1">
                          <iframe :src="selectedParticipant.photo_url" style="width:100%; height:100%; border:0;"></iframe>
                        </div>
                        <a :href="selectedParticipant.photo_url" target="_blank" class="btn btn-xs btn-outline-primary">
                          <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                        </a>
                      </template>
                      <template v-else>
                        <img :src="selectedParticipant.photo_url" class="img-fluid img-thumbnail mb-1" style="max-height:360px;" alt="Foto Peserta">
                        <div>
                          <a :href="selectedParticipant.photo_url" target="_blank" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </div>
                      </template>
                    </div>
                  </div>

                  <hr class="my-2"
                      v-if="selectedParticipant.id_card_url || selectedParticipant.family_card_url || selectedParticipant.bank_book_url || selectedParticipant.certificate_url || selectedParticipant.other_url">
                </div>

                <!-- KTP / KIA -->
                <div class="mb-3" v-if="selectedParticipant.id_card_url">
                  <div class="d-flex flex-wrap">
                    <div class="mb-2 mr-md-3" style="min-width:240px; max-width:260px;">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>KTP / KIA</strong>
                      </div>

                      <div class="form-group mb-1">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="chkIdCard" v-model="verificationForm.checked_id_card">
                          <label class="form-check-label" for="chkIdCard">Sudah dicek</label>
                        </div>
                      </div>

                      <div class="form-group mb-0">
                        <label class="small mb-1">Hasil kesesuaian</label>
                        <select class="form-control form-control-sm" v-model="verificationForm.field_matches.documents.id_card_url">
                          <option :value="null">Belum dinilai</option>
                          <option :value="true">Sesuai</option>
                          <option :value="false">Tidak sesuai</option>
                        </select>
                      </div>
                    </div>

                    <div class="flex-grow-1">
                      <template v-if="String(selectedParticipant.id_card_url).toLowerCase().endsWith('.pdf')">
                        <div style="height:360px; border:1px solid #dee2e6;" class="mb-1">
                          <iframe :src="selectedParticipant.id_card_url" style="width:100%; height:100%; border:0;"></iframe>
                        </div>
                        <a :href="selectedParticipant.id_card_url" target="_blank" class="btn btn-xs btn-outline-primary">
                          <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                        </a>
                      </template>
                      <template v-else>
                        <img :src="selectedParticipant.id_card_url" class="img-fluid img-thumbnail mb-1" style="max-height:360px;" alt="KTP / KIA">
                        <div>
                          <a :href="selectedParticipant.id_card_url" target="_blank" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </div>
                      </template>
                    </div>
                  </div>

                  <hr class="my-2"
                      v-if="selectedParticipant.family_card_url || selectedParticipant.bank_book_url || selectedParticipant.certificate_url || selectedParticipant.other_url">
                </div>

                <!-- KARTU KELUARGA -->
                <div class="mb-3" v-if="selectedParticipant.family_card_url">
                  <div class="d-flex flex-wrap">
                    <div class="mb-2 mr-md-3" style="min-width:240px; max-width:260px;">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>Kartu Keluarga</strong>
                      </div>

                      <div class="form-group mb-1">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="chkFamilyCard" v-model="verificationForm.checked_family_card">
                          <label class="form-check-label" for="chkFamilyCard">Sudah dicek</label>
                        </div>
                      </div>

                      <div class="form-group mb-0">
                        <label class="small mb-1">Hasil kesesuaian</label>
                        <select class="form-control form-control-sm" v-model="verificationForm.field_matches.documents.family_card_url">
                          <option :value="null">Belum dinilai</option>
                          <option :value="true">Sesuai</option>
                          <option :value="false">Tidak sesuai</option>
                        </select>
                      </div>
                    </div>

                    <div class="flex-grow-1">
                      <template v-if="String(selectedParticipant.family_card_url).toLowerCase().endsWith('.pdf')">
                        <div style="height:360px; border:1px solid #dee2e6;" class="mb-1">
                          <iframe :src="selectedParticipant.family_card_url" style="width:100%; height:100%; border:0;"></iframe>
                        </div>
                        <a :href="selectedParticipant.family_card_url" target="_blank" class="btn btn-xs btn-outline-primary">
                          <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                        </a>
                      </template>
                      <template v-else>
                        <img :src="selectedParticipant.family_card_url" class="img-fluid img-thumbnail mb-1" style="max-height:360px;" alt="Kartu Keluarga">
                        <div>
                          <a :href="selectedParticipant.family_card_url" target="_blank" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </div>
                      </template>
                    </div>
                  </div>

                  <hr class="my-2"
                      v-if="selectedParticipant.bank_book_url || selectedParticipant.certificate_url || selectedParticipant.other_url">
                </div>

                <!-- BUKU REKENING -->
                <div class="mb-3" v-if="selectedParticipant.bank_book_url">
                  <div class="d-flex flex-wrap">
                    <div class="mb-2 mr-md-3" style="min-width:240px; max-width:260px;">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>Buku Rekening</strong>
                      </div>

                      <div class="form-group mb-1">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="chkBankBook" v-model="verificationForm.checked_bank_book">
                          <label class="form-check-label" for="chkBankBook">Sudah dicek</label>
                        </div>
                      </div>

                      <div class="form-group mb-0">
                        <label class="small mb-1">Hasil kesesuaian</label>
                        <select class="form-control form-control-sm" v-model="verificationForm.field_matches.documents.bank_book_url">
                          <option :value="null">Belum dinilai</option>
                          <option :value="true">Sesuai</option>
                          <option :value="false">Tidak sesuai</option>
                        </select>
                      </div>
                    </div>

                    <div class="flex-grow-1">
                      <template v-if="String(selectedParticipant.bank_book_url).toLowerCase().endsWith('.pdf')">
                        <div style="height:360px; border:1px solid #dee2e6;" class="mb-1">
                          <iframe :src="selectedParticipant.bank_book_url" style="width:100%; height:100%; border:0;"></iframe>
                        </div>
                        <a :href="selectedParticipant.bank_book_url" target="_blank" class="btn btn-xs btn-outline-primary">
                          <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                        </a>
                      </template>
                      <template v-else>
                        <img :src="selectedParticipant.bank_book_url" class="img-fluid img-thumbnail mb-1" style="max-height:360px;" alt="Buku Rekening">
                        <div>
                          <a :href="selectedParticipant.bank_book_url" target="_blank" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </div>
                      </template>
                    </div>
                  </div>

                  <hr class="my-2" v-if="selectedParticipant.certificate_url || selectedParticipant.other_url">
                </div>

                <!-- SERTIFIKAT -->
                <div class="mb-3" v-if="selectedParticipant.certificate_url">
                  <div class="d-flex flex-wrap">
                    <div class="mb-2 mr-md-3" style="min-width:240px; max-width:260px;">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>Sertifikat Pendukung</strong>
                      </div>

                      <div class="form-group mb-1">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="chkCertificate" v-model="verificationForm.checked_certificate">
                          <label class="form-check-label" for="chkCertificate">Sudah dicek</label>
                        </div>
                      </div>

                      <div class="form-group mb-0">
                        <label class="small mb-1">Hasil kesesuaian</label>
                        <select class="form-control form-control-sm" v-model="verificationForm.field_matches.documents.certificate_url">
                          <option :value="null">Belum dinilai</option>
                          <option :value="true">Sesuai</option>
                          <option :value="false">Tidak sesuai</option>
                        </select>
                      </div>
                    </div>

                    <div class="flex-grow-1">
                      <template v-if="String(selectedParticipant.certificate_url).toLowerCase().endsWith('.pdf')">
                        <div style="height:360px; border:1px solid #dee2e6;" class="mb-1">
                          <iframe :src="selectedParticipant.certificate_url" style="width:100%; height:100%; border:0;"></iframe>
                        </div>
                        <a :href="selectedParticipant.certificate_url" target="_blank" class="btn btn-xs btn-outline-primary">
                          <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                        </a>
                      </template>
                      <template v-else>
                        <img :src="selectedParticipant.certificate_url" class="img-fluid img-thumbnail mb-1" style="max-height:360px;" alt="Sertifikat Pendukung">
                        <div>
                          <a :href="selectedParticipant.certificate_url" target="_blank" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </div>
                      </template>
                    </div>
                  </div>

                  <hr class="my-2" v-if="selectedParticipant.other_url">
                </div>

                <!-- DOKUMEN LAINNYA -->
                <div class="mb-0" v-if="selectedParticipant.other_url">
                  <div class="d-flex flex-wrap">
                    <div class="mb-2 mr-md-3" style="min-width:240px; max-width:260px;">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>Dokumen Lainnya</strong>
                      </div>

                      <div class="form-group mb-1">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="chkOther" v-model="verificationForm.checked_other">
                          <label class="form-check-label" for="chkOther">Sudah dicek</label>
                        </div>
                      </div>

                      <div class="form-group mb-0">
                        <label class="small mb-1">Hasil kesesuaian</label>
                        <select class="form-control form-control-sm" v-model="verificationForm.field_matches.documents.other_url">
                          <option :value="null">Belum dinilai</option>
                          <option :value="true">Sesuai</option>
                          <option :value="false">Tidak sesuai</option>
                        </select>
                      </div>
                    </div>

                    <div class="flex-grow-1">
                      <template v-if="String(selectedParticipant.other_url).toLowerCase().endsWith('.pdf')">
                        <div style="height:360px; border:1px solid #dee2e6;" class="mb-1">
                          <iframe :src="selectedParticipant.other_url" style="width:100%; height:100%; border:0;"></iframe>
                        </div>
                        <a :href="selectedParticipant.other_url" target="_blank" class="btn btn-xs btn-outline-primary">
                          <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                        </a>
                      </template>
                      <template v-else>
                        <img :src="selectedParticipant.other_url" class="img-fluid img-thumbnail mb-1" style="max-height:360px;" alt="Dokumen Lainnya">
                        <div>
                          <a :href="selectedParticipant.other_url" target="_blank" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>

                <!-- kosong -->
                <div
                  v-if="!selectedParticipant.photo_url
                    && !selectedParticipant.id_card_url
                    && !selectedParticipant.family_card_url
                    && !selectedParticipant.bank_book_url
                    && !selectedParticipant.certificate_url
                    && !selectedParticipant.other_url"
                  class="text-center text-muted"
                >
                  Tidak ada dokumen yang diunggah oleh peserta ini.
                </div>

              </div>
            </div>

            <!-- ==================== -->
            <!-- 2. DATA PESERTA -->
            <!-- ==================== -->
            <div class="card mb-3">
              <div class="card-header py-2">
                <strong>2. Data Peserta (Untuk Dicocokkan Dengan Dokumen di Atas)</strong>
                <div class="small text-muted">
                  Cocokkan NIK, nama, tanggal lahir, jenis kelamin, pendidikan, alamat, tanggal terbit KTP/KK,
                  serta data rekening dengan dokumen yang tampil di panel.
                </div>
              </div>

              <div class="card-body p-2">
                <div class="row">
                  <!-- IDENTITAS -->
                  <div class="col-md-6 mb-2">
                    <h6 class="mb-1 d-flex justify-content-between align-items-center">
                      <span>2.1 Identitas</span>
                      <small>
                        <label class="mb-0">
                          <input type="checkbox" class="form-check-input mr-1" v-model="verificationForm.checked_identity">
                          <span class="align-middle">Sudah dicek</span>
                        </label>
                      </small>
                    </h6>

                    <table class="table table-sm table-borderless mb-1">
                      <tbody>
                        <tr>
                          <th class="p-1 align-middle">NIK</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.nik || '-' }}</td>
                          <td class="p-1 align-middle" style="width:110px;">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.identity.nik">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>

                        <tr>
                          <th class="p-1 align-middle">Nama</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.full_name || '-' }}</td>
                          <td class="p-1 align-middle">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.identity.full_name">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>

                        <tr>
                          <th class="p-1 align-middle">Tempat / Tgl Lahir</th>
                          <td class="p-1 align-middle">
                            {{ selectedParticipant.place_of_birth || '-' }}, {{ selectedParticipant.date_of_birth || '-' }}
                          </td>
                          <td class="p-1 align-middle">
                            <div class="d-flex flex-column">
                              <select class="form-control form-control-sm mb-1" v-model="verificationForm.field_matches.identity.place_of_birth">
                                <option :value="null">Tmpt: -</option>
                                <option :value="true">Sesuai</option>
                                <option :value="false">Tidak sesuai</option>
                              </select>
                              <select class="form-control form-control-sm" v-model="verificationForm.field_matches.identity.date_of_birth">
                                <option :value="null">Tgl: -</option>
                                <option :value="true">Sesuai</option>
                                <option :value="false">Tidak sesuai</option>
                              </select>
                            </div>
                          </td>
                        </tr>

                        <tr>
                          <th class="p-1 align-middle">Jenis Kelamin</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.gender || '-' }}</td>
                          <td class="p-1 align-middle">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.identity.gender">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- KONTAK & DOMISILI -->
                  <div class="col-md-6 mb-2">
                    <h6 class="mb-1 d-flex justify-content-between align-items-center">
                      <span>2.2 Kontak</span>
                      <small>
                        <label class="mb-0">
                          <input type="checkbox" class="form-check-input mr-1" v-model="verificationForm.checked_contact">
                          <span class="align-middle">Sudah dicek</span>
                        </label>
                      </small>
                    </h6>

                    <table class="table table-sm table-borderless mb-2">
                      <tbody>
                        <tr>
                          <th class="p-1 align-middle">No. HP</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.phone_number || '-' }}</td>
                          <td class="p-1 align-middle" style="width:110px;">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.contact.phone_number">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <h6 class="mb-1 d-flex justify-content-between align-items-center">
                      <span>2.3 Domisili</span>
                      <small>
                        <label class="mb-0">
                          <input type="checkbox" class="form-check-input mr-1" v-model="verificationForm.checked_domicile">
                          <span class="align-middle">Sudah dicek</span>
                        </label>
                      </small>
                    </h6>

                    <table class="table table-sm table-borderless mb-1">
                      <tbody>
                        <tr>
                          <th class="p-1 align-middle">Alamat</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.address || '-' }}</td>
                          <td class="p-1 align-middle" style="width:110px;">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.domicile.address">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <div class="text-muted text-sm">
                      Wilayah: {{ selectedParticipant.full_address || '-' }}
                    </div>
                  </div>
                </div>

                <div class="row">
                  <!-- PENDIDIKAN -->
                  <div class="col-md-6 mb-2">
                    <h6 class="mb-1 d-flex justify-content-between align-items-center">
                      <span>2.4 Pendidikan</span>
                      <small>
                        <label class="mb-0">
                          <input type="checkbox" class="form-check-input mr-1" v-model="verificationForm.checked_education">
                          <span class="align-middle">Sudah dicek</span>
                        </label>
                      </small>
                    </h6>

                    <table class="table table-sm table-borderless mb-1">
                      <tbody>
                        <tr>
                          <th class="p-1 align-middle">Pendidikan</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.education || '-' }}</td>
                          <td class="p-1 align-middle" style="width:110px;">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.education.education">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- REKENING & TANGGAL DOKUMEN -->
                  <div class="col-md-6 mb-2">
                    <h6 class="mb-1 d-flex justify-content-between align-items-center">
                      <span>2.5 Rekening Bank</span>
                      <small>
                        <label class="mb-0">
                          <input type="checkbox" class="form-check-input mr-1" v-model="verificationForm.checked_bank_account">
                          <span class="align-middle">Sudah dicek</span>
                        </label>
                      </small>
                    </h6>

                    <table class="table table-sm table-borderless mb-2">
                      <tbody>
                        <tr>
                          <th class="p-1 align-middle">No. Rekening</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.bank_account_number || '-' }}</td>
                          <td class="p-1 align-middle" style="width:110px;">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.bank_account.bank_account_number">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                        <tr>
                          <th class="p-1 align-middle">Nama di Rekening</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.bank_account_name || '-' }}</td>
                          <td class="p-1 align-middle">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.bank_account.bank_account_name">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                        <tr>
                          <th class="p-1 align-middle">Nama Bank</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.bank_name || '-' }}</td>
                          <td class="p-1 align-middle">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.bank_account.bank_name">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <h6 class="mb-1 d-flex justify-content-between align-items-center">
                      <span>2.6 Tanggal Dokumen</span>
                      <small>
                        <label class="mb-0">
                          <input type="checkbox" class="form-check-input mr-1" v-model="verificationForm.checked_document_dates">
                          <span class="align-middle">Sudah dicek</span>
                        </label>
                      </small>
                    </h6>

                    <table class="table table-sm table-borderless mb-1">
                      <tbody>
                        <tr>
                          <th class="p-1 align-middle">Tgl Terbit KTP</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.tanggal_terbit_ktp || '-' }}</td>
                          <td class="p-1 align-middle" style="width:110px;">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.document_dates.tanggal_terbit_ktp">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                        <tr>
                          <th class="p-1 align-middle">Tgl Terbit KK</th>
                          <td class="p-1 align-middle">{{ selectedParticipant.tanggal_terbit_kk || '-' }}</td>
                          <td class="p-1 align-middle">
                            <select class="form-control form-control-sm" v-model="verificationForm.field_matches.document_dates.tanggal_terbit_kk">
                              <option :value="null">Belum dinilai</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak sesuai</option>
                            </select>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                  </div>
                </div>
              </div>
            </div>


            <!-- STATUS VERIFIKASI -->
            <div class="form-group mb-3">
              <label class="d-block mb-1"><strong>Status Verifikasi Sesi Ini</strong></label>
              <div class="form-check form-check-inline">
                <input
                  class="form-check-input"
                  type="radio"
                  id="statusVerified"
                  value="verified"
                  v-model="verificationForm.status"
                >
                <label class="form-check-label" for="statusVerified">Terverifikasi</label>
              </div>
              <div class="form-check form-check-inline">
                <input
                  class="form-check-input"
                  type="radio"
                  id="statusRejected"
                  value="rejected"
                  v-model="verificationForm.status"
                >
                <label class="form-check-label" for="statusRejected">Ditolak</label>
              </div>
            </div>

            <!-- KEPUTUSAN TERHADAP PENDAFTARAN (event_participants.registration_status) -->
            <div class="form-group mb-3">
              <label class="d-block mb-1"><strong>Keputusan Terhadap Pendaftaran</strong></label>

              <div class="row">
                <div class="col-md-6">
                  <select class="form-control" v-model="verificationForm.registration_status">
                    <option value="bank_data">Bank data</option>
                    <option value="process">Proses</option>
                    <option value="verified">Diterima</option>
                    <option value="need_revision">Perbaiki</option>
                    <option value="rejected">Tolak</option>
                    <option value="disqualified">Diskualifikasi</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <small class="form-text text-muted">
                    Status ini akan mengubah kolom <code>registration_status</code> pada tabel
                    <code>event_participants</code> (berdasarkan <code>event_participant_id</code>).
                    <br>
                    Disarankan:
                    <ul class="mb-0 pl-3">
                      <li><code>verified</code> untuk peserta yang lulus verifikasi</li>
                      <li><code>need_revision</code> jika masih ada kekurangan</li>
                      <li><code>rejected</code> bila tidak memenuhi syarat</li>
                    </ul>
                  </small>
                </div>
              </div>
            </div>

            
            <!-- CATATAN -->
            <div class="form-group mb-0">
              <label for="verificationNotes"><strong>Catatan Verifikasi (opsional)</strong></label>
              <textarea
                id="verificationNotes"
                rows="3"
                class="form-control"
                v-model="verificationForm.notes"
                placeholder="Ringkasan hasil verifikasi..."
              ></textarea>
            </div>

          </div>

          <!-- LOADING / EMPTY -->
          <div class="modal-body" v-else>
            <div class="text-center text-muted">Memuat data peserta...</div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" :disabled="savingVerification" data-dismiss="modal">
              Tutup
            </button>
            <button type="button" class="btn btn-primary" @click="submitVerification" :disabled="savingVerification">
              <i v-if="savingVerification" class="fas fa-spinner fa-spin mr-1"></i>
              Simpan Verifikasi
            </button>
          </div>

        </div>
      </div>
    </div>



    <ViewParticipantModal :selected-participant="selectedEventParticipant" />
  </section>
</template>

<script setup>
import { ref, watch, onMounted, computed, reactive } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../stores/AuthUserStore'
import { useMasterDataStore } from '../stores/MasterDataStore'

import { useSettingStore } from '../stores/SettingStore'
import ViewParticipantModal from './ViewParticipantModal.vue'
import { registrationBadgeClass, registrationStatusLabel } from './EventParticipantHelpers'

const props = defineProps({
  status: { type: String, default: '' },
})

const filters = ref({
  event_group_id: '',      // ✅ filter cabang/golongan
})

const fetchEventMasterData = async () => {
  if (!eventId.value) return
  try {
    eventBranches.value = masterDataStore.eventBranches
    eventGroups.value = masterDataStore.eventGroups
    eventCategories.value = masterDataStore.eventCategories
  } catch (error) {
    console.error('Gagal memuat master event (branches/groups/categories):', error)
    Swal.fire('Gagal', 'Gagal memuat daftar cabang event & golongan.', 'error')
  }
}

const eventBranches = ref([])   // event_branches (cabang/golongan)
const eventGroups = ref([])
const eventCategories = ref([])


const settingStore = useSettingStore()
const authUserStore = useAuthUserStore()
const masterDataStore = useMasterDataStore()
const currentUser = computed(() => authUserStore.user || null)

// SUPERADMIN=1, VERIFIKATOR=4 (sesuai enum RoleType)
const canVerifyRole = computed(() => {
  const u = currentUser.value
  const roleId = u?.role_id ?? u?.role?.id ?? null
  return [1, 4].includes(Number(roleId))
})

// (opsional) kalau mau sekalian cek permission slug
const canVerifyPermission = computed(() => {
  const perms = currentUser.value?.permissions || []
  return perms.includes('participant.verify') || perms.includes('participant.manage') // sesuaikan kalau ada slug khusus verifikasi
})

const canShowVerifyButton = (item) => {
  if (!canVerifyRole.value) return false
  // kalau mau wajib permission juga, aktifkan ini:
  // if (!canVerifyPermission.value) return false

  const s = item?.registration_status
  return !['verified', 'rejected', 'disqualified'].includes(s)
}


const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

const activeStatus = ref(props.status || '')
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)

const items = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})


const statusList = [
  { key: 'process',       label: 'Proses',    badgeClass: 'badge-warning' },
  { key: 'verified',      label: 'Diterima',  badgeClass: 'badge-success' },
  { key: 'need_revision', label: 'Perbaiki',  badgeClass: 'badge-info' },
  { key: 'rejected',      label: 'Tolak',     badgeClass: 'badge-secondary' },
  { key: 'disqualified',  label: 'Mundur',    badgeClass: 'badge-danger' },
]

const statusCounts = ref({
  process: 0,
  verified: 0,
  need_revision: 0,
  rejected: 0,
  disqualified: 0,
})

const progressBarClass = (percent = 0) => {
  const p = Number(percent) || 0
  if (p <= 20) return 'bg-danger'
  if (p <= 50) return 'bg-warning'
  if (p <= 80) return 'bg-info'
  return 'bg-success'
}

const openBiodataPdf = (p) => {
  const id = p?.id
  if (!id) return

  const url = `/api/v1/get/event-participants/${id}/biodata-pdf`
  window.open(url, '_blank')
}


const fetchItems = async (page = 1) => {
  if (!eventId.value) return
  isLoading.value = true

  try {
    const res = await axios.get(`/api/v1/events/${eventId.value}/participants`, {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        registration_status: activeStatus.value,
        withVerifications: 1, // atau true
        event_group_id: filters.value.event_group_id || '',   // ✅ tambah ini
      },
    })

    const paginated = res.data
    items.value = paginated.data || []
    meta.value = {
      current_page: paginated.current_page,
      per_page: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
      last_page: paginated.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat event_participants:', error)
    if (error?.response?.status === 401) authUserStore.logout()
    else Swal.fire('Gagal', 'Gagal memuat data peserta event.', 'error')
  } finally {
    isLoading.value = false
  }
}

const fetchStatusCounts = async () => {
  if (!eventId.value) return
  try {
    const { data } = await axios.get('/api/v1/get/event-participants/status-counts', {
      params: { event_id: eventId.value },
    })

    statusCounts.value = {
      process: data.process || 0,
      verified: data.verified || 0,
      need_revision: data.need_revision || 0,
      rejected: data.rejected || 0,
      disqualified: data.disqualified || 0,
    }

  } catch (error) {
    console.error('Gagal memuat rekap status peserta event:', error)
  }
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchItems(page)
}

const changeStatus = (key) => {
  activeStatus.value = key
  fetchItems(1)
}

const openViewModal = (row) => {
  selectedEventParticipant.value = row
  $('#viewParticipantModal').modal('show')
}

// =========================
// VERIFICATIONS (sesuai schema)
// =========================
// =========================
// VERIFICATIONS (sesuai skema)
// =========================
const selectedEventParticipant = ref(null)

// participant row (participants)
const selectedParticipant = computed(() => selectedEventParticipant.value?.participant || null)

// label cabang untuk modal (event_participants -> event_branch)
const selectedBranchName = computed(() => {
  const ep = selectedEventParticipant.value
  return (
    ep?.event_branch?.branch_name ||
    ep?.eventBranch?.branch_name ||
    ep?.eventBranch?.name ||
    '-'
  )
})

const savingVerification = ref(false)

// tombol verifikasi: contoh rule, tidak tampil jika sudah final
const canVerify = (ep) => {
  const s = ep?.registration_status
  return !['verified', 'rejected', 'disqualified'].includes(s)
}

// ====== FORM (participant_verifications) ======
const verificationForm = reactive({
  id: null,
  status: 'verified', // participant_verifications.status: verified|rejected

  // dokumen dicek?
  checked_photo: false,
  checked_id_card: false,
  checked_family_card: false,
  checked_bank_book: false,
  checked_certificate: false,
  checked_other: false,

  // kelompok data dicek?
  checked_identity: false,
  checked_contact: false,
  checked_domicile: false,
  checked_education: false,
  checked_bank_account: false,
  checked_document_dates: false,

  // detail hasil cek per field (json)
  field_matches: {
    identity: {
      nik: null,
      full_name: null,
      place_of_birth: null,
      date_of_birth: null,
      gender: null,
    },
    contact: {
      phone_number: null,
    },
    domicile: {
      province_id: null,
      regency_id: null,
      district_id: null,
      village_id: null,
      address: null,

      // optional: fallback text manual (kalau mau ikut dinilai)
      province_name: null,
      regency_name: null,
      district_name: null,
      village_name: null,
    },
    education: {
      education: null,
    },
    bank_account: {
      bank_account_number: null,
      bank_account_name: null,
      bank_name: null,
    },
    document_dates: {
      tanggal_terbit_ktp: null,
      tanggal_terbit_kk: null,
    },
    documents: {
      photo_url: null,
      id_card_url: null,
      family_card_url: null,
      bank_book_url: null,
      certificate_url: null,
      other_url: null,
    },
  },

  // ✅ keputusan untuk event_participants.registration_status
  registration_status: 'process',

  notes: '',
})

const resetVerificationForm = () => {
  verificationForm.id = null
  verificationForm.status = 'verified'
  verificationForm.registration_status = 'process'

  verificationForm.checked_photo = false
  verificationForm.checked_id_card = false
  verificationForm.checked_family_card = false
  verificationForm.checked_bank_book = false
  verificationForm.checked_certificate = false
  verificationForm.checked_other = false

  verificationForm.checked_identity = false
  verificationForm.checked_contact = false
  verificationForm.checked_domicile = false
  verificationForm.checked_education = false
  verificationForm.checked_bank_account = false
  verificationForm.checked_document_dates = false

  Object.assign(verificationForm.field_matches, {
    identity: { nik: null, full_name: null, place_of_birth: null, date_of_birth: null, gender: null },
    contact: { phone_number: null },
    domicile: {
      province_id: null, regency_id: null, district_id: null, village_id: null, address: null,
      province_name: null, regency_name: null, district_name: null, village_name: null,
    },
    education: { education: null },
    bank_account: { bank_account_number: null, bank_account_name: null, bank_name: null },
    document_dates: { tanggal_terbit_ktp: null, tanggal_terbit_kk: null },
    documents: {
      photo_url: null, id_card_url: null, family_card_url: null,
      bank_book_url: null, certificate_url: null, other_url: null,
    },
  })

  verificationForm.notes = ''
}

// ====== OPEN MODAL ======
const openVerification = (ep) => {
  selectedEventParticipant.value = ep
  resetVerificationForm()

  // default keputusan pendaftaran mengikuti status saat ini
  verificationForm.registration_status = ep?.registration_status || 'process'

  // ambil latest verification (sesuaikan nama property dari API)
  const v =
    ep?.participant?.latest_verification ||
    ep?.latest_verification ||
    ep?.verification ||
    null

  if (v) {
    verificationForm.id = v.id ?? null
    verificationForm.status = v.status || 'verified'

    verificationForm.checked_photo = !!v.checked_photo
    verificationForm.checked_id_card = !!v.checked_id_card
    verificationForm.checked_family_card = !!v.checked_family_card
    verificationForm.checked_bank_book = !!v.checked_bank_book
    verificationForm.checked_certificate = !!v.checked_certificate
    verificationForm.checked_other = !!v.checked_other

    verificationForm.checked_identity = !!v.checked_identity
    verificationForm.checked_contact = !!v.checked_contact
    verificationForm.checked_domicile = !!v.checked_domicile
    verificationForm.checked_education = !!v.checked_education
    verificationForm.checked_bank_account = !!v.checked_bank_account
    verificationForm.checked_document_dates = !!v.checked_document_dates

    verificationForm.notes = v.notes || ''

    // ✅ merge supaya reactive aman
    if (v.field_matches) {
      Object.assign(verificationForm.field_matches, v.field_matches)
    }
  }

  $('#showVerificationModal').modal('show')
}

// ====== VALIDASI (pakai participants) ======
const validateVerificationDetails = () => {
  const errors = []
  const p = selectedParticipant.value
  if (!p) return ['Peserta tidak ditemukan.']

  const fm = verificationForm.field_matches || {}
  const isUnset = (val) => val === null || typeof val === 'undefined'
  const docs = fm.documents || {}

  // 1) Dokumen (wajib kalau file ada)
  if (p.photo_url) {
    if (!verificationForm.checked_photo) errors.push('Foto peserta: "Sudah dicek" belum dicentang.')
    if (isUnset(docs.photo_url)) errors.push('Foto peserta: hasil kesesuaian belum dipilih.')
  }
  if (p.id_card_url) {
    if (!verificationForm.checked_id_card) errors.push('KTP/KIA: "Sudah dicek" belum dicentang.')
    if (isUnset(docs.id_card_url)) errors.push('KTP/KIA: hasil kesesuaian belum dipilih.')
  }
  if (p.family_card_url) {
    if (!verificationForm.checked_family_card) errors.push('Kartu Keluarga: "Sudah dicek" belum dicentang.')
    if (isUnset(docs.family_card_url)) errors.push('Kartu Keluarga: hasil kesesuaian belum dipilih.')
  }
  if (p.bank_book_url) {
    if (!verificationForm.checked_bank_book) errors.push('Buku Rekening: "Sudah dicek" belum dicentang.')
    if (isUnset(docs.bank_book_url)) errors.push('Buku Rekening: hasil kesesuaian belum dipilih.')
  }
  if (p.certificate_url) {
    if (!verificationForm.checked_certificate) errors.push('Sertifikat: "Sudah dicek" belum dicentang.')
    if (isUnset(docs.certificate_url)) errors.push('Sertifikat: hasil kesesuaian belum dipilih.')
  }
  if (p.other_url) {
    if (!verificationForm.checked_other) errors.push('Dokumen lainnya: "Sudah dicek" belum dicentang.')
    if (isUnset(docs.other_url)) errors.push('Dokumen lainnya: hasil kesesuaian belum dipilih.')
  }

  // 2) Identitas (nik, full_name, place_of_birth, date_of_birth, gender)
  const identity = fm.identity || {}
  if (!verificationForm.checked_identity) errors.push('Kelompok "Identitas" belum dicentang "Sudah dicek".')
  ;['nik','full_name','place_of_birth','date_of_birth','gender'].forEach((k) => {
    if (isUnset(identity[k])) errors.push(`Identitas: ${k} belum dinilai.`)
  })

  // 3) Kontak (phone_number)
  const contact = fm.contact || {}
  if (!verificationForm.checked_contact) errors.push('Kelompok "Kontak" belum dicentang "Sudah dicek".')
  if (isUnset(contact.phone_number)) errors.push('Kontak: phone_number belum dinilai.')

  // 4) Domisili (WAJIB: address saja)
  const domicile = fm.domicile || {}
  if (!verificationForm.checked_domicile) errors.push('Kelompok "Domisili" belum dicentang "Sudah dicek".')

  // ✅ hanya alamat yang wajib dinilai
  if (isUnset(domicile.address)) errors.push('Domisili: address belum dinilai.')

  // ❌ keluarkan verifikasi wilayah
  // ;['province_id','regency_id','district_id','village_id'].forEach((k) => {
  //   if (isUnset(domicile[k])) errors.push(`Domisili: ${k} belum dinilai.`)
  // })

  // 5) Pendidikan (education)
  const edu = fm.education || {}
  if (!verificationForm.checked_education) errors.push('Kelompok "Pendidikan" belum dicentang "Sudah dicek".')
  if (isUnset(edu.education)) errors.push('Pendidikan: education belum dinilai.')

  // 6) Rekening (bank_account_number, bank_account_name, bank_name)
  const bank = fm.bank_account || {}
  if (!verificationForm.checked_bank_account) errors.push('Kelompok "Rekening Bank" belum dicentang "Sudah dicek".')
  ;['bank_account_number','bank_account_name','bank_name'].forEach((k) => {
    if (isUnset(bank[k])) errors.push(`Rekening: ${k} belum dinilai.`)
  })

  // 7) Tanggal dokumen (tanggal_terbit_ktp, tanggal_terbit_kk)
  const dd = fm.document_dates || {}
  if (!verificationForm.checked_document_dates) errors.push('Kelompok "Tanggal Dokumen" belum dicentang "Sudah dicek".')
  ;['tanggal_terbit_ktp','tanggal_terbit_kk'].forEach((k) => {
    if (isUnset(dd[k])) errors.push(`Tanggal dokumen: ${k} belum dinilai.`)
  })

  return errors
}

// ====== SUBMIT (sesuai schema) ======
const submitVerification = async () => {
  if (!selectedEventParticipant.value || !selectedParticipant.value) {
    Swal.fire({ icon: 'warning', title: 'Tidak ada peserta yang dipilih.' })
    return
  }

  if (!verificationForm.status) {
    Swal.fire({ icon: 'warning', title: 'Status verifikasi belum dipilih.' })
    return
  }

  // ✅ wajib ada keputusan registration_status
  if (!verificationForm.registration_status) {
    Swal.fire({ icon: 'warning', title: 'Keputusan pendaftaran belum dipilih.' })
    return
  }

  if(!settingStore.isDevelopment) {
    const detailErrors = validateVerificationDetails()
    if (detailErrors.length) {
      Swal.fire({
        icon: 'warning',
        title: 'Form verifikasi belum lengkap',
        html: `
          <ul style="text-align:left; max-height:260px; overflow-y:auto; padding-left:18px;">
            ${detailErrors.map(e => `<li>${e}</li>`).join('')}
          </ul>
        `,
      })
      return
    }
  }

  const confirmResult = await Swal.fire({
    icon: 'question',
    title: 'Simpan hasil verifikasi?',
    text: 'Pastikan data dan dokumen sudah dicek sebelum disimpan.',
    showCancelButton: true,
    confirmButtonText: 'Ya, simpan',
    cancelButtonText: 'Batal',
  })
  if (!confirmResult.isConfirmed) return

  savingVerification.value = true
  try {
    const ep = selectedEventParticipant.value
    const p  = selectedParticipant.value

    const payload = {
      // context participant_verifications
      event_id: ep?.event_id ?? null,
      event_participant_id: ep?.id ?? null,

      status: verificationForm.status,

      checked_photo: !!verificationForm.checked_photo,
      checked_id_card: !!verificationForm.checked_id_card,
      checked_family_card: !!verificationForm.checked_family_card,
      checked_bank_book: !!verificationForm.checked_bank_book,
      checked_certificate: !!verificationForm.checked_certificate,
      checked_other: !!verificationForm.checked_other,

      checked_identity: !!verificationForm.checked_identity,
      checked_contact: !!verificationForm.checked_contact,
      checked_domicile: !!verificationForm.checked_domicile,
      checked_education: !!verificationForm.checked_education,
      checked_bank_account: !!verificationForm.checked_bank_account,
      checked_document_dates: !!verificationForm.checked_document_dates,

      field_matches: verificationForm.field_matches || null,
      notes: verificationForm.notes || null,

      // ✅ update event_participants.registration_status (sesuai schema event_participants)
      registration_status: verificationForm.registration_status,
    }

    await axios.post(`/api/v1/participants/${p.uuid}/verifications`, payload)

    $('#showVerificationModal').modal('hide')

    // reset state (jangan set selectedParticipant karena computed)
    selectedEventParticipant.value = null
    resetVerificationForm()

    await Swal.fire({ icon: 'success', title: 'Verifikasi peserta berhasil disimpan.' })

    await fetchStatusCounts?.()
    await fetchItems?.(meta.value?.current_page || 1)
  } catch (error) {
    console.error('Gagal menyimpan verifikasi:', error)
    let msg = error.response?.data?.message || 'Gagal menyimpan hasil verifikasi peserta.'
    if (error.response?.data?.errors) {
      const k = Object.keys(error.response.data.errors)[0]
      if (k) msg = error.response.data.errors[k]?.[0] || msg
    }
    Swal.fire({ icon: 'error', title: 'Error', text: msg })
  } finally {
    savingVerification.value = false
  }
}



// =========================
// WATCHERS
// =========================

// filter status
watch(
  () => ({ ...filters.value }),
  () => {
    fetchItems(1)
  }
)


watch(
  () => props.status,
  (val) => {
    activeStatus.value = val || ''
    fetchItems(1)
  },
  { immediate: true }
)

watch(
  () => eventId.value,
  (val) => {
    if (!val) return
    fetchItems(1)
    fetchStatusCounts()
  },
  { immediate: true }
)

watch(
  () => search.value,
  useDebounceFn(() => fetchItems(1), 400)
)

watch(
  () => perPage.value,
  () => fetchItems(1)
)

// =========================
// MOUNTED
// =========================
onMounted(() => {
  if (!eventId.value) {
    Swal.fire('Event belum dipilih', 'Silakan pilih event melalui Portal Event terlebih dahulu.', 'info')
  }
  fetchEventMasterData()
})
</script>

<style scoped>
.badge-pink {
  background-color: #e83e8c;
  color: #fff;
}

.btn-xs {
  padding: 2px 5px !important;
  font-size: 0.65rem !important;
  line-height: 1 !important;
}

.btn-xs i {
  font-size: 0.55rem !important;
}

.text-xs {
  font-size: 0.75rem;
}

.gap-2 {
  gap: .5rem;
}
</style>
