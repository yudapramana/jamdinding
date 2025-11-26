<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Bank Data Peserta</h1>
          <p class="mb-0 text-muted text-sm">
            Event:
            <strong>{{ eventInfo?.nama_event || '-' }}</strong>
            <span v-if="eventInfo?.lokasi_event"> • {{ eventInfo.lokasi_event }}</span>
          </p>
        </div>
        <button
          class="btn btn-primary btn-sm"
          @click="openCreateModal"
          :disabled="!eventId"
        >
          <i class="fas fa-user-plus mr-1"></i>
          Tambah Peserta
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <input
            v-model="search"
            type="text"
            class="form-control form-control-sm w-50"
            placeholder="Cari nama, NIK, atau nomor HP..."
          />
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm">
            <thead class="thead-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Nama Peserta</th>
                <th>NIK</th>
                <th>Cabang_Golongan</th>
                <th>Asal</th>
                <th style="width: 90px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="participants.length === 0">
                <td colspan="7" class="text-center">Tidak ada data peserta.</td>
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
                <!-- <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-warning btn-xs"
                      title="Edit"
                      @click="openEditModal(p)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>

                    <button
                      class="btn btn-outline-danger btn-xs"
                      title="Hapus"
                      @click="deleteParticipant(p)"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td> -->

                <td class="text-center">
                  <div class="btn-group btn-group-sm">

                    <!-- EDIT BIODATA -->
                    <button
                      class="btn btn-outline-warning btn-xs"
                      title="Edit Biodata"
                      @click="openEditModal(p)"
                    >
                      <i class="fas fa-user-edit"></i>
                    </button>

                    <!-- EDIT LAMPIRAN -->
                    <button
                      class="btn btn-outline-info btn-xs"
                      title="Edit Lampiran"
                      @click="openLampiranModal(p)"
                    >
                      <i class="fas fa-file-upload"></i>
                    </button>

                    <!-- LIHAT DATA -->
                    <button
                      class="btn btn-outline-primary btn-xs"
                      title="Lihat Data Peserta"
                      @click="openViewModal(p)"
                    >
                      <i class="fas fa-eye"></i>
                    </button>

                    <!-- MUTASI PESERTA -->
                    <button
                      class="btn btn-outline-success btn-xs"
                      title="Mutasi Peserta"
                      @click="openMutasiModal(p)"
                    >
                      <i class="fas fa-random"></i>
                    </button>

                  </div>
                </td>


              </tr>
            </tbody>
          </table>
        </div>

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
    </div>

    <!-- Modal Tambah / Edit Peserta -->
    <div class="modal fade" id="participantModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title" id="participantModalLabel">
              <i class="fas fa-user-edit mr-2"></i>
              {{ isEdit ? 'Edit Peserta' : 'Tambah Peserta' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body pt-2">
            <!-- TAB NAV -->
            <ul class="nav nav-tabs mb-3">
              <li class="nav-item">
                <a
                  href="#"
                  class="nav-link"
                  :class="{ active: activeTab === 'biodata' }"
                  @click.prevent="activeTab = 'biodata'"
                >
                  Biodata
                </a>
              </li>
              <li class="nav-item">
                <a
                  href="#"
                  class="nav-link"
                  :class="{ active: activeTab === 'lampiran' }"
                  @click.prevent="activeTab = 'lampiran'"
                >
                  Lampiran
                </a>
              </li>
            </ul>

            <form @submit.prevent="submitForm">
              <!-- TAB BIODATA -->
              <div v-if="activeTab === 'biodata'">
                <div class="row">
                  <!-- ===================== IDENTITAS PESERTA ===================== -->
                  <div class="col-12">
                    <h6 class="mb-1 font-weight-bold">Identitas Peserta</h6>
                    <hr class="mt-1 mb-3" />
                  </div>

                  <!-- NIK, NAMA, TELEPON -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">NIK Peserta <span class="text-danger">*</span></label>
                      <div class="input-group input-group-sm">
                        <input
                          v-model="form.nik"
                          @blur="() => { onNikBlur(); validateField('nik') }"
                          type="text"
                          maxlength="16"
                          class="form-control"
                          :class="{
                            'is-invalid': fieldErrors.nik || nikError,
                            'is-valid': !fieldErrors.nik && !nikError && form.nik
                          }"
                          placeholder="Masukkan NIK"
                        />
                        <div class="input-group-append">
                          <span class="input-group-text">
                            <i
                              v-if="isNikChecking"
                              class="fas fa-spinner fa-spin text-muted"
                            ></i>
                            <i v-else class="fas fa-id-card"></i>
                          </span>
                        </div>
                        <div class="invalid-feedback" v-if="fieldErrors.nik || nikError">
                          {{ fieldErrors.nik || nikError }}
                        </div>
                        <div class="valid-feedback" v-else-if="form.nik">
                          NIK dapat digunakan
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">
                        Kategori / Cabang Peserta <span class="text-danger">*</span>
                      </label>
                      <select
                        v-model="form.event_competition_branch_id"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid':
                            fieldErrors.event_competition_branch_id || ageStatus === 'invalid',
                          'is-valid':
                            !fieldErrors.event_competition_branch_id &&
                            form.event_competition_branch_id &&
                            ageStatus === 'valid'
                        }"
                        @blur="() => validateField('event_competition_branch_id')"
                      >
                        <option value="" disabled>-- Pilih Cabang/Golongan --</option>
                        <option
                          v-for="b in branchOptions"
                          :key="b.id"
                          :value="b.id"
                        >
                          {{ b.name }}
                        </option>
                      </select>

                      <!-- error dari validasi umum field -->
                      <div
                        class="invalid-feedback"
                        v-if="fieldErrors.event_competition_branch_id"
                      >
                        {{ fieldErrors.event_competition_branch_id }}
                      </div>

                      <!-- error khusus umur tidak memenuhi -->
                      <div
                        class="invalid-feedback"
                        v-else-if="ageStatus === 'invalid'"
                      >
                        {{ ageMessage }}
                      </div>

                      <!-- pesan umur valid -->
                      <div
                        class="valid-feedback"
                        v-else-if="ageStatus === 'valid'"
                      >
                        {{ ageMessage }}
                      </div>
                    </div>
                  </div>


                  

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Nama Peserta <span class="text-danger">*</span></label>
                      <input
                        v-model="form.full_name"
                        type="text"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.full_name,
                          'is-valid': !fieldErrors.full_name && form.full_name
                        }"
                        @blur="() => validateField('full_name')"
                      />
                      <div class="invalid-feedback" v-if="fieldErrors.full_name">
                        {{ fieldErrors.full_name }}
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Telepon <span class="text-danger">*</span></label>
                      <input
                        v-model="form.phone_number"
                        type="text"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.phone_number,
                          'is-valid': !fieldErrors.phone_number && form.phone_number
                        }"
                        @input="form.phone_number = form.phone_number.replace(/[^0-9]/g, '')"
                        @blur="() => validateField('phone_number')"
                        maxlength="13"
                      />
                      <div class="invalid-feedback" v-if="fieldErrors.phone_number">
                        {{ fieldErrors.phone_number }}
                      </div>
                    </div>
                  </div>


                  <!-- TEMPAT LAHIR, TANGGAL LAHIR, JENIS KELAMIN -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Tempat Lahir <span class="text-danger">*</span></label>
                      <input
                        v-model="form.place_of_birth"
                        type="text"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.place_of_birth,
                          'is-valid': !fieldErrors.place_of_birth && form.place_of_birth
                        }"
                        @blur="() => validateField('place_of_birth')"
                      />
                      <div class="invalid-feedback" v-if="fieldErrors.place_of_birth">
                        {{ fieldErrors.place_of_birth }}
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Tanggal Lahir <span class="text-danger">*</span></label>
                      <div class="input-group input-group-sm">
                        <input
                          v-model="form.date_of_birth"
                          type="date"
                          class="form-control"
                          :class="{
                            'is-invalid': fieldErrors.date_of_birth,
                            'is-valid': !fieldErrors.date_of_birth && form.date_of_birth
                          }"
                          readonly
                          @blur="() => validateField('date_of_birth')"
                        />
                        <div class="input-group-append">
                          <span class="input-group-text">
                            <i class="far fa-calendar-alt"></i>
                          </span>
                        </div>
                        <div class="invalid-feedback" v-if="fieldErrors.date_of_birth">
                          {{ fieldErrors.date_of_birth }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Jenis Kelamin <span class="text-danger">*</span></label>
                      <select
                        v-model="form.gender"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.gender,
                          'is-valid': !fieldErrors.gender && form.gender
                        }"
                        readonly
                        @blur="() => validateField('gender')"
                      >
                        <option value="MALE">LAKI-LAKI</option>
                        <option value="FEMALE">PEREMPUAN</option>
                      </select>
                      <div class="invalid-feedback" v-if="fieldErrors.gender">
                        {{ fieldErrors.gender }}
                      </div>
                    </div>
                  </div>

                  <!-- PENDIDIKAN, NO REK, BANK -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Pendidikan <span class="text-danger">*</span></label>
                      <select
                        v-model="form.education"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.education,
                          'is-valid': !fieldErrors.education && form.education
                        }"
                        @blur="() => validateField('education')"
                      >
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="D1">DIPLOMA I</option>
                        <option value="D2">DIPLOMA II</option>
                        <option value="D3">DIPLOMA III</option>
                        <option value="D4">DIPLOMA IV</option>
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                      </select>
                      <div class="invalid-feedback" v-if="fieldErrors.education">
                        {{ fieldErrors.education }}
                      </div>
                    </div>
                  </div>

                  <!-- ===================== ALAMAT DOMISILI ===================== -->
                  <div class="col-12 mt-3">
                    <h6 class="mb-1 font-weight-bold">Alamat Domisili</h6>
                    <hr class="mt-1 mb-3" />
                  </div>

                  <!-- PROVINSI, KAB/KOTA, KECAMATAN -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Provinsi (Sesuai KTP) <span class="text-danger">*</span></label>
                      <select
                        v-model="form.province_id"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.province_id,
                          'is-valid': !fieldErrors.province_id && form.province_id
                        }"
                        :disabled="
                          !provinceOptions.length ||
                          tingkatEvent === 'provinsi' ||
                          tingkatEvent === 'kabupaten_kota' ||
                          tingkatEvent === 'kecamatan'
                        "
                        @blur="() => validateField('province_id')"
                      >
                        <option value="" disabled>-- Pilih Provinsi --</option>
                        <option
                          v-for="p in provinceOptions"
                          :key="p.id"
                          :value="p.id"
                        >
                          {{ p.name }}
                        </option>
                      </select>
                      <div class="invalid-feedback" v-if="fieldErrors.province_id">
                        {{ fieldErrors.province_id }}
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Kab / Kota (Sesuai KTP) <span class="text-danger">*</span></label>
                      <select
                        v-model="form.regency_id"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.regency_id,
                          'is-valid': !fieldErrors.regency_id && form.regency_id
                        }"
                        :disabled="
                          !form.province_id ||
                          isLoadingRegencies ||
                          tingkatEvent === 'kabupaten_kota' ||
                          tingkatEvent === 'kecamatan'
                        "
                        @blur="() => validateField('regency_id')"
                      >
                        <option value="" disabled>
                          {{ isLoadingRegencies ? 'Memuat Kabupaten/Kota...' : '-- Pilih Kabupaten/Kota --' }}
                        </option>
                        <option
                          v-for="r in regencyOptions"
                          :key="r.id"
                          :value="r.id"
                        >
                          {{ r.name }}
                        </option>
                      </select>
                      <small v-if="isLoadingRegencies" class="text-muted">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Sedang memuat kabupaten/kota...
                      </small>
                      <div class="invalid-feedback" v-if="fieldErrors.regency_id">
                        {{ fieldErrors.regency_id }}
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Kecamatan (Sesuai KTP) <span class="text-danger">*</span></label>
                      <select
                        v-model="form.district_id"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.district_id,
                          'is-valid': !fieldErrors.district_id && form.district_id
                        }"
                        :disabled="
                          !form.regency_id ||
                          isLoadingDistricts ||
                          tingkatEvent === 'kecamatan'
                        "
                        @blur="() => validateField('district_id')"
                      >
                        <option value="" disabled>
                          {{ isLoadingDistricts ? 'Memuat Kecamatan...' : '-- Pilih Kecamatan --' }}
                        </option>
                        <option
                          v-for="d in districtOptions"
                          :key="d.id"
                          :value="d.id"
                        >
                          {{ d.name }}
                        </option>
                      </select>
                      <small v-if="isLoadingDistricts" class="text-muted">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Sedang memuat kecamatan...
                      </small>
                      <div class="invalid-feedback" v-if="fieldErrors.district_id">
                        {{ fieldErrors.district_id }}
                      </div>
                    </div>
                  </div>

                  <!-- DESA & ALAMAT -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Kelurahan / Desa</label>
                      <select
                        v-model="form.village_id"
                        class="form-control form-control-sm"
                        :disabled="!form.district_id || isLoadingVillages"
                      >
                        <option :value="null">
                          {{ isLoadingVillages ? 'Memuat Kelurahan/Desa...' : '-- (Opsional) Pilih Kel/Desa --' }}
                        </option>
                        <option
                          v-for="v in villageOptions"
                          :key="v.id"
                          :value="v.id"
                        >
                          {{ v.name }}
                        </option>
                      </select>
                      <small v-if="isLoadingVillages" class="text-muted">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Sedang memuat kelurahan/desa...
                      </small>
                    </div>
                  </div>

                  <div class="col-md-8">
                    <div class="form-group">
                      <label class="mb-1">Alamat Lengkap Peserta <span class="text-danger">*</span></label>
                      <textarea
                        v-model="form.address"
                        rows="2"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.address,
                          'is-valid': !fieldErrors.address && form.address
                        }"
                        @blur="() => validateField('address')"
                      ></textarea>
                      <div class="invalid-feedback" v-if="fieldErrors.address">
                        {{ fieldErrors.address }}
                      </div>
                    </div>
                  </div>

                  <!-- ===================== INFORMASI REKENING ===================== -->
                  <div class="col-12 mt-3">
                    <h6 class="mb-1 font-weight-bold">Informasi Rekening & Kategori</h6>
                    <hr class="mt-1 mb-3" />
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Nomor Rekening <span class="text-danger">*</span></label>
                      <input
                        v-model="form.bank_account_number"
                        type="text"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.bank_account_number,
                          'is-valid': !fieldErrors.bank_account_number && form.bank_account_number
                        }"
                        @blur="() => validateField('bank_account_number')"
                      />
                      <div class="invalid-feedback" v-if="fieldErrors.bank_account_number">
                        {{ fieldErrors.bank_account_number }}
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Bank <span class="text-danger">*</span></label>
                      <select
                        v-model="form.bank_name"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.bank_name,
                          'is-valid': !fieldErrors.bank_name && form.bank_name
                        }"
                        @blur="() => validateField('bank_name')"
                      >
                        <option value="" disabled>-- Pilih Bank --</option>
                        <option
                          v-for="bank in bankOptions"
                          :key="bank"
                          :value="bank"
                        >
                          {{ bank }}
                        </option>
                      </select>

                      <div class="invalid-feedback" v-if="fieldErrors.bank_name">
                        {{ fieldErrors.bank_name }}
                      </div>
                    </div>
                  </div>


                  <!-- ATAS NAMA, KATEGORI/CABANG -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Atas Nama Rekening <span class="text-danger">*</span></label>
                      <input
                        v-model="form.bank_account_name"
                        type="text"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.bank_account_name,
                          'is-valid': !fieldErrors.bank_account_name && form.bank_account_name
                        }"
                        @blur="() => validateField('bank_account_name')"
                      />
                      <div class="invalid-feedback" v-if="fieldErrors.bank_account_name">
                        {{ fieldErrors.bank_account_name }}
                      </div>
                    </div>
                  </div>

                  <!-- TANGGAL TERBIT DOKUMEN -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Tanggal Terbit KTP</label>
                      <input
                        v-model="form.tanggal_terbit_ktp"
                        type="date"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.tanggal_terbit_ktp,
                          'is-valid': !fieldErrors.tanggal_terbit_ktp && form.tanggal_terbit_ktp
                        }"
                        @blur="() => validateField('tanggal_terbit_ktp')"
                      />
                      <div class="invalid-feedback" v-if="fieldErrors.tanggal_terbit_ktp">
                        {{ fieldErrors.tanggal_terbit_ktp }}
                      </div>

                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="mb-1">Tanggal Terbit KK</label>
                      <input
                        v-model="form.tanggal_terbit_kk"
                        type="date"
                        class="form-control form-control-sm"
                        :class="{
                          'is-invalid': fieldErrors.tanggal_terbit_kk,
                          'is-valid': !fieldErrors.tanggal_terbit_kk && form.tanggal_terbit_kk
                        }"
                        @blur="() => validateField('tanggal_terbit_kk')"
                      />
                      <div class="invalid-feedback" v-if="fieldErrors.tanggal_terbit_kk">
                        {{ fieldErrors.tanggal_terbit_kk }}
                      </div>

                    </div>
                  </div>
                </div>
              </div>


              <!-- TAB LAMPIRAN -->
              <div v-else>
                <div class="row">
                  <!-- KOLOM FOTO -->
                  <div class="col-md-4">
                    <div class="card card-outline card-primary lampiran-photo-card">
                      <div class="card-body d-flex flex-column align-items-center">

                        <!-- FRAME FOTO -->
                        <div class="lampiran-photo-frame mb-2">
                          <img
                            v-if="form.photo_url"
                            :src="form.photo_url"
                            alt="Foto Peserta"
                            class="img-fluid"
                          />
                          <span v-else class="text-muted text-sm">
                            Belum ada foto
                          </span>
                        </div>

                        <!-- INPUT FOTO -->
                        <div class="custom-file mt-2 lampiran-photo-input">
                          <input
                            type="file"
                            class="custom-file-input"
                            id="photoInput"
                            accept="image/jpeg,image/png,image/jpg"
                            @change="onFileChange($event, 'photo_url')"
                          />
                          <label class="custom-file-label" for="photoInput">
                            Pilih foto...
                          </label>
                        </div>

                        <!-- INFO / ERROR FOTO -->
                        <small
                          v-if="fileErrors.photo_url"
                          class="text-danger d-block mt-2 text-center"
                        >
                          {{ fileErrors.photo_url }}
                        </small>
                        <small
                          v-else-if="form.photo_url"
                          class="text-muted d-block mt-2 text-center"
                        >
                          File saat ini:
                          <a :href="form.photo_url" target="_blank" rel="noopener">
                            Lihat
                          </a>
                        </small>

                        <small class="text-muted d-block mt-2 text-center text-xs">
                          Format <strong>JPG/JPEG/PNG</strong>, maksimal
                          <strong>1 MB</strong>.
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- KOLOM DOKUMEN PDF -->
                  <div class="col-md-8">
                    <!-- KTP -->
                    <div class="form-group row align-items-center lampiran-row">
                      <label class="col-sm-3 col-form-label col-form-label-sm mb-0">
                        KTP<br></br>
                        <small class="text-muted" style="line-height: 0px !important">(Jika umur dibawah 17 Tahun tidak perlu input)</small>
                        
                      </label>
                      <div class="col-sm-7">
                        <div class="custom-file">
                          <input
                            type="file"
                            class="custom-file-input"
                            id="ktpInput"
                            accept="application/pdf"
                            @change="onFileChange($event, 'id_card_url')"
                          />
                          <label class="custom-file-label" for="ktpInput">
                            Pilih file...
                          </label>
                        </div>
                        <small
                          v-if="fileErrors.id_card_url"
                          class="text-danger d-block mt-1"
                        >
                          {{ fileErrors.id_card_url }}
                        </small>
                        <!-- <small
                          v-else-if="form.id_card_url"
                          class="text-muted d-block mt-1"
                        >
                          File saat ini:
                          <a :href="form.id_card_url" target="_blank" rel="noopener">
                            Lihat
                          </a>
                        </small> -->
                        <small class="text-muted d-block mt-1 text-xs">
                          Format <strong>PNG, JPG, JPEG</strong>, maksimal
                          <strong>1 MB</strong>.
                        </small>
                      </div>
                      <div class="col-sm-2 text-right">
                        <span
                          v-if="hasFile('id_card_url')"
                          class="badge badge-pill badge-success badge-file"
                          @click="openFile('id_card_url')"
                          style="cursor: pointer;"
                          title="Klik untuk melihat file"
                        >
                          <i class="fas fa-check"></i>
                        </span>
                        <span
                          v-else
                          class="badge badge-pill badge-secondary"
                        >
                          <i class="fas fa-minus"></i>
                        </span>
                      </div>

                    </div>

                    <!-- KK -->
                    <div class="form-group row align-items-center lampiran-row">
                      <label class="col-sm-3 col-form-label col-form-label-sm mb-0">
                        Kartu Keluarga
                      </label>
                      <div class="col-sm-7">
                        <div class="custom-file">
                          <input
                            type="file"
                            class="custom-file-input"
                            id="kkInput"
                            accept="application/pdf"
                            @change="onFileChange($event, 'family_card_url')"
                          />
                          <label class="custom-file-label" for="kkInput">
                            Pilih file...
                          </label>
                        </div>
                        <small
                          v-if="fileErrors.family_card_url"
                          class="text-danger d-block mt-1"
                        >
                          {{ fileErrors.family_card_url }}
                        </small>
                        <small class="text-muted d-block mt-1 text-xs">
                          Format <strong>PDF</strong>, maksimal
                          <strong>1 MB</strong>.
                        </small>
                      </div>
                      <div class="col-sm-2 text-right">
                        <span
                          v-if="hasFile('family_card_url')"
                          class="badge badge-pill badge-success badge-file"
                          @click="openFile('family_card_url')"
                          style="cursor: pointer;"
                          title="Klik untuk melihat file"
                        >
                          <i class="fas fa-check"></i>
                        </span>
                        <span
                          v-else
                          class="badge badge-pill badge-secondary"
                        >
                          <i class="fas fa-minus"></i>
                        </span>
                      </div>
                    </div>

                    <!-- BUKU TABUNGAN -->
                    <div class="form-group row align-items-center lampiran-row">
                      <label class="col-sm-3 col-form-label col-form-label-sm mb-0">
                        Buku Tabungan
                      </label>
                      <div class="col-sm-7">
                        <div class="custom-file">
                          <input
                            type="file"
                            class="custom-file-input"
                            id="tabunganInput"
                            accept="application/pdf"
                            @change="onFileChange($event, 'bank_book_url')"
                          />
                          <label class="custom-file-label" for="tabunganInput">
                            Pilih file...
                          </label>
                        </div>
                        <small
                          v-if="fileErrors.bank_book_url"
                          class="text-danger d-block mt-1"
                        >
                          {{ fileErrors.bank_book_url }}
                        </small>
                        <!-- <small
                          v-else-if="form.bank_book_url"
                          class="text-muted d-block mt-1"
                        >
                          File saat ini:
                          <a :href="form.bank_book_url" target="_blank" rel="noopener">
                            Lihat
                          </a>
                        </small> -->
                        <small class="text-muted d-block mt-1 text-xs">
                          Format <strong>PDF</strong>, maksimal
                          <strong>1 MB</strong>.
                        </small>
                      </div>
                      <div class="col-sm-2 text-right">
                        <span
                          v-if="hasFile('bank_book_url')"
                          class="badge badge-pill badge-success badge-file"
                          @click="openFile('bank_book_url')"
                          style="cursor: pointer;"
                          title="Klik untuk melihat file"
                        >
                          <i class="fas fa-check"></i>
                        </span>
                        <span
                          v-else
                          class="badge badge-pill badge-secondary"
                        >
                          <i class="fas fa-minus"></i>
                        </span>
                      </div>
                    </div>

                    <!-- PIAGAM -->
                    <div class="form-group row align-items-center lampiran-row">
                      <label class="col-sm-3 col-form-label col-form-label-sm mb-0">
                        Piagam Penghargaan
                      </label>
                      <div class="col-sm-7">
                        <div class="custom-file">
                          <input
                            type="file"
                            class="custom-file-input"
                            id="sertifikatInput"
                            accept="application/pdf"
                            @change="onFileChange($event, 'certificate_url')"
                          />
                          <label class="custom-file-label" for="sertifikatInput">
                            Pilih file...
                          </label>
                        </div>
                        <small
                          v-if="fileErrors.certificate_url"
                          class="text-danger d-block mt-1"
                        >
                          {{ fileErrors.certificate_url }}
                        </small>
                        <!-- <small
                          v-else-if="form.certificate_url"
                          class="text-muted d-block mt-1"
                        >
                          File saat ini:
                          <a :href="form.certificate_url" target="_blank" rel="noopener">
                            Lihat
                          </a>
                        </small> -->
                        <small class="text-muted d-block mt-1 text-xs">
                          Format <strong>PDF</strong>, maksimal
                          <strong>1 MB</strong>.
                        </small>
                      </div>
                      <div class="col-sm-2 text-right">
                        <span
                          v-if="hasFile('certificate_url')"
                          class="badge badge-pill badge-success badge-file"
                          @click="openFile('certificate_url')"
                          style="cursor: pointer;"
                          title="Klik untuk melihat file"
                        >
                          <i class="fas fa-check"></i>
                        </span>
                        <span
                          v-else
                          class="badge badge-pill badge-secondary"
                        >
                          <i class="fas fa-minus"></i>
                        </span>
                      </div>
                    </div>

                    <!-- LAINNYA -->
                    <div class="form-group row align-items-center lampiran-row">
                      <label class="col-sm-3 col-form-label col-form-label-sm mb-0">
                        Lainnya <span class="text-muted">(Opsional)</span>
                      </label>
                      <div class="col-sm-7">
                        <div class="custom-file">
                          <input
                            type="file"
                            class="custom-file-input"
                            id="otherInput"
                            accept="application/pdf"
                            @change="onFileChange($event, 'other_url')"
                          />
                          <label class="custom-file-label" for="otherInput">
                            Pilih file...
                          </label>
                        </div>
                        <small
                          v-if="fileErrors.other_url"
                          class="text-danger d-block mt-1"
                        >
                          {{ fileErrors.other_url }}
                        </small>
                        <!-- <small
                          v-else-if="form.other_url"
                          class="text-muted d-block mt-1"
                        >
                          File saat ini:
                          <a :href="form.other_url" target="_blank" rel="noopener">
                            Lihat
                          </a>
                        </small> -->
                        <small class="text-muted d-block mt-1 text-xs">
                          Format <strong>PDF</strong>, maksimal
                          <strong>1 MB</strong>.
                        </small>
                      </div>
                      <div class="col-sm-2 text-right">
                        <span
                          v-if="hasFile('other_url')"
                          class="badge badge-pill badge-success badge-file"
                          @click="openFile('other_url')"
                          style="cursor: pointer;"
                          title="Klik untuk melihat file"
                        >
                          <i class="fas fa-check"></i>
                        </span>
                        <span
                          v-else
                          class="badge badge-pill badge-secondary"
                        >
                          <i class="fas fa-minus"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>


              <div class="text-end mt-3">
                <button
                  type="submit"
                  class="btn btn-sm btn-primary"
                  :disabled="isSubmitting"
                >
                  <i
                    v-if="isSubmitting"
                    class="fas fa-spinner fa-spin mr-1"
                  ></i>
                  <i v-else class="fas fa-save mr-1"></i>
                  Simpan
                </button>
              </div>
            </form>
          </div>
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

    <!-- MODAL MUTASI PESERTA -->
    <div class="modal fade" id="mutasiModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <!-- HEADER -->
          <div class="modal-header">
            <h5 class="modal-title">Mutasi Peserta</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <!-- BODY -->
          <div class="modal-body">
            <div class="form-group">
              <label>Provinsi</label>
              <select
                v-model="mutasiForm.province_id"
                class="form-control form-control-sm"
                :class="{ 'is-invalid': mutasiErrors.province_id }"
                @change="onMutasiProvinceChange"
              >
                <option value="">Pilih Provinsi</option>
                <option
                  v-for="p in mutasiProvinceOptions"
                  :key="p.id"
                  :value="p.id"
                >
                  {{ p.name }}
                </option>
              </select>
              <div class="invalid-feedback" v-if="mutasiErrors.province_id">
                {{ mutasiErrors.province_id }}
              </div>
            </div>

            <div class="form-group">
              <label>Kab / Kota</label>
              <select
                v-model="mutasiForm.regency_id"
                class="form-control form-control-sm"
                :class="{ 'is-invalid': mutasiErrors.regency_id }"
                @change="onMutasiRegencyChange"
              >
                <option value="">Pilih Kab / Kota</option>
                <option
                  v-for="r in mutasiRegencyOptions"
                  :key="r.id"
                  :value="r.id"
                >
                  {{ r.name }}
                </option>
              </select>
              <div class="invalid-feedback" v-if="mutasiErrors.regency_id">
                {{ mutasiErrors.regency_id }}
              </div>
            </div>

            <div class="form-group mb-1">
              <label>Kecamatan</label>
              <select
                v-model="mutasiForm.district_id"
                class="form-control form-control-sm"
                :class="{ 'is-invalid': mutasiErrors.district_id }"
              >
                <option value="">Pilih Kecamatan</option>
                <option
                  v-for="d in mutasiDistrictOptions"
                  :key="d.id"
                  :value="d.id"
                >
                  {{ d.name }}
                </option>
              </select>
              <div class="invalid-feedback" v-if="mutasiErrors.district_id">
                {{ mutasiErrors.district_id }}
              </div>
            </div>

            <small class="text-danger d-block mt-2">
              <i class="fas fa-exclamation-circle mr-1"></i>
              Disarankan input kecamatan agar data lebih spesifik, sesuaikan data
              dengan KTP atau KK.
            </small>
          </div>

          <!-- FOOTER -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-sm btn-secondary"
              data-dismiss="modal"
            >
              Tidak
            </button>
            <button
              type="button"
              class="btn btn-sm btn-primary"
              :disabled="isMutasiSubmitting"
              @click="submitMutasi"
            >
              <i
                v-if="isMutasiSubmitting"
                class="fas fa-spinner fa-spin mr-1"
              ></i>
              Pindahkan
            </button>
          </div>
        </div>
      </div>
    </div>


  </section>
