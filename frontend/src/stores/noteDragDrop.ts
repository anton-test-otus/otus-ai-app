import { defineStore } from 'pinia'
import { ref } from 'vue'
import { folderDropKey } from '@/constants/noteDragDrop'

export const useNoteDragDropStore = defineStore('noteDragDrop', () => {
  const draggedNoteId = ref<string | null>(null)
  const dropTargetKey = ref<string | null>(null)

  function startDrag(noteId: string) {
    draggedNoteId.value = noteId
  }

  function setDropTarget(folderId: string | null | undefined) {
    if (!draggedNoteId.value) {
      return
    }
    dropTargetKey.value = folderDropKey(folderId)
  }

  function clearDropTarget() {
    dropTargetKey.value = null
  }

  function endDrag() {
    draggedNoteId.value = null
    dropTargetKey.value = null
  }

  function isDropActive(folderId: string | null | undefined): boolean {
    return (
      draggedNoteId.value !== null
      && dropTargetKey.value === folderDropKey(folderId)
    )
  }

  return {
    draggedNoteId,
    dropTargetKey,
    startDrag,
    setDropTarget,
    clearDropTarget,
    endDrag,
    isDropActive,
  }
})
