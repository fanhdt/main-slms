<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { useBranding } from '@/composables/useBranding'
import { useNotifications } from '@/composables/useNotifications'
import NotificationBell from '@/components/NotificationBell.vue'
import { toast } from 'vue-sonner'

const authStore = useAuthStore()
const labStore = useLabStore()
const router = useRouter()
const route = useRoute()
const { primaryBgStyle, primaryTextStyle, labName } = useBranding()

const labSlug = computed(() => route.params.labSlug as string)

onMounted(async () => {
  await labStore.setActiveLab(labSlug.value)
  useNotifications().subscribeRealtime()
})

async function handleLogout() {
  await authStore.logout()
  toast.success('Logout berhasil')
  router.push({ name: 'login' })
}

function navLink(path: string) {
  return `/dashboard/lab/${labSlug.value}/${path}`
}
const backLink = computed(() => {
  if (authStore.hasRole('super_admin')) return '/admin'
  return '/dashboard'
})

const showBackButton = computed(() => {
  return authStore.hasRole('super_admin') || authStore.hasRole('lab_admin')
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
      <!-- Header Lab -->
      <div
        class="h-16 flex items-center px-6 border-b border-gray-200 gap-3"
        :style="primaryBgStyle"
      >
        <div class="flex-1 min-w-0">
          <p class="text-white font-bold text-sm truncate">{{ labName }}</p>
          <p class="text-white/60 text-xs">Lab Dashboard</p>
        </div>
        <RouterLink
          v-if="showBackButton"
          :to="backLink"
          class="text-white/60 hover:text-white text-xs transition-colors shrink-0"
          :title="authStore.hasRole('super_admin') ? 'Global Panel' : 'Ganti Lab'"
        >
          {{ authStore.hasRole('super_admin') ? '⚙' : '⇄' }}
        </RouterLink>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-4 py-4 space-y-1">
        <RouterLink
          :to="`/dashboard/lab/${labSlug}`"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          active-class="bg-gray-100 font-semibold"
          :exact="true"
        >
          🏠 Dashboard
        </RouterLink>

        <RouterLink
          :to="navLink('bookings')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          active-class="bg-gray-100 font-semibold"
        >
          📅 Booking
        </RouterLink>

        <RouterLink
          :to="navLink('assets')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          active-class="bg-gray-100 font-semibold"
        >
          📦 Aset
        </RouterLink>

        <RouterLink
          :to="navLink('users')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          active-class="bg-gray-100 font-semibold"
        >
          👥 Pengguna
        </RouterLink>

        <RouterLink
          :to="navLink('services')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          active-class="bg-gray-100 font-semibold"
        >
          🛎 Layanan
        </RouterLink>

        <RouterLink
          :to="navLink('packages')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          active-class="bg-gray-100 font-semibold"
        >
          📋 Paket
        </RouterLink>
        <RouterLink
          :to="navLink('scan')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          active-class="bg-gray-100 font-semibold"
        >
          📷 Scan QR
        </RouterLink>
        <RouterLink
          :to="navLink('photo-projects')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          active-class="bg-gray-100 font-semibold"
        >
          🖼 Photo Delivery
        </RouterLink>
      </nav>

      <!-- User Info -->
      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3 mb-3">
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
            :style="primaryBgStyle"
          >
            <span class="text-xs font-medium text-white">
              {{ authStore.user?.name?.charAt(0).toUpperCase() }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">
              {{ authStore.user?.name }}
            </p>
            <p class="text-xs text-gray-500 truncate capitalize">
              {{ authStore.user?.roles[0]?.replace('_', ' ') }}
            </p>
          </div>
        </div>
        <button
          @click="handleLogout"
          class="w-full text-left text-sm text-red-600 hover:text-red-700 px-3 py-2 rounded-lg hover:bg-red-50 transition-colors"
        >
          Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 gap-4">
        <h1 class="text-lg font-semibold text-gray-900 flex-1">
          {{ labName }}
        </h1>
        <NotificationBell />
        <span
          class="text-sm font-medium px-3 py-1 rounded-full bg-gray-100"
          :style="primaryTextStyle"
        >
          {{ authStore.user?.roles[0]?.replace('_', ' ') }}
        </span>
      </header>

      <main class="flex-1 p-6 overflow-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>
