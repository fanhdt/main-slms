<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useBookingFlowMode } from '@/composables/useBookingFlowMode'
import QRCode from 'qrcode'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Check,
  Clock,
  Wallet,
  RotateCcw,
  ListChecks,
  Home,
  LayoutDashboard,
  Download,
} from 'lucide-vue-next'

declare const snap: any

const route = useRoute()
const router = useRouter()
const { isStaffMode } = useBookingFlowMode()

const slug = computed(() => route.params.slug as string)
const code = computed(() => route.params.code as string)
const qrDataUrl = ref('')
const isPaying = ref(false)

const { data: lab } = useQuery({
  queryKey: ['success-lab', slug],
  queryFn: async () => {
    const res = await api.get(`/labs/${slug.value}`)
    return res.data.data
  },
})

const { data: booking, refetch: refetchBooking } = useQuery({
  queryKey: ['success-booking', code],
  queryFn: async () => {
    const res = await api.get('/bookings/my', { params: { search: code.value, per_page: 1 } })
    return res.data.data.data?.[0] ?? null
  },
})

const isPaid = computed(() => booking.value?.payment_status?.value === 'paid')

watch(
  isPaid,
  async (paid) => {
    if (paid && !qrDataUrl.value) {
      qrDataUrl.value = await QRCode.toDataURL(code.value, {
        width: 200,
        margin: 2,
        color: { dark: '#1a1a2e', light: '#ffffff' },
      })
    }
  },
  { immediate: true },
)

async function payNow() {
  if (!booking.value) return
  isPaying.value = true
  try {
    const res = await api.post(`/bookings/${booking.value.uuid}/pay`)
    const { snap_token } = res.data.data

    snap.pay(snap_token, {
      onSuccess: async () => {
        toast.success('Pembayaran berhasil!')
        await refetchBooking()
        qrDataUrl.value = await QRCode.toDataURL(code.value, {
          width: 200,
          margin: 2,
          color: { dark: '#1a1a2e', light: '#ffffff' },
        })
      },
      onPending: () => {
        toast.info('Menunggu pembayaran kamu diselesaikan.')
      },
      onError: () => {
        toast.error('Pembayaran gagal.')
      },
    })
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal memulai pembayaran.')
  } finally {
    isPaying.value = false
  }
}

function goToBookings() {
  if (isStaffMode.value) {
    router.push({ name: 'lab-bookings', params: { labSlug: slug.value } })
  } else {
    router.push('/my-bookings')
  }
}

function goHome() {
  if (isStaffMode.value) {
    router.push({ name: 'lab-dashboard', params: { labSlug: slug.value } })
  } else {
    router.push('/')
  }
}

function downloadQR() {
  if (!qrDataUrl.value) return
  const link = document.createElement('a')
  link.href = qrDataUrl.value
  link.download = `QR-Booking-${code.value}.png`
  link.click()
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
      <Card class="p-0 overflow-hidden">
        <!-- Success Header -->
        <div
          class="p-8 text-center text-white"
          :style="{ backgroundColor: lab?.branding.primary_color ?? '#1a1a2e' }"
        >
          <div
            class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-4"
          >
            <Check class="size-8" />
          </div>
          <h1 class="text-2xl font-bold">Booking Berhasil!</h1>
          <p class="text-white/70 mt-1">Pesanan kamu di {{ lab?.name }} telah dikonfirmasi</p>
        </div>

        <CardContent class="p-8 text-center">
          <p class="text-sm text-gray-500 mb-2">Kode Booking</p>
          <div class="bg-gray-50 rounded-xl p-3 mb-6 inline-block">
            <p class="text-xl font-mono font-bold text-gray-900 tracking-wider">
              {{ code }}
            </p>
          </div>

          <!-- Belum bayar -->
          <template v-if="!isPaid">
            <div
              class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 flex items-start gap-3 text-left"
            >
              <Clock class="size-5 text-yellow-600 shrink-0 mt-0.5" />
              <div>
                <p class="text-sm text-yellow-800 font-medium">Menunggu Pembayaran</p>
                <p class="text-xs text-yellow-700 mt-1">
                  Selesaikan pembayaran untuk mendapatkan QR Code check-in.
                </p>
              </div>
            </div>

            <Button
              class="w-full mb-3"
              size="lg"
              :style="{ backgroundColor: lab?.branding.secondary_color ?? '#e94560' }"
              :disabled="isPaying"
              @click="payNow"
            >
              <Wallet class="size-4" />
              {{ isPaying ? 'Memproses...' : 'Bayar Sekarang' }}
            </Button>
          </template>

          <!-- Sudah lunas -->
          <template v-else>
            <div class="flex justify-center mb-4">
              <div class="bg-white p-3 rounded-xl border border-gray-200 inline-block">
                <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Code Booking" class="w-48 h-48" />
                <Skeleton v-else class="w-48 h-48 rounded" />
              </div>
            </div>

            <p class="text-sm text-gray-500 mb-4">
              Tunjukkan QR Code ini ke admin saat datang ke lokasi untuk check-in otomatis.
            </p>

            <Button
              variant="outline"
              class="w-full mb-6"
              :disabled="!qrDataUrl"
              @click="downloadQR"
            >
              <Download class="size-4" />
              Download QR Code
            </Button>
          </template>

          <div class="flex flex-col gap-3">
            <Button variant="outline" class="w-full" @click="goToBookings">
              <ListChecks class="size-4" />
              {{ isStaffMode ? 'Kembali ke Daftar Booking' : 'Lihat Semua Booking' }}
            </Button>
            <Button variant="outline" class="w-full" @click="router.push(`/booking/${slug}`)">
              <RotateCcw class="size-4" />
              {{ isStaffMode ? 'Buat Booking Lain' : 'Booking Lagi' }}
            </Button>
            <Button variant="ghost" class="w-full" @click="goHome">
              <component :is="isStaffMode ? LayoutDashboard : Home" class="size-4" />
              {{ isStaffMode ? 'Kembali ke Dashboard Lab' : 'Kembali ke Beranda' }}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
