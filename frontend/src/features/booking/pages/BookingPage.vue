<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { useQuery } from '@tanstack/vue-query'
import api from '@/lib/axios'

const authStore = useAuthStore()
const labStore = useLabStore()
const router = useRouter()

onMounted(async () => {
  await labStore.fetchLabs()
})

function selectLab(slug: string) {
  router.push(`/booking/${slug}`)
}

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <span class="text-xl font-bold text-gray-900">SLMS</span>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-600">
            {{ authStore.user?.name }}
          </span>
          <button @click="handleLogout" class="text-sm text-red-600 hover:text-red-700">
            Logout
          </button>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="max-w-6xl mx-auto px-6 py-12">
      <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900">Pilih Laboratorium</h1>
        <p class="text-gray-500 mt-2">Pilih laboratorium yang ingin kamu booking layanannya.</p>
      </div>

      <!-- Loading -->
      <div v-if="labStore.loading" class="text-center py-20 text-gray-500">
        Memuat laboratorium...
      </div>

      <!-- Lab Cards -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <button
          v-for="lab in labStore.labs"
          :key="lab.uuid"
          @click="selectLab(lab.slug)"
          class="group text-left bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-200"
        >
          <!-- Header -->
          <div
            class="h-36 flex items-center justify-center relative"
            :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
          >
            <span class="text-6xl font-black text-white/10">
              {{ lab.name.charAt(0) }}
            </span>
            <div class="absolute inset-0 flex items-center justify-center">
              <span class="text-3xl font-bold text-white">
                {{ lab.name.charAt(0) }}
              </span>
            </div>
            <div class="absolute top-3 right-3">
              <span
                class="text-xs px-2 py-0.5 rounded-full bg-green-500/20 text-green-200 font-medium"
              >
                Aktif
              </span>
            </div>
          </div>

          <!-- Body -->
          <div class="p-5">
            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
              {{ lab.name }}
            </h3>
            <p class="text-sm text-gray-500 mt-1 line-clamp-2">
              {{ lab.description ?? 'Tidak ada deskripsi.' }}
            </p>
            <div class="mt-4 flex items-center justify-between">
              <div class="flex gap-1.5">
                <div
                  class="w-3 h-3 rounded-full border border-gray-200"
                  :style="{ backgroundColor: lab.branding.primary_color ?? '#ccc' }"
                />
                <div
                  class="w-3 h-3 rounded-full border border-gray-200"
                  :style="{ backgroundColor: lab.branding.secondary_color ?? '#ccc' }"
                />
              </div>
              <span class="text-xs text-blue-600 font-medium group-hover:underline">
                Lihat Layanan →
              </span>
            </div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
