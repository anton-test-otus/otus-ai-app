/** UUID заметки в wiki-ссылке: [[uuid]] или [[uuid|отображаемый текст]] */
export const WIKI_LINK_UUID_SOURCE =
  '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'

export interface ParsedWikiLink {
  noteId: string
  alias: string | null
  placeholder: string
}

export interface WikiLinkMatch {
  from: number
  to: number
  fullMatch: string
  noteId: string
  alias: string | null
}

export function isValidWikiLinkUuid(value: string): boolean {
  return new RegExp(`^${WIKI_LINK_UUID_SOURCE}$`, 'i').test(value.trim())
}

export function normalizeWikiLinkUuid(value: string): string {
  return value.trim().toLowerCase()
}

/** Matches [[uuid]], [[uuid|alias]] and escaped \\[[uuid]], \\[[uuid|alias]] */
export function createWikiLinkPattern(): RegExp {
  return new RegExp(
    `(?:\\\\\\[\\\\\\[|\\[\\[)(${WIKI_LINK_UUID_SOURCE})(?:\\|([^\\]]+))?\\]\\]`,
    'gi',
  )
}

export function replaceWikiLinksForPlainText(
  content: string,
  titlesById: Record<string, string> = {},
): string {
  return content.replace(createWikiLinkPattern(), (_match, noteId: string, alias?: string) => {
    const trimmedAlias = alias?.trim()
    if (trimmedAlias) {
      return trimmedAlias
    }

    const normalizedId = normalizeWikiLinkUuid(noteId)
    return titlesById[normalizedId] ?? ''
  })
}

export function formatWikiLink(noteId: string, alias?: string | null): string {
  const id = normalizeWikiLinkUuid(noteId)
  if (!isValidWikiLinkUuid(id)) {
    throw new Error('Invalid note id for wiki link')
  }

  const trimmedAlias = alias?.trim()
  if (trimmedAlias) {
    return `[[${id}|${trimmedAlias}]]`
  }

  return `[[${id}]]`
}

export function parseWikiLinks(content: string): ParsedWikiLink[] {
  const links: ParsedWikiLink[] = []
  const pattern = createWikiLinkPattern()
  let match: RegExpExecArray | null

  while ((match = pattern.exec(content)) !== null) {
    const noteId = normalizeWikiLinkUuid(match[1])
    if (!isValidWikiLinkUuid(noteId)) {
      continue
    }

    links.push({
      noteId,
      alias: match[2] ? match[2].trim() : null,
      placeholder: match[0],
    })
  }

  return links
}

export function findWikiLinksInText(text: string, basePos: number): WikiLinkMatch[] {
  const matches: WikiLinkMatch[] = []
  const pattern = createWikiLinkPattern()
  let match: RegExpExecArray | null

  while ((match = pattern.exec(text)) !== null) {
    const noteId = normalizeWikiLinkUuid(match[1])
    if (!isValidWikiLinkUuid(noteId)) {
      continue
    }

    const from = basePos + match.index
    matches.push({
      from,
      to: from + match[0].length,
      fullMatch: match[0],
      noteId,
      alias: match[2] ? match[2].trim() : null,
    })
  }

  return matches
}
