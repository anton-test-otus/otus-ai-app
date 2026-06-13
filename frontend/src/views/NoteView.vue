<template>
  <div class="h-[calc(100vh-4rem)] flex min-w-0 overflow-hidden">
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <div class="border-b app-border app-chrome shrink-0">
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
              <SaveIndicator
                :status="saveStatus"
                :saved-at="lastSavedAt"
                v-tooltip.bottom="`Сохранить (${formatShortcutKeys(SHORTCUT_KEYS.save)})`"
              />

              <div class="flex items-center gap-1 md:gap-2 overflow-visible">
                <Button
                  v-if="viewMode === 'preview'"
                  icon="pi pi-pencil"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn"
                  @click="switchToEditMode"
                  v-tooltip.bottom="`Редактировать (${formatShortcutKeys(SHORTCUT_KEYS.toggleMode)})`"
                />
                <Button
                  v-else
                  icon="pi pi-eye"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn"
                  @click="switchToPreviewMode"
                  v-tooltip.bottom="`Просмотр (${formatShortcutKeys(SHORTCUT_KEYS.toggleMode)})`"
                />

                <span
                  v-if="!isDraft && notesStore.currentNote"
                  class="inline-flex shrink-0"
                  v-tooltip.bottom="graphButtonTooltip"
                  :title="showLinksGraph ? undefined : graphButtonTooltip"
                >
                  <Button
                    icon="pi pi-share-alt"
                    severity="secondary"
                    text
                    rounded
                    class="note-action-btn"
                    :disabled="!showLinksGraph"
                    @click="showGraphDialog = true"
                  />
                </span>

                <span
                  v-if="!isDraft && notesStore.currentNote"
                  class="inline-flex shrink-0"
                  v-tooltip.bottom="versionButtonTooltip"
                  :title="hasVersions ? undefined : versionButtonTooltip"
                >
                  <Button
                    icon="pi pi-history"
                    severity="secondary"
                    text
                    rounded
                    class="note-action-btn"
                    :disabled="!hasVersions"
                    @click="showVersionHistoryDialog = true"
                  />
                </span>

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
                  v-if="!isDraft && notesStore.currentNote"
                  icon="pi pi-download"
                  severity="secondary"
                  text
                  rounded
                  class="note-action-btn"
                  :loading="isExporting"
                  :disabled="isExporting"
                  @click="toggleExportMenu"
                  v-tooltip.bottom="'Экспорт'"
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
                  v-tooltip.bottom="`Назад (${formatShortcutKeys(SHORTCUT_KEYS.goBack)})`"
                />
                <Button
                  icon="pi pi-arrow-left"
                  label="Назад"
                  text
                  class="hidden md:flex"
                  @click="goBack"
                  v-tooltip.bottom="`Назад (${formatShortcutKeys(SHORTCUT_KEYS.goBack)})`"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <LoadingState
        v-if="!isNoteReady && notesStore.isLoading"
        class="flex-1"
      />

      <ErrorState
        v-else-if="!isNoteReady && notesStore.error"
        class="flex-1"
        :message="notesStore.error"
        @retry="retryLoadNote"
      />

      <div v-else class="flex-1 overflow-hidden flex flex-col min-w-0">
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
    </div>

    <NoteMetadata v-if="isNoteReady && !notesStore.error" ref="metadataRef">
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

        <Divider />

        <div>
          <h4 class="text-sm font-semibold mb-2">Информация</h4>
          <div class="text-xs text-surface-500 dark:text-surface-400 space-y-1">
            <div v-if="isDraft">Черновик — сохранится после ввода текста</div>
            <template v-else>
              <div>Создано: {{ formatDate(notesStore.currentNote?.createdAt) }}</div>
              <div>Обновлено: {{ formatDate(notesStore.currentNote?.updatedAt) }}</div>
              <div>Версий: {{ versionCountLabel }}</div>
            </template>
          </div>
        </div>
      </div>
    </NoteMetadata>
  </div>

  <NoteLinksGraphDialog
    v-if="!isDraft && notesStore.currentNote"
    v-model:visible="showGraphDialog"
    :note-id="notesStore.currentNote.id"
    :focus-note-id="notesStore.currentNote.id"
  />

  <VersionHistoryDialog
    v-if="!isDraft && notesStore.currentNote"
    v-model:visible="showVersionHistoryDialog"
    :note-id="notesStore.currentNote.id"
    :current-note="notesStore.currentNote"
    @restore="handleVersionRestore"
  />

  <LinkNoteModal
      v-model:visible="showLinkModal"
      :exclude-note-id="notesStore.currentNote?.id ?? null"
      @select="handleLinkSelect"
    />

  <Menu ref="exportMenuRef" :model="exportMenuItems" popup />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import Menu from 'primevue/menu'
