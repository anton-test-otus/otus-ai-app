<template>
  <div class="page-container-narrow">
    <div class="page-header">
      <div>
        <h1 class="page-title">Управление тегами</h1>
      </div>
      <p class="page-subtitle mt-0 text-surface-600 dark:text-surface-400">
        Управляйте тегами для организации ваших заметок
      </p>
    </div>

    <!-- Create new tag -->
    <Card class="mb-6">
      <template #content>
        <div class="flex gap-4">
          <InputText
            v-model="newTagName"
            placeholder="Название нового тега"
            class="flex-1"
            @keyup.enter="createTag"
          />
          <Button
            label="Создать тег"
            icon="pi pi-plus"
            @click="createTag"
            :disabled="!newTagName.trim()"
            :loading="creating"
          />
        </div>
      </template>
    </Card>

    <!-- Tags list -->
    <Card>
      <template #title>
        <div class="flex items-center justify-between">
          <span>Ваши теги ({{ tags.length }})</span>
          <div class="relative w-64">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-muted"></i>
            <InputText
              v-model="searchQuery"
              placeholder="Поиск..."
              class="w-full pl-10"
            />
          </div>
        </div>
      </template>

      <template #content>
        <div v-if="loading && tags.length === 0" class="flex justify-center py-8">
          <ProgressSpinner style="width: 50px; height: 50px" stroke-width="4" />
        </div>

        <div v-else-if="filteredTags.length === 0" class="text-center py-8 text-surface-500 dark:text-surface-400">
          <i class="pi pi-tag text-4xl mb-4" />
          <p>{{ searchQuery ? 'Теги не найдены' : 'У вас пока нет тегов' }}</p>
        </div>

        <DataTable
          v-else
          :value="filteredTags"
          :rows="20"
          :paginator="filteredTags.length > 20"
          dataKey="id"
          class="tags-table"
        >
          <Column field="name" header="Название">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <i class="pi pi-tag text-primary-500" />
                <span class="font-medium">{{ data.name }}</span>
              </div>
            </template>
          </Column>

          <Column header="Действия" style="width: 12rem">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  icon="pi pi-pencil"
                  size="small"
                  outlined
                  @click="startEdit(data)"
                  v-tooltip.top="'Редактировать'"
                />
                <Button
                  icon="pi pi-trash"
                  size="small"
                  severity="danger"
                  outlined
                  @click="confirmDelete(data)"
                  v-tooltip.top="'Удалить'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Edit Dialog -->
    <Dialog
      v-model:visible="showEditDialog"
      modal
      header="Редактировать тег"
      :style="MODAL_WIDTH.sm"
    >
      <div class="flex flex-col gap-4 py-4">
        <div class="flex flex-col gap-2">
          <label for="edit-tag-name" class="font-semibold">Название тега</label>
          <InputText
            id="edit-tag-name"
            v-model="editTagName"
            autofocus
            @keyup.enter="saveEdit"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Отмена" text @click="showEditDialog = false" />
        <Button
          label="Сохранить"
          @click="saveEdit"
          :disabled="!editTagName.trim()"
          :loading="updating"
        />
      </template>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog
      v-model:visible="showDeleteDialog"
      modal
      header="Удаление тега"
      :style="MODAL_WIDTH.md"
    >
      <div class="py-4">
        <Message severity="warn" :closable="false">
          <p>
            Вы действительно хотите удалить тег <strong>{{ tagToDelete?.name }}</strong>?
          </p>
          <p class="mt-2 text-sm">
            Тег будет удален из всех заметок, где он используется.
          </p>
        </Message>
      </div>

      <template #footer>
        <Button label="Отмена" text @click="showDeleteDialog = false" />
        <Button
          label="Удалить"
          severity="danger"
          @click="deleteTag"
          :loading="deleting"
        />
      </template>
    </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import { MODAL_WIDTH } from '@/constants/modal';
import { useTagsStore } from '../stores/tags';
import type { Tag } from '../types';

const tagsStore = useTagsStore();

const newTagName = ref('');
const searchQuery = ref('');
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const editTagName = ref('');
const tagToEdit = ref<Tag | null>(null);
const tagToDelete = ref<Tag | null>(null);
const creating = ref(false);
const updating = ref(false);
const deleting = ref(false);

const tags = computed(() => tagsStore.tags);
const loading = computed(() => tagsStore.loading);

const filteredTags = computed(() => {
  if (!searchQuery.value.trim()) {
    return tags.value;
  }
  const query = searchQuery.value.toLowerCase();
  return tags.value.filter(tag => tag.name.toLowerCase().includes(query));
});

async function createTag() {
  if (!newTagName.value.trim()) return;

  creating.value = true;
  try {
    await tagsStore.createTag(newTagName.value.trim());
    newTagName.value = '';
  } catch (error) {
    console.error('Failed to create tag:', error);
  } finally {
    creating.value = false;
  }
}

function startEdit(tag: Tag) {
  tagToEdit.value = tag;
  editTagName.value = tag.name;
  showEditDialog.value = true;
}

async function saveEdit() {
  if (!tagToEdit.value || !editTagName.value.trim()) return;

  updating.value = true;
  try {
    await tagsStore.updateTag(tagToEdit.value.id, editTagName.value.trim());
    showEditDialog.value = false;
    tagToEdit.value = null;
    editTagName.value = '';
  } catch (error) {
    console.error('Failed to update tag:', error);
  } finally {
    updating.value = false;
  }
}

function confirmDelete(tag: Tag) {
  tagToDelete.value = tag;
  showDeleteDialog.value = true;
}

async function deleteTag() {
  if (!tagToDelete.value) return;

  deleting.value = true;
  try {
    await tagsStore.deleteTag(tagToDelete.value.id);
    showDeleteDialog.value = false;
    tagToDelete.value = null;
  } catch (error) {
    console.error('Failed to delete tag:', error);
  } finally {
    deleting.value = false;
  }
}

onMounted(async () => {
  await tagsStore.fetchTags();
});
</script>

