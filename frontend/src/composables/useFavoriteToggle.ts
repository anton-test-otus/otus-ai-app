import { useToast } from 'primevue/usetoast'
import { useNotesStore } from '@/stores/notes'
import type { Note } from '@/types'

export function useFavoriteToggle() {
  const notesStore = useNotesStore()
  const toast = useToast()

  async function toggleFavorite(note: Note) {
    try {
      const updated = await notesStore.toggleFavorite(note)
      toast.add({
        severity: 'success',
        summary: updated.isFavorite ? 'Добавлено в избранное' : 'Убрано из избранного',
        detail: updated.isFavorite
          ? `«${updated.title}» в избранном`
          : `«${updated.title}» больше не в избранном`,
        life: 3000,
      })
      return updated
    } catch {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: 'Не удалось обновить избранное',
        life: 3000,
      })
      throw new Error('Failed to toggle favorite')
    }
  }

  return { toggleFavorite }
}
