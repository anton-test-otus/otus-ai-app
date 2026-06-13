export const NOTE_DRAG_MIME = 'application/x-otus-note-id'

export function folderDropKey(folderId: string | null | undefined): string {
  return folderId ?? 'root'
}