</template>


<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import Swal from 'sweetalert2';

const selectedParticipant = ref(null)
const authUserStore = useAuthUserStore()

// user login sekarang
const currentUser = computed(() => authUserStore.user || {})

// SUPERADMIN & ADMIN_EVENT: boleh pilih wilayah bebas
const isPrivileged = computed(() => {
  const roleName = currentUser.value?.role?.name || ''
  return roleName === 'SUPERADMIN' || roleName === 'ADMIN_EVENT'
})

// cek apakah sebuah field lampiran sudah terisi file / url
const hasFile = (field) => {
  return !!(files.value[field] || form.value[field])
}

const hasFileDetail = (field) => {
  if (!selectedParticipant.value) return false
  return !!selectedParticipant.value[field]
}

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


// TAB aktif (biodata / lampiran)
const activeTab = ref('biodata')

// LIST
const participants = ref([])
const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})
const search = ref('')
const isLoading = ref(false)

// EVENT
const eventInfo = ref(null)
const eventId = ref(null)
const tingkatEvent = computed(() => eventInfo.value?.tingkat_event || '')

// FORM / MODAL
const isEdit = ref(false)
const isSubmitting = ref(false)
const form = ref({
  id: null,
  event_competition_branch_id: '',
  nik: '',
  full_name: '',
  phone_number: '',
  place_of_birth: '',
  date_of_birth: '',
  gender: 'MALE',
  province_id: '',
  regency_id: '',
  district_id: '',
  village_id: null,
  address: '',
  education: 'SMA',
  bank_account_number: '',
  bank_account_name: '',
  bank_name: '',
  photo_url: '',
  id_card_url: '',
  family_card_url: '',
  bank_book_url: '',
  certificate_url: '',
  other_url: '',
  tanggal_terbit_ktp: '',
  tanggal_terbit_kk: '',
})

