const ALLOWED_PROTOCOLS = ['http:', 'https:', 'mailto:']

export const LINK_URL_ERROR =
  'Некорректный URL. Используйте http://, https://, mailto: или относительный путь (/..., #...)'

export function isValidLinkUrl(url: string): boolean {
  const trimmed = url.trim()
  if (!trimmed) {
    return false
  }

  if (/^(#|\/|\.\/|\.\.\/)[^\s]*$/.test(trimmed)) {
    return true
  }

  try {
    const parsed = new URL(trimmed)
    return ALLOWED_PROTOCOLS.includes(parsed.protocol)
  } catch {
    return false
  }
}
