import { useRouter } from 'vue-router'
import { useFoldersStore } from '@/stores/folders'
import { useNotesStore } from '@/stores/notes'
import { DEFAULT_NOTE_TITLE } from '@/utils/note'
import type { CreateNoteRequest } from '@/types'

interface NewNoteContext {
  folderId?: string | null
  tags?: string[]
}

export function useCreateNote() {
  const router = useRouter()
  const foldersStore = useFoldersStore()
  const notesStore = useNotesStore()

  function openNewNote(context: NewNoteContext = {}) {
    const folderId = context.folderId !== undefined
      ? context.folderId
      : foldersStore.selectedFolderId

    router.push({
      name: 'note-new',
      query: {
        mode: 'edit',
        ...(folderId ? { folderId } : {}),
      },
    })
  }

  async function createNoteWithContent(data: CreateNoteRequest) {
    return notesStore.createNote({
      title: data.title.trim() || DEFAULT_NOTE_TITLE,
      content: data.content,
      folderId: data.folderId,
    })
  }

  return {
    openNewNote,
    createNoteWithContent,
  }
}
