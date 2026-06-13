import { apiClient } from './client';
import { parseHydraCollection } from '@/utils/hydra';
import type { Tag, Note, PaginatedResponse, HydraCollection } from '../types';

export interface TagListCriteria {
  folderId?: string | null;
  tags?: string[];
}

export const tagsApi = {
  async getAll(criteria?: TagListCriteria): Promise<Tag[]> {
    const params: Record<string, string | string[]> = {};
    if (criteria?.folderId) {
      params.folderId = criteria.folderId;
    }
    if (criteria?.tags && criteria.tags.length > 0) {
      params.tags = criteria.tags;
    }
    const response = await apiClient.get<HydraCollection<Tag>>('/tags', { params });
    return parseHydraCollection(response).data;
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
    const { data, total } = parseHydraCollection(response);
    
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
