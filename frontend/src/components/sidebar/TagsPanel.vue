<template>
  <div class="tags-panel">
    <div class="tags-header flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Теги</h3>
      <Button
        icon="pi pi-cog"
        text
        rounded
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

    <div v-else class="tags-list space-y-1">
      <div
        v-for="tag in tags"
        :key="tag.id"
        class="tag-item flex items-center justify-between px-3 py-2 rounded cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors"
        :class="{ 'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-primary-500': isTagSelected(tag.id) }"
        @click="toggleTag(tag.id)"
      >
        <div class="flex items-center gap-2 flex-1">
          <i class="pi pi-tag text-sm text-primary-500" />
          <span class="text-sm">{{ tag.name }}</span>
        </div>
        <Badge
          v-if="isTagSelected(tag.id)"
          value="✓"
          severity="success"
        />
      </div>
    </div>

    <!-- Clear filters button -->
    <div v-if="selectedTags.length > 0" class="mt-4">
      <Button
        label="Очистить фильтры"
        icon="pi pi-times"
        outlined
        class="w-full"
        @click="clearFilters"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import ProgressSpinner from 'primevue/progressspinner';
import { useTagsStore } from '../../stores/tags';

const tagsStore = useTagsStore();

const emit = defineEmits<{
  filterChange: [tagIds: string[]];
}>();

const tags = computed(() => tagsStore.tags);
const selectedTags = computed(() => tagsStore.selectedTags);
const loading = computed(() => tagsStore.loading);

function isTagSelected(tagId: string): boolean {
  return selectedTags.value.includes(tagId);
}

function toggleTag(tagId: string) {
  tagsStore.toggleTagSelection(tagId);
  emit('filterChange', selectedTags.value);
}

function clearFilters() {
  tagsStore.clearTagSelection();
  emit('filterChange', []);
}

onMounted(async () => {
  if (tags.value.length === 0) {
    await tagsStore.fetchTags();
  }
});
</script>

<style scoped>
.tags-panel {
  @apply select-none;
}

.tag-item {
  @apply transition-all;
}

.tag-item:hover {
  @apply transform scale-[1.02];
}
</style>
