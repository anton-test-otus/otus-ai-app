<template>
  <div class="note-tags-editor">
    <label class="block text-sm font-semibold mb-2">
      Теги
    </label>
    
    <Chips
      :model-value="modelValue"
      @update:model-value="$emit('update:modelValue', $event)"
      placeholder="Добавить тег..."
      separator=","
      class="w-full"
      @add="handleAddTag"
    />

    <!-- Autocomplete suggestions -->
    <div v-if="showSuggestions && filteredSuggestions.length > 0" class="suggestions mt-2">
      <div class="text-xs text-surface-500 dark:text-surface-400 mb-1">
        Предложения:
      </div>
      <div class="flex flex-wrap gap-2">
        <Button
          v-for="suggestion in filteredSuggestions"
          :key="suggestion"
          :label="suggestion"
          size="small"
          outlined
          @click="addSuggestion(suggestion)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import Chips from 'primevue/chips';
import Button from 'primevue/button';
import { tagsApi } from '@/api/tags';
import type { Tag } from '@/types';

interface Props {
  modelValue: string[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: string[]];
}>();

const userTags = ref<Tag[]>([]);
const showSuggestions = ref(true);

const availableTags = computed(() => userTags.value.map((tag) => tag.name));

const filteredSuggestions = computed(() => {
  return availableTags.value.filter(tag => !props.modelValue.includes(tag));
});

async function handleAddTag(event: { value: string[] }) {
  const newTag = event.value[event.value.length - 1];
  if (newTag && newTag.trim()) {
    await createTagIfNotExists(newTag.trim());
  }
}

function addSuggestion(tagName: string) {
  if (!props.modelValue.includes(tagName)) {
    emit('update:modelValue', [...props.modelValue, tagName]);
  }
}

function getTagByName(tagName: string): Tag | undefined {
  const normalized = tagName.toLowerCase();
  return userTags.value.find((tag) => tag.name.toLowerCase() === normalized);
}

async function loadUserTags() {
  userTags.value = await tagsApi.getAll();
}

async function createTagIfNotExists(tagName: string) {
  if (getTagByName(tagName)) {
    return;
  }

  try {
    const newTag = await tagsApi.create(tagName);
    userTags.value = [...userTags.value, newTag].sort((a, b) =>
      a.name.localeCompare(b.name),
    );
  } catch (error) {
    console.error('Failed to create tag:', error);
  }
}

onMounted(loadUserTags);

watch(() => props.modelValue, () => {
  showSuggestions.value = filteredSuggestions.value.length > 0;
});
</script>

<style scoped>
.note-tags-editor :deep(.p-chips-multiple-container) {
  @apply gap-2;
}

.note-tags-editor :deep(.p-chips-token) {
  @apply bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-full px-3 py-1 text-sm;
}

.note-tags-editor :deep(.p-chips-token-icon) {
  @apply text-primary-600 dark:text-primary-400;
}

.note-tags-editor :deep(.p-chips-input-token input) {
  @apply text-sm;
}
</style>
