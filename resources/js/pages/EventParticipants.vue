<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Bank Data Peserta per Event </h1>
          <p class="mb-0 text-muted text-sm">
            Mengelola peserta yang terdaftar pada event aktif, termasuk status pendaftaran dan daftar ulang.
          </p>

          <!-- Info event aktif -->
          <!-- <p v-if="eventId" class="mb-0 mt-1 text-sm text-muted">
            Event aktif:
            <strong>{{ eventData?.event_name }}</strong>
            <span v-if="eventData?.event_year"> ({{ eventData.event_year }})</span>
            • Lokasi: <strong>{{ eventData?.event_location || '-' }}</strong>
          </p> -->
        </div>

        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-info btn-sm disabled">
            Dipilih: <strong>{{ selectedParticipantIds.length }}</strong>
          </button>

          <button
            class="btn btn-success btn-sm"
            :disabled="!selectedParticipantIds.length || !eventId || !canRegisterParticipant"
            @click="openRegisterModal"
          >
            <i class="fas fa-check-double mr-1"></i>
            Daftarkan
          </button>

          <button
            class="btn btn-primary btn-sm"
            @click="openCreateModal"
            :disabled="!eventId || !canAddParticipant"
          >
            <i class="fas fa-user-plus mr-1"></i>
            Tambah
          </button>
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

      <div class="card">
        <div class="card-header py-2">
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

              <!-- STATUS -->
              <select
                v-model="filters.registration_status"
                class="form-control form-control-sm w-auto"
                title="Status"
              >
                <option value="">Status</option>
                <option value="bank_data">Bank Data</option>
                <option value="process">Proses</option>
                <option value="verified">Verified</option>
                <option value="need_revision">Revisi</option>
                <option value="rejected">Ditolak</option>
                <option value="disqualified">Diskualifikasi</option>
              </select>

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
                <th style="width: 30px;" class="text-center">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll($event)"
                />
              </th>
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
                <td colspan="8" class="text-center">
                  Memuat data peserta event...
                </td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="8" class="text-center">
                  Belum ada peserta terdaftar untuk event ini.
                  <br />
                  <small class="text-muted">
                    Klik <strong>Tambah Peserta Event</strong> untuk menambahkan peserta.
                  </small>
                </td>
              </tr>
              <tr
                v-for="(item, index) in items"
                :key="item.id"
              >
                <td class="text-center">
                  <input
                    type="checkbox"
                    :value="item.id"
                    v-model="selectedParticipantIds"
                    :disabled="isCheckboxDisabled(item)"


                  />
                </td>

                <!-- Nomor -->
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>

                <!-- Nama Peserta -->
                <td>
                  
                  <strong>{{ item.participant?.full_name }}</strong>
                  
                  <br>
                  <span
                    class="badge mr-1" style="width:17px;"
                    :class="item.participant?.gender === 'MALE' ? 'badge-primary' : 'badge-pink'"
                  >
                    <i :class="item.participant?.gender === 'MALE' ? 'fas fa-mars' : 'fas fa-venus'"></i>
                  </span>

                  <span
                    class="badge"
                    :class="registrationBadgeClass(item.registration_status)"
                  >
                    {{ registrationStatusLabel(item.registration_status) }}
                  </span>
                </td>

                <!-- NIK -->
                <td>
                    <strong>{{ item.participant?.nik }}</strong>
                    <div v-if="item.age_year !== null" class="text-xs text-muted">
                        Umur:
                        {{ item.age_year }}T
                        {{ item.age_month }}B
                        {{ item.age_day }}H
                    </div> 
                  <!-- <div>
                    {{ item.participant?.place_of_birth || '-' }},
                    {{ formatDate(item.participant?.date_of_birth) || '-' }}
                  </div>
                  <span
                    class="badge mt-1"
                    :class="item.participant?.gender === 'MALE' ? 'badge-primary' : 'badge-pink'"
                  >
                    {{ item.participant?.gender === 'MALE' ? 'Laki-laki' : 'Perempuan' }}
                  </span>
                  <div v-if="item.age_year !== null" class="text-xs text-muted mt-1">
                    Umur:
                    {{ item.age_year }} th
                    <span v-if="item.age_month"> {{ item.age_month }} bln</span>
                  </div> -->
                </td>

                <td>
                    <strong>{{ item.event_group.full_name }}</strong>
                    <div class="text-xs text-muted" v-if="item.event_group">
                        Batas:
                        {{ item.event_group.max_age - 1 }}T
                        11B
                        29H
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
                      :class="{
                        'bg-danger': item.participant.lampiran_completion_percent <= 20,
                        'bg-warning': item.participant.lampiran_completion_percent > 20 && item.participant.lampiran_completion_percent <= 50,
                        'bg-info': item.participant.lampiran_completion_percent > 50 && item.participant.lampiran_completion_percent <= 80,
                        'bg-success': item.participant.lampiran_completion_percent > 80
                      }"
                      role="progressbar"
                      :style="{ width: item.participant.lampiran_completion_percent + '%' }"
                      :aria-valuenow="item.participant.lampiran_completion_percent"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    >
                      {{ item.participant.lampiran_completion_percent }}%
                    </div>
                  </div>
                </td>

                <!-- <td>
                  <span
                    class="badge"
                    :class="registrationBadgeClass(item.registration_status)"
                  >
                    {{ registrationStatusLabel(item.registration_status) }}
                  </span>
                  <div v-if="item.registration_notes" class="text-xs text-muted mt-1">
                    {{ item.registration_notes }}
                  </div>
                </td>

                <td>
                  <span
                    class="badge"
                    :class="reregistrationBadgeClass(item.reregistration_status)"
                  >
                    {{ reregistrationStatusLabel(item.reregistration_status) }}
                  </span>
                  <div v-if="item.reregistered_at" class="text-xs text-muted mt-1">
                    {{ formatDateTime(item.reregistered_at) }}
                  </div>
                </td> -->

                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <!-- EDIT BIODATA (hanya bank_data & need_revision) -->
                        <button
                        v-if="['bank_data', 'need_revision'].includes(item.registration_status)"
                        class="btn btn-outline-warning btn-xs"
                        title="Edit Biodata"
                        @click="openEditModal(item)"
                        >
                        <i class="fas fa-user-edit"></i>
                        </button>

                        <!-- EDIT LAMPIRAN (hanya bank_data & need_revision) -->
                        <button
                        v-if="['bank_data', 'need_revision'].includes(item.registration_status)"
                        class="btn btn-outline-info btn-xs"
                        title="Edit Lampiran"
                        @click="openLampiranModal(item)"
                        >
                        <i class="fas fa-file-upload"></i>
                        </button>

                       
                        <!-- LIHAT DATA (selalu tampil) -->
                        <button
                          class="btn btn-outline-primary btn-xs"
                          title="Lihat Data Peserta"
                          @click="openViewModal(item)" 
                        >
                          <i class="fas fa-eye"></i>
                        </button>

                        <!-- MUTASI PESERTA (hanya bank_data & perbaiki) -->
                        <button
                          v-if="['bank_data', 'perbaiki'].includes(item.registration_status)"
                          class="btn btn-outline-success btn-xs"
                          title="Mutasi Peserta"
                          @click="openMutasiModal(item)"
                        >
                          <i class="fas fa-random"></i>
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
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari
              {{ meta.total || 0 }} peserta event
            </div>
            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a
                  href="#"
                  class="page-link"
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
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a
                  href="#"
                  class="page-link"
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

    <!-- MODAL TAMBAH / EDIT PESERTA EVENT -->
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

                  <!-- NIK, Kategori/Cabang, Nama, Telepon -->
                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">NIK Peserta <span class="text-danger">*</span></label>
                      <div class="input-group input-group-sm">
                          <input
                          v-model="form.participant.nik"
                          @blur="onNikBlur(); validateField('participant.nik')"
                          :disabled="isEdit"
                          type="text"
                          maxlength="16"
                          class="form-control form-control-sm"
                          :class="{
                              'is-invalid': fieldErrors['participant.nik'] || nikError,
                              'is-valid': !fieldErrors['participant.nik'] && !nikError && form.participant.nik
                              
                          }"
                          placeholder="Masukkan NIK"
                          />
                          <div class="input-group-append">
                          <button
                              type="button"
                              class="btn btn-outline-secondary btn-sm"
                              @click="onSearchNik"
                              :disabled="isNikChecking || !form.participant.nik || isEdit"
                              title="Cari data peserta berdasarkan NIK"
                          >
                              <i v-if="isNikChecking" class="fas fa-spinner fa-spin"></i>
                              <span v-else>
                              <i class="fas fa-search mr-1"></i> Cari
                              </span>
                          </button>
                          </div>

                          <div class="invalid-feedback" v-if="fieldErrors['participant.nik'] || nikError">
                          {{ fieldErrors['participant.nik'] || nikError }}
                          </div>
                          <div
                          class="valid-feedback"
                          v-else-if="form.participant.nik && !nikError"
                          >
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
                          v-model="form.event_participant.event_category_id"
                          class="form-control form-control-sm"
                          @change="validateField('event_participant.event_category_id')"
                          :class="{
                          'is-invalid': fieldErrors['event_participant.event_category_id'],
                          'is-valid': !fieldErrors['event_participant.event_category_id'] && form.event_participant.event_category_id
                          }"
                      >
                          <option value="" disabled>-- Pilih Cabang/Golongan --</option>
                          <option
                          v-for="b in eventCategories"
                          :key="b.id"
                          :value="b.id"
                          >
                          {{ b.full_name }}
                          </option>
                      </select>
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['event_participant.event_category_id']"
                      >
                          {{ fieldErrors['event_participant.event_category_id'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                      <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="form.participant.full_name"
                          @blur="validateField('participant.full_name')"
                          :class="{
                          'is-invalid': fieldErrors['participant.full_name'],
                          'is-valid': !fieldErrors['participant.full_name'] && form.participant.full_name
                          }"
                      />
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.full_name']"
                      >
                          {{ fieldErrors['participant.full_name'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Nomor HP <span class="text-danger">*</span></label>
                      <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="form.participant.phone_number"
                          @blur="validateField('participant.phone_number')"
                          :class="{
                          'is-invalid': fieldErrors['participant.phone_number'],
                          'is-valid': !fieldErrors['participant.phone_number'] && form.participant.phone_number
                          }"
                      />
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.phone_number']"
                      >
                          {{ fieldErrors['participant.phone_number'] }}
                      </div>
                      </div>
                  </div>

                  <!-- TEMPAT LAHIR, TANGGAL LAHIR, JENIS KELAMIN -->
                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Tempat Lahir <span class="text-danger">*</span></label>
                      <input
                          v-model="form.participant.place_of_birth"
                          type="text"
                          class="form-control form-control-sm"
                          @blur="validateField('participant.place_of_birth')"
                          :class="{
                          'is-invalid': fieldErrors['participant.place_of_birth'],
                          'is-valid': !fieldErrors['participant.place_of_birth'] && form.participant.place_of_birth
                          }"
                      />
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.place_of_birth']"
                      >
                          {{ fieldErrors['participant.place_of_birth'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-4">
                      <div class="form-group mb-0">
                          <label class="mb-1">Tanggal Lahir <span class="text-danger">*</span></label>
                          <div class="input-group input-group-sm">
                          <input
                              v-model="form.participant.date_of_birth"
                              type="date"
                              class="form-control form-control-sm"
                              @blur="validateField('participant.date_of_birth')"
                              :class="{
                              'is-invalid': fieldErrors['participant.date_of_birth'],
                              'is-valid': !fieldErrors['participant.date_of_birth'] && form.participant.date_of_birth
                              }"
                              disabled
                          />
                          <div class="input-group-append">
                              <span class="input-group-text">
                              <i class="far fa-calendar-alt"></i>
                              </span>
                          </div>
                          </div>

                          <!-- pesan error utama DOB (required/format/umur) -->
                          <div
                          class="invalid-feedback d-block"
                          v-if="fieldErrors['participant.date_of_birth']"
                          >
                          {{ fieldErrors['participant.date_of_birth'] }}
                          </div>

                          <!-- hint dari NIK -->
                          <small v-if="nikDobHint" class="text-muted d-block">
                          Diambil dari NIK: {{ nikDobHint }}
                          </small>

                          <!-- pesan khusus validasi umur vs max_age -->
                          <!-- <small
                          v-if="ageMessage"
                          class="d-block mt-1"
                          :class="ageStatus === 'invalid' ? 'text-danger' : 'text-success'"
                          >
                          {{ ageMessage }}
                          </small> -->
                      </div>
                  </div>


                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Jenis Kelamin <span class="text-danger">*</span></label>
                      <select
                          v-model="form.participant.gender"
                          class="form-control form-control-sm"
                          @change="validateField('participant.gender')"
                          :class="{
                          'is-invalid': fieldErrors['participant.gender'],
                          'is-valid': !fieldErrors['participant.gender'] && form.participant.gender
                          }"
                          disabled
                      >
                          <option value="">-- Pilih --</option>
                          <option value="MALE">LAKI-LAKI</option>
                          <option value="FEMALE">PEREMPUAN</option>
                      </select>
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.gender']"
                      >
                          {{ fieldErrors['participant.gender'] }}
                      </div>
                      <small v-if="nikGenderHint" class="text-muted">
                          Diambil dari NIK: {{ nikGenderHint }}
                      </small>
                      </div>
                  </div>

                  <!-- PENDIDIKAN -->
                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Pendidikan <span class="text-danger">*</span></label>
                      <select
                          v-model="form.participant.education"
                          class="form-control form-control-sm"
                          @change="validateField('participant.education')"
                          :class="{
                          'is-invalid': fieldErrors['participant.education'],
                          'is-valid': !fieldErrors['participant.education'] && form.participant.education
                          }"
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
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.education']"
                      >
                          {{ fieldErrors['participant.education'] }}
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
                      <label class="mb-1">
                          Provinsi (Sesuai KTP) <span class="text-danger">*</span>
                      </label>
                      <select
                          v-model="form.participant.province_id"
                          class="form-control form-control-sm"
                          :disabled="disabledProvince"
                          @change="validateField('participant.province_id')"
                          :class="{
                          'is-invalid': fieldErrors['participant.province_id'],
                          'is-valid': !fieldErrors['participant.province_id'] && form.participant.province_id
                          }"
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
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.province_id']"
                      >
                          {{ fieldErrors['participant.province_id'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">
                          Kab / Kota (Sesuai KTP) <span class="text-danger">*</span>
                      </label>
                      <select
                          v-model="form.participant.regency_id"
                          class="form-control form-control-sm"
                          :disabled="disabledRegency"
                          @change="validateField('participant.regency_id')"
                          :class="{
                          'is-invalid': fieldErrors['participant.regency_id'],
                          'is-valid': !fieldErrors['participant.regency_id'] && form.participant.regency_id
                          }"
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
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.regency_id']"
                      >
                          {{ fieldErrors['participant.regency_id'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">
                          Kecamatan (Sesuai KTP) <span class="text-danger">*</span>
                      </label>
                      <select
                          v-model="form.participant.district_id"
                          class="form-control form-control-sm"
                          :disabled="disabledDistrict"
                          @change="validateField('participant.district_id')"
                          :class="{
                          'is-invalid': fieldErrors['participant.district_id'],
                          'is-valid': !fieldErrors['participant.district_id'] && form.participant.district_id
                          }"
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
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.district_id']"
                      >
                          {{ fieldErrors['participant.district_id'] }}
                      </div>
                      </div>
                  </div>

                  <!-- DESA & ALAMAT -->
                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Kelurahan / Desa <span class="text-danger">*</span></label>
                      <select
                          v-model="form.participant.village_id"
                          class="form-control form-control-sm"
                          :disabled="!form.participant.district_id || isLoadingVillages"
                          @change="validateField('participant.village_id')"
                          :class="{
                          'is-invalid': fieldErrors['participant.village_id'],
                          'is-valid': !fieldErrors['participant.village_id'] && form.participant.village_id
                          }"
                      >
                          <option :value="null">
                          {{ isLoadingVillages ? 'Memuat Kelurahan/Desa...' : '-- Pilih Kel/Desa --' }}
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
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.village_id']"
                      >
                          {{ fieldErrors['participant.village_id'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-8">
                      <div class="form-group">
                      <label class="mb-1">Alamat Lengkap Peserta <span class="text-danger">*</span></label>
                      <textarea
                          v-model="form.participant.address"
                          rows="2"
                          class="form-control form-control-sm"
                          @blur="validateField('participant.address')"
                          :class="{
                          'is-invalid': fieldErrors['participant.address'],
                          'is-valid': !fieldErrors['participant.address'] && form.participant.address
                          }"
                      ></textarea>
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.address']"
                      >
                          {{ fieldErrors['participant.address'] }}
                      </div>
                      </div>
                  </div>

                  <!-- ===================== INFORMASI REKENING ===================== -->
                  <div class="col-12 mt-3">
                      <h6 class="mb-1 font-weight-bold">Informasi Rekening</h6>
                      <hr class="mt-1 mb-3" />
                  </div>

                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Nomor Rekening <span class="text-danger">*</span></label>
                      <input
                          v-model="form.participant.bank_account_number"
                          type="text"
                          class="form-control form-control-sm"
                          @blur="validateField('participant.bank_account_number')"
                          :class="{
                          'is-invalid': fieldErrors['participant.bank_account_number'],
                          'is-valid': !fieldErrors['participant.bank_account_number'] && form.participant.bank_account_number
                          }"
                      />
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.bank_account_number']"
                      >
                          {{ fieldErrors['participant.bank_account_number'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Bank <span class="text-danger">*</span></label>
                      <select
                          v-model="form.participant.bank_name"
                          class="form-control form-control-sm"
                          @change="validateField('participant.bank_name')"
                          :class="{
                          'is-invalid': fieldErrors['participant.bank_name'],
                          'is-valid': !fieldErrors['participant.bank_name'] && form.participant.bank_name
                          }"
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
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.bank_name']"
                      >
                          {{ fieldErrors['participant.bank_name'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Atas Nama Rekening <span class="text-danger">*</span></label>
                      <input
                          v-model="form.participant.bank_account_name"
                          type="text"
                          class="form-control form-control-sm"
                          @blur="validateField('participant.bank_account_name')"
                          :class="{
                          'is-invalid': fieldErrors['participant.bank_account_name'],
                          'is-valid': !fieldErrors['participant.bank_account_name'] && form.participant.bank_account_name
                          }"
                      />
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.bank_account_name']"
                      >
                          {{ fieldErrors['participant.bank_account_name'] }}
                      </div>
                      </div>
                  </div>

                  <!-- TANGGAL TERBIT DOKUMEN -->
                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Tanggal Terbit KTP <span class="text-danger">*</span></label>
                      <input
                          v-model="form.participant.tanggal_terbit_ktp"
                          type="date"
                          class="form-control form-control-sm"
                          @blur="validateField('participant.tanggal_terbit_ktp')"
                          :class="{
                          'is-invalid': fieldErrors['participant.tanggal_terbit_ktp'],
                          'is-valid': !fieldErrors['participant.tanggal_terbit_ktp'] && form.participant.tanggal_terbit_ktp
                          }"
                      />
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.tanggal_terbit_ktp']"
                      >
                          {{ fieldErrors['participant.tanggal_terbit_ktp'] }}
                      </div>
                      </div>
                  </div>

                  <div class="col-md-4">
                      <div class="form-group">
                      <label class="mb-1">Tanggal Terbit KK <span class="text-danger">*</span></label>
                      <input
                          v-model="form.participant.tanggal_terbit_kk"
                          type="date"
                          class="form-control form-control-sm"
                          @blur="validateField('participant.tanggal_terbit_kk')"
                          :class="{
                          'is-invalid': fieldErrors['participant.tanggal_terbit_kk'],
                          'is-valid': !fieldErrors['participant.tanggal_terbit_kk'] && form.participant.tanggal_terbit_kk
                          }"
                      />
                      <div
                          class="invalid-feedback"
                          v-if="fieldErrors['participant.tanggal_terbit_kk']"
                      >
                          {{ fieldErrors['participant.tanggal_terbit_kk'] }}
                      </div>
                      </div>
                  </div>

                      <!-- ===================== DATA LOMBA ===================== -->
                      <!-- <div class="col-12 mt-3">
                          <h6 class="mb-1 font-weight-bold">Data Lomba</h6>
                          <hr class="mt-1 mb-3" />
                      </div>

                      <div class="col-md-4">
                          <div class="form-group">
                          <label class="mb-1">Kontingen</label>
                          <input
                              v-model="form.event_participant.contingent"
                              type="text"
                              class="form-control form-control-sm"
                              placeholder="Kab/Kota/Instansi"
                          />
                          </div>
                      </div>

                      <div class="col-md-4">
                          <div class="form-group">
                          <label class="mb-1">Status Pendaftaran <span class="text-danger">*</span></label>
                          <select
                              v-model="form.event_participant.registration_status"
                              class="form-control form-control-sm"
                              @change="validateField('event_participant.registration_status')"
                              :class="{
                              'is-invalid': fieldErrors['event_participant.registration_status'],
                              'is-valid': !fieldErrors['event_participant.registration_status'] && form.event_participant.registration_status
                              }"
                          >
                              <option value="bank_data">Bank Data</option>
                              <option value="process">Diproses</option>
                              <option value="verified">Terverifikasi</option>
                              <option value="need_revision">Perlu Perbaikan</option>
                              <option value="rejected">Ditolak</option>
                              <option value="disqualified">Diskualifikasi</option>
                          </select>
                          <div
                              class="invalid-feedback"
                              v-if="fieldErrors['event_participant.registration_status']"
                          >
                              {{ fieldErrors['event_participant.registration_status'] }}
                          </div>
                          </div>
                      </div>

                      <div class="col-md-4">
                          <div class="form-group">
                          <label class="mb-1">Catatan Pendaftaran</label>
                          <textarea
                              v-model="form.event_participant.registration_notes"
                              class="form-control form-control-sm"
                              rows="2"
                          ></textarea>
                          </div>
                      </div>

                      <div v-if="isEdit" class="col-md-4">
                          <div class="form-group">
                          <label class="mb-1">Status Daftar Ulang</label>
                          <select
                              v-model="form.event_participant.reregistration_status"
                              class="form-control form-control-sm"
                          >
                              <option value="not_yet">Belum Hadir</option>
                              <option value="verified">Lolos Daftar Ulang</option>
                              <option value="rejected">Tidak Lolos Daftar Ulang</option>
                          </select>
                          </div>
                      </div>

                      <div v-if="isEdit" class="col-md-8">
                          <div class="form-group">
                          <label class="mb-1">Catatan Daftar Ulang</label>
                          <textarea
                              v-model="form.event_participant.reregistration_notes"
                              class="form-control form-control-sm"
                              rows="2"
                          ></textarea>
                          </div>
                      </div> -->
                  </div>
              </div>

              <!-- TAB LAMPIRAN -->
              <div v-else>
                  <div class="row">
                  <!-- KOLOM FOTO -->
                  <div class="col-md-4">
                      <div class="card card-outline card-primary lampiran-photo-card">
                      <div class="card-body d-flex flex-column align-items-center">
                          <div class="lampiran-photo-frame mb-2">
                          <img
                              v-if="form.participant.photo_url"
                              :src="form.participant.photo_url"
                              alt="Foto Peserta"
                              class="img-fluid"
                          />
                          <span v-else class="text-muted text-sm">
                              Belum ada foto
                          </span>
                          </div>

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

                          <small class="text-muted d-block mt-2 text-center text-xs">
                          Format <strong>JPG/JPEG/PNG</strong>, maksimal
                          <strong>1 MB</strong>.
                          </small>
                      </div>
                      </div>
                  </div>

                  <!-- KOLOM DOKUMEN -->
                  <div class="col-md-8">
                      <!-- KTP -->
                      <div class="form-group row align-items-center lampiran-row">
                      <label class="col-sm-3 col-form-label col-form-label-sm mb-0">
                          KTP
                          <br />
                          <small class="text-muted">(Jika umur &lt; 17 tahun tidak wajib)</small>
                      </label>
                      <div class="col-sm-7">
                          <div class="custom-file">
                          <input
                              type="file"
                              class="custom-file-input"
                              id="ktpInput"
                              accept="image/jpeg,image/png,image/jpg,application/pdf"
                              @change="onFileChange($event, 'id_card_url')"
                          />
                          <label class="custom-file-label" for="ktpInput">
                              Pilih file...
                          </label>
                          </div>
                          <small class="text-muted d-block mt-1 text-xs">
                          Format <strong>PDF/PNG/JPG/JPEG</strong>, maksimal
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
                          Akta Kelahiran
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

    <!-- MODAL KONFIRMASI DAFTARKAN PESERTA -->
    <div class="modal fade" id="registerParticipantsModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              <i class="fas fa-check-double mr-2"></i>
              Konfirmasi Pendaftaran Peserta
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <p class="mb-2">
              Anda akan mendaftarkan <strong>{{ selectedParticipants.length }}</strong> peserta
              untuk event ini dan mengubah
              <code>registration_status</code> menjadi
              <span class="badge badge-info">process</span>.
            </p>

            <div v-if="selectedParticipants.length">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th style="width: 50px;">#</th>
                    <th>Nama Peserta</th>
                    <th>NIK</th>
                    <th>Cabang / Golongan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(ep, idx) in selectedParticipants"
                    :key="ep.id"
                  >
                    <td>{{ idx + 1 }}</td>
                    <td>{{ ep.participant?.full_name }}</td>
                    <td class="text-monospace">{{ ep.participant?.nik }}</td>
                    <td>
                      {{
                        ep.event_category?.full_name
                        || ep.event_group?.full_name
                        || ep.event_branch?.full_name
                        || '-'
                      }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="text-muted">
              Tidak ada peserta yang dipilih.
            </div>
          </div>

          <div class="modal-footer py-2">
            <button
              type="button"
              class="btn btn-sm btn-secondary"
              data-dismiss="modal"
              :disabled="isRegistering"
            >
              Batal
            </button>
            <button
              type="button"
              class="btn btn-sm btn-success"
              :disabled="!selectedParticipants.length || isRegistering"
              @click="submitRegisterParticipants"
            >
              <i v-if="isRegistering" class="fas fa-spinner fa-spin mr-1"></i>
              <i v-else class="fas fa-check mr-1"></i>
              Ya, Daftarkan
            </button>
          </div>
        </div>
      </div>
    </div>



  
    <ViewParticipantModal :selected-participant="selectedParticipant" />
  </section>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../stores/AuthUserStore'
import { useSettingStore } from '../stores/SettingStore'
import { useMasterDataStore } from '../stores/MasterDataStore'
import ViewParticipantModal from './ViewParticipantModal.vue'
import {
  formatDate,
  formatDateTime,
  registrationBadgeClass,
  registrationStatusLabel,
  reregistrationBadgeClass,
  reregistrationStatusLabel,
  parseNikToDobGender,
  createRegionHelpers,  
  bankOptions,
  createEmptyEventParticipantForm,
  eventParticipantRequiredFields,
  getNestedFieldValue,
  eventParticipantAttachmentFields,
  createAttachmentHandlers,
} from './EventParticipantHelpers'

const settingStore = useSettingStore()
const isDevelopmentMode = computed(() => {
  return settingStore.isDevelopment === true
})

const isStageActive = (stageName) => {
  // 🔥 ENVIRONMENT OVERRIDE (PALING ATAS)
  if (isDevelopmentMode.value) {
    return true
  }

  const stage = eventStages.value.find(
    s => s.name?.toLowerCase() === stageName.toLowerCase()
  )

  if (!stage) return false
  if (!stage.is_active) return false

  const start = new Date(stage.start_date)
  const end   = new Date(stage.end_date)
  const nowTs = Date.now()

  return nowTs >= start.getTime() && nowTs <= end.getTime()
}



const eventStages = computed(() => masterDataStore.eventStages || [])

const now = () => new Date()


const canAddParticipant = computed(() => {
  return isDevelopmentMode.value || isStageActive('Persiapan')
})

const canRegisterParticipant = computed(() => {
  return isDevelopmentMode.value || isStageActive('Pendaftaran')
})



// ==================================================
// AUTH & EVENT CONTEXT
// ==================================================
const authUserStore = useAuthUserStore()
const masterDataStore = useMasterDataStore()

const currentUser = computed(() => authUserStore.user || {})
const eventInfo = computed(() => authUserStore.eventData || null)
const tingkatEvent = computed(() => eventInfo.value?.event_level || null)

const isPrivileged = computed(() => {
  const roleName = currentUser.value?.role?.name || ''
  return roleName === 'SUPERADMIN' || roleName === 'ADMIN_EVENT'
})




const isCheckboxDisabled = (p) => {
  if (isDevelopmentMode.value) return false
  if (!canRegisterParticipant.value) return true

  return (
    !['bank_data', 'need_revision'].includes(
      (p.registration_status || '').toLowerCase()
    ) ||
    (p.participant?.lampiran_completion_percent || 0) < 80
  )
}






// event aktif
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

// ==================================================
// STATE TABLE & FILTER
// ==================================================
const items = ref([])
const eventBranches = ref([])   // event_branches (cabang/golongan)
const eventGroups = ref([])
const eventCategories = ref([])

const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)
const searchNikLoading = ref(false)

const activeTab = ref('biodata') // 'biodata' | 'lampiran'

// flag agar watcher wilayah tidak nembak API saat init
const isInitLocation = ref(false)

// ==================================================
// FORM
// ==================================================
const emptyForm = () => createEmptyEventParticipantForm(eventId.value)
const form = ref(emptyForm())

const filters = ref({
  registration_status: '',
  event_group_id: '',      // ✅ filter cabang/golongan
  reregistration_status: '',
})


const meta = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1,
})

const nikSearchResult = ref(null) // 'found' | 'new' | null
const nikDobHint = ref('')
const nikGenderHint = ref('')

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

const attachmentFields = eventParticipantAttachmentFields

const {
  onFileChange,
  openFile,
  hasFile,
  resetFiles,
} = createAttachmentHandlers({ files, fileErrors, form })

// ==================================================
// OPEN VIEW MODAL
// ==================================================




const selectedParticipant = ref(null)

// dipanggil dari tombol di tabel: @click="openViewModal(row)"
const openViewModal = (row) => {
  // row = object eventparticipant (yang ada participant, event_group, event_category, event_branch)
  selectedParticipant.value = row
  $('#viewParticipantModal').modal('show')
}

// ==================================================
// FILE HANDLER
// ==================================================


const fieldErrors = ref({})
const nikError = ref('') 
const isNikChecking = ref(false)


const requiredFields = eventParticipantRequiredFields

const getFieldValue = (path) => {
  return getNestedFieldValue(form.value, path)
}


const validateField = (field) => {
  let msg = ''
  const val = getFieldValue(field)

  // WAJIB ISI
  if (requiredFields.includes(field)) {
    if (!val) {
      msg = 'Kolom ini wajib diisi.'
    }
  }

  // VALIDASI NIK (kalau kamu pakai nikError hasil cek ke backend)
  if (field === 'participant.nik' && !msg && nikError.value) {
    msg = nikError.value
  }

  // VALIDASI TANGGAL OPSIONAL / WAJIB (format yyyy-mm-dd)
  if (
    (field === 'participant.tanggal_terbit_ktp' ||
      field === 'participant.tanggal_terbit_kk' ||
      field === 'participant.date_of_birth') &&
    val
  ) {
    const valid = /^\d{4}-\d{2}-\d{2}$/.test(val)
    if (!valid) {
      msg = 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD.'
    }
  }

  // VALIDASI TELEPON
  if (field === 'participant.phone_number' && !msg) {
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



// ==================================================
// WILAYAH STATE
// ==================================================
const provinceOptions = ref([])
const regencyOptions = ref([])
const districtOptions = ref([])
const villageOptions = ref([])

const isLoadingRegencies = ref(false)
const isLoadingDistricts = ref(false)
const isLoadingVillages = ref(false)

// ==================================================
// DISABLED STATE UNTUK SELECT WILAYAH
// ==================================================
const disabledProvince = computed(() => {
  // kalau belum ada data provinsi → tetap disabled
  if (!provinceOptions.value.length) return true

  const level = tingkatEvent.value
  if (!level) return false

  // non-privileged: kunci provinsi kalau event di level province/regency/district
  if (!isPrivileged.value && ['province', 'regency', 'district'].includes(level)) {
    return true
  }

  return false
})

const disabledRegency = computed(() => {
  // kalau belum pilih provinsi atau sedang load kab/kota → disabled
  if (!form.value.participant.province_id || isLoadingRegencies.value) return true

  const level = tingkatEvent.value
  if (!level) return false

  // non-privileged: kunci kab/kota kalau event level regency/district
  if (!isPrivileged.value && ['regency', 'district'].includes(level)) {
    return true
  }

  return false
})

const disabledDistrict = computed(() => {
  // kalau belum pilih kab/kota atau sedang load kecamatan → disabled
  if (!form.value.participant.regency_id || isLoadingDistricts.value) return true

  const level = tingkatEvent.value
  if (!level) return false

  // Contoh sesuai permintaan:
  // di select district, akan disabled ketika event_level 'regency' dan !isPrivileged
  if (!isPrivileged.value && level === 'regency') {
    return true
  }

  return false
})










// ==================================================
// API: LIST & MASTER EVENT
// ==================================================
const fetchItems = async (page = 1) => {
  if (!eventId.value) return

  isLoading.value = true
  try {
    const res = await axios.get(`/api/v1/events/${eventId.value}/participants`, {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        registration_status: filters.value.registration_status || '',
        event_group_id: filters.value.event_group_id || '',   // ✅ tambah ini
        reregistration_status: filters.value.reregistration_status || '',
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
      authUserStore.logout()
    } else {
      Swal.fire('Gagal', 'Gagal memuat data peserta event.', 'error')
    }
  } finally {
    isLoading.value = false
  }
}

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


// ==================================================
// NIK HELPER + SEARCH & VALIDATION
// ==================================================

// wrapper dari parseNikToDobGender → bentuk { dateOfBirth, gender }
const extractBirthdateFromNik = (nikRaw) => {
  const nik = (nikRaw || '').replace(/\D/g, '')
  if (nik.length !== 16) return null

  const parsed = parseNikToDobGender(nik)
  if (!parsed) return null

  return {
    dateOfBirth: parsed.date,
    gender: parsed.gender,
  }
}

// isi form.participant dari data bank peserta
const prefillFormFromParticipant = async (p) => {
  form.value.participant = {
    id: p.id,
    nik: p.nik,
    full_name: p.full_name,
    phone_number: p.phone_number,
    place_of_birth: p.place_of_birth,
    date_of_birth: p.date_of_birth,
    gender: p.gender,
    education: p.education || 'SMA',
    address: p.address,

    province_id: p.province_id || null,
    regency_id: p.regency_id || null,
    district_id: p.district_id || null,
    village_id: p.village_id || null,

    bank_account_number: p.bank_account_number || '',
    bank_account_name: p.bank_account_name || '',
    bank_name: p.bank_name || '',

    photo_url: p.photo_url || '',
    id_card_url: p.id_card_url || '',
    family_card_url: p.family_card_url || '',
    bank_book_url: p.bank_book_url || '',
    certificate_url: p.certificate_url || '',
    other_url: p.other_url || '',

    tanggal_terbit_ktp: p.tanggal_terbit_ktp || '',
    tanggal_terbit_kk: p.tanggal_terbit_kk || '',
  }
}

/**
 * VALIDASI NIK (format, extract DOB/gender, cek konflik ke server)
 * —> dipanggil lebih dulu sebelum onSearchNik / searchExistingParticipantByNik
 */
const validateNik = async () => {
  nikError.value = ''
  fieldErrors.value['participant.nik'] = ''

  const nikRaw = form.value.participant.nik || ''
  const nik = nikRaw.replace(/\D/g, '')

  if (!nik) {
    nikError.value = 'NIK wajib diisi.'
    fieldErrors.value['participant.nik'] = nikError.value
    return false
  }

  if (nik.length !== 16) {
    nikError.value = 'NIK harus 16 digit.'
    fieldErrors.value['participant.nik'] = nikError.value
    return false
  }

  const result = extractBirthdateFromNik(nik)
  if (!result) {
    nikError.value =
      'NIK tidak valid atau tanggal lahir tidak dapat dibaca dari NIK.'
    fieldErrors.value['participant.nik'] = nikError.value
    return false
  }

  // isi otomatis tanggal lahir & gender dari NIK
  form.value.participant.date_of_birth = result.dateOfBirth
  form.value.participant.gender = result.gender

  // kalau event belum dipilih, cukup sampai sini
  if (!eventId.value) {
    return true
  }

  try {
    isNikChecking.value = true

    const res = await axios.get('/api/v1/check-nik', {
      params: {
        nik,
        event_id: eventId.value,
        participant_id: form.value.participant.id || null,
        province_id: form.value.participant.province_id,
        regency_id: form.value.participant.regency_id,
        district_id: form.value.participant.district_id,
        village_id: form.value.participant.village_id,
      },
    })

    if (res.data.conflict) {
      // contoh: "NIK ini sudah terdaftar pada cabang yang lain."
      nikError.value = res.data.message || 'NIK konflik dengan peserta lain.'
      fieldErrors.value['participant.nik'] = nikError.value
      return false
    }

    nikError.value = ''
    fieldErrors.value['participant.nik'] = ''
    return true
  } catch (e) {
    console.error('Gagal cek NIK ke server:', e)
    nikError.value = 'Gagal melakukan validasi NIK ke server.'
    fieldErrors.value['participant.nik'] = nikError.value
    return false
  } finally {
    isNikChecking.value = false
  }
}

/**
 * Cari peserta di bank data berdasarkan NIK.
 * Catatan: TIDAK mengubah nikError/fieldErrors supaya
 * pesan konflik dari validateNik tidak hilang.
 */
const searchExistingParticipantByNik = async () => {
  // saat edit, kita tidak auto-override dari bank data
  if (isEdit.value) return

  const nikRaw = form.value.participant.nik || ''
  const nik = nikRaw.replace(/\D/g, '')

  // asumsi di titik ini sudah lewat validateNik (16 digit, dsb.)
  if (!nik || nik.length !== 16) {
    return
  }

  try {
    isNikChecking.value = true

    // backend: GET /api/v1/participants/search-by-nik?nik=...
    const res = await axios.get('/api/v1/participants/search-by-nik', {
      params: { nik },
    })

    const p = res.data.data || res.data

    if (p && p.id) {
      await prefillFormFromParticipant(p)

      Swal.fire({
        icon: 'success',
        title: 'Data peserta ditemukan',
        text: `Data ${p.full_name} telah dimuat ke formulir.`,
      })
    } else {
      Swal.fire({
        icon: 'info',
        title: 'Tidak ditemukan',
        text: 'NIK ini belum ada di bank data peserta. Silakan isi biodata secara manual.',
      })
    }
  } catch (e) {
    console.error('Gagal mencari peserta by NIK:', e)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal mencari peserta berdasarkan NIK.',
    })
  } finally {
    isNikChecking.value = false
  }
}

/**
 * Tombol "Cari" di samping NIK
 * —> SELALU validasi NIK dulu.
 * Jika validateNik() menemukan konflik, tidak akan lanjut prefill,
 * sehingga pesan "NIK ini sudah terdaftar..." tetap muncul.
 */
const onSearchNik = async () => {
  // Tombol Cari tidak dipakai saat edit
  if (isEdit.value) return

  const okNik = await validateNik()
  if (!okNik) return

  await searchExistingParticipantByNik()
}


// cek ke server secara debounce saat NIK 16 digit & event ada
const debouncedNikCheck = useDebounceFn(async () => {
  // ❌ Jangan validate otomatis kalau sedang edit
  if (isEdit.value) return

  const nikRaw = form.value.participant.nik || ''
  const nik = nikRaw.replace(/\D/g, '')

  if (!nik || nik.length !== 16 || !eventId.value) return

  await validateNik()
}, 600)


// update DOB & gender dari NIK setiap perubahan + trigger debounced check
watch(
  () => form.value.participant.nik,
  (newNik) => {
    // ⛔ Saat mode edit, jangan apa-apa walaupun nilai NIK di-set dari item
    if (isEdit.value) return

    if (!newNik) {
      nikError.value = ''
      fieldErrors.value['participant.nik'] = ''
      form.value.participant.date_of_birth = ''
      form.value.participant.gender = ''
      return
    }

    const result = extractBirthdateFromNik(newNik)
    if (!result) {
      // kalau parsing gagal, jangan isi DOB/gender tapi juga jangan langsung kasih error,
      // biar validateNik / debounce yang handle
      return
    }

    form.value.participant.date_of_birth = result.dateOfBirth
    form.value.participant.gender = result.gender
    nikError.value = ''
    fieldErrors.value['participant.nik'] = ''

    debouncedNikCheck()
  }
)


/**
 * BLUR pada kolom NIK
 * —> validasi dulu, kalau OK dan bukan edit mode, baru coba prefill bank data.
 */
const onNikBlur = async () => {
  // ❌ Kalau lagi edit, jangan validate ke server
  if (isEdit.value) return

  if (!form.value.participant.nik) {
    nikError.value = ''
    fieldErrors.value['participant.nik'] = ''
    return
  }

  if (debouncedNikCheck.cancel) {
    debouncedNikCheck.cancel()
  }

  const okNik = await validateNik()
  if (!okNik) return

  if (!isEdit.value) {
    await searchExistingParticipantByNik()
  }
}




const resetForm = () => {
  // 🔒 matikan efek watcher wilayah selama reset
  isInitLocation.value = true
  try {
    // tab default ke Biodata
    activeTab.value = 'biodata'

    // mode form
    isEdit.value = false

    // isi default form
    form.value = {
      participant: {
        id: null,
        nik: '',
        full_name: '',
        phone_number: '',
        place_of_birth: '',
        date_of_birth: '',
        gender: '',
        education: 'SMA',
        address: '',

        province_id: null,
        regency_id: null,
        district_id: null,
        village_id: null,

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
      },

      event_participant: {
        id: null,
        event_id: eventId.value || null,
        participant_id: null,
        event_category_id: '',

        contingent: '',
        registration_status: 'bank_data',
        registration_notes: '',
        reregistration_status: 'not_yet',
        reregistration_notes: '',
      },
    }

    // reset error per field (frontend validation)
    fieldErrors.value = {
      // participant
      'participant.nik': '',
      'participant.full_name': '',
      'participant.phone_number': '',
      'participant.place_of_birth': '',
      'participant.date_of_birth': '',
      'participant.gender': '',
      'participant.education': '',
      'participant.province_id': '',
      'participant.regency_id': '',
      'participant.district_id': '',
      'participant.village_id': '',
      'participant.address': '',
      'participant.bank_account_number': '',
      'participant.bank_account_name': '',
      'participant.bank_name': '',
      'participant.tanggal_terbit_ktp': '',
      'participant.tanggal_terbit_kk': '',

      // event_participant
      'event_participant.event_branch_id': '',
      'event_participant.event_category_id': '',
      'event_participant.registration_status': '',
    }

    nikError.value = ''
    ageStatus.value = null
    ageMessage.value = ''

    resetFiles()
  } finally {
    // 🔓 aktifkan lagi watcher
    isInitLocation.value = false
  }
}





// ==================================================
// SIMPAN (Participant + EventParticipant)
// ==================================================
const submitForm = async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum mengelola peserta.',
      'warning'
    )
    return
  }

  // VALIDASI FRONTEND
  if (!validateAllFields()) {
    Swal.fire(
      'Validasi gagal',
      'Periksa kembali kolom yang diberi tanda merah.',
      'warning'
    )
    return
  }

  await validateAgeForGroup();
  if(ageStatus.value == 'invalid') {
    Swal.fire(
      'Validasi gagal',
      'Umur tidak memenuhi syarat untuk golongan ini',
      'warning'
    )
    return
  }

  isSubmitting.value = true

  try {
    const payload = {
      participant: {
        id: form.value.participant.id || null,

        nik: form.value.participant.nik,
        full_name: form.value.participant.full_name,
        phone_number: form.value.participant.phone_number,
        place_of_birth: form.value.participant.place_of_birth,
        date_of_birth: form.value.participant.date_of_birth,
        gender: form.value.participant.gender,
        education: form.value.participant.education,
        address: form.value.participant.address,

        province_id: form.value.participant.province_id,
        regency_id: form.value.participant.regency_id,
        district_id: form.value.participant.district_id,
        village_id: form.value.participant.village_id,

        bank_account_number: form.value.participant.bank_account_number,
        bank_account_name: form.value.participant.bank_account_name,
        bank_name: form.value.participant.bank_name,

        tanggal_terbit_ktp: form.value.participant.tanggal_terbit_ktp,
        tanggal_terbit_kk: form.value.participant.tanggal_terbit_kk,
      },

      event_participant: {
        id: form.value.event_participant.id || null,

        event_id: eventId.value,
        // participant_id boleh dikosongkan, backend akan isi dari hasil save participant
        participant_id: form.value.participant.id || null,

        event_branch_id: form.value.event_participant.event_branch_id,
        event_group_id: form.value.event_participant.event_group_id,
        event_category_id: form.value.event_participant.event_category_id,

        contingent: form.value.event_participant.contingent,
        registration_status: form.value.event_participant.registration_status,
        registration_notes: form.value.event_participant.registration_notes,
        reregistration_status: form.value.event_participant.reregistration_status,
        reregistration_notes: form.value.event_participant.reregistration_notes,
      },
    }

    // ====== FormData untuk kirim payload + file ======
    const fd = new FormData()

    // kirim participant & event_participant sebagai JSON string
    fd.append('participant', JSON.stringify(payload.participant))
    fd.append('event_participant', JSON.stringify(payload.event_participant))

    // kirim lampiran
    attachmentFields.forEach((field) => {
        if (files.value[field]) {
        // file baru diupload di frontend
        fd.append(field, files.value[field])
        } else if (form.value.participant[field]) {
        // kalau sedang edit, kirim URL/path lama (biar backend tau tak dihapus)
        fd.append(field, form.value.participant[field])
        }
    })

   // Panggil endpoint gabungan
    await axios.post('/api/v1/save-event-participants', fd, {
        headers: {
        'Content-Type': 'multipart/form-data',
        },
    })

    Swal.fire(
      'Berhasil',
      'Data peserta event berhasil disimpan.',
      'success'
    )

    $('#participantModal').modal('hide')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menyimpan peserta event:', error)
    let message = 'Terjadi kesalahan saat menyimpan data.'

    if (error.response?.status === 422) {
        const errors = error.response.data?.errors || {}

        // mapping error field biasa
        Object.keys(errors).forEach((key) => {
        const msgs = errors[key]
        if (!Array.isArray(msgs) || !msgs.length) return

        // error untuk lampiran
        if (attachmentFields.includes(key)) {
            fileErrors.value[key] = msgs[0]
        } else {
            // contoh backend pakai "participant.nik", "event_participant.registration_status", etc.
            fieldErrors.value[key] = msgs[0]
        }
        })

        // kalau ada error file, pindahkan ke tab lampiran
        if (Object.values(fileErrors.value).some((msg) => !!msg)) {
        activeTab.value = 'lampiran'
        } else {
        activeTab.value = 'biodata'
        }

        const firstKey = Object.keys(errors)[0]
        if (firstKey) {
        message = errors[firstKey][0]
        } else if (error.response.data?.message) {
        message = error.response.data.message
        }
    }

    Swal.fire('Gagal', message, 'error')
    } finally {
    isSubmitting.value = false
  }
}


// ==================================================
// HAPUS event_participant
// ==================================================
const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus Peserta Event?',
    text: `Yakin ingin menghapus peserta "${item.participant?.full_name}" dari event ini?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/event-participants/${item.id}`)
    Swal.fire('Terhapus', 'Peserta event berhasil dihapus.', 'success')
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal menghapus peserta event:', error)
    Swal.fire('Gagal', 'Gagal menghapus peserta event.', 'error')
  }
}

