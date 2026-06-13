import { NOTE_DRAG_MIME } from '@/constants/noteDragDrop'
import { useNoteDragDropStore } from '@/stores/noteDragDrop'
import { useMoveNoteToFolder } from '@/composables/useMoveNoteToFolder'

export function useNoteDragSource(noteId: string, noteTitle: string) {
  const dragDropStore = useNoteDragDropStore()
  let dragStarted = false

  function onDragStart(event: DragEvent) {
    if (!event.dataTransfer) {
      return
    }

    dragStarted = true
    event.dataTransfer.setData(NOTE_DRAG_MIME, noteId)
    event.dataTransfer.setData('text/plain', noteTitle)
    event.dataTransfer.effectAllowed = 'move'
    dragDropStore.startDrag(noteId)
  }

  function onDragEnd() {
    dragDropStore.endDrag()
    window.setTimeout(() => {
      dragStarted = false
    }, 0)
  }

  function shouldIgnoreClick(): boolean {
    return dragStarted
  }

  return {
    onDragStart,
    onDragEnd,
    shouldIgnoreClick,
  }
}

export function useNoteFolderDropZone(folderId: string | null) {
  const dragDropStore = useNoteDragDropStore()
  const { moveNote } = useMoveNoteToFolder()

  function onDragOver(event: DragEvent) {
    if (!dragDropStore.draggedNoteId) {
      return
    }

    event.preventDefault()
    if (event.dataTransfer) {
      event.dataTransfer.dropEffect = 'move'
    }
  }

  function onDragEnter(event: DragEvent) {
    event.preventDefault()
    dragDropStore.setDropTarget(folderId)
  }

  function onDragLeave(event: DragEvent) {
    const related = event.relatedTarget as Node | null
    const current = event.currentTarget as HTMLElement | null
    if (current && related && current.contains(related)) {
      return
    }
    dragDropStore.clearDropTarget()
  }

  async function onDrop(event: DragEvent) {
    event.preventDefault()
    event.stopPropagation()

    const noteId = event.dataTransfer?.getData(NOTE_DRAG_MIME)
    dragDropStore.endDrag()

    if (!noteId) {
      return
    }

    await moveNote(noteId, folderId)
  }

  function dropZoneClass(isBaseSelected = false) {
    return {
      'folder-drop-target': dragDropStore.draggedNoteId !== null,
      'folder-drop-target-active': dragDropStore.isDropActive(folderId),
      'folder-drop-target-selected': isBaseSelected,
    }
  }

  return {
    onDragOver,
    onDragEnter,
    onDragLeave,
    onDrop,
    dropZoneClass,
  }
}
