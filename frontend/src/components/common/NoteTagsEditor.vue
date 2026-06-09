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
    >
      <template #chip="slotProps">
        <div class="flex items-center gap-2 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-3 py-1 rounded-full">
          <span class="text-sm">{{ slotProps.value }}</span>
        </div>
      </template>
    </Chips>

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
import { useTagsStore } from '../../stores/tags';

interface Props {
  modelValue: string[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: string[]];
}>();

const tagsStore = useTagsStore();
const showSuggestions = ref(true);

const availableTags = computed(() => tagsStore.tags.map(t => t.name));

const filteredSuggestions = computed(() => {
  return availableTags.value.filter(tag => !props.modelValue.includes(tag));
});

function handleAddTag(event: { value: string[] }) {
  const newTag = event.value[event.value.length - 1];
  if (newTag && newTag.trim()) {
    createTagIfNotExists(newTag.trim());
  }
}

function addSuggestion(tagName: string) {
  if (!props.modelValue.includes(tagName)) {
    emit('update:modelValue', [...props.modelValue, tagName]);
  }
}

async function createTagIfNotExists(tagName: string) {
  const existing = tagsStore.getTagByName(tagName);
  if (!existing) {
    try {
      await tagsStore.createTag(tagName);
    } catch (error) {
      console.error('Failed to create tag:', error);
    }
  }
}

onMounted(async () => {
  if (tagsStore.tags.length === 0) {
    await tagsStore.fetchTags();
  }
});

watch(() => props.modelValue, () => {
  showSuggestions.value = filteredSuggestions.value.length > 0;
});
</script>

<style scoped>
.note-tags-editor :deep(.p-chips-input-token input) {
  @apply text-sm;
}
</style>
