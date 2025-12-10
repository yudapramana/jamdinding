  <template>
    <section class="content-header">
      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="mb-1">Pendaftaran Ulang Peserta</h1>
            <p class="mb-0 text-muted text-sm">
              Event:
              <strong>{{ eventInfo?.nama_event || '-' }}</strong>
              <span v-if="eventInfo?.lokasi_event">
                • {{ eventInfo.lokasi_event }}
              </span>
            </p>
          </div>
          
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
         
          <!-- MAIN TABLE -->
          <div class="col-md-12">
            <div class="card">
              <!-- HEADER: perPage + status_daftar_ulang + search -->
              <div class="card-header">
                <div class="row w-100">

                  <!-- LEFT SIDE -->
                  <div class="col-md-6 d-flex align-items-center flex-wrap">

                    <!-- perPage -->
                    <label class="mb-0 mr-2 text-sm text-muted">Tampilkan</label>
                    <select v-model.number="perPage" class="form-control form-control-sm w-auto mr-3">
                      <option :value="10">10</option>
                      <option :value="25">25</option>
                      <option :value="50">50</option>
                      <option :value="100">100</option>
                    </select>

                    <!-- status daftar ulang -->
                    <label class="mb-0 mr-2 text-sm text-muted">Daftar Ulang</label>
                    <select v-model="filterDaftarUlang" class="form-control form-control-sm w-auto">
                      <option value="belum">Belum</option>
                      <option value="terverifikasi">Terverifikasi</option>
                      <option value="gagal">Gagal</option>
                    </select>

                  </div>

                  <!-- RIGHT SIDE (search) -->
                  <div class="col-md-6 d-flex justify-content-end">
                    <input
                      v-model="search"
                      type="text"
                      class="form-control form-control-sm w-75"
                      placeholder="Cari nama, NIK, atau nomor HP..."
                    />
                  </div>

                </div>
              </div>


              <!-- TABLE -->
              <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-hover text-sm mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th style="width: 40px;">#</th>
                      <th>Nama Peserta</th>
                      <th>NIK</th>
                      <th>Cabang / Golongan</th>
                      <th>Asal</th>
                      <!-- <th>Pendaftaran</th> -->
                      <th>Daftar Ulang</th>
                      <th style="width: 110px;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="isLoading">
                      <td colspan="7" class="text-center">Memuat data...</td>
                    </tr>
                    <tr v-else-if="participants.length === 0">
                      <td colspan="7" class="text-center">
                        Tidak ada data peserta dengan status
                        <strong class="text-capitalize">{{ activeStatus }}</strong>.
                      </td>
                    </tr>
                    <tr
                      v-for="(p, index) in participants"
                      :key="p.id"
                    >
                      <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>

                      <td>
                        <strong>{{ p.full_name }}</strong>
                        <div class="text-xs text-muted">
                          {{ p.gender === 'MALE' ? 'Laki-laki' : 'Perempuan' }},
                          lahir {{ p.place_of_birth }}
                        </div>
                      </td>

                      <td>
                        {{ p.nik }}
                        <div class="text-xs text-muted">
                          Umur:
                          {{ p.age_year }}T
                          {{ p.age_month }}B
                          {{ p.age_day }}H
                        </div>
                      </td>

                      <td>
                        <span class="text-sm">
                          {{ p.competition_branch?.name || '-' }}
                        </span>
                        <div class="text-xs text-muted" v-if="p.competition_branch">
                          Batas:
                          {{ p.competition_branch.max_age - 1 }}T
                          11B
                          29H
                        </div>
                      </td>

                      <td>
                        <span class="text-sm">
                          {{ getAsalWilayah(p) }}
                        </span>
                      </td>

                      <!-- <td>
                        <span
                          class="badge badge-sm text-uppercase"
                          :class="statusBadgeClass(p.status_pendaftaran)"
                        >
                          {{ p.status_pendaftaran || 'bankdata' }}
                        </span>
                      </td> -->
                      <td>
                        <span
                          class="badge badge-sm text-uppercase"
                          :class="statusBadgeClass(p.status_daftar_ulang)"
                        >
                          {{ p.status_daftar_ulang || 'belum' }}
                        </span>
                      </td>

                      <td class="text-center">
                        <div class="btn-group btn-group-sm">

                          <!-- LIHAT DATA -->
                          <button
                            class="btn btn-outline-primary btn-xs"
                            title="Lihat Data Peserta"
                            @click="openViewModal(p)"
                          >
                            <i class="fas fa-eye"></i>
                          </button>

                          <!-- CETAK / LIHAT BIODATA PDF -->
                          <button
                            class="btn btn-outline-danger btn-xs"
                            title="Cetak Biodata (PDF)"
                            @click="openBiodataPdf(p)"
                          >
                            <i class="far fa-file-pdf"></i>
                          </button>

                          <button
                            v-if="!['diterima', 'tolak', 'mundur'].includes(p.status_pendaftaran)"
                            class="btn btn-outline-success btn-xs"
                            title="Verifikasi Peserta"
                            @click="openVerification(p)"
                          >
                            <i class="fas fa-clipboard-check"></i>
                          </button>

                          <!-- DAFTAR ULANG (HANYA YANG SUDAH DITERIMA) -->
                            <button
                            v-if="canReRegister(p)"
                            class="btn btn-outline-warning btn-xs"
                            title="Proses Daftar Ulang"
                            @click="openReRegisterModal(p)"
                            >
                            <i class="fas fa-user-check"></i>
                            </button>
                        </div>
                      </td>

                      

                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- FOOTER: pagination -->
              <div class="card-footer clearfix">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="text-muted text-sm">
                    Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
                    {{ meta.total || 0 }} peserta
                  </div>
                  <ul class="pagination pagination-sm m-0">
                    <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                      <a
                        class="page-link"
                        href="#"
                        @click.prevent="changePage(meta.current_page - 1)"
                      >
                        «
                      </a>
                    </li>
                    <li class="page-item disabled">
                      <span class="page-link">
                        Halaman {{ meta.current_page }} / {{ meta.last_page || 1 }}
                      </span>
                    </li>
                    <li
                      class="page-item"
                      :class="{ disabled: meta.current_page === meta.last_page }"
                    >
                      <a
                        class="page-link"
                        href="#"
                        @click.prevent="changePage(meta.current_page + 1)"
                      >
                        »
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- di sini tetap bisa include modal2 yang sama dengan ParticipantList.vue:
                - Modal Tambah/Edit (participantModal)
                - Modal Lihat (viewParticipantModal)
                - Modal Mutasi, dll.
                Tinggal copy dari file sebelumnya -->
          </div>
        </div>
      </div>

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
                              {{ selectedParticipant.full_name }}
                            </td>
                          </tr>
                          <tr>
                            <th>NIK</th>
                            <td class="text-monospace">
                              {{ selectedParticipant.nik }}
                            </td>
                          </tr>
                          <tr>
                            <th>Tempat Lahir</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.place_of_birth || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Tanggal Lahir</th>
                            <td>
                              <span class="text-danger font-weight-bold mr-2">
                                {{ formatDate(selectedParticipant.date_of_birth) }}
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
                            <td>{{ selectedParticipant.phone_number || '-' }}</td>
                          </tr>
                          <tr>
                            <th>Jenis Kelamin</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.gender === 'MALE' ? 'LAKI-LAKI' : 'PEREMPUAN' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Cabang Lomba</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.competition_branch?.name || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Asal</th>
                            <td class="text-uppercase">
                              {{ getAsalWilayah(selectedParticipant) }}
                            </td>
                          </tr>
                          <tr>
                            <th>Alamat</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.address || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Pendidikan</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.education || '-' }}
                            </td>
                          </tr>
                          <!-- <tr>
                            <th>Detail Rekening</th>
                            <td>
                              {{ selectedParticipant.bank_account_number || '-' }} <br>
                              a.n <span class="text-uppercase">{{ selectedParticipant.bank_account_name || '-' }}</span><br>
                              {{ selectedParticipant.bank_name || '-' }}<br>
                            </td>
                          </tr> -->
                          <tr>
                            <th>Nomor Rekening</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.bank_account_number || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Akun Rekening</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.bank_account_name || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Bank Rekening</th>
                            <td class="text-uppercase">
                              {{ selectedParticipant.bank_name || '-' }}
                            </td>
                          </tr>
                          <tr>
                            <th>Kategori</th>
                            <td class="text-uppercase">
                              <!-- kalau punya field kategori sendiri bisa ganti -->
                              PESERTA INTI
                            </td>
                          </tr>
                          <tr>
                            <th>Terbit KTP</th>
                            <td class="text-danger font-weight-bold">
                              {{ formatDate(selectedParticipant.tanggal_terbit_ktp) }}
                            </td>
                          </tr>

                          <tr>
                            <th>Terbit KK</th>
                            <td class="text-danger font-weight-bold">
                              {{ formatDate(selectedParticipant.tanggal_terbit_kk) }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- RIWAYAT VERIFIKASI (HANYA YANG REJECTED) -->
                  <div
                    class="card shadow-sm border mt-3"
                    v-if="rejectedVerifications.length != 0 && selectedParticipant.status_pendaftaran != 'diterima'"
                  >
                    <div class="card-header border-0 d-flex justify-content-between align-items-center py-2">
                      <span class="font-weight-bold">
                        Riwayat Verifikasi (Ditolak)
                      </span>
                      <span class="badge badge-warning text-uppercase">
                        Status Pendaftaran: {{ selectedParticipant.status_pendaftaran || '-' }}
                      </span>
                    </div>

                    <div class="card-body p-0">
                      <ul class="list-group list-group-flush mb-0">
                        <li
                          v-for="(verif, index) in rejectedVerifications"
                          :key="verif.id || index"
                          class="list-group-item"
                        >
                          <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                              <span class="badge badge-danger mr-2">DITOLAK</span>
                              <small class="text-muted">
                                #{{ index + 1 }}
                              </small>
                            </div>
                            <small class="text-muted">
                              {{ formatDateTime(verif.created_at) }}
                            </small>
                          </div>

                          <p class="mb-0 text-sm">
                            {{ verif.notes || 'Catatan verifikasi belum diisi. Silakan hubungi verifikator atau cek hasil verifikasi lebih lanjut.' }}
                          </p>
                        </li>
                      </ul>
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
                        v-if="selectedParticipant.photo_url"
                        class="mx-auto rounded-circle overflow-hidden border"
                        style="width:180px;height:180px;"
                      >
                        <img
                          :src="selectedParticipant.photo_url"
                          alt="Foto Peserta"
                          class="img-fluid"
                          style="object-fit:cover;width:100%;height:100%;"
                        />
                      </div>

                      <div v-else class="mx-auto text-muted" style="align-items: center; text-align: center;">
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
                            <th>Tanggal Input Data<br></br>
                            <span class="text-right text-danger font-weight-bold">
                              {{ formatDateTime(selectedParticipant.created_at) }}
                            </span>
                            </th>
                          </tr>

                          <tr>
                            <th>Tanggal Update Data<br></br>
                            <span class="text-right text-danger font-weight-bold">
                              {{ formatDateTime(selectedParticipant.updated_at) }}
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

            <div class="modal-footer py-2">
              <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
                Tutup
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- MODAL VERIFIKASI PESERTA -->
      <div class="modal fade" id="showVerificationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                Verifikasi Data & Dokumen Peserta
              </h5>
              <button type="button" class="close" data-dismiss="modal">
                <span>&times;</span>
              </button>
            </div>

            <div class="modal-body" v-if="selectedParticipant">
              <!-- Info singkat peserta -->
              <div class="mb-3">
                <h6 class="mb-1">
                  {{ selectedParticipant.full_name || selectedParticipant.nama_lengkap }}
                </h6>
                <p class="mb-0 text-muted text-sm">
                  NIK: {{ selectedParticipant.nik || '-' }}<br>
                  Cabang: {{ selectedParticipant.branch_name || selectedParticipant.cabang_lomba || '-' }}
                </p>
              </div>

              <hr>

              <!-- STATUS VERIFIKASI -->
              <div class="form-group">
                <label class="d-block mb-1"><strong>Status Verifikasi Sesi Ini</strong></label>
                <div class="form-check form-check-inline">
                  <input
                    class="form-check-input"
                    type="radio"
                    id="statusVerified"
                    value="verified"
                    v-model="verificationForm.status"
                  >
                  <label class="form-check-label" for="statusVerified">
                    Terverifikasi
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <input
                    class="form-check-input"
                    type="radio"
                    id="statusRejected"
                    value="rejected"
                    v-model="verificationForm.status"
                  >
                  <label class="form-check-label" for="statusRejected">
                    Ditolak
                  </label>
                </div>
              </div>

              <!-- KEPUTUSAN TERHADAP PENDAFTARAN (EVENT_PARTICIPANTS.status_pendaftaran) -->
              <div class="form-group">
                <label class="d-block mb-1">
                  <strong>Keputusan Terhadap Pendaftaran</strong>
                </label>

                <div class="row">
                  <div class="col-md-6">
                    <select
                      class="form-control"
                      v-model="verificationForm.status_pendaftaran"
                    >
                      <option value="bankdata">Bank data (data awal / belum diproses)</option>
                      <option value="proses">Proses (sedang diverifikasi / menunggu keputusan)</option>
                      <option value="diterima">Diterima (lolos verifikasi)</option>
                      <option value="perbaiki">Perbaiki (butuh perbaikan data/dokumen)</option>
                      <option value="mundur">Mundur (peserta mengundurkan diri)</option>
                      <option value="tolak">Tolak (tidak memenuhi syarat)</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <small class="form-text text-muted">
                      Status ini akan mengubah kolom <code>status_pendaftaran</code> pada tabel
                      <code>event_participants</code> (berdasarkan <code>event_participant_id</code>).
                      <br>
                      Disarankan:
                      <ul class="mb-0 pl-3">
                        <li><code>diterima</code> untuk peserta yang lulus verifikasi</li>
                        <li><code>perbaiki</code> jika masih ada kekurangan</li>
                        <li><code>tolak</code> bila tidak memenuhi syarat</li>
                      </ul>
                    </small>
                  </div>
                </div>
              </div>


              <!-- ====================== -->
              <!-- BAGIAN: DOKUMEN UNGGAHAN (VERSI COMPACT) -->
              <!-- ====================== -->
              <div class="card mb-3">
                <div class="card-header py-2">
                  <strong>1. Dokumen Unggahan Peserta</strong>
                  <div class="small text-muted">
                    PDF ditampilkan langsung di panel, gambar tampil sebagai preview di sebelah kanan.
                  </div>
                </div>
                <div class="card-body p-2">

                  <!-- Foto Peserta -->
                  <div class="mb-3" v-if="selectedParticipant.photo_url">
                    <div class="d-flex flex-wrap">
                      <!-- KONTROL KIRI -->
                      <div class="mb-2 mr-md-3" style="min-width: 240px; max-width: 260px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <strong>Foto Peserta</strong>
                        </div>
                        <div class="form-group mb-1">
                          <div class="form-check">
                            <input
                              type="checkbox"
                              class="form-check-input"
                              id="chkPhoto"
                              v-model="verificationForm.checked_photo"
                            >
                            <label class="form-check-label" for="chkPhoto">
                              Sudah dicek
                            </label>
                          </div>
                        </div>
                        <div class="form-group mb-0">
                          <label class="small mb-1">Hasil kesesuaian</label>
                          <select
                            class="form-control form-control-sm"
                            v-model="verificationForm.field_matches.documents.photo_url"
                          >
                            <option :value="null">Belum dinilai</option>
                            <option :value="true">Sesuai</option>
                            <option :value="false">Tidak sesuai</option>
                          </select>
                        </div>
                      </div>

                      <!-- VIEWER KANAN -->
                      <div class="flex-grow-1">
                        <template v-if="selectedParticipant.photo_url.toLowerCase().endsWith('.pdf')">
                          <div style="height: 360px; border: 1px solid #dee2e6;" class="mb-1">
                            <iframe
                              :src="selectedParticipant.photo_url"
                              style="width: 100%; height: 100%; border: 0;"
                            ></iframe>
                          </div>
                          <a
                            :href="selectedParticipant.photo_url"
                            target="_blank"
                            class="btn btn-xs btn-outline-primary"
                          >
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </template>
                        <template v-else>
                          <img
                            :src="selectedParticipant.photo_url"
                            alt="Foto Peserta"
                            class="img-fluid img-thumbnail mb-1"
                            style="max-height: 360px;"
                          >
                          <div>
                            <a
                              :href="selectedParticipant.photo_url"
                              target="_blank"
                              class="btn btn-xs btn-outline-primary"
                            >
                              <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                            </a>
                          </div>
                        </template>
                      </div>
                    </div>
                    <hr class="my-2" v-if="selectedParticipant.id_card_url || selectedParticipant.family_card_url || selectedParticipant.bank_book_url || selectedParticipant.certificate_url || selectedParticipant.other_url">
                  </div>

                  <!-- KTP / KIA -->
                  <div class="mb-3" v-if="selectedParticipant.id_card_url">
                    <div class="d-flex flex-wrap">
                      <div class="mb-2 mr-md-3" style="min-width: 240px; max-width: 260px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <strong>KTP / KIA</strong>
                        </div>
                        <div class="form-group mb-1">
                          <div class="form-check">
                            <input
                              type="checkbox"
                              class="form-check-input"
                              id="chkIdCard"
                              v-model="verificationForm.checked_id_card"
                            >
                            <label class="form-check-label" for="chkIdCard">
                              Sudah dicek
                            </label>
                          </div>
                        </div>
                        <div class="form-group mb-0">
                          <label class="small mb-1">Hasil kesesuaian</label>
                          <select
                            class="form-control form-control-sm"
                            v-model="verificationForm.field_matches.documents.id_card_url"
                          >
                            <option :value="null">Belum dinilai</option>
                            <option :value="true">Sesuai</option>
                            <option :value="false">Tidak sesuai</option>
                          </select>
                        </div>
                      </div>

                      <div class="flex-grow-1">
                        <template v-if="selectedParticipant.id_card_url.toLowerCase().endsWith('.pdf')">
                          <div style="height: 360px; border: 1px solid #dee2e6;" class="mb-1">
                            <iframe
                              :src="selectedParticipant.id_card_url"
                              style="width: 100%; height: 100%; border: 0;"
                            ></iframe>
                          </div>
                          <a
                            :href="selectedParticipant.id_card_url"
                            target="_blank"
                            class="btn btn-xs btn-outline-primary"
                          >
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </template>
                        <template v-else>
                          <img
                            :src="selectedParticipant.id_card_url"
                            alt="KTP / KIA"
                            class="img-fluid img-thumbnail mb-1"
                            style="max-height: 360px;"
                          >
                          <div>
                            <a
                              :href="selectedParticipant.id_card_url"
                              target="_blank"
                              class="btn btn-xs btn-outline-primary"
                            >
                              <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                            </a>
                          </div>
                        </template>
                      </div>
                    </div>
                    <hr class="my-2" v-if="selectedParticipant.family_card_url || selectedParticipant.bank_book_url || selectedParticipant.certificate_url || selectedParticipant.other_url">
                  </div>

                  <!-- Kartu Keluarga -->
                  <div class="mb-3" v-if="selectedParticipant.family_card_url">
                    <div class="d-flex flex-wrap">
                      <div class="mb-2 mr-md-3" style="min-width: 240px; max-width: 260px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <strong>Kartu Keluarga</strong>
                        </div>
                        <div class="form-group mb-1">
                          <div class="form-check">
                            <input
                              type="checkbox"
                              class="form-check-input"
                              id="chkFamilyCard"
                              v-model="verificationForm.checked_family_card"
                            >
                            <label class="form-check-label" for="chkFamilyCard">
                              Sudah dicek
                            </label>
                          </div>
                        </div>
                        <div class="form-group mb-0">
                          <label class="small mb-1">Hasil kesesuaian</label>
                          <select
                            class="form-control form-control-sm"
                            v-model="verificationForm.field_matches.documents.family_card_url"
                          >
                            <option :value="null">Belum dinilai</option>
                            <option :value="true">Sesuai</option>
                            <option :value="false">Tidak sesuai</option>
                          </select>
                        </div>
                      </div>

                      <div class="flex-grow-1">
                        <template v-if="selectedParticipant.family_card_url.toLowerCase().endsWith('.pdf')">
                          <div style="height: 360px; border: 1px solid #dee2e6;" class="mb-1">
                            <iframe
                              :src="selectedParticipant.family_card_url"
                              style="width: 100%; height: 100%; border: 0;"
                            ></iframe>
                          </div>
                          <a
                            :href="selectedParticipant.family_card_url"
                            target="_blank"
                            class="btn btn-xs btn-outline-primary"
                          >
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </template>
                        <template v-else>
                          <img
                            :src="selectedParticipant.family_card_url"
                            alt="Kartu Keluarga"
                            class="img-fluid img-thumbnail mb-1"
                            style="max-height: 360px;"
                          >
                          <div>
                            <a
                              :href="selectedParticipant.family_card_url"
                              target="_blank"
                              class="btn btn-xs btn-outline-primary"
                            >
                              <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                            </a>
                          </div>
                        </template>
                      </div>
                    </div>
                    <hr class="my-2" v-if="selectedParticipant.bank_book_url || selectedParticipant.certificate_url || selectedParticipant.other_url">
                  </div>

                  <!-- Buku Rekening -->
                  <div class="mb-3" v-if="selectedParticipant.bank_book_url">
                    <div class="d-flex flex-wrap">
                      <div class="mb-2 mr-md-3" style="min-width: 240px; max-width: 260px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <strong>Buku Rekening</strong>
                        </div>
                        <div class="form-group mb-1">
                          <div class="form-check">
                            <input
                              type="checkbox"
                              class="form-check-input"
                              id="chkBankBook"
                              v-model="verificationForm.checked_bank_book"
                            >
                            <label class="form-check-label" for="chkBankBook">
                              Sudah dicek
                            </label>
                          </div>
                        </div>
                        <div class="form-group mb-0">
                          <label class="small mb-1">Hasil kesesuaian</label>
                          <select
                            class="form-control form-control-sm"
                            v-model="verificationForm.field_matches.documents.bank_book_url"
                          >
                            <option :value="null">Belum dinilai</option>
                            <option :value="true">Sesuai</option>
                            <option :value="false">Tidak sesuai</option>
                          </select>
                        </div>
                      </div>

                      <div class="flex-grow-1">
                        <template v-if="selectedParticipant.bank_book_url.toLowerCase().endsWith('.pdf')">
                          <div style="height: 360px; border: 1px solid #dee2e6;" class="mb-1">
                            <iframe
                              :src="selectedParticipant.bank_book_url"
                              style="width: 100%; height: 100%; border: 0;"
                            ></iframe>
                          </div>
                          <a
                            :href="selectedParticipant.bank_book_url"
                            target="_blank"
                            class="btn btn-xs btn-outline-primary"
                          >
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </template>
                        <template v-else>
                          <img
                            :src="selectedParticipant.bank_book_url"
                            alt="Buku Rekening"
                            class="img-fluid img-thumbnail mb-1"
                            style="max-height: 360px;"
                          >
                          <div>
                            <a
                              :href="selectedParticipant.bank_book_url"
                              target="_blank"
                              class="btn btn-xs btn-outline-primary"
                            >
                              <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                            </a>
                          </div>
                        </template>
                      </div>
                    </div>
                    <hr class="my-2" v-if="selectedParticipant.certificate_url || selectedParticipant.other_url">
                  </div>

                  <!-- Sertifikat Pendukung -->
                  <div class="mb-3" v-if="selectedParticipant.certificate_url">
                    <div class="d-flex flex-wrap">
                      <div class="mb-2 mr-md-3" style="min-width: 240px; max-width: 260px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <strong>Sertifikat Pendukung</strong>
                        </div>
                        <div class="form-group mb-1">
                          <div class="form-check">
                            <input
                              type="checkbox"
                              class="form-check-input"
                              id="chkCertificate"
                              v-model="verificationForm.checked_certificate"
                            >
                            <label class="form-check-label" for="chkCertificate">
                              Sudah dicek
                            </label>
                          </div>
                        </div>
                        <div class="form-group mb-0">
                          <label class="small mb-1">Hasil kesesuaian</label>
                          <select
                            class="form-control form-control-sm"
                            v-model="verificationForm.field_matches.documents.certificate_url"
                          >
                            <option :value="null">Belum dinilai</option>
                            <option :value="true">Sesuai</option>
                            <option :value="false">Tidak sesuai</option>
                          </select>
                        </div>
                      </div>

                      <div class="flex-grow-1">
                        <template v-if="selectedParticipant.certificate_url.toLowerCase().endsWith('.pdf')">
                          <div style="height: 360px; border: 1px solid #dee2e6;" class="mb-1">
                            <iframe
                              :src="selectedParticipant.certificate_url"
                              style="width: 100%; height: 100%; border: 0;"
                            ></iframe>
                          </div>
                          <a
                            :href="selectedParticipant.certificate_url"
                            target="_blank"
                            class="btn btn-xs btn-outline-primary"
                          >
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </template>
                        <template v-else>
                          <img
                            :src="selectedParticipant.certificate_url"
                            alt="Sertifikat Pendukung"
                            class="img-fluid img-thumbnail mb-1"
                            style="max-height: 360px;"
                          >
                          <div>
                            <a
                              :href="selectedParticipant.certificate_url"
                              target="_blank"
                              class="btn btn-xs btn-outline-primary"
                            >
                              <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                            </a>
                          </div>
                        </template>
                      </div>
                    </div>
                    <hr class="my-2" v-if="selectedParticipant.other_url">
                  </div>

                  <!-- Dokumen Lainnya -->
                  <div class="mb-0" v-if="selectedParticipant.other_url">
                    <div class="d-flex flex-wrap">
                      <div class="mb-2 mr-md-3" style="min-width: 240px; max-width: 260px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <strong>Dokumen Lainnya</strong>
                        </div>
                        <div class="form-group mb-1">
                          <div class="form-check">
                            <input
                              type="checkbox"
                              class="form-check-input"
                              id="chkOther"
                              v-model="verificationForm.checked_other"
                            >
                            <label class="form-check-label" for="chkOther">
                              Sudah dicek
                            </label>
                          </div>
                        </div>
                        <div class="form-group mb-0">
                          <label class="small mb-1">Hasil kesesuaian</label>
                          <select
                            class="form-control form-control-sm"
                            v-model="verificationForm.field_matches.documents.other_url"
                          >
                            <option :value="null">Belum dinilai</option>
                            <option :value="true">Sesuai</option>
                            <option :value="false">Tidak sesuai</option>
                          </select>
                        </div>
                      </div>

                      <div class="flex-grow-1">
                        <template v-if="selectedParticipant.other_url.toLowerCase().endsWith('.pdf')">
                          <div style="height: 360px; border: 1px solid #dee2e6;" class="mb-1">
                            <iframe
                              :src="selectedParticipant.other_url"
                              style="width: 100%; height: 100%; border: 0;"
                            ></iframe>
                          </div>
                          <a
                            :href="selectedParticipant.other_url"
                            target="_blank"
                            class="btn btn-xs btn-outline-primary"
                          >
                            <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                          </a>
                        </template>
                        <template v-else>
                          <img
                            :src="selectedParticipant.other_url"
                            alt="Dokumen Lainnya"
                            class="img-fluid img-thumbnail mb-1"
                            style="max-height: 360px;"
                          >
                          <div>
                            <a
                              :href="selectedParticipant.other_url"
                              target="_blank"
                              class="btn btn-xs btn-outline-primary"
                            >
                              <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                            </a>
                          </div>
                        </template>
                      </div>
                    </div>
                  </div>

                  <!-- Jika tidak ada dokumen sama sekali -->
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
              <!-- BAGIAN: DATA PESERTA -->
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
                            <input
                              type="checkbox"
                              class="form-check-input mr-1"
                              v-model="verificationForm.checked_identity"
                            >
                            <span class="align-middle">Sudah dicek</span>
                          </label>
                        </small>
                      </h6>
                      <table class="table table-sm table-borderless mb-1">
                        <tbody>
                          <tr>
                            <th class="p-1 align-middle">NIK</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.nik || '-' }}
                            </td>
                            <td class="p-1 align-middle" style="width: 110px;">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.identity.nik"
                              >
                                <option :value="null">Belum dinilai</option>
                                <option :value="true">Sesuai</option>
                                <option :value="false">Tidak sesuai</option>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th class="p-1 align-middle">Nama</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.full_name || selectedParticipant.nama_lengkap || '-' }}
                            </td>
                            <td class="p-1 align-middle">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.identity.full_name"
                              >
                                <option :value="null">Belum dinilai</option>
                                <option :value="true">Sesuai</option>
                                <option :value="false">Tidak sesuai</option>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th class="p-1 align-middle">Tempat / Tgl Lahir</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.place_of_birth || '-' }},
                              {{ selectedParticipant.date_of_birth || '-' }}
                            </td>
                            <td class="p-1 align-middle">
                              <div class="d-flex flex-column">
                                <select
                                  class="form-control form-control-sm mb-1"
                                  v-model="verificationForm.field_matches.identity.place_of_birth"
                                >
                                  <option :value="null">Tmpt: -</option>
                                  <option :value="true">Sesuai</option>
                                  <option :value="false">Tidak sesuai</option>
                                </select>
                                <select
                                  class="form-control form-control-sm"
                                  v-model="verificationForm.field_matches.identity.date_of_birth"
                                >
                                  <option :value="null">Tgl: -</option>
                                  <option :value="true">Sesuai</option>
                                  <option :value="false">Tidak sesuai</option>
                                </select>
                              </div>
                            </td>
                          </tr>
                          <tr>
                            <th class="p-1 align-middle">Jenis Kelamin</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.gender || '-' }}
                            </td>
                            <td class="p-1 align-middle">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.identity.gender"
                              >
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
                            <input
                              type="checkbox"
                              class="form-check-input mr-1"
                              v-model="verificationForm.checked_contact"
                            >
                            <span class="align-middle">Sudah dicek</span>
                          </label>
                        </small>
                      </h6>
                      <table class="table table-sm table-borderless mb-2">
                        <tbody>
                          <tr>
                            <th class="p-1 align-middle">No. HP</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.phone_number || '-' }}
                            </td>
                            <td class="p-1 align-middle" style="width: 110px;">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.contact.phone_number"
                              >
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
                            <input
                              type="checkbox"
                              class="form-check-input mr-1"
                              v-model="verificationForm.checked_domicile"
                            >
                            <span class="align-middle">Sudah dicek</span>
                          </label>
                        </small>
                      </h6>
                      <table class="table table-sm table-borderless mb-1">
                        <tbody>
                          <tr>
                            <th class="p-1 align-middle">Alamat</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.address || '-' }}
                            </td>
                            <td class="p-1 align-middle" style="width: 110px;">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.domicile.address"
                              >
                                <option :value="null">Belum dinilai</option>
                                <option :value="true">Sesuai</option>
                                <option :value="false">Tidak sesuai</option>
                              </select>
                            </td>
                          </tr>
                          <!-- Bisa ditambah provinsi/kab/kec/kel kalau tersedia -->
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="row">
                    <!-- PENDIDIKAN -->
                    <div class="col-md-6 mb-2">
                      <h6 class="mb-1 d-flex justify-content-between align-items-center">
                        <span>2.4 Pendidikan</span>
                        <small>
                          <label class="mb-0">
                            <input
                              type="checkbox"
                              class="form-check-input mr-1"
                              v-model="verificationForm.checked_education"
                            >
                            <span class="align-middle">Sudah dicek</span>
                          </label>
                        </small>
                      </h6>
                      <table class="table table-sm table-borderless mb-1">
                        <tbody>
                          <tr>
                            <th class="p-1 align-middle">Pendidikan</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.education || '-' }}
                            </td>
                            <td class="p-1 align-middle" style="width: 110px;">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.education.education"
                              >
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
                            <input
                              type="checkbox"
                              class="form-check-input mr-1"
                              v-model="verificationForm.checked_bank_account"
                            >
                            <span class="align-middle">Sudah dicek</span>
                          </label>
                        </small>
                      </h6>
                      <table class="table table-sm table-borderless mb-2">
                        <tbody>
                          <tr>
                            <th class="p-1 align-middle">No. Rekening</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.bank_account_number || '-' }}
                            </td>
                            <td class="p-1 align-middle" style="width: 110px;">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.bank_account.bank_account_number"
                              >
                                <option :value="null">Belum dinilai</option>
                                <option :value="true">Sesuai</option>
                                <option :value="false">Tidak sesuai</option>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th class="p-1 align-middle">Nama di Rekening</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.bank_account_name || '-' }}
                            </td>
                            <td class="p-1 align-middle">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.bank_account.bank_account_name"
                              >
                                <option :value="null">Belum dinilai</option>
                                <option :value="true">Sesuai</option>
                                <option :value="false">Tidak sesuai</option>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th class="p-1 align-middle">Nama Bank</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.bank_name || '-' }}
                            </td>
                            <td class="p-1 align-middle">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.bank_account.bank_name"
                              >
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
                            <input
                              type="checkbox"
                              class="form-check-input mr-1"
                              v-model="verificationForm.checked_document_dates"
                            >
                            <span class="align-middle">Sudah dicek</span>
                          </label>
                        </small>
                      </h6>
                      <table class="table table-sm table-borderless mb-1">
                        <tbody>
                          <tr>
                            <th class="p-1 align-middle">Tgl Terbit KTP</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.tanggal_terbit_ktp || '-' }}
                            </td>
                            <td class="p-1 align-middle" style="width: 110px;">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.document_dates.tanggal_terbit_ktp"
                              >
                                <option :value="null">Belum dinilai</option>
                                <option :value="true">Sesuai</option>
                                <option :value="false">Tidak sesuai</option>
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <th class="p-1 align-middle">Tgl Terbit KK</th>
                            <td class="p-1 align-middle">
                              {{ selectedParticipant.tanggal_terbit_kk || '-' }}
                            </td>
                            <td class="p-1 align-middle">
                              <select
                                class="form-control form-control-sm"
                                v-model="verificationForm.field_matches.document_dates.tanggal_terbit_kk"
                              >
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

              <!-- CATATAN VERIFIKATOR -->
              <div class="form-group mb-0">
                <label for="verificationNotes"><strong>Catatan Verifikasi (opsional)</strong></label>
                <textarea
                  id="verificationNotes"
                  rows="3"
                  class="form-control"
                  v-model="verificationForm.notes"
                  placeholder="Tuliskan ringkasan hasil verifikasi, misalnya: data dan dokumen sesuai, atau jelaskan item yang tidak sesuai."
                ></textarea>
              </div>
            </div>


            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                @click="closeVerification"
                :disabled="savingVerification"
              >
                Tutup
              </button>
              <button
                type="button"
                class="btn btn-primary"
                @click="submitVerification"
                :disabled="savingVerification"
              >
                <i
                  v-if="savingVerification"
                  class="fas fa-spinner fa-spin mr-1"
                ></i>
                Simpan Verifikasi
              </button>
            </div>
          </div>
        </div>
      </div>

        <!-- Modal Daftar Ulang Peserta -->
        <div class="modal fade" id="reRegisterModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">
                <i class="fas fa-user-check mr-2"></i>
                Proses Daftar Ulang Peserta
                </h5>
                <button type="button" class="close" @click="closeReRegisterModal" data-dismiss="modal">
                <span>&times;</span>
                </button>
            </div>

            <div class="modal-body" v-if="selectedReRegParticipant">
                <!-- Info singkat peserta -->
                <div class="mb-3">
                <h6 class="mb-1">
                    {{ selectedReRegParticipant.full_name || selectedReRegParticipant.nama_lengkap }}
                </h6>
                <p class="mb-0 text-muted text-sm">
                    NIK: {{ selectedReRegParticipant.nik || '-' }}<br>
                    Cabang: {{ selectedReRegParticipant.competition_branch?.name || selectedReRegParticipant.branch_name || '-' }}
                </p>
                <p class="mb-0 text-sm mt-1">
                    <span class="badge badge-success mr-1">
                    Pendaftaran: {{ (selectedReRegParticipant.status_pendaftaran || '').toUpperCase() }}
                    </span>
                    <span class="badge badge-info">
                    Daftar Ulang: {{ (selectedReRegParticipant.status_daftar_ulang || 'belum').toUpperCase() }}
                    </span>
                </p>
                </div>

                <hr>

                <!-- PILIH STATUS DAFTAR ULANG -->
                <div class="form-group">
                <label><strong>Status Daftar Ulang</strong></label>
                <select
                    class="form-control"
                    v-model="reRegForm.status_daftar_ulang"
                >
                    <option value="belum">Belum daftar ulang</option>
                    <option value="proses">Sedang diproses</option>
                    <option value="terverifikasi">Terverifikasi & sah ikut lomba</option>
                    <option value="gagal">Gagal daftar ulang (data tidak sesuai)</option>
                </select>
                <small class="form-text text-muted">
                    - Pilih <code>terverifikasi</code> bila peserta sudah hadir dan semua syarat daftar ulang sesuai.<br>
                    - Pilih <code>gagal</code> bila peserta datang tetapi data/berkas tidak sesuai sehingga tidak dapat mengikuti lomba.
                </small>
                </div>

                <!-- CATATAN DAFTAR ULANG -->
                <div class="form-group mb-0">
                <label for="reRegNotes"><strong>Catatan Daftar Ulang (opsional)</strong></label>
                <textarea
                    id="reRegNotes"
                    rows="3"
                    class="form-control"
                    v-model="reRegForm.daftar_ulang_notes"
                    placeholder="Tuliskan catatan singkat, misalnya: 'Hadir dan berkas asli sesuai', atau 'Nama di KTP berbeda dengan akta, tidak memenuhi syarat'."
                ></textarea>
                </div>
            </div>

            <div class="modal-footer py-2">
                <button
                type="button"
                class="btn btn-sm btn-secondary"
                @click="closeReRegisterModal"
                data-dismiss="modal"
                :disabled="savingReReg"
                >
                Tutup
                </button>
                <button
                type="button"
                class="btn btn-sm btn-primary"
                @click="submitReRegistration"
                :disabled="savingReReg"
                >
                <i v-if="savingReReg" class="fas fa-spinner fa-spin mr-1"></i>
                Simpan Status Daftar Ulang
                </button>
            </div>
            </div>
        </div>
        </div>



    </section>
  </template>

