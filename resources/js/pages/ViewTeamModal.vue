<template>
  <!-- Modal Lihat Data TIM -->
  <div class="modal fade" id="viewTeamModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header py-2">
          <h5 class="modal-title">
            <i class="fas fa-users mr-2"></i>
            Detail Tim
          </h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>

        <!-- BODY -->
        <div class="modal-body" v-if="team">

          <!-- =========================
               RINGKASAN TIM
          ========================== -->
          <div class="card mb-3 border shadow-sm">
            <div class="card-body py-3">
              <div class="row">

                <div class="col-md-8">
                  <h5 class="mb-1 font-weight-bold text-uppercase">
                    {{ team.team_name || team.display_name }}
                  </h5>

                  <div class="text-sm text-muted mb-1">
                    Cabang / Golongan:
                    <strong>{{ team.event_group?.full_name || '-' }}</strong>
                  </div>

                  <div class="text-sm text-muted mb-1">
                    Kategori:
                    <strong>{{ team.event_category?.full_name || '-' }}</strong>
                  </div>

                  <div class="text-sm text-muted">
                    Kontingen:
                    <span class="badge badge-light border">
                      {{ team.contingent || '-' }}
                    </span>
                  </div>
                </div>

                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                  <div class="mb-1">
                    Nomor Tim:
                    <div class="h5 font-weight-bold text-primary mb-0">
                      {{ team.participant_number || '-' }}
                    </div>
                  </div>

                  <div class="mb-1">
                    Status Daftar Ulang:
                    <span
                      class="badge"
                      :class="reregistrationBadgeClass(team.reregistration_status)"
                    >
                      {{ reregistrationStatusLabel(team.reregistration_status) }}
                    </span>
                  </div>

                  <div class="text-xs text-muted" v-if="team.reregistered_at">
                    Diproses:
                    {{ formatDateTime(team.reregistered_at) }}
                  </div>
                </div>

              </div>
            </div>

            <!-- Catatan jika ditolak -->
            <div
              v-if="team.reregistration_status === 'rejected'"
              class="border-top p-3"
            >
              <div class="alert alert-danger mb-0">
                <strong>
                  <i class="fas fa-exclamation-triangle mr-1"></i>
                  Tim Ditolak
                </strong>
                <div class="text-sm mt-1">
                  {{ team.reregistration_notes || 'Tidak ada catatan.' }}
                </div>
              </div>
            </div>
          </div>

          <!-- =========================
               ANGGOTA TIM
          ========================== -->
          <div class="card border shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
              <strong>
                Anggota Tim
              </strong>
              <span class="badge badge-light border">
                {{ team.participants?.length || 0 }} orang
              </span>
            </div>

            <div class="card-body p-2">

              <div
                v-for="(ep, idx) in team.participants"
                :key="ep.id || idx"
                class="border rounded p-3 mb-3"
              >
                <div class="row">

                  <!-- BIODATA -->
                  <div class="col-md-8">
                    <div class="font-weight-bold text-uppercase">
                      {{ ep.participant?.full_name || '-' }}
                    </div>

                    <div class="text-sm text-muted">
                      NIK:
                      <span class="text-monospace">
                        {{ ep.participant?.nik || '-' }}
                      </span>
                    </div>

                    <div class="text-sm text-muted">
                      TTL:
                      {{ ep.participant?.place_of_birth || '-' }},
                      {{ formatDate(ep.participant?.date_of_birth) }}
                      <span v-if="ep.age_year != null">
                        ({{ ep.age_year }}T {{ ep.age_month }}B {{ ep.age_day }}H)
                      </span>
                    </div>

                    <div class="text-sm text-muted">
                      JK:
                      <strong>
                        {{
                          ep.participant?.gender === 'MALE'
                            ? 'Laki-laki'
                            : 'Perempuan'
                        }}
                      </strong>
                    </div>

                    <div class="text-sm text-muted">
                      Alamat:
                      {{ ep.participant?.address || '-' }}
                    </div>
                  </div>

                  <!-- STATUS -->
                  <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    <div class="mb-1">
                      Status Registrasi:
                      <span class="badge badge-success">
                        VERIFIED
                      </span>
                    </div>

                    <div class="mb-1">
                      Status Daftar Ulang:
                      <span
                        class="badge"
                        :class="reregistrationBadgeClass(ep.reregistration_status)"
                      >
                        {{ reregistrationStatusLabel(ep.reregistration_status) }}
                      </span>
                    </div>

                    <div class="text-xs text-muted" v-if="ep.reregistered_at">
                      {{ formatDateTime(ep.reregistered_at) }}
                    </div>
                  </div>

                </div>
              </div>

              <div
                v-if="!team.participants || team.participants.length === 0"
                class="text-muted text-center py-3"
              >
                Tidak ada anggota tim.
              </div>

            </div>
          </div>

        </div>

        <!-- FOOTER -->
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
import {
  formatDate,
  formatDateTime,
  reregistrationBadgeClass,
  reregistrationStatusLabel,
} from './EventParticipantHelpers'

const props = defineProps({
  selectedTeam: {
    type: Object,
    default: null,
  },
})

const team = computed(() => props.selectedTeam)
</script>
