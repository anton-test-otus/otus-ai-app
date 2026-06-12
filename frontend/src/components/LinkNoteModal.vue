<template>
  <Dialog
    v-model:visible="isVisible"
    modal
    header="Вставить ссылку на заметку"
    :style="MODAL_WIDTH.md"
    @hide="onHide"
  >
    <div class="space-y-3">
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">Поиск заметки:</label>
        <InputText
          v-model="searchQuery"
          placeholder="Введите название заметки..."
          @input="onSearchInput"
          autofocus
        />
      </div>

      <div v-if="searching" class="text-center py-4">
        <ProgressSpinner style="width: 30px; height: 30px" />
      </div>

      <div v-else-if="searchResults.length > 0" class="max-h-[400px] overflow-y-auto space-y-1">
        <div
          v-for="note in searchResults"
          :key="note.id"
          class="p-2 border border-gray-200 dark:border-gray-700 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
          @click="selectNote(note)"
        >
          <div class="font-medium text-sm">{{ note.title }}</div>
          <div class="text-xs text-gray-500">
            {{ formatDate(note.updatedAt) }}
          </div>
        </div>
      </div>

      <div v-else-if="searchQuery && !searching" class="text-center text-gray-500 py-4">
        Заметки не найдены
      </div>

      <div v-else class="text-center text-gray-500 py-4">
        Начните вводить для поиска
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import ProgressSpinner from 'primevue/progressspinner';
import { MODAL_WIDTH } from '@/constants/modal';
import { notesApi } from '@/api/notes';
import type { NoteListItem } from '@/types';

export interface SelectedWikiLinkNote {
  id: string;
  title: string;
}

const props = defineProps<{
  visible: boolean;
  excludeNoteId?: string | null;
}>();

const emit = defineEmits<{
  'update:visible': [value: boolean];
  'select': [note: SelectedWikiLinkNote];
}>();

const isVisible = ref(props.visible);
const searchQuery = ref('');
const searchResults = ref<NoteListItem[]>([]);
const searching = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => props.visible, (val) => {
  isVisible.value = val;
  if (val) {
    searchQuery.value = '';
    searchResults.value = [];
  }
});

watch(isVisible, (val) => {
  emit('update:visible', val);
});

const onSearchInput = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  searchTimeout = setTimeout(async () => {
    if (!searchQuery.value.trim()) {
      searchResults.value = [];
      return;
    }

    searching.value = true;
    try {
      const response = await notesApi.search(searchQuery.value, 1, 10);
      searchResults.value = response.data.filter((note) => note.id !== props.excludeNoteId);
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      searching.value = false;
    }
  }, 300);
};

const selectNote = (note: NoteListItem) => {
  emit('select', { id: note.id, title: note.title });
  isVisible.value = false;
};

const onHide = () => {
  searchQuery.value = '';
  searchResults.value = [];
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 60) return `${diffMins} мин назад`;
  if (diffHours < 24) return `${diffHours} ч назад`;
  if (diffDays < 7) return `${diffDays} дн назад`;

  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
  });
};
</script>
