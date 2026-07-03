<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'

const authStore = useAuthStore()
const labStore = useLabStore()
const router = useRouter()

onMounted(async () => {
  await labStore.fetchLabs()
})

function enterLab(slug: string) {
  router.push(`/dashboard/lab/${slug}`)
}

function goToAdmin() {
  router.push('/admin')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <span class="text-xl font-bold text-gray-900">SLMS</span>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-600">
            Selamat datang, <span class="font-medium">{{ authStore.user?.name }}</span>
          </span>
          <button
            v-if="authStore.hasRole('super_admin')"
            @click="goToAdmin"
            class="text-sm bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors"
          >
            Admin Panel
          </button>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="max-w-6xl mx-auto px-6 py-12">
      <!-- Title -->
      <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900">Pilih Laboratorium</h1>
        <p class="text-gray-500 mt-2">Pilih laboratorium yang ingin kamu kelola atau akses.</p>
      </div>

      <!-- Loading -->
      <div v-if="labStore.loading" class="text-center text-gray-500 py-20">
        <div class="text-4xl mb-4">⏳</div>
        <p>Memuat laboratorium...</p>
      </div>

      <!-- Empty -->
      <div v-else-if="!labStore.labs.length" class="text-center text-gray-500 py-20">
        <div class="text-4xl mb-4">🔒</div>
        <p class="font-medium text-gray-700">Belum ada akses laboratorium</p>
        <p class="text-sm mt-1">Hubungi administrator untuk mendapatkan akses.</p>
      </div>

      <!-- Lab Cards Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <button
          v-for="lab in labStore.labs"
          :key="lab.uuid"
          @click="enterLab(lab.slug)"
          class="group text-left bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-200"
        >
          <!-- Card Header dengan warna branding -->
          <div
            class="h-32 flex items-center justify-center relative overflow-hidden"
            :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
          >
            <!-- Logo atau initial -->
            <div v-if="lab.branding.logo">
              <img :src="lab.branding.logo" class="h-16 object-contain" />
            </div>
            <div v-else class="text-center">
              <div class="text-4xl font-bold text-white/20">
                {{ lab.name.charAt(0) }}
              </div>
            </div>

            <!-- Status badge -->
            <div class="absolute top-3 right-3">
              <span
                class="text-xs px-2 py-0.5 rounded-full font-medium"
                :class="
                  lab.is_active ? 'bg-green-500/20 text-green-200' : 'bg-red-500/20 text-red-200'
                "
              >
                {{ lab.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
          </div>

          <!-- Card Body -->
          <div class="p-5">
            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
              {{ lab.name }}
            </h3>
            <p class="text-sm text-gray-500 mt-1 line-clamp-2">
              {{ lab.description ?? 'Tidak ada deskripsi.' }}
            </p>

            <!-- Contact info -->
            <div v-if="lab.contact?.email" class="mt-3 text-xs text-gray-400">
              📧 {{ lab.contact.email }}
            </div>

            <!-- Color dots -->
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
              <span class="text-xs text-blue-600 font-medium group-hover:underline"> Masuk → </span>
            </div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
