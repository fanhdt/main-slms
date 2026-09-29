<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import StatusBadge from '@/components/common/StatusBadge.vue'
import api from '@/lib/axios'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { Clock, CheckCircle2, Wallet, Plus, ListChecks, Inbox } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const { data: bookings, isLoading } = useQuery({
  queryKey: ['dashboard-bookings'],
  queryFn: async () => {
    const res = await api.get('/bookings/my', { params: { per_page: 50 } })
    return res.data.data.data as any[]
  },
})

const pendingCount = computed(
  () => bookings.value?.filter((b) => b.status.value === 'pending').length ?? 0,
)
const unpaidCount = computed(
  () => bookings.value?.filter((b) => b.payment_status.value === 'unpaid').length ?? 0,
)
const activeCount = computed(
  () => bookings.value?.filter((b) => ['approved', 'ongoing'].includes(b.status.value)).length ?? 0,
)

const nextBooking = computed(() => {
  const upcoming = (bookings.value ?? [])
    .filter((b) => ['pending', 'approved'].includes(b.status.value))
    .sort((a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime())
  return upcoming[0] ?? null
})

const recentBookings = computed(() => (bookings.value ?? []).slice(0, 5))

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatPrice(price: string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}

const statCards = computed(() => [
  {
    label: 'Menunggu Persetujuan',
    value: pendingCount.value,
    icon: Clock,
    tone: 'text-amber-600 bg-amber-50',
  },
  {
    label: 'Booking Aktif',
    value: activeCount.value,
    icon: CheckCircle2,
    tone: 'text-blue-600 bg-blue-50',
  },
  {
    label: 'Belum Dibayar',
    value: unpaidCount.value,
    icon: Wallet,
    tone: unpaidCount.value > 0 ? 'text-red-600 bg-red-50' : 'text-gray-500 bg-gray-100',
  },
])
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-bold text-gray-900">
        Halo, {{ authStore.user?.name?.split(' ')[0] }} 👋
      </h2>
      <p class="text-gray-500 text-sm mt-1">Ini ringkasan aktivitas booking kamu.</p>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <template v-if="isLoading">
        <Skeleton v-for="i in 3" :key="i" class="h-[86px] w-full rounded-xl" />
      </template>
      <template v-else>
        <Card v-for="stat in statCards" :key="stat.label" class="p-0">
          <CardContent class="px-4 py-3.5 flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="text-xs text-gray-400 truncate">{{ stat.label }}</p>
              <p class="text-2xl font-bold mt-1" :class="stat.tone.split(' ')[0]">
                {{ stat.value }}
              </p>
            </div>
            <div
              class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center"
              :class="stat.tone"
            >
              <component :is="stat.icon" class="size-4.5" />
            </div>
          </CardContent>
        </Card>
      </template>
    </div>

    <!-- Booking berikutnya -->
    <Card v-if="nextBooking" class="p-0">
      <CardContent class="p-5 flex items-center justify-between gap-4">
        <div>
          <p class="text-xs text-gray-400">Booking berikutnya</p>
          <p class="font-semibold text-gray-900 mt-0.5">
            {{ nextBooking.booking_code }} &middot; {{ formatDate(nextBooking.start_time) }}
          </p>
        </div>
        <StatusBadge :status="nextBooking.status" type="booking" />
      </CardContent>
    </Card>

    <!-- Quick actions -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <button
        @click="router.push('/booking')"
        class="bg-gray-900 text-white rounded-xl p-5 text-left hover:bg-gray-800 transition-colors flex items-start gap-3"
      >
        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
          <Plus class="size-4.5" />
        </div>
        <div>
          <p class="font-semibold">Buat Booking Baru</p>
          <p class="text-sm text-white/70 mt-0.5">Pilih lab dan layanan yang kamu butuhkan.</p>
        </div>
      </button>
      <button
        @click="router.push('/my-bookings')"
        class="bg-white border border-gray-200 rounded-xl p-5 text-left hover:bg-gray-50 transition-colors flex items-start gap-3"
      >
        <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
          <ListChecks class="size-4.5 text-gray-600" />
        </div>
        <div>
          <p class="font-semibold text-gray-900">Lihat Semua Booking</p>
          <p class="text-sm text-gray-500 mt-0.5">Riwayat lengkap & status pembayaran.</p>
        </div>
      </button>
    </div>

    <!-- Aktivitas terbaru -->
    <Card class="p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <p class="font-semibold text-gray-900">Aktivitas Terbaru</p>
        <RouterLink to="/my-bookings" class="text-xs text-blue-600 font-medium hover:underline">
          Lihat semua
        </RouterLink>
      </div>

      <div v-if="isLoading" class="p-5 space-y-3">
        <Skeleton v-for="i in 3" :key="i" class="h-10 w-full" />
      </div>

      <div v-else-if="!recentBookings.length" class="p-10 text-center">
        <Inbox class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-gray-500 text-sm">Belum ada booking. Yuk mulai booking pertamamu!</p>
      </div>

      <div v-else class="divide-y divide-gray-100">
        <div
          v-for="booking in recentBookings"
          :key="booking.uuid"
          class="px-5 py-3 flex items-center justify-between text-sm"
        >
          <div class="min-w-0">
            <p class="font-medium text-gray-900 truncate">{{ booking.booking_code }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(booking.start_time) }}</p>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <span class="font-medium text-gray-700">{{ formatPrice(booking.total_price) }}</span>
            <StatusBadge :status="booking.status" type="booking" />
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>
