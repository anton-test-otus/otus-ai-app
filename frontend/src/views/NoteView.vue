<template>
  <div class="h-[calc(100vh-4rem)] flex flex-col">
      <div class="border-b app-border app-chrome">
        <div class="max-w-full mx-auto note-toolbar-padding">
          <div class="flex items-center justify-between gap-2 min-w-0">
            <div class="flex items-center gap-2 min-w-0 flex-1">
              <Button
                v-if="notesStore.currentNote"
                :icon="notesStore.currentNote.isFavorite ? 'pi pi-star-fill' : 'pi pi-star'"
                :severity="notesStore.currentNote.isFavorite ? 'warn' : 'secondary'"
                text
                rounded
                class="note-action-btn shrink-0"
                @click="handleToggleFavorite"
                v-tooltip.bottom="notesStore.currentNote.isFavorite ? 'Убрать из избранного' : 'В избранное'"
              />
              <InputText
                v-model="noteTitle"
                placeholder="Название заметки"
                class="w-full min-w-0 note-title-field"
                @focus="isTitleFocused = true"
                @blur="isTitleFocused = false"
                @input="handleTitleChange"
              />
            </div>

            <div v-show="!isTitleFocused" class="flex items-center gap-1 md:gap-4 shrink-0">
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
                  v-if="!isDraft"
                  icon="pi pi-history"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn"
                  @click="showVersionHistory = !showVersionHistory"
                  v-tooltip.bottom="'История версий'"
                />

                <Button
                  v-if="!isDraft && notesStore.currentNote"
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  class="note-action-btn"
                  @click="confirmDelete"
                  v-tooltip.bottom="'Удалить заметку'"
                />

                <Button
                  icon="pi pi-arrow-left"
                  text
                  rounded
                  class="note-action-btn md:hidden"
                  @click="goBack"
                  v-tooltip.bottom="'Назад'"
                />
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
              v-if="!isDraft && showVersionHistory && notesStore.currentNote"
              :note-id="notesStore.currentNote.id"
              :current-note="notesStore.currentNote"
              @close="showVersionHistory = false"
              @restore="handleVersionRestore"
            />
            
            <Divider />
            
            <BacklinksPanel
              v-if="!isDraft && notesStore.currentNote"
              :note-id="notesStore.currentNote.id"
            />
            
            <Divider />
            
            <div>
              <h4 class="text-sm font-semibold mb-2">Информация</h4>
              <div class="text-xs text-surface-500 dark:text-surface-400 space-y-1">
                <div v-if="isDraft">Черновик — сохранится после ввода текста</div>
                <template v-else>
                  <div>Создано: {{ formatDate(notesStore.currentNote?.createdAt) }}</div>
                  <div>Обновлено: {{ formatDate(notesStore.currentNote?.updatedAt) }}</div>
                </template>
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
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
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
import { useFoldersStore } from '@/stores/folders'
import { useAutosave } from '@/composables/useAutosave'
import { useCreateNote } from '@/composables/useCreateNote'
import { DEFAULT_NOTE_TITLE, hasNoteBody } from '@/utils/note'
import { useUserSettings } from '@/composables/useUserSettings'
import { useBreakpoints } from '@/composables/useBreakpoints'
import { useFavoriteToggle } from '@/composables/useFavoriteToggle'
import type { ViewMode, RestoreVersionRequest } from '@/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()
const { createNoteWithContent } = useCreateNote()
const { isBelow3xl } = useBreakpoints()
const { effectiveAutosaveDelayMs } = useUserSettings()
const { toggleFavorite } = useFavoriteToggle()

const noteTitle = ref('')
const noteContent = ref('')
const noteFolderId = ref<string | null>(null)
const noteTags = ref<string[]>([])
const viewMode = ref<ViewMode>('preview')
const showLinkModal = ref(false)
const showVersionHistory = ref(false)
const isTitleFocused = ref(false)
const editorRef = ref<InstanceType<typeof MarkdownEditor> | null>(null)
const metadataRef = ref<InstanceType<typeof NoteMetadata> | null>(null)
const isNoteReady = ref(false)

const isDraft = computed(() => route.name === 'note-new')

interface NoteDraftSnapshot {
  title: string
  content: string
  folderId: string | null
  tags: string[]
}

let savedSnapshot: NoteDraftSnapshot = {
  title: '',
  content: '',
  folderId: null,
  tags: [],
}

function getCurrentContent(): string {
  if (viewMode.value === 'edit') {
    const latestContent = editorRef.value?.getMarkdown()
    if (latestContent !== undefined) {
      return latestContent
    }
  }
  return noteContent.value
}

function createSnapshot(): NoteDraftSnapshot {
  return {
    title: noteTitle.value,
    content: getCurrentContent(),
    folderId: noteFolderId.value,
    tags: [...noteTags.value].sort(),
  }
}

function syncSavedSnapshot() {
  savedSnapshot = createSnapshot()
}

