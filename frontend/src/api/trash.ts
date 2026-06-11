import { apiClient } from './client';
import { normalizeNoteListItem } from '@/utils/note';
import type { Note, NoteListItem, PaginatedResponse, HydraCollection } from '../types';

export const trashApi = {
  async getTrash(page = 1, perPage = 20): Promise<PaginatedResponse<NoteListItem>> {
    const response = await apiClient.get<HydraCollection<NoteListItem>>('/notes/trash', { 
      params: { page, itemsPerPage: perPage } 
    });
    
    const data = (response['hydra:member'] || response['member'] || []).map(normalizeNoteListItem);
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
