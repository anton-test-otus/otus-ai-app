<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title flex items-center gap-2">
        <i class="pi pi-star-fill text-amber-500" aria-hidden="true" />
        Избранное
      </h1>
      <p v-if="!isInitialLoading && notesStore.favoriteNotes.length > 0" class="page-subtitle">
        {{ pageSubtitle }}
      </p>
    </div>

    <LoadingState v-if="isInitialLoading" />

    <ErrorState
      v-else-if="notesStore.favoritesError"
      :message="notesStore.favoritesError"
      @retry="loadFavorites"
    />

    <EmptyState
      v-else-if="isEmpty"
      icon="pi-star"
      title="Нет избранных заметок"
      description="Отмечайте важные заметки звёздочкой — они появятся здесь"
    >
      <template #actions>
        <Button
          label="К заметкам"
          icon="pi pi-inbox"
          @click="goToDashboard"
        />
      </template>
    </EmptyState>

    <div v-else class="stack-sections">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch">
        <NoteCard
          v-for="note in notesStore.favoriteNotes"
          :key="note.id"
          :note="note"
          show-folder
          :format-date="formatDate"
          @open="openNote"
          @edit="openNoteInEditMode"
          @delete="confirmDelete"
          @toggle-favorite="handleToggleFavorite"
        />
      </div>

      <div
        v-if="notesStore.favoritesHasMore || notesStore.isLoadingMoreFavorites"
        ref="loadMoreSentinel"
        class="flex justify-center py-6"
        aria-hidden="true"
      >
        <LoadingState v-if="notesStore.isLoadingMoreFavorites" compact />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import EmptyState from '@/components/common/EmptyState.vue'
import LoadingState from '@/components/common/LoadingState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import { useAppToast } from '@/composables/useAppToast'
import { useInfiniteList } from '@/composables/useInfiniteList'
import NoteCard from '@/components/dashboard/NoteCard.vue'
import { useNotesStore } from '@/stores/notes'
import { useFavoriteToggle } from '@/composables/useFavoriteToggle'
import type { NoteListItem } from '@/types'

const router = useRouter()
const notesStore = useNotesStore()
const confirm = useConfirm()
const { showSuccess, showError } = useAppToast()
const { toggleFavorite } = useFavoriteToggle()

const isInitialLoading = computed(
  () => notesStore.isLoadingFavorites && notesStore.favoriteNotes.length === 0,
)

const isEmpty = computed(() => notesStore.favoriteNotes.length === 0)

const pageSubtitle = computed(() => {
  const total = notesStore.favoritesPagination.total
  return `${total} ${pluralizeNotes(total)}`
})

async function loadFavorites() {
  await notesStore.fetchFavorites(
    1,
    notesStore.favoritesPagination.perPage,
  )
}

async function loadMoreFavorites() {
  try {
    await notesStore.loadMoreFavorites()
  } catch (error) {
    showError(error, 'Не удалось загрузить избранное')
  }
}

const { sentinelRef: loadMoreSentinel } = useInfiniteList({
  onLoadMore: loadMoreFavorites,
  canLoadMore: () =>
    notesStore.favoritesHasMore &&
    !notesStore.isLoadingMoreFavorites &&
    !notesStore.isLoadingFavorites &&
    !notesStore.favoritesError,
})

onMounted(async () => {
  await loadFavorites()
})

function goToDashboard() {
  router.push({ name: 'dashboard' })
}

function openNote(id: string) {
  router.push({ name: 'note', params: { id }, query: { mode: 'preview' } })
}

function openNoteInEditMode(id: string) {
  router.push({ name: 'note', params: { id }, query: { mode: 'edit' } })
}

async function handleToggleFavorite(note: NoteListItem) {
  await toggleFavorite(note)
}

function confirmDelete(note: NoteListItem) {
  confirm.require({
    message: `Вы уверены, что хотите удалить заметку "${note.title}"?`,
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await notesStore.deleteNote(note.id)
        showSuccess('Заметка удалена')
      } catch (error) {
        showError(error, 'Не удалось удалить заметку')
      }
    },
  })
}

function pluralizeNotes(count: number): string {
  const mod10 = count % 10
  const mod100 = count % 100

  if (mod10 === 1 && mod100 !== 11) return 'заметка'
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return 'заметки'
  return 'заметок'
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (days === 0) return 'Сегодня'
  if (days === 1) return 'Вчера'
  if (days < 7) return `${days} дн. назад`

  return date.toLocaleDateString('ru-RU')
}
</script>
