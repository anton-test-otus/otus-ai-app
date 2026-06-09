export interface User {
  id: string
  email: string
  roles: string[]
  isActive: boolean
  createdAt?: string
}

export interface Note {
  id: string
  userId: string
  folderId: string | null
  folder?: Folder
  title: string
  content: string
  tags?: Tag[]
  createdAt: string
  updatedAt: string
  deletedAt: string | null
}

export interface Folder {
  id: string
  userId: string
  parentId: string | null
  parent?: Folder
  name: string
  children?: Folder[]
  deletedAt: string | null
}

export interface Tag {
  id: string
  userId: string
  name: string
}

export interface NoteVersion {
  id: string
  noteId: string
  title: string
  content: string
  createdAt: string
}

export interface NoteLink {
  sourceNoteId: string
  targetNoteId: string
}

export interface ApiResponse<T> {
  data: T
  meta?: PaginationMeta
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
}

export interface PaginationMeta {
  currentPage: number
  perPage: number
  total: number
  totalPages: number
}

// API Platform Hydra Collection
export interface HydraCollection<T> {
  'hydra:member'?: T[]
  'member'?: T[]
  'hydra:totalItems'?: number
  'totalItems'?: number
  'hydra:view'?: {
    'hydra:first'?: string
    'hydra:last'?: string
    'hydra:next'?: string
    'hydra:previous'?: string
  }
  '@context'?: string
  '@id'?: string
  '@type'?: string
}

export interface LoginRequest {
  username: string
  password: string
}

export interface RegisterRequest {
  email: string
  password: string
}

export interface AuthResponse {
  token: string
  refreshToken?: string
  user: User
}

export interface CreateNoteRequest {
  title: string
  content: string
  folderId?: string | null
}

export interface UpdateNoteRequest {
  title?: string
  content?: string
  folderId?: string | null
  tags?: string[]
}

export type SaveStatus = 'idle' | 'saving' | 'saved' | 'error'

export type ViewMode = 'edit' | 'preview' | 'split'

export type Theme = 'light' | 'dark'
