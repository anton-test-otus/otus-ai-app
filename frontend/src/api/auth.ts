import { apiClient } from './client'
import type {
  LoginRequest,
  RegisterRequest,
  AuthResponse,
  User,
  UpdateUserSettingsRequest,
  ChangePasswordRequest,
} from '@/types'

export const authApi = {
  async login(credentials: LoginRequest): Promise<AuthResponse> {
    return apiClient.post<AuthResponse>('/auth/login', credentials)
  },

  async register(credentials: RegisterRequest): Promise<AuthResponse> {
    return apiClient.post<AuthResponse>('/auth/register', credentials)
  },

  async me(): Promise<User> {
    return apiClient.get<User>('/auth/me')
  },

  async refresh(refreshToken: string): Promise<AuthResponse> {
    return apiClient.post<AuthResponse>('/auth/refresh', { refreshToken })
  },

  async updateSettings(settings: UpdateUserSettingsRequest): Promise<User> {
    return apiClient.patch<User>('/auth/settings', settings)
  },

  async changePassword(payload: ChangePasswordRequest): Promise<{ message: string }> {
    return apiClient.post<{ message: string }>('/auth/change-password', payload)
  },
}
