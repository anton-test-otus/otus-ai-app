import { apiClient } from './client';
import type { Tag, Note, PaginatedResponse, HydraCollection } from '../types';

export const tagsApi = {
  async getAll(): Promise<Tag[]> {
    const response = await apiClient.get<HydraCollection<Tag>>('/tags');
    return response['hydra:member'] || response['member'] || [];
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
    const response = await apiClient.get<HydraCollection<Note>>(`/tags/${id}/notes`, { 
      params: { page, itemsPerPage: perPage } 
    });
    
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
    };
  },
};
