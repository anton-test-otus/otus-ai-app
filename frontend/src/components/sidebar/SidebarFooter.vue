<template>
  <footer class="sidebar-footer border-t app-border bg-surface-0 dark:bg-surface-900">
    <nav class="sidebar-footer-nav stack-items panel-padding-x py-3">
      <router-link
        :to="{ name: 'trash' }"
        class="sidebar-nav-item"
        :class="{ 'sidebar-nav-item--active': route.name === 'trash' }"
        @click="handleNavigate"
      >
        <i class="pi pi-trash sidebar-nav-icon" aria-hidden="true" />
        <span class="sidebar-nav-label">Корзина</span>
        <Badge
          v-if="trashStore.count > 0"
          :value="formatBadgeCount(trashStore.count)"
          severity="secondary"
          class="ml-auto"
        />
      </router-link>

      <router-link
        v-if="authStore.isAdmin"
        :to="{ name: 'admin-users' }"
        class="sidebar-nav-item"
        :class="{ 'sidebar-nav-item--active': route.name === 'admin-users' }"
        @click="handleNavigate"
      >
        <i class="pi pi-users sidebar-nav-icon" aria-hidden="true" />
        <span class="sidebar-nav-label">Управление пользователями</span>
      </router-link>
    </nav>

    <div class="sidebar-account border-t app-border panel-padding-x py-3">
      <router-link
        :to="{ name: 'settings' }"
        class="sidebar-nav-item sidebar-account-link"
        :class="{ 'sidebar-nav-item--active': route.name === 'settings' }"
        @click="handleNavigate"
      >
        <i class="pi pi-cog sidebar-nav-icon" aria-hidden="true" />
        <span class="sidebar-nav-label truncate">{{ authStore.user?.email }}</span>
      </router-link>

      <button
        type="button"
        class="sidebar-nav-item sidebar-logout-btn w-full mt-1"
        @click="logout"
      >
        <i class="pi pi-sign-out sidebar-nav-icon" aria-hidden="true" />
        <span class="sidebar-nav-label">Выход</span>
      </button>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Badge from 'primevue/badge'
import { useAuthStore } from '@/stores/auth'
import { useTrashStore } from '@/stores/trash'
import { useLayoutPanels } from '@/composables/useLayoutPanels'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const trashStore = useTrashStore()
const layoutPanels = useLayoutPanels()

onMounted(() => {
  trashStore.fetchCount()
})

function formatBadgeCount(value: number): string {
  return value > 99 ? '99+' : String(value)
}

function handleNavigate() {
  layoutPanels?.closeNavigation?.()
}

function logout() {
  authStore.logout()
  router.push({ name: 'login' })
}
</script>
