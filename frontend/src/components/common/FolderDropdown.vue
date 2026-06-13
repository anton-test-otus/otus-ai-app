<template>
  <Dropdown
    :id="inputId"
    v-model="modelValue"
    :options="folderOptions"
    option-label="label"
    option-value="value"
    :placeholder="placeholder"
    show-clear
    class="w-full"
    append-to="body"
  >
    <template #value="slotProps">
      <div
        v-if="slotProps.value"
        class="flex items-center gap-2 min-w-0"
        :style="getFolderIndentStyle(slotProps.value)"
      >
        <i
          class="text-primary-500 text-sm shrink-0"
          :class="getFolderIconClass(slotProps.value)"
        />
        <span class="truncate">{{ getFolderLabel(slotProps.value) }}</span>
      </div>
      <span v-else class="text-surface-400">{{ slotProps.placeholder }}</span>
    </template>
    <template #option="slotProps">
      <div
        class="flex items-center gap-2 min-w-0"
        :style="{ paddingLeft: `${slotProps.option.depth * 16}px` }"
      >
        <i
          class="text-primary-500 text-sm shrink-0"
          :class="slotProps.option.iconClass"
        />
        <span class="truncate">{{ slotProps.option.label }}</span>
      </div>
    </template>
  </Dropdown>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Dropdown from 'primevue/dropdown'
import { useFolderDropdownOptions } from '@/composables/useFolderDropdownOptions'
import { useFoldersStore } from '@/stores/folders'

const props = withDefaults(defineProps<{
  inputId?: string
  placeholder?: string
  onlySelectableParents?: boolean
}>(), {
  inputId: 'folder-dropdown',
  placeholder: 'Выберите папку (опционально)',
  onlySelectableParents: false,
})

const modelValue = defineModel<string | null>()

const foldersStore = useFoldersStore()

const folders = computed(() => foldersStore.folders)

const {
  folderOptions,
  getFolderLabel,
  getFolderIconClass,
  getFolderIndentStyle,
} = useFolderDropdownOptions(folders, {
  onlySelectableParents: props.onlySelectableParents,
})
</script>
