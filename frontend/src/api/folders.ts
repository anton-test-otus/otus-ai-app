import { apiClient } from './client';
import type { Folder, HydraCollection } from '../types';

export const foldersApi = {
  async getAll(): Promise<Folder[]> {
    const response = await apiClient.get<Folder[] | HydraCollection<Folder>>('/folders/tree');
    
    // API Platform может вернуть либо 'hydra:member' либо 'member'
    if (response && typeof response === 'object') {
      const hydraResponse = response as HydraCollection<Folder>;
      if ('hydra:member' in hydraResponse) {
        return hydraResponse['hydra:member'] || [];
      }
      if ('member' in hydraResponse) {
        return hydraResponse['member'] || [];
      }
    }
    
    // Если это массив
    return (response as Folder[]) || [];
  },

  async create(folderData: { name: string; parentId?: string }): Promise<Folder> {
    return apiClient.post<Folder>('/folders', folderData);
  },

  async update(id: string, folderData: Partial<Folder>): Promise<Folder> {
    return apiClient.put<Folder>(`/folders/${id}`, folderData);
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(`/folders/${id}`);
  },

  async count(id: string): Promise<{ notes: number; folders: number }> {
    return apiClient.get<{ notes: number; folders: number }>(`/folders/${id}/count`);
  },
};
