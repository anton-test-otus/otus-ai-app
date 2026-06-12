import { useNotesStore } from '@/stores/notes'
import { useAppToast } from '@/composables/useAppToast'
import type { Note, NoteListItem } from '@/types'

export function useFavoriteToggle() {
  const notesStore = useNotesStore()
  const { showSuccess, showError } = useAppToast()

  async function toggleFavorite(note: NoteListItem | Note) {
    try {
      const updated = await notesStore.toggleFavorite(note)
      showSuccess(
        updated.isFavorite
          ? `«${updated.title}» в избранном`
          : `«${updated.title}» больше не в избранном`,
        updated.isFavorite ? 'Добавлено в избранное' : 'Убрано из избранного',
      )
      return updated
    } catch (error) {
      showError(error, 'Не удалось обновить избранное')
      throw error
    }
  }

  return { toggleFavorite }
}
