import { describe, expect, it } from 'vitest'
import { highlightMatch } from '@/utils/highlightMatch'

const MARK = '<mark class="bg-yellow-200 dark:bg-yellow-700">'

describe('highlightMatch', () => {
  it('wraps case-insensitive query match in mark element', () => {
    const result = highlightMatch('Hello World', 'hello')

    expect(result).toContain(`${MARK}Hello</mark>`)
  })

  it('escapes script tags and highlights safe match text', () => {
    const result = highlightMatch('<script>alert(1)</script>', 'script')

    expect(result).not.toContain('<script>')
    expect(result).toContain('&lt;')
    expect(result).toContain(`${MARK}script</mark>`)
  })

  it('escapes XSS payload when query does not match', () => {
    const result = highlightMatch('<img src=x onerror=alert(1)>', 'nomatch')

    expect(result).not.toContain('<img')
    expect(result).toBe('&lt;img src=x onerror=alert(1)&gt;')
  })

  it('returns escaped text unchanged when query is empty', () => {
    expect(highlightMatch('<b>bold</b>', '')).toBe('&lt;b&gt;bold&lt;/b&gt;')
  })
})
