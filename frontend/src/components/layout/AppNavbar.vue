<template>
  <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <div class="flex items-center space-x-4">
          <router-link to="/" class="flex items-center space-x-2">
            <i class="pi pi-book text-2xl text-blue-600"></i>
            <span class="text-xl font-bold text-gray-900 dark:text-white">База знаний</span>
          </router-link>
        </div>

        <!-- Search bar in the center -->
        <div v-if="authStore.isAuthenticated" class="flex-1 max-w-2xl mx-4">
          <SearchBar />
        </div>

        <div class="flex items-center space-x-4">
          <Button
            v-if="authStore.isAuthenticated"
            icon="pi pi-plus"
            label="Новая заметка"
            @click="createNewNote"
            class="hidden sm:flex"
          />
          <Button
            v-if="authStore.isAuthenticated"
            icon="pi pi-plus"
            @click="createNewNote"
            class="sm:hidden"
            rounded
          />

          <div v-if="authStore.isAuthenticated" class="flex items-center space-x-2">
            <span class="text-sm text-gray-700 dark:text-gray-300 hidden md:inline">
              {{ authStore.user?.email }}
            </span>
            <Button
              icon="pi pi-sign-out"
              label="Выход"
              severity="secondary"
              text
              @click="logout"
              class="hidden sm:flex"
            />
            <Button
              icon="pi pi-sign-out"
              severity="secondary"
              text
              @click="logout"
              class="sm:hidden"
              rounded
            />
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import SearchBar from '@/components/common/SearchBar.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotesStore } from '@/stores/notes'

const router = useRouter()
const authStore = useAuthStore()
const notesStore = useNotesStore()

async function createNewNote() {
  try {
    const note = await notesStore.createNote({
      title: 'Новая заметка',
      content: '',
    })
    router.push({ name: 'note', params: { id: note.id } })
  } catch (error) {
    console.error('Ошибка создания заметки:', error)
  }
}

function logout() {
  authStore.logout()
  router.push({ name: 'login' })
}
</script>