<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import Swal from 'sweetalert2';

const filterDaftarUlang = ref("belum");
const selectedReRegParticipant = ref(null)

const reRegForm = reactive({
  status_daftar_ulang: 'proses',
  daftar_ulang_notes: '',
})

const canReRegister = (p) => {
  if (!p) return false
  // Hanya yang sudah DITERIMA di pendaftaran awal
  if (p.status_pendaftaran !== 'diterima') return false
  // Kalau sudah final (terverifikasi/gagal), tidak perlu daftar ulang lagi
  if (['terverifikasi', 'gagal'].includes(p.status_daftar_ulang)) return false
  return true
}

const openReRegisterModal = (row) => {
  selectedReRegParticipant.value = row

  // Set nilai awal form berdasarkan data yang ada
  reRegForm.status_daftar_ulang = row.status_daftar_ulang && row.status_daftar_ulang !== 'tidak_dibuka'
    ? row.status_daftar_ulang
    : 'proses'

  reRegForm.daftar_ulang_notes = row.daftar_ulang_notes || ''

  // Tampilkan modal bootstrap
  $('#reRegisterModal').modal('show')
}

const closeReRegisterModal = () => {
  $('#reRegisterModal').modal('hide')
  selectedReRegParticipant.value = null
}

const savingReReg = ref(false)

