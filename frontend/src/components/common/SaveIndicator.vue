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
import { computed } from 'vue'
import type { SaveStatus } from '@/types'

interface Props {
  status: SaveStatus
}

const props = defineProps<Props>()

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
      return 'Сохранено'
    case 'error':
      return 'Ошибка сохранения'
    default:
      return ''
  }
})
</script>
