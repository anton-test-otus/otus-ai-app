import { apiClient } from './client';
import { parseHydraCollection } from '@/utils/hydra';
import type { Folder, HydraCollection } from '../types';

function buildFolderPayload(data: {
  name?: string
  parentId?: string | null
  icon?: string | null
}): Record<string, unknown> {
  const payload: Record<string, unknown> = {};

  if (data.name !== undefined) {
    payload.name = data.name;
  }

  if ('parentId' in data) {
    payload.parent = data.parentId ? `/api/folders/${data.parentId}` : null;
  }

  if ('icon' in data) {
    payload.icon = data.icon;
  }

  return payload;
}

export const foldersApi = {
  async getAll(): Promise<Folder[]> {
    const response = await apiClient.get<Folder[] | HydraCollection<Folder>>('/folders/tree');
    return parseHydraCollection(response).data;
  },

  async create(folderData: { name: string; parentId?: string; icon?: string | null }): Promise<Folder> {
    return apiClient.post<Folder>('/folders', buildFolderPayload(folderData));
  },

  async update(id: string, folderData: Partial<Folder>): Promise<Folder> {
    const payload = buildFolderPayload(folderData);
    return apiClient.patch<Folder>(`/folders/${id}`, payload);
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(`/folders/${id}`);
  },

  async count(id: string): Promise<{ notes: number; folders: number }> {
    return apiClient.get<{ notes: number; folders: number }>(`/folders/${id}/count`);
  },
};