const submitReRegistration = async () => {
  if (!selectedReRegParticipant.value) return

  const confirmResult = await Swal.fire({
    title: 'Konfirmasi',
    text: 'Simpan status daftar ulang untuk peserta ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, simpan',
    cancelButtonText: 'Batal',
  })

  if (!confirmResult.isConfirmed) return

  savingReReg.value = true

  try {
    const url = `/api/v1/event-participants/${selectedReRegParticipant.value.id}/re-registration`

    const payload = {
      status_daftar_ulang: reRegForm.status_daftar_ulang,
      daftar_ulang_notes: reRegForm.daftar_ulang_notes,
    }

    const { data } = await axios.post(url, payload)

    // SUCCESS swal
    await Swal.fire({
      title: 'Berhasil!',
      text: data?.message || 'Status daftar ulang berhasil disimpan.',
      icon: 'success',
      timer: 1800,
      showConfirmButton: false,
    })

    // Update baris di tabel (kalau kamu simpan di array participants)
    if (Array.isArray(participants.value)) {
      const idx = participants.value.findIndex(p => p.id === selectedReRegParticipant.value.id)
      if (idx !== -1) {
        participants.value[idx] = {
          ...participants.value[idx],
          ...data.data, // sesuaikan dengan struktur response
        }
      }
    } else if (typeof loadData === 'function') {
      await loadData()
    }

    closeReRegisterModal()

  } catch (error) {
    console.error(error)

    // ERROR swal
    Swal.fire({
      title: 'Gagal!',
      text: error.response?.data?.message || 'Gagal menyimpan status daftar ulang.',
      icon: 'error',
    })

  } finally {
    savingReReg.value = false
  }
}


