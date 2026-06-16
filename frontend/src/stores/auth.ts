import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import { getApiErrorMessage } from '@/utils/apiError'
import { resetUserStores } from '@/stores/resetUserStores'
import type {
  User,
  AuthResponse,
  LoginRequest,
  RegisterRequest,
  UpdateUserSettingsRequest,
  ChangePasswordRequest,
} from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const refreshToken = ref<string | null>(localStorage.getItem('refreshToken'))
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.roles?.includes('ROLE_ADMIN') || false)

  function applyAuthResponse(response: AuthResponse) {
    token.value = response.token
    refreshToken.value = response.refreshToken || null
    user.value = response.user

    localStorage.setItem('token', response.token)
    if (response.refreshToken) {
      localStorage.setItem('refreshToken', response.refreshToken)
    }
  }

  async function login(credentials: LoginRequest) {
    isLoading.value = true
    error.value = null
    try {
      const response = await authApi.login(credentials)
      applyAuthResponse(response)

      return true
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Неверный email или пароль')
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function register(credentials: RegisterRequest) {
    isLoading.value = true
    error.value = null
    try {
      const response = await authApi.register(credentials)
      applyAuthResponse(response)

      return true
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка регистрации')
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return false

    isLoading.value = true
    error.value = null
    try {
      user.value = await authApi.me()
      return true
    } catch (err: any) {
      error.value = err.message || 'Ошибка загрузки пользователя'
      logout()
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function updateSettings(settings: UpdateUserSettingsRequest) {
    isLoading.value = true
    error.value = null
    try {
      user.value = await authApi.updateSettings(settings)
      return true
    } catch (err: any) {
      error.value = err.message || 'Ошибка сохранения настроек'
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function changePassword(payload: ChangePasswordRequest) {
    error.value = null
    try {
      await authApi.changePassword(payload)
      return true
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Не удалось сменить пароль')
      throw err
    }
  }

  function logout() {
    resetUserStores()
    user.value = null
    token.value = null
    refreshToken.value = null
    error.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('refreshToken')
  }

  return {
    user,
    token,
    isAuthenticated,
    isAdmin,
    isLoading,
    error,
    login,
    register,
    fetchUser,
    updateSettings,
    changePassword,
    logout,
    applyAuthResponse,
  }
})