import LoadingState from '@/components/common/LoadingState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import NoteMetadata from '@/components/layout/NoteMetadata.vue'
import { useAppToast } from '@/composables/useAppToast'
import MarkdownEditor from '@/components/editor/MarkdownEditor.vue'
import MarkdownPreview from '@/components/editor/MarkdownPreview.vue'
import SaveIndicator from '@/components/common/SaveIndicator.vue'
import FolderSelector from '@/components/common/FolderSelector.vue'
import NoteTagsEditor from '@/components/common/NoteTagsEditor.vue'
import NoteLinksGraphDialog from '@/components/notes/NoteLinksGraphDialog.vue'
import VersionHistoryDialog from '@/components/editor/VersionHistoryDialog.vue'
import LinkNoteModal, { type SelectedWikiLinkNote } from '@/components/LinkNoteModal.vue'
import { useNotesStore } from '@/stores/notes'
import { useFoldersStore } from '@/stores/folders'
import { useAutosave } from '@/composables/useAutosave'
import { useCreateNote, syncActiveNoteContext } from '@/composables/useCreateNote'
import { DEFAULT_NOTE_TITLE, hasNoteBody } from '@/utils/note'
import { hasNoteLinks } from '@/utils/noteGraph'
import { useUserSettings } from '@/composables/useUserSettings'
import { useBreakpoints } from '@/composables/useBreakpoints'
import { useFavoriteToggle } from '@/composables/useFavoriteToggle'
import { useNoteKeyboardShortcuts } from '@/composables/useAppKeyboardShortcuts'
import { useNoteExport } from '@/composables/useNoteExport'
import { deriveAutoTitleFromMarkdown } from '@/utils/autoTitle'
import { sanitizeNoteContent, sanitizeNoteTitle } from '@/utils/sanitizeText'
import { formatVersionCount } from '@/utils/version'
import type { ViewMode, RestoreVersionRequest } from '@/types'
import { formatShortcutKeys, SHORTCUT_KEYS } from '@/constants/keyboardShortcuts'

const route = useRoute()
const router = useRouter()
const { showSuccess, showError } = useAppToast()
const confirm = useConfirm()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()
const { createNoteWithContent } = useCreateNote()
const { isBelow3xl } = useBreakpoints()
const { effectiveAutosaveDelayMs } = useUserSettings()
const { toggleFavorite } = useFavoriteToggle()
const { isExporting, exportMarkdown, openPrintView } = useNoteExport()

const noteTitle = ref('')
const noteContent = ref('')
const noteFolderId = ref<string | null>(null)
const noteTags = ref<string[]>([])
const viewMode = ref<ViewMode>('preview')
const showLinkModal = ref(false)
const showGraphDialog = ref(false)
const showVersionHistoryDialog = ref(false)
const isTitleFocused = ref(false)
const titleWasManuallyEdited = ref(false)
const editorRef = ref<InstanceType<typeof MarkdownEditor> | null>(null)
const metadataRef = ref<InstanceType<typeof NoteMetadata> | null>(null)
const exportMenuRef = ref<InstanceType<typeof Menu> | null>(null)
const isNoteReady = ref(false)

const isDraft = computed(() => route.name === 'note-new')

const showLinksGraph = computed(() => {
  const stats = notesStore.currentNote?.linkStats
  if (!stats) {
    return false
  }

  return hasNoteLinks(stats.incoming, stats.outgoing)
})

const hasVersions = computed(() => (notesStore.currentNote?.versionCount ?? 0) > 0)

const graphButtonTooltip = computed(() => (
  showLinksGraph.value ? 'Связанные заметки' : 'Нет wiki-связей с другими заметками'
))

const versionButtonTooltip = computed(() => (
  hasVersions.value ? 'История версий' : 'Версий пока нет'
))

const versionCountLabel = computed(() => {
  const count = notesStore.currentNote?.versionCount
  return formatVersionCount(count ?? 0)
})

function syncNoteContextForCreate() {
  syncActiveNoteContext({
    folderId: noteFolderId.value,
    tags: [...noteTags.value],
  })
}

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

