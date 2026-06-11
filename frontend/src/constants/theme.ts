import type { Theme } from '@/types'

export const THEME_STORAGE_KEY = 'theme'

export const THEME_OPTIONS: { label: string; value: Theme }[] = [
  { label: 'Светлая', value: 'light' },
  { label: 'Тёмная', value: 'dark' },
]
