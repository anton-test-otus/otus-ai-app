<template>
  <div class="markdown-editor h-full flex flex-col">
    <!-- Панель форматирования -->
    <div class="toolbar border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-2 flex flex-wrap gap-1">
      <button
        v-for="tool in tools"
        :key="tool.name"
        @click="applyFormat(tool.command)"
        :title="tool.title"
        class="toolbar-button p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
        type="button"
      >
        <i :class="tool.icon"></i>
      </button>
    </div>
    
    <!-- Редактор -->
    <div ref="editorRef" class="milkdown-editor flex-1 overflow-auto"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { Editor, editorViewCtx, rootCtx } from '@milkdown/core'
import { commonmark } from '@milkdown/preset-commonmark'
import { gfm } from '@milkdown/preset-gfm'
import { history } from '@milkdown/plugin-history'
import { listener, listenerCtx } from '@milkdown/plugin-listener'
import { clipboard } from '@milkdown/plugin-clipboard'
import { replaceAll } from '@milkdown/utils'

interface Props {
  modelValue: string
  readonly?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: string): void
  (e: 'insertWikiLink'): void
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false,
})

const emit = defineEmits<Emits>()

const editorRef = ref<HTMLElement>()
let editor: Editor | null = null
let isUpdating = false

const tools = [
  { name: 'bold', icon: 'pi pi-bold', title: 'Жирный (Ctrl+B)', command: 'bold' },
  { name: 'italic', icon: 'pi pi-italic', title: 'Курсив (Ctrl+I)', command: 'italic' },
  { name: 'underline', icon: 'pi pi-underline', title: 'Подчеркнутый', command: 'underline' },
  { name: 'code', icon: 'pi pi-code', title: 'Код', command: 'code' },
  { name: 'link', icon: 'pi pi-link', title: 'Ссылка', command: 'link' },
  { name: 'wiki-link', icon: 'pi pi-file', title: 'Ссылка на заметку', command: 'wiki_link' },
  { name: 'list', icon: 'pi pi-list', title: 'Список', command: 'bullet_list' },
  { name: 'ordered-list', icon: 'pi pi-sort-numeric-down', title: 'Нумерованный список', command: 'ordered_list' },
  { name: 'quote', icon: 'pi pi-comments', title: 'Цитата', command: 'blockquote' },
  { name: 'heading', icon: 'pi pi-heading', title: 'Заголовок', command: 'heading' },
]

onMounted(async () => {
  if (!editorRef.value) return

  try {
    editor = await Editor.make()
      .config((ctx) => {
        ctx.set(rootCtx, editorRef.value!)
        
        ctx.get(listenerCtx).markdownUpdated((_ctx, markdown) => {
          if (!isUpdating) {
            emit('update:modelValue', markdown)
          }
        })
      })
      .use(commonmark)
      .use(gfm)
      .use(history)
      .use(listener)
      .use(clipboard)
      .create()

    // Устанавливаем начальное значение
    await nextTick()
    if (props.modelValue && editor) {
      isUpdating = true
      editor.action(replaceAll(props.modelValue))
      isUpdating = false
    }
  } catch (error) {
    console.error('Failed to create Milkdown editor:', error)
  }
})

watch(
  () => props.modelValue,
  (newValue) => {
    if (editor && !isUpdating) {
      try {
        isUpdating = true
        editor.action(replaceAll(newValue))
      } catch (error) {
        console.error('Failed to update editor content:', error)
      } finally {
        setTimeout(() => {
          isUpdating = false
        }, 100)
      }
    }
  }
)

const applyFormat = (command: string) => {
  if (!editor) return

  try {
    switch (command) {
      case 'bold':
        wrapSelection('**', '**')
        break
      case 'italic':
        wrapSelection('*', '*')
        break
      case 'underline':
        wrapSelection('__', '__')
        break
      case 'code':
        wrapSelection('`', '`')
        break
      case 'link':
        insertLink()
        break
      case 'wiki_link':
        emit('insertWikiLink')
        break
      case 'bullet_list':
        insertAtLineStart('- ')
        break
      case 'ordered_list':
        insertAtLineStart('1. ')
        break
      case 'blockquote':
        insertAtLineStart('> ')
        break
      case 'heading':
        insertAtLineStart('## ')
        break
    }
  } catch (error) {
    console.error('Failed to apply format:', error)
  }
}

