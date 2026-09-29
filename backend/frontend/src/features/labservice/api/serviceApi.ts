import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse, Service } from '@/types'

export const serviceApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<Service>>('/services', { params })
  },

  getByUuid(uuid: string) {
    return api.get<ApiResponse<Service>>(`/services/${uuid}`)
  },

  create(data: Record<string, unknown>) {
    return api.post<ApiResponse<Service>>('/services', data)
  },

  update(uuid: string, data: Record<string, unknown>) {
    return api.put<ApiResponse<Service>>(`/services/${uuid}`, data)
  },

  updateImage(uuid: string, file: File) {
    const formData = new FormData()
    formData.append('image', file)
    return api.post(`/services/${uuid}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  delete(uuid: string) {
    return api.delete(`/services/${uuid}`)
  },

  removeImage(uuid: string) {
    return api.delete(`/services/${uuid}/image`)
  },
}
