<template>
  <div class="backlinks-panel">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
      Обратные ссылки
    </h3>
    
    <div v-if="loading" class="text-sm text-gray-500">
      Загрузка...
    </div>
    
    <div v-else-if="backlinks.length === 0" class="text-sm text-gray-500">
      Нет ссылок на эту заметку
    </div>
    
    <ul v-else class="space-y-1">
      <li v-for="backlink in backlinks" :key="backlink.id">
        <router-link
          :to="`/note/${backlink.id}`"
          class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:underline block"
        >
          {{ backlink.title }}
        </router-link>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { wikiLinksApi, type BacklinkNote } from '@/api/wikilinks';

const props = defineProps<{
  noteId: string;
}>();

const backlinks = ref<BacklinkNote[]>([]);
const loading = ref(false);

const loadBacklinks = async () => {
  if (!props.noteId) return;
  
  loading.value = true;
  try {
    backlinks.value = await wikiLinksApi.getBacklinks(props.noteId);
  } catch (error) {
    console.error('Failed to load backlinks:', error);
  } finally {
    loading.value = false;
  }
};

watch(() => props.noteId, loadBacklinks, { immediate: true });
</script>