const selectedParticipant = ref(null)

// 🔹 computed khusus verifikasi yang ditolak
const rejectedVerifications = computed(() => {
  const p = selectedParticipant.value
  if (!p || !Array.isArray(p.verifications)) {
    return []
  }

  return p.verifications.filter(v => v && v.status === 'rejected')
})


const authUserStore = useAuthUserStore()

const verificationForm = reactive({
  id: null,
  status: 'verified', // 'verified' | 'rejected'

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

  // detail hasil cek per field
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
  status_pendaftaran: 'proses', // 🔹 default, silakan sesuaikan
  notes: '',
})

const resetVerificationForm = () => {
  verificationForm.id = null
  verificationForm.status = 'verified'
  verificationForm.status_pendaftaran = 'proses' // 🔹 reset

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
  })

  verificationForm.notes = ''
}

const openVerification = (participant) => {
  selectedParticipant.value = participant
  resetVerificationForm()
  console.log('selectedParticipant');
  console.log(selectedParticipant);

  // Jika sudah pernah diverifikasi, isi dari data yang ada
  if (participant.verification) {
    const v = participant.verification
    verificationForm.id = v.id
    verificationForm.status = v.status || 'verified'
    // verificationForm.status_pendaftaran = participant.event_participant.status_pendaftaran || 'proses'
    verificationForm.checked_photo = !!v.checked_photo
    verificationForm.checked_id_card = !!v.checked_id_card
    verificationForm.checked_family_card = !!v.checked_family_card
    verificationForm.checked_bank_book = !!v.checked_bank_book
    verificationForm.checked_certificate = !!v.checked_certificate
    verificationForm.checked_other = !!v.checked_other
    verificationForm.notes = v.notes || ''
  }

  $('#showVerificationModal').modal('show')
}


