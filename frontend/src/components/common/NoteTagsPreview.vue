<template>
  <div v-if="tags.length > 0" class="tag-cloud">
    <span
      v-for="tag in visibleTags"
      :key="tag.id"
      class="note-tag-pill"
      v-tooltip.top="tag.name"
    >
      {{ tag.name }}
    </span>
    <span
      v-if="overflowCount > 0"
      class="note-tag-pill note-tag-pill-overflow"
      v-tooltip.top="overflowTooltip"
    >
      +{{ overflowCount }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Tag } from '@/types'

const props = withDefaults(defineProps<{
  tags?: Tag[]
  maxVisible?: number
}>(), {
  tags: () => [],
  maxVisible: 3,
})

const visibleTags = computed(() => props.tags.slice(0, props.maxVisible))

const overflowCount = computed(() => Math.max(0, props.tags.length - props.maxVisible))

const overflowTooltip = computed(() =>
  props.tags.slice(props.maxVisible).map((tag) => tag.name).join(', '),
)
</script>
