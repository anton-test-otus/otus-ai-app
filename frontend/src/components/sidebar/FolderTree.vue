<template>
  <div class="folder-tree">
    <div class="folder-tree-header flex justify-between items-center mb-4">
      <h3 class="section-title">Папки</h3>
      <Button
        icon="pi pi-plus"
        size="small"
        text
        rounded
        class="sidebar-icon-btn"
        @mousedown.prevent
        @click="showCreateDialog = true"
        v-tooltip.top="'Создать папку'"
      />
    </div>

    <LoadingState v-if="foldersStore.loading && folders.length === 0" compact />

    <ErrorState
      v-else-if="foldersStore.error && folders.length === 0"
      :message="foldersStore.error"
      compact
      @retry="retryLoadFolders"
    />

    <div v-else class="space-y-0.5">
      <div
        class="folder-row group flex items-center gap-2 list-row-padding rounded cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors"
        :class="[
          {
            'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-primary-500 pl-[6px]': isAllNotesActive,
            'border-l-2 border-transparent pl-[6px]': !isAllNotesActive,
          },
          dropZoneClass(isAllNotesActive),
        ]"
        @click="selectAllNotes"
        @dragover="onDragOver"
        @dragenter="onDragEnter"
        @dragleave="onDragLeave"
        @drop="onDrop"
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
          <FolderDropdown
            id="parent-folder"
            v-model="newFolderParentId"
            input-id="parent-folder"
            placeholder="Без родителя"
            only-selectable-parents
          />
        </div>

        <FolderIconPicker v-model="newFolderIcon" :depth="newFolderDepth" />
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
import { useRoute } from 'vue-router';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import LoadingState from '@/components/common/LoadingState.vue';
import ErrorState from '@/components/common/ErrorState.vue';
import FolderDropdown from '@/components/common/FolderDropdown.vue';
import { useAppToast } from '@/composables/useAppToast';
import { MODAL_WIDTH } from '@/constants/modal';
import { findFolderDepthInTree } from '@/utils/folderPath'
import { MAX_FOLDER_TREE_DEPTH } from '@/utils/folderIcon'
import FolderTreeItem from './FolderTreeItem.vue';
import FolderIconPicker from './FolderIconPicker.vue';
import { useNoteFolderDropZone } from '@/composables/useNoteFolderDnD';
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
const route = useRoute();
const { showError } = useAppToast();
const {
  onDragOver,
  onDragEnter,
  onDragLeave,
  onDrop,
  dropZoneClass,
} = useNoteFolderDropZone(null);
const showCreateDialog = ref(false);
const newFolderName = ref('');
const newFolderParentId = ref<string | null>(null);
const newFolderIcon = ref<string | null>(null);

const newFolderDepth = computed(() => {
  if (!newFolderParentId.value) {
    return 0;
  }

  const parentDepth = findFolderDepthInTree(foldersStore.folders, newFolderParentId.value);
  if (parentDepth === null) {
    return 0;
  }

  return Math.min(parentDepth + 1, MAX_FOLDER_TREE_DEPTH);
});

const isAllNotesActive = computed(
  () => route.name === 'dashboard' && !foldersStore.selectedFolderId,
);

function selectAllNotes() {
  foldersStore.clearFolderSelection();
  emit('select', null);
}

async function createFolder() {
  if (!newFolderName.value.trim()) return;

  try {
    await foldersStore.createFolder(
      newFolderName.value,
      newFolderParentId.value || undefined,
      newFolderIcon.value,
    );
    newFolderName.value = '';
    newFolderParentId.value = null;
    newFolderIcon.value = null;
    showCreateDialog.value = false;
    emit('update');
  } catch (error) {
    showError(error, 'Не удалось создать папку');
  }
}

async function retryLoadFolders() {
  await foldersStore.fetchFolders({ force: true });
  emit('update');
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
