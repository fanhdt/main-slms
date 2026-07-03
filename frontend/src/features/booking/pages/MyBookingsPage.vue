<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import NotificationBell from '@/components/NotificationBell.vue'
import { useNotifications } from '@/composables/useNotifications'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import QRCode from 'qrcode'
import api from '@/lib/axios'

const router = useRouter()
const authStore = useAuthStore()
const page = ref(1)
const selectedBooking = ref<any>(null)
const qrDataUrl = ref('')
const showQR = ref(false)
const { subscribeRealtime } = useNotifications()

const { data, isLoading } = useQuery({
  queryKey: ['my-bookings', page],
  queryFn: async () => {
    const res = await api.get('/bookings/my', {
      params: { page: page.value },
    })
    return res.data.data
  },
})

onMounted(() => {
 subscribeRealtime()
})

async function openQR(booking: any) {
  selectedBooking.value = booking
  showQR.value = true
  const code = booking.booking_code ?? booking.code
  qrDataUrl.value = await QRCode.toDataURL(code, {
    width: 250,
    margin: 2,
    color: {
      dark: '#1a1a2e',
      light: '#ffffff',
    },
  })
}

function closeQR() {
  showQR.value = false
  selectedBooking.value = null
  qrDataUrl.value = ''
}

// NEW — tentukan aksi tombol foto berdasarkan status photo_project
function photoAction(booking: any): { label: string; route: string } | null {
  const status = booking.photo_project?.status?.value
  if (!status) return null

  if (status === 'preview_uploaded') {
    return { label: '📸 Pilih Foto', route: 'photo-selection' }
  }
  if (['approval', 'delivered', 'expired'].includes(status)) {
    return { label: '🖼 Lihat Hasil Foto', route: 'photo-delivery' }
  }
  // pending / selection / editing — belum ada aksi buat customer, cuma info status
  return null
}

function goToPhoto(booking: any) {
  const action = photoAction(booking)
  if (!action) return
  router.push({ name: action.route, params: { uuid: booking.photo_project.uuid } })
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
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
      <div class="max-w-3xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button @click="router.push('/booking')" class="text-gray-500 hover:text-gray-700">
            ← Kembali
          </button>
          <h1 class="font-bold text-gray-900">Booking Saya</h1>
        </div>
        <span class="text-sm text-gray-500">{{ authStore.user?.name }}</span>
      </div>
    </header>

    <div class="max-w-3xl mx-auto px-6 py-8">
      <div v-if="isLoading" class="text-center py-20 text-gray-400">Memuat booking...</div>

      <div v-else-if="!data?.data?.length" class="text-center py-20">
        <div class="text-5xl mb-4">📭</div>
        <h3 class="font-semibold text-gray-700">Belum ada booking</h3>
        <p class="text-gray-500 text-sm mt-1">Booking layanan lab favoritmu sekarang!</p>
        <button
          @click="router.push('/booking')"
          class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
        >
          Booking Sekarang
        </button>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="booking in data.data"
          :key="booking.uuid"
          class="bg-white rounded-xl border border-gray-200 p-5"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-3">
            <div>
              <p class="font-mono font-bold text-gray-900 text-lg">
                {{ booking.booking_code ?? booking.code }}
              </p>
              <p class="text-xs text-gray-400 mt-0.5">
                {{ formatDate(booking.created_at) }}
              </p>
            </div>
            <div class="flex flex-col gap-1 items-end">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="statusColor(booking.status.value)"
              >
                {{ booking.status.label }}
              </span>
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="paymentColor(booking.payment_status.value)"
              >
                {{ booking.payment_status.label }}
              </span>
            </div>
          </div>

          <!-- Detail -->
          <div class="grid grid-cols-2 gap-3 text-sm border-t border-gray-100 pt-3">
            <div>
              <p class="text-gray-400 text-xs">Mulai</p>
              <p class="font-medium text-gray-700">{{ formatDate(booking.start_time) }}</p>
            </div>
            <div>
              <p class="text-gray-400 text-xs">Selesai</p>
              <p class="font-medium text-gray-700">{{ formatDate(booking.end_time) }}</p>
            </div>
          </div>

          <!-- NEW: info status photo project, kalau ada tapi belum ada aksi customer -->
          <div
            v-if="booking.photo_project && !photoAction(booking)"
            class="mt-3 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2"
          >
            🖼 Foto: {{ booking.photo_project.status.label }}
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 gap-2">
            <span class="font-bold text-gray-900">
              {{ formatPrice(booking.total_price) }}
            </span>
            <div class="flex items-center gap-2">
              <!-- NEW -->
              <button
                v-if="photoAction(booking)"
                @click="goToPhoto(booking)"
                class="flex items-center gap-2 text-sm font-medium text-purple-600 hover:text-purple-700 px-3 py-1.5 rounded-lg hover:bg-purple-50 transition-colors"
              >
                {{ photoAction(booking)!.label }}
              </button>
              <button
                @click="openQR(booking)"
                class="flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors"
              >
                📱 Tampilkan QR
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="data?.meta" class="flex items-center justify-between text-sm text-gray-600 pt-2">
          <span>{{ data.meta.total }} booking</span>
          <div class="flex gap-2">
            <button
              :disabled="page <= 1"
              @click="page--"
              class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40"
            >
              ←
            </button>
            <button
              :disabled="page >= data.meta.last_page"
              @click="page++"
              class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40"
            >
              →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showQR" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50" @click="closeQR" />
          <div class="relative bg-white rounded-2xl p-8 text-center max-w-sm w-full shadow-xl">
            <h3 class="font-bold text-gray-900 text-lg mb-1">QR Code Booking</h3>
            <p class="text-sm text-gray-500 mb-4">Tunjukkan ke admin untuk check-in</p>

            <div class="flex justify-center mb-4">
              <div class="bg-white p-3 rounded-xl border border-gray-200 inline-block">
                <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Code" class="w-52 h-52" />
                <div v-else class="w-52 h-52 bg-gray-100 animate-pulse rounded" />
              </div>
            </div>

            <p class="font-mono font-bold text-xl text-gray-900 tracking-wider mb-1">
              {{ selectedBooking?.booking_code ?? selectedBooking?.code }}
            </p>
            <p class="text-xs text-gray-400 mb-6">
              {{ selectedBooking?.status?.label }}
            </p>

            <button
              @click="closeQR"
              class="w-full py-3 rounded-xl font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors"
            >
              Tutup
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
