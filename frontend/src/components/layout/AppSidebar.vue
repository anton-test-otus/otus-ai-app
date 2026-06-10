<template>
  <AppSidePanel
    ref="panelRef"
    position="left"
    :collapsed="isBelowLg"
    fixed-visibility-class="hidden lg:block"
    aria-label="Папки и теги"
    drawer-title="Навигация"
  >
    <slot />
  </AppSidePanel>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppSidePanel from './AppSidePanel.vue'
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
