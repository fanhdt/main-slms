import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse, Asset } from '@/types'

export const assetApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<Asset>>('/assets', { params })
  },

  getByUuid(uuid: string) {
    return api.get<ApiResponse<Asset>>(`/assets/${uuid}`)
  },

  create(data: Record<string, unknown>) {
    return api.post<ApiResponse<Asset>>('/assets', data)
  },

  update(uuid: string, data: Record<string, unknown>) {
    return api.put<ApiResponse<Asset>>(`/assets/${uuid}`, data)
  },

  updateStatus(uuid: string, status: string) {
    return api.patch(`/assets/${uuid}/status`, { status })
  },

  delete(uuid: string) {
    return api.delete(`/assets/${uuid}`)
  },

  updateImage(uuid: string, file: File) {
    const formData = new FormData()
    formData.append('image', file)
    return api.post<ApiResponse<Asset>>(`/assets/${uuid}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  removeImage(uuid: string) {
    return api.delete<ApiResponse<Asset>>(`/assets/${uuid}/image`)
  },
}
