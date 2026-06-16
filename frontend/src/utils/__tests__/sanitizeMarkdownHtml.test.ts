import { describe, expect, it } from 'vitest'
import { getNoteContentPreview } from '@/utils/note'

describe('getNoteContentPreview', () => {
  it('strips script tags from content preview', () => {
    const preview = getNoteContentPreview('<script>alert(1)</script>Hello')

    expect(preview).not.toContain('<script>')
    expect(preview).toContain('Hello')
  })

  it('strips img tags with event handlers', () => {
    const preview = getNoteContentPreview('<img src=x onerror=alert(1)>Note text')

    expect(preview).not.toContain('<img')
    expect(preview).toContain('Note text')
  })

  it('renders plain markdown headings as plain text', () => {
    expect(getNoteContentPreview('# Title')).toBe('Title')
  })
})
