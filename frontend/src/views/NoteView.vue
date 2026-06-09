<template>
  <AppLayout>
    <div class="h-[calc(100vh-4rem)] flex flex-col">
      <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <div class="flex-1 min-w-0">
              <InputText
                v-model="noteTitle"
                placeholder="Название заметки"
                class="w-full text-2xl font-bold"
                @input="handleTitleChange"
              />
            </div>
            
            <div class="flex items-center space-x-4">
              <SaveIndicator :status="saveStatus" />
              
              <SelectButton
                v-model="viewMode"
                :options="viewModeOptions"
                optionLabel="label"
                optionValue="value"
                class="hidden md:flex"
              >
                <template #option="slotProps">
                  <i :class="slotProps.option.icon"></i>
                </template>
              </SelectButton>

              <Button
                icon="pi pi-arrow-left"
                label="Назад"
                text
                @click="goBack"
                class="hidden sm:flex"
              />
              <Button
                icon="pi pi-arrow-left"
                text
                @click="goBack"
                class="sm:hidden"
                rounded
              />
            </div>
          </div>
        </div>
      </div>

      <div v-if="notesStore.isLoading" class="flex-1 flex items-center justify-center">
        <ProgressSpinner />
      </div>

      <div v-else-if="notesStore.error" class="flex-1 flex items-center justify-center">
        <Message severity="error">{{ notesStore.error }}</Message>
      </div>

      <div v-else class="flex-1 overflow-hidden flex">
        <div class="flex-1 flex flex-col md:flex-row">
          <div
            v-show="viewMode === 'edit' || viewMode === 'split'"
            :class="[
              'flex-1 border-r border-gray-200 dark:border-gray-700 overflow-auto',
              viewMode === 'split' ? 'w-full md:w-1/2' : 'w-full'
            ]"
          >
            <MarkdownEditor
              v-model="noteContent"
              @update:modelValue="handleContentChange"
            />
          </div>

          <div
            v-show="viewMode === 'preview' || viewMode === 'split'"
            :class="[
              'flex-1 overflow-auto',
              viewMode === 'split' ? 'w-full md:w-1/2' : 'w-full'
            ]"
          >
            <MarkdownPreview :content="noteContent" />
          </div>
        </div>

        <!-- Note Metadata Panel -->
        <NoteMetadata>
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
                <div>Создано: {{ formatDate(notesStore.currentNote?.createdAt) }}</div>
                <div>Обновлено: {{ formatDate(notesStore.currentNote?.updatedAt) }}</div>
              </div>
            </div>
          </div>
        </NoteMetadata>
      </div>

      <div class="md:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-2">
        <SelectButton
          v-model="viewMode"
          :options="viewModeOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        >
          <template #option="slotProps">
            <div class="flex items-center space-x-2">
              <i :class="slotProps.option.icon"></i>
              <span>{{ slotProps.option.label }}</span>
            </div>
          </template>
        </SelectButton>
      </div>
    </div>

    <Toast />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import SelectButton from 'primevue/selectbutton'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Toast from 'primevue/toast'
import AppLayout from '@/components/layout/AppLayout.vue'
import NoteMetadata from '@/components/layout/NoteMetadata.vue'
import MarkdownEditor from '@/components/editor/MarkdownEditor.vue'
import MarkdownPreview from '@/components/editor/MarkdownPreview.vue'
import SaveIndicator from '@/components/common/SaveIndicator.vue'
import FolderSelector from '@/components/common/FolderSelector.vue'
import NoteTagsEditor from '@/components/common/NoteTagsEditor.vue'
import { useNotesStore } from '@/stores/notes'
import { useAutosave } from '@/composables/useAutosave'
import type { ViewMode } from '@/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const notesStore = useNotesStore()

const noteTitle = ref('')
const noteContent = ref('')
const noteFolderId = ref<string | null>(null)
const noteTags = ref<string[]>([])
const viewMode = ref<ViewMode>('split')

const viewModeOptions = [
  { label: 'Редактор', value: 'edit', icon: 'pi pi-pencil' },
  { label: 'Разделение', value: 'split', icon: 'pi pi-table' },
  { label: 'Превью', value: 'preview', icon: 'pi pi-eye' },
]

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
  try {
    const note = await notesStore.fetchNoteById(noteId)
    noteTitle.value = note.title
    noteContent.value = note.content
    noteFolderId.value = note.folderId || null
    noteTags.value = note.tags?.map(t => t.name) || []
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

function goBack() {
  router.push({ name: 'dashboard' })
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
