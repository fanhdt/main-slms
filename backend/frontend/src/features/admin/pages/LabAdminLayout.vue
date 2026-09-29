<script setup lang="ts">
import { RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'

const authStore = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await authStore.logout()
  toast.success('Logout berhasil')
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">
    <aside class="w-64 bg-gray-900 flex flex-col">
      <div class="h-16 flex items-center gap-2.5 px-6 border-b border-gray-700">
        <img src="/images/logo-lab.svg" alt="Logo SLMS" class="w-7 h-7 object-contain shrink-0" />
        <span class="h-5 w-px bg-gray-600 shrink-0" aria-hidden="true" />
        <img
          src="/images/logo-upi.svg"
          alt="Logo UPI"
          class="h-6 w-auto max-w-16 object-contain shrink-0 brightness-0 invert"
        />
        <span class="text-xs text-gray-400 ml-1">Super Admin</span>
      </div>

      <nav class="flex-1 px-4 py-4 space-y-1">
        <RouterLink
          to="/admin"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors"
          active-class="bg-gray-800 text-white"
        >
          🏠 Dashboard
        </RouterLink>
        <RouterLink
          to="/admin/labs"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors"
          active-class="bg-gray-800 text-white"
        >
          🏛 Laboratorium
        </RouterLink>
        <RouterLink
          to="/admin/users"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors"
          active-class="bg-gray-800 text-white"
        >
          👥 Pengguna
        </RouterLink>
        <RouterLink
          to="/dashboard"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors"
        >
          🔀 Masuk ke Lab
        </RouterLink>
      </nav>

      <div class="p-4 border-t border-gray-700">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center">
            <span class="text-xs font-medium text-white">
              {{ authStore.user?.name?.charAt(0).toUpperCase() }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">
              {{ authStore.user?.name }}
            </p>
            <p class="text-xs text-gray-400">Super Admin</p>
          </div>
        </div>
        <button
          @click="handleLogout"
          class="w-full text-left text-sm text-red-400 hover:text-red-300 px-3 py-2 rounded-lg hover:bg-gray-800 transition-colors"
        >
          Logout
        </button>
      </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6">
        <h1 class="text-lg font-semibold text-gray-900">Global Admin Panel</h1>
      </header>
      <main class="flex-1 p-6 overflow-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>
