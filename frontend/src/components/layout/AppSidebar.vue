<template>
  <div>
    <!-- Collapsed: Hamburger button -->
    <Button
      v-if="isCollapsed"
      icon="pi pi-bars"
      class="fixed top-20 left-4 z-40 lg:hidden"
      rounded
      @click="visible = true"
      v-tooltip.right="'Папки'"
    />

    <!-- Desktop: Fixed sidebar -->
    <div
      v-if="!isCollapsed"
      class="hidden lg:block fixed left-0 top-16 h-[calc(100vh-4rem)] w-80 bg-surface-0 dark:bg-surface-900 border-r border-surface-200 dark:border-surface-700 overflow-y-auto z-30"
    >
      <div class="p-6">
        <slot />
      </div>
    </div>

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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Button from 'primevue/button';
import Sidebar from 'primevue/sidebar';

const SIDEBAR_BREAKPOINT = 1024;

const visible = ref(false);
const windowWidth = ref(window.innerWidth);

const isCollapsed = computed(() => windowWidth.value < SIDEBAR_BREAKPOINT);

function updateWidth() {
  windowWidth.value = window.innerWidth;
  if (!isCollapsed.value && visible.value) {
    visible.value = false;
  }
}

onMounted(() => {
  window.addEventListener('resize', updateWidth);
});

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth);
});

defineExpose({
  close: () => visible.value = false,
});
</script>
