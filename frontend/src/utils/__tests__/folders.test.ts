import { describe, expect, it } from 'vitest'
import { findFolderInTree } from '@/utils/folders'
import type { Folder } from '@/types'

const folderTree: Folder[] = [
  {
    id: 'root',
    userId: 'user-1',
    parentId: null,
    name: 'Root',
    deletedAt: null,
    children: [
      {
        id: 'child',
        userId: 'user-1',
        parentId: 'root',
        name: 'Child',
        deletedAt: null,
        children: [
          {
            id: 'nested',
            userId: 'user-1',
            parentId: 'child',
            name: 'Nested',
            deletedAt: null,
          },
        ],
      },
    ],
  },
]

describe('findFolderInTree', () => {
  it('finds a deeply nested folder by id', () => {
    expect(findFolderInTree(folderTree, 'nested')).toEqual(
      expect.objectContaining({ id: 'nested', name: 'Nested' }),
    )
  })

  it('returns undefined when the folder is missing', () => {
    expect(findFolderInTree(folderTree, 'missing')).toBeUndefined()
    expect(findFolderInTree(undefined, 'root')).toBeUndefined()
  })
})
