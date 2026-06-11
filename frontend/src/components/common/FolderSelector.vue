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
    <Dropdown
      :id="inputId"
      :model-value="modelValue"
      @update:model-value="$emit('update:modelValue', $event)"
      :options="folderOptions"
      option-label="label"
      option-value="value"
      placeholder="Выберите папку (опционально)"
      show-clear
      class="w-full"
      append-to="body"
    >
      <template #value="slotProps">
        <div v-if="slotProps.value" class="flex items-center gap-2">
          <i class="pi pi-folder text-primary-500 text-sm" />
          <span>{{ getFolderLabel(slotProps.value) }}</span>
        </div>
        <span v-else class="text-surface-400">{{ slotProps.placeholder }}</span>
      </template>
      <template #option="slotProps">
        <div class="flex items-center gap-2">
          <i class="pi pi-folder text-primary-500 text-sm" />
          <span>{{ slotProps.option.label }}</span>
        </div>
      </template>
    </Dropdown>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import Dropdown from 'primevue/dropdown';
import ProgressSpinner from 'primevue/progressspinner';
import { useFoldersStore } from '../../stores/folders';
import type { Folder } from '../../types';

interface Props {
  modelValue?: string | null;
  inputId?: string;
}

withDefaults(defineProps<Props>(), {
  inputId: 'folder-select',
});

defineEmits<{
  'update:modelValue': [value: string | null];
}>();

const foldersStore = useFoldersStore();

const loading = computed(() => foldersStore.loading);

const folderOptions = computed(() => {
  const options: { label: string; value: string }[] = [];
  
  const flatten = (items: Folder[], depth = 0) => {
    if (!items || !Array.isArray(items)) return;
    items.forEach(item => {
      const indent = '\u00A0\u00A0'.repeat(depth);
      options.push({
        label: indent + item.name,
        value: item.id,
      });
      if (item.children && item.children.length > 0) {
        flatten(item.children, depth + 1);
      }
    });
  };
  
  if (foldersStore.folders && foldersStore.folders.length > 0) {
    flatten(foldersStore.folders);
  }
  
  return options;
});

function getFolderLabel(folderId: string): string {
  const option = folderOptions.value.find(opt => opt.value === folderId);
  return option?.label.trim() || 'Неизвестная папка';
}

onMounted(async () => {
  if (!foldersStore.folders || foldersStore.folders.length === 0) {
    await foldersStore.fetchFolders();
  }
});
</script>
