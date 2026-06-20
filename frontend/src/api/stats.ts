import { apiClient } from './client'

export interface FolderNoteCount {
  folderId: string | null
  folderName: string
  count: number
}

export interface TagNoteCount {
  tagId: string
  tagName: string
  count: number
}

export interface DashboardStats {
  notesCount: number
  foldersCount: number
  tagsCount: number
  linksCount: number
  favoritesCount: number
  trashCount: number
  notesByFolder: FolderNoteCount[]
  topTags: TagNoteCount[]
}

export const statsApi = {
  getDashboardStats(): Promise<DashboardStats> {
    return apiClient.get<DashboardStats>('/stats')
  },
}
