import { apiClient } from './client'
import type { LoginRequest, RegisterRequest, AuthResponse, User } from '@/types'

export const authApi = {
  async login(credentials: LoginRequest): Promise<AuthResponse> {
    const response = await apiClient.post<AuthResponse>('/auth/login', credentials)
    return response.data
  },

  async register(credentials: RegisterRequest): Promise<AuthResponse> {
    const response = await apiClient.post<AuthResponse>('/auth/register', credentials)
    return response.data
  },

  async me(): Promise<User> {
    const response = await apiClient.get<User>('/auth/me')
    return response.data
  },

  async refresh(refreshToken: string): Promise<AuthResponse> {
    const response = await apiClient.post<AuthResponse>('/auth/refresh', { refreshToken })
    return response.data
  },
}
