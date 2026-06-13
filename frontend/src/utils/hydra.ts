import type { HydraCollection } from '@/types'

export function parseHydraCollection<T>(
  response: HydraCollection<T> | T[]
): { data: T[]; total: number } {
  if (Array.isArray(response)) {
    return { data: response, total: response.length }
  }

  const data = response['hydra:member'] ?? response.member ?? []
  const total = response['hydra:totalItems'] ?? response.totalItems ?? 0

  return { data, total }
}
