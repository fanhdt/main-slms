import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse, Booking } from '@/types'

export const bookingApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<Booking>>('/bookings', { params })
  },

  getByUuid(uuid: string) {
    return api.get<ApiResponse<Booking>>(`/bookings/${uuid}`)
  },

  create(data: Record<string, unknown>) {
    return api.post<ApiResponse<Booking>>('/bookings', data)
  },

  updateStatus(uuid: string, status: string) {
    return api.patch(`/bookings/${uuid}/status`, { status })
  },

  updatePaymentStatus(uuid: string, payment_status: string) {
    return api.patch(`/bookings/${uuid}/payment-status`, { payment_status })
  },

  checkin(code: string) {
    return api.post<ApiResponse<Booking>>('/bookings/checkin', { code })
  },

  cancel(uuid: string) {
    return api.post(`/bookings/${uuid}/cancel`)
  },
}
