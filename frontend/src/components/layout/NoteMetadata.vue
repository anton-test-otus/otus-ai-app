<template>
  <div class="note-metadata">
    <!-- Desktop: Fixed right panel -->
    <div
      v-if="!isMobile"
      class="hidden lg:block fixed right-0 top-0 h-screen w-80 bg-surface-0 dark:bg-surface-900 border-l border-surface-200 dark:border-surface-700 overflow-y-auto"
    >
      <div class="p-6">
        <h3 class="text-lg font-semibold mb-4">Метаданные</h3>
        <slot />
      </div>
    </div>

    <!-- Mobile: Toggle button -->
    <div v-else class="lg:hidden border-t border-surface-200 dark:border-surface-700 mt-4">
      <Button
        :label="showMetadata ? 'Скрыть метаданные' : 'Показать метаданные'"
        :icon="showMetadata ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
        text
        class="w-full"
        @click="showMetadata = !showMetadata"
      />
      
      <div v-if="showMetadata" class="p-4 bg-surface-50 dark:bg-surface-800">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Button from 'primevue/button';

const showMetadata = ref(false);
const windowWidth = ref(window.innerWidth);

const isMobile = computed(() => windowWidth.value < 1024);

function updateWidth() {
  windowWidth.value = window.innerWidth;
}

onMounted(() => {
  window.addEventListener('resize', updateWidth);
});

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth);
});
</script>
