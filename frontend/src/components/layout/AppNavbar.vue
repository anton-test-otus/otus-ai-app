<template>
  <nav class="app-chrome border-b app-border fixed top-0 left-0 right-0 z-40">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16 gap-4">
        <!-- Logo + navigation toggle -->
        <div class="flex items-center flex-shrink-0 gap-1">
          <Button
            v-if="layoutPanels?.showNavToggle"
            icon="pi pi-bars"
            severity="secondary"
            text
            rounded
            class="lg:hidden"
            @click="layoutPanels.openNavigation()"
            v-tooltip.bottom="'Навигация'"
          />
          <router-link to="/" class="flex items-center space-x-2">
            <i class="pi pi-book text-2xl text-blue-600"></i>
            <span class="text-xl font-bold text-surface-900 dark:text-white hidden sm:inline">База знаний</span>
          </router-link>
        </div>

        <!-- Search bar - hidden on small screens -->
        <div v-if="authStore.isAuthenticated" class="hidden md:flex flex-1 max-w-xl">
          <SearchBar />
        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-2 flex-shrink-0">
          <Button
            v-if="authStore.isAuthenticated"
            icon="pi pi-search"
            severity="secondary"
            text
            rounded
            class="md:hidden"
            @click="openMobileSearch"
            v-tooltip.bottom="'Поиск'"
          />
          <Button
            v-if="authStore.isAuthenticated"
            icon="pi pi-plus"
            label="Новая заметка"
            @click="openNewNote"
            class="hidden lg:flex"
          />
          <Button
            v-if="authStore.isAuthenticated"
            icon="pi pi-plus"
            @click="openNewNote"
            class="lg:hidden"
            rounded
            v-tooltip.bottom="'Новая заметка'"
          />

        </div>
      </div>
    </div>

    <Dialog
      v-model:visible="showMobileSearch"
      modal
      header="Поиск заметок"
      class="search-mobile-dialog"
      :style="MODAL_WIDTH.lg"
      @show="onMobileSearchShow"
    >
      <SearchBar
        ref="mobileSearchRef"
        in-modal
        @note-opened="showMobileSearch = false"
      />
    </Dialog>
  </nav>
</template>

<script setup lang="ts">
import { ref, nextTick } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import { MODAL_WIDTH } from '@/constants/modal'
import SearchBar from '@/components/common/SearchBar.vue'
import { useAuthStore } from '@/stores/auth'
import { useCreateNote } from '@/composables/useCreateNote'
import { useLayoutPanels } from '@/composables/useLayoutPanels'

const authStore = useAuthStore()
const { openNewNote } = useCreateNote()
const layoutPanels = useLayoutPanels()

const showMobileSearch = ref(false)
const mobileSearchRef = ref<InstanceType<typeof SearchBar> | null>(null)

function openMobileSearch() {
  showMobileSearch.value = true
}

async function onMobileSearchShow() {
  await nextTick()
  mobileSearchRef.value?.focusInput()
}

</script>
