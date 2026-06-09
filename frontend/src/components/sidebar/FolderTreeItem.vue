<template>
  <div class="folder-item">
    <div
      class="folder-content flex items-center gap-2 px-3 py-2 rounded cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors"
      :class="{ 'bg-primary-50 dark:bg-primary-900/20': isSelected }"
      @click="$emit('select', folder.id)"
    >
      <i class="folder-drag-handle pi pi-bars text-sm text-surface-400 cursor-move hover:text-surface-600" />
      <i
        class="pi text-sm cursor-pointer hover:text-primary-500 transition-colors"
        :class="isExpanded ? 'pi-chevron-down' : 'pi-chevron-right'"
        @click.stop="toggleExpand"
        v-if="hasChildren"
      />
      <i class="pi pi-folder text-primary-500" />
      <span class="flex-1 text-sm">{{ folder.name }}</span>
      
      <div class="folder-actions flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <Button
          icon="pi pi-pencil"
          text
          rounded
          size="small"
          @click.stop="showEditDialog = true"
          v-tooltip.top="'Редактировать'"
        />
        <Button
          icon="pi pi-trash"
          text
          rounded
          size="small"
          severity="danger"
          @click.stop="confirmDelete"
          v-tooltip.top="'Удалить'"
        />
      </div>
    </div>

    <!-- Children -->
    <div v-if="hasChildren && isExpanded" class="ml-6 mt-1 space-y-1">
      <VueDraggable
        v-model="localChildren"
        :animation="200"
        handle=".folder-drag-handle"
        :group="{ name: 'folders' }"
        @end="$emit('update')"
      >
        <FolderTreeItem
          v-for="child in localChildren"
          :key="child.id"
          :folder="child"
          :selected-folder-id="selectedFolderId"
          @select="$emit('select', $event)"
          @update="$emit('update')"
          @delete="$emit('delete')"
        />
      </VueDraggable>
    </div>

    <!-- Edit Dialog -->
    <Dialog
      v-model:visible="showEditDialog"
      modal
      header="Редактировать папку"
      :style="{ width: '25rem' }"
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
      :style="{ width: '30rem' }"
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
import { ref, computed, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { useFoldersStore } from '../../stores/folders';
import type { Folder } from '../../types';

interface Props {
  folder: Folder;
  selectedFolderId?: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  select: [folderId: string];
  update: [];
  delete: [];
}>();

const foldersStore = useFoldersStore();
const isExpanded = ref(true);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const editName = ref(props.folder.name);
const deleteCount = ref<{ folders: number; notes: number } | null>(null);
const deleting = ref(false);
const localChildren = ref<Folder[]>(props.folder.children || []);

watch(() => props.folder.children, (newChildren) => {
  if (newChildren) {
    localChildren.value = [...newChildren];
  }
}, { deep: true });

const isSelected = computed(() => props.selectedFolderId === props.folder.id);
const hasChildren = computed(() => props.folder.children && props.folder.children.length > 0);

function toggleExpand() {
  isExpanded.value = !isExpanded.value;
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
  @apply select-none;
}

.folder-content:hover .folder-actions {
  @apply opacity-100;
}
</style>
