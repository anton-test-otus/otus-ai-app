import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { appConfig } from '@/config/app'

export function useUserSettings() {
  const authStore = useAuthStore()

  const effectiveAutosaveDelayMs = computed(() => {
    const userSeconds = authStore.user?.settings?.autosaveDelaySeconds
    const defaultSeconds = authStore.user?.defaults?.autosaveDelaySeconds ?? appConfig.autosaveDelaySeconds
    const seconds = userSeconds ?? defaultSeconds
    return seconds * 1000
  })

  const effectiveVersionConsolidationWindowMinutes = computed(() => {
    const userMinutes = authStore.user?.settings?.versionConsolidationWindowMinutes
    const defaultMinutes = authStore.user?.defaults?.versionConsolidationWindowMinutes
      ?? appConfig.versionConsolidationWindowMinutes
    return userMinutes ?? defaultMinutes
  })

  return {
    effectiveAutosaveDelayMs,
    effectiveVersionConsolidationWindowMinutes,
  }
}
