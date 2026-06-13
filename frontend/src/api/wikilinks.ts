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

export interface NoteGraphNode {
  id: string;
  title: string;
  folderId: string | null;
  isFavorite: boolean;
}

export interface NoteGraphEdge {
  id: string;
  source: string;
  target: string;
  aliases: (string | null)[];
}

export interface NoteGraphResponse {
  nodes: NoteGraphNode[];
  edges: NoteGraphEdge[];
  truncated: boolean;
  frontierNodeIds: string[];
}

export type NoteGraphDirection = 'both' | 'outgoing' | 'incoming';

export const wikiLinksApi = {
  async getBacklinks(noteId: string): Promise<BacklinkNote[]> {
    return apiClient.get<BacklinkNote[]>(`/notes/${noteId}/backlinks`);
  },

  async getGraph(
    noteId: string,
    depth = 1,
    direction: NoteGraphDirection = 'both',
  ): Promise<NoteGraphResponse> {
    return apiClient.get<NoteGraphResponse>(`/notes/${noteId}/graph`, {
      params: { depth, direction },
    });
  },

  async resolveWikiLinks(ids: string[]): Promise<ResolveWikiLinksResponse> {
    return apiClient.post<ResolveWikiLinksResponse>('/notes/resolve-wikilinks', { ids });
  },
};
