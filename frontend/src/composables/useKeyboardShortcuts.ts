import { ref } from 'vue'

const shortcutsHelpVisible = ref(false)
const searchFocusHandlers = new Set<() => void>()

export function useKeyboardShortcutsHelp() {
  function openShortcutsHelp() {
    shortcutsHelpVisible.value = true
  }

  function closeShortcutsHelp() {
    shortcutsHelpVisible.value = false
  }

  function toggleShortcutsHelp() {
    shortcutsHelpVisible.value = !shortcutsHelpVisible.value
  }

  return {
    shortcutsHelpVisible,
    openShortcutsHelp,
    closeShortcutsHelp,
    toggleShortcutsHelp,
  }
}

export function registerSearchFocusHandler(handler: () => void): () => void {
  searchFocusHandlers.add(handler)
  return () => {
    searchFocusHandlers.delete(handler)
  }
}

export function focusAppSearch() {
  for (const handler of searchFocusHandlers) {
    handler()
  }
}
