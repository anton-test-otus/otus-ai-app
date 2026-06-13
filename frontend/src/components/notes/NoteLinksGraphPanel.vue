<template>
  <div class="note-links-graph-panel">
    <h4 class="text-sm font-semibold mb-2">Связи</h4>

    <Button
      type="button"
      label="Связанные заметки"
      icon="pi pi-share-alt"
      severity="secondary"
      outlined
      class="w-full"
      @click="dialogVisible = true"
    />

    <NoteLinksGraphDialog
      v-model:visible="dialogVisible"
      :note-id="noteId"
      :focus-note-id="noteId"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import NoteLinksGraphDialog from '@/components/notes/NoteLinksGraphDialog.vue'

defineProps<{
  noteId: string
}>()

const dialogVisible = ref(false)
</script>
