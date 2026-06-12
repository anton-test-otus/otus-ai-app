import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { foldersApi } from '../api/folders';
import { getApiErrorMessage } from '@/utils/apiError';
import type { Folder } from '../types';

export const useFoldersStore = defineStore('folders', () => {
  const folders = ref<Folder[]>([]);
  const selectedFolderId = ref<string | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const folderTree = computed(() => folders.value);

  const selectedFolder = computed(() =>
    selectedFolderId.value ? getFolderById(selectedFolderId.value) : undefined
  );

  const flatFolders = computed(() => {
    const flat: Folder[] = [];
    const flatten = (items: Folder[], depth = 0) => {
      if (!items || !Array.isArray(items)) return;
      items.forEach(item => {
        flat.push({ ...item, depth } as Folder & { depth: number });
        if (item.children && item.children.length > 0) {
          flatten(item.children, depth + 1);
        }
      });
    };
    flatten(folders.value || []);
    return flat;
  });

  let initialized = false;
  let fetchPromise: Promise<void> | null = null;

  async function fetchFolders(options?: { force?: boolean }) {
    if (initialized && !options?.force) {
      return;
    }

    if (fetchPromise && !options?.force) {
      return fetchPromise;
    }

    loading.value = true;
    error.value = null;
    fetchPromise = (async () => {
      try {
        const result = await foldersApi.getAll();
        folders.value = result || [];
        initialized = true;
      } catch (e: unknown) {
        folders.value = [];
        error.value = getApiErrorMessage(e, 'Ошибка загрузки папок');
        throw e;
      } finally {
        loading.value = false;
        fetchPromise = null;
      }
    })();

    return fetchPromise;
  }

  async function createFolder(name: string, parentId?: string) {
    loading.value = true;
    error.value = null;
    try {
      const newFolder = await foldersApi.create({ name, parentId });
      await fetchFolders({ force: true });
      return newFolder;
    } catch (e: unknown) {
      error.value = getApiErrorMessage(e, 'Ошибка создания папки');
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function updateFolder(id: string, data: Partial<Folder>) {
    loading.value = true;
    error.value = null;
    try {
      const updated = await foldersApi.update(id, data);
      await fetchFolders({ force: true });
      return updated;
    } catch (e: unknown) {
      error.value = getApiErrorMessage(e, 'Ошибка обновления папки');
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteFolder(id: string) {
    loading.value = true;
    error.value = null;
    try {
      await foldersApi.delete(id);
      if (selectedFolderId.value === id) {
        selectedFolderId.value = null;
      }
      await fetchFolders({ force: true });
    } catch (e: unknown) {
      error.value = getApiErrorMessage(e, 'Ошибка удаления папки');
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function getFolderCount(id: string) {
    try {
      return await foldersApi.count(id);
    } catch (e: unknown) {
      error.value = getApiErrorMessage(e, 'Ошибка получения статистики папки');
      throw e;
    }
  }

  function getFolderById(id: string): Folder | undefined {
    const find = (items: Folder[]): Folder | undefined => {
      if (!items || !Array.isArray(items)) return undefined;
      for (const item of items) {
        if (item.id === id) return item;
        if (item.children) {
          const found = find(item.children);
          if (found) return found;
        }
      }
      return undefined;
    };
    return find(folders.value || []);
  }

  function selectFolder(id: string) {
    selectedFolderId.value = id;
  }

  function clearFolderSelection() {
    selectedFolderId.value = null;
  }

  return {
    folders,
    folderTree,
    flatFolders,
    selectedFolderId,
    selectedFolder,
    loading,
    error,
    fetchFolders,
    createFolder,
    updateFolder,
    deleteFolder,
    getFolderCount,
    getFolderById,
    selectFolder,
    clearFolderSelection,
  };
});
