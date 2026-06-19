const API_URL = import.meta.env.VITE_API_URL || '/api'

interface RequestConfig extends RequestInit {
  params?: Record<string, any>
  skipAuth?: boolean
}

class HttpError extends Error {
  constructor(
    public status: number,
    public statusText: string,
    public response?: any
  ) {
    super(`HTTP ${status}: ${statusText}`)
    this.name = 'HttpError'
  }
}

let refreshPromise: Promise<boolean> | null = null

async function refreshAccessToken(): Promise<boolean> {
  if (refreshPromise) {
    return refreshPromise
  }

  refreshPromise = (async () => {
    const storedRefreshToken = localStorage.getItem('refreshToken')
    if (!storedRefreshToken) {
      return false
    }

    try {
      const response = await fetch(`${API_URL}/auth/refresh`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ refreshToken: storedRefreshToken }),
      })

      if (!response.ok) {
        return false
      }

      const data = await response.json()
      localStorage.setItem('token', data.token)
      if (data.refreshToken) {
        localStorage.setItem('refreshToken', data.refreshToken)
      } else {
        localStorage.removeItem('refreshToken')
      }

      if (data.user) {
        const { useAuthStore } = await import('@/stores/auth')
        useAuthStore().applyAuthResponse(data)
      }

      return true
    } catch {
      return false
    } finally {
      refreshPromise = null
    }
  })()

  return refreshPromise
}

async function clearSessionAndRedirect(): Promise<void> {
  localStorage.removeItem('token')
  localStorage.removeItem('refreshToken')

  const { useAuthStore } = await import('@/stores/auth')
  const authStore = useAuthStore()
  window.location.href = authStore.authUiEnabled ? '/login' : '/'
}

class ApiClient {
  private baseURL: string
  private defaultHeaders: HeadersInit

  constructor(baseURL: string) {
    this.baseURL = baseURL
    this.defaultHeaders = {
      Accept: 'application/ld+json',
    }
  }

  private async request<T>(
    endpoint: string,
    config: RequestConfig = {},
    retriedAfterRefresh = false
  ): Promise<T> {
    const { params, headers, skipAuth, ...restConfig } = config

    let url = `${this.baseURL}${endpoint}`
    if (params) {
      const searchParams = new URLSearchParams()
      Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null) {
          return
        }
        if (Array.isArray(value)) {
          value.forEach((item) => {
            if (item !== undefined && item !== null) {
              searchParams.append(`${key}[]`, String(item))
            }
          })
          return
        }
        searchParams.append(key, String(value))
      })
      const queryString = searchParams.toString()
      if (queryString) {
        url += `?${queryString}`
      }
    }

    const mergedHeaders = new Headers(this.defaultHeaders)
    if (headers) {
      const headersToMerge = new Headers(headers)
      headersToMerge.forEach((value, key) => {
        mergedHeaders.set(key, value)
      })
    }

    const token = skipAuth ? null : localStorage.getItem('token')
    if (token) {
      mergedHeaders.set('Authorization', `Bearer ${token}`)
    }

    const response = await fetch(url, {
      ...restConfig,
      headers: mergedHeaders,
    })

    const isAuthAttempt =
      endpoint === '/auth/login' ||
      endpoint === '/auth/register' ||
      endpoint === '/auth/refresh'

    if (response.status === 401) {
      const { useAuthStore } = await import('@/stores/auth')
      const authUiEnabled = useAuthStore().authUiEnabled

      if (!skipAuth && !isAuthAttempt && !retriedAfterRefresh && authUiEnabled) {
        const refreshed = await refreshAccessToken()
        if (refreshed) {
          return this.request<T>(endpoint, config, true)
        }
      }

      let errorData
      try {
        errorData = await response.json()
      } catch {
        errorData = { message: response.statusText }
      }

      // skipAuth: probe for single-user mode (/auth/me without JWT) — 401 is expected in multi-user
      if (authUiEnabled && !skipAuth && (!isAuthAttempt || endpoint === '/auth/refresh')) {
        await clearSessionAndRedirect()
      }

      throw new HttpError(401, 'Unauthorized', errorData)
    }

    if (!response.ok) {
      let errorData
      try {
        errorData = await response.json()
      } catch {
        errorData = { message: response.statusText }
      }
      throw new HttpError(response.status, response.statusText, errorData)
    }

    if (response.status === 204 || response.headers.get('content-length') === '0') {
      return undefined as T
    }

    const data = await response.json()
    return data as T
  }

  async get<T>(url: string, config?: RequestConfig): Promise<T> {
    return this.request<T>(url, { ...config, method: 'GET' })
  }

  async post<T>(url: string, data?: any, config?: RequestConfig): Promise<T> {
    return this.request<T>(url, {
      ...config,
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
      headers: {
        ...config?.headers,
        'Content-Type': 'application/json',
      },
    })
  }

  async put<T>(url: string, data?: any, config?: RequestConfig): Promise<T> {
    return this.request<T>(url, {
      ...config,
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
      headers: {
        ...config?.headers,
        'Content-Type': 'application/json',
      },
    })
  }

  async patch<T>(url: string, data?: any, config?: RequestConfig): Promise<T> {
    return this.request<T>(url, {
      ...config,
      method: 'PATCH',
      body: data ? JSON.stringify(data) : undefined,
      headers: {
        ...config?.headers,
        'Content-Type': 'application/merge-patch+json',
      },
    })
  }

  async delete<T>(url: string, config?: RequestConfig): Promise<T> {
    return this.request<T>(url, { ...config, method: 'DELETE' })
  }
}

export const apiClient = new ApiClient(API_URL)
export { HttpError }
