import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse, User, Lab } from '@/types'

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

  assignRfid(uuid: string, rfid_uid: string) {
    return api.patch(`/users/${uuid}/rfid`, { rfid_uid })
  },

  lookupByRfid(uid: string) {
    return api.get(`/users/rfid/${uid}/bookings`)
  },

  // === Akses lab per user ===
  getUserLabs(uuid: string) {
    return api.get<ApiResponse<{ data: Lab[] }>>(`/users/${uuid}/labs`)
  },

  assignLab(uuid: string, lab_uuid: string, role?: string) {
    return api.post(`/users/${uuid}/labs`, { lab_uuid, role })
  },

  revokeLab(uuid: string, lab_uuid: string) {
    return api.delete(`/users/${uuid}/labs`, { data: { lab_uuid } })
  },

  getLabCustomers(labId: number, params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<User>>(`/users/lab/${labId}/customers`, { params })
  },
}
