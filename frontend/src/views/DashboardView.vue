<template>
  <AppLayout>
    <div class="page-container">
      <div class="page-header">
        <h1 class="page-title">
          {{ pageTitle }}
        </h1>
        <p class="page-subtitle">
          {{ pageSubtitle }}
        </p>
      </div>

      <div v-if="notesStore.isLoading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <div v-else-if="notesStore.error" class="py-12">
        <Message severity="error">{{ notesStore.error }}</Message>
      </div>

      <EmptyState
        v-else-if="isEmpty"
        :icon="foldersStore.selectedFolderId ? 'pi-folder-open' : 'pi-book'"
        :title="emptyMessage"
      >
        <template #actions>
          <Button
            :label="foldersStore.selectedFolderId ? 'Создать заметку' : 'Создать первую заметку'"
            icon="pi pi-plus"
            @click="createNewNote"
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

        <div v-if="notesStore.pagination.totalPages > 1" class="flex justify-center">
          <Paginator
            :rows="notesStore.pagination.perPage"
            :totalRecords="notesStore.pagination.total"
            :first="(notesStore.pagination.currentPage - 1) * notesStore.pagination.perPage"
            @page="onPageChange"
          />
        </div>
      </div>
    </div>

    <ConfirmDialog />
    <Toast />
  </AppLayout>
</template>

<script setup lang="ts">
import { onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Paginator from 'primevue/paginator'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import AppLayout from '@/components/layout/AppLayout.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import NoteCard from '@/components/dashboard/NoteCard.vue'
import { useNotesStore } from '@/stores/notes'
import { useFoldersStore } from '@/stores/folders'
import { useFavoriteToggle } from '@/composables/useFavoriteToggle'
import type { Note } from '@/types'

const router = useRouter()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()
const confirm = useConfirm()
const toast = useToast()
const { toggleFavorite } = useFavoriteToggle()

const showFavoritesBlock = computed(() => notesStore.pagination.currentPage === 1)

const isEmpty = computed(
  () =>
    notesStore.notes.length === 0 &&
    (!showFavoritesBlock.value || notesStore.favoriteNotes.length === 0)
)

const pageTitle = computed(() =>
  foldersStore.selectedFolder
    ? foldersStore.selectedFolder.name
    : 'Мои заметки'
)

const pageSubtitle = computed(() => {
  const total = notesStore.pagination.total
  const countLabel = `${total} ${pluralizeNotes(total)}`
  return foldersStore.selectedFolder
    ? `${countLabel} в этой папке`
    : `Всего заметок: ${total}`
})

const emptyMessage = computed(() =>
  foldersStore.selectedFolder
    ? 'В этой папке пока нет заметок'
    : 'У вас пока нет заметок'
)

async function loadNotes(page = 1, perPage = notesStore.pagination.perPage) {
  await notesStore.fetchNotes(page, perPage, foldersStore.selectedFolderId)
}

onMounted(async () => {
  await loadNotes()
})

watch(() => foldersStore.selectedFolderId, async () => {
  await loadNotes()
})

async function createNewNote() {
  try {
    const note = await notesStore.createNote({
      title: 'Новая заметка',
      content: '',
      folderId: foldersStore.selectedFolderId,
    })
    router.push({ name: 'note', params: { id: note.id }, query: { mode: 'edit' } })
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось создать заметку',
      life: 3000,
    })
  }
}

function openNote(id: string) {
  router.push({ name: 'note', params: { id }, query: { mode: 'preview' } })
}

function openNoteInEditMode(id: string) {
  router.push({ name: 'note', params: { id }, query: { mode: 'edit' } })
}

async function handleToggleFavorite(note: Note) {
  await toggleFavorite(note)
}

function confirmDelete(note: Note) {
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
        toast.add({
          severity: 'success',
          summary: 'Успешно',
          detail: 'Заметка удалена',
          life: 3000,
        })
      } catch {
        toast.add({
          severity: 'error',
          summary: 'Ошибка',
          detail: 'Не удалось удалить заметку',
          life: 3000,
        })
      }
    },
  })
}

async function onPageChange(event: any) {
  await loadNotes(event.page + 1, event.rows)
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
