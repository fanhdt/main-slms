import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse } from '@/types'
import type { PhotographerPortfolio } from '@/features/portfolio/types'

export const portfolioApi = {
  // ---- Admin ----
  getAll(params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<PhotographerPortfolio>>('/portfolios', { params })
  },

  create(data: {
    lab_id: number
    photographer_id: number
    image: File
    caption?: string
    order?: number
  }) {
    const formData = new FormData()
    formData.append('lab_id', String(data.lab_id))
    formData.append('photographer_id', String(data.photographer_id))
    formData.append('image', data.image)
    if (data.caption) formData.append('caption', data.caption)
    if (data.order !== undefined) formData.append('order', String(data.order))

    return api.post<ApiResponse<PhotographerPortfolio>>('/portfolios', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  update(uuid: string, data: Record<string, unknown>) {
    return api.put<ApiResponse<PhotographerPortfolio>>(`/portfolios/${uuid}`, data)
  },

  updateImage(uuid: string, file: File) {
    const formData = new FormData()
    formData.append('image', file)
    return api.post<ApiResponse<PhotographerPortfolio>>(`/portfolios/${uuid}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  delete(uuid: string) {
    return api.delete(`/portfolios/${uuid}`)
  },

  getGalleryByPhotographer(labId: number, photographerUuid: string, page = 1, perPage = 12) {
    return api.get<PaginatedResponse<PhotographerPortfolio>>(
      `/labs/${labId}/portfolios/${photographerUuid}`,
      { params: { page, per_page: perPage } },
    )
  },
}
