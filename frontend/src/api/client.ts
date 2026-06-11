const API_URL = import.meta.env.VITE_API_URL || '/api'

interface RequestConfig extends RequestInit {
  params?: Record<string, any>
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

class ApiClient {
  private baseURL: string
  private defaultHeaders: HeadersInit

  constructor(baseURL: string) {
    this.baseURL = baseURL
    this.defaultHeaders = {
      'Accept': 'application/ld+json',
    }
  }

  private async request<T>(
    endpoint: string,
    config: RequestConfig = {}
  ): Promise<T> {
    const { params, headers, ...restConfig } = config

    // Build URL with query params
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

    // Merge headers
    const mergedHeaders = new Headers(this.defaultHeaders)
    if (headers) {
      const headersToMerge = new Headers(headers)
      headersToMerge.forEach((value, key) => {
        mergedHeaders.set(key, value)
      })
    }

    // Add JWT token from localStorage
    const token = localStorage.getItem('token')
    if (token) {
      mergedHeaders.set('Authorization', `Bearer ${token}`)
    }

    // Make request
    const response = await fetch(url, {
      ...restConfig,
      headers: mergedHeaders,
    })

    const isAuthAttempt = endpoint === '/auth/login' || endpoint === '/auth/register'

    // Handle 401 Unauthorized
    if (response.status === 401) {
      let errorData
      try {
        errorData = await response.json()
      } catch {
        errorData = { message: response.statusText }
      }

      if (!isAuthAttempt) {
        localStorage.removeItem('token')
        localStorage.removeItem('refreshToken')
        window.location.href = '/login'
      }

      throw new HttpError(401, 'Unauthorized', errorData)
    }

    // Check if response is ok
    if (!response.ok) {
      let errorData
      try {
        errorData = await response.json()
      } catch {
        errorData = { message: response.statusText }
      }
      throw new HttpError(response.status, response.statusText, errorData)
    }

    // Parse JSON response only if there is content
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