const fieldErrors = ref({
  nik: '',
  full_name: '',
  phone_number: '',
  place_of_birth: '',
  date_of_birth: '',
  gender: '',
  event_competition_branch_id: '',
  province_id: '',
  regency_id: '',
  district_id: '',
  address: '',
  education: '',
  bank_account_number: '',
  bank_account_name: '',
  bank_name: '',
  tanggal_terbit_ktp: '',
  tanggal_terbit_kk: '',
})

const bankOptions = [
  // BANK BUMN
  'BRI',
  'BNI',
  'MANDIRI',
  'BTN',

  // BANK SYARIAH
  'BSI',
  'BRI SYARIAH',
  'BNI SYARIAH',
  'MANDIRI SYARIAH',

  // BANK SWASTA NASIONAL
  'BCA',
  'CIMB NIAGA',
  'PERMATA',
  'PANIN',
  'OCBC NISP',
  'DANAMON',
  'MEGA',
  'SINARMAS',
  'BUKOPIN',
  'MAYBANK',
  'BTPN',
  'J TRUST BANK',

  // BANK PEMBANGUNAN DAERAH
  'BANK DKI',
  'BANK BJB',
  'BANK BJB SYARIAH',
  'BANK JATENG',
  'BANK JATIM',
  'BANK SUMUT',
  'BANK NAGARI',
  'BANK RIAU KEPRI',
  'BANK SUMSEL BABEL',
  'BANK LAMPUNG',
  'BANK KALSEL',
  'BANK KALBAR',
  'BANK KALTIMTARA',
  'BANK SULSEL BAR',
  'BANK SULTRA',
  'BANK SULUTGO',
  'BANK NTB SYARIAH',
  'BANK NTT',
  'BANK PAPUA',
  'BANK MALUKU MALUT',
]


