import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'

function getScrollParent(el: HTMLElement | null): HTMLElement | null {
  let parent = el?.parentElement ?? null
  while (parent) {
    const { overflowY, overflow } = getComputedStyle(parent)
    if (/(auto|scroll)/.test(overflowY) || /(auto|scroll)/.test(overflow)) {
      return parent
    }
    parent = parent.parentElement
  }
  return null
}

function isElementScrollable(el: HTMLElement): boolean {
  return el.scrollHeight > el.clientHeight + 1
}

function parseRootMarginBottom(margin?: string): number {
  const parts = (margin ?? '200px').trim().split(/\s+/)
  let bottom = parts[0] ?? '200px'
  if (parts.length === 3 || parts.length === 4) {
    bottom = parts[2] ?? bottom
  }
  const match = bottom.match(/^(-?\d+(?:\.\d+)?)(px)?$/)
  return match ? Number(match[1]) : 200
}

export function useInfiniteList(options: {
  onLoadMore: () => void | Promise<void>
  canLoadMore: () => boolean
  rootMargin?: string
}) {
  const sentinelRef = ref<HTMLElement | null>(null)
  const scrollRoot = ref<HTMLElement | null>(null)
  const scrollArmed = ref(false)
  const marginBottom = parseRootMarginBottom(options.rootMargin)
  let scrollCleanup: (() => void) | null = null

  function isSentinelNearViewport(): boolean {
    const sentinel = sentinelRef.value
    if (!sentinel) {
      return false
    }

    return sentinel.getBoundingClientRect().top <= window.innerHeight + marginBottom
  }

  function tryLoadMore() {
    if (!scrollArmed.value || !options.canLoadMore() || !isSentinelNearViewport()) {
      return
    }
    void options.onLoadMore()
  }

  function bindScrollListeners() {
    scrollCleanup?.()
    const cleanups: (() => void)[] = []

    const onScroll = () => {
      scrollArmed.value = true
      tryLoadMore()
    }

    window.addEventListener('scroll', onScroll, { passive: true })
    cleanups.push(() => window.removeEventListener('scroll', onScroll))

    const root = scrollRoot.value
    if (root && isElementScrollable(root)) {
      root.addEventListener('scroll', onScroll, { passive: true })
      cleanups.push(() => root.removeEventListener('scroll', onScroll))
    }

    scrollCleanup = () => cleanups.forEach((fn) => fn())
  }

  function updateScrollRoot() {
    scrollRoot.value = getScrollParent(sentinelRef.value)
    bindScrollListeners()
  }

  onMounted(bindScrollListeners)
  onUnmounted(() => scrollCleanup?.())

  watch(sentinelRef, (el, prev) => {
    if (el && !prev) {
      scrollArmed.value = false
    }
    updateScrollRoot()
  })

  // Viewport as root: main has overflow-y-auto but page often scrolls via window (flex without min-h-0).
  useIntersectionObserver(
    sentinelRef,
    ([entry]) => {
      if (entry?.isIntersecting) {
        tryLoadMore()
      }
    },
    {
      rootMargin: options.rootMargin ?? '200px',
    },
  )

  return { sentinelRef }
}
