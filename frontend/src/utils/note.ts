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

export function normalizeNote(raw: Note): Note {
  return {
    ...raw,
    folderId: getNoteFolderId(raw),
  }
}
