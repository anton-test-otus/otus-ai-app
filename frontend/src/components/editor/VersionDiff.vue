<template>
  <div class="version-diff">
    <!-- Mode Toggle (desktop only) -->
    <div v-if="!isMobile" class="flex justify-end mb-4">
      <div class="inline-flex rounded-md shadow-sm">
        <button
          @click="viewMode = 'inline'"
          :class="[
            'px-4 py-2 text-sm font-medium border',
            viewMode === 'inline'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'app-chrome text-surface-700 dark:text-surface-300 border-surface-300 dark:border-surface-600 hover:bg-surface-50 dark:hover:bg-surface-800'
          ]"
          class="rounded-l-md"
        >
          Подсветка
        </button>
        <button
          @click="viewMode = 'unified'"
          :class="[
            'px-4 py-2 text-sm font-medium border-t border-b border-r',
            viewMode === 'unified'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'app-chrome text-surface-700 dark:text-surface-300 border-surface-300 dark:border-surface-600 hover:bg-surface-50 dark:hover:bg-surface-800'
          ]"
          class="rounded-r-md"
        >
          Построчно
        </button>
      </div>
    </div>

    <!-- Diff Content -->
    <div class="diff-container app-chrome rounded-lg border app-border content-padding overflow-auto">
      <!-- Title Diff -->
      <div v-if="titleDiff.length > 0" class="mb-6">
        <h3 class="text-sm font-semibold text-surface-700 dark:text-surface-300 mb-2">Изменения заголовка:</h3>
        <div v-if="viewMode === 'inline'" class="inline-diff">
          <span
            v-for="(part, index) in titleDiff"
            :key="index"
            :class="getDiffClass(part)"
          >{{ part.value }}</span>
        </div>
        <div v-else class="unified-diff">
          <pre
            v-for="(part, index) in titleDiff"
            :key="index"
            :class="getUnifiedDiffClass(part)"
          >{{ getUnifiedPrefix(part) }}{{ part.value }}</pre>
        </div>
      </div>

      <!-- Content Diff -->
      <div>
        <h3 class="text-sm font-semibold text-surface-700 dark:text-surface-300 mb-2">Изменения содержимого:</h3>
        <div v-if="viewMode === 'inline'" class="inline-diff whitespace-pre-wrap font-mono text-sm">
          <span
            v-for="(part, index) in contentDiff"
            :key="index"
            :class="getDiffClass(part)"
          >{{ part.value }}</span>
        </div>
        <div v-else class="unified-diff">
          <pre
            v-for="(part, index) in contentDiff"
            :key="index"
            :class="getUnifiedDiffClass(part)"
            class="font-mono text-sm"
          >{{ getUnifiedPrefix(part) }}{{ part.value }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { diffChars, diffLines, type Change } from 'diff'
import type { DiffViewMode } from '@/types'

interface Props {
  oldTitle: string
  oldContent: string
  newTitle: string
  newContent: string
  defaultMode?: DiffViewMode
}

const props = withDefaults(defineProps<Props>(), {
  defaultMode: 'inline'
})

const isMobile = ref(window.innerWidth < 768)
const viewMode = ref<DiffViewMode>(isMobile.value ? 'inline' : props.defaultMode)

// Compute title diff (character-level for titles)
const titleDiff = computed(() => {
  return diffChars(props.oldTitle, props.newTitle)
})

// Compute content diff (line-level for content in unified, character-level for inline)
const contentDiff = computed(() => {
  if (viewMode.value === 'unified') {
    return diffLines(props.oldContent, props.newContent)
  }
  return diffChars(props.oldContent, props.newContent)
})

// Get CSS class for inline diff
const getDiffClass = (part: Change): string => {
  if (part.added) {
    return 'bg-green-100 text-green-800'
  }
  if (part.removed) {
    return 'bg-red-100 text-red-800 line-through'
  }
  return 'text-gray-900'
}

// Get CSS class for unified diff
const getUnifiedDiffClass = (part: Change): string => {
  if (part.added) {
    return 'bg-green-50 text-green-900 border-l-4 border-green-500 pl-2'
  }
  if (part.removed) {
    return 'bg-red-50 text-red-900 border-l-4 border-red-500 pl-2'
  }
  return 'text-gray-700 pl-2'
}

// Get prefix for unified diff (like Git)
const getUnifiedPrefix = (part: Change): string => {
  if (part.added) return '+ '
  if (part.removed) return '- '
  return '  '
}

// Handle window resize
if (typeof window !== 'undefined') {
  window.addEventListener('resize', () => {
    const wasMobile = isMobile.value
    isMobile.value = window.innerWidth < 768
    
    // Reset to inline mode on mobile
    if (!wasMobile && isMobile.value) {
      viewMode.value = 'inline'
    }
  })
}
</script>

<style scoped>
.diff-container {
  max-height: 600px;
}

.inline-diff {
  line-height: 1.8;
}

.unified-diff pre {
  margin: 0;
  padding: 0.25rem 0;
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
