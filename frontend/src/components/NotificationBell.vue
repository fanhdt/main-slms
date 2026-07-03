<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useNotifications } from '@/composables/useNotifications'

const {
  notifications,
  unreadCount,
  hasUnread,
  fetchNotifications,
  fetchUnreadCount,
  markAsRead,
  markAllAsRead,
  subscribeRealtime,
} = useNotifications()

const open = ref(false)

onMounted(async () => {
  await fetchUnreadCount()
  subscribeRealtime()
})

async function toggle() {
  open.value = !open.value
  if (open.value) {
    await fetchNotifications()
  }
}

async function handleClick(notif: { uuid: string; read_at: string | null }) {
  if (!notif.read_at) {
    await markAsRead(notif.uuid)
  }
}

function timeAgo(dateStr: string) {
  const diff = Date.now() - new Date(dateStr).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'baru saja'
  if (minutes < 60) return `${minutes} menit lalu`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} jam lalu`
  return `${Math.floor(hours / 24)} hari lalu`
}
</script>

<template>
  <div class="relative">
    <button
      @click="toggle"
      class="relative w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
      title="Notifikasi"
    >
      <span class="text-lg">🔔</span>
      <span
        v-if="hasUnread"
        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <div
      v-if="open"
      class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto"
    >
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <p class="text-sm font-semibold text-gray-900">Notifikasi</p>
        <button
          v-if="hasUnread"
          @click="markAllAsRead"
          class="text-xs text-blue-600 hover:underline"
        >
          Tandai semua dibaca
        </button>
      </div>

      <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
        Belum ada notifikasi
      </div>

      <button
        v-for="notif in notifications"
        :key="notif.uuid"
        @click="handleClick(notif)"
        class="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors flex gap-2"
        :class="!notif.read_at && 'bg-blue-50/50'"
      >
        <span
          class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0"
          :class="notif.read_at ? 'bg-transparent' : 'bg-blue-500'"
        />
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-gray-900 truncate">{{ notif.title }}</p>
          <p v-if="notif.body" class="text-xs text-gray-500 truncate">{{ notif.body }}</p>
          <p class="text-[11px] text-gray-400 mt-0.5">{{ timeAgo(notif.created_at) }}</p>
        </div>
      </button>
    </div>
  </div>
</template>