const savingVerification = ref(false)

const submitVerification = async () => {
  // pastikan ada peserta yang dipilih
  if (!selectedParticipant.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Tidak ada peserta yang dipilih.',
      text: 'Silakan pilih peserta terlebih dahulu.',
    })
    return
  }

  // validasi sederhana: status wajib ada
  if (!verificationForm.status) {
    Swal.fire({
      icon: 'warning',
      title: 'Status verifikasi belum dipilih.',
      text: 'Silakan pilih status verifikasi (Terverifikasi / Ditolak).',
    })
    return
  }

  // (opsional) kalau ingin mewajibkan catatan kalau status = rejected
  if (verificationForm.status === 'rejected' && !verificationForm.notes) {
    const confirmWithoutNotes = await Swal.fire({
      icon: 'warning',
      title: 'Catatan verifikasi kosong.',
      text: 'Peserta ditolak tanpa catatan. Lanjutkan tetap menyimpan?',
      showCancelButton: true,
      confirmButtonText: 'Ya, simpan',
      cancelButtonText: 'Batal',
    })

    if (!confirmWithoutNotes.isConfirmed) {
      return
    }
  } else {
    // konfirmasi biasa
    const confirmResult = await Swal.fire({
      icon: 'question',
      title: 'Simpan hasil verifikasi?',
      text: 'Pastikan data dan dokumen sudah dicek sebelum disimpan.',
      showCancelButton: true,
      confirmButtonText: 'Ya, simpan',
      cancelButtonText: 'Batal',
    })

    if (!confirmResult.isConfirmed) {
      return
    }
  }

  savingVerification.value = true

  try {
    const url = `/api/v1/participants/${selectedParticipant.value.id}/verifications`

    const payload = {
      status: verificationForm.status,

      checked_photo: verificationForm.checked_photo,
      checked_id_card: verificationForm.checked_id_card,
      checked_family_card: verificationForm.checked_family_card,
      checked_bank_book: verificationForm.checked_bank_book,
      checked_certificate: verificationForm.checked_certificate,
      checked_other: verificationForm.checked_other,

      checked_identity: verificationForm.checked_identity,
      checked_contact: verificationForm.checked_contact,
      checked_domicile: verificationForm.checked_domicile,
      checked_education: verificationForm.checked_education,
      checked_bank_account: verificationForm.checked_bank_account,
      checked_document_dates: verificationForm.checked_document_dates,

      field_matches: verificationForm.field_matches,
      notes: verificationForm.notes,

      // 🔹 penting: untuk update event_participants
      status_pendaftaran: verificationForm.status_pendaftaran,
      event_id: selectedParticipant.value.event_id ?? null,              // kalau ada
      event_participant_id: selectedParticipant.value.id ?? null, // kalau ada
    }

    await axios.post(url, payload)

    // tutup modal
    $('#showVerificationModal').modal('hide')
    selectedParticipant.value = null

    // notifikasi sukses
    await Swal.fire({
      icon: 'success',
      title: 'Verifikasi peserta berhasil disimpan.',
    })

    // reload list peserta
    if (typeof fetchParticipants === 'function') {
      // jika pakai pagination seperti contoh mutasi
      await fetchParticipants(meta.value?.current_page || 1)
    } else if (typeof loadData === 'function') {
      // fallback kalau fungsi reload bernama lain
      await loadData()
    }
  } catch (error) {
    console.error('Gagal menyimpan verifikasi peserta:', error)

    let msg =
      error.response?.data?.message ||
      'Gagal menyimpan hasil verifikasi peserta.'

    // kalau ada errors dari validasi backend, ambil salah satu
    if (error.response?.data?.errors) {
      const firstErrorKey = Object.keys(error.response.data.errors)[0]
      if (firstErrorKey) {
        msg = error.response.data.errors[firstErrorKey][0] || msg
      }
    }

    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: msg,
    })
  } finally {
    savingVerification.value = false
  }
}



