export interface ParsedWikiLink {
  title: string
  alias: string | null
  placeholder: string
}

export interface WikiLinkMatch {
  from: number
  to: number
  fullMatch: string
  title: string
  alias: string | null
}

export function createWikiLinkPattern(): RegExp {
  return /\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/g
}

export function parseWikiLinks(content: string): ParsedWikiLink[] {
  const links: ParsedWikiLink[] = []
  const pattern = createWikiLinkPattern()
  let match: RegExpExecArray | null

  while ((match = pattern.exec(content)) !== null) {
    links.push({
      title: match[1].trim(),
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
    const from = basePos + match.index
    matches.push({
      from,
      to: from + match[0].length,
      fullMatch: match[0],
      title: match[1].trim(),
      alias: match[2] ? match[2].trim() : null,
    })
  }

  return matches
}
