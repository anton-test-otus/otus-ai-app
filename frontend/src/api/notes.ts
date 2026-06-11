import { apiClient } from './client'
import { normalizeNote } from '@/utils/note'
import { resolveTagNamesToIris } from '@/utils/tags'
import type { Note, CreateNoteRequest, UpdateNoteRequest, ApiResponse, HydraCollection } from '@/types'

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
  async filter(criteria: NoteListCriteria): Promise<ApiResponse<Note[]>> {
    const page = criteria.page ?? 1
    const perPage = criteria.perPage ?? 20
    const response = await apiClient.get<{
      data: Note[]
      meta: ApiResponse<Note[]>['meta']
    }>('/notes/search', { params: buildSearchParams(criteria) })

    const data = (response.data || []).map(normalizeNote)
    const meta = response.meta ?? {
      currentPage: page,
      perPage,
      total: data.length,
      totalPages: Math.ceil(data.length / perPage),
    }

    return { data, meta }
  },

  async getAll(page = 1, perPage = 20, folderId?: string | null, tags?: string[]): Promise<ApiResponse<Note[]>> {
    if (tags && tags.length > 0) {
      return this.filter({ page, perPage, folderId, tags, isFavorite: false })
    }
    const params: Record<string, string | number | boolean> = {
      page,
      itemsPerPage: perPage,
      isFavorite: false,
    }
    if (folderId !== undefined && folderId !== null) {
      params['folder.id'] = folderId
    }
    const response = await apiClient.get<HydraCollection<Note>>('/notes', { params })
    
    const data = (response['hydra:member'] || response['member'] || []).map(normalizeNote);
    const total = response['hydra:totalItems'] || response['totalItems'] || 0;
    
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

  async getFavorites(
    folderId?: string | null,
    perPage = 100,
    tags?: string[],
  ): Promise<ApiResponse<Note[]>> {
    if (tags && tags.length > 0) {
      return this.filter({ page: 1, perPage, folderId, tags, isFavorite: true })
    }

    const params: Record<string, string | number | boolean> = {
      isFavorite: true,
      itemsPerPage: perPage,
    }
    if (folderId !== undefined && folderId !== null) {
      params['folder.id'] = folderId
    }
    const response = await apiClient.get<HydraCollection<Note>>('/notes', { params })

    const data = (response['hydra:member'] || response['member'] || []).map(normalizeNote)

    return { data }
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
      title: data.title,
      content: data.content,
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

  async update(id: string, data: UpdateNoteRequest): Promise<Note> {
    // Преобразуем folderId в IRI для API Platform
    const payload: any = {
      ...data,
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

  async search(query: string, page = 1, perPage = 20): Promise<ApiResponse<Note[]>> {
    const params = { 
      page, 
      itemsPerPage: perPage,
      'title': query,
    }
    const response = await apiClient.get<HydraCollection<Note>>('/notes', { params })
    
    const data = (response['hydra:member'] || response['member'] || []).map(normalizeNote);
    const total = response['hydra:totalItems'] || response['totalItems'] || 0;
    
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
}
