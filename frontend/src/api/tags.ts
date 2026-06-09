import apiClient from './client';
import type { Tag, Note, PaginatedResponse } from '../types';

export const tagsApi = {
  async getAll(): Promise<Tag[]> {
    return apiClient.get<Tag[]>('/tags');
  },

  async create(name: string): Promise<Tag> {
    return apiClient.post<Tag>('/tags', { name });
  },

  async update(id: string, name: string): Promise<Tag> {
    return apiClient.put<Tag>(`/tags/${id}`, { name });
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(`/tags/${id}`);
  },

  async getNotes(id: string, page = 1, perPage = 20): Promise<PaginatedResponse<Note>> {
    return apiClient.get<PaginatedResponse<Note>>(`/tags/${id}/notes`, { page, perPage });
  },
};
