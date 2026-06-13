import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notesApi } from '@/api/notes'
import { useTrashStore } from '@/stores/trash'
import { toNoteListItem } from '@/utils/note'
import { getApiErrorMessage } from '@/utils/apiError'
import type { Note, NoteListItem, CreateNoteRequest, UpdateNoteRequest, PaginationMeta } from '@/types'

export const useNotesStore = defineStore('notes', () => {
  const notes = ref<NoteListItem[]>([])
  const favoriteNotes = ref<NoteListItem[]>([])
  const currentNote = ref<Note | null>(null)
  const isLoading = ref(false)
  const isLoadingMore = ref(false)
  const isLoadingFavorites = ref(false)
  const isLoadingMoreFavorites = ref(false)
  const error = ref<string | null>(null)
  const favoritesError = ref<string | null>(null)
  const pagination = ref<PaginationMeta>({
    currentPage: 1,
    perPage: 20,
    total: 0,
    totalPages: 0,
  })
  const favoritesPagination = ref<PaginationMeta>({
    currentPage: 1,
    perPage: 20,
    total: 0,
    totalPages: 0,
  })

  const hasMore = computed(
    () => pagination.value.currentPage < pagination.value.totalPages,
  )

  const favoritesHasMore = computed(
    () => favoritesPagination.value.currentPage < favoritesPagination.value.totalPages,
  )

  let listCriteriaKey: string | null = null
  let fetchNotesPromise: Promise<void> | null = null
  let loadMorePromise: Promise<void> | null = null
  let fetchFavoritesPromise: Promise<void> | null = null
  let loadMoreFavoritesPromise: Promise<void> | null = null

  function buildListCriteriaKey(folderId?: string | null, tagIds?: string[]) {
    return JSON.stringify({
      folderId: folderId ?? null,
      tags: [...(tagIds ?? [])].sort(),
    })
  }

  async function fetchFavorites(
    page = 1,
    perPage = favoritesPagination.value.perPage,
    options?: { append?: boolean },
  ) {
    const append = options?.append ?? false

    if (append) {
      if (isLoadingMoreFavorites.value || isLoadingFavorites.value || !favoritesHasMore.value) {
        return
      }
      if (loadMoreFavoritesPromise) {
        return loadMoreFavoritesPromise
      }
      isLoadingMoreFavorites.value = true
    } else {
      if (fetchFavoritesPromise) {
        return fetchFavoritesPromise
      }
      isLoadingFavorites.value = true
      favoritesError.value = null
      favoriteNotes.value = []
    }

    const promise = (async () => {
      try {
        const response = await notesApi.getFavorites(page, perPage)

        if (append) {
          const existingIds = new Set(favoriteNotes.value.map((n) => n.id))
          const newItems = response.data.filter((n) => !existingIds.has(n.id))
          favoriteNotes.value = [...favoriteNotes.value, ...newItems]
        } else {
          favoriteNotes.value = response.data
        }

        if (response.meta) {
          favoritesPagination.value = response.meta
        }
      } catch (err: unknown) {
        if (!append) {
          favoritesError.value = getApiErrorMessage(err, 'Ошибка загрузки избранного')
        }
        throw err
      } finally {
        if (append) {
          isLoadingMoreFavorites.value = false
          loadMoreFavoritesPromise = null
        } else {
          isLoadingFavorites.value = false
          fetchFavoritesPromise = null
        }
      }
    })()

    if (append) {
      loadMoreFavoritesPromise = promise
    } else {
      fetchFavoritesPromise = promise
    }

    return promise
  }

  async function loadMoreFavorites() {
    const nextPage = favoritesPagination.value.currentPage + 1
    await fetchFavorites(nextPage, favoritesPagination.value.perPage, { append: true })
  }

  async function fetchNotes(
    page = 1,
    perPage = pagination.value.perPage,
    folderId?: string | null,
    tagIds?: string[],
    options?: { append?: boolean },
  ) {
    const append = options?.append ?? false
    const criteriaKey = buildListCriteriaKey(folderId, tagIds)

    if (append) {
      if (isLoadingMore.value || isLoading.value || !hasMore.value) {
        return
      }
      if (listCriteriaKey !== criteriaKey) {
        return
      }
      if (loadMorePromise) {
        return loadMorePromise
      }
      isLoadingMore.value = true
    } else {
      if (fetchNotesPromise) {
        return fetchNotesPromise
      }
      isLoading.value = true
      error.value = null
      notes.value = []
      listCriteriaKey = criteriaKey
    }

    const promise = (async () => {
      try {
        const response = await notesApi.getAll(page, perPage, folderId, tagIds)

        if (append) {
          const existingIds = new Set(notes.value.map((n) => n.id))
          const newItems = response.data.filter((n) => !existingIds.has(n.id))
          notes.value = [...notes.value, ...newItems]
        } else {
          notes.value = response.data
        }

        if (response.meta) {
          pagination.value = response.meta
        }
      } catch (err: unknown) {
        if (!append) {
          error.value = getApiErrorMessage(err, 'Ошибка загрузки заметок')
        }
        throw err
      } finally {
        if (append) {
          isLoadingMore.value = false
          loadMorePromise = null
        } else {
          isLoading.value = false
          fetchNotesPromise = null
        }
      }
    })()

    if (append) {
      loadMorePromise = promise
    } else {
      fetchNotesPromise = promise
    }

    return promise
  }

  async function loadMoreNotes(folderId?: string | null, tagIds?: string[]) {
    const nextPage = pagination.value.currentPage + 1
    await fetchNotes(
      nextPage,
      pagination.value.perPage,
      folderId,
      tagIds,
      { append: true },
    )
  }

  async function fetchNoteById(id: string) {
    isLoading.value = true
    error.value = null
    try {
      currentNote.value = await notesApi.getById(id)
      return currentNote.value
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка загрузки заметки')
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
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка создания заметки')
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

  function noteMatchesListFilters(
    note: NoteListItem,
    folderId?: string | null,
    tagIds?: string[],
  ): boolean {
    if (folderId && note.folderId !== folderId) {
      return false
    }

    const activeTags = tagIds ?? []
    if (activeTags.length === 0) {
      return true
    }

    const noteTagIds = new Set(note.tags?.map((tag) => tag.id) ?? [])
    return activeTags.every((tagId) => noteTagIds.has(tagId))
  }

  function applyNoteFolderChange(
    note: Note,
    options?: { folderId?: string | null; tagIds?: string[] },
  ) {
    const item = toNoteListItem(note)
    const wasInList = notes.value.some((entry) => entry.id === note.id)
    const matchesFilters = noteMatchesListFilters(
      item,
      options?.folderId,
      options?.tagIds,
    )

    syncNoteInLists(note)

    if (wasInList && !matchesFilters) {
      notes.value = notes.value.filter((entry) => entry.id !== note.id)
      if (pagination.value.total > 0) {
        pagination.value.total -= 1
        pagination.value.totalPages = Math.ceil(
          pagination.value.total / pagination.value.perPage,
        )
      }
    } else if (!wasInList && matchesFilters) {
      notes.value.unshift(item)
      pagination.value.total += 1
      pagination.value.totalPages = Math.ceil(
        pagination.value.total / pagination.value.perPage,
      )
    }

    const favoriteIndex = favoriteNotes.value.findIndex((entry) => entry.id === note.id)
    if (favoriteIndex !== -1) {
      favoriteNotes.value[favoriteIndex] = item
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
        favoritesPagination.value.total += 1
        favoritesPagination.value.totalPages = Math.ceil(
          favoritesPagination.value.total / favoritesPagination.value.perPage,
        )
      }
      favoriteNotes.value.sort(
        (a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime()
      )
      return
    }

    favoriteNotes.value = favoriteNotes.value.filter((n) => n.id !== note.id)
    if (favoritesPagination.value.total > 0) {
      favoritesPagination.value.total -= 1
      favoritesPagination.value.totalPages = Math.ceil(
        favoritesPagination.value.total / favoritesPagination.value.perPage,
      )
    }
  }

  async function toggleFavorite(note: NoteListItem | Note) {
    error.value = null
    try {
      const updated = await notesApi.toggleFavorite(note.id, !note.isFavorite)
      syncNoteInLists(updated)
      syncFavoriteNotes(updated)
      return updated
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка обновления избранного')
      throw err
    }
  }

  async function moveNoteToFolder(
    id: string,
    folderId: string | null,
    options?: { folderId?: string | null; tagIds?: string[] },
  ) {
    error.value = null
    try {
      const note = await notesApi.moveToFolder(id, folderId)
      applyNoteFolderChange(note, options)
      return note
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка перемещения заметки')
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
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка обновления заметки')
      throw err
    }
  }

  async function deleteNote(id: string) {
    isLoading.value = true
    error.value = null
    try {
      await notesApi.delete(id)
      notes.value = notes.value.filter((n) => n.id !== id)
      const wasFavorite = favoriteNotes.value.some((n) => n.id === id)
      favoriteNotes.value = favoriteNotes.value.filter((n) => n.id !== id)
      if (wasFavorite && favoritesPagination.value.total > 0) {
        favoritesPagination.value.total -= 1
        favoritesPagination.value.totalPages = Math.ceil(
          favoritesPagination.value.total / favoritesPagination.value.perPage,
        )
      }
      if (currentNote.value?.id === id) {
        currentNote.value = null
      }
      await useTrashStore().fetchCount()
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка удаления заметки')
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
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка поиска заметок')
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function clearCurrentNote() {
    currentNote.value = null
  }

  function reset() {
    notes.value = []
    favoriteNotes.value = []
    currentNote.value = null
    isLoading.value = false
    isLoadingMore.value = false
    isLoadingFavorites.value = false
    isLoadingMoreFavorites.value = false
    error.value = null
    favoritesError.value = null
    pagination.value = {
      currentPage: 1,
      perPage: 20,
      total: 0,
      totalPages: 0,
    }
    favoritesPagination.value = {
      currentPage: 1,
      perPage: 20,
      total: 0,
      totalPages: 0,
    }
    listCriteriaKey = null
    fetchNotesPromise = null
    loadMorePromise = null
    fetchFavoritesPromise = null
    loadMoreFavoritesPromise = null
  }

  return {
    notes,
    favoriteNotes,
    currentNote,
    isLoading,
    isLoadingMore,
    isLoadingFavorites,
    isLoadingMoreFavorites,
    hasMore,
    favoritesHasMore,
    error,
    favoritesError,
    pagination,
    favoritesPagination,
    fetchNotes,
    loadMoreNotes,
    fetchFavorites,
    loadMoreFavorites,
    fetchNoteById,
    createNote,
    updateNote,
    moveNoteToFolder,
    toggleFavorite,
    deleteNote,
    searchNotes,
    clearCurrentNote,
    reset,
  }
})
