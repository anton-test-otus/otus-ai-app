import { ref } from 'vue'
import { useNotesStore } from '@/stores/notes'
import { useFoldersStore } from '@/stores/folders'
import { useTagsStore } from '@/stores/tags'
import { useAppToast } from '@/composables/useAppToast'
import { getNoteFolderId } from '@/utils/note'
import type { NoteListItem } from '@/types'

const moveDialogVisible = ref(false)
const noteToMove = ref<NoteListItem | null>(null)
const selectedTargetFolderId = ref<string | null>(null)
const moving = ref(false)

export function useMoveNoteToFolder() {
  const notesStore = useNotesStore()
  const foldersStore = useFoldersStore()
  const tagsStore = useTagsStore()
  const { showSuccess, showError } = useAppToast()

  function getListFilterOptions() {
    return {
      folderId: foldersStore.selectedFolderId,
      tagIds: [...tagsStore.selectedTags],
    }
  }

  function openMoveDialog(note: NoteListItem) {
    noteToMove.value = note
    selectedTargetFolderId.value = getNoteFolderId(note)
    moveDialogVisible.value = true
  }

  function closeMoveDialog() {
    moveDialogVisible.value = false
    noteToMove.value = null
    selectedTargetFolderId.value = null
  }

  async function moveNote(noteId: string, folderId: string | null, currentFolderId?: string | null) {
    if (currentFolderId === undefined) {
      const note = notesStore.notes.find((entry) => entry.id === noteId)
        ?? notesStore.favoriteNotes.find((entry) => entry.id === noteId)
      currentFolderId = note ? getNoteFolderId(note) : null
    }

    if (currentFolderId === folderId) {
      return null
    }

    try {
      const updated = await notesStore.moveNoteToFolder(noteId, folderId, getListFilterOptions())
      showSuccess('Заметка перемещена')
      return updated
    } catch (error) {
      showError(error, 'Не удалось переместить заметку')
      throw error
    }
  }

  async function confirmMoveFromDialog() {
    if (!noteToMove.value || moving.value) {
      return
    }

    moving.value = true
    try {
      await moveNote(
        noteToMove.value.id,
        selectedTargetFolderId.value,
        getNoteFolderId(noteToMove.value),
      )
      closeMoveDialog()
    } finally {
      moving.value = false
    }
  }

  return {
    moveDialogVisible,
    noteToMove,
    selectedTargetFolderId,
    moving,
    openMoveDialog,
    closeMoveDialog,
    moveNote,
    confirmMoveFromDialog,
  }
}
