<template>
  <div class="markdown-editor h-full">
    <div ref="editorRef" class="milkdown-editor h-full"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Editor, rootCtx, defaultValueCtx } from '@milkdown/core'
import { commonmark } from '@milkdown/preset-commonmark'
import { gfm } from '@milkdown/preset-gfm'
import { history } from '@milkdown/plugin-history'
import { listener, listenerCtx } from '@milkdown/plugin-listener'
import { nord } from '@milkdown/theme-nord'

interface Props {
  modelValue: string
  readonly?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false,
})

const emit = defineEmits<Emits>()

const editorRef = ref<HTMLElement>()
let editor: Editor | null = null

onMounted(async () => {
  if (!editorRef.value) return

  try {
    editor = await Editor.make()
      .config((ctx) => {
        ctx.set(rootCtx, editorRef.value!)
        ctx.set(defaultValueCtx, props.modelValue)
        
        ctx.get(listenerCtx).markdownUpdated((ctx, markdown) => {
          emit('update:modelValue', markdown)
        })
      })
      .use(nord)
      .use(commonmark)
      .use(gfm)
      .use(history)
      .use(listener)
      .create()
  } catch (error) {
    console.error('Failed to create Milkdown editor:', error)
  }
})

watch(
  () => props.modelValue,
  (newValue) => {
    if (editor && newValue !== editor.action((ctx) => ctx.get(defaultValueCtx))) {
      editor.action((ctx) => {
        const view = ctx.get(rootCtx)
        // Update editor content if different from current
      })
    }
  }
)

onBeforeUnmount(() => {
  editor?.destroy()
})
</script>

<style>
.milkdown-editor {
  @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100;
}

.milkdown {
  @apply h-full p-4;
}

.milkdown .editor {
  @apply min-h-full;
}
</style>
