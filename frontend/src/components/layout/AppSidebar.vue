<template>
  <div>
    <!-- Collapsed: Hamburger button -->
    <Button
      v-if="isCollapsed"
      icon="pi pi-bars"
      class="fixed top-[4.5rem] left-4 z-40 lg:hidden"
      rounded
      @click="visible = true"
      v-tooltip.right="'Папки'"
    />

    <!-- Desktop: in-flow spacer + fixed panel (ширина всегда синхронизирована с контентом) -->
    <aside
      v-if="!isCollapsed"
      :class="['hidden lg:block shrink-0', SIDEBAR_WIDTH_CLASS]"
      aria-label="Папки и теги"
    >
      <div
        :class="[
          'fixed left-0 top-16 z-30 h-[calc(100vh-4rem)] bg-surface-0 dark:bg-surface-900 border-r border-surface-200 dark:border-surface-700 overflow-y-auto',
          SIDEBAR_WIDTH_CLASS,
        ]"
      >
        <div class="p-4 lg:p-5 3xl:p-6 min-w-0">
          <slot />
        </div>
      </div>
    </aside>

    <!-- Collapsed: Sidebar drawer -->
    <Sidebar
      v-model:visible="visible"
      position="left"
      class="lg:hidden"
      :style="{ width: '85vw', maxWidth: '400px' }"
    >
      <template #header>
        <div class="flex items-center justify-between w-full">
          <h2 class="text-xl font-bold">Меню</h2>
        </div>
      </template>

      <div class="p-4">
        <slot />
      </div>
    </Sidebar>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from 'primevue/button'
import Sidebar from 'primevue/sidebar'
import { useBreakpoints, SIDEBAR_WIDTH_CLASS } from '@/composables/useBreakpoints'

const { isBelowLg } = useBreakpoints()

const visible = ref(false)
const isCollapsed = isBelowLg

watch(isBelowLg, (collapsed) => {
  if (!collapsed) {
    visible.value = false
  }
})

defineExpose({
  close: () => { visible.value = false },
})
</script>