// Validasi umur vs max_age cabang
const ageStatus = ref(null) // 'valid' | 'invalid' | null
const ageMessage = ref('')

const validateAgeForBranch = () => {
  ageStatus.value = null
  ageMessage.value = ''

  const dobStr = form.value.date_of_birth
  const branchId = form.value.event_competition_branch_id

  if (!dobStr || !branchId) return

  const branch = branchOptions.value.find(b => b.id === branchId)
  if (!branch || !branch.max_age) return

  const dob = new Date(dobStr)
  if (isNaN(dob.getTime())) return

  // pakai tanggal batas umur event kalau ada, fallback ke hari ini
  const refStr = eventInfo.value?.tanggal_batas_umur || null
  const refDate = refStr ? new Date(refStr) : new Date()
  if (isNaN(refDate.getTime())) return

  let age = refDate.getFullYear() - dob.getFullYear()
  const mDiff = refDate.getMonth() - dob.getMonth()
  if (mDiff < 0 || (mDiff === 0 && refDate.getDate() < dob.getDate())) {
    age--
  }

  if (age <= branch.max_age) {
    ageStatus.value = 'valid'
    ageMessage.value = `Umur memenuhi untuk mendaftar di cabang ini (${branch.max_age}T). Umur Peserta ${age}T`
  } else {
    ageStatus.value = 'invalid'
    ageMessage.value = `Umur tidak memenuhi untuk mendaftar di cabang ini(${branch.max_age}T). Umur Peserta ${age}T`
  }
}



