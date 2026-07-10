<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { bookingApi } from '../api/bookingApi'
import { useNotifications } from '@/composables/useNotifications'
import { toast } from 'vue-sonner'
import QRCode from 'qrcode'
import api from '@/lib/axios'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Inbox, Images, Smartphone, Wallet, X } from 'lucide-vue-next'

declare const snap: any
const router = useRouter()
const queryClient = useQueryClient()
const page = ref(1)
const selectedBooking = ref<any>(null)
const qrDataUrl = ref('')
const showQR = ref(false)
const { subscribeRealtime } = useNotifications()
const activeTab = ref<'all' | 'pending' | 'active' | 'completed' | 'canceled'>('all')
const payingBookingUuid = ref<string | null>(null)

const TAB_STATUS_MAP: Record<string, string> = {
  pending: 'pending',
  active: 'approved,ongoing',
  completed: 'completed',
  canceled: 'canceled,rejected',
}

const { data, isLoading } = useQuery({
  queryKey: ['my-bookings', page, activeTab],
  queryFn: async () => {
    const res = await api.get('/bookings/my', {
      params: {
        page: page.value,
        status: activeTab.value === 'all' ? undefined : TAB_STATUS_MAP[activeTab.value],
      },
    })
    return res.data.data
  },
})

watch(activeTab, () => {
  page.value = 1
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
    color: { dark: '#1a1a2e', light: '#ffffff' },
  })
}

function closeQR() {
  showQR.value = false
  selectedBooking.value = null
  qrDataUrl.value = ''
}

function photoAction(booking: any): { label: string; route: string } | null {
  const status = booking.photo_project?.status?.value
  if (!status) return null
  if (status === 'preview_uploaded') {
    return { label: 'Pilih Foto', route: 'photo-selection' }
  }
  if (['approval', 'delivered', 'expired'].includes(status)) {
    return { label: 'Lihat Hasil Foto', route: 'photo-delivery' }
  }
  return null
}