function hasUnsavedChanges(): boolean {
  const current = createSnapshot()
  return (
    current.title !== savedSnapshot.title
    || current.content !== savedSnapshot.content
    || current.folderId !== savedSnapshot.folderId
    || current.tags.join('\0') !== savedSnapshot.tags.join('\0')
  )
}

function syncEditorContent() {
  if (viewMode.value !== 'edit') {
    return
  }

  const latestContent = editorRef.value?.getMarkdown()
  if (latestContent !== undefined && latestContent !== noteContent.value) {
    noteContent.value = latestContent
  }
}

let isPersistingDraft = false

async function saveNoteIfChanged() {
  const content = getCurrentContent()

  if (isDraft.value) {
    if (!hasNoteBody(content) || isPersistingDraft) {
      return
    }
    await persistDraftNote(content)
    return
  }

  if (!notesStore.currentNote || !hasUnsavedChanges()) {
    return
  }

  if (!hasNoteBody(content)) {
    return
  }

  await notesStore.updateNote(notesStore.currentNote.id, {
    title: noteTitle.value,
    content,
    folderId: noteFolderId.value,
    tags: noteTags.value,
  })
  noteContent.value = content
  syncSavedSnapshot()
}

async function persistDraftNote(content: string) {
  if (isPersistingDraft) {
    return
  }

  isPersistingDraft = true
  try {
    const note = await createNoteWithContent({
      title: noteTitle.value,
      content,
      folderId: noteFolderId.value,
    })

    if (noteTags.value.length > 0) {
      await notesStore.updateNote(note.id, { tags: noteTags.value })
    }

    noteContent.value = content
    syncSavedSnapshot()

    await router.replace({
      name: 'note',
      params: { id: note.id },
      query: { mode: viewMode.value },
    })
  } finally {
    isPersistingDraft = false
  }
}

function shouldAutosave(): boolean {
  if (!hasUnsavedChanges()) {
    return false
  }
  if (isDraft.value) {
    return hasNoteBody(getCurrentContent()) && !isPersistingDraft
  }
  return true
}

const { saveStatus, saveError, triggerSave, flushSave, reset: resetAutosave } = useAutosave(
  saveNoteIfChanged,
  { hasChanges: shouldAutosave, delay: effectiveAutosaveDelayMs },
)

async function leaveNote(): Promise<void> {
  syncEditorContent()

  if (isDraft.value) {
    const content = getCurrentContent()
    if (hasNoteBody(content) && !isPersistingDraft) {
      await persistDraftNote(content)
    }
    return
  }

  await flushSave()
}

function openMetadata() {
  metadataRef.value?.open()
}

async function handleToggleFavorite() {
  if (!notesStore.currentNote) return
  await toggleFavorite(notesStore.currentNote)
}

async function loadNote(noteId: string) {
  isNoteReady.value = false
  isTitleFocused.value = false
  resetAutosave()

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
    syncSavedSnapshot()
    isNoteReady.value = true
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить заметку',
      life: 3000,
    })
    router.push({ name: 'dashboard' })
  }
}

function initDraft() {
  isNoteReady.value = false
  isTitleFocused.value = false
  resetAutosave()
  notesStore.clearCurrentNote()

  const modeFromQuery = route.query.mode as ViewMode | undefined
  viewMode.value = modeFromQuery === 'preview' ? 'preview' : 'edit'

  const folderIdFromQuery = typeof route.query.folderId === 'string'
    ? route.query.folderId
    : null

  noteTitle.value = DEFAULT_NOTE_TITLE
  noteContent.value = ''
  noteFolderId.value = folderIdFromQuery ?? foldersStore.selectedFolderId ?? null
  noteTags.value = []
  syncSavedSnapshot()
  isNoteReady.value = true
}

onMounted(() => {
  if (isDraft.value) {
    initDraft()
  } else {
    loadNote(route.params.id as string)
  }
})

watch(
  () => [route.name, route.params.id] as const,
  async ([newName, newId], [oldName, oldId]) => {
    if (newName === oldName && newId === oldId) {
      return
    }
    if (!oldName) {
      return
    }

    const transitioningFromDraft = oldName === 'note-new' && newName === 'note'

    if (!transitioningFromDraft) {
      try {
        await leaveNote()
      } catch {
        return
      }
    }

    if (newName === 'note-new') {
      initDraft()
    } else if (newName === 'note' && typeof newId === 'string' && !transitioningFromDraft) {
      await loadNote(newId)
    }
  },
)

onBeforeRouteLeave(async () => {
  if (!isNoteReady.value) {
    return true
  }

  try {
    await leaveNote()
    return true
  } catch {
    return false
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
  syncEditorContent()
  triggerSave()

  viewMode.value = 'preview'
  router.replace({
    name: 'note',
    params: { id: route.params.id },
    query: { mode: 'preview' },
  })
}

async function goBack() {
  try {
    await leaveNote()
    router.push({ name: 'dashboard' })
  } catch {
    // Ошибка отображается через watch(saveError)
  }
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
    syncSavedSnapshot()
    
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

.note-title-field :deep(.p-inputtext) {
  @apply min-w-0 truncate;
}
</style>
