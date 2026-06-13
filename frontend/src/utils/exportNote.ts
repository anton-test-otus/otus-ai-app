import { wikiLinksApi } from '@/api/wikilinks'
import {
  normalizeWikiLinkUuid,
  parseWikiLinks,
  replaceWikiLinksForExport,
} from '@/lib/wikiLinks'
import { formatDateTime } from '@/utils/date'
import { sanitizeNoteText } from '@/utils/sanitizeText'
import type { NoteExportMetadata } from '@/utils/exportMetadata'

export type { NoteExportMetadata } from '@/utils/exportMetadata'
export { buildExportMetadata } from '@/utils/exportMetadata'

function buildMetadataFooter(metadata: NoteExportMetadata): string {
  const items: string[] = []

  if (metadata.folderPath) {
    items.push(`- **Папка:** ${metadata.folderPath}`)
  }

  if (metadata.tags.length > 0) {
    items.push(`- **Теги:** ${metadata.tags.join(', ')}`)
  }

  items.push(`- **Создано:** ${formatDateTime(metadata.createdAt)}`)
  items.push(`- **Обновлено:** ${formatDateTime(metadata.updatedAt)}`)

  return `---\n\n### Метаданные\n\n${items.join('\n')}`
}

function sanitizeExportMetadata(metadata: NoteExportMetadata): NoteExportMetadata {
  return {
    title: sanitizeNoteText(metadata.title),
    tags: metadata.tags.map((tag) => sanitizeNoteText(tag)),
    folderPath: metadata.folderPath ? sanitizeNoteText(metadata.folderPath) : null,
    createdAt: metadata.createdAt,
    updatedAt: metadata.updatedAt,
  }
}

function buildMarkdownTitle(title: string): string {
  return `# ${title.trim()}`
}

async function resolveWikiTitlesForExport(content: string): Promise<Record<string, string>> {
  const idsNeedingTitle = [
    ...new Set(
      parseWikiLinks(content)
        .filter((link) => !link.alias)
        .map((link) => link.noteId),
    ),
  ]

  if (idsNeedingTitle.length === 0) {
    return {}
  }

  const resolved = await wikiLinksApi.resolveWikiLinks(idsNeedingTitle)
  const titlesById: Record<string, string> = {}

  for (const [id, note] of Object.entries(resolved)) {
    if (note) {
      titlesById[normalizeWikiLinkUuid(id)] = sanitizeNoteText(note.title)
    }
  }

  return titlesById
}

export async function buildMarkdownExport(
  metadata: NoteExportMetadata,
  rawContent: string,
): Promise<string> {
  const safeMetadata = sanitizeExportMetadata(metadata)
  const titlesById = await resolveWikiTitlesForExport(rawContent)
  const body = sanitizeNoteText(
    replaceWikiLinksForExport(sanitizeNoteText(rawContent), titlesById),
  ).trimEnd()
  const title = buildMarkdownTitle(safeMetadata.title)
  const footer = buildMetadataFooter(safeMetadata)

  const parts = [title]
  if (body) {
    parts.push(body)
  }
  parts.push(footer)

  return sanitizeNoteText(parts.join('\n\n'))
}

export function sanitizeExportFilename(title: string, fallbackId: string): string {
  const fallback = `note-${fallbackId.slice(0, 8)}`
  const sanitized = sanitizeNoteText(title)
    .trim()
    .replace(/[<>:"/\\|?*\u0000-\u001f]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 200)

  return sanitized || fallback
}

export function downloadTextFile(content: string, filename: string, mime = 'text/markdown;charset=utf-8') {
  const blob = new Blob([content], { type: mime })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = filename
  anchor.click()
  URL.revokeObjectURL(url)
}
