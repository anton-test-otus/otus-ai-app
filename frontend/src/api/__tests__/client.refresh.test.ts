import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/config/app', () => ({
  appConfig: {
    authEnabled: true,
    autosaveDelaySeconds: 10,
    versionConsolidationWindowMinutes: 5,
  },
}))

function createLocalStorageMock() {
  const store = new Map<string, string>()

  return {
    getItem: vi.fn((key: string) => store.get(key) ?? null),
    setItem: vi.fn((key: string, value: string) => {
      store.set(key, value)
    }),
    removeItem: vi.fn((key: string) => {
      store.delete(key)
    }),
    clear: vi.fn(() => {
      store.clear()
    }),
  }
}

function jsonResponse(status: number, body?: unknown): Response {
  const hasBody = body !== undefined

  return {
    ok: status >= 200 && status < 300,
    status,
    statusText: status >= 200 && status < 300 ? 'OK' : 'Unauthorized',
    headers: new Headers({
      'content-length': hasBody ? '1' : '0',
    }),
    json: async () => body,
  } as Response
}

function getAuthorizationHeader(init?: RequestInit): string | null {
  if (!init?.headers) {
    return null
  }

  return new Headers(init.headers as HeadersInit).get('Authorization')
}

async function loadClient() {
  return import('@/api/client')
}

describe('apiClient token refresh', () => {
  let localStorageMock: ReturnType<typeof createLocalStorageMock>
  let fetchMock: ReturnType<typeof vi.fn>

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorageMock = createLocalStorageMock()
    fetchMock = vi.fn()
    vi.stubGlobal('fetch', fetchMock)
    vi.stubGlobal('localStorage', localStorageMock)
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: { href: 'http://localhost/' },
    })
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('retries the original request after a successful refresh on 401', async () => {
    localStorageMock.setItem('token', 'old-access-token')
    localStorageMock.setItem('refreshToken', 'refresh-token-1')

    fetchMock
      .mockResolvedValueOnce(jsonResponse(401, { message: 'Expired JWT Token' }))
      .mockResolvedValueOnce(
        jsonResponse(200, {
          token: 'new-access-token',
          refreshToken: 'refresh-token-2',
        })
      )
      .mockResolvedValueOnce(jsonResponse(200, { ok: true }))

    const { apiClient } = await loadClient()
    const result = await apiClient.get<{ ok: boolean }>('/notes/1')

    expect(result).toEqual({ ok: true })
    expect(fetchMock).toHaveBeenCalledTimes(3)

    const refreshCall = fetchMock.mock.calls[1]
    expect(String(refreshCall[0])).toContain('/api/auth/refresh')
    expect(JSON.parse(String(refreshCall[1]?.body))).toEqual({
      refreshToken: 'refresh-token-1',
    })

    const retryCall = fetchMock.mock.calls[2]
    expect(String(retryCall[0])).toContain('/api/notes/1')
    expect(getAuthorizationHeader(retryCall[1])).toBe('Bearer new-access-token')
    expect(localStorageMock.setItem).toHaveBeenCalledWith('token', 'new-access-token')
    expect(localStorageMock.setItem).toHaveBeenCalledWith('refreshToken', 'refresh-token-2')
  })

  it('uses a single refresh for parallel 401 responses', async () => {
    localStorageMock.setItem('token', 'old-access-token')
    localStorageMock.setItem('refreshToken', 'refresh-token-1')

    fetchMock.mockImplementation(async (url: string | URL | Request, init?: RequestInit) => {
      const urlStr = String(url)

      if (urlStr.includes('/auth/refresh')) {
        await new Promise((resolve) => setTimeout(resolve, 20))
        return jsonResponse(200, {
          token: 'new-access-token',
          refreshToken: 'refresh-token-2',
        })
      }

      if (getAuthorizationHeader(init) === 'Bearer old-access-token') {
        return jsonResponse(401, { message: 'Expired JWT Token' })
      }

      return jsonResponse(200, { path: urlStr })
    })

    const { apiClient } = await loadClient()
    const [first, second] = await Promise.all([
      apiClient.get<{ path: string }>('/notes/a'),
      apiClient.get<{ path: string }>('/notes/b'),
    ])

    expect(first.path).toContain('/api/notes/a')
    expect(second.path).toContain('/api/notes/b')

    const refreshCalls = fetchMock.mock.calls.filter(([url]) =>
      String(url).includes('/auth/refresh')
    )
    expect(refreshCalls).toHaveLength(1)
  })

  it('clears the session and redirects to /login when refresh returns 401', async () => {
    localStorageMock.setItem('token', 'old-access-token')
    localStorageMock.setItem('refreshToken', 'refresh-token-1')

    fetchMock
      .mockResolvedValueOnce(jsonResponse(401, { message: 'Expired JWT Token' }))
      .mockResolvedValueOnce(jsonResponse(401, { message: 'Invalid refresh token' }))

    const { apiClient, HttpError } = await loadClient()

    await expect(apiClient.get('/notes/1')).rejects.toMatchObject({
      status: 401,
      name: 'HttpError',
    })

    expect(fetchMock).toHaveBeenCalledTimes(2)
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('token')
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('refreshToken')
    expect(window.location.href).toBe('/login')
  })

  it('does not redirect on 401 when skipAuth is used (single-user probe)', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse(401, { message: 'JWT Token not found' }))

    const { apiClient, HttpError } = await loadClient()

    await expect(
      apiClient.get('/auth/me', { skipAuth: true })
    ).rejects.toMatchObject({
      status: 401,
      name: 'HttpError',
    })

    expect(fetchMock).toHaveBeenCalledTimes(1)
    expect(localStorageMock.removeItem).not.toHaveBeenCalled()
    expect(window.location.href).toBe('http://localhost/')
  })

  it('does not call refresh when refreshToken is missing', async () => {
    localStorageMock.setItem('token', 'old-access-token')

    fetchMock.mockResolvedValueOnce(jsonResponse(401, { message: 'Expired JWT Token' }))

    const { apiClient, HttpError } = await loadClient()

    await expect(apiClient.get('/notes/1')).rejects.toBeInstanceOf(HttpError)

    expect(fetchMock).toHaveBeenCalledTimes(1)
    expect(String(fetchMock.mock.calls[0][0])).toContain('/api/notes/1')
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('token')
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('refreshToken')
    expect(window.location.href).toBe('/login')
  })
})
