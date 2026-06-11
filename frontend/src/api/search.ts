import { apiClient } from './client';
import type { Note, PaginatedResponse } from '../types';

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
  async search(criteria: SearchCriteria): Promise<PaginatedResponse<Note>> {
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

    return apiClient.get<PaginatedResponse<Note>>('/notes/search', { params });
  },
};
