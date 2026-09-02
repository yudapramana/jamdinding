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
              <button v-for="s in statusList" :key="s.key" type="button"
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                :class="{ active: activeStatus === s.key }" @click="changeStatus(s.key)">
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
                  <select v-model.number="perPage" class="form-control form-control-sm w-auto">
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                  </select>
                  <span class="text-xs text-muted">entri</span>

                  <select v-model="filters.event_group_id" class="form-control form-control-sm w-auto"
                    title="Cabang / Golongan">
                    <option value="">Semua Cabang</option>
                    <option v-for="g in masterDataStore.eventGroups" :key="g.id" :value="String(g.id)">
                      {{ g.full_name || g.name || g.group_name || ('Gol #' + g.id) }}
                    </option>
                  </select>
                </div>

                <!-- RIGHT: SEARCH -->
                <input v-model="search" type="text" class="form-control form-control-sm mt-2 mt-md-0"
                  style="width: 220px" placeholder="Cari nama / NIK / kontingen" />
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
                      Belum ada peserta terdaftar untuk event ini.<br />
                      <small class="text-muted">Klik <strong>Tambah Peserta Event</strong> untuk menambahkan
                        peserta.</small>
                    </td>
                  </tr>

                  <tr v-for="(item, index) in items" :key="item.id">
                    <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                    <td>
                      <strong>{{ item.participant?.full_name }}</strong><br />
                      <span class="badge mr-1" style="width: 17px;"
                        :class="item.participant?.gender === 'MALE' ? 'badge-primary' : 'badge-pink'">
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
                      <span class="badge badge-light border">{{ item.contingent || '-' }}</span>
                    </td>
                    <td class="align-center text-center">
                      <div class="progress" style="height: 16px; font-size: 10px;">
                        <div class="progress-bar d-flex justify-content-center align-items-center"
                          :class="progressBarClass(item.participant?.lampiran_completion_percent)" role="progressbar"
                          :style="{ width: (item.participant?.lampiran_completion_percent || 0) + '%' }">
                          {{ item.participant?.lampiran_completion_percent || 0 }}%
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-xs" title="Lihat Data Peserta"
                          @click="openViewModal(item)">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-xs" title="Cetak Biodata (PDF)"
                          @click="openBiodataPdf(item)">
                          <i class="far fa-file-pdf"></i>
                        </button>
                        <button v-if="canShowVerifyButton(item)" class="btn btn-outline-success btn-xs"
                          title="Verifikasi Peserta" @click="openVerification(item)">
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
      </div>
    </div>

    <!-- MODAL VERIFIKASI PESERTA (DIROMBAK SIDE-BY-SIDE) -->
    <div class="modal fade" id="showVerificationModal" tabindex="-1" role="dialog" aria-hidden="true"
      data-backdrop="static">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header bg-light">
            <h5 class="modal-title"><i class="fas fa-clipboard-check text-success mr-2"></i> Verifikasi Data & Dokumen
              Peserta</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body bg-light" v-if="selectedEventParticipant && selectedParticipant">
            <!-- Info singkat peserta -->
            <div class="d-flex justify-content-between align-items-end mb-3 pb-2 border-bottom">
              <div>
                <h5 class="mb-1 text-primary"><strong>{{ selectedParticipant.full_name || '-' }}</strong></h5>
                <p class="mb-0 text-sm">
                  <i class="fas fa-id-card text-muted mr-1"></i> NIK: {{ selectedParticipant.nik || '-' }} &nbsp;|&nbsp;
                  <i class="fas fa-code-branch text-muted mr-1"></i> Cabang: {{ selectedBranchName }}
                </p>
              </div>
              <div>
                <span class="badge px-3 py-2 text-uppercase" style="font-size: 0.8rem;"
                  :class="registrationBadgeClass(selectedEventParticipant.registration_status)">
                  Status: {{ registrationStatusLabel(selectedEventParticipant.registration_status) }}
                </span>
              </div>
            </div>

            <div class="alert alert-info py-2 text-sm shadow-sm">
              <i class="fas fa-info-circle mr-1"></i> <strong>Instruksi:</strong> Sandingkan dokumen unggahan di kolom
              kiri dengan data teks di kolom kanan. Berikan penilaian pada setiap baris data.
            </div>

            <!-- ============================================== -->
            <!-- SECTION 1: KTP, AKTA KELAHIRAN & IDENTITAS -->
            <!-- ============================================== -->
            <div class="card shadow-sm mb-3">
              <div class="card-header py-2 bg-white d-flex align-items-center justify-content-between">
                <strong><i class="fas fa-address-card mr-2 text-primary"></i> 1. KTP, Akta Kelahiran & Data Identitas
                  Pribadi</strong>
              </div>
              <div class="card-body p-0">
                <div class="row m-0">
                  <!-- KIRI: Dokumen KTP & Akta Kelahiran -->
                  <div class="col-md-5 p-3 border-right" style="background-color: #fcfcfc;">

                    <!-- DOKUMEN KTP -->
                    <div class="mb-4">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="mb-0 text-primary">Dokumen KTP / KIA</label>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="chkIdCard"
                            v-model="verificationForm.checked_id_card">
                          <label class="custom-control-label text-sm font-weight-normal" for="chkIdCard">KTP
                            Dicek</label>
                        </div>
                      </div>

                      <div class="mb-2 text-center" v-if="selectedParticipant.id_card_url">
                        <template v-if="String(selectedParticipant.id_card_url).toLowerCase().endsWith('.pdf')">
                          <iframe :src="selectedParticipant.id_card_url"
                            style="width:100%; height:200px; border:1px solid #ddd;" class="rounded"></iframe>
                        </template>
                        <template v-else>
                          <img :src="selectedParticipant.id_card_url" class="img-fluid rounded border shadow-sm"
                            style="max-height:200px;" alt="KTP">
                        </template>
                        <div class="mt-2 text-right">
                          <a :href="selectedParticipant.id_card_url" target="_blank"
                            class="btn btn-xs btn-outline-primary"><i class="fas fa-external-link-alt"></i> Buka
                            Penuh</a>
                        </div>
                      </div>
                      <div v-else class="text-center text-muted p-3 border rounded border-dashed">
                        Belum mengunggah KTP
                      </div>

                      <div class="form-group mb-0 mt-2">
                        <label class="text-xs mb-1">Hasil Verifikasi File KTP:</label>
                        <select class="form-control form-control-sm"
                          v-model="verificationForm.field_matches.documents.id_card_url">
                          <option :value="null">-- Pilih Penilaian --</option>
                          <option :value="true">Valid & Sesuai</option>
                          <option :value="false">Buram / Tidak Sesuai</option>
                        </select>
                      </div>
                    </div>

                    <hr>

                    <!-- DOKUMEN AKTA KELAHIRAN (other_url) -->
                    <div>
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="mb-0 text-info">Dokumen Akta Kelahiran</label>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="chkOther"
                            v-model="verificationForm.checked_other">
                          <label class="custom-control-label text-sm font-weight-normal" for="chkOther">Akta
                            Dicek</label>
                        </div>
                      </div>

                      <div class="mb-2 text-center" v-if="selectedParticipant.other_url">
                        <template v-if="String(selectedParticipant.other_url).toLowerCase().endsWith('.pdf')">
                          <iframe :src="selectedParticipant.other_url"
                            style="width:100%; height:200px; border:1px solid #ddd;" class="rounded"></iframe>
                        </template>
                        <template v-else>
                          <img :src="selectedParticipant.other_url" class="img-fluid rounded border shadow-sm"
                            style="max-height:200px;" alt="Akta Kelahiran">
                        </template>
                        <div class="mt-2 text-right">
                          <a :href="selectedParticipant.other_url" target="_blank"
                            class="btn btn-xs btn-outline-info"><i class="fas fa-external-link-alt"></i> Buka Penuh</a>
                        </div>
                      </div>
                      <div v-else class="text-center text-muted p-3 border rounded border-dashed">
                        Belum mengunggah Akta Kelahiran
                      </div>

                      <div class="form-group mb-0 mt-2">
                        <label class="text-xs mb-1">Hasil Verifikasi File Akta:</label>
                        <select class="form-control form-control-sm"
                          v-model="verificationForm.field_matches.documents.other_url">
                          <option :value="null">-- Pilih Penilaian --</option>
                          <option :value="true">Valid & Sesuai</option>
                          <option :value="false">Tidak Sesuai</option>
                        </select>
                      </div>
                    </div>

                  </div>

                  <!-- KANAN: Data Identitas -->
                  <div class="col-md-7 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="mb-0">Data Sistem (Cocokkan dengan Dokumen)</label>
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="chkIdentity"
                          v-model="verificationForm.checked_identity">
                        <label class="custom-control-label text-sm font-weight-normal" for="chkIdentity">Data Identitas
                          Dicek</label>
                      </div>
                    </div>

                    <table class="table table-sm table-bordered text-sm mb-4">
                      <thead class="bg-light">
                        <tr>
                          <th>Kolom</th>
                          <th>Data Peserta</th>
                          <th style="width: 130px;">Kesesuaian</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td class="align-middle"><strong>NIK</strong></td>
                          <td class="align-middle">{{ selectedParticipant.nik || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.identity.nik">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                        <tr>
                          <td class="align-middle"><strong>Nama Lengkap</strong></td>
                          <td class="align-middle">{{ selectedParticipant.full_name || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.identity.full_name">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                        <tr>
                          <td class="align-middle"><strong>Tempat Lahir</strong></td>
                          <td class="align-middle">{{ selectedParticipant.place_of_birth || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.identity.place_of_birth">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                        <tr>
                          <td class="align-middle"><strong>Tgl Lahir</strong></td>
                          <td class="align-middle">{{ selectedParticipant.date_of_birth || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.identity.date_of_birth">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                        <tr>
                          <td class="align-middle"><strong>Gender</strong></td>
                          <td class="align-middle">{{ selectedParticipant.gender || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.identity.gender">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                      </tbody>
                    </table>

                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="mb-0 text-sm">Tanggal Terbit Dokumen KTP</label>
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="chkDocDates"
                          v-model="verificationForm.checked_document_dates">
                        <label class="custom-control-label text-xs font-weight-normal text-muted" for="chkDocDates">Tgl
                          Dokumen
                          Dicek</label>
                      </div>
                    </div>
                    <table class="table table-sm table-borderless bg-light mb-0 rounded">
                      <tbody>
                        <tr>
                          <td class="align-middle" style="width:140px;">Tgl Terbit KTP</td>
                          <td class="align-middle"><strong>{{ selectedParticipant.tanggal_terbit_ktp || '-' }}</strong>
                          </td>
                          <td class="p-1" style="width:130px;">
                            <select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.document_dates.tanggal_terbit_ktp">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                  </div>
                </div>
              </div>
            </div>

            <!-- ============================================== -->
            <!-- SECTION 2: KARTU KELUARGA & DOMISILI -->
            <!-- ============================================== -->
            <div class="card shadow-sm mb-3">
              <div class="card-header py-2 bg-white d-flex align-items-center justify-content-between">
                <strong><i class="fas fa-users mr-2 text-info"></i> 2. Kartu Keluarga & Domisili</strong>
              </div>
              <div class="card-body p-0">
                <div class="row m-0">
                  <!-- KIRI: Dokumen KK -->
                  <div class="col-md-5 p-3 border-right" style="background-color: #fcfcfc;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="mb-0">Dokumen Kartu Keluarga</label>
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="chkFamilyCard"
                          v-model="verificationForm.checked_family_card">
                        <label class="custom-control-label text-sm font-weight-normal" for="chkFamilyCard">KK
                          Dicek</label>
                      </div>
                    </div>

                    <div class="mb-2 text-center" v-if="selectedParticipant.family_card_url">
                      <template v-if="String(selectedParticipant.family_card_url).toLowerCase().endsWith('.pdf')">
                        <iframe :src="selectedParticipant.family_card_url"
                          style="width:100%; height:250px; border:1px solid #ddd;" class="rounded"></iframe>
                      </template>
                      <template v-else>
                        <img :src="selectedParticipant.family_card_url" class="img-fluid rounded border shadow-sm"
                          style="max-height:250px;" alt="KK">
                      </template>
                      <div class="mt-2 text-right">
                        <a :href="selectedParticipant.family_card_url" target="_blank"
                          class="btn btn-xs btn-outline-primary"><i class="fas fa-external-link-alt"></i> Buka Penuh</a>
                      </div>
                    </div>
                    <div v-else class="text-center text-muted p-4 border rounded border-dashed">
                      Belum mengunggah KK
                    </div>

                    <div class="form-group mb-0 mt-3 border-top pt-2">
                      <label class="text-xs mb-1">Hasil Verifikasi File KK:</label>
                      <select class="form-control form-control-sm"
                        v-model="verificationForm.field_matches.documents.family_card_url">
                        <option :value="null">-- Pilih Penilaian --</option>
                        <option :value="true">Valid & Sesuai</option>
                        <option :value="false">Buram / Tidak Sesuai</option>
                      </select>
                    </div>
                  </div>

                  <!-- KANAN: Data Domisili -->
                  <div class="col-md-7 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="mb-0">Data Sistem (Cocokkan dengan KK)</label>
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="chkDomicile"
                          v-model="verificationForm.checked_domicile">
                        <label class="custom-control-label text-sm font-weight-normal" for="chkDomicile">Domisili
                          Dicek</label>
                      </div>
                    </div>

                    <table class="table table-sm table-bordered text-sm mb-3">
                      <thead class="bg-light">
                        <tr>
                          <th>Kolom</th>
                          <th>Data Peserta</th>
                          <th style="width: 130px;">Kesesuaian</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td class="align-middle"><strong>Jalan/Alamat Detail</strong></td>
                          <td class="align-middle">{{ selectedParticipant.address || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.domicile.address">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                        <tr>
                          <td class="align-middle"><strong>Wilayah Lengkap</strong></td>
                          <td class="align-middle text-muted">{{ selectedParticipant.full_address || '-' }}</td>
                          <td class="align-middle text-center"><i class="fas fa-check-circle text-success"
                              title="Terverifikasi By System"></i></td>
                        </tr>
                      </tbody>
                    </table>

                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="mb-0 text-sm">Tanggal Terbit Dokumen KK</label>
                    </div>
                    <table class="table table-sm table-borderless bg-light mb-0 rounded">
                      <tbody>
                      <tr>
                        <td class="align-middle" style="width:140px;">Tgl Terbit KK</td>
                        <td class="align-middle"><strong>{{ selectedParticipant.tanggal_terbit_kk || '-' }}</strong>
                        </td>
                        <td class="p-1" style="width:130px;">
                          <select class="form-control form-control-sm"
                            v-model="verificationForm.field_matches.document_dates.tanggal_terbit_kk">
                            <option :value="null">- Nilai -</option>
                            <option :value="true">Sesuai</option>
                            <option :value="false">Tidak</option>
                          </select>
                        </td>
                      </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- ============================================== -->
            <!-- SECTION 3: REKENING BANK -->
            <!-- ============================================== -->
            <div class="card shadow-sm mb-3">
              <div class="card-header py-2 bg-white d-flex align-items-center justify-content-between">
                <strong><i class="fas fa-money-check-alt mr-2 text-success"></i> 3. Buku Rekening & Data Bank</strong>
              </div>
              <div class="card-body p-0">
                <div class="row m-0">
                  <!-- KIRI: Dokumen Bank -->
                  <div class="col-md-5 p-3 border-right" style="background-color: #fcfcfc;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="mb-0">Dokumen Buku Rekening</label>
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="chkBankBook"
                          v-model="verificationForm.checked_bank_book">
                        <label class="custom-control-label text-sm font-weight-normal" for="chkBankBook">Buku Rek.
                          Dicek</label>
                      </div>
                    </div>

                    <div class="mb-2 text-center" v-if="selectedParticipant.bank_book_url">
                      <template v-if="String(selectedParticipant.bank_book_url).toLowerCase().endsWith('.pdf')">
                        <iframe :src="selectedParticipant.bank_book_url"
                          style="width:100%; height:200px; border:1px solid #ddd;" class="rounded"></iframe>
                      </template>
                      <template v-else>
                        <img :src="selectedParticipant.bank_book_url" class="img-fluid rounded border shadow-sm"
                          style="max-height:200px;" alt="Rekening">
                      </template>
                      <div class="mt-2 text-right">
                        <a :href="selectedParticipant.bank_book_url" target="_blank"
                          class="btn btn-xs btn-outline-primary"><i class="fas fa-external-link-alt"></i> Buka Penuh</a>
                      </div>
                    </div>
                    <div v-else class="text-center text-muted p-4 border rounded border-dashed">
                      Belum mengunggah Buku Rekening
                    </div>

                    <div class="form-group mb-0 mt-3 border-top pt-2">
                      <label class="text-xs mb-1">Hasil Verifikasi File Rekening:</label>
                      <select class="form-control form-control-sm"
                        v-model="verificationForm.field_matches.documents.bank_book_url">
                        <option :value="null">-- Pilih Penilaian --</option>
                        <option :value="true">Valid & Sesuai</option>
                        <option :value="false">Buram / Tidak Sesuai</option>
                      </select>
                    </div>
                  </div>

                  <!-- KANAN: Data Bank -->
                  <div class="col-md-7 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="mb-0">Data Sistem (Cocokkan dengan Buku)</label>
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="chkBankAcc"
                          v-model="verificationForm.checked_bank_account">
                        <label class="custom-control-label text-sm font-weight-normal" for="chkBankAcc">Data Rekening
                          Dicek</label>
                      </div>
                    </div>

                    <table class="table table-sm table-bordered text-sm mb-0">
                      <thead class="bg-light">
                        <tr>
                          <th>Kolom</th>
                          <th>Data Peserta</th>
                          <th style="width: 130px;">Kesesuaian</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td class="align-middle"><strong>Nama Bank</strong></td>
                          <td class="align-middle">{{ selectedParticipant.bank_name || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.bank_account.bank_name">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                        <tr>
                          <td class="align-middle"><strong>No. Rekening</strong></td>
                          <td class="align-middle">{{ selectedParticipant.bank_account_number || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.bank_account.bank_account_number">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                        <tr>
                          <td class="align-middle"><strong>Atas Nama (A/N)</strong></td>
                          <td class="align-middle">{{ selectedParticipant.bank_account_name || '-' }}</td>
                          <td class="p-1"><select class="form-control form-control-sm"
                              v-model="verificationForm.field_matches.bank_account.bank_account_name">
                              <option :value="null">- Nilai -</option>
                              <option :value="true">Sesuai</option>
                              <option :value="false">Tidak</option>
                            </select></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- ============================================== -->
            <!-- SECTION 4: SERTIFIKAT, FOTO PROPORSIONAL & KONTAK -->
            <!-- ============================================== -->
            <div class="row">
              <!-- KIRI BAWAH: Pendidikan & Sertifikat -->
              <div class="col-md-5 pr-md-2 mb-3">
                <div class="card shadow-sm h-100">
                  <div class="card-header py-2 bg-white">
                    <strong><i class="fas fa-graduation-cap mr-2 text-warning"></i> 4. Sertifikat Pendukung</strong>
                  </div>
                  <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="mb-0 text-sm">Dok. Sertifikat</label>
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="chkCert"
                          v-model="verificationForm.checked_certificate">
                        <label class="custom-control-label text-xs font-weight-normal" for="chkCert">File Dicek</label>
                      </div>
                    </div>

                    <div class="mb-2 text-center bg-light p-2 rounded border"
                      v-if="selectedParticipant.certificate_url">
                      <a :href="selectedParticipant.certificate_url" target="_blank"
                        class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt"></i> Buka
                        Sertifikat</a>
                    </div>
                    <div v-else class="text-center text-xs text-muted py-3 border rounded border-dashed mb-2">Belum ada
                      file</div>

                    <select class="form-control form-control-sm mb-3"
                      v-model="verificationForm.field_matches.documents.certificate_url">
                      <option :value="null">-- Penilaian File --</option>
                      <option :value="true">Valid</option>
                      <option :value="false">Tidak Valid</option>
                    </select>

                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="mb-0 text-sm">Data Pendidikan</label>
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="chkEdu"
                          v-model="verificationForm.checked_education">
                        <label class="custom-control-label text-xs font-weight-normal" for="chkEdu">Data Dicek</label>
                      </div>
                    </div>
                    <table class="table table-sm table-bordered text-sm mb-0">
                      <tbody>
                      <tr>
                        <td class="align-middle"><strong>Tingkat</strong></td>
                        <td class="align-middle">{{ selectedParticipant.education || '-' }}</td>
                        <td class="p-1" style="width: 80px;"><select class="form-control form-control-sm"
                            v-model="verificationForm.field_matches.education.education">
                            <option :value="null">Nilai</option>
                            <option :value="true">Sesuai</option>
                            <option :value="false">Tidak</option>
                          </select></td>
                      </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <!-- KANAN BAWAH: Pas Foto & Kontak -->
              <div class="col-md-7 pl-md-2 mb-3">
                <div class="card shadow-sm h-100">
                  <div class="card-header py-2 bg-white">
                    <strong><i class="fas fa-user-circle mr-2 text-secondary"></i> 5. Pas Foto & Kontak</strong>
                  </div>
                  <div class="card-body p-0">
                    <div class="row m-0 h-100">

                      <!-- AREA PAS FOTO -->
                      <div
                        class="col-sm-5 border-right p-3 text-center d-flex flex-column align-items-center justify-content-center"
                        style="background-color: #fcfcfc;">
                        <label class="d-block text-sm mb-1 w-100 text-left">Pas Foto (Proporsional)</label>
                        <div class="custom-control custom-checkbox mb-2 align-self-start">
                          <input type="checkbox" class="custom-control-input" id="chkPhoto"
                            v-model="verificationForm.checked_photo">
                          <label class="custom-control-label text-xs font-weight-normal" for="chkPhoto">Foto
                            Dicek</label>
                        </div>

                        <!-- PENGATURAN FOTO 3x4 / 4x6 ASPECT RATIO -->
                        <div class="mb-3" v-if="selectedParticipant.photo_url">
                          <img :src="selectedParticipant.photo_url" class="img-thumbnail shadow-sm"
                            style="width: 120px; height: 160px; object-fit: cover; object-position: top center;"
                            alt="Pas Foto">
                        </div>
                        <div v-else class="text-center text-xs text-muted mb-3 py-4 w-100 border rounded border-dashed">
                          Belum ada pas foto
                        </div>

                        <select class="form-control form-control-sm w-100"
                          v-model="verificationForm.field_matches.documents.photo_url">
                          <option :value="null">- Nilai Pas Foto -</option>
                          <option :value="true">Sesuai Syarat</option>
                          <option :value="false">Tidak Sesuai</option>
                        </select>
                      </div>

                      <!-- AREA KONTAK -->
                      <div class="col-sm-7 p-3">
                        <label class="d-block text-sm mb-1">Kontak HP / WhatsApp</label>
                        <div class="custom-control custom-checkbox mb-2">
                          <input type="checkbox" class="custom-control-input" id="chkContact"
                            v-model="verificationForm.checked_contact">
                          <label class="custom-control-label text-xs font-weight-normal" for="chkContact">Data Kontak
                            Dicek</label>
                        </div>

                        <div class="mb-2 mt-3 text-sm bg-light p-3 rounded border text-center font-weight-bold"
                          style="font-size: 1.1rem !important;">
                          <i class="fas fa-phone-alt mr-2 text-secondary"></i> {{ selectedParticipant.phone_number ||
                            '-' }}
                        </div>

                        <select class="form-control form-control-sm mt-3"
                          v-model="verificationForm.field_matches.contact.phone_number">
                          <option :value="null">-- Nilai Kesesuaian Kontak --</option>
                          <option :value="true">Nomor Sesuai / Aktif</option>
                          <option :value="false">Tidak Sesuai</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ============================================== -->
            <!-- KEPUTUSAN FINAL -->
            <!-- ============================================== -->
            <div class="card bg-light border-primary">
              <div class="card-body p-3">
                <div class="row">
                  <div class="col-md-6 border-right">
                    <label class="d-block mb-2 text-primary"><strong>Status Verifikasi Sesi Ini</strong></label>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" id="statusVerified" value="verified"
                        v-model="verificationForm.status">
                      <label class="form-check-label font-weight-bold text-success" for="statusVerified"><i
                          class="fas fa-check-circle mr-1"></i> Terverifikasi</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" id="statusRejected" value="rejected"
                        v-model="verificationForm.status">
                      <label class="form-check-label font-weight-bold text-danger" for="statusRejected"><i
                          class="fas fa-times-circle mr-1"></i> Ditolak</label>
                    </div>

                    <label class="d-block mt-3 mb-2 text-primary"><strong>Keputusan Terhadap
                        Pendaftaran</strong></label>
                    <select class="form-control border-primary" v-model="verificationForm.registration_status">
                      <option value="process">Kembalikan ke Proses</option>
                      <option value="verified">Lulus / Diterima (Verified)</option>
                      <option value="need_revision">Butuh Perbaikan (Revision)</option>
                      <option value="rejected">Tolak (Rejected)</option>
                      <option value="disqualified">Diskualifikasi</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="verificationNotes" class="text-primary"><strong>Catatan Verifikator (Wajib diisi jika
                        ada
                        penolakan/revisi)</strong></label>
                    <textarea id="verificationNotes" rows="4" class="form-control" v-model="verificationForm.notes"
                      placeholder="Tulis alasan jika butuh revisi (misal: 'Foto KTP buram, silakan upload ulang')..."></textarea>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- LOADING / EMPTY -->
          <div class="modal-body py-5" v-else>
            <div class="text-center text-muted">
              <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Memuat data peserta...
            </div>
          </div>

          <div class="modal-footer bg-light justify-content-between">
            <button type="button" class="btn btn-secondary" :disabled="savingVerification" data-dismiss="modal">
              Batal & Tutup
            </button>
            <button type="button" class="btn btn-primary px-4" @click="submitVerification"
              :disabled="savingVerification">
              <i v-if="savingVerification" class="fas fa-spinner fa-spin mr-1"></i>
              <i v-else class="fas fa-save mr-1"></i>
              Simpan Hasil Verifikasi
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
  event_group_id: '',
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

const eventBranches = ref([])
const eventGroups = ref([])
const eventCategories = ref([])

const settingStore = useSettingStore()
const authUserStore = useAuthUserStore()
const masterDataStore = useMasterDataStore()
const currentUser = computed(() => authUserStore.user || null)

const canVerifyRole = computed(() => {
  const u = currentUser.value
  const roleId = u?.role_id ?? u?.role?.id ?? null
  return [1, 4].includes(Number(roleId))
})

const canShowVerifyButton = (item) => {
  if (!canVerifyRole.value) return false
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
  { key: 'process', label: 'Proses', badgeClass: 'badge-warning' },
  { key: 'verified', label: 'Diterima', badgeClass: 'badge-success' },
  { key: 'need_revision', label: 'Perbaiki', badgeClass: 'badge-info' },
  { key: 'rejected', label: 'Tolak', badgeClass: 'badge-secondary' },
  { key: 'disqualified', label: 'Mundur', badgeClass: 'badge-danger' },
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
        withVerifications: 1,
        event_group_id: filters.value.event_group_id || '',
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
    if (error.response && error.response.status === 401) {
      // ✅ Gunakan handler bawaan store untuk penanganan error 401 yang bersih
      authUserStore.handleAuthError(error)
    } else {
      Swal.fire('Gagal', 'Gagal memuat data peserta event.', 'error')
    }
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
// VERIFICATIONS
// =========================
const selectedEventParticipant = ref(null)
const selectedParticipant = computed(() => selectedEventParticipant.value?.participant || null)
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

const verificationForm = reactive({
  id: null,
  status: 'verified',
  checked_photo: false,
  checked_id_card: false,
  checked_family_card: false,
  checked_bank_book: false,
  checked_certificate: false,
  checked_other: false,

  checked_identity: false,
  checked_contact: false,
  checked_domicile: false,
  checked_education: false,
  checked_bank_account: false,
  checked_document_dates: false,

  field_matches: {
    identity: { nik: null, full_name: null, place_of_birth: null, date_of_birth: null, gender: null },
    contact: { phone_number: null },
    domicile: { province_id: null, regency_id: null, district_id: null, village_id: null, address: null, province_name: null, regency_name: null, district_name: null, village_name: null },
    education: { education: null },
    bank_account: { bank_account_number: null, bank_account_name: null, bank_name: null },
    document_dates: { tanggal_terbit_ktp: null, tanggal_terbit_kk: null },
    documents: { photo_url: null, id_card_url: null, family_card_url: null, bank_book_url: null, certificate_url: null, other_url: null },
  },
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
    domicile: { province_id: null, regency_id: null, district_id: null, village_id: null, address: null, province_name: null, regency_name: null, district_name: null, village_name: null },
    education: { education: null },
    bank_account: { bank_account_number: null, bank_account_name: null, bank_name: null },
    document_dates: { tanggal_terbit_ktp: null, tanggal_terbit_kk: null },
    documents: { photo_url: null, id_card_url: null, family_card_url: null, bank_book_url: null, certificate_url: null, other_url: null },
  })
  verificationForm.notes = ''
}

const openVerification = (ep) => {
  selectedEventParticipant.value = ep
  resetVerificationForm()

  verificationForm.registration_status = ep?.registration_status || 'process'

  const v = ep?.participant?.latest_verification || ep?.latest_verification || ep?.verification || null
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
    if (v.field_matches) {
      Object.assign(verificationForm.field_matches, v.field_matches)
    }
  }
  $('#showVerificationModal').modal('show')
}

const validateVerificationDetails = () => {
  const errors = []
  const p = selectedParticipant.value
  if (!p) return ['Peserta tidak ditemukan.']
  const fm = verificationForm.field_matches || {}
  const isUnset = (val) => val === null || typeof val === 'undefined'
  const docs = fm.documents || {}

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
    if (!verificationForm.checked_other) errors.push('Akta Kelahiran: "Sudah dicek" belum dicentang.')
    if (isUnset(docs.other_url)) errors.push('Akta Kelahiran: hasil kesesuaian belum dipilih.')
  }

  const identity = fm.identity || {}
  if (!verificationForm.checked_identity) errors.push('Kelompok "Identitas" belum dicentang "Sudah dicek".')
    ;['nik', 'full_name', 'place_of_birth', 'date_of_birth', 'gender'].forEach((k) => {
      if (isUnset(identity[k])) errors.push(`Identitas: ${k} belum dinilai.`)
    })

  const contact = fm.contact || {}
  if (!verificationForm.checked_contact) errors.push('Kelompok "Kontak" belum dicentang "Sudah dicek".')
  if (isUnset(contact.phone_number)) errors.push('Kontak: phone_number belum dinilai.')

  const domicile = fm.domicile || {}
  if (!verificationForm.checked_domicile) errors.push('Kelompok "Domisili" belum dicentang "Sudah dicek".')
  if (isUnset(domicile.address)) errors.push('Domisili: address belum dinilai.')

  const edu = fm.education || {}
  if (!verificationForm.checked_education) errors.push('Kelompok "Pendidikan" belum dicentang "Sudah dicek".')
  if (isUnset(edu.education)) errors.push('Pendidikan: education belum dinilai.')

  const bank = fm.bank_account || {}
  if (!verificationForm.checked_bank_account) errors.push('Kelompok "Rekening Bank" belum dicentang "Sudah dicek".')
    ;['bank_account_number', 'bank_account_name', 'bank_name'].forEach((k) => {
      if (isUnset(bank[k])) errors.push(`Rekening: ${k} belum dinilai.`)
    })

  const dd = fm.document_dates || {}
  if (!verificationForm.checked_document_dates) errors.push('Kelompok "Tanggal Dokumen" belum dicentang "Sudah dicek".')
    ;['tanggal_terbit_ktp', 'tanggal_terbit_kk'].forEach((k) => {
      if (isUnset(dd[k])) errors.push(`Tanggal dokumen: ${k} belum dinilai.`)
    })

  return errors
}

const submitVerification = async () => {
  if (!selectedEventParticipant.value || !selectedParticipant.value) {
    Swal.fire({ icon: 'warning', title: 'Tidak ada peserta yang dipilih.' })
    return
  }

  if (!verificationForm.status) {
    Swal.fire({ icon: 'warning', title: 'Status verifikasi belum dipilih.' })
    return
  }
  if (!verificationForm.registration_status) {
    Swal.fire({ icon: 'warning', title: 'Keputusan pendaftaran belum dipilih.' })
    return
  }

  if (!settingStore.isDevelopment) {
    const detailErrors = validateVerificationDetails()
    if (detailErrors.length) {
      Swal.fire({
        icon: 'warning',
        title: 'Form verifikasi belum lengkap',
        html: `<ul style="text-align:left; max-height:260px; overflow-y:auto; padding-left:18px;">${detailErrors.map(e => `<li>${e}</li>`).join('')}</ul>`,
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
    const p = selectedParticipant.value

    const payload = {
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
      registration_status: verificationForm.registration_status,
    }

    await axios.post(`/api/v1/participants/${p.uuid}/verifications`, payload)

    $('#showVerificationModal').modal('hide')
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

watch(() => ({ ...filters.value }), () => fetchItems(1))
watch(() => props.status, (val) => { activeStatus.value = val || ''; fetchItems(1) }, { immediate: true })
watch(() => eventId.value, (val) => { if (!val) return; fetchItems(1); fetchStatusCounts() }, { immediate: true })
watch(() => search.value, useDebounceFn(() => fetchItems(1), 400))
watch(() => perPage.value, () => fetchItems(1))

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

.border-dashed {
  border: 2px dashed #dee2e6 !important;
}
</style>