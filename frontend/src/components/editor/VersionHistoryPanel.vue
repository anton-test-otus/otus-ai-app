<template>
  <div class="version-history-panel">
    <div class="versions-header flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Version History</h3>
      <Button
        icon="pi pi-times"
        text
        rounded
        @click="$emit('close')"
        v-tooltip.top="'Close'"
      />
    </div>

    <div v-if="loading" class="flex justify-center py-4">
      <ProgressSpinner style="width: 30px; height: 30px" stroke-width="4" />
    </div>

    <div
      v-else-if="error"
      class="text-sm text-red-600 bg-red-50 p-3 rounded-md"
    >
      {{ error }}
    </div>

    <div
      v-else-if="!versions || versions.length === 0"
      class="text-sm text-surface-500 dark:text-surface-400 text-center py-4"
    >
      No versions yet
    </div>

    <div v-else class="versions-list space-y-2">
      <!-- Current version indicator -->
      <div class="current-version-indicator px-3 py-2 bg-blue-50 border border-blue-200 rounded-md">
        <div class="flex items-center gap-2">
          <i class="pi pi-file text-blue-600" />
          <span class="text-sm font-medium text-blue-900">Current Version</span>
        </div>
      </div>

      <!-- Version items -->
      <div
        v-for="(version, index) in versions"
        :key="version.id"
        class="version-item px-3 py-3 rounded-md cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors border"
        :class="selectedVersion?.id === version.id ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-500' : 'border-gray-200'"
        @click="selectVersion(version)"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <i class="pi pi-history text-sm text-primary-500" />
              <span class="text-xs text-gray-500">
                {{ formatDate(version.createdAt) }}
              </span>
            </div>
            <p class="text-sm font-medium text-gray-900 truncate">
              {{ version.title }}
            </p>
            <p class="text-xs text-gray-600 mt-1">
              Version {{ versions.length - index }} of {{ versions.length }}
            </p>
          </div>
          <div class="flex items-center gap-1 ml-2">
            <Button
              v-if="selectedVersion?.id === version.id"
              icon="pi pi-eye"
              text
              size="small"
              rounded
              @click.stop="showDiff"
              v-tooltip.top="'View changes'"
            />
            <Button
              icon="pi pi-history"
              text
              size="small"
              rounded
              severity="success"
              @click.stop="confirmRestore(version)"
              v-tooltip.top="'Restore this version'"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Diff viewer modal -->
    <Dialog
      v-model:visible="showDiffModal"
      modal
      :header="`Changes from ${selectedVersion ? formatDate(selectedVersion.createdAt) : ''}`"
      :style="{ width: '90vw', maxWidth: '1200px' }"
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

    <!-- Restore confirmation modal -->
    <RestoreVersionModal
      v-model="showRestoreModal"
      :version-date="versionToRestore?.createdAt || ''"
      @confirm="handleRestore"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
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

// Format date for display
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)

  if (diffInHours < 1) {
    const minutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
    return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`
  } else if (diffInHours < 24) {
    const hours = Math.floor(diffInHours)
    return `${hours} hour${hours !== 1 ? 's' : ''} ago`
  } else if (diffInHours < 168) {
    const days = Math.floor(diffInHours / 24)
    return `${days} day${days !== 1 ? 's' : ''} ago`
  } else {
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date)
  }
}

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

// Watch for noteId changes and fetch versions
watch(
  () => props.noteId,
  async (newNoteId) => {
    if (newNoteId) {
      await fetchVersions(newNoteId)
    }
  },
  { immediate: true }
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
