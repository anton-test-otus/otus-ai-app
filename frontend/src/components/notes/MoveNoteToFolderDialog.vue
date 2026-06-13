<template>
  <Dialog
    v-model:visible="moveDialogVisible"
    modal
    header="Переместить в папку"
    :style="MODAL_WIDTH.sm"
    @hide="closeMoveDialog"
  >
    <div v-if="noteToMove" class="flex flex-col gap-4 py-2">
      <p class="text-sm text-surface-600 dark:text-surface-400">
        Заметка: <strong class="text-surface-900 dark:text-surface-100">{{ noteToMove.title }}</strong>
      </p>

      <div class="flex flex-col gap-2">
        <label for="move-note-folder" class="font-semibold text-sm">Папка</label>
        <FolderDropdown
          id="move-note-folder"
          v-model="selectedTargetFolderId"
          input-id="move-note-folder"
          placeholder="Без папки (корень)"
        />
      </div>
    </div>

    <template #footer>
      <Button label="Отмена" text @click="closeMoveDialog" />
      <Button
        label="Переместить"
        :loading="moving"
        @click="confirmMoveFromDialog"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import FolderDropdown from '@/components/common/FolderDropdown.vue'
import { MODAL_WIDTH } from '@/constants/modal'
import { useMoveNoteToFolder } from '@/composables/useMoveNoteToFolder'

const {
  moveDialogVisible,
  noteToMove,
  selectedTargetFolderId,
  moving,
  closeMoveDialog,
  confirmMoveFromDialog,
} = useMoveNoteToFolder()
</script>
