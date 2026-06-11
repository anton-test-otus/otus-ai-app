<template>
  <div class="folder-item">
    <div
      class="group folder-content relative flex items-center gap-1 list-row-padding rounded cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors min-w-0"
      :class="{
        'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-primary-500 pl-[6px]': isSelected,
        'border-l-2 border-transparent pl-[6px]': !isSelected,
      }"
      @click="handleSelect"
    >
      <span class="w-4 shrink-0 flex items-center justify-center">
        <i
          v-if="hasChildren"
          class="pi text-xs text-surface-500 cursor-pointer hover:text-primary-500 transition-colors"
          :class="isExpanded ? 'pi-chevron-down' : 'pi-chevron-right'"
          @click.stop="toggleExpand"
        />
      </span>

      <i
        class="pi w-4 shrink-0 text-sm text-primary-500"
        :class="hasChildren && isExpanded ? 'pi-folder-open' : 'pi-folder'"
      />

      <span class="folder-name relative flex-1 min-w-0 overflow-hidden">
        <span class="folder-name-text block text-sm truncate">{{ folder.name }}</span>
      </span>

      <div
        class="folder-actions absolute right-0 top-0 bottom-0 flex w-[7.5rem] items-center justify-end gap-0 pr-0.5 pl-2 opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition-opacity rounded-r"
        :class="isSelected
          ? 'bg-primary-50 dark:bg-primary-900/20'
          : 'bg-surface-100 dark:bg-surface-800'"
      >
        <Button
          icon="pi pi-plus"
          text
          rounded
          size="small"
          class="sidebar-icon-btn"
          @mousedown.prevent
          @click.stop="showCreateDialog = true"
          v-tooltip.top="'Создать подпапку'"
        />
        <Button
          icon="pi pi-pencil"
          text
          rounded
          size="small"
          class="sidebar-icon-btn"
          @mousedown.prevent
          @click.stop="showEditDialog = true"
          v-tooltip.top="'Редактировать'"
        />
        <Button
          icon="pi pi-trash"
          text
          rounded
          size="small"
          severity="danger"
          class="sidebar-icon-btn"
          @mousedown.prevent
          @click.stop="confirmDelete"
          v-tooltip.top="'Удалить'"
        />
      </div>
    </div>

    <div
      v-if="hasChildren && isExpanded"
      class="folder-children ml-[10px] pl-3 border-l border-surface-200 dark:border-surface-700 mt-0.5 space-y-0.5"
    >
      <FolderTreeItem
        v-for="child in folder.children"
        :key="child.id"
        :folder="child"
        :depth="depth + 1"
        @select="$emit('select', $event)"
        @update="$emit('update')"
        @delete="$emit('delete')"
      />
    </div>

    <!-- Create Subfolder Dialog -->
    <Dialog
      v-model:visible="showCreateDialog"
      modal
      header="Создать подпапку"
      :style="MODAL_WIDTH.sm"
    >
      <div class="flex flex-col gap-4 py-4">
        <div class="flex flex-col gap-2">
          <label for="subfolder-name" class="font-semibold">Название папки</label>
          <InputText
            id="subfolder-name"
            v-model="newSubfolderName"
            autofocus
            @keyup.enter="createSubfolder"
          />
        </div>
        <p class="text-sm text-surface-500">
          Родительская папка: <strong>{{ folder.name }}</strong>
        </p>
      </div>

      <template #footer>
        <Button label="Отмена" text @click="showCreateDialog = false" />
        <Button label="Создать" @click="createSubfolder" :disabled="!newSubfolderName.trim()" />
      </template>
    </Dialog>

    <!-- Edit Dialog -->
    <Dialog
      v-model:visible="showEditDialog"
      modal
      header="Редактировать папку"
      :style="MODAL_WIDTH.sm"
    >
      <div class="flex flex-col gap-4 py-4">
        <div class="flex flex-col gap-2">
          <label for="edit-folder-name" class="font-semibold">Название папки</label>
          <InputText
            id="edit-folder-name"
            v-model="editName"
            autofocus
            @keyup.enter="saveEdit"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Отмена" text @click="showEditDialog = false" />
        <Button label="Сохранить" @click="saveEdit" :disabled="!editName.trim()" />
      </template>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog
      v-model:visible="showDeleteDialog"
      modal
      header="Удаление папки"
      :style="MODAL_WIDTH.md"
    >
      <div class="py-4">
        <Message severity="warn" :closable="false">
          <template v-if="deleteCount">
            <p class="mb-2">
              Папка <strong>{{ folder.name }}</strong> содержит:
            </p>
            <ul class="list-disc list-inside">
              <li v-if="deleteCount.folders > 0">{{ deleteCount.folders }} {{ pluralize(deleteCount.folders, 'подпапку', 'подпапки', 'подпапок') }}</li>
              <li v-if="deleteCount.notes > 0">{{ deleteCount.notes }} {{ pluralize(deleteCount.notes, 'заметку', 'заметки', 'заметок') }}</li>
            </ul>
            <p class="mt-2">Всё содержимое будет перемещено в корзину.</p>
          </template>
          <p v-else>
            Вы действительно хотите удалить папку <strong>{{ folder.name }}</strong>?
          </p>
        </Message>
      </div>

      <template #footer>
        <Button label="Отмена" text @click="showDeleteDialog = false" />
        <Button label="Удалить" severity="danger" @click="deleteFolder" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { MODAL_WIDTH } from '@/constants/modal';
