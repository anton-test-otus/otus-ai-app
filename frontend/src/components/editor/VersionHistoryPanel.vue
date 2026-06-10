<template>
  <div class="version-history-panel">
    <div class="versions-header flex justify-between items-center mb-4">
      <h3 class="section-title">История версий</h3>
      <Button
        icon="pi pi-times"
        text
        rounded
        @click="$emit('close')"
        v-tooltip.top="'Закрыть'"
      />
    </div>

    <div v-if="loading" class="flex justify-center py-4">
      <ProgressSpinner style="width: 30px; height: 30px" stroke-width="4" />
    </div>

    <div
      v-else-if="error"
      class="text-sm text-red-600 bg-red-50 dark:bg-red-900/20 p-3 rounded-md"
    >
      {{ error }}
    </div>

    <EmptyState
      v-else-if="!versions || versions.length === 0"
      icon="pi-history"
      title="Версий пока нет"
      compact
    />

    <div v-else class="versions-list stack-items">
      <div class="current-version-indicator px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
        <div class="flex items-center gap-2">
          <i class="pi pi-file text-blue-600 dark:text-blue-400" />
          <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Текущая версия</span>
        </div>
      </div>

      <div
        v-for="(version, index) in versions"
        :key="version.id"
        class="version-item px-3 py-3 rounded-md cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors border app-border"
        :class="selectedVersion?.id === version.id ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-500' : ''"
        @click="selectVersion(version)"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <i class="pi pi-history text-sm text-primary-500" />
              <span class="text-xs text-muted">
                {{ formatRelativeDate(version.createdAt) }}
              </span>
            </div>
            <p class="text-sm font-medium text-surface-900 dark:text-surface-100 truncate">
              {{ version.title }}
            </p>
            <p class="text-xs text-muted mt-1">
              Версия {{ versions.length - index }} из {{ versions.length }}
            </p>
          </div>
          <div class="flex items-center gap-1 ml-2 shrink-0">
            <Button
              v-if="selectedVersion?.id === version.id"
              icon="pi pi-eye"
              text
              size="small"
              rounded
              @click.stop="showDiff"
              v-tooltip.top="'Просмотр изменений'"
            />
            <Button
              icon="pi pi-history"
              text
              size="small"
              rounded
              severity="success"
              @click.stop="confirmRestore(version)"
              v-tooltip.top="'Восстановить эту версию'"
            />
          </div>
        </div>
      </div>
    </div>

    <Dialog
      v-model:visible="showDiffModal"
      modal
      :header="diffModalHeader"
      :style="MODAL_WIDTH.xl"
      :breakpoints="{ '960px': '90vw', '640px': '95vw' }"
    >
      <VersionDiff
        v-if="selectedVersion && currentNote"
        :old-title="selectedVersion.title"
        :old-content="selectedVersion.content"
        :new-title="currentNote.title"
        :new-content="currentNote.content"
      />
    </Dialog>

    <RestoreVersionModal
      v-model="showRestoreModal"
      :version-date="versionToRestore?.createdAt || ''"
      @confirm="handleRestore"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import EmptyState from '@/components/common/EmptyState.vue'
import { MODAL_WIDTH } from '@/constants/modal'
import { formatRelativeDate } from '@/utils/date'
import { useNoteVersions } from '@/composables/useNoteVersions'
import VersionDiff from './VersionDiff.vue'
import RestoreVersionModal from './RestoreVersionModal.vue'
import type { Note, NoteVersion, RestoreVersionRequest } from '@/types'

interface Props {
  noteId: string
  currentNote: Note
}

interface Emits {
  (e: 'close'): void
  (e: 'restore', versionId: string, mode: RestoreVersionRequest['mode']): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { versions, loading, error, fetchVersions, restoreVersion } = useNoteVersions()

const selectedVersion = ref<NoteVersion | null>(null)
const showDiffModal = ref(false)
const showRestoreModal = ref(false)
const versionToRestore = ref<NoteVersion | null>(null)

const diffModalHeader = computed(() => {
  if (!selectedVersion.value) return 'Изменения'
  return `Изменения от ${formatRelativeDate(selectedVersion.value.createdAt)}`
})

const selectVersion = (version: NoteVersion) => {
  selectedVersion.value = version
}

const showDiff = () => {
  if (selectedVersion.value) {
    showDiffModal.value = true
  }
}

const confirmRestore = (version: NoteVersion) => {
  versionToRestore.value = version
  showRestoreModal.value = true
}

const handleRestore = async (mode: RestoreVersionRequest['mode']) => {
  if (!versionToRestore.value) return

  try {
    await restoreVersion(props.noteId, versionToRestore.value.id, mode)
    emit('restore', versionToRestore.value.id, mode)
    versionToRestore.value = null
  } catch (error) {
    console.error('Failed to restore version:', error)
  }
}

watch(
  () => props.noteId,
  async (newNoteId) => {
    if (newNoteId) {
      await fetchVersions(newNoteId)
    }
  },
  { immediate: true },
)
</script>

<style scoped>
.version-history-panel {
  @apply select-none;
}

.version-item {
  @apply transition-all;
}

.version-item:hover {
  @apply transform scale-[1.01];
}

.versions-list {
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}
</style>