const wrapSelection = (prefix: string, suffix: string) => {
  if (!editor) return

  try {
    const view = editor.action((ctx) => ctx.get(editorViewCtx))
    const { state, dispatch } = view
    const { from, to } = state.selection
    const selectedText = state.doc.textBetween(from, to)

    const tr = state.tr.replaceWith(
      from,
      to,
      state.schema.text(prefix + selectedText + suffix)
    )
    dispatch(tr)
    
    // Устанавливаем курсор после вставки
    const newPos = from + prefix.length + selectedText.length + suffix.length
    setTimeout(() => {
      const newView = editor!.action((ctx) => ctx.get(editorViewCtx))
      const tr = newView.state.tr.setSelection(
        // @ts-ignore - ProseMirror types
        newView.state.selection.constructor.near(newView.state.doc.resolve(newPos))
      )
      newView.dispatch(tr)
      newView.focus()
    }, 0)
  } catch (error) {
    console.error('Failed to wrap selection:', error)
  }
}

const insertAtLineStart = (prefix: string) => {
  if (!editor) return

  try {
    const view = editor.action((ctx) => ctx.get(editorViewCtx))
    const { state, dispatch } = view
    const { from } = state.selection
    
    // Находим начало строки
    const $from = state.doc.resolve(from)
    const lineStart = $from.start()

    const tr = state.tr.insertText(prefix, lineStart)
    dispatch(tr)
    
    setTimeout(() => {
      const newView = editor!.action((ctx) => ctx.get(editorViewCtx))
      newView.focus()
    }, 0)
  } catch (error) {
    console.error('Failed to insert at line start:', error)
  }
}

const insertLink = () => {
  if (!editor) return

  const url = prompt('Введите URL:')
  if (!url) return

  try {
    const view = editor.action((ctx) => ctx.get(editorViewCtx))
    const { state, dispatch } = view
    const { from, to } = state.selection
    const selectedText = state.doc.textBetween(from, to) || 'ссылка'

    const linkText = `[${selectedText}](${url})`
    const tr = state.tr.replaceWith(from, to, state.schema.text(linkText))
    dispatch(tr)
    
    setTimeout(() => {
      const newView = editor!.action((ctx) => ctx.get(editorViewCtx))
      newView.focus()
    }, 0)
  } catch (error) {
    console.error('Failed to insert link:', error)
  }
}

onBeforeUnmount(() => {
  editor?.destroy()
})
</script>

<style>
.markdown-editor {
  @apply bg-white dark:bg-gray-800;
}

.toolbar-button {
  @apply flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300;
}

.toolbar-button:hover {
  @apply bg-gray-200 dark:bg-gray-700;
}

.milkdown-editor {
  @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-4;
}

/* Milkdown стили */
:deep(.milkdown) {
  @apply h-full;
}

:deep(.milkdown .editor) {
  @apply min-h-full outline-none;
}

:deep(.milkdown .ProseMirror) {
  @apply outline-none min-h-full;
}

:deep(.milkdown .ProseMirror p) {
  @apply mb-4;
}

:deep(.milkdown .ProseMirror h1) {
  @apply text-3xl font-bold mb-4 mt-6;
}

:deep(.milkdown .ProseMirror h2) {
  @apply text-2xl font-bold mb-3 mt-5;
}

:deep(.milkdown .ProseMirror h3) {
  @apply text-xl font-bold mb-2 mt-4;
}

:deep(.milkdown .ProseMirror ul) {
  @apply list-disc list-inside mb-4;
}

:deep(.milkdown .ProseMirror ol) {
  @apply list-decimal list-inside mb-4;
}

:deep(.milkdown .ProseMirror blockquote) {
  @apply border-l-4 border-gray-300 dark:border-gray-600 pl-4 italic my-4;
}

:deep(.milkdown .ProseMirror code) {
  @apply bg-gray-100 dark:bg-gray-900 px-1 py-0.5 rounded text-sm font-mono;
}

:deep(.milkdown .ProseMirror pre) {
  @apply bg-gray-100 dark:bg-gray-900 p-4 rounded my-4 overflow-x-auto;
}

:deep(.milkdown .ProseMirror pre code) {
  @apply bg-transparent p-0;
}

:deep(.milkdown .ProseMirror a) {
  @apply text-blue-600 dark:text-blue-400 underline;
}
</style>
