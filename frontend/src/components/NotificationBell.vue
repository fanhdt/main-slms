<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotifications } from '@/composables/useNotifications'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import type { AppNotification } from '@/types'
import { Bell, CheckCheck } from 'lucide-vue-next'

const router = useRouter()
const {
  notifications,
  unreadCount,
  markAsRead,
  markAllAsRead,
  fetchNotifications,
  fetchUnreadCount,
} = useNotifications()
const labStore = useLabStore()

const isOpen = ref(false)
const buttonRef = ref<HTMLButtonElement | null>(null)
const panelRef = ref<HTMLElement | null>(null)

// Posisi dropdown dihitung manual (di-teleport ke body), supaya tidak
// pernah kepotong oleh overflow-hidden milik parent manapun, dan tetap
// pas menempel di bawah tombol bell baik di HP maupun desktop.
const panelStyle = reactive<{ top: string; left: string; width: string }>({
  top: '0px',
  left: '0px',
  width: '384px',
})

const PANEL_WIDTH = 384 // w-96
const VIEWPORT_MARGIN = 16 // jarak minimum ke tepi layar

function computePosition() {
  const btn = buttonRef.value
  if (!btn) return

  const rect = btn.getBoundingClientRect()
  const viewportWidth = window.innerWidth

  // Lebar dropdown menyesuaikan layar: penuh (minus margin) di HP,
  // maksimal 384px di layar lebih besar.
  const width = Math.min(PANEL_WIDTH, viewportWidth - VIEWPORT_MARGIN * 2)

  // Sejajarkan sisi kanan dropdown dengan sisi kanan tombol bell,
  // tapi jangan sampai keluar dari tepi kiri/kanan layar.
  let left = rect.right - width
  left = Math.max(VIEWPORT_MARGIN, Math.min(left, viewportWidth - width - VIEWPORT_MARGIN))

  panelStyle.top = `${rect.bottom + 8}px`
  panelStyle.left = `${left}px`
  panelStyle.width = `${width}px`
}

function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    // tunggu tick berikutnya supaya buttonRef pasti sudah ada
    requestAnimationFrame(computePosition)
  }
}

function handleClickOutside(event: MouseEvent) {
  const target = event.target as Node
  if (
    buttonRef.value &&
    !buttonRef.value.contains(target) &&
    panelRef.value &&
    !panelRef.value.contains(target)
  ) {
    isOpen.value = false
  }
}

function handleReposition() {
  if (isOpen.value) computePosition()
}

onMounted(async () => {
  await fetchUnreadCount()
  await fetchNotifications()
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('resize', handleReposition)
  window.addEventListener('scroll', handleReposition, true)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', handleReposition)
  window.removeEventListener('scroll', handleReposition, true)
})

async function handleNotifClick(notif: AppNotification) {
  if (!notif.read_at) {
    await markAsRead(notif.uuid)
  }
  isOpen.value = false

  const type = notif.type || ''
  const payload = notif.data || {}
  const projectUuid = payload.project_uuid as string | undefined
  const labSlug = labStore.activeLab?.slug

  if (type.includes('BookingCreated') || type.includes('BookingCheckedIn')) {
    if (labSlug) router.push({ name: 'lab-bookings', params: { labSlug } })
  } else if (type.includes('BookingStatusChanged') || type.includes('BookingCanceled')) {
    router.push({ name: 'my-bookings' })
  } else if (type.includes('PhotoPreviewUploaded')) {
    if (projectUuid) router.push({ name: 'photo-selection', params: { uuid: projectUuid } })
  } else if (type.includes('PhotoSelectionSubmitted')) {
    if (labSlug && projectUuid) {
      router.push({ name: 'lab-photo-project-detail', params: { labSlug, uuid: projectUuid } })
    }
  } else if (type.includes('PhotoApprovalRequested') || type.includes('PhotoDelivered')) {
    if (projectUuid) router.push({ name: 'photo-delivery', params: { uuid: projectUuid } })
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
  <div class="relative inline-block">
    <button
      ref="buttonRef"
      @click="toggle"
      class="relative p-2 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors focus:outline-none"
    >
      <Bell class="size-5" />
      <span
        v-if="unreadCount > 0"
        class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white ring-2 ring-white"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Panel di-teleport ke body + posisi fixed dihitung manual,
         jadi tidak mungkin kepotong overflow-hidden milik layout manapun. -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1"
      >
        <div
          v-if="isOpen"
          ref="panelRef"
          class="fixed bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-[100] flex flex-col"
          :style="{ top: panelStyle.top, left: panelStyle.left, width: panelStyle.width }"
        >
          <div
            class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50/50"
          >
            <h3 class="text-sm font-semibold text-gray-900">Notifikasi</h3>
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1"
            >
              <CheckCheck class="size-3.5" />
              Tandai semua dibaca
            </button>
          </div>

          <div class="max-h-[60vh] overflow-y-auto divide-y divide-gray-100">
            <div v-if="notifications.length === 0" class="p-8 text-center text-sm text-gray-500">
              Belum ada notifikasi.
            </div>

            <div
              v-for="notif in notifications"
              :key="notif.uuid"
              @click="handleNotifClick(notif)"
              class="p-4 hover:bg-gray-50 cursor-pointer transition-colors relative"
              :class="!notif.read_at ? 'bg-blue-50/30' : ''"
            >
              <div class="flex gap-3">
                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm text-gray-900"
                    :class="!notif.read_at ? 'font-semibold' : 'font-medium'"
                  >
                    {{ notif.title }}
                  </p>
                  <p class="text-xs text-gray-500 mt-0.5 line-clamp-2 leading-relaxed">
                    {{ notif.body }}
                  </p>
                  <p class="text-[10px] text-gray-400 mt-1.5 font-medium">
                    {{ timeAgo(notif.created_at) }}
                  </p>
                </div>
                <div
                  v-if="!notif.read_at"
                  class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 shrink-0"
                />
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
