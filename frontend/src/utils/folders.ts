import type { Folder } from '@/types'

export interface FlatFolderEntry {
  folder: Folder
  depth: number
}

export function findFolderInTree(items: Folder[] | undefined, id: string): Folder | undefined {
  if (!items?.length) return undefined

  for (const item of items) {
    if (item.id === id) return item
    if (item.children?.length) {
      const found = findFolderInTree(item.children, id)
      if (found) return found
    }
  }

  return undefined
}

export function flattenFolderTree(items: Folder[] | undefined, depth = 0): FlatFolderEntry[] {
  const result: FlatFolderEntry[] = []

  for (const item of items ?? []) {
    result.push({ folder: item, depth })
    if (item.children?.length) {
      result.push(...flattenFolderTree(item.children, depth + 1))
    }
  }

  return result
}