// ==================================================
// MODAL HELPERS
// ==================================================
const openCreateModal = async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event sebelum mengelola peserta.',
      'warning'
    )
    return
  }

  resetForm()
  isEdit.value = false
  form.value = emptyForm()
  form.value.event_participant.event_id = eventId.value
  nikSearchResult.value = null
  nikDobHint.value = ''
  nikGenderHint.value = ''
  activeTab.value = 'biodata'

  // inisialisasi wilayah (default event) + load sampai desa
  // await initRegionForForm()
  // inisialisasi wilayah tanpa trigger watcher
  isInitLocation.value = true
  try {
    await applyEventRegionToForm()

    // 🔁 kalau event level district / ada district_id default → langsung load desa
    if (form.value.participant.district_id) {
      await fetchVillageOptions()
    }
  } finally {
    isInitLocation.value = false
  }

  $('#participantModal').modal('show')
}


const openEditModal = async (item) => {
  // Edit biodata (tab biodata)
resetForm()
  isEdit.value = true
  activeTab.value = 'biodata'

  form.value = {
    participant: {
      id: item.participant?.id,
      nik: item.participant?.nik,
      full_name: item.participant?.full_name,
      phone_number: item.participant?.phone_number,
      place_of_birth: item.participant?.place_of_birth,
      date_of_birth: item.participant?.date_of_birth,
      gender: item.participant?.gender,
      education: item.participant?.education || 'SMA',
      address: item.participant?.address,
      province_id: item.participant?.province_id || null,
      regency_id: item.participant?.regency_id || null,
      district_id: item.participant?.district_id || null,
      village_id: item.participant?.village_id || null,
      bank_account_number: item.participant?.bank_account_number || '',
      bank_account_name: item.participant?.bank_account_name || '',
      bank_name: item.participant?.bank_name || '',
      tanggal_terbit_ktp: item.participant?.tanggal_terbit_ktp || '',
      tanggal_terbit_kk: item.participant?.tanggal_terbit_kk || '',
      photo_url: item.participant?.photo_url || '',
      id_card_url: item.participant?.id_card_url || '',
      family_card_url: item.participant?.family_card_url || '',
      bank_book_url: item.participant?.bank_book_url || '',
      certificate_url: item.participant?.certificate_url || '',
      other_url: item.participant?.other_url || '',
    },
    event_participant: {
      id: item.id,
      event_id: item.event_id,
      event_branch_id: item.event_branch_id || item.event_branch?.id || '',
      event_group_id: item.event_group_id,
      event_category_id: item.event_category_id,
      contingent: item.contingent,
      registration_status: item.registration_status,
      registration_notes: item.registration_notes,
      reregistration_status: item.reregistration_status,
      reregistration_notes: item.reregistration_notes,
    },
  }

  const rowRegion = {
    province_id: item.participant?.province_id,
    regency_id: item.participant?.regency_id,
    district_id: item.participant?.district_id,
    village_id: item.participant?.village_id,
  }

 // inisialisasi wilayah sesuai peserta + load sampai desa
  await initRegionForForm(rowRegion)

  nikSearchResult.value = 'found'
  nikDobHint.value = ''
  nikGenderHint.value = ''
  $('#participantModal').modal('show')
}

