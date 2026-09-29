import api from '@/lib/axios'

export const packageApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get('/packages', { params })
  },
  getByUuid(uuid: string) {
    return api.get(`/packages/${uuid}`)
  },
  create(data: Record<string, unknown>) {
    return api.post('/packages', data)
  },
  update(uuid: string, data: Record<string, unknown>) {
    return api.patch(`/packages/${uuid}`, data)
  },
  delete(uuid: string) {
    return api.delete(`/packages/${uuid}`)
  },
  updateImage(uuid: string, file: File) {
    const formData = new FormData()
    formData.append('image', file)
    return api.post(`/packages/${uuid}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  removeImage(uuid: string) {
    return api.delete(`/packages/${uuid}/image`)
  },
}
