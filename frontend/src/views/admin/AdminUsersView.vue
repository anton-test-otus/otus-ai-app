<template>
  <AppLayout>
  <div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
        Управление пользователями
      </h1>
      <p class="text-gray-600 dark:text-gray-400">
        Просмотр, активация/деактивация и удаление пользователей
      </p>
    </div>

    <div class="mb-6 flex gap-4 items-center">
      <div class="relative flex-1 max-w-md">
        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <InputText
          v-model="searchQuery"
          placeholder="Поиск по email..."
          class="w-full pl-10"
          @input="debouncedSearch"
        />
      </div>
      <Button
        v-if="searchQuery"
        label="Сбросить"
        icon="pi pi-times"
        severity="secondary"
        @click="clearSearch"
      />
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <ProgressSpinner />
    </div>

    <div v-else-if="users.length === 0" class="text-center py-12">
      <i class="pi pi-users text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
      <p class="text-gray-500 dark:text-gray-400">
        {{ searchQuery ? 'Пользователи не найдены' : 'Нет пользователей' }}
      </p>
    </div>

    <div v-else class="space-y-4">
      <Card
        v-for="user in users"
        :key="user.id"
        class="hover:shadow-lg transition-shadow"
      >
        <template #content>
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 mb-2">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                    <i class="pi pi-user text-primary-600 dark:text-primary-400 text-xl"></i>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                    {{ user.email }}
                  </h3>
                  <div class="flex flex-wrap gap-2 mt-1">
                    <Tag
                      v-if="user.roles.includes('ROLE_ADMIN')"
                      severity="danger"
                      value="Администратор"
                      icon="pi pi-shield"
                    />
                    <Tag
                      v-if="!user.isActive"
                      severity="warning"
                      value="Неактивен"
                      icon="pi pi-ban"
                    />
                    <Tag
                      v-else
                      severity="success"
                      value="Активен"
                      icon="pi pi-check-circle"
                    />
                  </div>
                </div>
              </div>

              <div v-if="user.statistics" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">Заметок</div>
                  <div class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ user.statistics.notesCount }}
                  </div>
                </div>
                <div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">Папок</div>
                  <div class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ user.statistics.foldersCount }}
                  </div>
                </div>
                <div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">Тегов</div>
                  <div class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ user.statistics.tagsCount }}
                  </div>
                </div>
                <div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">Размер</div>
                  <div class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ formatStorageSize(user.statistics.storageSize) }}
                  </div>
                </div>
              </div>

              <div class="mt-3 text-sm text-gray-500 dark:text-gray-400 flex flex-wrap gap-4">
                <span>
                  <i class="pi pi-calendar mr-1"></i>
                  Создан: {{ formatDate(user.createdAt) }}
                </span>
                <span v-if="user.statistics?.lastActivity">
                  <i class="pi pi-clock mr-1"></i>
                  Последняя активность: {{ formatDate(user.statistics.lastActivity) }}
                </span>
              </div>
            </div>

            <div class="flex flex-wrap lg:flex-col gap-2 lg:min-w-[180px]">
              <Button
                v-if="user.isActive"
                label="Деактивировать"
                icon="pi pi-ban"
                severity="warning"
                outlined
                size="small"
                @click="disableUser(user)"
                :loading="actionLoading[user.id]"
                class="flex-1 lg:flex-none"
              />
              <Button
                v-else
                label="Активировать"
                icon="pi pi-check-circle"
                severity="success"
                outlined
                size="small"
                @click="enableUser(user)"
                :loading="actionLoading[user.id]"
                class="flex-1 lg:flex-none"
              />

              <Button
                v-if="!user.roles.includes('ROLE_ADMIN')"
                label="Сделать админом"
                icon="pi pi-shield"
                severity="info"
                outlined
                size="small"
                @click="promoteUser(user)"
                :loading="actionLoading[user.id]"
                class="flex-1 lg:flex-none"
              />
              <Button
                v-else-if="user.id !== currentUserId"
                label="Снять админа"
                icon="pi pi-shield"
                severity="secondary"
                outlined
                size="small"
                @click="demoteUser(user)"
                :loading="actionLoading[user.id]"
                class="flex-1 lg:flex-none"
              />

              <Button
                v-if="user.id !== currentUserId"
                label="Удалить"
                icon="pi pi-trash"
                severity="danger"
                outlined
                size="small"
                @click="confirmDelete(user)"
                :loading="actionLoading[user.id]"
                class="flex-1 lg:flex-none"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <Paginator
      v-if="!loading && totalPages > 1"
      :rows="perPage"
      :totalRecords="total"
      :first="(currentPage - 1) * perPage"
      @page="onPageChange"
      class="mt-6"
    />

    <ConfirmDialog>
      <template #message="{ message }">
        <div class="flex items-start gap-3">
          <i class="pi pi-exclamation-triangle text-orange-500 text-3xl"></i>
          <div>
            <p class="font-semibold mb-2">{{ message.header }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ message.message }}</p>
          </div>
        </div>
      </template>
    </ConfirmDialog>
  </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { adminApi } from '@/api/admin'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import Paginator from 'primevue/paginator'
