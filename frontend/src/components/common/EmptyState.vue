<template>
  <div
    class="empty-state text-center"
    :class="compact ? 'py-8' : 'py-12'"
  >
    <i
      v-if="icon"
      :class="[
        'pi',
        icon,
        'text-muted block mx-auto mb-4',
        compact ? 'text-4xl' : 'text-6xl',
      ]"
    />
    <h3 v-if="title" class="empty-state-title mb-2">
      {{ title }}
    </h3>
    <p v-if="description" class="empty-state-text mb-4">
      {{ description }}
    </p>
    <div v-if="$slots.actions" class="empty-state-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  icon?: string
  title?: string
  description?: string
  compact?: boolean
}>(), {
  compact: false,
})
</script>
