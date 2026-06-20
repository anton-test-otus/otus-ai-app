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
        v-if="authStore.authUiEnabled && authStore.isAdmin"
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
      <div
        class="group relative flex items-center min-w-0 rounded"
        :class="{ 'sidebar-nav-item--active': isAccountSectionActive }"
      >
        <router-link
          :to="{ name: 'settings' }"
          class="sidebar-nav-item sidebar-account-link flex-1 min-w-0 pr-10"
          :class="{ 'sidebar-nav-item--active': route.name === 'settings' }"
          @click="handleNavigate"
        >
          <i class="pi pi-cog sidebar-nav-icon" aria-hidden="true" />
          <span class="sidebar-nav-label truncate">
            {{ authStore.authUiEnabled ? authStore.user?.email : 'Настройки' }}
          </span>
        </router-link>

        <div
          class="absolute right-0 top-0 bottom-0 flex items-center justify-end pr-0.5 pl-2 transition-opacity rounded-r"
          :class="[
            statsActionsVisibilityClass,
            isAccountSectionActive
              ? 'bg-primary-50 dark:bg-primary-900/20'
              : 'bg-surface-0 group-hover:bg-surface-100 dark:bg-surface-900 dark:group-hover:bg-surface-800',
          ]"
        >
          <Button
            icon="pi pi-chart-bar"
            text
            rounded
            size="small"
            class="sidebar-icon-btn"
            :severity="route.name === 'stats' ? 'info' : undefined"
            @mousedown.prevent
            @click.stop="goToStats"
            v-tooltip.top="'Статистика'"
          />
        </div>
      </div>

      <button
        v-if="authStore.authUiEnabled"
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
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth'
import { useTrashStore } from '@/stores/trash'
import { useLayoutPanels } from '@/composables/useLayoutPanels'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const trashStore = useTrashStore()
const layoutPanels = useLayoutPanels()

const isAccountSectionActive = computed(
  () => route.name === 'settings' || route.name === 'stats',
)

const statsActionsVisibilityClass = computed(() =>
  route.name === 'stats'
    ? 'opacity-100 pointer-events-auto'
    : 'opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto',
)

onMounted(() => {
  trashStore.fetchCount()
})

function formatBadgeCount(value: number): string {
  return value > 99 ? '99+' : String(value)
}

function handleNavigate() {
  layoutPanels?.closeNavigation?.()
}

function goToStats() {
  handleNavigate()
  router.push({ name: 'stats' })
}

function logout() {
  authStore.logout()
  router.push({ name: 'login' })
}
</script>
