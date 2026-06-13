<template>
  <Dialog
    v-model:visible="visible"
    modal
    header="История версий"
    :style="MODAL_WIDTH.lg"
    :breakpoints="MODAL_FULLSCREEN_MOBILE_BREAKPOINTS"
    :dismissable-mask="true"
    :class="MODAL_FULLSCREEN_MOBILE_CLASS"
    class="version-history-dialog"
    @hide="handleHide"
  >
    <VersionHistoryPanel
      v-if="visible && currentNote"
      :note-id="noteId"
      :current-note="currentNote"
      @restore="handleRestore"
    />

    <template #footer>
      <Button
        label="Закрыть"
        severity="secondary"
        text
        @click="visible = false"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import VersionHistoryPanel from '@/components/editor/VersionHistoryPanel.vue'
import {
  MODAL_FULLSCREEN_MOBILE_BREAKPOINTS,
  MODAL_FULLSCREEN_MOBILE_CLASS,
  MODAL_WIDTH,
} from '@/constants/modal'
import type { Note, RestoreVersionRequest } from '@/types'

defineProps<{
  noteId: string
  currentNote: Note | null
}>()

const visible = defineModel<boolean>('visible', { required: true })

const emit = defineEmits<{
  restore: [versionId: string, mode: RestoreVersionRequest['mode']]
}>()

function handleRestore(versionId: string, mode: RestoreVersionRequest['mode']) {
  emit('restore', versionId, mode)
}

function handleHide() {
  visible.value = false
}
</script>
