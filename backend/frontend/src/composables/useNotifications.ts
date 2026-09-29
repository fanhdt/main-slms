import { ref, computed, watch } from 'vue'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { notificationApi } from '@/features/notification/api/notificationApi'
import { initEcho, disconnectEcho } from '@/lib/echo'
import { useQueryClient } from '@tanstack/vue-query' // <-- Tambahan untuk refresh data
import type { AppNotification } from '@/types'

const notifications = ref<AppNotification[]>([])
const unreadCount = ref(0)
const loading = ref(false)
let subscribed = false
let currentLabChannelId: number | null = null

const EVENT_LABELS: Record<string, string> = {
  'booking.created': 'Booking baru masuk',
  'booking.status-changed': 'Status booking diperbarui',
  'booking.checked-in': 'Booking di-checkin',
  'photo.preview-uploaded': 'Foto preview sudah siap',
  'photo.selection-submitted': 'Customer sudah pilih foto',
  'photo.approval-requested': 'Hasil edit siap direview',
  'photo.delivered': 'Foto siap didownload',
}

export function useNotifications() {
  const authStore = useAuthStore()
  const labStore = useLabStore()
  const queryClient = useQueryClient() // Panggil query client untuk memanipulasi cache

  const hasUnread = computed(() => unreadCount.value > 0)

  async function fetchNotifications() {
    loading.value = true
    try {
      const res = await notificationApi.getAll({ per_page: 20 })

      // FIX 1: Pengecekan defensif untuk mencegah list notifikasi kosong
      const responseData = res.data?.data
      notifications.value = Array.isArray(responseData) ? responseData : responseData?.data || []
    } catch (error) {
      console.error('Gagal mengambil notifikasi:', error)
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCount() {
    try {
      const res = await notificationApi.unreadCount()
      unreadCount.value = res.data.data.unread_count
    } catch (error) {
      console.error('Gagal memuat jumlah notifikasi', error)
    }
  }

  async function markAsRead(uuid: string) {
    try {
      await notificationApi.markAsRead(uuid)
      const notif = notifications.value.find((n) => n.uuid === uuid)
      if (notif) notif.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
      console.error('Gagal menandai telah dibaca', error)
    }
  }

  async function markAllAsRead() {
    try {
      await notificationApi.markAllAsRead()
      notifications.value.forEach((n) => (n.read_at = n.read_at ?? new Date().toISOString()))
      unreadCount.value = 0
    } catch (error) {
      console.error('Gagal menandai semua dibaca', error)
    }
  }

  function handleIncoming(eventName: string, payload: Record<string, unknown>) {
    // HAPUS baris unreadCount.value += 1 dari sini

    toast.info(EVENT_LABELS[eventName] ?? 'Notifikasi baru', {
      description:
        (payload.booking_code as string) ?? (payload.project_uuid as string) ?? undefined,
    })
    fetchNotifications()
    fetchUnreadCount()

    queryClient.refetchQueries({ queryKey: ['my-bookings'] })
    queryClient.refetchQueries({ queryKey: ['bookings'] })
    queryClient.refetchQueries({ queryKey: ['dashboard-bookings'] })
    queryClient.refetchQueries({ queryKey: ['lab-bookings-stats'] })
    queryClient.refetchQueries({ queryKey: ['photo-project'] })
    queryClient.refetchQueries({ queryKey: ['photo-projects'] })
  }

  function subscribeRealtime() {
    if (subscribed || !authStore.token || !authStore.user) return
    subscribed = true

    const echo = initEcho(authStore.token)

    // Channel personal — semua role (customer & staff) dengar ini
    const userChannel = echo.private(`App.Models.User.${authStore.user.id}`)
    Object.keys(EVENT_LABELS).forEach((event) => {
      userChannel.listen(`.${event}`, (payload: Record<string, unknown>) =>
        handleIncoming(event, payload),
      )
    })

    // Channel per-lab — Pantau perubahan lab aktif untuk staff
    if (!authStore.hasRole('customer')) {
      watch(
        () => labStore.activeLab?.id,
        (newLabId) => {
          if (currentLabChannelId && currentLabChannelId !== newLabId) {
            echo.leave(`Lab.${currentLabChannelId}`)
          }
          if (newLabId && currentLabChannelId !== newLabId) {
            currentLabChannelId = newLabId
            const labChannel = echo.private(`Lab.${newLabId}`)
            Object.keys(EVENT_LABELS).forEach((event) => {
              labChannel.listen(`.${event}`, (payload: Record<string, unknown>) =>
                handleIncoming(event, payload),
              )
            })
          }
        },
        { immediate: true },
      )
    }
  }

  function unsubscribeRealtime() {
    disconnectEcho()
    subscribed = false
    currentLabChannelId = null
  }

  return {
    notifications,
    unreadCount,
    hasUnread,
    loading,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    subscribeRealtime,
    unsubscribeRealtime,
  }
}
