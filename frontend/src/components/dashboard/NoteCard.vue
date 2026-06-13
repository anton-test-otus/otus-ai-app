<template>
  <div
    class="note-card-shell h-full"
    :class="{ 'note-card-dragging': isDragging }"
  >
    <NoteDragHandle
      :note-id="note.id"
      :note-title="note.title"
      @dragging="isDragging = $event"
    />
    <Card
      class="note-card h-full cursor-pointer hover:shadow-lg transition-shadow"
      @click="handleOpen"
    >
      <template #content>
        <div class="note-card-body">
          <h3 class="card-title">{{ note.title }}</h3>
          <div class="card-meta flex items-center justify-between gap-2 mt-1 mb-2">
            <span class="shrink-0">{{ formattedDate }}</span>
            <span
              v-if="showFolder && note.folder"
              class="flex items-center gap-1 min-w-0 max-w-[55%]"
              v-tooltip.top="note.folder.name"
            >
              <i class="shrink-0 text-xs" :class="folderIconClass" />
              <span class="truncate">{{ note.folder.name }}</span>
            </span>
          </div>
          <NoteTagsPreview
            v-if="note.tags?.length"
            :tags="note.tags"
            class="mb-2"
          />
          <div class="card-preview note-card-preview">
            {{ preview }}
          </div>
        </div>
      </template>
      <template #footer>
        <div class="note-card-actions">
          <Button
            :icon="note.isFavorite ? 'pi pi-star-fill' : 'pi pi-star'"
            :severity="note.isFavorite ? 'warn' : 'secondary'"
            text
            rounded
            @click.stop="emit('toggle-favorite', note)"
            v-tooltip.bottom="note.isFavorite ? 'Убрать из избранного' : 'В избранное'"
          />
          <Button
            v-if="!canHover"
            icon="pi pi-folder"
            text
            rounded
            @click.stop="openMoveDialog(note)"
            v-tooltip.bottom="'Переместить в папку'"
          />
          <Button
            icon="pi pi-pencil"
            text
            rounded
            @click.stop="emit('edit', note.id)"
            v-tooltip.bottom="'Редактировать'"
          />
          <Button
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            @click.stop="emit('delete', note)"
            v-tooltip.bottom="'Удалить'"
          />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import NoteTagsPreview from '@/components/common/NoteTagsPreview.vue'
import NoteDragHandle from '@/components/notes/NoteDragHandle.vue'
import { useCanHover } from '@/composables/useCanHover'
import { useMoveNoteToFolder } from '@/composables/useMoveNoteToFolder'
import { useNoteDragSource } from '@/composables/useNoteFolderDnD'
import { useFoldersStore } from '@/stores/folders'
import { findFolderDepthInTree } from '@/utils/folderPath'
import { resolveFolderTreeIcon } from '@/utils/folderIcon'
import type { NoteListItem } from '@/types'

const props = defineProps<{
  note: NoteListItem
  showFolder?: boolean
  formatDate: (dateString: string) => string
}>()

const foldersStore = useFoldersStore()
const { canHover } = useCanHover()
const { openMoveDialog } = useMoveNoteToFolder()
const { shouldIgnoreClick } = useNoteDragSource(
  props.note.id,
  props.note.title,
)

const emit = defineEmits<{
  open: [id: string]
  edit: [id: string]
  delete: [note: NoteListItem]
  'toggle-favorite': [note: NoteListItem]
}>()

const isDragging = ref(false)

function handleOpen() {
  if (shouldIgnoreClick()) {
    return
  }
  emit('open', props.note.id)
}

const preview = computed(() => props.note.contentPreview)
const formattedDate = computed(() => props.formatDate(props.note.updatedAt))

const folderIconClass = computed(() => {
  const folderId = props.note.folderId ?? props.note.folder?.id
  if (!folderId) {
    return resolveFolderTreeIcon(null, 0)
  }

  const treeFolder = foldersStore.getFolderById(folderId)
  const icon = props.note.folder?.icon ?? treeFolder?.icon ?? null
  const depth = findFolderDepthInTree(foldersStore.folders, folderId) ?? 0

  return resolveFolderTreeIcon(icon, depth)
})

onMounted(async () => {
  if (!foldersStore.folders.length) {
    await foldersStore.fetchFolders()
  }
})
</script>
