  <template>
    <div class="modal fade" id="membersModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

          <!-- HEADER -->
          <div class="modal-header">
            <h5 class="modal-title">
              {{ panel?.name || '-' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <!-- BODY -->
          <div class="modal-body">

            <!-- SEARCH -->
            <div class="mb-3">
              <label class="font-weight-bold mb-1">Cari Hakim</label>
              <input
                v-model="search"
                type="text"
                class="form-control"
                placeholder="Ketik nama hakim..."
              />
            </div>

            <!-- SEARCH RESULT -->
            <div v-if="filteredJudges.length" class="mb-4">
              <div
                v-for="j in filteredJudges"
                :key="j.id"
                class="d-flex justify-content-between align-items-center border rounded p-2 mb-2"
              >
                <div>
                  <strong>{{ j.user?.name }}</strong>
                  <div class="text-muted text-sm">
                    {{ j.judge_code || '—' }}
                  </div>
                </div>

                <button
                  class="btn btn-outline-primary btn-sm"
                  @click="addMember(j)"
                  :disabled="isAlreadyMember(j.id)"
                >
                  <i class="fas fa-plus mr-1"></i> Tambah
                </button>
              </div>
            </div>

            <!-- MEMBERS TABLE -->
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Daftar Hakim Cabang</strong>
                <span class="text-muted text-sm">
                  Pilih 1 ketua majelis (opsional)
                </span>
              </div>

              <table class="table table-bordered">
                <thead class="thead-light">
                  <tr>
                    <th>Hakim</th>
                    <th style="width:120px" class="text-center">Ketua</th>
                    <th style="width:80px" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="members.length === 0">
                    <td colspan="3" class="text-center text-muted">
                      Belum ada hakim ditambahkan
                    </td>
                  </tr>

                  <tr v-for="m in members" :key="m.master_judge_id">
                    <td>
                      <strong>{{ m.name }}</strong>
                    </td>
                    <td class="text-center">
                      <input
                        type="radio"
                        name="chief"
                        :checked="chiefId === m.master_judge_id"
                        @change="chiefId = m.master_judge_id"
                      />
                    </td>
                    <td class="text-center">
                      <button
                        class="btn btn-sm btn-outline-danger"
                        @click="removeMember(m.master_judge_id)"
                      >
                        ✕
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>

          <!-- FOOTER -->
          <div class="modal-footer">
            <button
              class="btn btn-secondary"
              data-dismiss="modal"
            >
              Tutup
            </button>
            <button
              class="btn btn-primary"
              @click="saveAll"
              :disabled="isSubmitting"
            >
              <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
              <i v-else class="fas fa-save mr-1"></i>
              Simpan
            </button>
          </div>

        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, computed } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'

  /* ================= STATE ================= */
  const panel = ref(null)
  const search = ref('')
  const allJudges = ref([])
  const members = ref([])
  const chiefId = ref(null)
  const isSubmitting = ref(false)
  const isLoading = ref(false)

  /* ================= EMIT ================= */
  const emit = defineEmits(['updated'])

  /* ================= FETCH ================= */
  const fetchJudges = async () => {
    const res = await axios.get('/api/v1/master-judges', {
      params: { simple: 1 },
    })
    allJudges.value = res.data.data || []
  }

  const fetchMembers = async () => {
    const res = await axios.get(
      `/api/v1/event-judge-panels/${panel.value.id}/members`
    )

    members.value = (res.data.data || []).map(m => ({
      master_judge_id: m.master_judge_id,
      name: m.master_judge.user.name,
      is_chief: m.is_chief,
    }))

    const chief = members.value.find(m => m.is_chief)
    chiefId.value = chief ? chief.master_judge_id : null
  }

  /* ================= OPEN MODAL (AWAITABLE) ================= */
  const open = async (panelData) => {
    panel.value = panelData
    search.value = ''
    isLoading.value = true

    try {
      await Promise.all([
        fetchJudges(),
        fetchMembers(),
      ])

      $('#membersModal').modal('show')
    } catch (e) {
      Swal.fire('Gagal', 'Gagal memuat data hakim.', 'error')
    } finally {
      isLoading.value = false
    }
  }

  /* ================= COMPUTED ================= */
  const filteredJudges = computed(() => {
    if (!search.value) return []
    return allJudges.value.filter(j =>
      j.user?.name.toLowerCase().includes(search.value.toLowerCase())
    )
  })

  const isAlreadyMember = (judgeId) => {
    return members.value.some(m => m.master_judge_id === judgeId)
  }

  /* ================= ACTIONS ================= */
  const addMember = (judge) => {
    members.value.push({
      master_judge_id: judge.id,
      name: judge.user.name,
      is_chief: false,
    })
  }

  const removeMember = (judgeId) => {
    members.value = members.value.filter(m => m.master_judge_id !== judgeId)
    if (chiefId.value === judgeId) chiefId.value = null
  }

  const saveAll = async () => {
    isSubmitting.value = true
    try {
      await axios.post(
        `/api/v1/event-judge-panels/${panel.value.id}/sync-members`,
        {
          members: members.value.map((m, i) => ({
            master_judge_id: m.master_judge_id,
            is_chief: chiefId.value === m.master_judge_id,
            order_number: i + 1,
          })),
        }
      )

      Swal.fire('Berhasil', 'Hakim cabang berhasil disimpan.', 'success')
      emit('updated')
      $('#membersModal').modal('hide')
    } catch {
      Swal.fire('Gagal', 'Gagal menyimpan hakim.', 'error')
    } finally {
      isSubmitting.value = false
    }
  }

  /* ================= EXPOSE ================= */
  defineExpose({ open })
  </script>

