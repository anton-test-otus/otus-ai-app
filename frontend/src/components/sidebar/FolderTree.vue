<template>
  <div class="folder-tree">
    <div class="folder-tree-header flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Папки</h3>
      <Button
        icon="pi pi-plus"
        size="small"
        text
        rounded
        @click="showCreateDialog = true"
        v-tooltip.top="'Создать папку'"
      />
    </div>

    <VueDraggable
      v-model="localFolders"
      :animation="200"
      handle=".folder-drag-handle"
      :group="{ name: 'folders' }"
      @end="onDragEnd"
      class="space-y-1"
    >
      <FolderTreeItem
        v-for="folder in localFolders"
        :key="folder.id"
        :folder="folder"
        :selected-folder-id="selectedFolderId"
        @select="$emit('select', $event)"
        @update="handleUpdate"
        @delete="handleDelete"
      />
    </VueDraggable>

    <!-- Create Folder Dialog -->
    <Dialog
      v-model:visible="showCreateDialog"
      modal
      header="Создать папку"
      :style="{ width: '25rem' }"
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
import { ref, computed, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import FolderTreeItem from './FolderTreeItem.vue';
import { useFoldersStore } from '../../stores/folders';
import type { Folder } from '../../types';

interface Props {
  folders: Folder[];
  selectedFolderId?: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  select: [folderId: string | null];
  update: [];
}>();

const foldersStore = useFoldersStore();
const localFolders = ref<Folder[]>(Array.isArray(props.folders) ? [...props.folders] : []);
const showCreateDialog = ref(false);
const newFolderName = ref('');
const newFolderParentId = ref<string | null>(null);

watch(() => props.folders, (newFolders) => {
  localFolders.value = Array.isArray(newFolders) ? [...newFolders] : [];
}, { deep: true });

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
  
  flatten(localFolders.value || []);
  return options;
});

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

async function handleUpdate() {
  emit('update');
}

async function handleDelete() {
  emit('update');
}

function onDragEnd() {
  emit('update');
}
</script>

<style scoped>
.folder-tree {
  @apply select-none;
}
</style>
