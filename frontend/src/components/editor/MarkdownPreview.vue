<template>
  <div class="markdown-preview prose dark:prose-invert max-w-none p-4 overflow-auto h-full">
    <div v-html="renderedContent"></div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { marked } from 'marked'

interface Props {
  content: string
}

const props = defineProps<Props>()

const renderedContent = computed(() => {
  try {
    return marked.parse(props.content)
  } catch (error) {
    console.error('Markdown parsing error:', error)
    return '<p>Ошибка отображения содержимого</p>'
  }
})
</script>

<style>
.markdown-preview {
  @apply bg-white dark:bg-gray-800;
}
</style>
