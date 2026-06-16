import { describe, expect, it } from 'vitest'
import { hasNoteBody } from '@/utils/note'

describe('hasNoteBody', () => {
  it('returns false for empty string', () => {
    expect(hasNoteBody('')).toBe(false)
  })

  it('returns false for whitespace-only content', () => {
    expect(hasNoteBody('   ')).toBe(false)
    expect(hasNoteBody('\n\t  ')).toBe(false)
  })

  it('returns true for non-empty content', () => {
    expect(hasNoteBody('hello')).toBe(true)
    expect(hasNoteBody('  hello  ')).toBe(true)
  })
})
