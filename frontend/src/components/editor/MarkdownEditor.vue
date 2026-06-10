<template>
  <div class="markdown-editor h-full flex flex-col">
    <!-- Панель форматирования -->
    <div class="toolbar border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-2 flex flex-wrap gap-1">
      <button
        v-for="tool in tools"
        :key="tool.name"
        v-tooltip.bottom="tool.title"
        @mousedown.prevent
        @click="applyFormat(tool.command)"
        :class="[
          'toolbar-button p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors',
          { 'toolbar-button--active': isToolbarCommand(tool.command) && activeCommands.has(tool.command) },
        ]"
        type="button"
      >
        <span
          v-if="tool.label"
          class="toolbar-label"
          :class="tool.labelClass"
        >{{ tool.label }}</span>
        <i v-else :class="tool.icon"></i>
      </button>
    </div>
    
    <!-- Редактор -->
    <div ref="editorRef" class="milkdown-editor flex-1 overflow-auto"></div>

    <Dialog
      v-model:visible="showLinkDialog"
      modal
      header="Вставить ссылку"
      :style="{ width: '90vw', maxWidth: '480px' }"
      @hide="resetLinkDialog"
    >
      <div class="space-y-3">
        <label for="link-url-input" class="text-sm font-medium">URL:</label>
        <InputText
          id="link-url-input"
          v-model="linkUrl"
          placeholder="https://example.com"
          class="w-full"
          autofocus
          @keyup.enter="confirmLink"
        />
      </div>
      <template #footer>
        <Button label="Отмена" severity="secondary" text @click="showLinkDialog = false" />
        <Button label="Вставить" :disabled="!linkUrl.trim()" @click="confirmLink" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { Editor, editorViewCtx, rootCtx, commandsCtx } from '@milkdown/core'
import type { CmdKey } from '@milkdown/core'
import { commonmark } from '@milkdown/preset-commonmark'
import {
  toggleStrongCommand,
  toggleEmphasisCommand,
  toggleInlineCodeCommand,
  toggleLinkCommand,
  wrapInBulletListCommand,
  wrapInOrderedListCommand,
  wrapInBlockquoteCommand,
  wrapInHeadingCommand,
} from '@milkdown/preset-commonmark'
import { gfm } from '@milkdown/preset-gfm'
import { history } from '@milkdown/plugin-history'
import { listener, listenerCtx } from '@milkdown/plugin-listener'
import { clipboard } from '@milkdown/plugin-clipboard'
import { replaceAll, getMarkdown } from '@milkdown/utils'
import { TextSelection } from '@milkdown/prose/state'
import { formatWikiLink } from '@/lib/wikiLinks'
import { wikiLinkDecorationPlugin } from './wikiLinkDecorationPlugin'
import { toolbarStatePlugin, registerToolbarStateCallback } from './toolbarStatePlugin'
import type { ToolbarCommand } from './toolbarActiveState'

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
const showLinkDialog = ref(false)
const linkUrl = ref('')
const activeCommands = ref(new Set<ToolbarCommand>())
let editor: Editor | null = null
let unregisterToolbarState: (() => void) | null = null
let isUpdating = false
let lastEmittedMarkdown: string | null = null
let savedSelection: { from: number; to: number } | null = null
let savedWikiSelection: { from: number; to: number } | null = null

function getCurrentMarkdown(): string {
  if (!editor) return ''
  try {
    return editor.action(getMarkdown())
  } catch {
    return ''
  }
}

function syncExternalContent(markdown: string) {
  if (!editor) return

  isUpdating = true
  lastEmittedMarkdown = markdown
  editor.action(replaceAll(markdown))
  nextTick(() => {
    isUpdating = false
  })
}

function isToolbarCommand(command: ToolbarCommand | 'wiki_link'): command is ToolbarCommand {
  return command !== 'wiki_link'
}

const tools: Array<{
  name: string
  title: string
  command: ToolbarCommand | 'wiki_link'
  label?: string
  labelClass?: string
  icon?: string
}> = [
  { name: 'heading', label: 'H', labelClass: 'font-semibold', title: 'Заголовок', command: 'heading' },
  { name: 'bold', label: 'B', labelClass: 'font-bold', title: 'Жирный (Ctrl+B)', command: 'bold' },
  { name: 'italic', label: 'I', labelClass: 'italic', title: 'Курсив (Ctrl+I)', command: 'italic' },
  { name: 'list', icon: 'pi pi-list', title: 'Маркированный список', command: 'bullet_list' },
  { name: 'ordered-list', icon: 'pi pi-sort-numeric-down', title: 'Нумерованный список', command: 'ordered_list' },
  { name: 'quote', icon: 'pi pi-quote-left', title: 'Цитата', command: 'blockquote' },
  { name: 'code', icon: 'pi pi-code', title: 'Код', command: 'code' },
  { name: 'link', icon: 'pi pi-link', title: 'Ссылка', command: 'link' },
  { name: 'wiki-link', icon: 'pi pi-sitemap', title: 'Ссылка на заметку', command: 'wiki_link' },
]

