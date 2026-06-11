import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notesApi } from '@/api/notes'
import { useTrashStore } from '@/stores/trash'
import { toNoteListItem } from '@/utils/note'
import type { Note, NoteListItem, CreateNoteRequest, UpdateNoteRequest, PaginationMeta } from '@/types'

export const useNotesStore = defineStore('notes', () => {
  const notes = ref<NoteListItem[]>([])
  const favoriteNotes = ref<NoteListItem[]>([])
  const currentNote = ref<Note | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref<PaginationMeta>({
    currentPage: 1,
    perPage: 20,
    total: 0,
    totalPages: 0,
  })

  async function fetchFavorites(folderId?: string | null, tagIds?: string[]) {
    try {
      const response = await notesApi.getFavorites(folderId, 100, tagIds)
      favoriteNotes.value = response.data
    } catch {
      favoriteNotes.value = []
    }
  }

  async function fetchNotes(
    page = 1,
    perPage = 20,
    folderId?: string | null,
    tagIds?: string[],
  ) {
    isLoading.value = true
    error.value = null
    try {
      const response = await notesApi.getAll(page, perPage, folderId, tagIds)
      notes.value = response.data
      if (response.meta) {
        pagination.value = response.meta
      }
      if (page === 1) {
        await fetchFavorites(folderId, tagIds)
      } else {
        favoriteNotes.value = []
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка загрузки заметок'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchNoteById(id: string) {
    isLoading.value = true
    error.value = null
    try {
      currentNote.value = await notesApi.getById(id)
      return currentNote.value
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка загрузки заметки'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function createNote(data: CreateNoteRequest) {
    isLoading.value = true
    error.value = null
    try {
      const note = await notesApi.create(data)
      notes.value.unshift(toNoteListItem(note))
      currentNote.value = note
      return note
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка создания заметки'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function syncNoteInLists(note: Note) {
    const item = toNoteListItem(note)
    const index = notes.value.findIndex((n) => n.id === note.id)
    if (index !== -1) {
      notes.value[index] = item
    }
    if (currentNote.value?.id === note.id) {
      currentNote.value = note
    }
  }

  function syncFavoriteNotes(note: Note) {
    const item = toNoteListItem(note)
    if (note.isFavorite) {
      const index = favoriteNotes.value.findIndex((n) => n.id === note.id)
      if (index !== -1) {
        favoriteNotes.value[index] = item
      } else {
        favoriteNotes.value.unshift(item)
      }
      favoriteNotes.value.sort(
        (a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime()
      )
      notes.value = notes.value.filter((n) => n.id !== note.id)
      if (pagination.value.total > 0) {
        pagination.value.total -= 1
        pagination.value.totalPages = Math.ceil(pagination.value.total / pagination.value.perPage)
      }
      return
    }

    favoriteNotes.value = favoriteNotes.value.filter((n) => n.id !== note.id)
    if (pagination.value.currentPage === 1) {
      const index = notes.value.findIndex((n) => n.id === note.id)
      if (index === -1) {
        notes.value.push(item)
        notes.value.sort(
          (a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime()
        )
      }
    }
    pagination.value.total += 1
    pagination.value.totalPages = Math.ceil(pagination.value.total / pagination.value.perPage)
  }

  async function toggleFavorite(note: NoteListItem | Note) {
    error.value = null
    try {
      const updated = await notesApi.toggleFavorite(note.id, !note.isFavorite)
      syncNoteInLists(updated)
      syncFavoriteNotes(updated)
      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка обновления избранного'
      throw err
    }
  }

  async function updateNote(id: string, data: UpdateNoteRequest) {
    error.value = null
    try {
      const note = await notesApi.update(id, data)
      syncNoteInLists(note)
      if ('isFavorite' in data) {
        syncFavoriteNotes(note)
      }
      return note
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка обновления заметки'
      throw err
    }
  }

  async function deleteNote(id: string) {
    isLoading.value = true
    error.value = null
    try {
      await notesApi.delete(id)
      notes.value = notes.value.filter((n) => n.id !== id)
      favoriteNotes.value = favoriteNotes.value.filter((n) => n.id !== id)
      if (currentNote.value?.id === id) {
        currentNote.value = null
      }
      await useTrashStore().fetchCount()
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка удаления заметки'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function searchNotes(query: string, page = 1, perPage = 20) {
    isLoading.value = true
    error.value = null
    try {
      const response = await notesApi.search(query, page, perPage)
      notes.value = response.data
      if (response.meta) {
        pagination.value = response.meta
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка поиска заметок'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function clearCurrentNote() {
    currentNote.value = null
  }

  return {
    notes,
    favoriteNotes,
    currentNote,
    isLoading,
    error,
    pagination,
    fetchNotes,
    fetchFavorites,
    fetchNoteById,
    createNote,
    updateNote,
    toggleFavorite,
    deleteNote,
    searchNotes,
    clearCurrentNote,
  }
})
