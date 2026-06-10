<template>
  <div class="min-h-screen flex flex-col pt-16">
    <AppNavbar />

    <div class="flex-1 flex min-w-0">
      <!-- Sidebar with folders and tags -->
      <AppSidebar
        v-if="authStore.isAuthenticated && showSidebar"
        ref="sidebarRef"
      >
        <div class="space-y-6">
          <FolderTree
            :folders="foldersStore.folderTree"
            @select="handleFolderSelect"
            @update="foldersStore.fetchFolders()"
          />
          
          <Divider />
          
          <TagsPanel @filter-change="handleTagFilter" />
        </div>
      </AppSidebar>

      <!-- Main content -->
      <main class="flex-1 min-w-0 overflow-y-auto bg-gray-50 dark:bg-gray-900">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed, ref, provide } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Divider from 'primevue/divider'
import AppNavbar from './AppNavbar.vue'
import AppSidebar from './AppSidebar.vue'
import FolderTree from '@/components/sidebar/FolderTree.vue'
import TagsPanel from '@/components/sidebar/TagsPanel.vue'
import { useAuthStore } from '@/stores/auth'
import { useFoldersStore } from '@/stores/folders'
import { useTagsStore } from '@/stores/tags'
import { useBreakpoints } from '@/composables/useBreakpoints'
import { LAYOUT_PANELS_KEY } from '@/composables/useLayoutPanels'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const foldersStore = useFoldersStore()
const tagsStore = useTagsStore()
const { isBelowLg } = useBreakpoints()

const sidebarRef = ref<InstanceType<typeof AppSidebar> | null>(null)

const showSidebar = computed(() => {
  return route.name !== 'login' && route.name !== 'register'
})

const showNavToggle = computed(
  () => authStore.isAuthenticated && showSidebar.value && isBelowLg.value,
)

provide(LAYOUT_PANELS_KEY, {
  showNavToggle,
  openNavigation: () => sidebarRef.value?.open(),
})

function handleFolderSelect(_folderId: string | null) {
  if (route.name !== 'dashboard') {
    router.push({ name: 'dashboard' })
  }
}

function handleTagFilter(_tagIds: string[]) {
  // TODO: Filter notes by tags
}

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await Promise.all([
      foldersStore.fetchFolders(),
      tagsStore.fetchTags(),
    ])
  }
})
</script>
