import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { formatCardDate } from '@/utils/date'

describe('formatCardDate', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2024-06-15T12:00:00.000Z'))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('returns "Сегодня" for dates from the same calendar day', () => {
    expect(formatCardDate('2024-06-15T08:30:00.000Z')).toBe('Сегодня')
  })

  it('returns "Вчера" for dates one day ago', () => {
    expect(formatCardDate('2024-06-14T08:30:00.000Z')).toBe('Вчера')
  })

  it('returns a locale date string for older dates', () => {
    expect(formatCardDate('2024-05-01T08:30:00.000Z')).toBe(
      new Date('2024-05-01T08:30:00.000Z').toLocaleDateString('ru-RU'),
    )
  })

  it('supports sentence-style relative labels', () => {
    expect(
      formatCardDate('2024-06-15T08:30:00.000Z', { relativeLabels: 'sentence' }),
    ).toBe('сегодня')
    expect(
      formatCardDate('2024-06-14T08:30:00.000Z', { relativeLabels: 'sentence' }),
    ).toBe('вчера')
  })
})
