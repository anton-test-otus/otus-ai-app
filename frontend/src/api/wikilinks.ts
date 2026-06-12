import { apiClient } from './client';

export interface BacklinkNote {
  id: string;
  title: string;
  updatedAt: string;
}

export interface ResolvedWikiLink {
  id: string;
  title: string;
  updatedAt: string;
}

export interface ResolveWikiLinksRequest {
  ids: string[];
}

export type ResolveWikiLinksResponse = Record<string, ResolvedWikiLink | null>;

export const wikiLinksApi = {
  async getBacklinks(noteId: string): Promise<BacklinkNote[]> {
    return apiClient.get<BacklinkNote[]>(`/notes/${noteId}/backlinks`);
  },

  async resolveWikiLinks(ids: string[]): Promise<ResolveWikiLinksResponse> {
    return apiClient.post<ResolveWikiLinksResponse>('/notes/resolve-wikilinks', { ids });
  },
};
