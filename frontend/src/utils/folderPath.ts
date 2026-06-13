import type { Folder } from '@/types'

export function findFolderDepthInTree(
  items: Folder[] | undefined,
  folderId: string,
  depth = 0,
): number | null {
  if (!items?.length) {
    return null
  }

  for (const item of items) {
    if (item.id === folderId) {
      return depth
    }
    if (item.children?.length) {
      const childDepth = findFolderDepthInTree(item.children, folderId, depth + 1)
      if (childDepth !== null) {
        return childDepth
      }
    }
  }

  return null
}

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
