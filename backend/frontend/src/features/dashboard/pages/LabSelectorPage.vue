<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { LayoutDashboard, Lock, Mail, ArrowRight } from 'lucide-vue-next'

const authStore = useAuthStore()
const labStore = useLabStore()
const router = useRouter()

onMounted(async () => {
  await labStore.fetchManagedLabs()
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
        <div class="flex items-center gap-2.5">
          <div class="flex items-center gap-2.5">
            <img src="/images/logo-lab.svg" alt="Logo SLMS" class="w-7 h-7 object-contain" />
            <span class="text-lg font-bold text-gray-900">SLMS</span>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-600">
            Selamat datang,
            <span class="font-medium text-gray-900">{{ authStore.user?.name }}</span>
          </span>
          <Button v-if="authStore.hasRole('super_admin')" size="sm" @click="goToAdmin">
            <LayoutDashboard class="size-4" />
            Admin Panel
          </Button>
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
      <div v-if="labStore.loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <Skeleton v-for="i in 3" :key="i" class="h-64 w-full rounded-2xl" />
      </div>

      <!-- Empty -->
      <div v-else-if="!labStore.managedLabs.length" class="text-center py-20">
        <Lock class="size-9 mx-auto text-gray-300 mb-3" />
        <p class="font-medium text-gray-700">Belum ada akses laboratorium</p>
        <p class="text-sm text-gray-500 mt-1">Hubungi administrator untuk mendapatkan akses.</p>
      </div>

      <!-- Lab Cards Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <button
          v-for="lab in labStore.managedLabs"
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
            <div v-else class="text-4xl font-bold text-white/20">
              {{ lab.name.charAt(0) }}
            </div>

            <!-- Status badge -->
            <Badge
              variant="outline"
              class="absolute top-3 right-3 border-0"
              :class="
                lab.is_active ? 'bg-green-500/20 text-green-100' : 'bg-red-500/20 text-red-100'
              "
            >
              {{ lab.is_active ? 'Aktif' : 'Nonaktif' }}
            </Badge>
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
            <div
              v-if="lab.contact?.email"
              class="mt-3 flex items-center gap-1.5 text-xs text-gray-400"
            >
              <Mail class="size-3.5 shrink-0" />
              <span class="truncate">{{ lab.contact.email }}</span>
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
              <span
                class="text-xs text-blue-600 font-medium group-hover:underline flex items-center gap-1"
              >
                Masuk
                <ArrowRight class="size-3" />
              </span>
            </div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
