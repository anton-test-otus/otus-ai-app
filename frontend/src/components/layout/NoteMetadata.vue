<template>
  <AppSidePanel
    ref="panelRef"
    position="right"
    :collapsed="isBelow3xl"
    fixed-visibility-class="hidden 3xl:block"
    aria-label="Метаданные заметки"
    drawer-title="Метаданные"
  >
    <template #header>
      <h3 class="section-title mb-4">Метаданные</h3>
    </template>
    <slot />
  </AppSidePanel>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppSidePanel from './AppSidePanel.vue'
import { useBreakpoints } from '@/composables/useBreakpoints'

const { isBelow3xl } = useBreakpoints()

const panelRef = ref<InstanceType<typeof AppSidePanel> | null>(null)

function open() {
  panelRef.value?.open()
}

function close() {
  panelRef.value?.close()
}

defineExpose({ open, close })
</script>
