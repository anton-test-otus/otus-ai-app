import type { Note } from '@/types'

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

export function hasNoteBody(content: string): boolean {
  return content.trim().length > 0
}

/** Plain-text preview for note lists: strips HTML/markdown and normalizes whitespace */
export function getNoteContentPreview(content: string, maxLength = 150): string {
  const withoutHtml = content.replace(/<[^>]*>/g, ' ')
  const withoutMarkdown = withoutHtml.replace(/[#*`\[\]]/g, '')
  const plainText = withoutMarkdown.replace(/\s+/g, ' ').trim()

  if (plainText.length <= maxLength) {
    return plainText
  }

  return `${plainText.substring(0, maxLength)}...`
}
