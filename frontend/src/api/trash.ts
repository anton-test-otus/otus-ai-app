import { apiClient } from './client';
import type { Note, PaginatedResponse, HydraCollection } from '../types';

interface TrashNote {
  id: string;
  title: string;
  content: string;
  deletedAt: string;
  folder: {
    id: string;
    name: string;
  } | null;
}

export const trashApi = {
  async getTrash(page = 1, perPage = 20): Promise<PaginatedResponse<TrashNote>> {
    const response = await apiClient.get<HydraCollection<TrashNote>>('/notes/trash', { 
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

  async restore(id: string): Promise<Note> {
    return apiClient.post<Note>(`/notes/${id}/restore`, {});
  },

  async deletePermanent(id: string): Promise<void> {
    await apiClient.delete(`/notes/${id}`);
  },

  async emptyTrash(): Promise<void> {
    await apiClient.post('/notes/trash/empty', {});
  },
};
