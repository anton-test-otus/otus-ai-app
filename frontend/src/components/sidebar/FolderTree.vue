<template>
  <div class="folder-tree">
    <div class="folder-tree-header flex justify-between items-center mb-4">
      <h3 class="section-title">Папки</h3>
      <Button
        icon="pi pi-plus"
        size="small"
        text
        rounded
        @click="showCreateDialog = true"
        v-tooltip.top="'Создать папку'"
      />
    </div>

    <div class="space-y-0.5">
      <div
        class="folder-row group flex items-center gap-2 list-row-padding rounded cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors"
        :class="{
          'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-primary-500 pl-[6px]': !foldersStore.selectedFolderId,
          'border-l-2 border-transparent pl-[6px]': foldersStore.selectedFolderId,
        }"
        @click="selectAllNotes"
      >
        <i class="pi pi-inbox w-4 shrink-0 text-sm text-surface-500" />
        <span class="flex-1 text-sm">Все заметки</span>
      </div>

      <FolderTreeItem
        v-for="folder in folders"
        :key="folder.id"
        :folder="folder"
        @select="$emit('select', $event)"
        @update="handleUpdate"
        @delete="handleDelete"
      />
    </div>

    <!-- Create Folder Dialog -->
    <Dialog
      v-model:visible="showCreateDialog"
      modal
      header="Создать папку"
      :style="MODAL_WIDTH.sm"
    >
      <div class="flex flex-col gap-4 py-4">
        <div class="flex flex-col gap-2">
          <label for="folder-name" class="font-semibold">Название папки</label>
          <InputText
            id="folder-name"
            v-model="newFolderName"
            autofocus
            @keyup.enter="createFolder"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="parent-folder" class="font-semibold">Родительская папка (опционально)</label>
          <Dropdown
            id="parent-folder"
            v-model="newFolderParentId"
            :options="selectableFolders"
            option-label="label"
            option-value="value"
            placeholder="Без родителя"
            show-clear
          />
        </div>
      </div>

      <template #footer>
        <Button label="Отмена" text @click="showCreateDialog = false" />
        <Button label="Создать" @click="createFolder" :disabled="!newFolderName.trim()" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import { MODAL_WIDTH } from '@/constants/modal';
import FolderTreeItem from './FolderTreeItem.vue';
import { useFoldersStore } from '../../stores/folders';
import type { Folder } from '../../types';

interface Props {
  folders: Folder[];
}

defineProps<Props>();

const emit = defineEmits<{
  select: [folderId: string | null];
  update: [];
}>();

const foldersStore = useFoldersStore();
const showCreateDialog = ref(false);
const newFolderName = ref('');
const newFolderParentId = ref<string | null>(null);

const selectableFolders = computed(() => {
  const options: { label: string; value: string }[] = [];

  const flatten = (items: Folder[], depth = 0) => {
    if (!items || !Array.isArray(items)) return;
    items.forEach(item => {
      options.push({
        label: '  '.repeat(depth) + item.name,
        value: item.id,
      });
      if (item.children && item.children.length > 0) {
        flatten(item.children, depth + 1);
      }
    });
  };

  flatten(foldersStore.folders || []);
  return options;
});

function selectAllNotes() {
  foldersStore.clearFolderSelection();
  emit('select', null);
}

async function createFolder() {
  if (!newFolderName.value.trim()) return;

  try {
    await foldersStore.createFolder(newFolderName.value, newFolderParentId.value || undefined);
    newFolderName.value = '';
    newFolderParentId.value = null;
    showCreateDialog.value = false;
    emit('update');
  } catch (error: any) {
    console.error('Failed to create folder:', error);
  }
}

function handleUpdate() {
  emit('update');
}

function handleDelete() {
  emit('update');
}
</script>

<style scoped>
.folder-tree {
  @apply select-none min-w-0 w-full;
}

.folder-row {
  @apply transition-all;
}

.folder-row:hover {
  @apply scale-[1.02];
}
</style>
