<template>
  <div class="page-container">
      <div class="page-header">
        <h1 class="page-title">
          {{ pageTitle }}
        </h1>
        <p class="page-subtitle">
          {{ pageSubtitle }}
        </p>
      </div>

      <LoadingState v-if="isInitialLoading" />

      <ErrorState
        v-else-if="notesStore.error"
        :message="notesStore.error"
        @retry="loadNotes"
      />

      <EmptyState
        v-else-if="isEmpty"
        :icon="foldersStore.selectedFolderId ? 'pi-folder-open' : 'pi-book'"
        :title="emptyMessage"
      >
        <template #actions>
          <Button
            :label="foldersStore.selectedFolderId ? 'Создать заметку' : 'Создать первую заметку'"
            icon="pi pi-plus"
            @click="openNewNote"
          />
        </template>
      </EmptyState>

      <div v-else class="stack-sections">
        <section v-if="showFavoritesBlock && notesStore.favoriteNotes.length > 0">
          <h2 class="section-title flex items-center gap-2">
            <i class="pi pi-star-fill text-amber-500" />
            Избранные
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch">
            <NoteCard
              v-for="note in notesStore.favoriteNotes"
              :key="`favorite-${note.id}`"
              :note="note"
              :show-folder="!foldersStore.selectedFolderId"
              :format-date="formatDate"
              @open="openNote"
              @edit="openNoteInEditMode"
              @delete="confirmDelete"
              @toggle-favorite="handleToggleFavorite"
            />
          </div>
        </section>

        <section v-if="notesStore.notes.length > 0 || !showFavoritesBlock">
          <h2
            v-if="showFavoritesBlock && notesStore.favoriteNotes.length > 0"
            class="section-title"
          >
            Все заметки
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch">
            <NoteCard
              v-for="note in notesStore.notes"
              :key="note.id"
              :note="note"
              :show-folder="!foldersStore.selectedFolderId"
              :format-date="formatDate"
              @open="openNote"
              @edit="openNoteInEditMode"
              @delete="confirmDelete"
              @toggle-favorite="handleToggleFavorite"
            />
          </div>
        </section>

        <div
          v-if="notesStore.hasMore || notesStore.isLoadingMore"
          ref="loadMoreSentinel"
          class="flex justify-center py-6"
          aria-hidden="true"
        >
          <LoadingState v-if="notesStore.isLoadingMore" compact />
        </div>
      </div>
    </div>

</template>

<script setup lang="ts">
import { onMounted, watch, computed } from 'vue'
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
import { useFoldersStore } from '@/stores/folders'
import { useTagsStore } from '@/stores/tags'
import { useFavoriteToggle } from '@/composables/useFavoriteToggle'
import { useCreateNote } from '@/composables/useCreateNote'
import type { NoteListItem } from '@/types'

const router = useRouter()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()
const tagsStore = useTagsStore()
const confirm = useConfirm()
const { showSuccess, showError } = useAppToast()
const { toggleFavorite } = useFavoriteToggle()
const { openNewNote } = useCreateNote()

const showFavoritesBlock = computed(() => notesStore.favoriteNotes.length > 0)

const isInitialLoading = computed(
  () =>
    notesStore.isLoading &&
    notesStore.notes.length === 0 &&
    notesStore.favoriteNotes.length === 0,
)

const isEmpty = computed(
  () =>
    notesStore.notes.length === 0 &&
    (!showFavoritesBlock.value || notesStore.favoriteNotes.length === 0)
)

const selectedTagNames = computed(() =>
  tagsStore.selectedTags
    .map((tagId) => tagsStore.getTagById(tagId)?.name)
    .filter((name): name is string => Boolean(name))
)

const pageTitle = computed(() => {
  if (selectedTagNames.value.length > 0) {
    return 'Фильтр по тегам'
  }
  return foldersStore.selectedFolder
    ? foldersStore.selectedFolder.name
    : 'Мои заметки'
})

const pageSubtitle = computed(() => {
  const total = notesStore.pagination.total
  const countLabel = `${total} ${pluralizeNotes(total)}`
  const parts: string[] = []

  if (foldersStore.selectedFolder) {
    parts.push(`${countLabel} в папке «${foldersStore.selectedFolder.name}»`)
  } else {
    parts.push(`Всего заметок: ${total}`)
  }

  if (selectedTagNames.value.length > 0) {
    parts.push(`теги: ${selectedTagNames.value.join(', ')}`)
  }

  return parts.join(' · ')
})

const emptyMessage = computed(() => {
  if (selectedTagNames.value.length > 0) {
    return 'Нет заметок с выбранными тегами'
  }
  return foldersStore.selectedFolder
    ? 'В этой папке пока нет заметок'
    : 'У вас пока нет заметок'
})

async function loadNotes() {
  await notesStore.fetchNotes(
    1,
    notesStore.pagination.perPage,
    foldersStore.selectedFolderId,
    tagsStore.selectedTags,
  )
}

async function loadMoreNotes() {
  try {
    await notesStore.loadMoreNotes(
      foldersStore.selectedFolderId,
      tagsStore.selectedTags,
    )
  } catch (error) {
    showError(error, 'Не удалось загрузить заметки')
  }
}

const { sentinelRef: loadMoreSentinel } = useInfiniteList({
  onLoadMore: loadMoreNotes,
  canLoadMore: () =>
    notesStore.hasMore &&
    !notesStore.isLoadingMore &&
    !notesStore.isLoading &&
    !notesStore.error,
})

watch(
  [() => foldersStore.selectedFolderId, () => [...tagsStore.selectedTags]],
  async () => {
    await loadNotes()
  },
)

onMounted(async () => {
  await loadNotes()
})

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