onMounted(async () => {
  if (!editorRef.value) return

  try {
    unregisterToolbarState = registerToolbarStateCallback((active) => {
      activeCommands.value = new Set(active)
    })

    editor = await Editor.make()
      .config((ctx) => {
        ctx.set(rootCtx, editorRef.value!)
        
        ctx.get(listenerCtx).markdownUpdated((_ctx, markdown) => {
          if (isUpdating) return

          isUpdating = true
          lastEmittedMarkdown = markdown
          emit('update:modelValue', markdown)
          nextTick(() => {
            isUpdating = false
          })
        })
      })
      .use(commonmark)
      .use(gfm)
      .use(history)
      .use(listener)
      .use(clipboard)
      .use(wikiLinkDecorationPlugin)
      .use(toolbarStatePlugin)
      .create()

    // Устанавливаем начальное значение
    await nextTick()
    if (props.modelValue && editor) {
      syncExternalContent(props.modelValue)
    } else {
      lastEmittedMarkdown = props.modelValue
    }

    editor.action((ctx) => ctx.get(editorViewCtx)).focus()
  } catch (error) {
    console.error('Failed to create Milkdown editor:', error)
  }
})

watch(
  () => props.modelValue,
  (newValue) => {
    if (!editor || isUpdating) return
    if (newValue === lastEmittedMarkdown) return
    if (newValue === getCurrentMarkdown()) {
      lastEmittedMarkdown = newValue
      return
    }

    syncExternalContent(newValue)
  }
)

const applyFormat = (command: string) => {
  if (!editor) return

  try {
    switch (command) {
      case 'bold':
        runMilkdownCommand(toggleStrongCommand.key)
        break
      case 'italic':
        runMilkdownCommand(toggleEmphasisCommand.key)
        break
      case 'code':
        runMilkdownCommand(toggleInlineCodeCommand.key)
        break
      case 'link':
        insertLink()
        break
      case 'wiki_link':
        openWikiLinkModal()
        break
      case 'bullet_list':
        runMilkdownCommand(wrapInBulletListCommand.key)
        break
      case 'ordered_list':
        runMilkdownCommand(wrapInOrderedListCommand.key)
        break
      case 'blockquote':
        runMilkdownCommand(wrapInBlockquoteCommand.key)
        break
      case 'heading':
        runMilkdownCommand(wrapInHeadingCommand.key, 2)
        break
    }
  } catch (error) {
    console.error('Failed to apply format:', error)
  }
}

function runMilkdownCommand<T = unknown>(commandKey: CmdKey<T>, payload?: T) {
  if (!editor) return

  editor.action((ctx) => {
    const commands = ctx.get(commandsCtx)
    return payload === undefined
      ? commands.call(commandKey)
      : commands.call(commandKey, payload)
  })
}

const insertLink = () => {
  if (!editor) return

  editor.action((ctx) => {
    const view = ctx.get(editorViewCtx)
    const { from, to } = view.state.selection
    savedSelection = { from, to }
  })

  linkUrl.value = ''
  showLinkDialog.value = true
}

function openWikiLinkModal() {
  if (!editor) return

  editor.action((ctx) => {
    const view = ctx.get(editorViewCtx)
    const { from, to } = view.state.selection
    savedWikiSelection = { from, to }
  })

  emit('insertWikiLink')
}

function insertWikiLinkAtCursor(noteTitle: string): boolean {
  const title = noteTitle.trim()
  if (!title || !editor) return false

  let inserted = false

  editor.action((ctx) => {
    const view = ctx.get(editorViewCtx)
    const { state, dispatch } = view

    const selection = savedWikiSelection ?? {
      from: state.selection.from,
      to: state.selection.to,
    }
    const { from, to } = selection

    const alias = from !== to ? state.doc.textBetween(from, to) : null
    const wikiLinkText = formatWikiLink(title, alias)
    const tr = state.tr.insertText(wikiLinkText, from, to)

    dispatch(tr.setSelection(TextSelection.create(tr.doc, from + wikiLinkText.length)))
    inserted = true
  })

  savedWikiSelection = null
  return inserted
}