// khusus tombol "Edit Lampiran"
const openLampiranModal = async (item) => {
  await openEditModal(item)
  activeTab.value = 'lampiran'
}

// ==================================================
// PAGINATION
// ==================================================
const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchItems(page)
}


// ==================================================
// WILAYAH: APPLY EVENT LEVEL KE FORM
// ==================================================
const {
  fetchProvinceOptions,
  fetchRegencyOptions,
  fetchDistrictOptions,
  fetchVillageOptions,
  applyEventRegionToForm,
} = createRegionHelpers({
  form,
  provinceOptions,
  regencyOptions,
  districtOptions,
  villageOptions,
  isPrivileged,
  currentUser,
  eventInfo,
  isLoadingRegencies,
  isLoadingDistricts,
  isLoadingVillages,
  disabledDistrict,
})

/**
 * Inisialisasi wilayah form (baik mode create maupun edit)
 * rowRegion optional:
 *   - null  → pakai default event / user (di-handle applyEventRegionToForm)
 *   - { province_id, regency_id, district_id, village_id } → pakai wilayah peserta
 */
async function initRegionForForm(rowRegion = null) {
  isInitLocation.value = true
  try {
    if (!provinceOptions.value.length) {
      await fetchProvinceOptions()
    }
    await applyEventRegionToForm(rowRegion) // biar helper yang cascade
    // tidak usah fetchVillageOptions() lagi di sini
  } finally {
    isInitLocation.value = false
  }
}






