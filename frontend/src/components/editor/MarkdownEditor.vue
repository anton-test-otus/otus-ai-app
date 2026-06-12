<template>
  <div class="markdown-editor h-full flex flex-col">
    <!-- Панель форматирования -->
    <div class="toolbar border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-2 flex flex-wrap gap-1 md:gap-1">
      <button
        v-for="tool in tools"
        :key="tool.name"
        v-tooltip.bottom="tool.title"
        @mousedown.prevent
        @click="applyFormat(tool.command)"
        :class="[
          'toolbar-button rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors',
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
    <div ref="editorRef" class="milkdown-editor markdown-prose flex-1 overflow-auto content-padding"></div>

    <Dialog
      v-model:visible="showLinkDialog"
      modal
      :header="linkIsEditing ? 'Редактировать ссылку' : 'Вставить ссылку'"
      :style="MODAL_WIDTH.md"
      @hide="resetLinkDialog"
    >
      <div class="space-y-4">
        <div class="flex flex-col gap-2">
          <label for="link-anchor-input" class="text-sm font-medium">Текст ссылки</label>
          <InputText
            id="link-anchor-input"
            v-model="linkAnchor"
            placeholder="Отображаемый текст"
            class="w-full"
            autofocus
            @keyup.enter="confirmLink"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="link-url-input" class="text-sm font-medium">URL</label>
          <InputText
            id="link-url-input"
            v-model="linkUrl"
            placeholder="https://example.com"
            class="w-full"
            :invalid="!!linkUrlError"
            @input="linkUrlError = ''"
            @keyup.enter="confirmLink"
          />
          <small v-if="linkUrlError" class="text-red-500 dark:text-red-400">
            {{ linkUrlError }}
          </small>
        </div>
      </div>
      <template #footer>
        <Button label="Отмена" severity="secondary" text @click="showLinkDialog = false" />
        <Button
          :label="linkIsEditing ? 'Сохранить' : 'Вставить'"
          :disabled="!linkUrl.trim()"
          @click="confirmLink"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useEventListener } from '@vueuse/core'
import { MODAL_WIDTH } from '@/constants/modal'
import { formatShortcutKeys, SHORTCUT_KEYS } from '@/constants/keyboardShortcuts'
import { useEditorFormattingShortcuts } from '@/composables/useAppKeyboardShortcuts'
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
import { getLinkSelectionContext } from './linkSelection'
import type { ToolbarCommand } from './toolbarActiveState'
import { isValidLinkUrl, LINK_URL_ERROR } from '@/utils/url'

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
const linkAnchor = ref('')
const linkUrlError = ref('')
const linkIsEditing = ref(false)
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
  { name: 'heading', label: 'H', labelClass: 'font-semibold', title: `Заголовок (${formatShortcutKeys(SHORTCUT_KEYS.heading)})`, command: 'heading' },
  { name: 'bold', label: 'B', labelClass: 'font-bold', title: `Жирный (${formatShortcutKeys(SHORTCUT_KEYS.bold)})`, command: 'bold' },
  { name: 'italic', label: 'I', labelClass: 'italic', title: `Курсив (${formatShortcutKeys(SHORTCUT_KEYS.italic)})`, command: 'italic' },
  { name: 'list', icon: 'pi pi-list', title: `Маркированный список (${formatShortcutKeys(SHORTCUT_KEYS.bulletList)})`, command: 'bullet_list' },
  { name: 'ordered-list', icon: 'pi pi-sort-numeric-down', title: `Нумерованный список (${formatShortcutKeys(SHORTCUT_KEYS.orderedList)})`, command: 'ordered_list' },
  { name: 'quote', icon: 'pi pi-comment', title: `Цитата (${formatShortcutKeys(SHORTCUT_KEYS.blockquote)})`, command: 'blockquote' },
  { name: 'code', icon: 'pi pi-code', title: `Код (${formatShortcutKeys(SHORTCUT_KEYS.code)})`, command: 'code' },
  { name: 'link', icon: 'pi pi-link', title: `Ссылка (${formatShortcutKeys(SHORTCUT_KEYS.link)})`, command: 'link' },
  { name: 'wiki-link', icon: 'pi pi-sitemap', title: `Ссылка на заметку (${formatShortcutKeys(SHORTCUT_KEYS.wikiLink)})`, command: 'wiki_link' },
]

const { onEditorKeyDown } = useEditorFormattingShortcuts({
  heading: () => applyFormat('heading'),
  bullet_list: () => applyFormat('bullet_list'),
  ordered_list: () => applyFormat('ordered_list'),
  blockquote: () => applyFormat('blockquote'),
  code: () => applyFormat('code'),
  link: () => applyFormat('link'),
  wiki_link: () => applyFormat('wiki_link'),
})

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

useEventListener(
  () => editorRef.value,
  'keydown',
  onEditorKeyDown,
  { capture: true },
)

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
    const context = getLinkSelectionContext(view.state)

    savedSelection = { from: context.from, to: context.to }
    linkUrl.value = context.href
    linkAnchor.value = context.text
    linkIsEditing.value = context.isExisting
    linkUrlError.value = ''
    showLinkDialog.value = true
  })
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
  linkAnchor.value = ''
  linkUrlError.value = ''
  linkIsEditing.value = false
  savedSelection = null
}

function confirmLink() {
  const url = linkUrl.value.trim()
  const anchor = linkAnchor.value.trim() || url
  if (!url || !editor || !savedSelection) return

  if (!isValidLinkUrl(url)) {
    linkUrlError.value = LINK_URL_ERROR
    return
  }

  const selection = savedSelection

  editor.action((ctx) => {
    const view = ctx.get(editorViewCtx)
    const { state, dispatch } = view
    const linkType = state.schema.marks.link

    if (!linkType || !selection) return

    const { from, to } = selection
    let tr = state.tr.insertText(anchor, from, to)

    const newTo = from + anchor.length
    tr = tr.removeMark(from, newTo, linkType)
    tr = tr.addMark(from, newTo, linkType.create({ href: url }))
    tr = tr.setSelection(TextSelection.create(tr.doc, newTo))

    dispatch(tr.scrollIntoView())
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
  @apply flex items-center justify-center w-11 h-11 md:w-8 md:h-8 text-gray-700 dark:text-gray-300;
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
  @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 min-w-0 w-full;
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

.markdown-editor .milkdown .ProseMirror .wiki-link-edit {
  @apply text-blue-600 dark:text-blue-400 no-underline border-b border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 rounded-sm;
  text-decoration: none !important;
}
</style>
