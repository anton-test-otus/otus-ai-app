import { ref } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { appConfig } from '@/config/app'
import type { SaveStatus } from '@/types'

interface UseAutosaveOptions {
  delay?: number
  hasChanges?: () => boolean
}

export function useAutosave(
  saveFunction: () => Promise<void>,
  options: UseAutosaveOptions = {},
) {
  const delay = options.delay ?? appConfig.autosaveDebounceMs
  const saveStatus = ref<SaveStatus>('idle')
  const saveError = ref<string | null>(null)

  let activeSave: Promise<void> | null = null

  async function performSave(): Promise<void> {
    if (options.hasChanges && !options.hasChanges()) {
      return
    }

    if (activeSave) {
      return activeSave
    }

    activeSave = (async () => {
      saveStatus.value = 'saving'
      saveError.value = null

      try {
        await saveFunction()
        saveStatus.value = 'saved'
        setTimeout(() => {
          if (saveStatus.value === 'saved') {
            saveStatus.value = 'idle'
          }
        }, delay)
      } catch (error: unknown) {
        saveStatus.value = 'error'
        saveError.value = error instanceof Error ? error.message : 'Ошибка сохранения'
        throw error
      } finally {
        activeSave = null
      }
    })()

    return activeSave
  }

  const debouncedSave = useDebounceFn(() => performSave(), delay)

  function triggerSave() {
    if (saveStatus.value !== 'saving') {
      debouncedSave()
    }
  }

  async function flushSave(): Promise<void> {
    debouncedSave.cancel()
    await performSave()
  }

  function reset() {
    debouncedSave.cancel()
    saveStatus.value = 'idle'
    saveError.value = null
  }

  return {
    saveStatus,
    saveError,
    triggerSave,
    flushSave,
    reset,
  }
}