// ==================================================
// Validasi umur vs max_age eventGroups
// ==================================================

const ageStatus = ref(null) // 'valid' | 'invalid' | null
const ageMessage = ref('')

// eventGroups sudah ada dari API simple() → pastikan setiap item punya max_age
// contoh struktur di backend: event_groups: [ { id, group_name, max_age, ... } ]

const validateAgeForGroup = () => {
    ageStatus.value = null
    ageMessage.value = ''

    const dobStr = form.value.participant.date_of_birth
    const categoryIdRaw = form.value.event_participant.event_category_id


    // kalau DOB atau kategori belum dipilih, tidak perlu validasi
    if (!dobStr || !categoryIdRaw) return

    const categoryId = Number(categoryIdRaw)

    // eventCategories bisa berupa ref([]) atau array biasa
    const categories = Array.isArray(eventCategories?.value)
        ? eventCategories.value
        : Array.isArray(eventCategories)
        ? eventCategories
        : []

    const selectedCategory =
        categories.find(cat => Number(cat.id) === categoryId) || null

    if (!selectedCategory) return
    console.log('selectedCategory: ' + selectedCategory);


    const groupId = selectedCategory.group_id
    if (!groupId) return
    console.log('groupId: ' + groupId);


    // eventGroups juga bisa ref([]) atau array
    const groups = Array.isArray(eventGroups?.value)
        ? eventGroups.value
        : Array.isArray(eventGroups)
        ? eventGroups
        : []

    const group =
        groups.find(g => Number(g.id) === Number(groupId)) || null
    console.log('group');
    console.log(group);

    if (!group || group.max_age == null) return
    console.log('group and group max age');


    const dob = new Date(dobStr)
    if (isNaN(dob.getTime())) return

    // pakai age_limit_date / tanggal_batas_umur event kalau ada, fallback ke hari ini
    const refStr =
        eventInfo.value?.age_limit_date ||
        eventData.value?.tanggal_batas_umur ||
        null

    const refDate = refStr ? new Date(refStr) : new Date()
    if (isNaN(refDate.getTime())) return

    // hitung umur dalam tahun (dibulatkan ke bawah)
    let age = refDate.getFullYear() - dob.getFullYear()
    const mDiff = refDate.getMonth() - dob.getMonth()
    if (mDiff < 0 || (mDiff === 0 && refDate.getDate() < dob.getDate())) {
        age--
    }

    const maxAge = Number(group.max_age)
    if (!Number.isFinite(maxAge)) return

    // 🔴 aturan baru: umur harus DI BAWAH max_age
    // contoh: max_age = 18 → umur 18 TIDAK BOLEH, 17 tahun 11 bulan 31 hari MASIH BOLEH
    if (age < maxAge) {
        ageStatus.value = 'valid'
        ageMessage.value =
        `Umur memenuhi syarat untuk golongan ini (harus di bawah ${maxAge} tahun). ` +
        `Umur peserta ${age} tahun.`

        // kalau sebelumnya ada error karena umur, bersihkan
        if (
        fieldErrors.value['participant.date_of_birth'] &&
        fieldErrors.value['participant.date_of_birth'].startsWith('Umur')
        ) {
        fieldErrors.value['participant.date_of_birth'] = ''
        }
    } else {
        ageStatus.value = 'invalid'
        ageMessage.value =
        `Umur tidak memenuhi syarat untuk golongan ini (harus di bawah ${maxAge} tahun). ` +
        `Umur peserta ${age} tahun.`

        // tandai DOB sebagai invalid
        fieldErrors.value['participant.date_of_birth'] = ageMessage.value
    }
}

