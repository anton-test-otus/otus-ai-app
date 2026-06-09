import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notesApi } from '@/api/notes'
import type { Note, CreateNoteRequest, UpdateNoteRequest, PaginationMeta } from '@/types'

export const useNotesStore = defineStore('notes', () => {
  const notes = ref<Note[]>([])
  const currentNote = ref<Note | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref<PaginationMeta>({
    currentPage: 1,
    perPage: 20,
    total: 0,
    totalPages: 0,
  })

  async function fetchNotes(page = 1, perPage = 20, folderId?: string | null) {
    isLoading.value = true
    error.value = null
    try {
      const response = await notesApi.getAll(page, perPage, folderId)
      notes.value = response.data
      if (response.meta) {
        pagination.value = response.meta
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
      notes.value.unshift(note)
      return note
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка создания заметки'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function updateNote(id: string, data: UpdateNoteRequest) {
    error.value = null
    try {
      const note = await notesApi.update(id, data)
      const index = notes.value.findIndex((n) => n.id === id)
      if (index !== -1) {
        notes.value[index] = note
      }
      if (currentNote.value?.id === id) {
        currentNote.value = note
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
      if (currentNote.value?.id === id) {
        currentNote.value = null
      }
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
    currentNote,
    isLoading,
    error,
    pagination,
    fetchNotes,
    fetchNoteById,
    createNote,
    updateNote,
    deleteNote,
    searchNotes,
    clearCurrentNote,
  }
})
