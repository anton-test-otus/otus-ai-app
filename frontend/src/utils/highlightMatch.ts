import { escapeHtml } from './escapeHtml'

function escapeRegex(str: string): string {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

export function highlightMatch(text: string, query: string): string {
  const escaped = escapeHtml(text)
  if (!query) return escaped

  const regex = new RegExp(`(${escapeRegex(query)})`, 'gi')
  return escaped.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-700">$1</mark>')
}
