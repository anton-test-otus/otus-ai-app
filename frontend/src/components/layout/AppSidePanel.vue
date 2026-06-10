<template>
  <div>
    <!-- In-flow spacer + fixed panel -->
    <aside
      v-if="!collapsed"
      :class="['shrink-0', fixedVisibilityClass, SIDEBAR_WIDTH_CLASS]"
      :aria-label="ariaLabel"
    >
      <div
        :class="[
          'fixed top-16 z-30 h-[calc(100vh-4rem)] bg-surface-0 dark:bg-surface-900 overflow-y-auto',
          fixedEdgeClass,
          SIDEBAR_WIDTH_CLASS,
        ]"
      >
        <div class="panel-padding min-w-0">
          <slot name="header" />
          <slot />
        </div>
      </div>
    </aside>

    <!-- Drawer -->
    <Sidebar
      v-if="collapsed"
      v-model:visible="drawerVisible"
      :position="position"
      :style="DRAWER_WIDTH.sidebar"
    >
      <template #header>
        <slot name="drawer-header">
          <h2 class="text-xl font-bold">{{ drawerTitle }}</h2>
        </slot>
      </template>

      <div class="panel-padding min-w-0">
        <slot />
      </div>
    </Sidebar>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Sidebar from 'primevue/sidebar'
import { DRAWER_WIDTH } from '@/constants/modal'
import { SIDEBAR_WIDTH_CLASS } from '@/composables/useBreakpoints'

interface Props {
  position: 'left' | 'right'
  collapsed: boolean
  /** Tailwind visibility classes for fixed panel, e.g. `hidden lg:block` */
  fixedVisibilityClass: string
  ariaLabel: string
  drawerTitle: string
}

const props = defineProps<Props>()

const drawerVisible = ref(false)

const fixedEdgeClass = computed(() =>
  props.position === 'left'
    ? 'left-0 border-r border-surface-200 dark:border-surface-700'
    : 'right-0 border-l border-surface-200 dark:border-surface-700',
)

watch(
  () => props.collapsed,
  (isCollapsed) => {
    if (!isCollapsed) {
      drawerVisible.value = false
    }
  },
)

function open() {
  if (props.collapsed) {
    drawerVisible.value = true
  }
}

function close() {
  drawerVisible.value = false
}

defineExpose({ open, close })
</script>
