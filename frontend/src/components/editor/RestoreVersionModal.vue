<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="handleCancel"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div
            class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6"
            @click.stop
          >
            <!-- Close Button -->
            <button
              @click="handleCancel"
              class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <!-- Header -->
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                Restore Version
              </h3>
              <p class="mt-1 text-sm text-gray-600">
                Choose how you want to restore this version
              </p>
            </div>

            <!-- Version Info -->
            <div class="mb-6 p-3 bg-gray-50 rounded-md">
              <p class="text-sm text-gray-700">
                <span class="font-medium">Version from:</span>
                {{ formatDate(versionDate) }}
              </p>
            </div>

            <!-- Options -->
            <div class="space-y-3 mb-6">
              <label
                v-for="option in options"
                :key="option.value"
                class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                :class="selectedMode === option.value ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
              >
                <input
                  type="radio"
                  :value="option.value"
                  v-model="selectedMode"
                  class="mt-1 mr-3 text-blue-600 focus:ring-blue-500"
                />
                <div class="flex-1">
                  <div class="font-medium text-gray-900">{{ option.label }}</div>
                  <div class="text-sm text-gray-600 mt-1">{{ option.description }}</div>
                </div>
              </label>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
              <button
                @click="handleCancel"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Cancel
              </button>
              <button
                @click="handleConfirm"
                :disabled="!selectedMode"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Restore
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { RestoreVersionRequest } from '@/types'

interface Props {
  modelValue: boolean
  versionDate: string
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm', mode: RestoreVersionRequest['mode']): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const selectedMode = ref<RestoreVersionRequest['mode']>('create_version')

const options = [
  {
    value: 'create_version' as const,
    label: 'Create backup & restore',
    description: 'Save the current version as a backup before restoring this one (recommended)'
  },
  {
    value: 'overwrite' as const,
    label: 'Overwrite current',
    description: 'Replace the current content without creating a backup'
  },
  {
    value: 'copy' as const,
    label: 'Create new note',
    description: 'Create a new note with this version content (original note unchanged)'
  }
]

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const handleCancel = () => {
  emit('update:modelValue', false)
}

const handleConfirm = () => {
  if (selectedMode.value) {
    emit('confirm', selectedMode.value)
    emit('update:modelValue', false)
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
}
</style>