function goToPhoto(booking: any) {
  const action = photoAction(booking)
  if (!action) return
  router.push({ name: action.route, params: { uuid: booking.photo_project.uuid } })
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

async function payNow(booking: any) {
  if (payingBookingUuid.value) return
  payingBookingUuid.value = booking.uuid
  try {
    const res = await api.post(`/bookings/${booking.uuid}/pay`)
    const { snap_token } = res.data.data
    snap.pay(snap_token, {
      onSuccess: () => {
        toast.success('Pembayaran berhasil! Menunggu konfirmasi...')
        pollBookingStatus(booking.uuid)
      },
      onPending: () => {
        toast.info('Menunggu pembayaran kamu diselesaikan.')
        payingBookingUuid.value = null
      },
      onError: () => {
        toast.error('Pembayaran gagal.')
        payingBookingUuid.value = null
      },
      onClose: () => {
        payingBookingUuid.value = null
      },
    })
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal memulai pembayaran.')
    payingBookingUuid.value = null
  }
}

function pollBookingStatus(uuid: string, attempt = 1) {
  const maxAttempts = 6
  const delayMs = 2000
  setTimeout(async () => {
    await queryClient.invalidateQueries({ queryKey: ['my-bookings'] })
    const fresh = queryClient
      .getQueryData<any>(['my-bookings', page.value])
      ?.data?.find((b: any) => b.uuid === uuid)
    if (fresh?.payment_status?.value === 'paid') {
      toast.success('Status booking sudah diperbarui menjadi lunas ✅')
      payingBookingUuid.value = null
      return
    }
    if (attempt < maxAttempts) {
      pollBookingStatus(uuid, attempt + 1)
    } else {
      toast.info('Status belum berubah — coba refresh halaman sebentar lagi.')
      payingBookingUuid.value = null
    }
  }, delayMs)
}

const TAB_LABELS: Record<string, string> = {
  all: 'Semua',
  pending: 'Menunggu',
  active: 'Aktif',
  completed: 'Selesai',
  canceled: 'Dibatalkan',
}

const { mutate: cancelBooking, isPending: isCanceling } = useMutation({
  mutationFn: (uuid: string) => bookingApi.cancel(uuid),
  onSuccess: () => {
    toast.success('Booking berhasil dibatalkan.')
    queryClient.invalidateQueries({ queryKey: ['my-bookings'] })
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal membatalkan booking.')
  },
})

function confirmCancel(booking: any) {
  if (confirm(`Batalkan booking ${booking.booking_code}?`)) {
    cancelBooking(booking.uuid)
  }
}

function canCancel(booking: any) {
  return ['pending', 'approved'].includes(booking.status.value)
}
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-5">
    <!-- Tab filter status -->
    <div class="flex gap-2 overflow-x-auto pb-1">
      <button
        v-for="tab in ['all', 'pending', 'active', 'completed', 'canceled']"
        :key="tab"
        @click="activeTab = tab as any"
        class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors"
        :class="
          activeTab === tab
            ? 'bg-gray-900 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
        "
      >
        {{ TAB_LABELS[tab] }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-3">
      <Skeleton v-for="i in 4" :key="i" class="h-40 w-full rounded-xl" />
    </div>

    <!-- Empty -->
    <Card v-else-if="!data?.data?.length" class="p-0">
      <CardContent class="p-12 text-center">
        <Inbox class="size-10 mx-auto text-gray-300 mb-3" />
        <h3 class="font-semibold text-gray-700">
          {{ activeTab === 'all' ? 'Belum ada booking' : 'Tidak ada booking di kategori ini' }}
        </h3>
        <p class="text-gray-500 text-sm mt-1">Booking layanan lab favoritmu sekarang!</p>
        <Button v-if="activeTab === 'all'" size="sm" class="mt-4" @click="router.push('/booking')">
          Booking Sekarang
        </Button>
      </CardContent>
    </Card>

    <!-- Booking list -->
    <Card v-for="booking in data?.data ?? []" :key="booking.uuid" class="p-0">
      <CardContent class="p-5 space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <p class="font-mono font-bold text-gray-900 text-lg">
              {{ booking.booking_code ?? booking.code }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">
              {{ formatDate(booking.created_at) }}
            </p>
          </div>
          <div class="flex flex-col gap-1 items-end">
            <StatusBadge :status="booking.status" type="booking" />
            <StatusBadge :status="booking.payment_status" type="payment" />
            <button
              v-if="canCancel(booking)"
              @click="confirmCancel(booking)"
              :disabled="isCanceling"
              class="text-xs text-red-600 hover:underline mt-1 disabled:opacity-50"
            >
              Batalkan
            </button>
          </div>
        </div>

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

        <div
          v-if="booking.photo_project && !photoAction(booking)"
          class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2 flex items-center gap-1.5"
        >
          <Images class="size-3.5" />
          Foto: {{ booking.photo_project.status.label }}
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-gray-100 gap-2">
          <span class="font-bold text-gray-900">
            {{ formatPrice(booking.total_price) }}
          </span>
          <div class="flex items-center gap-2">
            <button
              v-if="photoAction(booking)"
              @click="goToPhoto(booking)"
              class="flex items-center gap-1.5 text-sm font-medium text-purple-600 hover:text-purple-700 px-3 py-1.5 rounded-lg hover:bg-purple-50 transition-colors"
            >
              <Images class="size-4" />
              {{ photoAction(booking)!.label }}
            </button>

            <button
              v-if="booking.payment_status.value === 'paid'"
              @click="openQR(booking)"
              class="flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors"
            >
              <Smartphone class="size-4" />
              Tampilkan QR
            </button>

            <button
              v-if="booking.payment_status.value === 'unpaid'"
              @click="payNow(booking)"
              :disabled="payingBookingUuid === booking.uuid"
              class="flex items-center gap-1.5 text-sm font-medium text-green-600 hover:text-green-700 px-3 py-1.5 rounded-lg hover:bg-green-50 disabled:opacity-50 transition-colors"
            >
              <Wallet class="size-4" />
              {{ payingBookingUuid === booking.uuid ? 'Memproses...' : 'Bayar Sekarang' }}
            </button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Pagination -->
    <div v-if="data?.meta" class="flex items-center justify-between text-sm text-gray-600 pt-2">
      <span>{{ data.meta.total }} booking</span>
      <div class="flex gap-2">
        <Button variant="outline" size="icon-sm" :disabled="page <= 1" @click="page--">←</Button>
        <Button
          variant="outline"
          size="icon-sm"
          :disabled="page >= data.meta.last_page"
          @click="page++"
          >→</Button
        >
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
        <Card class="relative w-full max-w-sm p-0">
          <CardContent class="p-8 text-center">
            <h3 class="font-bold text-gray-900 text-lg mb-1">QR Code Booking</h3>
            <p class="text-sm text-gray-500 mb-4">Tunjukkan ke admin untuk check-in</p>
            <div class="flex justify-center mb-4">
              <div class="bg-white p-3 rounded-xl border border-gray-200 inline-block">
                <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Code" class="w-52 h-52" />
                <Skeleton v-else class="w-52 h-52 rounded" />
              </div>
            </div>
            <p class="font-mono font-bold text-xl text-gray-900 tracking-wider mb-1">
              {{ selectedBooking?.booking_code ?? selectedBooking?.code }}
            </p>
            <p class="text-xs text-gray-400 mb-6">{{ selectedBooking?.status?.label }}</p>
            <Button variant="outline" class="w-full" @click="closeQR">
              <X class="size-4" />
              Tutup
            </Button>
          </CardContent>
        </Card>
      </div>
    </Transition>
  </Teleport>
</template>
