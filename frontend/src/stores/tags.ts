import { defineStore } from 'pinia';
import { ref } from 'vue';
import { tagsApi } from '../api/tags';
import type { Tag, Note, PaginatedResponse } from '../types';

export const useTagsStore = defineStore('tags', () => {
  const tags = ref<Tag[]>([]);
  const selectedTags = ref<string[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchTags() {
    loading.value = true;
    error.value = null;
    try {
      const result = await tagsApi.getAll();
      tags.value = result || [];
    } catch (e: any) {
      console.error('Tags fetch error:', e);
      tags.value = [];
      error.value = e.message || 'Ошибка загрузки тегов';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function createTag(name: string) {
    loading.value = true;
    error.value = null;
    try {
      const newTag = await tagsApi.create(name);
      tags.value.push(newTag);
      tags.value.sort((a, b) => a.name.localeCompare(b.name));
      return newTag;
    } catch (e: any) {
      error.value = e.message || 'Ошибка создания тега';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function updateTag(id: string, name: string) {
    loading.value = true;
    error.value = null;
    try {
      const updated = await tagsApi.update(id, name);
      const index = tags.value.findIndex(t => t.id === id);
      if (index !== -1) {
        tags.value[index] = updated;
        tags.value.sort((a, b) => a.name.localeCompare(b.name));
      }
      return updated;
    } catch (e: any) {
      error.value = e.message || 'Ошибка обновления тега';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteTag(id: string) {
    loading.value = true;
    error.value = null;
    try {
      await tagsApi.delete(id);
      tags.value = tags.value.filter(t => t.id !== id);
      selectedTags.value = selectedTags.value.filter(tagId => tagId !== id);
    } catch (e: any) {
      error.value = e.message || 'Ошибка удаления тега';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function getTagNotes(id: string, page = 1, perPage = 20): Promise<PaginatedResponse<Note>> {
    try {
      return await tagsApi.getNotes(id, page, perPage);
    } catch (e: any) {
      error.value = e.message || 'Ошибка загрузки заметок тега';
      throw e;
    }
  }

  function toggleTagSelection(tagId: string) {
    const index = selectedTags.value.indexOf(tagId);
    if (index === -1) {
      selectedTags.value.push(tagId);
    } else {
      selectedTags.value.splice(index, 1);
    }
  }

  function clearTagSelection() {
    selectedTags.value = [];
  }

  function getTagById(id: string): Tag | undefined {
    return tags.value.find(t => t.id === id);
  }

  function getTagByName(name: string): Tag | undefined {
    return tags.value.find(t => t.name.toLowerCase() === name.toLowerCase());
  }

  return {
    tags,
    selectedTags,
    loading,
    error,
    fetchTags,
    createTag,
    updateTag,
    deleteTag,
    getTagNotes,
    toggleTagSelection,
    clearTagSelection,
    getTagById,
    getTagByName,
  };
});
