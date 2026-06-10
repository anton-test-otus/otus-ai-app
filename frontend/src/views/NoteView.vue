<template>
  <AppLayout>
    <div class="h-[calc(100vh-4rem)] flex flex-col">
      <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="max-w-full mx-auto note-toolbar-padding">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between md:gap-4">
            <div class="flex items-center gap-2 min-w-0 flex-1">
              <Button
                icon="pi pi-arrow-left"
                text
                rounded
                class="note-action-btn md:hidden shrink-0"
                @click="goBack"
                v-tooltip.bottom="'Назад'"
              />
              <InputText
                v-model="noteTitle"
                placeholder="Название заметки"
                class="w-full min-w-0 note-title-field"
                @input="handleTitleChange"
              />
            </div>

            <div class="flex items-center justify-between md:justify-end gap-2 md:gap-4 shrink-0">
              <SaveIndicator :status="saveStatus" />

              <div class="flex items-center gap-1 md:gap-2">
                <Button
                  v-if="viewMode === 'preview'"
                  icon="pi pi-pencil"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn"
                  @click="switchToEditMode"
                  v-tooltip.bottom="'Редактировать'"
                />
                <Button
                  v-else
                  icon="pi pi-eye"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn"
                  @click="switchToPreviewMode"
                  v-tooltip.bottom="'Просмотр'"
                />

                <Button
                  v-if="isBelow3xl"
                  icon="pi pi-info-circle"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn"
                  @click="openMetadata"
                  v-tooltip.bottom="'Метаданные'"
                />

                <Button
                  icon="pi pi-history"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn hidden md:inline-flex"
                  @click="showVersionHistory = !showVersionHistory"
                  v-tooltip.bottom="'История версий'"
                />

                <Button
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  class="note-action-btn hidden md:inline-flex"
                  @click="confirmDelete"
                  v-tooltip.bottom="'Удалить заметку'"
                />

                <Button
                  icon="pi pi-ellipsis-v"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn md:hidden"
                  @click="toggleMobileMenu"
                  v-tooltip.bottom="'Ещё'"
                />
                <Menu ref="mobileMenu" :model="mobileMenuItems" popup />

                <Button
                  icon="pi pi-arrow-left"
                  label="Назад"
                  text
                  class="hidden md:flex"
                  @click="goBack"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!isNoteReady" class="flex-1 flex items-center justify-center">
        <ProgressSpinner />
      </div>

      <div v-else-if="notesStore.error" class="flex-1 flex items-center justify-center">
        <Message severity="error">{{ notesStore.error }}</Message>
      </div>

      <div v-else class="flex-1 overflow-hidden flex min-w-0">
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
          <div
            v-if="viewMode === 'edit'"
            class="flex-1 min-w-0 overflow-hidden flex flex-col"
          >
            <MarkdownEditor
              ref="editorRef"
              v-model="noteContent"
              @update:modelValue="handleContentChange"
              @insertWikiLink="showLinkModal = true"
            />
          </div>

          <div
            v-else
            class="flex-1 min-w-0 overflow-auto"
          >
            <MarkdownPreview :content="noteContent" />
          </div>
        </div>

        <!-- Note Metadata Panel -->
        <NoteMetadata ref="metadataRef">
          <div class="space-y-6">
            <FolderSelector
              v-model="noteFolderId"
              @update:model-value="handleFolderChange"
            />
            
            <Divider />
            
            <NoteTagsEditor
              v-model="noteTags"
              @update:model-value="handleTagsChange"
            />
            
            <Divider v-if="showVersionHistory" />
            
            <VersionHistoryPanel
              v-if="showVersionHistory && notesStore.currentNote"
              :note-id="notesStore.currentNote.id"
              :current-note="notesStore.currentNote"
              @close="showVersionHistory = false"
              @restore="handleVersionRestore"
            />
            
            <Divider />
            
            <BacklinksPanel
              v-if="notesStore.currentNote"
              :note-id="notesStore.currentNote.id"
            />
            
            <Divider />
            
            <div>
              <h4 class="text-sm font-semibold mb-2">Информация</h4>
              <div class="text-xs text-surface-500 dark:text-surface-400 space-y-1">
                <div>Создано: {{ formatDate(notesStore.currentNote?.createdAt) }}</div>
                <div>Обновлено: {{ formatDate(notesStore.currentNote?.updatedAt) }}</div>
              </div>
            </div>
          </div>
        </NoteMetadata>
      </div>
    </div>

    <Toast />
    <ConfirmDialog />
    <LinkNoteModal
      v-model:visible="showLinkModal"
      @select="handleLinkSelect"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import AppLayout from '@/components/layout/AppLayout.vue'
import NoteMetadata from '@/components/layout/NoteMetadata.vue'
import MarkdownEditor from '@/components/editor/MarkdownEditor.vue'
import MarkdownPreview from '@/components/editor/MarkdownPreview.vue'
import SaveIndicator from '@/components/common/SaveIndicator.vue'
import FolderSelector from '@/components/common/FolderSelector.vue'
import NoteTagsEditor from '@/components/common/NoteTagsEditor.vue'
import BacklinksPanel from '@/components/BacklinksPanel.vue'
import LinkNoteModal from '@/components/LinkNoteModal.vue'
import VersionHistoryPanel from '@/components/editor/VersionHistoryPanel.vue'
import { useNotesStore } from '@/stores/notes'
import { useAutosave } from '@/composables/useAutosave'
import { useBreakpoints } from '@/composables/useBreakpoints'
import type { ViewMode, RestoreVersionRequest } from '@/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const notesStore = useNotesStore()
const { isBelow3xl } = useBreakpoints()

