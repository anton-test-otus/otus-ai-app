import { ref, watch, toValue, type MaybeRefOrGetter } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { appConfig } from '@/config/app'
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

  let activeSave: Promise<void> | null = null
  let currentDelay = toValue(delaySource)

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
        }, currentDelay)
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

  let debouncedSave = useDebounceFn(() => performSave(), currentDelay)

  watch(
    () => toValue(delaySource),
    (newDelay) => {
      if (newDelay === currentDelay) {
        return
      }
      debouncedSave.cancel()
      currentDelay = newDelay
      debouncedSave = useDebounceFn(() => performSave(), currentDelay)
    },
  )

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
