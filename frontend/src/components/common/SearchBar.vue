<template>
  <div class="search-bar relative" ref="searchBarRef">
    <div class="search-input-wrapper relative">
      <div class="relative w-full">
        <i v-if="searchQuery.length === 0" class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none z-10"></i>
        
        <InputText
          ref="inputRef"
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
      class="quick-results mt-2 app-chrome border app-border rounded-lg shadow-lg overflow-hidden z-50"
      :class="inModal ? 'relative' : 'absolute top-full left-0 right-0'"
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
            <i class="pi pi-file text-primary-500 mt-1 shrink-0" />
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm truncate" v-html="highlightSearchMatch(note.title, searchQuery)" />
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
      :style="MODAL_WIDTH.lg"
      :maximizable="true"
    >
      <LoadingState v-if="searching && fullResults.length === 0" />

      <EmptyState
        v-else-if="!searching && fullResults.length === 0"
        icon="pi-search"
        title="Ничего не найдено"
        description="Попробуйте изменить запрос или проверить орфографию"
        compact
      />

      <div v-else>
        <div class="space-y-4">
          <Card
            v-for="note in fullResults"
            :key="note.id"
            class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="openNote(note.id)"
          >
            <template #title>
              <div class="text-lg" v-html="highlightSearchMatch(note.title, searchQuery)" />
            </template>
            <template #content>
              <div
                class="text-sm text-surface-600 dark:text-surface-400 line-clamp-3"
                v-html="highlightSearchMatch(note.contentPreview, searchQuery)"
              />
              <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-xs text-surface-500 dark:text-surface-400">
                <span><i class="pi pi-calendar mr-1" />{{ formatDate(note.updatedAt) }}</span>
                <span v-if="note.folder"><i class="pi pi-folder mr-1" />{{ note.folder.name }}</span>
                <div v-if="note.tags && note.tags.length > 0" class="tag-cloud">
                  <span
                    v-for="tag in note.tags"
                    :key="tag.id"
                    class="note-tag-pill"
                  >
                    {{ tag.name }}
                  </span>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <div
          v-if="hasMoreResults || loadingMore"
          ref="loadMoreSentinel"
          class="flex justify-center py-4"
          aria-hidden="true"
        >
          <LoadingState v-if="loadingMore" compact />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';

const props = withDefaults(defineProps<{
  inModal?: boolean
}>(), {
  inModal: false,
})

const emit = defineEmits<{
  'note-opened': []
}>()
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import { MODAL_WIDTH } from '@/constants/modal';
import EmptyState from '@/components/common/EmptyState.vue';
import LoadingState from '@/components/common/LoadingState.vue';
import { useInfiniteList } from '@/composables/useInfiniteList';
import { useAppToast } from '@/composables/useAppToast';
import { searchApi } from '../../api/search';
import type { NoteListItem } from '../../types';
import { highlightMatch as highlightSearchMatch } from '@/utils/highlightMatch';

const router = useRouter();
const { showError } = useAppToast();
const searchBarRef = ref<HTMLElement | null>(null);
const inputRef = ref<InstanceType<typeof InputText> | null>(null);
const searchQuery = ref('');
const quickResults = ref<NoteListItem[]>([]);
const fullResults = ref<NoteListItem[]>([]);
const showQuickResults = ref(false);
const showFullResults = ref(false);
const searching = ref(false);
const loadingMore = ref(false);
const totalResults = ref(0);
const totalPages = ref(0);
const currentPage = ref(1);
const perPage = ref(10);

const hasMoreResults = computed(() => currentPage.value < totalPages.value);

let fullSearchPromise: Promise<void> | null = null;

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

async function performFullSearch(page = 1, append = false) {
  if (searchQuery.value.length < 2) return;

  if (append) {
    if (loadingMore.value || searching.value || !hasMoreResults.value) {
      return;
    }
    if (fullSearchPromise) {
      return fullSearchPromise;
    }
    loadingMore.value = true;
  } else {
    if (fullSearchPromise && searching.value) {
      return fullSearchPromise;
    }
    searching.value = true;
    showQuickResults.value = false;
    showFullResults.value = true;
    fullResults.value = [];
    currentPage.value = 1;
    totalResults.value = 0;
    totalPages.value = 0;
  }

  const promise = (async () => {
    try {
      const response = await searchApi.search({
        q: searchQuery.value,
        page,
        perPage: perPage.value,
      });

      if (append) {
        const existingIds = new Set(fullResults.value.map((note) => note.id));
        const newItems = response.data.filter((note) => !existingIds.has(note.id));
        fullResults.value = [...fullResults.value, ...newItems];
      } else {
        fullResults.value = response.data;
      }

      currentPage.value = response.meta.currentPage;
      totalResults.value = response.meta.total;
      totalPages.value = response.meta.totalPages;
    } catch (error) {
      if (!append) {
        fullResults.value = [];
      }
      showError(error, 'Не удалось выполнить поиск');
      throw error;
    } finally {
      if (append) {
        loadingMore.value = false;
        fullSearchPromise = null;
      } else {
        searching.value = false;
        fullSearchPromise = null;
      }
    }
  })();

  fullSearchPromise = promise;
  return promise;
}

async function loadMoreSearchResults() {
  try {
    await performFullSearch(currentPage.value + 1, true);
  } catch {
    // toast уже показан в performFullSearch
  }
}

const { sentinelRef: loadMoreSentinel } = useInfiniteList({
  onLoadMore: loadMoreSearchResults,
  canLoadMore: () =>
    showFullResults.value &&
    hasMoreResults.value &&
    !loadingMore.value &&
    !searching.value,
});

function clearSearch() {
  searchQuery.value = '';
  quickResults.value = [];
  fullResults.value = [];
  showQuickResults.value = false;
  showFullResults.value = false;
  loadingMore.value = false;
  currentPage.value = 1;
  totalResults.value = 0;
  totalPages.value = 0;
  fullSearchPromise = null;
}

function closeResults() {
  showQuickResults.value = false;
}

function openNote(noteId: string) {
  router.push({ name: 'note', params: { id: noteId }, query: { mode: 'preview' } });
  clearSearch();
  emit('note-opened');
}

function focusInput() {
  const root = inputRef.value?.$el as HTMLElement | undefined
  const input = root?.querySelector('input') ?? root
  if (input instanceof HTMLInputElement) {
    input.focus()
  }
}

defineExpose({ focusInput })

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
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
