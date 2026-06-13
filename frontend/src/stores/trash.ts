import { defineStore } from 'pinia'
import { ref } from 'vue'
import { trashApi } from '@/api/trash'

export const useTrashStore = defineStore('trash', () => {
  const count = ref(0)
  const isLoading = ref(false)

  async function fetchCount() {
    isLoading.value = true
    try {
      const response = await trashApi.getTrash(1, 1)
      count.value = response.meta?.total ?? 0
    } catch {
      count.value = 0
    } finally {
      isLoading.value = false
    }
  }

  function reset() {
    count.value = 0
    isLoading.value = false
  }

  return {
    count,
    isLoading,
    fetchCount,
    reset,
  }
})
