<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLogout } from '@/composables/useLogout'
import NotificationBell from '@/components/NotificationBell.vue'
import { ArrowLeft, LogOut, Menu, X } from 'lucide-vue-next'

defineProps<{
  backTo?: string
  title?: string
}>()

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const logout = useLogout()
const isMenuOpen = ref(false)

// Tutup menu mobile setiap pindah halaman
watch(
  () => route.fullPath,
  () => (isMenuOpen.value = false),
)

async function handleLogout() {
  await logout()
}
</script>

<template>
  <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
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

      <!-- Desktop -->
      <nav class="hidden md:flex items-center gap-1 shrink-0">
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
          <span class="text-sm font-medium text-gray-700 hidden lg:inline">
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

      <!-- Mobile: bell + hamburger -->
      <div class="flex items-center gap-1 md:hidden shrink-0">
        <NotificationBell />
        <button
          type="button"
          class="inline-flex size-10 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100"
          :aria-expanded="isMenuOpen"
          aria-label="Menu"
          @click="isMenuOpen = !isMenuOpen"
        >
          <X v-if="isMenuOpen" class="size-5" />
          <Menu v-else class="size-5" />
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <div
      v-if="isMenuOpen"
      class="md:hidden absolute inset-x-0 top-full max-h-[calc(100dvh-4rem)] overflow-y-auto border-b border-gray-200 bg-white px-4 py-3 shadow-sm"
    >
      <div class="flex flex-col gap-1">
        <RouterLink to="/home" class="mobile-link" active-class="nav-link-active"
          >Beranda</RouterLink
        >
        <RouterLink to="/booking" class="mobile-link" active-class="nav-link-active">
          Booking Baru
        </RouterLink>
        <RouterLink to="/my-bookings" class="mobile-link" active-class="nav-link-active">
          Booking Saya
        </RouterLink>
        <RouterLink to="/profile" class="mobile-link" active-class="nav-link-active">
          Profil{{ authStore.user?.name ? ` (${authStore.user.name.split(' ')[0]})` : '' }}
        </RouterLink>
        <button
          type="button"
          class="mobile-link flex items-center gap-2 text-left text-red-600"
          @click="handleLogout"
        >
          <LogOut class="size-4" />
          Logout
        </button>
      </div>
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

.mobile-link {
  display: block;
  min-height: 2.75rem;
  padding: 0.7rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #374151; /* gray-700 */
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
