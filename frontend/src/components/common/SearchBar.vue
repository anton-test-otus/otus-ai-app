<template>
  <div class="search-bar relative" ref="searchBarRef">
    <div class="search-input-wrapper relative">
      <div class="relative w-full">
        <i v-if="searchQuery.length === 0" class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none z-10"></i>
        
        <InputText
          v-model="searchQuery"
          placeholder="Поиск заметок..."
          class="search-input w-full"
          :class="{ 'pr-20': searchQuery.length > 0, 'pl-10': searchQuery.length === 0, 'pl-3': searchQuery.length > 0 }"
          @input="onInput"
          @keyup.enter="() => performFullSearch()"
          @focus="showQuickResults = true"
        />
        
        <div v-if="searchQuery.length > 0" class="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1">
          <Button
            icon="pi pi-times"
            text
            rounded
            size="small"
            @click="clearSearch"
            v-tooltip.bottom="'Очистить'"
          />
          <Button
            icon="pi pi-search"
            rounded
            size="small"
            @click="() => performFullSearch()"
            :loading="searching"
            v-tooltip.bottom="'Полный поиск (Enter)'"
          />
        </div>
      </div>
    </div>

    <!-- Quick results dropdown -->
    <div
      v-if="showQuickResults && quickResults.length > 0 && !showFullResults"
      class="quick-results absolute top-full left-0 right-0 mt-2 bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-lg shadow-lg overflow-hidden z-50"
    >
      <div class="p-2 border-b border-surface-200 dark:border-surface-700 text-xs text-surface-500 dark:text-surface-400">
        Быстрый поиск (нажмите Enter для полного поиска)
      </div>
      <div class="max-h-80 overflow-y-auto">
        <div
          v-for="note in quickResults"
          :key="note.id"
          class="p-3 hover:bg-surface-50 dark:hover:bg-surface-700 cursor-pointer border-b border-surface-100 dark:border-surface-700 last:border-b-0"
          @click="openNote(note.id)"
        >
          <div class="flex items-start gap-3">
            <i class="pi pi-file text-primary-500 mt-1" />
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm truncate" v-html="highlightMatch(note.title)" />
              <div class="text-xs text-surface-500 dark:text-surface-400 mt-1">
                {{ formatDate(note.updatedAt) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Full search results modal -->
    <Dialog
      v-model:visible="showFullResults"
      modal
      header="Результаты поиска"
      :style="{ width: '50rem' }"
      :maximizable="true"
    >
      <div v-if="searching" class="flex justify-center py-8">
        <ProgressSpinner style="width: 50px; height: 50px" stroke-width="4" />
      </div>

      <div v-else-if="fullResults.length === 0" class="text-center py-8 text-surface-500 dark:text-surface-400">
        <i class="pi pi-search text-4xl mb-4" />
        <p>Ничего не найдено</p>
      </div>

      <div v-else>
        <div class="space-y-4">
          <Card
            v-for="note in fullResults"
            :key="note.id"
            class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="openNote(note.id)"
          >
            <template #title>
              <div class="text-lg" v-html="highlightMatch(note.title)" />
            </template>
            <template #content>
              <div
                class="text-sm text-surface-600 dark:text-surface-400 line-clamp-3"
                v-html="highlightMatch(getContentPreview(note.content))"
              />
              <div class="flex items-center gap-4 mt-3 text-xs text-surface-500 dark:text-surface-400">
                <span><i class="pi pi-calendar mr-1" />{{ formatDate(note.updatedAt) }}</span>
                <span v-if="note.folder"><i class="pi pi-folder mr-1" />{{ note.folder.name }}</span>
                <div v-if="note.tags && note.tags.length > 0" class="flex gap-1">
                  <Tag v-for="tag in note.tags" :key="tag.id" :value="tag.name" severity="info" />
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-4">
          <Paginator
            v-model:first="firstResult"
            :rows="perPage"
            :total-records="totalResults"
            @page="onPageChange"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';
import ProgressSpinner from 'primevue/progressspinner';
import { searchApi } from '../../api/search';
import type { Note } from '../../types';

const router = useRouter();
const searchBarRef = ref<HTMLElement | null>(null);
const searchQuery = ref('');
const quickResults = ref<Note[]>([]);
const fullResults = ref<Note[]>([]);
const showQuickResults = ref(false);
const showFullResults = ref(false);
const searching = ref(false);
const totalResults = ref(0);
const totalPages = ref(0);
const currentPage = ref(1);
const perPage = ref(10);
const firstResult = ref(0);

let debounceTimer: number | null = null;

// Click outside handler
function handleClickOutside(event: MouseEvent) {
  if (searchBarRef.value && !searchBarRef.value.contains(event.target as Node)) {
    closeResults();
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});

function onInput() {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }

  if (searchQuery.value.length < 2) {
    quickResults.value = [];
    showQuickResults.value = false;
    return;
  }

  debounceTimer = window.setTimeout(async () => {
    await performQuickSearch();
  }, 300);
}

async function performQuickSearch() {
  if (searchQuery.value.length < 2) return;

  try {
    const response = await searchApi.search({
      q: searchQuery.value,
      page: 1,
      perPage: 5,
    });
    quickResults.value = response.data;
    showQuickResults.value = true;
  } catch (error) {
    console.error('Quick search failed:', error);
  }
}

async function performFullSearch(page = 1) {
  if (searchQuery.value.length < 2) return;

  searching.value = true;
  showQuickResults.value = false;
  showFullResults.value = true;
  currentPage.value = page;

  try {
    const response = await searchApi.search({
      q: searchQuery.value,
      page: currentPage.value,
      perPage: perPage.value,
    });
    fullResults.value = response.data;
    totalResults.value = response.meta.total;
    totalPages.value = response.meta.totalPages;
  } catch (error) {
    console.error('Full search failed:', error);
  } finally {
    searching.value = false;
  }
}

function clearSearch() {
  searchQuery.value = '';
  quickResults.value = [];
  fullResults.value = [];
  showQuickResults.value = false;
  showFullResults.value = false;
}

function closeResults() {
  showQuickResults.value = false;
}

function openNote(noteId: string) {
  router.push({ name: 'note', params: { id: noteId }, query: { mode: 'preview' } });
  clearSearch();
}

function highlightMatch(text: string): string {
  if (!searchQuery.value) return text;
  
  const regex = new RegExp(`(${escapeRegex(searchQuery.value)})`, 'gi');
  return text.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-700">$1</mark>');
}

function escapeRegex(str: string): string {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function getContentPreview(content: string, length = 200): string {
  return content.length > length ? content.substring(0, length) + '...' : content;
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function onPageChange(event: any) {
  firstResult.value = event.first;
  performFullSearch(event.page + 1);
}
</script>

<style scoped>
.search-input {
  @apply w-full;
}

@media (max-width: 768px) {
  .search-input {
    min-width: auto;
  }
}

.quick-results {
  max-width: 600px;
}

:deep(mark) {
  @apply font-semibold;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
