<template>
  <Dialog
    v-model:visible="visible"
    modal
    header="Восстановление версии"
    :style="MODAL_WIDTH.md"
    @hide="handleCancel"
  >
    <p class="text-sm text-muted mb-4">
      Выберите способ восстановления этой версии
    </p>

    <div class="mb-4 p-3 bg-surface-50 dark:bg-surface-800 rounded-md">
      <p class="text-sm text-surface-700 dark:text-surface-300">
        <span class="font-medium">Версия от:</span>
        {{ formatDateTime(versionDate) }}
      </p>
    </div>

    <div class="stack-items mb-4">
      <label
        v-for="option in options"
        :key="option.value"
        class="flex items-start p-3 border app-border rounded-lg cursor-pointer hover:bg-surface-50 dark:hover:bg-surface-800 transition-colors"
        :class="selectedMode === option.value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : ''"
      >
        <RadioButton
          v-model="selectedMode"
          :input-id="option.value"
          :value="option.value"
          class="mt-1 mr-3"
        />
        <div class="flex-1">
          <label :for="option.value" class="font-medium text-surface-900 dark:text-surface-100 cursor-pointer">
            {{ option.label }}
          </label>
          <p class="text-sm text-muted mt-1">{{ option.description }}</p>
        </div>
      </label>
    </div>

    <template #footer>
      <Button label="Отмена" severity="secondary" text @click="handleCancel" />
      <Button label="Восстановить" :disabled="!selectedMode" @click="handleConfirm" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import RadioButton from 'primevue/radiobutton'
import { MODAL_WIDTH } from '@/constants/modal'
import { formatDateTime } from '@/utils/date'
import type { RestoreVersionRequest } from '@/types'

const visible = defineModel<boolean>({ required: true })

defineProps<{
  versionDate: string
}>()

const emit = defineEmits<{
  confirm: [mode: RestoreVersionRequest['mode']]
}>()

const selectedMode = ref<RestoreVersionRequest['mode']>('create_version')

const options = [
  {
    value: 'create_version' as const,
    label: 'Сохранить текущую и восстановить',
    description: 'Текущая версия сохранится как резервная копия перед восстановлением (рекомендуется)',
  },
  {
    value: 'overwrite' as const,
    label: 'Заменить текущую версию',
    description: 'Заменить содержимое без создания резервной копии',
  },
  {
    value: 'copy' as const,
    label: 'Создать новую заметку',
    description: 'Создать новую заметку с содержимым этой версии (исходная заметка не изменится)',
  },
]

function handleCancel() {
  visible.value = false
}

function handleConfirm() {
  if (selectedMode.value) {
    emit('confirm', selectedMode.value)
    visible.value = false
  }
}
</script>
