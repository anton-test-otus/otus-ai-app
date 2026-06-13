import { apiClient } from './client'
import { normalizeNote, normalizeNoteListItem } from '@/utils/note'
import { sanitizeNoteContent, sanitizeNoteTitle } from '@/utils/sanitizeText'
import { resolveTagNamesToIris } from '@/utils/tags'
import { parseHydraCollection } from '@/utils/hydra'
import type { Note, NoteListItem, CreateNoteRequest, UpdateNoteRequest, ApiResponse, HydraCollection } from '@/types'

export interface NoteListCriteria {
  page?: number
  perPage?: number
  folderId?: string | null
  tags?: string[]
  isFavorite?: boolean
}

function buildSearchParams(criteria: NoteListCriteria): Record<string, string | number | boolean | string[]> {
  const params: Record<string, string | number | boolean | string[]> = {
    page: criteria.page ?? 1,
    perPage: criteria.perPage ?? 20,
  }

  if (criteria.folderId) {
    params.folderId = criteria.folderId
  }

  if (criteria.tags && criteria.tags.length > 0) {
    params.tags = criteria.tags
  }

  if (criteria.isFavorite !== undefined) {
    params.isFavorite = criteria.isFavorite
  }

  return params
}

export const notesApi = {
  async filter(criteria: NoteListCriteria): Promise<ApiResponse<NoteListItem[]>> {
    const page = criteria.page ?? 1
    const perPage = criteria.perPage ?? 20
    const response = await apiClient.get<{
      data: NoteListItem[]
      meta: ApiResponse<NoteListItem[]>['meta']
    }>('/notes/search', { params: buildSearchParams(criteria) })

    const data = (response.data || []).map(normalizeNoteListItem)
    const meta = response.meta ?? {
      currentPage: page,
      perPage,
      total: data.length,
      totalPages: Math.ceil(data.length / perPage),
    }

    return { data, meta }
  },

  async getAll(page = 1, perPage = 20, folderId?: string | null, tags?: string[]): Promise<ApiResponse<NoteListItem[]>> {
    if (tags && tags.length > 0) {
      return this.filter({ page, perPage, folderId, tags })
    }
    const params: Record<string, string | number | boolean> = {
      page,
      itemsPerPage: perPage,
    }
    if (folderId !== undefined && folderId !== null) {
      params['folder.id'] = folderId
    }
    const response = await apiClient.get<HydraCollection<NoteListItem>>('/notes', { params })
    const { data: rawData, total } = parseHydraCollection(response)
    const data = rawData.map(normalizeNoteListItem)
    
    return {
      data,
      meta: {
        currentPage: page,
        perPage,
        total,
        totalPages: Math.ceil(total / perPage),
      },
    }
  },

  async getFavorites(page = 1, perPage = 20): Promise<ApiResponse<NoteListItem[]>> {
    const params: Record<string, string | number | boolean> = {
      isFavorite: true,
      page,
      itemsPerPage: perPage,
    }
    const response = await apiClient.get<HydraCollection<NoteListItem>>('/notes', { params })

    const { data: rawData, total } = parseHydraCollection(response)
    const data = rawData.map(normalizeNoteListItem)

    return {
      data,
      meta: {
        currentPage: page,
        perPage,
        total,
        totalPages: Math.ceil(total / perPage),
      },
    }
  },

  async toggleFavorite(id: string, isFavorite: boolean): Promise<Note> {
    const note = await apiClient.patch<Note>(`/notes/${id}`, { isFavorite })
    return normalizeNote(note)
  },

  async getById(id: string): Promise<Note> {
    const note = await apiClient.get<Note>(`/notes/${id}`)
    return normalizeNote(note)
  },

  async create(data: CreateNoteRequest): Promise<Note> {
    const payload: Record<string, unknown> = {
      title: sanitizeNoteTitle(data.title),
      content: sanitizeNoteContent(data.content),
    }

    if (data.folderId) {
      payload.folder = `/api/folders/${data.folderId}`
    }

    if (data.tags && data.tags.length > 0) {
      payload.tags = await resolveTagNamesToIris(data.tags)
    }

    const note = await apiClient.post<Note>('/notes', payload)
    return normalizeNote(note)
  },

  async moveToFolder(id: string, folderId: string | null): Promise<Note> {
    const payload: Record<string, unknown> = {
      folder: folderId ? `/api/folders/${folderId}` : null,
    }

    const note = await apiClient.patch<Note>(`/notes/${id}`, payload)
    return normalizeNote(note)
  },

  async update(id: string, data: UpdateNoteRequest): Promise<Note> {
    // Преобразуем folderId в IRI для API Platform
    const payload: any = {
      ...data,
    }

    if ('title' in data && data.title !== undefined) {
      payload.title = sanitizeNoteTitle(data.title)
    }

    if ('content' in data && data.content !== undefined) {
      payload.content = sanitizeNoteContent(data.content)
    }
    
    if ('folderId' in data) {
      delete payload.folderId
      if (data.folderId) {
        payload.folder = `/api/folders/${data.folderId}`
      } else {
        payload.folder = null
      }
    }

    if ('tags' in data) {
      delete payload.tags
      payload.tags = data.tags?.length
        ? await resolveTagNamesToIris(data.tags)
        : []
    }
    
    const note = await apiClient.put<Note>(`/notes/${id}`, payload)
    return normalizeNote(note)
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(`/notes/${id}`)
  },
}
