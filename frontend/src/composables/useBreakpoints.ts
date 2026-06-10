import { ref, computed, onMounted, onUnmounted } from 'vue'

/** Единая сетка breakpoints приложения (синхронизирована с tailwind: md/lg/3xl) */
export const BREAKPOINTS = {
  md: 768,
  lg: 1024,
  xl: 1400,
} as const

/** Tailwind-классы адаптивной ширины боковых панелей (lg: 288px, 3xl: 320px) */
export const SIDEBAR_WIDTH_CLASS = 'w-64 lg:w-72 3xl:w-80'

export function useBreakpoints() {
  const windowWidth = ref(window.innerWidth)

  function updateWidth() {
    windowWidth.value = window.innerWidth
  }

  onMounted(() => {
    window.addEventListener('resize', updateWidth)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', updateWidth)
  })

  const isBelowMd = computed(() => windowWidth.value < BREAKPOINTS.md)
  const isBelowLg = computed(() => windowWidth.value < BREAKPOINTS.lg)
  const isBelowXl = computed(() => windowWidth.value < BREAKPOINTS.xl)

  return {
    windowWidth,
    isBelowMd,
    isBelowLg,
    isBelowXl,
  }
}
