import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse, Lab } from '@/types'

export const labApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<Lab>>('/labs', { params })
  },

  getBySlug(slug: string) {
    return api.get<ApiResponse<Lab>>(`/labs/${slug}`)
  },

  create(data: Record<string, unknown>) {
    return api.post<ApiResponse<Lab>>('/labs', data)
  },

  update(uuid: string, data: Record<string, unknown>) {
    return api.put<ApiResponse<Lab>>(`/labs/${uuid}`, data)
  },

  delete(uuid: string) {
    return api.delete(`/labs/${uuid}`)
  },
}
