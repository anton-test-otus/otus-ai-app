<template>
  <AppLayout>
    <div class="page-container">
      <!-- Header -->
      <div class="page-header">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="page-title">Корзина</h1>
            <p v-if="!isLoading && notes.length > 0" class="page-subtitle">
              Заметки хранятся 30 дней после удаления
            </p>
          </div>
          <div class="flex gap-3">
            <Button
              v-if="selectedIds.length > 0"
              label="Восстановить выбранные"
              icon="pi pi-undo"
              severity="secondary"
              :loading="isRestoring"
              @click="restoreSelected"
            />
            <Button
              label="Очистить корзину"
              icon="pi pi-trash"
              severity="danger"
              :disabled="!notes || notes.length === 0"
              :loading="isEmptying"
              @click="confirmEmptyTrash"
            />
          </div>
        </div>
      </div>

      <EmptyState
        v-if="!isLoading && notes && notes.length === 0"
        icon="pi-trash"
        title="Корзина пуста"
        description="Удалённые заметки будут отображаться здесь"
      />

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <!-- Notes List -->
      <div v-if="!isLoading && notes.length > 0" class="space-y-4">
        <Card
          v-for="note in notes"
          :key="note.id"
          class="hover:shadow-lg transition-shadow"
        >
          <template #content>
            <div class="flex items-start gap-4">
              <!-- Checkbox -->
              <Checkbox
                v-model="selectedIds"
                :value="note.id"
                :binary="false"
                class="mt-1"
              />

              <!-- Note Content -->
              <div class="flex-1 min-w-0">
                <h3 class="card-title mb-2">
                  {{ note.title }}
                </h3>
                <p class="card-preview line-clamp-2 mb-3">
                  {{ getNoteContentPreview(note.content) }}
                </p>
                <div class="flex items-center gap-4 text-xs text-muted">
                  <span v-if="note.folder" class="flex items-center gap-1">
                    <i class="pi pi-folder"></i>
                    {{ note.folder.name }}
                  </span>
                  <span class="flex items-center gap-1">
                    <i class="pi pi-clock"></i>
                    Удалена {{ formatDate(note.deletedAt) }}
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2">
                <Button
                  icon="pi pi-undo"
                  severity="secondary"
                  outlined
                  @click="restoreNote(note.id)"
                  v-tooltip.top="'Восстановить'"
                />
                <Button
                  icon="pi pi-times"
                  severity="danger"
                  outlined
                  @click="confirmDelete(note.id)"
                  v-tooltip.top="'Удалить навсегда'"
                />
              </div>
            </div>
          </template>
        </Card>

        <!-- Pagination -->
        <Paginator
          v-if="meta.totalPages > 1"
          :rows="meta.perPage"
          :total-records="meta.total"
          :first="(meta.currentPage - 1) * meta.perPage"
          @page="onPageChange"
        />
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog
      v-model:visible="showDeleteDialog"
      header="Окончательное удаление"
      :modal="true"
      :style="MODAL_WIDTH.md"
    >
      <p class="mb-4">
        Вы уверены, что хотите окончательно удалить эту заметку?
        Это действие нельзя отменить.
      </p>
      <template #footer>
        <Button label="Отмена" severity="secondary" @click="showDeleteDialog = false" />
        <Button label="Удалить" severity="danger" @click="deleteNotePermanent" />
      </template>
    </Dialog>

    <!-- Empty Trash Confirmation Dialog -->
    <Dialog
      v-model:visible="showEmptyDialog"
      header="Очистить корзину"
      :modal="true"
      :style="MODAL_WIDTH.md"
    >
      <p class="mb-4">
        Вы уверены, что хотите очистить корзину? Будут удалены все
        {{ notes.length }} заметок. Это действие нельзя отменить.
      </p>
      <template #footer>
        <Button label="Отмена" severity="secondary" @click="showEmptyDialog = false" />
        <Button label="Очистить" severity="danger" @click="emptyTrash" />
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import { useToast } from 'primevue/usetoast';
import { trashApi } from '../api/trash';
import { getNoteContentPreview } from '@/utils/note';
import { MODAL_WIDTH } from '@/constants/modal';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import Paginator from 'primevue/paginator';
import ProgressSpinner from 'primevue/progressspinner';

interface TrashNote {
  id: string;
  title: string;
  content: string;
  deletedAt: string;
  folder: {
    id: string;
    name: string;
  } | null;
}

const toast = useToast();

const notes = ref<TrashNote[]>([]);
const selectedIds = ref<string[]>([]);
const isLoading = ref(false);
const isRestoring = ref(false);
const isEmptying = ref(false);

const meta = ref({
  currentPage: 1,
  perPage: 20,
  total: 0,
  totalPages: 0,
});

const showDeleteDialog = ref(false);
const showEmptyDialog = ref(false);
const noteToDelete = ref<string | null>(null);

onMounted(() => {
  loadTrash();
});

async function loadTrash(page = 1) {
  isLoading.value = true;
  try {
    const response = await trashApi.getTrash(page, meta.value.perPage);
    notes.value = response.data || [];
    meta.value = response.meta || {
      currentPage: 1,
      perPage: 20,
      total: 0,
      totalPages: 0,
    };
  } catch (error) {
    console.error('Trash load error:', error);
    notes.value = [];
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить корзину',
      life: 3000,
    });
  } finally {
    isLoading.value = false;
  }
}

async function restoreNote(id: string) {
  try {
    await trashApi.restore(id);
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Заметка восстановлена',
      life: 3000,
    });
    await loadTrash(meta.value.currentPage);
    selectedIds.value = selectedIds.value.filter((noteId) => noteId !== id);
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось восстановить заметку',
      life: 3000,
    });
  }
}

async function restoreSelected() {
  if (selectedIds.value.length === 0) return;

  isRestoring.value = true;
  try {
    await Promise.all(selectedIds.value.map((id) => trashApi.restore(id)));
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: `Восстановлено заметок: ${selectedIds.value.length}`,
      life: 3000,
    });
    selectedIds.value = [];
    await loadTrash(meta.value.currentPage);
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось восстановить выбранные заметки',
      life: 3000,
    });
  } finally {
    isRestoring.value = false;
  }
}

function confirmDelete(id: string) {
  noteToDelete.value = id;
  showDeleteDialog.value = true;
}

async function deleteNotePermanent() {
  if (!noteToDelete.value) return;

  try {
    await trashApi.deletePermanent(noteToDelete.value);
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Заметка окончательно удалена',
      life: 3000,
    });
    showDeleteDialog.value = false;
    noteToDelete.value = null;
    await loadTrash(meta.value.currentPage);
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось удалить заметку',
      life: 3000,
    });
  }
}

function confirmEmptyTrash() {
  showEmptyDialog.value = true;
}

async function emptyTrash() {
  isEmptying.value = true;
  try {
    await trashApi.emptyTrash();
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Корзина очищена',
      life: 3000,
    });
    showEmptyDialog.value = false;
    await loadTrash(1);
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось очистить корзину',
      life: 3000,
    });
  } finally {
    isEmptying.value = false;
  }
}

function onPageChange(event: any) {
  loadTrash(event.page + 1);
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays === 0) {
    return 'сегодня';
  } else if (diffDays === 1) {
    return 'вчера';
  } else if (diffDays < 7) {
    return `${diffDays} дн. назад`;
  } else {
    return date.toLocaleDateString('ru-RU');
  }
}
</script>
