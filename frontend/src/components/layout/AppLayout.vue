<template>
  <div class="min-h-screen flex flex-col pt-16">
    <AppNavbar />

    <div class="flex-1 flex min-w-0">
      <!-- Sidebar with folders and tags -->
      <AppSidebar
        v-if="authStore.isAuthenticated && showSidebar"
        ref="sidebarRef"
      >
        <div class="stack-sections">
          <FavoritesNavLink />

          <FolderTree
            :folders="foldersStore.folderTree"
            @select="handleFolderSelect"
            @update="foldersStore.fetchFolders({ force: true })"
          />
          
          <Divider />
          
          <TagsPanel @filter-change="handleTagFilter" />
        </div>
      </AppSidebar>

      <!-- Main content -->
      <main class="flex-1 min-w-0 overflow-y-auto app-ground">
        <RouterView />
      </main>
    </div>

    <KeyboardShortcutsDialog />
    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { onMounted, watch, computed, ref, provide } from 'vue'
import { useRoute, useRouter, RouterView } from 'vue-router'
import Divider from 'primevue/divider'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import AppNavbar from './AppNavbar.vue'
import AppSidebar from './AppSidebar.vue'
import FolderTree from '@/components/sidebar/FolderTree.vue'
import FavoritesNavLink from '@/components/sidebar/FavoritesNavLink.vue'
import TagsPanel from '@/components/sidebar/TagsPanel.vue'
import { useAuthStore } from '@/stores/auth'
import { useFoldersStore } from '@/stores/folders'
import { useTagsStore } from '@/stores/tags'
import { useTrashStore } from '@/stores/trash'
import { useBreakpoints } from '@/composables/useBreakpoints'
import { useAppKeyboardShortcuts } from '@/composables/useAppKeyboardShortcuts'
import { LAYOUT_PANELS_KEY } from '@/composables/useLayoutPanels'
import KeyboardShortcutsDialog from '@/components/common/KeyboardShortcutsDialog.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const foldersStore = useFoldersStore()
const tagsStore = useTagsStore()
const trashStore = useTrashStore()
const { isBelowLg } = useBreakpoints()

useAppKeyboardShortcuts()

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
  closeNavigation: () => sidebarRef.value?.close(),
})

function handleFolderSelect(_folderId: string | null) {
  if (route.name !== 'dashboard') {
    router.push({ name: 'dashboard' })
  }
}

function handleTagFilter(_tagIds: string[]) {
  if (route.name !== 'dashboard') {
    router.push({ name: 'dashboard' })
  }
}

async function loadSidebarTags() {
  await tagsStore.fetchTags({
    folderId: foldersStore.selectedFolderId,
    tags: tagsStore.selectedTags,
  })
}

watch(
  [() => foldersStore.selectedFolderId, () => [...tagsStore.selectedTags]],
  async ([folderId], [previousFolderId]) => {
    if (previousFolderId !== undefined && folderId !== previousFolderId) {
      tagsStore.clearTagSelection()
      await tagsStore.fetchTags({
        folderId: foldersStore.selectedFolderId,
        tags: [],
      })
      return
    }

    await loadSidebarTags()
  },
)

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    return
  }

  await foldersStore.fetchFolders()
  await loadSidebarTags()
  await trashStore.fetchCount()
})
</script>