import { useFoldersStore } from '../../stores/folders';
import type { Folder } from '../../types';

interface Props {
  folder: Folder;
  depth?: number;
}

const props = withDefaults(defineProps<Props>(), {
  depth: 0,
});

const emit = defineEmits<{
  select: [folderId: string];
  update: [];
  delete: [];
}>();

const foldersStore = useFoldersStore();
const isExpanded = ref(true);
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const newSubfolderName = ref('');
const editName = ref(props.folder.name);
const deleteCount = ref<{ folders: number; notes: number } | null>(null);
const deleting = ref(false);

const isSelected = computed(() => foldersStore.selectedFolderId === props.folder.id);
const hasChildren = computed(() => props.folder.children && props.folder.children.length > 0);

function handleSelect() {
  foldersStore.selectFolder(props.folder.id);
  emit('select', props.folder.id);
}

function toggleExpand() {
  isExpanded.value = !isExpanded.value;
}

async function createSubfolder() {
  if (!newSubfolderName.value.trim()) return;

  try {
    await foldersStore.createFolder(newSubfolderName.value, props.folder.id);
    newSubfolderName.value = '';
    showCreateDialog.value = false;
    isExpanded.value = true;
    emit('update');
  } catch (error) {
    console.error('Failed to create subfolder:', error);
  }
}

async function saveEdit() {
  if (!editName.value.trim()) return;

  try {
    await foldersStore.updateFolder(props.folder.id, { name: editName.value });
    showEditDialog.value = false;
    emit('update');
  } catch (error) {
    console.error('Failed to update folder:', error);
  }
}

async function confirmDelete() {
  try {
    deleteCount.value = await foldersStore.getFolderCount(props.folder.id);
    showDeleteDialog.value = true;
  } catch (error) {
    console.error('Failed to get folder count:', error);
    deleteCount.value = null;
    showDeleteDialog.value = true;
  }
}

async function deleteFolder() {
  deleting.value = true;
  try {
    await foldersStore.deleteFolder(props.folder.id);
    showDeleteDialog.value = false;
    emit('delete');
    emit('update');
  } catch (error) {
    console.error('Failed to delete folder:', error);
  } finally {
    deleting.value = false;
  }
}

function pluralize(count: number, one: string, few: string, many: string): string {
  const mod10 = count % 10;
  const mod100 = count % 100;

  if (mod10 === 1 && mod100 !== 11) return one;
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return few;
  return many;
}
</script>

<style scoped>
.folder-item {
  @apply select-none min-w-0;
}

.folder-content {
  @apply transition-all;
}

.folder-content:hover {
  @apply scale-[1.02];
}

.group:hover .folder-name {
  margin-right: 7.5rem;
}

.group:hover .folder-name-text {
  text-overflow: clip;
  -webkit-mask-image: linear-gradient(to right, #000 0%, #000 35%, rgb(0 0 0 / 0.55) 65%, transparent 100%);
  mask-image: linear-gradient(to right, #000 0%, #000 35%, rgb(0 0 0 / 0.55) 65%, transparent 100%);
}
</style>
