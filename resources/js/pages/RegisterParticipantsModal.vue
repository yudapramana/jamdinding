<template>
  <div
    class="modal fade"
    id="registerParticipantsModal"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header py-2">
          <h5 class="modal-title">
            <i class="fas fa-check-double mr-2"></i>
            Konfirmasi Pendaftaran Peserta
          </h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>

        <!-- BODY -->
        <div class="modal-body">
          <p class="mb-2">
            Anda akan mendaftarkan
            <strong>{{ participants.length }}</strong>
            peserta untuk event ini dan mengubah
            <code>registration_status</code>
            menjadi
            <span class="badge badge-info">process</span>.
          </p>

          <div v-if="participants.length">
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
                  v-for="(ep, idx) in participants"
                  :key="ep.id"
                >
                  <td>{{ idx + 1 }}</td>
                  <td>{{ ep.participant?.full_name }}</td>
                  <td class="text-monospace">
                    {{ ep.participant?.nik }}
                  </td>
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

        <!-- FOOTER -->
        <div class="modal-footer py-2">
          <button
            type="button"
            class="btn btn-sm btn-secondary"
            data-dismiss="modal"
            :disabled="isSubmitting"
          >
            Batal
          </button>
          <button
            type="button"
            class="btn btn-sm btn-success"
            :disabled="!participants.length || isSubmitting"
            @click="onConfirm"
          >
            <i
              v-if="isSubmitting"
              class="fas fa-spinner fa-spin mr-1"
            ></i>
            <i
              v-else
              class="fas fa-check mr-1"
            ></i>
            Ya, Daftarkan
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  participants: {
    type: Array,
    default: () => [],
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['confirm'])

const onConfirm = () => {
  emit('confirm')
}
</script>
