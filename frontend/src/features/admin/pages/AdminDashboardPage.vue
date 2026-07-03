<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import api from '@/lib/axios'

const authStore = useAuthStore()

const { data: labs } = useQuery({
  queryKey: ['admin-labs'],
  queryFn: async () => {
    const res = await api.get('/labs')
    return res.data.data
  },
})

const { data: users } = useQuery({
  queryKey: ['admin-users'],
  queryFn: async () => {
    const res = await api.get('/users')
    return res.data.data
  },
})

const { data: assets } = useQuery({
  queryKey: ['admin-assets'],
  queryFn: async () => {
    const res = await api.get('/assets')
    return res.data.data
  },
})

const { data: bookings } = useQuery({
  queryKey: ['admin-bookings'],
  queryFn: async () => {
    const res = await api.get('/bookings')
    return res.data.data
  },
})
</script>

<template>
  <div class="space-y-6">
    <!-- Welcome -->
    <div class="bg-gray-900 rounded-2xl p-6 text-white">
      <h2 class="text-2xl font-bold">Global Admin Panel 🌐</h2>
      <p class="text-gray-400 mt-1">
        Selamat datang, {{ authStore.user?.name }}. Kamu punya akses penuh ke semua laboratorium.
      </p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total Lab</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          {{ labs?.meta?.total ?? 0 }}
        </p>
        <RouterLink
          to="/admin/labs"
          class="text-xs text-blue-600 hover:underline mt-2 inline-block"
        >
          Kelola Lab →
        </RouterLink>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total User</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          {{ users?.meta?.total ?? 0 }}
        </p>
        <RouterLink
          to="/admin/users"
          class="text-xs text-blue-600 hover:underline mt-2 inline-block"
        >
          Kelola User →
        </RouterLink>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total Aset</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          {{ assets?.meta?.total ?? 0 }}
        </p>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total Booking</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          {{ bookings?.meta?.total ?? 0 }}
        </p>
      </div>
    </div>

    <!-- Lab Cards -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-4">Semua Laboratorium</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <RouterLink
          v-for="lab in labs?.data"
          :key="lab.uuid"
          :to="`/dashboard/lab/${lab.slug}`"
          class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-colors group"
        >
          <div
            class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold flex-shrink-0"
            :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
          >
            {{ lab.name.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900 text-sm truncate group-hover:text-blue-700">
              {{ lab.name }}
            </p>
            <p class="text-xs text-gray-500">
              {{ lab.is_active ? '● Aktif' : '○ Nonaktif' }}
            </p>
          </div>
          <span class="text-gray-400 group-hover:text-blue-600 text-xs">→</span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