// Lampiran: file & error per field
const files = ref({
  photo_url: null,
  id_card_url: null,
  family_card_url: null,
  bank_book_url: null,
  certificate_url: null,
  other_url: null,
})

const fileErrors = ref({
  photo_url: '',
  id_card_url: '',
  family_card_url: '',
  bank_book_url: '',
  certificate_url: '',
  other_url: '',
})

// Helper asal wilayah
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

// OPTIONS
const branchOptions = ref([])
const provinceOptions = ref([])
const regencyOptions = ref([])
const districtOptions = ref([])
const villageOptions = ref([])

const isLoadingRegencies = ref(false)
const isLoadingDistricts = ref(false)
const isLoadingVillages = ref(false)

// FLAGS
const nikError = ref('')
const isInitLocation = ref(false)
const isNikChecking = ref(false)


// =======================================
// HELPER EVENT
// =======================================
const getEventInfoFromStorage = () => {
  let raw = ''

  try {
    raw = localStorage.getItem('event_data') || ''
  } catch (e) {}

  if (!raw) {
    const cookie = document.cookie
      .split('; ')
      .find(row => row.startsWith('event_data='))
    if (cookie) {
      raw = decodeURIComponent(cookie.split('=')[1])
    }
  }

  if (raw) {
    try {
      eventInfo.value = JSON.parse(raw)
      eventId.value = eventInfo.value.id || null
    } catch (e) {
      console.error('Gagal parse event_data:', e)
      eventInfo.value = null
    }
  }
}

const openFile = (field) => {
  let fileObj = files.value[field]
  let url = ''

  if (fileObj) {
    // file baru diupload → buat blob URL
    url = URL.createObjectURL(fileObj)
  } else if (form.value[field]) {
    // file lama dari server
    url = form.value[field]
  }

  if (url) {
    window.open(url, '_blank')
  }
}

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

// =======================================
// FETCH LIST PESERTA
// =======================================
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
        search: search.value,
        event_id: eventId.value,
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

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchParticipants(page)
}

// =======================================
// WILAYAH: FETCH MASTER
// =======================================

const fetchProvinceOptions = async () => {
  // role non-privileged: pakai wilayah user
  if (!isPrivileged.value) {
    provinceOptions.value = []
    const u = currentUser.value

    if (u.province) {
      provinceOptions.value = [u.province]
      if (!form.value.province_id) {
        form.value.province_id = u.province.id
      }
    } else if (u.province_id) {
      provinceOptions.value = [
        { id: u.province_id, name: 'Provinsi Akun' },
      ]
      if (!form.value.province_id) {
        form.value.province_id = u.province_id
      }
    }
    return
  }

  // privileged: load semua
  try {
    const res = await axios.get('/api/v1/get/provinces')
    provinceOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal load provinsi:', e)
  }
}

const fetchRegencyOptions = async (preserveSelection = false) => {
  if (!form.value.province_id) {
    regencyOptions.value = []
    districtOptions.value = []
    villageOptions.value = []
    if (!preserveSelection) {
      form.value.regency_id = ''
      form.value.district_id = ''
      form.value.village_id = null
    }
    return
  }

  if (!isPrivileged.value) {
    regencyOptions.value = []
    const u = currentUser.value

    if (u.regency) {
      regencyOptions.value = [u.regency]
      form.value.regency_id = u.regency.id
    } else if (u.regency_id) {
      regencyOptions.value = [
        { id: u.regency_id, name: 'Kab/Kota Akun' },
      ]
      form.value.regency_id = u.regency_id
    }

    return
  }

  isLoadingRegencies.value = true

  if (!preserveSelection) {
    regencyOptions.value = []
    form.value.regency_id = ''
    districtOptions.value = []
    form.value.district_id = ''
    villageOptions.value = []
    form.value.village_id = null
  }

  try {
    const res = await axios.get('/api/v1/get/regencies', {
      params: { province_id: form.value.province_id },
    })
    regencyOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal load kab/kota:', e)
  } finally {
    isLoadingRegencies.value = false
  }
}

const fetchDistrictOptions = async (preserveSelection = false) => {
  if (!form.value.regency_id) {
    districtOptions.value = []
    villageOptions.value = []
    if (!preserveSelection) {
      form.value.district_id = ''
      form.value.village_id = null
    }
    return
  }

  if (!isPrivileged.value) {
    districtOptions.value = []
    const u = currentUser.value

    if (u.district) {
      districtOptions.value = [u.district]
      form.value.district_id = u.district.id
    } else if (u.district_id) {
      districtOptions.value = [
        { id: u.district_id, name: 'Kecamatan Akun' },
      ]
      form.value.district_id = u.district_id
    }

    return
  }

  isLoadingDistricts.value = true

  if (!preserveSelection) {
    districtOptions.value = []
    form.value.district_id = ''
    villageOptions.value = []
    form.value.village_id = null
  }

  try {
    const res = await axios.get('/api/v1/get/districts', {
      params: { regency_id: form.value.regency_id },
    })
    districtOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal load kecamatan:', e)
  } finally {
    isLoadingDistricts.value = false
  }
}

