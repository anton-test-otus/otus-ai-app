import { apiClient } from './client'
import type { Note, CreateNoteRequest, UpdateNoteRequest, ApiResponse, HydraCollection } from '@/types'

export const notesApi = {
  async getAll(page = 1, perPage = 20, folderId?: string | null): Promise<ApiResponse<Note[]>> {
    const params: any = { page, itemsPerPage: perPage }
    if (folderId !== undefined && folderId !== null) {
      params['folder.id'] = folderId
    }
    const response = await apiClient.get<HydraCollection<Note>>('/notes', { params })
    
    const data = response['hydra:member'] || response['member'] || [];
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

  async getById(id: string): Promise<Note> {
    return apiClient.get<Note>(`/notes/${id}`)
  },

  async create(data: CreateNoteRequest): Promise<Note> {
    // Преобразуем folderId в IRI для API Platform
    const payload: any = {
      title: data.title,
      content: data.content,
    }
    
    if (data.folderId) {
      payload.folder = `/api/folders/${data.folderId}`
    }
    
    return apiClient.post<Note>('/notes', payload)
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
    
    return apiClient.put<Note>(`/notes/${id}`, payload)
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
    
    const data = response['hydra:member'] || response['member'] || [];
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
