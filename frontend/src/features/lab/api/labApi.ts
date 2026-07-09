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

  updateRentalRates(
    uuid: string,
    data: { student_price_per_hour: number; public_price_per_hour: number },
  ) {
    return api.patch<ApiResponse<Lab>>(`/labs/${uuid}/rental-rates`, data)
  },

  updateImage(uuid: string, type: 'logo' | 'hero_image' | 'favicon', file: File) {
    const formData = new FormData()
    formData.append('type', type)
    formData.append('image', file)
    return api.post(`/labs/${uuid}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
}
