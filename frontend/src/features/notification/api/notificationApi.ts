import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse, AppNotification } from '@/types'

export const notificationApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<AppNotification>>('/notifications', { params })
  },

  unreadCount() {
    return api.get<ApiResponse<{ unread_count: number }>>('/notifications/unread-count')
  },

  markAsRead(uuid: string) {
    return api.post(`/notifications/${uuid}/read`)
  },

  markAllAsRead() {
    return api.post('/notifications/read-all')
  },
}
