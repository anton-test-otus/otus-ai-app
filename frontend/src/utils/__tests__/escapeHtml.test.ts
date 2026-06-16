import { describe, expect, it } from 'vitest'
import { escapeHtml } from '@/utils/escapeHtml'

describe('escapeHtml', () => {
  it('escapes angle brackets in HTML tags', () => {
    expect(escapeHtml('<img src=x onerror=alert(1)>')).toBe(
      '&lt;img src=x onerror=alert(1)&gt;',
    )
  })

  it('escapes ampersands and quotes', () => {
    expect(escapeHtml('Tom & Jerry say "hi" and \'bye\'')).toBe(
      'Tom &amp; Jerry say &quot;hi&quot; and &#39;bye&#39;',
    )
  })

  it('leaves safe plain text unchanged', () => {
    expect(escapeHtml('Hello World')).toBe('Hello World')
  })
})
