<template>
  <div class="flex items-center space-x-2 text-sm">
    <i
      v-if="status !== 'idle'"
      :class="iconClass"
      class="transition-all"
    ></i>
    <span class="hidden sm:inline" :class="textClass">
      {{ statusText }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { formatSavedAgo } from '@/utils/date'
import type { SaveStatus } from '@/types'

interface Props {
  status: SaveStatus
  savedAt?: Date | null
}

const props = defineProps<Props>()

const tick = ref(new Date())
let refreshTimer: ReturnType<typeof setTimeout> | null = null

const REFRESH_INTERVAL_SEC = 5_000
const REFRESH_INTERVAL_MIN = 60_000

function clearRefreshTimer() {
  if (refreshTimer !== null) {
    clearTimeout(refreshTimer)
    refreshTimer = null
  }
}

function getRefreshDelay(savedAt: Date, now: Date): number {
  const diffSec = Math.floor((now.getTime() - savedAt.getTime()) / 1000)
  return diffSec >= 60 ? REFRESH_INTERVAL_MIN : REFRESH_INTERVAL_SEC
}

function scheduleRefresh() {
  clearRefreshTimer()
  if (props.status !== 'saved' || !props.savedAt) {
    return
  }

  tick.value = new Date()
  const delay = getRefreshDelay(props.savedAt, tick.value)

  refreshTimer = setTimeout(() => {
    scheduleRefresh()
  }, delay)
}

watch(
  () => [props.status, props.savedAt] as const,
  ([status, savedAt]) => {
    clearRefreshTimer()
    if (status === 'saved' && savedAt) {
      scheduleRefresh()
    }
  },
  { immediate: true },
)

onUnmounted(clearRefreshTimer)

const iconClass = computed(() => {
  switch (props.status) {
    case 'saving':
      return 'pi pi-spin pi-spinner text-blue-600'
    case 'saved':
      return 'pi pi-check text-green-600'
    case 'error':
      return 'pi pi-times text-red-600'
    default:
      return ''
  }
})

const textClass = computed(() => {
  switch (props.status) {
    case 'saving':
      return 'text-blue-600'
    case 'saved':
      return 'text-green-600'
    case 'error':
      return 'text-red-600'
    default:
      return 'text-gray-500'
  }
})

const statusText = computed(() => {
  switch (props.status) {
    case 'saving':
      return 'Сохранение...'
    case 'saved':
      if (props.savedAt) {
        return formatSavedAgo(props.savedAt, tick.value)
      }
      return 'Сохранено'
    case 'error':
      return 'Ошибка сохранения'
    default:
      return ''
  }
})
</script>
