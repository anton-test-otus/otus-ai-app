import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { MAX_FOLDER_TREE_DEPTH, resolveFolderTreeIcon } from '@/utils/folderIcon'
import { flattenFolderTree } from '@/utils/folders'
import type { Folder } from '@/types'

export interface FolderDropdownOption {
  label: string
  value: string
  iconClass: string
  depth: number
}

export function useFolderDropdownOptions(
  folders: MaybeRefOrGetter<Folder[] | undefined>,
  options?: { onlySelectableParents?: boolean },
) {
  const folderOptions = computed(() => {
    const items = toValue(folders) || []
    const result: FolderDropdownOption[] = []

    for (const { folder, depth } of flattenFolderTree(items)) {
      const canSelect = !options?.onlySelectableParents || depth < MAX_FOLDER_TREE_DEPTH

      if (canSelect) {
        result.push({
          label: folder.name,
          value: folder.id,
          iconClass: resolveFolderTreeIcon(folder.icon, depth),
          depth,
        })
      }
    }
    return result
  })

  const folderOptionById = computed(() =>
    new Map(folderOptions.value.map((option) => [option.value, option])),
  )

  function getFolderLabel(folderId: string): string {
    return folderOptionById.value.get(folderId)?.label || 'Неизвестная папка'
  }

  function getFolderIconClass(folderId: string): string {
    return folderOptionById.value.get(folderId)?.iconClass || resolveFolderTreeIcon(null, 0)
  }

  function getFolderIndentStyle(folderId: string): { paddingLeft: string } {
    const depth = folderOptionById.value.get(folderId)?.depth ?? 0
    return { paddingLeft: `${depth * 16}px` }
  }

  return {
    folderOptions,
    getFolderLabel,
    getFolderIconClass,
    getFolderIndentStyle,
  }
}
