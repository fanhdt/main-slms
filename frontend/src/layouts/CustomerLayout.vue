<script setup lang="ts">
import { ref, watch } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import NotificationBell from '@/components/NotificationBell.vue'
import { toast } from 'vue-sonner'
import {
  Home,
  CalendarPlus,
  ListChecks,
  User as UserIcon,
  LogOut,
  Menu,
  X,
  ShieldCheck,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const mobileNavOpen = ref(false)

watch(
  () => route.fullPath,
  () => {
    mobileNavOpen.value = false
  },
)

async function handleLogout() {
  await authStore.logout()
  toast.success('Logout berhasil')
  router.push({ name: 'login' })
}

const navItems = [
  { to: '/home', label: 'Beranda', icon: Home, exact: true },
  { to: '/booking', label: 'Booking Baru', icon: CalendarPlus },
  { to: '/my-bookings', label: 'Booking Saya', icon: ListChecks },
  { to: '/profile', label: 'Profil Saya', icon: UserIcon },
]

const PAGE_TITLES: Record<string, string> = {
  'user-dashboard': 'Dashboard',
  booking: 'Booking Baru',
  'booking-lab': 'Booking Baru',
  'booking-form': 'Konfirmasi Booking',
  'booking-success': 'Booking Berhasil',
  'asset-catalog': 'Sewa Alat',
  'booking-cart': 'Keranjang Sewa',
  'my-bookings': 'Booking Saya',
  'photo-selection': 'Pilih Foto',
  'photo-delivery': 'Hasil Foto',
  profile: 'Profil Saya',
}
</script>

<template>
  <div class="h-screen bg-gray-50 flex overflow-hidden">
    <!-- Backdrop mobile -->
    <Transition
      enter-active-class="transition duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileNavOpen"
        class="fixed inset-0 z-30 lg:hidden"
        style="background: rgba(0, 0, 0, 0.4)"
        @click="mobileNavOpen = false"
      />
    </Transition>

    <!-- SIDEBAR -->
    <aside
      class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0 h-screen fixed inset-y-0 left-0 z-40 transition-transform duration-200 lg:sticky lg:top-0 lg:translate-x-0"
      :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="h-16 flex items-center justify-between px-5 border-b border-gray-200 shrink-0">
        <RouterLink to="/home" class="flex items-center gap-2.5">
          <img src="/images/logo-lab.svg" alt="Logo SLMS" class="w-7 h-7 object-contain" />
          <span class="font-semibold text-sm text-gray-900">SLMS</span>
        </RouterLink>
        <button class="lg:hidden" @click="mobileNavOpen = false" aria-label="Tutup menu">
          <X :size="18" class="text-gray-500" />
        </button>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          :exact="item.exact"
          class="nav-item"
          active-class="nav-active"
        >
          <component :is="item.icon" :size="17" class="shrink-0" />
          <span class="truncate">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <!-- Banner staff -->
      <div
        v-if="authStore.hasRole('lab_admin') || authStore.hasRole('super_admin')"
        class="mx-3 mb-3 p-3 rounded-lg bg-blue-50 border border-blue-100 shrink-0"
      >
        <p class="text-xs text-blue-800 flex items-start gap-1.5">
          <ShieldCheck class="size-3.5 shrink-0 mt-0.5" />
          Kamu juga staff lab.
        </p>
        <RouterLink
          :to="authStore.hasRole('super_admin') ? '/admin' : '/dashboard'"
          class="text-xs font-medium text-blue-700 hover:underline mt-1 inline-block"
        >
          Ke Dashboard Admin →
        </RouterLink>
      </div>

      <div class="p-3 border-t border-gray-200 space-y-0.5 shrink-0">
        <RouterLink
          to="/profile"
          class="flex items-center gap-3 px-2.5 py-2 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <div
            class="w-8 h-8 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center shrink-0"
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
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-gray-500">Customer</p>
          </div>
        </RouterLink>
        <button @click="handleLogout" class="nav-item w-full text-red-600 hover:bg-red-50">
          <LogOut :size="16" class="shrink-0" />
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
      <header
        class="h-16 bg-white border-b border-gray-200 flex items-center px-4 lg:px-6 gap-3 shrink-0"
      >
        <button class="lg:hidden" @click="mobileNavOpen = true" aria-label="Buka menu">
          <Menu :size="20" class="text-gray-600" />
        </button>
        <h1 class="text-lg font-semibold text-gray-900 flex-1 truncate">
          {{ PAGE_TITLES[route.name as string] ?? 'SLMS' }}
        </h1>
        <NotificationBell />
      </header>

      <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.nav-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.55rem 0.65rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  transition:
    background-color 0.15s ease,
    color 0.15s ease;
}
.nav-item:hover {
  background: #f3f4f6;
}
.nav-active {
  background: #f3f4f6;
  font-weight: 600;
  color: #111827;
}
</style>