// ==================================================
// MUTASI WILAYAH
// ==================================================
const isMutasiSubmitting = ref(false)

const mutasiForm = ref({
  id: null,           // <-- ini akan diisi participant.id
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

const openMutasiModal = async (item) => {
  // item = event_participant (punya nested participant)
  mutasiErrors.value = {
    province_id: '',
    regency_id: '',
    district_id: '',
  }

  mutasiForm.value = {
    id: item.participant?.id || null,                        // <-- pakai participant.id
    province_id: item.participant?.province_id || '',
    regency_id: item.participant?.regency_id || '',
    district_id: item.participant?.district_id || '',
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
      `/api/v1/event-participants/${mutasiForm.value.id}/mutasi-wilayah`,
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

    // reload list event_participants
    fetchItems(meta.value.current_page)
  } catch (error) {
    console.error('Gagal mutasi peserta:', error)
    const msg = error.response?.data?.message || 'Gagal memindahkan peserta.'
    Swal.fire({ icon: 'error', title: 'Error', text: msg })
  } finally {
    isMutasiSubmitting.value = false
  }
}

// ==================================================
// SELECTION & BULK REGISTER
// ==================================================

const selectedParticipantIds = ref([])
const isRegistering = ref(false)

// peserta event yang sedang terpilih (berdasarkan selectedParticipantIds)
const selectedParticipants = computed(() =>
  items.value.filter(ep => selectedParticipantIds.value.includes(ep.id))
)

// apakah di halaman ini semua peserta sudah tercentang
const isAllSelected = computed(() => {

  if (isDevelopmentMode.value) {
    return items.value.length &&
      items.value.every(ep =>
        selectedParticipantIds.value.includes(ep.id)
      )
  }

  if (!items.value.length) return false

  // hanya peserta yang BISA dipilih (bank_data + lampiran >=80) yang dihitung
  const selectableIds = items.value
    .filter(ep =>
      (ep.registration_status || '').toLowerCase() === 'bank_data' &&
      (ep.participant?.lampiran_completion_percent || 0) >= 80
    )
    .map(ep => ep.id)

  if (!selectableIds.length) return false

  return selectableIds.every(id => selectedParticipantIds.value.includes(id))
})

const toggleSelectAll = (event) => {
  const checked = event.target.checked

  // 🧪 DEVELOPMENT MODE → semua bisa dipilih
  const selectableIds = isDevelopmentMode.value
    ? items.value.map(ep => ep.id)
    : items.value
        .filter(ep =>
          ['bank_data', 'need_revision'].includes(
            (ep.registration_status || '').toLowerCase()
          ) &&
          (ep.participant?.lampiran_completion_percent || 0) >= 80
        )
        .map(ep => ep.id)


  if (checked) {
    // gabungkan id yang bisa dipilih di halaman ini dengan yang sudah ada (tanpa duplikat)
    selectedParticipantIds.value = Array.from(
      new Set([...selectedParticipantIds.value, ...selectableIds])
    )
  } else {
    // hapus semua id di halaman ini dari selection
    const pageIds = items.value.map(ep => ep.id)
    selectedParticipantIds.value = selectedParticipantIds.value.filter(
      id => !pageIds.includes(id)
    )
  }
}


const openRegisterModal = () => {
  if (!selectedParticipantIds.value.length) return
  $('#registerParticipantsModal').modal('show')
}

const submitRegisterParticipants = async () => {
  if (!selectedParticipantIds.value.length || !eventId.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Belum ada peserta dipilih',
      text: 'Silakan checklist peserta berstatus BANKDATA yang akan didaftarkan.',
    })
    return
  }

  const selected = selectedParticipants.value

  const allowedStatuses = ['bank_data', 'need_revision']

  // peserta yang BUKAN bank_data / need_revision
  const invalidSelected = selected.filter(
    ep => !allowedStatuses.includes((ep.registration_status || '').toLowerCase())
  )

  // peserta yang boleh didaftarkan (bank_data atau need_revision)
  const bankdataParticipants = selected.filter(
    ep => allowedStatuses.includes((ep.registration_status || '').toLowerCase())
  )

  if (!bankdataParticipants.length) {
    Swal.fire({
      icon: 'warning',
      title: 'Tidak ada peserta yang valid',
      text: 'Hanya peserta dengan status "bank_data" atau "need_revision" yang dapat didaftarkan.',
    })
    return
  }


  if (invalidSelected.length) {
    const names = invalidSelected
      .slice(0, 5)
      .map(ep => `- ${ep.participant?.full_name} (${registrationStatusLabel(ep.registration_status)})`)
      .join('\n')

    Swal.fire({
      icon: 'warning',
      title: 'Sebagian peserta tidak bisa didaftarkan',
      text: 'Hanya peserta dengan status "bank_data" yang akan diproses. Periksa kembali daftar peserta.',
      footer: `<pre style="text-align:left;margin:0;">${names}${invalidSelected.length > 5 ? '\n... dan lainnya' : ''}</pre>`,
    })
  }

  const idsToRegister = bankdataParticipants.map(ep => ep.id)

  const confirmResult = await Swal.fire({
    icon: 'question',
    title: `Daftarkan ${idsToRegister.length} peserta?`,
    text: 'Status pendaftaran akan diubah menjadi "process".',
    showCancelButton: true,
    confirmButtonText: 'Ya, daftarkan',
    cancelButtonText: 'Batal',
  })

  if (!confirmResult.isConfirmed) {
    return
  }

  isRegistering.value = true

  try {
    // ⚠️ endpoint disesuaikan untuk EVENT PARTICIPANTS
    await axios.post('/api/v1/event-participants/bulk-register', {
      ids: idsToRegister,          // id EventParticipant
      event_id: eventId.value,
      registration_status: 'process',
    })

    $('#registerParticipantsModal').modal('hide')
    selectedParticipantIds.value = []
    await fetchItems(meta.value.current_page)

    Swal.fire({
      icon: 'success',
      title: 'Pendaftaran berhasil',
      text: `Sebanyak ${idsToRegister.length} peserta berstatus BANKDATA berhasil dipindahkan ke status "process".`,
    })
  } catch (error) {
    console.error('Gagal mendaftarkan peserta:', error)
    const msg = error.response?.data?.message || 'Gagal mendaftarkan peserta.'
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: msg,
    })
  } finally {
    isRegistering.value = false
  }
}




