import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notesApi } from '@/api/notes'
import { useTrashStore } from '@/stores/trash'
import { toNoteListItem } from '@/utils/note'
import { getApiErrorMessage } from '@/utils/apiError'
import type {
  Note,
  NoteListItem,
  CreateNoteRequest,
  UpdateNoteRequest,
  PaginationMeta,
  ApiResponse,
} from '@/types'

interface PaginatedListRefs {
  items: { value: NoteListItem[] }
  pagination: { value: PaginationMeta }
  isLoading: { value: boolean }
  isLoadingMore: { value: boolean }
  error: { value: string | null }
  hasMore: { value: boolean }
}

interface PaginatedListInFlight {
  fetchPromise: Promise<void> | null
  loadMorePromise: Promise<void> | null
  criteriaKey: string | null
}

interface FetchPaginatedListOptions {
  page: number
  perPage: number
  append: boolean
  criteriaKey?: string
  fetchFn: (page: number, perPage: number) => Promise<ApiResponse<NoteListItem[]>>
  errorMessage: string
}

async function fetchPaginatedList(
  refs: PaginatedListRefs,
  inFlight: PaginatedListInFlight,
  options: FetchPaginatedListOptions,
): Promise<void> {
  const { page, perPage, append, criteriaKey, fetchFn, errorMessage } = options

  if (append) {
    if (refs.isLoadingMore.value || refs.isLoading.value || !refs.hasMore.value) {
      return
    }
    if (criteriaKey !== undefined && inFlight.criteriaKey !== criteriaKey) {
      return
    }
    if (inFlight.loadMorePromise) {
      return inFlight.loadMorePromise
    }
    refs.isLoadingMore.value = true
  } else {
    if (inFlight.fetchPromise) {
      return inFlight.fetchPromise
    }
    refs.isLoading.value = true
    refs.error.value = null
    refs.items.value = []
    if (criteriaKey !== undefined) {
      inFlight.criteriaKey = criteriaKey
    }
  }

  const promise = (async () => {
    try {
      const response = await fetchFn(page, perPage)

      if (append) {
        const existingIds = new Set(refs.items.value.map((n) => n.id))
        const newItems = response.data.filter((n) => !existingIds.has(n.id))
        refs.items.value = [...refs.items.value, ...newItems]
      } else {
        refs.items.value = response.data
      }

      if (response.meta) {
        refs.pagination.value = response.meta
      }
    } catch (err: unknown) {
      if (!append) {
        refs.error.value = getApiErrorMessage(err, errorMessage)
      }
      throw err
    } finally {
      if (append) {
        refs.isLoadingMore.value = false
        inFlight.loadMorePromise = null
      } else {
        refs.isLoading.value = false
        inFlight.fetchPromise = null
      }
    }
  })()

  if (append) {
    inFlight.loadMorePromise = promise
  } else {
    inFlight.fetchPromise = promise
  }

  return promise
}

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

  const notesInFlight: PaginatedListInFlight = {
    fetchPromise: null,
    loadMorePromise: null,
    criteriaKey: null,
  }
  const favoritesInFlight: PaginatedListInFlight = {
    fetchPromise: null,
    loadMorePromise: null,
    criteriaKey: null,
  }

  const notesListRefs: PaginatedListRefs = {
    items: notes,
    pagination,
    isLoading,
    isLoadingMore,
    error,
    hasMore,
  }

  const favoritesListRefs: PaginatedListRefs = {
    items: favoriteNotes,
    pagination: favoritesPagination,
    isLoading: isLoadingFavorites,
    isLoadingMore: isLoadingMoreFavorites,
    error: favoritesError,
    hasMore: favoritesHasMore,
  }

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
    return fetchPaginatedList(favoritesListRefs, favoritesInFlight, {
      page,
      perPage,
      append: options?.append ?? false,
      fetchFn: (p, pp) => notesApi.getFavorites(p, pp),
      errorMessage: 'Ошибка загрузки избранного',
    })
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
    return fetchPaginatedList(notesListRefs, notesInFlight, {
      page,
      perPage,
      append: options?.append ?? false,
      criteriaKey: buildListCriteriaKey(folderId, tagIds),
      fetchFn: (p, pp) => notesApi.getAll(p, pp, folderId, tagIds),
      errorMessage: 'Ошибка загрузки заметок',
    })
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
    notesInFlight.fetchPromise = null
    notesInFlight.loadMorePromise = null
    notesInFlight.criteriaKey = null
    favoritesInFlight.fetchPromise = null
    favoritesInFlight.loadMorePromise = null
    favoritesInFlight.criteriaKey = null
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
    clearCurrentNote,
    reset,
  }
})
