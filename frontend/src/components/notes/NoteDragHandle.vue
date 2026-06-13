<template>
  <div
    v-if="canHover"
    class="note-drag-grip"
    draggable="true"
    role="button"
    tabindex="-1"
    aria-label="Перетащить в папку"
    v-tooltip.bottom="'Перетащить в папку'"
    @mousedown.stop
    @click.stop
    @dragstart="handleDragStart"
    @dragend="handleDragEnd"
  >
    <i class="pi pi-bars text-xs" aria-hidden="true" />
  </div>
</template>

<script setup lang="ts">
import { useCanHover } from '@/composables/useCanHover'
import { useNoteDragSource } from '@/composables/useNoteFolderDnD'

const props = defineProps<{
  noteId: string
  noteTitle: string
}>()

const emit = defineEmits<{
  dragging: [value: boolean]
}>()

const { canHover } = useCanHover()
const { onDragStart, onDragEnd } = useNoteDragSource(props.noteId, props.noteTitle)

function handleDragStart(event: DragEvent) {
  emit('dragging', true)
  onDragStart(event)
}

function handleDragEnd() {
  emit('dragging', false)
  onDragEnd()
}
</script>
