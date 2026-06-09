import { apiClient } from './client'
import type { Note, CreateNoteRequest, UpdateNoteRequest, ApiResponse } from '@/types'

export const notesApi = {
  async getAll(page = 1, perPage = 20, folderId?: string | null): Promise<ApiResponse<Note[]>> {
    const params: any = { page, perPage }
    if (folderId !== undefined) {
      params.folderId = folderId
    }
    return await apiClient.get<Note[]>('/notes', { params })
  },

  async getById(id: string): Promise<Note> {
    const response = await apiClient.get<Note>(`/notes/${id}`)
    return response.data
  },

  async create(data: CreateNoteRequest): Promise<Note> {
    const response = await apiClient.post<Note>('/notes', data)
    return response.data
  },

  async update(id: string, data: UpdateNoteRequest): Promise<Note> {
    const response = await apiClient.put<Note>(`/notes/${id}`, data)
    return response.data
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(`/notes/${id}`)
  },

  async search(query: string, page = 1, perPage = 20): Promise<ApiResponse<Note[]>> {
    return await apiClient.get<Note[]>('/notes/search', {
      params: { q: query, page, perPage },
    })
  },
}
