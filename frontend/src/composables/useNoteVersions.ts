import { ref, Ref } from 'vue'
import { apiClient } from '@/api/client'
import { getApiErrorMessage } from '@/utils/apiError'
import { parseHydraCollection } from '@/utils/hydra'
import type { NoteVersion, HydraCollection, RestoreVersionRequest } from '@/types'

interface UseNoteVersionsReturn {
  versions: Ref<NoteVersion[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  fetchVersions: (noteId: string) => Promise<void>
  restoreVersion: (noteId: string, versionId: string, mode: RestoreVersionRequest['mode']) => Promise<void>
  refresh: () => Promise<void>
}

export function useNoteVersions(initialNoteId?: string): UseNoteVersionsReturn {
  const versions = ref<NoteVersion[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const currentNoteId = ref(initialNoteId)

  const fetchVersions = async (noteId: string) => {
    currentNoteId.value = noteId
    loading.value = true
    error.value = null

    try {
      const response = await apiClient.get<HydraCollection<NoteVersion>>(
        `/notes/${noteId}/versions`
      )
      const { data } = parseHydraCollection(response)

      versions.value = data
        .map(version => ({
          ...version,
          // Normalize field names from snake_case to camelCase
          noteId: version.noteId || (version as any).note_id || noteId,
          createdAt: version.createdAt || (version as any).created_at,
        }))
        .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
    } catch (e: unknown) {
      error.value = getApiErrorMessage(e, 'Ошибка загрузки версий')
    } finally {
      loading.value = false
    }
  }

  const restoreVersion = async (
    noteId: string,
    versionId: string,
    mode: RestoreVersionRequest['mode']
  ) => {
    loading.value = true
    error.value = null

    try {
      await apiClient.post(
        `/notes/${noteId}/versions/${versionId}/restore`,
        { mode }
      )
      
      // Refresh versions list after restore
      await fetchVersions(noteId)
    } catch (e: unknown) {
      error.value = getApiErrorMessage(e, 'Ошибка восстановления версии')
      throw e
    } finally {
      loading.value = false
    }
  }

  const refresh = async () => {
    if (currentNoteId.value) {
      await fetchVersions(currentNoteId.value)
    }
  }

  return {
    versions,
    loading,
    error,
    fetchVersions,
    restoreVersion,
    refresh,
  }
}
