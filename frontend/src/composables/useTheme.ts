import { ref, readonly } from 'vue'
import type { Theme } from '@/types'
import { THEME_STORAGE_KEY } from '@/constants/theme'
import lightThemeUrl from 'primevue/resources/themes/lara-light-blue/theme.css?url'
import darkThemeUrl from 'primevue/resources/themes/lara-dark-blue/theme.css?url'

const PRIMEVUE_THEME_URLS: Record<Theme, string> = {
  light: lightThemeUrl,
  dark: darkThemeUrl,
}

const theme = ref<Theme>('light')

function resolveTheme(): Theme {
  const saved = localStorage.getItem(THEME_STORAGE_KEY)
  if (saved === 'light' || saved === 'dark') {
    return saved
  }
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function applyTheme(newTheme: Theme) {
  document.documentElement.classList.toggle('dark', newTheme === 'dark')

  const link = document.getElementById('primevue-theme') as HTMLLinkElement | null
  if (link) {
    link.href = PRIMEVUE_THEME_URLS[newTheme]
  }
}

function persistTheme(newTheme: Theme) {
  localStorage.setItem(THEME_STORAGE_KEY, newTheme)
}

/** Синхронизирует ref, DOM и PrimeVue theme CSS. Вызывать один раз при старте приложения. */
export function initTheme() {
  const resolved = resolveTheme()
  theme.value = resolved
  applyTheme(resolved)
}

export function useTheme() {
  function setTheme(newTheme: Theme) {
    theme.value = newTheme
    applyTheme(newTheme)
    persistTheme(newTheme)
  }

  function toggleTheme() {
    setTheme(theme.value === 'light' ? 'dark' : 'light')
  }

  return {
    theme: readonly(theme),
    setTheme,
    toggleTheme,
  }
}
