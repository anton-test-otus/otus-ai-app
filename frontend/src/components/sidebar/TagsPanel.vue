<template>
  <div class="tags-panel">
    <div class="tags-header flex justify-between items-center mb-4">
      <h3 class="section-title">Теги</h3>
      <Button
        icon="pi pi-cog"
        text
        rounded
        class="sidebar-icon-btn"
        @mousedown.prevent
        @click="$router.push('/tags')"
        v-tooltip.top="'Управление тегами'"
      />
    </div>

    <div v-if="loading" class="flex justify-center py-4">
      <ProgressSpinner style="width: 30px; height: 30px" stroke-width="4" />
    </div>

    <div v-else-if="!tags || tags.length === 0" class="text-sm text-surface-500 dark:text-surface-400 text-center py-4">
      Нет тегов
    </div>

    <div v-else class="tag-cloud">
      <TagPill
        v-for="tag in tags"
        :key="tag.id"
        :label="tag.name"
        :selected="isTagSelected(tag.id)"
        @click="toggleTag(tag.id)"
      />
    </div>

    <div v-if="selectedTags.length > 0" class="mt-4">
      <Button
        label="Очистить фильтры"
        icon="pi pi-times"
        outlined
        size="small"
        class="w-full"
        @click="clearFilters"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import TagPill from '@/components/common/TagPill.vue'
import { useTagsStore } from '@/stores/tags'

const tagsStore = useTagsStore()

const emit = defineEmits<{
  filterChange: [tagIds: string[]]
}>()

const tags = computed(() => tagsStore.tags)
const selectedTags = computed(() => tagsStore.selectedTags)
const loading = computed(() => tagsStore.loading)

function isTagSelected(tagId: string): boolean {
  return selectedTags.value.includes(tagId)
}

function toggleTag(tagId: string) {
  tagsStore.toggleTagSelection(tagId)
  emit('filterChange', selectedTags.value)
}

function clearFilters() {
  tagsStore.clearTagSelection()
  emit('filterChange', [])
}
</script>

<style scoped>
.tags-panel {
  @apply select-none;
}
</style>
