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
                            <!-- Prioritas: event_category.full_name > event_group.full_name > event_branch.full_name -->
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
                            <!-- contoh: Menengah / Putra -->
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
                            <!-- pakai contingent kalau ada, fallback ke district/regency/province -->
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
                        <!-- <tr>
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
                        </tr> -->
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