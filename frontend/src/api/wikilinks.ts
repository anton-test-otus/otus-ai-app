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
  titles: string[];
}

export interface ResolveWikiLinksResponse {
  [title: string]: ResolvedWikiLink | ResolvedWikiLink[] | null;
}

export const wikiLinksApi = {
  async getBacklinks(noteId: string): Promise<BacklinkNote[]> {
    return apiClient.get<BacklinkNote[]>(`/notes/${noteId}/backlinks`);
  },

  async resolveWikiLinks(titles: string[]): Promise<ResolveWikiLinksResponse> {
    return apiClient.post<ResolveWikiLinksResponse>('/notes/resolve-wikilinks', { titles });
  },
};
