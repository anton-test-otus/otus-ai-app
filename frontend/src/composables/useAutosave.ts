import { ref, watch, toValue, type MaybeRefOrGetter } from 'vue'
import { appConfig } from '@/config/app'
import { getApiErrorMessage } from '@/utils/apiError'
import type { SaveStatus } from '@/types'

interface UseAutosaveOptions {
  delay?: MaybeRefOrGetter<number>
  hasChanges?: () => boolean
}

export function useAutosave(
  saveFunction: () => Promise<void>,
  options: UseAutosaveOptions = {},
) {
  const delaySource = options.delay ?? appConfig.autosaveDelaySeconds * 1000
  const saveStatus = ref<SaveStatus>('idle')
  const saveError = ref<string | null>(null)
  const lastSavedAt = ref<Date | null>(null)

  let activeSave: Promise<void> | null = null
  let debounceTimer: ReturnType<typeof setTimeout> | null = null
  let currentDelay = toValue(delaySource)

  function cancelDebouncedSave() {
    if (debounceTimer !== null) {
      clearTimeout(debounceTimer)
      debounceTimer = null
    }
  }

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
        lastSavedAt.value = new Date()
        saveStatus.value = 'saved'
      } catch (error: unknown) {
        saveStatus.value = 'error'
        saveError.value = getApiErrorMessage(error, 'Ошибка сохранения')
        throw error
      } finally {
        activeSave = null
      }
    })()

    return activeSave
  }

  function scheduleSave() {
    cancelDebouncedSave()
    debounceTimer = setTimeout(() => {
      debounceTimer = null
      void performSave()
    }, currentDelay)
  }

  watch(
    () => toValue(delaySource),
    (newDelay) => {
      if (newDelay === currentDelay) {
        return
      }
      cancelDebouncedSave()
      currentDelay = newDelay
    },
  )

  function triggerSave() {
    if (saveStatus.value !== 'saving') {
      scheduleSave()
    }
  }

  async function flushSave(): Promise<void> {
    cancelDebouncedSave()
    await performSave()
  }

  function reset() {
    cancelDebouncedSave()
    saveStatus.value = 'idle'
    saveError.value = null
    lastSavedAt.value = null
  }

  return {
    saveStatus,
    saveError,
    lastSavedAt,
    triggerSave,
    flushSave,
    reset,
  }
}
