<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { bookingApi } from '@/features/booking/api/bookingApi'
import StatusBadge from '@/components/common/StatusBadge.vue'
import type { Booking } from '@/types'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Search, QrCode, CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const search = ref('')
const page = ref(1)
const filterStatus = ref('')
const authStore = useAuthStore()
const labStore = useLabStore()
const queryClient = useQueryClient()
const showScanner = ref(false)

const { data, isLoading } = useQuery({
  queryKey: ['bookings', labStore.activeLab?.id, search, page, filterStatus],
  queryFn: async () => {
    const res = await bookingApi.getAll({
      lab_id: labStore.activeLab?.id,
      search: search.value || undefined,
      page: page.value,
      status: filterStatus.value || undefined,
    })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
})

const { mutate: updateStatus } = useMutation({
  mutationFn: ({ uuid, status }: { uuid: string; status: string }) =>
    bookingApi.updateStatus(uuid, status),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['bookings'] })
    toast.success('Status booking berhasil diupdate.')
  },
  onError: () => {
    toast.error('Gagal mengupdate status.')
  },
})

function canManage() {
  return (
    authStore.hasRole('super_admin') ||
    authStore.hasRole('lab_admin') ||
    authStore.hasRole('operator')
  )
}

function onCheckinSuccess() {
  queryClient.invalidateQueries({ queryKey: ['bookings'] })
}

function formatPrice(price: string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Booking</h1>
        <p class="text-sm text-gray-500 mt-0.5">Kelola semua booking laboratorium.</p>
      </div>
      <Button v-if="canManage()" size="sm" @click="showScanner = true">
        <QrCode class="size-4" />
        Scan QR Check-in
      </Button>
    </div>

    <!-- Filters -->
    <Card class="p-0">
      <CardContent class="p-4 flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-48">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400" />
          <input
            v-model="search"
            type="text"
            placeholder="Cari kode booking..."
            class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="page = 1"
          />
        </div>
        <select
          v-model="filterStatus"
          class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          @change="page = 1"
        >
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="ongoing">Ongoing</option>
          <option value="completed">Completed</option>
          <option value="canceled">Canceled</option>
          <option value="rejected">Rejected</option>
        </select>
      </CardContent>
    </Card>

    <!-- Table -->
    <Card class="p-0 overflow-hidden">
      <div v-if="isLoading" class="p-5 space-y-3">
        <Skeleton v-for="i in 5" :key="i" class="h-12 w-full" />
      </div>

      <div v-else-if="!data?.data?.length" class="p-10 text-center">
        <CalendarDays class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">Tidak ada booking ditemukan.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Kode</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Customer</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Waktu</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Total</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Pembayaran</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="booking in data.data"
              :key="booking.uuid"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-4 py-3">
                <span class="font-mono text-xs font-medium text-gray-900">
                  {{ booking.booking_code }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 truncate">{{ booking.user?.name }}</div>
                <div class="text-xs text-gray-500 truncate">{{ booking.user?.email }}</div>
              </td>
              <td class="px-4 py-3">
                <div class="text-gray-900">{{ formatDate(booking.start_time) }}</div>
                <div class="text-xs text-gray-500">s/d {{ formatDate(booking.end_time) }}</div>
              </td>
              <td class="px-4 py-3 font-medium text-gray-900">
                {{ formatPrice(booking.total_price) }}
              </td>
              <td class="px-4 py-3">
                <StatusBadge :status="booking.status" type="booking" />
              </td>
              <td class="px-4 py-3">
                <StatusBadge :status="booking.payment_status" type="payment" />
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-2">
                  <template v-if="canManage()">
                    <button
                      v-if="booking.status.value === 'pending'"
                      @click="updateStatus({ uuid: booking.uuid, status: 'approved' })"
                      class="text-xs font-medium text-green-600 hover:underline"
                    >
                      Approve
                    </button>
                    <button
                      v-if="booking.status.value === 'pending'"
                      @click="updateStatus({ uuid: booking.uuid, status: 'rejected' })"
                      class="text-xs font-medium text-red-600 hover:underline"
                    >
                      Reject
                    </button>
                    <button
                      v-if="booking.status.value === 'ongoing'"
                      @click="updateStatus({ uuid: booking.uuid, status: 'completed' })"
                      class="text-xs font-medium text-purple-600 hover:underline"
                    >
                      Selesai
                    </button>
                  </template>
                  <span v-else class="text-xs text-gray-400">{{ booking.status.label }}</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="data?.meta"
        class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600"
      >
        <span>
          Menampilkan {{ data.meta.from }}–{{ data.meta.to }} dari {{ data.meta.total }} data
        </span>
        <div class="flex gap-2">
          <Button variant="outline" size="icon-sm" :disabled="page <= 1" @click="page--">
            <ChevronLeft class="size-4" />
          </Button>
          <Button
            variant="outline"
            size="icon-sm"
            :disabled="page >= data.meta.last_page"
            @click="page++"
          >
            <ChevronRight class="size-4" />
          </Button>
        </div>
      </div>
    </Card>

    <QrCheckinModal :show="showScanner" @close="showScanner = false" @success="onCheckinSuccess" />
  </div>
</template>
