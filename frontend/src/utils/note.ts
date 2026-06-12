import { replaceWikiLinksForPlainText } from '@/lib/wikiLinks'
import type { Note, NoteListItem } from '@/types'

export const NOTE_PREVIEW_MAX_LENGTH = 150

type NoteWithFolder = {
  folderId?: string | null
  folder?: Note['folder'] | string | null
}

export function getNoteFolderId(note: NoteWithFolder): string | null {
  if (note.folderId) {
    return note.folderId
  }

  const folder = note.folder
  if (!folder) {
    return null
  }

  if (typeof folder === 'string') {
    const id = folder.split('/').filter(Boolean).pop()
    return id ?? null
  }

  return folder.id ?? null
}

export const DEFAULT_NOTE_TITLE = 'Новая заметка'

export function normalizeNote(raw: Note): Note {
  return {
    ...raw,
    folderId: getNoteFolderId(raw),
    isFavorite: raw.isFavorite ?? false,
  }
}

type NoteListItemRaw = Omit<NoteListItem, 'folderId' | 'isFavorite' | 'contentPreview'> & {
  folderId?: string | null
  isFavorite?: boolean
  contentPreview?: string
  content?: string
}

export function normalizeNoteListItem(raw: NoteListItemRaw): NoteListItem {
  return {
    id: raw.id,
    title: raw.title,
    folderId: getNoteFolderId(raw),
    folder: raw.folder,
    tags: raw.tags,
    isFavorite: raw.isFavorite ?? false,
    contentPreview: getNoteContentPreview(raw.contentPreview ?? raw.content ?? ''),
    createdAt: raw.createdAt,
    updatedAt: raw.updatedAt,
    deletedAt: raw.deletedAt ?? null,
  }
}

export function toNoteListItem(note: Note): NoteListItem {
  return {
    id: note.id,
    title: note.title,
    folderId: getNoteFolderId(note),
    folder: note.folder,
    tags: note.tags,
    isFavorite: note.isFavorite,
    contentPreview: getNoteContentPreview(note.contentPreview ?? note.content),
    createdAt: note.createdAt,
    updatedAt: note.updatedAt,
    deletedAt: note.deletedAt ?? null,
  }
}

export function hasNoteBody(content: string): boolean {
  return content.trim().length > 0
}

/** Plain-text preview for note lists: strips HTML/markdown and normalizes whitespace */
export function getNoteContentPreview(content: string, maxLength = NOTE_PREVIEW_MAX_LENGTH): string {
  const withoutWikiLinks = replaceWikiLinksForPlainText(content)
  const withoutHtml = withoutWikiLinks.replace(/<[^>]*>/g, ' ')
  const withoutMarkdown = withoutHtml.replace(/[#*`\[\]]/g, '')
  const plainText = withoutMarkdown.replace(/\s+/g, ' ').trim()

  if (plainText.length <= maxLength) {
    return plainText
  }

  return `${plainText.substring(0, maxLength)}...`
}
