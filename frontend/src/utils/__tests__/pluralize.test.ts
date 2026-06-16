import { describe, expect, it } from 'vitest'
import { pluralizeNotes } from '@/utils/pluralize'

describe('pluralizeNotes', () => {
  it('uses singular form for 1', () => {
    expect(pluralizeNotes(1)).toBe('заметка')
  })

  it('uses few form for 2', () => {
    expect(pluralizeNotes(2)).toBe('заметки')
  })

  it('uses many form for 5', () => {
    expect(pluralizeNotes(5)).toBe('заметок')
  })
})
