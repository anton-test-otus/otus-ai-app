import type { ApiResponse, Note, NoteListItem } from '@/types'

export const mockListItem: NoteListItem = {
  id: 'note-1',
  title: 'Test Note',
  folderId: null,
  isFavorite: false,
  contentPreview: 'preview',
  createdAt: '2024-01-01T10:00:00.000Z',
  updatedAt: '2024-01-02T10:00:00.000Z',
}

export const mockNote: Note = {
  id: 'note-1',
  title: 'Test Note',
  folderId: null,
  content: 'body',
  isFavorite: false,
  createdAt: '2024-01-01T10:00:00.000Z',
  updatedAt: '2024-01-02T10:00:00.000Z',
}

export const mockListResponse: ApiResponse<NoteListItem[]> = {
  data: [mockListItem],
  meta: {
    currentPage: 1,
    perPage: 20,
    total: 1,
    totalPages: 1,
  },
}

export function createListItem(overrides: Partial<NoteListItem> = {}): NoteListItem {
  return {
    ...mockListItem,
    ...overrides,
  }
}

export function createNote(overrides: Partial<Note> = {}): Note {
  return {
    ...mockNote,
    ...overrides,
  }
}

export function createListResponse(
  data: NoteListItem[],
  meta?: ApiResponse<NoteListItem[]>['meta'],
): ApiResponse<NoteListItem[]> {
  return {
    data,
    meta: meta ?? {
      currentPage: 1,
      perPage: 20,
      total: data.length,
      totalPages: Math.max(1, Math.ceil(data.length / 20)),
    },
  }
}