function resetLinkDialog() {
  linkUrl.value = ''
  savedSelection = null
}

function confirmLink() {
  const url = linkUrl.value.trim()
  if (!url || !editor) return

  editor.action((ctx) => {
    const view = ctx.get(editorViewCtx)
    const commands = ctx.get(commandsCtx)

    if (savedSelection) {
      const { from, to } = savedSelection
      const selection = TextSelection.create(view.state.doc, from, to)
      view.dispatch(view.state.tr.setSelection(selection))
    }

    commands.call(toggleLinkCommand.key, { href: url })
  })

  showLinkDialog.value = false
  resetLinkDialog()
}

onBeforeUnmount(() => {
  unregisterToolbarState?.()
  unregisterToolbarState = null
  editor?.destroy()
})

defineExpose({
  getMarkdown: getCurrentMarkdown,
  insertWikiLinkAtCursor,
})
</script>

<style>
.markdown-editor {
  @apply bg-white dark:bg-gray-800 min-w-0 overflow-hidden;
}

.toolbar-button {
  @apply flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300;
}

.toolbar-label {
  @apply text-sm leading-none select-none;
}

.toolbar-button:hover {
  @apply bg-gray-200 dark:bg-gray-700;
}

.toolbar-button--active {
  @apply bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-gray-100;
}

.milkdown-editor {
  @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-4 min-w-0 w-full;
  outline: none;
  box-shadow: none;
  border: none;
}

.milkdown-editor:focus,
.milkdown-editor:focus-within {
  outline: none;
  box-shadow: none;
  border: none;
}

/* Без scoped :deep() не компилируется — используем обычные селекторы */
.markdown-editor .milkdown {
  @apply h-full w-full max-w-full;
  outline: none;
  box-shadow: none;
  border: none;
}

.markdown-editor .milkdown .editor,
.markdown-editor .milkdown .ProseMirror {
  @apply min-h-full w-full max-w-full cursor-text;
  caret-color: currentColor;
  outline: none !important;
  outline-offset: 0 !important;
  box-shadow: none !important;
  border: none !important;
}

.markdown-editor .milkdown .ProseMirror:focus,
.markdown-editor .milkdown .ProseMirror:focus-visible,
.markdown-editor .milkdown .ProseMirror-focused {
  outline: none !important;
  outline-offset: 0 !important;
  box-shadow: none !important;
  border: none !important;
}

.markdown-editor .milkdown .ProseMirror-selectednode,
.markdown-editor .milkdown .ProseMirror .ProseMirror-selectednode {
  outline: none !important;
  box-shadow: none !important;
}

.markdown-editor .milkdown .ProseMirror p {
  @apply mb-4;
}

.markdown-editor .milkdown .ProseMirror strong {
  @apply font-bold;
}

.markdown-editor .milkdown .ProseMirror em {
  @apply italic;
}

.markdown-editor .milkdown .ProseMirror h1 {
  @apply text-3xl font-bold mb-4 mt-6;
}

.markdown-editor .milkdown .ProseMirror h2 {
  @apply text-2xl font-bold mb-3 mt-5;
}

.markdown-editor .milkdown .ProseMirror h3 {
  @apply text-xl font-bold mb-2 mt-4;
}

.markdown-editor .milkdown .ProseMirror ul {
  @apply list-disc list-outside pl-6 mb-4;
}

.markdown-editor .milkdown .ProseMirror ol {
  @apply list-decimal list-outside pl-6 mb-4;
}

.markdown-editor .milkdown .ProseMirror li {
  @apply mb-1;
}

.markdown-editor .milkdown .ProseMirror blockquote {
  @apply border-l-4 border-gray-300 dark:border-gray-600 pl-4 italic my-4;
}

.markdown-editor .milkdown .ProseMirror code {
  @apply bg-gray-100 dark:bg-gray-900 px-1 py-0.5 rounded text-sm font-mono;
}

.markdown-editor .milkdown .ProseMirror pre {
  @apply bg-gray-100 dark:bg-gray-900 p-4 rounded my-4 overflow-x-auto;
}

.markdown-editor .milkdown .ProseMirror pre code {
  @apply bg-transparent p-0;
}

.markdown-editor .milkdown .ProseMirror a {
  @apply text-blue-600 dark:text-blue-400 underline;
}

.markdown-editor .milkdown .ProseMirror .wiki-link-edit {
  @apply text-blue-600 dark:text-blue-400 no-underline border-b border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 rounded-sm;
  text-decoration: none !important;
}
</style>
