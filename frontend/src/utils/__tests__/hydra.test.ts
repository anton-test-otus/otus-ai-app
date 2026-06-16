import { describe, expect, it } from 'vitest'
import { parseHydraCollection } from '@/utils/hydra'

describe('parseHydraCollection', () => {
  it('parses hydra:member and hydra:totalItems', () => {
    const item = { id: 'a' }

    expect(parseHydraCollection({ 'hydra:member': [item], 'hydra:totalItems': 1 })).toEqual({
      data: [item],
      total: 1,
    })
  })

  it('parses member without hydra prefix', () => {
    const item = { id: 'b' }

    expect(parseHydraCollection({ member: [item] })).toEqual({
      data: [item],
      total: 0,
    })
  })

  it('returns bare arrays with total equal to length', () => {
    const items = [{ id: 'c' }, { id: 'd' }]

    expect(parseHydraCollection(items)).toEqual({
      data: items,
      total: 2,
    })
  })

  it('returns empty data and zero total for empty hydra collection', () => {
    expect(parseHydraCollection({ 'hydra:member': [], 'hydra:totalItems': 0 })).toEqual({
      data: [],
      total: 0,
    })
  })
})
