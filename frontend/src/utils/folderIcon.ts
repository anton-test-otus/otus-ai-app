import { DEFAULT_FOLDER_ICON, FOLDER_ICON_VALUES } from '@/constants/folderIcons'

/** Tree depth is 0-based; max 2 = three nesting levels (briefcase → folder → book). */
export const MAX_FOLDER_TREE_DEPTH = 2

export function toPrimeIconClass(icon: string | null | undefined): string {
  const name = icon && FOLDER_ICON_VALUES.has(icon) ? icon : DEFAULT_FOLDER_ICON
  return `pi pi-${name}`
}

/** Default tree icons: depth 0 — briefcase, 1 — folder, 2 — book */
export function getDefaultFolderIconByDepth(depth: number): string {
  const level = Math.min(Math.max(depth, 0), MAX_FOLDER_TREE_DEPTH)

  if (level === 0) {
    return 'pi pi-briefcase'
  }
  if (level === 1) {
    return 'pi pi-folder'
  }
  return 'pi pi-book'
}

export function resolveFolderTreeIcon(
  icon: string | null | undefined,
  depth = 0,
): string {
  if (icon) {
    return toPrimeIconClass(icon)
  }

  return getDefaultFolderIconByDepth(depth)
}
