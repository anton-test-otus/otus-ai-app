import { apiClient } from './client';
import { normalizeNoteListItem } from '@/utils/note';
import { parseHydraCollection } from '@/utils/hydra';
import type { HydraCollection, NoteListItem, PaginatedResponse } from '../types';

export interface SearchCriteria {
  q: string;
  folderId?: string;
  tags?: string[];
  dateFrom?: string;
  dateTo?: string;
  page?: number;
  perPage?: number;
}

export const searchApi = {
  async search(criteria: SearchCriteria): Promise<PaginatedResponse<NoteListItem>> {
    const params: Record<string, any> = {
      q: criteria.q,
      page: criteria.page || 1,
      perPage: criteria.perPage || 20,
    };

    if (criteria.folderId) {
      params.folderId = criteria.folderId;
    }

    if (criteria.tags && criteria.tags.length > 0) {
      params.tags = criteria.tags;
    }

    if (criteria.dateFrom) {
      params.dateFrom = criteria.dateFrom;
    }

    if (criteria.dateTo) {
      params.dateTo = criteria.dateTo;
    }

    const response = await apiClient.get<PaginatedResponse<NoteListItem>>('/notes/search', { params });
    return {
      ...response,
      data: (response.data || []).map(normalizeNoteListItem),
    };
  },

  /** Title-only search (wiki link picker). Full-text search uses {@link search}. */
  async searchByTitle(criteria: {
    q: string;
    page?: number;
    perPage?: number;
  }): Promise<PaginatedResponse<NoteListItem>> {
    const page = criteria.page ?? 1;
    const perPage = criteria.perPage ?? 20;

    const response = await apiClient.get<HydraCollection<NoteListItem>>('/notes', {
      params: {
        title: criteria.q,
        page,
        itemsPerPage: perPage,
      },
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
};