const fetchVillageOptions = async (preserveSelection = false) => {
  if (!form.value.district_id) {
    villageOptions.value = []
    if (!preserveSelection) {
      form.value.village_id = null
    }
    return
  }

  if (preserveSelection && villageOptions.value.length) return

  isLoadingVillages.value = true
  if (!preserveSelection) {
    villageOptions.value = []
    form.value.village_id = null
  }

  try {
    const res = await axios.get('/api/v1/get/villages', {
      params: { district_id: form.value.district_id },
    })
    villageOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal load kel/desa:', e)
  } finally {
    isLoadingVillages.value = false
  }
}

const fetchBranchOptions = async () => {
  if (!eventId.value) return
  if (branchOptions.value.length > 0) return

  try {
    const res = await axios.get('/api/v1/get/event-competition-branches', {
      params: { event_id: eventId.value },
    })
    branchOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal memuat cabang/golongan event:', e)
  }
}

// =======================================
// APPLY WILAYAH BERDASARKAN TINGKAT_EVENT
// =======================================

const applyEventRegionToForm = async (row = null) => {
  if (!eventInfo.value) return

  const te = eventInfo.value.tingkat_event
  const rowData = row || {}

  if (te === 'provinsi') {
    if (eventInfo.value.province_id) {
      form.value.province_id = eventInfo.value.province_id
      await fetchRegencyOptions(true)
    }
    if (rowData.regency_id) {
      form.value.regency_id = rowData.regency_id
      await fetchDistrictOptions(true)
    }
    if (rowData.district_id) {
      form.value.district_id = rowData.district_id
      await fetchVillageOptions(true)
    }
    if (rowData.village_id) {
      form.value.village_id = rowData.village_id
    }
    return
  }

  if (te === 'kabupaten_kota') {
    if (eventInfo.value.province_id) {
      form.value.province_id = eventInfo.value.province_id
      await fetchRegencyOptions(true)
    }
    if (eventInfo.value.regency_id) {
      form.value.regency_id = eventInfo.value.regency_id
      await fetchDistrictOptions(true)
    }
    if (rowData.district_id) {
      form.value.district_id = rowData.district_id
      await fetchVillageOptions(true)
    }
    if (rowData.village_id) {
      form.value.village_id = rowData.village_id
    }
    return
  }

  if (te === 'kecamatan') {
    if (eventInfo.value.province_id) {
      form.value.province_id = eventInfo.value.province_id
      await fetchRegencyOptions(true)
    }
    if (eventInfo.value.regency_id) {
      form.value.regency_id = eventInfo.value.regency_id
      await fetchDistrictOptions(true)
    }
    if (eventInfo.value.district_id) {
      form.value.district_id = eventInfo.value.district_id
      await fetchVillageOptions(true)
    }
    if (rowData.village_id) {
      form.value.village_id = rowData.village_id
    }
    return
  }

  // nasional / default
  if (rowData.province_id) {
    form.value.province_id = rowData.province_id
    await fetchRegencyOptions(true)
  }
  if (rowData.regency_id) {
    form.value.regency_id = rowData.regency_id
    await fetchDistrictOptions(true)
  }
  if (rowData.district_id) {
    form.value.district_id = rowData.district_id
    await fetchVillageOptions(true)
  }
  if (rowData.village_id) {
    form.value.village_id = rowData.village_id
  }
}

// =======================================
// MODAL CREATE / EDIT
// =======================================

const resetForm = () => {
  form.value = {
    id: null,
    event_competition_branch_id: '',
    nik: '',
    full_name: '',
    phone_number: '',
    place_of_birth: '',
    date_of_birth: '',
    gender: 'MALE',
    province_id: '',
    regency_id: '',
    district_id: '',
    village_id: null,
    address: '',
    education: 'SMA',
    bank_account_number: '',
    bank_account_name: '',
    bank_name: '',
    photo_url: '',
    id_card_url: '',
    family_card_url: '',
    bank_book_url: '',
    certificate_url: '',
    other_url: '',
    tanggal_terbit_ktp: '',
    tanggal_terbit_kk: '',
  }

  files.value = {
    photo_url: null,
    id_card_url: null,
    family_card_url: null,
    bank_book_url: null,
    certificate_url: null,
    other_url: null,
  }

  fileErrors.value = {
    photo_url: '',
    id_card_url: '',
    family_card_url: '',
    bank_book_url: '',
    certificate_url: '',
    other_url: '',
  }

  fieldErrors.value = {
    nik: '',
    full_name: '',
    phone_number: '',
    place_of_birth: '',
    date_of_birth: '',
    gender: '',
    event_competition_branch_id: '',
    province_id: '',
    regency_id: '',
    district_id: '',
    address: '',
    education: '',
    bank_account_number: '',
    bank_account_name: '',
    bank_name: '',
    tanggal_terbit_ktp: '',
    tanggal_terbit_kk: '',
  }
}

const requiredFields = [
  'nik',
  'full_name',
  'phone_number',
  'place_of_birth',
  'date_of_birth',
  'gender',
  'event_competition_branch_id',
  'province_id',
  'regency_id',
  'district_id',
  'address',
  'education',
  'bank_account_number',
  'bank_account_name',
  'bank_name',
  'tanggal_terbit_ktp',
  'tanggal_terbit_kk',
]

const validateField = (field) => {
  let msg = ''
  const val = form.value[field]

  if (requiredFields.includes(field)) {
    if (!val) {
      msg = 'Kolom ini wajib diisi.'
    }
  }

  // khusus NIK → ikut pesan dari validateNik
  if (field === 'nik' && !msg && nikError.value) {
    msg = nikError.value
  }

   // ➕ VALIDASI TANGGAL OPSIONAL (jika diisi wajib valid)
  if ((field === 'tanggal_terbit_ktp' || field === 'tanggal_terbit_kk') && val) {
    const valid = /^\d{4}-\d{2}-\d{2}$/.test(val)
    if (!valid) {
      msg = 'Format tanggal tidak valid.'
    }
  }

   // ➕ VALIDASI TELEPON
  if (field === 'phone_number' && !msg) {
    const onlyNum = /^[0-9]+$/.test(val || '')

    if (!onlyNum) {
      msg = 'Nomor telepon hanya boleh berisi angka.'
    } else if (val.length < 10) {
      msg = 'Nomor telepon minimal 10 digit.'
    } else if (val.length > 13) {
      msg = 'Nomor telepon maksimal 13 digit.'
    }
  }

  fieldErrors.value[field] = msg
  return !msg
}

const validateAllFields = () => {
  let ok = true
  requiredFields.forEach((f) => {
    if (!validateField(f)) ok = false
  })
  return ok
}


const openCreateModal = async () => {
  isEdit.value = false
  nikError.value = ''
  activeTab.value = 'biodata'
  resetForm()

  isInitLocation.value = true
  await applyEventRegionToForm()
  isInitLocation.value = false

  $('#participantModal').modal('show')
}

const openEditModal = async (p) => {
  isEdit.value = true
  nikError.value = ''
  activeTab.value = 'biodata'
  isInitLocation.value = true

  files.value = {
    photo_url: null,
    id_card_url: null,
    family_card_url: null,
    bank_book_url: null,
    certificate_url: null,
    other_url: null,
  }
  fileErrors.value = {
    photo_url: '',
    id_card_url: '',
    family_card_url: '',
    bank_book_url: '',
    certificate_url: '',
    other_url: '',
  }

  form.value = {
    id: p.id,
    event_competition_branch_id: p.event_competition_branch_id,
    nik: p.nik,
    full_name: p.full_name,
    phone_number: p.phone_number,
    place_of_birth: p.place_of_birth,
    date_of_birth: toDateInput(p.date_of_birth),
    gender: p.gender || 'MALE',
    province_id: p.province_id,
    regency_id: p.regency_id,
    district_id: p.district_id,
    village_id: p.village_id,
    address: p.address,
    education: p.education || 'SMA',
    bank_account_number: p.bank_account_number,
    bank_account_name: p.bank_account_name,
    bank_name: p.bank_name,
    photo_url: p.photo_url,
    id_card_url: p.id_card_url,
    family_card_url: p.family_card_url,
    bank_book_url: p.bank_book_url,
    certificate_url: p.certificate_url,
    other_url: p.other_url,
    tanggal_terbit_ktp: toDateInput(p.tanggal_terbit_ktp),
    tanggal_terbit_kk: toDateInput(p.tanggal_terbit_kk),
  }

  try {
    await applyEventRegionToForm(p)
  } finally {
    isInitLocation.value = false
  }

  $('#participantModal').modal('show')
}