// =========================
// EVENT INFO (dari localStorage / cookie seperti sebelumnya)
// =========================
const eventInfo = ref(null)
const eventId = ref(null)

const formatDate = (val) => {
  if (!val) return '-'
  const str = String(val).substring(0, 10)
  const [year, month, day] = str.split('-')
  const bulanIndo = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
  ]
  return `${day} ${bulanIndo[parseInt(month, 10) - 1]} ${year}`
}

const formatDateTime = (value) => {
  if (!value) return '-'

  const d = new Date(value)
  if (isNaN(d)) return '-'

  const pad = (n) => (n < 10 ? '0' + n : n)

  const day = pad(d.getDate())
  const month = pad(d.getMonth() + 1)
  const year = d.getFullYear()

  const hour = pad(d.getHours())
  const minute = pad(d.getMinutes())
  const second = pad(d.getSeconds())

  return `${day}-${month}-${year} ${hour}:${minute}:${second}`
}


const toDateInput = (val) => {
  if (!val) return ''
  if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val
  return String(val).substring(0, 10)
}

// =========================
// STATUS LIST + COUNTS
// =========================
const statusList = [
  {
    key: 'proses',
    label: 'Proses',
    badgeClass: 'badge-warning',
    icon: 'fas fa-hourglass-half',
  },
  {
    key: 'diterima',
    label: 'Diterima',
    badgeClass: 'badge-success',
    icon: 'fas fa-check-circle',
  },
  {
    key: 'perbaiki',
    label: 'Perbaiki',
    badgeClass: 'badge-info',
    icon: 'fas fa-tools',
  },
  {
    key: 'mundur',
    label: 'Mundur',
    badgeClass: 'badge-secondary',
    icon: 'fas fa-sign-out-alt',
  },
  {
    key: 'tolak',
    label: 'Tolak',
    badgeClass: 'badge-danger',
    icon: 'fas fa-times-circle',
  },
]

