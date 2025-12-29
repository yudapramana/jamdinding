<template>
  <div class="modal fade" id="reRegisterTeamModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header">
          <div>
            <h5 class="modal-title">Proses Daftar Ulang Tim</h5>
            <div class="text-sm text-muted">
              Verifikasi kehadiran dan kelengkapan tim secara kolektif.
            </div>
          </div>
          <button class="close" data-dismiss="modal" :disabled="loading">
            <span>&times;</span>
          </button>
        </div>

        <!-- BODY -->
        <div class="modal-body" v-if="team">

          <!-- RINGKASAN TIM -->
          <div class="card mb-3">
            <div class="card-body py-3">
              <div class="d-flex justify-content-between flex-wrap">
                <div>
                  <h6 class="mb-1">{{ team.display_name }}</h6>
                  <div class="text-sm text-muted">
                    Kontingen:
                    <span class="badge badge-light border">
                      {{ team.contingent || '-' }}
                    </span>
                  </div>
                  <div class="text-sm text-muted">
                    Cabang/Golongan:
                    <span class="badge badge-info">
                      {{ team.event_group?.full_name || '-' }}
                    </span>
                  </div>
                  <div class="text-sm text-muted">
                    Jumlah anggota: {{ team.members_count }}
                  </div>
                </div>

                <div class="text-sm">
                  Status sekarang:
                  <span class="badge" :class="badgeClass(team.reregistration_status)">
                    {{ team.reregistration_status }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- DAFTAR ANGGOTA -->
          <div class="card mb-3">
            <div class="card-header py-2">
              <strong>Daftar Anggota Tim</strong>
            </div>
            <div class="card-body p-2">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th style="width: 40px">#</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Umur</th>
                    <th>Status Awal</th>
                    <th>Status Daftar Ulang</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(ep, idx) in team.participants"
                    :key="ep.id"
                  >
                    <td class="text-center">{{ idx + 1 }}</td>

                    <td>
                      {{ ep.participant?.full_name || '-' }}
                    </td>

                    <td>
                      {{ ep.participant?.nik || '-' }}
                    </td>

                    <td class="text-sm">
                      {{ ep.age_year }}T
                      {{ ep.age_month }}B
                      {{ ep.age_day }}H
                    </td>

                    <td>
                      <span class="badge badge-success">
                        {{ ep.registration_status }}
                      </span>
                    </td>

                    <td>
                      <span
                        class="badge"
                        :class="badgeClass(ep.reregistration_status)"
                      >
                        {{ ep.reregistration_status || 'not_yet' }}
                      </span>
                    </td>
                  </tr>

                  <tr v-if="!team.participants || team.participants.length === 0">
                    <td colspan="6" class="text-center text-muted py-3">
                      Tidak ada anggota
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- CATATAN -->
          <div class="form-group">
            <label class="text-sm mb-1">Catatan Verifikator</label>
            <textarea
              v-model="note"
              class="form-control"
              rows="3"
              placeholder="Catatan daftar ulang tim"
            />
          </div>

          <!-- KEPUTUSAN -->
          <div class="form-group">
            <label class="text-sm mb-1">Keputusan</label>
            <select v-model="decision" class="form-control">
              <option value="">-- pilih keputusan --</option>
              <option value="verified">ACC / Terima</option>
              <option value="rejected">Tolak</option>
            </select>
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button
            class="btn btn-light"
            data-dismiss="modal"
            :disabled="loading"
          >
            Tutup
          </button>
          <button
            class="btn btn-primary"
            :disabled="loading"
            @click="submit"
          >
            Submit
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({
  eventTeam: Object,
})

const emit = defineEmits(['updated'])

const team = computed(() => props.eventTeam || null)

const loading = ref(false)
const decision = ref('')
const note = ref('')

const submit = async () => {
  if (!decision.value) {
    Swal.fire(
      'Pilih keputusan',
      'Silakan pilih ACC atau Tolak.',
      'warning'
    )
    return
  }

  loading.value = true
  try {
    await axios.post(
      `/api/v1/event-teams/${team.value.id}/re-registration`,
      {
        reregistration_status: decision.value,
        reregistration_notes: note.value || null,
      }
    )

    $('#reRegisterTeamModal').modal('hide')

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Daftar ulang tim berhasil diproses.',
      timer: 1500,
      showConfirmButton: false,
    })

    emit('updated')

  } catch (e) {
    Swal.fire(
      'Gagal',
      e.response?.data?.message || 'Terjadi kesalahan.',
      'error'
    )
  } finally {
    loading.value = false
  }
}

const badgeClass = (status) => {
  if (status === 'verified') return 'badge-success'
  if (status === 'rejected') return 'badge-danger'
  return 'badge-secondary'
}

watch(() => props.eventTeam, () => {
  decision.value = ''
  note.value = ''
})
</script>
