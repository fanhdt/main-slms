import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse, User } from '@/types'

export const userApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<User>>('/users', { params })
  },

  getByUuid(uuid: string) {
    return api.get<ApiResponse<User>>(`/users/${uuid}`)
  },

  create(data: Record<string, unknown>) {
    return api.post<ApiResponse<User>>('/users', data)
  },

  update(uuid: string, data: Record<string, unknown>) {
    return api.put<ApiResponse<User>>(`/users/${uuid}`, data)
  },

  delete(uuid: string) {
    return api.delete(`/users/${uuid}`)
  },

  assignRole(uuid: string, role: string) {
    return api.post(`/users/${uuid}/assign-role`, { role })
  },
}
