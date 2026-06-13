import { apiClient } from './client';
import { normalizeNoteListItem } from '@/utils/note';
import { parseHydraCollection } from '@/utils/hydra';
import type { Note, NoteListItem, PaginatedResponse, HydraCollection } from '../types';

export const trashApi = {
  async getTrash(page = 1, perPage = 20): Promise<PaginatedResponse<NoteListItem>> {
    const response = await apiClient.get<HydraCollection<NoteListItem>>('/notes/trash', { 
      params: { page, itemsPerPage: perPage } 
    });
    const { data: rawData, total } = parseHydraCollection(response);
    const data = rawData.map(normalizeNoteListItem);
    
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