function normalizeEditableFields(): { title: string; content: string } {
  const title = sanitizeNoteTitle(noteTitle.value)
  if (title !== noteTitle.value) {
    noteTitle.value = title
  }

  let content = getCurrentContent()
  const sanitizedContent = sanitizeNoteContent(content)
  if (sanitizedContent !== content) {
    content = sanitizedContent
    noteContent.value = sanitizedContent
  }

  return { title, content }
}

let persistDraftPromise: Promise<void> | null = null

function isDraftPersistInFlight(): boolean {
  return persistDraftPromise !== null
}

async function saveNoteIfChanged() {
  const { title, content } = normalizeEditableFields()

  if (isDraft.value) {
    if (!hasUnsavedChanges() || !hasNoteBody(content) || isDraftPersistInFlight()) {
      return
    }
    await persistDraftNote(content, title)
    return
  }

  if (!notesStore.currentNote || !hasUnsavedChanges()) {
    return
  }

  if (!hasNoteBody(content)) {
    return
  }

  await notesStore.updateNote(notesStore.currentNote.id, {
    title,
    content,
    folderId: noteFolderId.value,
    tags: noteTags.value,
  })
  noteContent.value = content
  syncSavedSnapshot()
}

async function persistDraftNote(content: string, title = noteTitle.value): Promise<void> {
  if (persistDraftPromise) {
    return persistDraftPromise
  }

  if (!hasUnsavedChanges() || !hasNoteBody(content)) {
    return
  }

  persistDraftPromise = (async () => {
    const note = await createNoteWithContent({
      title,
      content,
      folderId: noteFolderId.value,
      tags: noteTags.value,
    })

    noteContent.value = content
    syncSavedSnapshot()

    await router.replace({
      name: 'note',
      params: { id: note.id },
      query: { mode: viewMode.value },
    })
  })().finally(() => {
    persistDraftPromise = null
  })

  return persistDraftPromise
}

function shouldAutosave(): boolean {
  if (!hasUnsavedChanges()) {
    return false
  }
  if (isDraft.value) {
    return hasNoteBody(getCurrentContent()) && !isDraftPersistInFlight()
  }
  return true
}

const { saveStatus, saveError, lastSavedAt, triggerSave, flushSave, reset: resetAutosave } = useAutosave(
  saveNoteIfChanged,
  { hasChanges: shouldAutosave, delay: effectiveAutosaveDelayMs },
)

useNoteKeyboardShortcuts({
  onSave: async () => {
    syncEditorContent()
    await flushSave()
  },
  onToggleMode: () => {
    if (viewMode.value === 'preview') {
      switchToEditMode()
    } else {
      switchToPreviewMode()
    }
  },
  onGoBack: goBack,
})

function maybeApplyAutoTitle() {
  if (titleWasManuallyEdited.value) {
    return
  }

  const content = getCurrentContent()
  const autoTitle = deriveAutoTitleFromMarkdown(content)
  if (!autoTitle || autoTitle === noteTitle.value) {
    return
  }

  noteTitle.value = autoTitle
}

async function leaveNote(): Promise<void> {
  syncEditorContent()
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
    titleWasManuallyEdited.value = note.title !== DEFAULT_NOTE_TITLE
    syncSavedSnapshot()
    syncNoteContextForCreate()
    isNoteReady.value = true
  } catch {
    // Ошибка отображается через ErrorState
  }
}

async function retryLoadNote() {
  if (isDraft.value) {
    return
  }

  const noteId = route.params.id
  if (typeof noteId === 'string') {
    await loadNote(noteId)
  }
}

function initDraft() {
  isNoteReady.value = false
  isTitleFocused.value = false
  resetAutosave()
  persistDraftPromise = null
  notesStore.clearCurrentNote()

  const modeFromQuery = route.query.mode as ViewMode | undefined
  viewMode.value = modeFromQuery === 'preview' ? 'preview' : 'edit'

  const folderIdFromQuery = typeof route.query.folderId === 'string'
    ? route.query.folderId
    : null

  const tagsFromQuery = route.query.tags
  const tags: string[] = Array.isArray(tagsFromQuery)
    ? tagsFromQuery.filter((tag): tag is string => typeof tag === 'string')
    : typeof tagsFromQuery === 'string'
      ? [tagsFromQuery]
      : []

  noteTitle.value = DEFAULT_NOTE_TITLE
  noteContent.value = ''
  noteFolderId.value = folderIdFromQuery ?? foldersStore.selectedFolderId ?? null
  noteTags.value = tags
  titleWasManuallyEdited.value = false
  syncSavedSnapshot()
  syncNoteContextForCreate()
  isNoteReady.value = true
}