import ConfirmDialog from 'primevue/confirmdialog'

const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const users = ref<User[]>([])
const loading = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const total = ref(0)
const totalPages = ref(0)
const actionLoading = reactive<Record<string, boolean>>({})

const currentUserId = computed(() => authStore.user?.id)

let searchTimeout: ReturnType<typeof setTimeout>

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    loadUsers()
  }, 300)
}

const clearSearch = () => {
  searchQuery.value = ''
  currentPage.value = 1
  loadUsers()
}

const loadUsers = async () => {
  try {
    loading.value = true
    const response = await adminApi.getUsers({
      page: currentPage.value,
      perPage: perPage.value,
      q: searchQuery.value || undefined,
    })

    users.value = response.data
    total.value = response.meta.total
    totalPages.value = response.meta.totalPages
    currentPage.value = response.meta.currentPage
  } catch (error) {
    console.error('Failed to load users:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить список пользователей',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onPageChange = (event: { page: number }) => {
  currentPage.value = event.page + 1
  loadUsers()
}

const enableUser = async (user: User) => {
  try {
    actionLoading[user.id] = true
    await adminApi.enableUser(user.id)
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: `Пользователь ${user.email} активирован`,
      life: 3000,
    })
    await loadUsers()
  } catch (error) {
    console.error('Failed to enable user:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось активировать пользователя',
      life: 3000,
    })
  } finally {
    actionLoading[user.id] = false
  }
}

const disableUser = async (user: User) => {
  try {
    actionLoading[user.id] = true
    await adminApi.disableUser(user.id)
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: `Пользователь ${user.email} деактивирован`,
      life: 3000,
    })
    await loadUsers()
  } catch (error) {
    console.error('Failed to disable user:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось деактивировать пользователя',
      life: 3000,
    })
  } finally {
    actionLoading[user.id] = false
  }
}

const promoteUser = async (user: User) => {
  try {
    actionLoading[user.id] = true
    await adminApi.promoteUser(user.id)
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: `${user.email} назначен администратором`,
      life: 3000,
    })
    await loadUsers()
  } catch (error) {
    console.error('Failed to promote user:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось назначить администратора',
      life: 3000,
    })
  } finally {
    actionLoading[user.id] = false
  }
}

const demoteUser = async (user: User) => {
  try {
    actionLoading[user.id] = true
    await adminApi.demoteUser(user.id)
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: `Роль администратора снята с ${user.email}`,
      life: 3000,
    })
    await loadUsers()
  } catch (error) {
    console.error('Failed to demote user:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось снять роль администратора',
      life: 3000,
    })
  } finally {
    actionLoading[user.id] = false
  }
}

const confirmDelete = (user: User) => {
  confirm.require({
    header: 'Подтверждение удаления',
    message: `Вы уверены, что хотите удалить пользователя ${user.email}? Все его заметки, папки и теги будут безвозвратно удалены.`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    acceptClass: 'p-button-danger',
    accept: () => deleteUser(user),
  })
}

const deleteUser = async (user: User) => {
  try {
    actionLoading[user.id] = true
    await adminApi.deleteUser(user.id)
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: `Пользователь ${user.email} удалён`,
      life: 3000,
    })
    await loadUsers()
  } catch (error) {
    console.error('Failed to delete user:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось удалить пользователя',
      life: 3000,
    })
  } finally {
    actionLoading[user.id] = false
  }
}

const formatDate = (dateString?: string | null) => {
  if (!dateString) return 'Никогда'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Только что'
  if (diffMins < 60) return `${diffMins} мин. назад`
  if (diffHours < 24) return `${diffHours} ч. назад`
  if (diffDays < 7) return `${diffDays} дн. назад`
  
  return date.toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatStorageSize = (bytes: number): string => {
  if (bytes === 0) return '0 Б'
  const k = 1024
  const sizes = ['Б', 'КБ', 'МБ', 'ГБ']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${Math.round(bytes / Math.pow(k, i))} ${sizes[i]}`
}

onMounted(() => {
  loadUsers()
})
</script>
