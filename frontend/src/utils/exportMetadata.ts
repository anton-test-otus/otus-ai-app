import { getFolderPath } from '@/utils/folderPath'
import type { Folder, Note } from '@/types'

export interface NoteExportMetadata {
  title: string
  tags: string[]
  folderPath: string | null
  createdAt: string
  updatedAt: string
}

export function buildExportMetadata(
  note: Pick<Note, 'title' | 'tags' | 'folderId' | 'createdAt' | 'updatedAt'>,
  getFolderById: (id: string) => Folder | undefined,
): NoteExportMetadata {
  return {
    title: note.title,
    tags: note.tags?.map((tag) => tag.name) ?? [],
    folderPath: getFolderPath(note.folderId, getFolderById),
    createdAt: note.createdAt,
    updatedAt: note.updatedAt,
  }
}
