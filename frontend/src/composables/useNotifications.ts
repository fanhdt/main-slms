import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { notificationApi } from '@/features/notification/api/notificationApi'
import { initEcho, disconnectEcho } from '@/lib/echo'
import type { AppNotification } from '@/types'

const notifications = ref<AppNotification[]>([])
const unreadCount = ref(0)
const loading = ref(false)
let subscribed = false

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

  const hasUnread = computed(() => unreadCount.value > 0)

  async function fetchNotifications() {
    loading.value = true
    try {
      const res = await notificationApi.getAll({ per_page: 20 })
      notifications.value = res.data.data.data
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCount() {
    const res = await notificationApi.unreadCount()
    unreadCount.value = res.data.data.unread_count
  }

  async function markAsRead(uuid: string) {
    await notificationApi.markAsRead(uuid)
    const notif = notifications.value.find((n) => n.uuid === uuid)
    if (notif) notif.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  }

  async function markAllAsRead() {
    await notificationApi.markAllAsRead()
    notifications.value.forEach((n) => (n.read_at = n.read_at ?? new Date().toISOString()))
    unreadCount.value = 0
  }

  function handleIncoming(eventName: string, payload: Record<string, unknown>) {
    unreadCount.value += 1
    toast.info(EVENT_LABELS[eventName] ?? 'Notifikasi baru', {
      description:
        (payload.booking_code as string) ?? (payload.project_uuid as string) ?? undefined,
    })
    // Refresh list biar konsisten dengan DB (uuid notif dibuat di backend, bukan di payload broadcast)
    fetchNotifications()
  }

  /**
   * Subscribe ke channel personal user + channel lab aktif (kalau staff).
   * Aman dipanggil berkali-kali — hanya subscribe sekali per session.
   */
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

    // Channel per-lab — hanya relevan untuk staff yang punya activeLab
    if (labStore.activeLab?.id && !authStore.hasRole('customer')) {
      const labChannel = echo.private(`Lab.${labStore.activeLab.id}`)
      Object.keys(EVENT_LABELS).forEach((event) => {
        labChannel.listen(`.${event}`, (payload: Record<string, unknown>) =>
          handleIncoming(event, payload),
        )
      })
    }
  }

  function unsubscribeRealtime() {
    disconnectEcho()
    subscribed = false
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
