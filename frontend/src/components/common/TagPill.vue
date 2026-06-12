<template>
  <component
    :is="interactive ? 'button' : 'span'"
    :type="interactive ? 'button' : undefined"
    class="tag-cloud-pill"
    :class="{
      'tag-cloud-pill--interactive': interactive,
      'tag-cloud-pill--selected': selected,
      'tag-cloud-pill--static': !interactive,
    }"
    v-tooltip.top="tooltip || label"
    @click="interactive ? $emit('click') : undefined"
  >
    <span class="truncate">{{ label }}</span>
  </component>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  label: string
  selected?: boolean
  interactive?: boolean
  tooltip?: string
}>(), {
  selected: false,
  interactive: true,
  tooltip: undefined,
})

defineEmits<{
  click: []
}>()
</script>
