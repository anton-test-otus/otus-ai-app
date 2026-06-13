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

    <Dialog
      v-model:visible="showWikiLinkDialog"
      modal
      header="Редактировать ссылку на заметку"
      :style="MODAL_WIDTH.md"
      @hide="resetWikiLinkDialog"
    >
      <div class="space-y-4">
        <p v-if="wikiLinkTargetTitle" class="text-sm text-gray-600 dark:text-gray-400">
          Заметка: <span class="font-medium text-gray-800 dark:text-gray-200">{{ wikiLinkTargetTitle }}</span>
        </p>

        <div class="flex flex-col gap-2">
          <label for="wiki-link-alias-input" class="text-sm font-medium">Отображаемый текст</label>
          <InputText
            id="wiki-link-alias-input"
            v-model="wikiLinkAlias"
            placeholder="Текст ссылки"
            class="w-full"
            autofocus
            @keyup.enter="confirmWikiLinkAlias"
          />
          <small class="text-gray-500 dark:text-gray-400">
            Пустое значение — показывать актуальный заголовок заметки
          </small>
        </div>
      </div>
      <template #footer>
        <Button
          label="Как заголовок"
          severity="secondary"
          text
          @click="useNoteTitleAsWikiLinkAlias"
        />
        <Button label="Отмена" severity="secondary" text @click="showWikiLinkDialog = false" />
        <Button label="Сохранить" @click="confirmWikiLinkAlias" />
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
import { wikiLinkInputPlugin, registerWikiLinkBracketCallback } from './wikiLinkInputPlugin'
import { remarkStripHtmlPlugin } from './remarkStripHtml'
import {
  insertWikiLinkIntoEditor,
  updateWikiLinkInEditor,
  remarkWikiLinkPlugin,
  remarkWikiLinkStringifyPlugin,
  wikiLinkNodeView,
  wikiLinkSchema,
  insertWikiLinkCommand,
  updateWikiLinkCommand,
  registerWikiLinkEditCallback,
  resolveWikiLinkTitle,
} from './wikiLinkNode'
import { getWikiLinkSelectionContext } from './wikiLinkSelection'
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
const showWikiLinkDialog = ref(false)
const wikiLinkAlias = ref('')
const wikiLinkTargetTitle = ref('')
const activeCommands = ref(new Set<ToolbarCommand>())
let editor: Editor | null = null
let unregisterToolbarState: (() => void) | null = null
let unregisterWikiLinkBracket: (() => void) | null = null
let unregisterWikiLinkEdit: (() => void) | null = null
let isUpdating = false
let lastEmittedMarkdown: string | null = null
let savedSelection: { from: number; to: number } | null = null
let savedWikiSelection: { from: number; to: number } | null = null
let savedWikiLinkEdit: { pos: number; noteId: string } | null = null

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

    unregisterWikiLinkBracket = registerWikiLinkBracketCallback((trigger) => {
      savedWikiSelection = trigger
      emit('insertWikiLink')
    })

    unregisterWikiLinkEdit = registerWikiLinkEditCallback((request) => {
      void openWikiLinkAliasDialog(request)
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
      .use(remarkStripHtmlPlugin)
      .use(history)
      .use(listener)
      .use(clipboard)
      .use(wikiLinkSchema)
      .use(remarkWikiLinkPlugin)
      .use(remarkWikiLinkStringifyPlugin)
      .use(insertWikiLinkCommand)
      .use(updateWikiLinkCommand)
      .use(wikiLinkNodeView)
      .use(wikiLinkInputPlugin)
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

  let existingContext: ReturnType<typeof getWikiLinkSelectionContext> = null
  editor.action((ctx) => {
    const view = ctx.get(editorViewCtx)
    existingContext = getWikiLinkSelectionContext(view.state)
    if (!existingContext) {
      const { from, to } = view.state.selection
      savedWikiSelection = { from, to }
    }
  })

  if (existingContext) {
    void openWikiLinkAliasDialog(existingContext)
    return
  }

  emit('insertWikiLink')
}

async function openWikiLinkAliasDialog(request: { pos: number; noteId: string; label: string }) {
  savedWikiLinkEdit = { pos: request.pos, noteId: request.noteId }
  wikiLinkTargetTitle.value = await resolveWikiLinkTitle(request.noteId)

  if (request.label.trim()) {
    wikiLinkAlias.value = request.label.trim()
  } else {
    wikiLinkAlias.value = wikiLinkTargetTitle.value
  }

  showWikiLinkDialog.value = true
}

function resetWikiLinkDialog() {
  wikiLinkAlias.value = ''
  wikiLinkTargetTitle.value = ''
  savedWikiLinkEdit = null
}

function useNoteTitleAsWikiLinkAlias() {
  wikiLinkAlias.value = ''
  confirmWikiLinkAlias()
}

function confirmWikiLinkAlias() {
  if (!editor || !savedWikiLinkEdit) return

  const alias = wikiLinkAlias.value.trim()
  const label = alias && alias !== wikiLinkTargetTitle.value.trim() ? alias : ''

  editor.action((ctx) => {
    updateWikiLinkInEditor(ctx, {
      pos: savedWikiLinkEdit!.pos,
      label,
    })
  })

  showWikiLinkDialog.value = false
  resetWikiLinkDialog()
}

function insertWikiLinkAtCursor(noteId: string, noteTitle: string): boolean {
  const id = noteId.trim()
  const title = noteTitle.trim()
  if (!id || !title || !editor) return false

  let inserted = false

  editor.action((ctx) => {
    const view = ctx.get(editorViewCtx)
    const selection = savedWikiSelection ?? {
      from: view.state.selection.from,
      to: view.state.selection.to,
    }
    const { from, to } = selection
    const selectedText = from !== to ? view.state.doc.textBetween(from, to) : ''
    const label = selectedText && selectedText !== '[[' ? selectedText : title

    inserted = insertWikiLinkIntoEditor(ctx, { noteId: id, label, from, to })
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
  unregisterWikiLinkBracket?.()
  unregisterWikiLinkBracket = null
  unregisterWikiLinkEdit?.()
  unregisterWikiLinkEdit = null
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
  @apply text-blue-600 dark:text-blue-400 no-underline border-b border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 rounded-sm cursor-pointer;
  text-decoration: none !important;
}
</style>
