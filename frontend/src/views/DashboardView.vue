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
        v-else-if="notesStore.notes && notesStore.notes.length === 0"
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

      <div v-else>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch">
          <Card
            v-for="note in notesStore.notes"
            :key="note.id"
            class="note-card cursor-pointer hover:shadow-lg transition-shadow"
            @click="openNote(note.id)"
          >
            <template #content>
              <div class="note-card-body">
                <h3 class="card-title">{{ note.title }}</h3>
                <div class="card-meta flex items-center justify-between gap-2 mt-1 mb-2">
                  <span class="shrink-0">{{ formatDate(note.updatedAt) }}</span>
                  <span
                    v-if="!foldersStore.selectedFolderId && note.folder"
                    class="flex items-center gap-1 min-w-0 max-w-[55%]"
                    v-tooltip.top="note.folder.name"
                  >
                    <i class="pi pi-folder shrink-0 text-xs" />
                    <span class="truncate">{{ note.folder.name }}</span>
                  </span>
                </div>
                <NoteTagsPreview
                  v-if="note.tags?.length"
                  :tags="note.tags"
                  class="mb-2"
                />
                <div class="card-preview note-card-preview">
                  {{ getNoteContentPreview(note.content) }}
                </div>
              </div>
            </template>
            <template #footer>
              <div class="note-card-actions">
                <Button
                  icon="pi pi-pencil"
                  text
                  rounded
                  @click.stop="openNoteInEditMode(note.id)"
                  v-tooltip.bottom="'Редактировать'"
                />
                <Button
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  @click.stop="confirmDelete(note)"
                  v-tooltip.bottom="'Удалить'"
                />
              </div>
            </template>
          </Card>
        </div>

        <div v-if="notesStore.pagination.totalPages > 1" class="mt-8 flex justify-center">
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
import Card from 'primevue/card'
import Button from 'primevue/button'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Paginator from 'primevue/paginator'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import AppLayout from '@/components/layout/AppLayout.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import NoteTagsPreview from '@/components/common/NoteTagsPreview.vue'
import { useNotesStore } from '@/stores/notes'
import { useFoldersStore } from '@/stores/folders'
import type { Note } from '@/types'
import { getNoteContentPreview } from '@/utils/note'

const router = useRouter()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()
const confirm = useConfirm()
const toast = useToast()

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
  } catch (error) {
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
      } catch (error) {
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