// trigger otomatis kalau DOB atau Golongan berubah
watch(
  () => [
    form.value.participant.date_of_birth,
    form.value.event_participant.event_category_id,
  ],
  () => {
    console.log('ini jalan');
    validateAgeForGroup()
  }
)


// ==================================================
// WATCHERS
// ==================================================
watch(
  () => form.value.participant.province_id,
  () => {
    if (isInitLocation.value) return
    fetchRegencyOptions()
  }
)

watch(
  () => form.value.participant.regency_id,
  () => {
    if (isInitLocation.value) return
    fetchDistrictOptions()
  }
)

watch(
  () => form.value.participant.district_id,
  () => {
    if (isInitLocation.value) return
    fetchVillageOptions()
  }
)

// search debounce
watch(
  () => search.value,
  useDebounceFn(() => fetchItems(1), 400)
)

// perPage change
watch(
  () => perPage.value,
  () => {
    fetchItems(1)
  }
)

// filter status
watch(
  () => ({ ...filters.value }),
  () => {
    fetchItems(1)
  }
)

// kalau eventId baru ter-set setelah halaman dibuka
watch(
  () => eventId.value,
  (val) => {
    if (val) {
      fetchEventMasterData()
      fetchItems(1)
    }
  }
)

// ==================================================
// MOUNTED
// ==================================================
onMounted(async () => {
  if (!eventId.value) {
    Swal.fire(
      'Event belum dipilih',
      'Silakan pilih event melalui Portal Event terlebih dahulu.',
      'info'
    )
  } else {
    await fetchProvinceOptions()
    fetchEventMasterData()
    fetchItems()
  }

  $('#participantModal').on('hidden.bs.modal', () => {
    resetForm()
  })
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

.gap-2 {
  gap: .5rem;
}

</style>
