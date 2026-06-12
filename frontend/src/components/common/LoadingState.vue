<template>
  <div
    class="loading-state flex justify-center items-center"
    :class="compact ? 'py-4' : 'py-12'"
    role="status"
    aria-live="polite"
    aria-busy="true"
  >
    <ProgressSpinner
      :style="spinnerStyle"
      stroke-width="4"
    />
    <span v-if="message" class="sr-only">{{ message }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ProgressSpinner from 'primevue/progressspinner'

const props = withDefaults(defineProps<{
  compact?: boolean
  message?: string
}>(), {
  compact: false,
})

const spinnerStyle = computed(() =>
  props.compact
    ? { width: '30px', height: '30px' }
    : undefined,
)
</script>
