<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import NotificationBell from '@/components/NotificationBell.vue'
import { ArrowLeft, LogOut } from 'lucide-vue-next'

defineProps<{
  backTo?: string
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
      <div class="flex items-center gap-3 min-w-0">
        <button
          v-if="backTo"
          @click="router.push(backTo)"
          class="text-gray-500 hover:text-gray-700 shrink-0 flex items-center gap-1.5 text-sm font-medium"
        >
          <ArrowLeft class="size-4" />
          Kembali
        </button>
        <RouterLink v-else to="/home" class="flex items-center gap-2 shrink-0">
          <div
            class="w-7 h-7 rounded-lg bg-gray-900 flex items-center justify-center text-xs font-bold text-white"
          >
            S
          </div>
          <span class="text-lg font-bold text-gray-900">SLMS</span>
        </RouterLink>
        <template v-if="title">
          <span class="text-gray-300">/</span>
          <span class="font-semibold text-gray-900 truncate">{{ title }}</span>
        </template>
      </div>

      <nav class="flex items-center gap-1 shrink-0">
        <RouterLink to="/home" class="nav-link" active-class="nav-link-active">
          Beranda
        </RouterLink>
        <RouterLink to="/booking" class="nav-link" active-class="nav-link-active">
          Booking Baru
        </RouterLink>
        <RouterLink to="/my-bookings" class="nav-link" active-class="nav-link-active">
          Booking Saya
        </RouterLink>

        <div class="w-px h-5 bg-gray-200 mx-1.5" />

        <NotificationBell />

        <RouterLink
          to="/profile"
          class="flex items-center gap-2 ml-1 pl-1 pr-2.5 py-1 rounded-full hover:bg-gray-50 transition-colors"
        >
          <div
            class="w-7 h-7 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center shrink-0"
          >
            <img
              v-if="authStore.user?.avatar"
              :src="authStore.user.avatar"
              :alt="authStore.user.name"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-xs font-medium text-gray-500">
              {{ authStore.user?.name?.charAt(0).toUpperCase() }}
            </span>
          </div>
          <span class="text-sm font-medium text-gray-700 hidden sm:inline">
            {{ authStore.user?.name?.split(' ')[0] }}
          </span>
        </RouterLink>

        <button
          @click="handleLogout"
          title="Logout"
          class="p-2 rounded-full text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
        >
          <LogOut class="size-4" />
        </button>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.nav-link {
  padding: 0.4rem 0.7rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #4b5563; /* gray-600 */
  transition:
    background-color 0.15s ease,
    color 0.15s ease;
}

.nav-link:hover {
  background: #f9fafb; /* gray-50 */
  color: #111827; /* gray-900 */
}

.nav-link-active {
  background: #eff6ff; /* blue-50 */
  color: #2563eb; /* blue-600 */
}
</style>
