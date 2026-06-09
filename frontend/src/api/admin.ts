import { apiClient } from './client'
import type { User, PaginatedResponse } from '@/types'

export interface AdminUsersParams {
  page?: number
  perPage?: number
  q?: string
}

class AdminApi {
  async getUsers(params: AdminUsersParams = {}): Promise<PaginatedResponse<User>> {
    return apiClient.get<PaginatedResponse<User>>('/admin/users', { params })
  }

  async getUserDetails(userId: string): Promise<User> {
    return apiClient.get<User>(`/admin/users/${userId}`)
  }

  async enableUser(userId: string): Promise<{ message: string; user: User }> {
    return apiClient.patch(`/admin/users/${userId}/enable`, {})
  }

  async disableUser(userId: string): Promise<{ message: string; user: User }> {
    return apiClient.patch(`/admin/users/${userId}/disable`, {})
  }

  async promoteUser(userId: string): Promise<{ message: string; user: User }> {
    return apiClient.patch(`/admin/users/${userId}/promote`, {})
  }

  async demoteUser(userId: string): Promise<{ message: string; user: User }> {
    return apiClient.patch(`/admin/users/${userId}/demote`, {})
  }

  async deleteUser(userId: string): Promise<{ message: string }> {
    return apiClient.delete(`/admin/users/${userId}`)
  }
}

export const adminApi = new AdminApi()