watch([noteFolderId, noteTags], syncNoteContextForCreate, { deep: true })

onMounted(() => {
  if (isDraft.value) {
    initDraft()
  } else {
    loadNote(route.params.id as string)
  }
})

onUnmounted(() => {
  syncActiveNoteContext(null)
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
  const sanitized = sanitizeNoteTitle(noteTitle.value)
  if (sanitized !== noteTitle.value) {
    noteTitle.value = sanitized
  }
  titleWasManuallyEdited.value = true
  triggerSave()
}

function handleContentChange() {
  maybeApplyAutoTitle()
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
  if (isDraft.value) {
    router.replace({
      name: 'note-new',
      query: { ...route.query, mode: 'edit' },
    })
    return
  }
  router.replace({
    name: 'note',
    params: { id: route.params.id as string },
    query: { mode: 'edit' },
  })
}

function switchToPreviewMode() {
  syncEditorContent()
  triggerSave()

  viewMode.value = 'preview'
  if (isDraft.value) {
    router.replace({
      name: 'note-new',
      query: { ...route.query, mode: 'preview' },
    })
    return
  }
  router.replace({
    name: 'note',
    params: { id: route.params.id as string },
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
        showSuccess('Заметка перемещена в корзину')
        router.push({ name: 'dashboard' })
      } catch (error) {
        showError(error, 'Не удалось удалить заметку')
      }
    },
  })
}

function handleLinkSelect(note: SelectedWikiLinkNote) {
  const inserted = editorRef.value?.insertWikiLinkAtCursor(note.id, note.title)

  if (!inserted) {
    showError(null, 'Не удалось вставить ссылку на заметку')
    return
  }

  triggerSave()

  showSuccess(
    `Ссылка на «${note.title}» вставлена в позицию курсора`,
    'Ссылка добавлена',
  )
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
    titleWasManuallyEdited.value = note.title !== DEFAULT_NOTE_TITLE
    syncSavedSnapshot()
    
    let message = 'Версия восстановлена'
    if (mode === 'copy') {
      message = 'Новая заметка создана из версии'
    } else if (mode === 'create_version') {
      message = 'Версия восстановлена, текущее состояние сохранено'
    }
    
    showSuccess(message)
  } catch (error) {
    showError(error, 'Не удалось восстановить версию')
  }
}

function formatDate(dateString?: string): string {
  if (!dateString) return 'Неизвестно'
  return new Date(dateString).toLocaleString('ru-RU')
}

const exportMenuItems = [
  {
    label: 'Markdown (.md)',
    icon: 'pi pi-file',
    command: () => {
      void handleExportMarkdown()
    },
  },
  {
    label: 'PDF (печать)',
    icon: 'pi pi-print',
    command: () => {
      void handleExportPdf()
    },
  },
]

function toggleExportMenu(event: Event) {
  exportMenuRef.value?.toggle(event)
}

async function handleExportMarkdown() {
  const note = notesStore.currentNote
  if (!note) {
    return
  }

  syncEditorContent()
  try {
    await flushSave()
    await exportMarkdown({
      noteId: note.id,
      title: noteTitle.value,
      content: getCurrentContent(),
      folderId: noteFolderId.value,
      tags: [...noteTags.value],
      createdAt: note.createdAt,
      updatedAt: note.updatedAt,
    })
    showSuccess('Файл Markdown скачан')
  } catch {
    // toast уже показан в useNoteExport
  }
}

async function handleExportPdf() {
  if (!notesStore.currentNote) {
    return
  }

  syncEditorContent()
  try {
    await flushSave()
    openPrintView(notesStore.currentNote.id)
  } catch (error) {
    showError(error, 'Не удалось подготовить печать')
  }
}

watch(saveError, (error) => {
  if (error) {
    showError(error, 'Ошибка сохранения', 'Ошибка сохранения')
  }
})
</script>

<style scoped>
.note-action-btn {
  @apply shrink-0 overflow-visible !w-11 !h-11 md:!w-auto md:!h-auto md:!p-1;
}

.note-title-field :deep(.p-inputtext) {
  @apply min-w-0 truncate;
}
</style>
