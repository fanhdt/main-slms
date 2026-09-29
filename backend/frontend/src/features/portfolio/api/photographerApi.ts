import api from '@/lib/axios'
import type { ApiResponse } from '@/types'
import type { Photographer } from '@/features/portfolio/types'

export const photographerApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get<ApiResponse<Photographer[]>>('/photographers', { params })
  },

  getPublicForLab(labId: number) {
    return api.get<ApiResponse<Photographer[]>>(`/labs/${labId}/photographers`, {
      params: { active_only: 1 },
    })
  },

  create(data: { lab_id: number; name: string; bio?: string; instagram?: string; photo?: File }) {
    const formData = new FormData()
    formData.append('lab_id', String(data.lab_id))
    formData.append('name', data.name)
    if (data.bio) formData.append('bio', data.bio)
    if (data.instagram) formData.append('instagram', data.instagram)
    if (data.photo) formData.append('photo', data.photo)

    return api.post<ApiResponse<Photographer>>('/photographers', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  update(uuid: string, data: Record<string, unknown>) {
    return api.put<ApiResponse<Photographer>>(`/photographers/${uuid}`, data)
  },

  updatePhoto(uuid: string, file: File) {
    const formData = new FormData()
    formData.append('photo', file)
    return api.post<ApiResponse<Photographer>>(`/photographers/${uuid}/photo`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  delete(uuid: string) {
    return api.delete(`/photographers/${uuid}`)
  },
}
