import api from '@/lib/axios'

export const reportApi = {
  getAll: (params?: Record<string, any>) => api.get('/reports', { params }),
  getByUuid: (uuid: string) => api.get(`/reports/${uuid}`),
}