const noteTitle = ref('')
const noteContent = ref('')
const noteFolderId = ref<string | null>(null)
const noteTags = ref<string[]>([])
const viewMode = ref<ViewMode>('preview')
const showLinkModal = ref(false)
const showVersionHistory = ref(false)
const editorRef = ref<InstanceType<typeof MarkdownEditor> | null>(null)
const mobileMenu = ref<InstanceType<typeof Menu> | null>(null)
const metadataRef = ref<InstanceType<typeof NoteMetadata> | null>(null)
const isNoteReady = ref(false)

const mobileMenuItems = computed(() => [
  {
    label: showVersionHistory.value ? 'Скрыть историю' : 'История версий',
    icon: 'pi pi-history',
    command: () => { showVersionHistory.value = !showVersionHistory.value },
  },
  {
    label: 'Удалить заметку',
    icon: 'pi pi-trash',
    command: () => confirmDelete(),
  },
])

function toggleMobileMenu(event: Event) {
  mobileMenu.value?.toggle(event)
}

function openMetadata() {
  metadataRef.value?.open()
}

const { saveStatus, saveError, triggerSave } = useAutosave(async () => {
  if (!notesStore.currentNote) return

  await notesStore.updateNote(notesStore.currentNote.id, {
    title: noteTitle.value,
    content: noteContent.value,
    folderId: noteFolderId.value,
    tags: noteTags.value,
  })
}, 2000)

onMounted(async () => {
  const noteId = route.params.id as string
  isNoteReady.value = false
  
  // Определяем режим просмотра из query параметра или по умолчанию 'preview'
  const modeFromQuery = route.query.mode as ViewMode | undefined
  if (modeFromQuery === 'edit' || modeFromQuery === 'preview') {
    viewMode.value = modeFromQuery
  }
  
  try {
    const note = await notesStore.fetchNoteById(noteId)
    noteTitle.value = note.title
    noteContent.value = note.content
    noteFolderId.value = note.folderId || null
    noteTags.value = note.tags?.map(t => t.name) || []
    isNoteReady.value = true
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить заметку',
      life: 3000,
    })
    router.push({ name: 'dashboard' })
  }
})

watch(
  () => route.query.mode,
  (mode) => {
    if (mode === 'edit' || mode === 'preview') {
      viewMode.value = mode
    }
  },
)

function handleTitleChange() {
  triggerSave()
}

function handleContentChange() {
  triggerSave()
}

function handleFolderChange() {
  triggerSave()
}

function handleTagsChange() {
  triggerSave()
}

function switchToEditMode() {
  viewMode.value = 'edit'
  router.replace({ 
    name: 'note', 
    params: { id: route.params.id },
    query: { mode: 'edit' }
  })
}

function switchToPreviewMode() {
  const latestContent = editorRef.value?.getMarkdown()
  if (latestContent !== undefined) {
    noteContent.value = latestContent
  }

  viewMode.value = 'preview'
  router.replace({ 
    name: 'note', 
    params: { id: route.params.id },
    query: { mode: 'preview' }
  })
}

function goBack() {
  router.push({ name: 'dashboard' })
}

function confirmDelete() {
  if (!notesStore.currentNote) return
  
  confirm.require({
    message: 'Вы уверены, что хотите удалить эту заметку? Она будет перемещена в корзину.',
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await notesStore.deleteNote(notesStore.currentNote!.id)
        toast.add({
          severity: 'success',
          summary: 'Успешно',
          detail: 'Заметка перемещена в корзину',
          life: 3000,
        })
        router.push({ name: 'dashboard' })
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

function handleLinkSelect(noteTitle: string) {
  const inserted = editorRef.value?.insertWikiLinkAtCursor(noteTitle)

  if (!inserted) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось вставить ссылку на заметку',
      life: 3000,
    })
    return
  }

  triggerSave()

  toast.add({
    severity: 'success',
    summary: 'Ссылка добавлена',
    detail: `Ссылка на «${noteTitle}» вставлена в позицию курсора`,
    life: 3000,
  })
}

async function handleVersionRestore(_versionId: string, mode: RestoreVersionRequest['mode']) {
  try {
    // Reload the note after restore
    const noteId = route.params.id as string
    const note = await notesStore.fetchNoteById(noteId)
    
    // Update local state
    noteTitle.value = note.title
    noteContent.value = note.content
    noteFolderId.value = note.folderId || null
    noteTags.value = note.tags?.map(t => t.name) || []
    
    let message = 'Версия восстановлена'
    if (mode === 'copy') {
      message = 'Новая заметка создана из версии'
    } else if (mode === 'create_version') {
      message = 'Версия восстановлена, текущее состояние сохранено'
    }
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: message,
      life: 3000,
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось восстановить версию',
      life: 3000,
    })
  }
}

function formatDate(dateString?: string): string {
  if (!dateString) return 'Неизвестно'
  return new Date(dateString).toLocaleString('ru-RU')
}

watch(saveError, (error) => {
  if (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка сохранения',
      detail: error,
      life: 3000,
    })
  }
})
</script>

<style scoped>
.note-action-btn {
  @apply !w-11 !h-11 md:!w-auto md:!h-auto;
}
</style>
