import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useFoldersStore } from '@/stores/folders'
import { useAppToast } from '@/composables/useAppToast'
import { buildExportMetadata } from '@/utils/exportMetadata'
import {
  buildMarkdownExport,
  downloadTextFile,
  sanitizeExportFilename,
} from '@/utils/exportNote'

export function useNoteExport() {
  const router = useRouter()
  const foldersStore = useFoldersStore()
  const { showError } = useAppToast()
  const isExporting = ref(false)

  async function exportMarkdown(options: {
    noteId: string
    title: string
    content: string
    folderId: string | null
    tags: string[]
    createdAt: string
    updatedAt: string
  }) {
    isExporting.value = true

    try {
      await foldersStore.fetchFolders()
      const metadata = buildExportMetadata(
        {
          title: options.title,
          folderId: options.folderId,
          tags: options.tags.map((name) => ({ id: '', userId: '', name })),
          createdAt: options.createdAt,
          updatedAt: options.updatedAt,
        },
        foldersStore.getFolderById.bind(foldersStore),
      )
      const markdown = await buildMarkdownExport(metadata, options.content)
      const filename = `${sanitizeExportFilename(options.title, options.noteId)}.md`
      downloadTextFile(markdown, filename)
    } catch (error) {
      showError(error, 'Не удалось экспортировать заметку в Markdown')
      throw error
    } finally {
      isExporting.value = false
    }
  }

  function openPrintView(noteId: string) {
    const target = router.resolve({
      name: 'note-print',
      params: { id: noteId },
      query: { auto: '1' },
    })
    window.open(target.href, '_blank', 'noopener,noreferrer')
  }

  return {
    isExporting,
    exportMarkdown,
    openPrintView,
  }
}
