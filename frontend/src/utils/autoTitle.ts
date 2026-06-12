export const AUTO_TITLE_MAX_LENGTH = 128

import { replaceWikiLinksForPlainText } from '@/lib/wikiLinks'

/** Убирает inline-markdown из одной строки */
function stripInlineMarkdown(line: string): string {
  return replaceWikiLinksForPlainText(
    line
      .replace(/^#{1,6}\s+/, '')
      .replace(/^>\s+/, '')
      .replace(/^[-*+]\s+/, '')
      .replace(/^\d+\.\s+/, '')
      .replace(/!\[([^\]]*)\]\([^)]*\)/g, '$1')
      .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1')
      .replace(/\*\*([^*]+)\*\*/g, '$1')
      .replace(/\*([^*]+)\*/g, '$1')
      .replace(/__([^_]+)__/g, '$1')
      .replace(/_([^_]+)_/g, '$1')
      .replace(/`([^`]+)`/g, '$1'),
  ).trim()
}

/** Первый непустой абзац markdown как plain text */
export function getFirstParagraphPlainText(markdown: string): string | null {
  const normalized = markdown.replace(/\r\n/g, '\n').trim()
  if (!normalized) {
    return null
  }

  const blocks = normalized.split(/\n\s*\n/)
  for (const block of blocks) {
    const lines = block
      .split('\n')
      .map((line) => stripInlineMarkdown(line))
      .filter(Boolean)

    if (lines.length > 0) {
      return lines.join(' ').replace(/\s+/g, ' ').trim()
    }
  }

  return null
}

const SENTENCE_END = /[.!?](?:\s|$)/

/** Первое предложение из текста; без разделителя — весь текст */
export function getFirstSentence(text: string): string {
  const trimmed = text.trim()
  if (!trimmed) {
    return ''
  }

  const match = trimmed.match(SENTENCE_END)
  if (!match || match.index === undefined) {
    return trimmed
  }

  return trimmed.slice(0, match.index + 1).trim()
}

/** Обрезка по границе слова; fallback — жёсткая обрезка */
export function truncateAtWordBoundary(text: string, maxLength: number): string {
  const trimmed = text.trim()
  if (trimmed.length <= maxLength) {
    return trimmed
  }

  const slice = trimmed.slice(0, maxLength)
  const lastSpace = slice.lastIndexOf(' ')
  if (lastSpace > maxLength * 0.5) {
    return slice.slice(0, lastSpace).trim()
  }

  return slice.trim()
}

/** Заголовок из первого предложения первого абзаца */
export function deriveAutoTitleFromMarkdown(
  markdown: string,
  maxLength = AUTO_TITLE_MAX_LENGTH,
): string | null {
  const paragraph = getFirstParagraphPlainText(markdown)
  if (!paragraph) {
    return null
  }

  const sentence = getFirstSentence(paragraph)
  if (!sentence) {
    return null
  }

  return truncateAtWordBoundary(sentence, maxLength)
}
