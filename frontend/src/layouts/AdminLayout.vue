<script setup lang="ts">
import { RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { onMounted } from 'vue'
import { toast } from 'vue-sonner'

const authStore = useAuthStore()
const labStore = useLabStore()
const router = useRouter()

onMounted(async () => {
  await labStore.fetchLabs()
})

async function handleLogout() {
  await authStore.logout()
  toast.success('Logout berhasil')
  router.push({ name: 'login' })
}

function enterLab(slug: string) {
  router.push(`/dashboard/lab/${slug}`)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">
    <aside class="w-64 bg-gray-900 flex flex-col">
      <!-- Logo -->
      <div class="h-16 flex items-center px-6 border-b border-gray-700">
        <span class="text-xl font-bold text-white">SLMS</span>
        <span class="text-xs text-gray-400 ml-2">Super Admin</span>
      </div>

      <!-- Global Menu -->
      <nav class="px-4 py-4 space-y-1">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 mb-2">Global</p>
        <RouterLink
          to="/admin"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors"
          active-class="bg-gray-800 text-white"
          :exact="true"
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
      </nav>

      <!-- Lab Shortcuts -->
      <div class="px-4 py-2 border-t border-gray-700 flex-1">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 mb-2 mt-2">
          Masuk ke Lab
        </p>
        <div class="space-y-1">
          <button
            v-for="lab in labStore.labs"
            :key="lab.uuid"
            @click="enterLab(lab.slug)"
            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors text-left"
          >
            <div
              class="w-4 h-4 rounded-full flex-shrink-0"
              :style="{ backgroundColor: lab.branding.primary_color ?? '#ccc' }"
            />
            <span class="truncate">{{ lab.name }}</span>
          </button>
        </div>
      </div>

      <!-- User Info -->
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
