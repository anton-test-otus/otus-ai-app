<template>
  <div
    class="error-state"
    :class="compact ? 'py-4 px-2 text-center' : 'py-12 text-center'"
    role="alert"
  >
    <Message
      v-if="!compact"
      severity="error"
      :closable="false"
      class="inline-block text-left max-w-lg"
    >
      <div class="flex flex-col gap-3">
        <p>{{ message }}</p>
        <Button
          v-if="showRetry"
          label="Повторить"
          icon="pi pi-refresh"
          size="small"
          severity="secondary"
          outlined
          @click="$emit('retry')"
        />
      </div>
    </Message>

    <div
      v-else
      class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-3 rounded-md"
    >
      <p>{{ message }}</p>
      <Button
        v-if="showRetry"
        label="Повторить"
        icon="pi pi-refresh"
        size="small"
        severity="secondary"
        text
        class="mt-2"
        @click="$emit('retry')"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Message from 'primevue/message'
import Button from 'primevue/button'

const props = defineProps<{
  message: string
  compact?: boolean
  retryable?: boolean
}>()

defineEmits<{
  retry: []
}>()

const showRetry = computed(() => props.retryable !== false)
</script>
