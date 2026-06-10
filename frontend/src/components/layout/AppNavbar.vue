<template>
  <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 fixed top-0 left-0 right-0 z-40">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16 gap-4">
        <!-- Logo -->
        <div class="flex items-center flex-shrink-0">
          <router-link to="/" class="flex items-center space-x-2">
            <i class="pi pi-book text-2xl text-blue-600"></i>
            <span class="text-xl font-bold text-gray-900 dark:text-white hidden sm:inline">База знаний</span>
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
            v-if="authStore.isAdmin"
            icon="pi pi-users"
            severity="secondary"
            text
            @click="router.push({ name: 'admin-users' })"
            v-tooltip.bottom="'Управление пользователями'"
            rounded
          />
          <Button
            v-if="authStore.isAuthenticated"
            icon="pi pi-trash"
            severity="secondary"
            text
            @click="router.push({ name: 'trash' })"
            v-tooltip.bottom="'Корзина'"
            rounded
          />
          <Button
            v-if="authStore.isAuthenticated"
            icon="pi pi-plus"
            label="Новая заметка"
            @click="createNewNote"
            class="hidden lg:flex"
          />
          <Button
            v-if="authStore.isAuthenticated"
            icon="pi pi-plus"
            @click="createNewNote"
            class="lg:hidden"
            rounded
            v-tooltip.bottom="'Новая заметка'"
          />

          <div v-if="authStore.isAuthenticated" class="flex items-center space-x-2">
            <span class="text-sm text-gray-700 dark:text-gray-300 hidden lg:inline">
              {{ authStore.user?.email }}
            </span>
            <Button
              icon="pi pi-sign-out"
              label="Выход"
              severity="secondary"
              text
              @click="logout"
              class="hidden md:flex"
            />
            <Button
              icon="pi pi-sign-out"
              severity="secondary"
              text
              @click="logout"
              class="md:hidden"
              rounded
              v-tooltip.bottom="'Выход'"
            />
          </div>
        </div>
      </div>
    </div>

    <Dialog
      v-model:visible="showMobileSearch"
      modal
      header="Поиск заметок"
      class="search-mobile-dialog"
      :style="{ width: '90vw', maxWidth: '50rem' }"
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
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import SearchBar from '@/components/common/SearchBar.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotesStore } from '@/stores/notes'
import { useFoldersStore } from '@/stores/folders'

const router = useRouter()
const authStore = useAuthStore()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()

const showMobileSearch = ref(false)
const mobileSearchRef = ref<InstanceType<typeof SearchBar> | null>(null)

function openMobileSearch() {
  showMobileSearch.value = true
}

async function onMobileSearchShow() {
  await nextTick()
  mobileSearchRef.value?.focusInput()
}

async function createNewNote() {
  try {
    const note = await notesStore.createNote({
      title: 'Новая заметка',
      content: '',
      folderId: foldersStore.selectedFolderId,
    })
    router.push({ name: 'note', params: { id: note.id }, query: { mode: 'edit' } })
  } catch (error) {
    console.error('Ошибка создания заметки:', error)
  }
}

function logout() {
  authStore.logout()
  router.push({ name: 'login' })
}
</script>