const openLampiranModal = async (p) => {
  isEdit.value = true
  nikError.value = ''
  activeTab.value = 'lampiran'  // ⬅️ pindah tab

  isInitLocation.value = true

  form.value = {
    id: p.id,
    event_competition_branch_id: p.event_competition_branch_id,
    nik: p.nik,
    full_name: p.full_name,
    phone_number: p.phone_number,
    place_of_birth: p.place_of_birth,
    date_of_birth: toDateInput(p.date_of_birth),
    gender: p.gender || 'MALE',
    province_id: p.province_id,
    regency_id: p.regency_id,
    district_id: p.district_id,
    village_id: p.village_id,
    address: p.address,
    education: p.education || 'SMA',
    bank_account_number: p.bank_account_number,
    bank_account_name: p.bank_account_name,
    bank_name: p.bank_name,
    photo_url: p.photo_url,
    id_card_url: p.id_card_url,
    family_card_url: p.family_card_url,
    bank_book_url: p.bank_book_url,
    certificate_url: p.certificate_url,
    other_url: p.other_url,
    tanggal_terbit_ktp: toDateInput(p.tanggal_terbit_ktp),
    tanggal_terbit_kk: toDateInput(p.tanggal_terbit_kk),
  }

  try {
    await applyEventRegionToForm(p)
  } finally {
    isInitLocation.value = false
  }

  $('#participantModal').modal('show')
}



// =======================================
// FILE HANDLER (PDF, max 1MB)
// =======================================

const MAX_FILE_SIZE = 1024 * 1024 // 1 MB

const onFileChange = (event, field) => {
  fileErrors.value[field] = ''
  const file = event.target.files[0]

  if (!file) {
    files.value[field] = null
    return
  }

  // Kalau field = photo_url → hanya boleh JPG/PNG
  if (field === 'photo_url') {
    const allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png']
    const fileType = file.type

    if (!allowedImageTypes.includes(fileType)) {
      fileErrors.value[field] = 'Foto harus berupa file JPG atau PNG.'
      event.target.value = ''
      files.value[field] = null
      return
    }
  } else {
    // Selain photo_url → wajib PDF
    if (file.type !== 'application/pdf') {
      fileErrors.value[field] = 'Format file harus PDF.'
      event.target.value = ''
      files.value[field] = null
      return
    }
  }

  // Cek ukuran (semua file maks 1 MB)
  if (file.size > MAX_FILE_SIZE) {
    fileErrors.value[field] = 'Ukuran file maksimal 1 MB.'
    event.target.value = ''
    files.value[field] = null
    return
  }

  // Kalau semua valid → simpan
  files.value[field] = file
}


// =======================================
// NIK → tanggal lahir & gender + validasi
// =======================================

const extractBirthdateFromNik = (nikRaw) => {
  const nik = String(nikRaw || '').replace(/\D/g, '')
  if (nik.length !== 16) return null

  const ddStr = nik.slice(6, 8)
  const mmStr = nik.slice(8, 10)
  const yyStr = nik.slice(10, 12)

  let day = parseInt(ddStr, 10)
  const month = parseInt(mmStr, 10)
  const year2 = parseInt(yyStr, 10)

  if (Number.isNaN(day) || Number.isNaN(month) || Number.isNaN(year2)) {
    return null
  }

  let gender = 'MALE'
  if (day > 40) {
    day -= 40
    gender = 'FEMALE'
  }

  if (day < 1 || day > 31 || month < 1 || month > 12) {
    return null
  }

  const now = new Date()
  const currentYear2 = now.getFullYear() % 100
  const fullYear = year2 <= currentYear2 ? 2000 + year2 : 1900 + year2

  const yyyy = String(fullYear).padStart(4, '0')
  const mm = String(month).padStart(2, '0')
  const dd = String(day).padStart(2, '0')

  return {
    dateOfBirth: `${yyyy}-${mm}-${dd}`,
    gender,
  }
}

const validateNik = async () => {
  nikError.value = ''
  fieldErrors.value.nik = ''
  const nikRaw = form.value.nik || ''
  const nik = nikRaw.replace(/\D/g, '')

  if (!nik) {
    nikError.value = 'NIK wajib diisi.'
    return false
  }

  // if (nik.length !== 16) {
  //   nikError.value = 'NIK harus terdiri dari 16 digit angka.'
  //   return false
  // }

  const result = extractBirthdateFromNik(nik)
  if (!result) {
    nikError.value =
      'NIK tidak valid atau tanggal lahir tidak dapat dibaca dari NIK.'
      fieldErrors.value.nik = nikError.value
    return false
  }

  form.value.date_of_birth = result.dateOfBirth
  form.value.gender = result.gender

  if (!eventId.value) {
    return true
  }

  try {
    isNikChecking.value = true

    const res = await axios.get('/api/v1/check-nik', {
      params: {
        nik,
        event_id: eventId.value,
        participant_id: form.value.id || null,
        province_id: form.value.province_id,
        regency_id: form.value.regency_id,
        district_id: form.value.district_id,
        village_id: form.value.village_id,
      },
    })

    if (res.data.conflict) {
      nikError.value = res.data.message || 'NIK konflik dengan peserta lain.'
      fieldErrors.value.nik = nikError.value
      return false
    }

    nikError.value = ''
    fieldErrors.value.nik = ''
    return true
  } catch (e) {
    console.error('Gagal cek NIK ke server:', e)
    nikError.value = 'Gagal melakukan validasi NIK ke server.'
    return false
  } finally {
    isNikChecking.value = false
  }
}

const debouncedNikCheck = useDebounceFn(async () => {
  const nikRaw = form.value.nik || ''
  const nik = nikRaw.replace(/\D/g, '')

  if (!nik || nik.length !== 16 || !eventId.value) return

  await validateNik()
}, 600)

watch(
  () => form.value.nik,
  (newNik) => {
    if (!newNik) {
      nikError.value = ''
      form.value.date_of_birth = ''
      return
    }

    const result = extractBirthdateFromNik(newNik)
    if (!result) return

    form.value.date_of_birth = result.dateOfBirth
    form.value.gender = result.gender
    nikError.value = ''

    debouncedNikCheck()
  }
)

const onNikBlur = async () => {
  if (!form.value.nik) {
    nikError.value = ''
    return
  }
  if (debouncedNikCheck.cancel) {
    debouncedNikCheck.cancel()
  }
  await validateNik()
}



// =========================
// MUTASI PESERTA
// =========================
const isMutasiSubmitting = ref(false)

const mutasiForm = ref({
  id: null,
  province_id: '',
  regency_id: '',
  district_id: '',
})

const mutasiErrors = ref({
  province_id: '',
  regency_id: '',
  district_id: '',
})

const mutasiProvinceOptions = ref([])
const mutasiRegencyOptions = ref([])
const mutasiDistrictOptions = ref([])

const loadMutasiProvinces = async () => {
  try {
    // pakai API master yang sama
    const res = await axios.get('/api/v1/get/provinces')
    mutasiProvinceOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal memuat provinsi mutasi:', e)
  }
}

const loadMutasiRegencies = async () => {
  if (!mutasiForm.value.province_id) {
    mutasiRegencyOptions.value = []
    mutasiForm.value.regency_id = ''
    mutasiDistrictOptions.value = []
    mutasiForm.value.district_id = ''
    return
  }

  try {
    const res = await axios.get('/api/v1/get/regencies', {
      params: { province_id: mutasiForm.value.province_id },
    })
    mutasiRegencyOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal memuat kab/kota mutasi:', e)
  }
}

