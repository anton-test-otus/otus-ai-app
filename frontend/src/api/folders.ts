import apiClient from './client';
import type { Folder, ApiResponse } from '../types';

export const foldersApi = {
  async getAll(): Promise<Folder[]> {
    const data = await apiClient.get<Folder[]>('/folders');
    return data;
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

  async count(id: string): Promise<{ folders: number; notes: number }> {
    return apiClient.get<{ folders: number; notes: number }>(`/folders/${id}/count`);
  },
};
