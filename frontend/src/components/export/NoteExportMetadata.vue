<template>
  <header class="note-export-metadata">
    <h1 class="note-export-metadata__title">{{ metadata.title }}</h1>
    <dl class="note-export-metadata__list">
      <div v-if="metadata.folderPath" class="note-export-metadata__row">
        <dt>Папка</dt>
        <dd>{{ metadata.folderPath }}</dd>
      </div>
      <div v-if="metadata.tags.length" class="note-export-metadata__row">
        <dt>Теги</dt>
        <dd>{{ metadata.tags.join(', ') }}</dd>
      </div>
      <div class="note-export-metadata__row">
        <dt>Создано</dt>
        <dd>{{ formatDateTime(metadata.createdAt) }}</dd>
      </div>
      <div class="note-export-metadata__row">
        <dt>Обновлено</dt>
        <dd>{{ formatDateTime(metadata.updatedAt) }}</dd>
      </div>
    </dl>
  </header>
</template>

<script setup lang="ts">
import { formatDateTime } from '@/utils/date'
import type { NoteExportMetadata } from '@/utils/exportMetadata'

defineProps<{
  metadata: NoteExportMetadata
}>()
</script>

<style scoped>
.note-export-metadata__title {
  @apply text-3xl font-bold text-gray-900 mb-4;
}

.note-export-metadata__list {
  @apply grid gap-2 text-sm text-gray-600;
}

.note-export-metadata__row {
  @apply grid gap-1 sm:grid-cols-[7rem_1fr];
}

.note-export-metadata__row dt {
  @apply font-medium text-gray-700;
}

.note-export-metadata__row dd {
  @apply m-0;
}
</style>
