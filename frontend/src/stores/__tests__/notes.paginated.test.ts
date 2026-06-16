import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { notesApi } from '@/api/notes'
import { useNotesStore } from '@/stores/notes'
import { createListItem, createListResponse } from './fixtures/notes'

vi.mock('@/api/notes', () => ({
  notesApi: {
    getAll: vi.fn(),
    getById: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
    toggleFavorite: vi.fn(),
    moveToFolder: vi.fn(),
    getFavorites: vi.fn(),
  },
}))

describe('useNotesStore paginated list', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.mocked(notesApi.getAll).mockReset()
    useNotesStore().reset()
  })

  it('deduplicates concurrent fetchNotes calls into one in-flight request', async () => {
    let resolveFetch: ((value: ReturnType<typeof createListResponse>) => void) | undefined
    vi.mocked(notesApi.getAll).mockImplementation(
      () =>
        new Promise((resolve) => {
          resolveFetch = resolve
        }),
    )

    const store = useNotesStore()
    const first = store.fetchNotes()
    const second = store.fetchNotes()

    expect(notesApi.getAll).toHaveBeenCalledTimes(1)

    resolveFetch?.(
      createListResponse([createListItem({ id: 'note-1' })], {
        currentPage: 1,
        perPage: 20,
        total: 1,
        totalPages: 1,
      }),
    )

    await Promise.all([first, second])
    expect(store.notes).toHaveLength(1)
  })

  it('appends the next page on loadMoreNotes', async () => {
    vi.mocked(notesApi.getAll)
      .mockResolvedValueOnce(
        createListResponse([createListItem({ id: 'note-1' })], {
          currentPage: 1,
          perPage: 20,
          total: 2,
          totalPages: 2,
        }),
      )
      .mockResolvedValueOnce(
        createListResponse([createListItem({ id: 'note-2' })], {
          currentPage: 2,
          perPage: 20,
          total: 2,
          totalPages: 2,
        }),
      )

    const store = useNotesStore()
    await store.fetchNotes()
    await store.loadMoreNotes()

    expect(notesApi.getAll).toHaveBeenCalledTimes(2)
    expect(store.notes.map((note) => note.id)).toEqual(['note-1', 'note-2'])
    expect(store.pagination.currentPage).toBe(2)
    expect(store.pagination.total).toBe(2)
  })

  it('ignores loadMore when filter criteria no longer match the active list', async () => {
    vi.mocked(notesApi.getAll)
      .mockResolvedValueOnce(
        createListResponse([createListItem({ id: 'note-a', folderId: 'folder-a' })], {
          currentPage: 1,
          perPage: 20,
          total: 2,
          totalPages: 2,
        }),
      )
      .mockResolvedValueOnce(
        createListResponse([createListItem({ id: 'note-b', folderId: 'folder-b' })], {
          currentPage: 1,
          perPage: 20,
          total: 1,
          totalPages: 1,
        }),
      )

    const store = useNotesStore()
    await store.fetchNotes(1, 20, 'folder-a')
    await store.fetchNotes(1, 20, 'folder-b')
    await store.loadMoreNotes('folder-a')

    expect(notesApi.getAll).toHaveBeenCalledTimes(2)
    expect(store.notes.map((note) => note.id)).toEqual(['note-b'])
  })
})
