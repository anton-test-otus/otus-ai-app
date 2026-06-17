import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import { getApiErrorMessage } from '@/utils/apiError'
import { resetUserStores } from '@/stores/resetUserStores'
import { appConfig } from '@/config/app'
import { HttpError } from '@/api/client'
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
  /** false = single-user (login UI hidden); detected at runtime or from VITE_AUTH_ENABLED */
  const authUiEnabled = ref(appConfig.authEnabled)
  let sessionInitialized = false
  let sessionInitPromise: Promise<boolean> | null = null

  const isAuthenticated = computed(() => {
    if (!authUiEnabled.value) {
      return user.value !== null
    }

    return !!token.value && !!user.value
  })

  const isAdmin = computed(() => {
    if (!authUiEnabled.value) {
      return false
    }

    return user.value?.roles?.includes('ROLE_ADMIN') || false
  })

  function applyAuthResponse(response: AuthResponse) {
    authUiEnabled.value = true
    token.value = response.token
    refreshToken.value = response.refreshToken || null
    user.value = response.user

    localStorage.setItem('token', response.token)
    if (response.refreshToken) {
      localStorage.setItem('refreshToken', response.refreshToken)
    }
  }

  async function initializeSession(): Promise<boolean> {
    if (sessionInitialized) {
      return isAuthenticated.value
    }

    if (sessionInitPromise) {
      return sessionInitPromise
    }

    sessionInitPromise = (async () => {
      const implicitLoaded = await fetchImplicitUser()
      if (implicitLoaded) {
        if (token.value) {
          localStorage.removeItem('token')
          localStorage.removeItem('refreshToken')
          token.value = null
          refreshToken.value = null
        }
        sessionInitialized = true
        return true
      }

      if (token.value) {
        authUiEnabled.value = true
        const loaded = await fetchUser()
        sessionInitialized = true
        return loaded
      }

      authUiEnabled.value = true
      sessionInitialized = true
      return false
    })()

    try {
      return await sessionInitPromise
    } finally {
      sessionInitPromise = null
    }
  }

  async function fetchImplicitUser(): Promise<boolean> {
    isLoading.value = true
    error.value = null
    try {
      user.value = await authApi.me({ skipAuth: true })
      authUiEnabled.value = false
      return true
    } catch (err: unknown) {
      if (err instanceof HttpError && err.status === 401) {
        authUiEnabled.value = true
        return false
      }

      error.value = getApiErrorMessage(err, 'Ошибка загрузки пользователя')
      user.value = null
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function login(credentials: LoginRequest) {
    isLoading.value = true
    error.value = null
    try {
      const response = await authApi.login(credentials)
      applyAuthResponse(response)
      sessionInitialized = true

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
      sessionInitialized = true

      return true
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка регистрации')
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) {
      return false
    }

    isLoading.value = true
    error.value = null
    try {
      user.value = await authApi.me()
      return true
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка загрузки пользователя')
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
    } catch (err: unknown) {
      error.value = getApiErrorMessage(err, 'Ошибка сохранения настроек')
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
    if (!authUiEnabled.value) {
      return
    }

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
    authUiEnabled,
    isAuthenticated,
    isAdmin,
    isLoading,
    error,
    initializeSession,
    login,
    register,
    fetchUser,
    updateSettings,
    changePassword,
    logout,
    applyAuthResponse,
  }
})
