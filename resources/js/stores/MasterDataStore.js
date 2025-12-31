import axios from 'axios'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useStorage } from '@vueuse/core'
import { useAuthUserStore } from './AuthUserStore'

export const useMasterDataStore = defineStore('MasterDataStore', () => {

  const authUserStore = useAuthUserStore()

  /* =========================
   * STATE (PERSISTED)
   * ========================= */
  const eventStages     = useStorage('md:eventStages', [])
  const eventBranches   = useStorage('md:eventBranches', [])
  const eventGroups     = useStorage('md:eventGroups', [])
  const eventCategories = useStorage('md:eventCategories', [])

  const loading = ref(false)

  /* =========================
   * INTERNAL HELPER
   * ========================= */
  const fetchMaster = async (type, params = {}) => {
    if (!authUserStore.eventData?.id) return []

    // 🔎 Tentukan storage target
    const storageMap = {
      event_stages: eventStages,
      event_branches: eventBranches,
      event_groups: eventGroups,
      event_categories: eventCategories,
    }

    const target = storageMap[type]

    // ✅ Jika TANPA filter & data sudah ada → pakai cache
    const hasFilter = Object.keys(params).length > 0
    if (!hasFilter && target?.value?.length) {
      return target.value
    }

    loading.value = true
    try {
      const { data } = await axios.get('/api/v1/master', {
        params: {
          type,
          event_id: authUserStore.eventData.id,
          ...params,
        },
      })

      return data.data || []
    } finally {
      loading.value = false
    }
  }

  /* =========================
   * ACTIONS
   * ========================= */

  /* ---------- STAGES ---------- */
  const loadEventStages = async () => {
    eventStages.value = await fetchMaster('event_stages')
  }

  /* ---------- BRANCHES ---------- */
  const loadEventBranches = async () => {
    eventBranches.value = await fetchMaster('event_branches')
  }

  /* ---------- GROUPS ---------- */

  const loadAllEventGroups = async () => {
    eventGroups.value = await fetchMaster('event_groups')
  }

  const loadEventGroups = async (eventBranchId = null) => {
    eventGroups.value = await fetchMaster(
      'event_groups',
      eventBranchId ? { event_branch_id: eventBranchId } : {}
    )
  }

  /* ---------- CATEGORIES ---------- */

  const loadAllEventCategories = async () => {
    eventCategories.value = await fetchMaster('event_categories')
  }

  const loadEventCategories = async (eventGroupId = null) => {
    eventCategories.value = await fetchMaster(
      'event_categories',
      eventGroupId ? { event_group_id: eventGroupId } : {}
    )
  }

  /* ---------- PRELOAD ---------- */
  const preloadMasterMTQ = async () => {
    if (!authUserStore.eventData?.id) return

    await Promise.all([
      loadEventStages(),
      loadEventBranches(),
      loadAllEventGroups(),
      loadAllEventCategories(),
    ])
  }

  /* ---------- RESET ---------- */
  const clearMaster = () => {
    eventStages.value     = []
    eventBranches.value   = []
    eventGroups.value     = []
    eventCategories.value = []
  }

  return {
    // state
    eventStages,
    eventBranches,
    eventGroups,
    eventCategories,
    loading,

    // actions
    loadEventStages,
    loadEventBranches,

    loadAllEventGroups,
    loadEventGroups,

    loadAllEventCategories,
    loadEventCategories,

    preloadMasterMTQ,
    clearMaster,
  }
})
