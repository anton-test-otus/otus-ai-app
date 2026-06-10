<template>
  <div class="note-metadata">
    <!-- Tablet/desktop: side panel in flex layout (>= 768px) -->
    <div
      v-if="!isCollapsed"
      :class="[
        'hidden md:flex md:flex-col shrink-0 bg-surface-0 dark:bg-surface-900 border-l border-surface-200 dark:border-surface-700 overflow-hidden',
        SIDEBAR_WIDTH_CLASS,
      ]"
    >
      <div class="h-full overflow-y-auto p-4 lg:p-5 3xl:p-6">
        <h3 class="text-lg font-semibold mb-4">Метаданные</h3>
        <slot />
      </div>
    </div>

    <!-- Mobile: Toggle button + right drawer (< 768px) -->
    <template v-else>
      <Button
        icon="pi pi-info-circle"
        class="fixed top-[4.5rem] right-4 z-40"
        rounded
        @click="visible = true"
        v-tooltip.left="'Метаданные'"
      />

      <Sidebar
        v-model:visible="visible"
        position="right"
        :style="{ width: '85vw', maxWidth: '400px' }"
      >
        <template #header>
          <h2 class="text-xl font-bold">Метаданные</h2>
        </template>

        <div class="p-4">
          <slot />
        </div>
      </Sidebar>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from 'primevue/button'
import Sidebar from 'primevue/sidebar'
import { useBreakpoints, SIDEBAR_WIDTH_CLASS } from '@/composables/useBreakpoints'

const { isBelowMd } = useBreakpoints()

const visible = ref(false)
const isCollapsed = isBelowMd

watch(isBelowMd, (collapsed) => {
  if (!collapsed) {
    visible.value = false
  }
})
</script>