const loadMutasiDistricts = async () => {
  if (!mutasiForm.value.regency_id) {
    mutasiDistrictOptions.value = []
    mutasiForm.value.district_id = ''
    return
  }

  try {
    const res = await axios.get('/api/v1/get/districts', {
      params: { regency_id: mutasiForm.value.regency_id },
    })
    mutasiDistrictOptions.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Gagal memuat kecamatan mutasi:', e)
  }
}

// handler change
const onMutasiProvinceChange = () => {
  mutasiForm.value.regency_id = ''
  mutasiForm.value.district_id = ''
  mutasiRegencyOptions.value = []
  mutasiDistrictOptions.value = []
  loadMutasiRegencies()
}

const onMutasiRegencyChange = () => {
  mutasiForm.value.district_id = ''
  mutasiDistrictOptions.value = []
  loadMutasiDistricts()
}

const openMutasiModal = async (p) => {
  // reset error
  mutasiErrors.value = {
    province_id: '',
    regency_id: '',
    district_id: '',
  }

  mutasiForm.value = {
    id: p.id,
    province_id: p.province_id,
    regency_id: p.regency_id,
    district_id: p.district_id,
  }

  await loadMutasiProvinces()
  await loadMutasiRegencies()
  await loadMutasiDistricts()

  $('#mutasiModal').modal('show')
}

const submitMutasi = async () => {
  mutasiErrors.value = {
    province_id: '',
    regency_id: '',
    district_id: '',
  }

  // simple front validation
  if (!mutasiForm.value.province_id) {
    mutasiErrors.value.province_id = 'Provinsi wajib dipilih.'
  }
  if (!mutasiForm.value.regency_id) {
    mutasiErrors.value.regency_id = 'Kabupaten/Kota wajib dipilih.'
  }
  if (!mutasiForm.value.district_id) {
    mutasiErrors.value.district_id = 'Kecamatan wajib dipilih.'
  }

  if (
    mutasiErrors.value.province_id ||
    mutasiErrors.value.regency_id ||
    mutasiErrors.value.district_id
  ) {
    return
  }

  if (!mutasiForm.value.id) return

  isMutasiSubmitting.value = true

  try {
    await axios.post(
      `/api/v1/participants/${mutasiForm.value.id}/mutasi-wilayah`,
      {
        province_id: mutasiForm.value.province_id,
        regency_id: mutasiForm.value.regency_id,
        district_id: mutasiForm.value.district_id,
      }
    )

    $('#mutasiModal').modal('hide')

    Swal.fire({
      icon: 'success',
      title: 'Wilayah peserta berhasil dipindahkan.',
    })

    // reload list
    fetchParticipants(meta.value.current_page)
  } catch (error) {
    console.error('Gagal mutasi peserta:', error)
    const msg = error.response?.data?.message || 'Gagal memindahkan peserta.'
    Swal.fire({ icon: 'error', title: 'Error', text: msg })
  } finally {
    isMutasiSubmitting.value = false
  }
}




// =======================================
// SUBMIT & DELETE (pakai FormData)
// =======================================

const submitForm = async () => {
  if (!eventId.value) {
    alert('Event belum terdeteksi. Silakan pilih event terlebih dahulu.')
    return
  }

   // validasi semua field biodata dulu
  const okBasic = validateAllFields()
  if (!okBasic) {
    activeTab.value = 'biodata'
    Swal.fire({
      icon: 'error',
      title: 'Biodata belum lengkap',
      text: 'Periksa kembali isian yang ditandai merah.',
    })
    return
  }

  // validasi khusus NIK (format + cek server)
  const okNik = await validateNik()
  if (!okNik) {
    activeTab.value = 'biodata'
    return
  }

  // cek error lampiran seperti sebelumnya
  for (const key of Object.keys(fileErrors.value)) {
    if (fileErrors.value[key]) {
      activeTab.value = 'lampiran'
      Swal.fire({
        icon: 'error',
        title: 'Lampiran belum valid',
        text: 'Periksa kembali file lampiran, masih ada error.',
      })
      return
    }
  }

  isSubmitting.value = true

  const fd = new FormData()

  // field biasa
  fd.append('event_id', eventId.value)
  fd.append('event_competition_branch_id', form.value.event_competition_branch_id || '')
  fd.append('nik', form.value.nik || '')
  fd.append('full_name', form.value.full_name || '')
  fd.append('phone_number', form.value.phone_number || '')
  fd.append('place_of_birth', form.value.place_of_birth || '')
  fd.append('date_of_birth', form.value.date_of_birth || '')
  fd.append('gender', form.value.gender || '')
  fd.append('province_id', form.value.province_id || '')
  fd.append('regency_id', form.value.regency_id || '')
  fd.append('district_id', form.value.district_id || '')
  fd.append('village_id', form.value.village_id || '')
  fd.append('address', form.value.address || '')
  fd.append('education', form.value.education || '')
  fd.append('bank_account_number', form.value.bank_account_number || '')
  fd.append('bank_account_name', form.value.bank_account_name || '')
  fd.append('bank_name', form.value.bank_name || '')
  fd.append('tanggal_terbit_ktp', form.value.tanggal_terbit_ktp || '')
  fd.append('tanggal_terbit_kk', form.value.tanggal_terbit_kk || '')

  // lampiran: kalau ada file, kirim file; kalau tidak, kirim path lama (agar backend bisa tetap pakai)
  const attachmentFields = [
    'photo_url',
    'id_card_url',
    'family_card_url',
    'bank_book_url',
    'certificate_url',
    'other_url',
  ]

  attachmentFields.forEach((field) => {
    if (files.value[field]) {
      fd.append(field, files.value[field]) // UploadedFile di backend
    } else if (form.value[field]) {
      fd.append(field, form.value[field]) // path lama (optional)
    }
  })

  try {
    if (isEdit.value && form.value.id) {
      fd.append('_method', 'PUT')
      await axios.post(`/api/v1/participants/${form.value.id}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    } else {
      await axios.post('/api/v1/participants', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }

    $('#participantModal').modal('hide')
    Swal.fire({
      icon: 'success',
      title: `Data ${form.value.full_name} berhasil disimpan.`
    });
    fetchParticipants(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan peserta:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan peserta.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteParticipant = async (p) => {
  if (!confirm(`Yakin ingin menghapus peserta "${p.full_name}"?`)) return

  try {
    await axios.delete(`/api/v1/participants/${p.id}`)
    fetchParticipants(1)
  } catch (error) {
    console.error('Gagal menghapus peserta:', error)
    alert(error.response?.data?.message || 'Gagal menghapus peserta.')
  }
}

// =======================================
// WATCHERS & MOUNTED
// =======================================

watch(
  () => [form.value.date_of_birth, form.value.event_competition_branch_id],
  () => {
    validateAgeForBranch()
  }
)


watch(
  () => search.value,
  useDebounceFn(() => fetchParticipants(1), 400)
)

watch(
  () => form.value.province_id,
  () => {
    if (isInitLocation.value) return
    fetchRegencyOptions()
  }
)

watch(
  () => form.value.regency_id,
  () => {
    if (isInitLocation.value) return
    fetchDistrictOptions()
  }
)

watch(
  () => form.value.district_id,
  () => {
    if (isInitLocation.value) return
    fetchVillageOptions()
  }
)

onMounted(async () => {
  getEventInfoFromStorage()
  await fetchProvinceOptions()
  await fetchBranchOptions()
  fetchParticipants()
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

.lampiran-photo-card {
  min-height: 100%;
}

.lampiran-photo-frame {
  width: 180px;
  height: 260px;
  border: 1px solid #dee2e6;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.lampiran-photo-frame img {
  max-width: 100%;
  max-height: 100%;
  object-fit: cover;
}

.lampiran-photo-input {
  max-width: 220px;
}

.lampiran-row {
  margin-bottom: 1rem;
  border-bottom: 1px dashed #f0f0f0;
  padding-bottom: 0.5rem;
}

.text-xs {
  font-size: 0.75rem;
}

.badge-file:hover {
  opacity: 0.8;
}
</style>



