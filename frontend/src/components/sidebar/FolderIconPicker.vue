<template>
  <div class="folder-icon-picker">
    <label v-if="showLabel" class="font-semibold text-sm">Иконка</label>
    <div
      class="folder-icon-picker-grid grid grid-cols-6 sm:grid-cols-8 gap-1.5 max-h-48 overflow-y-auto p-1"
      role="listbox"
      :aria-label="showLabel ? 'Иконка папки' : undefined"
    >
      <button
        type="button"
        class="folder-icon-picker-btn flex items-center justify-center rounded-md border transition-colors"
        :class="modelValue === null
          ? 'border-primary-500 bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300'
          : 'border-dashed border-surface-300 hover:border-surface-400 hover:bg-surface-100 dark:border-surface-600 dark:hover:bg-surface-800'"
        title="По умолчанию"
        aria-label="По умолчанию"
        :aria-selected="modelValue === null"
        role="option"
        @click="$emit('update:modelValue', null)"
      >
        <i :class="defaultIconClass" class="text-base" />
      </button>

      <button
        v-for="option in FOLDER_ICON_OPTIONS"
        :key="option.value"
        type="button"
        class="folder-icon-picker-btn flex items-center justify-center rounded-md border transition-colors"
        :class="option.value === modelValue
          ? 'border-primary-500 bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300'
          : 'border-transparent hover:border-surface-300 hover:bg-surface-100 dark:hover:border-surface-600 dark:hover:bg-surface-800'"
        :title="option.label"
        :aria-label="option.label"
        :aria-selected="option.value === modelValue"
        role="option"
        @click="$emit('update:modelValue', option.value)"
      >
        <i :class="toPrimeIconClass(option.value)" class="text-base" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { FOLDER_ICON_OPTIONS } from '@/constants/folderIcons'
import { getDefaultFolderIconByDepth, toPrimeIconClass } from '@/utils/folderIcon'

const props = withDefaults(defineProps<{
  modelValue: string | null
  showLabel?: boolean
  depth?: number
}>(), {
  showLabel: true,
  depth: 0,
})

defineEmits<{
  'update:modelValue': [value: string | null]
}>()

const defaultIconClass = computed(() => getDefaultFolderIconByDepth(props.depth))
</script>

<style scoped>
.folder-icon-picker {
  @apply flex flex-col gap-2;
}

.folder-icon-picker-btn {
  @apply h-9 w-9 min-h-9 min-w-9;
}
</style>
