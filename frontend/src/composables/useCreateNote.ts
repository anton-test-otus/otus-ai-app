import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFoldersStore } from '@/stores/folders'
import { useNotesStore } from '@/stores/notes'
import { useTagsStore } from '@/stores/tags'
import { DEFAULT_NOTE_TITLE } from '@/utils/note'
import type { CreateNoteRequest } from '@/types'

interface NewNoteContext {
  folderId?: string | null
  tags?: string[]
}

interface ActiveNoteContext {
  folderId: string | null
  tags: string[]
}

const activeNoteContext = ref<ActiveNoteContext | null>(null)

export function syncActiveNoteContext(context: ActiveNoteContext | null) {
  activeNoteContext.value = context
}

function resolveFilterTagNames(
  selectedTagIds: string[],
  getTagById: (id: string) => { name: string } | undefined,
): string[] {
  return selectedTagIds
    .map((tagId) => getTagById(tagId)?.name)
    .filter((name): name is string => Boolean(name))
}

function resolveNewNoteContext(
  context: NewNoteContext,
  routeName: string | symbol | null | undefined,
  selectedFolderId: string | null,
  selectedTagIds: string[],
  getTagById: (id: string) => { name: string } | undefined,
): { folderId: string | null; tags: string[] } {
  if (context.folderId !== undefined || context.tags !== undefined) {
    return {
      folderId: context.folderId ?? null,
      tags: context.tags ?? [],
    }
  }

  if (
    (routeName === 'note' || routeName === 'note-new')
    && activeNoteContext.value
  ) {
    return {
      folderId: activeNoteContext.value.folderId,
      tags: [...activeNoteContext.value.tags],
    }
  }

  return {
    folderId: selectedFolderId,
    tags: resolveFilterTagNames(selectedTagIds, getTagById),
  }
}

export function useCreateNote() {
  const route = useRoute()
  const router = useRouter()
  const foldersStore = useFoldersStore()
  const notesStore = useNotesStore()
  const tagsStore = useTagsStore()

  function openNewNote(context: NewNoteContext = {}) {
    const { folderId, tags } = resolveNewNoteContext(
      context,
      route.name,
      foldersStore.selectedFolderId,
      tagsStore.selectedTags,
      tagsStore.getTagById,
    )

    router.push({
      name: 'note-new',
      query: {
        mode: 'edit',
        ...(folderId ? { folderId } : {}),
        ...(tags.length > 0 ? { tags } : {}),
      },
    })
  }

  async function createNoteWithContent(data: CreateNoteRequest) {
    return notesStore.createNote({
      title: data.title.trim() || DEFAULT_NOTE_TITLE,
      content: data.content,
      folderId: data.folderId,
      tags: data.tags,
    })
  }

  return {
    openNewNote,
    createNoteWithContent,
  }
}
