import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { notesApi } from '@/api/notes'
import { useNotesStore } from '@/stores/notes'
import {
  createListItem,
  createListResponse,
  createNote,
  mockListItem,
  mockListResponse,
  mockNote,
} from './fixtures/notes'

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

vi.mock('@/stores/trash', () => ({
  useTrashStore: () => ({
    fetchCount: vi.fn().mockResolvedValue(undefined),
  }),
}))

describe('useNotesStore loading and error isolation', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.mocked(notesApi.getAll).mockReset()
    vi.mocked(notesApi.getById).mockReset()
    vi.mocked(notesApi.create).mockReset()
    vi.mocked(notesApi.update).mockReset()
    vi.mocked(notesApi.delete).mockReset()
    vi.mocked(notesApi.toggleFavorite).mockReset()
    vi.mocked(notesApi.moveToFolder).mockReset()
    useNotesStore().reset()
  })

  it('sets listError on fetchNotes reject and keeps detailError null', async () => {
    vi.mocked(notesApi.getAll).mockRejectedValue(new Error('list fail'))
    const store = useNotesStore()

    await expect(store.fetchNotes()).rejects.toThrow('list fail')

    expect(store.listError).toBe('list fail')
    expect(store.detailError).toBeNull()
    expect(store.isLoadingList).toBe(false)
  })

  it('sets detailError on fetchNoteById reject and keeps listError null', async () => {
    vi.mocked(notesApi.getById).mockRejectedValue(new Error('detail fail'))
    const store = useNotesStore()

    await expect(store.fetchNoteById('note-1')).rejects.toThrow('detail fail')

    expect(store.detailError).toBe('detail fail')
    expect(store.listError).toBeNull()
    expect(store.isLoadingDetail).toBe(false)
  })

  it('keeps listError after a successful fetchNoteById following list error', async () => {
    vi.mocked(notesApi.getAll).mockRejectedValue(new Error('list fail'))
    vi.mocked(notesApi.getById).mockResolvedValue(mockNote)
    const store = useNotesStore()

    await expect(store.fetchNotes()).rejects.toThrow('list fail')
    await store.fetchNoteById('note-1')

    expect(store.detailError).toBeNull()
    expect(store.currentNote).toEqual(mockNote)
    expect(store.listError).toBe('list fail')
  })

  it('sets detailError on createNote reject and keeps listError null', async () => {
    vi.mocked(notesApi.create).mockRejectedValue(new Error('create fail'))
    const store = useNotesStore()

    await expect(
      store.createNote({ title: 'New', content: 'body' }),
    ).rejects.toThrow('create fail')

    expect(store.detailError).toBe('create fail')
    expect(store.listError).toBeNull()
  })

  it('sets detailError on deleteNote reject and keeps listError null', async () => {
    vi.mocked(notesApi.delete).mockRejectedValue(new Error('delete fail'))
    const store = useNotesStore()

    await expect(store.deleteNote('note-1')).rejects.toThrow('delete fail')

    expect(store.detailError).toBe('delete fail')
    expect(store.listError).toBeNull()
    expect(store.isLoadingDetail).toBe(false)
  })

  it('does not write listError or detailError when updateNote rejects', async () => {
    vi.mocked(notesApi.update).mockRejectedValue(new Error('update fail'))
    const store = useNotesStore()
    store.currentNote = mockNote

    await expect(store.updateNote('note-1', { title: 'Updated' })).rejects.toThrow(
      'update fail',
    )

    expect(store.listError).toBeNull()
    expect(store.detailError).toBeNull()
  })

  it('does not write listError, detailError, or favoritesError when toggleFavorite rejects', async () => {
    vi.mocked(notesApi.toggleFavorite).mockRejectedValue(new Error('favorite fail'))
    const store = useNotesStore()

    await expect(store.toggleFavorite(mockListItem)).rejects.toThrow('favorite fail')

    expect(store.listError).toBeNull()
    expect(store.detailError).toBeNull()
    expect(store.favoritesError).toBeNull()
  })

  it('does not write listError or detailError when moveNoteToFolder rejects', async () => {
    vi.mocked(notesApi.moveToFolder).mockRejectedValue(new Error('move fail'))
    const store = useNotesStore()

    await expect(store.moveNoteToFolder('note-1', 'folder-1')).rejects.toThrow('move fail')

    expect(store.listError).toBeNull()
    expect(store.detailError).toBeNull()
  })

  it('loads notes on successful fetchNotes', async () => {
    vi.mocked(notesApi.getAll).mockResolvedValue(mockListResponse)
    const store = useNotesStore()

    await store.fetchNotes()

    expect(store.notes).toHaveLength(1)
    expect(store.notes[0].id).toBe('note-1')
    expect(store.listError).toBeNull()
    expect(store.isLoadingList).toBe(false)
  })

  it('loads currentNote on successful fetchNoteById', async () => {
    vi.mocked(notesApi.getById).mockResolvedValue(mockNote)
    const store = useNotesStore()

    await store.fetchNoteById('note-1')

    expect(store.currentNote).toEqual(mockNote)
    expect(store.detailError).toBeNull()
  })

  it('sets currentNote and prepends list item on successful createNote', async () => {
    const created = createNote({ id: 'note-new', title: 'Created' })
    vi.mocked(notesApi.create).mockResolvedValue(created)
    const store = useNotesStore()

    await store.createNote({ title: 'Created', content: 'body' })

    expect(store.currentNote).toEqual(created)
    expect(store.notes[0].id).toBe('note-new')
    expect(store.detailError).toBeNull()
  })

  it('removes note from list and clears currentNote on successful deleteNote', async () => {
    vi.mocked(notesApi.delete).mockResolvedValue(undefined)
    const store = useNotesStore()
    store.notes = [mockListItem]
    store.currentNote = mockNote

    await store.deleteNote('note-1')

    expect(store.notes).toHaveLength(0)
    expect(store.currentNote).toBeNull()
  })
})
