<template>
  <AppSidePanel
    ref="panelRef"
    position="left"
    :collapsed="isBelowLg"
    fixed-visibility-class="hidden lg:block"
    ariaLabel="Папки и теги"
    drawer-title="Навигация"
  >
    <slot />
    <template #footer>
      <SidebarFooter />
    </template>
  </AppSidePanel>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppSidePanel from './AppSidePanel.vue'
import SidebarFooter from '@/components/sidebar/SidebarFooter.vue'
import { useBreakpoints } from '@/composables/useBreakpoints'

const { isBelowLg } = useBreakpoints()

const panelRef = ref<InstanceType<typeof AppSidePanel> | null>(null)

function open() {
  panelRef.value?.open()
}

function close() {
  panelRef.value?.close()
}

defineExpose({ open, close })
</script>
