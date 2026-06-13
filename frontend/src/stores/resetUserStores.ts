import { clearWikiLinkTitleCache } from '@/components/editor/wikiLinkNode'
import { useFoldersStore } from '@/stores/folders'
import { useNotesStore } from '@/stores/notes'
import { useTagsStore } from '@/stores/tags'
import { useTrashStore } from '@/stores/trash'

export function resetUserStores(): void {
  useNotesStore().reset()
  useFoldersStore().reset()
  useTagsStore().reset()
  useTrashStore().reset()
  clearWikiLinkTitleCache()
}
