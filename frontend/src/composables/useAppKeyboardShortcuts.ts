import { onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useEventListener } from '@vueuse/core'
import { useAuthStore } from '@/stores/auth'
import { useCreateNote } from '@/composables/useCreateNote'
import { SHORTCUT_MATCH } from '@/constants/keyboardShortcuts'
import {
  focusAppSearch,
  useKeyboardShortcutsHelp,
} from '@/composables/useKeyboardShortcuts'
import {
  isDialogOpen,
  isEditableTarget,
  matchesShortcut,
} from '@/utils/keyboard'

function isProseMirrorEditor(target: EventTarget | null): boolean {
  return target instanceof HTMLElement && Boolean(target.closest('.ProseMirror'))
}

type NoteShortcutHandler = (event: KeyboardEvent) => boolean

const noteHandlers = new Set<NoteShortcutHandler>()

export function registerNoteShortcutHandler(handler: NoteShortcutHandler): () => void {
  noteHandlers.add(handler)
  return () => {
    noteHandlers.delete(handler)
  }
}

/** Глобальные сочетания: новая заметка, поиск, справка */
export function useAppKeyboardShortcuts() {
  const route = useRoute()
  const authStore = useAuthStore()
  const { openNewNote } = useCreateNote()
  const { openShortcutsHelp, toggleShortcutsHelp, shortcutsHelpVisible } = useKeyboardShortcutsHelp()

  function handleGlobalShortcut(event: KeyboardEvent): boolean {
    if (!authStore.isAuthenticated) {
      return false
    }

    if (shortcutsHelpVisible.value && event.key === 'Escape') {
      shortcutsHelpVisible.value = false
      event.preventDefault()
      return true
    }

    if (isDialogOpen() && !shortcutsHelpVisible.value) {
      return false
    }

    const inEditable = isEditableTarget(event.target)
    const inEditor = isProseMirrorEditor(event.target)

    if (SHORTCUT_MATCH.newNote && matchesShortcut(event, SHORTCUT_MATCH.newNote)) {
      if (route.name === 'trash') {
        return false
      }
      event.preventDefault()
      openNewNote()
      return true
    }

    if (SHORTCUT_MATCH.search && matchesShortcut(event, SHORTCUT_MATCH.search) && !inEditor) {
      event.preventDefault()
      focusAppSearch()
      return true
    }

    if (
      event.key === '?'
      && !inEditable
      && !event.ctrlKey
      && !event.metaKey
      && !event.altKey
    ) {
      event.preventDefault()
      toggleShortcutsHelp()
      return true
    }

    if (matchesShortcut(event, { key: 'F1' })) {
      event.preventDefault()
      openShortcutsHelp()
      return true
    }

    return false
  }

  useEventListener(
    window,
    'keydown',
    (event: KeyboardEvent) => {
      if (handleGlobalShortcut(event)) {
        return
      }

      for (const handler of noteHandlers) {
        if (handler(event)) {
          return
        }
      }
    },
    { capture: true },
  )
}

/** Сочетания на странице заметки: сохранение, режим, назад */
export function useNoteKeyboardShortcuts(handlers: {
  onSave: () => void | Promise<void>
  onToggleMode: () => void
  onGoBack: () => void | Promise<void>
}) {
  let unregister: (() => void) | null = null

  function handleNoteShortcut(event: KeyboardEvent): boolean {
    if (isDialogOpen()) {
      return false
    }

    if (SHORTCUT_MATCH.save && matchesShortcut(event, SHORTCUT_MATCH.save)) {
      event.preventDefault()
      void handlers.onSave()
      return true
    }

    if (SHORTCUT_MATCH.toggleMode && matchesShortcut(event, SHORTCUT_MATCH.toggleMode)) {
      event.preventDefault()
      handlers.onToggleMode()
      return true
    }

    if (SHORTCUT_MATCH.goBack && matchesShortcut(event, SHORTCUT_MATCH.goBack)) {
      event.preventDefault()
      void handlers.onGoBack()
      return true
    }

    return false
  }

  onMounted(() => {
    unregister = registerNoteShortcutHandler(handleNoteShortcut)
  })

  onUnmounted(() => {
    unregister?.()
    unregister = null
  })
}

const EDITOR_SHORTCUT_ACTIONS: Record<string, keyof typeof SHORTCUT_MATCH> = {
  heading: 'heading',
  bullet_list: 'bulletList',
  ordered_list: 'orderedList',
  blockquote: 'blockquote',
  code: 'code',
  link: 'link',
  wiki_link: 'wikiLink',
}

/** Сочетания форматирования в Milkdown-редакторе */
export function useEditorFormattingShortcuts(handlers: Record<string, () => void>) {
  function onEditorKeyDown(event: KeyboardEvent) {
    if (isDialogOpen()) {
      return
    }

    for (const [action, shortcutKey] of Object.entries(EDITOR_SHORTCUT_ACTIONS)) {
      const match = SHORTCUT_MATCH[shortcutKey]
      const handler = handlers[action]
      if (!match || !handler) {
        continue
      }
      if (matchesShortcut(event, match)) {
        event.preventDefault()
        handler()
        return
      }
    }
  }

  return { onEditorKeyDown }
}
