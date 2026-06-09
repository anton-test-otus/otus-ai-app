import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { foldersApi } from '../api/folders';
import type { Folder } from '../types';

export const useFoldersStore = defineStore('folders', () => {
  const folders = ref<Folder[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const folderTree = computed(() => folders.value);

  const flatFolders = computed(() => {
    const flat: Folder[] = [];
    const flatten = (items: Folder[], depth = 0) => {
      items.forEach(item => {
        flat.push({ ...item, depth } as Folder & { depth: number });
        if (item.children && item.children.length > 0) {
          flatten(item.children, depth + 1);
        }
      });
    };
    flatten(folders.value);
    return flat;
  });

  async function fetchFolders() {
    loading.value = true;
    error.value = null;
    try {
      folders.value = await foldersApi.getAll();
    } catch (e: any) {
      error.value = e.message || 'Ошибка загрузки папок';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function createFolder(name: string, parentId?: string) {
    loading.value = true;
    error.value = null;
    try {
      const newFolder = await foldersApi.create({ name, parentId });
      await fetchFolders();
      return newFolder;
    } catch (e: any) {
      error.value = e.message || 'Ошибка создания папки';
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
      await fetchFolders();
      return updated;
    } catch (e: any) {
      error.value = e.message || 'Ошибка обновления папки';
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
      await fetchFolders();
    } catch (e: any) {
      error.value = e.message || 'Ошибка удаления папки';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function getFolderCount(id: string) {
    try {
      return await foldersApi.count(id);
    } catch (e: any) {
      error.value = e.message || 'Ошибка получения статистики папки';
      throw e;
    }
  }

  function getFolderById(id: string): Folder | undefined {
    const find = (items: Folder[]): Folder | undefined => {
      for (const item of items) {
        if (item.id === id) return item;
        if (item.children) {
          const found = find(item.children);
          if (found) return found;
        }
      }
      return undefined;
    };
    return find(folders.value);
  }

  return {
    folders,
    folderTree,
    flatFolders,
    loading,
    error,
    fetchFolders,
    createFolder,
    updateFolder,
    deleteFolder,
    getFolderCount,
    getFolderById,
  };
});
