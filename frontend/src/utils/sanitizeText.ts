/** Нормализация текста заметки (nbsp, zero-width, control chars). Не защита от XSS. */
export function sanitizeNoteText(text: string): string {
  return text
    .replace(/\u00a0/g, ' ')
    .replace(/\u202f/g, ' ')
    .replace(/\u2007/g, ' ')
    .replace(/[\u200b-\u200d\u2060\ufeff]/g, '')
    .replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]/g, '')
}

export function sanitizeNoteTitle(title: string): string {
  return sanitizeNoteText(title)
}

export function sanitizeNoteContent(content: string): string {
  return sanitizeNoteText(content)
}
