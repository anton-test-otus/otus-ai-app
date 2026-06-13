import type { Folder } from '@/types'

export function getFolderPath(
  folderId: string | null | undefined,
  getFolderById: (id: string) => Folder | undefined,
): string | null {
  if (!folderId) {
    return null
  }

  const segments: string[] = []
  const visited = new Set<string>()
  let current = getFolderById(folderId)

  while (current) {
    if (visited.has(current.id)) {
      break
    }
    visited.add(current.id)
    segments.unshift(current.name)
    current = current.parentId ? getFolderById(current.parentId) : undefined
  }

  return segments.length > 0 ? segments.join('/') : null
}
