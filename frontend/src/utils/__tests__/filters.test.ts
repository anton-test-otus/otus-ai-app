import { describe, expect, it } from 'vitest'
import { buildFilterCriteriaKey } from '@/utils/filters'

describe('buildFilterCriteriaKey', () => {
  it('is stable regardless of tag id order', () => {
    const first = buildFilterCriteriaKey(null, ['b', 'a'])
    const second = buildFilterCriteriaKey(null, ['a', 'b'])

    expect(first).toBe(second)
    expect(first).toBe(JSON.stringify({ folderId: null, tags: ['a', 'b'] }))
  })
})
