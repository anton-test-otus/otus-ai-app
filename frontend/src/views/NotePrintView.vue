<template>
  <div class="note-print-page min-h-screen bg-white text-gray-900">
    <div class="no-print note-print-toolbar border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
        <span class="text-sm text-gray-600">Предпросмотр печати</span>
        <div class="flex items-center gap-2">
          <Button
            label="Печать / PDF"
            icon="pi pi-print"
            @click="printPage"
          />
          <Button
            label="Закрыть"
            icon="pi pi-times"
            severity="secondary"
            text
            @click="closePage"
          />
        </div>
      </div>
    </div>

    <LoadingState v-if="isLoading" class="min-h-[50vh]" />

    <ErrorState
      v-else-if="error"
      class="min-h-[50vh]"
      :message="error"
      @retry="loadPrintNote"
    />

    <article v-else-if="metadata" class="note-print-content max-w-4xl mx-auto px-4 py-8">
      <ExportMetadataPanel :metadata="metadata" />
      <hr class="note-print-divider" />
      <div class="note-print-body">
        <MarkdownPreview :content="noteContent" />
      </div>
    </article>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import LoadingState from '@/components/common/LoadingState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import MarkdownPreview from '@/components/editor/MarkdownPreview.vue'
import ExportMetadataPanel from '@/components/export/NoteExportMetadata.vue'
import { useNotesStore } from '@/stores/notes'
import { useFoldersStore } from '@/stores/folders'
import { buildExportMetadata, type NoteExportMetadata } from '@/utils/exportMetadata'
import { sanitizeExportFilename } from '@/utils/exportNote'
import { getApiErrorMessage } from '@/utils/apiError'

const route = useRoute()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()

const previousDocumentTitle = document.title

const isLoading = ref(true)
const error = ref<string | null>(null)
const metadata = ref<NoteExportMetadata | null>(null)
const noteContent = ref('')

const PREVIEW_RENDER_DELAY_MS = 600

async function loadPrintNote() {
  const noteId = route.params.id
  if (typeof noteId !== 'string') {
    error.value = 'Некорректный идентификатор заметки'
    isLoading.value = false
    return
  }

  isLoading.value = true
  error.value = null
  metadata.value = null

  try {
    await foldersStore.fetchFolders()
    const note = await notesStore.fetchNoteById(noteId)
    metadata.value = buildExportMetadata(note, foldersStore.getFolderById.bind(foldersStore))
    noteContent.value = note.content
    document.title = sanitizeExportFilename(note.title, note.id)
  } catch (e: unknown) {
    error.value = getApiErrorMessage(e, 'Не удалось загрузить заметку для печати')
  } finally {
    isLoading.value = false
  }
}

function printPage() {
  window.print()
}

function closePage() {
  window.close()
}

function scheduleAutoPrint() {
  if (route.query.auto !== '1' || error.value || !metadata.value) {
    return
  }

  window.setTimeout(() => {
    window.print()
  }, PREVIEW_RENDER_DELAY_MS)
}

onMounted(async () => {
  await loadPrintNote()
  scheduleAutoPrint()
})

onUnmounted(() => {
  document.title = previousDocumentTitle
})
</script>

<style scoped>
.note-print-divider {
  @apply my-6 border-gray-200;
}

.note-print-body :deep(.markdown-preview) {
  @apply bg-white;
}

.note-print-body :deep(.milkdown-preview-root .wiki-link-exists) {
  @apply text-blue-700 border-blue-300;
}
</style>
