<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import NotificationBell from '@/components/NotificationBell.vue'

defineProps<{
  backTo?: string // opsional — kalau halaman butuh tombol "← Kembali" custom (mis. dari dalam flow booking)
  title?: string
}>()

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between gap-4">
      <div class="flex items-center gap-4 min-w-0">
        <button
          v-if="backTo"
          @click="router.push(backTo)"
          class="text-gray-500 hover:text-gray-700 shrink-0"
        >
          ← Kembali
        </button>
        <RouterLink v-else to="/home" class="text-xl font-bold text-gray-900 shrink-0">
          SLMS
        </RouterLink>
        <span v-if="title" class="text-gray-300">/</span>
        <span v-if="title" class="font-semibold text-gray-900 truncate">{{ title }}</span>
      </div>

      <nav class="flex items-center gap-5 shrink-0">
        <RouterLink
          to="/home"
          class="text-sm font-medium text-gray-600 hover:text-gray-900"
          active-class="text-blue-600"
        >
          Beranda
        </RouterLink>
        <RouterLink
          to="/booking"
          class="text-sm font-medium text-gray-600 hover:text-gray-900"
          active-class="text-blue-600"
        >
          Booking Baru
        </RouterLink>
        <RouterLink
          to="/my-bookings"
          class="text-sm font-medium text-gray-600 hover:text-gray-900"
          active-class="text-blue-600"
        >
          Booking Saya
        </RouterLink>
        <NotificationBell />
        <RouterLink to="/profile" class="text-sm text-gray-600 hover:text-gray-900 hover:underline">
          {{ authStore.user?.name }}
        </RouterLink>
        <button @click="handleLogout" class="text-sm text-red-600 hover:text-red-700">
          Logout
        </button>
      </nav>
    </div>
  </header>
</template>