const activeStatus = ref('diterima') // default tab
const statusCounts = ref({
  proses: 0,
  diterima: 0,
  perbaiki: 0,
  mundur: 0,
  tolak: 0,
})

// helper badge status di table
const statusBadgeClass = (status) => {
  switch (status) {
    case 'proses':
      return 'badge-warning'
    case 'diterima':
      return 'badge-success'
    case 'perbaiki':
      return 'badge-info'
    case 'mundur':
      return 'badge-secondary'
    case 'tolak':
      return 'badge-danger'
    case 'belum':
      return 'badge-warning'
    case 'terverifikasi':
      return 'badge-success'
    case 'gagal':
      return 'badge-danger'
    default:
      return 'badge-light'
  }
}

const openBiodataPdf = (p) => {
  if (!p || !p.id) return

  // p.id = ID event_participants (sesuai transformEventParticipant)
  const url = `/api/v1/get/participants/${p.id}/biodata-pdf`
  window.open(url, '_blank')
}


// =========================
// LIST & PAGINATION STATE
// =========================
const participants = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})
const perPage = ref(10)
const search = ref('')
const isLoading = ref(false)

// =========================
// HELPER ASAL WILAYAH
// (silakan sesuaikan dengan versi di ParticipantList.vue Anda)
// =========================
const getAsalWilayah = (p) => {
  const te = eventInfo.value?.tingkat_event
  if (!p) return '-'

  if (te === 'provinsi') {
    return p.regency?.name || '-'
  }
  if (te === 'kabupaten_kota') {
    return p.district?.name || '-'
  }
  if (te === 'kecamatan') {
    return p.village?.name || p.district?.name || '-'
  }
  return p.province?.name || '-'
}

