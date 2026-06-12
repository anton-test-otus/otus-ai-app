import type { ShortcutMatchOptions } from '@/utils/keyboard'

export interface ShortcutDefinition {
  id: string
  keys: string
  description: string
}

export interface ShortcutGroup {
  title: string
  shortcuts: ShortcutDefinition[]
}

/** Подписи для UI (без конфликтов с Chrome / Firefox / Edge) */
export const SHORTCUT_KEYS = {
  newNote: 'Ctrl+Alt+N',
  search: 'Ctrl+K',
  save: 'Ctrl+S',
  shortcutsHelp: '?',
  toggleMode: 'Ctrl+Alt+M',
  goBack: 'Ctrl+Alt+B',
  bold: 'Ctrl+B',
  italic: 'Ctrl+I',
  heading: 'Ctrl+Shift+H',
  bulletList: 'Ctrl+Shift+8',
  orderedList: 'Ctrl+Shift+7',
  blockquote: 'Ctrl+Shift+.',
  code: 'Ctrl+Alt+C',
  link: 'Ctrl+Alt+K',
  wikiLink: 'Ctrl+Alt+W',
} as const

export const SHORTCUT_MATCH: Record<keyof typeof SHORTCUT_KEYS, ShortcutMatchOptions | null> = {
  newNote: { key: 'n', mod: true, alt: true },
  search: { key: 'k', mod: true },
  save: { key: 's', mod: true },
  shortcutsHelp: null,
  toggleMode: { key: 'm', mod: true, alt: true },
  goBack: { key: 'b', mod: true, alt: true },
  bold: { key: 'b', mod: true },
  italic: { key: 'i', mod: true },
  heading: { key: 'h', mod: true, shift: true },
  bulletList: { key: '8', mod: true, shift: true },
  orderedList: { key: '7', mod: true, shift: true },
  blockquote: { key: '.', mod: true, shift: true },
  code: { key: 'c', mod: true, alt: true },
  link: { key: 'k', mod: true, alt: true },
  wikiLink: { key: 'w', mod: true, alt: true },
}

export const KEYBOARD_SHORTCUT_GROUPS: ShortcutGroup[] = [
  {
    title: 'Общие',
    shortcuts: [
      { id: 'new-note', keys: SHORTCUT_KEYS.newNote, description: 'Новая заметка' },
      { id: 'search', keys: SHORTCUT_KEYS.search, description: 'Поиск заметок' },
      { id: 'save', keys: SHORTCUT_KEYS.save, description: 'Сохранить заметку' },
      { id: 'shortcuts-help', keys: SHORTCUT_KEYS.shortcutsHelp, description: 'Горячие клавиши' },
    ],
  },
  {
    title: 'Заметка',
    shortcuts: [
      { id: 'toggle-mode', keys: SHORTCUT_KEYS.toggleMode, description: 'Редактирование / просмотр' },
      { id: 'go-back', keys: SHORTCUT_KEYS.goBack, description: 'Назад к списку заметок' },
    ],
  },
  {
    title: 'Форматирование (в редакторе)',
    shortcuts: [
      { id: 'bold', keys: SHORTCUT_KEYS.bold, description: 'Жирный' },
      { id: 'italic', keys: SHORTCUT_KEYS.italic, description: 'Курсив' },
      { id: 'heading', keys: SHORTCUT_KEYS.heading, description: 'Заголовок' },
      { id: 'bullet-list', keys: SHORTCUT_KEYS.bulletList, description: 'Маркированный список' },
      { id: 'ordered-list', keys: SHORTCUT_KEYS.orderedList, description: 'Нумерованный список' },
      { id: 'blockquote', keys: SHORTCUT_KEYS.blockquote, description: 'Цитата' },
      { id: 'code', keys: SHORTCUT_KEYS.code, description: 'Код' },
      { id: 'link', keys: SHORTCUT_KEYS.link, description: 'Ссылка (в редакторе)' },
      { id: 'wiki-link', keys: SHORTCUT_KEYS.wikiLink, description: 'Ссылка на заметку' },
    ],
  },
]

export const MOD_KEY_LABEL = typeof navigator !== 'undefined'
  && /Mac|iPhone|iPad|iPod/.test(navigator.platform)
  ? '⌘'
  : 'Ctrl'

/** Подпись с учётом ОС (Ctrl → ⌘ на macOS) */
export function formatShortcutKeys(keys: string): string {
  if (MOD_KEY_LABEL === '⌘') {
    return keys.replace(/\bCtrl\b/g, '⌘')
  }
  return keys
}
