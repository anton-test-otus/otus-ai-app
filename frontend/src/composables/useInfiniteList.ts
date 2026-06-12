import { ref, onMounted, watch, type Ref } from 'vue'
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

export function useInfiniteList(options: {
  onLoadMore: () => void | Promise<void>
  canLoadMore: () => boolean
  rootMargin?: string
}) {
  const sentinelRef = ref<HTMLElement | null>(null)
  const scrollRoot: Ref<HTMLElement | null | undefined> = ref(null)

  function updateScrollRoot() {
    scrollRoot.value = getScrollParent(sentinelRef.value) ?? undefined
  }

  onMounted(updateScrollRoot)

  watch(sentinelRef, updateScrollRoot)

  useIntersectionObserver(
    sentinelRef,
    ([entry]) => {
      if (entry?.isIntersecting && options.canLoadMore()) {
        void options.onLoadMore()
      }
    },
    {
      root: scrollRoot,
      rootMargin: options.rootMargin ?? '200px',
    },
  )

  return { sentinelRef }
}
