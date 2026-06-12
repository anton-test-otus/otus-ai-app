<template>
  <Dialog
    v-model:visible="visible"
    modal
    header="Горячие клавиши"
    :style="MODAL_WIDTH.md"
    :dismissable-mask="true"
  >
    <div class="stack-sections">
      <section
        v-for="group in KEYBOARD_SHORTCUT_GROUPS"
        :key="group.title"
      >
        <h3 class="text-sm font-semibold text-surface-900 dark:text-surface-100 mb-3">
          {{ group.title }}
        </h3>
        <ul class="space-y-2">
          <li
            v-for="shortcut in group.shortcuts"
            :key="shortcut.id"
            class="flex items-center justify-between gap-4 list-row-padding rounded-lg bg-surface-50 dark:bg-surface-800/60"
          >
            <span class="text-sm text-surface-700 dark:text-surface-300">
              {{ shortcut.description }}
            </span>
            <kbd class="shortcut-kbd shrink-0">
              {{ formatShortcutKeys(shortcut.keys) }}
            </kbd>
          </li>
        </ul>
      </section>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import Dialog from 'primevue/dialog'
import { MODAL_WIDTH } from '@/constants/modal'
import {
  KEYBOARD_SHORTCUT_GROUPS,
  formatShortcutKeys,
} from '@/constants/keyboardShortcuts'
import { useKeyboardShortcutsHelp } from '@/composables/useKeyboardShortcuts'

const { shortcutsHelpVisible: visible } = useKeyboardShortcutsHelp()
</script>

<style scoped>
.shortcut-kbd {
  @apply inline-flex items-center rounded border app-border bg-surface-0 dark:bg-surface-900 px-2 py-1 text-xs font-medium text-surface-600 dark:text-surface-300;
}
</style>
