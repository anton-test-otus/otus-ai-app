import { ref, watch, onMounted } from 'vue'
import type { Theme } from '@/types'

const THEME_STORAGE_KEY = 'theme'

export function useTheme() {
  const theme = ref<Theme>('light')

  function initTheme() {
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY) as Theme | null
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches

    theme.value = savedTheme || (prefersDark ? 'dark' : 'light')
    applyTheme(theme.value)
  }

  function applyTheme(newTheme: Theme) {
    const root = document.documentElement
    if (newTheme === 'dark') {
      root.classList.add('dark')
    } else {
      root.classList.remove('dark')
    }
  }

  function toggleTheme() {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
  }

  function setTheme(newTheme: Theme) {
    theme.value = newTheme
  }

  watch(theme, (newTheme) => {
    applyTheme(newTheme)
    localStorage.setItem(THEME_STORAGE_KEY, newTheme)
  })

  onMounted(() => {
    initTheme()
  })

  return {
    theme,
    toggleTheme,
    setTheme,
  }
}
