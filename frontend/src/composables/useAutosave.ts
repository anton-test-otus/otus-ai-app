import { ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import type { SaveStatus } from '@/types'

export function useAutosave(
  saveFunction: () => Promise<void>,
  delay = 2000
) {
  const saveStatus = ref<SaveStatus>('idle')
  const saveError = ref<string | null>(null)

  const debouncedSave = useDebounceFn(async () => {
    saveStatus.value = 'saving'
    saveError.value = null

    try {
      await saveFunction()
      saveStatus.value = 'saved'
      setTimeout(() => {
        if (saveStatus.value === 'saved') {
          saveStatus.value = 'idle'
        }
      }, 2000)
    } catch (error: any) {
      saveStatus.value = 'error'
      saveError.value = error.message || 'Ошибка сохранения'
    }
  }, delay)

  function triggerSave() {
    if (saveStatus.value !== 'saving') {
      debouncedSave()
    }
  }

  function reset() {
    saveStatus.value = 'idle'
    saveError.value = null
  }

  return {
    saveStatus,
    saveError,
    triggerSave,
    reset,
  }
}
