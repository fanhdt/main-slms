<script setup lang="ts">
import { ref } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { bookingApi } from '@/features/booking/api/bookingApi'
import type { Booking } from '@/types'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'

const search = ref('')
const page = ref(1)
const filterStatus = ref('')
const authStore = useAuthStore()
const queryClient = useQueryClient()
const showScanner = ref(false) // NEW

const { data, isLoading } = useQuery({
  queryKey: ['bookings', search, page, filterStatus],
  queryFn: async () => {
    const res = await bookingApi.getAll({
      search: search.value || undefined,
      page: page.value,
      status: filterStatus.value || undefined,
    })
    return res.data.data
  },
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
  // NEW
  queryClient.invalidateQueries({ queryKey: ['bookings'] })
}

function statusColor(status: string) {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-blue-100 text-blue-700',
    ongoing: 'bg-purple-100 text-purple-700',
    completed: 'bg-green-100 text-green-700',
    canceled: 'bg-red-100 text-red-700',
    rejected: 'bg-gray-100 text-gray-600',
  }
  return colors[status] ?? 'bg-gray-100 text-gray-600'
}

function paymentColor(status: string) {
  const colors: Record<string, string> = {
    unpaid: 'bg-red-100 text-red-700',
    partial: 'bg-yellow-100 text-yellow-700',
    paid: 'bg-green-100 text-green-700',
    refunded: 'bg-gray-100 text-gray-600',
  }
  return colors[status] ?? 'bg-gray-100 text-gray-600'
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
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Booking</h2>
        <p class="text-gray-500 mt-1 text-sm">Kelola semua booking laboratorium.</p>
      </div>
      <!-- NEW -->
      <button
        v-if="canManage()"
        @click="showScanner = true"
        class="px-4 py-2 rounded-xl bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition-colors flex items-center gap-2"
      >
        📷 Scan QR Check-in
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Cari kode booking..."
        class="flex-1 min-w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="page = 1"
      />
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
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>

      <div v-else-if="!data?.data?.length" class="p-8 text-center text-gray-500 text-sm">
        Tidak ada booking ditemukan.
      </div>

      <table v-else class="w-full text-sm">
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
              <div class="font-medium text-gray-900">{{ booking.user?.name }}</div>
              <div class="text-xs text-gray-500">{{ booking.user?.email }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="text-gray-900">{{ formatDate(booking.start_time) }}</div>
              <div class="text-xs text-gray-500">s/d {{ formatDate(booking.end_time) }}</div>
            </td>
            <td class="px-4 py-3 font-medium text-gray-900">
              {{ formatPrice(booking.total_price) }}
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="statusColor(booking.status.value)"
              >
                {{ booking.status.label }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="paymentColor(booking.payment_status.value)"
              >
                {{ booking.payment_status.label }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <template v-if="canManage()">
                  <button
                    v-if="booking.status.value === 'pending'"
                    @click="updateStatus({ uuid: booking.uuid, status: 'approved' })"
                    class="text-xs text-green-600 hover:underline"
                  >
                    Approve
                  </button>
                  <button
                    v-if="booking.status.value === 'pending'"
                    @click="updateStatus({ uuid: booking.uuid, status: 'rejected' })"
                    class="text-xs text-red-600 hover:underline"
                  >
                    Reject
                  </button>
                  <!-- Check-in manual dihapus, sekarang lewat tombol Scan QR di atas -->
                  <button
                    v-if="booking.status.value === 'ongoing'"
                    @click="updateStatus({ uuid: booking.uuid, status: 'completed' })"
                    class="text-xs text-purple-600 hover:underline"
                  >
                    Selesai
                  </button>
                </template>
                <span v-else class="text-xs text-gray-400">
                  {{ booking.status.label }}
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="data?.meta"
        class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600"
      >
        <span>
          Menampilkan {{ data.meta.from }}–{{ data.meta.to }} dari {{ data.meta.total }} data
        </span>
        <div class="flex gap-2">
          <button
            :disabled="page <= 1"
            @click="page--"
            class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-50"
          >
            ←
          </button>
          <button
            :disabled="page >= data.meta.last_page"
            @click="page++"
            class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-50"
          >
            →
          </button>
        </div>
      </div>
    </div>

    <!-- NEW -->
    <QrCheckinModal :show="showScanner" @close="showScanner = false" @success="onCheckinSuccess" />
  </div>
</template>
