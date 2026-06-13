<template>
  <router-link
    :to="{ name: 'favorites' }"
    class="folder-row flex items-center gap-2 list-row-padding rounded transition-colors"
    :class="{
      'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-primary-500 pl-[6px]': isActive,
      'border-l-2 border-transparent pl-[6px] hover:bg-surface-100 dark:hover:bg-surface-800': !isActive,
    }"
    @click="handleNavigate"
  >
    <i class="pi pi-star-fill w-4 shrink-0 text-sm text-amber-500" aria-hidden="true" />
    <span class="flex-1 text-sm">Избранное</span>
  </router-link>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useLayoutPanels } from '@/composables/useLayoutPanels'

const route = useRoute()
const layoutPanels = useLayoutPanels()

const isActive = computed(() => route.name === 'favorites')

function handleNavigate() {
  layoutPanels?.closeNavigation?.()
}
</script>
