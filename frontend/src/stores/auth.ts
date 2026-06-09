import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import type { User, LoginRequest, RegisterRequest } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const refreshToken = ref<string | null>(localStorage.getItem('refreshToken'))
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.role === 'ROLE_ADMIN')

  async function login(credentials: LoginRequest) {
    isLoading.value = true
    error.value = null
    try {
      const response = await authApi.login(credentials)
      token.value = response.token
      refreshToken.value = response.refreshToken
      user.value = response.user

      localStorage.setItem('token', response.token)
      localStorage.setItem('refreshToken', response.refreshToken)

      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка входа'
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
      token.value = response.token
      refreshToken.value = response.refreshToken
      user.value = response.user

      localStorage.setItem('token', response.token)
      localStorage.setItem('refreshToken', response.refreshToken)

      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка регистрации'
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
      error.value = err.response?.data?.message || 'Ошибка загрузки пользователя'
      logout()
      return false
    } finally {
      isLoading.value = false
    }
  }

  function logout() {
    user.value = null
    token.value = null
    refreshToken.value = null
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
    logout,
  }
})