// =========================
// FETCH LIST PESERTA (per status)
// =========================
const fetchParticipants = async (page = 1) => {
  if (!eventId.value) {
    participants.value = []
    return
  }

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/participants', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        event_id: eventId.value,
        status_pendaftaran: activeStatus.value, // ⬅ filter status di backend
        status_daftar_ulang: filterDaftarUlang.value,
      },
    })

    participants.value = res.data.data || []
    meta.value = {
      current_page: res.data.current_page,
      per_page: res.data.per_page,
      total: res.data.total,
      from: res.data.from,
      to: res.data.to,
      last_page: res.data.last_page,
    }
  } catch (error) {
    console.error('Gagal memuat peserta:', error)
    if (error.response && error.response.status === 401) {
      authUserStore.logout()
    }
  } finally {
    isLoading.value = false
  }
}

// =========================
// FETCH STATUS COUNTS
// backend disarankan sediakan endpoint:
// GET /api/v1/participants/status-counts?event_id=XX
// return: { proses: 10, diterima: 5, perbaiki: 2, mundur: 1, tolak: 3 }
// =========================
const fetchStatusCounts = async () => {
  if (!eventId.value) return

  try {
    const res = await axios.get('/api/v1/get/participants/status-counts', {
      params: { event_id: eventId.value },
    })

    statusCounts.value = {
      proses: res.data.proses || 0,
      diterima: res.data.diterima || 0,
      perbaiki: res.data.perbaiki || 0,
      mundur: res.data.mundur || 0,
      tolak: res.data.tolak || 0,
    }
  } catch (error) {
    console.error('Gagal memuat rekap status peserta:', error)
  }
}

// =========================
// HANDLER UI
// =========================
const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchParticipants(page)
}

const changeStatus = (key) => {
  if (activeStatus.value === key) return
  activeStatus.value = key
  meta.value.current_page = 1
  fetchParticipants(1)
}


// cek apakah sebuah field lampiran sudah terisi file / url
const hasFile = (field) => {
  return !!(files.value[field] || form.value[field])
}

const hasFileDetail = (field) => {
  if (!selectedParticipant.value) return false
  return !!selectedParticipant.value[field]
}

// stub: sesuaikan dengan ParticipantList.vue Anda
const openViewModal = (p) => {
  selectedParticipant.value = p
  $('#viewParticipantModal').modal('show')
}

const openFileDetail = (field) => {
  if (!selectedParticipant.value) return
  const url = selectedParticipant.value[field]
  if (!url) return
  window.open(url, '_blank')
}

// =========================
// WATCHERS
// =========================
watch(
  () => search.value,
  useDebounceFn(() => fetchParticipants(1), 400)
)

watch(
  () => perPage.value,
  () => {
    fetchParticipants(1)
  }
)

watch([perPage, filterDaftarUlang], () => {
  fetchParticipants(1)
})

watch(
  () => activeStatus.value,
  () => {
    // setiap ganti status, refresh juga rekap
    fetchStatusCounts()
  }
)

// =========================
// MOUNTED
// =========================
onMounted(async () => {
  eventInfo.value = authUserStore.eventData
  eventId.value = authUserStore.eventData.id
  await fetchStatusCounts()
  await fetchParticipants()
})
</script>

<style scoped>
.btn-xs {
  padding: 2px 5px !important;
  font-size: 0.65rem !important;
  line-height: 1 !important;
}

.btn-xs i {
  font-size: 0.55rem !important;
}

.list-group-item.active {
  background-color: #007bff;
  border-color: #007bff;
  color: #fff;
}

.list-group-item.active .badge {
  background-color: rgba(255, 255, 255, 0.3);
}
</style>
