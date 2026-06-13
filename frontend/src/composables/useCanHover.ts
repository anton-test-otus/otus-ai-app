import { ref, onMounted, onUnmounted } from 'vue'

export function useCanHover() {
  const canHover = ref(false)

  let mediaQuery: MediaQueryList | null = null

  function sync() {
    canHover.value = mediaQuery?.matches ?? false
  }

  onMounted(() => {
    mediaQuery = window.matchMedia('(hover: hover)')
    sync()
    mediaQuery.addEventListener('change', sync)
  })

  onUnmounted(() => {
    mediaQuery?.removeEventListener('change', sync)
  })

  return { canHover }
}
