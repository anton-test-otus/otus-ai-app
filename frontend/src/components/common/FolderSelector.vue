<template>
  <div class="folder-selector">
    <label :for="inputId" class="block text-sm font-semibold mb-2">
      <span class="inline-flex items-center gap-2">
        Папка
        <ProgressSpinner
          v-if="loading"
          style="width: 1rem; height: 1rem"
          stroke-width="4"
        />
      </span>
    </label>
    <FolderDropdown
      v-model="modelValue"
      :input-id="inputId"
      :placeholder="placeholder"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import ProgressSpinner from 'primevue/progressspinner';
import FolderDropdown from '@/components/common/FolderDropdown.vue';
import { useFoldersStore } from '../../stores/folders';

interface Props {
  inputId?: string;
  placeholder?: string;
}

withDefaults(defineProps<Props>(), {
  inputId: 'folder-select',
  placeholder: 'Выберите папку (опционально)',
});

const modelValue = defineModel<string | null>();

const foldersStore = useFoldersStore();

const loading = computed(() => foldersStore.loading);

onMounted(async () => {
  if (!foldersStore.folders || foldersStore.folders.length === 0) {
    await foldersStore.fetchFolders();
  }
});
</script>
