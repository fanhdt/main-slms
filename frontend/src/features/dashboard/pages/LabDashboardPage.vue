<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { useBranding } from '@/composables/useBranding'
import api from '@/lib/axios'

const route = useRoute()
const authStore = useAuthStore()
const labStore = useLabStore()
const { primaryBgStyle } = useBranding()

const labSlug = computed(() => route.params.labSlug as string)

const { data: bookings } = useQuery({
  queryKey: ['lab-bookings', labSlug],
  queryFn: async () => {
    const lab = labStore.activeLab
    if (!lab) return null
    const res = await api.get('/bookings', {
      params: { lab_id: lab.id },
    })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab),
})

const { data: assets } = useQuery({
  queryKey: ['lab-assets', labSlug],
  queryFn: async () => {
    const lab = labStore.activeLab
    if (!lab) return null
    const res = await api.get('/assets', {
      params: { lab_id: lab.id },
    })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab),
})
</script>

<template>
  <div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="rounded-2xl p-6 text-white" :style="primaryBgStyle">
      <h2 class="text-2xl font-bold">Selamat datang, {{ authStore.user?.name }} 👋</h2>
      <p class="text-white/70 mt-1">
        {{ labStore.activeLab?.name }} — {{ labStore.activeLab?.description }}
      </p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total Booking</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          {{ bookings?.meta?.total ?? 0 }}
        </p>
        <RouterLink
          :to="`/dashboard/lab/${labSlug}/bookings`"
          class="text-xs text-blue-600 hover:underline mt-2 inline-block"
        >
          Lihat semua →
        </RouterLink>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total Aset</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          {{ assets?.meta?.total ?? 0 }}
        </p>
        <RouterLink
          :to="`/dashboard/lab/${labSlug}/assets`"
          class="text-xs text-blue-600 hover:underline mt-2 inline-block"
        >
          Lihat semua →
        </RouterLink>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Lab</p>
        <p class="text-lg font-bold text-gray-900 mt-1 truncate">
          {{ labStore.activeLab?.name }}
        </p>
        <div class="flex gap-1.5 mt-2">
          <div
            class="w-3 h-3 rounded-full"
            :style="{ backgroundColor: labStore.activeLab?.branding.primary_color ?? '#ccc' }"
          />
          <div
            class="w-3 h-3 rounded-full"
            :style="{ backgroundColor: labStore.activeLab?.branding.secondary_color ?? '#ccc' }"
          />
        </div>
      </div>
    </div>

    <!-- Lab Info -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-4">Informasi Lab</h3>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-gray-500">Email</p>
          <p class="font-medium text-gray-900">
            {{ labStore.activeLab?.contact?.email ?? '-' }}
          </p>
        </div>
        <div>
          <p class="text-gray-500">Telepon</p>
          <p class="font-medium text-gray-900">
            {{ labStore.activeLab?.contact?.phone ?? '-' }}
          </p>
        </div>
        <div class="col-span-2">
          <p class="text-gray-500">Alamat</p>
          <p class="font-medium text-gray-900">
            {{ labStore.activeLab?.contact?.address ?? '-' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
